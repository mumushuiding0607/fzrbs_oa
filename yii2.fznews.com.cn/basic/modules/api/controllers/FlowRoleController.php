<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\models\WxDepartment;
use app\modules\api\models\WeixinOaRole;
use app\modules\api\models\WeixinQYAppInterface;
use app\modules\api\models\WeixinFinanceCompany;
use app\modules\api\models\WeixinOaUserinfo;

/**
 * 流程角色管理相关接口类
 */
class FlowRoleController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinOaFlowrole';
    protected $_orderBy = 'id asc';    
	private $_levels = ['普通员工','中层正职','中层副职'];

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
        $roleId = isset($this->_request['role']) ? $this->_request['role'] : 0;
        $username = isset($this->_request['username']) ? $this->_request['username'] : '';
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if($roleId){
            $where[] = ['=', 'role', $roleId];
        }
        if(isset($this->_request['type'])){
            $where[] = ['=', 'type', $this->_request['type']];
        }
        if($username){
            $where[] = ['like', 'username', $username];
        }
        $roles = WeixinOaRole::find()->where(['>','id',0])->asArray()->all();
        $roleArr = [];
        foreach($roles as $row){
            $roleArr[$row['id']] = $row['rolename'];
        }
        $model = $this->modelClass;
        $model = $model::find()->where($where);
        $total = $model->count();
        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->asArray()->all();
        $data = [];
        foreach($res as $row){
            $item = $row;
            $item['rolename'] = $roleArr[$row['role']];
            $data[] = $item;
        }
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        $this->_result['data'] = $data;
        return $this->_result;
    }

    /**
     * 重写create的业务实现动作
     */
    public function actionCreate()
    {
        if ($this->_request['values']) {
            $model = new $this->modelClass;
            $values = $this->_request['values'];
            $dept = is_array($values['dept'])?$values['dept']:[$values['dept']];
            $dept = $this->getMergeChildDepartments($dept);
            $values['dept'] = implode(',',$dept);
            $values['agent'] = $values['agent']?implode(',',$values['agent']):'';
            $values['company'] = $values['company']?implode(',',$values['company']):'';
            $values['level'] = $values['level']?implode(',',$values['level']):'';
            $userinfo = $this->getUserinfo($values['userid']);
            $values['username'] = $userinfo['name'];
            $model->attributes = $values;
            $ruleResult = Tools::modelRules($model, 4000);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $this->_result['lastid'] = $model->id;
                    $action = '[流程角色管理]新增';
                    $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，流程角色。ID：".$model->id."名称：" . $model->username . '。';
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
            $values = $this->_request['values'];
            $dept = is_array($values['dept'])?$values['dept']:[$values['dept']];
            $dept = $this->getMergeChildDepartments($dept);
            $values['dept'] = implode(',',$dept);
            $values['agent'] = $values['agent']?implode(',',$values['agent']):'';
            $values['company'] = $values['company']?implode(',',$values['company']):'';
            $values['level'] = $values['level']?implode(',',$values['level']):'';
            $userinfo = $this->getUserinfo($values['userid']);
            $values['username'] = $userinfo['name'];
            $model->attributes = $values;
            $ruleResult = Tools::modelRules($model, 4001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '[流程角色管理]修改';
                    $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，流程角色。ID：".$model->id."名称：" . $model->username . '。';
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
            $models = $this->modelClass::find()->where(['in', 'id', $ids])->all();
            $num = count($models);
            $names = [];
            foreach ($models as $model) {
                $names[] = $model->username;
                $model->delete();
            }
            if ($names) {
                $action = '[流程角色管理]删除';
                $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，流程角色。包含 " . implode(',', $names) . " 全部删除。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    //获取所有应用
    public function actionGetRole()
    {    
        $res = WeixinOaRole::find()->where(['>','id',0])->all();
        $data = [];
        foreach ($res as $row) {
            $node = ['title' => $row->rolename, 'key' => strval($row->id), 'isLeaf' => true];
            $data[] = $node;
        }
        $this->_result['data'] = $data;
        return $this->_result;
    }

    //获取选项数据
    public function actionGetDict()
    {    
        $_Tmp = [];
        foreach($this->_levels as $k=>$v){
            $_Tmp[$k]=['text'=>$v];
        }
        $data['level'] = $_Tmp;
        $_Tmp = [];
        $res = WeixinFinanceCompany::find()->where(['>','id',0])->all();
        foreach ($res as $row) {
            $_Tmp[$row->id] = ['text'=>$row->company];
        }
        $data['company'] = $_Tmp;
        $_Tmp = [];
        $res = WeixinQYAppInterface::find()->where(['>','id',0])->all();
        foreach ($res as $row) {
            $_Tmp[$row->appid] = ['text'=>$row->appname];
        }
        $data['app'] = $_Tmp;
        $_Tmp = [];
        $res = WeixinOaRole::find()->where(['>','id',0])->all();
        foreach ($res as $row) {
            $_Tmp[$row->id] = ['text'=>$row->rolename];
        }
        $data['role'] = $_Tmp;
        $this->_result['data'] = $data;
        return $this->_result;
    }
    
    /**
     * 获取企业微信用户信息
     */
    private function getUserinfo($userid)
    {
        $userinfo = WeixinOaUserinfo::find()->where(['=', 'userid', $userid])->asArray()->one();
        return $userinfo;
    }
    
    /**
     * 返回所有下级部门
     */
    private function getMergeChildDepartments($dept)
    {
        $childs = [];
        $res = WxDepartment::find()->where(['in','parentid',$dept])->asArray()->all();
        foreach($res as $row){
            $childs[] = $row['id'];
        }
        if($childs){
            $child = $this->getMergeChildDepartments($childs);
            if($child){
                $childs = array_merge($childs,$child);
            }            
        }else{
            return [];
        }
        return array_merge($dept,$childs);
    }
}
