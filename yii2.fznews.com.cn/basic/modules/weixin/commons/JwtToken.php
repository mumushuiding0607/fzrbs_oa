<?php

namespace app\modules\weixin\commons;

use Yii;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use app\modules\api\commons\Aes;
use app\modules\api\commons\Tools;

/**
 * jwt token生成验证类
 */
class JwtToken
{
    protected static $jwtKey = 'K8etdBKAW5bPPsXuJqLKsurZ5XPKgAsR';
    protected static $tokenExpireTime = 3600;

    /**
     * 生成jwt Token
     * @param model $adminModel 用户表model
     * @return string jwt Token信息
     */
    public static function createJwtToken($adminModel)
    {
        // 这里是自定义的一个随机字串，应该写在config文件中的，解密时也会用
        $loginSignature = self::setLoginSignature($adminModel->id);
        $key = self::$jwtKey;
        $now = time();
        $token = [
            "iss" => "fznews",  // 签发者 可以为空
            "aud" => $adminModel->username, // 面象的用户
            "iat" => $now, // 签发时间
            "nbf" => $now, // 生效时间
            "exp" => $now + self::$tokenExpireTime, // 过期时间
            "data" => ['id' => $adminModel->id, 'username' => $adminModel->username, 'realname' => $adminModel->realname, 'mobile' => $adminModel->mobile]
        ];
        $jwt = JWT::encode($token, $key, "HS256"); // 根据参数生成了 token
        $otherInfo = Aes::encryptParams(['username' => $adminModel->username, 'signature' => $loginSignature]);
        return 'bearer ' . $jwt . ' ' . $otherInfo['content'] . ' ' . $otherInfo['sign'];
    }

    /**
     * 校验jwt Token
     * @return array 用户信息
     */
    public static function checkJwtToken($authorization = '')
    {
        if (!$authorization) {
            return false;
        }
        try {
            $redis = Yii::$app->redis;
            $jwt = $authorization;
            // $jwt = substr($jwt, 7);
            $jwtInfo = explode(' ', $jwt);
            $jwt = $jwtInfo[1];
            $otherInfo = $jwtInfo[2];
            $sign = $jwtInfo[3];
            if ($otherInfo && $sign) {
                $otherInfo = json_decode(Aes::decrypt($otherInfo), true);
                if (!Aes::checkSignature($otherInfo, $sign)) {
                    $result = ['success' => true, 'data' => ['isLogin' => false], 'errorCode' => '401', 'errorMessage' => '认证信息校验失败，请重新登录！'];
                    Tools::responseJson($result);
                }
                $userName = $otherInfo['username'];
                $signature = $otherInfo['signature'];
                // 用户登录签名保存用户接口请求时间，在jwt token超时后给用户设置新的jwt token
                if ($signature) {
                    $loginSignKey = 'fzrbs_loginsign_' . $signature;
                    $loginSignExpireTime = self::$tokenExpireTime * 2 * 24 * 7;
                    if ($redis->exists($loginSignKey)) {
                        // 两次接口请求是否超过超时时间
                        $tokenExpire =  time() - intval($redis->get($loginSignKey)) > self::$tokenExpireTime;
                        if ($tokenExpire) {
                            Tools::loginTimeOut($userName);
                        } else {
                            $redis->setex($loginSignKey, $loginSignExpireTime, time());
                        }
                    }
                }
            } else {
                return false;
            }
            $key = self::$jwtKey;
            $token = JWT::decode($jwt, new Key($key, 'HS256'));
            if (!$redis->exists($loginSignKey)) {
                $redis->setex($loginSignKey, $loginSignExpireTime, time());
            }
            if (!$token || time() > $token->exp) {
                if ($tokenExpire) {
                    return false;
                } else {
                    return self::refreshToken($userName);
                }
            }
        } catch (ExpiredException $e) {
            if ($tokenExpire) {
                return false;
            } else {
                return self::refreshToken($userName);
            }
        }
        $adminModel = Tools::checkUserStatus($token->data->username);
        return $adminModel->attributes;
    }

    /**
     * 刷新 jwt token
     * @param string $userName 用户名
     * @return array 包含新token的用户信息
     */
    public static function refreshToken($userName)
    {
        // 设置新jwt token
        if ($userName) {
            $adminModel = Tools::getUserInfo(['username' => $userName]);
            if ($adminModel) {
                $token = self::createJwtToken($adminModel);
            }
            $data = $adminModel->attributes;
            $data['token'] = $token;
            return $data;
        }
        Tools::loginTimeOut($userName);
    }

    /**
     * 设置用户登录签名
     * @param int $userId 用户id
     * @return string 登录签名
     */
    public static function setLoginSignature($userId)
    {
        return time() . '_' . $userId;
    }
}
