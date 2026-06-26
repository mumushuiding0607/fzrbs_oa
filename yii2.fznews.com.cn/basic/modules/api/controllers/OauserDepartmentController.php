<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;

/**
 * 部门操作接口类
 */
class OauserDepartmentController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WxOauserDepartment';
    protected $_orderBy = '`order` desc';
    protected $_db;

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
        $this->_db =\Yii::$app->db;

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
      
        $parentId = isset($this->_request['parentid']) ? $this->_request['parentid'] : 0;
        $where = [
            'and',
            ['=', 'st', 1],
            ['=', 'parentid', $parentId],
        ];
        $model = $this->modelClass;
        $child = $model::find()->where(['and',['=','parentid',1],['=','st',1]])->asArray()->all();
        $model = $model::find()->where($where);
        if (isset($this->_request['tree'])) {
            $data = [];
            $res = $model->orderBy($this->_orderBy)->all();
            foreach ($res as $row) {
                $model = $this->modelClass;
                $child = $model::find()->where(['and',['=','parentid',$row->id],['=','st',1]])->asArray()->all();
                $data[] = ['title' => $row->name, 'key' => $row->id,'isLeaf' => count($child) ? false:true];
            }
            $this->_result['data'] = $data;
        } else {
            $total = $model->count();
            $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->asArray()->all();
            $this->_result["current"] = $page;
            $this->_result["pageSize"] = $limit;
            $this->_result["total"] = $total;
            $this->_result['data'] = $res;
        }

        return $this->_result;
    }
    //部门列表页 数据获取
    protected function getTree($pid){
        $pIds = [];
        array_push($pIds, $pid);
        if($pIds){
            $pId = array_shift($pIds);
            $parent = $this->modelClass::find()->select('id,name,order,st')->where(['and',['=', 'id', $pId],['>','st',0]])->asArray()->one();
            
            $models = $this->modelClass::find()->select('id')->where(['and',['=', 'parentid', $pId],['>','st',0]])->orderBy($this->_orderBy)->asArray()->all();
            
            // 子节点入栈
            // $child = array_column($models, "id");
            if ($models) {
                foreach ($models as $item) {
                    $child[] = $this->getTree($item['id']);
                }
                $parent['children'] = $child;
                // 更新子节点的parentid
            }
            return $parent;
        }
    }
    //树形列表页
    public function actionTreeList()
    {
        $total = 1;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        // $offset = $limit * ($page - 1);
        
        // $where = [
        //     'and',
        //     ['>', 'st', 0],
        //     ['=', 'parentid', 0],
        // ];
        // $model = $this->modelClass;
        // $child = $model::find()->where(['and',['=','parentid',1],['>','st',0]])->asArray()->all();
        // $model = $model::find()->where($where);

        // $total = $model->count();
        // $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->asArray()->all();
        $data = $this->getTree(1);
        // var_dump($data);exit;
        // $res[0]['children'] = $res;
        $res = [$data];
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
            $pModel = $this->modelClass;
            // $p = $pModel::findOne($this->_request['pid']);

            $model = new $this->modelClass(['scenario' => 'create']);
           
            $this->_request['values']['parentid'] = $this->_request['pid'];
            $model->attributes = $this->_request['values'];
            $ruleResult = Tools::modelRules($model, 4000);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $this->_result['lastid'] = $model->id;
                    $action = '新增';
                    $remark = $action . "职员管理-部门列表-新增：" . $model->name . '。';
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
            $model = $this->modelClass;
            $model = $model::findOne($id);
            $model->scenario = 'update';
            $oldName = $model->name;
            $oldOrder = $model->order;
            $model->attributes = $this->_request['values'];
            $ruleResult = Tools::modelRules($model, 4001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '修改';
                    $remark = $action . "职员管理-部门列表-" . ($oldName != $model->name ? '部门名称由 ' . $oldName . ' 改为 ' . $model->name . '。' : '').("新排序：".$model->order." 旧排序：$oldOrder");
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
     * 显示或者隐藏部门
     */
    public function actionVisiable()
    {
        $id = intval($this->_request['id']);
        if ($id) {
            $model = $this->modelClass;
            $model = $model::findOne($id);
            $model->scenario = 'update';
            $model->attributes = ['st'=>$this->_request['st']];
            $ruleResult = Tools::modelRules($model, 4001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '修改';
                    $remark = $action . "职员管理-部门列表-" . "状态修改为：".($this->_request['st']==1 ? "显示":"隐藏");
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
     * 移动部门
     */
    public function actionMove()
    {
        $id = $this->_request['infoIds'];
        $id = $id[0];
        if ($id) {
            $model = $this->modelClass;
            $model = $model::findOne($id);
            $oldPid = $model->parentid;
            $model->scenario = 'update';
            $model->attributes = ['parentid'=>$this->_request['toId']];
            $ruleResult = Tools::modelRules($model, 4001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '修改';
                    $remark = $action . "职员管理-部门列表-" . "部门移动：旧父级id:$oldPid,新父级id:".$this->_request['toId'];
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
            $models = $this->modelClass::find()->where(['in', 'parentid', $ids])->all();
            $num = count($models);
            if($num){
                $this->_result = Tools::wrongRules(1000, '存在子部门，删除失败');
            }else{
                // $this->modelClass::updateAllCounters(['st'=>0], ['in', 'id',  $ids]);
                $this->modelClass::updateAll(['st'=>0], "id in(".implode(',', $ids).")");
            }
            $models = $this->modelClass::find()->where(['in', 'id', $ids])->asArray()->all();
            $names = array_column($models,'name');
            if ($names) {
                $action = '删除';
                $remark = $action . "职员管理-部门列表-删除：" . implode(',', $names) . "";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }
}