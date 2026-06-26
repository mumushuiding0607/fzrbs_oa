<?php

namespace app\modules\api\controllers\apps;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\models\FzrbsMyApps;
use Yii;

/**
 * 企业微信应用首页相关接口类
 */
class AppsController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\FzrbsRouteMenu';
    protected $_orderBy = 'id asc';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }

    public function init()
    {
        parent::init();
        $this->_checkUserBindWx();
    }

    /**
     * 重写index的业务实现动作
     */
    public function actionIndex()
    {
        $apps = [];
        // 我的应用
        $table = FzrbsMyApps::tableName();
        $table1 = $this->modelClass::tableName();
        $where = [
            'and',
            ['=', "$table1.hideinmenu", 0],
            ['=', "$table.userid", $this->_adminInfo['wxuserid']],
        ];
        if ($this->_adminInfo['usertype'] == 0) {
            $where[] = ['in', "$table.appid", $this->_routes];
        }
        $query = new \yii\db\Query();
        $res = $query->select(["$table1.id", "$table1.name", "$table1.path", "$table1.image", "$table1.icon"])->from($table)->join('LEFT JOIN', $table1, "$table1.id=$table.appid")->where($where)->orderBy("$table.id desc")->all();
        $children = [];
        $route = ['id' => 0, 'name' => '我的应用', 'path' => '', 'children' => $children];
        foreach ($res as $row) {
            $row['id'] = intval($row['id']);
            $children[] = $row;
        }
        $route['children'] = $children;
        $apps[] = $route;
        // 企业微信应用菜单id
        $parentId = 140;
        $where = [
            'and',
            ['>', 'id', 0],
            ['=', 'hideinmenu', 0],
            ['=', 'parentid', $parentId],
        ];
        if ($this->_adminInfo['usertype'] == 0) {
            $where[] = ['in', 'id', $this->_routes];
        }
        $model = $this->modelClass;
        $model = $model::find()->select('id, name, path')->where($where);
        $res = $model->orderBy($this->_orderBy)->all();
        foreach ($res as $row) {
            $route = ['id' => $row->id, 'name' => $row->name, 'path' => $row->path];
            $children = $this->_getRouteMenuChildren($row->id, ['=', 'hideinmenu', 0]);
            if ($children) {
                $route['children'] = $children;
            }
            $apps[] = $route;
        }
        $this->_result['data'] = $apps;
        return $this->_result;
    }

    /**
     * 我的应用操作接口
     */
    public function actionMyApps()
    {
        $appId = intval($this->_request['appId']);
        $action = $this->_request['action'];
        if ($action) {
            $userId = $this->_adminInfo['wxuserid'];
            if (in_array($action, ['add', 'remove'])) {
                if ($appId) {
                    switch ($action) {
                        case 'add':
                            $model = new FzrbsMyApps();
                            $model->userid = $userId;
                            $model->appid = $appId;
                            $model->inserttime = date('Y-m-d H:i:s');
                            $model->save();
                            break;
                        case 'remove':
                            FzrbsMyApps::deleteAll(['and', ['=', 'userid', $userId], ['=', 'appid', $appId]]);
                    }
                } else {
                    $this->_result = Tools::wrongRules(1000, '参数错误');
                }
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }
}
