<?php

namespace app\modules\api\controllers;
use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WorkflowParse;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\models\FzrbsCompany;
use app\modules\api\models\WeixinAttendanceTemplate;
use app\modules\api\models\WeixinOaApprovaldata;
use app\modules\api\models\WeixinAttendanceInfo;
use app\modules\api\models\WeixinOaApprovalLog;
use app\modules\api\models\WeixinOaAttachment;
use app\modules\api\models\WeixinOaDepartment;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinOaNotifyLog;
use app\modules\api\models\WeixinOaRole;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\models\WxDepartment;
use Exception;
use yii\db\Expression;

// 考勤异常审批

class AttendanceController extends ApiBase{
  public $modelClass = 'app\modules\api\models\WeixinOAUserInfo';
  protected $agentId= 1000067;
  
  protected $statusCn = array('','审批中','已同意','已驳回','已取消');
  protected $userinfo = array();
  protected $exceptionStates = array(0=>'正常打卡',1=>'已处理',2=>'时间异常',3=>'地点异常',4=>'未打卡',5=>'wifi异常',6=>'非常用设备',7=>'请假',8=>'忽略');
  protected $exceptstr = '2,3,4,5,6,8';
  protected $DAYSPECIAL = 3; 
  protected $checkinTable = 'weixin_checkin_data'; // 打卡纪录表
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
    $data = WeixinAttendanceInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']]])->asArray()->one();

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
    $data = WeixinAttendanceInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']]])->asArray()->one();

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

      $data = WeixinAttendanceInfo::find()->where(array('thirdNo'=>$thirdNo))->one();
        
      if($data){
        
        $this->send($data['approvalUserid'],'【催办】您有流程要审批!',$data);
      }
      return array('data'=>'催办成功');

  }
  public function actionCancel(){//撤消


      $userid = $this->_adminInfo['wxuserid'];
      $postdatas = $this->_request;
      if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空');
      
      $data = WeixinAttendanceInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']]])->one();

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
      

      
      $project = WeixinAttendanceInfo::find()->where(['and',['thirdNo'=>$thirdNo]])->asArray()->one();
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
            $this->send(implode('|',$ret['tonotify']['userid']),'【抄送】'.$project['userName'].'的考勤异常申请',$project);
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

      WeixinAttendanceInfo::updateAll($temp,["thirdNo"=>$thirdNo]);
      

    } catch (\Throwable $th) {
      $transaction->rollBack();
      throw $th;
    }
    $transaction->commit();
    $this->send($noticeuserids,$msg.$project['userName'].'的考勤异常申请',$project);
 

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
    $data = WeixinAttendanceInfo::find()->where(['and',['thirdNo'=>$thirdNo]])->one();
    if(!$data){
      return array('errorMessage'=>'数据不存在');
    }
    return array('info'=>$data);
  }
  // 获取未打卡时间段
	// $date = '2022-8-23 晚上';
	private function getUncheckTimePeriod($date) {
		$darr = explode(' ',$date); 
		$x = $darr[0];
		$d = $darr[1];
		$r = array();
		switch ($d) {
			case '上午':
				$r['start'] = $x.' 06:00:00';
				$r['end'] = $x.' 12:00:00';
				break;
			case '下午':
				$r['start'] = $x.' 12:00:00';
				$r['end'] = $x.' 18:00:00';
				break;
			case '晚上':
				$r['start'] = $x.' 18:00:00';
				$r['end'] = $x.' 23:59:59';
				break;
			default:
				$r['start'] = $x.' 06:00:00';
				$r['end'] = $x.' 23:59:59';
				break;
		}
		$r['start'] = strtotime($r['start']);
		$r['end'] = strtotime($r['end']);
		return $r;
	}
  // 每月超过3号，就不能填写1号之前所有的考核表
	private function check($exceptiondate){
		if (!$exceptiondate) {
			return array('ret' => 0,'msg' => '日期为空！');
		}
		$nowd = date('d');
		$y = date('Y');
		$m = date('m');
		$d2 = "$y-$m-1 00:00:00";
		$d2_time = strtotime($d2);
		// return array('ret'=>0,'now'=>$now,'exceptiondate'=>$exceptiondate,'daynow'=>$nowd,'lastmonth'=>$d2,'lastmonth_time'=>$d2_time);
		if ($nowd>$this->DAYSPECIAL&&$d2_time>$exceptiondate) { // 是否是本月之前的申请
			return array('ret' => 0,'errorMessage' => $this->DAYSPECIAL.'号之后，无法提交本月之前的异常！！','exceptiondate'=>$exceptiondate,'lastmonth_time'=>$d2_time);
		}
		return array('ret'=>1);
	}

  // 是否有行政人员
	private function hasAdmini($flow) {
		if ($flow && $flow['ApprovalNodes'] && $flow['ApprovalNodes']['ApprovalNode']) {
			foreach($flow['ApprovalNodes']['ApprovalNode'] as $k=>$v){
				if ($v['NodeType']==0 && $v['NodeRoleid']==3) return true;
			}
		}
		return false;
	}
  public function actionSave(){
    $userid = $this->_adminInfo['wxuserid'];
    $userinfo = $this->userinfo;
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }
    $result = array();

    // 设置为已处理
		$date =$obj['date'];
		$timeperid = $this->getUncheckTimePeriod($date);
		$res = $this->check($timeperid['start']);
		if ($res['ret']==0){
			return $res;
		}


    $thirdNo=$this->getThirdNo();
    $obj['thirdNo']=$thirdNo;
    $data = WeixinAttendanceInfo::find()->where(['and',['thirdNo'=>$obj['thirdNo']]])->one();
    if($data){
      return array('errorMessage'=>'不要重复提交');
    }

    $obj['userId']=$userid;
    $obj['userName']=$userinfo['name'];
    $obj['departmentid']=$userinfo['departmentid'];
    $obj['department']=$userinfo['departmentname'];

    $obj['expire'] = 0;
    $obj['exception_time']=explode(' ', $date)[0].' 00:00:00';
  

    // 启动流程
    try {
      $flow = $this->getflow($obj);
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }


    $data = $obj;

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
      // 流程结束更新状态
          // sch_checkin_time 为标准打卡时间、

      $sql = "update ".$this->checkinTable." set state=1 where userid='".$data['userId']."' and state in (".$this->exceptstr.") and sch_checkin_time>".$timeperid['start']." and sch_checkin_time<".$timeperid['end'];
      Yii::$app->db->createCommand($sql)->execute();
      // 基本信息
      $data = new WeixinAttendanceInfo($data);
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
  
    $this->send($touserstr,$this->userinfo['name'].'【提交】了考勤异常申请',$obj);
    $result['thirdNo']=$thirdNo;
    
    
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

    $template_userinfo=$this->getUserinfo($userid);
    $template = $this->getTemplate($data, $template_userinfo);	

    if (!$template) {
 
      throw new Exception('申请失败,无模版,部门【'.$template_userinfo['departmentname'].'】,用户【'.$template_userinfo['name'].'】');
    }
    $wfp = new WorkflowParse;
		$flowdata = $wfp->flowParse($userid, $template['templateid'],array());
		
    
  
 

    return $flowdata;
  }
  private function getTemplate($data,$userinfo){
    $data['expire']=0;
        if($userinfo) {
            $where = ' and agentid='.$this->agentId.' and isdel=0 and expire='.$data['expire'];
            // 根据用户
			$template = Yii::$app->db->createCommand("select * from ".WeixinAttendanceTemplate::tableName()." where FIND_IN_SET('".$userinfo['id']."',uids) ".$where)->queryOne();
			if($template){
				return $template;
			}
            // 根据部门和职级
            $sql = "select * from ".WeixinAttendanceTemplate::tableName()." where FIND_IN_SET('".$userinfo['level']."',type) and  FIND_IN_SET('".$userinfo['departmentid']."',dids) ".$where;
			$template = Yii::$app->db->createCommand($sql)->queryOne();
			if($template){
				return $template;
			}
            // 根据部门
			$sql = "select * from ".WeixinAttendanceTemplate::tableName()." where type='' and  FIND_IN_SET('".$userinfo['departmentid']."',dids) ".$where;
			$template = Yii::$app->db->createCommand($sql)->queryOne();
			if($template){
			
				return $template;
			}
        }
        return null;
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
      $flowdata= WeixinAttendanceInfo::find()->alias('i')->select('u.avatar as avatarUrl,i.*')->where(['and',['thirdNo'=>$thirdNo]])
      ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=i.userId')->asArray()->one();
      $flowdata['annex']=$this->getFileurlByids($flowdata['annex']);
      // 申请表信息
      
      $viewdata = $wfp->flowViewdata($thirdNo);


    } catch (\Throwable $th) {
      return array('errorMessage'=> $th->getMessage());
    }
    
    return array('viewdata'=>$viewdata,'flowdata'=>$flowdata,'statusCn'=>$this->statusCn);

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
  public function actionException(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 30;
    $offset = $limit * ($page - 1);
		// 开始日期和结束日期
		$y = date('Y');
		$m = date('m');
		$d = date('d');

    $start = mktime(0,0,0,$m-1,1,$y);
		$end = mktime(23,59,59,$m,$d,$y);
    $timestr = " and sch_checkin_time>=".$start." and sch_checkin_time<=".$end."";

		$sql="select * from ".$this->checkinTable." where state in (".$this->exceptstr.") and userid='".$this->_adminInfo['wxuserid']."' $timestr order by id desc limit $offset,$limit";
		$data = Yii::$app->db->createCommand($sql)->queryAll();
    // $data map
    $data = array_map(function($e) {
      $e['checkin_time'] = date('Y-m-d H:i',$e['checkin_time']);
      $e['sch_checkin_time'] = date('Y-m-d H:i',$e['sch_checkin_time']);
      return $e;
    }, $data);
		return array('data'=>$data,'state'=>$this->exceptionStates);
	}
  // 忽略异常
	public function actionIgnore() {

		$id = $this->_request['id'];
    if (!$id){
      return array('errorMessage'=>'id不能为空');
    }
		Yii::$app->db->createCommand()->update($this->checkinTable, ['state'=>8],"id=".$id)->execute();// 已忽略
    return array('errorMessage'=>'');
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
    if ($this->_request['userid']){
      $userid=$this->_request['userid'];
    }
    // $where[] = ['userId' => $userid];


    if ($this->_request['status']){
      $where[] = ['status' => $this->_request['status']];
    }
    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression("payer like '%$keyword%' or receiver like '%$keyword%' or amount like '%$keyword%' or reason like '%$keyword%'");
    }
    

    


    $model = WeixinAttendanceInfo::find()->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $this->transform($res);
    return $_result;
  }
  private function transform($list){
 

    return $list;
  }
  /**
     * 获取部门子节点(本地数据表部门)
     * @param int $parentId 父节点id
     * @return array 子节点信息
     */
    protected function _getLocalDepartmentChildren($parentId)
    {
        $where = [
            'and',
            ['=', 'parentid', $parentId],
        ];
        if (isset($this->_request['childrenId']) && $this->_request['childrenId']) {
            $where[] = ['in', 'id', explode(',', $this->_request['childrenId'])];
        }
        $res = WxDepartment::find()->where($where)->orderBy('order desc')->all();
        $routes = [];
        foreach ($res as $row) {
            $node = ['title' => $row->name, 'key' => strval($row->id), 'value' => strval($row->id), 'isLeaf' => true];
            $children = [];
            $child = WxDepartment::find()->where(['=', 'parentid', $row->id])->orderBy('order desc')->one();
            if ($child) {
                $node['isLeaf'] = false;
            }
            if ($this->_request['showAll'] && !$node['isLeaf']) {
                $children  = $this->_getLocalDepartmentChildren($row->id);
            }
            if (intval($this->_request['user']) === 1) {
                $users = WeixinOAUserInfo::find()->where(['and',['=', 'departmentid', $row->id],['=', 'status', 1],['=', 'st', 1]])->all();
                if ($users) {
                    foreach ($users as $user) {
                        $children[] = ['title' => $user['name'], 'key' => $user['userid'], 'value' => $user['userid'], 'isLeaf' => true];
                    }
                }
            }
            if ($children) {
                $node['isLeaf'] = false;
                $node['children'] = $children;
            }

            if ($this->_request['noBodyDepartment'] && $node['isLeaf'] && intval($node['key']) > 0 && !$node['children']){
                continue;
            }
            
            $routes[] = $node;
        }
        return $routes;
    }

  // 导出统计结果
	public function actionExport(){
		$start=$this->_request['start'];
		$end=$this->_request['end'];
    $start_time = strtotime(''.$start);
    $end_time = strtotime(''.$end);
		$status=$this->_request['status'];
    // throw new Exception(json_encode(array('start'=>$start,'end'=>$end,'start_time'=>$start_time,'$end_time'=>$end_time,'startTime'=>strtotime('2023-04-01'))));
		if(!$status) $status = 2;
		if(!$start||!$end){
			return array('errorMessage'=>'start or end can not be empty');
		}
    // 具备【考勤管理】角色才可以任意导出
    $result = WeixinOaFlowrole::find()->where(['and',['=', 'userid', $this->_adminInfo['wxuserid']],new Expression("role in (select  id from ".WeixinOaRole::tableName()." where rolename='考勤管理')")])->one();
    if (!$result) {
      $deptid = $this->userinfo['departmentid'];
      $depts = WeixinOaDepartment::findBySql("SELECT GROUP_CONCAT(id SEPARATOR ',') as ids from weixin_oa_department where id=$deptid or FIND_IN_SET($deptid,parentids)")->asArray()->one();
  
      $deptstr = " and departmentid in (".$depts['ids'].") ";
      
    }else{
      $deptstr = " and departmentid in (".$result['dept'].") ";
    }
  

  
    
    
 

    // 异常查询，包括：异常总数，时间异常次数，未打卡次数
    $sql1="select userid,username,department,COUNT(*) as total,SUM(CASE WHEN exception_type = '时间异常' THEN 1 ELSE 0 END) AS late,SUM(CASE WHEN exception_type = '未打卡' THEN 1 ELSE 0 END) AS uncheck from  weixin_checkin_data where exception_type in('时间异常','未打卡') and sch_checkin_time>=$start_time and sch_checkin_time<$end_time $deptstr and state not in(7) GROUP BY userid,username,department";

    // 申请查询，包括：申请总数，时间异常申请次数，未打卡申请次数
    $sql2="select  wi.userId,wi.userName,wi.department,COUNT(*) as apply_total,SUM(CASE WHEN exception_type = '时间异常' THEN 1 ELSE 0 END) AS apply_late,SUM(CASE WHEN exception_type = '未打卡' THEN 1 ELSE 0 END) AS apply_uncheck  from weixin_attendance_info wi JOIN weixin_checkin_data cd on cd.userid=wi.userId and FROM_UNIXTIME(UNIX_TIMESTAMP(exception_time),'%Y-%m-%d')=FROM_UNIXTIME(cd.sch_checkin_time,'%Y-%m-%d') and exception_type in('时间异常','未打卡') where  status=2 and exception_time>='$start' and exception_time<'$end' GROUP BY wi.userId,wi.userName,wi.department";

    $sql = "select b.*,a.apply_total,a.apply_late,a.apply_uncheck from ($sql1) b left join ($sql2) a on a.userId=b.userid";

    try {
      $res = Yii::$app->db->createCommand($sql)->queryAll();
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
		
	
    $header = array(
      '姓名','部门','异常天数合计(天)',
      '异常-迟到(次)','异常-缺卡(次)','考勤登记合计(次)',
      '迟到-考勤登记(次)','缺卡-考勤登记(次)'
    );
		$data = array_map(function($row) {
      $payratio = $row['payratio']?($row['payratio']*100):0;
      $conditionratio = $row['conditionratio']?intval($row['conditionratio']):0;
      if ($row['overdue']>0&&$payratio<$conditionratio){
        $row['paydefault'] = '否';
      }else{
        $row['paydefault'] = '是';
      }
        return [
            $row['username'],
            $row['department'],
            $row['total'],
            $row['late'],
            $row['uncheck'],
            $row['apply_total'], 
            $row['apply_late'],
            $row['apply_uncheck']
            
        ];
    }, $res);
    // 在最前面插入
    array_unshift($data, $header);
    return array('data'=>$data,'header'=>$header);
	}
  // 导出异常申请纪录
  public function actionExportapply(){
    $start=$this->_request['start'];
		$end=$this->_request['end'];
		$status=$this->_request['status'];
		if(!$status) $status = 2;
		if(!$start||!$end){
      return array('errorMessage'=>'start or end can not be empty');
		}
  
  
    
    $result = WeixinOaFlowrole::find()->where(['and',['=', 'userid', $this->_adminInfo['wxuserid']],new Expression("role in (select  id from ".WeixinOaRole::tableName()." where rolename='考勤管理')")])->one();
    if (!$result) {
      return array('errorMessage'=>'没有【考勤管理】权限');
    }
    $deptstr = " and departmentid in (".$result['dept'].") ";
 
    $sql= "select * from ".WeixinAttendanceInfo::tableName()." where status=$status and exception_time>='$start' and exception_time<'$end' $deptstr  order by userName asc";

		$res = Yii::$app->db->createCommand($sql)->queryAll();

    $header = array(
      '姓名','部门','异常日期',
      '原因','类型'
    );
		$data = array_map(function($row) {
      $payratio = $row['payratio']?($row['payratio']*100):0;
      $conditionratio = $row['conditionratio']?intval($row['conditionratio']):0;
      if ($row['overdue']>0&&$payratio<$conditionratio){
        $row['paydefault'] = '否';
      }else{
        $row['paydefault'] = '是';
      }
        return [
            $row['userName'],
            $row['department'],
            $row['date'],
            $row['reason'],
            $row['type']
            
        ];
    }, $res);
    // 在最前面插入
    array_unshift($data, $header);
    return array('data'=>$data,'header'=>$header);
  }
	
  public function actionAgreeall(){
    $thirdNos =$this->_request['thirdNos']; // 单号字符串
    $userid = $this->_adminInfo['wxuserid'];
    if (!$thirdNos) {
      return array('errorMessage'=>'未选中任何申请');
    }
    $arr = explode(',',$thirdNos);
    $status = 2;
    $msg='';
    for($j=0;$j<sizeof($arr);$j++) {
      $thirdNo = $arr[$j];
      $data = Yii::$app->db->createCommand("select * from ".WeixinAttendanceInfo::tableName()." where thirdNo ='$thirdNo' and  status!=$status")->queryOne();
      if ($data) {
        // 是否是当前审批人
        if ($data['approvalUserid'] && !in_array($userid,explode('|',$data['approvalUserid']))){
          continue;
        }
        
        $status = 2;

        try {
          $ret = $this->changeStatus($userid,$data['id'],$thirdNo,$status,array());
          $this->updateAfterFlowChange($ret,$thirdNo,$status,array());
        
        } catch (\Throwable $th) {
          $msg.=$thirdNo.':'.$th->getMessage().';<br/>';
          continue;
        }
        
      } else {
        continue;
      }
      
    }
    return array('errorMessage'=>$msg);
  }
  // 待审批
  public function actionInglist(){

    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'inserttime desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }

    $where = ['and',new Expression("status=1 and LOCATE('|".$userid."|',CONCAT('|',approvalUserid,'|'))")];


    $model = WeixinAttendanceInfo::find()->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $this->transform($res);

    $canAgreeAll = false;
    if ($userid == 'linwei') {
      $canAgreeAll = true;
    }
   
    $_result["canAgreeAll"] = $canAgreeAll;
    return $_result;
  }
  // 已审批
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
    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression("userName like '%$keyword%' or datet like '%$keyword%'");
    }

    $model = WeixinAttendanceInfo::find()->where($where);

  
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

  

    $model = WeixinAttendanceInfo::find()->where($where);
    
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
              'route'=>'/attendance/exception'
            ),
            array(
              'name'=>'我的审批',
              'route'=>'/attendance/index?tab=0'
            ),
            array(
              'name'=>'申请信息',
              'children'=>[
                array(
                  'name'=>'我的申请',
                  'route'=>'/attendance/mylist'
                ),
                array(
                  'name'=>'报表导出',
                  'route'=>'/attendance/stat'
                ),
                array(
                  'name'=>'异常列表',
                  'route'=>'/attendance/exception'
                )
              ]
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
    $url = "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/attendance/view?thirdNo=".$data['thirdNo'];
  

    if (!$approvalUserid) return;
    $msgdata = [
      'touser' => $approvalUserid,
      'msgtype' => 'textcard',
      'agentid' => $this->agentId,
      'textcard' => [
          'title' => $title,
          'description' =>  '<div class="normal">申请部门：' . $data['department'] . '</div><div class="normal">异常日期：' . $data['date'] . '</div>',
          'url' => $url,
          'btntxt' => '详情'
          
      ]
    ];
    $this->sendmsg($msgdata);
  }
  // private function sendmsg($data)
  // {
  //     WxQyhJk::sendMessage($data['agentid'],$data['touser'],$data['textcard'],'textcard');;
  // }
   private function sendmsg($data)
   {
       $content = '您有一条新的审批消息，请前往掌上福州APP查看。';
       WxQyhJk::sendMessage($data['agentid'],$data['touser'],$content,'text');
   }
   
   // 打卡异常数据分页查询
   public function actionCheckinlist(){
     $total = 0;
     $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
     $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
     $offset = $limit * ($page - 1);
     $orderby = 'id desc';
     
     $where = [
       'and',
       ['>', 'id', 0],
     ];
     
     // 日期范围过滤
     if (isset($this->_request['start']) && $this->_request['start']){
       $start = strtotime($this->_request['start']);
       $where[] = ['>=', 'sch_checkin_time', $start];
     }
     if (isset($this->_request['end']) && $this->_request['end']){
       $end = strtotime($this->_request['end']);
       $where[] = ['<=', 'sch_checkin_time', $end];
     }
     
     // 异常类型过滤
     if (isset($this->_request['exception_type']) && $this->_request['exception_type'] !== ''){
       $where[] = ['state' => $this->_request['exception_type']];
     }
     
     // 用户ID过滤
     if (isset($this->_request['userid']) && $this->_request['userid']){
       $where[] = ['userid' => $this->_request['userid']];
     }
     
     $count = (new \yii\db\Query())
       ->from($this->checkinTable)
       ->where($where)
       ->count();
     
     $data = (new \yii\db\Query())
       ->select(['c.*'])
       ->from(['c' => $this->checkinTable])
       ->where($where)
       ->orderBy($orderby)
       ->limit($limit)
       ->offset($offset)
       ->all();
     
     // 格式化时间
     $data = array_map(function($e) {
       $e['checkin_time'] = $e['checkin_time'] ? date('Y-m-d H:i:s', $e['checkin_time']) : '';
       $e['sch_checkin_time'] = $e['sch_checkin_time'] ? date('Y-m-d H:i:s', $e['sch_checkin_time']) : '';
       return $e;
     }, $data);
     
     $_result["current"] = $page;
     $_result["pageSize"] = $limit;
     $_result["total"] = $count;
     $_result['data'] = $data;
     return $_result;
   }
   
   // 打卡异常数据导出
   public function actionExportcheckin(){
     $start = isset($this->_request['start']) ? $this->_request['start'] : '';
     $end = isset($this->_request['end']) ? $this->_request['end'] : '';
     $exception_type = isset($this->_request['exception_type']) ? $this->_request['exception_type'] : '';
     $userid = isset($this->_request['userid']) ? $this->_request['userid'] : '';
     
     $where = [
       'and',
       ['>', 'id', 0],
     ];
     
     if ($start){
       $start_time = strtotime($start);
       $where[] = ['>=', 'sch_checkin_time', $start_time];
     }
     if ($end){
       $end_time = strtotime($end);
       $where[] = ['<=', 'sch_checkin_time', $end_time];
     }
     if ($exception_type !== ''){
       $where[] = ['exception_type' => $exception_type];
     }
     if ($userid){
       $where[] = ['userid' => $userid];
     }
     
     $data = (new \yii\db\Query())
       ->select(['c.*', 'u.name as username', 'u.departmentname as department'])
       ->from(['c' => $this->checkinTable])
       ->leftJoin(['u' => WeixinOAUserInfo::tableName()], 'c.userid = u.userid')
       ->where($where)
       ->orderBy('id desc')
       ->all();
     
     $header = array(
       'ID', '用户ID', '姓名', '部门', '打卡时间', '标准打卡时间', 
       '异常类型', '打卡地点', '设备信息', 'WIFI信息'
     );
     
     $exportData = array_map(function($row) {
       return [
           $row['id'],
           $row['userid'],
           $row['username'],
           $row['department'],
           $row['checkin_time'] ? date('Y-m-d H:i:s', $row['checkin_time']) : '',
           $row['sch_checkin_time'] ? date('Y-m-d H:i:s', $row['sch_checkin_time']) : '',
           isset($this->exceptionStates[$row['exception_type']]) ? $this->exceptionStates[$row['exception_type']] : $row['exception_type'],
           $row['location'],
           $row['device_info'],
           $row['wifi_mac']
       ];
     }, $data);
     
     array_unshift($exportData, $header);
     return array('data' => $exportData);
   }
 }