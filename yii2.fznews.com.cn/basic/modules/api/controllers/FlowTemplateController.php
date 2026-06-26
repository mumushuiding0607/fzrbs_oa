<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\models\WxDepartment;
use app\modules\api\models\WeixinOaRole;
use app\modules\api\models\WeixinQYAppInterface;
use app\modules\api\models\WeixinFinanceCompany;
use app\modules\api\models\WeixinOaUserinfo;
use app\modules\api\models\WeixinOaUsertag;

/**
 * 流程模板管理相关接口类
 */
class FlowTemplateController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinOaTemplates';
    protected $_orderBy = 'id desc';    
    private $_notifyAttr = [1=>'提交申请时抄送','审批通过后抄送','提交申请时和审批通过后都抄送'];
    private $_levelname = [1=>'直接上级','第二级上级','第三级上级','第四级上级','第五级上级'];
    private $_approvalAttr = [1=>'或签','会签'];

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
        $appid = isset($this->_request['appid']) ? $this->_request['appid'] : 0;
        $notifyAttr = isset($this->_request['notifyAttr']) ? $this->_request['notifyAttr'] : 0;
        $templateId = isset($this->_request['templateId']) ? $this->_request['templateId'] : '';
        $templateName = isset($this->_request['templateName']) ? $this->_request['templateName'] : '';
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if($appid){
            $where[] = ['=', 'appid', $appid];
        }
        if($notifyAttr){
            $where[] = ['=', 'notifyAttr', $notifyAttr];
        }
        if($templateId){
            $where[] = ['=', 'templateId', $templateId];
        }
        if($templateName){
            $where[] = ['like', 'templateName', $templateName];
        }
        $qyapp = WeixinQYAppInterface::find()->where(['>','id',0])->asArray()->all();
        $qyappArr = [];
        foreach($qyapp as $row){
            $qyappArr[$row['appid']] = $row['appname'];
        }
        $role = WeixinOaRole::find()->where(['>','id',0])->asArray()->all();
        $roleArr = [];
        foreach($role as $row){
            $roleArr[$row['id']] = $row['rolename'];
        }
        $model = $this->modelClass;
        $model = $model::find()->where($where);
        $total = $model->count();
        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->asArray()->all();
        $data = [];
        foreach($res as $row){
            $item = $row;
            $item['templateData'] = json_decode($row['templateData'],true);
            $approval = [];
            foreach($item['templateData']['approval'] as  $k=>$r){
                $_aitem = $r;
                $_aitem['key'] = $k+1;
                $_aitem['title'] = $this->getTitle($_aitem,$roleArr);
                if($r['type']==1){
                    $uinfo = $this->getUserinfo($r['id'],'id');
                    $_aitem['userid'] = $uinfo['userid'];
                }
                $approval[] = $_aitem;
            }
            $item['templateData']['approval'] = $approval;
            $notify = [];
            foreach($item['templateData']['notify'] as  $k=>$r){
                $_nitem = $r;
                $_nitem['key'] = $k+1;
                $_nitem['title'] = $this->getTitle($_nitem,$roleArr);
                if($r['type']==1){
                    $uinfo = $this->getUserinfo($r['id'],'id');
                    $_nitem['userid'] = $uinfo['userid'];
                }
                $notify[] = $_nitem;
            }
            $item['templateData']['notify'] = $notify;
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
            $values['templateData'] = json_encode($values['templateData']);
            $row = WeixinQYAppInterface::find()->where(['=','appid',$values['appid']])->one();
            $values['appname'] = $row['appname'];
            $values['templateId'] = md5($values['templateName'].time()).'_'.time();
            $model->attributes = $values;
            $ruleResult = Tools::modelRules($model, 4000);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $this->_result['lastid'] = $model->id;
                    $action = '[流程模板管理]新增';
                    $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，流程模板。ID：".$model->id."名称：" . $model->templateName . '。';
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
            $values['templateData'] = json_encode($values['templateData']);
            $row = WeixinQYAppInterface::find()->where(['=','appid',$values['appid']])->one();
            $values['appname'] = $row['appname'];
            $model->attributes = $values;
            $ruleResult = Tools::modelRules($model, 4001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '[流程模板管理]修改';
                    $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，流程模板。ID：".$model->id."名称：" . $model->templateName . '。';
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
                $names[] = $model->templateName;
                $model->delete();
            }
            if ($names) {
                $action = '[流程模板管理]删除';
                $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，流程模板。包含 " . implode(',', $names) . " 全部删除。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    //获取所有角色类别
    public function actionGetQyapp()
    {    
        $res = $this->modelClass::find()->select('appid,appname')->where(['>','id',0])->groupBy('appid,appname')->all();
        $data = [];
        foreach ($res as $row) {
            if($row->appid){
                $node = ['title' => $row->appname, 'key' => strval($row->appid), 'isLeaf' => true];
                $data[] = $node;
            }
        }
        $this->_result['data'] = $data;
        return $this->_result;
    }

    //获取选项数据
    public function actionGetDict()
    {    
        $_Tmp = [];
        foreach($this->_notifyAttr as $k=>$v){
            $_Tmp[$k]=['text'=>$v];
        }
        $data['notifyAttr'] = $_Tmp;
        $_Tmp = [];  
        foreach($this->_approvalAttr as $k=>$v){
            $_Tmp[$k]=['text'=>$v];
        }
        $data['approvalAttr'] = $_Tmp;
        $_Tmp = [];
        foreach($this->_levelname as $k=>$v){
            $_Tmp[$k]=['text'=>$v];
        }
        $data['level'] = $_Tmp;
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
        $_Tmp = [];
        $res = WeixinOaUsertag::find()->where(['>','id',0])->all();
        foreach ($res as $row) {
            $_Tmp[$row->id] = ['text'=>$row->tagName];
        }
        $data['tag'] = $_Tmp;
        $_Tmp = [];
        $res = WeixinOaUserinfo::find()->where(['and',['=','status',1],['=','st',1]])->all();
        foreach ($res as $row) {
            $_Tmp[$row->userid] = ['id'=>$row->id,'text'=>$row->name];
        }
        $data['user'] = $_Tmp;
        $this->_result['data'] = $data;
        return $this->_result;
    }
    
    /**
     * 获取企业微信用户信息
     */
    private function getUserinfo($userid,$key='userid')
    {
        $userinfo = WeixinOaUserinfo::find()->where(['=', $key, $userid])->asArray()->one();
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

    /**
     * 生成审批人标题
     */
    private function getTitle($data,$role)
    {
        switch($data['type'])
        {
            case 0:
            case 5:
                return $role[$data['role']];
                break;
            case 1:
                return $data['uname'];
                break;
            case 2:
                return $data['tag'].($data['attr']?'（'.$this->_approvalAttr[$data['attr']].'）':'');
                break;
            case 3:
                return $this->_levelname[$data['level']].($data['attr']?'（'.$this->_approvalAttr[$data['attr']].'）':'');
                break;
            case 4:
                return $role[$data['nrole']];
                break;
            case 6:
                return '手动选择';
                break;
            case 7:
                return $role[5];
                break;
            case 8:
                return '主体负责人';
                break;
            case 9:
                return $role[10];
                break;
        }
    }
}
