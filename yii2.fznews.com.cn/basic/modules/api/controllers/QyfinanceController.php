<?php

namespace app\modules\api\controllers;
use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WorkflowParse;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\models\FzrbsBudgetDict;
use app\modules\api\models\FzrbsCompany;
use app\modules\api\models\FzrbsContract;
use app\modules\api\models\FzrbsInvoice;
use app\modules\api\models\FzrbsInvoicing;
use app\modules\api\models\FzrbsInvoicingInvoice;
use app\modules\api\models\FzrbsOperationLog;
use app\modules\api\models\WeixinFinanceCompany;
use app\modules\api\models\WeixinFinanceInfo;
use app\modules\api\models\WeixinFinanceTemplate;
use app\modules\api\models\WeixinOaApprovaldata;
use app\modules\api\models\WeixinOaApprovalInfo;
use app\modules\api\models\WeixinOaApprovalLog;
use app\modules\api\models\WeixinOaAttachment;
use app\modules\api\models\WeixinOaDepartment;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinOaNotifyLog;
use app\modules\api\models\WeixinOaPrintPosition;
use app\modules\api\models\WeixinOaRole;
use app\modules\api\models\WeixinOaTemplates;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\models\WeixinOaUsertag;
use app\modules\api\models\WeixinOauserTaguser;
use app\modules\weixin\Weixin;
use Exception;
use yii\db\Expression;



class QyfinanceController extends ApiBase{
  public $modelClass = 'app\modules\api\models\WeixinOAUserInfo';
  protected $statusCn = array('','审批中','已同意','已驳回','已取消');
  protected $userinfo = array();
  protected $agentId= 1000066;

  protected $TYPE = array(0=>'付款审批',1=>'工资审批',2=>'车队报销',3=>'跨部门付款审批',4=>'跨部门工资审批',5=>'内转');
  protected $payStatus = array(1 => '转账', 2 => '现金', 3 => '冲账');
  protected $positions = array(0=>'部门主管',1=>'公司主体负责人',2=>'部门负责人',3=>'会计',4=>'财务主管',5=>'分管领导',6=>'社长',7=>'人事主管',8=>'人事经办');
  public function init()
  {
      parent::init();
      $this->userinfo = $this->getUserinfo($this->_adminInfo['wxuserid']);
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
    $where = ['and',new Expression("status=1 and LOCATE('|".$userid."|',CONCAT('|',approvalUserid,'|'))")];
    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression($this->getKeywordSql($keyword));
    }

    $model = WeixinFinanceInfo::find()->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $this->transform($res);
    return $_result;
  }
  private function transform($list){
    $types = ['付','工','车','付','工'];
    if (!$list) return $list;
    for ($i=0; $i < sizeof($list); $i++) { 
      $list[$i]['amountsTypeName']=$types[$list[$i]['amountsType']];
    }
    return $list;
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
      'and',
      ['>', 'id', 0],
    ];
 


    if ($this->_request['status']){
      $where[] = ['status' => $this->_request['status']];
    }
    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression("payer like '%$keyword%' or receiver like '%$keyword%' or amount like '%$keyword%' or account like '%$keyword%' or reason like '%$keyword%'");
    }
    if ($this->_request['userid']){
      $userid=$this->_request['userid'];
    }
    $where[] = ['userId' => $userid];

    


    $model = WeixinFinanceInfo::find()->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $this->transform($res);
    return $_result;
  }

  private function getKeywordSql($keyword){
    return "userName like '%".$keyword."%' or reason like '%".$keyword."%' or amount like '%".$keyword."%' or thirdNo like '%".$keyword."%' or receiver like '%".$keyword."%' ";
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
    $where = ['and',new  Expression("thirdNo in (SELECT distinct thirdNo FROM ".WeixinOaApprovalLog::tableName()." where userId='$userid'  and agentid=".$this->agentId.")")];
    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression($this->getKeywordSql($keyword));
    }
    $model = WeixinFinanceInfo::find()->where($where);
    
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
    $orderby = 'verified asc,inserttime desc';
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

    $where[] = new Expression("thirdNo in (SELECT distinct thirdNo FROM ".WeixinOaNotifyLog::tableName()." where userId='$userid'  and agentid=".$this->agentId.")");

    $model = WeixinFinanceInfo::find()->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $res;
    return $_result;
  }
  public function actionHistorylist(){

    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'id desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    $where = ['and',new Expression("thirdNo in (SELECT distinct thirdNo FROM ".WeixinOaApprovalLog::tableName()." where userId='$userid'  and agentid=".$this->agentId.")")];
  

    $model = WeixinFinanceInfo::find()->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $res;
    return $_result;
  }
  // 查询当前用户所在部门对应的付款单位 
  public function actionGetpayers(){
   
    $keyword = $this->_request['keyword'];
    $limit = $this->_request['limit'];
    if(!$limit) $limit = 50;
    $where=['and',['>', 'id', 0]];
    if ($keyword) {
      // 如果keyword是数字，或者是用逗号分割的数字
      if (is_numeric($keyword) || preg_match('/^[\d,]+$/', $keyword)) {
        $where[] = ['in', 'id', explode(',', $keyword)];
      }else{
        $where[] = ['like', 'company', $keyword];
      }
      
    }
    
    $users = WeixinFinanceCompany::find()->select('id,company,ctype,crossdept')->where($where)->orderBy('ord desc')->limit($limit)->all();
    return $users;

  }


  public function actionGetbankaccount(){
		$keyword = $this->_request['keyword'];
    $where = '';
    if($keyword){
      $where = "and receiver like '%".$keyword."%'";
		} else {
      $where = "and userId ='".$this->userinfo['userid']. "'";
		}
    $sql = "select DISTINCT t.temp,t.receiver,t.account,t.bank from (select CONCAT(receiver,account) as temp,receiver,account,bank,id from ".WeixinFinanceInfo::tableName()." where  status=2 $where order by id desc) t limit 20";
		
    $res = WeixinFinanceInfo::findBySql($sql)->asArray()->all();
		return $res;
	}
  public function actionIscrossdept(){

    $payer = $this->_request['payer'];
	
		$userinfo = $this->userinfo;
		// 查询 parentid 为 1 为止
		$id=$userinfo['departmentid'];
		$name = '';
		while ($id>1) {
      $d = WeixinOaDepartment::find()->select('id,name,parentid')->where(['id'=>$id])->asArray()->one();
			$id=$d['parentid'];
			$name=$d['name'];
		}
  
		return array('ret' => 0, 'data' => count(explode($payer,$name))>1?false:true,'msg'=>'');
	}

  // 判断收款主体是否是内部的
  public function actionIsinsidecompany(){
    $res = array('ret'=>1,'data'=>false);
    $keyword = $this->_request['keyword'];

    $s = WeixinFinanceCompany::find()->select('count(id) cnt')->where(['company'=>$keyword])->asArray()->one();

    if ($s['cnt'] > 0 && in_array($this->userinfo['departmentid'],[11])) {
      $res['data']=true;
    } 
    return $res;
  }
  // 流程相关
  public function actionGetflowdata(){
    $thirdNo = $this->_request['thirdNo'];
    if (!$thirdNo){
      return array('errorMessage'=>'thirdNo不能为空');
    }
    
    $viewdata=0;

    $wfp = new WorkflowParse($this->agentId);
    
    try {
      $info= WeixinFinanceInfo::find()->alias('i')->select('u.avatar,i.*,c.company as payer')->where(['thirdNo'=>$thirdNo])
      ->leftJoin(['c'=>WeixinFinanceCompany::tableName()],'c.id=i.payerid')
      ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=i.userId')->asArray()->one();


      $info['amountsTypeName']=$this->TYPE[$info['amountsType']];
      $info['amountCap']= $this->convertAmountToCn($info['amount']);
      if($info['amountreal']){
        $info['amountrealCap']= $this->convertAmountToCn($info['amountreal']);
      }
      $info['payStatusName'] = $this->payStatus[$info['payStatus']];

      // 查询证明人
      if ($info['certifier']) {
				$ce = WeixinOAUserInfo::find()->select("Group_concat(name) as names")->where(['in','userid',explode(',',$info['certifier'])])->asArray()->one();
        if ($ce){
          $info['certifierNames'] = $ce['names'];
        }
				
			}
      
			// 查询用车人
      if ($info['amountsType']==2 &&$info['caruserid']) {
				$ce = WeixinOAUserInfo::find()->select("Group_concat(name) as names")->where(['in','userid',explode(',',$info['caruserid'])])->asArray()->one();
        if ($ce){
          $info['caruseridNames'] = $ce['names'];
        }
				
			}
      
      // 转换
      $info['annex']=$this->getFileurlByids($info['annex']);
			$info['invoice']=$this->getFileurlByids($info['invoice']);
      
      
      $viewdata = $wfp->flowViewdata($thirdNo);
      if ($viewdata) $viewdata['thirdNo']=$thirdNo;
      
       
      // 出纳确认
      $needverify = false;
      if ($viewdata&&$viewdata['notifierUserid']){
        // 判断抄送人是否包含当前用户
        $needverify = in_array($this->userinfo['userid'],$viewdata['notifierUserid']);
      }

    } catch (\Throwable $th) {
      return array('errorMessage'=> $th->getMessage());
    }

    $canUpate = $this->canUpate($thirdNo);
    
    return array('viewdata'=>$viewdata,'info'=>$info,'statusCn'=>$this->statusCn,'needverify'=>$needverify,'canUpate'=>$canUpate);

  }

  private function canUpate($thirdNo){


    // 判断财务主管审批过没，如果已经审批过了，那么就不能修改
    
    $flag = WeixinOaApprovalLog::findBySql("select * from ".WeixinOaApprovalLog::tableName()." where userId in (select userid from weixin_oauser_taguser tu join weixin_leave_userinfo u on u.id=tu.uId where tagId in (SELECT id from weixin_oauser_tag where tagName='财务主管')) and status=2 and thirdNo ='".$thirdNo."' order by id desc")->one();

    if ($flag) { // 财务审批过了
      return false;
    }
    return true;
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
      if (in_array($e->fileType,['jpg','png','gif','jpeg','JPG','PNG','GIF','JPEG'])){
        $url = "http://fzrb.fznews.com.cn/index.php?r=qiyehao/attachment/view2&attachment=".$url;
      }else{
        $url = "/www/web/fzrb.fznews.com.cn/".$url;
      }

      return $url."&name=".urlencode($e->baseName)."&time=".((strtotime($e->inserttime))*1000)."&size=".$e->fileSize;
    },$datas);
    return implode(',',$datas);

  }
  public function actionGetdriverleader(){
    
    $taguser=WeixinOAUserInfo::findBySql("select b.userid,b.`name`,b.avatar,b.mobile from weixin_oauser_taguser a LEFT JOIN weixin_leave_userinfo b on a.uId=b.id where tagId=48")->asArray()->one();
		
    return $taguser;
  }
  public function actionVerify() {
		$thirdNo = $this->_request['thirdNo'];
    if (!$thirdNo) {
				return array('errorMessage'=> 'thirdNo为空');
		}
    WeixinFinanceInfo::updateAll(['verified'=>1],['thirdNo'=>$thirdNo]);
    
		return array('ret'=>1,'msg'=>'成功');
	}
  public function actionGetdata(){
    $thirdNo = $this->_request['thirdNo'];
    if (!$thirdNo){
      return array('errorMessage'=>'thirdNo不能为空');
    }
    $data = WeixinFinanceInfo::find()->where(['thirdNo'=>$thirdNo])->asArray()->one();
    if($data['annex']){
      $data['annex']=$this->getFileurlByids($data['annex']);
    }
    
    return $data;
    
    
  }

  public function actionUpdateapproval(){
    $thirdNo = $this->_request['thirdNo'];
    if (!$thirdNo){
      return array('errorMessage'=>'thirdNo不能为空');
    }
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }
    try {
      $data = WeixinFinanceInfo::find()->where(['thirdNo'=>$thirdNo])->one();
      $data->approvalUserid=$obj['approvalUserid'];
      $data->approvalUsername=$obj['approvalUsername'];
      $data->save();
    } catch (\Throwable $th) {
      return array('errorMessage'=> $th->getMessage());
    }
    return array('errorMessage'=>'');
    
    
    
  }
  public function actionSave(){
    $userid = $this->_adminInfo['wxuserid'];
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }
    unset($obj['print']);
    $result = array();
    if ($obj['id']){
      $old=WeixinFinanceInfo::findOne($obj['id']);
      // 判断是否是本人修改
      if ($old['userId']!=$userid) {
        return array('errorMessage'=>'只能修本人提交的数据');
      }
      if(!$obj['receiver']){
        return array('errorMessage'=>'收款单位不能为空！');
      }
      // 判断财务主管是否已经审过
      $canupdate = true;
      $approvedata = WeixinOaApprovaldata::find()->where(['and',['=','agentid',$this->agentId],["=","thirdNo",$old['thirdNo']]])->one();
      $approvearr = json_decode($approvedata['data'],true);
      if ($approvearr&&$approvearr['step']){
        foreach($approvearr['data']['ApprovalNodes']['ApprovalNode'] as $e) {
            if ($e['NodeType'] == 2 && $e['NodeTagid']==53 && $e['NodeStatus'] == 2) { // 判断财务主管审过没
              $canupdate = false;
            }
          }
      }
      if (!$canupdate){
        return array('errorMessage'=>'财务主管已审，不能修改');
      }

      try {
        WeixinFinanceInfo::updateAll($obj,['id'=>$obj['id']]);
      } catch (\Throwable $th) {
        return array('errorMessage'=>$th->getMessage());
      }
      
				
			

    } else {
      
        $obj['thirdNo']=$obj['thirdNo']?$obj['thirdNo']:$this->getMsecTime();
        // 判断 是否已经存在
        $temp = WeixinFinanceInfo::find()->where(['thirdNo'=>$obj['thirdNo']])->one();
        if  ($temp) {
          return array('errorMessage'=>'thirdNo【'.$obj['thirdNo'].'】已经存在');
        }
        $obj['verified']=0;
        $obj['status']=1;
        $obj['userId']=$userid;
        $obj['userName']=$this->userinfo['name'];
        $obj['departmentid']=$this->userinfo['departmentid'];
        $obj['department']=$this->userinfo['departmentname'];
       



        // 启动流程

        $ret = $this->getflow($obj);
        if ($ret['ret']) $flow = $ret['msg'];
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
        $obj['approvalUserid'] = implode('|', $approvalUserid);
        $obj['approvalUsername'] = implode('|', $approvalUsername);
        $obj['status'] = 1;

        $transaction = Yii::$app->getDb()->beginTransaction();

        try {
          // 基本信息
          $data = new WeixinFinanceInfo($obj);
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
        
         $this->send($touserstr,$this->userinfo['name'].'的付款审批申请',$data);
    }
    $result['thirdNo']=$obj['thirdNo'];
    return $result;
  }

  // =========================  流程 ==================================
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
public function actionAgree(){
    
    $postdatas = $this->_request;
   
    $userid = $this->_adminInfo['wxuserid'];
    
    if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空');
    $data = WeixinFinanceInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']]])->asArray()->one();

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
public function actionReject(){//驳回
  $userid = $this->_adminInfo['wxuserid'];
  $postdatas = $this->_request;
  if (!$postdatas['speech']) return array('errorMessage'=>'审批意见不能为空');
  $data = WeixinFinanceInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']]])->asArray()->one();

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
  public function actionCancel(){//撤消


      $userid = $this->_adminInfo['wxuserid'];
      $postdatas = $this->_request;
      if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空');
      
      $data = WeixinFinanceInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']]])->one();

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

    
  $db = Yii::$app->db;
  $tran = $db->beginTransaction();
  try {

    $project = WeixinFinanceInfo::find()->where(["thirdno"=>$thirdNo])->asArray()->one();
    if (!$project) throw new Exception("找不到流程信息");
    if ($ret){
      if($ret['approveres']){
        
        WeixinOaApprovaldata::updateAll($ret['approveres'],'id='.$ret['approveres']['id']);
      }
      if($ret['logdata']){
   
          $db->createCommand()->insert(WeixinOaApprovalLog::tableName(), $ret['logdata'])->execute();
     
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
          $this->send(implode('|',$ret['tonotify']['userid']),$project['userName'].'的付款审批申请',$project);
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
 
    
    WeixinFinanceInfo::updateAll($temp,["thirdNo"=>$thirdNo]);
    

  } catch (\Throwable $th) {
    $tran->rollBack();
    throw $th;
  }
  
  $tran->commit();
  
  $this->send($noticeuserids,$msg.$project['userName'].'的付款审批申请',$project);
 

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
private function changeStatus($userid,$id,$thirdNo,$status,$condition=array()){

  $ret = 0;
  $condition['agentId']=$this->agentId;
  $condition['userid']=$userid;
  $condition['status']=$status;
  $condition['thirdNo']=$thirdNo;

  $ret = $this->flowChange($condition);
  
  

  return $ret;
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
    $data = WeixinFinanceInfo::find()->where(['and',['=','thirdNo',$thirdNo]])->asArray()->one();

    // 是否是当前审批人
    if ($data['approvalUserid'] && !in_array($this->userinfo['userid'],explode('|',$data['approvalUserid']))){
      return array('errorMessage'=>'只有当前审批人可转审');
    }

    $flow = WeixinOaApprovaldata::find()->where(['agentid'=>$this->agentId,'thirdNo'=>$thirdNo])->one();
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
        WeixinFinanceInfo::updateAll($data,['id'=>$data['id']]);

        $this->send($curuser['userid'].'|'.$user['userid'],$this->userinfo['name']."发起了转审操作",$data);
        


      }
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=> $th->getMessage());
    }
    $transaction->commit();

		
		return array('ret'=>1);
}
public function actionUrge(){
  $thirdNo=$this->_request['thirdNo'];
  if (!$thirdNo) return array('errorMessage'=>'thirdNo为空');

    $data = WeixinFinanceInfo::find()->where(array('thirdNo'=>$thirdNo))->one();
		if(!$data){
      return array('errorMessage'=>'【'.$thirdNo.'】对应的审批不存在');
		}
    try {
      $this->send($data['approvalUserid'],'您有流程要审批!',$data);
    } catch (\Throwable $th) {
      $this->_operationlog(['catalog' => "催办【".$thirdNo."】：", 'remark' => "催办【".$thirdNo."】：".$th->getMessage()]);
      return array('errorMessage'=>$th->getMessage());
    }
    $this->_operationlog(['catalog' => "催办【".$thirdNo."】：", 'remark' => "财务开支审批催办【".$thirdNo."】,待办人：".$data['approvalUserid']]);
    

    return array('data'=>'催办成功');

}
protected function _operationlog($log)
    {
      
      if (is_array($log)) {
            $log['username'] = $this->userinfo['name'];
            $log['realname'] = $this->userinfo['name'];
            $log['ip'] = $this->_userIp;
            $log['url'] = Yii::$app->request->getHostInfo() . Yii::$app->request->url;
            $log['inserttime'] = time();
            $model = new FzrbsOperationLog($log);

            $model->save();

        }
    }
public function actionGettabs(){
  return array(
    'activeTab'=>1,
    'data'=>[
          array(
            'name'=>'我要申请',
            // 'icon'=>'https://fastly.jsdelivr.net/npm/@vant/assets/user-inactive.png',
            // 'iconActive'=>'https://fastly.jsdelivr.net/npm/@vant/assets/user-active.png',
            'children'=>[
              array(
                'name'=>'付款审批',
                'route'=>'/finance/add?amountsType=0'
              ),
              array(
                'name'=>'工资审批',
                'route'=>'/finance/add?amountsType=1'
              ),
              array(
                'name'=>'用车报销',
                'route'=>'/finance/add?amountsType=2'
              )
            ]

          ),
          array(
            'name'=>'我的审批',
            'route'=>'/finance/index?tab=0'
          ),
          array(
            'name'=>'我的申请',
            'route'=>'/finance/mylist?tab=3'
          )
          ]
  );
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
      $temp = $this->getflow($obj);
      $templatename = $temp['templatename'];
      
    } catch (\Throwable $th) {
      return array('errorMessage'=> $th->getMessage());
    }
    $flow = $temp['msg'];
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
    
    return  array('viewdata'=>array('step'=>$step,'approval'=>$approvaldata,'notify'=>$notifier,'templateid'=>$flow['OpenTemplateId']),'templatename'=>$templatename,'statusCn'=>$this->statusCn,'printinfo'=>$printinfo);
	}
  
  private function getflow($data){
  
    
    if (!$data) {
      throw new Exception('data参数为空，{amount:5000,payerid:24,payer:日报,amountsType:3,userid:cainig}');
		}
    if  (!$data['payerid']) {
      throw new Exception('请指定付款公司');
		}
    $userid = $this->_adminInfo['wxuserid'];
    if ($data['userid']){
      $userid = $data['userid'];
    }else if ($data['userId']){
      $userid = $data['userId'];
    }
    if(!$data['userId']) {
      $data['userId'] = $userid;
    }

    $template_userid=$userid;
		$template_userinfo=$this->getUserinfo($userid);

		$condition = array('company' => $data['payerid']);
		// ============ 如果是车队报销 =====================
		if ($data['caruserid']&&$data['caruserid']!='undefined') { // 车队报销
			$userid=$data['caruserid'];
			$userinfo2 = $this->getUserinfo($userid);	//获取用户信息 
			$userinfo['caruser_departmentid'] = $userinfo2['departmentid'];
		}
 
    $template = $this->getTemplate($data, $template_userinfo);	

    if (!$template) {
      $at = FzrbsBudgetDict::find()->where(['type'=>'付款审批类型','value'=>$data['amountsType']])->one();
      throw new Exception('【'.$data['userId'].'】【'.$at['label'].'】未配置流程付款单位【'.$data['payer'].'】金额：【'.$data['amount'].'】');
    }
    
    //解析审批流程数据
		$ret = $this->flowparse($template_userid,$template['templateid'],$condition,$data);
		$tt = WeixinOaTemplates::find()->where(['templateId'=>$template['templateid']])->one();
    if ($tt) $ret['templatename']=$tt['templateName'];
    
    return $ret;
  }
  private function getTemplate($data,$userinfo){//获取财务审批流程模版
    
   
		if($userinfo){
      
			// 判断金额大小
			$amount = $data['amount'];
			// 0：付款审批，1：工资审批,2：车队报销
			$type = isset($data['amountsType']) ? $data['amountsType']:0;
			$departmentid=$userinfo['departmentid'];
			$where = ' and agentid='.$this->agentId.' and type='.$type;
			if ($type!=1){ // 除工资审批外
				$where = $where.' and ((lamount<'.$amount.' and hamount>='.$amount.') or (lamount<'.$amount.' and hamount=0))';
			}
			if ($type==2) {
				$departmentid=$userinfo['caruser_departmentid'];
			}
			$where.=' order by id desc';
			// 0根据用户和主体
      $template = WeixinFinanceTemplate::findBySql("select * from ".WeixinFinanceTemplate::tableName()." where FIND_IN_SET('".$data['payerid']."',company) and FIND_IN_SET('".$userinfo['id']."',uids) ".$where)->one();
			
			if($template){
				return $template;
			}
			// 主体、部门


      $template = WeixinFinanceTemplate::findBySql("select * from ".WeixinFinanceTemplate::tableName()." where FIND_IN_SET('".$departmentid."',dids) and FIND_IN_SET('".$data['payerid']."',company) ".$where)->one();
			if($template){
				
				return $template;
			}
			// 1根据主体
			
      $template = WeixinFinanceTemplate::findBySql("select * from ".WeixinFinanceTemplate::tableName()." where  FIND_IN_SET('".$data['payerid']."',company) ".$where)->one();
			if($template){
				
				return $template;
			}
			// 根据部门
			$sql = "select * from ".WeixinFinanceTemplate::tableName()." where company='' and  FIND_IN_SET('".$departmentid."',dids) ".$where;
      $template = WeixinFinanceTemplate::findBySql($sql)->one();

			if($template){
				
				return $template;
			}
		}
		
		return false;
	}
  // 打印设置
  public function actionSetposition() {
    $thirdNo = $this->_request['thirdNo'];
		$step = $this->_request['step'];
		$position = $this->_request['position'];
    if (!$thirdNo){
	
      return array('errorMessage'=>'thirdNo 不能为空');
		}
		if(!isset($this->_request['step'])){
			return array('errorMessage'=>'请选择需要修改打印位置的审批人');
		}
    if($position!=0&&!$position){

      return array('errorMessage'=>'position 不能为空');
		}
    try {
      $flow = WeixinOaApprovaldata::findBySql("select * from ".WeixinOaApprovaldata::tableName()." where agentid=".$this->agentId." and thirdNo ='$thirdNo'")->one();
      $flowdata = json_decode($flow['data'],true);
      $node = $flowdata['data']['ApprovalNodes']['ApprovalNode'][$step];
      $node['position'] = intval($position);
      $flowdata['data']['ApprovalNodes']['ApprovalNode'][$step]=$node;
      // 重新索引数组
      $flowdata['data']['ApprovalNodes']['ApprovalNode'] = array_values($flowdata['data']['ApprovalNodes']['ApprovalNode']);
      $flow['data'] = json_encode($flowdata);
      WeixinOaApprovaldata::updateAll($flow,array('id'=>$flow['id']));
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('errorMessage'=>'');
    
  }
  // 修改用户本人的审批意见
  public function actionAlterspeech(){
    $thirdNo = $this->_request['thirdNo'];
		$step = $this->_request['step'];
		$speech = $this->_request['speech'];
    if (!$thirdNo){
	
      return array('errorMessage'=>"thirdNo 不能为空");
		}
		if($step!=0&&!$step){
			return array('errorMessage'=>"step 不能为空");
		}
    try {
      $speech = urldecode($speech);
      $flow = WeixinOaApprovaldata::findBySql("select * from ".WeixinOaApprovaldata::tableName()." where agentid=".$this->agentId." and thirdNo ='$thirdNo'")->one();
      $flowdata = json_decode($flow['data'],true);
      $node = $flowdata['data']['ApprovalNodes']['ApprovalNode'][$step];

      if ($node['Items']['Item']&&count($node['Items']['Item'])>0) {
        
        for ($i=0;$i<count($node['Items']['Item']);$i++){
          
          if ($node['Items']['Item'][$i]['ItemUserId']==$this->_adminInfo['wxuserid']){
            $node['Items']['Item'][$i]['ItemSpeech'] = $speech;
            $flowdata['data']['ApprovalNodes']['ApprovalNode'][$step]=$node;
            $flow['data'] = json_encode($flowdata);
            WeixinOaApprovaldata::updateAll($flow,array('id'=>$flow['id']));
          }
        }

      } else {

        return array('errorMessage'=>'审批节点['.$step.']为空！');
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    
     return array('errorMessage'=>'');
  }
  private function flowparse($userid,$templateid,$condition,$data) {

    if (!$templateid){
      throw new Exception('未匹配到流程模板');
    }

		//解析流程数据
		$wfp = new WorkflowParse($this->agentId);
		$flowdata = array();
		if (in_array($data['amountsType'],array(3,4))) {
			$condition['crossdepartmentid'] = $this->getCrossDeptid($data['payer']);
			if (!$condition['crossdepartmentid']){
        throw new Exception('付款单位【'.$data['payer'].'】未设置所跨部门,如果确实需要跨部门，请联系管理员设置；若不需要跨部门，请在取消跨部门后重新发起申请');
			}
      
     
			$flowdata = $wfp->flowParseCrossDepartment($userid,$templateid,$condition);
		} else {
     
			$flowdata =$wfp->flowParse($userid, $templateid,$condition);
		}
    
    
		if ($data['certifier']){ // 插入流程
      foreach (explode(',',$data['certifier']) as $item) {
        $cond = array('step'=>0,'position' => 'before');
        $inuser = array('userid'=>$item,'NodeAttr'=>1,'NodeType'=>99,'NodeRoleid'=>0,'NodeTagid'=>0,'NodeLevel'=>0);
        $flow = $wfp->insertFlow(array('data'=>$flowdata), $inuser,$cond);
        $flowdata = $flow['data'];
      }
			
		}
    if (!$flowdata['ApprovalNodes']['ApprovalNode']){

      throw new Exception('未设置审批人请联系管理员设置，相关流程ID【'.$templateid.'】');
    }
    if (!$flowdata['NotifyNodes']['NotifyNode']){

      throw new Exception('未设置抄送人，请联系管理员设置，默认为出纳，相关流程ID【'.$templateid.'】');
    }
    

    
		return array('ret'=>1,'msg'=>$flowdata);
	}
  public function actionViewpic() {
    $thirdNo = $this->_request['thirdNo'];
    if (!$thirdNo) {
      return array('errorMessage'=> 'thirdNo不能为空');
    }
		
    $preview = $this->_request['preview'];//是否是预览

    $data = WeixinFinanceInfo::find()->alias('i')->select('i.*,d.label as payStatusName')
    ->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=i.payStatus and d.type='付款方式'")
    ->where("thirdNo ='$thirdNo'")->asArray()->one();
		$approvedata = WeixinOaApprovaldata::find()->where( "agentid=".$this->agentId." and thirdNo ='$thirdNo'")->asArray()->one();
    $flowdata = array();
    $certifiers = array();
    $speeches = array(); // 纪录备注
    $approverNames = array();
    $approverUser = array();
    // 查询证明人
    if ($data && $data['certifier']) {
      $certifiers = $this->getUsersByUserid($data['certifier'],'name');
      foreach($certifiers as $k=>$v) {
        $data['certifiers'] .= $v['name'].' ';
      }
    }
    if ($data && $approvedata) {
      $approvearr = json_decode($approvedata['data'], true);
      foreach ($approvearr['data']['ApprovalNodes']['ApprovalNode'] as $k => $r) {
        if ($r['Items']['Item']&&count($r['Items']['Item']) > 1) {
            $tempname = '';
            foreach ($r['Items']['Item'] as $key => $item) {
              $tempname.= $item['ItemName'].',';
              $approverUser[] = array('userid'=>$item['ItemUserId'],'name'=>$item['ItemName']);
            }
            $approverNames[]=$tempname;
          } else {
            $approverNames[] = $r['Items']['Item'][0]['ItemName'];
            $approverUser[] = array('userid'=>$r['Items']['Item'][0]['ItemUserId'],'name'=>$r['Items']['Item'][0]['ItemName']);
          }
      }
      $temp = $this->getPrintInfo($approvearr['data']['ApprovalNodes']['ApprovalNode'],false,$data);
      $flowdata = $temp['flowdata'];
      $speeches = $temp['speeches'];
      
    }
			// 金额大写
			$data['amountCap'] = $this->convertAmountToCn($data['amount']);
      if ($data['amountreal']>0){
        $data['amountrealCap'] = $this->convertAmountToCn(floatval($data['amountreal']));
      }
	
			return array('data' => $data,'flowdata' => $flowdata, 'approverNames' => $approverNames,'approverUser'=>$approverUser, 'payStatus'=>$this->payStatus,'speeches'=>$speeches,'positions'=>$this->positions);
 
	}
  
  private function getPrintInfo($approvalNode,$preview=true,$data){
    $flowdata = array();
    foreach ($approvalNode as $k => $r) {
      
      if ($preview||$r['NodeStatus'] != 1) {
        // 如果是或签
        $tmparr = array();
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
          $position = $this->getPositionByFlowNode($r,$tmparr['userid']);
          if ($position>-1){
              if ($flowdata[$position]) {
                $flowdata[$position]['title'] = $tmparr['title'].' '.$flowdata[$position]['title'];
              } else {
                $flowdata[$position] = $tmparr;
              }
          }
        }
        


        
      }

    }

    return array('flowdata'=>$flowdata,'speeches'=>$speeches);
		
  }
  // $r为流程节点数据
  private function getPositionByFlowNode($r,$userid){
    $position = -1;
    if (isset($r['position'])) {
      return $r['position'];
    } else {
    
      $data = WeixinOaPrintPosition::findBySql("select * from ".WeixinOaPrintPosition::tableName()." where agentid=".$this->agentId." and type=20 and userid='".$userid."' order by updatetime desc")->one();
      if($data){
        return $data['position'];
      }
      $sql = "select * from ".WeixinOaPrintPosition::tableName()." where agentid=".$this->agentId." and type=".$r['NodeType']." ";
      switch ($r['NodeType']) {
        case 0://用户角色
          $sql .= " and FIND_IN_SET(" . $r['NodeRoleid'] .",value)";
          break;
        case 1: // 固定成员
          $sql .= " and userid='".$userid."' ";
          break;
        case 3: // 上级
          $sql .= " and FIND_IN_SET(" . $r['NodeLevel'] .",value)";
          break;
        case 8: // 主体负责人
          break;
        case 2: // 标签
          $sql .= " and FIND_IN_SET(" . $r['NodeTagid'] .",value)";
        case 99: // 不显示
          break;
        default:
          break;
      }
      $sql.=" order by updatetime desc";
      $data = WeixinOaPrintPosition::findBySql($sql)->one();
      if ($data){
        return $data['position'];
      }
    }
    
    

    return $position;
  }
  private function getUsersByUserid ($userids,$field){
		if (!$field) $field = 'userid,mobile,avatar,name';
		$tmp = explode(',', $userids);
		$arr = [];
		foreach($tmp as $t) {
			$arr[] = "'$t'";
		}
    return WeixinOAUserInfo::findBySql("select $field from ".WeixinOAUserInfo::tableName()." where userid in (".implode(',',$arr).")")->all();
		
	}
  private function send($approvalUserid,$title,$data){
    $url = "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/finance/view?thirdNo=".$data['thirdNo'];
    if (preg_match('/【撤销】/',$title)){
      $url = "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/finance/add?action=reapply&thirdNo=".$data['thirdNo'];
    }

    if (!$approvalUserid) return;


    $msgdata = [
      'touser' => $approvalUserid,
      'msgtype' => 'textcard',
      'agentid' => $this->agentId,
      'textcard' => [
          'title' => $title,
          'description' => '<div class="normal">付款单位：' . $data['payer'].'</div><div class="normal">收款单位：' . $data['receiver'].'</div><div class="normal">付款金额：' . $data['amount'].'</div>',
          'url' => $url,
          'btntxt' => '详情'
          
      ]
    ];
    return $this->sendmsg($msgdata);
  }
private function getTagName($tagid) {
    $t = WeixinOauserTaguser::findOne($tagid);
		return $t?$t['tagName']:'';
	}
    private function getCrossDeptid($payer){
    
    $row = WeixinFinanceCompany::find()->where(['=','company',$payer])->one();
    return $row?intval($row['crossdept']):0;
  }

private function getMsecTime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
	}

  // 根据关键字查询合同
  public function actionGetcontract(){
    $keyword = $this->_request['keyword'];

    $where = ["type=16"];
    // $where[] = "(departmentid=".$this->userinfo['departmentid']." or signdeptid=".$this->userinfo['departmentid']." or signuserid='".$this->userinfo['userid']."' or creator='".$this->userinfo['userid']."')";
    if ($keyword){
      $where[] = "(title like '%".$keyword."%' or serial like '%".$keyword."%')";
    }
    $con = implode(' and ', $where);
    $sql = "select id,CONCAT(serial,' ',title) as title,fileurls as fileurls,signdate from fzrbs_contract where $con  limit 15";

    $datas = FzrbsContract::findBySql($sql)->asArray()->all();
   
    if(sizeof($datas)==0||(sizeof($datas)==1&&!$datas[0]['title'])){
      return array('errorMessage'=>'只能查询【'.$this->userinfo['departmentname'].'】、本人经办、以及签订人为本人的【付款】合同');
    }
    
    
    return array('list'=>$datas);
 
  }

  // 开票申请系统：搜索开票单位、开票项目、开票金额
  public function actionGetinvoice(){
    $keyword = $this->_request['keyword'];
   
    $limit = $this->_request['limit'];
    if(!$limit) $limit = 10;
    $where =['and',new Expression("inv.realinvoiceamount>0 and inv.invoiceids is not null")];
    $where[]=['=','i.departmentid',$this->userinfo['departmentid']];
    if ($keyword){
      $where[] = ['or',['like','i.partbname',$keyword],['=','i.amount',$keyword]];
    }
    $datas = FzrbsInvoicing::find()->alias('i')->select('i.partbname,i.amount,inv.invoiceno,inv.fileurls as fileurls,i.pdffileurls')
      ->leftJoin(['inv'=>"(SELECT invoicingid,GROUP_CONCAT(EIid) as invoiceno,GROUP_CONCAT(fileurls) as fileurls,GROUP_CONCAT(id) as invoiceids,sum(TotalTaxIncludedAmount) as realinvoiceamount from ".FzrbsInvoice::tableName()." GROUP BY invoicingid)"],'inv.invoicingid=i.id')
      ->where($where)->orderBy('id desc')->limit($limit)->asArray()->all();
    
    return $datas;
  }
  
  private function convertAmountToCn($num) {

		$c1 = "零壹贰叁肆伍陆柒捌玖";

		$c2 = "分角元拾佰仟万拾佰仟亿";

		//精确到分后面就不要了，所以只留两个小数位

		$num = round($num, 2);

		//将数字转化为整数

		$num = $num * 100;
    $num = floatval(sprintf('%.2f', $num));
    
		if (strlen($num) > 10) {    

				return "金额太大，请检查";

		}

		$i = 0;

		$c = "";

		while (1) {    

				if ($i == 0) {    

							//获取最后一位数字        

							$n = substr($num, strlen($num) - 1, 1);    

				} else {        

							$n = $num % 10;    

				}    //每次将最后一位数字转化为中文    

				$p1 = substr($c1, 3 * $n, 3);    

				$p2 = substr($c2, 3 * $i, 3);    

				if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {        

							$c = $p1 . $p2 . $c;    

				} else {        

							$c = $p1 . $c;    

				}    

				$i = $i + 1;    

				//去掉数字最后一位了    

				$num = $num / 10;    
        $num = (int) (''.$num);   

				//结束循环    

				if ($num == 0) {        

							break;    

				}

		}

		$j = 0;

		$slen = strlen($c);

		while ($j < $slen) {    

				//utf8一个汉字相当3个字符    

				$m = substr($c, $j, 6);    

				//处理数字中很多0的情况,每次循环去掉一个汉字“零”    

				if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {        

							$left = substr($c, 0, $j);        

							$right = substr($c, $j + 3);        

							$c = $left . $right;        

							$j = $j - 3;        

							$slen = $slen - 3;    

				}    

				$j = $j + 3;

		}

		//这个是为了去掉类似23.0中最后一个“零”字

		if (substr($c, strlen($c) - 3, 3) == '零') {    

				$c = substr($c, 0, strlen($c) - 3);

		}

		if (empty($c)) {    

				return "零元整";

		} else {    

				return $c . "整";

		}

	}
  public function actionGetusers(){
    
    $keyword = $this->_request['keyword'];
    $limit = $this->_request['limit'];
    if(!$limit) $limit = 10;
    $where =['and',['>','id',0]];
    if ($keyword){
      $where[] = ['like','name',$keyword];
    }
    if ($this->_request['userids']){
      $where[] = ['in','userid',explode(',',$this->_request['userids'])];
    }
    $users = WeixinOAUserInfo::find()->select('id,userid,name,mobile,avatar')->where($where)->limit($limit)->all();
    return $users;
  }
  private function getUserinfo($userid)
  {
      $userinfo = WeixinOAUserInfo::find()->where(['=', 'userid', $userid])->asArray()->one();
      return $userinfo;
  }
  private function sendmsg($data)
  {
      WxQyhJk::sendMessage($data['agentid'],$data['touser'],$data['textcard'],'textcard');
  }
}