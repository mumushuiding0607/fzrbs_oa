<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;
use app\modules\api\models\WeixinSuggest;

/**
 * 意见建议相关接口类
 */
class SuggestController extends ApiBase
{
    public $enableCsrfValidation = false;
    protected $_orderBy = 'id desc';
    protected $_type = [
        1 => ['text' => '从严治党对照整改意见征集'],
        2 => ['text' => '其他'],
    ];

    public function init()
    {
        parent::init();
    }

    /**
     * 意见建议列表动作
     */
    public function actionList()
    {
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if ($this->_request['username']) {
            $where[] = ['=', 'username', $this->_request['username']];
        }
        if ($this->_request['type']) {
            $where[] = ['=', 'type', $this->_request['type']];
        }
        if ($this->_request['message']) {
            $where[] = ['like', 'message', $this->_request['message']];
        }
        if ($this->_request['inserttime']) {
            $inserttime = explode(',', $this->_request['inserttime']);
            $starTime = $inserttime[0] . ' 00:00:00';
            $endTime = $inserttime[1] . ' 23:59:59';
            $where[] = ['between', 'inserttime', $starTime, $endTime];
        }
        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        $model = new WeixinSuggest;
        $model = $model::find()->where($where);
        $total = $model->count();
        $result['total'] = $total;
        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
        $result['data'] = $res;
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result]);
    }

    /**
     * 保存内容
     */
    public function actionSave()
    {
        $type = intval($this->_request['type']);
        $content = $this->_request['content'];
        if ($this->_UserId && $type && $content) {
            $model = new WeixinSuggest();
            $model->message = $content;
            $model->type = $type;
            $model->userid = $this->_UserId;
            $model->username = $this->_userInfo->name;
            $model->img = $this->_userInfo->avatar;
            $model->inserttime = date('Y-m-d H:i:s');
            $model->save();
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => '']);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
        }
    }

    /**
     * 意见建议分类
     */
    public function actionType()
    {
        $data = $this->_type;
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $data]);
    }
}
