<?php

namespace app\modules\api\controllers;
use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WorkflowParse;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\models\FzrbsBudgetDict;
use app\modules\api\models\FzrbsCompany;
use app\modules\api\models\WeixinOaApprovalLog;
use app\modules\api\models\WeixinUsesealApprovaldata;
use app\modules\api\models\WeixinUsesealInfo;
use app\modules\api\models\WeixinUsesealApprovalLog;
use app\modules\api\models\WeixinOaAttachment;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinOaNotifyLog;
use app\modules\api\models\WeixinOaRole;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\models\WeixinOaUsertag;
use app\modules\api\models\WeixinOauserTaguser;
use app\modules\api\models\WeixinUsesealNotifyLog;
use app\modules\api\models\WeixinUsesealTemplate;
use Exception;
use yii\db\Expression;



class QyusesealController extends ApiBase{
  public $modelClass = 'app\modules\api\models\WeixinOAUserInfo';
  protected $agentId= 1000065;
  protected $TEMPLATE = '3596b8b82d9eb42225237f65c14b8c80_1680076143';
  protected $statusCn = array('','审批中','已同意','已驳回','已取消');
  protected $userinfo = array();

  public function init()
  {
      parent::init();
      $this->userinfo = $this->getUserinfo($this->_adminInfo['wxuserid']);
  }

  private function getThirdNo()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime =  substr(sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000).strtolower($this->_adminInfo['wxuserid']),0,20);
        return $msectime;
	}

  public function actionFlowact(){
    
    $postdatas = $this->_request;
    if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空');
    if (!$postdatas['act']) return array('errorMessage'=>'act为空,可选：agree,cancel,reject');
    
    switch ($postdatas['act']) {
      case 'agree':
        return $this->actionAgree();
      case 'cancel':
        return $this->actionCancel();
      case 'reject':
        return $this->actionReject();
      case 'urge':
        return $this->actionUrge();
      case 'alter':
        return $this->actionAlterApprover();
      default:
        # code...
        break;
    }
  }
  public function actionAlterApprover(){
  
    
		$thirdNo = $this->_request['thirdNo'];
		$step = $this->_request['step'];
		$userid = $this->_request['userid'];
    if (!$userid){
      return array('errorMessage'=>"userid 不能为空$userid $thirdNo");
    }
		if (!$thirdNo){
      return array('errorMessage'=>'thirdNo 不能为空');
		}
		if(!isset($step)){
      return array('errorMessage'=>'step 不能为空');
		}
		$user = $this->getUserinfo($userid);	//获取用户信息
		if (!$user){
      return array('errorMessage'=>'userid：['.$userid.']不存在');
		}

    // 只有当前审批人才能转审
    $data = WeixinUsesealInfo::find()->where(['and',['=','thirdNo',$thirdNo]])->asArray()->one();

    // 是否是当前审批人
    if ($data['approvalUserid'] && !in_array($this->userinfo['userid'],explode('|',$data['approvalUserid']))){
      return array('errorMessage'=>'只有当前审批人可转审');
    }

    $flow = WeixinUsesealApprovaldata::find()->where(['thirdNo'=>$thirdNo])->one();
		$flowdata = json_decode($flow['data'],true);
		$curstep = $flow['step'];
		$node = $flowdata['data']['ApprovalNodes']['ApprovalNode'][$step];
		$item = array(
			'ItemName' => $user['name'],
			'ItemParty' => '',
			'ItemImage' => $user['avatar'],
			'ItemUserId' => $user['userid'],
			'ItemStatus' => 1,
			'ItemSpeech' => '',
			'ItemOpTime' => 0
		);
		$node['Items']['Item'] = array($item);
    $curuser = $this->getUserinfo($this->_adminInfo['wxuserid']);
    // 修改人
    $node['FromUserid'] = $curuser['userid'];
    $node['FromUsername'] = $curuser['name'];

		$flowdata['data']['ApprovalNodes']['ApprovalNode'][$step]=$node;
		$flow['data'] = json_encode($flowdata);
    
    $transaction = Yii::$app->db->beginTransaction();
    try {
      $flow->save();
      // 判断是否是当前审批步骤
      if ($curstep==$step){
        // 修改当前审批人

        $data['approvalUserid'] = $user['userid'];
        $data['approvalUsername'] = $user['name'];
        WeixinUsesealInfo::updateAll($data,['id'=>$data['id']]);

        $this->send($curuser['userid'].'|'.$user['userid'],'【转审】'.$data['userName']."的用印申请",$data);
        


      }
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=> $th->getMessage());
    }
    $transaction->commit();

		
		return array('ret'=>1);
  }
  public function actionReject(){//驳回
    $userid = $this->_adminInfo['wxuserid'];
    $postdatas = $this->_request;
    if (!$postdatas['speech']) return array('errorMessage'=>'审批意见不能为空');
    $data = WeixinUsesealInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']]])->asArray()->one();

    // 是否是当前审批人
    if ($data['approvalUserid'] && !in_array($userid,explode('|',$data['approvalUserid']))){
      return array('errorMessage'=>'当前审批人是：'.$data['approvalUsername']);
    }
    
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      $status=3;
      $ret = $this->changeStatus($userid,$data['id'],$postdatas['thirdNo'],$status,$postdatas);
      $this->updateAfterFlowChange($ret,$postdatas['thirdNo'],$status,$postdatas);
      
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    
    return array('data'=>array('ret'=>1));
  }
  public function actionAgree(){
    
    $postdatas = $this->_request;
   
    $userid = $this->_adminInfo['wxuserid'];
    
    if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空');
    $data = WeixinUsesealInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']]])->asArray()->one();

    // 是否是当前审批人
    if ($data['approvalUserid'] && !in_array($userid,explode('|',$data['approvalUserid']))){
      return array('errorMessage'=>'当前审批人是：'.$data['approvalUsername']);
    }
    
    $status = 2;

    try {
      $ret = $this->changeStatus($userid,$data['id'],$postdatas['thirdNo'],$status,$postdatas);
      
      
      $this->updateAfterFlowChange($ret,$postdatas['thirdNo'],$status,$postdatas);


		
    } catch (\Throwable $th) {
      
      return array('errorMessage'=>$th->getMessage());
      // throw $th;
    }
    
    
    return array('data'=>$ret);
  }
  public function actionUrge(){
    $thirdNo=$this->_request['thirdNo'];
    if (!$thirdNo) return array('errorMessage'=>'thirdNo为空');

      $data = WeixinUsesealInfo::find()->where(array('thirdNo'=>$thirdNo))->one();

      try {
        $this->send($data['approvalUserid'],'【催办】您有流程要审批!',$data);
      } catch (\Throwable $th) {
        return array('errorMessage'=>$th->getMessage());
      }
      return array('data'=>'催办成功');

  }
  public function actionCancel(){//撤消


      $userid = $this->_adminInfo['wxuserid'];
      $postdatas = $this->_request;
      if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空');
      
      $data = WeixinUsesealInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']]])->one();

      // 只能本人撤消
      if ($data['userId']!=$userid){
        return array('errorMessage'=>'只能本人撤销');
      }


      $transaction = Yii::$app->getDb()->beginTransaction();
      try {
        $status=4;
        $ret = $this->changeStatus($userid,$data['id'],$postdatas['thirdNo'],$status,$postdatas);
        $this->updateAfterFlowChange($ret,$postdatas['thirdNo'],$status,$postdatas);
        
      } catch (\Throwable $th) {
        $transaction->rollBack();
        return array('errorMessage'=>$th->getMessage());
      }
      $transaction->commit();

      return array('data'=>array('ret'=>1));
	
	}
  private function updateAfterFlowChange($ret,$thirdNo,$status,$condition){
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      

      
      $project = WeixinUsesealInfo::find()->where(['and',['thirdNo'=>$thirdNo]])->asArray()->one();
      if (!$project) throw new Exception("找不到流程信息");
      if ($ret){
        if($ret['approveres']){
          WeixinUsesealApprovaldata::updateAll($ret['approveres'],'id='.$ret['approveres']['id']);
        }
        if($ret['logdata']){
            unset($ret['logdata']['agentid']);
            $log = new WeixinUsesealApprovalLog($ret['logdata']);
            $log->save();
        }
        
    
        $temp = ['status'=>$status];
        if($ret['isfinish']) {//没有下个审批人,可能是会签，直接结束
          // 更新流程信息表
          $msg = "【已同意】";
          $noticeuserids=$project['userId'];
          if($ret['tonotify']){
        
            foreach($ret['tonotify']['userid'] as $k=>$v){
              $this->setNotifylog(array('thirdNo'=>$thirdNo,'userId'=>$v,'userName'=>$ret['tonotify']['username'][$k]));
            }
            $this->send(implode('|',$ret['tonotify']['userid']),'【抄送】'.$project['userName'].'的用印申请',$project['data']);
          }

        }else if ($ret['nextdata']&&$ret['nextdata']['approvalUserid']) { // 有下个审批人，说明没结束
          if (!$ret['isfinish']) {
            $status = 1;
          }
          $temp = ['status'=>$status,'approvalUserid'=>$ret['nextdata']['approvalUserid'],'approvalUsername'=>$ret['nextdata']['approvalUsername']];
          $noticeuserids=$ret['nextdata']['approvalUserid'];
          if ($condition['notNotice']&&$noticeuserids){
            $noticeuserids=implode('|',array_diff(explode('|',$noticeuserids),explode('|',$condition['notNotice'])));
          }
          $msg = "【待办】";

        }else {
          
          if (!$ret['isfinish']) { // 可能是会签
            $status = 1;
            $temp['status']=$status;
          }
        

        }
      }
      switch ($condition['act']) {
        case 'cancel':
          $msg='【撤销】';
          $toUser = array();
          $toUser[] = $project['userId'];
          $log = WeixinUsesealApprovalLog::findBySql("select userId from ".WeixinUsesealApprovalLog::tableName()." where thirdNo ='$thirdNo'")->all();
          foreach($log as $r){
            if(!in_array($r['userId'],$toUser)){
              $toUser[] = $r['userId'];
            }
          }
          $noticeuserids=implode('|',$toUser);
          $temp['status']=4;
          break;
        case 'reject':
          $msg='【驳回】';
          $noticeuserids=$project['userId'];
          $temp['status']=3;
          break;
        default:
          break;
      }

      WeixinUsesealInfo::updateAll($temp,["thirdNo"=>$thirdNo]);
      

    } catch (\Throwable $th) {
      $transaction->rollBack();
      throw $th;
    }
    $transaction->commit();
    $this->send($noticeuserids,$msg.$project['userName'].'的用印申请',$project);
 

  }
 
  private function changeStatus($userid,$id,$thirdNo,$status,$condition=array()){

    $ret = 0;
    $condition['agentId']=$this->agentId;
    $condition['userid']=$userid;
    $condition['status']=$status;
    $condition['thirdNo']=$thirdNo;

    $ret = $this->flowChange($condition);
    
    

    return $ret;
  }
  public function flowChange($par)
  {
       
        
      $agentid =$par['agentId']?$par['agentId']:$this->agentId;
      $thirdNo=$par['thirdNo'];
      $userid=$par['userid'];
      $status=$par['status'];
      $speech=$par['speech'];
      $act=$par['act'];

      if (!$thirdNo) throw new Exception('thirdNo 不能为空');
      $approveres = WeixinUsesealApprovaldata::find()->where(['and',["=","agentid",$agentid],["=","thirdNo",$thirdNo]])->asArray()->one();
      
      
      if($approveres){
        $optime = time();
        $ret = ['isfinish'=>0];
        $nextdata = [];
        $approvedata = [];
        $step = intval($approveres['step']);
        $approvearr = json_decode($approveres['data'],true);
      
        
     
        if(in_array($status,[3,4]) || ($status==2 && $step==count($approvearr['data']['ApprovalNodes']['ApprovalNode'])-1&&$approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeAttr']!=2)){//不是会签
          
          $approvearr['data']['OpenSpstatus'] = $status;
          $nextdata['status'] = $status;
          $approvedata['status'] = $status;

          if($status==2){
            $ret['isfinish']=1;
          }

          if($approveres['notifyAttr']>1){
            $notifyUserid = array();
            $notifyUsername = array();
            foreach($approvearr['data']['NotifyNodes']['NotifyNode'] as $notify){
              $notifyUserid[] = $notify['ItemUserId'];
              $notifyUsername[] = $notify['ItemName'];
            }
            $ret['tonotify'] = array('userid'=>$notifyUserid,'username'=>$notifyUsername);
          }
        }
        
        if(in_array($status,[2,3])){
          $logdata = array(
            'agentid'=>$agentid,
            'thirdNo'=>$thirdNo,
            'userId'=>$userid,
            'status'=>$status,
            'speech'=>$speech,
            'opTime'=>$optime
          );

          foreach($approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'] as $k=>$item){
            if($item['ItemUserId'] == $userid){
              $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeStatus'] = $status;

              $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'][$k]['ItemStatus'] = $status;
              
              $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'][$k]['ItemSpeech'] = $speech;
              $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'][$k]['ItemOpTime'] = $optime;
              $logdata['userName'] = $item['ItemName'];
              
            }
          }
        }
        $isNext = true;
        foreach($approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'] as $k=>$item){
          // 当前审批人无法执行，因为ItemStatus=2
          // 会签节点审批后不会通知同级审批人
          if($item['ItemStatus']==1 && $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeAttr']==2){//是会签节点 
          
            $isNext = false;
            if($status==2)$approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeStatus'] = 1;
          }
          
        }
        if (!$isNext) $status = 1;



        if($status==2 && $isNext){
          if ($step==count($approvearr['data']['ApprovalNodes']['ApprovalNode'])-1){
            $approvearr['data']['OpenSpstatus'] = $status;
            $nextdata['status'] = $status;
            $approvedata['status'] = $status;

            $ret['isfinish']=1;

            if($approveres['notifyAttr']>1){
              $notifyUserid = array();
              $notifyUsername = array();
              foreach($approvearr['data']['NotifyNodes']['NotifyNode'] as $notify){
                $notifyUserid[] = $notify['ItemUserId'];
                $notifyUsername[] = $notify['ItemName'];
              }
              $ret['tonotify'] = array('userid'=>$notifyUserid,'username'=>$notifyUsername);
            }
          }
          
          if($step<count($approvearr['data']['ApprovalNodes']['ApprovalNode'])-1)$step++;
          $approvearr['data']['approverstep'] = $step;
          $approvalUserid = array();
          $approvalUsername = array();
          foreach($approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'] as $k=>$item){
            if ($item['ItemStatus']==1){
              $approvalUserid[] = $item['ItemUserId'];
              $approvalUsername[] = $item['ItemName'];
            }
            
          }
          $nextdata['approvalUserid'] = implode('|',$approvalUserid);
          $nextdata['approvalUsername'] = implode('|',$approvalUsername);
          $nextdata['approvalStep'] = $step;
          $ret['touser'] = $nextdata['approvalUserid'];
         
        }
       
        if($nextdata){
            $ret['nextdata'] = $nextdata;
        }
        
        
        $approvedata['data'] = json_encode($approvearr);
        $approvedata['step'] = $step;
        
        $approveres['status'] = $status;
        $approveres['data'] = $approvedata['data'];
        $approveres['step'] = $approvedata['step'];
        

        $ret['approveres']=$approveres;
        $ret['logdata']=$logdata;
      
        return $ret;
      }
      

		return 0;
  }

  public function actionGetdata(){
    $thirdNo = $this->_request['thirdNo'];
    $data = WeixinUsesealInfo::find()->where(['and',['thirdNo'=>$thirdNo]])->one();
    if(!$data){
      return array('errorMessage'=>'数据不存在');
    }
    return array('info'=>$data);
  }

  private function getTemplate($data,$userinfo){//获取用印审批流程模版
    if (!$data['flowtype']) $data['flowtype'] = 0;
		if($userinfo){
			$where = in_array($data['usesealType'],array(1,2))? ' and amounts='. $data['amountsType']:'';
		
      $template = WeixinUsesealTemplate::findBySql("select * from weixin_useseal_template where flowtype=". $data['flowtype']." and type=". $data['usesealType']."$where and FIND_IN_SET('".$userinfo['id']."',uids)")->one();

			if($template)return $template;
		

      $template = WeixinUsesealTemplate::findBySql("select * from weixin_useseal_template where flowtype=". $data['flowtype']." and type=" . $data['usesealType'] . "$where and FIND_IN_SET('".$userinfo['departmentid']."',dids)")->one();
			if($template)return $template;
		}
		return false;
	}
  public function actionSave(){
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }
    $result = array();
    if ($obj['thirdNo']){
      $data = WeixinUsesealInfo::find()->where(['and',['thirdNo'=>$obj['thirdNo']]])->one();
      WeixinUsesealInfo::updateAll($obj,['thirdNo'=>$obj['thirdNo']]);
      
      $result = $obj;

    } else {
      
 
       $obj['thirdNo']=$obj['thirdNo']?$obj['thirdNo']:$this->getThirdNo();
        // 判断 是否已经存在
        $temp = WeixinUsesealInfo::find()->where(['thirdNo'=>$obj['thirdNo']])->one();
        if  ($temp) {
          return array('errorMessage'=>'thirdNo【'.$obj['thirdNo'].'】已经存在');
        }

        $obj['userId']=$this->userinfo['userid'];
        $obj['userName']=$this->userinfo['name'];
        $obj['departmentid']=$this->userinfo['departmentid'];
        $obj['department']=$this->userinfo['departmentname'];
        $data = $obj;
  



        // 启动流程

        try {
          $flow = $this->getflow($obj);
        } catch (\Throwable $th) {
          return array('errorMessage'=>$th->getMessage());
        }
        
        $usesealflow = array(
          'errcode' => 0,
          'errmsg' => 'ok',
          'data' => array(
            'ThirdNo' => $obj['thirdNo'],
            'OpenTemplateId' => $flow['OpenTemplateId'],
            'OpenSpName' => $flow['OpenSpName'],
            'OpenSpstatus' => 1,
            'ApplyTime' => time(),
            'ApplyUsername' => $this->userinfo['name'],
            'ApplyUserParty' => '',
            'ApplyUserImage' => $this->userinfo['avatar'],
            'ApplyUserId' => $this->userinfo['userid'],
            'ApprovalNodes' => $flow['ApprovalNodes'],
            'NotifyNodes' => $flow['NotifyNodes'],
            'approverstep' => 0
          )
        );

        $applydata = array(
          'agentid' => $this->agentId,
          'thirdNo' => ''.$obj['thirdNo'],
          'data' => json_encode($usesealflow),
          'step' => 0,
          'status' => 1,
          'notifyAttr' => $flow['NotifyAttr']
        );
     
        $approvalUserid = array();
        $approvalUsername = array();
        foreach ($flow['ApprovalNodes']['ApprovalNode'][0]['Items']['Item'] as $item) {
          $approvalUserid[] = $item['ItemUserId'];
          $approvalUsername[] = $item['ItemName'];
        }
        $data['approvalUserid'] = implode('|', $approvalUserid);
        $data['approvalUsername'] = implode('|', $approvalUsername);
        $data['status'] = 1;

        $transaction = Yii::$app->getDb()->beginTransaction();

        try {
          // 基本信息
          $data = new WeixinUsesealInfo($data);
          $data->save();
          $applydata = new WeixinUsesealApprovaldata($applydata);
          $applydata->save();
          

        } catch (\Throwable $th) {
          $transaction->rollBack();
          return array('errorMessage'=>$th->getMessage());
        }
        $transaction->commit();

        $touser1 = [$data['approvalUserid'],$data['userId']];
      
        $touserstr = implode('|', $touser1);
      
        $this->send($touserstr,'【提交】'.$data['userName'].'的用印申请',$obj);
        $result['thirdNo']=$obj['thirdNo'];

        if (intval($flow['NotifyAttr']) == 1 || intval($flow['NotifyAttr']) == 3) {
          $notifyUserid = array();
          foreach ($flow['NotifyNodes']['NotifyNode'] as $notify) {
            $notifyUserid[] = $notify['ItemUserId'];
            $this->setNotifylog(array('thirdNo' => $obj['thirdNo'], 'userId' => $notify['ItemUserId'], 'userName' => $notify['ItemName']));
          }
          if (count($notifyUserid) > 0) {
            $tonotify = implode('|', $notifyUserid);
            $this->send($tonotify,'【抄送】'.$data['userName'].'的用印申请',$obj);
          }
        }
    }
    
    return $result;
  }
  private function setNotifylog($data){//保存抄送人信息
		if($data){
      $res = WeixinUsesealNotifyLog::find()->where(['thirdNo'=>$data['thirdNo'],'userId'=>$data['userId']])->one();
			if(!$res){
        $data = new WeixinUsesealNotifyLog($data);
				$data->save();
			}
		}
	}
  public function actionSavefiles(){ 
    $obj = $this->_request['obj'];
    // $obj不能为空且必须是数组
    if (!$obj || !is_array($obj)){
      return array('errorMessage'=>'obj不能为空且必须是数组');
    }
    $ids=[];
    // 开启事物
    $transaction = Yii::$app->db->beginTransaction();
    try {
      foreach ($obj as $e) {



        $temp = new WeixinOaAttachment();
        $temp->userId=$this->userinfo['userid'];
        $temp->appId = $this->agentId;
        $temp->baseName=$e['originalName'];
        $temp->saveAs=$e['url'];
        $temp->savePath=$e['url'];
        $temp->fileType=str_replace('.', '', $e['type']);
        $temp->fileSize=$e['size'];
        $temp->save();
        $ids[]=$temp->id;

      }
    } catch (\Throwable $th) {
      // 回滚事务
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    // 提交事务
    $transaction->commit(); 
    return array('ids'=>$ids);
    
  }

  private function getTagName($tagid) {
    $t = WeixinOauserTaguser::findOne($tagid);
		return $t?$t['tagName']:'';
	}
  private function getRoleName($id)
    {
        $tagdata = '审批组';
        if($id){
            $temp = WeixinOaRole::findOne($id);
            if ($temp) $tagdata = $temp['rolename'];
        }      
        return $tagdata;
    }
  // 根据数据查询流程
	public function actionGetflow() {
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }
    try {
      $flow = $this->getflow($obj);
      
    } catch (\Throwable $th) {
      return array('errorMessage'=> $th->getMessage());
    }
 
    if($flow){
        foreach ($flow['ApprovalNodes']['ApprovalNode'] as $k=>$r) {
            $tmparr = array();
            if(count($r['Items']['Item'])>1){
              $tmparr['title'] = '直接上级';
              if ($r['NodeType']==2 && isset($r['NodeTagid'])){
                $tmparr['title']=$this->getTagName($r['NodeTagid']);
              }else if ($r['NodeType']==0 && isset($r['NodeRoleid'])){
                  $tmparr['title'] = $this->getRoleName($r['NodeRoleid']);
              }
                $tmparr['avatar'] = 'https://fzrb.fznews.com.cn/assets/oa/images/approvaltag.png';
                foreach ($r['Items']['Item'] as $key => $value) {
                    $itemarr = array();
                    $itemarr['title'] = $value['ItemName'];
                    $itemarr['date'] = intval($value['ItemOpTime']) > 0?date('m/d',$value['ItemOpTime']):'';
                    $itemarr['avatar'] = $value['ItemImage'];
                    $itemarr['speech'] = $value['ItemSpeech'];
                    $itemarr['status'] = $value['ItemStatus'];
                    $tmparr['items'][] = $itemarr;
                }
            }else{
                $tmparr['title'] = $r['Items']['Item'][0]['ItemName'];
                $tmparr['date'] = $r['Items']['Item'][0]['ItemOpTime']?date('m/d',$r['Items']['Item'][0]['ItemOpTime']):'';
                $tmparr['avatar'] = $r['Items']['Item'][0]['ItemImage'];
                $tmparr['speech'] = $r['Items']['Item'][0]['ItemSpeech'];
                $tmparr['status'] = $r['NodeStatus'];
                $tmparr['items'] = '';
            }
            $approvaldata[] = $tmparr;
        }
        $notifier = array();
        foreach ($flow['NotifyNodes']['NotifyNode'] as $r) {
            $notifier[] = $r['ItemName'];
        }
        $step = intval($flow['approverstep'])-1;

    }


    if($this->_request['print']){
        $approvalNode= $flow['ApprovalNodes']['ApprovalNode'];
        $printinfo = $this->getPrintInfo($approvalNode,true,$obj)['flowdata'];
    }
    
    return  array('viewdata'=>array('step'=>$step,'approval'=>$approvaldata,'notify'=>$notifier,'templateid'=>$flow['OpenTemplateId']),'statusCn'=>$this->statusCn,'printinfo'=>$printinfo);
	}
  private function getflow($data){
  
    $userid = $this->_adminInfo['wxuserid'];
    if ($data['userid']){
      $userid = $data['userid'];
    }else if ($data['userId']){
      $userid = $data['userId'];
    }

    $template_userid=$userid;
		$template_userinfo=$this->getUserinfo($userid);

	

  
    $template = $this->getTemplate($data, $template_userinfo);	

    if (!$template) {
      $temp1 = FzrbsBudgetDict::find()->where(['and',['=', 'value', $data['usesealType']],['=', 'type', '用印申请类别']])->asArray()->one();
      $temp2 = FzrbsBudgetDict::find()->where(['and',['=', 'value', $data['amountsType']],['=', 'type', '用印协议金额']])->asArray()->one();

      throw new Exception('申请失败,无模版,部门【'.$template_userinfo['departmentname'].'】,申请类别【'.$temp1['label'].'】，协议金额【'.$temp2['label'].'】');
    }

    //解析审批流程数据
		
		$wfp = new WorkflowParse($this->agentId);
		$ret = $wfp->flowParse($template_userid, $template['templateid']);
    
    // 在 $ret['ApprovalNodes']['ApprovalNode'] 前面插入一个测试节点
    // $item = array(
		// 		'ItemName' => $this->userinfo['name'],
		// 		'ItemImage' => $this->userinfo['avatar'],
		// 		'ItemUserId' => $this->userinfo['userid'],
		// 		'ItemStatus' => 1,
		// 		'ItemOpTime' => 0
		// 	);
		// 	array_unshift($ret['ApprovalNodes']['ApprovalNode'],array(
		// 		'NodeStatus' => 1,
		// 		'Items' => array('Item'=>array($item)),
		// 		'NodeAttr' => 1
		// 	));

   
    
    return $ret;
  }

  public function actionGetpapers(){
    return [array('value'=>24,'name'=>'日报'),array('value'=>25,'name'=>'晚报')];
  }
  public function actionFindpersononduty(){
    $company = $this->_request['company'];
		$ret = $this->findDuty($company);
    return $ret;
		
	}
	private function findDuty($company) {
		
    $leaders=WeixinOaFlowrole::findBySql("SELECT  userid,username,userid as value,username as name  from ".WeixinOaFlowrole::tableName()." where FIND_IN_SET(".$this->agentId.",agent)  and company=".$company." and role=".$this->LeaderRoleid)->asArray()->all();
		$directors = WeixinOaFlowrole::findBySql("SELECT  userid,username,userid as value,username as name from ".WeixinOaFlowrole::tableName()." where FIND_IN_SET(".$this->agentId.",agent) and company=".$company." and role=".$this->DirectorRoleid)->asArray()->all();
		return array('leaders'=>$leaders,'directors'=>$directors);
	}
  public function actionGetflowdata(){
    $thirdNo = $this->_request['thirdNo'];
    if (!$thirdNo){
      return array('errorMessage'=>'thirdNo不能为空');
    }
    
    $viewdata=0;

    try {
      // 流程数据
      $info= WeixinUsesealInfo::find()->alias('i')->select('u.avatar as avatar,u.mobile as mobile,d1.label as usesealTypeName,d2.label as amountsTypeName,i.*')->where(['and',['thirdNo'=>$thirdNo]])
      ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=i.userId')
      ->leftJoin(['d1'=>FzrbsBudgetDict::tableName()],"d1.value=i.usesealType and d1.type='用印申请类别'")
      ->leftJoin(['d2'=>FzrbsBudgetDict::tableName()],"d2.value=i.amountsType and d2.type='用印协议金额'")
      ->asArray()->one();
      
      $info['annex']=$this->getFileurlByids($info['annex']);
      // 申请表信息
     
      $viewdata = $this->flowViewdata($thirdNo);


    } catch (\Throwable $th) {
      return array('errorMessage'=> $th->getMessage());
    }
    
    return array('viewdata'=>$viewdata,'info'=>$info,'statusCn'=>$this->statusCn);

  }
  public function flowViewdata($thirdNo)
    {
        $approvaldata = array();
        $approvedata = WeixinUsesealApprovaldata::find()->where(['and',["=","thirdNo",$thirdNo]])->one();
        if($approvedata){
            $approvearr = json_decode($approvedata['data'],true);
            foreach ($approvearr['data']['ApprovalNodes']['ApprovalNode'] as $k=>$r) {
                $tmparr = $r;
                if(count($r['Items']['Item'])>1){
                    // $tmparr['title'] = '直接上级';
                    $tmparr['NodeAttr'] = $r['NodeAttr'];
                    if ($r['NodeType']==2 && isset($r['NodeTagid'])){
                      // $tmparr['title'] = $this->getUserTagName($r['NodeTagid']);
                    }else if ($r['NodeType']==0 && isset($r['NodeRoleid'])){
                        // $tmparr['title'] = $this->getRoleName($r['NodeRoleid']);
                    }
                    $tmparr['avatar'] = 'https://fzrb.fznews.com.cn/assets/oa/images/approvaltag.png';
                    foreach ($r['Items']['Item'] as $key => $value) {
                        $itemarr = array();
                        $itemarr['title'] = $value['ItemName'];
                        $itemarr['date'] = intval($value['ItemOpTime']) > 0?date('m/d',$value['ItemOpTime']):'';
                        $itemarr['avatar'] = $value['ItemImage'];
                        $itemarr['speech'] = $value['ItemSpeech'];
           
                        $itemarr['status'] = $value['ItemStatus'];
                        $tmparr['items'][] = $itemarr;
                    }
                   
                }else{
                    $tmparr['title'] = $r['Items']['Item'][0]['ItemName'];
                    $tmparr['date'] = $r['Items']['Item'][0]['ItemOpTime']?date('m/d',$r['Items']['Item'][0]['ItemOpTime']):'';
                    $tmparr['avatar'] = $r['Items']['Item'][0]['ItemImage'];
                    $tmparr['speech'] = $r['Items']['Item'][0]['ItemSpeech'];
                    $tmparr['status'] = $r['NodeStatus'];
                    $tmparr['items'] = '';
                }
                $approvaldata[] = $tmparr;
            }
            $notifier = array();
            $notifierUserid = array();
            foreach ($approvearr['data']['NotifyNodes']['NotifyNode'] as $r) {
                $notifier[] = $r['ItemName'];
                $notifierUserid[] = $r['ItemUserId'];
            }
            $step = intval($approvearr['data']['approverstep'])-1;
            return array('step'=>$step,'approval'=>$approvaldata,'notify'=>$notifier,'notifierUserid'=>$notifierUserid);
        }
        return 0;
    }
    /**
     * 获取标签名
     */
    private function getUserTagName($id)
    {
        if($id){
            $tagdata = WeixinOaUsertag::findOne($id);
        }else{
            $tagdata = '审批组';
        }        
        return $tagdata;
    }
  
  public function actionViewpic() {
    $thirdNo = $this->_request['thirdNo'];
    if (!$thirdNo) {
      return array('errorMessage'=> 'thirdNo不能为空');
    }
		
  

    $data = WeixinUsesealInfo::find()->where("thirdNo ='$thirdNo'")->asArray()->one();
    $temp = json_decode($data['data'], true);
    $data['inserttime'] = str_replace('-','/',$data['inserttime']);
    $data['date']=$temp['date'];
    $data['time']=$temp['time'];
    $data['layout'] = $temp['layout'];
    $data['reason'] = $temp['reason'];
    $data['notice'] = '注：以上延迟版面符合榕报【2021】60号编印发规定的第五项：特殊免责条款，可申请免于处罚，特此申请。';
		$approvedata = WeixinUsesealApprovaldata::find()->where( "agentid=".$this->agentId." and thirdNo ='$thirdNo'")->asArray()->one();
    $flowdata = array();
    $speeches = array(); // 纪录备注

   
    if ($data && $approvedata) {
      $approvearr = json_decode($approvedata['data'], true);
      $temp = $this->getPrintInfo($approvearr['data']['ApprovalNodes']['ApprovalNode'],false,$data);
      $flowdata = $temp['flowdata'];
      $speeches = $temp['speeches'];
      
    }

	
			return array('data' => $data,'flowdata' => $flowdata,'speeches'=>$speeches);
 
	}
  private function getPrintInfo($approvalNode,$preview=true,$data){
    
    $flowdata = array();
    foreach ($approvalNode as $k => $r) {
      
      if ($r['NodeStatus'] != 1) {
						$tmparr = array();
						// 要判断一下每个审批人对应的位置 array(0=>'直接上级或第二上级',1=>"第三上级",2=>"社办主任",3=>会计，4=>财务主管,5=>分管或常务,6=>社长)
						$nodetype = $r['NodeType'];
						// 如果是或签
						if($r['NodeAttr']==1 && count($r['Items']['Item']) > 1) {
							foreach ($r['Items']['Item'] as $key => $item) {
								if ($item['ItemStatus'] == 2) {
									$tmparr['title'] = $item['ItemName'];
									$tmparr['userid'] = $item['ItemUserId'];
									$tmparr['date'] = $item['ItemOpTime'] ? date('m/d', $item['ItemOpTime']) : '';
									$tmparr['speech'] = $item['ItemSpeech'];
									break;
								}
							}
						} else {
							$tmparr['title'] = $r['Items']['Item'][0]['ItemName'];
							$tmparr['userid'] = $r['Items']['Item'][0]['ItemUserId'];
							$tmparr['date'] = $r['Items']['Item'][0]['ItemOpTime'] ? date('m/d', $r['Items']['Item'][0]['ItemOpTime']) : '';
							$tmparr['speech'] = $r['Items']['Item'][0]['ItemSpeech'];
						}
						if ($tmparr['speech']) $speeches[] = $tmparr['title']."：".$tmparr['speech'];
						if (strpos($tmparr['speech'],'无须我审批') === false) {
							switch ($nodetype) {
								case 0: // 角色
									if ($r['NodeRoleid'] == 7) $flowdata[0] = $tmparr; // 常务副总编
									if ($r['NodeRoleid'] == 8) $flowdata[1] = $tmparr; // 总编
									if ($r['NodeRoleid'] == 9) $flowdata[2] = $tmparr; // 社长
									if ($r['NodeRoleid'] == 17) $flowdata[4] = $tmparr; // 值班领导
									if ($r['NodeRoleid'] == 18) $flowdata[3] = $tmparr; // 值班主任
									break;
								default:
									break;
							}
						}
					}

    }

    return array('flowdata'=>$flowdata,'speeches'=>$speeches);
		
  }
  public function getFileurlByids($ids){
    // 判断是否仅包含数字和
    if(!preg_match('/^\d+(,\d+)*$/', $ids)){
      return $ids;
    }
    $datas = WeixinOaAttachment::find()->where(['in','id',explode(',',$ids)])->all();
    
    $datas = array_map(function($e){
      $url = '';
      $pattern = '/attachment(?:\/[^\/\s"]+)+/';
      if (preg_match($pattern, $e->savePath, $matches)) {
          $url = $matches[0];
      }
      if (in_array($e->fileType,['jpg','png','gif','jpeg'])){
        $url = "http://fzrb.fznews.com.cn/index.php?r=qiyehao/attachment/view2&attachment=".$url;
      }else{
        $url = "/www/web/fzrb.fznews.com.cn/".$url;
      }

      return $url."&name=".urlencode($e->baseName)."&time=".((strtotime($e->inserttime))*1000)."&size=".$e->fileSize;
    },$datas);
    return implode(',',$datas);

  }
  public function actionList(){

    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'u.id desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    $where = [
      'and',['>', 'u.id', 0],
    ];
    if ($this->_request['userid']){
      $userid=$this->_request['userid'];
    }
    $where[] = ['u.userId' => $userid];


    if ($this->_request['status']){
      $where[] = ['u.status' => $this->_request['status']];
    }
    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression($this->getKeywordSql($keyword));
    }
    


    $model = WeixinUsesealInfo::find()->alias('u')->select("u.*,d.label as typename")
    ->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=u.usesealType and d.type='用印申请类别'")
    ->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $this->transform($res);
    return $_result;
  }
  private function transform($list){
 
    if (!$list) return $list;
    for ($i=0; $i < sizeof($list); $i++) { 
      $temp = json_decode($list[$i]['data'],true);
      $list[$i]['paper']=$temp['paper'];
      $list[$i]['layout']=$temp['layout'];
    }
    return $list;
  }

  private function getKeywordSql($keyword){
    return "u.userName like '%".$keyword."%' or u.usesealReason like '%".$keyword."%'  or u.thirdNo like '%".$keyword."%' ";
  }
  public function actionInglist(){

    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'u.id desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    
    $where = ['and',new Expression("status=1 and LOCATE('|".$userid."|',CONCAT('|',approvalUserid,'|'))")];
    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression($this->getKeywordSql($keyword));
    }
   
    $model = WeixinUsesealInfo::find()->alias('u')->select("u.*,d.label as typename")
    ->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=u.usesealType and d.type='用印申请类别'")
    ->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $res;
    return $_result;
  }
  public function actionFinishlist(){

    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'u.inserttime desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
  
    $where = ['and',new  Expression("thirdNo in (SELECT distinct thirdNo FROM ".WeixinUsesealApprovalLog::tableName()." where userId='$userid')")];
    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression($this->getKeywordSql($keyword));
    }



    $model = WeixinUsesealInfo::find()->alias('u')->select("u.*,d.label as typename")
    ->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=u.usesealType and d.type='用印申请类别'")
    ->where($where);

  
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $res;
    return $_result;
  }
  public function actionGetnotifydata(){

    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'u.id desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }

    $where = [
      'and',
      ['>', 'u.id', 0],
    ];

    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression($this->getKeywordSql($keyword));
    }

    $where[] = new Expression("thirdNo in (SELECT distinct thirdNo FROM ".WeixinUsesealNotifyLog::tableName()." where userId='$userid')");


    $model = WeixinUsesealInfo::find()->alias('u')->select("u.*,d.label as typename")
    ->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=u.usesealType and d.type='用印申请类别'")
    ->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $this->transform($res);;
    return $_result;
  }
  public function actionGettabs(){
    return array(
      'activeTab'=>1,
      'data'=>[
            array(
              'name'=>'我要申请',
              'route'=>'/useseal/add'
            ),
            array(
              'name'=>'我的审批',
              'route'=>'/useseal/index?tab=0'
            ),
            array(
              'name'=>'我的申请',
              'route'=>'/useseal/mylist?tab=3'
            )
            ]
    );
  }
  
  private function getUserinfo($userid)
  {
      $userinfo = WeixinOAUserInfo::find()->where(['=', 'userid', $userid])->asArray()->one();
      return $userinfo;
  }
  private function send($approvalUserid,$title,$data){
    $url = "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/useseal/view?thirdNo=".$data['thirdNo'];
    $temp = FzrbsBudgetDict::find()->where(['and',['=', 'value', $data['usesealType']],['=', 'type', '用印申请类别']])->asArray()->one();
    if ($temp) {
      $usesealTypeName = $temp['label'];
    }
    
    if ($data['usesealType'] == 1) {
      $temp = FzrbsBudgetDict::find()->where(['and',['=', 'value', $data['amountsType']],['=', 'type', '用印协议金额']])->asArray()->one();
      $amountsTypeName = $temp['label'];
 
    }

    if (!$approvalUserid) return;

    
    $msgdata = array(
      'touser' => $approvalUserid,
      'msgtype' => 'textcard',
      'agentid' => $this->agentId,
      'textcard' => array(
        'title' => $title,
        'description' => '<div class="normal">申请类型：' . $usesealTypeName . '</div>' . ($data['usesealType'] == 1 ? '<div class="normal">协议金额：'. $amountsTypeName .'</div>' : ''),
        'url' => $url,
        'btntxt' => '详情'
      )
    );

   
    
    $this->sendmsg($msgdata);
  }
  private function sendmsg($data)
  {
      $content = '您有一条新的审批消息，请登录掌上福州查看';
      WxQyhJk::sendMessage($data['agentid'],$data['touser'],$content,'text');
  }
}