<?php
namespace app\modules\api\controllers;
use yii\web\Controller;
use app\modules\api\models\FzrbsContractPaycondition;
use app\modules\api\models\FzrbsContract;
class ContractnoticeController extends Controller
{
  public function init()
  {
      parent::init();
  }
  public function actionNotice(){

    $result = array("dead"=>$this->getdeadlinesql(),"over"=>$this->getoverduesql());
    echo json_encode($result);
    exit;
  }
  /**
   * 临期合同查询
   * (当前日期-合同签订日期)/DATEDIFF(回款日期,合同签订日期)>=0.8 and (当前日期-合同签订日期)/DATEDIFF(回款日期,合同签订日期)<=1  and 回款金额/合同总额<1
   * 只查询执行中的正常合同
   */
  private function getdeadlinesql(){
    return "select id from (SELECT c.id,c.title,c.amount,p.rate,p.date as paydate,c.paycollection, IFNULL(c.paycollection/c.amount,0) as payratio,DATEDIFF(NOW(),start)/DATEDIFF(date,start) as ratio  from ".FzrbsContractPaycondition::tableName()." p left join ".FzrbsContract::tableName()." c on p.contractid=c.id and c.state=0  order by p.date desc  ) a where ratio>=0.8 and ratio<=1  and payratio*100<rate  group by id";
  }
  /**
   * 逾期合同查询
   * (当前日期-回款日期)>0  and 回款总金额/合同总金额<1
   * 只查询执行中的正常合同
   */
  private function getoverduesql(){
    return "select id from (SELECT c.id,c.title,c.amount,p.rate,p.date as paydate,c.paycollection, IFNULL(c.paycollection/c.amount,0) as payratio,DATEDIFF(NOW(),date) as overdue  from ".FzrbsContractPaycondition::tableName()." p left join ".FzrbsContract::tableName()." c on p.contractid=c.id and c.state=0  order by p.date desc  ) a where overdue>0  and payratio*100<rate  group by id";
  }
}


