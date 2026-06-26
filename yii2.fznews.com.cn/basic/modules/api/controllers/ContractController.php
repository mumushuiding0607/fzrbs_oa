<?php

namespace app\modules\api\controllers;
use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WorkflowParse;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\models\FzrbsBudgetDict;
use app\modules\api\models\FzrbsBudgetProject;
use app\modules\api\models\FzrbsCompany;
use app\modules\api\models\FzrbsContract;
use app\modules\api\models\FzrbsContractDebturge;
use app\modules\api\models\FzrbsContractDebturgeLog;
use app\modules\api\models\FzrbsContractLedger;
use app\modules\api\models\FzrbsContractLog;
use app\modules\api\models\FzrbsContractPaycollection;
use app\modules\api\models\FzrbsContractPaycondition;
use app\modules\api\models\FzrbsInvoice;
use app\modules\api\models\FzrbsInvoiceItem;
use app\modules\api\models\FzrbsInvoicing;
use app\modules\api\models\FzrbsInvoicingInvoice;
use app\modules\api\models\WeixinOaApprovaldata;
use app\modules\api\models\WeixinOaApprovalInfo;
use app\modules\api\models\WeixinOaApprovalLog;
use app\modules\api\models\WeixinOaDepartment;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinOaRole;
use app\modules\api\models\WeixinOaTemplates;
use app\modules\api\models\WeixinOAUserInfo;
use Exception;
use PHPExcel;
use yii\db\Expression;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use stdClass;

class ContractController extends ApiBase{
  public $modelClass = 'app\modules\api\models\FzrbsContract';
  protected $statusCn = array('','审批中','已通过','已驳回','已取消');
  protected $userinfo = array();
  protected $agentid = 1000078;
  protected $INCOME_DICID = 15;
  protected $EXPEND_DICID = 16;
  protected $Urge_Deal=6;//已处置
  protected $Urge_Finished = 5;
  protected $Urge_Urging = 2;

  public function init()
  {
      parent::init();

      $this->userinfo = $this->getUserinfo($this->_adminInfo['wxuserid']);
  }
  
  public function actionIndex(){
    
  }
// -------------------------------------------- 权限判断-------------------------------------------------------------

  public function haspower($power,$agentid,$dept,$creator){
    
    // 管理员可以修改
    if($this->_adminInfo['usertype']==1) return true;
    // 本人可以修改
    if($creator == $this->_adminInfo['wxuserid']) return true;
    if (!$power) throw new Exception('power不能为空');
    if (!$agentid) throw new Exception('agentid不能为空');
    $deptsql = '';
    if ($dept)  $deptsql ="and  FIND_IN_SET($dept, dept)";
    $model = WeixinOaFlowrole::findBySql("SELECT * from weixin_oa_flowrole where FIND_IN_SET('$agentid',agent) and userid='".$this->_adminInfo['wxuserid']."' $deptsql and role in (SELECT id from weixin_oa_role where  FIND_IN_SET('$power',powername))")->one();
    return $model?true:false;
  }
// 返回本人和部门领导的userid
  private function meAndLeader(){
    $res=WeixinOAUserInfo::findBySql("SELECT GROUP_CONCAT(userid SEPARATOR ',') as userids from weixin_leave_userinfo WHERE is_leader=1 and departmentid='".$this->userinfo['departmentid']."' and status=1")->asArray()->one();
    $temp=[$this->_adminInfo['wxuserid']];
    if ($res && $res['userids']){
      return array_merge(explode(',',$res['userids']),$temp);
    }
    return $temp;
  }
  public function actionHasauth(){
   

    $role = ['会计'];

    // 领导可以查看下属部门所有的合同全部内容
    $deptid = $this->_request['deptid'];
    if ($this->userinfo['is_leader']){
      $depts = WeixinOaDepartment::findBySql("SELECT count(*) as cnt from weixin_oa_department where id=$deptid or FIND_IN_SET($deptid,parentids)")->asArray()->one();
      if ($depts && $depts['cnt']>0) return true;
    }
    // 
    $sql = "SELECT count(*) as cnt from weixin_oa_flowrole where dept=$deptid and  userid='".$this->_adminInfo['wxuserid']."' and ( FIND_IN_SET(".$this->agentid.",agent)  and role in (select id from weixin_oa_role where rolename in ('".implode("','",$role)."')) )";
    $r = WeixinOaFlowrole::findBySql($sql)->asArray()->one(); 
    if ($r && $r['cnt']>0) return true;
    return false;
  }
  private function getDepts(){
    
    
    
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


    $sql = "SELECT userid,dept from weixin_oa_flowrole where  userid='".$this->_adminInfo['wxuserid']."' and  FIND_IN_SET(".$this->agentid.",agent)  and role in (select id from weixin_oa_role where FIND_IN_SET('$power',powername))";
    $result = WeixinOaFlowrole::findBySql($sql)->asArray()->all();

    if ($result) {
      
      foreach ($result as $e) {

        $arr = array_merge($arr,explode(',',$e['dept']));
      }
      $arr = array_unique($arr);

    }
   
    return $arr;
  }
  // ============================================ 合同 =================================================
  public function actionGetbyids(){
    $where = [
      'and',
      ['>', 'c.id', 0],['!=','c.state',4]
    ];
    if ($this->_request['type']){
      $where[]=['=','type',$this->_request['type']];
    }
    $keyword = $this->_request['ids'];
    $limit = $this->_request['limit'];
    if ($this->_request['id']){
      $where[] = ['=', 'id', $this->_request['id']];
    }
    if ($keyword){
      $keyword = explode(',', $keyword);
      $where[] = ['in', 'id', $keyword];
    }
    
    if (!isset($limit)) {
      $limit = 30;
    }

    $model = FzrbsContract::find()->alias('c')->where($where)->orderBy('id desc')->limit($limit)->asArray()->all();
    return $model;
  }
  public function actionGetbykeyword(){
    $where = [
      'and',
      ['>', 'c.id', 0],['!=','c.state',4]
    ];
    if ($this->_request['type']){
      $where[]=['=','type',$this->_request['type']];
    }
    $keyword = $this->_request['keyword'];
    $limit = $this->_request['limit'];
    if ($this->_request['id']){
      $where[] = ['=', 'id', $this->_request['id']];
    }
    if ($keyword){
      // 如果是数字拼接
      if (strpos($keyword, ',')!==false){
        $keyword = explode(',', $keyword);
        $where[] = ['in', 'id', $keyword];
      }else{
        $where[] = ['or',['like', 'title', $keyword],['=', 'serial', $keyword],['=', 'deptserial', $keyword]];
      }
    }
    
    if (!isset($limit)) {
      $limit = 30;
    }

    $model = FzrbsContract::find()->alias('c')->where($where)->orderBy('id desc')->limit($limit)->asArray()->all();
    return $model;
  }
  public function actionGetcontract(){
    $id = $this->_request['id'];
    if ($id && preg_match('/"id"\s*:\s*(\d+)/', $id, $matches)) $id = $matches[1];
    if ($id) {
      $c=FzrbsContract::find()->alias('c')
      ->select("c.*,dept.name as signdept,invoice.invoiceamount as invoiceamount,c.debturgeid as debturgeid")
      ->leftJoin(['dept'=>WeixinOaDepartment::tableName()],'dept.id=c.signdeptid')
      ->leftJoin(['invoice'=>"(select contractid,sum(realamount) as invoiceamount from ".FzrbsInvoicingInvoice::tableName()." group by contractid)"],'invoice.contractid=c.id')
      ->where(['c.id'=>$id])->asArray()->one();


      $pcs = FzrbsContractPaycondition::find()->where(['and',['=','contractid',$id]])->orderBy('date desc')->asArray()->all();
      $collections = FzrbsContractPaycollection::find()->alias('c')->select('c.*,IFNULL(u.name,`c`.`sysnote`) as name')->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=c.creator')->where(['and',['=','contractid',$id]])->orderBy('id desc')->asArray()->all();
      $c['payconditions'] = $pcs;

      
      for($i=0;$i<sizeof($c['payconditions']);$i++){
        $total = FzrbsContractPaycollection::find()->select('sum(amount) as amount')->where(['and',['=','contractid',$c['payconditions'][$i]['contractid']],['<=','date',$c['payconditions'][$i]['date']]])->one();
        $c['payconditions'][$i]['current'] = $total['amount']&&$c['amount']?intval($total['amount']/$c['amount']*100):0;
       
      }
      if ($c['supplementary']){
        $c['supplementary'] = json_decode($c['supplementary'],true);
      }
    

      $c['paycollections'] = $collections;

      // 如果是支出
      if ($c['type']==$this->EXPEND_DICID){
        
        $l = FzrbsContractLedger::find()->where(['contractid'=>$id])->one();
        if($l){
          $c['ledgerid']=$l->id;
        }
      }
      return array('data'=>$c);
    }
    return array('errorMessage'=>'id 为空');
  }

  // 合同存档
  public function actionLock(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    $old = FzrbsContract::findOne($id);
    // if ($old['paycollection']<$old['amount']){
    //   return array('errorMessage'=>'有欠款,禁止操作');
    // }
    $state = $this->_request['state'];
    if($state==0){//解档
      $hasauth = $this->haspower('解档',$this->_request['agentid'],$old['signdeptid'],'');
      if (!$hasauth) {
        return array('errorMessage'=>'需要【解档】权限');
      }
    }else{//存档，本人和小部门领导可以操作
      $userids = $this->meAndLeader();
      if (!in_array($old['creator'],$userids)){
        return array('errorMessage'=>'只有本人和部门领导可以存档');
      }
    }

    
    FzrbsContract::updateAll(['state'=>$state],['id'=>$id]);
    return true;
  }
  // 作废
  public function actionNullify(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    if(!$this->_request['nullifyurls']) return array('errorMessage'=>'nullifyurls 不能为空');
    $old = FzrbsContract::findOne($id);


    $userids = $this->meAndLeader();
    if (!in_array($old['creator'],$userids)){
      return array('errorMessage'=>'只有本人和部门领导可以作废');
    }

    if($old['paycollection']>0){
      return array('errorMessage'=>'该合同已回款，不能作废');
    }
    $old['state'] = 4;
    $old['nullifyurls']=$this->_request['nullifyurls'];
    $old->save();
    return true;
  }

  private function actionCheckContract($c){
    $errorMessage = '';
    if ($c['amount']==0){
      if ($c['balancetype']){
        in_array(108,explode(',',$c['balancetype']))?$errorMessage='':$errorMessage = '合同金额不能为0'; // 108 为框架协议
      }
      
    }
    return $errorMessage;
  }
  public function actionSavecontract(){
  

    $resp = array('errorMessage'=>'');
    $obj = $this->_request;

    $payconditions = $this->_request['payconditions'];
    unset($obj['payconditions']);

    $createMirror = $obj['createMirror'];
    
    unset($obj['createMirror']);
    

    $m = FzrbsContract::find()->where(['or',['=','serial',$obj['serial']],['=','title',$obj['title']]])->one();
    if ($m && $m['id']!=$obj['id']){
      if ($m['title']==$obj['title']){
        return array('errorMessage'=>'合同名称已经存在,请修改合同名称后再提交');
      }
      if ($m['serial']==$obj['serial']){
        return array('errorMessage'=>'合同编号已经存在,请修改合同编号后再提交');
      }
    }
    if ($obj['signdeptid']=='1'||$obj['signdeptid']=='7'){
      return array('errorMessage'=>'【签订部门】不能为【福州日报社】或【下属公司】');
    }
 
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {

      $logdata = null;
      $action='save';

      // 求合同总金额
      $obj['amount'] =$obj['mainamount'];
      if ($obj['supplementary']){
        $supplementary = [];
        if (is_array($obj['supplementary'])){
          $supplementary = $obj['supplementary'];
          $obj['supplementary']=json_encode($obj['supplementary']);
        } else {
          $supplementary = json_decode($obj['supplementary'],true);
        }
        
        if ($supplementary && sizeof($supplementary)>0){
          foreach ($supplementary as $item) {
            $obj['amount']=$obj['amount']+$item['amount'];
          }
        }
      }

      if (is_array($obj['supplementary'])) {
        $obj['supplementary'] = json_encode($obj['supplementary']);
      }
      
      if (isset($obj['companyinfo'])&&is_array($obj['companyinfo'])){
        $obj['companyinfo'] = json_encode($obj['companyinfo']);
      }
      if ($obj['fileurls']&&strlen($obj['fileurls'])){
        $obj['fileurls']=implode(',',array_unique(explode(',',$obj['fileurls'])));
      }


      // 校验
      $errorMessage = $this->actionCheckContract($obj);
      if ($errorMessage){
        return array('errorMessage'=>$errorMessage);
      }


      if ($obj['id']){

        $action='update';
        // 只允许本人修改
        $old = FzrbsContract::findOne($obj['id']);
        // 判断是否是收款合同
        $isIncome = $obj['type']==$this->INCOME_DICID?true:false;
        if ($isIncome){
          // 如果是收款合同，付款方单位性质不能为空
          if (!$obj['partatype']&&!$old['partatype']){
            return array('errorMessage'=>'是收款合同，付款方单位性质不能为空');
          }
        }

        if ($old['state']==1){
          return array('errorMessage'=>'合同已存档，无法操作');
        }

            // 判断是否有管理权限
        if ($old['creator']!=$this->_adminInfo['wxuserid']){ // 非本人
          $hasauth = $this->haspower('编辑',$this->_request['agentid'],$old['signdeptid'],'');
          if (!$hasauth) {
            return array('errorMessage'=>'需要【编辑】权限');
          }
        }

        // 判断合同金额是否改变
        if ($old['amount']!=$obj['amount']){
          // 判断该合同是否有关联的非报项目，如何有则需要更新非报项目关联的合同金额
          $projects = FzrbsBudgetProject::find()->where(new Expression("FIND_IN_SET(".$obj['id'].",contractids)"))->all();
          if ($projects && sizeof($projects)>0){
            foreach ($projects as $project) {
              $project->contractamount = $project->contractamount-$old['amount']+$obj['amount'];
              $project->save();
            }
          }
 
        }

        $logdata = $obj;
        FzrbsContract::updateAll($obj,['id'=>$obj['id']]);
        if($createMirror){
          $old = FzrbsContract::findOne($obj['id']);
          $this->createMirror($old);
        }

        if ($old['serial']!=$obj['serial']){
          Yii::$app->paymentdb->createCommand()
          ->update('advorder', ['contractserial' => $obj['serial']], ['contractid' => $obj['id']])
          ->execute();
        }


      } else {
        if (!$obj['fileurls']||!strlen($obj['fileurls'])){
          return array('errorMessage'=>'合同附件不能为空');
        }
        $obj['departmentid'] = $this->userinfo['departmentid'];
        $obj['department'] = $this->userinfo['departmentname'];
        $obj['creator'] = $this->_adminInfo['wxuserid'];
        $obj['creatorname'] = $this->userinfo['name'];
        if (!$obj['creator']) {
          return array('errorMessage'=>'合同经办人为空，无法保存,可能原因是未绑定企业微信。点击右上角头像，选择个人设置，绑定企业微信');
        }
        $isIncome = $obj['type']==$this->INCOME_DICID?true:false;
        if ($isIncome){
          // 如果是收款合同，付款方单位性质不能为空
          if (!$obj['partatype']){
            return array('errorMessage'=>'是收款合同，付款方单位性质不能为空');
          }
        }
        $c = new FzrbsContract($obj);
        $c->save();

        $obj['id']=$c->id;
        $logdata = $obj;
        if($createMirror){
          
          $this->createMirror($obj);
        }
      }
      $this->savelog($obj['id'],$action,$logdata);
    } catch (\Throwable $th) {
  
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
   
    if ($payconditions && sizeof($payconditions)){
      for ($i=0;$i<sizeof($payconditions);$i++){
        $payconditions[$i]['contractid'] = $obj['id'];
        $payconditions[$i]['start'] = $obj['signdate'];
      }
     
    }else{
      // 如果是收款合同，履约条件不能为空
      if ($isIncome){
        return array('errorMessage'=>'是收款合同，履约条件不能为空');
      }
    }

    $this->savepayconditions($payconditions);
    $transaction->commit();
    $resp['data'] =$obj;
    return $resp;
  }
  private function createMirror($contract){
    unset($contract['id']);
    // $parta=$contract['parta'];
    // $partaname = $contract['partaname'];
    // $partb=$contract['partb'];
    // $partbname = $contract['partbname'];
    // $contract['parta']=$partb;
    // $contract['partb']=$parta;
    // $contract['partaname']=$partbname;
    // $contract['partbname']=$partaname;
    $contract['type']=$contract['type']==$this->EXPEND_DICID?$this->INCOME_DICID:$this->EXPEND_DICID;
    // 判断title是否包含[镜像]
    if (strpos($contract['title'],'[镜像]')===false){
      $contract['title']='[镜像]'.$contract['title'];
    }
    try {
      // if (isset($contract['companyinfo'])){
      //   if (is_array($contract['companyinfo'])){
      //     $companyinfo = $contract['companyinfo'];
      //   }else{
      //     $companyinfo= json_decode($contract['companyinfo'],true);
      //   }

      //   $companyinfo = array_reverse($companyinfo);
      // }
      // $contract['companyinfo'] = json_encode($companyinfo);
      
      // 判断是否已经存在镜像
      $temp=FzrbsContract::find()->where(['title'=>$contract['title'],'type'=>$contract['type']])->one();
      if ($temp){
        return;
      }
      $obj = new FzrbsContract($contract);
      $obj->save();
    } catch (\Throwable $th) {
      throw $th;
    }
  }
  private function savepayconditions($payconditions){

    if ($payconditions && sizeof($payconditions)){
      // 检查会计角色权限
      // $sql = "SELECT count(*) as cnt from weixin_oa_flowrole where userid='".$this->_adminInfo['wxuserid']."' and role in (select id from weixin_oa_role where rolename='会计')";
      // $r = WeixinOaFlowrole::findBySql($sql)->asArray()->one();
      // if (!$r || $r['cnt'] <= 0) {
      //   return array('errorMessage' => '只有会计角色才能修改履约条件');
      // }
      foreach($payconditions as $p){
        unset($p['current']);
        unset($p['amount']);
        $obj = new FzrbsContractPaycondition($p);
        if ($obj['id']){
          FzrbsContractPaycondition::updateAll($obj,['id'=>$obj['id']]);
        } else {
          $obj['creator'] = $this->_adminInfo['wxuserid'];
          $obj->save();
        }
      }
    }

  }

  public function actionDelledger(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    // 只允许本人修改
    $old = FzrbsContractLedger::findOne($id);

    $userids = $this->meAndLeader();
    if (!in_array($old['ledger'],$userids)){
      return array('errorMessage'=>'只有本人和部门领导可以删除');
    }


    try {

      $old->delete();
      // 删除合同时,同时删除附件
    } catch (\Throwable $th) {
      
      return array('errorMessage'=>$th->getMessage());
    }
   
    
    return array('data'=>'删除成功');
  }
  public function actionDelcontract(){

    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    // 只允许本人修改
    $old = FzrbsContract::findOne($id);

    $userids = $this->meAndLeader();
    if (!in_array($old['creator'],$userids)){
      return array('errorMessage'=>'只有本人和部门领导可以删除');
    }

    if ($old['state']==1){
      return array('errorMessage'=>'合同已存档，无法操作');
    }


    if ($old['paycollection']>0){
      return array('errorMessage'=>'先删除回款再删除合同!');
    }
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      $item = FzrbsContract::findOne($id);
      $item->delete();
      // 删除合同时,同时删除附件
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    
    return array('data'=>'删除成功');
  }
  public function actionNotice(){
    $where = [
      'and',
        ['>', 'c.id', 0],
    ];

    $where[] = ['in', 'c.creator',$this->_adminInfo['wxuserid']];
    $deadlinenum = FzrbsContract::find()->alias('c')->select('*')->where($where)->andWhere(new Expression('c.id in ('.$this->getdeadlinesql().')'))->all();
    $overduenum = FzrbsContract::find()->alias('c')->select('id')->where($where)->andWhere(new Expression('c.id in ('.$this->getoverduesql().')'))->all();
    $title = "合同回款提醒";
    $date = date('Y-m-d');
		$notice = "您好，截至".$date."，与您相关的合同有%d笔逾期，有%d笔合同临期";
    $description = sprintf($notice,sizeof($overduenum),sizeof($deadlinenum));


  }
  public function actionGetdebt(){
    $where = [
        'and',
        ['>', 'id', 0],['=','type',$this->INCOME_DICID]
    ];
    if ($this->_request['companyid']) {
      $where[] = ['OR', new Expression("FIND_IN_SET(".$this->_request['companyid'].",parta)"),new Expression("FIND_IN_SET(".$this->_request['companyid'].",partb)")];
    }
    $model = FzrbsContract::find()->select('sum(amount-paycollection) as amount')->where($where)->asArray()->one();
    return array('data'=>$model?$model['amount']:0);
  }

  

  private function getWhere(){
    $where = [
        'and',
        ['>', 'c.id', 0],
    ];

    $dept = $this->getDepts();
   
    if (sizeof($dept)>0) {

      
      if (!$this->_request['signdeptid'] && !$this->_request['departmentid']){
        $where[] =  ['or',['in' , 'c.signdeptid' , $dept],['in' , 'c.departmentid' , $dept],['=' , 'c.creator' , $this->_adminInfo['wxuserid']]];
      }else{
        $where[] = ['or',['in' , 'c.signdeptid' , $dept],['in' , 'c.departmentid' , $dept]];
      }
    } else {
      // 没有指定部门时，需要查询我创建的
      $where[] =  ['=' , 'c.creator' , $this->_adminInfo['wxuserid']];
    }
  
    if ($this->_request['signdeptid']){
      $where[] =  ['in' , 'c.signdeptid' , explode(',',$this->_request['signdeptid'])];
    }
    if ($this->_request['departmentid']){
      $where[] =  ['in' , 'c.departmentid' , explode(',',$this->_request['departmentid'])];
    }

    
    

    if ($this->_request['partatype']){
      $where[] =  ['in' , 'c.partatype' , explode(',',$this->_request['partatype'])];
    }
    
    
    if ($this->_request['contractids']){
      $where[] =  ['in' , 'c.id' , explode(',',$this->_request['contractids'])];
    }
    
    
    if ($this->_request['title']) {
      $where[] = ['OR',['LIKE', 'c.serial', $this->_request['title']],['LIKE', 'c.title', $this->_request['title']]];
    }
    if ($this->_request['serial']) {
      $where[] = ['OR',['LIKE', 'c.serial', $this->_request['serial']],['LIKE', 'c.deptserial', $this->_request['serial']]];
    }
    if ($this->_request['type']) {
      $where[] = ['=', 'c.type', $this->_request['type']];
    }
    if (isset($this->_request['state']) && $this->_request['state']!=-1) {
      $where[] = ['=', 'c.state', $this->_request['state']];
    }
    if ($this->_request['creator']) {
      $where[] = ['in', 'c.creator', explode(',',$this->_request['creator'])];
    }
    if ($this->_request['balancetype']) {
      $where[] = ['in', 'c.balancetype', explode(',',$this->_request['balancetype'])];
    }
    if ($this->_request['balancetypename']) {
      $t = explode(',',$this->_request['balancetypename']);
      if (sizeof($t)>0){
        $t = array_map(function($v){
          return "FIND_IN_SET('$v',balancetypename)";
        },$t);
        $tt = implode(' OR ',$t);
        $where[] = new Expression("c.id in (SELECT id FROM fzrbs_contract WHERE $tt)");
      }
    }
    if ($this->_request['parta']) {
      $where[] = new Expression("FIND_IN_SET(".$this->_request['parta'].",c.parta)");

    }
    if ($this->_request['partb']) {
      $where[] = new Expression("FIND_IN_SET(".$this->_request['partb'].",c.partb)");
    }
    if ($this->_request['partanameLike']) {
      $where[] = ['LIKE', 'c.partaname', $this->_request['partanameLike']];
    }

    
    if ($this->_request['starttime']) {
      $where[] = ['>=', 'c.starttime', $this->_request['starttime']];
    }
    if ($this->_request['endtime']) {
      $where[] = ['<=', 'c.endtime', $this->_request['endtime']];
    }
    if ($this->_request['amountfloor']) {
      $where[] = ['>=', 'c.amount', $this->_request['amountfloor']];
    }
    if ($this->_request['amountceil']) {
      $where[] = ['<=', 'c.amount', $this->_request['amountceil']];
    }
    if ($this->_request['signdatestart']) {
      $where[] = ['>=', 'c.signdate', $this->_request['signdatestart']];
    }
    if ($this->_request['signdateend']) {
      $where[] = ['<=', 'c.signdate', $this->_request['signdateend']];
    }
    if ($this->_request['inserttimestart']) {
      $where[] = ['>=', 'c.inserttime', $this->_request['inserttimestart']];
    }
    if ($this->_request['inserttimeend']) {
      $where[] = ['<=', 'c.inserttime', $this->_request['inserttimeend']];
    }
    
    
    return $where;
  }


  public function actionSaveledger(){
  

    $resp = array('errorMessage'=>'');
    $obj = $this->_request;


    $m = FzrbsContractLedger::find()->where(['or',['=','ledgerserial',$obj['ledgerserial']]])->one();
    if ($m && $m['id']!=$obj['id']){
      return array('errorMessage'=>'采购编号已经存在');
    }
 
 
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {

 
      if ($obj['fileurls']&&strlen($obj['fileurls'])){
        $obj['fileurls']=implode(',',array_unique(explode(',',$obj['fileurls'])));
      }
 

      if ($obj['id']){
        unset($obj['creator']);
        // 只允许本人修改
        $old = FzrbsContractLedger::findOne($obj['id']);

            // 判断是否有管理权限
        if ($old['ledger']!=$this->_adminInfo['wxuserid']){ // 非本人
          $hasauth = $this->haspower('台账管理',$this->_request['agentid'],$old['departmentid'],'');
          if (!$hasauth) {
            return array('errorMessage'=>'需要【台账管理】权限');
          }
        }


        FzrbsContractLedger::updateAll($obj,['id'=>$obj['id']]);

      } else {

        // 合同id不能为空
        if (empty($obj['contractid'])) {
          return array('errorMessage'=>'合同id不能为空');
        }
        $t = FzrbsContractLedger::find()->where(['=','contractid',$obj['contractid']])->one();
        if ($t) {
          return array('errorMessage'=>'当前合同已经创建过台账了！！');
        }

        $obj['departmentid'] = $this->userinfo['departmentid'];
        $obj['department'] = $this->userinfo['departmentname'];
        $obj['ledger'] = $this->_adminInfo['wxuserid'];
        $obj['ledgername'] = $this->userinfo['name'];
        if (!$obj['ledger']) {
          return array('errorMessage'=>'未绑定企业微信。点击右上角头像，选择个人设置，绑定企业微信');
        }
        $c = new FzrbsContractLedger($obj);
        $c->save();

        $obj['id']=$c->id;
  

      }
   
    } catch (\Throwable $th) {
  
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
   
    $transaction->commit();
    $resp['data'] =$obj;
    return $resp;
  }

  public function actionGetledger(){
    $id = $this->_request['id'];
    if (!$id) {
      return array('errorMessage'=>'缺少id');
    }
    $where = ['l.id'=>$id];
    $model = FzrbsContractLedger::find()->alias('l')->select('l.*,c.amount,c.content,c.partbname,c.creatorname,c.serial,u.rate as conditionratio,u.payratio,u.overdue')
    ->leftJoin(FzrbsContract::tableName().' c', 'c.id=l.contractid')
    ->leftJoin("(SELECT c.id,p.rate,c.paycollection, IFNULL(c.paycollection/c.amount,0) as payratio,DATEDIFF(NOW(),date) as overdue  from fzrbs_contract_paycondition p left join fzrbs_contract c on p.contractid=c.id and c.state=0  order by p.date desc ) u", 'u.id=l.contractid')
    ->where($where)->with(['typename', 'method', 'resultid'])->asArray()->one();

    $model['typename'] = $model['typename']['label'];
    $model['methodname'] = $model['method']['label'];
    $model['result'] = $model['resultid']['label'];
    $model['method'] = $model['method']['value'];
    $model['resultid'] = $model['resultid']['value'];
    $payratio = $model['payratio']?($model['payratio']*100):0;
    $conditionratio = $model['conditionratio']?intval($model['conditionratio']):0;
    if ($model['overdue']>0&&$payratio<$conditionratio){
      $model['paydefault'] = 1;
    }else{
      $model['paydefault'] = 0;
    }
    return array('data'=>$model);
  }
  public function actionLedgerlist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;
    $offset = $limit * ($page - 1);
    $order = 'l.id desc';
    if ($this->_request['orderby']) {
      $order = $this->_request['orderby'];
    }
    $where=['and',['>', 'l.id', 0]];

    $dept = $this->getDepts();
    if (sizeof($dept)>0) {
      $where[] = ['or',['in' , 'l.departmentid' , $dept],['=' , 'l.ledger' , $this->_adminInfo['wxuserid']]];
    } else {
      $where[] =  ['=' , 'l.ledger' , $this->_adminInfo['wxuserid']];
    }
    if ($this->_request['departmentid']){
      $where[] =  ['in' , 'l.departmentid' , explode(',',$this->_request['departmentid'])];
    }

    if ($this->_request['title']) {
      $where[] = ['LIKE', 'l.title', $this->_request['title']];
    }
    if ($this->_request['ledgerserial']) {
      $where[] = ['LIKE', 'l.ledgerserial', $this->_request['ledgerserial']];
    }
    if ($this->_request['method']) {
      $where[] = ['in', 'l.method', explode(',', $this->_request['method'])];
    }
    if ($this->_request['partb']) {
      $where[] = ['=', 'partb', explode(',', $this->_request['partb'])];
    }
    if ($this->_request['agentid']) {
      $where[] = ['=', 'l.agentid', $this->_request['agentid']];
    }
    if ($this->_request['resultid']) {
      $where[] = ['in', 'l.resultid', explode(',', $this->_request['resultid'])];
    }
    if (isset($this->_request['file'])) {
      $where[] = ['=', 'l.file', intval($this->_request['file'])];
    }

    if ($this->_request['notes']) {
      $where[] = ['LIKE', 'l.notes', $this->_request['notes']];
    }
    if ($this->_request['inserttimestart']) {
      $where[] = ['>=', 'l.inserttime', $this->_request['inserttimestart']];
    }
    if ($this->_request['inserttimeend']) {
      $where[] = ['<=', 'l.inserttime', $this->_request['inserttimeend']];
    }



    $cwhere='id>0';
    if ($this->_request['serial']) {
      $cwhere.=" and serial like '%".$this->_request['serial']."%'";
    }
    if ($this->_request['creator']) {
      $cwhere.=" and creator='".$this->_request['creator']."'";
    }
    if ($cwhere!='id>0'){
      $where[] = new Expression("l.contractid in (select id from ".FzrbsContract::tableName()." where $cwhere)");
    }
  
    $model = FzrbsContractLedger::find()->alias('l')->select('l.*,c.amount,c.content,c.partbname,c.creatorname,c.serial,u.rate as conditionratio,u.payratio,u.overdue')
    ->leftJoin(FzrbsContract::tableName().' c', 'c.id=l.contractid')
    ->leftJoin("(SELECT c.id,p.rate,c.paycollection, IFNULL(c.paycollection/c.amount,0) as payratio,DATEDIFF(NOW(),date) as overdue  from fzrbs_contract_paycondition p left join fzrbs_contract c on p.contractid=c.id and c.state=0  order by p.date desc ) u", 'u.id=l.contractid')
    ->where($where)->with(['typename', 'method', 'resultid']);

    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($order)->asArray()->all();

    foreach ($res as $key => $value) {
      $res[$key]['typename'] = $value['typename']['label'];
      $res[$key]['methodname'] = $value['method']['label'];
      $res[$key]['result'] = $value['resultid']['label'];
      $res[$key]['method'] = $value['method']['value'];
      $res[$key]['resultid'] = $value['resultid']['value'];
      $payratio = $res[$key]['payratio']?($res[$key]['payratio']*100):0;
      $conditionratio = $res[$key]['conditionratio']?intval($res[$key]['conditionratio']):0;
      if ($res[$key]['overdue']>0&&$payratio<$conditionratio){
        $res[$key]['paydefault'] = 1;
      }else{
        $res[$key]['paydefault'] = 0;
      }


    }



    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;


    return $this->_result;

  }
  public function actionDownloadpurchase(){
  
    $order = 'id desc';
    if ($this->_request['orderby']) {
      $order = $this->_request['orderby'];
    }
    $where=['and',['>', 'l.id', 0]];

    $dept = $this->getDepts();
    if (sizeof($dept)>0) {
      $where[] = ['or',['in' , 'l.departmentid' , $dept],['=' , 'l.ledger' , $this->_adminInfo['wxuserid']]];
    } else {
      $where[] =  ['=' , 'l.ledger' , $this->_adminInfo['wxuserid']];
    }
    if ($this->_request['departmentid']){
      $where[] =  ['in' , 'l.departmentid' , explode(',',$this->_request['departmentid'])];
    }

    if ($this->_request['title']) {
      $where[] = ['LIKE', 'l.title', $this->_request['title']];
    }
    if ($this->_request['ledgerserial']) {
      $where[] = ['LIKE', 'l.ledgerserial', $this->_request['ledgerserial']];
    }
    if ($this->_request['method']) {
      $where[] = ['in', 'l.method', explode(',', $this->_request['method'])];
    }
    if ($this->_request['partb']) {
      $where[] = ['=', 'partb', explode(',', $this->_request['partb'])];
    }
    if ($this->_request['agentid']) {
      $where[] = ['=', 'l.agentid', $this->_request['agentid']];
    }
    if ($this->_request['resultid']) {
      $where[] = ['in', 'l.resultid', explode(',', $this->_request['resultid'])];
    }
    if (isset($this->_request['file'])) {
      $where[] = ['=', 'l.file', intval($this->_request['file'])];
    }

    if ($this->_request['notes']) {
      $where[] = ['LIKE', 'l.notes', $this->_request['notes']];
    }
    if ($this->_request['inserttimestart']) {
      $where[] = ['>=', 'l.inserttime', $this->_request['inserttimestart']];
    }
    if ($this->_request['inserttimeend']) {
      $where[] = ['<=', 'l.inserttime', $this->_request['inserttimeend']];
    }



    $cwhere='id>0';
    if ($this->_request['serial']) {
      $cwhere.=" and serial like '%".$this->_request['serial']."%'";
    }
    if ($this->_request['creator']) {
      $cwhere.=" and creator='".$this->_request['creator']."'";
    }
    if ($cwhere!='id>0'){
      $where[] = new Expression("l.contractid in (select id from ".FzrbsContract::tableName()." where $cwhere)");
    }
  
    $model = FzrbsContractLedger::find()->alias('l')->select('l.*,c.amount,c.content,c.partbname,c.creatorname,c.serial,u.rate as conditionratio,u.payratio,u.overdue')
    ->leftJoin(FzrbsContract::tableName().' c', 'c.id=l.contractid')
    ->leftJoin("(SELECT c.id,p.rate,c.paycollection, IFNULL(c.paycollection/c.amount,0) as payratio,DATEDIFF(NOW(),date) as overdue  from fzrbs_contract_paycondition p left join fzrbs_contract c on p.contractid=c.id and c.state=0  order by p.date desc ) u", 'u.id=l.contractid')
    ->where($where)->with(['typename', 'method', 'resultid']);

    $res = $model->orderBy($order)->asArray()->all();



    $header = array(
      '采购编号','采购类别（货物、服务、其他）','项目名称',
      '采购内容','招标代理机构','采购方式',
      '成交供应商','合同金额','是否依约付款',
      '验收结果','采购流程文件是否齐全','合同编号',
      '采购人','备注'
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
            $row['ledgerserial'],// 采购编号
            $row['typename']['label'],// 采购类别（货物、服务、其他）
            $row['title'],// 项目名称
            $row['content'],//采购内容
            $row['agent'],// 招标代理机构
            $row['method']['label'],// 采购方式
            $row['partbname'],// 成交供应商
            $row['amount'], // 合同金额
            $row['paydefault'],// 是否依约付款
            $row['resultid']['value'],// 验收结果
            $row['file']?'是':'否',// 采购流程文件是否齐全
            $row['serial'],// 合同编号
            $row['creatorname'],// 采购人
            $row['notes'],// 备注
            
        ];
    }, $res);
    // 在最前面插入
    array_unshift($data, $header);
    return array('data'=>$data,'header'=>$header);

  }
  public function actionGetcontractlist(){
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;
    $offset = $limit * ($page - 1);
    $order = 'id desc';
    if ($this->_request['orderby']) {
      $order = $this->_request['orderby'];
    }

    $where = $this->getWhere();

    $deadlinenum = FzrbsContract::find()->alias('c')->select('id')->where($where)->andWhere(new Expression('c.id in ('.$this->getdeadlinesql().')'))->all();
    $overduenum = FzrbsContract::find()->alias('c')->select('id')->where($where)->andWhere(new Expression('c.id in ('.$this->getoverduesql().')'))->all();

    $nopayconditions = FzrbsContract::find()->alias('c')->select('id')->where($where)->andWhere(new Expression("c.paycollection<c.amount and c.type=".$this->INCOME_DICID." and  c.id not in (select contractid from ".FzrbsContractPaycondition::tableName().")"))->all();
    // 查询临期
    if ($this->_request['showdeadline']) {
      $where[] = new Expression('c.id in ('.$this->getdeadlinesql().')');
    }
    // 查询逾期
    if ($this->_request['showoverdue']) {
      $where[] = new Expression('c.id in ('.$this->getoverduesql().')');
    }
    // 查询未设置履约条件
    if ($this->_request['shownopayconditions']) {
      $where[] = new Expression("c.paycollection<c.amount and c.type=".$this->INCOME_DICID." and  c.id not in (select contractid from ".FzrbsContractPaycondition::tableName().")");
    }
    

   
    $model = FzrbsContract::find()->alias('c')->select('c.*,invoice.invoiceamount as invoiceamount,d.name as signdept,u.avatar as avatar,u.name,d2.label as typename')
    ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=c.creator')
    ->leftJoin(['d'=>WeixinOaDepartment::tableName()],'d.id=c.signdeptid')
    ->leftJoin(['invoice'=>"(select contractid,sum(realamount) as invoiceamount from ".FzrbsInvoicingInvoice::tableName()." group by contractid)"],'invoice.contractid=c.id')
    ->leftJoin(['d2'=>FzrbsBudgetDict::tableName()],'d2.id=c.type')->where($where);
    



    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($order)->asArray()->all();

    // 临期id数组
    $deadlinids = array_map(function($item){return $item['id'];},$deadlinenum);
    // 逾期id数组
    $overdueids = array_map(function($item){return $item['id'];},$overduenum);
    for($i=0;$i<sizeof($res);$i++){
      $temp = [];
      if (in_array($res[$i]['id'],$deadlinids)&&$res[$i]['amount']){
        
        $temp[]='临期';
      }
      if (in_array($res[$i]['id'],$overdueids)&&$res[$i]['amount']){
        $temp[]='逾期';

      }
      $res[$i]['paystate']=join(',',$temp);
    }

    // 统计数据
    $stat = FzrbsContract::find()->alias('c')->select("sum(amount) as amount,sum(paycollection) as paycollection,(sum(amount)-sum(paycollection)) as left")->where($where)->andWhere(['=','type',$this->INCOME_DICID])->asArray()->one();


    $stat['deadlinenum'] = count($deadlinenum);
    $stat['overduenum'] = count($overduenum);
    $stat['nopayconditions'] = count($nopayconditions);
    $stat['total']=$total;
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    $this->_result['stat'] = $stat;
    $this->_result['viewfileMethod'] = "http://fzrb.fznews.com.cn/index.php?r=qiyehao/attachment/file&savepath=/www/web/fzrbs_oa/web/&attachment=";

    return $this->_result;
  }
  

  // ****************************** 回款条件 ***********************************

  public function actionGetpayconditions(){
    $contractid = $this->_request['contractid'];
    if (!$contractid){
      return array('errorMessage'=>'contractid 为空');
    }
    $pcs = FzrbsContractPaycondition::find()->where(['contractid'=>$contractid])->orderBy('date desc')->all();
    return array('data'=>$pcs);

  }
  public function actionDelpaycondition(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    // 只允许本人修改
    $old = FzrbsContractPaycondition::findOne($id);
    $contract = FzrbsContract::findOne($old['contractid']);
    // 判断当前日期相对inserttime是否超过7天
    $inserttime = strtotime($old['inserttime']);
    if ($inserttime+7*24*60*60<time()){
      // 只有会计才能删除
      $users = $this->getRole('会计',$contract['departmentid']);
      $users = array_map(function($item){return $item['userid'];},$users);
      
      if (!in_array($this->_adminInfo['wxuserid'],$users)){
        return array('errorMessage'=>'只有会计['.implode(',',$users).']才能删除');
      }
    }
    // if ($contract['creator'] != $this->_adminInfo['wxuserid']){
    //   return array('errorMessage'=>'只有创建人才能删除');
    // }
    $old->delete();
    return array('ret'=>1);
  }

  protected function upContractPaycollection($id){

  
    $paycollection = $this->getTotalpaycollection($id);
    
    FzrbsContract::updateAll(['paycollection'=>$paycollection],['id'=>$id]);

    return $paycollection;
  }
  private function getTotalpaycollection($contractid){
    $total = FzrbsContractPaycollection::find()->select('sum(amount) as amount')->where(['and',['=','contractid',$contractid]])->orderBy('contractid desc')->asArray()->one();
    if ($total && $total['amount']) {
      return $total['amount'];
    }
    return 0;
  }
  public function actionDelpaycollection(){
    

    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    // 只允许本人修改
    $old = FzrbsContractPaycollection::findOne($id);
    // if ($old['state']==1){ // 已存档
    //   return array('errorMessage'=>'合同已存档,禁止操作');
    // }
    if ($old['creator'] != $this->_adminInfo['wxuserid']){
      $hasauth = $this->haspower('回款',$this->_request['agentid'],$this->_request['signdeptid'],'');
      if (!$hasauth) {
        return array('errorMessage'=>'需要【回款】权限');
      }
    }
    if ($old['state']==3){
      return array('errorMessage'=>'财务已确认，不能删除');
    }
    if ($old['state']==0){
      return array('errorMessage'=>'状态为删除，不能删除');
    }
    $old['amount']=-$old['amount'];
    $old['state']=0;

    // 已收款合计
    

    unset($old->id);
    unset($old->inserttime);
    $transaction = Yii::$app->getDb()->beginTransaction();
    
    
    
    try {

      FzrbsContractPaycollection::updateAll(['valid'=>0],'id='.$id);
      $new = new FzrbsContractPaycollection($old);
      $new['inserttime'] = date('Y-m-d');
      $new['date'] = date('Y-m-d');
      $new->save();
     
   
      $paycollection = $this->upContractPaycollection($old['contractid']);

      
    
      

    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    return array('ret'=>1,'paycollection'=>$paycollection);
  }
  
  
  public function actionSavepaycollection(){
    $obj = $this->_request;
    
    // return array('errorMessage'=>'不支持回款，回款将全部由金蝶推送！');
    
    if (!$obj['contractid']){
      return array('errorMessage'=>'contractid 为空');
    }

    if ($obj['amount']==0) {
      return array('errorMessage'=>'amount 不能为0');
    }

    // 目前回款合计
    $total = FzrbsContractPaycollection::find()->select('sum(amount) as amount')->where(['and',['=','contractid',$obj['contractid']]])->groupBy('contractid')->orderBy('contractid desc')->asArray()->one();

    // 回款总额不能超过合同总额
    $c = FzrbsContract::findOne($obj['contractid']);
    // 判断是否已经存档

    // if ($c['state']==1){ // 已存档
    //   return array('errorMessage'=>'合同已存档，禁止操作');
    // }
 
    if (!$this->haspower('回款',$this->_request['agentid'],$c['signdeptid'],$c['creator'])) {
      return array('errorMessage'=>'需要【回款】权限');
    }
    $paycollection = floatval($obj['amount'])+floatval($total['amount']);
    $left = $c['amount']-$paycollection;
    if ($left<0&&$c['amount']!=0){
      return array('errorMessage'=>"回款总额[$paycollection]大于合同总额[".$c['amount']."]");
    }

    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      if ($obj['id']){
        FzrbsContractPaycollection::updateAll($obj,['id'=>$obj['id']]);
      } else {
        $obj['creator'] = $this->_adminInfo['wxuserid'];
        unset($obj['agentid']);
        $obj = new FzrbsContractPaycollection($obj);
        $obj->save();
      }
      
      FzrbsContract::updateAll(['paycollection'=>$paycollection],['id'=>$c['id']]);
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    return array('ret'=>1,'left'=>$left,'paycollection'=>$paycollection);

  }
  // ******************************* 数据统计 *********************************************

  /**
   * 临期和逾期通知
   */
  public function actionInformstat(){ 
    $hasauth = $this->haspower('临逾期通知',$this->_request['agentid'],'','');
      if (!$hasauth) {
        return array('errorMessage'=>'需要【临逾期通知】权限');
      }
    $where=[
      'and',
      ['>','id',0],
    ];
    
    // 查询我创建的逾期和临期数量
    $deadlinen = FzrbsContract::find()->alias('c')->select('id')->where($where)->andWhere(new Expression('c.id in ('.$this->getdeadlinesql().')'))->asArray()->all();
    $overdue = FzrbsContract::find()->alias('c')->select('id')->where($where)->andWhere(new Expression('c.id in ('.$this->getoverduesql().')'))->asArray()->all();
    // 生成通知消息卡片
    
    return $overdue;

  }
  /**
   * 临期合同查询
   * (当前日期-合同签订日期)/DATEDIFF(回款日期,合同签订日期)>=0.8 and (当前日期-合同签订日期)/DATEDIFF(回款日期,合同签订日期)<=1  and 回款金额/合同总额<1
   * 只查询执行中的正常合同
   */
  private function getdeadlinesql(){
    return "select id from (SELECT c.id,c.title,c.amount,p.rate,p.date as paydate,c.paycollection, IFNULL(c.paycollection/c.amount,0) as payratio,DATEDIFF(NOW(),start)/DATEDIFF(date,start) as ratio  from ".FzrbsContractPaycondition::tableName()." p left join ".FzrbsContract::tableName()." c on p.contractid=c.id and c.state=0 and c.amount>0  order by p.date desc  ) a where ratio>=0.8 and ratio<=1  and payratio*100<rate  group by id";
  }
  /**
   * 逾期合同查询
   * (当前日期-回款日期)>0  and 回款总金额/合同总金额<1
   * 只查询执行中的正常合同
   */
  private function getoverduesql(){
    return "select id from (SELECT c.id,c.title,c.amount,p.rate,p.date as paydate,c.paycollection, IFNULL(c.paycollection/c.amount,0) as payratio,DATEDIFF(NOW(),date) as overdue  from ".FzrbsContractPaycondition::tableName()." p left join ".FzrbsContract::tableName()." c on p.contractid=c.id and c.state=0 and c.amount>0  order by p.date desc  ) a where overdue>0  and payratio*100<rate  group by id";
  }

// ===================================== 数据导出 =======================================
public function actionGetheaders(){
  return [
    array("key"=>"title","title"=>"合同名称"),
    array("key"=>"amount","title"=>"合同总价"),
    array("key"=>"paycollection","title"=>"已回款"),
    array("key"=>"serial","title"=>"合同编号"),
    array("key"=>"deptserial","title"=>"部门编号"),
    array("key"=>"typename","title"=>"合同类型"),
    array("key"=>"balancetypename","title"=>"合同分类"),
    array("key"=>"partaname","title"=>"付款方"),
    array("key"=>"partbname","title"=>"收款方"),
    array("key"=>"signusername","title"=>"签订人"),
    array("key"=>"signdate","title"=>"签订日期"),
    array("key"=>"signdept","title"=>"签订部门"),
    array("key"=>"date","title"=>"合同期限"),
    array("key"=>"content","title"=>"合作内容"),
    array("key"=>"creator","title"=>"创建人"),
    array("key"=>"state","title"=>"状态")
  ];
}
// ===================================== 发票 ============================================
public function actionGetinvoicelist(){
  $total = 0;
  $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
  $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
  $offset = $limit * ($page - 1);
  $order ='id desc';
 
  $contractid = $this->_request['contractid'];
  $invoicingid = $this->_request['invoicingid'];
  $projectid = $this->_request['projectid'];
  
  $sql = 'id>0';
  if ($contractid && preg_match('/"id"\s*:\s*(\d+)/', $contractid, $matches)) $contractid = $matches[1];
  if ($projectid){
     $sql.= " and invoicingid in (select id from ".FzrbsInvoicing::tableName()." where projectids=$projectid)";
  }
  if ($invoicingid){
    $sql.= " and invoicingid=$invoicingid";
  }
  if ($contractid){
    $sql.= " and invoicingid in (select id from ".FzrbsInvoicing::tableName()." where FIND_IN_SET($contractid,contractid))";
  }
  if ($sql=='id>0') return array('errorMessage'=>'发票id、合同id、项目id不能都为空');
  
  
  $model = FzrbsInvoice::find()->alias('i')
  ->where(new Expression($sql));
  
  $total = $model->count();
  $res = $model->limit($limit)->offset($offset)->orderBy($order)->asArray()->all();

 
  $this->_result["current"] = $page;
  $this->_result["pageSize"] = $limit;
  $this->_result["total"] = $total;
  $this->_result['data'] = $res;
  

  return $this->_result;
}


public function actionSaveinvoice(){
  $obj = $this->_request;

    // 判断是否已经存在
    if (!$obj['contractid']) return array('errorMessage'=>'contractid不能为空');
    $items = $obj['IssuItemInformation'];
    if (!$items){
      return array('errorMessage'=>'IssuItemInformation 不能为空');
    }
    $c = null;
    if ($obj['contractid']){
      $c = FzrbsContract::findOne($obj['contractid']);
    }
    
    if (!$c) return array('errorMessage'=>'合同不存在');

    $hasauth = $this->haspower('发票管理',$this->agentid,$c['signdeptid'],$c['creator']);
    if (!$hasauth) {
      return array('errorMessage'=>'需要【发票管理】权限');
    }
    unset($obj['IssuItemInformation']);


    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
        $invoice = null;
        // 查询指定ID的发票是否已经存在
        $temp = FzrbsInvoice::find()->where(['and',['EIid'=>$obj['EIid']]])->one();

        if ($temp){//发票存在
          if ($temp['contractid']==$obj['contractid']){//已关联合同
            return array('errorMessage'=>'合同已经关联该发票');
          }else{//未关联合同
            $invoice = $temp;
            $invoice->contractid=$obj['contractid'];
          }

        }else{ // 发票不存在
          $invoice = new FzrbsInvoice($obj);
          $invoice->creator=$this->_adminInfo['wxuserid'];
        }
      
        $invoice->save();


 

        for ($i=0; $i < sizeof($items); $i++) { 
          $t=new FzrbsInvoiceItem($items[$i]);
          $t->invoiceid=$invoice['id'];
          $t->save();
        }
      
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    
    return array('data'=>$invoice);
}
// ===================================== 日志 ============================================

  private function savelog($relatedid,$action,$data){
    $d = new FzrbsContractLog();
    $d['action']=$action;
    $d['data']=$data;
    if (is_array($d['data'])) $d['data'] = json_encode($d['data'],false);
    $d['creator']=$this->_adminInfo['wxuserid'];
    $d['relatedtable']=FzrbsContract::tableName();
    $d['relatedid']=$relatedid;
    $d->save();
  }
  public function actionGetlog(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    $res = FzrbsContractLog::findOne($id);
    if ($res['data']&&is_string($res['data'])){
      $res['data'] = json_decode($res['data'],true);
    }
    
    return $res;
  }
  // supplementary
  public function actionGetlogs(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    $res = FzrbsContractLog::find()->alias('l')->select('l.id,l.inserttime,u.name,u.avatar')->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=l.creator')->where(['and',['=','relatedid',$id],['=','relatedtable',FzrbsContract::tableName()]])->orderBy('inserttime desc')->limit(10)->asArray()->all();
    
    return array('data'=>$res);
  }

   // ============================================= 欠款 ===========================================



   
   public function actionDebturge(){
    $contractid = $this->_request['id'];
    if (!$contractid) return array('errorMessage'=>'contractid 不能为空');
    $url = "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/contract/debturge&contractid=$contractid";

    $constract = FzrbsContract::findOne($contractid);
    $approvalUserid = $constract['creator'];

    if (!$approvalUserid) return;
    $msgdata = [
      'touser' => $approvalUserid,
      'msgtype' => 'textcard',
      'agentid' => $this->agentid,
      'textcard' => [
          'title' => "有逾期合同需要处理",
          'description' => '<div>合同名称：'.$constract['title'].'</div>',
          'url' => $url,
          'btntxt' => ' '
          
      ]
    ];
    
    $this->sendmsg($msgdata);
    return array('errorMessage'=>'');
   }

  public function getDealflow($dealresult){
    $result ='';
    switch ($dealresult) {

      
      default:
        $result='7ab35b30ae5958a28bdf8f707aaef0ae_1762163846';
        break;
    }
    return $result;
  }
  public function getDebturgeflow($urgetype){
    $result ='';
    switch ($urgetype) {
      case 1:
        $result = '4a057e343bc222daa932a1d7918a3bca_1764125539';
        break;
      
      default:
        $result='7ab35b30ae5958a28bdf8f707aaef0ae_1762163846';
        break;
    }
    return $result;
  }

  public function actionSaveurgelog(){
    $userid = $this->_adminInfo['wxuserid'];
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }
    if (!$obj['debturgeid']){
      return array('errorMessage'=>'debturgeid 不能为空');
    }
    $urge = FzrbsContractDebturge::findOne($obj['debturgeid']);
    $transaction = Yii::$app->db->beginTransaction();
    try {
      if ($obj['id']){

        unset($obj['urgetypename']);
        unset($obj['avatar']);
        unset($obj['contracttitle']);
        unset($obj['serial']);
        // 如果当前操作用户和creator不一致，更新uploader/uploadername/updatetime
        $existLog = FzrbsContractDebturgeLog::findOne($obj['id']);
        if ($existLog && $userid != $existLog->creator) {
          $obj['uploader'] = $userid;
          $obj['uploadername'] = $this->userinfo['name'];
          $obj['updatetime'] = date('Y-m-d H:i:s');
        }
        FzrbsContractDebturgeLog::updateAll($obj,['id'=>$obj['id']]);
        if ($obj['type']==1&&$obj['urgeresult']){
          $urge->state=$this->Urge_Finished;
          $urge->save();
        }
      }else{
        // 如果date为空就为当前日期
        if (!$obj['date']) $obj['date'] = date('Y-m-d');
        $obj['creator']=$userid;
        $obj['creatorname'] = $this->userinfo['name'];
        $obj = new FzrbsContractDebturgeLog($obj);
        
        $obj->save();
        // 如果type为1就结束催收,type=1 为清欠措施
        if ($obj['type']==1&&$obj['urgeresult']){
          $urge->state=$this->Urge_Finished;
          $urge->save();
        }
      }

      
      
     

    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }    
    $transaction->commit();
    return array('data'=>$obj);     
  }
  // 删除
  public function actionDelurgelog(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    $res = FzrbsContractDebturgeLog::findOne($id);
    // 只有本人才能删除
    if ($res['creator']!=$this->_adminInfo['wxuserid']) return array('errorMessage'=>'您没有权限删除此记录');
    $res->delete();
    return array('errorMessage'=>'');
  }
  public function actionEndurge(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    try {
      $res = FzrbsContractDebturgeLog::findOne($id);
      // 只有本人才能删除
      if ($res['creator']!=$this->_adminInfo['wxuserid']) return array('errorMessage'=>'只有经办才能结束催款');
      $res->state=5;
      $res->save();
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    
    return array('errorMessage'=>'');
  }
  public function actionGeturges(){ 
    $debturgeid = $this->_request['debturgeid'];
    if (!$debturgeid) return array('errorMessage'=>'debturgeid 不能为空');
    // 支持逗号分隔的多个ID查询
    if (strpos($debturgeid, ',') !== false) {
      $ids = explode(',', $debturgeid);
      $res = FzrbsContractDebturge::find()->where(['in', 'id', $ids])->asArray()->all();
    } else {
      $res = FzrbsContractDebturge::find()->where(['id'=>$debturgeid])->asArray()->all();
    }
    return array('data'=>$res);
  }

  // 删除欠款催收记录
  public function actionDelurge(){
    $id = $this->_request['id'];
    if (!$id) return array('errorMessage'=>'id 不能为空');
    
    $urge = FzrbsContractDebturge::findOne($id);
    if (!$urge) return array('errorMessage'=>'催收记录不存在');
    
    // 检查是否是本人创建
    if ($urge['creator'] != $this->_adminInfo['wxuserid']) {
      return array('errorMessage'=>'只有创建人本人可以删除');
    }
    
    $contractid = $urge['contractid'];
    
    // 开启事务
    $transaction = Yii::$app->db->beginTransaction();
    try {
      // 删除催收记录
      FzrbsContractDebturge::deleteAll(['id'=>$id]);
      
      // 删除关联的催收日志
      FzrbsContractDebturgeLog::deleteAll(['debturgeid'=>$id]);
      
      // 更新合同的debturgeid字段
      if ($contractid) {
        $contract = FzrbsContract::findOne($contractid);
        if ($contract && $contract->debturgeid) {
          $ids = explode(',', $contract->debturgeid);
          $ids = array_diff($ids, [$id]);
          $contract->debturgeid = implode(',', $ids);
          // 调用方法更新urgedamount
          $this->updateContractUrgedAmount($contractid);
          $contract->save();
        }
      }
      
      $transaction->commit();
      return array('errorMessage'=>'');
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
  }

  // 查询
  public function actionGeturgelogs(){
    $contractid = $this->_request['contractid'];
    $debturgeid = $this->_request['debturgeid'];
    $type = $this->_request['type'];
    $onlyThisUrge = $this->_request['onlyThisUrge'];
    if (!$contractid&&!$debturgeid) return array('errorMessage'=>'contractid和debturgeid不能同时为空');
    if ($onlyThisUrge&&!$debturgeid){
      return array('errorMessage'=>'onThisUrge为true时，debturgeid不能为空');
    }
    if (!$contractid&&$debturgeid){
      $where = ['l.debturgeid'=>$debturgeid];
    }else{
      $where = ['l.contractid'=>$contractid];
    }
    if (isset($type)) $where['l.type']=$type;
    $res = FzrbsContractDebturgeLog::find()->alias('l')
        ->select("l.*,u.avatar,d.label as urgetypename,d2.label as urgeresultname")
        ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=l.creator')
        ->leftJoin(['d'=>FzrbsBudgetDict::tableName()],'d.value=l.urgetype and d.type="清欠方式"')
        ->leftJoin(['d2'=>FzrbsBudgetDict::tableName()],'d2.value=l.urgeresult and d2.type="清欠结果"')
        ->where($where)->asArray()->orderBy('l.id desc')->all();

    return array('data'=>$res);
  }
  public function actionDebtflowact(){
    
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
        return $this->actionUpdateurge();
      default:
        # code...
        break;
    }
  }

  public function actionUrge(){
    $thirdNo=$this->_request['thirdNo'];
    if (!$thirdNo) return array('errorMessage'=>'thirdNo为空');

    $data = WeixinOaApprovalInfo::find()->where(array('thirdNo'=>$thirdNo))->one();
      
    if($data){
      $info = json_decode($data['data'],true);
      $this->send($data['approvalUserid'],'您有流程要审批!',$info,0);
    }
    return array('data'=>'催办成功');

  }
  // 部门简码+逾期年份+3位数字自动增号
  private function getSerialNo($contractid){
    if (!$contractid) {
      throw new Exception('contractid为空');
    }
    // 查询之前最新的判断是否存在序号
    $p = FzrbsContractDebturge::find()->where(['and',['=','contractid',$contractid]])->orderBy('id desc')->one();
    if ($p->serial) return $p->serial;
    // 查询逾期年份
    $condition= FzrbsContractPaycondition::find()->where(['and',['=','contractid',$contractid],['<','date',date('y-m-d')]])->orderBy('date desc')->one();
    $contract = FzrbsContract::findOne($contractid);
    $dept = WeixinOaDepartment::find()->where(['and',['=','id',$contract['departmentid']]])->one();
    if(!$dept['code']) {
      throw new Exception("部门【".$contract['department']."】的部门简码为空，无法生成催款序号，请联系财务设置");
    };
    // 逾期年份
    $signdate = substr($condition['date'],0,4);
 
    $serial = $dept['code'].'-'.$signdate;
    $p = FzrbsContractDebturge::find()->where(['and',['like','serial',$serial]])->orderBy('serial desc')->one();
    if (!$p) {
      $serial.='-001';
    }else{
      $serial = $p['serial'];
      $serial = substr($serial,0,strlen($serial)-3);
      $serial.=sprintf('%03d',intval(substr($p['serial'],-3))+1);
    }
    

    return $serial;
  }
 
  public function actionAgree(){
    
    $postdatas = $this->_request;
   
    $userid = $this->_adminInfo['wxuserid'];

    $status = 2;

    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      $wfp = new WorkflowParse($this->agentid);
      $ret = $wfp->changeFlow($userid,$status,$postdatas);
      
      $wfp->updateAfterFlowChange($ret,$userid,$status,$postdatas,$transaction);
      $d=FzrbsContractDebturge::find()->where(['thirdNo'=>$postdatas['thirdNo']])->one();
      $temp=array();
      if($ret['isfinish']){
        // 更新合同状态
        $c=FzrbsContract::findOne($d['contractid']);
        if (!$d['serial']){
          $serial = $this->getSerialNo($c['id']);
          $temp['serial']=$serial;
        }
        // 判断是否是处置审批
        if ($d->state==$this->Urge_Finished) { 
          $c->hasdeal=1;
          $state=$this->Urge_Deal; // 已处置
          // 从debturgeid中移除该催收ID
          if ($c->debturgeid) {
            $ids = explode(',', $c->debturgeid);
            $ids = array_diff($ids, [$d['id']]);
            $c->debturgeid = implode(',', $ids);
          }
          $users[]=$d->creator;
          $msg="逾期合同已处置";
          // 寻找最新的清欠措施
          $urgelog= FzrbsContractDebturgeLog::find()->where(['and',['=','type',1],['=','contractid',$c->id]])->orderBy('id desc')->one();
          $urgelog->dealresult = $d->dealresult;
          $urgelog->dealresultname = $d->dealresultname;
          $urgelog->dealresultnote = $d->dealresultnote;
          $urgelog->dealresultfileurls = $d->dealresultfileurls;
          $urgelog->dealer = $this->userinfo['name'];
          $urgelog->dealuserid = $this->userinfo['userid'];
          $urgelog->dealdate = date('Y-m-d');
          $urgelog->save();
        }else{
   
          // $c->urgestate=$d->urgetype;
          // 从debturgeid中移除该催收ID（因为已完成审批不再是催收中了）
          if ($c->debturgeid) {
            $ids = explode(',', $c->debturgeid);
            $ids = array_diff($ids, [$d['id']]);
            $c->debturgeid = implode(',', $ids);
          }
          $state=$this->Urge_Urging; // 正在催收
          // 推送给经办和抄送人
          $users[]=$d->creator;
          if ($d->notifiers){
            $users=array_merge($users,explode(',',$d->notifiers));
          }
          $msg="欠款催收审批已通过";
        }
        $c->save();
        $temp['state']=$state;
        
        FzrbsContractDebturge::updateAll($temp,['thirdNo'=>$postdatas['thirdNo']]);
        // 审批通过后自动生成清欠措施
        $urgelog=new FzrbsContractDebturgeLog();
        $urgelog['date'] = date('Y-m-d');
        $urgelog['creator']=$userid;
        $urgelog['creatorname'] = $this->userinfo['name'];
        $urgelog['contractid']=$c['id'];
        $urgelog['debturgeid']=$d['id'];
        $urgelog['type']=1;// 清欠措施
        $urgelog['urgetype']=$d['urgetype'];

        
        $urgelog->save();



        $this->send(implode('|',$users),$msg.'!',$d,1);

      }else if ($ret['nextdata']&&$ret['nextdata']['approvalUserid']){
        $this->send($ret['nextdata']['approvalUserid'],'有欠款审批申请需要您审批!',$d,0);
      }
      

    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    
    return array('data'=>$ret);
  }

  public function actionReject(){//驳回
    $userid = $this->_adminInfo['wxuserid'];
    $postdatas = $this->_request;
    if (!$postdatas['speech']) return array('errorMessage'=>'审批意见不能为空');
    
    
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      $status=3;
      $wfp = new WorkflowParse($this->agentid);
      $ret = $wfp->changeFlow($userid,$status,$postdatas);
      $wfp->updateAfterFlowChange($ret,$userid,$status,$postdatas,$transaction);
   
      FzrbsContractDebturge::updateAll(['thirdNo'=>'','dealresult'=>'','dealresultname'=>'','dealresultnote'=>''],['thirdNo'=>$postdatas['thirdNo']]);
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    $this->send($userid,'申请被驳回',$postdatas,1);
    return array('data'=>array('ret'=>1));
  }
  public function actionCancel(){//撤消


      $userid = $this->_adminInfo['wxuserid'];
      $postdatas = $this->_request;
      $transaction = Yii::$app->getDb()->beginTransaction();
      try {
        $status=4;
        $wfp = new WorkflowParse($this->agentid);
        $ret = $wfp->changeFlow($userid,$status,$postdatas);
        $wfp->updateAfterFlowChange($ret,$userid,$status,$postdatas,$transaction);
        FzrbsContractDebturge::updateAll(['thirdNo'=>'','dealresult'=>'','dealresultname'=>'','dealresultnote'=>''],['thirdNo'=>$postdatas['thirdNo']]);
        
      } catch (\Throwable $th) {
        $transaction->rollBack();
        return array('errorMessage'=>$th->getMessage());
      }
      $transaction->commit();

      return array('data'=>array('ret'=>1));
	
	}
  
  
  public function actionInglist(){

    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'i.id desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    $where = ['and',new Expression("status=1 and agentId=".$this->agentid." and LOCATE('|".$userid."|',CONCAT('|',approvalUserid,'|'))")];


    $model = WeixinOaApprovalInfo::find()->alias('i')->select("i.thirdNo,i.status,i.userName,i.inserttime,d.title,d.contractid as contractid")
        ->rightJoin(['d'=>"(select c.title,f.* from ".FzrbsContractDebturge::tableName()." f left join ".FzrbsContract::tableName()." c on c.id=f.contractid) "],'d.thirdNo=i.thirdNo') 
        ->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    foreach ($res as $key => $value) {
      $res[$key]['name'] = $res[$key]['userName'];
    }
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
    $orderby = 'i.id desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    $where = ['and',new  Expression("i.thirdNo in (SELECT distinct thirdNo FROM ".WeixinOaApprovalLog::tableName()." where userId='$userid'  and agentid=".$this->agentid).")"];

    $model = WeixinOaApprovalInfo::find()->alias('i')->select("i.thirdNo,i.status,i.userName,i.inserttime,d.title,d.contractid as contractid")
        ->leftJoin(['d'=>"(select c.title,f.* from ".FzrbsContractDebturge::tableName()." f left join ".FzrbsContract::tableName()." c on c.id=f.contractid) "],'d.thirdNo=i.thirdNo') 
        ->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    foreach ($res as $key => $value) {
      $res[$key]['name'] = $res[$key]['userName'];
    }

  
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $res;
    return $_result;
  }
  /** 查询催款中的合同
   * 相关人员都可以预览
   */
  public function actionUrginglist(){

    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'i.id desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    $where = ['and',new Expression("status=1 and agentId=".$this->agentid." and LOCATE('|".$userid."|',CONCAT('|',approvalUserid,'|'))")];


    $model = WeixinOaApprovalInfo::find()->alias('i')->select("i.status,i.userName,i.inserttime,d.title,d.contractid as contactid")
        ->leftJoin(['d'=>"(select c.title,f.* from ".FzrbsContractDebturge::tableName()." f left join ".FzrbsContract::tableName()." c on c.id=f.contractid) "],'d.thirdNo=i.thirdNo') 
        ->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    foreach ($res as $key => $value) {
      $res[$key]['name'] = $res[$key]['userName'];
    }
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $res;
    return $_result;
  }
  public function actionUpdateurge(){
    $userid = $this->_adminInfo['wxuserid'];
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }
    if ($obj['urgeresult']){
      $temp = FzrbsBudgetDict::find()->where(['type'=>'清欠结果','value'=>$obj['urgeresult']])->one();
      if ($temp){
        $obj['urgeresultname'] = $temp['label'];
      }
    }
    if ($obj['urgetype']){
      $temp = FzrbsBudgetDict::find()->where(['type'=>'清欠方式','value'=>$obj['urgetype']])->one();
      if ($temp){
        $obj['urgetypename'] = $temp['label'];
      }
    }
    unset($obj['debturgeid']);
    try {
      if ($obj['id']){
        // 如果是结束催收
        
        $old = FzrbsContractDebturge::findOne($obj['id']);
        if ($obj['status']==$this->Urge_Deal){
          // 判断是否是创建人
          if ($old->creator!= $userid){
            return array('errorMessage'=>'只有创建人才可以结束催收');
          }
        }
        // 如果 serial 变化了，需要判断是否已经存在，如果已经存在，则不允许保存
        if (isset($obj['serial'])&&$old->serial != $obj['serial']){
          $temp = FzrbsContractDebturge::find()->where(['serial'=>$obj['serial']])->one();
          if ($temp){
            return array('errorMessage'=>'催收编号已存在');
          }
        }
        // 如果清欠方式变化了
        if ($old->urgetype != $obj['urgetype']){
    
          $deptid = $this->userinfo['departmentid'];
          try {
            $noti = $this->getNotifier($obj['urgetype'],$deptid);
            $obj['notifiers'] = $noti['notifiers'];
            $obj['notifiernames'] = $noti['notifiernames'];
          } catch (\Throwable $th) {
            return array('errorMessage'=>$th->getMessage());
          }
        }
        
        FzrbsContractDebturge::updateAll($obj,['id'=>$obj['id']]);
        
        // 更新合同的urgedamount字段
        $old = FzrbsContractDebturge::findOne($obj['id']);
        if ($old && $old->contractid) {
          $this->updateContractUrgedAmount($old->contractid);
        }
      }else{

        $temp = new FzrbsContractDebturge($obj);
        $temp->save();
      }
      
    } catch (\Throwable $th) {

      return array('errorMessage'=>"保存：".$th->getMessage());
    }
    return array('errorMessage'=>'');
    
  }
 
  // 查询用户指定角色所属的所有部门ID
  private function getRoleDepts($rolename, $userid){
    $depts = WeixinOaFlowrole::findBySql("
        SELECT dept from weixin_oa_flowrole 
        where userid='$userid' 
        and role in (SELECT id from weixin_oa_role where rolename='$rolename') 
        and FIND_IN_SET(".$this->agentid.",agent)
    ")->column();
    
    $deptIds = [];
    foreach ($depts as $deptStr) {
        $deptIds = array_merge($deptIds, explode(',', $deptStr));
    }
    return array_unique(array_filter($deptIds));
  }

  // 查询对应部门的会计或法务
  private function getRole($rolename,$deptid){
    $model = WeixinOaFlowrole::findBySql("SELECT userid,username from weixin_oa_flowrole where role in (SELECT id from weixin_oa_role where rolename='$rolename') and FIND_IN_SET(".$this->agentid.",agent) and FIND_IN_SET($deptid,dept) and FIND_IN_SET(".$this->agentid.",agent)")->asArray();
    if (!$model->count()){
      $model = WeixinOaFlowrole::findBySql("SELECT userid,username from weixin_oa_flowrole where role in (SELECT id from weixin_oa_role where rolename='$rolename') and FIND_IN_SET($deptid,dept) and FIND_IN_SET(".$this->agentid.",agent)")->asArray();
    }
    if (!$model->count()){
      $dept = WeixinOaDepartment::findOne($deptid);
      throw new Exception("应用【合同管理】部门【".$dept['name']."】对应的【".$rolename."】未设置，请联系管理员！");
    }

    return $model->all();
  }
  // 更新合同的urgedamount字段
  private function updateContractUrgedAmount($contractid) {
    if (!$contractid) {
      return;
    }
    $totalDebtAmount = FzrbsContractDebturge::find()
      ->where(['contractid' => $contractid])
      ->andWhere(['!=', 'state', 5])
      ->sum('debtamount');
    $totalDebtAmount = $totalDebtAmount ? $totalDebtAmount : 0;
    FzrbsContract::updateAll(['urgedamount' => $totalDebtAmount], ['id' => $contractid]);
  }
  // 设置抄送人
  private function getNotifier($urgetype,$deptid){
    $canNotBeNull=false;
    try {
      switch ($urgetype) {
        case 3: // '发催款函'
        case 6: // '坏账核销'
          # 查询相关会计
          $users = $this->getRole('会计',$deptid);
          $canNotBeNull = true;
          break;
        case 4: // 发律师函
        case 5: // 提起诉讼
          # 查询相关法务
          $users = $this->getRole('经审小组-法务',$deptid);
          $canNotBeNull = true;
          break;
        default:
          # code...
          break;
      }
    } catch (\Throwable $th) {
      throw $th;
    }

    if ($users){
      return array('notifiers'=>implode(',',array_column($users,'userid')),'notifiernames'=>implode(',',array_column($users,'username')));
    }
 
    return array('notifiers'=>'','notifiernames'=>'','canNotBeNull'=>$canNotBeNull);
  }
  function convertToDays($str) {
      $days = 0;
      // 匹配“年”和“天”
      if (preg_match('/(\d+)年/', $str, $matches)) {
          $days += $matches[1] * 365;
      }
      if (preg_match('/(\d+)天/', $str, $matches)) {
          $days += $matches[1];
      }
      return $days;
  }

  function isGreaterThan3Years($str) {
      $totalDays = $this->convertToDays($str);
      return $totalDays > (3 * 365); // 3年 = 1095天
  }
  // 修改合同的申请部门和经办
  public function actionAltercharger(){
    $obj = $this->_request;
    if (!$obj['id']){
      return array('errorMessage'=>'id不能为空');
    }
    try {
      $old = FzrbsContract::findOne($obj['id']);
      $dept = WeixinOaDepartment::findOne($old['departmentid']);
      if ($this->haspower('财务管理',$this->agentid,$old['departmentid'],'')){
        $obj['department'] = $dept['name'];
     
        FzrbsContract::updateAll($obj,['id'=>$obj['id']]);
      }else{
        
        return array('errorMessage'=>'不是部门【'.$dept['name'].'】的会计，不能操作！');
      
      }

    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('errorMessage'=>'');

  }
  // 发起处置审批
  public function actionStartdeal(){
      // return array('errorMessage'=>'录入历史处置请按照下述指示进行操作。打开预览页面-》点击清欠措施按钮-》填写清欠措施和处置措施；或者，打开预览页面-》清欠措施显示栏-》点击清欠措施-》填写处置措施；');
      $obj = $this->_request;
      if ($this->_request['obj']){
        $obj = $this->_request['obj'];
      }
      if (!$obj['contractid']){
        return array('errorMessage'=>'contractid不能为空');
      }
      if (!$obj['dealresult']){
        return array('errorMessage'=>'处置结果不能为空');
      }
      

      $temp = FzrbsBudgetDict::find()->where(['type'=>'处置结果','value'=>$obj['dealresult']])->one();
      if ($temp){
        $obj['dealresultname'] = $temp['label'];
      }
      // 判断是否已经存在在正在催收中的
      $urge= FzrbsContractDebturge::find()->where(['and',['contractid'=>$obj['contractid']],['=','state',$this->Urge_Finished]])->orderBy("id desc")->one();
      if (!$urge){
        return array('errorMessage'=>'未找到与合同相关的、待处置的清欠措施！');
      }
      
      $userid = $this->_adminInfo['wxuserid'];

      $templateid=$this->getDealflow($obj['dealresult']);
      $wfp = new WorkflowParse($this->agentid);
      try {
        $tesult = $wfp->startFlow($userid,$templateid,array(),$obj);
        $thirdNo = $tesult['thirdNo'];
      } catch (\Throwable $th) {
        return array('errorMessage'=>"启动流程：".$th->getMessage());
      }
      $obj['thirdNo']=$thirdNo;
      
      // 发起事务
      $transaction = \Yii::$app->db->beginTransaction();
      try {
        unset($obj['debturgeid']);
        FzrbsContractDebturge::updateAll($obj,['id'=>$urge['id']]);
      } catch (\Throwable $th) {
        $transaction->rollBack();
        return array('errorMessage'=>"保存数据：".$th->getMessage());
      }
      $transaction->commit();
      if ($tesult['approvalUserid']){
        $tesult['approvalUserid'][]=$userid;
      }

      $this->send(implode('|',$tesult['approvalUserid']),$this->userinfo['name'].'的审批申请',$obj,0);
      return array('errorMessage'=>'','thirdNo'=>$thirdNo);
   }
  
  //  public function actionStartdebturge(){
 
  //     $obj = $this->_request;
  //     if ($this->_request['obj']){
  //       $obj = $this->_request['obj'];
  //     }
  //     unset($obj['debturgeid']);
  //     if (!$obj['reason']){
  //       return array('errorMessage'=>'拖欠原因不能为空');
  //     }
  //     if (!$obj['urgetype']){
  //       return array('errorMessage'=>'催收方式不能为空');
  //     }
      
  //     if ($obj['age']){
  //       if ($obj['urgetype']!=5&&$this->isGreaterThan3Years($obj['age'])){
  //         return array('errorMessage'=>'账龄大于3年，只能选择提起诉讼');
  //       }
  //     }
    
  //     unset($obj['id']);
  //     $temp = FzrbsBudgetDict::find()->where(['type'=>'清欠方式','value'=>$obj['urgetype']])->one();
  //     if ($temp){
  //       $obj['urgetypename'] = $temp['label'];
  //     }

  //     $userid = $this->_adminInfo['wxuserid'];
  //     $deptid = $this->userinfo['departmentid'];
  //     try {
  //       $serial = $obj['serial'];
  //       if (!$serial){
  //         if (!$obj['contractid']) return array('errorMessage'=>'清欠编号不能为空！');
  //         $serial = $this->getSerialNo($obj['contractid']);
  //       }
        
  //       $noti = $this->getNotifier($obj['urgetype'],$deptid);
   
  //     } catch (\Throwable $th) {
  //       return array('errorMessage'=>$th->getMessage());
  //     }

      
      
  //     // 发起事物
  //     $transaction = \Yii::$app->db->beginTransaction();
  //     try {
  //       unset($obj['age']);
  //       unset($obj['urgeresult']);
  //       unset($obj['urgeresultname']);
  //       $temp = new FzrbsContractDebturge($obj);
  //       $temp->creator = $userid;
  //       $temp->notifiers = $noti['notifiers'];
  //       $temp->notifiernames = $noti['notifiernames'];
  //       $temp->state = 2;
  //       $temp->serial = $serial;
  //       $temp->creator = $this->_adminInfo['wxuserid'];
  //       $temp->departmentid = $this->userinfo['departmentid'];
       
  //       $temp->save();
  //       // 更新合同的debturgeid（用逗号分隔多个催收ID）
  //       if ($obj['contractid']) {
  //         $contract = FzrbsContract::findOne($obj['contractid']);
  //         $existingIds = $contract->debturgeid ? explode(',', $contract->debturgeid) : [];
  //         $existingIds[] = $temp['id'];
  //         $newDebturgeId = implode(',', array_unique($existingIds));
  //         // 调用方法更新urgedamount
  //         $this->updateContractUrgedAmount($obj['contractid']);
  //         FzrbsContract::updateAll(['debturgeid'=>$newDebturgeId,'hasdeal'=>0],['id'=>$obj['contractid']]);
  //       }

  //       // 审批通过后自动生成清欠措施
  //       $urgelog=new FzrbsContractDebturgeLog();
  //       $urgelog['date'] = date('Y-m-d');
  //       $urgelog['creator']=$userid;
  //       $urgelog['creatorname'] = $this->userinfo['name'];
  //       $urgelog['contractid']=$obj['contractid'];
  //       $urgelog['debturgeid']=$temp['id'];
  //       $urgelog['type']=1;// 清欠措施
  //       $urgelog['urgetype']=$obj['urgetype'];
  //       $urgelog->save();

  //       // 生成清欠措施并发送通知给文件上传人
  //       if ($noti['notifiers']){
  //         $this->send($noti['notifiers'],$this->userinfo['name'].'的清欠措施',$obj,1);
  //       }
        
  //     } catch (\Throwable $th) {
  //       $transaction->rollBack();
  //       return array('errorMessage'=>"保存数据：".$th->getMessage());
  //     }
  //     $transaction->commit();

  //     return array('errorMessage'=>'','thirdNo'=>'');
  //  }
  public function actionStartdebturge(){
    
      $obj = $this->_request;
      if ($this->_request['obj']){
        $obj = $this->_request['obj'];
      }
      unset($obj['debturgeid']);

      if (!$obj['reason']){
        return array('errorMessage'=>'拖欠原因不能为空');
      }
      if (!$obj['urgetype']){
        return array('errorMessage'=>'催收方式不能为空');
      }
      
      if ($obj['age']){
        if ($obj['urgetype']!=5&&$this->isGreaterThan3Years($obj['age'])){
          return array('errorMessage'=>'账龄大于3年，只能选择提起诉讼');
        }
      }
  
      unset($obj['id']);
      $temp = FzrbsBudgetDict::find()->where(['type'=>'清欠方式','value'=>$obj['urgetype']])->one();
      if ($temp){
        $obj['urgetypename'] = $temp['label'];
      }

      
      $userid = $this->_adminInfo['wxuserid'];
      $deptid = $this->userinfo['departmentid'];
      try {
           $serial = $obj['serial'];
        if (!$serial){
          if (!$obj['contractid']) return array('errorMessage'=>'清欠编号不能为空！');
          $serial = $this->getSerialNo($obj['contractid']);
        }
        $noti = $this->getNotifier($obj['urgetype'],$deptid);
   
      } catch (\Throwable $th) {
        return array('errorMessage'=>$th->getMessage());
      }

      
      
      $templateid=$this->getDebturgeflow($obj['urgetype']);
      $wfp = new WorkflowParse($this->agentid);
      try {
        $tesult = $wfp->startFlow($userid,$templateid,array(),$obj);
        $thirdNo = $tesult['thirdNo'];
      } catch (\Throwable $th) {
        return array('errorMessage'=>"启动流程：".$th->getMessage());
      }
      $obj['thirdNo']=$thirdNo;
      
      
      // 发起事物
      $transaction = \Yii::$app->db->beginTransaction();
      try {
        unset($obj['age']);
        unset($obj['urgeresult']);
        unset($obj['urgeresultname']);
        $temp = new FzrbsContractDebturge($obj);
        $temp->creator = $userid;
        $temp->notifiers = $noti['notifiers'];
        $temp->notifiernames = $noti['notifiernames'];
        $temp->state = 1;
        $temp->creator = $this->_adminInfo['wxuserid'];
        $temp->departmentid = $this->_adminInfo['departmentid'];
        $temp->save();
        // 更新合同的debturgeid（用逗号分隔多个催收ID）
        if ($obj['contractid']) {
          $contract = FzrbsContract::findOne($obj['contractid']);
          $existingIds = $contract->debturgeid ? explode(',', $contract->debturgeid) : [];
          $existingIds[] = $temp['id'];
          $newDebturgeId = implode(',', array_unique($existingIds));
          // 调用方法更新urgedamount
          $this->updateContractUrgedAmount($obj['contractid']);
          FzrbsContract::updateAll(['debturgeid'=>$newDebturgeId,'hasdeal'=>0],['id'=>$obj['contractid']]);
        }
          } catch (\Throwable $th) {
            $transaction->rollBack();
            return array('errorMessage'=>"保存数据：".$th->getMessage());
          }
        $transaction->commit();
        if ($tesult['approvalUserid']){
          $tesult['approvalUserid'][]=$userid;
        }

        $this->send(implode('|',$tesult['approvalUserid']),$this->userinfo['name'].'的欠款催收审批申请',$obj,0);
        return array('errorMessage'=>'','thirdNo'=>$thirdNo);
   }

   public function actionPreviewdebtflow(){
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }

    if (!$obj['userid']){
      $obj['userid']=$this->_adminInfo['wxuserid'];
    }
    $dealresult = $obj['dealresult'];
    if (!$dealresult&&!$obj['urgetype']){
        return array('errorMessage'=>'urgetype和dealresult不能都为空');
    }
  
    
    $deptid = $this->userinfo['departmentid'];
    try {
      // 选择流程模板
      if ($obj['urgetype']){
        $templateid=$this->getDebturgeflow($obj['urgetype']);
        $noti = $this->getNotifier($obj['urgetype'],$deptid);
      }else{
        $templateid=$this->getDealflow($dealresult);
      }
      
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    
    $template = WeixinOaTemplates::find()->where(['templateid'=>$templateid])->one();
    if (!$template){
      return array('errorMessage'=>'模板【'.$templateid.'】可能已经被删除，联系管理员重新设置！');
    }
  
    $wfp = new WorkflowParse($this->agentid);
    try {
      $res = $wfp->previewFlow($obj['userid'],$templateid,$obj);
    } catch (\Throwable $th) {
      return array('errorMessage'=>"预览流程：".$th->getMessage());
    }
    
    if ($noti){
      $res['viewdata']['notify']=explode(',',$noti['notifiernames']);
    }
   
    $res['viewdata']['templatename']=$template['templateName'];
  
    return $res;
   }

   // 无合同查询 
   public function actionViewdebtwithoutcontract(){
    $id = $this->_request['debturgeid'];
    if (!$id) return array("errorMessage"=>"id 不能为空");
    $urge = FzrbsContractDebturge::find()->alias('d')->select('d.*,DATEDIFF(NOW(),`overduedate`) as age,u.name,t.name as department')
      ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=d.urgeuserid')
      ->leftJoin(['t'=>WeixinOaDepartment::tableName()],'t.id=d.urgedepartmentid')->where(['and',['=','d.id',$id]])->asArray()->one();
    $thirdNo = $urge['thirdNo'];
    // 流程信息
    $wfp = new WorkflowParse($this->agentid);
    $flowdata = $wfp->flowInfodata($thirdNo);
    $viewdata =  $wfp->flowViewdata($thirdNo);
    // 判断是否在催收中

    // 判断是否在审批中
    if($viewdata){
      // 判断流程是否已经审批结束
      if ($flowdata&&$flowdata['status']==1){
        // 判断是否是创建人
        if ($flowdata['userId']==$this->_adminInfo['wxuserid']){
          $flowdata['isApproving']=1;
        }
        // 判断是否是当前审批人,审批人是用'|'分隔的,不能用等于分别，也不要用strpos匹配
        $approvalUsers = explode(',', $flowdata['approvalUserid']);
        if (in_array($this->_adminInfo['wxuserid'], $approvalUsers, true)) {
            $flowdata['isCurrentApprover'] = 1;
        }

      }
    }
    return array('contract'=>array(),'isUrging'=>$urge['state']==2?1:0,'urge'=>$urge?$urge:new stdClass(),'flowdata'=>$flowdata,'viewdata'=>$viewdata,'statusCn'=>$this->statusCn);
   }
   public function actionViewdebt(){
    $id = $this->_request['id'];
    
    $thirdNo = $this->_request['thirdNo'];
    $debturgeid = $this->_request['debturgeid'];
    $urgeserial = $this->_request['urgeserial'];
    if (!$id&&$debturgeid){
      return $this->actionViewdebtwithoutcontract();
    }
    if(!$id&&!$thirdNo) return array('errorMessage'=>'id和thirdNo 不能都为空');
    if ($id) {
      $where=['and',['=','c.id',$id]];
    }
    
    // 合同信息
    $model = FzrbsContract::find()->alias('c')->select("c.id,c.debturgeid,c.signdate,c.serial,c.amount,c.paycollection,c.title,c.partaname,c.parta,co.overdue as age,DATE(co.paydate) as paydate,(c.amount-co.paycollection) as debt,u.name as creatorname")
    ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=c.creator')
    ->rightJoin(['co'=>"(select id,overdue,paycollection,paydate from (SELECT cc.id,cc.title,cc.amount,p.rate,p.date as paydate,cc.paycollection,DATEDIFF(NOW(),date) as overdue  from ".FzrbsContractPaycondition::tableName()." p left join ".FzrbsContract::tableName()." cc on p.contractid=cc.id  order by p.date desc  ) a where overdue>0  group by id)"],'co.id=c.id')
    ->leftJoin(['invoice'=>"(select contractid,sum(realamount) as invoiceamount from ".FzrbsInvoicingInvoice::tableName()." group by contractid)"],'invoice.contractid=c.id')
    ->leftJoin(['d2'=>FzrbsBudgetDict::tableName()],'d2.id=c.type')
    ->where($where)->asArray()->one();
   
    // 催办信息
    // 获取该合同的所有催收记录，检查是否存在催收中的记录

    if ($thirdNo){
      $uwhere = ['=','thirdNo',$thirdNo];
    } else if ($urgeserial){
      $uwhere = ['=','serial',$urgeserial];
    } else if ($id){
      $uwhere = ['=','contractid',$id];
    }
    $urges = FzrbsContractDebturge::find()->where($uwhere)->orderBy('id desc')->asArray()->all();
  
    $urge = $urges ? $urges[0] : null;
    $thirdNo = $urge ? $urge['thirdNo'] : null;
    // 判断是否存在催收中的记录
    $isUrging = 0;
    foreach ($urges as $u) {
      if ($u['state'] == 2) {
        $isUrging = 1;
        break;
      }
    }
    if ($model) {
      $model['age']=$model['age'].'天';
      $model['invoiceamount']=$model['invoiceamount']?$model['invoiceamount']:0;
    }
    
    if (!$urge){
      $company = FzrbsCompany::findOne($model['parta']);
      $urge['address']=$company['address'];
      $urge['mobile']=$company['contacts'];
    }
    // 流程信息
    
    $wfp = new WorkflowParse($this->agentid);
    $flowdata = $wfp->flowInfodata($thirdNo);
    $viewdata =  $wfp->flowViewdata($thirdNo);
    // 判断是否在催收中

    // 判断是否在审批中
    if($viewdata){
      // 判断流程是否已经审批结束
      if ($flowdata&&$flowdata['status']==1){
        // 判断是否是创建人
        if ($flowdata['userId']==$this->_adminInfo['wxuserid']){
          $flowdata['isApproving']=1;
        }
        // 判断是否是当前审批人,审批人是用'|'分隔的,不能用等于分别，也不要用strpos匹配
        $approvalUsers = explode(',', $flowdata['approvalUserid']);
        if (in_array($this->_adminInfo['wxuserid'], $approvalUsers, true)) {
            $flowdata['isCurrentApprover'] = 1;
        }

      }
    }
    return array('contract'=>$model,'isUrging'=>$isUrging,'urge'=>$urge?$urge:new stdClass(),'urges'=>$urges,'flowdata'=>$flowdata,'viewdata'=>$viewdata,'statusCn'=>$this->statusCn);
   }
   public function actionDebtlistbyfield(){ 

    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $order = 'overdue desc,amount desc';
    if ($this->_request['orderby']) {
      $order = $this->_request['orderby'];
    }
    $field = $this->_request['field'];
    if (!$field) return array('errorMessage'=>'field 不能为空');
    $fieldSelect="";
    $fieldGroup="";
    switch ($field) {
      case 'department':
        $fieldSelect="departmentid,department";
        break;
      case 'partaname':
        $fieldSelect="parta,partaname";
        break;
      default:
        # code...
        break;
    }
    if ($fieldSelect) $fieldGroup="group by $fieldSelect";


    $sql="select $fieldSelect,sum(amount-paycollection) as debt,sum(amount) as amount,sum(paycollection) as paycollection,ROUND(AVG(overdue)) as overdue from (select id,overdue,paycollection,amount,$fieldSelect,title from 
    (SELECT cc.id,cc.title,cc.amount,cc.departmentname as department,cc.departmentid,cc.partaname,cc.parta,p.rate,p.date as paydate,cc.paycollection, IFNULL(cc.paycollection/cc.amount,0) as payratio,DATEDIFF(NOW(),date) as overdue  from ".FzrbsContractPaycondition::tableName()." p  left join (select fc.*,ld.name as departmentname from fzrbs_contract fc left join weixin_leave_department ld on fc.departmentid=ld.id) cc on p.contractid=cc.id and cc.state=0  and cc.type=15 AND p.date = ( SELECT MIN(p2.date) FROM fzrbs_contract_paycondition p2 INNER JOIN fzrbs_contract c2 ON p2.contractid = c2.id WHERE p2.contractid = p.contractid AND c2.state = 0 ) order by p.date desc  ) 
    a where a.overdue>0  and payratio*100<rate  group by a.id) b where $field is not null $fieldGroup order by $order";

    $model = FzrbsContract::findBySql($sql);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->asArray()->all();


   
    $stat['total']=$total;
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;


    return $this->_result;
   }
  // 合同逾期欠款统计
  // 一个合同可能存在多个时间段的逾期，因此分阶段的统计结果之和会大于欠款总额
  public function actionDebtstat(){
    $where = [
        'and',
        ['>', 'c.id', 0],
    ];
    $dept = $this->getDepts();
    if (sizeof($dept)>0) {
      $where[] = ['or',['in' , 'c.signdeptid' , $dept],['in' , 'c.departmentid' , $dept],['=' , 'c.creator' , $this->_adminInfo['wxuserid']]];
    } else {
      $where[] =  ['=' , 'c.creator' , $this->_adminInfo['wxuserid']];
    }
    $arr = ['0+','0-30','31-60','90+'];
    $result = [];
    for ($i=0; $i < sizeof($arr); $i++) {
      $sql='';
      switch ($i) {
        case 0:
          $sql = "and overdue>0";
          break;
        case 1:
          $sql = "and overdue<=30 and overdue>0";
          break;
        case 2:
          $sql = "and overdue<=60 and overdue>30";
          break;
        case 3:
          $sql = "and overdue>=90";
          break;
        default:
          # code...
          break;
      }
      $model = FzrbsContract::find()->alias('c')->select("sum(c.amount-co.paycollection) as debt")
        ->rightJoin(['co'=>"(select id,overdue,paycollection from (SELECT cc.id, cc.title, cc.amount, p.rate, p.date AS paydate, cc.paycollection, IFNULL(cc.paycollection / cc.amount, 0) AS payratio, DATEDIFF(NOW(), p.date) AS overdue FROM fzrbs_contract_paycondition p INNER JOIN fzrbs_contract cc ON p.contractid = cc.id WHERE  cc.state = 0 AND p.date = ( SELECT MIN(p2.date) FROM fzrbs_contract_paycondition p2 INNER JOIN fzrbs_contract c2 ON p2.contractid = c2.id WHERE p2.contractid = p.contractid AND c2.state = 0 )  ) a where payratio*100<rate $sql group by id)"],'co.id=c.id')->where($where)->having("debt>0")->asArray()->one();
      if ($model) {
        $result[] = $model['debt'];
      }
    }
    
    $result= array(
      'ageGroup'=> array('欠款总额'=>$result[0],'30天以内'=> $result[1], '31-60天'=> $result[2], '90天以上'=>$result[3] ));
    return $result;
   }
  //  返回有效的收款
   public function actionGetvalidpaycollections(){
    $where = [
        'and',
        ['=', 'p.state', 3],['=', 'p.valid', 1],new Expression("p.contractid is not null")
    ];
    $datestart = $this->_request['datestart'];
    $dateend = $this->_request['dateend'];
    if ($datestart){
      $where[] = ['>=', 'p.date', $datestart];
    }
    if ($dateend){
      $where[] = ['<=', 'p.date', $dateend.' 23:59:59'];
    }
    $parta = $this->_request['parta'];
    $csql = "";
    if ($parta) {
      $csql.=" and parta=$parta";
    }
    $datas = FzrbsContractPaycollection::find()->alias('p')->select('p.*,c.title')
      // 关联合同
      ->rightJoin("(select * from ".FzrbsContract::tableName()." where id>0 $csql) c","c.id=p.contractid")
      ->where($where)->orderBy('p.id desc')->asArray()->all();
    return array('data'=>$datas);
   }
   public function actionPaycollectionlist(){
    $userid = $this->_adminInfo['wxuserid'];
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;
    $offset = $limit * ($page - 1);
    $order = 'c.parta desc';
    
    $where = $this->getWhere();
    // parta 不是空或空字符
    $where[] = new Expression('parta!="" and parta is not null');

    $psql = "";
    if ($this->_request['order']) {
      $order=$this->_request['order'];
    }
    $datestart = $this->_request['datestart'];
    if ($this->_request['datestart']) {
      $psql .= "and date>='".$datestart."'";
    }
    if ($this->_request['dateend']) {
      $psql .= "and date<='".$this->_request['dateend']." 23:59:59'";
    }
    $temp = FzrbsContract::find()->alias('c')
    // 期间回款
    ->leftJoin(['p'=>"(select contractid,sum(amount) as amount from ".FzrbsContractPaycollection::tableName()." where contractid is not null and state=3 and valid=1 $psql group by contractid )"],"c.id=p.contractid")
    // 期初回款
    ->leftJoin(['s'=>"(select contractid,sum(amount) as amount from ".FzrbsContractPaycollection::tableName()." where contractid is not null and state=3 and valid=1 and date<'".$datestart."' group by contractid )"],"c.id=s.contractid")
    
    ;
 
 

    $select="c.parta,c.partaname,sum(c.amount) as contractamount,SUM(p.amount) AS paycollection,SUM(s.amount) AS startpaycollection";
    $temp->select($select);
    
    
  
    $model = $temp->where($where);
    $total = $model->count();
  
    $res = $model->orderBy($order)->limit($limit)->offset($offset)->groupBy("c.parta, c.partaname")->asArray()->all();
    // $res 如果 paycollection 或 startpaycollection 为空，则显示0,如果parta为空，则
    $res = array_map(function($item) {
      $item['paycollection'] = $item['paycollection']?$item['paycollection']:0;
      $item['startpaycollection'] = $item['startpaycollection']?$item['startpaycollection']:0;
      return $item;
    }, $res);
   
    $stat['total']=$total;
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;


    return $this->_result;

   }
   //  设置账销案存
   public function actionSetrecoverable(){
    $contractid = $this->_request['contractid'];
    if (!$contractid) {
      return array('errorMessage'=>'contractid 不能为空');
    }
    $c = FzrbsContract::findOne($contractid);
    
    $c->recoverable = $c->recoverable?0:1;
    $c->save();
    return array('errorMessage'=>'');
   }
   public function actionUrgelist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);


    $order = 'd.id desc';

    $userid = $this->_adminInfo['wxuserid'];

    $where = [
        'and',
        ['>', 'd.id', 0]
        // ,['or',['=','d.creator',$userid],['=','d.urgeuserid',$userid]]
    ];

    if ($this->_request['orderby']) {
      $order=$this->_request['orderby'];
      // signdate 替换成 overduedate
      $order = str_replace('signdate', 'overduedate', $order);
      $order = str_replace('debt', 'debtamount', $order);
      $order = str_replace('creator', 'urgeuserid', $order);
    }

    if ($this->_request['creator']) {
      $where[] = ['=', 'd.urgeuserid', $this->_request['creator']];
    }
    if ($this->_request['departmentid']) {
      $where[] = ['in', 'd.departmentid', explode(',',$this->_request['departmentid'])];
    }
    if ($this->_request['parta']) {
      $where[] = ['=', 'd.parta', $this->_request['parta']];
    }
    if ($this->_request['contractids']) {
      $where[] = ['in', 'd.contractid', explode(',',$this->_request['contractids'])];
    }
    if ($this->_request['signdeptid']) {
      $where[] = new Expression("d.contractid in (select id from ".FzrbsContract::tableName()." where signdeptid in (".$this->_request['signdeptid']."))");
    }
    // urgeserial like 查询
    if ($this->_request['urgeserial']) {
      $where[] = ['like', 'd.urgeserial', $this->_request['urgeserial']];
    }

    // 账龄过滤（单位：年）
    if ($this->_request['debtage_start'] !== null && $this->_request['debtage_start'] !== '') {
      $debtageStartYears = intval($this->_request['debtage_start']);
      if ($debtageStartYears<5){//5年以下的查询指定账龄内的数据
        $where[] = new Expression("co.overdue <= " . ($debtageStartYears * 365));
      }else{
        $where[] = new Expression("co.overdue >= " . ($debtageStartYears * 365));
      }
      
    }
   
   
    
  
    $model = FzrbsContractDebturge::find()->alias('d')->select('d.*,c.title as title,c.signdate,c.amount as contractamount,c.creatorname,c.serial as contractserial,c.partaname as contractpartaname,c.parta as parta,d.serial as urgeserial,u.name,t.name as department,co.overdue as age,(c.amount-co.paycollection) as debt')
      ->leftJoin(['c'=>FzrbsContract::tableName()],'c.id=d.contractid')
      ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=d.urgeuserid')
      ->leftJoin(['t'=>WeixinOaDepartment::tableName()],'t.id=d.urgedepartmentid')
      ->rightJoin(['co'=>"(select id,overdue,ifnull(paycollection,0) as paycollection from (SELECT cc.id,cc.title,cc.amount,p.rate,p.date as paydate,pc.paycollection, IFNULL(pc.paycollection/cc.amount,0) as payratio,DATEDIFF(NOW(),date) as overdue  from fzrbs_contract_paycondition p left join fzrbs_contract cc on p.contractid=cc.id and cc.state=0 LEFT JOIN (SELECT contractid,sum(amount) as paycollection from fzrbs_contract_paycollection WHERE valid=1 and state in(1,3)  GROUP BY contractid) pc on pc.contractid=p.contractid order by p.date desc  ) a where overdue>0  group by id)"],'co.id=d.contractid')
      ->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($order)->asArray()->all();
    

    $result["current"] = $page;
    $result["pageSize"] = $limit;
    $result["total"] = $total;
    $result['data'] = $res;
    return $result;
   }
   public function actionNocontractdebtlist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);

    $order = 'd.id desc';

    $userid = $this->_adminInfo['wxuserid'];

    $where = [
        'and',
        ['>', 'd.id', 0],new Expression("contractid is null")
        // ,['or',['=','d.creator',$userid],['=','d.urgeuserid',$userid]]
    ];

    if ($this->_request['orderby']) {
      $order=$this->_request['orderby'];
      // signdate 替换成 overduedate
      $order = str_replace('signdate', 'overduedate', $order);
      $order = str_replace('debt', 'debtamount', $order);
      $order = str_replace('creator', 'urgeuserid', $order);
      
    }

    if ($this->_request['creator']) {
      $where[] = ['=', 'd.urgeuserid', $this->_request['creator']];
    }
    if ($this->_request['departmentid']) {
      $where[] = ['in', 'd.urgedepartmentid', explode(',',$this->_request['departmentid'])];
    }
    if ($this->_request['parta']) {
      $where[] = ['=', 'd.parta', $this->_request['parta']];
    }

     
  
  
    $model = FzrbsContractDebturge::find()->alias('d')->select('d.*,d.serial as urgeserial,DATEDIFF(NOW(),`overduedate`) as age,u.name,t.name as department')
      ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=d.urgeuserid')
      ->leftJoin(['t'=>WeixinOaDepartment::tableName()],'t.id=d.urgedepartmentid')
      ->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($order)->asArray()->all();
    

    $result["current"] = $page;
    $result["pageSize"] = $limit;
    $result["total"] = $total;
    $result['data'] = $res;
    return $result;
  }

  // 催收日志分页列表
  public function actionUrgelogslist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $userid = $this->_adminInfo['wxuserid'];
    $userDeptId = $this->userinfo['departmentid'];
    

    $order = 'l.id desc';

    $where = [
        'and',
        ['>', 'l.id', 0]
    ];

    // 权限控制：创建人可见自己所有数据
    $permissionWhere = ['=', 'l.creator', $userid];
    
    // 按urgetype和部门角色判断可见数据
    // urgetype=3/6: 对应部门会计可见
    $accountantDeptIds = $this->getRoleDepts('会计', $userid);
    if (!empty($accountantDeptIds)) {
        $permissionWhere = ['or', 
            $permissionWhere,
            ['and', 
                ['in', 'l.urgetype', [3,6]],
                ['in', 'c.departmentid', $accountantDeptIds]
            ]
        ];
    }
    
    // urgetype=4/5: 对应部门经审小组-法务可见
    $legalDeptIds = $this->getRoleDepts('经审小组-法务', $userid);
    if (!empty($legalDeptIds)) {
        $permissionWhere = ['or', 
            $permissionWhere,
            ['and', 
                ['in', 'l.urgetype', [4,5]],
                ['in', 'c.departmentid', $legalDeptIds]
            ]
        ];
    }
    
    $where[] = $permissionWhere;

    if ($this->_request['type']) {
        $where[] = ['=', 'l.type', $this->_request['type']];
    }
    // 搜索条件
    if ($this->_request['contractids']) {
        $where[] = ['in', 'l.contractid', explode(',',$this->_request['contractids'])];
    }
    if ($this->_request['debturgeid']) {
        $where[] = ['=', 'l.debturgeid', $this->_request['debturgeid']];
    }
    if (isset($this->_request['type']) && $this->_request['type'] !== '') {
        $where[] = ['=', 'l.type', $this->_request['type']];
    }
    if ($this->_request['creator']) {
        $where[] = ['=', 'l.creator', $this->_request['creator']];
    }
    if ($this->_request['urgetype']) {
        $where[] = ['=', 'l.urgetype', $this->_request['urgetype']];
    }
    if ($this->_request['urgeresult']) {
        $where[] = new Expression("l.urgeresult in (".$this->_request['urgeresult'].")");
    }

    $model = FzrbsContractDebturgeLog::find()->alias('l')
        ->select('l.*,u.name as creatorname,u.avatar,d.label as urgetypename,d2.label as urgeresultname,c.title as contracttitle,urge.serial as serial')
        ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=l.creator')
        ->leftJoin(['d'=>FzrbsBudgetDict::tableName()],'d.value=l.urgetype and d.type="清欠方式"')
        ->leftJoin(['d2'=>FzrbsBudgetDict::tableName()],'d2.value=l.urgeresult and d2.type="清欠结果"')
        ->leftJoin(['c'=>FzrbsContract::tableName()],'c.id=l.contractid')
        ->leftJoin(['urge'=>FzrbsContractDebturge::tableName()],'urge.id=l.debturgeid')
        ->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($order)->asArray()->all();
   
    $result["current"] = $page;
    $result["pageSize"] = $limit;
    $result["total"] = $total;
    $result['data'] = $res;
    return $result;
  }

  public function actionDebtlist(){
    $userid = $this->_adminInfo['wxuserid'];
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;
    $offset = $limit * ($page - 1);
    $order = 'id desc';
    
    $where = $this->getWhere();
    
    $dsql = "(FIND_IN_SET('$userid',notifiers) or creator='$userid')";
    // 查询逾期
    $where[] = new Expression('c.id in ('.$this->getoverduesql().')');
    $where[] =['>','c.amount',0];
    $where[] =new Expression('c.urgestate!=6'); // 清欠方式为坏账核销的不显示
    $payratiosql="and payratio*100<rate";
    $urgesql = "";
    if ($this->_request['searchtype']){
      switch ($this->_request['searchtype']) {
        case '待审批':
          
          // 修改：使用FIND_IN_SET检查该合同是否有待审批的催收记录
          $where[] = new Expression("EXISTS (
              SELECT 1 
              FROM " . FzrbsContractDebturge::tableName() . " u 
              WHERE FIND_IN_SET(u.id, c.debturgeid) > 0
                AND u.state = 1
          )");
          break;
        case '催收中':
          // 修改：使用FIND_IN_SET检查该合同是否有催收中的记录
          $where[] = new Expression("EXISTS (
              SELECT 1 
              FROM " . FzrbsContractDebturge::tableName() . " u 
              WHERE FIND_IN_SET(u.id, c.debturgeid) > 0
                AND u.state = 2
          )");
          break;
        case '待处置':
          // 修改：使用FIND_IN_SET检查是否有待处置的催收记录
          $where[] = new Expression("EXISTS (
              SELECT 1 
              FROM " . FzrbsContractDebturge::tableName() . " u 
              WHERE FIND_IN_SET(u.id, c.debturgeid) > 0
                AND u.state = 5
          )");
          break;
        case '应催收':
          $where[] = new Expression("NOT EXISTS (
              SELECT 1 
              FROM " . FzrbsContractDebturge::tableName() . " u 
              WHERE FIND_IN_SET(u.id, c.debturgeid) > 0
                AND u.state IN (1, 2)
          )");
          break;
        case '已处置':
          $where[] = ['=', 'c.hasdeal', 1];
          break;
        case '无合同':
          return $this->actionNocontractdebtlist();
        
         
        default:
          # code...
          break;
      }
    }
    // 修改：获取该合同的所有催收记录，催收编号和thirdNo都用逗号分割，新的在前面
    $urgeTable = "select contractid,contactor,mobile,address,GROUP_CONCAT(DISTINCT serial ORDER BY id DESC SEPARATOR ',') as serial,GROUP_CONCAT(DISTINCT thirdNo ORDER BY id DESC SEPARATOR ',') as thirdNo from ".FzrbsContractDebturge::tableName()." group by contractid";
    $urgeSelect = "urge.serial as urgeserial,urge.thirdNo";
    $urgeLogTable = "";
    $paycollectionSql="";
    $table=$this->_request['table'];
    
    if ($table){
      switch ($table) {
        case '催收记录':
          return $this->actionUrgelist();
        case '账销案全表':
          
          $where[]  = new Expression("c.recoverable=1");
          break;
        case '逾期明细表':
          // serial 不能为空
          $payratiosql="";
          $where[] = ['!=', 'c.hasdeal', 1];
          $urgesql.= " and serial is not null";
          $datestart= $this->_request['datestart'];
          $dateend= $this->_request['dateend'];
          // datestart 和 dateend 类似 2020-01，希望datestart转化成2020-01-01，dateend转化成下个月1日
          $datestart = $datestart?$datestart.'-01':'';
          // dateend转化成下个月1日
          $urgeLogSql="log.id>0";
          $dateend = $dateend ? date('Y-m-01', strtotime($dateend . '-01 +1 month')) : '';

          if ($datestart) {
            $where[] = ['>=', 'c.starttime', $datestart];
          }
          if ($dateend) {
            $where[] = ['<=', 'c.endtime', $dateend];
          }
        
          $urgeLogTable = "SELECT contractid, GROUP_CONCAT(CONCAT(DATE_FORMAT(date, '%Y-%m-%d'), ' ', d1.label) ORDER BY date SEPARATOR ' || ') AS urgetype_info, GROUP_CONCAT(CONCAT(DATE_FORMAT(dealdate, '%Y-%m-%d'), ' ', d2.label) ORDER BY date SEPARATOR ' || ') AS urgeresult_info FROM fzrbs_contract_debturge_log log LEFT JOIN fzrbs_budget_dict d1 ON d1.value = log.urgetype AND d1.type = '清欠方式' LEFT JOIN fzrbs_budget_dict d2 ON d2.value = log.urgeresult AND d2.type = '清欠结果' where $urgeLogSql GROUP BY contractid ";
          $urgeSelect.=",urgeLog.urgetype_info,urgeLog.urgeresult_info,urge.contactor,urge.mobile,urge.address";
          break;
        default:
          # code...
          break;
      }
    }
    // 合同编号
    if ($this->_request['contractserial']) {
      $where[] = ['or',['like','c.serial',$this->_request['contractserial']],['like','c.title',$this->_request['contractserial']]];
    }
    // 催收编号
    if ($this->_request['urgeserial']) {
      $where[] = ['like','urge.serial',$this->_request['urgeserial']];
    }
    if ($this->_request['keyword']) {
      $where[] = ['or',['like','c.title',$this->_request['keyword']],['like','c.serial',$this->_request['keyword']],['=','c.creatorname',$this->_request['keyword']]];
    }
    // 根据签订年份进行搜索
    if ($this->_request['signdate']) {
      $where[] = new Expression("date_format(c.signdate,'%Y')='".$this->_request['signdate']."'");
    }
    if ($this->_request['invoicedate']) {
      $where[] = new Expression("c.id in (select contractid from ".FzrbsInvoicing::tableName()." where date_format(date,'%Y')='".$this->_request['invoicedate']."')");
    }
    
    // 查询处理中的
    if ($this->_request['urgestate']) {
      $urgestate = $this->_request['urgestate'];
      $dsql.="  and state=$urgestate";
      if ($dsql){
        $where[] = new Expression("c.id in (select contractid from ".FzrbsContractDebturge::tableName()." where $dsql)");
      }
    }

    if ($this->_request['urgetype']) {
      $urgesql.= " and urgetype=".$this->_request['urgetype'];
      
    }
    if ($urgesql){
      $where[] = new Expression("c.id in (select contractid from ".FzrbsContractDebturge::tableName()." where id>0 $urgesql)");
    }


    $select = "c.*,u.name,d2.label as typename,$urgeSelect,invoicing.date as invoicedate,invoice.invoiceamount as invoiceamount";
    
    
    
    
    $temp = FzrbsContract::find()->alias('c')
    ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=c.creator')
    ->leftJoin(['invoicing'=>FzrbsInvoicing::tableName()],'invoicing.contractid=c.id')
    ->leftJoin(['invoice'=>"(select contractid,ifnull(sum(realamount),0) as invoiceamount from ".FzrbsInvoicingInvoice::tableName()." group by contractid)"],'invoice.contractid=c.id')
    ->leftJoin(['urge'=>"($urgeTable)"],"urge.contractid=c.id")
    ->leftJoin(['d2'=>FzrbsBudgetDict::tableName()],'d2.id=c.type');

    if ($urgeLogTable){
      $temp->leftJoin(['urgeLog'=>"($urgeLogTable)"],'urgeLog.contractid=c.id');
    }
    // 根据处理结果查询,此时不管是否逾期
    if ($this->_request['urgeresult']) {
      $where[] = new Expression("c.id in (select contractid from ".FzrbsContractDebturge::tableName()." where urgeresult in (".$this->_request['urgeresult']."))");
    }

    

    if ($urgesql){
      // 账龄过滤（单位：年）
      if ($this->_request['debtage_start'] !== null && $this->_request['debtage_start'] !== '') {
        $debtageStartYears = intval($this->_request['debtage_start']);
        if ($debtageStartYears<5){//5年以下的查询指定账龄内的数据
          $where[] = new Expression("co.overdue <= " . ($debtageStartYears * 365));
        }else{
          $where[] = new Expression("co.overdue >= " . ($debtageStartYears * 365));
        }
        
      }
   
      // 逾期
      $order = 'co.overdue desc,'.$order;
      $select .= ",co.overdue as age,(c.amount-co.paycollection) as debt,co.paycollection as paycollection,c.department";
      $temp->rightJoin(['co'=>"(select id,overdue,ifnull(paycollection,0) as paycollection from (SELECT cc.id,cc.title,cc.amount,p.rate,p.date as paydate,pc.paycollection, IFNULL(pc.paycollection/cc.amount,0) as payratio,DATEDIFF(NOW(),date) as overdue  from fzrbs_contract_paycondition p left join fzrbs_contract cc on p.contractid=cc.id and cc.state=0 LEFT JOIN (SELECT contractid,sum(amount) as paycollection from fzrbs_contract_paycollection WHERE valid=1 and state in(1,3) $paycollectionSql GROUP BY contractid) pc on pc.contractid=p.contractid order by p.date desc  ) a where overdue>0  $payratiosql  group by id)"],'co.id=c.id');

      $tempForOverdue = clone $temp;
      $tempForOverdue->where($where);
      $totalAmount = $tempForOverdue->sum('c.amount');
      $totalReceived = $tempForOverdue->sum('c.paycollection');

    }

    $temp->where($where);
    $model=$temp->select($select);
    $total = $model->count();
    if ($this->_request['orderby']) {
      $order = $this->_request['orderby'];
    }
    $res = $model->limit($limit)->offset($offset)->orderBy($order)->asArray()->all();
    
    if ($table=='逾期明细表'){
      $res = array_map(function($r){
        $r['urgetype_info'] = $r['urgetype_info']?$r['urgetype_info']:date('Y-m-d',strtotime($r['urgedate']))." ".$r['urgetypename'];
        return $r;
      },$res);
    }


    $stat['totalDebt'] = $totalAmount-$totalReceived;
    $stat['totalReceived'] = $totalReceived;
    $stat['totalAmount'] = $totalAmount;

    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    $this->_result['summary'] = $stat;


    return $this->_result;
  }
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
        if ($flow['NotifyNodes']['NotifyNode']){
          foreach ($flow['NotifyNodes']['NotifyNode'] as $r) {
              $notifier[] = $r['ItemName'];
          }
        }
        $step = intval($flow['approverstep'])-1;
    }


    if($this->_request['print']){
        $approvalNode= $flow['ApprovalNodes']['ApprovalNode'];
        $printinfo = $this->getPrintInfo($approvalNode,true,$obj)['flowdata'];
    }
    
    return  array('viewdata'=>array('step'=>$step,'approval'=>$approvaldata,'notify'=>$notifier,'templateid'=>$flow['OpenTemplateId']),'statusCn'=>$this->statusCn,'printinfo'=>$printinfo);
	}

   /**
   * 获取企业微信用户信息
   */
  private function getUserinfo($userid)
  {
      $userinfo = WeixinOAUserInfo::find()->where(['=', 'userid', $userid])->asArray()->one();
      return $userinfo;
  }
  private function send($approvalUserid,$title,$data,$tab){
    $url = "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/contract/index&tab=$tab";
  

    if (!$approvalUserid) return;
    $msgdata = [
      'touser' => $approvalUserid,
      'msgtype' => 'textcard',
      'agentid' => $this->agentid,
      'textcard' => [
          'title' => $title,
          'description' => '<div></div>',
          'url' => $url,
          'btntxt' => '详情'
          
      ]
    ];
    
    $this->sendmsg($msgdata);
    
   
    
  }
  private function sendmsg($data)
  {
      // $content = '【合同管理】您有一条新的审批消息，请登录掌上福州查看';
      // WxQyhJk::sendMessage($data['agentid'],$data['touser'],$content,'text');
     
      WxQyhJk::sendMessage($data['agentid'],$data['touser'],$data['textcard'],'textcard');
  }
 
}