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

class AdvertisemanangeController extends ApiBase
{
    protected $refController = 'AdvertisemanangeController';
    public $enableCsrfValidation = false; // 必须关闭csrf验证
    public function init()
    {
        parent::init();
    }

    
    public function actionGetadvitemsbyorderid(){
      return $this->_runAction($this->refController, 'getadvitemsbyorderid','POST'); 
    }
  
    public function actionFlowact(){
      return $this->_runAction($this->refController, 'flowact','POST'); 
    }

    public function actionGetflow() {
      return $this->_runAction($this->refController, 'getflow','GET');
    }
 
  
    public function actionGetflowdata(){
      return $this->_runAction($this->refController, 'getflowdata','GET');
    }



    public function actionFinishlist(){
      return $this->_runAction($this->refController, 'finishlist','GET');
    }
    public function actionGetnotifydata(){
      return $this->_runAction($this->refController, 'getnotifydata','GET');
    }
    public function actionApprovallist(){
      return $this->_runAction($this->refController, 'approvallist','GET');
    }
    public function actionGettabs(){
      return $this->_runAction($this->refController, 'gettabs','GET');
    }

    
}

