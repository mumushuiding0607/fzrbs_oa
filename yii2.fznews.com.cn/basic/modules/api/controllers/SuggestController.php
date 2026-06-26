<?php

namespace app\modules\api\controllers;

use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;

/**
 * 意见建议相关接口类
 */
class SuggestController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinSuggest';
    protected $_orderBy = 'inserttime desc';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }

    public function init()
    {
        parent::init();
        $this->_permissionDeny();
    }

    /**
     * 重写index的业务实现动作
     */
    public function actionIndex()
    {
        $current = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $pageSize = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $postData = ['current' => $current, 'pageSize' => $pageSize];
        if (isset($this->_request['username'])) {
            $postData['username'] = $this->_request['username'];
        }
        if (isset($this->_request['message'])) {
            $postData['message'] = $this->_request['message'];
        }
        if (isset($this->_request['type'])) {
            $postData['type'] = $this->_request['type'];
        }
        if (isset($this->_request['inserttime'])) {
            $postData['inserttime'] = $this->_request['inserttime'];
        }
        $url = Yii::$app->params['apiPrefix'] . 'weixin/suggest/list';
        $this->_result['current'] = $current;
        $this->_result['pageSize'] = $pageSize;
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
     * 意见建议分类
     */
    public function actionType()
    {
        $url = Yii::$app->params['apiPrefix'] . 'weixin/suggest/type';
        $result = Tools::locaApi([], $url);
        if (!isset($result['errorMessage'])) {
            $this->_result['data'] = $result;
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }
}
