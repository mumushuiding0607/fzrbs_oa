<?php

namespace app\modules\api\controllers;
use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\commons\WorkflowParse;
use app\modules\api\models\FzrbsBudgetBalance;
use app\modules\api\models\FzrbsBudgetCompany;
use app\modules\api\models\FzrbsBudgetContract;
use app\modules\api\models\FzrbsBudgetDict;
use app\modules\api\models\FzrbsBudgetEnteraccount;
use app\modules\api\models\FzrbsBudgetHistory;
use app\modules\api\models\FzrbsBudgetInvoice;
use app\modules\api\models\FzrbsBudgetProject;
use app\modules\api\models\FzrbsBudgetRole;
use app\modules\api\models\FzrbsBudgetTarget;
use app\modules\api\models\FzrbsBudgetTemplate;
use app\modules\api\models\FzrbsCompany;
use app\modules\api\models\FzrbsContract;
use app\modules\api\models\FzrbsContractPaycollection;
use app\modules\api\models\FzrbsInvoice;
use app\modules\api\models\FzrbsInvoiceCheck;
use app\modules\api\models\FzrbsRole;
use app\modules\api\models\WeixinOaApprovaldata;
use app\modules\api\models\WeixinOaApprovalInfo;
use app\modules\api\models\WeixinOaApprovalLog;
use app\modules\api\models\WeixinOaDepartment;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinOaNotifyLog;
use app\modules\api\models\WeixinOaRole;
use app\modules\api\models\WeixinOaTemplates;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\models\WxDepartment;
use app\modules\weixin\commons\Uploader;
use app\modules\weixin\Weixin;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\models\FzrbsContractPaycondition;
use app\modules\api\models\FzrbsOperationLog;
use app\modules\api\models\WeixinFinanceCompany;
use app\modules\api\models\WeixinFinanceInfo;
use Exception;
use Illuminate\Support\Arr;
use phpDocumentor\Reflection\DocBlock\Tags\Return_;
use Twig\ExpressionParser;
use vierbergenlars\SemVer\expression as SemVerExpression;
use yii\db\Expression;


/**
 * 预决算信息系统
 * 查看项目列表时：如果有设置年度指标则可以查看年度指标关联的所有部门，否则只能查看自己和本部门的数据
 * 年度指标及完成情况：如果有设置年度指标，则查看年度指标关联的所有部门，否则只统计本部门的数据
 */
class BudgetController extends ApiBase{
  public $modelClass = 'app\modules\api\models\WeixinOAUserInfo';
  protected $statusCn = array('','审批中','已同意','已驳回','已取消');
  protected $protectCn = array('预算审批','决算审批','待提交绩效','无效');
  protected $INCOME_DICID = 15;
  protected $EXPEND_DICID = 16;
  protected $INVALID_CODE = 4; 
  protected $agentId = 1000080;
  protected $CHARGER_ROLE = 27;
  protected $NEW_MEDIA_PROJECT = 7;
  protected $savepath = 'canteen/excel';
  protected $userinfo = array();
  protected $START_PROJECT =1; // 待立项
  protected $BUDGET_PROJECT=2; // 待预算
  protected $FINAL_PROJECT = 3; // 待决算
  protected $READYTOSUBMIT_PROJECT = 4; // 待提交
  protected $SUBMITTED_PROJECT = 5; // 已提交

  // 项目类型
  protected $ONLINE_PROJECT = 5; // 非报创新线上
  protected $ACT_AD_TYPE = 9;//活动促广告业务
  protected $OFFLINE = 6; // 非报创新线下
  protected $PURE_NEWMEDIA_TYPE = 7;//纯新媒体业务
  protected $OTHERS_TYPE = 8; // 其他

 
  // 分管领导角色id
  protected $LEADER_ROLE_ID=6;
  // 经管负责人
  protected $ECONOMIC_CHARGER=51;
  // 部门负责人
  protected $DEPTCHARGER_ROLE_ID=31;
  protected $PROJECTCHARGER_ROLE_ID=32;
  // 编委会
  protected $EDITORIAL_BOARD=24;
  // 经审会
  protected $ECONOMIC_BOARD=25;
  // 法务
  protected $LEGAL_ROLE=27;
  // 内审
  protected $INTERNEL_ROLE=28;
  // 会计
  protected $ACCOUNT_ROLE=5;
  // 公司会计
  protected $COMPANY_ROLE=14;

  protected $AMOUNT = 300000;
  public function init()
  {
      parent::init();

      $this->userinfo = $this->getUserinfo($this->_adminInfo['wxuserid']);

  }
  // 判断当前审批节点是否支持连审
  private function continuable($noderoleid){ 
    return !in_array($noderoleid,array($this->LEADER_ROLE_ID,$this->ECONOMIC_CHARGER));
  }
  public function actionViewfile (){
            // $attachment = Yii::app()->request->getQuery('attachment');
            $attachment = $this->_request['attachment'];
            $imagefile = substr($attachment,0,10)=='/uploaded/'?'/www/web/fzrbs_oa/web'.$attachment:$this->attachmentpath.$attachment;
            
            
            if(is_file($imagefile)){
                    $ext = strtolower(strrchr($attachment,'.'));
                    if(in_array($ext, array('.doc','.docx'))){
                            $imagefile = $this->getModule()->weburlpre.'/assets/img/word.png';
                    }else if(in_array($ext, array('.xls','.xlsx'))){
                            $imagefile = $this->getModule()->weburlpre.'/assets/img/excel.png';
                    }else if(in_array($ext, array('.txt'))){
                            $imagefile = $this->getModule()->weburlpre.'/assets/img/txt.png';
                    }else if(in_array($ext, array('.pdf'))){
                            $imagefile = $this->getModule()->weburlpre.'/assets/img/pdf.png';
                    }
                    $imageinfo=getimagesize($imagefile);
                    $imagetype=$imageinfo['mime'];
                    switch ($imagetype){
                            case 'image/jpeg':
                                    header ("Content-type: image/jpeg");
                                    $imageres=imagecreatefromjpeg($imagefile);
                                    imagejpeg($imageres);
                                    break;
                            case 'image/gif':
                                    header ("Content-type: image/gif");
                                    $imageres=imagecreatefromgif($imagefile);
                                    imagegif($imageres);
                                    break;
                            case 'image/png':
                                    header ("Content-type: image/png");
                                    $imageres=imagecreatefrompng($imagefile);
                                    imagepng($imageres);
                                    break;
                    }
            }
            exit;
    }
  public function actionGetapps(){
    return array('datas'=>[
      array('title'=>'非报业务信息系统','url'=>'/finance/budget/index/','icon'=>'/uploaded/icons/finance/yujuesuan.svg'),
      array('title'=>'合同管理','url'=>'/finance/contract/listc/','icon'=>'/uploaded/icons/finance/hetong.svg'),
      array('title'=>'开票申请系统','url'=>'/finance/invoice/list/','icon'=>'/uploaded/icons/finance/fapiao.svg'),
      array('title'=>'欠款催收管理','url'=>'/finance/contract/debt/','icon'=>'/uploaded/icons/finance/debt.svg'),
      array('title'=>'广告管理','url'=>'/finance/order/orderlist/','icon'=>'/uploaded/icons/finance/广告.svg'),

      
    ]);
  }
  // ---------------------------- 付款审批 -------------------------------
  /**
   * 付款审批申请
   */
  public function actionStartpayment(){
    $id = $this->_request['id'];
    
    if (!$id) {
      $balance = $this->_request;
    } else {
      $balance = FzrbsBudgetBalance::find()->where(['id'=>$id])->asArray()->one();
    }
    if (!$balance['projectid']){
      return array('errorMessage'=>'projectid不能为空');
    }
    

    // 支出才能发起
    if (intval($balance['type']) != $this->EXPEND_DICID) {
      return array('errorMessage'=>'不是支出类型');
    }
  
    // 只有项目经办才能发起
    $project = FzrbsBudgetProject::findOne($balance['projectid']);
    if ($project->creator != $this->_adminInfo['wxuserid']) {
      return array('errorMessage'=>'不是该项目经办');
    }
    // 查询对应的合同
    $fileurls = '';

    // 查询对应的发票id
    $invoiceids = '';
    if ($project->contractids){
      $tempi = FzrbsInvoice::find()->select('group_concat(id) as ids')->where(['in','contractid',explode(',',$project->contractids)])->asArray()->one();
      if($tempi) $invoiceids = $tempi['ids'];
      $tempc2 = FzrbsContract::find()->select('fileurls')->where(['in','id',explode(',',$project->contractids)]);
      $fileurls=implode(',',$tempc2->select('fileurls')->column());
      
    }
    // 生成付款审批数据
    $thirdNo = $this->getMsecTime();
    $userinfo = $this->userinfo;
    $data = array(
      'userId' => $userinfo['userid'],
      'userName' => $userinfo['name'],
      'departmentid' => $userinfo['departmentid'],
      'department' => $userinfo['departmentname'],
      'thirdNo' => $thirdNo,
      'reason' => $balance['finalnote']?$balance['finalnote']:$balance['budgetnote'],
      'payStatus' => intval($_POST['payStatus'])>0? intval($_POST['payStatus']):0, // 付款方式
      'amount' => $balance['final']?$balance['final']:$balance['budget'], //数字金额
      // 'invoice' => $_POST['invoice'], // 发票
      'amountsType'=>0, // 审批类型 0-付款审批,1-工资审批
      'fileurls'=>$fileurls,//合同附件
      'invoiceids'=>$invoiceids,//对应发票的id
      'otherfileurls'=>$balance['finalfileurls']?$balance['finalfileurls']:$balance['budgetfileurls'],//其他附件'
    );
    try {
      $obj = new WeixinFinanceInfo($data);
      $obj->save();
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    $msgdata = [
      'touser' => $userinfo['userid'],
      'msgtype' => 'textcard',
      'agentid' => 1000066,
      'textcard' => [
          'title' => '【非报系统】发起付款审批，点击查看并补全信息',
          'description' => '<div class="normal">项目名称：' . $project['title'].'</div><div class="normal">支出项目：' . $balance['title'].'</div>',
          'url' => 'https://fzrb.fznews.com.cn/index.php?r=qiyehao/qyfinance/edit&type='.$data['amountsType'].'&thirdNo='.$data['thirdNo'],
          'btntxt' => '详情'
          
      ]
    ];
    $this->sendmsg($msgdata);
    return array('data'=>'请点开企业微信付款审批通知，查看并补全信息');
  }


  private function send($approvalUserid,$title,$data){

    if (!$approvalUserid) return;
    $msgdata = [
      'touser' => $approvalUserid,
      'msgtype' => 'textcard',
      'agentid' => $this->agentId,
      'textcard' => [
          'title' => $title,
          'description' => '<div class="normal">项目：' . $data['title'].'</div>',
          'url' => "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/budget/view?projectid=".$data['id']."&thirdNo=".$data['thirdno'],
          'btntxt' => '详情'
          
      ]
    ];
    $this->sendmsg($msgdata);
  }
  private function sendChanges($approvalUserid,$title,$data,$changes){
    if (!$approvalUserid) return;
    $description = "<div class='normal'>".$changes['title']."</div>";
    foreach ($changes['items'] as $item) {
      $description .= "<div class='normal'>".$item['title']."：【".$item['oldval']."】修改成【".$item['newval']."】</div>";
    }
    $msgdata = [
      'touser' => $approvalUserid,
      'msgtype' => 'textcard',
      'agentid' => $this->agentId,
      'textcard' => [
          'title' => $title,
          'description' => $description,
          'url' => "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/budget/view?projectid=".$data['id']."&thirdNo=".$data['thirdno'],
          'btntxt' => '详情'
          
      ]
    ];
    $this->sendmsg($msgdata);
  }
  private function sendmsg($data)
  {
      $result = WxQyhJk::sendMessage($data['agentid'],$data['touser'],$data['textcard'],'textcard');
      return $result;
  }
  public function actionFlowact(){
    
    $postdatas = $this->_request;
    if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空，关掉弹窗、重新打开，再尝试操作！');
    if (!$postdatas['act']) return array('errorMessage'=>'act为空,可选：agree,cancel,reject');

    switch ($postdatas['act']) {
      case 'agree':
        return $this->actionAgree();
      case 'offlineAgree':
        return $this->actionOfflineAgree();
      case 'cancel':
        return $this->actionCancel();
      case 'reject':
        return $this->actionReject();
      case 'continue':
        return $this->actionContinue();
      case 'urge':
        return $this->actionUrge();
      default:
        # code...
        break;
    }
  }
  private function updateAfterFlowChange($ret,$thirdNo,$status,$condition){

    
    
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      
      
      $project = FzrbsBudgetProject::find()->alias('p')->select('p.*,d.label as approvaltypename')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=p.approvaltype and d.type='审批类型'")->where(["p.thirdno"=>$thirdNo])->asArray()->one();
      if (!$project){
        return array('errorMessage'=>'找不到项目信息');
      }
      $actname = $project['approvaltypename'];
      unset($project['approvaltypename']);
   
      
      $info = WeixinOaApprovalInfo::find()->where(["thirdNo"=>$thirdNo,"agentId"=>$this->agentId])->asArray()->one();
      if (!$info) throw new Exception("找不到流程信息");
      $infodata = json_decode($info['data'],1);
      $historystate = $infodata['approvaltype'];
      
      if ($ret){
        if($ret['approveres']){
          WeixinOaApprovaldata::updateAll($ret['approveres'],'id='.$ret['approveres']['id']);
        }

   
        if($ret['logdata']){
            $log = new WeixinOaApprovalLog($ret['logdata']);
            $log->save();
        }
        
         if($ret['isfinish']) {//没有下个审批人,可能是会签，直接结束
           
            // 更新项目信息
            $flowtype = $infodata['approvaltype'];
            $state = $project['state'];
            $history = $project['history'];
            if ($infodata['approvaltype']){
              $state=$infodata['approvaltype'];
              if (!$history){
                $history = $infodata['approvaltype'];
              }else{
                $history.= ','.$infodata['approvaltype'];
              }
            }
            if ($state<=$this->SUBMITTED_PROJECT){
              $state++;
            }
            
            
                    
            $par=['thirdno'=>null,'state'=>$state,'history'=>$history,'offline'=>0];
            if ($state==$this->SUBMITTED_PROJECT){//提交计量
              if (!$project['submitdate']) $par['submitdate']=date('Y-m-d');
              
              // 提交计量和项目状态是两条线相互不影响
              $par['directsubmit']=1;
              if(!$par['serial']){
                $par['serial'] = $this->getSerialNo($project['departmentid']);
              }
              
              
            }
            // 没到提交计量步骤,直接提交计量
            if ($project['state']<$this->READYTOSUBMIT_PROJECT&&$state==$this->SUBMITTED_PROJECT){
              $actname="提交计量";
              $historystate = $this->READYTOSUBMIT_PROJECT;
              $par['directsubmit']=1;
              $par['submitdate']=date('Y-m-d');
              $par['state']=$project['state'];
            }
            
            // 如果是撤销计量
            if ($flowtype==6){
              if ($project['directsubmit']==1){
                $par['state']=$project['state'];
              }else{
                $par['state']=$this->FINAL_PROJECT;
              }
              $par['directsubmit']=0;
              $par['submitdate']=null;
              

            }else {
              // 如果更新状态等于提交计量，但是历史纪录中有提交计量，则更新状态为已提交
              if ($par['state']==$this->READYTOSUBMIT_PROJECT&&preg_match("/$this->READYTOSUBMIT_PROJECT/", $project['history'])){
                $par['state']=$this->SUBMITTED_PROJECT;
              }
            }
          
            FzrbsBudgetProject::updateAll($par,['id'=>$project['id']]);
  
          // 更新流程信息表
            $temp = ['status'=>$status];
    
            $data = array(
              'projectid'=>$project['id'], // 项目id
              'starttime'=>$project['starttime'], // 立项时间
              'hascontract'=>$project['contractids']?true:false,
              'state'=>$project['state'], // 审批类型
              'chargername'=>$project['chargername'], // 项目负责人
              'approvers'=>'', // 法务、内审、会计
              'thirdNo'=>$thirdNo,
              'approvaltype'=>$infodata['approvaltype']
            );
            // 是预决算审批，审批通过
            if ($status=2&&($flowtype==$this->BUDGET_PROJECT)){ //是预算审批，要保存项目负责人的审批纪录
              
              // 查询分管领导审批节点
              $approveres = WeixinOaApprovaldata::find()->where(['and',["=","agentid",$this->agentId],["=","thirdNo",$thirdNo]])->one();
              if($approveres){
                $approvearr = json_decode($approveres['data'],true);
                foreach ($approvearr['data']['ApprovalNodes']['ApprovalNode'] as $node) {
                  $items = $node['Items']['Item'];
                  
                  if ($node['NodeRoleid']==$this->PROJECTCHARGER_ROLE_ID){ //项目负责人
                    $data['projectcharger']=$this->getApproverAndDate($items);
                  }
                  if ($node['NodeRoleid']==$this->DEPTCHARGER_ROLE_ID){ //部门负责人
                    $data['deptcharger']=$this->getApproverAndDate($items);
                  }
                  if ($node['NodeRoleid']==$this->LEADER_ROLE_ID){ //分管领导
                    $data['leaders']=$this->getApproverAndDate($items);
                  }
                  if (!$node['fileurls']){ //如果存在文件，说明是线下会签，显示文件，否则返回审批人及日期
                    
                    if($node['NodeRoleid']==$this->EDITORIAL_BOARD){// 编委会
                      $data['editorialboard']=$this->getApproverAndDate($items);
                    }
                    if ($node['NodeRoleid']==$this->ECONOMIC_BOARD){ //经审会
                      $data['economicalboard']=$this->getApproverAndDate($items);
                    }
    
                  }else{ // 
                    
                    if($node['NodeRoleid']==$this->EDITORIAL_BOARD){// 编委会
                      $data['editorialFileurls']=$node['fileurls'];
                    }
                    if ($node['NodeRoleid']==$this->ECONOMIC_BOARD){ //经审会
                      $data['economicalFileurls']=$node['fileurls'];
                    }
    
                  }
                  


                  
    
    
                }
                
              }

              
              
            }
          // 审批通过后查询项目当前最新信息并保存
          
          $tempdata = $this->getProjectModel(['and',['=','p.id',$project['id']]],'')->asArray()->one();
        
          $pt = new FzrbsBudgetHistory(array('state'=>$historystate,'projectid'=>$project['id'],'data'=>json_encode($tempdata)));
     
          $pt->save();
          
          $temp['data'] = json_encode($data);
          WeixinOaApprovalInfo::updateAll($temp,["thirdNo"=>$thirdNo,"agentId"=>$this->agentId]);
        }else if ($ret['nextdata']&&$ret['nextdata']['approvalUserid']) { // 有下个审批人，说明没结束

         
          if (!$ret['isfinish']) {
            $status = 1;
          }
          
          if(isset($condition['offline'])){
            $project['offline']=$condition['offline'];
            if($project['offline']==1){
              $project['offlinenote']=$this->userinfo['name'].':请线下上会处理';

            }
            

            
            FzrbsBudgetProject::updateAll(new FzrbsBudgetProject($project),["id"=>$project['id']]);

          }

          // 如果是项目决算、如果下一步是吴金铵审批，则跳过下一步，并设置
         

         
          WeixinOaApprovalInfo::updateAll(['status'=>$status,'approvalUserid'=>$ret['nextdata']['approvalUserid'],'approvalUsername'=>$ret['nextdata']['approvalUsername']],["thirdNo"=>$thirdNo,"agentId"=>$this->agentId]);
          
          $noticeuserids=$ret['nextdata']['approvalUserid'];
          if ($noticeuserids){
            // 通知人若包含当前审批人，则去掉
            $tempNoticeArr = explode('|',$noticeuserids);
            $tempNoticeArr = array_diff($tempNoticeArr,[$this->_adminInfo['wxuserid']]);
            if ($condition['notNotice']){
              $tempNoticeArr = array_diff($tempNoticeArr,explode('|',$condition['notNotice']));
            }
            $noticeuserids=implode('|',array_diff($tempNoticeArr,explode('|',$condition['notNotice'])));
          }
         
          $this->send($noticeuserids,$info['userName'].'的'.$actname.'申请',$project);

        }else if ($condition['act']=='cancel'){
          
          WeixinOaApprovalInfo::updateAll(['status'=>$status],["thirdNo"=>$thirdNo,"agentId"=>$this->agentId]);
          FzrbsBudgetProject::updateAll(['thirdno'=>null,'offline'=>0,'reject'=>0],["thirdno"=>$thirdNo]);
        }  else {
          
          if (!$ret['isfinish']) { // 可能是会签
            $status = 1;
          }
          WeixinOaApprovalInfo::updateAll(['status'=>$status],["thirdNo"=>$thirdNo,"agentId"=>$this->agentId]);
          if ($condition['act']=='cancel'){
  
            FzrbsBudgetProject::updateAll(['thirdno'=>null,'offline'=>0,'reject'=>0],["thirdno"=>$thirdNo]);
       
          }
        }
      } else {
        
          if ($condition['act']=='cancel'){

            WeixinOaApprovalInfo::updateAll(['status'=>$status],["thirdNo"=>$thirdNo,"agentId"=>$this->agentId]);
            FzrbsBudgetProject::updateAll(['thirdno'=>null,'offline'=>0,'reject'=>0],["thirdno"=>$thirdNo]);
          }
      }

    } catch (\Throwable $th) {
      $transaction->rollBack();
      throw $th;
    }
    $transaction->commit();
    
  }

  // 刷新指定部门对应项目的项目编码，其中仅部门简码更新，其它不动
  public function actionRefreshprojectdeptcode(){
    // 需要管理员权限
    $hasauth = $this->haspower('管理',$this->agentId,'','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【管理】权限,请找财务确认并刷新');
    }
    $departmentid = $this->_request['departmentid'];
    if (!$departmentid){
      return array("errorMessage"=>"departmentid不能为空");
    }
    $dept = WeixinOaDepartment::findOne($departmentid);
    if(!$dept['code']||$dept['code']=='') return array("errorMessage"=>"未设置部门简码无法更新");
  
    $where = ['and',['=','deleted',0],['=','departmentid',$departmentid],new Expression("LOCATE('".$dept['code']."',serial)<1"),['or',['=','state',$this->SUBMITTED_PROJECT],['=','directsubmit',1]]];
    $model = FzrbsBudgetProject::find()->where($where);
    $count = $model->count();
    
    $datas = $model->all();
    
    try {

      foreach ($datas as $p) {
        $serialNo = $this->getSerialNo($departmentid);
        $p->serial = $serialNo;
        $p->save();
      }

    } catch (\Throwable $th) {
      return array("errorMessage"=>$th->getMessage());
    }
    
    return array("data"=>"更新条数：".$count);

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

  private function getSerialNo($departmentid){
    if (!$departmentid) {
      throw new Exception('departmentid为空');
    }
    $dept = WeixinOaDepartment::find()->where(['and',['=','id',$departmentid]])->one();
    if(!$dept['code']) return '';
    // 获取结账年份，如果没有则使用当前年份
    $closeYearModel = FzrbsBudgetDict::find()->where(['type'=>'结账年份'])->one();
    $year = $closeYearModel ? $closeYearModel['value'] : date('Y');
    $serial = $dept['code'].'-'.$year;
    $p = FzrbsBudgetProject::find()->where(['and',['like','serial',$serial],['=','deleted',0]])->orderBy('serial desc')->one();
    if (!$p) {
      $serial.='-001';
    }else{
      $serial = $p['serial'];
      $serial = substr($serial,0,strlen($serial)-3);
      $serial.=sprintf('%03d',intval(substr($p['serial'],-3))+1);
    }

    return $serial;
  }
  // 线下上会后，经办上传会议纪要通过
  public function actionOfflineAgree(){
    $userid = $this->_adminInfo['wxuserid'];
    $obj = $this->_request;
    if (!$obj['thirdNo']) return array('errorMessage'=>'thirdNo为空');
    $data = WeixinOaApprovalInfo::find()->alias('a')->select("a.*,p.offline,p.id as projectid")->leftJoin(['p'=>FzrbsBudgetProject::tableName()],"p.thirdno=a.thirdNo")->where(['and',['=','a.thirdNo',$obj['thirdNo']],['=','agentId',$this->agentId]])->asArray()->one();
    if (!$data) return array('errorMessage'=>'流程不存在');

   
    if ($data['approvalUserid']!=$userid){
      return array('errorMessage'=>'只有【'.$data['approvalUsername'].'】才能操作');
    }

    $status = 2;
    $transaction = Yii::$app->getDb()->beginTransaction();
    
    try {
      $ret = $this->changeStatus($userid,$data['id'],$data['thirdNo'],$status,$obj);

      $this->updateAfterFlowChange($ret,$data['thirdNo'],$status,$obj);
      
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    return array('data'=>$ret);
  }
/**
   * 同意
   */
  public function actionAgree(){
    
    $postdatas = $this->_request;
   
    $userid = $this->_adminInfo['wxuserid'];
    
    if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空');
    $data = WeixinOaApprovalInfo::find()->alias('a')->select("a.*,p.offline,p.id as projectid")->leftJoin(['p'=>FzrbsBudgetProject::tableName()],"p.thirdno=a.thirdNo")->where(['and',['=','a.thirdNo',$postdatas['thirdNo']],['=','agentId',$this->agentId]])->asArray()->one();
    // 判断是否被锁定
    if ($data['offline']==1){
      if ($postdatas['continuous']==1){
        return array('errorMessage'=>'');
      }
      return array('errorMessage'=>'线下上会审批中');
    }
    if ($data['reject']==1){
      return array('errorMessage'=>'当前流程处于驳回状态，等经办重新提交后才能审批');
    }
    // 是否是当前审批人
    if ($data['approvalUserid'] && !in_array($userid,explode('|',$data['approvalUserid']))){
      return array('errorMessage'=>'当前审批人是：'.$data['approvalUsername']);
    }
    
    $status = 2;

    try {
      $ret = $this->changeStatus($userid,$data['id'],$postdatas['thirdNo'],$status,$postdatas);
      
      $this->updateAfterFlowChange($ret,$postdatas['thirdNo'],$status,$postdatas);

      
      if ($ret && $ret['isfinish']==1){
        if ($data['projectid']){
          $project = FzrbsBudgetProject::findOne($data['projectid']);
          $this->send($data['userId'],'项目审批申请【已通过】',$project);
        }
        
      }
		
    } catch (\Throwable $th) {
      
      return array('errorMessage'=>$th->getMessage());
      // throw $th;
    }
    
    
    return array('data'=>$ret);
  }
  public function actionUrge(){
    
    if (!$this->_request['thirdNo']) return array('errorMessage'=>'thirdNo为空');
    $p = FzrbsBudgetProject::find()->alias('p')->select('p.*,u.name as creatorname,d.label as statename')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=p.approvaltype and d.type='审批类型'")->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=p.creator')->where(["p.thirdno"=>$this->_request['thirdNo']])->asArray()->one();

    
    $temp=[];
    if ($p['offline']){
      // 线下上会中，通知创建人ApplyUserId
      $info=WeixinOaApprovalInfo::find()->where(['and',['=','thirdNo',$this->_request['thirdNo']],['=','agentId',$this->agentId]])->one();
      $temp[] = $info['approvalUserid'];
      

    }else{
      $approveres = WeixinOaApprovaldata::find()->where(['and',['=','thirdNo',$this->_request['thirdNo']],['=','agentId',$this->agentId]])->asArray()->one();
      if($approveres){
        $approvearr = json_decode($approveres['data'],true);
        
          $curnode = $approvearr['data']['ApprovalNodes']['ApprovalNode'][$approvearr['data']['approverstep']];
          if ($curnode['NodeStatus']==1){

            foreach ($curnode['Items']['Item'] as $item) {
              if ($item['ItemStatus']==1){
                $temp[] = $item['ItemUserId'];
              }
            }
            
          }
        }
      
      
    
    }
    
    
    
 
    if ($temp){
      $userids = implode('|',$temp);
      try {
        $this->send($userids,$p['creatorname'].'的'.$p['statename'].'审批【催办】',$p);
      } catch (\Throwable $th) {
         return array('errorMessage'=>$th->getMessage());
      }
      $this->_operationlog(['catalog' => "催办【".$this->_request['thirdNo']."】：", 'remark' => "非报信息系统催办【".$this->_request['thirdNo']."】,待办人：".$userids]);
      return array('ret'=>1,'userids'=>$userids);
    }
    

    return array('ret'=>1);
    
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
  public function actionContinue(){
    $userid = $this->_adminInfo['wxuserid'];
    $postdatas = $this->_request;
    $data = WeixinOaApprovalInfo::find()->alias('a')->select("a.*,p.offline,p.id as projectid")->leftJoin(['p'=>FzrbsBudgetProject::tableName()],"p.thirdno=a.thirdNo")->where(['and',['=','a.thirdNo',$postdatas['thirdNo']],['=','agentId',$this->agentId]])->asArray()->one();
    // 是否是项目发起人
    if ($data['userId']!=$userid){
      return array('errorMessage'=>'不是项目发起人：'.$data['userName']);
    }
    
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      
      $project = FzrbsBudgetProject::find()->where(['thirdno'=>$postdatas['thirdNo']])->one();
      
      FzrbsBudgetProject::updateAll(['reject'=>0],['thirdno'=>$postdatas['thirdNo']]);

      $msgdata = [
        'touser' => $data['userId'].'|'.$data['approvalUserid'],
        'msgtype' => 'textcard',
        'agentid' => $this->agentId,
        'textcard' => [
            'title' => '项目审批申请【驳回后重新提交】',
            'description' => '<div class="normal">项目：' . $project['title'].'</div>',
            'url' => "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/budget/view?projectid=".$project['id']."&thirdNo=".$project['thirdno'],
            'btntxt' => '详情'
            
        ]
      ];
      $this->sendmsg($msgdata);

      
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
		return array('data'=>array('ret'=>1));
  }
  public function actionReject(){//驳回
    $userid = $this->_adminInfo['wxuserid'];
		$postdatas = $this->_request;
    if (!$postdatas['speech']) return array('errorMessage'=>'审批意见不能为空');
    $data = WeixinOaApprovalInfo::find()->alias('a')->select("a.*,p.offline,p.id as projectid,p.reject as reject")->leftJoin(['p'=>FzrbsBudgetProject::tableName()],"p.thirdno=a.thirdNo")->where(['and',['=','a.thirdNo',$postdatas['thirdNo']],['=','agentId',$this->agentId]])->asArray()->one();
   
    if ($data['reject']==1){
      return array('errorMessage'=>'已驳回，不要重复操作');
    }
    // 判断是否被锁定
    if ($data['offline']==1){
      return array('errorMessage'=>'线下上会审批中，不能操作');
    }
    // 是否是当前审批人
    if ($data['approvalUserid'] && !in_array($userid,explode('|',$data['approvalUserid']))){
      return array('errorMessage'=>'当前审批人是：'.$data['approvalUsername']);
    }
    
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      
      $project = FzrbsBudgetProject::find()->where(['thirdno'=>$postdatas['thirdNo']])->one();
      // 锁定项目
      FzrbsBudgetProject::updateAll(['reject'=>1],['thirdno'=>$postdatas['thirdNo']]);


      $msgdata = [
        'touser' => $data['userId'],
        'msgtype' => 'textcard',
        'agentid' => $this->agentId,
        'textcard' => [
            'title' => '项目审批申请【被驳回】',
            'description' => '<div class="normal">驳回原因：' . $postdatas['speech'].'</div>',
            'url' => "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/budget/view?projectid=".$project['id']."&thirdNo=".$project['thirdno'],
            'btntxt' => '详情'
            
        ]
      ];
      $this->sendmsg($msgdata);

      
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
		return array('data'=>array('ret'=>1));
	}
  public function actionIndex(){
    
  }

  public function actionDepartment()
    {
        set_time_limit(0);
        $parentId = isset($this->_request['parentid']) ? $this->_request['parentid'] : 0;
        $localDepartment = intval($this->_request['local']) ? 1 : 0;
        // 本地数据表部门信息
        $where = [
          'and',
          ['>', 'id', 0],
      ];
      if (isset($this->_request['childrenId']) && $this->_request['childrenId']) {
          $where[] = ['in', 'id', explode(',', $this->_request['childrenId'])];
      }
      $data = [];
      if (intval($this->_request['firstRequest']) === 1 && $parentId > 0) {
          $rootNode = WxDepartment::find()->where(['=', 'id',  $parentId])->orderBy('order desc')->one();
          if ($rootNode) {
              $data[] = ['title' => $rootNode->name, 'key' => strval($rootNode->id), 'value' => strval($rootNode->id), 'isLeaf' => false];
          }
      } else {
          $res = WxDepartment::find()->where($where)->orderBy('order desc')->all();
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
                      $node['isLeaf'] = false;
                      if ($this->_request['showAll']) {
                          foreach ($users as $user) {
                              $children[] = ['title' => $user['name'], 'key' => $user['userid'], 'value' => $user['userid'], 'isLeaf' => true];
                          }
                      }
                  }
              }
              if ($children) {
                  $node['isLeaf'] = false;
                  $node['children'] = $children;
              }
              $data[] = $node;
          }
          if (intval($this->_request['user']) === 1) {
              $users = WeixinOAUserInfo::find()->where(['and',['=', 'departmentid', $parentId],['=', 'status', 1],['=', 'st', 1]])->all();
              if ($users) {
                  foreach ($users as $user) {
                      $data[] = ['title' => $user['name'], 'key' => $user['userid'], 'value' => $user['userid'], 'isLeaf' => true];
                  }
              }
          }
      }
      $this->_result['data'] = $data;
        return $this->_result;
    }

  // ===================== 首页 ===========================

  public function actionHome(){
    $result = array();
    $year = date('Y');
    // 今年指标查询
    $target = $this->getTarget($year);
    
    $result['target'] = $target;

    $dept = $this->getDepts();
    // 获取各项业务及相关数据

    // $result['projecttypes'] = $this->actionVolumestat($target['dept'],$year);
    // $dept = $this->getDepts();
    // $result['projecttypes'] = $this->actionVolumestat(implode(',',$dept),$year);
 
    // 业务考核报表
    $where = ['and',['>','p.id',0],['=','p.state',3]];
    if ($target['dept'] && $target['dept']!='all') $where[] = ['in' , 'p.departmentid' , explode(',',$target['dept'])];
    $projects = FzrbsBudgetProject::find()->alias('p')->select('p.*,u.avatar as avatar,u.name,d.label as typename')->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=p.creator')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],'p.type=d.value')->where($where)->limit(5)->orderBy('inserttime desc')->asArray()->all();
    $result['projects'] = $projects;

    // 今年到目前为止的收入：先查询所有部门、再查询今年所有状态为“已提交”的项目

    $result['targetProgress'] = $this->getTargetprogress($year);

    // 新媒体收入
    $result['newmedia'] = $this->getNewmediastat($year,$target['dept']);
    if ($result['targetProgress']['finalincome']!=0) $result['newmedia']['incomepercent'] = intval($result['newmedia']['finalincome']/$result['targetProgress']['finalincome']*100);
    if ($result['targetProgress']['finalprofit']!=0) $result['newmedia']['profitpercent'] = intval(($result['newmedia']['finalprofit'])/($result['targetProgress']['finalprofit'])*100);

    // 年度经营情况统计
    $result['businessstat'] = $this->getBusinessstat();

    $result['userinfo'] = $this->userinfo;

    $result['canSeeDepartments']=$dept;
    return $result;
  }
  // 指标获取
  private function getTarget($year) {

    // 如果是社领导和财 务主管可以查看所有
    $canseeall = false;
    if ($this->userinfo['departmentid'] == 31) { 
      $canseeall = true;
    }

    if ($canseeall) {
      $target = FzrbsBudgetTarget::find()->select("sum(income) as income,sum(profit) as profit,year")->where(['and',['=','year',$year]])->groupBy('year')->asArray()->one();
      $target['dept'] = 'all';
    } else {
      $target = FzrbsBudgetTarget::find()->alias('r')->select('r.*,u.avatar as avatar,u.name as creatorname')->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=r.creator')->where(['and',['=','year',$year],new Expression("FIND_IN_SET(".$this->userinfo['departmentid'].", r.dept)")])->asArray()->one();
    }
    // 如果对应部门没有设置指标
    
    $target['head'] = $year.'年度指标';
    if (!$target['dept']) $target['dept']=$this->userinfo['departmentid'];
    return $target;
  }
  public function actionApprovallist(){

    if ($this->_request['projectstate']==-1){
      return $this->actionApprovalhistory();
    }

    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'id desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    
    $where = ['and',['!=','reject',1],new Expression("p.thirdno is not null and p.thirdno!=''"),['=','i.status',1],['or',new Expression("LOCATE('".$userid."', i.approvalUserid) > 0"),['=','p.creator',$userid],['=','p.deleted',0]]];

    if (isset($this->_request['projectstate'])&&$this->_request['projectstate']>-1){
      $where[] = ['=','p.approvaltype',$this->_request['projectstate']];
    }

  
    $model = FzrbsBudgetProject::find()->alias('p')
    ->select('i.approvalUsername,`p`.*,d.label as statename,d2.label as approvaltypename,u.name,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.budget ELSE 0 END) as budgetincome,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.final ELSE 0 END) as finalincome')
    ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'p.creator=u.userid')
    ->rightJoin(['i'=>"(select * from ".WeixinOaApprovalInfo::tableName()." where status=1 and agentId=".$this->agentId." and LOCATE('|".$userid."|',CONCAT('|',approvalUserid,'|')))"],"p.thirdno=i.thirdNo")
    ->join('LEFT OUTER JOIN',['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id')
    ->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=p.state and d.type='审批类型'")
    ->leftJoin(['d2'=>FzrbsBudgetDict::tableName()],"d2.value=p.approvaltype and d2.type='审批类型'")
    ->where($where)->groupBy('p.id,i.approvalUsername,u.name,u.avatar,statename,approvaltypename');
    

    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();    
    $_result = array();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $res;
    return $_result;
  }
  // 查询待办事项
  public function actionTodolist(){
  
    $userid = $this->_adminInfo['wxuserid'];
    // 获取审批类型
    $types = FzrbsBudgetDict::find()->where(['and',['=','type','审批类型'],['=','agentid',$this->agentId]])->all();
    $result = array();


    // $target = $this->getTarget(date('Y'));
    

    // 统计全部项目数
    $awhere=['and',['=','deleted',0]];
    $dept = $this->getDepts();
    if (sizeof($dept)){
      $awhere[] = ['or',['in' , 'p.departmentid' , $dept],['=','p.creator',$userid]];
    }else {
      $awhere[] = ['or',['=','p.creator',$userid],['=','p.charger',$userid]];
    }
    $temp = FzrbsBudgetProject::find()->alias('p')->where($awhere)->count();
    $result[] =  array('title'=>'全部项目', 'count'=>$temp,'url'=>'/finance/budget/project/list','query'=>array('projectstate'=>-1));

    $where = ['and',new Expression("p.thirdno is not null and p.thirdno!=''"),['or',new Expression("LOCATE('".$userid."', i.approvalUserid) > 0"),['=','p.creator',$userid],['=','p.deleted',0]]];
    if ($types){
      foreach ($types as $type){
        if ($type['label']=='已提交'){
          continue;
        }
        $temp = FzrbsBudgetProject::find()->alias('p')->rightJoin(['i'=>"(select * from ".WeixinOaApprovalInfo::tableName()." where status=1 and agentId=".$this->agentId." and LOCATE('|".$userid."|',CONCAT('|',approvalUserid,'|')))"],'p.thirdno=i.thirdNo')->where($where)->andWhere(['=','p.approvaltype',$type['value']])->count();
        $result[] =  array('title'=>$type['label'].'审批中', 'count'=>$temp,'url'=>'/finance/budget/budget/applylist','query'=>array('projectstate'=>intval($type['value'])));
      }
    }


    return $result;

  }
  // 新媒体业务统计
  private function getNewmediastat($year,$dept){
    $month=date('m');
    $start = "$year-01-01";
    $month++;
    $end = "$year-$month-01";
    $deptsql = "";
    if ($dept && $dept!='all') {
      $deptsql = "and p.departmentid in ($dept)";
    }
    // 查询已提交考核项目且对应的合同收入子类型为“新媒体收入”

    $sumsql = "sum(CASE WHEN b.type=".$this->INCOME_DICID." THEN b.budget ELSE 0 END) as budgetincome,SUM(CASE WHEN b.type=".$this->EXPEND_DICID." THEN b.budget ELSE 0 END) as budgetexpend,sum(CASE WHEN b.type=".$this->INCOME_DICID." THEN b.final ELSE 0 END) as finalincome,SUM(CASE WHEN b.type=".$this->EXPEND_DICID." THEN b.final ELSE 0 END) as finalexpend";

    $sql= "SELECT $sumsql from fzrbs_budget_balance b   where  moneytype in (select id from fzrbs_budget_dict where subtype in ('新媒体')) and projectid in (select id from fzrbs_budget_project p where p.state=3 $deptsql and submitdate<'$end' and submitdate>='$start') ";

    $result = FzrbsBudgetProject::findBySql($sql)->asArray()->one();

    $result['finalprofit'] = $result['finalincome']-$result['finalexpend'];
    
    return $result;

  }
  // 指定年份指标完成情况
  private function getTargetprogress($year){
    
    $target = $this->getTarget($year);
    $dept = $target['dept'];
    
    $start = $year.'-01-01';
    $end = $year.'-12-31';
   
    $deptsql =  "p.departmentid in (".$target['dept'].")";

    $deptsql = "";
    if ($dept && $dept!='all') {
      $deptsql = "and p.departmentid in ($dept)";
    }

    $sql = "SELECT  sum(CASE WHEN b.type=$this->INCOME_DICID THEN b.budget ELSE 0 END) AS `budgetincome`, SUM(CASE WHEN b.type=$this->EXPEND_DICID THEN b.budget ELSE 0 END) AS `budgetexpend`, sum(CASE WHEN b.type=$this->INCOME_DICID THEN b.final ELSE 0 END) AS `finalincome`, SUM(CASE WHEN b.type=$this->EXPEND_DICID THEN b.final ELSE 0 END) AS `finalexpend` FROM `fzrbs_budget_project` `p`  LEFT OUTER JOIN `fzrbs_budget_balance` `b` ON b.projectid=p.id WHERE (`p`.`id` > 0)  $deptsql and p.state=3 and submitdate<='$end' and submitdate>='$start'";
    $result = FzrbsBudgetProject::findBySql($sql)->asArray()->one();

    $result['targetincome'] = $target['income'];
    $result['targetprofit'] = $target['profit'];
    $result['incomepercent'] = 0;
    $result['profitpercent'] = 0;
    $result['finalprofit'] = $result['finalincome'] - $result['finalexpend'];
    if ($result['targetincome']!=0){
      $result['incomepercent'] = intval(($result['finalincome']/$result['targetincome']*100));
    }
    if ($result['targetprofit']!=0){
      $result['profitpercent'] = intval((($result['finalincome']-$result['finalexpend'])/$result['targetprofit']*100));
    }
    return $result;

  }
  // 业务量统计 
  public function actionVolumestat($dept,$year){
    
    $projecttypes = FzrbsBudgetDict::find()->where(['type'=>'项目类别'])->asArray()->all();
  
    $month=date('m');
    $start = "$year-01-01";
    $month++;
    $end = "$year-$month-01";
    $deptsql = "";
    if ($dept && $dept!='all') {
      $deptsql = "and p.departmentid in ($dept)";
    }
 

    for ($i=0; $i < sizeof($projecttypes); $i++) { 
      $type = $projecttypes[$i]['value'];
      $sql = "SELECT  sum(CASE WHEN b.type=$this->INCOME_DICID THEN b.budget ELSE 0 END) AS `budgetincome`, SUM(CASE WHEN b.type=$this->EXPEND_DICID THEN b.budget ELSE 0 END) AS `budgetexpend`, sum(CASE WHEN b.type=$this->INCOME_DICID THEN b.final ELSE 0 END) AS `finalincome`, SUM(CASE WHEN b.type=$this->EXPEND_DICID THEN b.final ELSE 0 END) AS `finalexpend` FROM `fzrbs_budget_project` `p`  LEFT OUTER JOIN `fzrbs_budget_balance` `b` ON b.projectid=p.id WHERE (`p`.`id` > 0) AND (`p`.`type` = $type) $deptsql and p.state=3 and submitdate<'$end' and submitdate>='$start'";
      $result = FzrbsBudgetProject::findBySql($sql)->asArray()->one();
      $projecttypes[$i]['stat'] = $result;
    }
    return $projecttypes;
  }

  private function getBusinessstat(){
    $res = array();
    $res[] = array('title'=>'全年指标完成情况','fields'=>['目标','收入','利润 '],'datas'=>[50,50,75]);
    $res[] = array('title'=>'年度标完成情况','fields'=>['一季度','二季度','三季度','四季度'],'datas'=>[50,50,75,60]);
    $res[] = array('title'=>'收入情况','fields'=>['一季度','二季度','三季度','四季度'],'datas'=>[50,50,75,60]);
    $res[] = array('title'=>'利润情况','fields'=>['一季度','二季度','三季度','四季度'],'datas'=>[50,50,75,60]);
    return $res;
  }


  
  // *****************************  项目 *******************************************

  public function actionDelproject(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');

    $d = FzrbsBudgetProject::find()->alias('p')->select('p.*,u.name,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.budget ELSE 0 END) as budgetincome,SUM(CASE WHEN b.type='.$this->EXPEND_DICID.' THEN b.budget ELSE 0 END) as budgetexpend,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.final ELSE 0 END) as finalincome,SUM(CASE WHEN b.type='.$this->EXPEND_DICID.' THEN b.final ELSE 0 END) as finalexpend')->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'p.creator=u.userid')->join('LEFT OUTER JOIN',['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id')->where(['p.id'=>$id])->groupBy('p.id,u.name')->asArray()->one();

    // 已锁定项目无法修改
    if ($d->lock){
      return array('errorMessage'=>'项目已锁定，删除前先解锁！');
    }
    if(!$d) return array('data'=>'删除成功');
    // 是否已经提交
    if($d['state']==$this->SUBMITTED_PROJECT||$d['directsubmit']==1){
      return array('errorMessage'=>'项目已经提交计量并审批通过，不能删除!');
    }
    
    if($d['thirdno']){
      return array('errorMessage'=>'项目正在审批中，不能删除!');
    }
    if($d['serial']){
      return array('errorMessage'=>'有编号的项目不能删除!');
    }
    // 判断是否有权限
    if (!$this->haspower('编辑',$this->agentId,$d['departmentid'],$d['creator'])) {
      return array('errorMessage'=>'需要【编辑】权限');
    }


    FzrbsBudgetProject::updateAll(['deleted'=>1],['id'=>$id]);
    return array('data'=>'删除成功');
  }

  // 更改项目某些字段的值
  public function actionAlterproject(){
    
    $obj = $this->_request;
    if (!$obj['id']){
      return array('errorMessage'=>'id 不能为空');
    }
    // 查询项目和当前流程第一个节点是否被审批了
    $p=FzrbsBudgetProject::findOne($obj['id']);
    if(!$p) return array('errorMessage'=>'项目不存在');
    // 判断是否有权限
    if (!$this->haspower('编辑',$this->agentId,$p['departmentid'],$p['creator'])) {
      return array('errorMessage'=>'需要【编辑】权限');
    }
    FzrbsBudgetProject::updateAll($obj,['id'=>$obj['id']]);

    $title = '';

    if (isset($obj['budgetreport'])&&$obj['budgetreport']!=$p['budgetreport']){
      $title = '更新了【预算报告】';
    }
    if (isset($obj['finalreport'])&&$obj['finalreport']!=$p['finalreport']){
      if ($title) {
        $title.= '、【决算报告】';
      } else{
        $title = '更新了【决算报告】';
      }
      
    }
    // 消息通知
    $tousers = $this->getUserHasApproved($obj['id']);// 查询当前项目是否在审批，哪些人已经审批过了
    if ($tousers && $title){
      // 判断修改了哪些字段
      $this->sendChanges($tousers, $this->userinfo['name']."修改了【".$p['title']."】的项目报告", $p,array('title'=>$title));
    }

    return array('data'=>$obj);
  }

//  修改提交日期
  public function actionAltersubmitdate(){
    $obj = $this->_request;
    if (!$obj['id']) return array('errorMessage'=>'id 不能为空');
    $p = FzrbsBudgetProject::findOne($obj['id']);
    $departmentid = $p['pdepartmentid'];
    if (!$this->haspower('财务管理',$this->agentId,$departmentid,'')) {
      $dept = WeixinOaDepartment::findOne($departmentid);
      return array('errorMessage'=>'没有部门【'.$dept['name'].'】的【财务管理】权限,让【会计】进行操作或让【管理员】赋予权限');
    }
    FzrbsBudgetProject::updateAll($obj,['id'=>$obj['id']]);
    return array('errorMessage'=>'');
  }
  public function actionSaveproject(){
    $dept = WeixinOaDepartment::findOne($this->userinfo['departmentid']);
    if (!$dept['code']) return array('errorMessage'=>'你所在部门【'.$dept['name'].'】的部门简码为空，请点击页面底部的"部门简码"按钮进行更新，具体部门简码请在咨询财务后再设置');
    $userid = $this->_adminInfo['wxuserid'];
    $resp = array('errorMessage'=>'');
    $obj = $this->_request;

    $s = FzrbsBudgetProject::find()->where(['and',['=','title',$obj['title']],['=','deleted',0]])->all();
    if ($s) {
      if (sizeof($s)>1){
        return array('errorMessage'=>'项目名称已经存在！');
      } else{
        $m=$s[0];
        if ($m && $m['id']!=$obj['id']){
        
          if ($m['title']==$obj['title']){
            return array('errorMessage'=>'项目名称已经存在！');
          }
         
        }
      }
      
    }
    
    // 重新计算项目对应的收入合同的总价
    try {
      if($obj['contractids']){
      
        $c=FzrbsContract::findBySql("select sum(amount) as amount,group_concat(partaname) as partaname,group_concat(parta) as parta from ".FzrbsContract::tableName()." where id in (".$obj['contractids'].")")->asArray()->one();
        $c2=FzrbsContract::findBySql("select CONCAT_WS('至', DATE_FORMAT(starttime, '%Y%m%d'), ifnull(DATE_FORMAT(endtime, '%Y%m%d'),'执行结束')) as contractperiod from ".FzrbsContract::tableName()." where id in (".$obj['contractids'].")")->asArray()->one();
        if($c) {
          $obj['contractamount']=$c['amount'];
          $obj['parta']=$c['parta'];
          $obj['partaname']=$c['partaname'];
          $obj['contractperiod']=$c2['contractperiod'];
        }
        // 重新计算项目关联的合同已收、确认的回款
        $temp=FzrbsContractPaycollection::find()->select('sum(amount) as received')->where(['and',['in','contractid',explode(',',$obj['contractids'])],['=','state',3]])->asArray()->one();
        if($temp){
          $received=$temp['received'];
        }
        $obj['receivedmoney']=$received;

      }else{
        $obj['contractamount']=0;
        $obj['receivedmoney']=0;
        $obj['contractperiod']='';
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    try {
      
      if ($obj['id']) {
        $old = FzrbsBudgetProject::findOne($obj['id']);
        // 已锁定项目无法修改
        if ($old->lock){
          return array('errorMessage'=>'项目已被经办人员锁定，无法修改！');
        }
        if (!$this->haspower('编辑',$this->agentId,$old['departmentid'],$old['creator'])) {
          return array('errorMessage'=>'需要【编辑】权限');
        }
        
        
        
        if (in_array($obj['type'],[$this->OFFLINE])){//如果是线下项目
          
          if ($old['history']&&preg_match("/$this->FINAL_PROJECT/", $old['history'])){// 判断是否决算过，如果决算过
            $obj['state']=$this->READYTOSUBMIT_PROJECT; //已决算过从提交计量开始
          }else{ 
            $obj['state']=$this->FINAL_PROJECT;// 未决算过从决算开始
          }
          
          if ($old['history']&&preg_match("/$this->READYTOSUBMIT_PROJECT/", $old['history'])){// 决断是否提交过计量
            
            if ($old['history']&&preg_match("/$this->FINAL_PROJECT/", $old['history'])){// 若提交过决算，直接结束
              $obj['state']=$this->SUBMITTED_PROJECT;
              $obj['directsubmit']=0;
            }else{
              $obj['directsubmit']=1;
            }
            
          }


        } else if (in_array($obj['type'],$this->specialprojecttype())){
          if ($old['history']&&preg_match("/$this->READYTOSUBMIT_PROJECT/", $old['history'])){
            $obj['state']=$this->SUBMITTED_PROJECT;
            $obj['directsubmit']=0;
          }else if ($old['state']<$this->READYTOSUBMIT_PROJECT){
            $obj['state']=$this->READYTOSUBMIT_PROJECT;
          }
       
          
        } else{
          // 由非special项目转为special项目(7,8)时，state重置为4
          if (!in_array($old['type'], $this->specialprojecttype()) && in_array($obj['type'], [$this->PURE_NEWMEDIA_TYPE, $this->OTHERS_TYPE])) {
            $obj['state'] = $this->READYTOSUBMIT_PROJECT;
          }
          // 由不需要预决算审批=》需要预决算审批
          if(in_array($old['type'],$this->specialprojecttype())&&$old['type']!=$obj['type']){
            $obj['directsubmit']=0;
            if ($old['thirdno']){
              $obj['thirdno']='';
              $action = '非报项目修改项目类型';
              $remark = $action . "操作人=" . $this->userinfo['name'] . "，项目名称：".$old['title']."，因项目类型修改，审批流程【".$old['thirdno']."】自动取消";
              $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
            $obj['reject']=0;
            $obj['state']=$this->START_PROJECT;
            // 如果已经提交过计量
            if (preg_match("/$this->READYTOSUBMIT_PROJECT/", $old['history'])){
              $obj['directsubmit']=1;
            }
          }
          
          if ($old['history']){
            if (preg_match("/$this->FINAL_PROJECT/", $old['history'])){
              $obj['state']=$this->READYTOSUBMIT_PROJECT;
            } else if (preg_match("/$this->BUDGET_PROJECT/", $old['history'])) {
              $obj['state']=$this->FINAL_PROJECT;
            } else if (preg_match("/$this->START_PROJECT/", $old['history'])){
              $obj['state']=$this->BUDGET_PROJECT;
            }
          }
          
        }
        
        FzrbsBudgetProject::updateAll($obj,['id'=>$obj['id']]);

        // 修改执行绩效比例performanceratio和finalperformanceratio，需要更新预算和决算的绩效比例
        if($obj['performanceratio']!=$old['performanceratio']||$obj['finalperformanceratio']!=$old['finalperformanceratio']){
          $temp = $this->getProjectRealexpend($obj['id']);
      
          $old->realbudgetexpend = $temp['realbudgetexpend'];
          $old->realfinalexpend = $temp['realfinalexpend'];
          $old->budgetbonus = $temp['budgetbonus'];
          $old->finalbonus = $temp['finalbonus'];
          $old->budgettaxtotal = $temp['budgettaxtotal'];
          $old->finaltaxtotal = $temp['finaltaxtotal'];
          $old->save();
        }

        if ($old['type']!=$obj['type']){
          // 保存日志
          $dics = FzrbsBudgetDict::find()->where(['and',['=','type','项目类别'],['in','value',[$old['type'],$obj['type']]]])->asArray()->all();
          // dics 转换成对象
          if ($dics){
            $dics = array_column($dics, 'label', 'value');
          }
          $dics2 = FzrbsBudgetDict::find()->where(['and',['=','type','审批类型'],['in','value',[$old['state'],$obj['state']]]])->asArray()->all();
          // dics 转换成对象
          if ($dics2){
            $dics2 = array_column($dics2, 'label', 'value');
          }
          $this->_operationlog(['catalog' => "修改项目【".$obj['title']."】的类型：", 'remark' => "项目【".$obj['title']."】,history=[".$old['history']."],类型【".$dics[$old['type']]."】->【".$dics[$obj['type']]."】，状态：【".$dics2[$old['state']]."】->【".$dics2[$obj['state']]."】"]);
        }
      
      } else {

        // 纯新媒体业务、其他业务，只需要提交计量
        if (in_array($obj['type'],$this->specialprojecttype())){
          $obj['state']=$this->READYTOSUBMIT_PROJECT;
        }
        if (in_array($obj['type'],[$this->OFFLINE])){
          $obj['state']=$this->FINAL_PROJECT;
        }
        if ($obj['pdepartmentid']=='1'||$obj['pdepartmentid']=='7'){
          return array('errorMessage'=>'【立项部门】不能为【福州日报社】或【下属公司】');
        }
        $eformular = FzrbsBudgetDict::find()->where(['and',['=','type','税费计算公式'],['=','subtype','支出']])->orderBy('id desc')->one();
        
        
        $obj['expendtaxformula']=$eformular['label'];
        $obj['finalexpendtaxformula']=$eformular['label'];

        $obj['departmentid'] = $this->userinfo['departmentid'];
        $obj['creator'] = $userid;
        $obj['department'] = $this->userinfo['departmentname'];
        $p= new FzrbsBudgetProject($obj);
        $p->save();
        $obj['state'] = $p->state;
        $obj['id'] = $p->id;
      }

      
      



    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    $resp['data'] =$obj;
    return $resp;
  }
  
  public function actionTransfileurl(){
    
    $url = $this->_request['url'];
    if(substr($url,0,10)=='/uploaded/'){
      // 判断是否包含？有就分割并选择第一个
      if (strpos($url, '?')>=0){
        $url = substr($url,0,strpos($url, '?'));
      }
      $url = "http://fzrb.fznews.com.cn/index.php?r=qiyehao/attachment/file&savepath=/www/web/fzrbs_oa/web&attachment=".$url;
    }else if (strpos($url, "/www/web/fzrb.fznews.com.cn/")>=0){
      
      // 替换字符
      $url = str_replace("/www/web/fzrb.fznews.com.cn/", "", $url);
      if (strpos($url, '&')>=0){
        $url = substr($url,0,strpos($url, '&'));
      }
      $url = "http://fzrb.fznews.com.cn/index.php?r=qiyehao/attachment/file&savepath=/www/web/fzrb.fznews.com.cn/&attachment=".$url;
    }
    return $url;
  }
  
  public function actionGetfileurlsbycontractids(){
    $contractids = $this->_request['contractids'];
    if (!$contractids) return array('data'=>'');
    $c=FzrbsContract::findBySql("select fileurls from ".FzrbsContract::tableName()." where id in (".$contractids.")");
    return array('data'=>implode(',',$c->select('fileurls')->column()));
  }
  public function actionGetallfileurs(){
    $id=$this->_request['projectid'];
    if (!$id)return array('data'=>'');
    $result=[];
    // 项目
    $p = FzrbsBudgetProject::find()->select("fileurls,finalfileurls,contractids")->where(['=','id',$id])->asArray()->one();
    if ($p['fileurls']) {
      $result[] = array('label'=>'项目预算','value'=>$p['fileurls']);
    }
    if ($p['finalfileurls']) {
      $result[] = array('label'=>'项目决算','value'=>$p['finalfileurls']);
    };

    if ($p['contractids']) {
      $contract = FzrbsContract::find()->select("fileurls")->where(['in','id',explode(',',$p['contractids'])]);
      
      $fileurls = implode(',',$contract->select('fileurls')->column());
  
      if ($fileurls) {
        $result[] = array('label'=>'项目合同','value'=>$fileurls);
      }
    }
    $balancesM=FzrbsBudgetBalance::find()->select("contractids,budgetfileurls,finalfileurls) as finalfileurls")->where(['=','projectid',$id])->asArray();
    $budgetfileurls = implode(',',$balancesM->select('budgetfileurls')->column());
    $finalfileurls = implode(',',$balancesM->select('finalfileurls')->column());
    $contractids = implode(',',$balancesM->select('contractids')->column());
    if ($budgetfileurls) {
      $result[] = array('label'=>'收支预算','value'=>$budgetfileurls);
    }
    if ($finalfileurls) {
      $result[] = array('label'=>'收支决算','value'=>$finalfileurls);
    };

    if ($contractids) {
      $bc = FzrbsContract::find()->select("fileurls")->where(['in','id',explode(',',$contractids)]);
      $fileurls = implode(',',$bc->select('fileurls')->column());
      $result[] = array('label'=>'合同附件','value'=>$fileurls);
    }
    
    return array('list'=>$result);

    
  }
  public function actionGetbalancefileurls(){
    $id=$this->_request['id'];
    if (!$id)return array('data'=>'');
    $where=['and',['=','id',$id]];
    $c=FzrbsBudgetBalance::find()->select("projectid,contractids,budgetfileurls,finalfileurls")->where($where)->asArray()->one();
    $p = FzrbsBudgetProject::find()->select("fileurls,finalfileurls,contractids")->where(['=','id',$c['projectid']])->asArray()->one();
    $result = '';
    if ($c['budgetfileurls']) $result.=','.$c['budgetfileurls'];
    if ($c['finalfileurls']) $result.=','.$c['finalfileurls'];

    if ($c['contractids']) {
      $bc2 = FzrbsContract::find()->select("fileurls")->where(['in','id',explode(',',$c['contractids'])]);
      $fileurls = implode(',',$bc2->select('fileurls')->column());
      if ($fileurls) $result.=','.$fileurls;
    }


    if ($p['fileurls']) $result.=','.$p['fileurls'];
    if ($p['finalfileurls']) $result.=','.$p['finalfileurls'];


    // 收入返回收入合同,支出返回支出合同
    if ($p['contractids']) {
      $contract2 = FzrbsContract::find()->select("fileurls")->where(['and',['in','id',explode(',',$p['contractids'])],['=','type',$c['type']]])->asArray();
      $fileurls=implode(',',$contract2->select('fileurls')->column());
      if ($fileurls) $result.=','.$fileurls;
    }

    
    if ($result && strpos($result,',')==0) $result= substr($result,1);
    return array('data'=>$result);
  }
  
  public function actionGetprojectbythirdno(){
    $keyword = $this->_request['thirdno'];
    if (!$keyword) return  array('errorMessage'=>'thirdno为空');
    $res = FzrbsBudgetProject::find()->where(['=','thirdNo',$keyword])->one();
    return array('data'=>$res);
  }
  public function actionGetsingleprojectbyid(){
    $keyword = $this->_request['id'];
    if (!$keyword) return  array('errorMessage'=>'thirdno为空');
    $res = FzrbsBudgetProject::find()->where(['=','id',$keyword])->one();
    return array('data'=>$res);
  }
  

 
  private function getDepts(){
    $userid = $this->_adminInfo['wxuserid'];
    $power = '查看';
    $arr = array();

    // 领导可以查看下属部门所有的合同
    $deptid = $this->userinfo['departmentid'];
    if ($this->userinfo['is_leader']){
      $depts = WeixinOaDepartment::findBySql("SELECT GROUP_CONCAT(id SEPARATOR ',') as ids from weixin_oa_department where id=$deptid or FIND_IN_SET($deptid,parentids)")->asArray()->one();
      if ($depts['ids']) {
        $arr = array_merge($arr,explode(',',$depts['ids']));
      }
    }


    $sql = "SELECT userid,dept from weixin_oa_flowrole where  userid='".$userid."' and  FIND_IN_SET(".$this->agentId.",agent)  and role in (select id from weixin_oa_role where FIND_IN_SET('$power',powername))";
    $result = WeixinOaFlowrole::findBySql($sql)->asArray()->all();

    if ($result) {
      
      foreach ($result as $e) {

        $arr = array_merge($arr,explode(',',$e['dept']));
      }
      $arr = array_unique($arr);

    }
   
    return $arr;
  }
  public function actionGetonlyproject(){
    $id = $this->_request['id'];
    $field = $this->_request['field'];
    if (!$field) $field = '*';
    if (!$id) return  array('errorMessage'=>'id为空');
    $data = FzrbsBudgetProject::find()->select($field)->where(['=','id',$id])->asArray()->one();
    return array('data'=>$data);
  }
  public function actionGetprojectbyid(){
    $id = $this->_request['id'];
    if (!$id) return  array('errorMessage'=>'id为空');
    $sql = "select p.* from fzrbs_budget_project p  where p.id=$id";
    
    // 项目
    $res = FzrbsBudgetProject::find()->alias('p')->select('p.*,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.budget ELSE 0 END) as budgetincome,SUM(CASE WHEN b.type='.$this->EXPEND_DICID.' THEN b.budget ELSE 0 END) as budgetexpend,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.final ELSE 0 END) as finalincome,SUM(CASE WHEN b.type='.$this->EXPEND_DICID.' THEN b.final ELSE 0 END) as finalexpend')->join('LEFT OUTER JOIN',['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id')->where(['=','p.id',$id])->groupBy('p.id')->asArray()->one();
    $result = array('data'=>$res);

    // 合同总价：收入和支出
    $incomecontractid= isset($res['contractids'])?$res['contractids']:''; // 收入相关合同
    
    $rce = FzrbsBudgetBalance::findBySql("SELECT GROUP_CONCAT(contractids) as contractid from  fzrbs_budget_balance where projectid=$id")->asArray()->one(); // 支出相关合同
    $expendcontractid = ($rce&&isset($rce['contractid']))?$rce['contractid']:'';
    if ($incomecontractid){
      $relatedcontractids = $incomecontractid;
    }
    if ($expendcontractid){
      if ($relatedcontractids){
        $relatedcontractids .= ','.$expendcontractid;
      }else{
        $relatedcontractids = $expendcontractid;
      }
    }
    
    
    $relatedcontract = array();

    if ($relatedcontractids){
      // relatedcontractids去掉空字符串
      $relatedcontractids = implode(',',array_filter(explode(',',$relatedcontractids)));
      FzrbsContract::findBySql("SELECT group_concat(CASE WHEN c.type=".$this->INCOME_DICID." THEN c.partaname END) as partincome,group_concat(CASE WHEN c.type=".$this->EXPEND_DICID." THEN c.partbname END) as partexpend,sum(CASE WHEN c.type=".$this->INCOME_DICID." THEN c.amount ELSE 0 END) AS `contractincome`,sum(CASE WHEN c.type=".$this->INCOME_DICID." THEN c.invoiceamount ELSE 0 END) AS `incomeinvoiceamount`,sum(CASE WHEN c.type=".$this->EXPEND_DICID." THEN c.amount ELSE 0 END) AS `contractexpend`,sum(CASE WHEN c.type=".$this->EXPEND_DICID." THEN c.invoiceamount ELSE 0 END) AS `expendinvoiceamount` from fzrbs_contract c where c.id in($relatedcontractids)")->asArray()->one();
    }
    
    $result['contract'] = $relatedcontract;
    
    // 查询新媒体收入：收入和支出
    $newmedia = FzrbsBudgetProject::find()->alias('p')->select('p.*,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.budget ELSE 0 END) as budgetincome,SUM(CASE WHEN b.type='.$this->EXPEND_DICID.' THEN b.budget ELSE 0 END) as budgetexpend,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.final ELSE 0 END) as finalincome,SUM(CASE WHEN b.type='.$this->EXPEND_DICID.' THEN b.final ELSE 0 END) as finalexpend')->join('LEFT OUTER JOIN',['b'=>FzrbsBudgetBalance::tableName()],"b.projectid=p.id and moneytype in (select id from fzrbs_budget_dict where  type in ('收入类型','支出类型') and subtype in ('新媒体')) ")->where(['=','p.id',$id])->groupBy('p.id')->asArray()->one();

    $result['newmedia'] = $newmedia?$newmedia:array();


   

    return $result;
  }
  // ========================= 入账 ============================
  public function actionSaveenteraccount(){
    $userid = $this->_adminInfo['wxuserid'];
    $obj = $this->_request;

    if (!$obj['projectid']) return array('errorMessage'=>'projectid 不能为空');
    if(!$obj['type']) return array('errorMessage'=>'type 不能为空,'.$this->INCOME_DICID.'-收入，'.$this->EXPEND_DICID.'-支出');

    $p=FzrbsBudgetProject::findOne($obj['projectid']);

    
    $departmentid = $p['pdepartmentid'];
   
    if (!$this->haspower('财务管理',$this->agentId,$departmentid,'')) {
      $dept = WeixinOaDepartment::findOne($departmentid);
      return array('errorMessage'=>'没有部门【'.$dept['name'].'】的【财务管理】权限,让【会计】进行操作或让【管理员】赋予权限');
    }

    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      
        
        $ele = new FzrbsBudgetEnteraccount($obj);
        $ele->creator=$userid;
        $ele->save();



      
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    
    return array('data'=>$ele);

  }
  public function actionDelenteraccount(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    $old = FzrbsBudgetEnteraccount::findOne($id);
    $p=FzrbsBudgetProject::findOne($old['projectid']);
    
    if (!$this->haspower('财务管理',$this->agentId,$p['pdepartmentid'],'')) {
      $dept = WeixinOaDepartment::findOne($p['pdepartmentid']);
      return array('errorMessage'=>'没有部门【'.$dept['name'].'】的【财务管理】权限');
    }
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      FzrbsBudgetEnteraccount::updateAll(['state'=>0],['id'=>$id]);
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    
    return array('data'=>'删除成功');

  }
  public function actionGetenteraccount(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $order='i.id desc';
    $where = [
        'and',
        ['>', 'i.id', 0],
    ];
    $bid = $this->_request['bid'];
    $projectid= $this->_request['projectid'];
    // bid和projectid不能都为空
    if (!$bid&&!$projectid){
      return array('errorMessage'=>'bid和projectid不能都为空');
    }

    if ($this->_request['bid']){
      $where[] = ['=','i.bid',intval($this->_request['bid'])];
    }
    if ($this->_request['type']){
      $where[] = ['=','i.type',intval($this->_request['type'])];
    }
    
    if ($this->_request['projectid']){
      
      if (($this->_request['showAll']&&$this->_request['showAll']=='true')||$bid){
        $where[] = ['=','i.projectid',intval($this->_request['projectid'])];
      }else{
        
        $where[] = ['and',['=','i.projectid',intval($this->_request['projectid'])],new Expression('bid is null')];
        
      }
    
      

    }
    $model = FzrbsBudgetEnteraccount::find()->alias('i')->select('i.*,u.name as name')->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'i.creator=u.userid')->where($where);
      
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($order)->asArray()->all();

  
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    

    return $this->_result;
  }



  private function getBselect(){
    return "sum(CASE WHEN b.type=$this->INCOME_DICID THEN b.budget ELSE 0 END) AS `budgetincome`, SUM(CASE WHEN b.type=$this->EXPEND_DICID THEN b.budget ELSE 0 END) AS `budgetexpend`, sum(CASE WHEN b.type=$this->INCOME_DICID THEN b.final ELSE 0 END) AS `finalincome`, SUM(CASE WHEN b.type=$this->EXPEND_DICID THEN b.final ELSE 0 END) AS `finalexpend`";
  }

  // 获取项目历史记录
  public function actionGetprohistory(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $order='p.id desc';
    $where = [
        'and',
        ['>', 'p.id', 0],
    ];
    $projectid = $this->_request['projectid'];
    if (!$projectid) return array('errorMessage'=>'projectid 不能为空');
    if ($projectid){
      $where[] = ['=','p.projectid',$projectid];
    }
    if ($this->_request['state']){
      $where[] = ['=','p.state',$this->_request['state']];
    }
    $model = FzrbsBudgetHistory::find()->alias('p')->select('p.*,d.label as statename')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=p.state and d.type='审批类型'")->where($where);

    $total = $model->count();
    $res = $model->orderBy($order)->limit($limit)->offset($offset)->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["offset"] = $offset;
    $this->_result["total"] = $total;
    if ($res){
      // 批量查询所有涉及的 thirdNo 对应的 approval_info inserttime
      $thirdNos = array_filter(array_unique(array_map(function($item){
        $d = json_decode($item['data'],1);
        return isset($d['thirdno']) ? $d['thirdno'] : null;
      },$res)));
      $approvalMap = [];
      if ($thirdNos){
        $approvalInfos = WeixinOaApprovalInfo::find()->select('thirdNo,inserttime')->where(['and',['in','thirdNo',$thirdNos],['agentId'=>$this->agentId]])->asArray()->all();
        foreach ($approvalInfos as $ai){
          $approvalMap[$ai['thirdNo']] = $ai['inserttime'];
        }
      }
      $res = array_map(function($item) use($approvalMap){
        $ele = json_decode($item['data'],1);
        $ele['statename']=$item['statename'];
        $ele['createtime']=$item['createtime'];
        // approval_info.inserttime 作为节点开始时间
        $ele['approvalInserttime'] = isset($ele['thirdno']) && isset($approvalMap[$ele['thirdno']]) ? $approvalMap[$ele['thirdno']] : null;
        // project.inserttime 作为节点结束时间
        $ele['projectInserttime'] = isset($ele['inserttime']) ? $ele['inserttime'] : null;
        return $ele;
      },$res);
    }
    $this->_result['data'] = $res;
    
    return $this->_result;
  }
  // 项目全生命周期时间轴接口
  public function actionGetprotimeline(){
    $projectid = $this->_request['projectid'];
    if (!$projectid) return array('errorMessage'=>'projectid 不能为空');

    // 查询项目信息和状态
    $project = FzrbsBudgetProject::find()->select('state, inserttime, thirdno')->where(['id'=>$projectid])->asArray()->one();
    if (!$project){
      return array('errorMessage'=>'项目不存在');
    }

    // 查询该项目所有相关的 approval_info 记录
    // 方式1：通过 thirdNo
    // 方式2：通过 data JSON 中的 projectid
    $approvalList = [];
    $thirdNo = $project['thirdno'];

    // 直接通过项目的 thirdNo 查询
    if ($thirdNo){
      $approvalList = WeixinOaApprovalInfo::find()
        ->select('thirdNo, inserttime, data')
        ->where(['and',['=','thirdNo',$thirdNo],['agentId'=>$this->agentId]])
        ->orderBy('inserttime asc')
        ->asArray()->all();
    }

    // 如果按 thirdNo 查不到，通过 data JSON 中的 projectid 模糊查询
    if (empty($approvalList)){
      // 使用原生 SQL 查找 data 字段包含 projectid 的记录
      $sql = "SELECT thirdNo, inserttime, data FROM weixin_oa_approval_info
              WHERE agentId = :agentId
              AND data LIKE :projectid
              ORDER BY inserttime ASC";
      $approvalList = Yii::$app->db->createCommand($sql, [
        ':agentId' => $this->agentId,
        ':projectid' => '%"projectid":' . intval($projectid) . '%',
      ])->queryAll();
    }

    // 解析每个 approval_info 的 data JSON，提取 approvaltype
    $approvalByType = []; // key: approvaltype, value: inserttime
    foreach ($approvalList as $ai){
      $adata = json_decode($ai['data'],1);
      $approvalType = isset($adata['approvaltype']) ? intval($adata['approvaltype']) : 0;
      if ($approvalType && !isset($approvalByType[$approvalType])){
        $approvalByType[$approvalType] = $ai['inserttime'];
      }
    }

    // 按 state 分组获取进入时间（从 approval_info.inserttime）
    $stateEnterTime = []; // key: state(1-5), value: inserttime
    $stateLabels = [
      1 => '立项',
      2 => '预算',
      3 => '决算',
      4 => '提交计量',
      5 => '提交计量',
    ];
    foreach ([1,2,3,4,5] as $s){
      if (isset($approvalByType[$s])){
        $stateEnterTime[$s] = $approvalByType[$s];
      }
    }

    // 当前项目状态
    $currentState = intval($project['state']);

    // 组装时间轴数据
    $timeline = [];
    $states = [1, 2, 3, 4, 5];
    foreach ($states as $idx => $state){
      $enterTime = $stateEnterTime[$state] ?? null;

      // 结束时间 = 下一 state 的进入时间
      $endTime = null;
      $nextState = isset($states[$idx + 1]) ? $states[$idx + 1] : null;
      if ($nextState && isset($stateEnterTime[$nextState])){
        $endTime = $stateEnterTime[$nextState];
      }

      $isCurrent = ($currentState == $state);
      $isFinished = ($currentState > $state);

      // 计算耗时
      $durationDays = null;
      if ($enterTime && $endTime){
        $start = strtotime($enterTime);
        $end = strtotime($endTime);
        if ($start && $end){
          $durationDays = ceil(($end - $start) / 86400);
        }
      }

      $timeline[] = array(
        'key' => $state <= 3 ? ($state == 1 ? 'start' : ($state == 2 ? 'budget' : 'final')) : 'submit',
        'label' => $stateLabels[$state],
        'state' => $state,
        'enterTime' => $enterTime,
        'endTime' => $endTime,
        'durationDays' => $durationDays,
        'isCurrent' => $isCurrent,
        'isFinished' => $isFinished,
        'isArchived' => false,
      );
    }

    return array('data'=>$timeline, 'project'=>$project);
  }
  private function getProjectModel($where,$columns){
    if (!$columns){
     $columns = "p.*"; 
    }else{
      // 分解$columns,并添加p.前缀
      $columns = explode(',',$columns);
      $columns = array_merge(['budgetbonus','finalbonus','id','contractids','type','state','history'],$columns);
      $columns = implode(',',array_map(function($item){
        return "p.".$item;
      },$columns));
      
    }
    $model = FzrbsBudgetProject::find()->alias('p')
    ->select("$columns,u.name,".$this->getBselect().",d.label as typename,e.incomeinvoiceamount,e.expendinvoiceamount")
    ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'p.creator=u.userid')
    ->join('LEFT OUTER JOIN',['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id')
    ->join('LEFT OUTER JOIN',['e'=>"(SELECT projectid,sum(CASE WHEN e.type=$this->INCOME_DICID THEN e.amount ELSE 0 END) AS `incomeinvoiceamount`, SUM(CASE WHEN e.type=$this->EXPEND_DICID THEN e.amount ELSE 0 END) AS `expendinvoiceamount` from fzrbs_budget_enteraccount e where e.state=1 GROUP BY projectid)"],'e.projectid=p.id')

    ->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"p.type=d.value and d.type='项目类别'")
    
    ->where($where)->groupBy('p.id,u.name,e.incomeinvoiceamount,e.expendinvoiceamount,typename');
    return $model;
  }
  private function getrostat($where){
    
    $model = FzrbsBudgetProject::find()->alias('p')
    ->select($this->getBselect().',p.id')
    ->join('LEFT OUTER JOIN',['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id')

    ->where($where)->groupBy('p.id')->one();
    return $model;
  }
  public function actionGetprojectbykeyword(){
    $userid = $this->_adminInfo['wxuserid'];
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $order='id desc';
    if ($this->_request['orderby']) {
      $order = $this->_request['orderby'];
    }
    $where = [
        'and',
        ['>', 'p.id', 0],['=','p.deleted',0]
    ];

    // 根据id查询
    if ($this->_request['id']){
      $where[] = ['=', 'p.id', $this->_request['id']];
    }
 
    $keyword = $this->_request['keyword'];

    if($keyword){
      if (strpos($keyword, ',')!==false){
        $keyword = explode(',', $keyword);
        $where[] = ['in', 'id', $keyword];
      }else{
        $where[] = ['or',['=', 'p.id', $keyword],['LIKE', 'p.title', $keyword],['LIKE', 'p.serial', $keyword]];
      }
    }
    $model = FzrbsBudgetProject::find()->alias('p')->select("p.id,p.title,p.serial")->where($where);
    $res = $model->orderBy($order)->limit($limit)->offset($offset)->asArray()->all();
    

    
    return $res;
  }
  
  private function getWhere(){
    $userid = $this->_adminInfo['wxuserid'];
    $where = [
        'and',['=','p.deleted',0],
    ];
    
    // 判断用户是否有查看权限
    $dept = [];
 
 
    $dept = $this->getDepts();

    if ($this->_request['departmentid']){
      $tempdept = explode(',',$this->_request['departmentid']);
      if ($dept){
        $dept = array_intersect($dept,$tempdept);
      }else{
        // 无查看权限,不操作
        
      }
    }
    if (sizeof($dept)){
      $where[] = ['or',['in' , 'p.departmentid' , $dept],['in' , 'p.pdepartmentid' , $dept]];
    }else {
      $where[] = ['or',['=','p.creator',$userid],['=','p.charger',$userid]];
    }
    
    if ($this->_request['pdepartmentid']){
      $where[] = ['in' , 'p.pdepartmentid' , $this->_request['pdepartmentid']];
    }
    if ($this->_request['keyword']) {
      $where[] = ['or',['LIKE', 'p.title', $this->_request['keyword']],['LIKE', 'p.serial', $this->_request['keyword']]];
    }
 
    if ($this->_request['title']) {
      $where[] = ['or',['LIKE', 'p.title', $this->_request['title']],['LIKE', 'p.serial', $this->_request['title']]];
    }
    if ($this->_request['serial']) {
      $where[] = ['LIKE', 'p.serial', $this->_request['serial']];
    }
    if ($this->_request['parta']) {
      $where[] = new Expression("FIND_IN_SET(".$this->_request['parta'].",p.parta)");

    }
    if ($this->_request['partb']) {
      $where[] = new Expression("FIND_IN_SET(".$this->_request['partb'].",p.partb)");
    }
    if (isset($this->_request['state'])) {
      if ($this->_request['state'] == $this->SUBMITTED_PROJECT){
        $where[] = ['or',['=','p.directsubmit',1],['=', 'p.state', $this->SUBMITTED_PROJECT]];
      }else{
        $where[] = ['=', 'p.state', $this->_request['state']];
      }
      
    }
    if ($this->_request['directsubmit']==1){
      $where[] = ['or',['=','p.directsubmit',1],['=', 'p.state', $this->SUBMITTED_PROJECT]];
    }

    if ($this->_request['creators']) {
      $where[] = ['in', 'p.creator', explode(',',$this->_request['creators'])];
    }
    if ($this->_request['chargers']) {
      $where[] = ['in', 'p.charger', explode(',',$this->_request['chargers'])];
    }
    if ($this->_request['starttimestart']) {
      $where[] = ['>=', 'p.starttime', $this->_request['starttimestart']];
    }
    if ($this->_request['starttimeend']) {
      $where[] = ['<=', 'p.starttime', $this->_request['starttimeend']];
    }
    if ($this->_request['submitdatestart']) {
      $where[] = ['>=', 'p.submitdate', $this->_request['submitdatestart']];
    }
    if ($this->_request['submitdateend']) {
      $where[] = ['<=', 'p.submitdate', $this->_request['submitdateend']];
    }


    if ($this->_request['contractids']){
      $where[] = new Expression('FIND_IN_SET(p.contractids, "'.$this->_request['contractids'].'")');
    }


    if ($this->_request['type']&&$this->_request['type']!=-1) {
      $where[] = ['in', 'p.type', explode(',',''.$this->_request['type'])];
    }
    if (isset($this->_request['approvalstate'])&&$this->_request['approvalstate']>-1) {
      if($this->_request['approvalstate']==1){
        $where[] = new Expression("p.thirdno is not null and p.thirdno!=''");
      }else{
        $where[] = new Expression("p.thirdno is  null or p.thirdno=''");
      }
      
    }
    if (isset($this->_request['issubmitted'])&&$this->_request['issubmitted']>-1) {
      if($this->_request['issubmitted']==1){
        $where[] = new Expression("p.state=".$this->SUBMITTED_PROJECT." or p.directsubmit=1");
      }else{
        $where[] = new Expression("p.state!=".$this->SUBMITTED_PROJECT." and p.directsubmit=0");
      }
      
    }
    return $where;

  }
  public function actionGetstat(){
    $where = $this->getWhere();
    // $columns = $this->_request['columns'];
    // $model = $this->getProjectModel($where,$columns);
    $all = array('label'=>'全部','value'=>'-1','stat'=>array('finalincome'=>0,'finalexpend'=>0));
        
    $projecttypes = $projecttypes = FzrbsBudgetDict::find()->where(['type'=>'项目类别'])->asArray()->all();
    
    for ($i=0; $i < sizeof($projecttypes); $i++) {
      $type = $projecttypes[$i]['value'];
      if(!in_array($type,[$this->ONLINE_PROJECT,$this->ACT_AD_TYPE])){

        $temp = $this->sumProjectSpecial(array_merge($where,[['=','p.type',$type]]));
        

        $all['stat']['finalincome'] += $temp['finalincome'];
        $all['stat']['finalexpend'] += $temp['finalexpend'];
        
        // 实际收入 = 预算收入 + 决算收入
        $projecttypes[$i]['stat'] = array('finalincome'=>$temp['finalincome'],'finalexpend'=>$temp['finalexpend']);
        
        
      }else{
        
        

        $temp = $this->sumProject(array('where'=>$where,'type'=>$type));

        $all['stat']['finalincome'] += $temp['finalincome'];
        $all['stat']['finalexpend'] += $temp['finalexpend'];
        
        // 实际收入 = 预算收入 + 决算收入
        $projecttypes[$i]['stat'] = array('finalincome'=>$temp['finalincome'],'finalexpend'=>$temp['finalexpend']);
      }
      
  
    }
    array_unshift($projecttypes,$all);

    // // 首页列表下方统计栏
    // $tb = $this->getProjectModel($where,'');
    // $budgetincome=$tb->sum('budgetincome');
    // $tf = $this->getProjectModel($where,'');
    // $finalincome=$tf->sum('finalincome');


    // $realbudgetexpend=$tb->sum('realbudgetexpend');
    // $realfinalexpend=$tf->sum('realfinalexpend');

    // $profit = $all['stat']['finalincome']-$all['stat']['finalexpend'];

    // $stat=array();
    // // 优化这里可以缩减一半查询时间
    // $stat = array(
    //   array('label'=>'合同总价','value'=>$model->sum('contractamount')),
    //   array('label'=>'预算收入','value'=>$budgetincome),
    //   array('label'=>'决算收入','value'=>$finalincome),
    //   array('label'=>'已收款','value'=>$model->sum('receivedmoney')),
    //   array('label'=>'预算支出','value'=>$realbudgetexpend),
    //   array('label'=>'决算支出','value'=>$realfinalexpend),
    //   array('label'=>'毛利润','value'=>$profit),
    //   array('label'=>'入账收入','value'=>$model->sum('incomeinvoiceamount')),
    //   array('label'=>'入账成本','value'=>$model->sum('expendinvoiceamount')),
    //   array('label'=>'预算绩效','value'=>$model->sum('budgetbonus')),
    //   array('label'=>'决算绩效','value'=>$model->sum('finalbonus'))
    // );
    
    


    return array('projecttypes'=>$projecttypes?$projecttypes:array());
        
  }
  public function actionGetstattotal(){
    $where = $this->getWhere();

    // 余额子查询：按项目聚合收入和支出（避免 LEFT JOIN 产生多行）
    $balanceSubQuery = FzrbsBudgetBalance::find()
      ->select([
        'projectid',
        'budgetincome' => 'SUM(CASE WHEN type='.$this->INCOME_DICID.' THEN budget ELSE 0 END)',
        'budgetexpend' => 'SUM(CASE WHEN type='.$this->EXPEND_DICID.' THEN budget ELSE 0 END)',
        'finalincome' => 'SUM(CASE WHEN type='.$this->INCOME_DICID.' THEN final ELSE 0 END)',
        'finalexpend' => 'SUM(CASE WHEN type='.$this->EXPEND_DICID.' THEN final ELSE 0 END)',
      ])
      ->groupBy('projectid');

    // 主查询：关联项目表和余额子查询
    $statSqlModel = FzrbsBudgetProject::find()->alias('p')
      ->select([
        'contractamount' => 'SUM(p.contractamount)',
        'budgetincome' => 'SUM(sub.budgetincome)',
        'finalincome' => 'SUM(CASE WHEN p.state > '.$this->FINAL_PROJECT.' THEN sub.finalincome ELSE 0 END)',
        'budgetexpend' => 'SUM(sub.budgetexpend)',
        'finalexpend' => 'SUM(CASE WHEN p.state > '.$this->FINAL_PROJECT.' THEN sub.finalexpend ELSE 0 END)',
        'receivedmoney' => 'SUM(CASE WHEN p.directsubmit=1 OR p.state='.$this->SUBMITTED_PROJECT.' THEN p.receivedmoney ELSE 0 END)',
        // 预算支出：所有项目的realbudgetexpend之和
        'realbudgetexpend' => 'SUM(p.realbudgetexpend)',
        // 决算支出：所有项目的realfinalexpend之和
        'realfinalexpend' => 'SUM(p.realfinalexpend)',
        'incomeinvoiceamount' => 'SUM(e.incomeinvoiceamount)',
        'expendinvoiceamount' => 'SUM(e.expendinvoiceamount)',
        'budgetbonus' => 'SUM(p.budgetbonus)',
        // 毛利润：按项目逐个计算后求和
        // 未决算(state<=3)或realfinalexpend=0：预算毛利润=budgetincome-realbudgetexpend
        // 已决算且realfinalexpend>0：决算毛利润=finalincome-realfinalexpend
        'grossprofit' => 'SUM(CASE WHEN p.state <= '.$this->FINAL_PROJECT.' OR p.realfinalexpend = 0 THEN sub.budgetincome - p.realbudgetexpend ELSE sub.finalincome - p.realfinalexpend END)',
        // 决算绩效：未决算或realfinalexpend=0用预算绩效，否则用决算绩效
        'finalbonus' => 'SUM(CASE WHEN p.state <= '.$this->FINAL_PROJECT.' OR p.realfinalexpend = 0 THEN p.budgetbonus ELSE p.finalbonus END)',
      ])
      ->leftJoin(['sub' => $balanceSubQuery], 'sub.projectid = p.id')
      ->leftJoin(['e'=>"(SELECT projectid,sum(CASE WHEN e.type=$this->INCOME_DICID THEN e.amount ELSE 0 END) AS `incomeinvoiceamount`, SUM(CASE WHEN e.type=$this->EXPEND_DICID THEN e.amount ELSE 0 END) AS `expendinvoiceamount` from fzrbs_budget_enteraccount e where e.state=1 GROUP BY projectid)"],'e.projectid=p.id')
      ->where($where);

    $statData = $statSqlModel->asArray()->one();

    $stat = array(
      array('label'=>'合同总价','value'=>$statData['contractamount'] ?? 0),
      array('label'=>'预算收入','value'=>$statData['budgetincome'] ?? 0),
      array('label'=>'决算收入','value'=>$statData['finalincome'] ?? 0),
      array('label'=>'已收款','value'=>$statData['receivedmoney'] ?? 0),
      array('label'=>'预算支出','value'=>$statData['realbudgetexpend'] ?? 0),
      array('label'=>'决算支出','value'=>$statData['realfinalexpend'] ?? 0),
      array('label'=>'毛利润','value'=>$statData['grossprofit'] ?? 0),
      array('label'=>'入账收入','value'=>$statData['incomeinvoiceamount'] ?? 0),
      array('label'=>'入账成本','value'=>$statData['expendinvoiceamount'] ?? 0),
      array('label'=>'预算绩效','value'=>$statData['budgetbonus'] ?? 0),
      array('label'=>'决算绩效','value'=>$statData['finalbonus'] ?? 0)
    );

    return array('stat'=>$stat);
  }


  public function actionGetprolist2(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $order='id desc';
    if ($this->_request['orderby']) {
      $order = $this->_request['orderby'];
    }
    $userid = $this->_adminInfo['wxuserid'];
    $where = ['and',['>','b.id',0]];
    $pwhere ='';
     $dept = [];
 
 
    $dept = $this->getDepts();

    
    if ($this->_request['moneytypename']){
      $where[] = new Expression("moneytype in ( select id from fzrbs_budget_dict where subtype='新媒体' and label='".$this->_request['moneytypename']."')");
    }
    if (sizeof($dept)){
      $where[] = ['or',['in' , 'b.departmentid' , $dept],['=','b.creator',$userid]];
    }else {
      $where[] = ['or',['=','b.creator',$userid]];
    }
    if ($this->_request['departmentid']){
      $tempdept = explode(',',$this->_request['departmentid']);
      $where[] = ['in' , 'b.departmentid' , $tempdept];
    }

    if ($this->_request['submitdatestart']) {
      $pwhere=" and submitdate>='".$this->_request['submitdatestart']."'";
    }
    if ($this->_request['submitdateend']) {
      $pwhere.=" and submitdate<='".$this->_request['submitdateend']."'";
    }
    if ($this->_request['directsubmit']==1){
      $pwhere.=" and (directsubmit=1 or state=".$this->SUBMITTED_PROJECT.")";
      
    }
    
    
    $model = FzrbsBudgetBalance::find()->alias('b')->select('p.submitdate,b.projectid,sum(budget) as budget,sum(final) as final,p.title,p.serial,p.chargername,u.name as name')
            ->rightJoin("(select * from ".FzrbsBudgetProject::tableName()." where id>0 $pwhere ) p", 'b.projectid=p.id')
            ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'b.creator=u.userid')
            ->where($where)->groupBy('projectid,b.creator');
    
    
    $total = $model->count();
    
    $res = $model->orderBy($order)->limit($limit)->offset($offset)->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["offset"] = $offset;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    
    
    return $this->_result;
  }
  public function actionGetprolist(){
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $order='id desc';
    if ($this->_request['orderby']) {
      $order = $this->_request['orderby'];
    }
    
    $where = $this->getWhere();
    
    
    $columns = $this->_request['columns'];
    $model = $this->getProjectModel($where,$columns);
    
    
    $total = $model->count();
    
    $res = $model->orderBy($order)->limit($limit)->offset($offset)->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["offset"] = $offset;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    
    
    return $this->_result;
  }

  // 锁定项目禁止修改
  public function actionLockpro(){
    $id = $this->_request['id'];
    if (!$id) return array('errorMessage'=>'id不能为空');
    $p = FzrbsBudgetProject::findOne($id);

    if (!$this->haspower('财务管理',$this->agentId,$p['departmentid'],'')) {
      $dept = WeixinOaDepartment::findOne($p['departmentid']);
      return array('errorMessage'=>'没有部门【'.$dept['name'].'】的【财务管理】权限,让【会计】进行操作或让【管理员】赋予权限');
    }
    if ($p->lock){
      $p->lock = 0;
    }else{
      $p->lock = 1;
    }
    try {
      $p->save();
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    
    return array('errorMessage'=>'');
  }
  public function actionGetreportbyprojectid(){
    $id = $this->_request['id'];
    $field = $this->_request['field'];
    if (!$id) return array('data'=>'');
    if (!$field) return array('data'=>'');
    $p = FzrbsBudgetProject::findOne($id);
    if (!$p) return array('data'=>'');
    return array('data'=>$p[$field]);
  }
  public function actionAltercreator(){
    $id=$this->_request['id'];
    // 查询项目
    $p = FzrbsBudgetProject::findOne($id);
    // 只有项目创建人才能修改
    if ($p->creator!=$this->_adminInfo['wxuserid']) return array('errorMessage'=>'没有权限');
    $newcreator = $this->_request['creator'];
    $departmentid = $this->_request['departmentid'];
    if (!$newcreator) return array('errorMessage'=>'请选择新创建人');
    $newuser = WeixinOAUserInfo::find()->where(['userid'=>$newcreator])->one();
    $p->creator = $newcreator;
    $temp=['creator'=>$newcreator,'creatorname'=>$newuser->name];
    if ($departmentid){
      $p->departmentid = $departmentid;
      $dept = WeixinOaDepartment::findOne($p->departmentid);
      $p->department=$dept['name'];
      $temp['departmentid'] = $p->departmentid;
      $temp['department'] = $dept['name'];
    }
    $transaction = Yii::$app->db->beginTransaction();
    try {
      $p->save();
      FzrbsBudgetBalance::updateAll($temp,['projectid'=>$p->id]);

    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    return array('errorMessage'=>'');
  }
  private function sumProjectSpecial($where){
      // // FzrbsBudgetProject未通过决算的，显示为0
      $bmodel = FzrbsBudgetProject::find()->alias('p')
              ->select("p.id,IF( p.realfinalexpend>0,p.realfinalexpend,p.`realbudgetexpend` ) as `realfinalexpend`, ".$this->getBselect())
              ->leftJoin(['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id and b.final<=0')
              ->where($where)
              ->asArray()
              ->groupBy("p.id");
     

      $budgetincome=$bmodel->sum('budgetincome');
      $realfinalexpend=$bmodel->sum('realfinalexpend');

      // 已决算统计决算值
      $fmodel = FzrbsBudgetProject::find()->alias('p')
              ->select("p.id,".$this->getBselect())
              ->leftJoin(['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id and b.final>0')
              ->where($where)
              ->groupBy("p.id");
   
      $finalincome=$fmodel->sum('finalincome');

     
    // 实际收入 = 预算收入 + 决算收入
    return array('finalincome'=>$finalincome+$budgetincome,'finalexpend'=>$realfinalexpend);
  }
  private function sumProject($condition){
    $initwhere = $condition['where']; 
    $where = ['and',['>','p.id',0]];
    if($condition['type']){
      $where[]=['=','p.type',$condition['type']];
    }

    // 未决算统计预算值
    $bmodel = $this->getProjectModel($initwhere,'')->andWhere($where)->andWhere(['<=','p.state',$this->FINAL_PROJECT]);

    $bmodel = FzrbsBudgetProject::find()->alias('p')
              ->select("p.id,IF( p.state>".$this->FINAL_PROJECT.",sum(CASE WHEN b.type=$this->INCOME_DICID THEN b.final ELSE 0 END),sum(CASE WHEN b.type=$this->INCOME_DICID THEN b.budget ELSE 0 END)) as `finalincome`,IF( p.state>".$this->FINAL_PROJECT.",p.realfinalexpend,p.`realbudgetexpend` ) as `realfinalexpend`")
              ->leftJoin(['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id')
              ->where($initwhere)->andWhere($where)
              ->asArray()
              ->groupBy("p.id");

    
    $finalincome=$bmodel->sum('finalincome');
    $realfinalexpend=$bmodel->sum('realfinalexpend');

    // 实际收入 = 预算收入 + 决算收入
    return array('finalincome'=>$finalincome,'finalexpend'=>$realfinalexpend);
  }
  private function sumBalance($condition){
     
    // 项目对应的收支决算为零时按预算进行合计，否则一很按决算合计
    $where = ['and',['>','id',0]];

    $projectWhere = ["id>0"];

    if ($condition['departmentids']){
      $projectWhere[]="departmentid in(".$condition['departmentids'].")"; 
    }
    if ($condition['submitdatestart']){
      $projectWhere[]= "submitdate>='".$condition['submitdatestart']."'";
    }
    if ($condition['submitdateend']){
      $projectWhere[]= "submitdate<='".$condition['submitdateend']."'";
    }

    if ($condition['moneytype']){
      $where[]= ['in','moneytype',explode(',',$condition['moneytype'])];
    }
    $tempsql = "projectid in (select id from ".FzrbsBudgetProject::tableName()." where (state=".$this->SUBMITTED_PROJECT." or directsubmit=1) and ".implode(" and ",$projectWhere)."  and final>0 )";

    $select = "sum(CASE WHEN type=$this->INCOME_DICID THEN budget ELSE 0 END) AS `budgetincome`, SUM(CASE WHEN type=$this->EXPEND_DICID THEN budget ELSE 0 END) AS `budgetexpend`, sum(CASE WHEN type=$this->INCOME_DICID THEN final ELSE 0 END) AS `finalincome`, SUM(CASE WHEN type=$this->EXPEND_DICID THEN final ELSE 0 END) AS `finalexpend`";
    // 汇总决算大于0的
    $fbalance = FzrbsBudgetBalance::find()->select($select)->where($where)->andWhere(new Expression($tempsql))->asArray()->one();
    // 汇总决算不大于0的
    $bbalance = FzrbsBudgetBalance::find()->select($select)->where($where)->andWhere(new Expression(str_replace("final>0","final<=0",$tempsql)))->asArray()->one();
    $result = array("finalincome"=>0,"finalexpend"=>0);
 
    if ($fbalance){
      $result['finalincome'] = $fbalance['finalincome'];
      $result['finalexpend'] = $fbalance['finalexpend'];
    }
    if ($bbalance){
      $result['finalincome'] += $bbalance['budgetincome'];
      $result['finalexpend'] += $bbalance['budgetexpend'];
    }
    
    return $result;
  }
  // 分类统计
  public function actionGetcatogorystat(){

    $userid = $this->_adminInfo['wxuserid'];
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);


    
    $order='p.departmentid desc';

    $year = date('Y'); 
    
    $submitdatestart = $year.'-01-01';
    if ($this->_request['submitdatestart']) {
      $submitdatestart = $this->_request['submitdatestart'];
    }
    $submitdateend = date('Y-m-d H:i:s');
    if ($this->_request['submitdateend']) {
      $submitdateend = $this->_request['submitdateend'];
    }
    $lastYearSubmitdatestart = date('Y-m-d', strtotime('-1 year', strtotime($submitdatestart)));
    $lastYearSubmitdateend= date('Y-m-d', strtotime('-1 year', strtotime($submitdateend))).' 23:59:59';
    $where = [
        'and',['=','p.deleted',0],
        ['>=', 'p.submitdate', $submitdatestart],['<=', 'p.submitdate', $submitdateend],['or',['=','p.directsubmit',1],['=', 'p.state', $this->SUBMITTED_PROJECT]]
    ];
    
    
    // 判断用户是否有查看权限
    $dept = [];
    // 用户有权限查看的部门
    $deptstr='';
    
    $dept = $this->getDepts();
    
    if (sizeof($dept)){
      $where[] = ['or',['in' , 'p.departmentid' , $dept],['=','p.creator',$userid]];
      $deptstr=implode(',',$dept);
    }else {
      $where[] = ['or',['=','p.creator',$userid],['=','p.charger',$userid]];
    }
    if ($this->_request['departmentid']){
      $where[] =  ['in' , 'p.departmentid' , explode(',',$this->_request['departmentid'])];
      $deptstr=$this->_request['departmentid'];
    } 


    // 细分类
    $typesModle = FzrbsBudgetDict::find()->where(['and',['in','type',['收入类型','支出类型']],['in','subtype','新媒体']])->orderBy('type desc')->all();
    if (!$typesModle) return array("errorMessage"=>"未找到新媒体收入和支出类型");
    $typesids = array_map(function($v){return $v['id'];},$typesModle);
    $incomeTypeids = array_filter(array_map(function($v){return $v['id'];},array_filter($typesModle,function($v){return $v['type']=='收入类型';})));
    $incomeTypeidsstr=implode(',',$incomeTypeids);
    // 今年汇总
    $thisYearTotal = $this->sumBalance(array('submitdatestart'=>$submitdatestart,'submitdateend'=>$submitdateend,'departmentids'=>$deptstr,'moneytype'=>$incomeTypeidsstr));
    // 去年汇总
    $lastYearTotal = $this->sumBalance(array('submitdatestart'=>$lastYearSubmitdatestart,'submitdateend'=>$lastYearSubmitdateend,'departmentids'=>$deptstr,'moneytype'=>$incomeTypeidsstr));
    


    // 按部门统计所
    $model = FzrbsBudgetProject::find()->alias('p')
    ->select("p.departmentid,d.name as deptname")
    ->join('LEFT OUTER JOIN',['b'=>FzrbsBudgetBalance::tableName()],"b.projectid=p.id and b.moneytype in (".implode(",",$typesids).")")
    ->leftJoin(['d'=>WeixinOaDepartment::tableName()],"d.id=p.departmentid")
    ->where($where)->groupBy('p.departmentid,deptname');
    

    // 汇总
    $compare = 0;

    $totalstat = [
      'deptname'=>'合计',
      'lastyear'=>$lastYearTotal['finalincome'],
      'finalincome'=>$thisYearTotal['finalincome'],
      'compare'=>0,
    ];
    if($lastYearTotal['finalincome']){
      $compare = round(($thisYearTotal['finalincome']-$lastYearTotal['finalincome'])/$lastYearTotal['finalincome'],3)*100;
      $totalstat['compare'] = $compare.'%';
    }
    
    $total = $model->count();
    $datas = $model->orderBy($order)->limit($limit-1)->offset($offset)->asArray()->all();

    if(!$datas) $datas =[];
    
    // 上年同期支出和收入,本年支出和收入
    $col=[
      array('title'=>'部门','dataIndex'=>'deptname','key'=>'deptname','fixed'=>'left','width'=>120),
      array('title'=>'上年同期','dataIndex'=>'lastyear','key'=>'lastyear','width'=>120,'className'=>'right'),
      array('title'=>'本年累计','dataIndex'=>'finalincome','key'=>'finalincome','width'=>120,'className'=>'right'),
      array('title'=>'两年对比','dataIndex'=>'compare','key'=>'compare','width'=>120,'className'=>'right'),
    ];
   
    
  
    // 分部门统计
    for ($j=0; $j < sizeof($datas); $j++) {
      
      $thisYearDeptTotal = $this->sumBalance(array('moneytype'=>$incomeTypeidsstr,'submitdatestart'=>$submitdatestart,'submitdateend'=>$submitdateend,'departmentids'=>$datas[$j]['departmentid']));
      $lastYearDeptTotal = $this->sumBalance(array('moneytype'=>$incomeTypeidsstr,'submitdatestart'=>$lastYearSubmitdatestart,'submitdateend'=>$lastYearSubmitdateend,'departmentids'=>$datas[$j]['departmentid']));
      $thisYearDeptTotal['finalincome']=round($thisYearDeptTotal['finalincome'],2);
      $lastYearDeptTotal['finalincome']=round($lastYearDeptTotal['finalincome'],2);
      $datas[$j]['finalincome']=$thisYearDeptTotal['finalincome'];
      $datas[$j]['lastyear']=$lastYearDeptTotal['finalincome'];
      if ($lastYearDeptTotal['finalincome']){
        $datas[$j]['compare'] = round(($thisYearDeptTotal['finalincome']-$lastYearDeptTotal['finalincome'])/$lastYearDeptTotal['finalincome']*100,2);
        $datas[$j]['compare'] .='%';
      }

    }
   
    
    if ($typesids){


      for ($i=0; $i < sizeof($typesids); $i++) { 

        // 分类合计值
        if ($deptstr){
          $typeSum = $this->sumBalance(array('submitdatestart'=>$submitdatestart,'submitdateend'=>$submitdateend,'departmentids'=>$deptstr,'moneytype'=>$typesids[$i]));
          // 存在支出类型，因而在收入为0的情况下，可以输出支出
          $totalstat['type'.$i] = $typeSum['finalincome']?$typeSum['finalincome']:$typeSum['finalexpend'];
          $totalstat['type'.$i] = round($totalstat['type'.$i],2);
        }
      
       
        $col[]=array('width'=>120,'className'=>'right','title'=>$typesModle[$i]['label'],'key'=>'type'.$i,'dataIndex'=>'type'.$i);
    
      
        // 分类分部门
        for ($j=0; $j < sizeof($datas); $j++) {
          
          $balance = $this->sumBalance(array('submitdatestart'=>$submitdatestart,'submitdateend'=>$submitdateend,'departmentids'=>$datas[$j]['departmentid'],'moneytype'=>$typesids[$i]));
          $datas[$j]['type'.$i] = $balance['finalincome']?$balance['finalincome']:$balance['finalexpend'];
          $datas[$j]['type'.$i] = round($datas[$j]['type'.$i],2);

        }
        
      }
    }
  
    // 数据合计
    $datas[] = $totalstat;

    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["offset"] = $offset;
    $this->_result["total"] = $total;
    $this->_result['data'] = $datas;
    $this->_result['col'] = $col;
    return $this->_result;

  }
  public function actionGetcompany(){
    $keyword = $this->_request['keyword'];
    $limit = $this->_request['limit'];
    if(!$limit) $limit = 10;
    $users = FzrbsBudgetCompany::find()->select('*')->where(['like','company',$keyword])->limit($limit)->all();
    return $users;
  }
  public function actionGetcompanyanddict(){

    $keyword = $this->_request['keyword'];
    if (!$keyword){
      return [];
    }
    $limit = $this->_request['limit'];
    if(!$limit) $limit = 20;

    $company = FzrbsCompany::find()->select('id,company as label,company')->where(['like','company',$keyword])->limit($limit/2)->all();
    $dict = FzrbsBudgetDict::find()->select('id,value,label')->where(['like','label',$keyword])->limit($limit/2)->all();
    $result = array_merge($dict,$company);
  
    return $result;
  }
  public function actionGetusers(){
    
    $keyword = $this->_request['keyword'];
    if ($keyword){
      $where=['or',['like','name',$keyword],['=','userid',$keyword]];
    }
    $ids = $this->_request['ids'];
    if ($ids){
      if ($ids && preg_match('/^\d+(,\d+)*$/', $ids)) {
          $where = new Expression('id in ('.$ids.')');
        } else {
            $where = ['in','userid',explode(',',$ids)];
        }
    }
    if (!$where){
      return [];
    }
    $limit = $this->_request['limit'];
    if(!$limit) $limit = 20;
        

        $users = WeixinOAUserInfo::find()->select('id,userid,name,mobile,departmentid,departmentname')->where($where)->limit($limit)->all();
        return $users;
    }
// =================== 合同 ================
   public function actionGetcontractswithprojects(){
    $contractids = $this->_request['contractids'];
    if (!$contractids) return [];
    $contracts = FzrbsContract::find()->where(['in','id',explode(',',$contractids)])->asArray()->all();
    $result = [];
    for ($i=0; $i < sizeof($contracts); $i++) { 
      $where=['and',new Expression("FIND_IN_SET(".$contracts[$i]['id'].",p.contractids)"),['!=','p.deleted',1]];
      $data['contract'] = $contracts[$i];
      $data['projects'] = FzrbsBudgetProject::find()->alias('p')
      ->select("p.*,u.name,".$this->getBselect())
      ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'p.creator=u.userid')
      ->join('LEFT OUTER JOIN',['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id')
      ->where($where)
      ->groupBy('p.id,u.name')
      ->asArray()
      ->all();
    
      $result[] = $data;
    }
    return $result;
   }
// 仅用于支出关联的合同更新
  private function updateBalanceWhenContractupdate($contract){
   
   
    if (!$contract['bid']) {

      return 0;
    };
    // 关联合同的总价
    $temp = FzrbsContract::find()->select('sum(amount) as amount')->where(['and',new Expression('id in (select contractids from '.FzrbsBudgetContract::tableName().' where bid='.$contract['bid'].')')])->one();
    $amount = $temp['amount']?$temp['amount']:0;
  
    // 更新支出决算金额
    FzrbsBudgetBalance::updateAll(['final'=>$amount],['id'=>$contract['bid']]);
    return $amount;
  }
 
  public function actionSavecontract(){
    $userid = $this->_adminInfo['wxuserid'];
    $resp = array('errorMessage'=>'');
    $obj = $this->_request;
  
    if (!$obj['bid']&&!$obj['projectid']){
      return array('errorMessage'=>'bid或projectid不能同时为空');
    }

    if (!$obj['contractids']){
      return array('errorMessage'=>'contractids不能为空');
    }

    // 判断是否可以修改
    $canupdate = $this->canUpdate($obj['projectid'],$obj['bid'],$userid);
    if ($canupdate['errorMessage']){
      return $canupdate;
    }

    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      if ($obj['id']){
        FzrbsBudgetContract::updateAll($obj,['id'=>$obj['id']]);
      } else {
        // 判断是否已经关联过了
        $where = ['and',['=','contractids',$obj['contractids']]];
        if ($obj['projectid']){
          $where[]=['=','projectid',$obj['projectid']];
        }
        if ($obj['bid']){
          $where[]=['=','bid',$obj['bid']];
        }
        $old=FzrbsBudgetContract::find()->where($where)->one();
        if ($old) {
          return array('errorMessage'=>'不要重复关联合同');
        }

        // 判断是否有关联合同的权限

        $obj['creator']=$userid;
        $c = new FzrbsBudgetContract($obj);
        $c->save();
        $obj['id']=$c['id'];
      }
      $this->updateBalanceWhenContractupdate($obj);
      
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    $resp['data'] =$obj;
    return $resp;
  }
  public function actionDelcontract(){
    $userid = $this->_adminInfo['wxuserid'];
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');

    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      
      $item = FzrbsBudgetContract::findOne($id);

      // 判断是否可以修改
      $canupdate = $this->canUpdate($item['projectid'],$item['bid'],$userid);
      if ($canupdate['errorMessage']){
        return $canupdate;
      }

      if ($item['creator']!=$userid){
        return array('errorMessage'=>'只有项目创建人才能删除合同');
      }
      $item->delete();
      
      // 如果是直接关联项目的合同
      if($item['projectid']&&$item['contractids']){
        // 判断是否已经添加过收入
        $balance = FzrbsBudgetBalance::findBySql("select sum(b.budget) as budget,sum(b.final) as final from  fzrbs_budget_balance b where b.projectid=".$item['projectid']." and b.relatedcontractid=".$item['contractids'])->asArray()->one();
    
        if ($balance['budget']>0){

          return array('errorMessage'=>'操作失败，该合同添加过收入，先删除所有收入再删除');
        }
      }


      $amount = $this->updateBalanceWhenContractupdate($item);
      
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    
    
    return array('data'=>'删除成功','amount'=>$amount);
  }
  public function actionGetcontract(){
    $id = $this->_request['id'];
    if ($id) {
      $c=FzrbsContract::find()->where(['id'=>$id])->asArray()->one();


      $pcs = FzrbsContractPaycondition::find()->where(['and',['=','contractid',$id]])->orderBy('date desc')->asArray()->all();
      $collections = FzrbsContractPaycollection::find()->alias('c')->select('c.*,u.name')->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=c.creator')->where(['and',['=','contractid',$id]])->orderBy('id desc')->asArray()->all();
      $c['payconditions'] = $pcs;

      
      for($i=0;$i<sizeof($c['payconditions']);$i++){
        $total = FzrbsContractPaycollection::find()->select('sum(amount) as amount')->where(['and',['=','contractid',$c['payconditions'][$i]['contractid']],['<=','date',$c['payconditions'][$i]['date']]])->one();
        $c['payconditions'][$i]['current'] = $total['amount']?intval($total['amount']/$c['amount']*100):0;
       
      }

  

      $c['paycollections'] = $collections;
      return array('data'=>$c);
    }
    return array('errorMessage'=>'id 为空');
  }
  public function actionGetcontractlist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['>', 'b.id', 0],
    ];
    if ($this->_request['projectid']) {
      $where[] = ['=', 'b.projectid', $this->_request['projectid']];
    }
    if ($this->_request['bid']) {
      $where[] = ['=', 'b.bid', $this->_request['bid']];
    }

    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;

    $model = FzrbsBudgetContract::find()->alias('b')->select('b.*,d.label as typename,c.title,c.serial,c.amount,c.signdate')->leftJoin(['c'=>FzrbsContract::tableName()],'c.id=b.contractids')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],'d.id=b.type')->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('b.id desc')->asArray()->all();
    
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    
    
    return $this->_result;
  }
// ======== 收支表 ========================
  
  public function actionGetinvoicecheck(){
    if (!$this->_request['invoiceno']) return array('errorMessage'=>'invoiceno不能为空');
    $model = FzrbsInvoiceCheck::find()->where(['and',['=','invoiceno',$this->_request['invoiceno']]])->asArray()->one();
    return array('data'=>$model);
  }
  private function updateProReceivedWhenPaycheck($contractid){
    // 先查询与合同相关的所有项目
    $where=[
      'and',
      ['=','deleted',0],
      new Expression("FIND_IN_SET($contractid,contractids)"),
    ];
    $datas = FzrbsBudgetProject::find()->where($where)->all();
    //更新每个项目的已收款
    foreach ($datas as $p) {
      // 查询每个项目对应合同的、确认的回款
      $received=0;
      if($p['contractids']){
        $temp=FzrbsContractPaycollection::find()->select('sum(amount) as received')->where(['and',['in','contractid',explode(',',$p['contractids'])],['=','state',3]])->asArray()->one();
        if($temp){
          $received=$temp['received'];
        }
      }
      $p['receivedmoney']=$received;
      $p->save();
      
    }
  }
  // 回款确认通知财务
  public function actionPaycollectionnotice(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    $old = FzrbsContractPaycollection::findOne($id);
    // 只有经办才可以发起
    if ($this->_adminInfo['wxuserid']!=$old['creator']){
      return array('errorMessage'=>'只有经办才可以发起');
    }
    $contractid = $old['contractid'];
    // 合同项目
    $c = FzrbsContract::findOne($contractid);
    $dept = $c['signdeptid'];
    $deptsql = '';
    if ($dept)  $deptsql =" and  FIND_IN_SET($dept, dept)";
    $sql = "SELECT userid  from weixin_oa_flowrole where FIND_IN_SET('".$this->agentId."',agent)   $deptsql and role in (SELECT id from weixin_oa_role where  FIND_IN_SET('财务管理',powername))";
    $roles = WeixinOaFlowrole::findBySql($sql)->all();
    // 查询结果返回userid字段的值，并用|分割
    $useridsstr = '';
    if($roles){
      $userids = array_column($roles,'userid');
      $useridsstr = implode('|',$userids);
    }else{
      return array('errorMessage'=>'未设置相关的财务, 通知管理员');
    }

   
    
    $msgdata = [
      'touser' => $useridsstr,
      'msgtype' => 'textcard',
      'agentid' => $this->agentId,
      'textcard' => [
          'title' => '有新的回款需要确认，请登录系统操作',
          'description' => '<div class="normal">合同：' . $c['title'].'</div>',
          'url' => "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/budget/viewcollection?contractid=".$c['id'],
          'btntxt' => '详情'
      ]
    ];
  
    $this->sendmsg($msgdata);
 
    
    return array('data'=>'成功');
  }
  public function actionDelpaycollectioncheck(){ 
    $id = $this->_request['id'];
    $agentid = $this->_request['agentid'];
    if (!isset($agentid)){
      $agentid = $this->agentId;
    }
    if(!$id) return array('errorMessage'=>'id 不能为空');
    // 回款
    $old = FzrbsContractPaycollection::findOne($id);
    // 合同
    $c = FzrbsContract::findOne($old['contractid']);
    
    $transaction = Yii::$app->getDb()->beginTransaction();
    
    try {
      if (!$this->haspower('管理',$agentid,$c['signdeptid'],'')) {
        $dept = WeixinOaDepartment::findOne($c['signdeptid']);
        return array('errorMessage'=>'没有部门【'.$dept['name'].'】的【管理】权限');
      }
      $old['state']=1;//未确认
      $old['updator']=$this->userinfo['name'];
      $old->save();

      // 更新项目已收款金额
      $this->updateProReceivedWhenPaycheck($c['id']);

      
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    return array('ret'=>1);
  }
  public function actionPaycollectioncheck(){
    
   
    try {
      $id = $this->_request['id'];
      $note = $this->_request['note'];
      $agentid = $this->_request['agentid'];
      if (!isset($agentid)){
        $agentid = $this->agentId;
      }
      if(!$id) return array('errorMessage'=>'id 不能为空');
      $old = FzrbsContractPaycollection::findOne($id);
      $c = FzrbsContract::findOne($old['contractid']);
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    

    $transaction = Yii::$app->getDb()->beginTransaction();
    
    try {
      if (!$this->haspower('财务管理',$agentid,$c['signdeptid'],'')) {
        $dept = WeixinOaDepartment::findOne($c['signdeptid']);
        return array('errorMessage'=>'没有部门【'.$dept['name'].'】的【财务管理】权限');

      }
      $old['state']=3;//已确认
      $old['note']=$note;
      $old['updator']=$this->userinfo['name'];
      $old->save();

      // 更新项目已收款金额
      $this->updateProReceivedWhenPaycheck($c['id']);

      
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    return array('ret'=>1);
  
  }
  // 查询需要回款确认的合同
  public function actionPaycollectionchecklist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;
    $offset = $limit * ($page - 1);

    $where = [
        'and',
        ['>', 'c.id', 0],
    ];

    $dept = $this->getDepts();
    $order = 'id desc';
    if ($this->_request['orderby']) {
      $order = $this->_request['orderby'];
    }
    if (sizeof($dept)>0) {
      $where[] = ['or',['in' , 'c.signdeptid' , $dept],['=' , 'c.creator' , $this->_adminInfo['wxuserid']]];
    } else {
      $where[] =  ['=' , 'c.creator' , $this->_adminInfo['wxuserid']];
    }
    $where[] = new Expression("c.id in (SELECT DISTINCT contractid FROM ".FzrbsContractPaycollection::tableName()." where state=1 and valid=1)");
    $model = FzrbsContract::find()->alias('c')->select('c.*,d.name as signdept,u.avatar as avatar,u.name,d2.label as typename')->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=c.creator')->leftJoin(['d'=>WeixinOaDepartment::tableName()],'d.id=c.signdeptid')->leftJoin(['d2'=>FzrbsBudgetDict::tableName()],'d2.id=c.type')->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($order)->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }
  public function actionSaveinvoicecheck(){
    $userid = $this->_adminInfo['wxuserid'];
    $obj = $this->_request;
    if (!$obj['projectid']) return array('errorMessage'=>'projectid不能为空');
    if (!$obj['invoiceno']) return array('errorMessage'=>'invoiceno不能为空');
    $project = FzrbsBudgetProject::findOne($obj['projectid']);
    if (!$this->haspower('发票管理',$this->agentId,$project['departmentid'],$project['creator'])) {
      return array('errorMessage'=>'需有【发票管理】权限');
    }
    
    try {
      if ($obj['id']){
        FzrbsInvoiceCheck::updateAll($obj,['id'=>$obj['id']]);
      } else {
        // 回款确认
        $obj['creator'] = $userid;
        $obj['creatorname'] = $this->_adminInfo['username'];
        $temp = new FzrbsInvoiceCheck($obj);
        $temp->save();

        

      }


    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$obj);
  
      

  }
  public function actionDelbalance(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    $projectid = $this->_request['projectid'];
    if(!$projectid) return array('errorMessage'=>'projectid 不能为空');

    
    $project = FzrbsBudgetProject::findOne($projectid);
    if ($project->lock){
      return array('errorMessage'=>'项目已被经办人员锁定，无法修改！');
    }
    // if($project['state']>$this->START_PROJECT&&!in_array($project['type'],$this->specialprojecttype())){
    //   return array('errorMessage'=>'项目已经通过立项审批，无法删除收支');
    // }
    if (!$this->haspower('收支管理',$this->agentId,$project['departmentid'],$project['creator'])) {
      return array('errorMessage'=>'需有收支管理权限');
    }
    // 更新项目
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      FzrbsBudgetBalance::deleteAll(['id'=>$id]);

      // 更新项目的实际支出
      $temp = $this->getProjectRealexpend($projectid);
      $project->realbudgetexpend = $temp['realbudgetexpend'];
      $project->realfinalexpend = $temp['realfinalexpend'];
      $project->budgetbonus = $temp['budgetbonus'];
      $project->finalbonus = $temp['finalbonus'];
      $project->budgettaxtotal = $temp['budgettaxtotal'];
      $project->finaltaxtotal = $temp['finaltaxtotal'];
      $project->save();

    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    return array('data'=>'删除成功');
  }

  public function actionGetbalancelist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
  
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['>', 'b.id', 0],
    ];
    $orderby='id desc';
    if ($this->_request['orderby']){
      $orderby=$this->_request['orderby'];
    
    }
    if ($this->_request['projectid']) {
      $where[] = ['=', 'b.projectid', $this->_request['projectid']];
    }
    if ($this->_request['title']) {
      $where[] = ['LIKE', 'b.title', $this->_request['title']];
    }
    if ($this->_request['coenterprise']) {
      $where[] = ['LIKE', 'b.coenterprise', $this->_request['coenterprise']];
    }
    
    if ($this->_request['type']) {
      $where[] = ['=', 'b.type', $this->_request['type']];
    }
    if ($this->_request['moneytype']) {
      $where[] = ['=', 'b.moneytype', $this->_request['moneytype']];
    }
    // 浏览权限，用户只能查看自己，领
    $model = FzrbsBudgetBalance::find()->alias('b')->select('b.*,d.label as typename,d2.label as moneytypename,u.avatar as avatar')->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'b.creator=u.userid')->leftJoin(['d2'=>FzrbsBudgetDict::tableName()],'d2.id=b.moneytype')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],'d.id=b.type')->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;

    // 计算绩效和税费
    if($this->_request['projectid']){
      
      $temp = $this->getbudgetinfo($this->_request['projectid']);
      if(sizeof($temp['budget'])>0){
        $temp2 = $temp['budget'][2];
        $len = sizeof($temp2);
        $tax = $temp2[$len-3];
        $performance = $temp2[$len-2];
        $this->_result['tax']=$tax;
        $this->_result['performance']=$performance;
      }
    }
    
    return $this->_result;
  }

  public function actionUpdateattachment(){
    $obj = $this->_request;
    if ($obj['id']) {
      try {
        FzrbsBudgetBalance::updateAll(['attatchments'=>$obj['attatchments']],['id'=>$obj['id']]);
      } catch (\Throwable $th) {
        return array('errorMessage'=>$th->getMessage());
      }
      
    } else {
      return array('errorMessage'=>'id为空');
    }

  }
  private function canUpdate($projectid,$bid,$userid){

    if (!$projectid &&!$bid) return array('errorMessage'=>'projectid 和 bid 不能同时为空');

    $pro=null;
    if ($projectid){
      $pro = FzrbsBudgetProject::findOne($projectid);
    }
    if (!$projectid&&$bid){
      $pro = FzrbsBudgetProject::find()->where(['and',new Expression("id in (select projectid from ".FzrbsBudgetBalance::tableName()." where id=$bid)")])->one();
    }
    if ($userid){
      if($pro['creator']!=$userid) {
        return array('errorMessage'=>'只有项目创建人才能操作');
      }
    }
    if ($pro && $pro['thirdno']){
      return array('errorMessage'=>'项目正在审批，禁止新增或更新');
    }

    if($pro['state']==3){
      return array('errorMessage'=>'项目已提交绩效，禁止任何操作');
    }
    return true;
  }
  private function specialprojecttype(){
    return [$this->PURE_NEWMEDIA_TYPE,$this->OTHERS_TYPE,$this->OFFLINE];
  }
  private function needcheckBeforeSaveBalance($project){

    return !in_array($project['type'],$this->specialprojecttype());
  }
  public function actionSavebalance(){
    $userid = $this->_adminInfo['wxuserid'];
    $resp = array('errorMessage'=>'');
    $obj = $this->_request;
    // 检查参数
    $projectid = $obj['projectid'];
    if (!$projectid){
      return array('errorMessage'=>'projectid不能为空');
    }
    $project = FzrbsBudgetProject::findOne($projectid);
    // 已锁定项目无法修改
    if ($project->lock){
      return array('errorMessage'=>'项目已被经办人员锁定，无法修改！');
    }

    // 判断是否可以修改
    if (!$this->haspower('收支管理',$this->agentId,$project['departmentid'],$project['creator'])) {
      return array('errorMessage'=>'需有收支管理权限');
    }
    // 正在审批中禁止修改
    if ($project['thirdno']&&$project['thirdno']!=''&&$project['reject']!=1&&$project['offline']==0) {
      return array('errorMessage'=>'项目正在审批中,需要当前审批人驳回之后方能操作');
    }
    
    $transaction = Yii::$app->getDb()->beginTransaction();
    $changeitems = [];
    try {
      $balancetype = $obj['type'];
      
      // 保存到数据库
      if ($obj['id']){


        
        
        $oldb = FzrbsBudgetBalance::findOne($obj['id']);

        if ($this->needcheckBeforeSaveBalance($project)){

          // 非决算阶段
          if($project['state']!=$this->FINAL_PROJECT){
            

            if (isset($obj['finalnote'])&&$obj['finalnote']!=$oldb->finalnote){
              return array('errorMessage'=>'只有决算阶段，才能新增或修改决算备注');
            } 
            
          } 
          // 未完成预算
          if ($project['state']<$this->FINAL_PROJECT){
            if(isset($obj['final'])&&$obj['final']!=$oldb->final){
              return array('errorMessage'=>'项目未完成预算，无法添加决算金额');
            }
            if(isset($obj['finalnote'])&&$obj['finalnote']!=''&&$obj['finalnote']!=$oldb->final){
              return array('errorMessage'=>'项目未完成预算，无法添加决算备注');
            }
          }else{//已完成预算
            // 已完成预算禁止修改预算金额
            if(isset($obj['budget'])&&$obj['budget']!=$oldb->budget){
              return array('errorMessage'=>'项目已完成预算，无法修改预算金额');
            }

          }

        }

        // 判断修改了哪些内容

        if (isset($obj['finalnote'])&&$obj['finalnote']!=$oldb->finalnote){
          $changeitems[]=array('title'=>'决算备注','newval'=>$obj['finalnote'],'oldval'=>$oldb->finalnote);
        }
        if (isset($obj['budgetnote'])&&$obj['budgetnote']!=$oldb->budgetnote){
          $changeitems[]=array('title'=>'预算备注','newval'=>$obj['budgetnote'],'oldval'=>$oldb->budgetnote);
        }
        if (isset($obj['title'])&&$obj['title']!=$oldb->title){
          $changeitems[]=array('title'=>'收支项目名称','newval'=>$obj['title'],'oldval'=>$oldb->title);
        }


        

        

        $balancetype=$oldb->type;
        FzrbsBudgetBalance::updateAll($obj,['id'=>$obj['id']]);


        // 消息通知
        $tousers = $this->getUserHasApproved($projectid);// 查询当前项目是否在审批，哪些人已经审批过了
        if ($tousers && sizeof($changeitems)>0){
          // 判断修改了哪些字段
          $this->sendChanges($tousers, $this->userinfo['name']."修改了【".$project['title']."】的收支项目", $project,array('title'=>'收支项目名称：'.$oldb['title'],'items'=>$changeitems));
        }
        
      } else {
      
       if ($this->needcheckBeforeSaveBalance($project)){
        
  

        if($obj['final']>0){
          if($project['state']!=$this->FINAL_PROJECT){
            return array('errorMessage'=>'只有决算阶段，才能增改决算收支');
          }
          
        }
       }


        
        // 判断项目是否已经存在
        if (FzrbsBudgetBalance::find()->where(['and',new Expression("projectid=$projectid"),['=','title',$obj['title']]])->count()>0){
          return array('errorMessage'=>'当前项目已经存在名为【'.$obj['title'].'】的收支，不要重复添加');
        }
        // 判断是否是关联项目的创建人，如果不是就不能操作
        $obj['departmentid'] = $this->userinfo['departmentid'];
        $obj['department'] = $this->userinfo['departmentname'];
        $obj['creator'] = $userid;
        $obj['creatorname'] = $this->userinfo['name'];
        $c = new FzrbsBudgetBalance($obj);
        $c->save();
        $obj['id'] = $c->id;


      }

      
      // 更新项目的实际支出
      $temp = $this->getProjectRealexpend($projectid);
      
      $project->realbudgetexpend = $temp['realbudgetexpend'];
      $project->realfinalexpend = $temp['realfinalexpend'];
      $project->budgetbonus = $temp['budgetbonus'];
      $project->finalbonus = $temp['finalbonus'];
      $project->budgettaxtotal = $temp['budgettaxtotal'];
      $project->finaltaxtotal = $temp['finaltaxtotal'];
      $project->save();
      
    // 如果是收入
      if ($balancetype==$this->INCOME_DICID) {
        
        // 查询项目关联合同的总价
        $contractids = $project['contractids'];
        $amount = 0;
        if($contractids){

          // 保存
          $cc = FzrbsContract::find()->select("sum(amount) as amount")->where(['in','id',explode(',',$contractids)])->one();
          if ($cc) $amount=$cc['amount'];

          // 决算金额不能大于合同总价
          $balance = FzrbsBudgetBalance::find()->select("sum(budget) as budget,sum(final) as final")->where(['and',['=','projectid',$project['id']],['=','type',$this->INCOME_DICID]])->asArray()->one();

          // if ($balance['budget']>$amount&&$amount>0&&$project['state']<$this->FINAL_PROJECT){//非预算阶段不理会
          //   $transaction->rollBack();
          //   return array('errorMessage'=>'操作失败，预算收入总和['.$balance['budget'].']大于合同总价['.$amount.']！');
          // }
          if ($balance['final']>$amount&&$amount>0){
            $transaction->rollBack();
            return array('errorMessage'=>'操作失败，决算收入总和['.$balance['final'].']大于合同总价['.$amount.']！');
          }
        }


      }
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    $resp['data'] =$obj;
    return $resp;
  }
  

  // ========================== 审批 ========================

  private function getUserHasApproved($projectid){
    $res = WeixinOaApprovalLog::findBySql("select GROUP_CONCAT(userId  SEPARATOR '|') as tousers from  ".WeixinOaApprovalLog::tableName()." where thirdNo in (SELECT thirdno FROM ".FzrbsBudgetProject::tableName()." where id=$projectid)")->asArray()->one();
    return $res?$res['tousers']:'';
  }
  /**
   * 生成流程数据
   */
  private function gettemplate($condition){
    
    $departmentid = $condition['departmentid']?$condition['departmentid']:$this->userinfo['departmentid'];
 
    $userid = $condition['userid']?$condition['userid']:$this->userinfo['userid'];
    // 指定userid和部门id的
    $where = ['and',['>','id',0]];
    if ($condition['types']){
      $where[] = new Expression("FIND_IN_SET('".$condition['types']."',types)");
    }
    
    
    // 如果是预询价
    if ($condition['inquire']){
      $where[] = ['=','inquire',1];
    }else{
      if (isset($condition['amount'])){
        $amount = $condition['amount'];
        $where[] = new Expression("((lamount=0 and hamount>=$amount) or (lamount<=$amount and hamount=0))");
      }
     
      $where[] = new Expression("(inquire IS NULL OR inquire <> 1)");
    }

    $order = "id desc";
    // 部门和指定用户
    $result = FzrbsBudgetTemplate::find()->where($where)->andWhere(['and',new Expression('FIND_IN_SET('.$departmentid.',dids)'),new Expression("FIND_IN_SET('".$userid."',uids)")])->asArray()->orderBy($order )->one();
    if (!$result){
      
      $result = FzrbsBudgetTemplate::find()->where($where)->andWhere(['and',new Expression("FIND_IN_SET('".$userid."',uids)")])->asArray()->orderBy($order)->one();
    }
    // 指定项目类型和部门
    if (!$result){
     
      $result = FzrbsBudgetTemplate::find()->where($where)->andWhere(['and',new Expression("FIND_IN_SET('".$condition['projecttype']."',projecttypes)"),new Expression('FIND_IN_SET('.$departmentid.',dids)')])->asArray()->orderBy($order)->one();
    }
    // 指定项目类型
    if (!$result){

      $result = FzrbsBudgetTemplate::find()->where($where)->andWhere(['and',new Expression("FIND_IN_SET('".$condition['projecttype']."',projecttypes)")])->asArray()->orderBy($order)->one();
    }
    if (!$result){
 
      // 查询指定部门
      $result = FzrbsBudgetTemplate::find()->where($where)->andWhere(['and',new Expression('FIND_IN_SET('.$departmentid.',dids)'),new Expression('projecttypes is null')])->asArray()->orderBy($order )->one();
    }
    if (!$result){
    
      $result = FzrbsBudgetTemplate::find()->where($where)->andWhere(['and',new Expression('projecttypes is null')])->asArray()->orderBy($order)->one();
    }
    return $result;
  }
  
  public function actionGetflowinfodata(){
    $thirdNo = $this->_request['thirdNo'];
    if(!$thirdNo){
      return null;
    }
    try {
      $info = WeixinOaApprovalInfo::find()->where(['thirdNo'=>$thirdNo])->orderBy('id desc')->one();
      if(!$info) return null;
      $data = json_decode($info['data'],1);
      $approveres = WeixinOaApprovaldata::find()->where(['and',["=","agentid",$this->agentId],["=","thirdNo",$thirdNo]])->one();
      $temp =  [$this->LEGAL_ROLE,$this->INTERNEL_ROLE];
      $amountSmallThen =  false; // 金额是否小于指定金额
      if ($data['projectid']){
        // 统计项目的预算收入和决算收入
        $pp= FzrbsBudgetProject::find()->alias('p')
        ->select($this->getBselect())
        ->join('LEFT OUTER JOIN',['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id')
        ->where(['p.id'=>$data['projectid']])->asArray()->one();
        $pp['amount']=$pp['budgetincome'];
        if ($data['approvaltype']==$this->FINAL_PROJECT){
          $pp['amount']=$pp['finalincome'];
        }
        if($pp['amount']<=$this->AMOUNT){
          $amountSmallThen = true;
          $temp[]=$this->ACCOUNT_ROLE;
        }
      }
      if($approveres){
        $temparr = [];
        $approvearr = json_decode($approveres['data'],true);
        foreach ($approvearr['data']['ApprovalNodes']['ApprovalNode'] as $node) {
          $items = $node['Items']['Item'];
          // 如果是会计
          if ($node['NodeRoleid']==$this->ACCOUNT_ROLE&&!$data['accounts']){// 只显示会计第一次的审批时间
            $data['accounts']=$this->getApproverAndDate($items);
          }

          if (in_array($node['NodeRoleid'],$temp)){
            $temparr=array_merge($temparr,explode(';',$this->getApproverAndDate($items)));
          } else if ($node['NodeRoleid']==$this->PROJECTCHARGER_ROLE_ID){// 项目负责人
            $data['projectcharger'] = $this->getApproverAndDate($items);
          }else if ($node['NodeRoleid']==$this->DEPTCHARGER_ROLE_ID){
            $data['deptcharger'] = $this->getApproverAndDate($items);

          }else if ($node['NodeRoleid']==$this->LEADER_ROLE_ID){ //分管领导
  
            $data['leaders']=$this->getApproverAndDate($items);
          }
          if (!$node['fileurls']){ //如果存在文件，说明是线下会签，显示文件，否则返回审批人及日期
            
            if($node['NodeRoleid']==$this->EDITORIAL_BOARD){// 编委会
              $data['editorialboard']=$this->getApproverAndDate($items);
            }
            if ($node['NodeRoleid']==$this->ECONOMIC_BOARD){ //经审会
              $data['economicalboard']=$this->getApproverAndDate($items);

            }

          }else{ // 

            if($node['NodeRoleid']==$this->EDITORIAL_BOARD){// 编委会
              $data['editorialFileurls']=$node['fileurls'];
              $data['editorialSpeech']= $node['speech'];
              $data['editorialDate']= $node['date'];
              $data['editorialUsername']= $node['username']?$node['username']:$info['userName'];
            }
            if ($node['NodeRoleid']==$this->ECONOMIC_BOARD){ //经审会
              $data['economicalFileurls']=$node['fileurls'];
              $data['economicalSpeech']= $node['speech'];
              $data['economicalDate']= $node['date'];
              $data['economicalUsername']= $node['username']?$node['username']:$info['userName'];
            }

          }



          
     

        }
        // 金额少于300000
        if ($temparr&&$amountSmallThen){
          $tempstr = '';

          for ($i=sizeof($temparr)-1; $i>-1; $i--) {
            $arr = explode(' ',$temparr[$i]);
            if ($tempstr&&$arr[0]&&strpos($tempstr,$arr[0])>-1){ // 有重复
            }else{
              $tempstr.=';'.$temparr[$i];
            }
          }
          $data['approvers']=substr($tempstr,1);
        }
      }
   
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
  
    
    return $data;
  }
  /**
   * 查看流程审批数据
   */
  public function actionGetflowdata(){
    $thirdNo = $this->_request['thirdNo'];
    $projectid=$this->_request['projectid'];
    $state=$this->_request['state'];
    $info=false;
    $typename='';
    
    if($projectid){
      $where = ['and',new Expression("data like '%\"projectid\":$projectid\,%'")];
      if (isset($state)&&$state>-1){
        $where[] = new Expression("data like '%\"approvaltype\":$state%'");
      }
      $info = WeixinOaApprovalInfo::find()->where($where)->orderBy('id desc')->one();
      
      if ($info){
        $thirdNo=$info['thirdNo'];
        $infodata = json_decode($info->data,1);
        $state = $infodata['state'];
        if ($infodata['approvaltype']){
          $state = $infodata['approvaltype'];
        }
     
        if (isset($state)){
          $t = FzrbsBudgetDict::find()->where(['value'=>$state,'type'=>'审批类型'])->asArray()->one();
          if ($t) $typename = $t['label'];
        }
      }
    }
   
    $wfp = new WorkflowParse($this->agentId);
    try {
      $viewdata = $wfp->flowViewdata($thirdNo);
      $d = false;
      
      if ($thirdNo){
        // 流程基本信息
        
        $d=WeixinOaApprovalInfo::find()->alias('a')->select('a.*,d.label as typename,p.partbname,p.content as content,p.finalcontent,p.submitdate,p.reject,p.pdepartmentid,p.title as title,p.fileurls,p.finalfileurls,p.state as state,p.realbudgetexpend,p.realfinalexpend,p.inquire as inquire,p.offline as offline,p.offlinenote,p.performanceratio,p.chargername,d2.label as protypename,d3.label as statename,p.contractids,p.serial')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],'d.value=a.type and d.type="审批类型"')->join('join',['p'=>FzrbsBudgetProject::tableName()],'p.thirdno=a.thirdNo')->leftJoin(['d2'=>FzrbsBudgetDict::tableName()],'d2.value=p.type and d2.type="项目类别"')->leftJoin(['d3'=>FzrbsBudgetDict::tableName()],'d3.value=p.state and d3.type="审批类型"')->where(['a.thirdNo'=>$thirdNo])->asArray()->one();
        if ($d) {
          $d['statusname']=$this->statusCn[$d['status']];
          if ($d['pdepartmentid']){
            $d['pdepartment']=WeixinOaDepartment::find()->where(['id'=>$d['pdepartmentid']])->asArray()->one()['name'];
          }
          $d['typename']=$typename;
        }
    
      }
      $isFinal = false;// 是否是决算
      $isLeader = false; // 是否是分管领导
      $notShowCountersign = 0; // 是否显示会签按钮;
      if ((!$d||!$d['id']) && $projectid){
        
          $d=FzrbsBudgetProject::find()->alias('p')->select('p.*,u.avatar as avatar,u.name as userName,d.label as protypename,dp.name as pdepartment')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],'d.value=p.type')->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'p.creator=u.userid')->leftJoin(['dp'=>WeixinOaDepartment::tableName()],'dp.id=p.pdepartmentid')->where(['p.id'=>$projectid])->asArray()->one();
          $d['status']=$info['status'];
          $d['statusname']=$this->statusCn[$info['status']];
          $d['thirdno']= $info['thirdNo'];
          if ($typename) $d['typename']=$typename;
          
          
      }
    
    
    if ($projectid) $d['id']=$projectid;

    if ($d&&$d['id']) {

      $balance = FzrbsBudgetBalance::find()->alias('b')->select($this->getBselect())->where(['b.projectid'=>$d['id']])->asArray()->one();
      if ($balance) {
        $d['budgetincome']=$balance['budgetincome']?$balance['budgetincome']:0;
        $d['finalincome']=$balance['finalincome']?$balance['finalincome']:0;
      }
    }

    } catch (\Throwable $th) {
      return array('errorMessage'=> $th->getMessage());
    }
    
    if ($viewdata&&$viewdata['approval']){
      $step = $viewdata['step']+1;
      $node = $viewdata['approval'][$step];
      if ($node){
        

        if (in_array($node['NodeRoleid'],[$this->LEADER_ROLE_ID,$this->ECONOMIC_CHARGER])){
          $viewdata['showSpecialBtn']=1;

        }
        
        if ($node['Items']&&$node['Items']['Item']){
          $temp = array_map(function($item){
            return $item['ItemUserId'];
          },$node['Items']['Item']);
        
          
          if (!in_array($this->_adminInfo['wxuserid'],$temp)){//不是当前节点的审批人
            $viewdata['showSpecialBtn']=0;
          }else{
            // 是当前审批人
            $isLeader = $node['NodeRoleid'] == $this->LEADER_ROLE_ID;
           
            
          }
        
        }
      }
      for ($i=0; $i < sizeof($viewdata['approval']); $i++) { 
        if ($viewdata['approval'][$i]['NodeRoleid']){
          $role = WeixinOaRole::findOne($viewdata['approval'][$i]['NodeRoleid']);
          $viewdata['approval'][$i]['title'].='（'.$role['rolename'].'）';
        }else if ($viewdata['approval'][$i]['NodeLevel']==1){
          $viewdata['approval'][$i]['title'].='（直接上级）';
        }
      }
      $isFinal = $d['state']==$this->FINAL_PROJECT;
      // 决算、分管、吴金安，不显示会签按钮
      $notShowCountersign = ($isFinal&&$isLeader&&$this->_adminInfo['wxuserid']=='WuJinAn')?1:0;
      $viewdata['notShowCountersign']=$notShowCountersign;

    }

    $d['title'] = $d['inquire']?'[预询价]'.$d['title']:$d['title'];
    
    $result = array('viewdata'=>$viewdata,'basic'=>$d,'statusCn'=>$this->statusCn);
    return $result;

  }
  public function actionSubmitmeasure(){
    $id = $this->_request['id'];
    if (!$id) {
      return array('errorMessage'=>'id 不能为空');
    }
    $p=FzrbsBudgetProject::findOne($id);
    if (!$this->haspower('进度管理',$this->agentId,$p['departmentid'],$p['creator'])) {
      return array('errorMessage'=>'需要【进度管理】权限');
    }
    FzrbsBudgetProject::updateAll(['submitdate'=>date('Y-m-d'),'state'=>$this->READYTOSUBMIT_PROJECT],'id='.$id);
    return array('errorMessage'=>'');
  }
  public function actionGetflow(){
    $condition = $this->_request;
    if (!$condition['userid']){
      return array('errorMessage'=>'userid 不能为空');
    }
    $userinfo = WeixinOAUserInfo::find()->where(['userid'=>$condition['userid']])->asArray()->one();
    $condition['departmentid']=$condition['pdepartmentid'];
    // 根据开票单位查询对应同名主体的id，用于获取角色时同时根据部门和主体查询角色
    if ($condition['partbname']){
      $company = WeixinFinanceCompany::find()->select('id')->where(['=','company',$condition['partbname']])->asArray()->one();
      if ($company){
        $condition['company']=$company['id'];
      }
    }
    try{
      $approvedata = $this->generateApplydata('',$userinfo,$condition);
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    $flow = $approvedata['flow'];
    if($flow){
      $approvearr = $flow;
      foreach ($approvearr['data']['ApprovalNodes']['ApprovalNode'] as $k=>$r) {
          $tmparr = array();
          if(count($r['Items']['Item'])>1){
            $tmparr['title'] = '直接上级';
            if ($r['NodeType']==2 && isset($r['NodeTagid'])){
              $tmparr['title'] = $this->getUserTagName($r['NodeTagid']);
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
          if ($r['NodeRoleid']){
            $role = WeixinOaRole::findOne($r['NodeRoleid']);
            $tmparr['title'].='（'.$role['rolename'].'）';
          }else if ($r['NodeLevel']==1){
            $tmparr['title'].='（直接上级）';
          }
          $approvaldata[] = $tmparr;
      }
      $notifier = array();
      foreach ($approvearr['data']['NotifyNodes']['NotifyNode'] as $r) {
          $notifier[] = $r['ItemName'];
      }
      $step = intval($approvearr['data']['approverstep'])-1;

    }
    return  array('flow'=>$approvedata['flow'],'viewdata'=>array('step'=>$step,'approval'=>$approvaldata,'notify'=>$notifier,'templatename'=>$approvedata['templatename'],'templateid'=>$approvedata['templateid']),'statusCn'=>$this->statusCn);
    
  }
  /**
   * 流程预览
   */
  public function actionViewflow(){
    
    if (!$this->_request['projectid']) {
      return array('errorMessage'=>'projectid 不能为空');
    }
    $act = $this->_request['act'];
    if (!$act){
      return array('errorMessage'=>'act 不能为空,1-立项,2-预算,3-决算,4-提交计量');
    }
    try {
      $approvedata = $this->getflow($this->_request['projectid'],$act,'');
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    $flow = $approvedata['flow'];
    if($flow){
      $approvearr = $flow;
      foreach ($approvearr['data']['ApprovalNodes']['ApprovalNode'] as $k=>$r) {
          $tmparr = array();
          if(count($r['Items']['Item'])>1){
            $tmparr['title'] = '直接上级';
            if ($r['NodeType']==2 && isset($r['NodeTagid'])){
              $tmparr['title'] = $this->getUserTagName($r['NodeTagid']);
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
          if ($r['NodeRoleid']){
            $role = WeixinOaRole::findOne($r['NodeRoleid']);
            $tmparr['title'].='（'.$role['rolename'].'）';
          }else if ($r['NodeLevel']==1){
            $tmparr['title'].='（直接上级）';
          }
          $approvaldata[] = $tmparr;
      }
      $notifier = array();
      foreach ($approvearr['data']['NotifyNodes']['NotifyNode'] as $r) {
          $notifier[] = $r['ItemName'];
      }
      $step = intval($approvearr['data']['approverstep'])-1;

    }
    return  array('viewdata'=>array('step'=>$step,'approval'=>$approvaldata,'notify'=>$notifier,'templateid'=>$approvedata['templateid']),'statusCn'=>$this->statusCn);
    
  }
  private function getflow($projectid,$act,$thirdNo){
    
    $p= FzrbsBudgetProject::find()->alias('p')->select('p.*,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.budget ELSE 0 END) as budgetincome,SUM(CASE WHEN b.type='.$this->EXPEND_DICID.' THEN b.budget ELSE 0 END) as budgetexpend,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.final ELSE 0 END) as finalincome,SUM(CASE WHEN b.type='.$this->EXPEND_DICID.' THEN b.final ELSE 0 END) as finalexpend')->join('LEFT OUTER JOIN',['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id')->where(['=','p.id',$projectid])->groupBy('p.id')->asArray()->one();
    $condition = array();
    if (!$act){
      return array('errorMessage'=>'act 不能为空,1-立项,2-预算,3-决算,4-提交计量');
    }
    // 判断是否是预询价
    $condition['inquire']=$p['inquire'];
    // 审批流程根据立项主体部门来确定
    $condition['departmentid']=$p['pdepartmentid'];
    $condition['types']=$act;
    $condition['projecttype']=$p['type'];
    

    // 所有审批都要添加项目负责人
    $condition['charger']=$p['charger'];

    if($act==$this->BUDGET_PROJECT){ //如果是预算或决算审批需要加上项目金额
      $condition['amount']=$p['budgetincome'];
      
    } else if ($act==$this->FINAL_PROJECT){
      $condition['amount']=$p['finalincome'];
    }
    // 根据开票单位查询对应同名主体的id，用于获取角色时同时根据部门和主体查询角色
  if ($p['partbname']){
    $company = WeixinFinanceCompany::find()->select('id')->where(['=','company',$p['partbname']])->asArray()->one();
    if ($company){
      $condition['company']=$company['id'];
    }
  }
    
    return  $this->generateApplydata($thirdNo,$this->userinfo,$condition);
  }
  private function generateApplydata($thirdNo,$userinfo,$condition){
      
      $condition['roleToUserAll']=true; // 每个角色如果有多人就全部返回
      // 查询流程id
     
      $template = $this->gettemplate($condition);
      
      $templateid = $template['templateid'];
      if (!$templateid) {
        throw new Exception('流程未设置');
      }

      // 生成流程数据
      $wfp = new WorkflowParse($this->agentId);
      $flowdata = $wfp->flowParse($userinfo['userid'], $templateid,$condition);
      // 如果是立项审批，需要加上项目负责人
      if ($condition['charger']){
        $user = $this->getUserinfo($condition['charger']);
        if ($user){
          array_unshift($flowdata['ApprovalNodes']['ApprovalNode'],array(
            'NodeStatus'=>1,
            'NodeAttr' => 1,
            'NodeType' => 3,
            'NodeLevel' => 1,
            'NodeRoleid'=>$this->PROJECTCHARGER_ROLE_ID,
            'Items'=>array('Item'=>array(0=>array('ItemName' => $user['name'],
            'ItemParty' => '',
            'ItemImage' => $user['avatar'],
            'ItemUserId' => $user['userid'],
            'ItemStatus' => 1,
            'ItemSpeech' => '',
            'ItemOpTime' => 0)),
            ))
            );
        }
      }
      
        if (!$flowdata['ApprovalNodes']['ApprovalNode']){
          $user = $this->userinfo;
          array_unshift($flowdata['ApprovalNodes']['ApprovalNode'],array(
            'NodeStatus'=>1,
            'NodeAttr' => 1,
            'NodeType' => 3,
            'NodeLevel' => 1,
            'NodeRoleid'=>$this->PROJECTCHARGER_ROLE_ID,
            'Items'=>array('Item'=>array(0=>array('ItemName' => $user['name'],
            'ItemParty' => '',
            'ItemImage' => $user['avatar'],
            'ItemUserId' => $user['userid'],
            'ItemStatus' => 1,
            'ItemSpeech' => '',
            'ItemOpTime' => 0)),
            ))
            );
        }


      // 完整流程信息
      $flow = array(
        'errcode' => 0,
        'errmsg' => 'ok',
        'data' => array(
          'ThirdNo' => $thirdNo,
          'OpenTemplateId' => $flowdata['OpenTemplateId'],
          'OpenSpName' => $flowdata['OpenSpName'],
          'OpenSpstatus' => 1,
          'ApplyTime' => time(),
          'ApplyUsername' => $userinfo['name'],
          'ApplyUserParty' => '',
          'ApplyUserImage' => $userinfo['avatar'],
          'ApplyUserId' => $userinfo['userid'],
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
      $tt = WeixinOaTemplates::find()->where(['templateId'=>$template['templateid']])->one();
      if ($tt) $templatename=$tt['templateName'];
      return array('templateid'=>$templateid,'templatename'=>$templatename,'approvalUserid'=>implode('|',$approvalUserid),'approvalUsername'=>implode('|',$approvalUsername),'applydata'=>$applydata,'flow'=>$flow);
  }
  
/**
 * 开启审批
 */
  public function actionStartflow(){
    $userinfo = $this->userinfo;
    $resp = array('errorMessage'=>'');
    $postdatas = $this->_request;
    $act = $this->_request['act'];
    $report=$this->_request['report'];
    $needUpdateData=array('thirdno'=>$postdatas['thirdno']);
    if (!$act){
      return array('errorMessage'=>'act 不能为空,1-立项,2-预算,3-决算,4-提交计量,6-撤回计量');
    }
    if (!$postdatas['projectid']) {
      return array('errorMessage'=>'projectid 不能为空');
    }
    if($report){
      $needUpdateData[($act<$this->FINAL_PROJECT?'budget':'final').'report']=$report;
    }
    
    
    // 只有项目经办才能提交流程
    $p = FzrbsBudgetProject::find()->alias('p')->select('p.*,u.name as creatorname,d.label as statename')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=p.state and d.type='审批类型'")->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=p.creator')->where(["p.id"=>$postdatas['projectid']])->asArray()->one();
    $actname=$p['statename'];

    // 如果是立项判断一下是否是其他业务，如果是禁止立项
    if ($act==$this->START_PROJECT && $p['type']!=$this->OTHERS_TYPE){ //如果是立项
      return array('errorMessage'=>'其他业务，禁止立项');
    }
    

    if ($act==$this->READYTOSUBMIT_PROJECT && $p['state']==$this->READYTOSUBMIT_PROJECT && $p['history'] && strpos($p['history'],'4')!==false){ 
      // 更新状态为已提交
      $tttemp = ['state'=>$this->SUBMITTED_PROJECT];
      if (!$p['submitdate']) $tttemp['submitdate']=date('Y-m-d');
      FzrbsBudgetProject::updateAll($tttemp,['id'=>$postdatas['projectid']]);
      return array('errorMessage'=>'项目曾提交过计量，无须再提交；目前项目已经更新为已提交，可刷新后查看');
    }
    
    
    if (in_array($act,[$this->BUDGET_PROJECT,$this->FINAL_PROJECT])){ // 是预算或决算
      
      // 判断新媒体收入金额合计是否大于等于新媒体支出合计
      $balance = FzrbsBudgetBalance::find()->alias('b')->select($this->getBselect())->where(['and',['projectid'=>$p['id']],new Expression("moneytype in (SELECT id from fzrbs_budget_dict where subtype='新媒体')")])->asArray()->one();

      $eformular = FzrbsBudgetDict::find()->where(['and',['=','type','税费计算公式'],['=','subtype','支出']])->orderBy('id desc')->one();
      
      if($act==$this->FINAL_PROJECT){ // 是决算申请
        $actname='决算';
        if($p['hascontract']==1&&!$p['contractids']&&!in_array($p['type'],[$this->OFFLINE])){// 项目有合同，但是暂时没有，不能发起决算
          return array('errorMessage'=>'项目需要关联合同，但是暂时还未关联，不能发起决算');
        }
        // 判定合同金额如果小于收入金额，不能提交
        $cs = FzrbsContract::find()->select('sum(amount) as amount,GROUP_CONCAT(balancetype) as balancetype')->where(['in','id',explode(',',$p['contractids'])])->one();
        if (!$cs) return array('errorMessage'=>'所绑定合同已被删除，请更新项目绑定的合同');
        if($cs['amount']<$p['finalincome']){
          // 判断是否包含框架合同
          if($cs['balancetype']&&in_array(108,explode(',',$cs['balancetype']))){
            // 包含框架合同，合同金额可以小于收入金额
          }else {
            return array('errorMessage'=>'合同金额小于收入金额，不能提交决算');
          }
          
        }
        if ($balance['finalincome']-$balance['finalexpend']<0){
          return array('errorMessage'=>'新媒体决算收入金额【'.$balance['finalincome'].'】小于新媒体支出【'.$balance['finalexpend'].'】，不能提交');
        }

        // 更新决算税率公式
        $needUpdateData['finalexpendtaxformula']=$eformular['label'];
      }else{// 是预算申请
        $actname='预算';
        if ($balance['budgetincome']-$balance['budgetexpend']<0){
          return array('errorMessage'=>'新媒体预算收入金额【'.$balance['budgetincome'].'】小于新媒体支出【'.$balance['budgetexpend'].'】，不能提交');
        }
        // 更新预算税率公式
        $needUpdateData['expendtaxformula']=$eformular['label'];
      }
    }

    
   
    $needUpdateData['reject']=0;
    $needUpdateData['approvaltype']=$act;
    
    if ($act==$this->READYTOSUBMIT_PROJECT){ // 是提交计量

   
      $actname='提交计量';

      
    }

    if($p['creator']!=$userinfo['userid']){
      return array('errorMessage'=>'只有项目经办才能提交流程');
    }
    $datas = array(
			'agentId'=>$this->agentId,
      'userId' => $userinfo['userid'],
      'userName' => $userinfo['name'],
      'avatar'=>$userinfo['avatar'],
      'departmentid' => $userinfo['departmentid'],
      'department' => $userinfo['departmentname'],
			'thirdNo' => $postdatas['thirdno'],
      'type' => $postdatas['flowtype'],
      'status'=>1,
      'data'=>json_encode(array('id'=>$postdatas['projectid']))
    );
    // 判断是否已经存在
    $d=WeixinOaApprovalInfo::find()->where(['thirdNo' => $postdatas['thirdno']])->one();
    if($d){
      return array('errorMessage'=>'不要重复提交');
    }
    
    try {
      $allflowdatas = $this->getflow($postdatas['projectid'],$act,$postdatas['thirdno']);
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    
    $datas['data'] = json_encode(array('projectid'=>intval($postdatas['projectid']),'approvaltype'=>$act));
    $datas['template'] = $allflowdatas['templateid'];
    $datas['approvalUserid'] = $allflowdatas['approvalUserid'];
    $datas['approvalUsername'] = $allflowdatas['approvalUsername'];

    $transaction = Yii::$app->getDb()->beginTransaction();
    try {



 
      
      // 更新项目
      FzrbsBudgetProject::updateAll($needUpdateData,'id='.$postdatas['projectid']);
      // 保存数据
      $model = new WeixinOaApprovalInfo($datas);
      $model->save();

      // 保存执行流
      $model2 = new WeixinOaApprovaldata($allflowdatas['applydata']);
      $model2->save();

      $title = $p['creatorname'].'的'.$actname.'审批';
      $p['thirdno']=$datas['thirdNo'];
      $this->send($allflowdatas['approvalUserid'],$title,$p);

    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    // 发送消息
    $resp['flow'] = $allflowdatas['flow'];
    $resp['data'] = $datas;
    $resp['allflowdatas']=$allflowdatas;
    return $resp;
  }

      /**
     * 流程状态变更
     */
    public function flowChange($par)
    {
       
      
        $agentid =$par['agentId']?$par['agentId']:$this->agentId;
        $thirdNo=$par['thirdNo'];
        $userid=$par['userid'];
        $status=$par['status'];
        $speech=$par['speech'];
        $offline=$par['offline'];
        $fileurls=$par['fileurls'];
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
          if ($offline==1 && $act=='agree'){ // 下一步线下处理
            $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['next'] = 'offline';
          }
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

        
        // 如果是线下审批
        if ($act=='offlineAgree'){
          // 编委会
          $isNext=true;
          $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['fileurls']=$fileurls;
          $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['speech']=$speech;
          $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['offline']=1;
          
          $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['date']=date('m-d');
          $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['username']=$this->userinfo['name'];
          $status=2;
          
        }


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
          $nextNodeRoleid=$approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeRoleid'];
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
        
        
        // $approveres->save();

        $ret['approveres']=$approveres;
        $ret['logdata']=$logdata;


        if ($offline==1 && $act=='agree') { // 如果是线下上会，那么下个审批人就是申请人

          $ret['touser']=$approvearr['data']['ApplyUserId'];
          $nextdata['approvalUserid'] = $approvearr['data']['ApplyUserId'];
          $nextdata['approvalUsername'] = $approvearr['data']['ApplyUsername'];
          $ret['nextdata']=$nextdata;
        }
       
        if ($ret['touser']){
          // 如果下个审批节点是分管领导或经审负责人或者是线下审批，禁止连审
          if (!$this->continuable($nextNodeRoleid)||$act=='offlineAgree'){
            // 去掉下个审批人，就不会触发连审
            unset($ret['touser']);
          }
        }
        
        return $ret;
      }
      

		return 0;
  }
  private function setNotifylog($data){//保存抄送人信息
		if($data){
			$res = WeixinOaNotifyLog::find()->where(['and',['=','thirdNo',$data['thirdNo']],['=','agentId',$this->agentId],['=','userId',$data['userId']]])->one();
      if(!$res){
				$data['agentid'] =$this->agentId;
        $model = new WeixinOaNotifyLog($data);
				$model->save();
			}
		}
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
    
  
    $model = WeixinOaApprovalInfo::find()->where($where);
    

    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();    
    $_result = array();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $res;
    return $_result;
  }
  /**
   * 查询我审过的流程
   */
  public function actionApprovalhistory(){
    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'p.id desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    if (isset($this->_request['userid'])){
      $userid = $this->_request['userid'];
    }
    $where = ['and',new Expression("p.id in (SELECT SUBSTR(data FROM LOCATE('\"projectid\":',data)+12 FOR LOCATE(',',SUBSTR(data FROM LOCATE('\"projectid\":',data)+12))-1) as projectid from weixin_oa_approval_info where thirdNo in (SELECT DISTINCT thirdNo from weixin_oa_approval_log WHERE userId='$userid' and agentId=".$this->agentId."))")];

    if ($this->_request['title']) {
      $where[] = ['LIKE', 'p.title', $this->_request['title']];
    }


    $model = FzrbsBudgetProject::find()->alias('p')->select('`p`.*,d.label as statename,u.name,u.avatar,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.budget ELSE 0 END) as budgetincome,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.final ELSE 0 END) as finalincome')->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'p.creator=u.userid')->join('LEFT OUTER JOIN',['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=p.state and d.type='审批类型'")->where($where)->groupBy('p.id,u.name,u.avatar,statename');
    

    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();    
    $_result = array();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $res;
    return $_result;
    
  }
  
  
  public function actionCancel(){//撤消
    $userid = $this->_adminInfo['wxuserid'];
		$postdatas = $this->_request;
    if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空');
    
    $data = WeixinOaApprovalInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']],['=','agentId',$this->agentId]])->one();


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
    $action = '非报项目撤销审批';
    $remark = $action . "操作人=" . $this->userinfo['name'] . "，流程ID：".$postdatas['thirdNo']."，数据：".json_encode($data['data'],JSON_UNESCAPED_UNICODE);
    $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
    
    return array('data'=>'成功');
	}

 
  private function generateDif($dif){
    if ($dif>0) {
      return '实际收入较预算增加'.$dif.'元';
    } else if ($dif==0){
      return '预决算一致';
    } else{
      return '实际收入较预算减少'.(-$dif).'元';
    }
  }

  // 计算项目的实际支出，包括利润和绩效
  private function getProjectRealexpend($id){
    if (!$id) return array('budgetexpend'=>0,'finalexpend'=>0,'budgetbonus'=>0,'finalbonus'=>0);

    $project = FzrbsBudgetProject::find()->alias('p')->select('p.*,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.budget ELSE 0 END) as budgetincome,SUM(CASE WHEN b.type='.$this->EXPEND_DICID.' THEN b.budget ELSE 0 END) as budgetexpend,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.final ELSE 0 END) as finalincome,SUM(CASE WHEN b.type='.$this->EXPEND_DICID.' THEN b.final ELSE 0 END) as finalexpend')->leftJoin(['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id')->where(['p.id'=>$id])->asArray()->one();


    $budgettaxtotal = 0; // 税费
    $finaltaxtotal = 0; // 税费
    $finaltaxtotalnote='';
    $budgettaxtotalnote='';
    $expendtotal = 0; // 实际预算支出
    $finalexpend = 0; // 实际决算支出

    $incomes = FzrbsBudgetBalance::find()->alias('b')->select('b.*,d.label as moneytypename')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],'d.id=b.moneytype')->where(['and',['=','b.projectid',$id],['=','b.type',$this->INCOME_DICID],['<>','b.state',$this->INVALID_CODE]])->asArray()->all();

    // 收入税费
    foreach ($incomes as $element) {
      $budgettaxtotal = $budgettaxtotal + $this->getTax($element['budget'],$element['tax'],'');
      $finaltaxtotal = $finaltaxtotal + $this->getTax($element['final'],$element['finaltax'],'');
    }
    
    // 支出
    $expends = FzrbsBudgetBalance::find()->where(['and',['=','projectid',$id],['=','type',$this->EXPEND_DICID],['!=','state',4]])->all();
    foreach ($expends as $element) {
 
      $expendtotal = $expendtotal + $element['budget'];
      $finalexpend = $finalexpend + $element['final'];
      if ($element['specialinvoice']){ // 预算是专票

        $budgettaxtotal = $budgettaxtotal - $this->getTax($element['budget'],$element['tax'],$project['expendtaxformula']);
        
      }
      if ($element['finalspecialinvoice']){ // 决算是专票
        $finaltaxtotal = $finaltaxtotal - $this->getTax($element['final'],$element['finaltax'],$project['finalexpendtaxformula']);
      }
    }
    
    $budgettaxtotal=$budgettaxtotal>=0?$budgettaxtotal:0;
    $finaltaxtotal=$finaltaxtotal>=0?$finaltaxtotal:0;


    
    // 执行绩效奖励,（活动收入-支出-税费）*绩效比例
    $budgetbonus = 0;
    if ($project['budgetincome']>0){
 
      $budgetbonus = ($project['budgetincome']-$project['budgetexpend']-$budgettaxtotal)*$project['performanceratio']/100;
    }
    $finalbonus = 0;
    if ($project['finalincome']>0){

      $finalbonus = ($project['finalincome']-$project['finalexpend']-$finaltaxtotal)*($project['finalperformanceratio']?$project['finalperformanceratio']:0)/100;
    }
    $budgetbonus =$budgetbonus>=0?$budgetbonus:0;
    $finalbonus =$finalbonus>=0?$finalbonus:0;

    // “活动促广告”业务不需要计算税费,// 绩效：支出*比例
    if ($project['type']==$this->ACT_AD_TYPE){
      $budgettaxtotal=0;
      $finaltaxtotal=0;
      $budgetbonus = ($project['budgetexpend']-$budgettaxtotal)*$project['performanceratio']/100;
      $finalbonus = ($project['finalexpend']-$finaltaxtotal)*($project['finalperformanceratio']?$project['finalperformanceratio']:0)/100;
  
    }

    
    if ($project['budgetincome']<=0){
      $budgetbonus = 0;
    }
    if ($project['finalincome']<=0){
      $finalbonus = 0;
    }

    $budgettaxtotal = round($budgettaxtotal,2);
    $budgettaxtotal=$budgettaxtotal>0?$budgettaxtotal:0;
    $budgetbonus = round($budgetbonus,2);
    $finaltaxtotal = round($finaltaxtotal,2);
    $finaltaxtotal=$finaltaxtotal>0?$finaltaxtotal:0;
    $finalbonus = round($finalbonus,2);


    $expendtotal=$expendtotal+$budgettaxtotal+$budgetbonus;
    
    $finalexpend=$finalexpend+$finaltaxtotal+$finalbonus;

    
  
    return array('realbudgetexpend'=>round($expendtotal,2),'realfinalexpend'=>round($finalexpend,2),'budgetbonus'=>$budgetbonus,'finalbonus'=>$finalbonus,'budgettaxtotal'=>$budgettaxtotal,'finaltaxtotal'=>$finaltaxtotal);
  }

  private function getTax($amount,$tax,$formula){
    $tax = $tax/100;
    $temp = $formula?$formula:'金额*税率';
    $temp = str_replace(['金额','税率'],[$amount,$tax],$temp);
    
    $temp = eval('return '.$temp.';');
    // return round($temp,2);
    return $temp;
  }
  private function getTaxNote($amount,$tax,$formula){
    // $tax = $tax/100;
    $temp = $formula?$formula:'金额*税率';
    $temp = str_replace(['金额','税率'],[$amount,$tax.'%'],$temp);
    
    return $temp;
  }
  private function getbudgetinfo($id){
    $resp = array('errorMessage'=>'');
    if ($id) { // 项目id

      $project = FzrbsBudgetProject::find()->alias('p')->select('p.*,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.budget ELSE 0 END) as budgetincome,SUM(CASE WHEN b.type='.$this->EXPEND_DICID.' THEN b.budget ELSE 0 END) as budgetexpend,sum(CASE WHEN b.type='.$this->INCOME_DICID.' THEN b.final ELSE 0 END) as finalincome,SUM(CASE WHEN b.type='.$this->EXPEND_DICID.' THEN b.final ELSE 0 END) as finalexpend')->leftJoin(['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id')->where(['p.id'=>$id])->asArray()->one();
      if (!$project){
        return array('errorMessage'=>'项目不存在，检查id是否正确');
      }

      try {
        $budgettaxtotal = 0; // 税费
        $finaltaxtotal = 0; // 税费
        $finaltaxtotalnote='';
        $budgettaxtotalnote='';
        $expendtotal = 0; // 实际预算支出
        $finalexpend = 0; // 实际决算支出
        $incomeResult = [];
        // 收入明细
        
        $incomes = FzrbsBudgetBalance::find()->alias('b')->select('b.*,d.label as moneytypename')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],'d.id=b.moneytype')->where(['and',['=','b.projectid',$id],['=','b.type',$this->INCOME_DICID],['<>','b.state',$this->INVALID_CODE]])->orderBy('id asc')->asArray()->all();
       
        $incometotal = 0;
        $finalincome = 0;
        foreach ($incomes as $element) {

          // 汇总统计同类型的收入
         
          $finalincome = $finalincome + $element['final'];
          $incometotal = $incometotal + $element['budget'];

          // *************************收入税费公式：  收入*税率****************************************
          
          $budgettaxtotal = $budgettaxtotal + $this->getTax($element['budget'],$element['tax'],'');
          $finaltaxtotal = $finaltaxtotal + $this->getTax($element['final'],$element['finaltax'],'');
          $budgettaxtotalnote = $budgettaxtotalnote .'+'. $this->getTaxNote($element['budget'],$element['tax'],'');
          $finaltaxtotalnote = $finaltaxtotalnote .'+'. $this->getTaxNote($element['final'],$element['finaltax'],'');
          
          $index = -1;
          for ($i=0;$i<sizeof($incomeResult);$i++){
            if ($incomeResult[$i]['title']==$element['title']) {
              $index = $i;
              break;
            }
          }
          if ($index>-1){
            
            $incomeResult[$index]['budget']+=$element['budget'];
            $incomeResult[$index]['final']+=$element['final'];
            if ($incomeResult[$index]['budgetnote']){
              $incomeResult[$index]['budgetnote'] .='；'. ($element['budgetnote']?$element['budgetnote']:'');
            }else{
              $incomeResult[$index]['budgetnote']=$element['budgetnote'];
            }
            if ($incomeResult[$index]['finalnote']){
              $incomeResult[$index]['finalnote'].='；'. ($element['finalnote']?$element['finalnote']:'');
            }else{
              $incomeResult[$index]['finalnote']=$element['finalnote'];
            }

          } else {
            
            $incomeResult[] = $element;
          }
        }
        $incomeResult[] = ['title'=>'合计','budget'=>$incometotal,'final'=>$finalincome];
        
        
        // 支出明细
        $expendResult = [];
        $expends = FzrbsBudgetBalance::find()->where(['and',['=','projectid',$id],['=','type',$this->EXPEND_DICID],['!=','state',$this->EXPEND_DICID]])->all();
        foreach ($expends as $element) {
          $expendtotal = $expendtotal + $element['budget'];
          $finalexpend = $finalexpend + $element['final'];


          if ($element['specialinvoice']){ // 是专票
            // ********************** 支出税费公式：  支出*税率     ****************************************
            $budgettaxtotal = $budgettaxtotal - $this->getTax($element['budget'],$element['tax'],$project['expendtaxformula']);
            // 如果$element['budgetnote']不包含 专票：税率 字符串
            if (!preg_match('/专票：税率/', $element['budgetnote'])) {
                $element['budgetnote']=$element['budgetnote'].'（专票：税率'.$element['tax'].'%）';
            }
    
            
            
            $budgettaxtotalnote = $budgettaxtotalnote .'-'. $this->getTaxNote($element['budget'],$element['tax'],$project['expendtaxformula']);
          }
          if ($element['finalspecialinvoice']){ // 决算是专票
            // ********************** 支出税费公式：  支出*税率     ****************************************
            
            $finaltaxtotal = $finaltaxtotal - $this->getTax($element['final'],$element['finaltax'],$project['finalexpendtaxformula']);
            if (!preg_match('/专票：税率/', $element['finalnote'])) {
                $element['finalnote']=$element['finalnote'].'（专票：税率'.$element['finaltax'].'%）';
            }
            
            $finaltaxtotalnote = $finaltaxtotalnote .'-'. $this->getTaxNote($element['final'],$element['finaltax'],$project['finalexpendtaxformula']);
          }
          
          
          $expendResult[] = $element;
        }

       
        // 执行绩效奖励,（活动收入-支出-税费）*绩效比例
        $budgetbonus = 0;
        if ($project['budgetincome']>0){
          $budgetbonus = ($project['budgetincome']-$project['budgetexpend']-$budgettaxtotal)*$project['performanceratio']/100;
        }
        $budgetbonusnote = "(".$project['budgetincome']."-".round($project['budgetexpend'],2)."-".round($budgettaxtotal,2).")*".($project['performanceratio']/100);

        
        $finalbonus = 0;
        if ($project['finalincome']>0){
          $finalbonus = ($project['finalincome']-$project['finalexpend']-$finaltaxtotal)*($project['finalperformanceratio']?$project['finalperformanceratio']:0)/100;
      
        }

        
        $finalbonusnote = "(".$project['finalincome']."-".$project['finalexpend']."-".round($finaltaxtotal,2).")*".(($project['finalperformanceratio']?$project['finalperformanceratio']:0)/100);

        $budgetbonus =$budgetbonus>=0?$budgetbonus:0;
        $finalbonus =$finalbonus>=0?$finalbonus:0;

        // “活动促广告”业务不需要计算税费,// 绩效：支出*比例
        if ($project['type']==$this->ACT_AD_TYPE){
          $budgettaxtotal=0;
          $finaltaxtotal=0;
          $budgettaxtotalnote='“活动促广告”业务不需要计算税费';
          $finaltaxtotalnote='“活动促广告”业务不需要计算税费';

          $budgetbonus = ($project['budgetexpend']-$budgettaxtotal)*$project['performanceratio']/100;

          $budgetbonusnote ="(".$project['budgetexpend']."-".round($budgettaxtotal,2).")*".($project['performanceratio']/100);

          $finalbonus = ($project['finalexpend']-$finaltaxtotal)*($project['finalperformanceratio']?$project['finalperformanceratio']:0)/100;


          $finalbonusnote ="(".$project['finalexpend']."-".round($finaltaxtotal,2).")*".(($project['finalperformanceratio']?$project['finalperformanceratio']:0)/100);

        }
        
        if ($project['budgetincome']<=0){
          $budgetbonus=0;
          $budgetbonusnote='预算收入为0无绩效';
        }
        if ($project['finalincome']<=0){
          $finalbonus=0;
          $finalbonusnote=$project['type']==$this->PURE_NEWMEDIA_TYPE?'':'决算收入为0无绩效';
        }
        

        $budgettaxtotal = round($budgettaxtotal,2);
        $budgettaxtotal=$budgettaxtotal>0?$budgettaxtotal:0;
        $budgetbonus = round($budgetbonus,2);
        $finaltaxtotal = round($finaltaxtotal,2);
        $finaltaxtotal=$finaltaxtotal>0?$finaltaxtotal:0;
        $finalbonus = round($finalbonus,2);

        $expendtotal=$expendtotal+$budgettaxtotal+$budgetbonus;
  
        $finalexpend=$finalexpend+$finaltaxtotal+$finalbonus;

        

        $expendtotal = round($expendtotal,2);
        $finalexpend = round($finalexpend,2);
        $project['budgetincome'] = round($project['budgetincome'],2);
        $project['finalincome'] = round($project['finalincome'],2);
        
        // 预算收支总表
        $summary = [
          ['title'=>'总收入','budget'=>$project['budgetincome'],'final'=>$project['finalincome']],
          ['title'=>'总支出','budget'=>$expendtotal,'final'=>$finalexpend,'finalnote'=>'支出占比:'.(round($project['finalincome']>0?$finalexpend*100/$project['finalincome']:0,1))."%",'budgetnote'=>'支出占比:'.(round($project['budgetincome']>0?$expendtotal*100/$project['budgetincome']:0,1))."%",'memo'=>'支出占比:'.(round($project['budgetincome']>0?$expendtotal*100/$project['budgetincome']:0,1))."%"]
        ];
        
        

          $t = ['title'=>'毛利润','budget'=>$project['budgetincome']-$expendtotal,'final'=>$project['finalincome']-$finalexpend,'budgetnote'=>'毛利润率'.($project['budgetincome']?round(($project['budgetincome']-$expendtotal)/$project['budgetincome']*100,1):0).'%'];
          if ($project['finalincome']){
            $t['finalnote']='毛利润率'.($project['finalincome']?round(($project['finalincome']-$finalexpend)/$project['finalincome']*100,1):0).'%';
            $summary[0]['memo'].=$this->generateDif($project['finalincome']-$project['budgetincome']);
          }
          
          $summary[]=$t;
  
        
        



        $expendResult[] = ['title'=>'税费','budget'=>$budgettaxtotal,'final'=>$finaltaxtotal,'budgetnote'=>$budgettaxtotalnote,'finalnote'=>$finaltaxtotalnote];
        // 没收入的话，绩效全改成0
        $expendResult[] = ['title'=>'执行绩效奖励','budget'=>$budgetbonus,'final'=>$finalbonus,'budgetnote'=>$budgetbonusnote,'finalnote'=>$finalbonusnote];
        $expendResult[] = ['title'=>'合计','budget'=>$expendtotal,'final'=>$finalexpend];
        
        
        $resp['budget'][]=$summary;
        
        $resp['budget'][]=$incomeResult;

        $resp['budget'][]=$expendResult;

     
        
      } catch (\Throwable $th) {
        return array('errorMessage'=>$th->getMessage());
      }
    } else {
      $resp['errorMessage'] = 'id不能为空';
    }
    $resp['project'] = $project;
    return $resp;
  }
  
  /**
   * 获取预算汇总信息
   */
  public function actionGetbudgetinfo(){
    return $this->getBudgetInfo($this->_request['id']);
  }
// ========================== 角色 ====================

public function haspower($power,$agentid,$dept,$creator){
  $userid = $this->_adminInfo['wxuserid'];
  // if($this->_adminInfo['usertype']==1) return true;
  // 本人可以修改
  if($creator == $userid) return true;
  if (!$power) throw new Exception('power不能为空');
  $deptsql = '';
  if ($dept)  $deptsql ="and  FIND_IN_SET($dept, dept)";
  $sql = "SELECT * from weixin_oa_flowrole where  userid='".$userid."' $deptsql and role in (SELECT id from weixin_oa_role where  FIND_IN_SET('$power',powername))";
  $model = WeixinOaFlowrole::findBySql($sql)->one();
  
  
  return $model?true:false;

}
public function actionGetuserbyrole(){
  $type = $this->_request['type'];
  $agentid = $this->agentId;
  $rolename=$this->_request['rolename'];
  $departmentid = $this->_request['departmentid'];
  $where = [
      'and',
      ['>', 'id', 0],new Expression("FIND_IN_SET($agentid,agent)"),
  ];
  
  if ($type){
    $where[]=['=','type',$type];
  }
  if ($rolename){
    $where[]=new Expression("role in (select id from ".WeixinOaRole::tableName()." where rolename='$rolename' and FIND_IN_SET($agentid,agentid))");
  }
  if ($departmentid){
    $where[]=new Expression("FIND_IN_SET($departmentid,dept)");
  }
  $res = WeixinOaFlowrole::find()->where($where)->asArray()->all();
  return $res; 
}
// ========================== 合作单位 =====================
  public function actionSavecompany(){
    $userid = $this->_adminInfo['wxuserid'];
    $obj = $this->_request;
    $p = new FzrbsBudgetCompany($obj);
    

    try {
      if ($p['id']){
        $p->updateAll($obj,['id'=>$p['id']]);
      } else {
        $p['creator'] = $userid;
        $p['creatorname'] = $this->_adminInfo['username'];
        $p->save();
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$p);
  }
  /**
   * 获取企业微信用户信息
   */
  private function getUserinfo($userid)
  {
      $userinfo = WeixinOaUserinfo::find()->where(['=', 'userid', $userid])->asArray()->one();
      return $userinfo;
  }
  public function actionGetthirdno(){
    return $this->getThirdNo();
  }
  private function getThirdNo()
  {
      list($msec, $sec) = explode(' ', microtime());
      $msectime =  substr(sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000).$this->_adminInfo['id'],0,20);
      
      return $msectime;
  }
  // 上传文件
  public function actionUploadfile(){
    return $_FILES;
    if ($_FILES['file']) { // 判断是否需要重新上传
      $config = array(
        "rootPath" => $this->_fileSavePath,
        // "savePath" => 'canteen/excel',
        "savePath" => $this->savepath,
        "maxSize" => 2048000,
        "allowFiles" => array(".xls", ".xlsx","doc","docx","pdf","png","jpeg","jpg","bmp"),
    );
    try {
      $upInfo = new Uploader("file", $config);
      $fileinfo = $upInfo->getFileInfo();
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    
    return array('data'=>$fileinfo);
        
    }
  }

  // =============== 字典 ===============================
  public function actionGetbykeyword(){
    $type = $this->_request['keyword'];
    $subtype = $this->_request['subtype'];
    $showall = $this->_request['showall'];
    $userid = $this->_request['userid'];
    $ids = $this->_request['ids'];
    // $creator=$this->_request['creator'];
    $projecttype = $this->_request['projecttype'];
    
    $order = 'value asc';
    if ($this->_request['order']) $order = $this->_request['order'];
    
    // 如果传入了 ids 参数，则根据 ids 查询
    if ($ids) {
      $idArray = array_filter(explode(',', $ids));
      if (empty($idArray)) {
        return [];
      }
      $where = ['id' => $idArray];
      $datas = FzrbsBudgetDict::find()->where($where)->orderBy($order)->all();
      return $datas;
    }
    
    $where = [
      'and',
      ['=', 'type', $type]
    ];

    if ($type=='公司主体' && $showall=='false'){
      $where[] = new Expression("FIND_IN_SET(".$this->userinfo['departmentid'].", dept)");
    }else if ($type=='收入类型' && $projecttype==$this->NEW_MEDIA_PROJECT){
      $where[]=['=','subtype','新媒体'];
    }else if ($type=='支出类型' && $projecttype==$this->NEW_MEDIA_PROJECT){
      $where[]=['=','subtype','新媒体'];
    }else if (in_array($type,['付款审批类型','项目类别'])){
      $order = 'value asc';
    }
    if ($userid) {
      $where[]=['=','userid',$userid];
    }
    if ($subtype) {
      $where[]=['=','subtype',$subtype];
    }

    

    $datas = FzrbsBudgetDict::find()->where($where)->orderBy($order)->limit(200)->all();
    return $datas;
  }
  public function actionSavecompanydict(){
    $obj = $this->_request;
    $p = new FzrbsBudgetDict($obj);
    // 判断是否已经存在
    if (!$p['type']) return array('errorMessage'=>'type不能为空');
    if (!$p['label']) return array('errorMessage'=>'label不能为空');
    
    try {
      if ($p['id']){
        $p->updateAll($obj,['id'=>$p['id']]);
      } else {
        $res = FzrbsBudgetDict::find()->where(['and',['=','type',$p['type']],['=','label',$p['label']]])->one();
        if ($res) {
          $dept = $res['dept']?($res['dept'].','.$this->userinfo['departmentid']):$this->userinfo['departmentid'];
          $p['dept']= $dept;
          FzrbsBudgetDict::updateAll(['dept'=>$dept],['id'=>$res['id']]);
        } else {
          $p['dept'] = $this->userinfo['departmentid'];
          
          $p->save();
        }
        
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$p);
  }
  private function checkRole($roleName, $dept = null)
    {
        $userid = $this->_adminInfo['wxuserid'];
        $dept=$dept?$dept:$this->userinfo['departmentid'];
        $deptsql = "and FIND_IN_SET($dept, dept)";
        $sql = "SELECT userid,dept from weixin_oa_flowrole where  userid='" . $userid . "' and  role in (select id from weixin_oa_role where rolename = '$roleName') $deptsql";
        $result = WeixinOaFlowrole::findBySql($sql)->asArray()->all();
        return !empty($result);
    }
  public function actionSavedict(){
    $userid = $this->_adminInfo['wxuserid'];
    
    
    if ($this->_request['type']=='公司主体') return $this->actionSavecompanydict();
 
    unset($this->_request['needPower']);
    $obj = $this->_request;

    $p = new FzrbsBudgetDict($obj);
    // 判断是否已经存在
    if (!$p['type']) return array('errorMessage'=>'type不能为空');
    if (!$p['label']) return array('errorMessage'=>'label不能为空');
    $needRole='';
    switch ($p['type']) {
      case '版位':
        $needRole='广告审核';
        break;
      
      default:
        # code...
        break;
    }
    try {
      if ($p['id']){
        // 允許本人修改
        $temp = FzrbsBudgetDict::find()->where(['id'=>$p['id']])->one();
        if ($needRole){
          $t = $this->checkRole($needRole);
          if (!$t){
            return array('errorMessage'=>'需要['.$needRole.']角色');
          }
        }
        if ($temp['creator']!=$userid) {
          
          $hasauth = $this->haspower('字典管理',$this->_request['agentid'],'','');
          
          if (!$hasauth) return array('errorMessage'=>'只有创建人才能修改');
        }
   
        $p->updateAll($obj,['id'=>$p['id']]);
      } else {
        if ($needRole){
          $t = $this->checkRole($needRole);
          if (!$t){
            return array('errorMessage'=>'需要['.$needRole.']角色');
          }
        }
        $where = ['and',['=','type',$p['type']]];
        $max = FzrbsBudgetDict::find()->where($where)->max('value');
        if ($p['subtype']) $where[]=['=','subtype',$p['subtype']];
        $where[]=['=','label',$p['label']];
        $res = FzrbsBudgetDict::find()->where($where)->one();
        // 查询type、subtype相同的记录，如果它们的value值不为空，取最大值+ 1赋给$p['value']
       
    
        if ($max) $p['value'] = ''.($max+1);
        if ($res) return array('errorMessage'=>"[".$p['label']."]已经存在");
        $p->creator=$userid;
        $p->save();
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$p);
  }
  public function actionGetdicttypes(){
    
    return FzrbsBudgetDict::find()->select('type')->distinct()->asArray()->all();
  }
  public function actionGetdictlist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $orderby = 'id desc';
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['>', 'id', 0],
    ];
    if ($this->_request['orderby']) {
      $orderby = $this->_request['orderby'];
    }
    if ($this->_request['type']) {
      $where[] = ['=', 'type', $this->_request['type']];
    }
    if ($this->_request['subtype']) {
      $where[] = ['=', 'subtype', $this->_request['subtype']];
    }
    if ($this->_request['agentid']) {
      $where[] = ['=', 'agentid', $this->_request['agentid']];
    }
    if ($this->_request['label']) {
      $where[] = ['LIKE', 'label', $this->_request['label']];
    }
    
    if ($this->_request['dept']){
      $dept = $this->_request['dept'];
      $where[] = new Expression("FIND_IN_SET($dept, dept)");
    }
    
    $model = FzrbsBudgetDict::find()->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }
  public function actionDeldict(){
    $id = $this->_request['id'];
    $userid = $this->_adminInfo['wxuserid'];
    $model = FzrbsBudgetDict::findOne($id);

    $needRole='';
    switch ($model['type']) {
      case '版位':
        $needRole='广告审核';
        break;
      
      default:
        # code...
        break;
    }

    if ($model['creator']!=$userid) {
      if ($needRole){
          $t = $this->checkRole($needRole);
          if (!$t){
            return array('errorMessage'=>'需要['.$needRole.']角色');
          }
        }
      $hasauth = $this->haspower('管理',$this->agentId,'','');
      if (!$hasauth) return array('errorMessage'=>'只有创建人才能删除');
    }
    
    if(!$id) return array('errorMessage'=>'id 不能为空');
    FzrbsBudgetDict::deleteAll(['id'=>$id]);
    return array('data'=>'删除成功');
  
  }
  
  // =================== 指标 ==================================

  // 查询某个年度全部已经设置了指标的部门
  public function actionGetsettargetdeparts(){
    $year = $this->_request['year'];
    $result =FzrbsBudgetTarget::findBySql("SELECT year, GROUP_CONCAT(dept) as dept from fzrbs_budget_target where year=$year group by year")->one();
    return $result?$result:array('dept'=>'');
  }
  public function actionGettargetlist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
  
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['>', 'r.id', 0],
    ];
  
 
    if ($this->_request['year']) {
      $where[] = ['=', 'r.year', $this->_request['year']];
    }

    
    $model = FzrbsBudgetTarget::find()->alias('r')->select('r.*,u.avatar as avatar,u.name as creatorname')->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=r.creator')->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('r.id desc')->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }
  public function actionDeltarget(){
    $hasauth = $this->haspower('指标设定',$this->agentId,'','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【指标设定】权限');
    }
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    FzrbsBudgetTarget::deleteAll(['id'=>$id]);
    return array('data'=>'删除成功');
  }
  public function actionSavetarget(){
    $userid = $this->_adminInfo['wxuserid'];
    // 判断是否是财 务负责人
    $hasauth = $this->haspower('指标设定',$this->agentId,'','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【指标设定】权限');
    }
    $obj = $this->_request;
    $p = new FzrbsBudgetTarget($obj);
    // 判断是否已经存在
    if (!$p['year']) return array('errorMessage'=>'year不能为空');
    
    try {
      if ($p['id']){
        $p->updateAll($obj,['id'=>$p['id']]);
      } else {
      
        $p['creator'] = $userid;
        
        $p->save();
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$p);
  }
  // ************************ 流程设置模块 *******************************
  public function actionGetflowlist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
  
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['=', 'agentid', $this->agentId],
    ];
  
    if ($this->_request['keyword']) {
      $where[] = ['or',['like','templatename',$this->_request['keyword']],['=','templateid',$this->_request['keyword']]];
    }

    
    if ($this->_request['dept']){
      $dept = $this->_request['dept'];
      $where[] = new Expression("FIND_IN_SET($dept, dids)");
    }
    if ($this->_request['userid']){
      $userid = $this->_request['userid'];
      $where[] = new Expression("FIND_IN_SET($userid, uids)");
    }
    
    $model = FzrbsBudgetTemplate::find()->alias('t')->select('t.*,wt.templateName as templatename')->leftJoin(['wt'=>WeixinOaTemplates::tableName()],'wt.templateId=t.templateid')->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('id desc')->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }
  

  

  public function actionSaveflow(){
    // 判断是否有管理权限
    $hasauth = $this->haspower('管理','合同管理','','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要管理权限');
    }
    $obj = $this->_request;

    $p = new FzrbsBudgetTemplate($obj);


    try {
      if ($p['id']){
        $p->updateAll($obj,['id'=>$p['id']]);
      } else {
        $p['agentid']=$this->agentId;
        $p->save();
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$p);
  }
  public function actionDelflow(){
    // 判断是否是预决算系统管理员
    $hasauth = $this->haspower('管理',$this->agentId,'','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【管理】权限');
    }
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    FzrbsBudgetTemplate::deleteAll(['id'=>$id]);
    return array('data'=>'删除成功');
  }
  public function actionGettemplate(){
     $keyword = $this->_request['keyword'];
     $agentId = $this->agentId;
     if(!$keyword) return[];
    //  if ($this->_request['agentId']) $agentId =$this->_request['agentId'];
     $result = WeixinOaTemplates::find()->where(['or',['like','templateName',$keyword],['=','templateId',$keyword]])->limit(10)->asArray()->all();
     $result = array_map(function($item){
      $item['value']=$item['templateId'];
      $item['label']=$item['templateName'];
      return $item;
     },$result);
     return $result;
  }

  public function actionGetheaders(){
    $typename = $this->_request['typename'];
    if (!$typename) return array('errorMessage'=>'typename不能为空，可以为：已提交报表导出、统计数据导出');
    $res=[];
    switch ($typename) {
      case '已提交报表导出':
        $res = [
          array("key"=>"title","title"=>"项目名称"),
          array("key"=>"serial","title"=>"项目编号"),
          array("key"=>"contractids","title"=>"合同"),
          array("key"=>"department","title"=>"部门"),
array("key"=>"chargername","title"=>"项目负责人"),
          array("key"=>"name","title"=>"业务经办人"),
          array("key"=>"starttime","title"=>"立项时间"),
          array("key"=>"partaname","title"=>"付款方"),
          array("key"=>"contractperiod","title"=>"起止期限"),
          array("key"=>"contractamount","title"=>"合同总价"),
          array("key"=>"budgetincome","title"=>"预算收入"),
          array("key"=>"finalincome","title"=>"决算收入"),
          array("key"=>"receivedmoney","title"=>"已收款"),
          array("key"=>"realbudgetexpend","title"=>"预算支出"),
          array("key"=>"realfinalexpend","title"=>"决算支出"),
          array("key"=>"profit","title"=>"毛利润"),
          array("key"=>"incomeinvoiceamount","title"=>"入账收入"),
          array("key"=>"expendinvoiceamount","title"=>"入账成本"),
          array("key"=>"submitdate","title"=>"提交时间"),
        ];
        break;
      case '统计数据导出':
        $res = [
        ];
          break;
      default:
        
        break;
    }
    return array('data'=>$res);
  }

  // ***************************  部门简码 ***************************
  public function actionGetdeptlist(){

    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['>', 'id', 0]
    ];
    
    if ($this->_request['name']) {
      $where[] = ['LIKE', 'name', $this->_request['name']];
    }
    if ($this->_request['departmentid']){
      $where[] =  ['in' , 'id' , explode(',',$this->_request['departmentid'])];
    }
    
    $model = WeixinOaDepartment::find()->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('id asc')->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }
  public function actionGetdeptcode(){
   
    return WeixinOaDepartment::findOne($this->userinfo['departmentid']);
  }
  public function actionSavedeptcode(){
    if (!$this->_request['code']) return array('errorMessage'=>'code 不能为空');
    if (!$this->_request['id']) return array('errorMessage'=>'id 不能为空');
    try {
      WeixinOaDepartment::updateAll($this->_request,"id=".$this->_request['id']);
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>'保存成功');
    
  }
  private function getApproverAndDate($items=[]){
    $res = '';
    foreach ($items as $ele) {
      if($ele['ItemStatus']==2){
        $res.=';'.$ele['ItemName'].' '.(intval($ele['ItemOpTime']) > 0?date('m/d',$ele['ItemOpTime']):'');
      }
      
    }
    return $res?substr($res,1):'';

  }
   /**
     * 获取毫秒级别的时间戳
     */
    private function getMsecTime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
	}	
    /**
     * 角色名称
     */
    private function getRoleName($id)
    {
        $tagdata = '审批组';
        if($id){
            $temp = WeixinOaRole::findOne($id);
            if ($temp) $tagdata = $temp['rolename'];
        }      
        return $tagdata;
    }

    // 更新所有收支的税费和绩效
    public function actionUpdatealltax(){
      $res = FzrbsBudgetProject::find()->all();
      foreach ($res as $project) {
  
        $temp = $this->getProjectRealexpend($project->id);
        $project->realbudgetexpend = $temp['realbudgetexpend'];
        $project->realfinalexpend = $temp['realfinalexpend'];
        $project->budgetbonus = $temp['budgetbonus'];
        $project->finalbonus = $temp['finalbonus'];
        $project->budgettaxtotal = $temp['budgettaxtotal'];
        $project->finaltaxtotal = $temp['finaltaxtotal'];
        $project->save();
      }
      return array('data'=>'更新成功');

    }
    
}