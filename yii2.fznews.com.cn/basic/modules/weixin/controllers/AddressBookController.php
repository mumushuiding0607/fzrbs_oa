<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;
use app\modules\api\models\WxDepartment;
use app\modules\api\models\WeixinOAUserInfo;

/**
 * 通讯录相关接口类
 */
class AddressBookController extends ApiBase
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
        $parentId = isset($this->_request['parentid']) ? $this->_request['parentid'] : 0;
        $where = [
            'and',
            ['>', 'id', 0],
            ['=', 'parentid', $parentId],
        ];
        if (isset($this->_request['childrenId']) && $this->_request['childrenId']) {
            $where[] = ['in', 'id', explode(',', $this->_request['childrenId'])];
        }
        $data = [];
        if (intval($this->_request['firstRequest']) === 1 && $parentId > 0) {
            $rootNode = WxDepartment::find()->where(['=', 'id',  $parentId])->orderBy('order desc')->one();
            if ($rootNode) {
                $data[] = ['title' => $rootNode->name, 'key' => strval($rootNode->id), 'value' => strval($rootNode->id), 'isLeaf' => false];
            }
        } else {
            $res = WxDepartment::find()->where($where)->orderBy('order desc')->all();
            foreach ($res as $row) {
                $node = ['title' => $row->name, 'key' => strval($row->id), 'value' => strval($row->id), 'isLeaf' => true];
                $children = [];
                $child = WxDepartment::find()->where(['=', 'parentid', $row->id])->orderBy('order desc')->one();
                if ($child) {
                    $node['isLeaf'] = false;
                }
                if ($this->_request['showAll'] && !$node['isLeaf']) {
                    $children  = $this->_getLocalDepartmentChildren($row->id);
                }
                if (intval($this->_request['user']) === 1) {
                    $users = WeixinOAUserInfo::find()->where(['and', ['=', 'departmentid', $row->id], ['=', 'status', 1]])->all();
                    if ($users) {
                        $node['isLeaf'] = false;
                        if ($this->_request['showAll']) {
                            foreach ($users as $user) {
                                $children[] = ['title' => $user['name'], 'key' => $user['userid'], 'value' => $user['userid'], 'isLeaf' => true];
                            }
                        }
                    }
                }
                if ($children) {
                    $node['isLeaf'] = false;
                    $node['children'] = $children;
                }
                $data[] = $node;
            }
            if (intval($this->_request['user']) === 1) {
                $users = WeixinOAUserInfo::find()->where(['and', ['=', 'departmentid', $parentId], ['=', 'status', 1]])->all();
                if ($users) {
                    foreach ($users as $user) {
                        $data[] = ['title' => $user['name'], 'key' => $user['userid'], 'value' => $user['userid'], 'isLeaf' => true];
                    }
                }
            }
        }
        $this->_result['data'] = $data;
        Tools::responseJson($this->_result);
    }

    /**
     * 获取部门子节点(本地数据表部门)
     * @param int $parentId 父节点id
     * @return array 子节点信息
     */
    protected function _getLocalDepartmentChildren($parentId)
    {
        $where = [
            'and',
            ['=', 'parentid', $parentId],
        ];
        if (isset($this->_request['childrenId']) && $this->_request['childrenId']) {
            $where[] = ['in', 'id', explode(',', $this->_request['childrenId'])];
        }
        $res = WxDepartment::find()->where($where)->orderBy('order desc')->all();
        $routes = [];
        foreach ($res as $row) {
            $node = ['title' => $row->name, 'key' => strval($row->id), 'value' => strval($row->id), 'isLeaf' => true];
            $children = [];
            $child = WxDepartment::find()->where(['=', 'parentid', $row->id])->orderBy('order desc')->one();
            if ($child) {
                $node['isLeaf'] = false;
            }
            if ($this->_request['showAll'] && !$node['isLeaf']) {
                $children  = $this->_getLocalDepartmentChildren($row->id);
            }
            if (intval($this->_request['user']) === 1) {
                $users = WeixinOAUserInfo::find()->where(['and', ['=', 'departmentid', $row->id], ['=', 'status', 1]])->all();
                if ($users) {
                    foreach ($users as $user) {
                        $children[] = ['title' => $user['name'], 'key' => $user['userid'], 'value' => $user['userid'], 'isLeaf' => true];
                    }
                }
            }
            if ($children) {
                $node['isLeaf'] = false;
                $node['children'] = $children;
            }
            $routes[] = $node;
        }
        return $routes;
    }
}
