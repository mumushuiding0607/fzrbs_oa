<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\QYWeiXinAPI;
use app\modules\weixin\commons\Tools;
use app\modules\api\models\WeixinOAUserInfo;

/**
 * 报社企业号应用接口类
 */
class FzrbsQyhyyController extends ApiBase
{
    public $enableCsrfValidation = false;

    /**
     * 发送应用消息动作
     */
    public function actionSendMessage()
    {
        $appId = $this->_request['appid'];
        $userId = $this->_request['userid'];
        $content = $this->_request['content'];
        $toparty = $this->_request['toparty'] ? $this->_request['toparty'] : '';
        $msgType = $this->_request['msgtype'];
        if (!$msgType) {
            $msgType = 'text';
        }
        $qyhapi = new QYWeiXinAPI($appId);
        $data = array(
            'touser' => $userId,
            'toparty' => '',
            'msgtype' => $msgType,
            'agentid' => $appId,
        );
        switch ($msgType) {
            case 'text':
                $data['text'] = ['content' => $content];
                break;
            case 'textcard':
                $data['textcard'] = $content;
                break;
        }
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $result = $qyhapi->sendMessage($data);
        if ($result) {
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => $qyhapi->errmsg, 'errorCode' => $qyhapi->errcode]);
        }
        exit;
    }

    /**
     * 企业号通讯录部门动作
     */
    public function actionDepartment()
    {
        $id = $this->_request['id'];
        $qyhapi = new QYWeiXinAPI('', '', 'y4j-rdxPBySIu5FQkxiJq7JBn7-5fLZoNdHJynZulbw');
        $result = $qyhapi->departmentList($id);
        if ($result) {
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result['department']]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => $qyhapi->errmsg, 'errorCode' => $qyhapi->errcode]);
        }
        exit;
    }

    /**
     * 企业号通讯录部门成员动作
     */
    public function actionDepartmentUser()
    {
        $id = $this->_request['id'];
        $qyhapi = new QYWeiXinAPI('', '', 'y4j-rdxPBySIu5FQkxiJq7JBn7-5fLZoNdHJynZulbw');
        $result = $qyhapi->userSimpleList($id);
        if ($result) {
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result['userlist']]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => $qyhapi->errmsg, 'errorCode' => $qyhapi->errcode]);
        }
        exit;
    }

    /**
     * 企业号通讯录部门成员详细信息动作
     */
    public function actionDepartmentUserMore()
    {
        $id = $this->_request['id'];
        $qyhapi = new QYWeiXinAPI('', '', 'y4j-rdxPBySIu5FQkxiJq7JBn7-5fLZoNdHJynZulbw');
        $result = $qyhapi->userList($id);
        if ($result) {
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result['userlist']]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => $qyhapi->errmsg, 'errorCode' => $qyhapi->errcode]);
        }
        exit;
    }

    /**
     * 企业号成员详细信息动作
     */
    public function actionUser()
    {
        $userId = $this->_request['userid'];
        $qyhapi = new QYWeiXinAPI('', '', 'y4j-rdxPBySIu5FQkxiJq7JBn7-5fLZoNdHJynZulbw');
        $result = $qyhapi->userGet($userId);
        if ($result) {
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => $qyhapi->errmsg, 'errorCode' => $qyhapi->errcode]);
        }
        exit;
    }

    /**
     * 获取单个部门详情动作
     */
    public function actionDepartmentGet()
    {
        $id = $this->_request['id'];
        $qyhapi = new QYWeiXinAPI('', '', 'y4j-rdxPBySIu5FQkxiJq7JBn7-5fLZoNdHJynZulbw');
        $result = $qyhapi->departmentGet($id);
        if ($result) {
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result['department']]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => $qyhapi->errmsg, 'errorCode' => $qyhapi->errcode]);
        }
        exit;
    }

    /**
     * 通过openid获取用户企业通信录信息动作
     */
    public function actionConvert2UserId()
    {
        $openId = $this->_request['openid'];
        $qyhapi = new QYWeiXinAPI('', '', 'y4j-rdxPBySIu5FQkxiJq7JBn7-5fLZoNdHJynZulbw');
        $result = $qyhapi->convert2UserId($openId);
        if ($result) {
            if ($result['userid']) {
                $userInfo = WeixinOAUserInfo::find()->where(['=', 'userid', $result['userid']])->one();
                if ($userInfo) {
                    $result = $userInfo;
                }
            }
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => $qyhapi->errmsg, 'errorCode' => $qyhapi->errcode]);
        }
        exit;
    }

    /**
     * 根据扫描回调code获取userid动作
     */
    public function actionCode2UserId()
    {
        $code = $this->_request['code'];
        $qyhapi = new QYWeiXinAPI('', '', 'a0tKF46itKTuAyqozo-eFDUaqgnagwPCXl0_TYCL-iE');
        $result = $qyhapi->getUserInfo($code);
        if ($result) {
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => $qyhapi->errmsg, 'errorCode' => $qyhapi->errcode]);
        }
        exit;
    }

    /**
     * 根据企业微信oauth code获取userid动作
     */
    public function actionOauth2UserId()
    {
        $code = $this->_request['code'];
        $appId = $this->_request['appid'];
        $qyhapi = new QYWeiXinAPI($appId, '', '');
        $result = $qyhapi->oauth2UserId($code);
        if ($result) {
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => $qyhapi->errmsg, 'errorCode' => $qyhapi->errcode]);
        }
        exit;
    }
}
