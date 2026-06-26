<?php

namespace app\modules\api\controllers;
use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WorkflowParse;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\models\FzrbsBudgetBalance;
use app\modules\api\models\FzrbsBudgetDict;
use app\modules\api\models\FzrbsBudgetHistory;
use app\modules\api\models\FzrbsBudgetProject;
use app\modules\api\models\FzrbsCompany;
use app\modules\api\models\FzrbsContract;
use app\modules\api\models\FzrbsContractInvoice;
use app\modules\api\models\FzrbsContractPaycollection;
use app\modules\api\models\FzrbsContractPaycondition;
use app\modules\api\models\FzrbsInvoice;
use app\modules\api\models\FzrbsInvoiceItem;
use app\modules\api\models\FzrbsInvoicing;
use app\modules\api\models\FzrbsInvoicingInvoice;
use app\modules\api\models\FzrbsInvoicingInvoicer;
use app\modules\api\models\FzrbsInvoicingItem;
use app\modules\api\models\FzrbsInvoicingTemplate;
use app\modules\api\models\WeixinFinanceCompany;
use app\modules\api\models\WeixinOaApprovaldata;
use app\modules\api\models\WeixinOaApprovalInfo;
use app\modules\api\models\WeixinOaApprovalLog;
use app\modules\api\models\WeixinOaAttachment;
use app\modules\api\models\WeixinOaDepartment;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinOaRole;
use app\modules\api\models\WeixinOaTemplates;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\models\WeixinOauserTaguser;
use app\modules\weixin\Weixin;
use Exception;
use Faker\Provider\ar_EG\Payment;
use Swift_Attachment;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use yii\db\Expression;
use yii\swiftmailer\Mailer;
use linslin\yii2\curl;
class InvoicingController extends ApiBase{
  public $modelClass = 'app\modules\api\models\FzrbsInvoicing';
  protected $statusCn = array('','审批中','已同意','已驳回','已取消');
  protected $agentId = 1000085;
  protected $userinfo = array();
  protected $INCOME_DICID = 15;
  protected $EXPEND_DICID = 16;
  protected $CONTRACT_FRAME = 1; // 框架合同
  protected $CONTRACT_STANDER = 2; // 标准合同
  protected $CONTRACT_NONE = 3; // 无合同
  protected $STATES_INVOICED=1;//开票
  protected $STATES_DELETED=2;//作废
  protected $STATES_WAITDELETED=6;//待作废
  public function init()
  {
      parent::init();
      $this->userinfo = $this->getUserinfo($this->_adminInfo['wxuserid']);
  }

  // 

  private function getDepts(){
    
    
    
    $power = '查看';
    $userid = $this->_adminInfo['wxuserid'];
  
    
    $arr = array();

    // 领导可以查看下属部门所有的合同
    $deptid = $this->userinfo['departmentid'];
    if ($this->userinfo['is_leader']){
      $depts = WeixinOaDepartment::findBySql("SELECT GROUP_CONCAT(id SEPARATOR ',') as ids from weixin_oa_department where id=$deptid or FIND_IN_SET($deptid,parentids)")->asArray()->one();
      if ($depts['ids']) {
        $arr = array_merge($arr,explode(',',$depts['ids']));
      }
    }
    
    // 查询角色

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
  private function canUpdate($data){
    
    if (!$data)return '开票信息不存在';
    if ($data['state']==$this->STATES_DELETED) return '已作废，禁止操作';
    if ($this->_adminInfo['usertype']==1) return 0;
    if ($data['creator']!=$this->_adminInfo['wxuserid']){
      return '只有本人才可以操作'; 
    }
    return 0;
  } 

  private function getBselect(){
    return "sum(CASE WHEN b.type=$this->INCOME_DICID THEN b.budget ELSE 0 END) AS `budgetincome`, SUM(CASE WHEN b.type=$this->EXPEND_DICID THEN b.budget ELSE 0 END) AS `budgetexpend`, sum(CASE WHEN b.type=$this->INCOME_DICID THEN b.final ELSE 0 END) AS `finalincome`, SUM(CASE WHEN b.type=$this->EXPEND_DICID THEN b.final ELSE 0 END) AS `finalexpend`";
  }
  public function actionGettabs(){
  return array('data'=>[
          array(
            'name'=>'我要申请',
            // 'icon'=>'https://fastly.jsdelivr.net/npm/@vant/assets/user-inactive.png',
            // 'iconActive'=>'https://fastly.jsdelivr.net/npm/@vant/assets/user-active.png',
            'route'=>'/invoice/add'

          ),
          array(
            'name'=>'申请列表',
            'route'=>'/invoice/index?tab=0'
          ),
          // array(
          //   'name'=>'我的申请',
          //   'route'=>'/invoice/index?tab=3'
          // )
          ],'activeTab'=>1);
}
  public function actionGetprintinfo(){
    $ids=$this->_request['ids'];
    if (!$ids)return array('errorMessage'=>'ids不能为空');
    $datas=FzrbsInvoicing::find()->alias('i')->select("i.*,u.name,d.name as department")
      ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'i.creator=u.userid')
      ->leftJoin(['d'=>WeixinOaDepartment::tableName()],'d.id=i.departmentid')
      ->where(['and',['in','i.id',explode(',',$ids)]])->asArray()->all();
    if (!$datas)return array('errorMessage'=>'开票信息不存在,请刷新页面');
    for ($i=0; $i < count($datas); $i++) { 
      $datas[$i]['date']=substr($datas[$i]['inserttime'],0,10);
      // 如果删除了
      if($datas[$i]['deldate']) $datas[$i]['amount'] = -$datas[$i]['amount'];

      // 查询关联的开票项目
      $items = FzrbsInvoicingItem::find()->where(['invoicingid'=>$datas[$i]['id']])->asArray()->all();
      // 遍历items
      $tempItemArr=[];
      foreach  ($items as $item) { 
        
        $tempItemArr[]=$item['title']."：".$item['amount'].'元 '.($item['unit']&&$item['number']?" ".$item['number'].$item['unit']:'');
      }
      $datas[$i]['title']=implode("、", $tempItemArr);

      if ($datas[$i]['contractid']){//有合同
        
        // 合同名称
        $contracts = FzrbsContract::find()->select("title,serial")->where(new Expression("id in (".$datas[$i]['contractid'].")"))->asArray()->all();
        // 拼接title和serial,并用空格分割
        $ctemp=[];
        foreach ($contracts as $contract) {
      
          $ctemp[]=$contract['serial'].' '.$contract['title'];
        }
        $datas[$i]['contractnames'] = implode('、',$ctemp);
      
      }else{
        

        $datas[$i]['contractnames'] = $datas[$i]['contract']!=3?'未签':'无合同';
      }
      // 获取项目名称
      if ($datas[$i]['projectids']){
        $projects = FzrbsBudgetProject::find()->select("GROUP_CONCAT(title) as title")->where(new Expression("id in (".$datas[$i]['projectids'].")"))->asArray()->one();
        $datas[$i]['projectnames'] = $projects['title'];
      }
     
      // 查询专票客户信息
      $company = json_decode($datas[$i]['customer'],true);
      
      if ($company) {
        $temp=[];
        $temp[]=$company['bankaccount'];
        $temp[]=$company['address'];
        $temp[]=$company['contacts'];

        $temp=array_filter($temp);
        
        $datas[$i]['buyerinfo'] = implode(';',$temp);

        // 客户信息
        $datas[$i]['bueryid']=$company['code'];

      }
      
      
      // 查询审批人
      $info = WeixinOaApprovalInfo::find()->where(['and',['=','agentId',$this->agentId],new Expression("data like '%\"infoid\":".$datas[$i]['id']."%'")])->orderBy('id desc')->one();
    
     $datas[$i]['partaname'] = $datas[$i]['receiver']?$datas[$i]['receiver']:$datas[$i]['partaname'];
     

      if($info) {
        $datas[$i]['thirdNo']=$info['thirdNo'];
        $approveres = WeixinOaApprovaldata::find()->where(['and',["=","agentid",$this->agentId],["=","thirdNo",$info['thirdNo']]])->one();
        if($approveres){
          $temparr = [];
          $approvearr = json_decode($approveres['data'],true);
          foreach ($approvearr['data']['ApprovalNodes']['ApprovalNode'] as $node) {
            $items = $node['Items']['Item'];
            $roleid = $node['NodeRoleid'];
            
            $ttt = $this->getApproverAndDate($items);
            if ($ttt){
              if ($roleid) {
                $role=WeixinOaRole::find()->where(['id'=>$roleid])->one();
                if  ($role) {
                  $ttt = $role['rolename'].'：'.$ttt;
                }
              }
              $ele = explode(';',$ttt);
              if (count($ele)) {
                foreach ($ele as $e) {
                  
                  $temparr[] = $e;
                  
                }
              }
            }

          }
          // 去掉重复的审批人,保留后面的
          
          if ($temparr){
            $tempstr = '';
  
            for ($ij=sizeof($temparr)-1; $ij>-1; $ij--) {
              $arr = explode(' ',$temparr[$ij]);
              if ($tempstr&&$arr[0]&&strpos($tempstr,$arr[0])>-1){ // 有重复
              }else{
                $tempstr.=';'.$temparr[$ij];
              }
            }
            $approvers = $tempstr?substr($tempstr,1):'';
            if ($approvers&&$datas[$i]) $datas[$i]['approvers']=$approvers;
          }
    
        }
      }
     
      
      
      
      
      
    }
    return array('data'=>$datas);
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
  public function actionGetcontractsbykeyword(){
    $where = [
      'and',
      ['>', 'c.id', 0],['!=','c.state',4]
    ];
    if ($this->_request['type']){
      $where[]=['=','type',$this->_request['type']];
    }
    $keyword = $this->_request['keyword'];
    $limit = $this->_request['limit'];
 
    if ($keyword){
      if (strpos($keyword, ',')!==false){
        $keyword = explode(',', $keyword);
        $where[] = ['in', 'id', $keyword];
      }else{
        $where[] = ['or',['like', 'title', $keyword],['=', 'id', $keyword],['=', 'serial', $keyword],['=', 'deptserial', $keyword]];
      }
    }else{
      return [];
    }
    
    if (!isset($limit)) {
      $limit = 10;
    }

    $model = FzrbsContract::find()->alias('c')->where($where)->orderBy('id desc')->limit($limit)->asArray()->all();
    return $model;
  }
  public function actionGetcontracts(){
    if ($this->_request['keyword']) return $this->actionGetcontractsbykeyword();
    $ids = $this->_request['ids'];
    if (!$ids)return array('errorMessage'=>'ids不能为空');
    $contracts = FzrbsContract::find()->alias('c')
        ->select("c.id,c.fileurls,c.title,c.serial,c.partaname,c.amount,IFNULL(invoice.count,0) as `count`,IFNULL(invoice.realinvoiceamount,0) as `invoiceamount`,(c.amount-invoice.realinvoiceamount) as invoicableamount")
        ->join('left JOIN',['invoice'=>"(select contractid,count(contractid) as count,sum(amount) as invoiceamount,sum(realamount) as realinvoiceamount from ".FzrbsInvoicingInvoice::tableName()." where contractid in ($ids) and invoiceno!=NULL group by contractid)"],'invoice.contractid=c.id')
        ->where(['in','c.id',explode(',',$ids)])
        ->asArray()
        ->all();
        if ($contracts) {
          $result=$contracts;
        }else{
          $result=array();
        }
    return $result;
  }
  public function getFileurlByids($ids){
    // 判断是否仅包含数字和
    if(!preg_match('/^\d+(,\d+)*$/', $ids)){
      return $ids;
    }
    $datas = WeixinOaAttachment::find()->where(['in','id',explode(',',$ids)])->all();
    
    $datas = array_map(function($e){
      $url = $e->savePath;
      return $url."?name=".urlencode($e->baseName)."&time=".((strtotime($e->inserttime))*1000)."&size=".$e->fileSize;
    },$datas);
    return implode(',',$datas);

  }
  public function actionGetbyid(){
    $id = $this->_request['id'];
    if (!$id&&$this->_request['invoicingid']) $id = $this->_request['invoicingid'];
    $result=array();
    $data = FzrbsInvoicing::find()->alias('p')->select('p.*,d2.label as contracttypename,d.name as department,inv.invoiceids as invoiceids,inv.publication')
      ->leftJoin(['d'=>WeixinOaDepartment::tableName()],'d.id=p.departmentid')
      ->leftJoin(['d2'=>FzrbsBudgetDict::tableName()],"d2.value=p.contract and d2.type='合同业务类型'")
      ->leftJoin(['inv'=>"(SELECT invoicingid,publication,GROUP_CONCAT(id) as invoiceids from ".FzrbsInvoice::tableName()." GROUP BY invoicingid)"],'inv.invoicingid=p.id')
      ->where(['p.id'=>$id])->asArray()->one();

    if (!$data) return array('errorMessage'=>'开票申请不存在');

    $departmentid = $data['departmentid'];
    // 查询开票项目
    $items = FzrbsInvoicingItem::find()
    ->where(['invoicingid'=>$id])
    ->asArray()
    ->all();
    $data['items']=$items;
    // 查询关联的合同
    $contract = FzrbsContract::find()->select('id,title,parta,partaname,partb,partbname')->where(['id'=>$data['contractid']])->asArray()->one();
    if ($contract) {
      $data['contracts']=$contract;
      $data['contractnames'] = $contract['title'];
    }
    
    
    // 查询关联的项目
    $projects = FzrbsBudgetProject::find()->select('id,title')->where(['in','id',explode(',',$data['projectids'])])->asArray()->all();
    if ($projects) {
      $data['projects']=$projects;
    }
    if ($this->_request['show']=='all'){
      
      
      // 合同
      if ($data['contractid']){
        $contracts = FzrbsContract::find()->alias('c')
        ->select("c.id,c.serial,c.partaname,c.amount,IFNULL(invoice.count,0) as `count`,IFNULL(invoice.realinvoiceamount,0) as `realinvoiceamount`,(c.amount-IFNULL(invoice.realinvoiceamount, 0 )) as `invoicableamount`")
        ->join('left JOIN',['invoice'=>"(select contractid,count(contractid) as count,sum(amount) as invoiceamount,sum(realamount) as realinvoiceamount from ".FzrbsInvoicingInvoice::tableName()." where realamount!=0 and invoicingid=$id group by contractid)"],'invoice.contractid=c.id')
        ->where(['in','c.id',explode(',',$data['contractid'])])
        ->asArray()
        ->all();
        if ($contracts) {
          $result['contracts']=$contracts;
        }else{
          $result['contracts']=array();
        }
      }
      
      // 项目
      if ($data['projectids']){
        $projects = FzrbsBudgetProject::find()->alias('p')
        ->select("p.*,u.name,".$this->getBselect().",e.incomeinvoiceamount,e.expendinvoiceamount")
        ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'p.creator=u.userid')
        ->join('LEFT OUTER JOIN',['b'=>FzrbsBudgetBalance::tableName()],'b.projectid=p.id')
        ->join('LEFT OUTER JOIN',['e'=>"(SELECT projectid,sum(CASE WHEN e.type=$this->INCOME_DICID THEN e.amount ELSE 0 END) AS `incomeinvoiceamount`, SUM(CASE WHEN e.type=$this->EXPEND_DICID THEN e.amount ELSE 0 END) AS `expendinvoiceamount` from fzrbs_budget_enteraccount e where e.state=1 GROUP BY projectid)"],'e.projectid=p.id')
        ->where(['in','p.id',explode(',',$data['projectids'])])
        ->groupBy('p.id,u.name,e.incomeinvoiceamount,e.expendinvoiceamount')
        ->asArray()
        ->all();
        if ($projects) {
          $result['projects']=$projects;
        }else{
          $result['projects']=array();
        }
      }
    }
    $result['data']=$data;
    // 是否是开票员
    
    $errMsg=$this->isNotInvoicer($data['partb'],$departmentid);
    if (!$errMsg) {
      $result['isinvoicer']=true;
    }
    return $result;
  }
  public function actionSavecompany(){
   
 
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }

    // 判断是否已经存在
    if (!$obj['company']) return array('errorMessage'=>'company不能为空');

    
    try {
      $res = FzrbsCompany::find()->where(['and',['=','company',$obj['company']]])->one();
      if ($res) return array('errorMessage'=>"[".$obj['company']."]已经存在");
      $obj = new FzrbsCompany($obj);
      $obj->company = trim($obj->company);
      $obj->creator=$this->_adminInfo['wxuserid'];
      $obj->save();
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    
    return array('data'=>$obj);
  }
  public function actionAddcontract(){
    $obj = $this->_request;
    $id = $obj['id'];
    $contractid = $obj['contractid'];
    if (!$contractid)return array('errorMessage'=>'合同ID不能为空');
    $d = FzrbsInvoicing::findOne($id);

 

    $errorMessage = $this->canUpdate($d);
    if ($errorMessage)return array('errorMessage'=>$errorMessage);

    // 判断fzrbs_invoicing_invoice表,是否有该合同的信息，没有则关联，有则更新
    $invoicingInvoice = FzrbsInvoicingInvoice::find()->where(['=','invoicingid',$id])->one();
    // 已上传发票且已经关联合同的，禁止删除和修改关联合同

    if($invoicingInvoice&&$invoicingInvoice->contractid&&$invoicingInvoice->invoiceno){
      $tc = FzrbsContract::findOne($invoicingInvoice->contractid);
      if ($tc) {
        return array('errorMessage'=>'已上传发票且已关联合同【'.$tc['title'].'】，禁止删除和修改关联合同');
      }
    }

    $c = FzrbsContract::findOne($contractid);
    // 合同付款名称可能是多个名称并用逗号隔开，只要其中一个与开票信息客户名称一致，则通过
    $contractPartaNames = array_map('trim', explode(',', $c['partaname']));
    if (!in_array(trim($d['partaname']), $contractPartaNames)) {
      return array('errorMessage'=>'合同付款方名称【'.$c['partaname'].'】与开票信息客户名称【'.$d['partaname'].'】不一致');
    }


    $d->contractid = $contractid;
    // 开启事务
    $transaction = Yii::$app->getDb()->beginTransaction();
    try{
          
      $d->save();
      
      if (!$invoicingInvoice) {
        $invoicingInvoice = new FzrbsInvoicingInvoice(array('invoicingid'=>$id,'contractid'=>$contractid,'amount'=>$d['amount']));
      }else{
        $invoicingInvoice->contractid = $contractid;
      }
      
      $invoicingInvoice->save();

      // 查询所有与发票相关的回款，并更新回款的合同号
      if($invoicingInvoice['invoiceno']){
        $paycollections = FzrbsContractPaycollection::find()->where(['EIid'=>$invoicingInvoice['invoiceno']])->all();
        if($paycollections){
          foreach ($paycollections as $paycollection) {
            $paycollection->contractid = $contractid;
            $paycollection->save();
          }
        }
      }
      
      
      // 判断合同已开票金额是否超出
      $temp = $this->getContractInvoiceAmount($contractid);
      if ($temp['invoiceamount']>$temp['contractamount']) {
        throw new Exception('开票后，合同开票总金额达到【'.$temp['invoiceamount'].'】，超出合同金额【'.$temp['contractamount'].'】，操作失败');
      }
      // 判断开票项目关联合同的开票总额是否超出项目的开票额
      $totalinvoice = FzrbsInvoicingInvoice::find()->where(['=','invoicingid',$id])->sum('amount');
      if ($totalinvoice>$d['amount']){
        throw new Exception('开票后,开票项目关联的合同的开票总金额为【'.$totalinvoice.'】，超过开票项目的开票金额【'.$d['amount'].'】，操作失败');
      }

      // 判断是否有回款
      if ($contractid){
          $temp = FzrbsContractPaycollection::find()->where(['=','EIid',$invoicingInvoice->invoiceno])->one();
     
          if ($temp) {
            $temp->contractid = $contractid;
            $temp->save();
          }
          $total = Yii::$app->db->createCommand("SELECT sum(amount) as amount FROM ".FzrbsContractPaycollection::tableName()." where contractid=".$contractid."  group by contractid")->queryOne();
          Yii::$app->db->createCommand()->update(FzrbsContract::tableName(), ['paycollection' => $total['amount']], ['=', "id", $contractid])->execute();
          // 更新项目已收款
          $this->updateProReceivedWhenPaycheck($contractid);
      }

    }catch (\Exception $e){
      $transaction->rollBack();
      return array('errorMessage'=>$e->getMessage());
    }
    $transaction->commit();
    return array('data'=>'操作成功');
  }
  private function updateProReceivedWhenPaycheck($contractid){
      // 先查询与合同相关的所有项目
      $datas = FzrbsBudgetProject::find()->where("FIND_IN_SET($contractid,contractids)")->all();
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
  // 统计某个合同已经关联发票的金额
  private function getContractInvoiceAmount($contractid){
    $where = ['and',new Expression("c.id=".$contractid)];
    $temp = FzrbsContract::find()->alias('c')
    ->select("c.amount as contractamount")
    ->where($where)
    ->asArray()
    ->one();
    $invoice = FzrbsInvoicingInvoice::findBySql("SELECT sum(TotalTaxIncludedAmount) as amount FROM fzrbs_invoice i WHERE EXISTS (SELECT 1 FROM fzrbs_invoicing_invoice ii WHERE ii.contractid = $contractid AND FIND_IN_SET(i.EIid, ii.invoiceno) > 0)")->asArray()->one();
    $invoiceamount = $invoice?$invoice['amount']:0;
    $temp['invoiceamount']=$invoiceamount;

    return $temp?array("contractamount"=>$temp['contractamount']?$temp['contractamount']:0,"invoiceamount"=>$temp['invoiceamount']?$temp['invoiceamount']:0):array("contractamount"=>0,"invoiceamount"=>0);
  }
  // 撤回发票作废通知
  public function actionCanceldelinvoicingnotice(){
    $obj = $this->_request;
    try {
      $project=FzrbsInvoicing::findOne($obj['id']);
      // 只有经办人有权限撤回作废
      if($this->_adminInfo['wxuserid']!=$project['creator']) {
        return array('errorMessage'=>'只有经办才能撤回作废');
      }

      // 判断是否已经作废
      if ($project['state']==$this->STATES_DELETED) {
        return array('errorMessage'=>'发票已经作废，不能撤回');
      }
      $project->state = $this->STATES_INVOICED;
      $project->save();
      $invoicers = $this->getInoicer(array('company'=>$project['partb'],'dept'=>$project['departmentid']));
      if($invoicers){
        $userids = array_column($invoicers,'userid');
        $this->send(implode('|',$userids),$this->userinfo['name'].'取消了作废，需要继续上传发票',$project);
      }
    } catch (\Throwable $th) {
      return  array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>'操作成功');
  }
  // 发票作废通知
  public function actionDelinvoicingnotice(){
    $obj = $this->_request;
    try {
      $project=FzrbsInvoicing::findOne($obj['id']);
      if($this->_adminInfo['wxuserid']!=$project['creator']) {
        return array('errorMessage'=>'只有经办才能作废');
      }
      $temp = FzrbsInvoicingInvoice::find()->where(['invoicingid'=>$project['id']])->one();
      
      if (!$temp['invoiceno']) {
        $project->state = $this->STATES_DELETED;
        $project->save();
      }else{
        $project->state = 6;
        $project->save();
        $invoicers = $this->getInoicer(array('company'=>$project['partb'],'dept'=>$project['departmentid']));
        if($invoicers){
          $userids = array_column($invoicers,'userid');
          
          $this->send(implode('|',$userids),$this->userinfo['name'].'作废了开票申请，需要上传负数发票',$project);
        }
      }
      
      
    } catch (\Throwable $th) {
      return  array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>'操作成功');
  }
  private function sendMsgToApproverWhenUpdate($invoicing){
   
    if (!$invoicing['thirdNo']) return;
    // 查询已经审批过的所有人并去重
    $tousers = $this->getUserHasApproved($invoicing['thirdNo']);// 查询当前项目是否在审批，哪些人已经审批过了
    
    if (!$tousers){
      $tousers = $invoicing['creator'];
    }else{
      $tousers =$tousers.'|'.$invoicing['creator'];
    }
   
    $this->send($tousers,$this->userinfo['name'].'【更新了】开票申请',$invoicing);

  }
  private function getUserHasApproved($thirdNo){
    $res = WeixinOaApprovalLog::findBySql("select GROUP_CONCAT(userId  SEPARATOR '|') as tousers from  ".WeixinOaApprovalLog::tableName()." where thirdNo='$thirdNo'")->asArray()->one();
    
    return $res?$res['tousers']:'';
  }
  private function getBuyer($buyerName){
      if(!$buyerName) return null;
      $buyerName =str_replace('（个人）','',$buyerName);
      $buyer = Yii::$app->db->createCommand("select id as SYS_DOCUMENTID, company as CUST_NAME from ".FzrbsCompany::tableName()." where company='".$buyerName."' order by id desc")->queryOne();

      if (!$buyer){
        
        Yii::$app->db->createCommand()->insert(FzrbsCompany::tableName(), array('company'=>$buyerName))->execute();
       $buyer = Yii::$app->db->createCommand("select id as SYS_DOCUMENTID, company as CUST_NAME from ".FzrbsCompany::tableName()." where company='".$buyerName."' order by id desc")->queryOne();
      }
      return $buyer;
    }
 
  public function actionSave(){
    $userid = $this->_adminInfo['wxuserid'];
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }
  
    $transaction = Yii::$app->getDb()->beginTransaction();
   
    if($obj['customer']&&!is_string($obj['customer'])){
      $obj['customer'] = json_encode($obj['customer'],true);
    }
   
    unset($obj['contracttypename']);
    unset($obj['contractnames']);
    unset($obj['projects']);
    unset($obj['contracts']);
    unset($obj['isinvoicer']);
    unset($obj['department']);
    unset($obj['invoiceids']);
    try {
      if ($obj['id']){
        
        $old=FzrbsInvoicing::findOne($obj['id']);
        $errorMessage = $this->canUpdate($old);
        if ($errorMessage)return array('errorMessage'=>$errorMessage);
        $amount = $old->amount;
        $items = $obj['items'];
        
        // 更新开票项目
        if ($items&&sizeof($items)){

          $amount = array_sum(array_column($items,'amount'));
          $obj['amount']=$amount;

          if ($items&&sizeof($items)){
            foreach ($items as $item){
              $item['invoicingid']=$obj['id'];
              if ($item['id']){
                FzrbsInvoicingItem::updateAll($item,['id'=>$item['id']]);
              }else{
                $item['invoicingid']=$obj['id'];
                $i = new FzrbsInvoicingItem($item);
                $i->save();
              }
              
            }
          }
        }
   
        // 开票金额必须大于0
        if ($amount<=0) return array('errorMessage'=>'开票金额必须大于0（需要点击【新增】按钮确认添加）');
        unset($obj['items']);
        // 如果合同业务变化，并且变化后值为4，则将发票状态改为待开票
        if ($obj['contract']!=$old['contract']){
          if ($obj['contract']==4){
            $obj['state'] = $this->STATES_INVOICED;
            // 设置开票人
            $invoicers = $this->getInoicer(array('company'=>$obj['partb'],'dept'=>$old['departmentid']));
            if($invoicers){
              $obj['invoicers'] = implode(',',array_column($invoicers,'userid'));
            }else{
              return array('errorMessage'=>'开票单位【'.$obj['partbname'].'】，部门【'.$this->userinfo['department'].'】未设置开票人');
            }
          }else if ($old['contract']==4){
            $obj['state']=0;
           
          }
        }
        // 如果开票单位发生改变，状态为暂存，且处于审批阶段
       
        if ($obj['partb']!=$old['partb']){
          // 设置开票人
         
          $invoicers = $this->getInoicer(array('company'=>$obj['partb'],'dept'=>$old['departmentid']));
          if($invoicers){
            $obj['invoicers'] = implode(',',array_column($invoicers,'userid'));
          }else{
            $t= WeixinOaDepartment::findOne($old['departmentid']);
            return array('errorMessage'=>'开票单位【'.$obj['partbname'].'】，部门【'.$t['name'].'】未设置开票人');
          }
        }
        FzrbsInvoicing::updateAll($obj,['id'=>$obj['id']]);
        // 如果正在审批，则发送通知给当前审批人和经办本人
        $this->sendMsgToApproverWhenUpdate($old);
        // 关联合同
        $invoice = FzrbsInvoicingInvoice::find()->where(['invoicingid'=>$obj['id']])->one();
        if (!$invoice) {
          $invoice = new FzrbsInvoicingInvoice();
          $invoice['invoicingid'] = $obj['id'];
          $invoice['amount'] = $amount;
        }
        if ($obj['contractid']!=$invoice['contractid']){
          $invoice['contractid'] =$obj['contractid'];
        }
        $invoice->save();
        if ($invoicers){
          $userids = implode('|',array_column($invoicers,'userid'));
          $this->send($userids,'开票申请【待开票】',$obj);
        }
      } else {
        if(!$obj['partb']){
          return array('errorMessage'=>'开票单位必填！');
        }
        // 判断是否携带items
        $amount = 0;//开票金额
        $tems= $obj['items'];
        if ($tems&&sizeof($tems)){
          // 合计项目金额
          $amount = array_sum(array_column($tems,'amount'));
        }
        
        $obj['creator']=$userid;
        $obj['amount']=$amount;
        // 删除items字段
        unset($obj['items']);
        // 开票金额必须大于0
        if ($amount<=0) return array('errorMessage'=>'开票金额必须大于0');

        // 如果contract=4，则无需审批，直接到待开票状态
        if ($obj['contract']==4){
          $obj['state'] = $this->STATES_INVOICED;
          // 设置开票人
          $invoicers = $this->getInoicer(array('company'=>$obj['partb'],'dept'=>$this->userinfo['departmentid']));
          if($invoicers){
            $obj['invoicers'] = implode(',',array_column($invoicers,'userid'));
          }else{
            return array('errorMessage'=>'开票单位【'.$obj['partbname'].'】，部门【'.$this->userinfo['department'].'】未设置开票人');
          }

        }
        $c = new FzrbsInvoicing($obj);
        // 设置操作人所在部门
        $c->departmentid = $this->userinfo['departmentid'];
        $c->save();


        if ($invoicers){
          $userids = implode('|',array_column($invoicers,'userid'));
          $this->send($userids,'开票申请【待开票】',$c);
        }
        



        $obj['id']=$c['id'];
        // 保存所有项目
        if ($tems&&sizeof($tems)){
          foreach ($tems as $item){
            $item['invoicingid']=$obj['id'];
            $i = new FzrbsInvoicingItem($item);
            $i->save();
          }
        }
        $invoice = new FzrbsInvoicingInvoice(array('amount'=>$obj['amount'],'invoicingid'=>$obj['id']));
        if ($obj['contractid']){
          $invoice['contractid'] =$obj['contractid'];
        }
        
        $invoice->save();
      }
      
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    $resp['data'] =$obj;
    return $resp;
  }
  public function actionDelinvoicing(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');

    $d = FzrbsInvoicing::findOne($id);

    if(!$d) return array('data'=>'删除成功');
    if($d['state']==2){
      return array('errorMessage'=>'已作废不能删除');
    }
    // 是否已经提交
    if($d['state']!=0){
      return array('errorMessage'=>'已通过开票申请，不能删除');
    }
    
    if($d['thirdNo']){
      return array('errorMessage'=>'正在审批中，不能删除，可以先撤销审批再删除');
    }

 

    // 开启事务
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      // 删除关联的发票
      FzrbsInvoice::deleteAll(['invoicingid'=>$id]);
      FzrbsInvoicingInvoice::deleteAll(['invoicingid'=>$id]);
      // 删除开票信息
      $d->delete();

    }catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage()); 
    }
    $transaction->commit();
    
    return array('data'=>'删除成功');
  }
  private function businesstypes($type){
    if ($type=='新媒体业务'){
      return [

            array(
              'text'=>'广告宣传',
              'value'=>21
            ),
            array(
              'text'=>'代运营',
              'value'=>22
            ),
            array(
              'text'=>'视频制作',
              'value'=>23
            ),
            array(
              'text'=>'设计服务',
              'value'=>24
            ),
            array(
              'text'=>'技术服务',
              'value'=>25
            ),
            array(
              'text'=>'网站运营',
              'value'=>26
            ),
            array(
              'text'=>'其他',
              'value'=>27
            )
   
        
      ];
    }
    // text 用
    return [
      array(
        'text'=>'版面广告',
        'title'=>'版面广告',
        'value'=>1
      ),
      array(
        'text'=>'新媒体业务',
        'title'=>'新媒体业务',
        'value'=>2,
        // 'children'=>[
        //   array(
        //     'text'=>'广告宣传',
        //     'value'=>21
        //   ),
        //   array(
        //     'text'=>'代运营',
        //     'value'=>22
        //   ),
        //   array(
        //     'text'=>'视频制作',
        //     'value'=>23
        //   ),
        //   array(
        //     'text'=>'设计服务',
        //     'value'=>24
        //   ),
        //   array(
        //     'text'=>'技术服务',
        //     'value'=>25
        //   ),
        //   array(
        //     'text'=>'网站运营',
        //     'value'=>26
        //   ),
        //   array(
        //     'text'=>'其他',
        //     'value'=>27
        //   )
        
        // ]
      ),
      array(
        'text'=>'活动项目',
        'value'=>3
      ),
      array(
        'text'=>'销售货物',
        'value'=>4
      ),
      array(
        'text'=>'直接服务',
        'value'=>5
      ),
      array(
        'text'=>'租金',
        'value'=>6
      ),
      array(
        'text'=>'其他',
        'value'=>20
      )
    ];
  }
  public function actionInvoicetypes(){
    return $this->businesstypes($this->_request['type']);

  }
  public function actionGetinvoicelist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;
    $offset = $limit * ($page - 1);

    $where = [
        'and',
        ['>', 'i.id', 0],
    ];

    $order = 'id desc';
    if ($this->_request['orderby']) {
      $order = $this->_request['orderby'];
    }


    if($this->_request['invoicingid']||$this->_adminInfo['usertype']==1){
      // 查询单个开票申请对应的发票时，允许显示全部
    }else{
      $where[] =  ['=' , 'i.creator' , $this->_adminInfo['wxuserid']];
    }
    
    
    if ($this->_request['departmentid']){
      $where[] =  ['in' , 'i.departmentid' , explode(',',$this->_request['departmentid'])];
    }
    if ($this->_request['publication']){
      $where[] =  ['in' , 'i.publication' , explode(',',$this->_request['publication'])];
    }
    if ($this->_request['keyword']) {
      $where[] = ['or',['LIKE', 'i.EIid', $this->_request['keyword']],['LIKE', 'i.BuyerName', $this->_request['keyword']],['LIKE', 'i.SellerName', $this->_request['keyword']]];
    }
    if ($this->_request['EIid']){
      $where[] = ['like', 'i.EIid', $this->_request['EIid']];
    }

    if ($this->_request['RequestTimeStart']){
      $where[] = ['>=', 'i.RequestTime', $this->_request['RequestTimeStart']];
    }
    if ($this->_request['RequestTimeEnd']){
      $where[] = ['<=', 'i.RequestTime', $this->_request['RequestTimeEnd']];
    }
    if($this->_request['invoicingid']){
      $where[] = ['invoicingid'=>$this->_request['invoicingid']];
    }
    if(isset($this->_request['pushed'])){
      $where[] = ['pushed'=>$this->_request['pushed']];
    }
    if($this->_request['seller']){
      $where[] = ['LIKE', 'i.SellerName', $this->_request['seller']];
    }
    if($this->_request['businesstype']){
      if ('新媒体业务'==$this->_request['businesstype']){
        $temp = $this->businesstypes($this->_request['businesstype']);
        $temp = array_column($temp, 'text');
        $temp[]='新媒体业务';
        $where[] = ['in', 'i.businesstype', $temp];
      }else{
        $where[] = ['=', 'i.businesstype', $this->_request['businesstype']];
      }
      
    }

   
    $model = FzrbsInvoice::find()->alias('i')->select('i.*,d.name as department,u.name')
    ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=i.creator')
    ->leftJoin(['d'=>WeixinOaDepartment::tableName()],'d.id=i.departmentid')
    ->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($order)->asArray()->all();


    $stat['total']=$total;
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    

    return $this->_result;
  }
  
  private function updateInvoicingAmount($invoicingid){
    // 更新开票信息的开票总金额
    $total = FzrbsInvoicingItem::find()->where(['invoicingid'=>$invoicingid])->sum('amount');
    $total = $total?$total:0;
    FzrbsInvoicing::updateAll(['amount'=>$total],['id'=>$invoicingid]);
    FzrbsInvoicingInvoice::updateAll(['amount'=>$total],['invoicingid'=>$invoicingid]);

    // 判断合同可开票金额
    $obj = FzrbsInvoicing::findOne($invoicingid);
    if ($obj['contractid']&&$obj['contract']==$this->CONTRACT_STANDER){// 有合同且合同为标准合同
      // 逐个判断合同已经关联的开票金额
      $contract = $this->getContractInvoiceAmount($obj['contractid']);
      
      if ($contract['invoiceamount']>$contract['contractamount']){
        throw new Exception('合同业务为【标准合同】,执行操作后,合同开票总额达到['.$contract['invoiceamount'].']超过合同金额['.$contract['contractamount'].'],操作失败!');
      }
    }
    $this->sendMsgToApproverWhenUpdate($obj);
  }
  public function actionSaveinvoiceitem(){
    $userid = $this->_adminInfo['wxuserid'];
    $obj = $this->_request;
    $transaction = Yii::$app->getDb()->beginTransaction();
   
    $invoicingid = $obj['invoicingid'];
    if (!$invoicingid) return array('errorMessage'=>'invoicingid 不能为空');

    $invoicing = FzrbsInvoicing::find()->alias('i')->select('i.*,inv.invoiceids as invoiceids')
    ->leftJoin(['inv'=>"(SELECT invoicingid,GROUP_CONCAT(id) as invoiceids,sum(TotalTaxIncludedAmount) as realinvoiceamount from ".FzrbsInvoice::tableName()." GROUP BY invoicingid)"],'inv.invoicingid=i.id')
    ->where(['id'=>$invoicingid])->asArray()->one();
    // 如果已经关联发票则禁止更新
    if ($invoicing['invoiceids']) return array('errorMessage'=>'该开票信息已关联发票,不能修改');

    $errorMessage = $this->canUpdate(FzrbsInvoicing::findOne($invoicingid));
    if ($errorMessage)return array('errorMessage'=>$errorMessage);
    try {
      if ($obj['id']){
        FzrbsInvoicingItem::updateAll($obj,['id'=>$obj['id']]);
      } else {

        $obj['creator']=$userid;
        $c = new FzrbsInvoicingItem($obj);
        // 设置操作人所在部门
        $c->departmentid = $this->userinfo['departmentid'];
        $c->save();
        $obj['id']=$c['id'];
      }
      $this->updateInvoicingAmount($invoicingid);
      
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    
    
    $resp['data'] =$obj;
    return $resp;
  }
  public function actionDelinvoiceitem(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    $item = FzrbsInvoicingItem::findOne($id);
    $invoicing = FzrbsInvoicing::find()->alias('i')->select('i.*,inv.invoiceids as invoiceids')
    ->leftJoin(['inv'=>"(SELECT invoicingid,GROUP_CONCAT(id) as invoiceids,sum(TotalTaxIncludedAmount) as realinvoiceamount from ".FzrbsInvoice::tableName()." GROUP BY invoicingid)"],'inv.invoicingid=i.id')
    ->where(['id'=>$item['invoicingid']])->asArray()->one();
    // 如果已经关联发票则禁止更新
    if ($invoicing['invoiceids']) return array('errorMessage'=>'该开票信息已关联发票,不能修改');
    // 判断是否有操作权限
    if (!$this->haspower('发票管理',$this->agentId,$invoicing['departmentid'],$invoicing['creator'])) {
      $dept = WeixinOaDepartment::findOne($invoicing['departmentid']);
      return array('errorMessage'=>'没有部门【'.$dept['name'].'】的【发票管理】权限');
    }
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      $item->delete();
      $this->updateInvoicingAmount($item['invoicingid']);
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
  
    return array('data'=>'删除成功');
  }
  public function actionGetinvoiceitems(){
    $id = $this->_request['id'];
    $invoicingid = $this->_request['invoicingid'];
    if (!$id && !$invoicingid) return array('errorMessage'=>'id 和 invoicingid 不能都为空');
    $where = [
        'and',
        ['>', 'i.id', 0],
    ];
    if ($id){
      $where[] =  ['=' , 'i.id' , $id];
    }
    if ($invoicingid){
      $where[] =  ['=' , 'i.invoicingid' , $invoicingid];
    }
    $items = FzrbsInvoicingItem::find()->alias('i')->select("i.*,d.name as department,u.name")
    ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=i.creator')
    ->leftJoin(['d'=>WeixinOaDepartment::tableName()],'d.id=i.departmentid')
    ->where($where)
    ->asArray()->all();

    return array('data'=>$items);
  }
  public function actionGetstates(){


   return [
    array("value"=>-1,"label"=>"全部"),
    array("value"=>0,"label"=>"暂存"),
    array("value"=>1,"label"=>"已开票"),
    array("value"=>2,"label"=>"已撤销"),
    array("value"=>6,"label"=>"待作废"),
    array("value"=>7,"label"=>"合同未签"),
   ];
  }
  public function actionGetlist(){
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;

    $offset = $limit * ($page - 1);

    $where = [
        'and',
        ['>', 'i.id', 0],
    ];

    
    $order = 'id desc';
    if ($this->_request['orderby']) {
      $order = $this->_request['orderby'];
    }

    if ($this->_request['keyword']){
      $where[] = ['or',['LIKE', 'i.amount', $this->_request['keyword']],['LIKE', 'i.partbname', $this->_request['keyword']]];
      

    }



    if ($this->_request['departmentid']){
      $where[] =  ['in' , 'i.departmentid' , explode(',',$this->_request['departmentid'])];
    }
    
    if ($this->_request['title']) {
      $where[] = ['LIKE', 'i.title', $this->_request['title']];
    }
    if ($this->_request['code']) {
      $where[] = ['LIKE', 'i.code', $this->_request['code']];
    }
    if ($this->_request['type']) {
      $where[] = ['=', 'i.type', $this->_request['type']];
    }
    if ($this->_request['businesstype']) {
      $where[] = ['=', 'i.businesstype', $this->_request['businesstype']];
    }
    
    if ($this->_request['contract']) {
      $where[] = ['=', 'i.contract', $this->_request['contract']];
    }
    if (isset($this->_request['state']) && $this->_request['state']!=-1) {
      $where[] = ['=', 'i.state', $this->_request['state']];
    }
    
    if ($this->_request['creator']) {
      $where[] = ['in', 'i.creator', explode(',',$this->_request['creator'])];
    }
   
    
    if ($this->_request['parta']) {
      $where[] = new Expression("FIND_IN_SET(".$this->_request['parta'].",parta)");

    }
    if ($this->_request['partb']) {
      $where[] = new Expression("FIND_IN_SET(".$this->_request['partb'].",partb)");
    }

    if ($this->_request['datestart']) {
      $where[] = ['>=', 'i.date', $this->_request['datestart']];
    }
    if ($this->_request['dateend']) {
      $where[] = ['<=', 'i.date', $this->_request['dateend']];
    }
    if ($this->_request['inserttimestart']){
      $where[] = ['>=', 'i.inserttime', $this->_request['inserttimestart']];
    }
    if ($this->_request['inserttimeend']) {
      $where[] = ['<=', 'i.inserttime', $this->_request['inserttimeend']];
    }


    if ($page==1){
      
      // 默认统计本月的数据

      $andwhere = [
          'and',
          ['!=', 'i.state', $this->STATES_DELETED],
      ];
      
      // 合同覆盖率
      $total = FzrbsInvoicing::find()->alias('i')->where($where)->andWhere(['not in','contract',[3,4]])->count();
      
      // 未关联合同的
      $hasContractNumber=FzrbsInvoicing::find()->alias('i')->select('*')->where($where)->andWhere(['and',new Expression("contractid is not null and contractid!='' "),['not in','contract',[3,4]]])->count();
     
      

      if (!$this->_request['datestart']) {
        $andwhere[] = ['>=', 'i.inserttime', date('Y-m-01')];
      }
      if (!$this->_request['dateend']) {
        $andwhere[] = ['<', 'i.inserttime', date('Y-m-d', strtotime("+1 day"))];
      }
      $statmodel = FzrbsInvoicing::find()->alias('i')->select('i.amount,inv.realamount')
      ->leftJoin(['inv'=>FzrbsInvoicingInvoice::tableName()],'inv.invoicingid=i.id')
      ->where($where)->andWhere($andwhere);
      
      
      $invoiceamount = $statmodel->sum('inv.realamount');
      $totalAmount = $statmodel->sum('i.amount');
      
   

      
      $ratio = 0;
      if ($total>0){
        $ratio = round($hasContractNumber/$total,2)*100;
      }
      $stat = [
        array('label'=>'月度申请金额','value'=>$totalAmount),
        array('label'=>'月度开票金额','value'=>$invoiceamount),
        array('label'=>'合同覆盖率','value'=>$ratio.'%')

      ];

      $this->_result['stat'] = $stat;
      
    }
    // 是否是待开票或待作废
    $isSpecial = in_array($this->_request['currentState'],[2,6]);
    if ($isSpecial){
      $where[] =  ['or',['=' , 'i.creator' , $this->_adminInfo['wxuserid']],new Expression("FIND_IN_SET('".$this->_adminInfo['wxuserid']."',i.invoicers)")];
    }else{
        // 部门权限
        $dept = $this->getDepts();
        if (sizeof($dept)>0) {
          $where[] = ['or',['in' , 'i.departmentid' , $dept],['=' , 'i.creator' , $this->_adminInfo['wxuserid']]];
        } else {
          $where[] =  ['or',['=' , 'i.creator' , $this->_adminInfo['wxuserid']],new Expression("FIND_IN_SET('".$this->_adminInfo['wxuserid']."',i.invoicers)")];
        }
    }
    
    if ($this->_request['currentState']) {
      
      switch ($this->_request['currentState']) {
        case 4:
          # 已红冲
          $where[] = new Expression("inv.realinvoiceamount=0 and inv.invoiceids is not null");
          break;
        case 3:
          # 已开票
          $where[] = new Expression("inv.invoiceids is not null");
          break;
        case 2:
          # 待开票
          $where[] = new Expression("i.state=".$this->STATES_INVOICED." and inv.invoiceids is null");
          break;
        case 1:
          # 审批中G
          $where[] = new Expression("i.thirdNo is not null and i.thirdNo!=''");
          break;
        case 5:
          # 暂存
          $where[] = new Expression("i.state=0 and thirdNo is null");
          break;
          
        case 6:
          # 待作废
          $where[] = new Expression("i.state=6");
          break;
        case 7:
          # 合同未签
          $where[] = ['and',new Expression('i.contractid is null or i.contractid="" '),new Expression('i.contract not in (3,4)')];
          break;
        case 8:
          # 小额公告合同
          $where[] = ['and',new Expression('i.contract  in (4)')];
          break;
        default:
          # code...
          break;
      }
    }
    
   
    $model = FzrbsInvoicing::find()->alias('i')->select("i.*,DATE_FORMAT(`i`.`date`, '%Y-%m-%d') AS `date`,(CASE WHEN `i`.`type`=0 THEN '普票' ELSE '专票' END) as `typename`,item.title,inv.invoiceids,inv.realinvoiceamount,d.name as department,u.name,d1.label as contractname")
    ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=i.creator')
    ->leftJoin(['inv'=>"(SELECT invoicingid,GROUP_CONCAT(id) as invoiceids,sum(TotalTaxIncludedAmount) as realinvoiceamount from ".FzrbsInvoice::tableName()." GROUP BY invoicingid)"],'inv.invoicingid=i.id')
    ->leftJoin(['item'=>"(SELECT invoicingid,GROUP_CONCAT(title) as title from ".FzrbsInvoicingItem::tableName()." GROUP BY invoicingid)"],'item.invoicingid=i.id')
    ->leftJoin(['d1'=>FzrbsBudgetDict::tableName()],"d1.value=i.contract and d1.type='合同业务类型'")
    ->leftJoin(['d'=>WeixinOaDepartment::tableName()],'d.id=i.departmentid')->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($order)->asArray()->all();

    $this->_result["current"] = $page;
    $this->_result["pageSize"] = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    

    return $this->_result;
  }

  public function actionDelcontract(){
    $id = $this->_request['id'];
    $contractid = $this->_request['contractid'];
    if(!$contractid) return array('errorMessage'=>'contractid 不能为空');
    if(!$id) return array('errorMessage'=>'id 不能为空');

    $d = FzrbsInvoicing::findOne($id);
    if (!$this->haspower('发票管理',$this->agentId,$d['departmentid'],$d['creator'])) {
      $dept = WeixinOaDepartment::findOne($d['departmentid']);
      return array('errorMessage'=>'没有部门【'.$dept['name'].'】的【发票管理】权限');
    }
    // 开启事务
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      // 更新发票信息的contractid字段
      if ($d['contractid']){
        $d->contractid = implode(',',array_diff(explode(',',$d['contractid']),[$contractid]));

        $d->save();
      }
 
      FzrbsInvoicingInvoice::updateAll(['contractid'=>null,'invoicingid'=>$id],['invoicingid'=>$id]);
        
    }catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage()); 
    }
    $transaction->commit();

    return array('data'=>'删除成功');
  }

  /**
   * id,name,addr,tel,bankname,bankaccount
   */
  private function updateCompany($infoFromInvoice){
    // 先根据id查询
    // $company = FzrbsCompany::findOne(['code'=>$infoFromInvoice['id']]);
    // if (!$company){
    //   // 再根据公司名称查询
    //   $company = FzrbsCompany::findOne(['company'=>$infoFromInvoice['name']]);
    // }
    $company = FzrbsCompany::findOne(['company'=>$infoFromInvoice['name']]);
    if (!$company){
      // 找不到就新建
      $company = new FzrbsCompany();
    }
    $company->code = $infoFromInvoice['id'];
    $company->company = $infoFromInvoice['name'];
    $company->address = $infoFromInvoice['addr'];
    // 判断联系人是否为空，如果不为，那么在最前面插入
    $tel = $infoFromInvoice['tel'];

    // 联系方式
    $company->contacts = $tel;
    // 银行帐号
    $bankname=$infoFromInvoice['bankname'];
    $bankaccount=$infoFromInvoice['bankaccount'];
    if ($company->bankaccount){
      if (!preg_match("/$bankaccount/",$company->bankaccount)){//没找到
        $company->bankaccount= $bankname.' '.$bankaccount.','.$company->bankaccount;
      }
    }else{
      $company->bankaccount = $bankname.' '.$bankaccount;
    }
    $company->save();
   
    
  }
  private function refreshCompanyinfoFromInvoice($invoice){

    if ($invoice['SellerIdNum']){
      
      $this->updateCompany(array('id'=>$invoice['SellerIdNum'],'name'=>$invoice['SellerName'],'addr'=>$invoice['SellerAddr'],'tel'=>$invoice['SellerTelNum'],'bankname'=>$invoice['SellerBankName'],'bankaccount'=>$invoice['SellerBankAccNum']));
    }
    if ($invoice['BuyerIdNum']){
      $this->updateCompany(array('id'=>$invoice['BuyerIdNum'],'name'=>$invoice['BuyerName'],'addr'=>$invoice['BuyerAddr'],'tel'=>$invoice['BuyerTelNum'],'bankname'=>$invoice['BuyerBankName'],'bankaccount'=>$invoice['BuyerBankAccNum']));
    }

  }
  public function actionGetinvoicings(){
    $obj = $this->_request;
    $datas = FzrbsInvoicing::find()->alias('i')->select('i.*,item.title,inv.invoiceids,inv.realinvoiceamount,d.name as department,u.name,d1.label as contractname')
    ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=i.creator')
    ->leftJoin(['inv'=>"(SELECT invoicingid,GROUP_CONCAT(id) as invoiceids,sum(TotalTaxIncludedAmount) as realinvoiceamount from ".FzrbsInvoice::tableName()." GROUP BY invoicingid)"],'inv.invoicingid=i.id')
    ->leftJoin(['item'=>"(SELECT invoicingid,GROUP_CONCAT(title) as title from ".FzrbsInvoicingItem::tableName()." GROUP BY invoicingid)"],'item.invoicingid=i.id')
    ->leftJoin(['d1'=>FzrbsBudgetDict::tableName()],"d1.value=i.contract and d1.type='合同业务类型'")
    ->leftJoin(['d'=>WeixinOaDepartment::tableName()],'d.id=i.departmentid')
    ->where(['and',['=','partbname',$obj['SellerName']],['=','partaname',$obj['BuyerName']]])->orderBy('id desc')->limit(10)->asArray()->all();
    return array('invoicings'=>$datas);
  }
  private function UpdateInvoicingInvoiceByInvoicingid($id){
    $ii = FzrbsInvoicingInvoice::find()->where(['invoicingid'=>$id])->one();
    if (!$ii) {
      $invoicing = FzrbsInvoicing::findOne($id);
      $ii = new FzrbsInvoicingInvoice(array('invoicingid'=>$id,'amount'=>$invoicing->amount,'contractid'=>$invoicing->contractid));
    }
    // 更新开票总金额和关联的所有的发票号
    $temp = FzrbsInvoice::findBySql("SELECT invoicingid,GROUP_CONCAT(EIid) as invoiceno,sum(TotalTaxIncludedAmount) as realinvoiceamount from ".FzrbsInvoice::tableName()." where invoicingid=$id GROUP BY invoicingid")->asArray()->one();
    $ii->realamount=$temp['realinvoiceamount'];
    $ii->invoiceno=$temp['invoiceno'];

    $ii->save();
    if ($temp['realinvoiceamount']<0){
      throw new Exception('上传发票后，开票申请对应的开票金额为负数，操作失败！');
    }
    if ($temp['realinvoiceamount']==0&&$temp['invoiceno']){
      // 修改对应开票申请的状态为已作废
      // 获取当前日期
      
      FzrbsInvoicing::updateAll(['state'=>$this->STATES_DELETED,'deldate'=>date('Y-m-d')],['id'=>$id]);
      $msg = '已作废';
    }else if($temp['state']==$this->STATES_DELETED&&!$temp['invoiceno']){
        FzrbsInvoicing::updateAll(['state'=>$this->STATES_INVOICED],['id'=>$id]);
    } else{
      $msg = '已开票';
      
    }
    $invoicing = FzrbsInvoicing::findOne($id);
    // 发送消息给经办
    $this->send($invoicing->creator,"开票申请【".$msg."】",$invoicing);
    $this->send($this->_adminInfo['wxuserid'],"开票申请【".$msg."】",$invoicing);

  }
  public function actionSavepdfinvoice(){
    $id= $this->_request['id'];
    $pdffileurls= $this->_request['pdffileurls'];
    
    try {
      $invoicing = FzrbsInvoicing::findOne($id);

      // 判断是否有操作权限
      $errMsg=$this->isNotInvoicer($invoicing['partb'],$invoicing['departmentid']);
      if ($errMsg) {
        $dept = WeixinOaDepartment::findOne($invoicing['departmentid']);
        return array('errorMessage'=>'不是部门【'.$dept['name'].'】及销售单位【'.$invoicing['partbname'].'】的开票员,'.$errMsg);
      }

      $invoicing->pdffileurls = $pdffileurls;
      $invoicing->save();
      // 通知经办
      $this->send($invoicing->creator,"开票申请【上传PDF发票】",$invoicing);
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('success'=>true);
    
  }
  public function actionSaveinvoice(){
    // 判断参数
    $obj = $this->_request;
    if ($this->_request['obj']) $obj=$this->_request['obj'];
    $id = $obj['invoicingid'];

    
    if(!$id) {
      // 自动匹配销售方和客户
      return $this->actionGetinvoicings();

    }
    $d = FzrbsInvoicing::findOne($id);
    if(!$d) return array('errorMessage'=>'开票申请不存在');
    // 只有通过开票申请和待作废的发票才允许关联发票；
 
    if(!in_array($d->state,[$this->STATES_INVOICED,$this->STATES_WAITDELETED])) return array('errorMessage'=>'只有通过开票申请的和待作废的，才允许关联发票！');

    
        
    // 判断是否有操作权限
    $errMsg=$this->isNotInvoicer($d['partb'],$d['departmentid']);
    if ($errMsg) {
      $dept = WeixinOaDepartment::findOne($d['departmentid']);
      return array('errorMessage'=>'不是部门【'.$dept['name'].'】及销售单位【'.$d['partbname'].'】的开票员,'.$errMsg);
    }

    
    $items = $obj['IssuItemInformation'];
    if (!$items){
      return array('errorMessage'=>'IssuItemInformation 不能为空');
    }
    unset($obj['IssuItemInformation']);
    
    
    // 根据发票号查询此发票是否已经上传过，如果是返回对应的开票申请
    if (!$obj['EIid']) return array('errorMessage'=>'EIid不能为空,仅支持xml发票上传和解析');
    $invoice = FzrbsInvoice::find()->where(['and',['EIid'=>$obj['EIid']]])->one();
    if ($invoice && $invoice['invoicingid']){
      $datas = FzrbsInvoicing::find()->alias('i')->select('i.*,item.title,inv.invoiceids,inv.realinvoiceamount,d.name as department,u.name,d1.label as contractname')
      ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=i.creator')
      ->leftJoin(['inv'=>"(SELECT invoicingid,GROUP_CONCAT(id) as invoiceids,sum(TotalTaxIncludedAmount) as realinvoiceamount from ".FzrbsInvoice::tableName()." GROUP BY invoicingid)"],'inv.invoicingid=i.id')
      ->leftJoin(['item'=>"(SELECT invoicingid,GROUP_CONCAT(title) as title from ".FzrbsInvoicingItem::tableName()." GROUP BY invoicingid)"],'item.invoicingid=i.id')
      ->leftJoin(['d1'=>FzrbsBudgetDict::tableName()],"d1.value=i.contract and d1.type='合同业务类型'")
      ->leftJoin(['d'=>WeixinOaDepartment::tableName()],'d.id=i.departmentid')
      ->where(['and',['=','i.id',$invoice['invoicingid']]])->orderBy('id desc')->limit(10)->asArray()->all();
      return array('invoicings'=>$datas,'msg'=>'此发票已关联了其它开票申请');
    }
    // 开启事务
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      // 根据EIid查询发票信息
      
      // 如果发票已经存在，则提示发票已被占用
      if ($invoice){
        if ($invoice['invoicingid']){
          $temp = FzrbsInvoicing::find()->alias('i')->select('i.*,item.title,inv.invoiceids,inv.realinvoiceamount,d.name as department,u.name,d1.label as contractname')
          ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=i.creator')
          ->leftJoin(['inv'=>"(SELECT invoicingid,GROUP_CONCAT(id) as invoiceids,sum(TotalTaxIncludedAmount) as realinvoiceamount from ".FzrbsInvoice::tableName()." GROUP BY invoicingid)"],'inv.invoicingid=i.id')
          ->leftJoin(['item'=>"(SELECT invoicingid,GROUP_CONCAT(title) as title from ".FzrbsInvoicingItem::tableName()." GROUP BY invoicingid)"],'item.invoicingid=i.id')
          ->leftJoin(['d1'=>FzrbsBudgetDict::tableName()],"d1.value=i.contract and d1.type='合同业务类型'")
          ->leftJoin(['d'=>WeixinOaDepartment::tableName()],'d.id=i.departmentid')
          ->where(['and',['=','i.id',$invoice['invoicingid']]])->asArray()->all();

          return array('invoicings'=>$temp,'msg'=>'发票已经上传过了，以下为发票关联的开票项目');
        }

      }else{
        
        
        if (trim($d['partbname'])!=str_replace("（个人）","",$obj['SellerName'])){
          return array('errorMessage'=>"开票信息的销售方名称为【".$d['partbname']."】，而发票的销售方名称为【".$obj['SellerName']."】，两者不匹配，操作失败");
        }

  
        if (trim($d['partbname'])=='福州日报社'){
          if (!$obj['publication']) return array('errorMessage'=>'业务类型是【版面广告】或【新媒体业务】的，媒体必选');
        }
        // 如果是新媒体业务
        if ($d['businesstype']=='新媒体业务'){
          if (!$obj['subpublication']) return array('errorMessage'=>'业务类型是【新媒体业务】的，新媒体类型必选');
        }
        
        $invoice = new FzrbsInvoice($obj);
        $invoice->creator=$this->_adminInfo['wxuserid'];
        $invoice->businesstype=$d['businesstype'];
        $invoice->departmentid=$d['departmentid'];
        $invoice->receiver=$d['receiver'];
        $invoice->save();

   
        // 更新开票申请的开票日期
        $d->date = $invoice->RequestTime;
        $d->save();
    

        // 更新开票申请实际开票金额
        $this->UpdateInvoicingInvoiceByInvoicingid($id);
     

        // 更新公司的信息------------------------
        $this->refreshCompanyinfoFromInvoice($obj);


        for ($i=0; $i < sizeof($items); $i++) { 
          $t=new FzrbsInvoiceItem($items[$i]);
          $t->invoiceid=$invoice['id'];
          $t->save();
        }
      }
    }catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage()); 
    }
    $transaction->commit();

    return array('data'=>$invoice);
  }
  public function actionDelinvoice(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    $old = FzrbsInvoice::findOne($id);
  
    if ($old->creator!=$this->_adminInfo['wxuserid']) return array('errorMessage'=>'只有上传发票的人才可以删除');
  
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      
      $old->delete();
      // 更新开票申请实际开票金额
      $this->UpdateInvoicingInvoiceByInvoicingid($old->invoicingid);
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
  
    
    return array('data'=>'删除成功');
  }
  public function actionDelpaycollection(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    // 只允许本人修改
    $old = FzrbsContractPaycollection::findOne($id);
    $transaction = Yii::$app->getDb()->beginTransaction();
    $transaction2 = Yii::$app->paymentdb->beginTransaction();
    try {
      
      

      FzrbsContractPaycollection::updateAll(['valid'=>0],'id='.$id);
      
      
      $new = new FzrbsContractPaycollection($old);
      $new['state']=0;
      $new['sysnote']=$this->userinfo['name'];
      unset($new->id);
      $new['amount']=-$old['amount'];
      $new['inserttime'] = date('Y-m-d');
      $new['date'] = date('Y-m-d');
      $new->save();
     
      $paycollection = $this->upContractPaycollection($old['contractid']);

      // 广告管理系统
      $payment = Yii::$app->paymentdb->createCommand("SELECT * FROM payment where SYS_DELETEFLAG=0 and SYS_CURRENTSTATUS='借票回款' and P_InvoiceNo=".substr($old['EIid'], -8)." and P_Amount=".$old['amount'])->queryOne();
      

      if($payment){
        $source = Yii::$app->paymentdb->createCommand("SELECT * FROM payment where SYS_DELETEFLAG=0 and SYS_DOCUMENTID=".$payment['P_SrcID'])->queryOne();
        if($source['P_BalancedMoney']>0){
          return array('errorMessage'=>'已平账，先反平账再删除');
        }
        $invoice = Yii::$app->paymentdb->createCommand("SELECT * FROM invoice where SYS_DELETEFLAG=0 and I_InvoiceNo=".substr($old['EIid'], -8)." ")->queryOne();

        $source['P_Amount'] = $source['P_Amount'] - $payment['P_Amount'];
        $invoice['I_AmountBack'] = $invoice['I_AmountBack'] - $payment['P_Amount'];
        
        // 删除payment
        Yii::$app->paymentdb->createCommand()->delete('payment', ['=', "SYS_DOCUMENTID",  $payment['SYS_DOCUMENTID']])->execute();
        Yii::$app->paymentdb->createCommand()->update('payment', ['P_Amount' => $source['P_Amount']], ['=', "SYS_DOCUMENTID",  $source['SYS_DOCUMENTID']])->execute();
        Yii::$app->paymentdb->createCommand()->update('invoice', ['I_AmountBack' => $invoice['I_AmountBack']], ['=', "SYS_DOCUMENTID",  $invoice['SYS_DOCUMENTID']])->execute();


      }else{
        return array('errorMessage'=>'未找到对应的回款');
      }

    
    } catch (\Throwable $th) {
      $transaction->rollBack();
      $transaction2->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    $transaction2->commit();
    return array('ret'=>1,'paycollection'=>$paycollection);
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
  public function actionGetpaycollection(){
    $EIid = $this->_request['EIid'];
    $contractids=$this->_request['contractids'];
    if (!$EIid&&!$contractids){
      return array('errorMessage'=>'EIid 和 contractids 不能都为空');
    }
    $where = ['and','p.id>0'];
    if ($EIid) {
      $where[] = ['=','p.EIid',$EIid];
    }
    if ($contractids) {
      $where[] = ['in','p.contractid',explode(',',$contractids)];
    }
    $datas = FzrbsContractPaycollection::find()->alias('p')->select('p.*,c.title')
      // 关联合同
      ->leftJoin(FzrbsContract::tableName().' c','c.id=p.contractid')
      ->where($where)->orderBy('p.id desc')->asArray()->all();
    for ($i=0; $i < sizeof($datas); $i++) { 
      $datas[$i]['sysnote']=$datas[$i]['sysnote']?$datas[$i]['sysnote']:' ';
    }
    return array('data'=>$datas);
  }
   public function actionPushinvoicetoadsys(){

    $ids = $this->_request['ids'];
    
    if (!$ids) return array('errorMessage'=>'请选择要推送的开票项目');
    $rows = FzrbsInvoice::find()->where(['in','id',explode(',',$ids)])->all();

    

    $message = '';
    foreach ($rows as $row) {
      if($row->pushed) {
        $message .= '发票【'.$row->EIid.'】已经推送过了，不要重复操作！<br>';
        continue;
      }
      $ii = FzrbsInvoicing::findOne($row->invoicingid);
      if (!$ii) {
        $message .= '发票【'.$row->EIid.'】对应的开票项目【'.$row->invoicingid.'】不存在<br>';
        continue;
      }
      // 根据客户名称查询客户ID，若客户不存在就新增
      $publication = $row->publication;
      $publicationid = 0;
      if ($publication){
        $p = FzrbsBudgetDict::find()->where(['label'=>$publication,'type'=>'发票媒体'])->one();
        if ($p) $publicationid = $p->value;
      }else{
        $message .= '发票【'.$row->EIid.'】的发票媒体为空,推送失败<br>';
        continue;
      }
    
      try {
        $buyer = $this->getBuyer($ii->partaname);
      } catch (\Throwable $th) {
        $message .= '发票【'.$row->EIid.'】推送报错：'.$th->getMessage().'<br>';
        continue;
      }
     
      // 核算组件若为“福州日报社”，则核算组织使用媒体
      $accountancy = $row->SellerName=='福州日报社'?$publication:$row->SellerName;
      
      $invoice = array(
        'I_Customer'=>$buyer['CUST_NAME'],//客户
        'I_Customer_ID'=>$buyer['SYS_DOCUMENTID'],//客户
        'I_Amount'=>$row->TotalTaxIncludedAmount,//开票金额
        'I_Date'=>$row->RequestTime,//开票日期
        'I_InvoiceNo'=>substr($row->EIid, -8),//发票号码
        'publication'=>$publication,//媒体
        'I_Receiver'=>str_replace('（个人）','',isset($ii->receiver)?$ii->receiver:$row->BuyerName), //发票抬头
        'accountancy'=>$accountancy, // 核算组织
        'taxrate'=>round(floatval($row->TotalTaxAm)/floatval($row->TotalTaxIncludedAmount),2),//税率
        'taxamount'=>$row->TotalTaxAm,//税额
        'untaxamount'=>$row->TotalAmwithoutTax,//不含税金额
        'SYS_CURRENTSTATUS'=>'借票',//借票
        'I_AmountLinked'=>$row->TotalTaxIncludedAmount,
        'SYS_CURRENTUSERID'=>$this->userinfo['id'],
        'SYS_CURRENTUSERNAME'=>$this->userinfo['name'],
        'SYS_AUTHORS'=>$this->userinfo['name'],
        'I_OperatorID'=>$this->userinfo['id'],
        'SYS_DELETEFLAG'=>0,
        'isNew'=>1,
        'SYS_CREATED'=>date('Y-m-d H:i:s'),
        'SYS_LASTMODIFIED'=>date('Y-m-d H:i:s'),
      );
      $payment=array(
        'P_Customer'=>$invoice['I_Customer'],
        'P_Customer_ID'=>$invoice['I_Customer_ID'],
        'P_PayMode_ID'=>'59',
        'P_PayMode'=>'借票',
        'SYS_CURRENTSTATUS'=>'借票',
        'P_InvoiceNo'=>$invoice['I_InvoiceNo'],
        'P_Amount'=>0.0,
        'P_AmountLeft'=>0.0,
        'P_Date'=>$invoice['I_Date'],
        'P_Publication_ID'=>$publicationid,
        'P_Publication'=>$publication,
        'P_InvoicedMoney'=>$invoice['I_Amount'],
        'P_BalanceableMoney'=>$invoice['I_Amount'],
        'P_InvoiceableMoney'=>0,
        'SYS_CURRENTUSERID'=>$this->userinfo['id'],
        'SYS_CURRENTUSERNAME'=>$this->userinfo['name'],
        'SYS_AUTHORS'=>$this->userinfo['name'],
        'P_OperatorID'=>$this->userinfo['id'],
        // 'receipt'=>$invoice['receipt'], // 回单后6位
        'accountancy'=>$invoice['accountancy'],
        'receiver'=>str_replace('（个人）','',$invoice['I_Receiver']),
        'isNew'=>1,
        'SYS_CREATED'=>date('Y-m-d H:i:s'),
        'SYS_LASTMODIFIED'=>date('Y-m-d H:i:s'),
      );
     
      // $lastInvoice = Yii::$app->paymentdb->createCommand("select I_InvoiceNo from invoice where SYS_DELETEFLAG=0 order by SYS_CREATED desc limit 1")->queryOne();
    

      // 判断发票号是否已经存在
      $temp = Yii::$app->paymentdb->createCommand("SELECT * FROM invoice where SYS_DELETEFLAG=0 and I_InvoiceNo='".$invoice['I_InvoiceNo']."'")->queryOne();
      
      if ($temp){
         $message .= '发票【'.$row->EIid.'】推送报错：发票号已经存在<br>';
         continue;
      }
      $transaction = Yii::$app->getDb()->beginTransaction();
      
      try {
        $row->pushed=1;
        $row->save();
        Yii::$app->paymentdb->createCommand()->insert('invoice', $invoice)->execute();
        Yii::$app->paymentdb->createCommand()->insert('payment', $payment)->execute();

        $action = '推送发票到广告管理系统,';
        $remark = $action . "操作人=" . $this->userinfo['name'] . "，发票ID：".$row->EIid.",广告发票：".json_encode($invoice,JSON_UNESCAPED_UNICODE)."，借票收款：".json_encode($payment,JSON_UNESCAPED_UNICODE);
        $this->_operationlog(['catalog' => $action, 'remark' => $remark]);

      } catch (\Throwable $th) {
        $transaction->rollBack();
        $message .= '发票【'.$row->EIid.'】推送报错：'.$th->getMessage().'<br>';
        continue;
      }
      $transaction->commit();
      

      
    }
    

    
    
    return array('errorMessage'=>$message);

  }
  // 撤销推送到广告管理系统的借票
  public function actionDelpushinvoice(){
    $EIid = $this->_request['EIid'];
    if (!$EIid){
      return array('errorMessage'=>'EIid is null');
    }
    $temp = FzrbsInvoice::find()->where(['EIid'=>$EIid])->one();
    if(!$temp){
      return array('errorMessage'=>'发票【'.$EIid.'】不存在');
    }
    // 如果发票未推送，无须撤销
    if (!$temp->pushed) {
      return array('errorMessage'=>'发票【'.$EIid.'】未推送，无须撤销');
    }
    // 只有经办可以撤销发票推送
    if ($this->_adminInfo['wxuserid']!=$temp->creator&&$this->_adminInfo['usertype']!=1) {
      return array('errorMessage'=>'只有经办才可以撤销,操作失败');
    }

    $invoiceno = substr($EIid, -8);
    $invoice =  Yii::$app->paymentdb->createCommand("SELECT * FROM invoice where SYS_DELETEFLAG=0 and I_InvoiceNo='".$invoiceno."'")->queryOne();
    $payment = Yii::$app->paymentdb->createCommand("SELECT * FROM payment where SYS_DELETEFLAG=0 and P_InvoiceNo='".$invoiceno."'")->queryOne();
    // 发票必须是未平账未回款的
    if($invoice&&$invoice['I_AmountBack']>0){
      return array('errorMessage'=>'发票【'.$EIid.'】已回款，无法撤销');
    }
    if($payment&&$payment['P_BalancedMoney']>0){
      return array('errorMessage'=>'发票【'.$EIid.'】已平账,先反平账，再撤销');
    }
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      $temp->pushed=0;
      $temp->save();
    
      if($invoice){
        Yii::$app->paymentdb->createCommand()->delete('invoice', ['SYS_DOCUMENTID' => $invoice['SYS_DOCUMENTID']])->execute();
      }
      if($payment){
        Yii::$app->paymentdb->createCommand()->delete('payment', ['SYS_DOCUMENTID' => $payment['SYS_DOCUMENTID']])->execute();
      }
      
      
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>'发票【'.$EIid.'】撤销失败：'.$th->getMessage());
    }
    $transaction->commit();
    return array('success'=>true,'errorMessage'=>'');
  }
  public function actionGetheaders(){
    $type = $this->_request['type'];
    $res=[];
    switch ($type) {
      case 'invoice':
        $res = [
          array("key"=>"EIid","title"=>"发票号"),
          array("key"=>"SellerName","title"=>"销售方名称"),
          array("key"=>"BuyerName","title"=>"客户名称"),
          array("key"=>"TotalAmwithoutTax","title"=>"不含税开票额"),
          array("key"=>"TotalTaxIncludedAmount","title"=>"含税开票额")
        ];
        break; 
      default:
          $res = [
            array("key"=>"typename","title"=>"发票类别"),
            array("key"=>"partbname","title"=>"开票单位"),
            array("key"=>"partaname","title"=>"客户名称"),
            array("key"=>"title","title"=>"开票项目"),
            array("key"=>"amount","title"=>"开票金额"),
            array("key"=>"contractname","title"=>"合同业务"),
            array("key"=>"department","title"=>"部门"),
            array("key"=>"name","title"=>"经办"),
            array("key"=>"date","title"=>"开票日期")
          ];
        break;
    }
    return array('data'=>$res);
  }
  // 查询广告信息
  public function Getadvertise(){
    $keyword = $this->_request['keyword'];
    return $this->postRequestFileGetContents('http://129.0.97.23:8005/advitem/findAll',['keyword'=>$keyword]);
  }
  function postRequestFileGetContents($url, $data) {
    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    if ($result === FALSE) {
        die('Error');
    }
    
    return $result;
}

// ================== 审批流程 =====================
// 查询待办事项
public function actionTodolist(){
  
  $userid = $this->_adminInfo['wxuserid'];

  $result = [];



  
  // 查询所有
  $where = ['and',['>', 'i.id', 0]];


  
 
    // 部门权限
    $dept = $this->getDepts();
    if (sizeof($dept)>0) {
      $where[] = ['or',['in' , 'i.departmentid' , $dept],['=' , 'i.creator' , $this->_adminInfo['wxuserid']]];
    } else {
      $where[] =  ['=' , 'i.creator' , $this->_adminInfo['wxuserid']];
    }


  $itotal=FzrbsInvoicing::find()->alias('i')->where($where)->count();
  $result[] =  array('title'=>'全部', 'count'=>$itotal);

  // 待审批
  $awhere = ['and',new Expression("p.thirdNo is not null and p.thirdNo!=''"),['or',new Expression("FIND_IN_SET('$userid',i.approvalUserid)"),['=','p.creator',$userid]]];
  $temp = FzrbsInvoicing::find()->alias('p')->leftJoin(['i'=>WeixinOaApprovalInfo::tableName()],'p.thirdNo=i.thirdNo')->where($awhere)->count();
  if ($temp) {
    $result[] =  array('title'=>'待审批', 'count'=>$temp,'url'=>'/finance/invoice/applylist','query'=>array('state'=>1));
  }


  // 待开票
  $andwhere = ['and',['or',['=' , 'i.creator' , $this->_adminInfo['wxuserid']],new Expression("FIND_IN_SET('".$this->_adminInfo['wxuserid']."',i.invoicers)")],new Expression("i.state=".$this->STATES_INVOICED." and i.approvaltype!=2 and inv.invoiceids is null")];
 
  $model = FzrbsInvoicing::find()->alias('i')
    ->leftJoin(['inv'=>"(SELECT invoicingid,GROUP_CONCAT(id) as invoiceids,sum(TotalTaxIncludedAmount) as realinvoiceamount from ".FzrbsInvoice::tableName()." GROUP BY invoicingid)"],'inv.invoicingid=i.id')
    ->where($andwhere);
    
  $total = $model->count();
  if ($total) {
    $result[] =  array('title'=>'待开票', 'count'=>$total);
  }

  // 待作废
  $delwhere= ['and',['or',['=' , 'i.creator' , $this->_adminInfo['wxuserid']],new Expression("FIND_IN_SET('".$this->_adminInfo['wxuserid']."',i.invoicers)")],new Expression("i.state=6")];
  $delModel=FzrbsInvoicing::find()->alias('i')->where($delwhere);
  $dtotal = $delModel->count();
  if ($dtotal) {
    $result[] =  array('title'=>'待作废', 'count'=>$dtotal);
  }
  // 合同业务未关联合同的项目
  $model2 = FzrbsInvoicing::find()->alias('i')
   ->where($where)->andWhere(['and',new Expression('i.contractid is null or i.contractid="" '),new Expression('i.contract not in (3,4)')]);
   $total = $model2->count();
   if ($total) {
     $result[] =  array('title'=>'合同未签', 'count'=>$total,'query'=>array('withoutcontract'=>1));
   }
  //  统计小额公告数
  $model3 = FzrbsInvoicing::find()->alias('i')
   ->where($where)->andWhere(['and',new Expression('i.contract=4')]);
   $total = $model3->count();
   if ($total) {
     $result[] =  array('title'=>'小额公告业务', 'count'=>$total,'query'=>array('contract'=>4));
    }



  return $result;

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

  
  $where = ['and',new Expression("thirdNo in (SELECT distinct thirdNo FROM ".WeixinOaApprovalLog::tableName()." where userId='$userid'  and agentid=".$this->agentId.")"),new Expression("data like '%infoid\":%'")];
  

  $model = WeixinOaApprovalInfo::find()->where($where);
  

  $total = $model->count();
  $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
  $result = [];
  for ($i=0; $i < sizeof($res); $i++) {
    $temp =  json_decode($res[$i]['data'],true);
    $invoicingid = $temp['infoid'];

    if ($invoicingid){
      $res[$i] = FzrbsInvoicing::find()->alias('p')
      ->select('`p`.*,d2.label as approvaltypename,u.name')
      ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'p.creator=u.userid')
      ->leftJoin(['d2'=>FzrbsBudgetDict::tableName()],"d2.value=p.approvaltype and d2.type='开票审批'")
      ->where(['=','p.id',$invoicingid])->asArray()->one();
    }
  }
  // $res对象数组去掉值为null的对象
  $res = array_filter($res,fn($v)=>$v!=null);
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
    
    $where = ['and',new Expression("p.id in (SELECT SUBSTR(data FROM LOCATE('\"infoid\":',data)+9 FOR LOCATE(',',SUBSTR(data FROM LOCATE('\"infoid\":',data)+9))-1) as id from weixin_oa_approval_info where thirdNo in (SELECT DISTINCT thirdNo from weixin_oa_approval_log WHERE userId='$userid' and agentId=".$this->agentId."))")];

    if ($this->_request['title']) {
      $where[] = ['LIKE', 'p.title', $this->_request['title']];
    }


    $model = FzrbsInvoicing::find()->alias('p')->select('`p`.*,d.label as statename,u.name,u.avatar')
    ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'p.creator=u.userid')
    ->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=p.state and d.type='开票审批'")
    ->where($where)->groupBy('p.id,u.name,u.avatar,statename');
    

    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();    
    $_result = array();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    $_result['data'] = $res;
    return $_result;
    
  }
public function actionApprovallist(){

  if ($this->_request['state']==-1){
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
  
  $where = ['and',new Expression("p.thirdNo is not null and p.thirdNo!=''"),['or',new Expression("FIND_IN_SET('".$userid."', i.approvalUserid)"),['=','p.creator',$userid]]];

  if (isset($this->_request['state'])&&$this->_request['state']>-1){
    $where[] = ['=','p.approvaltype',$this->_request['state']];
  }


  $model = FzrbsInvoicing::find()->alias('p')
  ->select('i.approvalUsername,`p`.*,d2.label as approvaltypename,u.name,d1.label as contractname')
  ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'p.creator=u.userid')
  ->leftJoin(['i'=>WeixinOaApprovalInfo::tableName()],"p.thirdNo=i.thirdNo")
  ->leftJoin(['d1'=>FzrbsBudgetDict::tableName()],"d1.value=p.contract and d1.type='合同业务类型'")
  ->leftJoin(['d2'=>FzrbsBudgetDict::tableName()],"d2.value=p.approvaltype and d2.type='开票审批'")
  ->where($where)->groupBy('p.id,i.approvalUsername,u.name,u.avatar,approvaltypename,contractname');
  

  $total = $model->count();
  $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();    
  $_result = array();
  $_result["current"] = $page;
  $_result["pageSize"] = $limit;
  $_result["total"] = $total;
  $_result['data'] = $res;
  return $_result;
}
private function gettemplateid($condition){
  $userid = $condition['userid']?$condition['userid']:$this->_adminInfo['wxuserid'];

  $departmentid = $condition['departmentid']?$condition['departmentid']:$this->userinfo['departmentid'];
  
  // 指定userid和部门id的
  $where = ['and',['>','id',0]];
  // 判断审批类型
  if ($condition['type']){
    $where[] = new Expression("types is null or FIND_IN_SET('".$condition['type']."',types)");
  }
  // 判断合同业务
  if (isset($condition['contract'])){
    $where[] = new Expression("contract is null or FIND_IN_SET('".$condition['contract']."',contract)");
  }
  // 有无合同
  if (isset($condition['hascontract'])){
    $where[] = new Expression("hascontract is null or hascontract='".$condition['hascontract']."'");
  }
  // 判断金额
  if (isset($condition['amount'])){
    $amount = $condition['amount'];
    $where[] = new Expression("((lamount is null && hamount is null) or (lamount=0 and hamount>=$amount) or (lamount<=$amount and hamount=0))");
  }
  $order = "id desc";
  // 部门和指定用户
  $result = FzrbsInvoicingTemplate::find()->where($where)->andWhere(['and',new Expression('FIND_IN_SET('.$departmentid.',dids)'),new Expression("FIND_IN_SET('".$userid."',uids)")])->asArray()->orderBy($order )->one();

  if (!$result){
    $result = FzrbsInvoicingTemplate::find()->where($where)->andWhere(['and',new Expression("FIND_IN_SET('".$userid."',uids)")])->asArray()->orderBy($order)->one();
  }
  if (!$result){
    // 查询指定部门
    $result = FzrbsInvoicingTemplate::find()->where($where)->andWhere(['and',new Expression('FIND_IN_SET('.$departmentid.',dids)')])->asArray()->orderBy($order )->one();
  }
  if (!$result){
    $result = FzrbsInvoicingTemplate::find()->where($where)->asArray()->orderBy($order )->one();
  }
  return $result?$result['templateid']:null;
}
private function generateApplydata($thirdNo,$userinfo,$condition){
      
  $condition['roleToUserAll']=true; // 每个角色如果有多人就全部返回
  // 查询流程id
  $templateid = $this->gettemplateid($condition);


  if (!$templateid) {
    throw new Exception('流程未设置');
  }

  // 生成流程数据
  $wfp = new WorkflowParse($this->agentId);
 
  $flowdata = $wfp->flowParse($userinfo['userid'], $templateid,$condition);
  
  if (!$flowdata){
    throw new Exception($templateid.' 解析后审批节点为空，请联系管理员');
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
  if ($flowdata['ApprovalNodes']['ApprovalNode'][0]['Items']['Item']){
    foreach ($flowdata['ApprovalNodes']['ApprovalNode'][0]['Items']['Item'] as $item) {
      $approvalUserid[] = $item['ItemUserId'];
      $approvalUsername[] = $item['ItemName'];
    }
  }
  
  return array('templateid'=>$templateid,'approvalUserid'=>implode('|',$approvalUserid),'approvalUsername'=>implode('|',$approvalUsername),'applydata'=>$applydata,'flow'=>$flow);
}
public function actionGetflow() {
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }
    try {
      if ($obj['infoid']){
        $flow = $this->getflow($obj['infoid'],$obj['act'],$obj['thirdNo']);
      }
      
      
      
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

    $tt = WeixinOaTemplates::find()->where(['templateId'=>$flow['templateid']])->one();
    if ($tt) $templatename=$tt['templateName'];
    return  array('viewdata'=>array('step'=>$step,'approval'=>$approvaldata,'notify'=>$notifier,'templateid'=>$flow['templateid'],'templatename'=>$templatename,'invoicers'=>$flow['invoicers']),'statusCn'=>$this->statusCn);
	}
private function testflow($thirdNo,$condition){
  if(!$condition['userid']){
    throw new Exception('userid不能为空');
  }
  $userinfo = WeixinOAUserInfo::find()->where(['=','userid',$condition['userid']])->asArray()->one();
  $condition['departmentid']=$userinfo['departmentid'];
  // 根据开票单位查询对应同名主体的id，用于获取角色时同时根据部门和主体查询角色
  if ($condition['partbname']){
    $company = WeixinFinanceCompany::find()->select('id')->where(['=','company',$condition['partbname']])->asArray()->one();
    if ($company){
      $condition['company']=$company['id'];
    }
  }
  
  
  $result = $this->generateApplydata($thirdNo,$userinfo,$condition);
  
  
  // 获取开票人
  $invoicers = $this->getInoicer(array('company'=>$condition['partb'],'dept'=>$condition['departmentid']));
  
  if ($invoicers){
    $usernames = array_map(function($user) {
        return $user['username'];
    }, $invoicers);
    
    $result['invoicers']=implode('|',$usernames);
  }else{
    $result['invoicers']='';
  }
  return $result;
  
}
private function getflow($infoid,$act,$thirdNo){

  if (!$act){
    return array('errorMessage'=>'act 不能为空,1-开票,2-作废,');
  }
  $p= FzrbsInvoicing::find()->alias('i')->select('i.*')
  ->where(['=','i.id',$infoid])->groupBy('i.id')->asArray()->one();
  $condition = array();
  $condition['partbname']=$p['partbname'];
  $condition['partb']=$p['partb'];
  $condition['hascontract']=$p['contractid']?'1':'0';
  $condition['amount']=$p['amount'];
  $condition['departmentid']=$p['departmentid'];
  $condition['type']=$act;
  $condition['contract']=$p['contract'];
  $condition['userid']=$p['creator'];

  
  
  
  return  $this->testflow($thirdNo,$condition);
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
    case 'continue':
      return $this->actionContinue();
    case 'urge':
      return $this->actionUrge();
    case 'alter':
      return $this->actionAlterApprover();
    default:
      # code...
      break;
  }
}
public function actionReject(){//驳回
  $userid = $this->_adminInfo['wxuserid'];
  $postdatas = $this->_request;
  if (!$postdatas['speech']) return array('errorMessage'=>'审批意见不能为空');
  $data = WeixinOaApprovalInfo::find()->alias('a')->select("a.*,p.id as infoid")
  ->leftJoin(['p'=>FzrbsInvoicing::tableName()],"p.thirdNo=a.thirdNo")
  ->where(['and',['=','a.thirdNo',$postdatas['thirdNo']],['=','agentId',$this->agentId]])->asArray()->one();
 
  if ($data['reject']==1){
    return array('errorMessage'=>'已驳回，不要重复操作');
  }
  // 是否是当前审批人
  if ($data['approvalUserid'] && !in_array($userid,explode('|',$data['approvalUserid']))){
    return array('errorMessage'=>'当前审批人是：'.$data['approvalUsername']);
  }
  
  $transaction = Yii::$app->getDb()->beginTransaction();
  try {
    
    $project = FzrbsInvoicing::find()->where(['thirdNo'=>$postdatas['thirdNo']])->one();
    
    FzrbsInvoicing::updateAll(['reject'=>1],['thirdNo'=>$postdatas['thirdNo']]);

    $msgdata = [
      'touser' => $data['userId'],
      'msgtype' => 'textcard',
      'agentid' => $this->agentId,
      'textcard' => [
          'title' => '审批申请【被驳回】',
          'description' =>'
          <div class="normal">开票单位：' . $project['partbname'].'</div><div class="normal">客户名称：' . $project['partaname'].'</div><div class="normal">开票金额：' . $project['amount'].'</div><div class="normal">驳回原因：' . $postdatas['speech'].'</div>',
          'url' => "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/invoice/view?invoicingid=".$project['id']."&thirdNo=".$postdatas['thirdNo'],
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

  
  return array('data'=>'成功');
}
private function updateAfterFlowChange($ret,$thirdNo,$status,$condition){

    
   
  $transaction = Yii::$app->getDb()->beginTransaction();
  try {
    
    
    $project = FzrbsInvoicing::find()->alias('p')->select('p.*,d.label as approvaltypename')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=p.approvaltype and d.type='开票审批'")->where(["p.thirdno"=>$thirdNo])->asArray()->one();


    
    $info = WeixinOaApprovalInfo::find()->where(["thirdNo"=>$thirdNo,"agentId"=>$this->agentId])->asArray()->one();
    if (!$info) throw new Exception("找不到流程信息");
    $infodata = json_decode($info['data'],1);


    
    
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

          $state=$infodata['approvaltype'];;
          $history = $project['history'];
          if ($infodata['approvaltype']){
            $state=$infodata['approvaltype'];
            if (!$history){
              $history = $infodata['approvaltype'];
            }else{
              $history.= ','.$infodata['approvaltype'];
            }
          }
       
          $par=['thirdNo'=>'','state'=>$state,'history'=>$history];

          
          
          FzrbsInvoicing::updateAll($par,['id'=>$project['id']]);
          
        // 更新流程信息表
          $temp = ['status'=>$status];
  
          $data = array(
            'infoid'=>$project['id'], // 项目id
            'thirdNo'=>$thirdNo,
            'approvaltype'=>$infodata['approvaltype']
          );

          

          // 如果是作废审批
          if ($infodata['approvaltype']==$this->STATES_DELETED){
            $this->whenInvoicingDelete($project['id']);
          }

        if (!$data['infoid']){
          throw new Exception('updateAfterFlowChange项目id不存在');
        }
        
        $temp['data'] = json_encode($data);
        
        WeixinOaApprovalInfo::updateAll($temp,["thirdNo"=>$thirdNo,"agentId"=>$this->agentId]);

        
      }else if ($ret['nextdata']&&$ret['nextdata']['approvalUserid']) { // 有下个审批人，说明没结束
        if (!$ret['isfinish']) {
          $status = 1;
        }
        

       
        WeixinOaApprovalInfo::updateAll(['status'=>$status,'approvalUserid'=>$ret['nextdata']['approvalUserid'],'approvalUsername'=>$ret['nextdata']['approvalUsername']],["thirdNo"=>$thirdNo,"agentId"=>$this->agentId]);
        
        $noticeuserids=$ret['nextdata']['approvalUserid'];
        if ($condition['notNotice']&&$noticeuserids){
          $noticeuserids=implode('|',array_diff(explode('|',$noticeuserids),explode('|',$condition['notNotice'])));
        }
       
        $this->send($noticeuserids,$info['userName'].'的'.$project['approvaltypename'].'申请',$project);


      }else if ($condition['act']=='cancel'){
        
        WeixinOaApprovalInfo::updateAll(['status'=>$status],["thirdNo"=>$thirdNo,"agentId"=>$this->agentId]);
        FzrbsInvoicing::updateAll(['thirdno'=>null,'reject'=>0],["thirdno"=>$thirdNo]);
      }  else {
        
        if (!$ret['isfinish']) { // 可能是会签
          $status = 1;
        }
        WeixinOaApprovalInfo::updateAll(['status'=>$status],["thirdNo"=>$thirdNo,"agentId"=>$this->agentId]);
        if ($condition['act']=='cancel'){
          FzrbsInvoicing::updateAll(['thirdno'=>null,'reject'=>0],["thirdno"=>$thirdNo]);
     
        }
      }
    } else {
      
        if ($condition['act']=='cancel'){
          
          WeixinOaApprovalInfo::updateAll(['status'=>$status],["thirdNo"=>$thirdNo,"agentId"=>$this->agentId]);
          FzrbsInvoicing::updateAll(['thirdno'=>null,'reject'=>0],["thirdno"=>$thirdNo]);
        }
    }

    

  } catch (\Throwable $th) {
    $transaction->rollBack();
    throw $th;
  }
  $transaction->commit();
  
}
private function whenInvoicingDelete($invoicingid){
  try {
    // 取消合同关联
    FzrbsInvoicingInvoice::deleteAll(['invoicingid'=>$invoicingid]);
    // 取消发票关联
    FzrbsInvoice::deleteAll(['invoicingid'=>$invoicingid]);
    // 设置开票项目contractid和projectids 为空
    FzrbsInvoicing::updateAll(['contractid'=>null,'projectids'=>null],['id'=>$invoicingid]);
    // 
  } catch (\Throwable $th) {
    throw $th;
  }
}
public function actionContinue(){
  $userid = $this->_adminInfo['wxuserid'];
  $postdatas = $this->_request;
  $data = WeixinOaApprovalInfo::find()->alias('a')->select("a.*,p.id as infoid")->leftJoin(['p'=>FzrbsInvoicing::tableName()],"p.thirdNo=a.thirdNo")->where(['and',['=','a.thirdNo',$postdatas['thirdNo']],['=','agentId',$this->agentId]])->asArray()->one();
  // 是否是项目发起人
  if ($data['userId']!=$userid){
    return array('errorMessage'=>'不是经起人：'.$data['userName']);
  }
  
  $transaction = Yii::$app->getDb()->beginTransaction();
  try {
    
    $project = FzrbsInvoicing::find()->where(['thirdNo'=>$postdatas['thirdNo']])->one();
    
    FzrbsInvoicing::updateAll(['reject'=>0],['thirdNo'=>$postdatas['thirdNo']]);

    $msgdata = [
      'touser' => $data['userId'].'|'.$data['approvalUserid'],
      'msgtype' => 'textcard',
      'agentid' => $this->agentId,
      'textcard' => [
          'title' => '审批申请【驳回后重新提交】',
          'description' => '<div class="normal">开票单位：' . $project['partbname'].'</div><div class="normal">客户名称：' . $project['partaname'].'</div><div class="normal">开票金额：' . $project['amount'].'</div>',
          'url' => "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/invoice/view?invoicingid=".$project['id']."&thirdNo=".$project['thirdNo'],
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
public function actionUrge(){
    
  if (!$this->_request['thirdNo']) return array('errorMessage'=>'thirdNo为空');
  $p = FzrbsInvoicing::find()->alias('p')->select('p.*,u.name as creatorname,d.label as approvaltypename')
  ->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=p.approvaltype and d.type='开票审批'")
  ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=p.creator')
  ->where(["p.thirdno"=>$this->_request['thirdNo']])->asArray()->one();

  $approveres = WeixinOaApprovaldata::find()->where(['and',['=','thirdNo',$this->_request['thirdNo']],['=','agentId',$this->agentId]])->asArray()->one();
  $temp=[];
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

  if ($temp){
    $userids = implode('|',$temp);
    $this->send($userids,$p['creatorname'].'的'.$p['approvaltypename'].'审批【催办】',$p);
    return array('ret'=>1,'userids'=>$userids);
  }

  return array('ret'=>1);
  
}
/**
 * 开启审批
 */
public function actionStartflow(){
  $userinfo = $this->userinfo;
  $resp = array('errorMessage'=>'');
  $postdatas = $this->_request;
  $act = $this->_request['act'];
  $infoid = $postdatas['infoid'];
  if (!$act){
    return array('errorMessage'=>'act 不能为空,1-开票,2-作废');
  }
  if (!$infoid) {
    return array('errorMessage'=>'infoid 不能为空');
  }
  $thirdNo = $postdatas['thirdNo'];
  if (!$thirdNo) {
    $thirdNo = $this->getThirdNo();
  }

  $needUpdateData=array('thirdNo'=>$thirdNo);

  // 只有项目经办才能提交流程
  $p = FzrbsInvoicing::find()->alias('p')->select('p.*,u.name as creatorname')->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=p.creator')->where(["p.id"=>$infoid])->asArray()->one();
 
  if($p['creator']!=$userinfo['userid']){
    return array('errorMessage'=>'只有经办才能提交流程');
  }
  // 查询开票人
  $invoicersArr = $this->getInoicer(array('company'=>$p['partb'],'dept'=>$p['departmentid']));
  if($invoicersArr){
    $invoicers = implode(',',array_column($invoicersArr,'userid'));
  }else{
    $dept = WeixinOaDepartment::findOne($p['departmentid']);
    return array('errorMessage'=>'未设置部门【'.$dept['name'].'】及销售单位【'.$p['partbname'].'】的开票员');

  }
  $needUpdateData['invoicers']=$invoicers;
  
//项目金额为零时禁止提交
  if($p['amount']<=0){
    return array('errorMessage'=>'开票金额必须大于零');
  }
  
  
  $datas = array(
    'agentId'=>$this->agentId,
    'userId' => $userinfo['userid'],
    'userName' => $userinfo['name'],
    'avatar'=>$userinfo['avatar'],
    'departmentid' => $userinfo['departmentid'],
    'department' => $userinfo['departmentname'],
    'thirdNo' => $thirdNo,
    'type' => $postdatas['flowtype'],
    'status'=>1,
    'data'=>json_encode(array('id'=>$infoid))
  );
  // 判断是否已经存在
  $d=WeixinOaApprovalInfo::find()->where(['thirdNo' => $thirdNo])->one();
  if($d){
    return array('errorMessage'=>'不要重复提交');
  }
  
  try {
    $allflowdatas = $this->getflow($infoid,$act,$thirdNo);
  } catch (\Throwable $th) {
    return array('errorMessage'=>$th->getMessage());
  }
  
  $actEntity = FzrbsBudgetDict::find()->where(['value'=>$act,'type'=>'开票审批'])->one();


  $datas['data'] = json_encode(array('infoid'=>intval($infoid),'approvaltype'=>$act,'approvaltypename'=>$actEntity['label']));



  $datas['template'] = $allflowdatas['templateid'];
  $datas['approvalUserid'] = $allflowdatas['approvalUserid'];
  $datas['approvalUsername'] = $allflowdatas['approvalUsername'];

  $transaction = Yii::$app->getDb()->beginTransaction();
  try {


    $needUpdateData['approvaltype']=$act;
    
    
    
    // 更新项目
    FzrbsInvoicing::updateAll($needUpdateData,'id='.$infoid);
    // 保存数据
    $model = new WeixinOaApprovalInfo($datas);
    $model->save();

    // 保存执行流
    $model2 = new WeixinOaApprovaldata($allflowdatas['applydata']);
    $model2->save();

    $title = $p['creatorname'].'的'.$actEntity['label'].'审批';
    $p['thirdNo']=$datas['thirdNo'];
    $this->send($allflowdatas['approvalUserid'],$title,$p);

  } catch (\Throwable $th) {
    $transaction->rollBack();
    return array('errorMessage'=>$th->getMessage());
  }
  $transaction->commit();
 
  $resp['flow'] = $allflowdatas['flow'];
  // $resp['data'] = $datas;
  // $resp['allflowdatas']=$allflowdatas;
  $resp['thirdNo'] = $thirdNo;
  return $resp;
}
/**
   * 同意
   */
  public function actionAgree(){
    
    $postdatas = $this->_request;
   
    $userid = $this->_adminInfo['wxuserid'];
    
    if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空');
    $data = WeixinOaApprovalInfo::find()->alias('a')->select("a.*,p.id as infoid")->leftJoin(['p'=>FzrbsInvoicing::tableName()],"p.thirdNo=a.thirdNo")->where(['and',['=','a.thirdNo',$postdatas['thirdNo']],['=','agentId',$this->agentId]])->asArray()->one();
 
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
       
        
        if ($data['infoid']){
          $project = FzrbsInvoicing::findOne($data['infoid']);
          $this->send($data['userId'],'审批申请【已通过】',$project);
          // 如果当前状态是已开票，则发送一条信息给开票员
          if ($project->state==$this->STATES_INVOICED){
            $invoicers = $this->getInoicer(array('company'=>$project['partb'],'dept'=>$project['departmentid']));
            if($invoicers){
              $userids = array_column($invoicers,'userid');
              $this->send(implode('|',$userids),'开票申请【待开票】',$project);
            }
          }
        }
        
      }
		
    } catch (\Throwable $th) {
      
      return array('errorMessage'=>$th->getMessage());
      // throw $th;
    }
    
    
    return array('data'=>$ret);
  }
/**
   * 流程预览
   */
  public function actionViewflow(){
    
    // if (!$this->_request['infoid']) {
    //   return array('errorMessage'=>'infoid 不能为空');
    // }
    $act = $this->_request['act'];

    try {
      if ($this->_request['infoid']){
        $approvedata = $this->getflow($this->_request['infoid'],$act,'');
      }else{
        $approvedata = $this->testflow('',$this->_request);
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    
    $flow = $approvedata['flow'];
    if($flow){
      $approvearr = $flow;
      if($approvearr['data']['ApprovalNodes']['ApprovalNode']){
        foreach ($approvearr['data']['ApprovalNodes']['ApprovalNode'] as $k=>$r) {
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
      }
      
      $notifier = array();
      if ($approvearr['data']['NotifyNodes']['NotifyNode']){
        foreach ($approvearr['data']['NotifyNodes']['NotifyNode'] as $r) {
          $notifier[] = $r['ItemName'];
        }
      }
      
      $step = intval($approvearr['data']['approverstep'])-1;

    }
    $tt = WeixinOaTemplates::find()->where(['templateId'=>$approvedata['templateid']])->one();
      if ($tt) $templatename=$tt['templateName'];
    return  array('viewdata'=>array('step'=>$step,'approval'=>$approvaldata,'notify'=>$notifier,'templatename'=>$templatename,'templateid'=>$approvedata['templateid']),'invoicers'=>$approvedata['invoicers'],'statusCn'=>$this->statusCn);
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
  private function getTagName($tagid) {
    $t = WeixinOauserTaguser::findOne($tagid);
		return $t?$t['tagName']:'';
	}
  /**
   * 查看流程审批数据
   */
  public function actionGetflowdata(){

    $thirdNo = $this->_request['thirdNo'];
    $infoid=$this->_request['infoid'];


    $info=false;
    $viewdata=0;
    if($infoid){
      $where = ['and',new Expression("data like '%\"infoid\":$infoid\,%'")];

      $basic = WeixinOaApprovalInfo::find()->where($where)->orderBy('id desc')->one();

      $info = FzrbsInvoicing::find()->alias('p')->select('p.*,inv.invoiceids,inv.realinvoiceamount,d.label as approvaltypename,d2.label as contracttypename,d3.name as department')
      ->leftJoin(['inv'=>"(SELECT invoicingid,GROUP_CONCAT(id) as invoiceids,sum(TotalTaxIncludedAmount) as realinvoiceamount from ".FzrbsInvoice::tableName()." GROUP BY invoicingid)"],'inv.invoicingid=p.id')
      ->leftJoin(['d2'=>FzrbsBudgetDict::tableName()],"d2.value=p.contract and d2.type='合同业务类型'")
      ->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=p.approvaltype and d.type='开票审批'")
      ->leftJoin(['d3'=>WeixinOaDepartment::tableName()],'d3.id=p.departmentid')
      ->where(['p.id'=>$infoid])->asArray()->one();
      if ($info['contractid']){
        
        // 合同名称
        $contracts = FzrbsContract::find()->select("GROUP_CONCAT(title) as title")->where(new Expression("id in (".$info['contractid'].")"))->asArray()->one();
        $info['contractnames'] = $contracts['title'];
      
      }
      // 获取项目名称
      if ($info['projectids']){
        $projects = FzrbsBudgetProject::find()->select("id,title")->where(new Expression("id in (".$info['projectids'].")"))->asArray()->all();
        $info['projects'] = $projects;
      }
      if ($basic){
        $thirdNo = $basic->thirdNo;
      }
    }
   
    $wfp = new WorkflowParse($this->agentId);
    try {
     
      if ($thirdNo){
        $viewdata = $wfp->flowViewdata($thirdNo);
        // 流程基本信息
        $basic=WeixinOaApprovalInfo::find()->alias('a')->select('a.*')
        ->where(['a.thirdNo'=>$thirdNo])->asArray()->one();
        if (!$info){
          $info = FzrbsInvoicing::find()->alias('p')->select('p.*,d.label as approvaltypename')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],"d.value=p.approvaltype and d.type='开票审批'")->where(['thirdNo'=>$thirdNo])->asArray()->one();
        }
      }

      


    } catch (\Throwable $th) {
      return array('errorMessage'=> $th->getMessage());
    }
    
    return array('viewdata'=>$viewdata,'basic'=>$basic,'info'=>$info,'statusCn'=>$this->statusCn);

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
      WeixinOaApprovalInfo::updateAll(['approvalUserid'=>$user['userid'],'approvalUsername'=>$user['name']],["thirdNo"=>$thirdNo,"agentId"=>$this->agentId]);
      $p = FzrbsInvoicing::find()->alias('p')->select('p.*,u.name as creatorname')->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=p.creator')->where(["p.thirdNo"=>$thirdNo])->asArray()->one();

      $this->send($curuser['userid'].'|'.$user['userid'],$this->userinfo['name']."发起了转审操作",$p);
      

		}
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=> $th->getMessage());
    }
    $transaction->commit();

		
		return array('ret'=>1);
	}
// ================== 设置流程 ======================
  public function actionGettmplatelist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
  
    $offset = $limit * ($page - 1);
    $where = [
        'and',['>','t.id',0]
    ];
  
    if ($this->_request['keyword']) {
      $where[] = ['or',['like','t.templatename',$this->_request['keyword']],['=','t.templateid',$this->_request['keyword']]];
    }

    
    if ($this->_request['dept']){
      $dept = $this->_request['dept'];
      $where[] = new Expression("FIND_IN_SET($dept, t.dids)");
    }
    if ($this->_request['userid']){
      $userid = $this->_request['userid'];
      $where[] = new Expression("FIND_IN_SET($userid, t.uids)");
    }
    
    $model = FzrbsInvoicingTemplate::find()->alias('t')->select('t.*,wt.templateName as templatename')->leftJoin(['wt'=>WeixinOaTemplates::tableName()],'wt.templateId=t.templateid')->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('id desc')->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }

  public function actionSavetemplate(){

    $obj = $this->_request;

    try {
      if ($obj['id']){
        FzrbsInvoicingTemplate::updateAll($obj,['id'=>$obj['id']]);
      } else {
        $p = new FzrbsInvoicingTemplate($obj);
        $p->save();
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$p);
  }
  public function actionDeltemplate(){

    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    FzrbsInvoicingTemplate::deleteAll(['id'=>$id]);
    return array('data'=>'删除成功');
  }

  public function actionSendinvoice(){
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    $invoice = FzrbsInvoice::find()->where(['id'=>$id])->orderBy('id desc')->one();
    
    if (!$invoice->fileurls){
      return array('errorMessage'=>'发票为空');
    }
    // 查询客户的邮箱；
    $customer = FzrbsCompany::find()->where(['code'=>$invoice->BuyerIdNum])->one();
    if (!$customer->email){
      return array('errorMessage'=>'客户邮箱为空，无法发送，请先设置客户邮箱');
    }
    $tempDir = sys_get_temp_dir(); // 系统临时目录
    try {
      

      $filePath = $this->downloadFile($invoice->fileurls, $tempDir);
      // 调用发送邮件函数
      $this->sendEmailWithAttachment($customer->email, $filePath);

      // 删除临时文件
      unlink($filePath);
      $invoice->sended=1;
      $invoice->save();
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    
    return array('data'=>'发送成功');

  }
  // ===================================  开票员模块 =======================
  public function actionGetinvoicerlist(){

    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
  
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['>', 'r.id', 0]
    ];
    
    if ($this->_request['username']) {
      $where[] = ['LIKE', 'r.username', $this->_request['username']];
    }
    if ($this->_request['dept']){
      $dept = $this->_request['dept'];
      $where[] = new Expression("FIND_IN_SET($dept, r.dept)");
    }
    if ($this->_request['companyids']){
      $companyids = $this->_request['companyids'];
      $where[] = new Expression("FIND_IN_SET($companyids, r.companyids)");
    }
    $model = FzrbsInvoicingInvoicer::find()->alias('r')->select('r.*')->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('r.updatetime desc')->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }
  public function actionSaveinvoicer(){
    

    $resp = array('errorMessage'=>'');
    $obj = $this->_request;


    $obj['updator']=$this->userinfo['name'];
    $obj['updatetime']=date('Y-m-d H:i:s');
    try {
   
      if ($obj['id']){ 
        
        FzrbsInvoicingInvoicer::updateAll($obj,['id'=>$obj['id']]);
      } else {
        $res = FzrbsInvoicingInvoicer::find()->where(['and',['=','userid',$obj['userid']]])->one();
        if ($res) {
          FzrbsInvoicingInvoicer::updateAll($obj,['id'=>$res['id']]);
          $obj['id'] = $res['id'];
          return array('data'=>$obj);
        }
        $c=new FzrbsInvoicingInvoicer($obj);
        $c->save();
      }
    } catch (\Throwable $th) {
      
      return array('errorMessage'=>$th->getMessage());
    }
   
    $resp['data'] =$obj;
    return $resp;
  }
  public function actionDelinvoicer(){

    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    FzrbsInvoicingInvoicer::deleteAll(['id'=>$id]);
    return array('data'=>'删除成功');
  
  }
  private function isNotInvoicer($company,$dept){
    $condition = [
      'dept'=>$dept,
      'company'=>$company
    ];
  
    // 当前部门和公司对应的开票员
    $temp = $this->getInoicer($condition);
    // 判断是否包含当前用户，如果不包含返回当前开票人
    if (!$temp) return '不是开票员';
    $userids = array_map(function($user) {
        return $user['userid'];
    }, $temp);
    // 判断userids是否包含当前用户
    if (in_array($this->userinfo['userid'], $userids)) {
      return '';//包含当前用户
    }
    $username = array_map(function($user) {
        return $user['username'];
    }, $temp);
    
    return '当前开票申请对应的开票员应为：'.implode(',', $username);
  }
  private function getInoicer($condition){

    $dept=$condition['dept'];
    $company=$condition['company'];
    $userid=$condition['userid'];
    if (!$dept){
      throw new Exception('部门不能为空');
    }
    $sql = "id>0 and FIND_IN_SET($dept, dept)";
    
    if ($userid){
      $sql .= " and userid='$userid'";
    }
    // 先同时查询部门和公司
    if ($company){
      $invoicer = FzrbsInvoicingInvoicer::find()->where(new Expression($sql." and FIND_IN_SET($company, companyids)"))->orderBy('id desc')->all();
    }
    if (!$invoicer){
      $invoicer = FzrbsInvoicingInvoicer::find()->where(new Expression($sql))->orderBy('id desc')->all();
    }
    
    return $invoicer;
    
  }
  
  // ==========================发送消息===================
  private function  downloadFile($url,$tempDir) {

    $protocol = 'http';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
      $protocol = 'https';
    }


    $url = $protocol.'://'.$_SERVER['HTTP_HOST'].$url;
    
    $fileName = 'temp1';
    if (preg_match('/(?<=\/)[^\/?]+(?=\?name=)/', $url, $matches)){
      $fileName = $matches[0];
    }

    $filePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;
    

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 20);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($curl, CURLOPT_URL, $url);
    $result = curl_exec($curl);
    curl_close($curl);


    if ($result === false) {
        throw new Exception("无法下载文件: $url");
    }


    // 保存文件到临时目录
    file_put_contents($filePath, $result);

    return $filePath;
}


  private function sendEmailWithAttachment($to, $filePath) {



    $result = FzrbsBudgetDict::find()->where(['type'=>'发件邮箱'])->one();
    if ($result){
      $temp = json_decode($result->label,true);
      $smtp = $temp['smtp'];
      $port = $temp['port'];
      $username = $temp['email'];
      $password = $temp['code'];
    }else{
      throw new Exception("发件邮箱未设置");
    }
 

    $transport = (new Swift_SmtpTransport($smtp, $port, 'tls'))
    ->setUsername($username) // SMTP 用户名
    ->setTimeout(20)
    ->setPassword($password); 
    $mailer = new Swift_Mailer($transport);
    try {
      $message = (new Swift_Message('福州日报社 - 发票邮件'))
        ->setFrom([$username => '发件人名称'])
        ->setTo([$to => '收件人名称'])
        ->setBody('<p>您有新的发票待查阅</p>', 'text/html')
        ->attach(Swift_Attachment::fromPath($filePath));
      $mailer->send($message);
      
    } catch (Exception $e) {
       if (stripos($e->getMessage(), 'Failed to authenticate on SMTP') !== false){
        throw new Exception("邮件发送失败: 邮箱授权码可能已经过期，请联系管理员重新设置！");
       }
      
        throw new Exception("邮件发送失败: " . $e->getMessage());
    }
   

}
  private function send($approvalUserid,$title,$data){
    $url = "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/invoice/view?invoicingid=".$data['id']."&thirdNo=".$data['thirdNo'];
    if (preg_match('/【待开票】/',$title)){
      $url = "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/invoice/index?tab=1";
    }else if (preg_match('/【待作废】/',$title)){
      $url = "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/invoice/index?tab=2";
    }
    if (!$approvalUserid) return;
    $msgdata = [
      'touser' => $approvalUserid,
      'msgtype' => 'textcard',
      'agentid' => $this->agentId,
      'textcard' => [
          'title' => $title,
          'description' => '<div class="normal">开票单位：' . $data['partbname'].'</div><div class="normal">客户名称：' . $data['partaname'].'</div><div class="normal">开票金额：' . $data['amount'].'</div>',
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
  public function actionGetthirdno(){
    return $this->getThirdNo();
  }
  private function getThirdNo()
  {
      list($msec, $sec) = explode(' ', microtime());
      $msectime =  substr(sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000).$this->_adminInfo['id'],0,20);
      return $msectime;
  }
  private function getUserinfo($userid)
  {
      $userinfo = WeixinOAUserInfo::find()->where(['=', 'userid', $userid])->asArray()->one();
      return $userinfo;
  }
  public function haspower($power,$agentid,$dept,$creator){
    
    // 管理员可以修改
    if($this->_adminInfo['usertype']==1) return true;
    // 本人可以修改
    if($creator == $this->_adminInfo['wxuserid']) return true;
    if (!$power) throw new Exception('power不能为空');
    if (!$agentid) throw new Exception('agentid不能为空');
    $deptsql = '';
    if ($dept)  $deptsql ="and  FIND_IN_SET($dept, dept)";
    $model = WeixinOaFlowrole::findBySql("SELECT * from weixin_oa_flowrole where FIND_IN_SET('$agentid',agent) and userid='".$this->_adminInfo['wxuserid']."' $deptsql and role in (SELECT id from weixin_oa_role where   FIND_IN_SET('$power',powername))")->one();
    return $model?true:false;
  }

  public function actionGetcompany(){
    if ($this->_request['preloadInvoicingPartb']){
      $datas = FzrbsInvoicing::findBySql("select distinct partb as id,partbname as company from ".FzrbsInvoicing::tableName()." where creator='".$this->userinfo['userid']."' order by partb asc limit 30");
      return $datas->asArray()->all();
    }
    $company = $this->_request['company'];
    $id = $this->_request['id'];
    $keyword = $this->_request['keyword'];
   
    if(!$company && !$id&&!$keyword) return array();
    
    $where = [
      'and',[">","id",0]
    ];
    if ($company){
      $where[] = ['like', 'company', $company];
    }
    if ($id){
      $where[] = ['=','id',$id];
    }
    if (is_string($keyword)&&$keyword){
      if (strpos($keyword, ',')!==false){
        $keyword = explode(',', $keyword);
        $where[] = ['in', 'id', $keyword];
      }else{
        $where[] = ['or',['like', 'company', $keyword],['=', 'id', $keyword]];
      }
      
      
    }

    
    $datas = FzrbsCompany::find()->where($where)->orderBy('id desc')->limit(50)->all();
    return $datas;
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
        ['>', 'p.id', 0],
    ];
    // 判断用户是否有查看权限
    $dept = [];
    $dept = $this->getDepts();
    if (sizeof($dept)){
      $where[] = ['or',['in' , 'p.departmentid' , $dept],['=','p.creator',$userid]];
    }else {
      $where[] = ['or',['=','p.creator',$userid],['=','p.charger',$userid]];
    }

    if ($this->_request['keyword']) {
      $where[] = ['or',['in','id',explode(',',$this->_request['keyword'])],['LIKE', 'p.title', $this->_request['keyword']],['LIKE', 'p.serial', $this->_request['keyword']]];
      
    }
    $model = FzrbsBudgetProject::find()->alias('p')->select("p.id,p.title,p.serial")->where($where);
    $res = $model->orderBy($order)->limit($limit)->offset($offset)->asArray()->all();
    

    
    return $res;
  }
  public function actionGetusers(){
    
    $keyword = $this->_request['keyword'];
    $where = ['and',['>','id',0]];
    if ($keyword) {
      $where[]=['or',['like','name',$keyword],['=','userid',$keyword]];
    }
    $limit = $this->_request['limit'];
    if(!$limit) $limit = 20;
    $users = WeixinOAUserInfo::find()->select('id,userid,name,mobile,avatar')->where($where)->limit($limit)->all();
    return $users;
  }
  
  public function actionGetdictbykeyword(){
    
    $type = $this->_request['keyword'];
    $showall = $this->_request['showall'];
    $userid = $this->_request['userid'];
    $projecttype = $this->_request['projecttype'];
    
    $order = 'value asc,id desc';
    if ($this->_request['order']) $order = $this->_request['order'];
    $where = [
      'and',
      ['=', 'type', $type],
    ];

    if ($type=='公司主体' && $showall=='false'){
      $where[] = new Expression("FIND_IN_SET(".$this->userinfo['departmentid'].", dept)");
    }else if ($type=='收入类型' && $projecttype==$this->NEW_MEDIA_PROJECT){
      $where[]=['=','subtype','新媒体'];
    }else if ($type=='支出类型' && $projecttype==$this->NEW_MEDIA_PROJECT){
      $where[]=['=','subtype','新媒体'];
    }else if ($type=='项目类别'){
      $order = 'value asc';
    }
    if ($userid) {
      $where[]=['=','userid',$userid];
    }

    

    $datas = FzrbsBudgetDict::find()->where($where)->orderBy($order)->limit(20)->all();
    return $datas;
  }

  // ============================================= 欠款 ===========================================
  
}