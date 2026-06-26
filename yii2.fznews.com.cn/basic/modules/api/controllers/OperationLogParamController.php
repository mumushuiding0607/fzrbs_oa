<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;

/**
 * 用户操作日志参数接口类
 */
class OperationLogParamController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\FzrbsOperationLogParams';
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
        $logId = $this->_request['logid'];
        $where = ['=', 'logid', $logId];
        $model = $this->modelClass;
        $model = $model::find()->where($where)->one();
        if ($model) {
            $this->_result['data'] = $model->params;
        }
        return $this->_result;
    }
}
