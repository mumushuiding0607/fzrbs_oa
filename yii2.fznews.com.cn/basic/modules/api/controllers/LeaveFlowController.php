<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\models\WeixinOaUserinfo;
use app\modules\api\models\WeixinOaDepartment;

/**
 * 请销假流程操作接口类
 */
class LeaveFlowController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinLeaveTemplate';
    protected $_orderBy = '`id` desc';

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
            ['=', 'isdel', 0],
        ];
        if ($this->_request['templateid']) {
            $where[] = ['=', 'templateid', $this->_request['templateid']];
        }
        if ($this->_request['templatename']) {
            $where[] = ['like', 'templatename', $this->_request['templatename']];
        }
        if ($this->_request['level']) {
            $where[] = ['=', 'level', $this->_request['level']];
        }
        if (isset($this->_request['type']) && intval($this->_request['type'])>=0) {
            $where[] = ['=', 'type', $this->_request['type']];
        }
        if ($this->_request['is_company']) {
            $where[] = ['=', 'is_company', $this->_request['is_company']];
        }
        $model = $this->modelClass;
        $model = $model::find()->where($where);

        $total = $model->count();
        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
        $data = [];
        foreach($res as $item){
            if($item['uids']){
                $uids = explode(',',$item['uids']);
                $userids = $this->getUserinfo($uids,'in');
                $item['uids'] = implode(',',$userids);
            }
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
        $values = $this->_request['values'];
        if ($values) {
            $model = new $this->modelClass(['scenario' => 'create']);
            // if(isset($values['dids'])){
            //     $pids = $this->deptParent(implode(',',$values['dids']));
            //     $cids = $this->deptChild(implode(',',$values['dids']));
            //     $dids = array_unique(array_merge($pids,$values['dids'],$cids));
            //     sort($dids);
                $values['dids'] = implode(',',$values['dids']);
            // }
            // if(isset($values['uids'])){
            //     $uids = [];
            //     $userinfo = $this->userinfoModelClass::find()->where(['userid'=>$values['uids']])->asArray()->all();
            //     foreach($userinfo as $row){
            //         $uids[] = $row['id'];
            //     }
                $values['uids'] = implode(',',$values['uids']);
            // }
            if(isset($values['min_max'])){
                $values['min'] = isset($values['min_max'][0])?$values['min_max'][0]:0;
                $values['max'] = isset($values['min_max'][1])?$values['min_max'][1]:0;
                unset($values['min_max']);
            }
            $model->attributes = $values;
            $ruleResult = Tools::modelRules($model, 4000);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $this->_result['lastid'] = $model->id;
                    $action = '[请销假]流程新增';
                    $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，ID=" .$model->id . "，模板名称=" . $model->templatename . '。';
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
            $values = $this->_request['values'];
            if($values['dids']){
                $values['dids'] = implode(",",$values['dids']);
            }
            if($values['uids']){
                $values['uids'] = implode(",",$values['uids']);
            }
            $model = $this->modelClass::findOne($id);
            $model->scenario = 'update';
            $oldName = $model->templatename;
            $model->attributes = $values;
            $ruleResult = Tools::modelRules($model, 4001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '[请销假]流程修改';
                    $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，ID=" . $id . "，" . ($oldName != $model->templatename ? '流程名称由 ' . $oldName . ' 改为 ' . $model->templatename . '。' : $model->templatename);
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
        $id = intval($this->_request['id']);
        if ($id) {
            $model = $this->modelClass::findOne($id);
            $model->isdel = 1;
            if ($model->save()) {
                $action = '[请销假]流程删除';
                $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，ID=" . $id . "";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    private function deptParent($dids)
    {
        $ret = [];
        $dept = WeixinOaDepartment::find()->where(['id'=>$dids])->asArray()->all();
        if($dept){
            foreach($dept as $row){
                if($row['parentid'])$ret[] = $row['parentid']; 
            }
            $pret = $this->deptParent(implode(',',$ret));
            if($pret){
                return array_merge($ret,$pret);
            }else{
                return $ret;
            }
        }
        return 0;  
    }

    private function deptChild($dids)
    {
        $ret = [];
        $dept = WeixinOaDepartment::find()->where(['parentid'=>$dids])->asArray()->all();
        if($dept){
            foreach($dept as $row){
                $ret[] = $row['id']; 
            }
            $pret = $this->deptChild(implode(',',$ret));
            if($pret){
                return array_merge($ret,$pret);
            }else{
                return $ret;
            }
        }
        return 0;  
    }
    
    /**
     * 获取企业微信用户信息
     */
    private function getUserinfo($ids,$op='=')
    {
        $userinfo = WeixinOaUserinfo::find()->where([$op, 'id', $ids])->asArray()->all();
        $userids = [];
        foreach($userinfo as $row){
            $userids[] = $row['userid'];
        }
        return $userids;
    }
}