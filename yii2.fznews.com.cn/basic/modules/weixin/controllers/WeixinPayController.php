<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;
use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;
use WeChatPay\Formatter;
use app\modules\weixin\commons\QYWeiXinAPI;

/**
 * 微信支付相关接口类
 */
class WeixinPayController extends ApiBase
{
    public $enableCsrfValidation = false;
    // 
    protected $apiV3key = 'bRkNvKUXSVfkdFhCdGgpwiy4pt7bfb8p';
    // 应用ID
    protected $appId = 'wx2177c1498f36b768';
    // 商户号
    protected $merchantId = '1602459847';
    // 证书设置
    protected $merchantPrivateKeyFilePath = 'file:///www/web/fzrbs_oa/cert/wxpay/apiclient_key.pem';
    protected $platformCertificateFilePath = 'file:///www/web/fzrbs_oa/cert/wxpay/wechatpay_1D5A463880797F2D86A9A9BDEE9695E48109B129.pem';
    // 证书序列号
    protected $merchantCertificateSerial = '392B5BACB8E9A66AD97F2E264EF5A73849E33E9E';
    protected $merchantPrivateKeyInstance;
    protected $platformPublicKeyInstance;
    protected $platformCertificateSerial;
    protected $instance;
    protected $curTime;

    public function init()
    {
        parent::init();
        $this->curTime = time();
        $user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if (stristr($user_agent, 'wxwork')) {
            $this->appId = 'ww36092db762bf3430';
        }
    }

    /**
     * 食堂充值
     */
    public function actionShitangRecharge()
    {
        $openid = $this->_request['openid'];
        if ($openid) {
            $money = $this->_request['money'] * 100;
            $money1 = $this->_request['money1'] * 100;
            $money2 = $this->_request['money2'] * 100;
            $this->_setInstance();
            try {
                $insertData = array(
                    'userid' => $this->_UserId,
                    'username' => $this->_userInfo['name'],
                    'mobile' => $this->_userInfo['mobile'],
                    'avatar' => $this->_userInfo['avatar'],
                    'departmentid' => $this->_userInfo['departmentid'],
                    'departmentname' => $this->_userInfo['departmentname'],
                    'gender' => $this->_userInfo['gender'],
                    'recharge' => $money,
                    'recharge1' => $money1,
                    'recharge2' => $money2,
                );
                Yii::$app->db->createCommand()->insert('weixin_payorder', $insertData)->execute();
                $orderId = Yii::$app->db->getLastInsertID();
                $total = $money;
                // 商户系统内部订单号，只能是数字、大小写字母_-*且在同一个商户号下唯一
                $out_trade_no = 'st' . date("YmdHis") . $orderId;
                // 商品描述
                $description = '食堂账号微信支付充值';
                // 支付回调通知URL，该地址必须为直接可访问的URL，不允许携带查询串
                $notify_url = 'https://fzrb.fznews.com.cn/weixin/weixin-oauth/weixin-notify-callback';
                // 附加数据
                $attach = strval($orderId);
                //  订单金额信息
                $amount = [
                    'total' => $total,
                ];
                // 支付者信息
                $payer = [
                    'openid' => $openid,
                ];
                $postData = [
                    'appid' => $this->appId,
                    'mchid' => $this->merchantId,
                    'description' => $description,
                    'out_trade_no' => $out_trade_no,
                    'notify_url' => $notify_url,
                    'amount' => $amount,
                    'payer' => $payer,
                    'attach' => $attach,
                ];
                $resp = $this->instance
                    ->chain('v3/pay/transactions/jsapi')
                    ->post(['json' => $postData]);

                // echo $resp->getStatusCode(), PHP_EOL;
                // echo $resp->getBody(), PHP_EOL;
                $code = $resp->getStatusCode();
                $body = json_decode($resp->getBody(), true);
                if ($code == 200 && isset($body['prepay_id'])) {
                    Yii::$app->db->createCommand()->update('weixin_payorder', [
                        'orderid' => $out_trade_no,
                    ], 'id=:id', [':id' => $orderId])->execute();
                    Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'prepay_id' => $body['prepay_id'], 'order_id' => $orderId]);
                } else {
                    Yii::$app->db->createCommand()->delete('weixin_payorder', ['=', 'id', $orderId])->execute();
                    Tools::responseJson(['success' => true, 'errorMessage' => '预支付信息获取失败', 'errorCode' => 1000]);
                }
            } catch (\Exception $e) {
                // 进行错误处理
                // echo $e->getMessage(), PHP_EOL;
                $errorMessage = $e->getMessage();
                // if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                //     $r = $e->getResponse();
                //     echo $r->getStatusCode() . ' ' . $r->getReasonPhrase(), PHP_EOL;
                //     echo $r->getBody(), PHP_EOL, PHP_EOL, PHP_EOL;
                // }
                // echo $e->getTraceAsString(), PHP_EOL;
                Tools::responseJson(['success' => true, 'errorMessage' => $errorMessage, 'errorCode' => 1000]);
            }
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => 'openid获取失败', 'errorCode' => 1000]);
        }
    }

    /**
     * 食堂充值成功
     */
    public function actionShitangSuccess()
    {
        $orderId = $this->_request['orderId'];
        if ($orderId) {
            $row = (new \yii\db\Query())->select('*')->from('weixin_payorder')->where(['and', ['=', 'id', $orderId], ['=', 'userid', $this->_UserId]])->one();
            if ($row && !$row['status']) {
                Yii::$app->db->createCommand()->update('weixin_payorder', [
                    'status' => 1,
                ], 'id=:id', [':id' => $row['id']])->execute();
            }
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0]);
    }

    /**
     * 取消食堂充值
     */
    public function actionShitangCancel()
    {
        $orderId = $this->_request['orderId'];
        if ($orderId) {
            Yii::$app->db->createCommand()->delete('weixin_payorder', ['and', ['=', 'id', $orderId], ['=', 'userid', $this->_UserId]])->execute();
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0]);
    }

    /**
     * 签名
     */
    public function actionSign()
    {
        $prepay_id = $this->_request['prepay_id'];
        if ($prepay_id) {
            $merchantPrivateKeyInstance = Rsa::from($this->merchantPrivateKeyFilePath);
            $params = [
                'appId'     => $this->appId,
                'timeStamp' => (string)Formatter::timestamp(),
                'nonceStr'  => Formatter::nonce(),
                'package'   => 'prepay_id=' . $prepay_id,
            ];
            $params += ['paySign' => Rsa::sign(
                Formatter::joinedByLineFeed(...array_values($params)),
                $merchantPrivateKeyInstance
            ), 'signType' => 'RSA'];
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $params]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => '预支付信息错误', 'errorCode' => 1000]);
        }
    }

    protected function _setInstance()
    {
        $this->merchantPrivateKeyInstance = Rsa::from($this->merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);
        $this->platformPublicKeyInstance = Rsa::from($this->platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);
        $this->platformCertificateSerial = PemUtil::parseCertificateSerialNo($this->platformCertificateFilePath);
        $this->instance = Builder::factory([
            'mchid'      => $this->merchantId,
            'serial'     => $this->merchantCertificateSerial,
            'privateKey' => $this->merchantPrivateKeyInstance,
            'certs'      => [
                $this->platformCertificateSerial => $this->platformPublicKeyInstance,
            ],
        ]);
    }
}
