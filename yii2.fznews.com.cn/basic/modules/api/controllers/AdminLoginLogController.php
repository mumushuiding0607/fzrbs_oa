<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;

/**
 * 用户登录日志接口类
 */
class AdminLoginLogController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\FzrbsLoginLog';
    protected $_orderBy = 'inserttime desc';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }

    /**
     * 重写index的业务实现
     */
    public function actionIndex()
    {
        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if ($this->_request['username']) {
            $where[] = ['=', 'username', $this->_request['username']];
        } else {
            // 普通用户只能查看自己的数据
            if ($this->_adminInfo['usertype'] == 0) {
                $where[] = ['=', 'username', $this->_adminInfo['username']];
            }
        }
        if ($this->_request['realname']) {
            $where[] = ['=', 'realname', $this->_request['realname']];
        }
        if ($this->_request['logintype']) {
            $where[] = ['=', 'logintype', $this->_request['logintype']];
        }
        if ($this->_request['ip']) {
            $where[] = ['=', 'ip', $this->_request['ip']];
        }
        if ($this->_request['logtype']) {
            $where[] = ['=', 'logtype', $this->_request['logtype']];
        }
        if ($this->_request['remark']) {
            $where[] = ['like', 'remark', $this->_request['remark']];
        }
        if ($this->_request['inserttime']) {
            $inserttime = explode(',', $this->_request['inserttime']);
            $starTime = $inserttime[0] . ' 00:00:00';
            $endTime = $inserttime[1] . ' 23:59:59';
            $where[] = ['between', 'inserttime', $starTime, $endTime];
        }
        $this->_request = ArrayHelper::htmlDecode($this->_request);
        $sorter = Json::decode($this->_request['sorter'], true);
        if ($sorter) {
            if (isset($sorter['inserttime'])) {
                $this->_orderBy = "inserttime " . str_replace('end', '', $sorter['inserttime']);
            }
        }
        $model = $this->modelClass;
        $model = $model::find()->where($where);
        $total = $model->count();
        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        $this->_result['data'] = $res;
        return $this->_result;
    }
}
