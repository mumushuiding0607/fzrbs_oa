<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\commons\Uploader;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\models\WxDepartment;
use app\modules\api\models\WeixinOAUserInfo;

/**
 * 通用接口类
 */
class CommonController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\FzrbsAdmin';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }

    /**
     * 上传文件保存
     */
    public function actionUpload()
    {
        if (isset($_FILES['upfile'])) {
            $uploadType = isset($this->_request['uploadType']) ? intval($this->_request['uploadType']) : 1;
            $uploadPath = isset($this->_request['uploadPath']) ? $this->_request['uploadPath'] : 'common';
            $types = [
              1 => [".png", ".jpg", ".jpeg", ".gif", ".bmp", ".webp"],
              2 => [".xml", ".doc", ".docx", ".pdf", ".txt", ".xls", ".xlsx", ".zip", ".rar", ".ppt", ".pptx"],
              3 => [".xml", ".png", ".jpg", ".jpeg", ".gif", ".bmp", ".webp", ".doc", ".docx", ".pdf", ".txt", ".xls", ".xlsx", ".zip", ".rar", ".ppt", ".pptx", ".mp3", ".mp4", ".wmv", ".rm", ".mov", ".avi", ".m4v", ".3gp"],
              4 => [".mp3", ".mp4", ".wmv", ".rm", ".mov", ".avi", ".m4v", ".3gp"],
          ];
            $rootPath = $this->_imageSavePath;
            // 保护文件保存到web访问目录外
            $protect = $this->_request['protect'];
            if ($protect) {
                $rootPath = $this->_fileSavePath;
            }
            $oldName = $this->_request['oldName'];
            $config = array(
                "rootPath" => $rootPath,
                "savePath" => $uploadPath,
                "maxSize" => 2048000,
                "allowFiles" => $types[$uploadType],
            );
            if ($oldName) {
                $oldName = explode('/', str_replace('/uploaded/', '', $oldName));
                $config['oldName'] = array_pop($oldName);
                $config['oldPath'] = implode('/', $oldName);
            }
            $upInfo = new Uploader("upfile", $config);
            $upResult = $upInfo->getFileInfo();
            if (isset($upResult["url"])) {
                $this->_result["data"] = $upResult;
            }
        }
        return $this->_result;
    }

    /**
     * 上传文件删除
     */
    public function actionUploadDelete()
    {
        if (isset($this->_request['fileurl']) && $this->_request['fileurl']) {
            $rootPath = $this->_imageSavePath;
            $protect = $this->_request['protect'];
            if ($protect) {
                $rootPath = $this->_fileSavePath;
            }
            Tools::deleteFile($rootPath, $this->_request['fileurl']);
        }
        return $this->_result;
    }

    /**
     * 企业号通讯录部门或本地部门信息
     */
    public function actionDepartment()
    {
        set_time_limit(0);
        $parentId = isset($this->_request['parentid']) ? $this->_request['parentid'] : 0;
        $localDepartment = intval($this->_request['local']) ? 1 : 0;
        if ($localDepartment == 1) {
            // 本地数据表部门信息
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
                        $users = WeixinOAUserInfo::find()->where(['and',['=', 'departmentid', $row->id],['=', 'status', 1],['=', 'st', 1]])->all();
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
                    $users = WeixinOAUserInfo::find()->where(['and',['=', 'departmentid', $parentId],['=', 'status', 1],['=', 'st', 1]])->all();
                    if ($users) {
                        foreach ($users as $user) {
                            $data[] = ['title' => $user['name'], 'key' => $user['userid'], 'value' => $user['userid'], 'isLeaf' => true];
                        }
                    }
                }
            }
            $this->_result['data'] = $data;
        } else {
            // 企业号通讯录接口部门
            $sendResult =  WxQyhJk::department($parentId);
            if (!$sendResult['errorMessage']) {
                $departments = $sendResult['data'];
                if (isset($this->_request['childrenId']) && $this->_request['childrenId']) {
                    $requestDepartmentIds = explode(',', $this->_request['childrenId']);
                    $requestDepartments = [];
                    foreach ($departments as $department) {
                        if (in_array($department['id'], $requestDepartmentIds)) {
                            $requestDepartments[] = $department;
                        }
                    }
                    $departments = $requestDepartments;
                }
                $data = [];
                if (is_array($departments) && count($departments) > 0) {
                    if ($this->_request['showAll']) {
                        $data[] =  ['title' => '福州日报社', 'key' => '1', 'value' => '1', 'children' => $this->_getDepartmentChildren($departments, 1)];
                    } else {
                        if (intval($this->_request['firstRequest']) === 1 && $parentId > 0) {
                            $data[] = ['title' => $departments[0]['name'], 'key' => strval($departments[0]['id']), 'value' => strval($departments[0]['id']), 'isLeaf' => false];
                        } else {
                            foreach ($departments as $department) {
                                if ($department['parentid'] == $parentId) {
                                    $sortdepartments[$department['order']] = ['title' => $department['name'], 'key' => strval($department['id']), 'value' => strval($department['id']), 'isLeaf' => false];
                                }
                            }
                            if ($sortdepartments) {
                                uksort($sortdepartments, array($this, '_departmentSort'));
                                $data = array_values($sortdepartments);
                            }
                        }
                    }
                }
                if (!$this->_request['showAll']) {
                    if (intval($this->_request['user']) === 1) {
                        $sendResult = WxQyhJk::departmentUser($parentId);
                        $users = $sendResult['data'];
                        if ($users) {
                            foreach ($users as $user) {
                                $data[] = ['title' => $user['name'], 'key' => $user['userid'], 'value' => $user['userid'], 'isLeaf' => true];
                            }
                        }
                    }
                }
                $this->_result['data'] = $data;
            } else {
                $this->_result['errorMessage'] = $sendResult['errorMessage'];
            }
        }
        return $this->_result;
    }

    /**
     * 员工搜索
     */
    public function actionSearchUser()
    {
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if ($this->_request['username']) {
            $where[] = ['like', 'name', $this->_request['username']];
        }
        $users = WeixinOAUserInfo::find()->select('id,userid,name,avatar,departmentname')->where($where)->all();
        $this->_result['data']['data'] = $users;
        return $this->_result;
    }

    /**
     * 获取部门子节点(企业号通讯录)
     * @param array $departments 部门信息
     * @param int $parentId 父节点id
     * @return array 子节点信息
     */
    protected function _getDepartmentChildren($departments, $parentId)
    {
        $routes = [];
        foreach ($departments as $row) {
            if (isset($this->_request['childrenId']) && $this->_request['childrenId']) {
                $requestDepartmentIds = explode(',', $this->_request['childrenId']);
                if (!in_array($row['id'], $requestDepartmentIds)) {
                    continue;
                }
            }
            if ($row['parentid'] == $parentId) {
                // foreach ($departments as $row1) {
                //     if ($row1['parentid'] == $row['id']) {
                //         $isLeaf = false;
                //         break;
                //     }
                // }
                $node = ['title' => $row['name'], 'key' => strval($row['id']), 'value' => strval($row['id']), 'isLeaf' => true];
                if ($this->_request['showAll']) {
                    $children  = $this->_getDepartmentChildren($departments, $row['id']);
                    if (intval($this->_request['user']) === 1) {
                        $sendResult = WxQyhJk::departmentUser($row['id']);
                        $users = $sendResult['data'];
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
                }
                $routes[] = $node;
            }
        }
        return $routes;
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
                $users = WeixinOAUserInfo::find()->where(['and',['=', 'departmentid', $row->id],['=', 'status', 1],['=', 'st', 1]])->all();
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

            if ($this->_request['noBodyDepartment'] && $node['isLeaf'] && intval($node['key']) > 0 && !$node['children']){
                continue;
            }
            
            $routes[] = $node;
        }
        return $routes;
    }

    protected function _departmentSort($a, $b)
    {
        if ($a == $b) return 0;
        return ($a > $b) ? -1 : 1;
    }
}
