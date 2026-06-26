<?php
namespace app\modules\api\commons;

use app\modules\api\models\FzrbsBudgetBalance;
use app\modules\api\models\FzrbsBudgetDict;
use app\modules\api\models\FzrbsBudgetProject;
use app\modules\api\models\WeixinOaApprovalInfo;
use Exception;

class BudgetFlow{
  private $_agentId; 
  private $_userid; 
  private $INCOME_DICID = 15;
  private $EXPEND_DICID = 16;
  // 项目类型
  protected $ACT_AD_TYPE = 6;//活动促广告业务
  protected $PURE_NEWMEDIA_TYPE = 7;//纯新媒体业务
  protected $OTHERS_TYPE = 8; // 其他
  public function __construct($userid=0,$agentid)
    {
        $this->_agentId = $agentid;
        $this->_userid = $userid;
        if (!$this->_agentId) throw new Exception('agentid为空');
        if (!$this->_userid) throw new Exception('userid为空');
    }
    public function agree($postdatas){
      $userid = $this->_userid;
      $agentid = $this->_agentId;
    //   if (!$postdatas['thirdNo']) throw new Exception('thirdNo为空');
    //   $data = WeixinOaApprovalInfo::find()->alias('a')->select("a.*,p.offline,p.id as projectid")->leftJoin(['p'=>FzrbsBudgetProject::tableName()],"p.thirdno=a.thirdNo")->where(['and',['=','a.thirdNo',$postdatas['thirdNo']],['=','agentId',$agentid]])->asArray()->one();
    //   // 判断是否被锁定
    //   if ($data['offline']==1){
    //     throw new Exception('thirdNo为空');
    //     return array('errorMessage'=>'线下上会审批中，不能操作');
    //   }
    //   // 是否是当前审批人
    //   if ($data['approvalUserid'] && !in_array($userid,explode('|',$data['approvalUserid']))){
    //     throw new Exception('thirdNo为空');
    //     return array('errorMessage'=>'当前审批人是：'.$data['approvalUsername']);
    //   }
      
    //   $status = 2;
  
    //   try {
    //     $ret = $this->changeStatus($userid,$data['id'],$postdatas['thirdNo'],$status,$postdatas);
       
        
    //     $this->updateAfterFlowChange($ret,$postdatas['thirdNo'],$status,$postdatas);
      
    //   } catch (\Throwable $th) {
        
    //     throw $th;
    //   }
      
    //   return array('data'=>$ret);
    }
    public function getbudgetinfo($id){
      $resp = array('errorMessage'=>'');
      if ($id) { // 项目id
        try {
          $budgettaxtotal = 0; // 税费
          $finaltaxtotal = 0; // 税费
  
          $expendtotal = 0; // 实际预算支出
          $finalexpend = 0; // 实际决算支出
  
  
          $project = FzrbsBudgetProject::find()->alias('p')->select("p.*,sum(CASE WHEN b.type=$this->INCOME_DICID THEN b.budget ELSE 0 END) as budgetincome,SUM(CASE WHEN b.type=$this->EXPEND_DICID THEN b.budget ELSE 0 END) as budgetexpend,sum(CASE WHEN b.type=$this->INCOME_DICID THEN b.final ELSE 0 END) as finalincome,SUM(CASE WHEN b.type=$this->EXPEND_DICID THEN b.final ELSE 0 END) as finalexpend")->leftJoin(['b'=>FzrbsBudgetBalance::tableName()],"b.projectid=p.id")->where(['p.id'=>$id])->asArray()->one();
          if (!$project){
            return array('errorMessage'=>'项目不存在，检查id是否正确');
          }
          
  
          
          // 收入明细
          $incomeResult = [];
          $incomes = FzrbsBudgetBalance::find()->alias('b')->select('b.*,d.label as moneytypename')->leftJoin(['d'=>FzrbsBudgetDict::tableName()],'d.id=b.moneytype')->where(['and',['=','b.projectid',$id],['=','b.type',$this->INCOME_DICID]])->orderBy('id asc')->asArray()->all();
         
          $incometotal = 0;
          $finalincome = 0;
          foreach ($incomes as $element) {
  
            // 汇总统计同类型的收入
           
  
            $finalincome = $finalincome + $element['final'];
            $incometotal = $incometotal + $element['budget'];
            $budgettaxtotal = $budgettaxtotal + $element['budget']*$element['tax']/100;
            $finaltaxtotal = $finaltaxtotal + $element['final']*$element['finaltax']/100;
  
           
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
              $budgettaxtotal = $budgettaxtotal - $element['budget']*$element['tax']/100;
              $finaltaxtotal = $finaltaxtotal - $element['final']*$element['finaltax']/100;
            }
            
      
            $temp = ['id'=>$element['id'],'title'=>$element['title'],'budget'=>$element['budget'],'final'=>$element['final'],'budgetnote'=>$element['budgetnote'],'finalnote'=>$element['finalnote']];
  
            $expendResult[] = $temp;
          }
  
          $budgettaxtotal=$budgettaxtotal>0?$budgettaxtotal:0;
          $finaltaxtotal=$finaltaxtotal>0?$finaltaxtotal:0;
  
          
          
          // 执行绩效奖励,（活动收入-支出-税费）*绩效比例
          $budgetbonus = ($project['budgetincome']-$project['budgetexpend']-$budgettaxtotal)*$project['performanceratio']/100;
          $finalbonus = ($project['finalincome']-$project['finalexpend']-$finaltaxtotal)*$project['performanceratio']/100;
          $budgetbonus =$budgetbonus>=0?$budgetbonus:0;
          $finalbonus =$finalbonus>=0?$finalbonus:0;
  
          // “活动促广告”业务不需要计算税费,// 绩效：支出*比例
          if ($project['type']==$this->ACT_AD_TYPE){
            $budgettaxtotal=0;
            $finaltaxtotal=0;
            $budgetbonus = ($project['budgetexpend']-$budgettaxtotal)*$project['performanceratio']/100;
            $finalbonus = ($project['finalexpend']-$finaltaxtotal)*$project['performanceratio']/100;
          }
          
  
          $expendtotal=$expendtotal+$budgettaxtotal+$budgetbonus;
          $finalexpend=$finalexpend+$finaltaxtotal+$finalbonus;
  
          
  
          // 预算收支总表
          $summary = [
            ['title'=>'总收入','budget'=>$project['budgetincome'],'final'=>$project['finalincome']],
            ['title'=>'总支出','budget'=>$expendtotal,'final'=>$finalexpend,'memo'=>'支出占比:'.(number_format($project['budgetincome']>0?$expendtotal/$project['budgetincome']:0,4)*100)."%"]
          ];
          
  
          if ($project['budgetincome']){
            $t = ['title'=>'毛利润','budget'=>$project['budgetincome']-$expendtotal,'final'=>0];
            if ($project['finalincome']) {
              $t['final'] = $project['finalincome']-$finalexpend;
              $summary[1]['memo']='支出占比:'.(number_format($finalexpend/$project['finalincome'],4)*100)."%";
              $summary[0]['memo'].=$this->generateDif($project['finalincome']-$project['budgetincome']);
            }
            $summary[]=$t;
          }
          $resp['budget'][]=$summary;
          
          $resp['budget'][]=$incomeResult;
          $expendResult[] = ['title'=>'税费','budget'=>$budgettaxtotal,'final'=>$finaltaxtotal];
          $expendResult[] = ['title'=>'执行绩效奖励','budget'=>$budgetbonus,'final'=>$finalbonus];
          $expendResult[] = ['title'=>'合计','budget'=>$expendtotal,'final'=>$finalexpend];
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
    private function generateDif($dif){
      if ($dif>0) {
        return '实际收入较预算增加'.$dif.'元';
      } else if ($dif==0){
        return '预决算一致';
      } else{
        return '实际收入较预算减少'.(-$dif).'元';
      }
    }
}