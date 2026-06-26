<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WxQyhJk;

/**
 * 部门操作接口类
 */
class DepartmentController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WxDepartment';
    protected $_orderBy = '`order` desc';

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
            ['>', 'st', 0],
            ['=', 'parentid', $parentId],
        ];
        $model = $this->modelClass;
        $child = $model::find()->where(['and', ['=', 'parentid', 1], ['>', 'st', 0]])->asArray()->all();
        $model = $model::find()->where($where);
        if (isset($this->_request['tree'])) {
            $data = [];
            $res = $model->orderBy($this->_orderBy)->all();
            foreach ($res as $row) {
                $model = $this->modelClass;
                $child = $model::find()->where(['and', ['=', 'parentid', $row->id], ['>', 'st', 0]])->asArray()->all();
                $data[] = ['title' => $row->name, 'key' => $row->id, 'p_key' => $row->parentids, 'isLeaf' => count($child) ? false : true];
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
     * 同步企业通讯录部门
     */
    public function actionAsynchronization()
    {
        set_time_limit(0);
        $sendResult =  WxQyhJk::department('');
        if (!$sendResult['errorMessage']) {
            $departments = $sendResult['data'];
            if (is_array($departments) && count($departments) > 0) {
                foreach ($departments as $department) {
                    $department['leader'] = implode(',', $department['department_leader']);
                    $model = $this->modelClass::findOne($department['id']);
                    if ($model == null) {
                        unset($department['department_leader']);
                        $this->modelClass::getDb()->createCommand()->insert($this->modelClass::tableName(), $department)->execute();
                    } else {
                        $model->attributes = $department;
                        $model->save();
                    }
                }
                $this->_setParentids();
            }
        } else {
            $this->_result['errorMessage'] = $sendResult['errorMessage'];
        }
        return $this->_result;
    }

    /**
     * 设置parentids字段值
     */
    protected function _setParentids()
    {
        $pIds = [];
        array_push($pIds, 0);
        while ($pIds) {
            $pId = array_shift($pIds);
            $model = $this->modelClass::find()->select('parentids')->where(['=', 'id', $pId])->one();
            $models = $this->modelClass::find()->select('id')->where(['=', 'parentid', $pId])->all();
            $parentids = $model->parentids;
            // 子节点入栈
            $child = array_column($models, "id");
            if ($child) {
                foreach ($child as $newId) {
                    array_push($pIds, $newId);
                }
                // 更新子节点的parentid
                $parentids = $pId == 0 ? 0 : $parentids . "," . $pId;
                $this->modelClass::updateAll(['parentids' => $parentids], ['in', 'id', $child]);
            }
        }
    }
}
