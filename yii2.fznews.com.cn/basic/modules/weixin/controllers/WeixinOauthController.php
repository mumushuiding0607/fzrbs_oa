<?php

namespace app\modules\weixin\controllers;

use Yii;
use yii\web\Controller;
use app\modules\weixin\commons\QYWeiXinAPI;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Formatter;

/**
 * 微信h5 web oauth
 */
class WeixinOauthController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionIndex()
    {
        $this->layout = false;
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $queryInfo = $_SERVER['QUERY_STRING'];
        $code = strip_tags(Yii::$app->request->get('code'));
        if (!$code) {
            if ($queryInfo) {
                parse_str($queryInfo, $queryInfoArr);
                if (isset($queryInfoArr['backurl']) && strpos($queryInfoArr['backurl'], '?') === false) {
                    $queryInfoArr['backurl'] = $queryInfoArr['backurl'] . '?currenttime=' . time();
                    $queryInfo = urldecode(http_build_query($queryInfoArr));
                }
            }
            $backUrl = 'https://fzrb.fznews.com.cn/weixin/weixin-oauth/index' . ($queryInfo ? "?$queryInfo" : '');
            $REDIRECT_URI = urlencode($backUrl);
            $SCOPE = 'snsapi_base';
            if (stristr($user_agent, 'wxwork')) {
                $agentid = 1000002;
                header("Location:https://open.weixin.qq.com/connect/oauth2/authorize?appid=ww36092db762bf3430&redirect_uri=$REDIRECT_URI&response_type=code&scope=$SCOPE&agentid=$agentid&state=STATE#wechat_redirect");
            } else if (stristr($user_agent, 'micromessenger')) {
                header("Location:https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx2177c1498f36b768&redirect_uri=$REDIRECT_URI&response_type=code&scope=$SCOPE&state=STATE#wechat_redirect");
            }
        } else {
            if ($queryInfo) {
                parse_str($queryInfo, $queryInfoArr);
                if (isset($queryInfoArr['backurl'])) {
                    $backUrl = str_replace('backurl=', '', $queryInfo);
                    $urlPath = substr($backUrl, 0, stripos($backUrl, '?'));
                    $urlInfo = parse_url($backUrl);
                    if ($urlInfo['query']) {
                        parse_str($urlInfo['query'], $queryInfoArr);
                        unset($queryInfoArr['code']);
                        unset($queryInfoArr['state']);
                        $urlInfo['query'] = http_build_query($queryInfoArr);
                        $backUrl = $urlPath . '?' . $urlInfo['query'];
                    }
                }
            }
            if (stristr($user_agent, 'wxwork')) {
                $qyhapi = new QYWeiXinAPI('1000002');
                $token = $qyhapi->getToken();
                $url = "https://qyapi.weixin.qq.com/cgi-bin/user/getuserinfo?access_token=$token&code=$code";
                $res = json_decode($this->httpGet($url));
                if ($res->UserId) {
                    $data = $qyhapi->convert2OpenId($res->UserId);
                    if ($data['openid']) {
                        $backUrl = stristr($backUrl, '?') === false ? $backUrl . '?openid=' . $data['openid'] : $backUrl . '&openid=' . $data['openid'];
                    }
                    header("Location:$backUrl");
                }
            } else if (stristr($user_agent, 'micromessenger')) {
                $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=wx2177c1498f36b768&secret=04c179f6a1fadd10ef4e903ea48f58eb&code=$code&grant_type=authorization_code";
                $res = json_decode($this->httpGet($url));
                if ($res->openid && $res->access_token) {
                    $backUrl = stristr($backUrl, '?') === false ? $backUrl . '?openid=' . $res->openid : $backUrl . '&openid=' . $res->openid;
                }
                header("Location:$backUrl");
            }
        }
        exit;
    }

    public function actionWeixinNotifyCallback()
    {
        $inWechatpaySignature = $_SERVER['HTTP_WECHATPAY_SIGNATURE'];
        $inWechatpayTimestamp = $_SERVER['HTTP_WECHATPAY_TIMESTAMP'];
        $inWechatpaySerial = $_SERVER['HTTP_WECHATPAY_SERIAL'];
        $inWechatpayNonce = $_SERVER['HTTP_WECHATPAY_NONCE'];
        $inBody = file_get_contents('php://input');
        $apiv3Key = 'bRkNvKUXSVfkdFhCdGgpwiy4pt7bfb8p';
        // 根据通知的平台证书序列号，查询本地平台证书文件
        $platformPublicKeyInstance = Rsa::from('file:///www/web/fzrbs_oa/cert/wxpay/wechatpay_1D5A463880797F2D86A9A9BDEE9695E48109B129.pem', Rsa::KEY_TYPE_PUBLIC);

        // 检查通知时间偏移量，允许5分钟之内的偏移
        $timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
        $verifiedStatus = Rsa::verify(
            // 构造验签名串
            Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, $inBody),
            $inWechatpaySignature,
            $platformPublicKeyInstance
        );
        if ($timeOffsetStatus && $verifiedStatus) {
            // 转换通知的JSON文本消息为PHP Array数组
            $inBodyArray = (array)json_decode($inBody, true);
            // 使用PHP7的数据解构语法，从Array中解构并赋值变量
            ['resource' => [
                'ciphertext'      => $ciphertext,
                'nonce'           => $nonce,
                'associated_data' => $aad
            ]] = $inBodyArray;
            // 加密文本消息解密
            $inBodyResource = AesGcm::decrypt($ciphertext, $apiv3Key, $nonce, $aad);
            // 把解密后的文本转换为PHP Array数组
            $inBodyResourceArray = (array)json_decode($inBodyResource, true);
            if ($inBodyResourceArray['trade_state'] == 'SUCCESS' && $inBodyResourceArray['transaction_id']) {
                $id = $inBodyResourceArray['attach'];
                $row = (new \yii\db\Query())->select('*')->from('weixin_payorder')->where(['and', ['=', 'id', $id]])->one();
                if ($row && !$row['transactionid']) {
                    Yii::$app->db->createCommand()->update('weixin_payorder', [
                        'status' => 2,
                        'timeend' => str_replace(['T', '+08:00'], [' ', ''], $inBodyResourceArray['success_time']),
                        'transactionid' => $inBodyResourceArray['transaction_id'],
                    ], 'id=:id', [':id' => $row['id']])->execute();
                    Yii::$app->db->createCommand()->update('weixin_staff', [
                        'weixinbalance' => new \yii\db\Expression("weixinbalance+" . $row['recharge1']),
                    ], 'userid=:userid', [':userid' => $row['userid']])->execute();
                    $money = number_format($row['recharge'] / 100, 2, '.', '');
                    $money1 = number_format($row['recharge1'] / 100, 2, '.', '');
                    $money2 = number_format($row['recharge2'] / 100, 2, '.', '');
                    $intro = '微信支付充值：' . $money . '，实际到账：' . $money1 . '，费率：' . $money2;
                    $insertData = array(
                        'uid' => 0,
                        'uname' => $row['userid'],
                        'urealname' => $row['username'],
                        'targetuname' => $row['userid'],
                        'targetrealname' => $row['username'],
                        'departmentname' => $row['departmentname'],
                        'intro' => $intro,
                        'inserttime' => time(),
                        'rechargemoney' => $row['recharge1'],
                        'rechargeall' => $row['recharge'],
                        'rechargeusers' => $row['userid'],
                        'weixinpay' => 1,
                    );
                    Yii::$app->db->createCommand()->insert('weixin_rechargelog', $insertData)->execute();
                }
            }
        }
        exit;
    }


    protected function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }
}
