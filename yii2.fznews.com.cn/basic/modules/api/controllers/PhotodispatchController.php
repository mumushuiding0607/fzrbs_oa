<?php

namespace app\modules\api\controllers;
use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\commons\WorkflowParse;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\models\FzrbsBudgetDict;
use app\modules\api\models\FzrbsCompany;
use app\modules\api\models\WeixinComKindsPeople;
use app\modules\api\models\WeixinFlowProcess;
use app\modules\api\models\WeixinOaApprovaldata;
use app\modules\api\models\WeixinOaApprovalLog;
use app\modules\api\models\WeixinOaAttachment;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinOaNotifyLog;
use app\modules\api\models\WeixinOaRole;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\models\WeixinOaUsertag;
use app\modules\api\models\WeixinOauserTaguser;
use app\modules\api\models\WeixinPhotographDispatch;
use app\modules\api\models\WeixinUsesealTemplate;
use Exception;
use yii\db\Expression;



class PhotodispatchController extends ApiBase{
  public $modelClass = 'app\modules\api\models\WeixinOAUserInfo';
  protected $agentId= 1000064;
  protected $statusCn = array('','审批中','任务中','已驳回','已取消','已结束');
  protected $userinfo = array();
  protected $TEMPLATE = '49cc4e2eb250dc71f284c68ab7c44207_1753168893';
  public $_addRight = ['LinCaiYun','yeyibin','yusong','BaoMingHua'];
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
      case 'finish':
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
    $data = WeixinPhotographDispatch::find()->where(['and',['=','thirdNo',$thirdNo]])->asArray()->one();

    // 是否是当前审批人
    if ($data['approvalUserid'] && !in_array($this->userinfo['userid'],explode('|',$data['approvalUserid']))){
      return array('errorMessage'=>'只有当前审批人可转审');
    }

    $flow = WeixinOaApprovaldata::find()->where(['thirdNo'=>$thirdNo])->one();
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
        WeixinPhotographDispatch::updateAll($data,['id'=>$data['id']]);

        $this->send($curuser['userid'].'|'.$user['userid'],'【转审】'.$data['userName']."的派工申请",$data);
        


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
    $data = WeixinPhotographDispatch::find()->where(['and',['=','order_no',$postdatas['thirdNo']]])->asArray()->one();

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
    $data = WeixinPhotographDispatch::find()->where(['and',['=','order_no',$postdatas['thirdNo']]])->asArray()->one();

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
    $act=$this->_request['act'];
    if (!$thirdNo) return array('errorMessage'=>'thirdNo为空');
    
    try {
       $data = WeixinPhotographDispatch::find()->where(array('order_no'=>$thirdNo))->one();
        if ($act=='finish'){
          $this->send($data['opt_userid'],'【派工评价】摄影任务已完成，请及时结束派工并评价!',$data);
        }else{
          $this->send($data['approvalUserid'],'【催办】您有流程要审批!',$data);
        }
      
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }

   
    return array('errorMessage'=>'');

  }
  public function actionCancel(){//撤消


      $userid = $this->_adminInfo['wxuserid'];
      $postdatas = $this->_request;
      if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空');
      
      $data = WeixinPhotographDispatch::find()->where(['and',['=','order_no',$postdatas['thirdNo']]])->one();

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
      

      
      $project = WeixinPhotographDispatch::find()->where(['and',['order_no'=>$thirdNo]])->asArray()->one();
      if (!$project) throw new Exception("找不到流程信息");
      $project['userName']=$project['opt_name'];
      if ($ret){
        if($ret['approveres']){
          WeixinOaApprovaldata::updateAll($ret['approveres'],'id='.$ret['approveres']['id']);
        }
        if($ret['logdata']){
           
            $log = new WeixinOaApprovalLog($ret['logdata']);
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
            // 抄送
            $this->send(implode('|',$ret['tonotify']['userid']),'【抄送】'.$project['userName'].'的派工申请',$project['data']);
            // 通知记者
            $this->send($project['dispatch_userid'],'【任务】'.$project['userName'].'的派工申请',$project['data']);
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
          $log = WeixinOaApprovalLog::findBySql("select userId from ".WeixinOaApprovalLog::tableName()." where thirdNo ='$thirdNo'")->all();
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

      WeixinPhotographDispatch::updateAll($temp,["order_no"=>$thirdNo]);
      

    } catch (\Throwable $th) {
      $transaction->rollBack();
      throw $th;
    }
    $transaction->commit();
    $this->send($noticeuserids,$msg.$project['userName'].'的派工申请',$project);
 

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

  private function setNotifylog($data){//保存抄送人信息
		if($data){
			
      $res = WeixinOaNotifyLog::find()->where(['agentid'=>$this->agentId,'thirdNo'=>$data['order_no'],'userId'=>$data['userId']])->one();
			if(!$res){
        $data['agentid'] = $this->agentId;
        $temp = new WeixinOaNotifyLog($data);
        $temp->save();
				
			}
		}
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
      $approveres = WeixinOaApprovaldata::find()->where(['and',["=","agentid",$agentid],["=","thirdNo",$thirdNo]])->asArray()->one();
      
      
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
    $data = WeixinPhotographDispatch::find()->where(['and',['order_no'=>$thirdNo]])->one();
    if(!$data){
      return array('errorMessage'=>'数据不存在');
    }
    $data['begin_time']=date('Y-m-d H:i:s',$data['begin_time']);
    $data['end_time']=date('Y-m-d H:i:s',$data['end_time']);
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

  protected function handleArray($string)
    {
        $data = explode(',', $string);
        foreach ($data as $key => $value) {
            $data[$key] = $value = trim($value);
            if (empty($value)) {
                unset($data[$key]);
            }
        }
        $data = array_unique($data);
        return $data;
    }


  public function actionRate(){
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }
    if (!$obj['id']){
      return array('errorMessage'=>'id 为空');
    }
    try {
        $old=WeixinPhotographDispatch::findOne($obj['id']);
        // 判断是否是本人修改
        if ($old['userId']!=$this->_adminInfo['wxuserid']) {
          return array('errorMessage'=>'只有经办才能结束');
        }
        WeixinPhotographDispatch::updateAll($obj,['id'=>$obj['id']]);
        
        $this->send($old['dispatch_userid'],'【任务结束】'.$old['opt_name'].'的派工申请',$old);
      } catch (\Throwable $th) {
        return array('errorMessage'=>$th->getMessage());
      }
    return array('errorMessage'=>'');
  }

  public function haspower($power,$agentid,$dept,$creator){
    $userid = $this->_adminInfo['wxuserid'];
    if($this->_adminInfo['usertype']==1) return true;
    if (!$power) throw new Exception('power不能为空');
    if (!$agentid) throw new Exception('agentid不能为空');
    $deptsql = '';
    if ($dept)  $deptsql ="and  FIND_IN_SET($dept, dept)";
    $sql = "SELECT * from weixin_oa_flowrole where FIND_IN_SET('$agentid',agent) and userid='".$userid."' $deptsql and role in (SELECT id from weixin_oa_role where  FIND_IN_SET('$power',powername))";
    $model = WeixinOaFlowrole::findBySql($sql)->one();
    
    
    return $model?true:false;

}
  public function actionDelreporter(){
    if(!$this->haspower('管理',$this->agentId,'','')){
      return array('errorMessage'=>'没有权限');
    }
    $obj = $this->_request;
    try {
      $old = WeixinComKindsPeople::findOne($obj['id']);
      $old->delete();
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('errorMessage'=>'');

  }
  public function actionSavereporter(){
    if(!$this->haspower('管理',$this->agentId,'','')){
      return array('errorMessage'=>'没有权限');
    }
    $userid = $this->_adminInfo['wxuserid'];
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }
    $transaction = Yii::$app->getDb()->beginTransaction();
   
   
    try {
      if ($obj['id']){
        unset($obj['layout']);
        unset($obj['paper']);
        $obj['updated'] =  date("Y-m-d H:i:s");
        WeixinComKindsPeople::updateAll($obj,['=','id',$obj['id']]);
      } else {

        $obj['tp']=1;
        $data['created'] = $data['updated'] =  date("Y-m-d H:i:s");
        $c = new WeixinComKindsPeople($obj);
        $c->save();
        $obj['id']=$c['id'];
      }
 
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    
    
    $resp['data'] =$obj;
    return $resp;
  }
  public function actionSave(){
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }
    $result = array();
    if ($obj['id']){
      try {
        $old=WeixinPhotographDispatch::findOne($obj['id']);
        // 判断是否是本人修改
        if ($old['userId']!=$this->_adminInfo['wxuserid']) {
          return array('errorMessage'=>'只有经办才能结束');
        }
        WeixinPhotographDispatch::updateAll($obj,['id'=>$obj['id']]);
        
        
      } catch (\Throwable $th) {
        return array('errorMessage'=>$th->getMessage());
      }

    } else {
      
      $thirdNo=$this->getThirdNo();
      $obj['order_no']=$thirdNo;
      // 判断 是否已经存在
      $temp = WeixinPhotographDispatch::find()->where(['order_no'=>$obj['order_no']])->one();
      if  ($temp) {
        return array('errorMessage'=>'order_no【'.$obj['order_no'].'】已经存在');
      }

      $data = $obj;
      //数据验证 记者是否空闲
      if(empty($data['begin_time'])||empty($data['end_time'])){
          return array('errorMessage'=>'请输入开始时间、结束时间');
      }
      if(empty($data['reason'])){
          return array('errorMessage'=>'请输入派工事由');
      }
      if(empty($data['dispatch_userid'])){
          return array('errorMessage'=>'请选择派工记者');
      }
      $data['reason'] = trim($data['reason']);
      //数据新增
      $data['begin_time'] = strtotime($data['begin_time']);
      $data['end_time'] = strtotime($data['end_time']);

      if ($data['begin_time']>=$data['end_time']){
        return array('errorMessage'=>'结束时间必须大于开始时间');
      }

      $data['created'] = $data['updated'] =  date("Y-m-d H:i:s");
      $data['opt_userid'] =  $this->userinfo['userid'];
      $data['userId'] =  $this->userinfo['userid'];
      $data['opt_name'] =  $this->userinfo['name'];
      
  


      $transaction = Yii::$app->getDb()->beginTransaction();
      try {
        $flow = $this->getflow($obj);
        $usesealflow = array(
          'errcode' => 0,
          'errmsg' => 'ok',
          'data' => array(
            'ThirdNo' => $thirdNo,
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
          'thirdNo' => ''.$thirdNo,
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
            
        // 基础数据
        $temp = new WeixinPhotographDispatch($data);
        $temp->save();
        // 保存流程
        $applydata = new WeixinOaApprovaldata($applydata);
        $applydata->save();

      } catch (Exception $e) {
          $transaction->rollback();
          return array('errorMessage'=>$e->getMessage());
      }
      $transaction->commit();
      $touser1 = [$data['approvalUserid'],$data['opt_userid']];
      $touserstr = implode('|', $touser1);
      $this->send($touserstr,'【提交】'.$this->userinfo['name'].'的派工申请',$obj);
      $result['thirdNo']=$thirdNo;

    }
  
   return $result;
  
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
    
    $wfp = new WorkflowParse;
		$flowdata = $wfp->flowParse($userid, $this->TEMPLATE,array());
		$approvalids = array();
		foreach ($flowdata['ApprovalNodes']['ApprovalNode']  as $node){
			foreach($node['Items']['Item'] as $approval){
				$approvalids[]=$approval['ItemUserId'];
			}
		}
  

		

    return $flowdata;
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
      $info= WeixinPhotographDispatch::find()->alias('i')->select('u.avatar as avatar,u.mobile as mobile,u.departmentname as department,c.mobile as dispatchmobile,c.remark as remark,i.*')->where(['and',['order_no'=>$thirdNo]])
      ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=i.opt_userid')
      ->leftJoin(['c'=>WeixinComKindsPeople::tableName()],'c.userid=i.dispatch_userid')
      ->asArray()->one();
      
      $info['userId']=$info['opt_userid'];
      $info['thirdNo']=$thirdNo;
      // 时长，结束时间减去开始时间，然后转换成 X天X小时X分
      $info['duration']=$this->formatDuration($info['end_time'],$info['begin_time']);
      $info['begin_time']=date('Y-m-d H:i:s',$info['begin_time']);
      $info['end_time']=date('Y-m-d H:i:s',$info['end_time']);

      // 申请表信息
     
      $viewdata = $this->flowViewdata($thirdNo);


    } catch (\Throwable $th) {
      return array('errorMessage'=> $th->getMessage());
    }
    
    return array('viewdata'=>$viewdata,'info'=>$info,'statusCn'=>$this->statusCn);

  }
  function formatDuration($startTimestamp, $endTimestamp)
{
    $seconds = abs($endTimestamp - $startTimestamp);

    $days = floor($seconds / 86400);  // 1天 = 86400秒
    $hours = floor(($seconds % 86400) / 3600); // 剩余秒数中算小时
    $minutes = floor(($seconds % 3600) / 60);  // 剩余秒数中算分钟

    $result = '';
    if ($days > 0) $result .= $days . '天';
    if ($hours > 0) $result .= $hours . '小时';
    if ($minutes > 0) $result .= $minutes . '分';

    return $result ?: '0分';
}
  public function flowViewdata($thirdNo)
    {
        $approvaldata = array();
        $approvedata = WeixinOaApprovaldata::find()->where(['and',["=","thirdNo",$thirdNo],['=','agentId',$this->agentId]])->one();
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
		
  

    $data = WeixinPhotographDispatch::find()->where("thirdNo ='$thirdNo'")->asArray()->one();
    $temp = json_decode($data['data'], true);
    $data['inserttime'] = str_replace('-','/',$data['inserttime']);
    $data['date']=$temp['date'];
    $data['time']=$temp['time'];
    $data['layout'] = $temp['layout'];
    $data['reason'] = $temp['reason'];
    $data['notice'] = '注：以上延迟版面符合榕报【2021】60号编印发规定的第五项：特殊免责条款，可申请免于处罚，特此申请。';
		$approvedata = WeixinOaApprovaldata::find()->where( "agentid=".$this->agentId." and thirdNo ='$thirdNo'")->asArray()->one();
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
    $orderby = 'id desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    $where = [
      'and',['>', 'id', 0],
    ];
   
    $status=$this->_request['status'];
    
    if ($status){
      $where[] = ['=', 'status', $status];
    }
    
    $whereUser = "(opt_userid = '$userid' or dispatch_userid = '$userid' )";

    
    $where[]=new Expression($whereUser);
    
    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
   
      $where[] = new Expression($this->getKeywordSql($keyword));
    }


    $model = WeixinPhotographDispatch::find()->where($where);
    
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

    return "opt_name like '%$keyword%' or dispatch_name like '%$keyword%'  or reason like '%$keyword%'";
  }
  public function actionInglist(){

    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'begin_time desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    $where = ['and',new Expression("status=1 and LOCATE('|".$userid."|',CONCAT('|',approvalUserid,'|'))")];
    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression($this->getKeywordSql($keyword));
    }

    $model = WeixinPhotographDispatch::find()->where($where);


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
    $orderby = 'id desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }

    $where = ['and',new  Expression("order_no in (SELECT distinct thirdNo FROM ".WeixinOaApprovalLog::tableName()." where userId='$userid'  and agentid=".$this->agentId.")")];
    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression($this->getKeywordSql($keyword));
    }



    $model = WeixinPhotographDispatch::find()->where($where);

  
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
    $orderby = 'id desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }

    $where = [
      'and',
      ['>', 'id', 0],
    ];

    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression($this->getKeywordSql($keyword));
    }

    $where[] = new Expression("order_no in (SELECT distinct thirdNo FROM ".WeixinOaNotifyLog::tableName()." where userId='$userid')");


    $model = WeixinPhotographDispatch::find()->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $this->transform($res);
    return $_result;
  }

  public function actionReportst(){
       
        //姓名 状态 空闲，任务中，请假 所有的记者
        $people = WeixinComKindsPeople::findBySql("select * from ".WeixinComKindsPeople::tableName()." where tp = 1 and st = 1  order by order_num desc")->all();
   
        $peopleName = array_combine(array_column($people,'userid'),array_column($people,'username'));
        $peopleRemark = array_combine(array_column($people,'userid'),array_column($people,'remark'));
        $userids = array_column($people,'userid');

        //任务中状态
        $currentDay  = $this->_request['keyword'];
        $currentDay = $currentDay ? $currentDay:date("Y-m-d");
        $bTime = strtotime($currentDay."00:00:00");
        $eTime = strtotime($currentDay."23:59:59");

        $drawOut = WeixinPhotographDispatch::findBySql("select dispatch_userid,st,status,begin_time,end_time from ".WeixinPhotographDispatch::tableName()." where (st = 4 or status=2) and begin_time>='$bTime' and begin_time <='$eTime' order by dispatch_userid ")->all();
        
        $drawOutTimes = [];
        if(count($drawOut)){
            foreach($drawOut as $vd){
                $drawOutTimes[$vd['dispatch_userid']][] = date("m-d H:i",$vd['begin_time']).'~'.date("m-d H:i",$vd['end_time']);
            }
        }
        $drawOut = array_unique(array_column($drawOut,'dispatch_userid'));
  
        //请假
        $useridStr = "'".implode("','",$userids)."'";
        $currentTime = date("Y-m-d H:i:s");
        $leave = WeixinOAUserInfo::findBySql("select userId from weixin_leave_info 
                                              where status=2 and leaveType!='销假' and userId in($useridStr)
                                                and ('$currentTime' between leaveStarttime and leaveEndtime) group by userId")->all();

        $leave = array_column($leave,'userId');
        $await = [];//空闲
        foreach ($people as $key=>$value){
            //获取状态
            if(!in_array($value['userid'],$drawOut)&&!in_array($value['userid'],$leave)){
                $await[] = $value['userid'];
            }
        }
    
        return ['drawOut'=>$drawOut,'drawOutTimes'=>$drawOutTimes,'leave'=>$leave,'await'=>$await,'peopleName'=>$peopleName,'peopleRemark'=>$peopleRemark,'day'=>$currentDay];
    }
  public function actionReporterlist(){
    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'order_num desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }

    $where = [
      'and',
      ['=', 'st', 1],['=', 'tp', 1],
    ];

    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression("username like '%$keyword%' or remark like '%$keyword%'");
    }


    $model = WeixinComKindsPeople::find()->where($where);
    
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
            'name'=>'派工申请',

            'children'=>[
              array(
                'name'=>'我的派工',
                'route'=>'/photodispatch/mylist'
              ),
              array(
                'name'=>'我要派工',
                'route'=>'/photodispatch/add'
              )
            ]

          ),
          array(
            'name'=>'派工审批',
            'route'=>'/photodispatch/index?tab=0'
          ),
          array(
            'name'=>'人员去向',
            'route'=>'/photodispatch/reporters?tab=0'
          ),
          ]
    );
  }
  public function actionGetreporters(){
    
    $keyword = $this->_request['keyword'];
    $parameter = $this->_request['parameter'];

    $where = ['and',['>','id',0]];
    if ($keyword) {
      $where[]=['or',['like','username',$keyword],['=','userid',$keyword],['like','remark',$keyword]];
    }
    $limit = $this->_request['limit'];
    if(!$limit) $limit = 20;
    $users = WeixinComKindsPeople::find()->where($where)->orderBy('order_num desc')->limit($limit)->asArray()->all();
    
    // for ($i=0; $i < sizeof($users); $i++) { 
    //   $users[$i]['state']=0;
    //   $users[$i]['statename']='空闲';

    // }
    return $users;
  }
  private function getUserinfo($userid)
  {
      $userinfo = WeixinOAUserInfo::find()->where(['=', 'userid', $userid])->asArray()->one();
      return $userinfo;
  }
  private function send($approvalUserid,$title,$data){
    $url = "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/photodispatch/view?thirdNo=".$data['order_no'];
   

    if (!$approvalUserid) return;
    //发送消息给抄送人
    $msgdata = array(
      'touser' => $approvalUserid,
      'msgtype' => 'textcard',
      'agentid' => $this->agentId,
      'textcard' => array(
        'title' => $title,
        'description' => '<div class="normal">记者：'.$data['dispatch_name'].'</div><div class="normal">开始日期：'.date("Y-m-d H:i:s", intval($data['begin_time'])).'</div><div class="normal">结束日期：'.date("Y-m-d H:i:s", intval($data['end_time'])).'</div>',
        'url' => $url,
        'btntxt' => '详情'
      )
    );

    $this->sendmsg($msgdata);
  }
  private function sendmsg($data)
  {
      WxQyhJk::sendMessage($data['agentid'],$data['touser'],$data['textcard'],'textcard');;
  }
}