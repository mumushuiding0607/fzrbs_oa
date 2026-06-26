<?php

namespace app\modules\api\controllers;

use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\commons\Uploader;
use app\modules\api\models\FzrbsRouteMenu;
use Da\QrCode\QrCode;

/**
 * 用户账号接口类
 */
class AccountController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\FzrbsAdmin';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }

    /**
     * 当前登录用户账号信息动作
     */
    public function actionCurrent()
    {
        $adminInfo = $this->_adminInfo;
        $adminInfo['avatar'] =  $adminInfo['avatar'] ? $adminInfo['avatar'] : '/images/default_avatar.png';
        $adminInfo['access'] =  $adminInfo['usertype'] == 1 ? 'admin' : 'user';
        unset($adminInfo['password']);
        unset($adminInfo['salt']);
        if (isset($adminInfo['token'])) {
            unset($adminInfo['token']);
        }
        $this->_result['data'] = $adminInfo;
        return $this->_result;
    }

    /**
     * 用户账号退出登录动作
     */
    public function actionLoginOut()
    {
        if ($this->_adminInfo) {
            $logInfo = [
                'username' => $this->_adminInfo['username'],
                'realname' => $this->_adminInfo['realname'],
                'ip' => Tools::getClientIp(),
                'logtype' => '退出',
                'remark' => '用户自动退出',
                'inserttime' => date('Y-m-d H:i:s'),
            ];
            Tools::loginAndOutLog($logInfo);
        }
        return $this->_result;
    }

    /**
     * 保存用户上传头像动作
     */
    public function actionAvatarUpload()
    {
        if (isset($_FILES['avatar'])) {
            $userId = $this->_request['userId'];
            $oldAvatar = $this->_request['oldAvatar'];
            if ($userId && $userId == $this->_adminInfo['id']) {
                $config = array(
                    "rootPath" => $this->_imageSavePath,
                    "savePath" => 'avatar',
                    "maxSize" => 2048000,
                    "allowFiles" => array(".png", ".jpg", ".jpeg"),
                );
                if ($oldAvatar && $oldAvatar != '/images/default_avatar.png' && substr($oldAvatar, 0, 4) != 'http') {
                    $oldAvatar = explode('/', str_replace('/uploaded/', '', $oldAvatar));
                    $config['oldName'] = array_pop($oldAvatar);
                    $config['oldPath'] = implode('/', $oldAvatar);
                }
                $upInfo = new Uploader("avatar", $config);
                $upResult = $upInfo->getFileInfo();
                if (isset($upResult["url"])) {
                    $this->_result["data"] = $upResult;
                    $this->modelClass::updateAll(['avatar' => $upResult['url']], "id=:id", [':id' => $userId]);
                }
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 用户密码更新动作
     */
    public function actionChangePassword()
    {
        if ($this->_request['id'] && $this->_request['oldpassword'] && $this->_request['newpassword']) {
            $userId = $this->_request['id'];
            $password = $this->_request['oldpassword'];
            $newPassword = $this->_request['newpassword'];
            $confirmPassword = $this->_request['confirmpassword'];
            if ($newPassword != $confirmPassword) {
                return Tools::wrongRules('3000', '密码确认不正确');
            }
            $model = Tools::getUserInfo(['id' => $userId]);
            if (!$model) {
                return Tools::wrongRules('3001', '用户已经不存在');
            } else if (SM3(md5(md5(trim($password)) . $model->salt)) != $model->password) {
                return Tools::wrongRules('3002', '旧密码不正确');
            }
            if ($password == $newPassword) {
                return Tools::wrongRules('3003', '新密码不能跟旧密码完全一样');
            }
            if ($userId && $userId == $this->_adminInfo['id']) {
                $data = Tools::setSM3Password(['password' => $newPassword]);
                $this->modelClass::updateAll($data, "id=:id", [':id' => $userId]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 用户路由菜单动作
     */
    public function actionRouteMenu()
    {
        $routes = [];
        $parentId = isset($this->_request['parentid']) ? $this->_request['parentid'] : 0;
        $where = [
            'and',
            ['>', 'id', 0],
            ['=', 'parentid', $parentId],
        ];
        if ($this->_adminInfo['usertype'] == 0) {
            $where[] = ['in', 'id', $this->_routes];
        }
        $model = FzrbsRouteMenu::find()->where($where);
        $res = $model->orderBy('inserttime asc')->all();
        foreach ($res as $row) {
            $route = ['name' => $row->name, 'path' => $row->path, 'icon' => $this->_getRouteMenuIcon($row->icon)];
            if (!$row->hidechildreninmenu) {
                $children = $this->_getRouteMenuChildren($row->id);
                if ($children) {
                    $route['children'] = $children;
                }
            }
            $routes[] = $route;
        }
        if (!$routes && $this->_adminInfo['usertype'] == 1) {
            $routes = [
                ['name' => '欢迎', 'path' => '/welcome', 'icon' => 'icon-huanyingye'],
                ['name' => '系统设置', 'path' => '/admin/', 'icon' => 'icon-31shezhi', 'children' => [
                    ['name' => '用户管理', 'path' => '/admin/list/', 'icon' => 'icon-jurassic_user'],
                    ['name' => '路由菜单管理', 'path' => '/admin/route/list/', 'icon' => 'icon-caidan'],
                ]],
            ];
        }
        $this->_result['data'] = $routes;
        if ($this->_adminInfo['usertype'] == 0) {
            $this->_result['routes'] = $this->_routePaths;
        }
        return $this->_result;
    }

    /**
     * 解除绑定动作
     */
    public function actionUnbind()
    {
        // 标识：1：微信，2：企业微信
        $flag = intval($this->_request['flag']);
        $userName = $this->_request['username'];
        if ($flag && $userName) {
            $update = $flag == 1 ? ['wxopenid' => ''] : ['wxuserid' => ''];
            Yii::$app->db->createCommand()->update($this->modelClass::tableName(), $update, ['=', "username", $userName])->execute();
            $action = '设置';
            $remark = $action . "用户账号解除" . ($flag == 1 ? '微信' : '企业微信') . "绑定。账号名称：" . $userName . "。";
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        }
        return $this->_result;
    }

    /**
     * 绑定微信号二维码动作
     */
    public function actionBindWeixin()
    {
        $userId = $this->_adminInfo['id'];
        $qrcodeCode = md5(date("Ymdhis") . uniqid() . rand(1, 1000) . $userId);
        $time = time();
        $checkMd5 = md5($qrcodeCode . $time);
        $host = $this->_request['host'] ? $this->_request['host'] : '218.5.3.213';
        $port = $this->_request['port'] ? $this->_request['port'] : '8030';
        $useSSL = $this->_request['useSSL'] ? $this->_request['useSSL'] : 0;
        if (!$useSSL && !$port) {
            $port = '80';
        }
        $backUrl = urlencode('https://api.fznews.com.cn/weixin/oa-user-bind/index?id=' . $userId . '&time=' . $time . '&checkmd5=' . $checkMd5 . '&host=' . $host . '&port=' . $port . '&useSSL=' . $useSSL);
        $qrcodeUrl = "https://rtq7g0daat.fznews.com.cn/index.php?r=cms/jump/oauth&time={$time}&checkmd5={$checkMd5}&backurl=" . $backUrl;
        $qrCode = (new QrCode($qrcodeUrl))->setSize(200)->setMargin(5);
        $this->_result['data'] = $qrCode->writeDataUri();
        $tableName = 'fzrbs_wx_bind_login';
        $query = new \yii\db\Query();
        $row = $query->select('*')->from($tableName)->where(['and', ['=', 'userid', $userId], ['=', 'type', 1]])->one();
        if ($row) {
            Yii::$app->db->createCommand()->update($tableName, [
                'checkmd5' => $checkMd5,
                'updatetime' => date('Y-m-d H:i:s'),
            ], 'id=:id', [':id' => $row['id']])->execute();
        } else {
            Yii::$app->db->createCommand()->insert($tableName, [
                'userid' => $userId,
                'type' => 1,
                'checkmd5' => $checkMd5,
                'updatetime' => date('Y-m-d H:i:s'),
            ])->execute();
        }
        return $this->_result;
    }
}
