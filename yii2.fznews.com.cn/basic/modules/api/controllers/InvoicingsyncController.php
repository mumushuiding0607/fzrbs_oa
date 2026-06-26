<?php

namespace app\modules\api\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\Json;
use app\modules\api\commons\Tools;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\models\Advitem;
use app\modules\api\models\Advorder;
use app\modules\api\models\FzrbsBudgetBalance;
use app\modules\api\models\FzrbsBudgetDict;
use app\modules\api\models\FzrbsBudgetProject;
use app\modules\api\models\FzrbsCompany;
use app\modules\api\models\FzrbsContract;
use app\modules\api\models\FzrbsContractPaycollection;
use app\modules\api\models\FzrbsInvoice;
use app\modules\api\models\FzrbsInvoiceItem;
use app\modules\api\models\FzrbsInvoicing;
use app\modules\api\models\FzrbsInvoicingInvoice;
use app\modules\api\models\FzrbsOperationLog;
use app\modules\api\models\FzrbsOperationLogParams;
use app\modules\api\models\WeixinFinanceCompany;
use app\modules\api\models\WeixinOaDepartment;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\models\WxDepartment;
use linslin\yii2\curl;
use Exception;
use PHPExcel;
use PHPExcel_IOFactory;
use yii\db\Expression;

class InvoicingsyncController extends Controller
{
    public $enableCsrfValidation = false;
    protected $_userIp = null;
    protected $_request = null;
    protected $INCOME_DICID = 15;
    protected $EXPEND_DICID = 16;

    public function init()
    {
        parent::init();
        $this->_userIp = Tools::getClientIp();
        $this->_request = Yii::$app->request->queryParams;
    }
    public function actionGetnewinvoices(){

      $sql = '';
      $id= $this->_request['ids'];
      
      if ($id){
        $sql.=" and i.id in ($id)";
      }
      if (isset($this->_request['isNew'])){
        $sql.=" and isNew=".$this->_request['isNew'];
      }

      // 筛选条件为空时，只查询最新的。否则根据条件查询
      if (!$sql){
        $sql = 'and isNew=1';
      }
     
   
      $datas = Yii::$app->db->createCommand("SELECT i.*,j.businesstype as invoicingbusinesstype FROM ".FzrbsInvoice::tableName()." i left join ".FzrbsInvoicing::tableName()." j on i.invoicingid=j.id where i.id>0 $sql limit 1000")->queryAll();
      for ($i=0; $i <sizeof($datas) ; $i++) { 
        $items=Yii::$app->db->createCommand("SELECT * FROM ".FzrbsInvoiceItem::tableName()." where invoiceid=".$datas[$i]['id'])->queryAll();
        $datas[$i]['Items']=$items;
      }

      echo json_encode($datas,JSON_UNESCAPED_UNICODE);
      exit;
    }

    public function actionCancelsync(){
      $ids = $this->_request['ids'];
      if (!$ids) {

        echo json_encode(array('errorMessage'=>'ids is null'));
        exit;
      }
      try {
        Yii::$app->db->createCommand()->update(FzrbsInvoice::tableName(), ['isNew' => 1], ['in', "id", explode(',',$ids)])->execute();
      } catch (\Throwable $th) {
        echo json_encode(array('errorMessage'=>$th->getMessage()),JSON_UNESCAPED_UNICODE);
        exit;
      }

      echo json_encode(array('success'=>true,'errorMessage'=>''));
      exit;

    }
    public function actionSync(){
      $ids = $this->_request['ids'];
      if (!$ids) {

        echo json_encode(array('errorMessage'=>'ids is null'));
        exit;
      }
      try {
        Yii::$app->db->createCommand()->update(FzrbsInvoice::tableName(), ['isNew' => 0], ['in', "id", explode(',',$ids)])->execute();
      } catch (\Throwable $th) {
        echo json_encode(array('errorMessage'=>$th->getMessage()),JSON_UNESCAPED_UNICODE);
        exit;
      }

      echo json_encode(array('success'=>true,'errorMessage'=>''));
      exit;

    }
    public function actionGetcompany(){

      $datas = Yii::$app->db->createCommand("SELECT * FROM ".FzrbsCompany::tableName()." where isNew=1")->queryAll();
      echo json_encode($datas,JSON_UNESCAPED_UNICODE);
      exit;
    }
    public function actionSyncompany(){
      $ids = $this->_request['ids'];
      if (!$ids) {

        echo json_encode(array('errorMessage'=>'ids is null'));
        exit;
      }
      try {
        Yii::$app->db->createCommand()->update(FzrbsCompany::tableName(), ['isNew' => 0], ['in', "id", explode(',',$ids)])->execute();
      } catch (\Throwable $th) {
        echo json_encode(array('errorMessage'=>$th->getMessage()),JSON_UNESCAPED_UNICODE);
        exit;
      }

      echo json_encode(array('success'=>true,'errorMessage'=>''));
      exit;

    }
    // 回款
    public function actionPushcollection(){
      $par = json_decode(Yii::$app->request->getRawBody(), true);
      $EIid = $par['EIid'];
      $amount = $par['amount'];
      $date=$par['date'];

      if (!$EIid) {
        echo json_encode(array('errorMessage'=>'完整发票号 EIid  is null'),JSON_UNESCAPED_UNICODE);
        exit;
      }
      if (!$amount) {
        echo json_encode(array('errorMessage'=>'回款金额 amount is null'),JSON_UNESCAPED_UNICODE);
        exit;
      }
      if (!$date) {
        echo json_encode(array('errorMessage'=>'回款日期 date is null'),JSON_UNESCAPED_UNICODE);
        exit;
      }
      if (!$par['account']) {
        echo json_encode(array('errorMessage'=>'银行账号 account is null'),JSON_UNESCAPED_UNICODE);
        exit;
      }
      if (!$par['bank']) {
        echo json_encode(array('errorMessage'=>'开户行 bank is null'),JSON_UNESCAPED_UNICODE);
        exit;
      }
      if (!$par['accountancy']) {
        echo json_encode(array('errorMessage'=>'核算组织 accountancy is null'),JSON_UNESCAPED_UNICODE);
        exit;
      }



      $tran = Yii::$app->db->begintransaction();
      try {
        $data = array(
          'EIid'=>$EIid,
          'amount'=>$amount,
          'date'=>$date,
          'sysnote'=>'【金蝶推送】',
          'note'=>$par['memo'],
          'state'=>3,
          'valid'=>1,
        );
        
        // 查询发票号对应的合同号
        $temp = Yii::$app->db->createCommand("SELECT * FROM ".FzrbsInvoicingInvoice::tableName()." where invoiceno='$EIid'")->queryAll();
        if($temp){
          if(count($temp)>1){
            echo json_encode(array('errorMessage'=>"存在多条开票信息同发票号【".$EIid."】对应，收款推送失败！"),JSON_UNESCAPED_UNICODE);
            exit;
          }
          $data['contractid'] = $temp[0]['contractid'];
        }else{
          echo json_encode(array('errorMessage'=>"未找到对应发票号【".$EIid."】的开票信息！"),JSON_UNESCAPED_UNICODE);
          exit;
        }
        // 判断回款金额是否超过开票金额
        $receivable = Yii::$app->db->createCommand("SELECT (TotalTaxIncludedAmount-ifnull(j.amount,0)) as amount from ".FzrbsInvoice::tableName()." i LEFT JOIN (SELECT EIid,sum(amount) as amount from ".FzrbsContractPaycollection::tableName()." WHERE EIid='$EIid' GROUP BY EIid) j on i.EIid=j.EIid where i.EIid='$EIid'")->queryOne();
        
        if (($receivable['amount']<$amount)){
          echo json_encode(array('errorMessage'=>"回款金额【".$amount."】，超过发票未回款金额【".$receivable['amount']."】！"),JSON_UNESCAPED_UNICODE);
          exit;
        }
        
        
        
        // 日志
        $action = '[金蝶推送回款]'.$EIid;
        $remark = $action . "推送数据：".json_encode($par,JSON_UNESCAPED_UNICODE);
        $this->_operationlog(['catalog' => $action, 'remark' => $remark]);


        // 保存收款
        Yii::$app->db->createCommand()->insert(FzrbsContractPaycollection::tableName(), $data)->execute();
        // 更新对应合同的总收款
        if ($data['contractid']){
          $total = Yii::$app->db->createCommand("SELECT sum(amount) as amount FROM ".FzrbsContractPaycollection::tableName()." where contractid=".$data['contractid']."  group by contractid")->queryOne();
          Yii::$app->db->createCommand()->update(FzrbsContract::tableName(), ['paycollection' => $total['amount']], ['=', "id", $data['contractid']])->execute();
          try {
            $this->updateProReceivedWhenPaycheck($data['contractid']);
          } catch (\Throwable $th) {
            $tran->rollBack();
            $this->_operationlog(['catalog' => "回款同步非报系统报错【".$EIid."】：", 'remark' => $th->getMessage()]);
            echo json_encode(array('errorMessage'=>"回款同步非报系统报错：".$th->getMessage()),JSON_UNESCAPED_UNICODE);
            exit;
          }
        }

        // 推送回款到广告管理系统
        try {
          // 判断发票是否推送
          $result = $this->pushCollectionToAdversys($par);
        } catch (\Throwable $th) {
          $tran->rollBack();
          $this->_operationlog(['catalog' => "金蝶回款同步报错【".$EIid."】：", 'remark' => $th->getMessage()]);
          echo json_encode(array('errorMessage'=>"回款同步报错：".$th->getMessage()),JSON_UNESCAPED_UNICODE);
          exit;
        }

        if (!$result){
          $tran->rollBack();
          echo json_encode(array('errorMessage'=>"推送回款到广告管理系统失败，可能是网络问题，请稍后重试"),JSON_UNESCAPED_UNICODE);
          $this->_operationlog(['catalog' => '[金蝶推送回款]', 'remark' => '推送回款到广告管理系统失败，可能是网络问题，请稍后重试']);
          exit;
        }
        

        


      } catch (\Throwable $th) {
        $tran->rollBack();
        echo json_encode(array('errorMessage'=>$th->getMessage()),JSON_UNESCAPED_UNICODE);
        exit;
      }
      $tran->commit();

      echo json_encode(array('success'=>true,'errorMessage'=>''));
      exit;

    }

    public function actionRefreshprojectreceived(){
      $temps=[];
      $contractids =  $this->_request['contractids'];
      if ($contractids){
        $contractids = explode(',',''.$contractids);
        foreach ($contractids as $contractid) { 
          $temps[] = array('contractid'=>$contractid);
        }

      }else{
        $temps=FzrbsContractPaycollection::findBySql("SELECT distinct contractid from fzrbs_contract_paycollection where contractid is not null")->asArray()->all();
      }
      
      try {
        if ($temps){
          foreach ($temps as $temp) {
            $this->updateProReceivedWhenPaycheck($temp['contractid']);
          }
        }
      
      } catch (\Throwable $th) {
        echo json_encode(array('errorMessage'=>"回款同步报错：".$th->getMessage()),JSON_UNESCAPED_UNICODE);
        exit;
      }
      echo json_encode(array('errorMessage'=>""),JSON_UNESCAPED_UNICODE);
      
    }
    /**
     * 根据合同回款更新项目的已收款金额
     * 分配规则：优先分配给state=5(已提交)或directsubmit=1的项目，剩余金额再分配给其他项目
     * @param int $contractid 合同ID
     */
    private function updateProReceivedWhenPaycheck($contractid){
      // 根据当前回款合同的id，查询相关项目
      $temp = FzrbsBudgetProject::findBySql("SELECT GROUP_CONCAT(contractids) as contractids from ".FzrbsBudgetProject::tableName()." where deleted!=1 and FIND_IN_SET($contractid,contractids)")->asArray()->one();
      if (!$temp)return;

      $cids = explode(',',$temp['contractids']);
      // 数组去重
      $cids = array_unique($cids);
      $arr=[];
      foreach ($cids as $cid) {
        $arr[]="FIND_IN_SET('$cid', p.contractids)";
      }

      $sql = implode(' OR ', $arr);
      $projects = FzrbsBudgetProject::find()->alias('p')
        ->select("p.id,p.contractids,p.state,p.directsubmit,CASE WHEN sum(b.final)>0 THEN sum(b.final) ELSE sum(b.budget) END as amount")
        ->join('LEFT OUTER JOIN',['b'=>"(select * from  ".FzrbsBudgetBalance::tableName()." where type=15)"],'b.projectid=p.id')
        ->where($sql)->andWhere(new Expression("p.deleted!=1"))->groupBy('p.id,p.contractids,p.state,p.directsubmit')->orderBy("p.submitdate asc,p.id asc")->asArray()->all();

      // 获取$projects 中 contractids的值，并用逗号拼接
      $cids = implode(',',array_column($projects,'contractids'));
      // 根据项目查询相关所有合同的回款总金额
      $collections = FzrbsContractPaycollection::find()->select('contractid,sum(amount) as received')->where(['and',['in','contractid',explode(',',$cids)],['=','state',3]])->groupBy('contractid')->asArray()->all();

      // 按优先级排序：state=5或directsubmit=1的项目优先分配
      usort($projects, function($a, $b) {
        $aPriority = ($a['state'] == 5 || $a['directsubmit'] == 1) ? 1 : 0;
        $bPriority = ($b['state'] == 5 || $b['directsubmit'] == 1) ? 1 : 0;
        if ($aPriority != $bPriority) {
          return $bPriority - $aPriority;
        }
        return 0;
      });

      // 遍历$collections，并将已回款金额，分配给相关的项目的 receivedmoney 字段，分配金额不能超过项目的总金额
      foreach ($collections as $collection) {
        $received = $collection['received'];
        $cid = $collection['contractid'];
        // 遍历$projects
        for ($i=0; $i < sizeof($projects) ; $i++) {
          if ($received<=0) break;
          $project = $projects[$i];

          // 如果项目收入为负，跳过分配
          if ($project['amount'] < 0) {
            continue;
          }

          // 如果项目contractids字段如果包含cid

          if (in_array(''.$cid, explode(',', $project['contractids']), true)) {
            // 如果项目已回款金额receivedmoney小于项目的总额amount
            if ($project['receivedmoney'] < $project['amount']) {
              // 计算差额，并将$received分配给项目，并更新$received的值
              $diff = $project['amount'] - $project['receivedmoney'];
              if ($diff > $received) {
                $diff = $received;
              }
              $project['receivedmoney'] += $diff;

              $received -= $diff;
              $projects[$i] = $project;
            }

          }
        }

      }
      foreach ($projects as $p) {
       FzrbsBudgetProject::updateAll(['receivedmoney'=>$p['receivedmoney']?$p['receivedmoney']:0],"id=".$p['id']);
      }


    }
   
    private function getUserinfo($userid)
    {
        $userinfo = WeixinOAUserInfo::find()->where(['=', 'userid', $userid])->asArray()->one();
        return $userinfo;
    }
    private function pushCollectionToAdversys($par){
   
      // 查询发票
      $row = FzrbsInvoice::find()->where(['EIid'=>$par['EIid']])->one();
      if (!$row){
        $this->_operationlog(['catalog' => "【".$par['EIid']."】未推送：", 'remark' =>$par['EIid']." 还未录入开票申请系统，同步收款失败" ]);
        throw new Exception('发票【'.$par['EIid'].'】还未录入开票申请系统，同步收款失败');
      }
      // 判断发票是否已经推送，如果未推送就不同步
      if (!$row->pushed){
        $this->_operationlog(['catalog' => "【".$par['EIid']."】未推送到广告系统回款不同步", 'remark' =>$par['EIid']."未推送到广告系统回款不同步" ]);
        return true;
      }
      $userinfo = $this->getUserinfo($row->creator);
   
      // 根据客户名称查询客户ID，若客户不存在就新增
      $publication = $row->publication;
      
      // $tempa=['福州日报社','福州日报','福州晚报','福州报业传媒有限公司','福州日报文化传播有限公司'];
      // if(!in_array($par['accountancy'],$tempa)){
      //   $this->_operationlog(['catalog' => "【".$par['EIid']."】未推送：", 'remark' =>$par['EIid']." 的核算单位【".$par['accountancy']."】不在推送范围【".implode("|",$tempa)."】" ]);
      //   return true;
      // }
      $invoiceno=substr($par['EIid'], -8);
      $publicationid = 0;
      // 查询借票收款
      $oldpayment = Yii::$app->paymentdb->createCommand("SELECT * FROM payment where SYS_DELETEFLAG=0 and FIND_IN_SET('".$invoiceno."',P_InvoiceNo) order by SYS_DOCUMENTID asc limit 1")->queryOne();
      if(!$oldpayment) throw new Exception('借票收款不存在（可能被误删），无法回款，请在撤回发票后重试（撤回发票会同时删除广告系统上对应的发票和收款）！');
      if (!$publication&&$oldpayment['P_Publication']){
        $publication = $oldpayment['P_Publication'];
      }

      if ($publication){
        $p = FzrbsBudgetDict::find()->where(['label'=>$publication,'type'=>'发票媒体'])->one();
        if ($p) $publicationid = $p->value;
      }else{
        $this->_operationlog(['catalog' => "【".$par['EIid']."的发票媒体为空,回款推送失败", 'remark' =>$par['EIid']."的发票媒体为空,回款推送失败" ]);
        throw new Exception('发票【'.$row->EIid.'】的发票媒体为空,推送失败!');
      }
      $ii = FzrbsInvoicing::findOne($row->invoicingid);
      if (!$ii) {
        $this->_operationlog(['catalog' => "【".$par['EIid']."对应的开票申请【'.$row->invoicingid.'】,回款推送失败!", 'remark' =>$par['EIid']."对应的开票申请【'.$row->invoicingid.'】,回款推送失败" ]);
        throw new Exception('发票【'.$row->EIid.'】对应的开票申请【'.$row->invoicingid.'】,推送失败!');
       
      }
      // 判断发票是否已经推送到了广告管理系统
      
      $invoicePayment = Yii::$app->paymentdb->createCommand("SELECT * FROM invoice where SYS_DELETEFLAG=0 and I_InvoiceNo='".$invoiceno."'")->queryOne();
      // 若否，先推送发票
      if (!$invoicePayment) $invoicePayment = $this->pushInvoice($par['EIid']);
      
      
      // 根据发票对应的买家查询客户Id;
      try {
        $buyer = $this->getBuyer($ii->partaname);
      } catch (\Throwable $th) {
        $this->_operationlog(['catalog' => "【".$par['EIid']."获取客户信息报错,回款推送失败!".$th->getMessage(), 'remark' =>$par['EIid']."获取客户信息报错,回款推送失败!".$th->getMessage() ]);
        throw new Exception('发票【'.$row->EIid.'】,获取客户信息报错：'.$th->getMessage());
      }
      // 判断回款总额是否已经大于应收款总额
      // if ($par['amount']>$oldpayment['P_Amount']) throw new Exception('历史回款总额【】，本次回款后总额【】，开票金额【】，本次回款后金额大于开票金额，回款失败！');
      $accountancy = $par['accountancy']=='福州日报社'?$publication:$par['accountancy'];
      $data = array(
        'newpayment'=>array(
          'P_SrcID'=>$oldpayment['SYS_DOCUMENTID'],
          'SYS_CURRENTUSERID'=>$userinfo['id'],
          'SYS_CURRENTUSERNAME'=>$userinfo['name'],
          'SYS_AUTHORS'=>$userinfo['name'],
          'p_OperatoriD'=>$userinfo['id'],
          'SYS_CURRENTSTATUS'=>'借票回款',
          'isNew'=>1,
          'pCustomer'=>$buyer['CUST_NAME'],
          'p_Customer_iD'=>$buyer['SYS_DOCUMENTID'],
          'p_publication'=>$publication,
          'ppayModeiD'=>"57",
          'ppaymode'=>'现金',
          'ppublicationiD'=>$publicationid,
          'p_Memo'=>isset($par['memo'])?$par['memo']:'金蝶推送',

          'p_amount'=>$par['amount'],
          'pDate'=>$par['date'],
          'account'=>$par['account'],
          'accountancy'=>$accountancy,
          'bank'=>$par['bank'],
          'receipt'=>$par['receipt'],
          
          ),
          'oldpayment'=>array(
            'SYS_DOCUMENTiD'=>$oldpayment['SYS_DOCUMENTID']
          )

      );
      $curl = new curl\Curl();
      // $data转成字符串
      $curl->setHeader('Content-Type', 'application/json');


      $response = $curl->setRequestBody(json_encode($data))->post('http://129.0.98.23:7202/payment/moneyback');
      // $response = $curl->setRequestBody(json_encode($data))->post('http://130.0.6.169:8005/payment/moneyback');
     
      
      
      if (!$response){
        $this->_operationlog(['catalog' => "【".$par['EIid']."原因未知,可能是网络问题，请稍后再试!", 'remark' =>$par['EIid']."原因未知,可能是网络问题，请稍后再试" ]);
        throw new Exception('推送回款到广告管理系统失败，原因未知,可能是网络问题，请稍后再试');
      }
      $result = json_decode($response, true);
      
      
      
      if ($result['message']) {
        throw new Exception('同步回款报错message：'.$result['message']);
      }
      if ($result['err']) {
        throw new Exception('同步回款报错err：'.$result['err']);
      }
      if ($result['error']) {
        throw new Exception('同步回款报错error：'.$result['error']);
      }
      
      
      
      return true;
      
      
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

    
    private function pushInvoice($EIid){
      $row = FzrbsInvoice::find()->where(['EIid'=>$EIid])->one();
      $userinfo = $this->getUserinfo($row->creator);
      // 根据客户名称查询客户ID，若客户不存在就新增
      $publication = $row->publication;
      $publicationid = 0;
      if ($publication){
        $p = FzrbsBudgetDict::find()->where(['label'=>$publication,'type'=>'发票媒体'])->one();
        if ($p) $publicationid = $p->value;
      }else{
        throw new Exception('发票【'.$row->EIid.'】的发票媒体为空,推送失败!');
      }
      // 根据发票对应的买家查询客户Id;
      try {
        $ii = FzrbsInvoicing::findOne($row->invoicingid);
        if (!$ii) {
          throw new Exception('发票【'.$row->EIid.'】对应的开票申请【'.$row->invoicingid.'】,推送失败!');
        
        }
        $buyer = $this->getBuyer($ii->partaname);
      } catch (\Throwable $th) {
        throw new Exception('发票【'.$row->EIid.'】,获取客户信息报错：'.$th->getMessage());
      }

      $accountancy = $row->SellerName=='福州日报社'?$publication:$row->SellerName;
     

      $invoice = array(
        'I_Customer'=>$buyer['CUST_NAME'],//客户
        'I_Customer_ID'=>$buyer['SYS_DOCUMENTID'],//客户
        'I_Amount'=>$row->TotalTaxIncludedAmount,//开票金额
        'I_Date'=>$row->RequestTime,//开票日期
        'I_InvoiceNo'=>substr($row->EIid, -8),//发票号码
        'publication'=>$publication,//媒体
        'I_Receiver'=>str_replace('（个人）','',$row->BuyerName), //发票抬头
        'accountancy'=>$accountancy, // 核算组织
        'taxrate'=>round(floatval($row->TotalTaxAm)/floatval($row->TotalTaxIncludedAmount),2),//税率
        'taxamount'=>$row->TotalTaxAm,//税额
        'untaxamount'=>$row->TotalAmwithoutTax,//不含税金额
        'SYS_CURRENTSTATUS'=>'借票',//借票
        'I_AmountLinked'=>$row->TotalTaxIncludedAmount,
        'SYS_CURRENTUSERID'=>$userinfo['id'],
        'SYS_CURRENTUSERNAME'=>$userinfo['name'],
        'SYS_AUTHORS'=>$userinfo['name'],
        'I_OperatorID'=>$userinfo['id'],
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
        'P_MEMO'=>'金蝶推送',
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
        'SYS_CURRENTUSERID'=>$userinfo['id'],
        'SYS_CURRENTUSERNAME'=>$userinfo['name'],
        'SYS_AUTHORS'=>$userinfo['name'],
        'P_OperatorID'=>$userinfo['id'],
        // 'receipt'=>$invoice['receipt'], // 回单后6位
        'accountancy'=>$invoice['accountancy'],
        'receiver'=>str_replace('（个人）','',$invoice['I_Receiver']),
        'isNew'=>1,
        'SYS_CREATED'=>date('Y-m-d H:i:s'),
        'SYS_LASTMODIFIED'=>date('Y-m-d H:i:s'),
      );
      
      // 判断发票号是否已经存在
      $temp = Yii::$app->paymentdb->createCommand("SELECT * FROM invoice where SYS_DELETEFLAG=0 and I_InvoiceNo='".$invoice['I_InvoiceNo']."'")->queryOne();
      
      if ($temp){
        return $temp;
      }
      $transaction = Yii::$app->getDb()->beginTransaction();
      try {
       
        $row->pushed=1;
        $row->save();
        Yii::$app->paymentdb->createCommand()->insert('invoice', $invoice)->execute();
        Yii::$app->paymentdb->createCommand()->insert('payment', $payment)->execute();
      } catch (\Throwable $th) {
        $transaction->rollBack();
        throw new Exception('发票【'.$row->EIid.'】推送报错：'.$th->getMessage());
      }
      $transaction->commit();
      return $invoice;

    }

    // -----------------------广告管理系统----------------------------------------------------

    // 查询合同
    public function actionGetcontract(){
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
        $where[] = new Expression("id in (".$this->_request['id'].")");
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
        $limit = 20;
      }

      $datas = FzrbsContract::find()->alias('c')->select("id,serial,title,parta,partaname,partb,partbname")->where($where)->orderBy('id desc')->limit($limit)->asArray()->all();
      echo json_encode($datas,JSON_UNESCAPED_UNICODE);
      exit;
    }
    private function getBselect(){
    return "sum(CASE WHEN b.type=$this->INCOME_DICID THEN b.budget ELSE 0 END) AS `budgetincome`, SUM(CASE WHEN b.type=$this->EXPEND_DICID THEN b.budget ELSE 0 END) AS `budgetexpend`, sum(CASE WHEN b.type=$this->INCOME_DICID THEN b.final ELSE 0 END) AS `finalincome`, SUM(CASE WHEN b.type=$this->EXPEND_DICID THEN b.final ELSE 0 END) AS `finalexpend`";
  }
    // 查询与广告相关的项目
    public function actionGetcontractswithprojects(){
      $contractids = $this->_request['contractids'];
      if (!$contractids) return [];
      $contracts = FzrbsContract::find()->where(['in','id',explode(',',''.$contractids)])->asArray()->all();
      $result = [];
      for ($i=0; $i < sizeof($contracts); $i++) { 
        $data['contract'] = $contracts[$i];
        $where=['and',new Expression("FIND_IN_SET(".$contracts[$i]['id'].",p.contractids)"),['!=','p.deleted',1]];
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
      echo json_encode($result,JSON_UNESCAPED_UNICODE);
      exit;
   }
   
    // 添加订单
    public function actionSaveorder(){ 
      $par = json_decode(Yii::$app->request->getRawBody(), true);
      if (!$par['SYS_DOCUMENTID']){
        $par['SYS_CREATED']=date('Y-m-d H:i:s');
      }
      $par['SYS_LASTMODIFIED']=date('Y-m-d H:i:s');
      unset($par['initRowIndex']);
      try {
        if ($par['SYS_DOCUMENTID']){
          Yii::$app->paymentdb->createCommand()->update(Advorder::tableName(), $par, ['SYS_DOCUMENTID'=>$par['SYS_DOCUMENTID']])->execute();
        }else{
          Yii::$app->paymentdb->createCommand()->insert(Advorder::tableName(), $par)->execute();
        }
        
      } catch (\Throwable $th) {
        echo json_encode(array('errorMessage'=>$th->getMessage()),JSON_UNESCAPED_UNICODE);
        exit;
      }
      echo json_encode(array('errorMessage'=>""),JSON_UNESCAPED_UNICODE);
      
      exit;
    }
    protected function _operationlog($log)
    {
      
      if (is_array($log)) {
            $log['username'] = '金蝶';
            $log['realname'] = '金蝶';
            $log['ip'] = $this->_userIp;
            $log['url'] = Yii::$app->request->getHostInfo() . Yii::$app->request->url;
            $log['inserttime'] = time();
            $model = new FzrbsOperationLog($log);

            $model->save();
            // $logId = $model->id;
            // $model = new FzrbsOperationLogParams();
            // if (isset($this->_request['values']['password'])) {
            //     unset($this->_request['values']['password']);
            // }
            // if (isset($this->_request['values']['oldpassword'])) {
            //     unset($this->_request['values']['oldpassword']);
            // }
            // if (isset($this->_request['values']['newpassword'])) {
            //     unset($this->_request['values']['newpassword']);
            // }
            // if (isset($this->_request['values']['confirmpassword'])) {
            //     unset($this->_request['values']['confirmpassword']);
            // }
            // $params = json_encode($this->_request, JSON_UNESCAPED_UNICODE);
            // $model->attributes = ['logid' => $logId, 'params' => $params];
            
            // $model->save();
        }
    }
    public function actionDepartment(){
      set_time_limit(0);
      $result = [];
        $parentId = isset($this->_request['parentid']) ? $this->_request['parentid'] : 0;
        $localDepartment = intval($this->_request['local']) ? 1 : 0;
        if ($localDepartment == 1) {
            // 本地数据表部门信息
            $where = [
                'and',
                ['>', 'id', 0],
                ['=', 'parentid', $parentId],
            ];
            if (isset($this->_request['childrenId']) && $this->_request['childrenId']) {
                $where[] = ['in', 'id', explode(',', $this->_request['childrenId'])];
            }
            $data = [];
            if (intval($this->_request['firstRequest']) === 1 && $parentId > 0) {
                $rootNode = WxDepartment::find()->where(['=', 'id',  $parentId])->orderBy('order desc')->one();
                if ($rootNode) {
                    $data[] = ['title' => $rootNode->name,'label'=>$rootNode->name,'id'=>strval($rootNode->id), 'key' => strval($rootNode->id), 'value' => strval($rootNode->id), 'isLeaf' => false];
                }
            } else {
                $res = WxDepartment::find()->where($where)->orderBy('order desc')->all();
                foreach ($res as $row) {
                    $node = ['title' => $row->name,'label'=>$row->name,'id'=>strval($row->id), 'key' => strval($row->id), 'value' => strval($row->id), 'isLeaf' => true];
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
            $result = $data;
        } else {
            // 企业号通讯录接口部门
            $sendResult =  WxQyhJk::department($parentId);
            if (!$sendResult['errorMessage']) {
                $departments = $sendResult['data'];
                if (isset($this->_request['childrenId']) && $this->_request['childrenId']) {
                    $requestDepartmentIds = explode(',', $this->_request['childrenId']);
                    $requestDepartments = [];
                    foreach ($departments as $department) {
                        if (in_array($department['id'], $requestDepartmentIds)) {
                            $requestDepartments[] = $department;
                        }
                    }
                    $departments = $requestDepartments;
                }
                $data = [];
                if (is_array($departments) && count($departments) > 0) {
                    if ($this->_request['showAll']) {
                        $data[] =  ['title' => '福州日报社','label'=>'福州日报社' ,'id'=>'1','key' => '1', 'value' => '1', 'children' => $this->_getDepartmentChildren($departments, 1)];
                    } else {
                        if (intval($this->_request['firstRequest']) === 1 && $parentId > 0) {
                            $data[] = ['title' => $departments[0]['name'],'label'=>$departments[0]['name'],'id'=>$departments[0]['id'], 'key' => strval($departments[0]['id']), 'value' => strval($departments[0]['id']), 'isLeaf' => false];
                        } else {
                            foreach ($departments as $department) {
                                if ($department['parentid'] == $parentId) {
                                    $sortdepartments[$department['order']] = ['title' => $department['name'],'label'=>$departments['name'],'id'=>$departments['id'], 'key' => strval($department['id']), 'value' => strval($department['id']), 'isLeaf' => false];
                                }
                            }
                            if ($sortdepartments) {
                                uksort($sortdepartments, array($this, '_departmentSort'));
                                $data = array_values($sortdepartments);
                            }
                        }
                    }
                }
                if (!$this->_request['showAll']) {
                    if (intval($this->_request['user']) === 1) {
                        $sendResult = WxQyhJk::departmentUser($parentId);
                        $users = $sendResult['data'];
                        if ($users) {
                            foreach ($users as $user) {
                                $data[] = ['title' => $user['name'], 'key' => $user['userid'], 'value' => $user['userid'], 'isLeaf' => true];
                            }
                        }
                    }
                }
                $result = $data;
            }
        }
        echo json_encode($result,JSON_UNESCAPED_UNICODE);
      exit;
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
            $node = ['title' => $row->name, 'key' => strval($row->id),'label' => $row->name, 'id' => strval($row->id), 'value' => strval($row->id), 'isLeaf' => true];
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

    public function actionFzrbscompany(){
   
    $company = $this->_request['company'];
    $id = $this->_request['id'];
    $keyword = $this->_request['keyword'];

    if(!$company && !$id&&!$keyword) {
      echo json_encode(array());
      exit;
    }
    $sign = $this->_request['sign'];
    $where = [
      'and',[">","id",0]
    ];
    if ($company){
      $where = ['like', 'company', $company];
    }
    if ($id){
      
      $where = new Expression("id in ($id)");
    }
    if (is_string($keyword)&&$keyword){
      if (strpos($keyword, ',')!==false){
        $keyword = explode(',', $keyword);
        $where = ['in', 'id', $keyword];
      }else{
        $where = ['or',['like', 'company', $keyword],['=', 'id', $keyword]];
      }
      
    }
    if($sign){
      $where[]=['=','sign',$sign];
    }
    
    $datas = FzrbsCompany::find()->where($where)->orderBy('id desc')->limit(10)->asArray()->all();
    echo json_encode($datas,JSON_UNESCAPED_UNICODE);
    exit;
  }
  public function actionGetusers(){
    
    $keyword = $this->_request['keyword'];
    if ($keyword){
      $where=['or',['like','name',$keyword],['=','userid',$keyword]];
    }
    if (!$where){
      echo json_encode(array());
      exit;
    }
    $limit = $this->_request['limit'];
    if(!$limit) $limit = 20;
    $users = WeixinOAUserInfo::find()->select('id,userid,name,mobile,departmentname,departmentid')->where($where)->limit($limit)->asArray()->all();
    echo json_encode($users,JSON_UNESCAPED_UNICODE);
    exit;
  }
  public function actionDict(){
    $type = $this->_request['type'];
    $subtype = $this->_request['subtype'];
    $value = $this->_request['value'];
    if (!$type){
      echo json_encode(array());
      exit;
    }
    $where = [
      'and',
      ['=', 'type', $type]
    ];
    if ($subtype){
      $where[] = ['=', 'subtype', $subtype];
    }
    if ($value){
      $where[] = ['=', 'value', $value];
    }

    $datas = FzrbsBudgetDict::find()->where($where)->asArray()->all();
    echo json_encode($datas);
    exit;
  }

  private function safeSqlIn($value)
{
    if (is_array($value)) {
        $value = implode(',', $value);
    }
    // 只保留数字和逗号
    $clean = preg_replace('/[^0-9,]/', '', (string)$value);
    return $clean ?: '0';
}

/**
 * 转义字符串用于 SQL（模拟 real_escape_string）
 */
private function escape($string)
{
    // 如果有数据库连接，使用实际转义函数
    // return mysqli_real_escape_string($this->connection, $string);

    // 否则简单处理
    return addslashes((string)$string);
}
  private function getAdvitemWhere($json)
  {
      $where = [];

    
      

      if (!empty($json['withdeleted'])) {
        $where[] = "SYS_DOCUMENTID>0";
      }else{
        if ($json['SYS_DELETEFLAG']){
          $where[] = "SYS_DELETEFLAG=1";
        }else{
          $where[] = "SYS_DELETEFLAG=0";
        }
        

      }
      // 检查是否为数组或可访问对象
      if (!is_array($json) && !($json instanceof \ArrayAccess)) {
          return $where;
      }
      $advwhere = 'SYS_DOCUMENTID>0 ';

      $userId = $json['userid'];

      if ($userId){
        $depts = $this->getDepts($userId);
        // 构建与我相关的条件
        $myRelatedCondition = "(AO_Salesman_ID = '{$userId}' ";
        $myRelatedCondition .= " OR  AI_TradeID in (SELECT orgid from user_org WHERE userid='{$userId}') OR FIND_IN_SET('{$userId}', assistant) ";
        $myRelatedCondition .= " OR  SYS_AUTHORS = '{$userId}' ";
        $myRelatedCondition .= ")";        
        // 构建权限条件
        if (sizeof($depts) > 0) {
            // 没有指定部门时，需要查询我创建的或有权限的部门
            if (empty($this->_request['departmentid'])) {
                $permissionCondition = "(".$myRelatedCondition.") OR (departmentid IN (" . implode(',', $depts) . "))";
            } else {
                $permissionCondition = "(departmentid IN (" . implode(',', $depts) . "))";
            }
        } else {
            // 没有部门权限时，只能查询与我相关的
    
            $permissionCondition = $myRelatedCondition;
        }
        $advwhere .= " AND ({$permissionCondition})";
      }
      
  
      // 构建 advorder 子查询条件
      if (!empty($json['contractid'])) {
          $advwhere .= " and contractid in (" . $this->safeSqlIn($json['contractid']) . ")";
      }
      if (!empty($json['partb'])) {
          $advwhere .= " and partb in (" . $this->safeSqlIn($json['partb']) . ")";
      }
      if (!empty($json['assistant'])) {
          $advwhere .= " and `assistant`='" . $json['assistant'] . "'";
      }
      if (!empty($json['assistantdepartmentid'])) {
          $advwhere .= " and assistantdepartmentid in (" . $this->safeSqlIn($json['assistantdepartmentid']) . ")";
      }
      if (!empty($json['AI_Salesman'])) {
          $values = array_map(function ($v) {
              return "'" . $this->escape(trim($v)) . "'";
          }, explode(',', $json['AI_Salesman']));
          $advwhere.= " and AO_Salesman in (" . implode(',', $values) . ")";
      }
      if (!empty($json['AI_Salesman_ID'])) {
          $values = array_map(function ($v) {
              return "'" . $this->escape(trim($v)) . "'";
          }, explode(',', $json['AI_Salesman_ID']));
          $advwhere.= " and AO_Salesman_ID in (" . implode(',', $values) . ")";
      }

      
      if (!empty($json['orgids'])) {
          $advwhere .= " and AO_Org_ID in (" . $json['orgids'] . ")";
      }
    
      if (!empty($json['AI_Org_ID'])) {
          $advwhere .= " and AO_Org_ID in (" . $json['AI_Org_ID'] . ")";
      }


      if (strlen($advwhere) > 0) {
          $subQuery = "select SYS_DOCUMENTID from advorder where " . ltrim($advwhere, ' and');
          $where[] = "AI_OrderID in ({$subQuery})";
      }
     


     
      if (!empty($json['AI_OrderID'])) {
          $where[] = "AI_OrderID in (" . $json['AI_OrderID'] . ")";
      }
      if (!empty($json['AI_Customer_ID'])) {
          $where[] = "AI_Customer_ID in (" . $json['AI_Customer_ID'] . ")";
      }

      if (!empty($json['AI_Field_ID'])) {
          $where[] = "AI_Field_ID in (" .$json['AI_Field_ID'] . ")";
      }
      if (!empty($json['AI_Size_ID'])) {
          $where[] = "AI_Size_ID in (" .$json['AI_Size_ID'] . ")";
      }

      if (!empty($json['AI_CustomerLike'])) {
          $like = '%' . $this->escape($json['AI_CustomerLike']) . '%';
          $where[] = "AI_Customer like '{$like}'";
      }
      if (!empty($json['AI_Customer'])) {
          $like = '%' . $this->escape($json['AI_Customer']) . '%';
          $where[] = "AI_Customer like '{$like}'";
      }

      
      if (!empty($json['SYS_AUTHORS'])) {
          $like = '%' . $this->escape($json['SYS_AUTHORS']) . '%';
          $where[] = "SYS_AUTHORS like '{$like}'";
      }
      

      if (!empty($json['AI_PublishTimeStart'])) {
          $startTime = date('Y-m-d H:i:s', strtotime($json['AI_PublishTimeStart']));
          $where[] = "AI_PublishTime>='{$startTime}'";
      }
      if (!empty($json['AI_PublishTimeEnd'])) {
          $endTime = date('Y-m-d', strtotime($json['AI_PublishTimeEnd'] . ' +1 day'));
          $where[] = "AI_PublishEndTime<'{$endTime}'";
      }
      if (!empty($json['AI_PublishTime'])) {
          $startTime = date('Y-m-d H:i:s', strtotime($json['AI_PublishTime']));
          $where[] = "AI_PublishTime>='{$startTime}'";
      }
      if (!empty($json['AI_PublishEndTime'])) {
          $endTime = date('Y-m-d', strtotime($json['AI_PublishEndTime'] . ' +1 day'));
          $where[] = "AI_PublishEndTime<'{$endTime}'";
      }
   


      
      if (!empty($json['AI_Content'])) {
          $like = '%' . $this->escape($json['AI_Content']) . '%';
          $where[] = "AI_Content like '{$like}'";
      }
      if (!empty($json['AI_PayMode'])) {
          $where[] = "AI_PayMode='" . $this->escape($json['AI_PayMode']) . "'";
      }
      if (!empty($json['AI_InvoiceNo'])) {
          $like = '%' . $this->escape($json['AI_InvoiceNo']) . '%';
          $where[] = "AI_InvoiceNo like '{$like}'";
      }

      if (isset($json['isBalance']) && $json['isBalance'] === true) {
          $where[] = "AI_UnbalancedMoney != 0.0";
      }

      if (!empty($json['AI_Memo'])) {
          $like = '%' . $this->escape($json['AI_Memo']) . '%';
          $where[] = "AI_Memo like '{$like}'";
      }
      if (!empty($json['AI_Field'])) {
          $like = '%' . $this->escape($json['AI_Field']) . '%';
          $where[] = "AI_Field like '{$like}'";
      }
      if (!empty($json['AI_PubMemo'])) {
          $like = '%' . $this->escape($json['AI_PubMemo']) . '%';
          $where[] = "AI_PubMemo like '{$like}'";
      }

      if (!empty($json['matchDebt'])) {
          $x1 = substr($json['matchDebt'], 0, 1);
          $x2 = substr($json['matchDebt'], 1);
          $where[] = "AI_Debt{$x1}{$x2}";
      }
      if (!empty($json['matchReceived'])) {
          $x1 = substr($json['matchReceived'], 0, 1);
          $x2 = substr($json['matchReceived'], 1);
          $where[] = "AI_AmountReceived{$x1}{$x2}";
      }

      if (isset($json['haszgf']) && $json['haszgf']) {
          $where[] = "zgf > 0";
      }
      if (isset($json['unzgf']) && $json['unzgf']) {
          $where[] = "zgf = 0";
      }

      if (!empty($json['AI_Publication'])) {
          $values = array_map(function ($v) {
              return "'" . $this->escape(trim($v)) . "'";
          }, explode(',', $json['AI_Publication']));
          $where[] = "AI_Publication in (" . implode(',', $values) . ")";
      }
      
      if (!empty($json['AI_Publication_ID'])) {
          $where[] = "AI_Publication_ID in (" .$json['AI_Publication_ID'] . ")";
      }

      if (!empty($json['AI_TradeID'])) {
          $where[] = "AI_TradeID in (" . $this->safeSqlIn($json['AI_TradeID']) . ")";
      }
      if (!empty($json['SYS_DOCUMENTID'])) {
          $where[] = "SYS_DOCUMENTID in (" .$json['SYS_DOCUMENTID'] . ")";
      }


      return $where;
  }
  public function actionGetadvitems(){
      $total = 0;
      $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
      $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;
      $offset = $limit * ($page - 1);
      $contractid = $this->_request['contractid'];
      if (!$contractid) echo json_encode(array(),JSON_UNESCAPED_UNICODE); ;
      // 分页查询
  
      $result = Yii::$app->paymentdb->createCommand("SELECT * FROM advitem where AI_OrderID in (select SYS_DOCUMENTID from advorder where contractid=$contractid) limit $offset,$limit")->queryAll();
      $total = Yii::$app->paymentdb->createCommand("SELECT count(*) FROM advitem where AI_OrderID in (select SYS_DOCUMENTID from advorder where contractid=$contractid)")->queryScalar();
      $amount = Yii::$app->paymentdb->createCommand("SELECT sum(AI_AmountReceivable) FROM advitem where AI_OrderID in (select SYS_DOCUMENTID from advorder where contractid=$contractid)")->queryScalar();
      $result = array(
        'total'=>$total,
        'rows'=>$result,
        'amount'=>$amount,
      );
      echo json_encode($result,JSON_UNESCAPED_UNICODE);
      exit;
   }

   public function actionStatistics(){
    $par = json_decode(Yii::$app->request->getRawBody(), true);
    
    $where = $this->getAdvitemWhere($par);
    $fields = "sum(AI_AmountReceivable) as AI_AmountReceivable,sum(AI_AmountPaid) as AI_AmountPaid,sum(AI_AmountReceived) as AI_AmountReceived,sum(AI_Debt) as AI_Debt,sum(AI_AdvPages) as AI_AdvPages,sum(number) as number";
		$sql = "select ".$fields." from advitem ";
		if ($where){
			$sql.=" where ".join(' and ', $where);
		}
    $res = Yii::$app->paymentdb->createCommand($sql)->queryOne();
    // 查询结果转化成数字，并保留两位位小数
    // AI_AdvPages 要保留四位小数
    
    $res = array_map(function ($v) {
      return floatval($v);
    }, $res);
    $res['AI_AdvPages'] = floatval(sprintf("%.4f", $res['AI_AdvPages']));
    $res['version']=1;


    echo json_encode([$res],JSON_UNESCAPED_UNICODE);
    exit;
   }
   public function actionAdvitemslist(){
    $par = json_decode(Yii::$app->request->getRawBody(), true);
    $total = 0;
    $page = isset($par['pageIndex']) ? intval($par['pageIndex']) : 1;
    $limit = isset($par['pageSize']) ? intval($par['pageSize']) : 10;
    $offset = $limit * ($page - 1);
    $where = $this->getAdvitemWhere($par);
    $orderby = 'AI_PublishTime desc,SYS_DOCUMENTID desc';
    if ($orderby) {
      $orderby = " order by " . $orderby;
    }
      // 分页查询
  
    $conditions = join(' and ', $where);

    

		$temp = " (select * from advitem where ".$conditions." ".$orderby.") a LEFT JOIN advorder adv ON adv.SYS_DOCUMENTID=a.AI_OrderID ";



		$sql = "select a.*,adv.AO_Org as AI_Org, adv.contractid as contractid,adv.contractserial as contractserial,adv.assistantname,adv.partb,adv.partbname,adv.assistantdepartmentname,adv.departmentname,adv.AO_Salesman as AI_Salesman  from  $temp";


    

    $cnt = Yii::$app->paymentdb->createCommand("select count(*) as cnt from advitem where ".$conditions."")->queryOne();
    if ($cnt) {
      $total = $cnt['cnt'];
    }
    $sql .= " limit $offset,$limit";
 
    // 如果 pageSize为10000 且 columns 和 colnames 不为空，则生成Excel文件
    if ($limit == 10000 && !empty($par['columns']) && !empty($par['colnames'])) {
        // 清除任何之前的输出缓冲
        if (ob_get_level()) {
            ob_clean();
        }
        
        // 先执行查询获取所有数据
        $res = Yii::$app->paymentdb->createCommand($sql)->queryAll();
        
        // 解析列配置
        $columns = explode(',', $par['columns']);
        $colnames = explode(',', $par['colnames']);
        
        // 创建PHPExcel对象
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getActiveSheet()->setTitle('广告明细');
        
        // 设置表头
        $colIndex = 0;
        foreach ($colnames as $colname) {
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($colIndex, 1, trim($colname));
            $colIndex++;
        }
        
        // 填充数据
        $rowIndex = 2;
        foreach ($res as $rowData) {
            $colIndex = 0;
            foreach ($columns as $column) {
                $columnName = trim($column);
                $value = isset($rowData[$columnName]) ? $rowData[$columnName] : '';
                
                // 处理特殊字符，防止Excel格式问题
                if (is_string($value)) {
                    // 确保UTF-8编码
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
                //处理日期格式,比如2025-03-31 00:00:00，只要返回2025-03-31即可
                if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
                    $value = date('Y-m-d', strtotime($value));
                }
                
                
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($colIndex, $rowIndex, $value);
                $colIndex++;
            }
            $rowIndex++;
        }
        
        // 清除任何可能的输出缓冲，确保输出纯净的Excel文件
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // 设置HTTP头信息
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="广告明细导出.xlsx"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        header('Expires: 0');
        
        // 输出Excel文件
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        
        // 禁用输出缓冲并直接发送文件
        $objWriter->save('php://output');
        
        // 立即退出，确保不输出其他内容
        exit;
    }

    $res = Yii::$app->paymentdb->createCommand($sql)->queryAll();
    
    $result["pageIndex"] = $page;
    $result["pageSize"] = $limit;
    $result["total"] = $total;
    $result['rows'] = $res;

    // 如果 pageSize为10000
    // columns  不为空，比如：AI_Debt,AI_Salesman,AI_Customer,contractserial,AI_OrderID,SYS_DOCUMENTID,assistantname,parbname
    // colnames 不为空，比如: "欠款,业务员,客户,合同,订单号,广告号,协助人员"
    // 则将查询结果按照 columns中字段的顺序返回，xlsx文件

    echo json_encode($result,JSON_UNESCAPED_UNICODE);
    exit;

  }
  public function actionPricelist(){
      $par = json_decode(Yii::$app->request->getRawBody(), true);
      $E_PID = $par['E_PID'];
      $E_MID = $par['E_MID'];
      $E_AdField_ID = $par['E_AdField_ID'];
      $E_Color_ID = $par['E_Color_ID'];
      $E_AdSize_ID = $par['E_AdSize_ID'];
      $where="SYS_DOCUMENTID>0";
      if ($E_PID){
        $where.=" and E_PID in($E_PID)";
      }
      if ($E_MID){
        $where.=" and E_MID=$E_MID";
      }
      if ($E_AdField_ID){
        $where.=" and E_AdField_ID=$E_AdField_ID";
      }
      if ($E_Color_ID){
        $where.=" and E_Color_ID=$E_Color_ID";
      }
      if ($E_AdSize_ID){
        $where.=" and E_AdSize_ID=$E_AdSize_ID";
      }
      // 分页查询
      $result = Yii::$app->paymentdb->createCommand("select * from pricelistitem where $where order by SYS_DOCUMENTID desc limit 50")->queryAll();

      echo json_encode($result,JSON_UNESCAPED_UNICODE);
      exit;
   }
   private function getDepts($userid){
      $power = '查看';
  
      $arr = array();
      $user = $this->getUserinfo($userid);
      // 领导可以查看下属部门所有的订单
      $deptid = $user['departmentid'];
      if ($user['is_leader']){
          $depts = WeixinOaDepartment::findBySql("SELECT GROUP_CONCAT(id SEPARATOR ',') as ids from weixin_oa_department where id=$deptid or FIND_IN_SET($deptid,parentids)")->asArray()->one();
          if ($depts['ids']) {
              $arr = array_merge($arr,explode(',',$depts['ids']));
          }
      }

      // 查询角色权限
      $sql = "SELECT userid,dept from weixin_oa_flowrole where  userid='".$userid."' and role in (select id from weixin_oa_role where FIND_IN_SET('$power',powername))";
      $result = WeixinOaFlowrole::findBySql($sql)->asArray()->all();

      if ($result) {
          foreach ($result as $e) {
              $arr = array_merge($arr,explode(',',$e['dept']));
          }
          $arr = array_unique($arr);
      }

      return $arr;
  }
  /**
   * 获取行业树形结构
   * @return array
   */
  public function actionTrades()
  {
      try {
          // 查询所有行业节点
          $sql = "SELECT id, label, parentid, depth FROM org ";
          $nodes = Yii::$app->paymentdb->createCommand($sql)->queryAll();
          
          // 将数据转换成树形结构
          $tree = $this->buildTradeTree($nodes);
          
          echo json_encode($tree,JSON_UNESCAPED_UNICODE);
          exit;
       
      } catch (Exception $e) {
          return [

              'message' => '获取行业数据失败: ' . $e->getMessage(),

          ];
      }
  }

  /**
   * 递归构建行业树形结构
   * @param array $nodes 扁平节点数组
   * @param int $parentid 父节点ID
   * @return array
   */
  private function buildTradeTree($nodes, $parentid = 0)
  {
      $tree = [];
      foreach ($nodes as $node) {
          if ((int)$node['parentid'] === (int)$parentid) {
              $children = $this->buildTradeTree($nodes, $node['id']);
              $nodeData = [
                  'id' => (string)$node['id'],
                  'label' => $node['label'],
                  'title' => $node['label'],
                  'value' => (string)$node['id'],
                  'parentid' => (int)$node['parentid'],
                  'depth' => (int)$node['depth'],
              ];
              if (!empty($children)) {
                $nodeData['children'] = $children;
              }
              $tree[] = $nodeData;
          }
      }
      return $tree;
  }
    
}
