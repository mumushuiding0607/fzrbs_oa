<?php

namespace app\modules\api\controllers;
use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WorkflowParse;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\models\FzrbsCompany;
use app\modules\api\models\WeixinOaApprovaldata;
use app\modules\api\models\WeixinOaApprovalInfo;
use app\modules\api\models\WeixinOaApprovalLog;
use app\modules\api\models\WeixinOaAttachment;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinOaNotifyLog;
use app\modules\api\models\WeixinOAUserInfo;
use Exception;
use yii\db\Expression;



class QypressController extends ApiBase{
  public $modelClass = 'app\modules\api\models\WeixinOAUserInfo';
  protected $agentId= 1000070;
  protected $TEMPLATE = '3596b8b82d9eb42225237f65c14b8c80_1680076143';
  protected $statusCn = array('','审批中','已同意','已驳回','已取消');
  protected $userinfo = array();
  protected $LeaderRoleid = 17;
	protected $DirectorRoleid = 18;
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
      default:
        # code...
        break;
    }
  }
  public function actionReject(){//驳回
    $userid = $this->_adminInfo['wxuserid'];
    $postdatas = $this->_request;
    if (!$postdatas['speech']) return array('errorMessage'=>'审批意见不能为空');
    $data = WeixinOaApprovalInfo::find()->where(['and',['=','agentId',$this->agentId],['=','thirdNo',$postdatas['thirdNo']]])->asArray()->one();

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
    $data = WeixinOaApprovalInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']],['=','agentId',$this->agentId]])->asArray()->one();

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

      $data = WeixinOaApprovalInfo::find()->where(array('thirdNo'=>$thirdNo))->one();
        
      if($data){
        $info = json_decode($data['data'],true);
        $this->send($data['approvalUserid'],'【催办】您有流程要审批!',$info);
      }
      return array('data'=>'催办成功');

  }
  public function actionCancel(){//撤消


      $userid = $this->_adminInfo['wxuserid'];
      $postdatas = $this->_request;
      if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空');
      
      $data = WeixinOaApprovalInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']],['=','agentId',$this->agentId]])->one();

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
      

      
      $project = WeixinOaApprovalInfo::find()->where(['and',['=','agentId',$this->agentId],['thirdNo'=>$thirdNo]])->asArray()->one();
      if (!$project) throw new Exception("找不到流程信息");
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
              $this->setNotifylog(array('thirdNo'=>$thirdNo,'userId'=>$v,'userName'=>$ret['tonotify']['username'][$k],'agentid'=>$this->agentId));
            }
            $this->send(implode('|',$ret['tonotify']['userid']),'【抄送】'.$project['userName'].'的签付印延迟审批',json_decode($project['data'],true));
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
          $noticeuserids=$project['userId'];
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

      WeixinOaApprovalInfo::updateAll($temp,["thirdNo"=>$thirdNo,"agentId"=>$this->agentId]);
      

    } catch (\Throwable $th) {
      $transaction->rollBack();
      throw $th;
    }
    $transaction->commit();
    $this->send($noticeuserids,$msg.$project['userName'].'的签付印延迟审批',json_decode($project['data'],true));
 

  }
  private function setNotifylog($data){//保存抄送人信息
		if($data){
			
      $res = WeixinOaNotifyLog::find()->where(['agentid'=>$this->agentId,'thirdNo'=>$data['thirdNo'],'userId'=>$data['userId']])->one();
			if(!$res){
        $temp = new WeixinOaNotifyLog($data);
        $temp->save();
				
			}
		}
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
    $data = WeixinOaApprovalInfo::find()->where(['and',['=','agentId',$this->agentId],['thirdNo'=>$thirdNo]])->one();
    if(!$data){
      return array('errorMessage'=>'数据不存在');
    }
    return array('info'=>json_decode($data->data,true));
  }
  public function actionSave(){
    $userid = $this->_adminInfo['wxuserid'];
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }
    $result = array();
    if ($obj['thirdNo']){
      $data = WeixinOaApprovalInfo::find()->where(['and',['=','agentId',$this->agentId],['thirdNo'=>$obj['thirdNo']]])->one();
      if(!$data){
        return array('errorMessage'=>'数据不存在');
      }
      $data['data']=json_encode($obj);
      try {
        $data->save();
      } catch (\Throwable $th) {
        return array('errorMessage'=>$th->getMessage());
      }
      
      $result = $obj;

    } else {
      
       $thirdNo=$this->getThirdNo();
       $obj['thirdNo']=$thirdNo;
       $data = array(
        'agentId'=>$this->agentId,
        'userId' => $userid,
        'userName' => $this->userinfo['name'],
        'departmentid' => $this->userinfo['departmentid'],
        'department' => $this->userinfo['departmentname'],
        'thirdNo' => $thirdNo,
        'type' => 1,
        'data' => json_encode($obj)
        );



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

        $transaction = Yii::$app->getDb()->beginTransaction();

        try {
          // 基本信息
          $data = new WeixinOaApprovalInfo($data);
          $data->save();
          $applydata = new WeixinOaApprovaldata($applydata);
          $applydata->save();
          

        } catch (\Throwable $th) {
          $transaction->rollBack();
          return array('errorMessage'=>$th->getMessage());
        }
        $transaction->commit();

        $touser1 = [$data['approvalUserid'],$data['userId']];
      
        $touserstr = implode('|', $touser1);
      
        $this->send($touserstr,'【提交】'.$this->userinfo['name'].'的审批申请',$obj);
        $result['thirdNo']=$thirdNo;
    }
    
    return $result;
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
  
    
    if (!$data) {
      throw new Exception('data参数为空，{amount:5000,payerid:24,payer:日报,amountsType:3,userid:cainig}');
		}

    $userid = $this->_adminInfo['wxuserid'];
    if ($data['userid']){
      $userid = $data['userid'];
    }else if ($data['userId']){
      $userid = $data['userId'];
    }
    $wfp = new WorkflowParse;
		$flowdata = $wfp->flowParse($userid, $this->TEMPLATE,array());
		$approvalids = array();
		foreach ($flowdata['ApprovalNodes']['ApprovalNode']  as $node){
			foreach($node['Items']['Item'] as $approval){
				$approvalids[]=$approval['ItemUserId'];
			}
		}
    $leaderid = $data['leaderid'];
		if(!in_array($leaderid,$approvalids)){
			$u = $this->getUserinfo($leaderid);
			$item = array(
				'ItemName' => $u['name'],
				'ItemImage' => $u['avatar'],
				'ItemUserId' => $u['userid'],
				'ItemStatus' => 1,
				'ItemOpTime' => 0
			);
			array_unshift($flowdata['ApprovalNodes']['ApprovalNode'],array(
				'NodeStatus' => 1,
				'Items' => array('Item'=>array($item)),
				'NodeAttr' => 1,
				'NodeRoleid' => $this->LeaderRoleid
			));
		}
    $directorid = $data['directorid'];
		if ($directorid!=$leaderid){
			$u = $this->getUserinfo($directorid);
			$item = array(
				'ItemName' => $u['name'],
				'ItemImage' => $u['avatar'],
				'ItemUserId' => $u['userid'],
				'ItemStatus' => 1,
				'ItemOpTime' => 0
			);
			array_unshift($flowdata['ApprovalNodes']['ApprovalNode'],array(
				'NodeStatus' => 1,
				'Items' => array('Item'=>array($item)),
				'NodeAttr' => 1,
				'NodeRoleid' => $this->DirectorRoleid
			));
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

    $wfp = new WorkflowParse($this->agentId);
    try {
      // 流程数据
      $flowdata= WeixinOaApprovalInfo::find()->alias('i')->select('u.avatar as avatarUrl,i.*')->where(['and',['=','agentId',$this->agentId],['thirdNo'=>$thirdNo]])
      ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=i.userId')->asArray()->one();
      $flowdata['annex']=$this->getFileurlByids($flowdata['annex']);
      // 申请表信息
      $info = json_decode($flowdata['data'],true);
      $viewdata = $wfp->flowViewdata($thirdNo);


    } catch (\Throwable $th) {
      return array('errorMessage'=> $th->getMessage());
    }
    
    return array('viewdata'=>$viewdata,'flowdata'=>$flowdata,'info'=>$info,'statusCn'=>$this->statusCn);

  }
  public function actionViewpic() {
    $thirdNo = $this->_request['thirdNo'];
    if (!$thirdNo) {
      return array('errorMessage'=> 'thirdNo不能为空');
    }
		
  

    $data = WeixinOaApprovalInfo::find()->where("thirdNo ='$thirdNo'")->asArray()->one();
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
      $pattern = '/\/attachment(?:\/[^\/\s"]+)+/';
      if (preg_match($pattern, $e->savePath, $matches)) {
          $url = $matches[0];
      }
 
      return $url."?name=".urlencode($e->baseName)."&time=".((strtotime($e->inserttime))*1000)."&size=".$e->fileSize;
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
      'and',['=', 'agentId', $this->agentId],
    ];
    if ($this->_request['userid']){
      $userid=$this->_request['userid'];
    }
    $where[] = ['userId' => $userid];


    if ($this->_request['status']){
      $where[] = ['status' => $this->_request['status']];
    }
    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression("payer like '%$keyword%' or receiver like '%$keyword%' or amount like '%$keyword%' or reason like '%$keyword%'");
    }
    

    


    $model = WeixinOaApprovalInfo::find()->where($where);
    
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
  public function actionInglist(){

    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'id desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    $where = ['and',new Expression("status=1 and agentId=".$this->agentId." and LOCATE('|".$userid."|',CONCAT('|',approvalUserid,'|'))")];


    $model = WeixinOaApprovalInfo::find()->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $this->transform($res);
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
    $where = ['and',new  Expression("thirdNo in (SELECT distinct thirdNo FROM ".WeixinOaApprovalLog::tableName()." where userId='$userid'  and agentid=".$this->agentId).")"];

    $model = WeixinOaApprovalInfo::find()->where($where);

  
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $this->transform($res);;
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
      $where[] = new Expression("userName like '%".$keyword."%' or data like '%".$keyword."%' or thirdNo like '%".$keyword."%'");
    }

    $where[] = new Expression("thirdNo in (SELECT distinct thirdNo FROM ".WeixinOaNotifyLog::tableName()." where userId='$userid'  and agentid=".$this->agentId.")");

    $model = WeixinOaApprovalInfo::find()->where($where);
    
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
              'route'=>'/press/add'
            ),
            array(
              'name'=>'我的审批',
              'route'=>'/press/index?tab=0'
            ),
            array(
              'name'=>'我的申请',
              'route'=>'/press/mylist?tab=3'
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
    $url = "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/press/view?thirdNo=".$data['thirdNo'];
  

    if (!$approvalUserid) return;
    $msgdata = [
      'touser' => $approvalUserid,
      'msgtype' => 'textcard',
      'agentid' => $this->agentId,
      'textcard' => [
          'title' => $title,
          'description' => '<div class="normal">签付印日期：' . $data['paper'].'</div><div class="normal">版面：' . $data['layout'].'</div>',
          'url' => $url,
          'btntxt' => '详情'
          
      ]
    ];
    $this->sendmsg($msgdata);
  }
  private function sendmsg($data)
  {
      WxQyhJk::sendMessage($data['agentid'],$data['touser'],$data['textcard'],'textcard');;
  }
}