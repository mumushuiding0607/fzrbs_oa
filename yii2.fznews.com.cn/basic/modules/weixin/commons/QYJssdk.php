<?php

namespace app\modules\weixin\commons;

use Yii;
use app\modules\weixin\commons\QYWeiXinAPI;
use linslin\yii2\curl;

class QYJssdk
{
    private $appId;
    private $secret;
    private $corpId;

    public function __construct($appId = '1000002')
    {
        $this->appId = $appId;
        $query = new \yii\db\Query();
        $row = $query->select('*')->from('weixin_qy_appinterface')->where(['=', 'appid', $this->appId])->one();
        if ($row) {
            $this->secret = $row['secret'];
            $this->corpId = $row['corpid'];
        } else {
            $this->secret = 'XWQDb1fq_z8wwCVyVRbFJi3_HqibxQtjRDQoR4bX8c4';
            $this->corpId = 'ww36092db762bf3430';
        }
    }

    public function getSignPackage($url = '')
    {
        $jsapiTicket = $this->getJsApiTicket();
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId"     => $this->corpId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    private function createNonceStr($length = 16)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    private function getJsApiTicket()
    {
        $query = new \yii\db\Query();
        $row = $query->select('*')->from('weixin_access_qy_js_token')->where(['and', ['=', 'appid', $this->appId], ['=', 'secret', $this->secret]])->one();
        if (!$row || $row['expires'] < time()) {
            $token = $this->getAccessToken();
            $url = 'https://qyapi.weixin.qq.com/cgi-bin/get_jsapi_ticket?access_token=' . $token;
            $curl = new curl\Curl();
            $response = $curl->get($url);
            if ($curl->errorCode === null) {
                $result = json_decode($response, true);
                $ticket = $result['ticket'];
                if ($ticket) {
                    $expires = time() + 7000;
                    if ($row) {
                        Yii::$app->db->createCommand()->update('weixin_access_qy_js_token', ['token' => $ticket, 'expires' => $expires], "appid=:appid and secret=:secret", [":appid" => $this->appId, ":secret" => $this->secret])->execute();
                    } else {
                        $insertData = array(
                            'appid' => $this->appId,
                            'secret' => $this->secret,
                            'token' => $ticket,
                            'expires' => $expires,
                        );
                        Yii::$app->db->createCommand()->insert('weixin_access_qy_js_token', $insertData)->execute();
                    }
                }
            }
        } else {
            $ticket = $row['token'];
        }
        return $ticket;
    }

    private function getAccessToken()
    {
        $qyhapi = new QYWeiXinAPI($this->appId, '', '');
        $token = $qyhapi->getToken();
        return $token;
    }

    public function getAgentIdSignPackage($url = '')
    {
        $jsapiTicket = $this->getAgentIdJsApiTicket();
        $timestamp = time();
        $nonceStr = $this->createNonceStr();
        $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
        $signature = sha1($string);
        $signPackage = array(
            "appId"     => $this->corpId,
            "nonceStr"  => $nonceStr,
            "timestamp" => $timestamp,
            "url"       => $url,
            "signature" => $signature,
            "rawString" => $string
        );
        return $signPackage;
    }

    private function getAgentIdJsApiTicket()
    {
        $query = new \yii\db\Query();
        $row = $query->select('*')->from('weixin_access_qy_agent_js_token')->where(['and', ['=', 'appid', $this->appId], ['=', 'secret', $this->secret]])->one();
        if (!$row || $row['expires'] < time()) {
            $token = $this->getAccessToken();
            $url = 'https://qyapi.weixin.qq.com/cgi-bin/ticket/get?access_token=' . $token . '&type=agent_config';
            $curl = new curl\Curl();
            $response = $curl->get($url);
            if ($curl->errorCode === null) {
                $result = json_decode($response, true);
                $ticket = $result['ticket'];
                if ($ticket) {
                    $expires = time() + 7000;
                    if ($row) {
                        Yii::$app->db->createCommand()->update('weixin_access_qy_agent_js_token', ['token' => $ticket, 'expires' => $expires], "appid=:appid and secret=:secret", [":appid" => $this->appId, ":secret" => $this->secret])->execute();
                    } else {
                        $insertData = array(
                            'appid' => $this->appId,
                            'secret' => $this->secret,
                            'token' => $ticket,
                            'expires' => $expires,
                        );
                        Yii::$app->db->createCommand()->insert('weixin_access_qy_agent_js_token', $insertData)->execute();
                    }
                }
            }
        } else {
            $ticket = $row['token'];
        }
        return $ticket;
    }
}
