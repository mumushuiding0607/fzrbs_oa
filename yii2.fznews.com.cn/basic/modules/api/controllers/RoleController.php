<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\models\FzrbsRouteMenu;
use app\modules\api\models\WeixinChannel;

/**
 * 角色管理相关接口类
 */
class RoleController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\FzrbsRole';
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
            if (!isset($this->_request['values']['usernames'])) {
                $model->usernames = '';
            }
            if (!isset($this->_request['values']['routes'])) {
                $model->routes = '';
            } else {
                $model->routes = $this->_getAllRoutes($model, '');
            }
            if (!isset($this->_request['values']['channels'])) {
                $model->channels = '';
            } else {
                $model->channels = $this->_getAllChannels($model, '');
            }
            $ruleResult = Tools::modelRules($model, 5000);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '新增';
                    $remark = $action . "角色。名称：" . $model->name . '。';
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
            $oldRoutes = $model->routes;
            $oldChannels = $model->channels;
            $model->attributes = $this->_request['values'];
            if (!isset($this->_request['values']['usernames'])) {
                $model->usernames = '';
            }
            if (!isset($this->_request['values']['routes'])) {
                $model->routes = '';
            } else {
                $model->routes = $this->_getAllRoutes($model, $oldRoutes);
            }
            if (!isset($this->_request['values']['channels'])) {
                $model->channels = '';
            } else {
                $model->channels = $this->_getAllChannels($model, $oldChannels);
            }
            $ruleResult = Tools::modelRules($model, 5001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '修改';
                    $remark = $action . "角色。" . ($oldName != $model->name ? '名称由 ' . $oldName . ' 改为 ' . $model->name . '。' : '');
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
                $names[] = $model->name;
                $model->delete();
            }
            if ($names) {
                $action = '删除';
                $remark = $action . "角色。名称：" . implode(',', $names) . "。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 获取所有已选择路由及子路由
     * @param model $currentModel 当前model
     * @param string $oldRoutes 修改前的routes
     * @return string 路由ids
     */
    protected function _getAllRoutes($currentModel, $oldRoutes = '')
    {
        $routes = [];
        $checkedRoutes = [];
        if ($currentModel->routes) {
            $routeIds = explode(',', $currentModel->routes);
            foreach ($routeIds as $id) {
                $models = FzrbsRouteMenu::find()->where(['like', "CONCAT(',', parentids, ',')", ',' . $id . ','])->all();
                foreach ($models as $model) {
                    $routes[$id][] = $model->id;
                }
                if (isset($routes[$id])) {
                    $intersectArray  = array_intersect($routeIds, $routes[$id]);
                    if ($oldRoutes == '') {
                        if (!$intersectArray) {
                            $checkedRoutes[] = implode(',', $routes[$id]);
                        }
                    } else {
                        $oldRoutesArray = explode(',', $oldRoutes);
                        if (!$intersectArray && !array_intersect($oldRoutesArray, $routes[$id])) {
                            $checkedRoutes[] = implode(',', $routes[$id]);
                        } else if (!$intersectArray && array_intersect($oldRoutesArray, $routes[$id])) {
                            $oldIntersectArray = array_intersect($oldRoutesArray, $routes[$id]);
                            $checkedRoutes[] = implode(',', $oldIntersectArray);
                        }
                    }
                }
            }
        }
        $checkedIds = $currentModel->routes . ($checkedRoutes ? ',' . implode(',', $checkedRoutes) : '');
        return "$checkedIds";
    }

    /**
     * 获取所有已选择信息栏目及子栏目
     * @param model $currentModel 当前model
     * @param string $oldChannels 修改前的channels
     * @return string 路由ids
     */
    protected function _getAllChannels($currentModel, $oldChannels = '')
    {
        $channels = [];
        $checkedChannels = [];
        if ($currentModel->channels) {
            $channelIds = explode(',', $currentModel->channels);
            foreach ($channelIds as $id) {
                $models = WeixinChannel::find()->where(['like', "CONCAT(',', parentids, ',')", ',' . $id . ','])->all();
                foreach ($models as $model) {
                    $channels[$id][] = $model->id;
                }
                if (isset($channels[$id])) {
                    $intersectArray  = array_intersect($channelIds, $channels[$id]);
                    if ($oldChannels == '') {
                        if (!$intersectArray) {
                            $checkedChannels[] = implode(',', $channels[$id]);
                        }
                    } else {
                        $oldChannelsArray = explode(',', $oldChannels);
                        if (!$intersectArray && !array_intersect($oldChannelsArray, $channels[$id])) {
                            $checkedChannels[] = implode(',', $channels[$id]);
                        } else if (!$intersectArray && array_intersect($oldChannelsArray, $channels[$id])) {
                            $oldIntersectArray = array_intersect($oldChannelsArray, $channels[$id]);
                            $checkedChannels[] = implode(',', $oldIntersectArray);
                        }
                    }
                }
            }
        }
        $checkedIds = $currentModel->channels . ($checkedChannels ? ',' . implode(',', $checkedChannels) : '');
        return "$checkedIds";
    }
}
