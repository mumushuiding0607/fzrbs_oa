<?php

namespace app\modules\api\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\Json;
use app\modules\api\models\FzrbsLoginFail;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\models\FzrbsQyhyyCode;
use app\modules\api\models\FzrbsRole;
use app\modules\api\commons\Tools;
use app\modules\api\commons\Aes;
use app\modules\api\commons\JwtToken;
use app\modules\api\commons\WxQyhJk;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use Da\QrCode\QrCode;
use Exception;

/**
 * 用户账号登录和验证码类
 */
class LoginController extends Controller
{
    public $enableCsrfValidation = false;
    protected $_request = null;
    protected $_userIp = null;
    // 非正式在职员工部门id(其他：79，退休离职员工：50，下属其他：35，实习：77，物业：51)
    protected $_departmentId = [35, 50, 51, 77, 79];

    public function init()
    {
        parent::init();
        Tools::waf();
        $this->_userIp = Tools::getClientIp();
        $this->_request = Json::decode(Yii::$app->request->getRawBody(), true);
    }

    /**
     * 设置验证码独立动作
     */
    public function actions()
    {
        $fontFile = Yii::$app->basePath . '/assets/91545.ttf';
        return [
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'maxLength' => 4,
                'minLength' => 4,
                'padding' => 5,
                'height' => 40,
                'width' => 70,
                'offset' => 3,
                'fontFile' => $fontFile
            ],
        ];
    }

    /**
     * 用户登录验证动作
     */
    public function actionLogin()
    {
        $result = ['status' => 'error', 'type' => 'account', 'currentAuthority' => 'guest'];
        if ($this->_request) {
            $type = $this->_request['type'];
            $userName = $this->_request['username'];
            $password = $this->_request['password'];
            $imageCaptcha = $this->_request['imagecaptcha'];
            $mobile = $this->_request['phone'];
            $captcha = $this->_request['captcha'];
            $autoLogin = $this->_request['autologin'];
            $lockSecond = 900;
            $userName = Aes::decrypt($userName);
                $password = Aes::decrypt($password);
                $adminModel = Tools::getUserInfo(['username' => $userName]);
                if ($adminModel) {
                    $sm3Password = SM3(md5(md5($password) . $adminModel->salt));
                    $success = $sm3Password === $adminModel->password;
                    if ($success) {
                        $this->_loginSuccess($adminModel, '账号密码登录');
                        $result = $this->_getLoginSuccessResult($adminModel);
                    } else {
                        $result['msg'] = '用户名或密码错误！';
                        // $this->_LoginFail($model);
                    }
                } else {
                    $result['msg'] = '用户名或密码错误！';
                    // $this->_LoginFail($model);
                }
            // if (in_array($type, ['account', 'mobile'])) {
            //     $result['type'] = $type;
            //     // 删除时间超过 $lockSecond 的记录
            //     FzrbsLoginFail::deleteAll(['<', 'lastupdate', time() - $lockSecond]);
            //     $model = FzrbsLoginFail::findOne($this->_userIp);
            //     if ($model && $model->count >= 5 && time() < $model->lastupdate + $lockSecond) {
            //         $result['msg'] = '登录失败次数超过5次，请15分钟后再登录';
            //         Tools::responseJson($result);
            //     }
            //     // $success = $this->createAction('captcha')->validate($imageCaptcha, false);
            //     $userName = Aes::decrypt($userName);
            //     $password = Aes::decrypt($password);
            //     $adminModel = Tools::getUserInfo(['username' => $userName]);
            //     if ($adminModel) {
            //         $sm3Password = SM3(md5(md5($password) . $adminModel->salt));
            //         $success = $sm3Password === $adminModel->password;
            //         if ($success) {
            //             $this->_loginSuccess($adminModel, '账号密码登录');
            //             $result = $this->_getLoginSuccessResult($adminModel);
            //         } else {
            //             $result['msg'] = '用户名或密码错误！';
            //             $this->_LoginFail($model);
            //         }
            //     } else {
            //         $result['msg'] = '用户名或密码错误！';
            //         $this->_LoginFail($model);
            //     }
            //     // if ($type == 'account' && $userName && $password && $imageCaptcha) {
            //     //     $success = $this->createAction('captcha')->validate($imageCaptcha, false);
            //     //     $userName = Aes::decrypt($userName);
            //     //     $password = Aes::decrypt($password);
            //     //     $adminModel = Tools::getUserInfo(['username' => $userName]);
            //     //     if ($adminModel) {
            //     //         $sm3Password = SM3(md5(md5($password) . $adminModel->salt));
            //     //         $success = $sm3Password === $adminModel->password;
            //     //         if ($success) {
            //     //             $this->_loginSuccess($adminModel, '账号密码登录');
            //     //             $result = $this->_getLoginSuccessResult($adminModel);
            //     //         } else {
            //     //             $result['msg'] = '用户名或密码错误！';
            //     //             $this->_LoginFail($model);
            //     //         }
            //     //     } else {
            //     //         $result['msg'] = '用户名或密码错误！';
            //     //         $this->_LoginFail($model);
            //     //     }
            //     //     // if ($success === false) {
            //     //     //     $result['msg'] = '图片验证码错误！';
            //     //     //     $this->_LoginFail($model);
            //     //     // } else {
            //     //     //     $userName = Aes::decrypt($userName);
            //     //     //     $password = Aes::decrypt($password);
            //     //     //     $adminModel = Tools::getUserInfo(['username' => $userName]);
            //     //     //     if ($adminModel) {
            //     //     //         $sm3Password = SM3(md5(md5($password) . $adminModel->salt));
            //     //     //         $success = $sm3Password === $adminModel->password;
            //     //     //         if ($success) {
            //     //     //             $this->_loginSuccess($adminModel, '账号密码登录');
            //     //     //             $result = $this->_getLoginSuccessResult($adminModel);
            //     //     //         } else {
            //     //     //             $result['msg'] = '用户名或密码错误！';
            //     //     //             $this->_LoginFail($model);
            //     //     //         }
            //     //     //     } else {
            //     //     //         $result['msg'] = '用户名或密码错误！';
            //     //     //         $this->_LoginFail($model);
            //     //     //     }
            //     //     // }
            //     // } else if ($type == 'mobile' && $mobile && $captcha) {
            //     //     $codeModel = FzrbsQyhyyCode::find()->where(['mobile' => $mobile, 'code' => $captcha])->one();
            //     //     if (!$codeModel) {
            //     //         $result['msg'] = '动态验证码错误！';
            //     //         $this->_LoginFail($model);
            //     //     } else if (time() > $codeModel->sendtime + 300) {
            //     //         $result['msg'] = '动态验证码超时，请重新获取！';
            //     //         $this->_LoginFail($model);
            //     //     } else {
            //     //         $adminModel = Tools::getUserInfo(['mobile' => $mobile], false, $type);
            //     //         if ($adminModel) {
            //     //             if ($adminModel->islock == 0) {
            //     //                 $this->_loginSuccess($adminModel, '手机号动态码登录');
            //     //                 $result = $this->_getLoginSuccessResult($adminModel);
            //     //             } else {
            //     //                 $result['msg'] = '登录信息错误！';
            //     //                 $this->_LoginFail($model);
            //     //             }
            //     //         } else {
            //     //             // 手机号没在用户表，自动添加手机号用户
            //     //             $model = WeixinOAUserInfo::find()->where(['mobile' => $mobile])->one();
            //     //             if ($model) {
            //     //                 $generator = new ComputerPasswordGenerator();
            //     //                 $generator
            //     //                     ->setOptionValue(ComputerPasswordGenerator::OPTION_UPPER_CASE, true)
            //     //                     ->setOptionValue(ComputerPasswordGenerator::OPTION_LOWER_CASE, true)
            //     //                     ->setOptionValue(ComputerPasswordGenerator::OPTION_NUMBERS, true)
            //     //                     ->setOptionValue(ComputerPasswordGenerator::OPTION_SYMBOLS, true);
            //     //                 $password = $generator->generatePassword();
            //     //                 $data = [
            //     //                     'username' => $mobile,
            //     //                     'realname' => $model->name,
            //     //                     'mobile' => $mobile,
            //     //                     'department' => $model->departmentname,
            //     //                     'avatar' => $model->avatar,
            //     //                     'usertype' => 0,
            //     //                     'wxuserid' => $model->userid,
            //     //                     'password' => $password,
            //     //                     'classify' => 1,
            //     //                 ];
            //     //                 $adminModel = Tools::addUser($data);
            //     //                 // 为用户分配默认的角色
            //     //                 // 正式在职员工默认角色id
            //     //                 $defaultRoleId = [1];
            //     //                 if (in_array($model->departmentid, $this->_departmentId)) {
            //     //                     // 在$this->_departmentId的非正式在职员工默认角色id
            //     //                     $defaultRoleId = [2];
            //     //                 }
            //     //                 $roleTable = FzrbsRole::tableName();
            //     //                 $usernames = new \yii\db\Expression("TRIM(BOTH ',' FROM REPLACE(CONCAT(',',usernames,','),'," . $mobile . ",',','))");
            //     //                 Yii::$app->db->createCommand()->update($roleTable, ['usernames' => $usernames], ['in', "id", $defaultRoleId])->execute();
            //     //                 $usernames = new \yii\db\Expression("TRIM(BOTH ',' FROM CONCAT(usernames,'," . $mobile . "'))");
            //     //                 Yii::$app->db->createCommand()->update($roleTable, ['usernames' => $usernames], ['in', "id", $defaultRoleId])->execute();
            //     //                 $this->_loginSuccess($adminModel, '手机号动态码登录');
            //     //                 // 将随机生成的密码发送给用户
            //     //                 $appId = '1000014';
            //     //                 $content = '系统自动给您分配的登录密码是：' . $password;
            //     //                 WxQyhJk::sendMessage($appId, $model->userid, $content);
            //     //                 $result = $this->_getLoginSuccessResult($adminModel);
            //     //             } else {
            //     //                 $result['msg'] = '手机号错误！';
            //     //                 $this->_LoginFail($model);
            //     //             }
            //     //         }
            //     //     }
            //     // }
            // }
        }
        Tools::responseJson($result);
    }

    /**
     * 企业号动态验证码动作
     */
    public function actionDynamicCode()
    {
        $result = ['status' => 'error', 'msg' => '参数错误'];
        if ($this->_request) {
            $mobile = $this->_request['phone'];
            if ($mobile) {
                $model = WeixinOAUserInfo::find()->where(['mobile' => $mobile])->one();
                if ($model) {
                    // 非正式在职员工限制登录
                    if (in_array($model->departmentid, $this->_departmentId)) {
                        $result['msg'] = '没有权限登录！';
                        Tools::responseJson($result);
                    }
                    $appId = '1000054';
                    $code = rand(100000, 999999);
                    $content = '您的登录动态验证码是：' . $code;
                    $sendResult =  WxQyhJk::sendMessage($appId, $model->userid, $content);
                    if (!$sendResult['errorMessage']) {
                        $data = ['mobile' => $mobile, 'code' => $code, 'sendtime' => time(), 'appid' => $appId];
                        $model = FzrbsQyhyyCode::find()->where(['mobile' => $mobile])->one();
                        if ($model) {
                            $model->attributes = $data;
                        } else {
                            $model = new FzrbsQyhyyCode;
                            $model->attributes = $data;
                        }
                        $model->save();
                        $result['status'] = 'ok';
                        $result['msg'] = '';
                    } else {
                        $result['msg'] = $sendResult['errorMessage'];
                    }
                } else {
                    $result['msg'] = '手机号错误！';
                }
            }
        }
        Tools::responseJson($result);
    }

    /**
     * 登录二维码动作
     */
    public function actionQrcode()
    {
        $channel = $this->_request['channel'];
        $host = $this->_request['host'] ? $this->_request['host'] : '218.5.3.213';
        $port = $this->_request['port'] ? $this->_request['port'] : '8030';
        $useSSL = $this->_request['useSSL'] ? $this->_request['useSSL'] : 0;
        if (!$useSSL && !$port) {
            $port = '80';
        }
        if ($channel) {
            $time = time();
            $backUrl = urlencode('https://api.fznews.com.cn/weixin/oa-user-bind/login?channel=' . $channel . '&time=' . $time . '&website=fzrbsoa&host=' . $host . '&port=' . $port . '&useSSL=' . $useSSL);
            $qrcodeUrl = "https://rtq7g0daat.fznews.com.cn/index.php?r=cms/jump/oauth&time={$time}&backurl=" . $backUrl;
            $qrCode = (new QrCode($qrcodeUrl))->setSize(200)->setMargin(5);
            $result['data'] = $qrCode->writeDataUri();
            Tools::responseJson($result);
        }
        exit;
    }

    /**
     * 二维码登录动作
     */
    public function actionQrcodeLogin()
    {
        $userName = $this->_request['username'];
        $checkMd5 = $this->_request['checkmd5'];
        $type = $this->_request['type'];
        $result = ['status' => 'error'];
        if ($userName && $checkMd5 && $type) {
            $lockSecond = 900;
            FzrbsLoginFail::deleteAll(['<', 'lastupdate', time() - $lockSecond]);
            $model = FzrbsLoginFail::findOne($this->_userIp);
            $userName = Aes::decrypt($userName);
            $adminModel = Tools::getUserInfo(['username' => $userName]);
            if ($adminModel) {
                if ($type == 'wx') {
                    $query = new \yii\db\Query();
                    $row = $query->select('*')->from('fzrbs_wx_bind_login')->where(['and', ['=', 'userid', $adminModel->id], ['=', 'type', 2], ['=', 'checkmd5', $checkMd5]])->one();
                    if (!$row) {
                        $this->_LoginFail($model);
                        $result['msg'] = '登录失败';
                        Tools::responseJson($result);
                    }
                    if (time() - strtotime($row['updatetime']) > 600) {
                        $this->_LoginFail($model);
                        $result['msg'] = '登录超时';
                        Tools::responseJson($result);
                    }
                    $this->_loginSuccess($adminModel, '微信二维码扫描登录');
                    $result = $this->_getLoginSuccessResult($adminModel);
                    Tools::responseJson($result);
                } else if ($type == 'qywx') {
                    $query = new \yii\db\Query();
                    $row = $query->select('*')->from('fzrbs_qywx_login_check')->where(['and', ['=', 'userid', $adminModel->id], ['=', 'checkmd5', $checkMd5]])->one();
                    if (!$row) {
                        $this->_LoginFail($model);
                        $result['msg'] = '登录失败';
                        Tools::responseJson($result);
                    }
                    if (time() - strtotime($row['updatetime']) > 600) {
                        $this->_LoginFail($model);
                        $result['msg'] = '登录超时';
                        Tools::responseJson($result);
                    }
                    $this->_loginSuccess($adminModel, '企业微信二维码扫描登录');
                    $result = $this->_getLoginSuccessResult($adminModel);
                    Tools::responseJson($result);
                }
            } else {
                $this->_LoginFail($model);
                $result['msg'] = '登录失败';
                Tools::responseJson($result);
            }
        }
        Tools::responseJson($result);
        exit;
    }

    /**
     * 获取登录成功返回信息
     * @param model $adminModel 用户表model
     * @return array 登录成功信息
     */
    protected function _getLoginSuccessResult($adminModel)
    {
        $result['msg'] = '';
        $result['status'] = 'ok';
        $result['currentAuthority'] = 'admin';
        $result['token'] = JwtToken::createJwtToken($adminModel);
        return $result;
    }

    /**
     * 登录成功记录
     * @param model $adminModel 用户表model
     * @param string $loginType 登方式
     */
    protected function _loginSuccess($adminModel, $loginType)
    {
        $loginTime = date('Y-m-d H:i:s');
        // 更新用户最后登录信息
        $adminModel->lastloginip = $this->_userIp;
        $adminModel->lastlogintime = $loginTime;
        $adminModel->loginnum = $adminModel->loginnum + 1;
        $adminModel->save();
        // 记录用户成功登录日志
        $logInfo = [
            'username' => $adminModel->username,
            'realname' => $adminModel->realname,
            'ip' => $this->_userIp,
            'logintype' => $loginType,
            'logtype' => '登录',
            'inserttime' => date('Y-m-d H:i:s'),
        ];
        Tools::loginAndOutLog($logInfo);
        // 删除登录失败记录
        FzrbsLoginFail::deleteAll("ip=:ip", [':ip' => $this->_userIp]);
        // 删除动态码
        FzrbsQyhyyCode::deleteAll("mobile=:mobile", [':mobile' => $adminModel->mobile]);
    }

    /**
     * 登录失败次数记录
     * @param $model 登录失败表model
     */
    protected function _loginFail($model)
    {
        if ($model == null) {
            $model = new FzrbsLoginFail;
        }
        if ($model) {
            $model->count = $model->count + 1;
        } else {
            $model->count = 1;
        }
        $model->ip = $this->_userIp;
        $model->lastupdate = time();
        $model->save();
    }
}
