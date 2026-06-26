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

class PhotodispatchController extends ApiBase
{
    protected $refController = 'PhotodispatchController';
    public $enableCsrfValidation = false; // 必须关闭csrf验证
    public function init()
    {
        parent::init();
    }

    
   
    public function actionFlowact(){
      return $this->_runAction($this->refController, 'flowact','POST'); 
    }
    public function actionSave(){
      return $this->_runAction($this->refController, 'save','POST');
    }
    public function actionGetdata(){
      return $this->_runAction($this->refController, 'getdata','GET');
    }
    public function actionGetflow() {
      return $this->_runAction($this->refController, 'getflow','GET');
    }
    public function actionGetpapers(){
      return $this->_runAction($this->refController, 'getpapers','GET');
    }
    public function actionViewpic() {
      return $this->_runAction($this->refController, 'viewpic','GET');
    }
    public function actionGetflowdata(){
      return $this->_runAction($this->refController, 'getflowdata','GET');
    }
    public function actionList(){
      return $this->_runAction($this->refController, 'list','GET');
    }


    public function actionFinishlist(){
      return $this->_runAction($this->refController, 'finishlist','GET');
    }
    public function actionGetnotifydata(){
      return $this->_runAction($this->refController, 'getnotifydata','GET');
    }
    public function actionInglist(){
      return $this->_runAction($this->refController, 'inglist','GET');
    }
    public function actionGettabs(){
      return $this->_runAction($this->refController, 'gettabs','GET');
    }
    public function actionGetreporters(){
      return $this->_runAction($this->refController, 'getreporters','GET');
    }

    public function actionRate(){
      return $this->_runAction($this->refController, 'rate','POST');
    }
    public function actionReporterlist(){
      return $this->_runAction($this->refController, 'reporterlist','GET');
    }
    public function actionReportst(){
      return $this->_runAction($this->refController, 'reportst','POST');
    }
    public function actionSavereporter(){
      return $this->_runAction($this->refController, 'savereporter','POST');
    }
    public function actionDelreporter(){
      return $this->_runAction($this->refController, 'delreporter','POST');
    }

    
}

