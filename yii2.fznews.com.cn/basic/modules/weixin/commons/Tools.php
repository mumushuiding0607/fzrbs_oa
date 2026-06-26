<?php

namespace app\modules\weixin\commons;

use app\modules\weixin\commons\QYWeiXinAPI;
use app\modules\api\models\WeixinHolidays;
use app\modules\api\models\WeixinChannel;
use Xiuchuan\Ecc\SM4\Sm4;
use Yii;

/**
 * 工具函数类
 */
class Tools
{
    protected static $key = 'PT3ZOOSWtolC7fMJ';

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
        $response->send();
        exit;
    }

    /**
     * 企业微信应用文字消息发送
     * @param string $appId 应用id
     * @param string $userId 用户id
     * @param string $content 发送内容
     * @param string $toparty 部门id
     * @return array 发送结果
     */
    public static function sendWxMessage($appId, $userId, $content, $toparty = '', $msgType = 'text')
    {
        $data = [
            'touser' => $userId,
            'toparty' => $toparty,
            'msgtype' => $msgType,
            'agentid' => $appId,
        ];
        switch ($msgType) {
            case 'text':
                $data['text'] = ['content' => $content];
                break;
            case 'textcard':
                $data['textcard'] = $content;
                break;
        }
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $qyhapi = new QYWeiXinAPI($appId);
        $result = $qyhapi->sendMessage($data);
        return ['errcode' => $qyhapi->errcode, 'errmsg' => $qyhapi->errmsg, 'data' => $result];
    }

    /**
     * 获取每年假期配置信息
     * @param int $year 年份
     * @return array 假期配置信息
     */
    public static function getHolidayConfig($year = 0)
    {
        $config = [];
        if (!$year) {
            $year = [date('Y'), date('Y') + 1];
        }
        !is_array($year) && $year = [$year];
        $res = WeixinHolidays::find()->where(['in', 'year', $year])->all();
        if ($res) {
            foreach ($res as $row) {
                if (!isset($config[$row->type])) {
                    if ($row->days) {
                        $config[$row->type] = explode(',', $row->days);
                    }
                } else {
                    if ($row->days) {
                        $config[$row->type] = explode(',', implode(',', $config[$row->type]) . ',' . $row->days);
                    }
                }
            }
        }
        return $config;
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
     * 错误规则
     * @param string $logBaseName 日志基础文件名
     * @param string $content 日志内容
     */
    public static function writeLog($logBaseName, $content)
    {
        file_put_contents(Yii::$app->basePath . '/runtime/logs/' . $logBaseName . '_' . date('Ymd') . '.txt', date('Y-m-d H:i:s => ') . $content . "\r\n", FILE_APPEND);
    }

    /**
     * 视频音频播放器代码替换
     * @param string $content 内容
     * @return string 内容
     */
    public static function handleMedia($content = '', $mobile = 0, $from = '')
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
        if ($mobile == 1) {
            $dom = new SimpleHtmlDom(null, true, true, 'utf-8', true, "\r\n", " ");
            $dom->load($content);
            foreach ($dom->find('img') as $element) {
                if (strpos($element->src, 'ueditor/') === false) {
                    $element->style = "width:100%;height:auto;";
                }
            }
            if (strpos($content, '<video') !== false) {
                foreach ($dom->find('video') as $element) {
                    $element->style = "width:100%;height:auto;";
                }
            }
            $content = $dom->save();
        }
        if ($from == 'neiwang') {
            $content = str_replace('http://129.0.99.30/assets/', 'https://fzrb.fznews.com.cn/assetsnw/', $content);
        }
        return $content;
    }

    /**
     * 获取栏目名称
     * @param string $channelId 栏目id
     * @return string 栏目名称
     */
    public static function getChannelName($channelId)
    {
        $channelModal = new WeixinChannel;
        $channelModal = $channelModal::find()->select('id, name')->where(['=', 'id', $channelId])->one();
        return $channelModal ? $channelModal->name : '';
    }

    /**
     * 删除文件
     * @param string $rootPath 服务器路径
     * @param string $fileParh 文件路径
     */
    public static function deleteFile($rootPath, $filePath)
    {
        $localFile = $rootPath . str_replace('/uploaded/', '', $filePath);
        @unlink($localFile);
    }

    /**
     * 获取子部门
     * @param string $pid 部门id
     * @return array 子部门信息
     */
    public static function  getDepartmentChildren($pid)
    {
        $allDepartIds = $departIds = explode(',', $pid);
        $childArr = [];
        $firstChild = [];
        $first = 1;
        do {
            $ids = '';
            $child = (new \yii\db\Query())->select('id')->from('weixin_leave_department')->where(['and', ['=', 'st', 1], ['in', 'parentid', $departIds]])->all();
            if (count($child) > 0) {
                foreach ($child as $item) {
                    $childArr[] = $item['id'];
                    $allDepartIds[] = $item['id'];
                    $first && $firstChild[] = $item['id'];
                    $ids .= ',' . $item['id'];
                }
                $first = 0;
                $ids = substr($ids, 1, strlen($ids));
                $departIds = explode(',', $ids);
            }
        } while (!empty($child));
        return ['allDepartIds' => $allDepartIds, 'childIdsArr' => $childArr, 'firstChild' => $firstChild];
    }

    public static function getThirdNo($userId = 0)
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return strval($userId ? $userId . $msectime : $msectime);
    }

    public static function getFileData($filePath, $protect = false, $stream = false)
    {
        
        $rootPath = Yii::$app->basePath . '/web/uploaded/';
        if ($protect) {
            $rootPath = Yii::$app->basePath . '/attachments/';
        }
        $localFile = $rootPath . str_replace('/uploaded/', '', $filePath);
        
 

        if (strpos($filePath, '/www/web/fzrb.fznews.com.cn/attachment') === 0) {
            $localFile = $filePath;
            // 如果附件包含&的值就分割取第一个
            
            if(strpos($filePath, '&')>-1){
                $localFile = substr($filePath, 0, strpos($filePath, '&'));
            }
        }
        
 
       
        if (is_file($localFile)) {
            $fileExt = strtolower(strrchr($localFile, '.'));
            $file = fopen($localFile, 'rb');
            $fileSize= filesize($localFile);
            if(!$fileSize){
              
              $fileContent = file_get_contents($localFile);
           
            }else{
              $fileContent = fread($file, $fileSize);
            }
            
            
            fclose($file);
            if ($stream) {
                $filename = 'file.jpeg';
                $response = Yii::$app->response;
                $response->format = \yii\web\Response::FORMAT_RAW;
                if (in_array($fileExt, ['.jpg', '.jpeg'])) {
                    $response->headers->add('Content-Type', 'image/jpeg');
                    $filename = 'file.jpeg';
                } else if ($fileExt == '.png') {
                    $response->headers->add('Content-Type', 'image/png');
                    $filename = 'file.png';
                } else if ($fileExt == '.gif') {
                    $response->headers->add('Content-Type', 'image/gif');
                    $filename = 'file.gif';
                } else if ($fileExt == '.pdf') {
                    $response->headers->add('Content-Type', 'application/pdf');
                    $filename = 'file.pdf';
                }
                $response->headers->add('Content-Disposition', 'attachment; filename="' . $filename . '"');
                $response->headers->add('Content-Length', filesize($localFile));
                $response->data = $fileContent;
                $response->send();
                exit;
            }
            return ['ext' => $fileExt, 'content' => base64_encode($fileContent),'localFile'=>$localFile];
        }
        var_dump('hhhh1');exit;
        return false;
    }

    /**
     * 国密sm4加密
     * @param string $data 加密数据
     * @return string
     */
    public static function sm4Encrypt($data)
    {
        $sm4 = new Sm4();
        return $sm4->encrypt(self::$key, $data);
    }

    /**
     * 国密sm4解密
     * @param string $data 解密数据
     * @return string
     */
    public static function sm4Decrypt($data)
    {
        $sm4 = new Sm4();
        return $sm4->decrypt(self::$key, $data);
    }

    /**
     * 下载图片到本地服务器
     * @param string $url 图片地址
     * @return string
     */
    public static function downloadImages($url, $savePath = '/information/')
    {
        $filecontent = @file_get_contents($url);
        if ($filecontent) {
            $filename = time() . rand(1, 100000);
            $ext = pathinfo($url, PATHINFO_EXTENSION);
            if (!$ext || strlen($ext) > 4) {
                $imageResponseHeader = $http_response_header;
                if (array_search("Content-Type: image/png", $imageResponseHeader) !== false) {
                    $ext = 'png';
                } else if (array_search("Content-Type: image/jpeg", $imageResponseHeader) !== false) {
                    $ext = 'jpg';
                } else if (array_search("Content-Type: image/gif", $imageResponseHeader) !== false) {
                    $ext = 'gif';
                } else if (array_search("Content-Type: image/webp", $imageResponseHeader) !== false) {
                    $ext = 'webp';
                }
            }
            $filename = "$filename.$ext";
            $imageSavePath = Yii::$app->basePath . '/web/uploaded';
            $temppath = $imageSavePath . $savePath . date("Ymd");
            if (!file_exists($temppath)) {
                if (!mkdir($temppath, 0777, true)) {
                }
            }
            $fp = @fopen($temppath . "/$filename", "w");
            @fwrite($fp, $filecontent);
            @fclose($fp);
            $localImageUrl = '/uploaded' . $savePath . date("Ymd") . '/' . $filename;
            return $localImageUrl;
        }
        return $url;
    }

    public static function virtualKeyUsers()
    {
        return [];
    }
}
