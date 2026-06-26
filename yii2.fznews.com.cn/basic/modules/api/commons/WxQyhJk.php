<?php

namespace app\modules\api\commons;

use Yii;
use app\modules\api\commons\Aes;
use linslin\yii2\curl;

/**
 * 微信企业号接口
 */
class WxQyhJk
{
    /**
     * 向企业号应用发送消息
     * @param string $appId 企业号应用id
     * @param string $userId 企业号用户id
     * @param string $content 发送内容
     * @param string $msgType 消息类型
     * @return array 结果信息
     */
    public static function sendMessage($appId, $userId, $content, $msgType = 'text')
    {
      
        $data['errorMessage'] = '';
        $postdata = ['appid' => $appId, 'userid' => $userId, 'content' => $content, 'msgtype' => $msgType];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/fzrbs-qyhyy/send-message';
        $params = Aes::encryptParams($postdata);
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($params, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_IGNORE))->post($url);

        if ($curl->errorCode === null) {
            $result = json_decode($response, true);
            if ($result['errorMessage']) {
                $data['errorMessage'] = $result['errorMessage'];
            }
        } else {
            $data['errorMessage'] = '接口调用失败';
        }
    
        return $data;
    }

    /**
     * 获取通讯录部门
     * @param int $id 父部门id
     * @return array 结果信息
     */
    public static function department($id)
    {
        $data['errorMessage'] = '';
        $postdata = ['id' => $id];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/fzrbs-qyhyy/department';
        $params = Aes::encryptParams($postdata);
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($params))->post($url);
        if ($curl->errorCode === null) {
            $data = json_decode($response, true);
        } else {
            $data['errorMessage'] = '接口调用失败';
        }
        return $data;
    }

    /**
     * 获取通讯录部门成员
     * @param int $id 部门id
     * @return array 结果信息 (成员简单信息{"userid": "zhangsan", "name": "张三","department": [1, 2],"open_userid": "xxxxxx"})
     */
    public static function departmentUser($id)
    {
        $data['errorMessage'] = '';
        $postdata = ['id' => $id];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/fzrbs-qyhyy/department-user';
        $params = Aes::encryptParams($postdata);
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($params))->post($url);
        if ($curl->errorCode === null) {
            $data = json_decode($response, true);
        } else {
            $data['errorMessage'] = '接口调用失败';
        }
        return $data;
    }

    /**
     * 获取通讯录部门成员详细信息
     * @param int $id 部门id
     * @return array 结果信息
     */
    public static function departmentUserMore($id)
    {
        $data['errorMessage'] = '';
        $postdata = ['id' => $id];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/fzrbs-qyhyy/department-user-more';
        $params = Aes::encryptParams($postdata);
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($params))->post($url);
        if ($curl->errorCode === null) {
            $data = json_decode($response, true);
        } else {
            $data['errorMessage'] = '接口调用失败';
        }
        return $data;
    }

    /**
     * 获取成员详细信息
     * @param string $userId 成员id
     * @return array 结果信息
     */
    public static function user($userId)
    {
        $data['errorMessage'] = '';
        $postdata = ['userid' => $userId];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/fzrbs-qyhyy/user';
        $params = Aes::encryptParams($postdata);
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($params))->post($url);
        if ($curl->errorCode === null) {
            $data = json_decode($response, true);
        } else {
            $data['errorMessage'] = '接口调用失败';
        }
        return $data;
    }
}
