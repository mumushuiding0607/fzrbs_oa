<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;

/**
 * 假期配置管理相关接口类
 */
class HolidaySettingController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinHolidays';
    protected $_orderBy = 'id desc';

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
        $data = [];
        foreach ($res as $row) {
            if (!isset($data[$row->year])) {
                $data[$row->year] = ['id' => $row->id, 'year' => $row->year, 'type' => $row->type];
                if ($row->type == 0) {
                    $data[$row->year]['typeday0'] = $row->days ? $row->days : '';
                } else if ($row->type == 1) {
                    $data[$row->year]['typeday1'] = $row->days ? $row->days : '';
                }
            } else {
                if (!isset($data[$row->year]['typeday0'])) {
                    $data[$row->year]['typeday0'] = $row->days ? $row->days : '';
                } else if (!isset($data[$row->year]['typeday1'])) {
                    $data[$row->year]['typeday1'] = $row->days ? $row->days : '';
                }
            }
        }
        $this->_result['data'] = array_values($data);
        return $this->_result;
    }

    /**
     * 重写create的业务实现动作
     */
    public function actionCreate()
    {
        $year = $this->_request['values']['year'];
        $day1 = $this->_request['values']['day1'];
        $day2 = $this->_request['values']['day2'];
        if ($year && $day1 && $day2) {
            $model = $this->modelClass::find()->where(['=', 'year', $year])->one();
            if ($model) {
                $this->_result = Tools::wrongRules(10000, '添加年份已经存在');
            } else {
                $model = new $this->modelClass();
                $model->attributes = ['year' => $year, 'type' => 0, 'days' => $day1];
                $model->save();
                $model = new $this->modelClass();
                $model->attributes = ['year' => $year, 'type' => 1, 'days' => $day2];
                $model->save();
                $action = '新增';
                $remark = $action . "假期日期设置。年份：" . $year;
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
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
        $day1 = $this->_request['values']['day1'];
        $day2 = $this->_request['values']['day2'];
        if ($id && $day1 && $day2) {
            $model = $this->modelClass::findOne($id);
            if ($model) {
                $model->days = $model->type == 0 ? $day1 : $day2;
                $model->save();
                // 更新另一条记录
                $year = $model->year;
                $type =  $model->type == 0 ? 1 : 0;
                $model = $this->modelClass::find()->where(['and', ['=', 'year', $year], ['=', 'type', $type]])->one();
                if ($model) {
                    $model->days = $type == 0 ? $day1 : $day2;
                    $model->save();
                }
                $action = '修改';
                $remark = $action . "假期日期设置。年份：" . $year;
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
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
            $years = [];
            $models = $this->modelClass::find()->where(['in', 'id', $ids])->all();
            foreach ($models as $model) {
                $year = $model->year;
                $type = $model->type == 0 ? 1 : 0;
                $years[] = $year;
                $model->delete();
                // 删除另一条记录
                $this->modelClass::deleteAll(['year' => $year, 'type' => $type]);
            }
            if ($years) {
                $action = '删除';
                $remark = $action . "假期日期设置。年份：" . implode(',', $years) . "。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }
}
