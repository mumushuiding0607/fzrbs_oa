<?php

namespace app\modules\api\controllers\apps;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use Yii;

/**
 * 食堂开饭了应用相关接口类
 */
class CanteenController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\ShitangCanteenMenu';
    protected $_orderBy = 'id desc';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }

    public function init()
    {
        parent::init();
        $this->_checkUserBindWx();
    }

    /**
     * 重写index的业务实现动作
     */
    public function actionIndex()
    {
        $typeId = $this->_request['typeId'];
        $postData = ['typeId' => $typeId];
        if (isset($this->_request['flag'])) {
            $postData['flag'] = $this->_request['flag'];
        }
        $url = Yii::$app->params['apiPrefix'] . 'weixin/canteen/list';
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $this->_result['data'] = $result['data'];
            $this->_result['today'] = $result['today'];
            $this->_result['tomorrow'] = $result['tomorrow'];
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }

    /**
     * 配置数据
     */
    public function actionConfigData()
    {
        $postData = ['wxuserid' => $this->_adminInfo['wxuserid']];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/canteen/config-data';
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $types = [];
            $orderType = $result['orderType'];
            if ($orderType) {
                foreach ($orderType as $k => $v) {
                    if ($v == '早餐') {
                        $first = ['id' => $k, 'title' => $v, 'flag' => 'breakfast'];
                    }
                    if (!in_array($k, [3, 4, 6, 7, 100])) {
                        $data =  ['id' => $k, 'title' => $v];
                        if ($k == '2') {
                            $data['flag'] = 'dinner';
                        }
                        $types[] = $data;
                    }
                }
            }
            if ($first) {
                array_unshift($types, $first);
            }
            $this->_result['data'] = ['today' => date('Y-m-d'), 'types' => $types, 'timeInterval' => $result['timeInterval'], 'useTime' => $result['useTime'], 'notice' => $result['notice'], 'reporter' => $result['reporter'], 'holiday' => $result['holiday'], 'leader' => $result['leader'], 'now' => date('Y-m-d H:i:s'), 'dingcantime' => $result['dingcantime'], 'daigoutime1' => $result['daigoutime1'], 'daigoutime2' => $result['daigoutime2']];
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }

    /**
     * 保存订单
     */
    public function actionSaveOrder()
    {
        $payType = intval($this->_request['paytype']);
        $menuDate = $this->_request['menudate'];
        $typeId = $this->_request['typeid'];
        $publicTime = $this->_request['public'];
        $menus = $this->_request['menus'];
        $postData = ['wxuserid' => $this->_adminInfo['wxuserid'], 'paytype' => $payType, 'menudate' => $menuDate, 'typeid' => $typeId, 'public' => $publicTime, 'menus' => $menus];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/canteen/save-order';
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }

    /**
     * 食堂账号信息
     */
    public function actionAccount()
    {
        $postData = ['wxuserid' => $this->_adminInfo['wxuserid']];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/canteen/account';
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $this->_result['data'] = $result;
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }

    /**
     * 我的订单
     */
    public function actionMyOrder()
    {
        $current = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $pageSize = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $postData = ['wxuserid' => $this->_adminInfo['wxuserid'], 'current' => $current, 'pageSize' => $pageSize];
        if (isset($this->_request['status'])) {
            $postData['status'] = $this->_request['status'];
        }
        if (isset($this->_request['orderTime'])) {
            $postData['orderTime'] = $this->_request['orderTime'];
        }
        $this->_result['data']['current'] = $current;
        $this->_result['data']['pageSize'] = $pageSize;
        $url = Yii::$app->params['apiPrefix'] . 'weixin/canteen/my-order';
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $this->_result['data']['data'] = $result['data'];
            $this->_result['data']['total'] = $result['total'];
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }

    /**
     * 我的充值日志
     */
    public function actionRechargeLog()
    {
        $current = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $pageSize = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $postData = ['wxuserid' => $this->_adminInfo['wxuserid'], 'current' => $current, 'pageSize' => $pageSize];
        $this->_result['current'] = $current;
        $this->_result['pageSize'] = $pageSize;
        $url = Yii::$app->params['apiPrefix'] . 'weixin/canteen/recharge-log';
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $this->_result['data'] = $result['data'];
            $this->_result['total'] = $result['total'];
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }

    /**
     * 食堂订单代领
     */
    public function actionShareOrder()
    {
        $orderId = intval($this->_request['orderId']);
        $shareUserId = $this->_request['shareUserId'];
        $postData = ['wxuserid' => $this->_adminInfo['wxuserid'], 'orderId' => $orderId, 'shareUserId' => $shareUserId];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/canteen/share-order';
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $this->_result['shareUserName'] = $result['shareUserName'];
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }

    /**
     * 食堂订单转让
     */
    public function actionSellOrder()
    {
        $orderId = intval($this->_request['orderId']);
        $shareUserId = $this->_request['shareUserId'];
        $postData = ['wxuserid' => $this->_adminInfo['wxuserid'], 'orderId' => $orderId, 'shareUserId' => $shareUserId];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/canteen/sell-order';
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $this->_result['shareUserName'] = $result['shareUserName'];
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }

    /**
     * 食堂订单取消关闭
     */
    public function actionCloseOrder()
    {
        $orderId = $this->_request['orderId'];
        $postData = ['wxuserid' => $this->_adminInfo['wxuserid'], 'orderId' => $orderId];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/canteen/close-order';
        $result = Tools::locaApi($postData, $url);
        if (isset($result['errorMessage'])) {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }
}
