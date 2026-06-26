<?php

namespace app\modules\api\controllers;
use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WorkflowParse;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\models\FzrbsCblcCd;
use app\modules\api\models\FzrbsCblcMrbw;
use app\modules\api\models\FzrbsCompany;
use app\modules\api\models\WeixinOaApprovaldata;
use app\modules\api\models\WeixinOaApprovalInfo;
use app\modules\api\models\WeixinOaApprovalLog;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinOaRole;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\weixin\Weixin;
use Exception;
use yii\db\Expression;



class DailycheckController extends ApiBase{
  public $modelClass = 'app\modules\api\models\DailycheckController';
  protected $userinfo = array();
  protected $agentId= 1000081;
  protected $formtypes = ['','白班','夜班'];
  protected $PROBLEM_STATES = ['白班和夜班','仅白班','仅夜班']; 
  protected $TABS = ['我的申请','我的审批','历史审批'];
  protected $STATUS_ALL = ['未提交','审批中','已通过','已驳回','已撤销'];
  protected $ACTS = ['同意','驳回','撤销','催办'];
  public function init()
  {
      parent::init();
      // $this->_adminInfo['wxuserid']="wuwenlin";
      $this->userinfo = $this->getUserinfo($this->_adminInfo['wxuserid']);
      $usertype=$this->_adminInfo['usertype'];
      if($usertype==1){
        $this->TABS= ['所有申请','我的申请','我的审批','历史审批'];
      }
  }
  private function getMsecTime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
	}	
  public function actionGetthirdno(){
    return $this->getMsecTime();
  }
  private function generateApprovers($userid){
   
    if(!$userid){
      throw new Exception('userid不能为空');
    }
   
    $users = WeixinOAUserInfo::find()->where(['and',['in','userid',explode(',',$userid)]])->asArray()->all();
    return array_map(function($u){
      return array(
        'ItemName' => $u['name'],
				'ItemImage' => $u['avatar'],
				'ItemUserId' => $u['userid'],
				'ItemStatus' => 1,
				'ItemOpTime' => 0
      );
    },$users);
  }


  public function actionGetdailycheck(){
    $thirdNo = $this->_request['thirdNo'];
    if (!$thirdNo){
      return array('errorMessage'=>'thirdNo 不能为空');
    }
    // 获取基本信息
    $data=WeixinOaApprovalInfo::find()->where(['thirdNo'=>$thirdNo])->one();
    if(!$data){
      return array('errorMessage'=>'数据可能已被删除');
    }
    // 获取流程数据
    $applydata=WeixinOaApprovalData::find()->where(['thirdNo'=>$thirdNo])->one();

    $temp = json_decode($applydata['data']);

    $temp->data->approvalUserid = $data['approvalUserid'];

    $formdata = json_decode($data['data']);

    if ($formdata->typeid){
      $t = FzrbsCblcCd::findOne($formdata->typeid);
      $formdata->typeidname=$t['name'];
    }
    if ($formdata->formtype){
      
      $formdata->formtypename=$formdata->formtype==1?'白班':'夜班';
    }
    return array('data'=>$formdata,'applydata'=>$temp->data);

  } 
  public function actionGetdailychecklist(){
    $tabtype = $this->_request['tabtype'];

    if (!$tabtype){
      return array('errorMessage'=>"tabtype参数为空,该值可以为：".implode(",",$this->TABS));
    }
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;
    $offset = $limit * ($page - 1);

    $where = [
        'and',
        ['=', 'i.agentId', $this->agentId],
    ];
    // 查询内容可以在：我的申请、我的审批、历史审批 之间切换
    $model = null;
    
    switch ($tabtype) {
      case '所有申请':
        $model = WeixinOaApprovalInfo::find()->alias('i')->where($where);
        break;
      case '我的申请':
        $model = WeixinOaApprovalInfo::find()->alias('i')->where($where)->andWhere(['=', 'userId', $this->_adminInfo['wxuserid']]);
        break;
      case '我的审批':
        $model = WeixinOaApprovalInfo::find()->alias('i')->where($where)->andWhere(['and',new Expression("FIND_IN_SET('". $this->_adminInfo['wxuserid']."', approvalUserid)"),['=','status',1]]);
        break;
      case '历史审批':
        $model = WeixinOaApprovalInfo::find()->alias('i')->where($where)->andWhere(new Expression("i.thirdNo in (SELECT thirdNo from ".WeixinOaApprovalLog::tableName()." l where l.userId='". $this->_adminInfo['wxuserid']."')"));
        break;
      default:
        # code...
        break;
    }

    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('id desc')->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    $this->_result['tabtypes'] = $this->TABS;
    $this->_result['statusAll'] = $this->STATUS_ALL;
    return $this->_result;
  }
  public function actionSavedailycheck(){
  
    $obj = $this->_request;
    if (!$obj['thirdNo']){
      return array('errorMessage'=>'thirdNo不能为空,保存前请先调用api dailycheck/getthirdno 获取 thirdNo');
    }
    if (!$obj['datas']){
      return array('errorMessage'=>'datas不能为空,为流程九问的数据');
    }
    if (!$obj['formdate']){
      return array('errorMessage'=>'date不能为空,如：2017-01-01');
    }
    $obj['formdate'] = substr($obj['formdate'],0,10);
    if (!isset($obj['formtype'])){
      return array('errorMessage'=>'formtype不能为空,0-白班，1-夜班');
    }
    if (!isset($obj['typeid'])){
      return array('errorMessage'=>'typeid 不能为空,10-新媒体');
    }
    
    // 判断是否重复添加
    $thirdNo = ''.$obj['thirdNo'];
    $obj['annex']=str_replace('-','',$obj['formdate']).$obj['formtype'].$obj['typeid'];
    // 判断是否重复
    $d = WeixinOaApprovalInfo::find()->select('count(id) cnt')->where(['and',['in','status',[1,2]],['or',['agentId'=>$this->agentId,'thirdNo'=>$thirdNo],['annex'=>$obj['annex']]]])->asArray()->one();
    if ($d['cnt'] > 1) {
      return array('errorMessage'=>'当天该类型必问已经申请过');
    }
    
    // 生成流程数据
    $data = array(
      'agentId'=>$this->agentId,
      'userId' => $this->_adminInfo['wxuserid'],
			'userName' => $this->userinfo['name'],
      'avatar'=>$this->userinfo['avatar'],
			'departmentid' => $this->userinfo['departmentid'],
			'department' => $this->userinfo['departmentname'],
			'thirdNo' => $thirdNo,
      'annex' => $obj['annex'],
      'data'=>json_encode($obj)
    );
    
    // 创建流程
   
    $flowdata = array('ApprovalNodes'=>array('ApprovalNode'=>[]));


    // 审批人去重
    $approvers = [$this->_adminInfo['wxuserid']]; 
    $temparr=['general_director_userid','center_director_userid','director_userid'];
    for($i=0;$i<count($temparr);$i++){
      
      if ($obj[$temparr[$i]]){ // 审批人不为空
        // 去掉已经出现过的审批人，保留后面的
        $userarr = array_values(array_diff(explode(',',$obj[$temparr[$i]]),$approvers));
        if (count($userarr)>0){
          // 更新已出现的审批人
          $tttt = $this->generateApprovers(implode(',',$userarr));
          $approvers = array_merge($approvers,$userarr);
          array_unshift($flowdata['ApprovalNodes']['ApprovalNode'],array(
            'NodeStatus' => 1,
            'Items' => array('Item'=>$tttt),
            'NodeAttr' => count($tttt)>1?2:1,//2-会签,1-或签
            'Position'=>$i,
            'NodeType'=>2
          ));
        }
      }

    }
    $flow = array(
			'errcode' => 0,
			'errmsg' => 'ok',
			'data' => array(
				'ThirdNo' => $thirdNo,
				'OpenTemplateId' => $flowdata['OpenTemplateId'],
				'OpenSpName' => $flowdata['OpenSpName'],
				'OpenSpstatus' => 1,
				'ApplyTime' => time(),
				'ApplyUsername' => $this->userinfo['name'],
				'ApplyUserParty' => '',
				'ApplyUserImage' => $this->userinfo['avatar'],
				'ApplyUserId' => $this->userinfo['userid'],
				'ApprovalNodes' => $flowdata['ApprovalNodes'],
				'NotifyNodes' => $flowdata['NotifyNodes'],
				'approverstep' => 0
			)
		);
    $applydata = array(
			'agentid' => $this->agentId,
			'thirdNo' => $thirdNo,
			'data' => json_encode($flow),
			'step' => 0,
			'status' => 1,
			'notifyAttr' => $flowdata['NotifyAttr']
		);
    $approvalUserid = array();
		$approvalUsername = array();
		foreach ($flowdata['ApprovalNodes']['ApprovalNode'][0]['Items']['Item'] as $item) {
			$approvalUserid[] = $item['ItemUserId'];
			$approvalUsername[] = $item['ItemName'];
		}
		$data['approvalUserid'] = implode('|', $approvalUserid);
		$data['approvalUsername'] = implode('|', $approvalUsername);
		$data['status'] = 1;

    // 保存流程
    $transaction=Yii::$app->getDb()->beginTransaction();
		try {
      
			// 保存流程数据
      
      $tempdata = new WeixinOaApprovalInfo($data);
      $tempdata->save();
      
      // 保存执行流
      $tempapplydata = new WeixinOaApprovaldata($applydata);
      $tempapplydata->save();

      // 判断是否重复
      $d = WeixinOaApprovalInfo::find()->select('count(id) cnt')->where(['and',['in','status',[1,2]],['or',['agentId'=>$this->agentId,'thirdNo'=>$thirdNo],['annex'=>$obj['annex']]]])->asArray()->one();
      if ($d['cnt'] > 1) {
        $transaction->rollback();
        return array('errorMessage'=>'当天该类型必问已经申请过');
      }
      
		} catch (\Throwable $th) {
			$transaction->rollback();
      return array('errorMessage'=>'失败：'.$th->getMessage());
		}
		$transaction->commit();
    // 发送消息
    $touser1 = array($data['approvalUserid']);
		$touserstr = implode('|', $touser1);
		$msgdata = array(
			'touser' => $touserstr,
			'msgtype' => 'textcard',
			'agentid' => $this->agentId,
			'textcard' => $this->getTextCard($this->userinfo['name'].'提交了【每日必问】审批申请',$obj,$thirdNo)
		);
    $this->sendmsg($msgdata);
    return array('applydata'=>$tempapplydata,'data'=>$tempdata);
    
  }
  

  // ========================= 每日必问 ========================
  public function actionGetproblemstates(){
    return $this->PROBLEM_STATES;
  }
  public function actionDelproblem(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    // 只允许本人修改
    $old = FzrbsCblcCd::findOne($id);

    if ($old['creator'] != $this->_adminInfo['wxuserid']){
      return array('errorMessage'=>'只有创建人才能删除');
    }
    $old->delete();
    return array('ret'=>1);
  }
  public function actionSaveproblem(){
    $obj = $this->_request;
    if (!$obj['name']) return array('errorMessage'=>'name 不能为空');
    if (!isset($obj['state'])) return array('errorMessage'=>'state 不能为空');
    $obj['typeid']=1;
    try {
      if ($obj['id']){
        $obj['creator']=$this->_adminInfo['wxuserid'];
        FzrbsCblcCd::updateAll($obj,['id'=>$obj['id']]);
      } else {
        $res = FzrbsCblcCd::find()->where(['and',['=','name',$obj['name']],['=','state',$obj['state']]])->one();
        
        if ($res) return array('errorMessage'=>"[".$obj['name']."]已经存在");
        $obj['creator']=$this->_adminInfo['wxuserid'];
        $p = new FzrbsCblcCd($obj);
        $p->save();
        $obj['id']=$p['id'];
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$obj);
  }
  public function actionGetproblems(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;
    $offset = $limit * ($page - 1);


    $where = ['and',['=','typeid',1]];

    if (isset($this->_request['state'])){
      $where[]=['=','state',$this->_request['state']];
    }
    $model = FzrbsCblcCd::find()->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('id asc')->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    $this->_request['states']=$this->PROBLEM_STATES;
    return $this->_result;
  }
  public function saveproblems($formdata){
      $problems = $formdata['datas'];
     // 保存有问题的九问
      if (!is_array($problems)){
        $problems = json_decode($problems);
      }
      $temparr = ['director','center_director','general_director'];
      foreach($problems as $problem){

        if ($problem['problem']){ // 仅保存有问题的 
          $p = array();
          $p['thirdNo'] =$formdata['thirdNo'];
          $p['typeid']=$formdata['typeid']; // 媒体分类
          $p['typeid1'] = $problem['id']; // 问题id
          $p['problem'] = $problem['problem']; // 是否有问题
          $p['remark'] = $problem['remark']; // 具体问题描述
          $p['formdate'] = $formdata['formdate'];
          $p['formtype'] = $formdata['formtype'];
          $p['creator'] = $this->userinfo['userid'];
          foreach ($temparr as $k) {
            $p[$k.'_userid'] = $formdata[$k.'_userid'];
            $p[$k.'_username'] = $formdata[$k.'_username'];
          }
          $temp = new FzrbsCblcMrbw($p);
          $temp->save();

          
        }
      }
  }
  // ============================ 流程 ==========================
  public function actionFlow(){
    $obj = $this->_request;
    $act = $obj['act'];
    $thirdNo = $obj['thirdNo'];
    $speech = $obj['speech'];
    $infodata = WeixinOaApprovalInfo::find()->where(['and',['=','agentId',$this->agentId],['=','thirdNo',$thirdNo]])->one();
    if (!$act){
      return array('errorMessage'=>'act 不能为空，参数可为：'.implode(',',$this->ACTS));
    }
    if(!$thirdNo) {
      return array('errorMessage'=>'thirdNo不能为空');
    }
    $infodata = WeixinOaApprovalInfo::find()->where(['and',['=','agentId',$this->agentId],['=','thirdNo',$thirdNo]])->one();
    // 表单数据
    $formdata = json_decode($infodata['data'],true);
    $formdata['creator'] = $infodata['userName'];
    // 要发送的消息
    $msgdata = array('msgtype' => 'textcard','agentid' => $this->agentId,);

    switch ($act) {
      case '同意':
        $status = 2;

        $transaction=Yii::$app->getDb()->beginTransaction();
        try {
          $ret = $this->changeStatus($infodata['id'],$thirdNo,$status,$speech);
          if ($ret['isfinish']){
            $msgdata['touser'] = $infodata['userId'];
            $msgdata['textcard']= $this->getTextCard('您的【每日必问】审批已通过',$formdata,$thirdNo);

            // 将有问题的九必问保存到数据库
            $this->saveproblems($formdata);
          } else {
            $msgdata['touser'] = $ret['touser'];
            $msgdata['textcard']= $this->getTextCard('有【每日必问】需要审批',$formdata,$thirdNo);
          }
        } catch (\Throwable $th) {
          $transaction->rollback();
          return array('errorMessage'=>'失败：'.$th->getMessage());
        }
        $transaction->commit();
        break;
      case '驳回':
        $status = 3;
        $ret = $this->changeStatus($infodata['id'],$thirdNo,$status,$speech);
        $toUser = array($infodata['userId']);
        // 查询审批日志，通知审批过的所有人
        $log = WeixinOaApprovalLog::find()->select('userId')->where(['and',['=','agentId',$this->agentId],['=','thirdNo',$thirdNo]])->all();
        foreach ($log as $r) {
					if (!in_array($r['userId'], $toUser)) {
						if ($r['userId']!=$this->_adminInfo['wxuserid']) $toUser[] = $r['userId'];
					}
				}
        $msgdata['touser'] = implode('|', $toUser);
        $msgdata['textcard']= $this->getTextCard('【每日必问】被驳回',$formdata,$thirdNo);
        break;
       case '撤销':
        $ret = $this->changeStatus($infodata['id'],$thirdNo,4,'');
        if ($ret){
          $toUser = array($infodata['approvalUserid']);
				  $log = WeixinOaApprovalLog::find()->select('userId')->where(['and',['=','agentId',$this->agentId],['=','thirdNo',$thirdNo]])->all();
          foreach ($log as $r) {
            if (!in_array($r['userId'], $toUser)) {
              if ($r['userId']!=$this->_adminInfo['wxuserid']) $toUser[] = $r['userId'];
            }
          }
          $msgdata['touser'] = implode('|', $toUser);
          $msgdata['textcard']= $this->getTextCard('【每日必问】被撤销',$formdata,$thirdNo);
        }
        break;
        case '催办':
          $msgdata['touser'] = $infodata['approvalUserid'];
          $msgdata['textcard']= $this->getTextCard('有【每日必问】须要尽快审批！',$formdata,$thirdNo);
          break;
      default:
        return array('errorMessage'=>'当前act值为【'.$act.'】,但是有效的值为：'.implode(',',$this->ACTS));
        break;
    }
    $result = $this->sendmsg($msgdata);
    return array('ret'=>1,'msg'=>$act.'成功');
  }
  public function actionGetdict(){
    $where=['and',['>','id',0]];
    if (isset($this->_request['typeid'])){
      $where[]=['=','typeid',$this->_request['typeid']];
    }
    return FzrbsCblcCd::find()->where($where)->all();
  }
  public function actionGetuserbyrolename(){
    $where=['and',['=','agent',$this->agentId],new Expression("FIND_IN_SET('".$this->userinfo['departmentid']."',dept)")];
    if (isset($this->_request['rolename'])){
      $where[]=new Expression("role in (SELECT id from  ".WeixinOaRole::tableName()." where rolename='".$this->_request['rolename']."')");
    } else {
      return array('errorMessage'=>'rolename不能为空');
    }
    return WeixinOaFlowrole::find()->where($where)->all();
  }
  private function changeStatus($id,$thirdNo,$status,$speech = ''){
		$wfp = new WorkflowParse();
		$transaction=Yii::$app->getDb()->beginTransaction();
		$ret = 0;
		try {
			$ret = $wfp->flowChange($thirdNo,$this->_adminInfo['wxuserid'],$status,$this->agentId,$speech);
			if ($ret){
        if ($ret['nextdata']&&$ret['nextdata']['approvalUserid']) {
          if (!$ret['isfinish']) {
            $status = 1;
          }
					WeixinOaApprovalInfo::updateAll(['status'=>$status,'approvalUserid'=>$ret['nextdata']['approvalUserid'],'approvalUsername'=>$ret['nextdata']['approvalUsername']],"id=".$id);
				} else {
					WeixinOaApprovalInfo::updateAll(['status'=>$status],"id=".$id);
				}
			}
			
		} catch (\Throwable $th) {
			$transaction->rollback();
			throw $th;
		}
		$transaction->commit();
		return $ret;
	}
  private function getTextCard($title,$data,$thirdNo) {
    if ($data['typeid']&&!$data['typeidname']){
      $t = FzrbsCblcCd::findOne($data['typeid']);
      $data['typeidname']=$t['name'];
    }
		$method = 'view';
		if (isset($data['method'])) $method = $data['method'];

		return array(
			'title' => $title,
			'description' => '<div class="normal">部门：' . $data['typeidname'] . '</div><div class="normal">日期：' . $data['formdate'] . '</div><div class="normal">班次：' . $this->formtypes[$data['formtype']] . '</div><div class="normal">申请人：' . $data['creator'],
			'url' => 'https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/mrbw/edit?method=' . $method . '&thirdNo=' . $thirdNo,
			'btntxt' => '详情'
		);
	}
  
  private function sendmsg($msg)

  {
    $msg['touser']='linting';
    return WxQyhJk::sendMessage($msg['agentid'],$msg['touser'],$msg['textcard'],'textcard');

  }
  /**
   * 获取企业微信用户信息
   */
  private function getUserinfo($userid)
  {
      $userinfo = WeixinOAUserInfo::find()->where(['=', 'userid', $userid])->asArray()->one();
      return $userinfo;
  }
}