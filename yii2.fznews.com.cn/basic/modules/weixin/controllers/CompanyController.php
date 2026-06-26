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

class CompanyController extends ApiBase
{
    protected $refController = 'CompanyController';
    public function init()
    {
        parent::init();
    }
    public function actionGetcompany(){
      
      return $this->_runAction($this->refController, 'getcompany','GET');
  
    }

    
}

