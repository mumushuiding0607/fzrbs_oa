<?php

namespace app\modules\api\commons;

/**
 * Aes 加解密类
 */
class Aes
{
    protected static $aesKey = 'PT3ZOOSWtolC7fMJ';
    protected static $aesIV = 'r3uvSv17RfsPwd3J';
    protected static $clientId = 'rocr8kSx7SZLHfokGTphGuB1bfGIGvOj';

    /**
     * Aes解密
     * @param string $data Aes加密信息
     * @return string 解密后信息
     */
    public static function decrypt($data)
    {
        $str = openssl_decrypt(@hex2bin($data), 'AES-128-CBC', self::$aesKey, OPENSSL_RAW_DATA, self::$aesIV);
        return $str;
    }

    /**
     * 参数Aes加密和签名
     * @param array $params 参数
     * @return array 加密后参数和签名
     */
    public static function encryptParams($params = [])
    {
        $params['client_id'] = self::$clientId;
        $paramsStr = json_encode($params);
        $content = strtoupper(bin2hex(openssl_encrypt($paramsStr, 'AES-128-CBC', self::$aesKey, OPENSSL_RAW_DATA, self::$aesIV)));
        $sign = self::signature($params);
        return array('content' => $content, 'sign' => $sign);
    }

    /**
     * 生成参数签名
     * @param array $params 参数
     * @return string 签名
     */
    public static function signature($params)
    {
        if (is_array($params) && $params) {
            $tempArr = [];
            ksort($params, SORT_STRING);
            foreach ($params as $k => $v) {
                if (is_array($v)) {
                    $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }
                $tempArr[] = $k . '=' . $v;
            }
            $paramsStr = implode('&', $tempArr);
            $str = hash("sha256", $paramsStr);
            return $str;
        } else {
            return false;
        }
    }

    /**
     * 参数签名校验
     * @param array $params 参数
     * @param string $sign 签名
     * @return bool 签名是否通过
     */
    public static function checkSignature($params, $sign)
    {
        $str = self::signature($params);
        return $str === $sign;
    }
}
