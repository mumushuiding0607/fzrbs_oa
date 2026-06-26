<?php

namespace app\modules\api\controllers;

use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\models\FzrbsRole;

/**
 * 用户管理相关接口类
 */
class AdminController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\FzrbsAdmin';
    protected $_orderBy = 'inserttime desc';
    protected $_userType = ['普通用户', '管理员'];
    protected $_status = ['正常', '禁止登录'];

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
        if ($this->_request['username']) {
            $where[] = ['=', 'username', $this->_request['username']];
        }
        if ($this->_request['realname']) {
            $where[] = ['=', 'realname', $this->_request['realname']];
        }
        if ($this->_request['lastlogintime']) {
            $lastlogintime = explode(',', $this->_request['lastlogintime']);
            $starTime = $lastlogintime[0] . ' 00:00:00';
            $endTime = $lastlogintime[1] . ' 23:59:59';
            $where[] = ['between', 'lastlogintime', $starTime, $endTime];
        }
        if ($this->_request['inserttime']) {
            $inserttime = explode(',', $this->_request['inserttime']);
            $starTime = $inserttime[0] . ' 00:00:00';
            $endTime = $inserttime[1] . ' 23:59:59';
            $where[] = ['between', 'inserttime', $starTime, $endTime];
        }
        if (isset($this->_request['islock'])) {
            $where[] = ['=', 'islock', $this->_request['islock']];
        }
        if (isset($this->_request['usertype'])) {
            $where[] = ['=', 'usertype', $this->_request['usertype']];
        }
        if (isset($this->_request['classify'])) {
            $where[] = ['=', 'classify', $this->_request['classify']];
        }
        $model = $this->modelClass;
        $model = $model::find()->where($where);
        if (isset($this->_request['tree'])) {
            $data = [];
            $res = $model->orderBy($this->_orderBy)->all();
            foreach ($res as $row) {
                $title = $row->realname . '(' . ($row->classify == 0 ? '后台账号' : '企业账号') . ')';
                $data[] = ['title' => $title, 'key' => $row->username, 'isLeaf' => true];
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
            $this->_request['values'] = Tools::setSM3Password($this->_request['values']);
            $model = new $this->modelClass(['scenario' => 'create']);
            $model->attributes = $this->_request['values'];
            $ruleResult = Tools::modelRules($model, 2000);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '新增';
                    $remark = $action . "用户账号。账号：" . $model->username . '，姓名：' . $model->realname . '，手机：' . $model->mobile;
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
            $this->_request['values'] = Tools::setSM3Password($this->_request['values']);
            $model = $this->modelClass::findOne($id);
            $model->scenario = 'update';
            $oldRealname = $model->realname;
            $oldMobile = $model->mobile;
            $oldUserType = $model->usertype;
            $oldStatus = $model->islock;
            $model->attributes = $this->_request['values'];
            $ruleResult = Tools::modelRules($model, 2001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '修改';
                    $remark = $action . "用户账号。" . ($oldRealname != $model->realname ? '姓名由 ' . $oldRealname . ' 改为 ' . $model->realname . '。' : '') . ($oldMobile != $model->mobile ? '手机号由 ' . $oldMobile . ' 改为 ' . $model->mobile . '。' : '') . ($oldUserType != $model->usertype ? '用户类型由 ' . $this->_userType[$oldUserType] . ' 改为 ' . $this->_userType[$model->usertype] . '。' : '') . ($oldStatus != $model->islock ? '状态由 ' . $this->_status[$oldStatus] . ' 改为 ' . $this->_status[$model->islock] . '。' : '') . (isset($this->_request['values']['password']) ? '重新设置账号密码。' : '');
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
            // $this->modelClass::deleteAll(['in', 'id', explode(',', $this->_request['id'])]);
            $ids = explode(',', $this->_request['id']);
            $usernames = [];
            $models = $this->modelClass::find()->where(['in', 'id', $ids])->all();
            foreach ($models as $model) {
                $usernames[] = $model->username;
                $model->delete();
            }
            if ($usernames) {
                $action = '删除';
                $remark = $action . "用户账号。账号名称：" . implode(',', $usernames) . "。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 用户角色动作
     */
    public function actionRole()
    {
        $userName = $this->_request['username'];
        if ($userName) {
            $where = [
                'and',
                ['>', 'id', 0],
            ];
            $data = [];
            $userData = [];
            $models = FzrbsRole::find()->where($where)->all();
            foreach ($models as $model) {
                $data[] = ['label' => $model->name, 'value' => $model->id];
                if (strpos($model->usernames, $userName) !== false) {
                    $userData[] = $model->id;
                }
            }
            if ($data) {
                $this->_result['roles'] = $data;
                $this->_result['userRoles'] = $userData;
            }
        }
        return $this->_result;
    }

    /**
     * 用户角色保存动作
     */
    public function actionSaveRole()
    {
        $userName = $this->_request['username'];
        $userRoleId =  $this->_request['userRoleId'];
        $roleTable = FzrbsRole::tableName();
        $usernames = new \yii\db\Expression("TRIM(BOTH ',' FROM REPLACE(CONCAT(',',usernames,','),'," . $userName . ",',','))");
        Yii::$app->db->createCommand()->update($roleTable, ['usernames' => $usernames])->execute();
        if ($userRoleId) {
            $usernames = new \yii\db\Expression("TRIM(BOTH ',' FROM CONCAT(usernames,'," . $userName . "'))");
            Yii::$app->db->createCommand()->update($roleTable, ['usernames' => $usernames], ['in', "id", $userRoleId])->execute();
        }
        $action = '设置';
        $remark = $action . "用户账号角色信息。账号名称：" . $userName . "。";
        if ($userRoleId) {
            $models = FzrbsRole::find()->where(['in', "id", $userRoleId])->all();
            if ($models) {
                $names = array_column($models, 'name');
                $remark .= "角色名称：" . implode(',', $names);
            }
        } else {
            $remark .= "清除账号角色";
        }
        $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        return $this->_result;
    }
}
