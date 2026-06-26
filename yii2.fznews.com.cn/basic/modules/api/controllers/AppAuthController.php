<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;

/**
 * 应用管理员权限操作接口类
 */
class AppAuthController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinOaAuth';
    protected $_orderBy = '`id` desc';
    protected $_agentId = 1000037;

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
        $agentId = isset($this->_request['agentid']) ? intval($this->_request['agentid']) : 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);

        $where = [
            'and',
            ['=', 'agentid', $agentId],
        ];
        if ($this->_request['authName']) {
            $where[] = ['like', 'authName', $this->_request['authName']];
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
     * 重写create的业务实现动作
     */
    public function actionCreate()
    {
        if ($this->_request['values']) {
            $model = new $this->modelClass(['scenario' => 'create']);
            $values = $this->_request['values'];
            $values['sysusers'] = $values['sysusers']?implode(',',$values['sysusers']):'';
            $values['wxusers'] = $values['wxusers']?implode(',',$values['wxusers']):'';
            $values['modules'] = $values['modules']?implode(',',$values['modules']):'';
            $values['actions'] = $values['actions']?implode(',',$values['actions']):'';
            $values['departments'] = $values['departments']?implode(',',$values['departments']):'';
            $values['agentid'] = $values['agentid'];
            $model->attributes = $values;
            $ruleResult = Tools::modelRules($model, 4000);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $this->_result['lastid'] = $model->id;
                    $action = '[应用权限管理]新增';
                    $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，APPID=".$values['agentid']."，新增权限：" . $model->authName . '。';
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
            $values = $this->_request['values'];
            $values['sysusers'] = $values['sysusers']?implode(',',$values['sysusers']):'';
            $values['wxusers'] = $values['wxusers']?implode(',',$values['wxusers']):'';
            $values['modules'] = $values['modules']?implode(',',$values['modules']):'';
            $values['actions'] = $values['actions']?implode(',',$values['actions']):'';
            $values['departments'] = $values['departments']?implode(',',$values['departments']):'';
            $values['agentid'] = $values['agentid'];
            $oldName = $model->authName;
            $model->attributes = $values;
            $ruleResult = Tools::modelRules($model, 4001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '[应用权限管理]修改';
                    $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，APPID=".$values['agentid']."，" . ($oldName != $model->authName ? ' 由 ' . $oldName . ' 改为 ' . $model->authName . '。' : '');
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
            $agentId = isset($this->_request['agentid']) ? intval($this->_request['agentid']) : 0;
            $models = $this->modelClass::find()->where(['in', 'id', $ids])->asArray()->all();
            $this->modelClass::deleteAll(['agentid'=>$agentId], "id in(".implode(',', $ids).")");
            $names = array_column($models,'name');
            if ($names) {
                $action = '[应用权限管理]删除';
                $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，APPID=".$values['agentid']."，删除权限：" . implode(',', $names) . "";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }
}