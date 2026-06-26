<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;

/**
 * 企业号应用接口配置管理相关接口类
 */
class AppInterfaceController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinQYAppInterface';
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
        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        $where = [
            'and',
            ['>', 'id', 0],
        ];
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
     * 重写create的业务实现动作
     */
    public function actionCreate()
    {
        if ($this->_request['values']) {
            $model = new $this->modelClass(['scenario' => 'create']);
            $model->attributes = $this->_request['values'];
            $ruleResult = Tools::modelRules($model, 9000);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '新增';
                    $remark = $action . "企业号应用接口。名称：" . $model->appname . '。';
                    $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
                }
            } else {
                $this->_result['errorCode'] = $ruleResult['errorCode'];
                $this->_result['errorMessage'] = $ruleResult['errorMessage'];
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 重写update的业务实现动作
     */
    public function actionUpdate()
    {
        $id = intval($this->_request['id']);
        if ($id) {
            $model = $this->modelClass::findOne($id);
            $model->scenario = 'update';
            $oldName = $model->appname;
            $oldAppId = $model->appid;
            $model->attributes = $this->_request['values'];
            $ruleResult = Tools::modelRules($model, 9001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '修改';
                    $remark = $action . "企业号应用接口。" . ($oldName != $model->appname ? '应用名称由 ' . $oldName . ' 改为 ' . $model->appname . '。' : '') . ($oldAppId != $model->appid ? ($oldName == $model->appname ? '名称：' . $model->appname . '，' : '') . '应用id由 ' . $oldAppId . ' 改为 ' . $model->appid . '。' : '');
                    $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
                }
            } else {
                $this->_result['errorCode'] = $ruleResult['errorCode'];
                $this->_result['errorMessage'] = $ruleResult['errorMessage'];
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 重写delete的业务实现动作
     */
    public function actionDelete()
    {
        if ($this->_request['id']) {
            $ids = explode(',', $this->_request['id']);
            $names = [];
            $models = $this->modelClass::find()->where(['in', 'id', $ids])->all();
            foreach ($models as $model) {
                $names[] = $model->appname;
                $model->delete();
            }
            if ($names) {
                $action = '删除';
                $remark = $action . "企业号应用接口。名称：" . implode(',', $names) . "。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }
}
