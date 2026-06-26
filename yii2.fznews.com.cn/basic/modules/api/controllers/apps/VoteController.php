<?php

namespace app\modules\api\controllers\apps;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use Yii;

/**
 * 员工评议相关接口类
 */
class VoteController extends ApiBase
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
        $postData = ['wxuserid' => $this->_adminInfo['wxuserid'], 'current' => $current, 'pageSize' => $pageSize];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/vote/list';
        $this->_result['data']['current'] = $current;
        $this->_result['data']['pageSize'] = $pageSize;
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
     * 评议详情动作
     */
    public function actionViewInfo()
    {
        $id = $this->_request['id'];
        $postData = ['wxuserid' => $this->_adminInfo['wxuserid'], 'id' => $id];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/vote/view-info';
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
     * 保存评议记录动作
     */
    public function actionSaveVote()
    {
        $id = $this->_request['id'];
        $data = $this->_request['data'];
        unset($data['title']);
        $postData = ['wxuserid' => $this->_adminInfo['wxuserid'], 'id' => $id, 'data' => $data];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/vote/save-vote';
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
