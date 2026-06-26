<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;

/**
 * 用户操作日志接口类
 */
class OperationLogController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\FzrbsOperationLog';
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
        }
        if ($this->_request['realname']) {
            $where[] = ['=', 'realname', $this->_request['realname']];
        }
        if ($this->_request['catalog']) {
            $where[] = ['=', 'catalog', $this->_request['catalog']];
        }
        if ($this->_request['ip']) {
            $where[] = ['=', 'ip', $this->_request['ip']];
        }
        if ($this->_request['remark']) {
            $where[] = ['like', 'remark', $this->_request['remark']];
        }
        if ($this->_request['inserttime']) {
            $inserttime = explode(',', $this->_request['inserttime']);
            $starTime = strtotime($inserttime[0] . ' 00:00:00');
            $endTime = strtotime($inserttime[1] . ' 23:59:59');
            $where[] = ['between', 'inserttime', $starTime, $endTime];
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

    /**
     * 操作类型
     */
    public function actionOperationType()
    {
        $this->_result['data'] = ['新增' => ['text' => '新增'], '修改' => ['text' => '修改'], '删除' => ['text' => '删除'], '充值' => ['text' => '充值']];
        return $this->_result;
    }
}
