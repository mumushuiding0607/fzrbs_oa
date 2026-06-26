<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;
use app\modules\weixin\commons\CanteenConfig;
use app\modules\api\models\ShitangCanteenMenu;
use app\modules\api\models\WeixinStaff;
use app\modules\api\models\ShitangCanteenOrder;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\models\WeixinRechargeLog;
use Exception;
use linslin\yii2\curl;

/**
 * 食堂开饭了应用相关接口类
 */
class CanteenController extends ApiBase
{
    public $enableCsrfValidation = false;


    public function init()
    {
        parent::init();
       
    }
    public function actionList()
    {
        $data = [];
        throw new Exception('接口已关闭');
        
    }
}