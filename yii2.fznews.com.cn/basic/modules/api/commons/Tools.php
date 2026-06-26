<?php

namespace app\modules\api\commons;

use Yii;
use app\modules\api\models\FzrbsAdmin;
use app\modules\api\models\FzrbsLoginLog;
use yii\helpers\ArrayHelper;
use app\modules\api\commons\Aes;
use linslin\yii2\curl;
use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;

/**
 * 工具函数类
 */
class Tools
{
    /**
     * 获取用户ip
     * @return string ip地址
     */
    public static function getClientIp()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else if (isset($_SERVER['HTTP_X_REAL_IP']) && $_SERVER['HTTP_X_REAL_IP']) {
            return $_SERVER['HTTP_X_REAL_IP'];
        } else if (isset($_SERVER['HTTP_REMOTE_HOST']) && $_SERVER['HTTP_REMOTE_HOST']) {
            return $_SERVER['HTTP_REMOTE_HOST'];
        }
        return Yii::$app->request->userIP;
    }

    /**
     * json格式数据返回
     * @param array $data 返回数据
     */
    public static function responseJson($data)
    {
        $response = Yii::$app->response;
        $response->format = \yii\web\Response::FORMAT_JSON;
        $response->data = $data;
        if (isset($data['errorCode']) && $data['errorCode'] == '401') {
            $response->setStatusCode(401);
        }
        $response->send();
        exit;
    }

    /**
     * 通过id、用户名、手机号获取用户信息
     * @param array $conditions 获取条件
     * @param bool $status 用户状态是否正常
     * @param string $type 登录类型
     * @return model 用户表model
     */
    public static function getUserInfo($conditions = [], $status = true, $type = 'account')
    {
        if (isset($conditions['username']) || isset($conditions['mobile'])) {
            $value = isset($conditions['username']) ? $conditions['username'] : $conditions['mobile'];
            if ($type == 'account') {
                $where = [
                    'or',
                    ['=', 'username', $value],
                    ['=', 'mobile', $value]
                ];
            } else {
                $where = ['=', 'username', $value];
            }
        } else if (isset($conditions['id'])) {
            $where = ['=', 'id', $conditions['id']];
        }
        if ($status) {
            $where = [
                'and',
                ['=', 'islock', 0],
                $where,
            ];
        }
        $model = FzrbsAdmin::find()->where($where)->one();
        return $model;
    }

    /**
     * 实时检查用户的状态
     * @param string $userName 用户名
     * @return model 用户表model
     */
    public static function checkUserStatus($userName)
    {
        $model = self::getUserInfo(['username' => $userName]);
        if (!$model) {
            $result = ['success' => true, 'data' => ['isLogin' => false], 'errorCode' => '401', 'errorMessage' => '登录账号已经不存在！'];
            self::responseJson($result);
        }
        return $model;
    }

    /**
     * 添加用户
     * @param array $data 用户信息
     * @return model 用户表model
     */
    public static function addUser($data)
    {
        if (!isset($data['password'])) {
            $generator = new ComputerPasswordGenerator();
            $generator
                ->setOptionValue(ComputerPasswordGenerator::OPTION_UPPER_CASE, true)
                ->setOptionValue(ComputerPasswordGenerator::OPTION_LOWER_CASE, true)
                ->setOptionValue(ComputerPasswordGenerator::OPTION_NUMBERS, true)
                ->setOptionValue(ComputerPasswordGenerator::OPTION_SYMBOLS, true);
            $password = $generator->generatePassword();
            $data['password'] = $password;
        }
        $data = self::setSM3Password($data);
        $adminModel = new FzrbsAdmin;
        $adminModel->attributes = $data;
        $adminModel->save();
        return $adminModel;
    }

    /**
     * 登录超时
     * @param string $userName 用户名
     */
    public static function loginTimeOut($userName)
    {
        if ($userName) {
            $model = self::getUserInfo(['username' => $userName]);
            if ($model) {
                $logInfo = [
                    'username' => $model->username,
                    'realname' => $model->realname,
                    'ip' => self::getClientIp(),
                    'logtype' => '退出',
                    'remark' => '登录超时退出',
                    'inserttime' => date('Y-m-d H:i:s'),
                ];
                self::loginAndOutLog($logInfo);
            }
        }
        $result = ['success' => true, 'data' => ['isLogin' => false], 'errorCode' => '401', 'errorMessage' => '登录超时，请重新登录！'];
        self::responseJson($result);
    }

    /**
     * 登录退出日志记录
     * @param array $logInfo 日志信息
     */
    public static function loginAndOutLog($logInfo)
    {
        $loginLogModel = new FzrbsLoginLog;
        $loginLogModel->attributes = $logInfo;
        $loginLogModel->save();
    }

    /**
     * 用户密码国密m3加密
     * @param array $data 用户信息
     * @return array 用户信息
     */
    public static function setSM3Password($data)
    {
        if (isset($data['password'])) {
            $data['salt'] = substr(uniqid(rand()), -6);
            $data['password'] = SM3(md5(md5(trim($data['password'])) . $data['salt']));
        }
        return $data;
    }

    /**
     * 验证model规则
     * @param model $model 数据model
     * @param int $errorCode 错误代码
     * @return bool|array 成功或错误信息
     */
    public static function modelRules($model, $errorCode)
    {
        if (!$model->validate()) {
            $errors = $model->getErrors();
            $errorsInfo = [];
            foreach ($errors as $error) {
                $errorsInfo[] = implode(',', $error);
            }
            return ['errorCode' => $errorCode, 'errorMessage' => implode('/', $errorsInfo)];
        } else {
            return true;
        }
    }

    /**
     * 错误规则
     * @param int $errorCode 错误代码
     * @param string $errorMessage 错误信息
     * @return array 错误信息
     */
    public static function wrongRules($errorCode, $errorMessage)
    {
        return ['success' => true, 'errorCode' => $errorCode, 'errorMessage' => $errorMessage];
    }

    /**
     * 删除文件
     * @param string $rootPath 服务器路径
     * @param string $fileParh 文件路径
     */
    public static function deleteFile($rootPath, $fileParh)
    {
        $localFile = $rootPath . str_replace('/uploaded/', '', $fileParh);
        @unlink($localFile);
    }

    /**
     * html字符编码
     * @param array $data 要编码数据
     * @return array 编码后数据
     */
    public static function htmlDecode($data)
    {
        return ArrayHelper::htmlDecode($data);
    }

    /**
     * Excel数据读取
     * @param string $filePath excel文件
     * @return array 数据
     */
    public static function getExcelData($filePath)
    {
        $data = [];
        $fileType = \PHPExcel_IOFactory::identify($filePath);
        $excelReader = \PHPExcel_IOFactory::createReader($fileType);
        $phpexcel = $excelReader->load($filePath)->getSheet(0);
        $totalRow = $phpexcel->getHighestRow();
        $totalColumn = $phpexcel->getHighestColumn();
        if (1 < $totalRow) {
            for ($row = 1; $row <= $totalRow; $row++) {
                $data[$row] = [];
                for ($column = 'A'; $column <= $totalColumn; $column++) {
                    $value = trim($phpexcel->getCell($column . $row)->getCalculatedValue());
                    if (is_object($value)) {
                        $value = $value->__toString();
                    }
                    $data[$row][] = $value;
                }
            }
        }
        return $data;
    }

    /**
     * php web waf
     */
    public static function waf()
    {
        $rules = [
            '\.\./', //禁用包含 ../ 的参数
            '\<\?', //禁止php脚本出现
            '\s*or\s+.*=.*', //匹配' or 1=1 ,防止sql注入
            'select([\s\S]*?)(from|limit)', //防止sql注入
            '(?:(union([\s\S]*?)select))', //防止sql注入
            'having|updatexml|extractvalue', //防止sql注入
            'sleep\((\s*)(\d*)(\s*)\)', //防止sql盲注
            'benchmark\((.*)\,(.*)\)', //防止sql盲注
            'base64_decode\(', //防止sql变种注入
            '(?:from\W+information_schema\W)', //防止sql注入
            '(?:(?:current_)user|database|schema|connection_id)\s*\(', //防止sql注入
            '(?:etc\/\W*passwd)', //防止窥探linux用户信息
            'into(\s+)+(?:dump|out)file\s*', //禁用mysql导出函数
            'group\s+by.+\(', //防止sql注入
            '(?:define|eval|file_get_contents|include|require|require_once|shell_exec|phpinfo|system|passthru|preg_\w+|execute|echo|print|print_r|var_dump|(fp)open|alert|showmodaldialog)\(', //禁用webshell相关某些函数
            '(gopher|doc|php|glob|file|phar|zlib|ftp|ldap|dict|ogg|data)\:\/', //防止一些协议攻击
            '\$_(GET|post|cookie|files|session|env|phplib|GLOBALS|SERVER)\[', //禁用一些内置变量,建议自行修改
            '\<(iframe|script|body|layer|meta|base|object|input)', //防止xss标签植入
            '(onmouseover|onerror|onload|onclick)\=', //防止xss事件植入
            '\|\|.*(?:ls|pwd|whoami|ll|ifconfog|ipconfig|&&|chmod|cd|mkdir|rmdir|cp|mv)', //防止执行shell
            '\s*and\s+.*=.*', //匹配 and 1=1
            'alert\(.*\)', //匹配 alert() 函数
        ];
        $waf = new \Xielei\Waf\Waf($rules);
        if (!$waf->check()) {
            $result = ['success' => true, 'errorCode' => '403', 'errorMessage' => '非法请求数据'];
            self::responseJson($result);
        }
    }

    /**
     * 本地接口调用
     * @param array $param 接口参数
     * @param string $url 接口地址
     * @return array 数据
     */
    public static function locaApi($param, $url)
    {
        $params = Aes::encryptParams($param);
        $curl = new curl\Curl();
        $response = $curl->setRequestBody(json_encode($params))->post($url);
        if ($curl->errorCode === null) {
            $result = json_decode($response, true);
            if ($result['errorMessage']) {
                $data['errorCode'] = '1001';
                $data['errorMessage'] = $result['errorMessage'];
            } else {
                $data = $result['data'];
            }
        } else {
            $data['errorCode'] = '1001';
            $data['errorMessage'] = '接口调用失败';
        }
        return $data;
    }

    /**
     * 视频音频播放器代码替换
     * @param string $content 内容
     * @return string 内容
     */
    public static function handleMedia($content = '')
    {
        if ($content) {
            preg_match_all('/<embed type=[\'|"]application\/x-shockwave-flash[\'|"] class=[\'|"]edui-faked-video[\'|"]\s*.*\s*src=[\'|"](.*\.(flv|mp4|m3u8))[\'|"]\s*width=[\'|"](\d+)[\'|"]\s*height=[\'|"](\d+)[\'|"].*\/?>(<\/embed>)?/isU', $content, $media);
            if (isset($media[1]) && count($media[1]) > 0) {
                foreach ($media[1] as $k => $v) {
                    $content = str_replace($media[0][$k], '<video src="' . $v . '" width="' . $media[3][$k] . '" height="' . $media[4][$k] . '" controls="controls" preload="auto" style="background:#000;display:block;margin:0 auto;"></video>', $content);
                }
            }
            preg_match_all('/<embed type="application\/x-shockwave-flash" class="edui-faked-music"\s*.*\s*src="(.*\.(mp3|mp4))"\s*width="(\d+)"\s*height="(\d+)".*\/?>(<\/embed>)?/isU', $content, $music);
            if (isset($music[1]) && count($music[1]) > 0) {
                foreach ($music[1] as $k => $v) {
                    $content = str_replace($music[0][$k], str_replace(array('{url}'), array($v), '<audio src="{url}" controls="controls"/>'), $content);
                }
            }
        }
        return $content;
    }

    /**
     * 获取用户姓名部门
     * @param string $where 条件
     * @return array ['name'=>'','departmentname'=>'','departmentid'=>'']
     */
    public static function getUserNameDepartment($where = ['>', 'id', 0])
    {
        $getUserArr = [];
        $res = (new \yii\db\query())->select(['name', 'userid', 'departmentname', 'departmentid'])->from('weixin_leave_userinfo')->where($where)->all();
        foreach ($res as $val) {
            $getUserArr[$val['userid']] = [
                'name' => $val['name'],
                'departmentname' => $val['departmentname'],
                'departmentid' => $val['departmentid']
            ];
        }
        return $getUserArr;
    }

    /**
     * 根据部门名称获取部门id
     * @param string $name 部门名称
     * @return array 部门id
     */
    public static function getDepartmentIds($name)
    {
        $res = $departmentId = [];
        $department = (new \yii\db\Query())->select('id')->from('weixin_oa_department')->where(['like', 'name', $name])->all();
        foreach ($department as $v) {
            $departmentId[] = $v['id'];
        }
        if ($departmentId) {
            $where = ['or'];
            foreach ($departmentId as $v) {
                $where[] = ['like', "CONCAT(',', parentids, ',')", ',' . $v . ','];
            }
            $where1 = ['or', ['in', 'id', $departmentId]];
            if (count($where) > 1) {
                $where1[] = $where;
            }
            $res = (new \yii\db\query())->select('id')->from('weixin_oa_department')->where($where1)->all();
            if ($res) {
                $res = array_column($res, 'id');
            }
        }
        return $res;
    }
}
