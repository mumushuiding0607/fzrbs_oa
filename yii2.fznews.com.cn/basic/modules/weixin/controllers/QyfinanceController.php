<?php

namespace app\modules\weixin\controllers;
use app\modules\weixin\commons\ApiBase;

use app\modules\api\commons\WorkflowParse;
use app\modules\api\controllers\InvoicingController as ControllersInvoicingController;
use app\modules\api\models\FzrbsBudgetDict;
use app\modules\api\models\WeixinOaApprovalInfo;
use app\modules\api\models\WeixinOAUserInfo;
use Yii;

use app\modules\weixin\commons\Tools;
use Exception;
use yii\db\Expression;

class QyfinanceController extends ApiBase
{
  public $enableCsrfValidation = false; // 必须关闭csrf验证
  protected $refController = 'QyfinanceController';
    public function init()
    {
        parent::init();
    }
    public function actionInglist(){
      
      return $this->_runAction($this->refController, 'inglist','GET');
  
    }
    public function actionAlterspeech(){
      return $this->_runAction($this->refController, 'alterspeech','POST');
    }
    public function actionSetposition() {
      return $this->_runAction($this->refController, 'setposition','POST');
    }
    public function actionUpdateapproval(){
      return $this->_runAction($this->refController, 'updateapproval','POST');
    }
    public function actionList(){
      
      return $this->_runAction($this->refController, 'list','GET');
  
    }

    public function actionFinishlist(){
      
      return $this->_runAction($this->refController, 'finishlist','GET');
  
    }
    public function actionHistorylist(){
      
      return $this->_runAction($this->refController, 'historylist','GET');
  
    }

    public function actionGetnotifydata(){
      
      return $this->_runAction($this->refController, 'getnotifydata','GET');
  
    }
    public function actionGetflowdata(){
      
      return $this->_runAction($this->refController, 'getflowdata','GET');
  
    }

    public function actionGetpayers(){
      
      return $this->_runAction($this->refController, 'getpayers','GET');
    }
    public function actionIscrossdept(){
      return $this->_runAction($this->refController, 'iscrossdept','GET');
    }
    public function actionGetbankaccount(){
      return $this->_runAction($this->refController, 'getbankaccount','GET');
    }
    
    public function actionIsinsidecompany(){
      return $this->_runAction($this->refController, 'isinsidecompany','GET');
    }
    public function actionGetusers(){
      return $this->_runAction($this->refController, 'getusers','GET');
    }
    public function actionGetcontract(){
      return $this->_runAction($this->refController, 'getcontract','GET');
    }
    public function actionGetinvoice(){
      return $this->_runAction($this->refController, 'getinvoice','GET');
    }
   public function actionGetflow() {
    return $this->_runAction($this->refController, 'getflow','GET');
   }

   public function actionSave(){
    return $this->_runAction($this->refController, 'save','POST');
   }
   public function actionViewpic() {
    return $this->_runAction($this->refController, 'viewpic','GET');
   }
   public function actionGetdata(){
    return $this->_runAction($this->refController, 'getdata','GET');
   }
   public function actionFlowact(){
    return $this->_runAction($this->refController, 'flowact','POST');
   }
   public function actionVerify() {
    return $this->_runAction($this->refController, 'verify','GET');
   }
   public function actionGetdriverleader(){
    return $this->_runAction($this->refController, 'getdriverleader','GET');
   }
   public function actionGettabs(){
      return $this->_runAction($this->refController, 'gettabs','GET');
   }
    
}

