<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;

/**
 * 路由菜单管理相关接口类
 */
class RouteMenuController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\FzrbsRouteMenu';
    protected $_orderBy = 'inserttime asc';

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
        $parentId = isset($this->_request['parentid']) ? $this->_request['parentid'] : 0;
        $where = [
            'and',
            ['>', 'id', 0],
            ['=', 'parentid', $parentId],
        ];
        $model = $this->modelClass;
        $model = $model::find()->where($where);
        if (isset($this->_request['tree'])) {
            $data = [];
            if ($this->_request['showAll']) {
                $data[] =  ['title' => '福州日报社OA管理系统', 'key' => '0', 'children' => []];
            }
            $res = $model->orderBy($this->_orderBy)->all();
            foreach ($res as $row) {
                $isLeaf = $row->childrenmenunum > 0 ? false : true;
                $node = ['title' => $row->name, 'key' => strval($row->id), 'isLeaf' => $isLeaf];
                if ($this->_request['showAll'] && !$isLeaf) {
                    $children  = $this->_getRouteMenuChildren($row->id);
                    if ($children) {
                        $node['children'] = $children;
                    }
                }
                if ($this->_request['showAll']) {
                    $data[0]['children'][] = $node;
                } else {
                    $data[] = $node;
                }
            }
            $this->_result['data'] = $data;
        } else {
            $total = $model->count();
            $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
            $this->_result["current"] = $page;
            $this->_result["pageSize"] = $limit;
            $this->_result["total"] = $total;
            $this->_result['data'] = $res;
        }
        return $this->_result;
    }

    /**
     * 重写create的业务实现动作
     */
    public function actionCreate()
    {
        if ($this->_request['values']) {
            $this->_request['values']['parentids'] = "0";
            if ($this->_request['values']['parentid'] > 0) {
                $parentModel = $this->modelClass::findOne($this->_request['values']['parentid']);
                if ($parentModel) {
                    $this->_request['values']['parentids'] = $parentModel->parentids . ',' . $this->_request['values']['parentid'];
                }
            }
            $model = new $this->modelClass(['scenario' => 'create']);
            $model->attributes = $this->_request['values'];
            $ruleResult = Tools::modelRules($model, 4000);
            if ($ruleResult === true) {
                if ($model->save()) {
                    if ($parentModel) {
                        $parentIds = explode(',', $this->_request['values']['parentids']);
                        $this->modelClass::updateAllCounters(['childrenmenunum' => 1], ['in', 'id', $parentIds]);
                    }
                    $this->_result['lastid'] = $model->id;
                    $action = '新增';
                    $remark = $action . "路由菜单。名称：" . $model->name . '。';
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
            $oldName = $model->name;
            $oldIcon = $model->icon;
            $model->attributes = $this->_request['values'];
            $ruleResult = Tools::modelRules($model, 4001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '修改';
                    $remark = $action . "路由菜单。" . ($oldName != $model->name ? '名称由 ' . $oldName . ' 改为 ' . $model->name . '。' : '') . ($oldIcon != $model->icon ? ($oldName == $model->name ? '名称：' . $model->name . '，' : '') . '图标由 ' . $oldIcon . ' 改为 ' . $model->icon . '。' : '');
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
            $parentIds = 0;
            $num = count($models);
            $names = [];
            foreach ($models as $model) {
                $parentIds = $model->parentids;
                $childrenNum = $this->modelClass::deleteAll(['like', "CONCAT(',', parentids, ',')", ',' . $model->id . ',']);
                if ($childrenNum) {
                    $num = $num + $childrenNum;
                }
                $names[] = $model->name;
                $model->delete();
            }
            if ($parentIds && $num) {
                $this->modelClass::updateAllCounters(['childrenmenunum' => -$num], ['in', 'id',  explode(',', $parentIds)]);
            }
            if ($names) {
                $action = '删除';
                $remark = $action . "路由菜单。包含 " . implode(',', $names) . " 路由菜单及子路由菜单全部删除。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 获取路由菜单子节点
     * @param int $parentId 父节点id
     * @return array 子节点信息
     */
    protected function _getRouteMenuChildren($parentId, $otherCondition = [])
    {
        $where = [
            'and',
            ['=', 'parentid', $parentId],
        ];
        $res = $this->modelClass::find()->where($where)->orderBy('inserttime asc')->all();
        $routes = [];
        foreach ($res as $row) {
            $isLeaf = $row->childrenmenunum > 0 ? false : true;
            $node = ['title' => $row->name, 'key' => strval($row->id), 'isLeaf' => $isLeaf];
            if ($this->_request['showAll'] && !$isLeaf) {
                $children  = $this->_getRouteMenuChildren($row->id);
                if ($children) {
                    $node['children'] = $children;
                }
            }
            $routes[] = $node;
        }
        return $routes;
    }
}
