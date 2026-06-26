<?php

namespace app\modules\weixin\controllers;
use app\modules\weixin\commons\ApiBase;

use app\modules\api\commons\BudgetFlow;
use app\modules\api\commons\WorkflowParse;
use app\modules\api\controllers\BudgetController as ControllersBudgetController;
use app\modules\api\models\FzrbsBudgetBalance;
use app\modules\api\models\FzrbsBudgetDict;
use app\modules\api\models\FzrbsBudgetProject;
use app\modules\api\models\WeixinOaApprovalInfo;
use app\modules\api\models\WeixinOAUserInfo;
use Yii;


use app\modules\weixin\commons\Tools;
use Exception;
use yii\db\Expression;

class BudgetController extends ApiBase
{
    public $enableCsrfValidation = false;
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
    protected $ACT_AD_TYPE = 6;//活动促广告业务
    protected $PURE_NEWMEDIA_TYPE = 7;//纯新媒体业务
    protected $OTHERS_TYPE = 8; // 其他
    // 分管领导角色id
    protected $LEADER_ROLE_ID=6;
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
    public function init()
    {
        parent::init();
    }
    
    public function actionGetflowdata(){
      return $this->_runAction('BudgetController', 'getflowdata','GET');
  
    }
    public function actionTransfileurl(){
      return $this->_runAction('BudgetController', 'transfileurl','GET');
    }
    public function actionDebtlist(){
      return $this->_runAction('ContractController', 'debtlist','GET');
    }
    public function actionViewdebt(){
      return $this->_runAction('ContractController', 'viewdebt','GET');
    }
    public function actionStartdebturge(){
      return $this->_runAction('ContractController', 'startdebturge','GET');
    }
    public function actionDebtflowact(){
      return $this->_runAction('ContractController', 'debtflowact','GET');
    }
    public function actionPreviewdebtflow(){
      return $this->_runAction('ContractController', 'previewdebtflow','GET');
    }
     public function actionViewfile (){
      return $this->_runAction('BudgetController', 'viewfile','GET');
    }
    public function actionPaycollectioncheck(){
      return $this->_runAction('BudgetController', 'paycollectioncheck','GET');
  
    }
    public function actionPaycollectionchecklist(){
      return $this->_runAction('BudgetController', 'paycollectionchecklist','GET');
    }
    public function actionGetcontract(){
      return $this->_runAction('BudgetController', 'getcontract','GET');
    }
    public function actionFlowact(){
      return $this->_runAction('BudgetController', 'flowact','GET');

    }
  
    public function actionApprovallist(){
      return $this->_runAction('BudgetController', 'approvallist','GET');
    }
    public function actionHistorylist(){
      return $this->_runAction('BudgetController', 'historylist','GET');
    }
    public function actionGetprolist(){
      
      return $this->_runAction('BudgetController', 'getprolist','GET');
    }

    
    public function actionGetbudgetinfo(){
      
      return $this->_runAction('BudgetController', 'getbudgetinfo','GET');
    }
    public function actionGetbalancefileurls(){
      
      return $this->_runAction('BudgetController', 'getbalancefileurls','GET');
    }
    public function actionGetproject(){
      $id = $this->_request['id'];
      $info = FzrbsBudgetProject::find()->where(['id'=>$id])->asArray()->one();
      Tools::responseJson($info);
    }

    public function actionGetfileurlsbycontractids(){
      
      return $this->_runAction('BudgetController', 'getfileurlsbycontractids','GET');
    }
    
    
    public function actionFile (){
      $attachment = $this->_request['attachment'];
      $name = $this->_request['name'];
      $savepath = $this->_request['savepath'];
      $imagefile = $savepath.$attachment;
      // Tools::responseJson($imagefile);
      if(is_file($imagefile)){
          $filesize = filesize($imagefile);
          header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
          header("Content-Disposition: attachment;filename = ". ($name?trim($name):basename($attachment)));
          header("Content-Length: " . $filesize);
          header("Content-Type: application/octet-stream");
          if($filesize > 50*1024*1024){
              $fp=fopen($imagefile,'r');
              while (!feof($fp)){
                  $str=fread($fp, $filesize/10);//每次读出文件10分之1
                  echo $str;
              }
              fclose($imagefile);
              
          }else{
              echo file_get_contents($imagefile);
          }
      }
      exit;
  }
}

