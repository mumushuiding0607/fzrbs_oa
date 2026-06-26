<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;
use app\modules\api\models\FzrbsRouteMenu;

/**
 * 微信企业应用相关接口类
 */
class AppsController extends ApiBase
{
    public $enableCsrfValidation = false;
    protected $_orderBy = 'id asc';

    public function init()
    {
        parent::init();
    }

    /**
     * 企业微信应用动作
     */
    public function actionIndex()
    {
        $apps = [];
        // 企业微信应用菜单id
        $parentId = 140;
        $where = [
            'and',
            ['>', 'id', 0],
            ['=', 'hideinmenu', 0],
            ['=', 'parentid', $parentId],
        ];
        $model = new FzrbsRouteMenu;
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
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $apps]);
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
        if ($otherCondition) {
            $where[] = $otherCondition;
        }
        $res = FzrbsRouteMenu::find()->where($where)->orderBy('inserttime asc')->all();
        $routes = [];
        foreach ($res as $row) {
            $routes[] = ['id' => $row->id, 'name' => $row->name, 'path' => $row->path, 'image' => 'https://fzrb.fznews.com.cn'.$row->image];
        }
        return $routes;
    }
}
