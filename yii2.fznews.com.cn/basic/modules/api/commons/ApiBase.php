<?php

namespace  app\modules\api\commons;

use Yii;
use yii\helpers\Json;
use yii\rest\ActiveController;
use app\modules\api\commons\Tools;
use app\modules\api\commons\JwtToken;
use app\modules\api\models\FzrbsOperationLog;
use app\modules\api\models\FzrbsOperationLogParams;
use app\modules\api\models\FzrbsRole;
use app\modules\api\models\FzrbsRouteMenu;
use app\modules\api\models\FzrbsAdmin;
use app\modules\api\models\WeixinOAUserInfo;

/**
 * api接口基类
 */
class ApiBase extends ActiveController
{
    protected $_request = null;
    protected $_userIp = null;
    protected $_apiPrefix = '';
    protected $_webDomain = '';
    protected $_imageSavePath = '';
    protected $_fileSavePath = '';
    protected $_adminInfo;
    protected $_result;
    protected $_routes = [];
    protected $_channels = [];
    protected $_headers = [];
    protected $_routePaths = [];
    protected $_nwchannels = [];

    public function init()
    {
        parent::init();
        $controllerId = strtolower($this->id);
        if (!in_array($controllerId, ['neiwang-information', 'information', 'budget'])) {
            Tools::waf();
        }
        $this->_userIp = Tools::getClientIp();
        $this->_apiPrefix = Yii::$app->params['apiPrefix'];
        $this->_webDomain = Yii::$app->params['webDomain'];
        $queryParams = $bodyParams = [];
        // 获取GET参数
        $queryParams =  Yii::$app->request->queryParams;
        // 获取POST或其他请求方式参数
        if (Yii::$app->request->isPost) {
            $bodyParams = Yii::$app->request->bodyParams;
            if (!$bodyParams) {
                $bodyParams = Json::decode(Yii::$app->request->getRawBody(), true);
            }
        } else {
            $bodyParams = Json::decode(Yii::$app->request->getRawBody(), true);
        }
        // 合并参数
        $this->_request = array_merge($queryParams, is_array($bodyParams) ? $bodyParams : []);
        $this->_imageSavePath = Yii::$app->basePath . '/web/uploaded/';
        $this->_fileSavePath = Yii::$app->basePath . '/attachments/';
        $this->_result = ['success' => true];
        $key = "fzrbs_runAction_" . $controllerId;
        $redis = Yii::$app->redis;
        $insideCallController = $redis->get($key);
        if (!isset($this->_request['fromController'])) {
            $this->_adminInfo = JwtToken::checkJwtToken();
        } else if ($insideCallController == $controllerId && $controllerId == $this->_request['fromController']) {
            if ($this->_request['wxuserid']) {
                $this->_adminInfo = FzrbsAdmin::find()->where(['and', ['=', 'wxuserid', $this->_request['wxuserid']], ['=', 'islock', 0]])->one();
                if (!$this->_adminInfo) {
                    try {
                        $userInfo = WeixinOAUserInfo::find()->select('id,userid,name,avatar,mobile,departmentid,departmentname,gender,status')->where(['and', ['=', 'userid', $this->_request['wxuserid']]])->one();
                        $username = $userInfo ? ($userInfo->mobile ? $userInfo->mobile : $userInfo->name) : '';
                        if ($username) {
                            $data = [
                                'username' => $username,
                                'realname' => $userInfo ? $userInfo->name : '',
                                'mobile' => $userInfo ? $userInfo->mobile : '',
                                'department' => $userInfo ? $userInfo->departmentname : '',
                                'avatar' => $userInfo ? $userInfo->avatar : '',
                                'usertype' => 0,
                                'wxuserid' => $this->_request['wxuserid'],
                                'classify' => 1,
                            ];
                            $this->_adminInfo = Tools::addUser($data);
                            $defaultRoleId = [1];
                            $roleTable = FzrbsRole::tableName();
                            $usernames = new \yii\db\Expression("TRIM(BOTH ',' FROM REPLACE(CONCAT(',',usernames,','),'," . $username . ",',','))");
                            Yii::$app->db->createCommand()->update($roleTable, ['usernames' => $usernames], ['in', "id", $defaultRoleId])->execute();
                            $usernames = new \yii\db\Expression("TRIM(BOTH ',' FROM CONCAT(usernames,'," . $username . "'))");
                            Yii::$app->db->createCommand()->update($roleTable, ['usernames' => $usernames], ['in', "id", $defaultRoleId])->execute();
                        }
                    } catch (\Exception $e) {
                        Tools::responseJson(['success' => true]);
                    }
                }
            }
        }
        if (!$this->_adminInfo) {
            Tools::responseJson(['success' => true, 'errorCode' => '401', 'errorMessage' => '请先登录！']);
        }
        // 调试时登录信息指定到特定用户
        // if ($this->_adminInfo['id'] == 1) {
        //     $adminModel = Tools::checkUserStatus('13805098818');
        //     $this->_adminInfo = $adminModel->attributes;
        // }
        if (isset($this->_adminInfo['token'])) {
            $result['token'] = $this->_adminInfo['token'];
        }
        $this->_userAccessScope();
        $excludeControllers = ['ueditor', 'common', 'budget', 'contract', 'financerole', 'company', 'invoicing', 'qyfinance', 'qypress', 'qyuseseal', 'photodispatch', 'attendance', 'manuscriptscoring','advertisemanange'];
        $this->_headers = Yii::$app->request->getHeaders();
        if ($this->_adminInfo['usertype'] == 0) {
            if (!in_array($controllerId, $excludeControllers) && !in_array($this->_headers['pathName'], $this->_routePaths)) {
                $this->_permissionDeny();
            }
        }
    }

    /**
     * 获取非管理员用户角色权限
     */
    protected function _userAccessScope()
    {
        if ($this->_adminInfo['usertype'] == 0) {
            $this->_routePaths = ['/user/login/', '/account/settings/', '/', '/finance/budget/project/list/'];
            $routes = $channels = $nwchannels = [];
            $where = [
                'and',
                ['>', 'id', 0],
                ['like', "CONCAT(',', usernames, ',')", ',' . $this->_adminInfo['username'] . ',']
            ];
            $models = FzrbsRole::find()->where($where)->all();
            foreach ($models as $model) {
                $model->routes && $routes[] = $model->routes;
                $model->channels && $channels[] = $model->channels;
                $model->neiwang_channels && $nwchannels[] = $model->neiwang_channels;
            }
            if ($routes) {
                $tempRoutes = implode(',', $routes);
                $tempRoutes = explode(',', $tempRoutes);
                $this->_routes = array_unique($tempRoutes);

                $tempChannels = implode(',', $channels);
                $tempChannels = explode(',', $tempChannels);
                $this->_channels = array_unique($tempChannels);

                $tempChannels = implode(',', $nwchannels);
                $tempChannels = explode(',', $tempChannels);
                $this->_nwchannels = array_unique($tempChannels);
            } else {
                $this->_routes = [0];
            }
            $res = FzrbsRouteMenu::find()->where(['in', 'id', $this->_routes])->all();
            foreach ($res as $row) {
                $this->_routePaths[] = $row->path;
            }
            if (in_array('/welcome', $this->_routePaths)) {
                $this->_routePaths[] = '/welcome/';
            }
        }
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
        if ($this->_adminInfo['usertype'] == 0) {
            $where[] = ['in', 'id', $this->_routes];
        }
        $res = FzrbsRouteMenu::find()->where($where)->orderBy('inserttime asc')->all();
        $routes = [];
        foreach ($res as $row) {
            $routes[] = ['id' => $row->id, 'name' => $row->name, 'path' => $row->path, 'image' => $row->image, 'icon' => $this->_getRouteMenuIcon($row->icon)];
        }
        return $routes;
    }

    /**
     * 获取路由菜单图标名称
     * @param string $icon 图标名称
     * @return string 图标名称
     */
    protected function _getRouteMenuIcon($icon)
    {
        return $icon ? $icon : 'icon-caidan';
    }

    /**
     * 操作日志记录
     * @param array $log 操作信息 key:{catalog:操作类别,remark:操作备注}
     */
    protected function _operationlog($log)
    {
        if (is_array($log)) {
            $log['userid'] = $this->_adminInfo['id'];
            $log['username'] = $this->_adminInfo['username'];
            $log['realname'] = $this->_adminInfo['realname'];
            $log['ip'] = $this->_userIp;
            $log['url'] = Yii::$app->request->getHostInfo() . Yii::$app->request->url;
            $log['inserttime'] = time();
            $model = new FzrbsOperationLog;
            $model->attributes = $log;
            $model->save();
            $logId = $model->id;
            $model = new FzrbsOperationLogParams;
            if (isset($this->_request['values']['password'])) {
                unset($this->_request['values']['password']);
            }
            if (isset($this->_request['values']['oldpassword'])) {
                unset($this->_request['values']['oldpassword']);
            }
            if (isset($this->_request['values']['newpassword'])) {
                unset($this->_request['values']['newpassword']);
            }
            if (isset($this->_request['values']['confirmpassword'])) {
                unset($this->_request['values']['confirmpassword']);
            }
            $params = json_encode($this->_request, JSON_UNESCAPED_UNICODE);
            $model->attributes = ['logid' => $logId, 'params' => $params];
            $model->save();
        }
    }

    /**
     * 管理员权限访问接口和无权限访问路由
     */
    protected function _permissionDeny()
    {
        if ($this->_adminInfo['usertype'] == 0) {
            $result = ['success' => true, 'errorCode' => '403', 'errorMessage' => '没有权限访问接口！', 'data' => []];
            Tools::responseJson($result);
        }
    }

    /**
     * 用户是否绑定微信企业号
     */
    protected function _checkUserBindWx()
    {
        if (!$this->_adminInfo['wxuserid']) {
            $result = ['success' => true, 'errorCode' => '403', 'errorMessage' => '没有权限访问接口！', 'data' => []];
            Tools::responseJson($result);
        }
    }
}
