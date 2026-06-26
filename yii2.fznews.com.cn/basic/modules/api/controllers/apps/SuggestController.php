<?php

namespace app\modules\api\controllers\apps;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use Yii;

/**
 * 意见建议相关接口类
 */
class SuggestController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinNews';
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
        $current = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $pageSize = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $postData = ['current' => $current, 'pageSize' => $pageSize];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/suggest/list';
        $this->_result['data']['current'] = $current;
        $this->_result['data']['pageSize'] = $pageSize;
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $this->_result['data'] = $result['data'];
            $this->_result['data'] = $result['total'];
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }

    /**
     * 保存内容
     */
    public function actionSave()
    {
        $type = $this->_request['type'];
        $content = $this->_request['content'];
        $postData = ['wxuserid' => $this->_adminInfo['wxuserid'], 'type' => $type, 'content' => $content];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/suggest/save';
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
     * 重写view的业务实现
     */
    public function actionType()
    {

        $postData = [];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/suggest/type';
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $this->_result['data'] = $result;
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }
}
