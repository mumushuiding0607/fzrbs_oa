<?php

namespace  app\modules\weixin\commons;

use Yii;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use app\modules\weixin\commons\Tools;
use app\modules\weixin\commons\Aes;
use app\modules\api\models\WeixinOAUserInfo;

/**
 * api接口基类
 */
class ApiBase extends Controller
{
    protected $_request = null;
    protected $_userIp = null;
    protected $_result;
    protected $_UserId = null;
    protected $_userInfo = null;
    protected $_webDomain = '';
    protected $_imageSavePath = '';
    protected $_fileSavePath = '';

    public function init()
    {
        parent::init();
        $this->_userIp = Tools::getClientIp();
        $this->_imageSavePath = Yii::$app->basePath . '/web/uploaded/';
        $this->_fileSavePath = Yii::$app->basePath . '/attachments/';
        if (Yii::$app->request->isPost || Yii::$app->request->isPut) {
            $this->_request = Json::decode(Yii::$app->request->getRawBody(), true);
            !$this->_request && $this->_request = Yii::$app->request->post();
        } else if (Yii::$app->request->isGet || Yii::$app->request->isDelete) {
            $this->_request = Yii::$app->request->queryParams;
        }
        if ($this->_request) {
            $this->_request = ArrayHelper::htmlEncode($this->_request);
            $sign = $this->_request['sign'];
            $content = $this->_request['content'];
            // 禁用浮点数精度，解决浮点数参数引发的签名跟用户端签名不匹配问题
            ini_set('precision', -1);
            $this->_request = json_decode(Aes::decrypt($content), true);
        }

        if (!isset($_FILES['upfile'])) {
            if (!Aes::checkSignature($this->_request, $sign)) {
                Tools::writeLog('sign', '签名出错---' . $content . '---' . $sign);
                Tools::responseJson(Tools::wrongRules(1000, '签名出错'));
            }
        } else {
            $this->_request = Yii::$app->request->post();
            if ($this->_request['client_id'] != 'rocr8kSx7SZLHfokGTphGuB1bfGIGvOj') {
                Tools::responseJson(Tools::wrongRules(1000, '请求无效'));
            }
        }
        $this->_result = ['success' => true];
        $this->_webDomain = Yii::$app->params['webDomain'];
        if (isset($this->_request['wxuserid'])) {
            $this->_UserId = $this->_request['wxuserid'];
            $this->_userInfo = $this->_getUserInfo($this->_UserId);
        }
    }

    protected function _getUserInfo($userId)
    {
        $userInfo = WeixinOAUserInfo::find()->select('id,userid,name,avatar,mobile,departmentid,departmentname,gender,status')->where(['and', ['=', 'userid', $userId]])->one();
        return $userInfo;
    }

    protected function _runAction($controllerId, $actionId, $method = null)
    {
        $controllerName = $this->id;
        $key = "fzrbs_runAction_" . $controllerName;
        $redis = Yii::$app->redis;
        $redis->setex($key, 60, $controllerName);
        $this->_request['fromController'] = $controllerName;
        Yii::$app->request->setBodyParams($this->_request);
        if ($method) {
            $_SERVER['REQUEST_METHOD'] = $method;
            if (in_array($method, ['GET'])) {
                Yii::$app->request->setQueryParams($this->_request);
            }
        }
        $controllerPath = "\app\modules\api\controllers\\" . $controllerId;
        return (new $controllerPath($this->id, $this->module))->runAction($actionId);
    }
}
