<?php

namespace app\modules\api\controllers;

use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Uploader;

/**
 * Ueditor相关接口类
 */
class UeditorController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinNews';
    protected $_savePath;
    protected $_config = '';
    protected $_action;

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }

    public function init()
    {
        parent::init();
        $this->_action = $this->_request['action'];
        $this->_savePath = 'information';

        $this->_config = [
            "imageActionName" => "uploadimage",
            "imageFieldName" => "upfile",
            "imageAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp"],
            "imageCompressEnable" => false,
            "imageInsertAlign" => "center",
            "imageUrlPrefix" => "",
            "scrawlActionName" => "uploadscrawl",
            "scrawlFieldName" => "upfile",
            "scrawlUrlPrefix" => "",
            "scrawlInsertAlign" => "center",
            "snapscreenActionName" => "uploadimage",
            "snapscreenUrlPrefix" => "",
            "snapscreenImgAlign" => "center",
            "catcherLocalDomain" => ["127.0.0.1", "localhost", "img.baidu.com"],
            "catcherActionName" => "catchimage",
            "catcherFieldName" => "source",
            "catcherUrlPrefix" => "",
            "catcherAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp"],
            "videoActionName" => "uploadvideo",
            "videoFieldName" => "upfile",
            "videoUrlPrefix" => "",
            "videoAllowFiles" => [
                ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
                ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid"
            ],
            "fileActionName" => "uploadfile",
            "fileFieldName" => "upfile",
            "fileUrlPrefix" => "",
            "fileAllowFiles" => [
                ".png", ".jpg", ".jpeg", ".gif", ".bmp",
                ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
                ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid",
                ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso",
                ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"
            ],
            "imageManagerActionName" => "listimage",
            "imageManagerListPath" => "/",
            "imageManagerListSize" => 20,
            "imageManagerUrlPrefix" => "",
            "imageManagerInsertAlign" => "center",
            "imageManagerAllowFiles" => [".png", ".jpg", ".jpeg", ".gif", ".bmp"],
            "fileManagerActionName" => "listfile",
            "fileManagerListPath" => "/",
            "fileManagerUrlPrefix" => "",
            "fileManagerListSize" => 20,
            "fileManagerAllowFiles" => [
                ".png", ".jpg", ".jpeg", ".gif", ".bmp",
                ".flv", ".swf", ".mkv", ".avi", ".rm", ".rmvb", ".mpeg", ".mpg",
                ".ogg", ".ogv", ".mov", ".wmv", ".mp4", ".webm", ".mp3", ".wav", ".mid",
                ".rar", ".zip", ".tar", ".gz", ".7z", ".bz2", ".cab", ".iso",
                ".doc", ".docx", ".xls", ".xlsx", ".ppt", ".pptx", ".pdf", ".txt", ".md", ".xml"
            ]
        ];
    }

    public function actionDo()
    {
        switch ($this->_action) {
            case 'config':
                $result =  $this->_config;
                break;
            case 'uploadimage':
                $result =  $this->_upload();
                break;
            case 'catchimage':
                $result =  $this->_remote();
                break;
            case 'uploadfile':
                $result =  $this->_attach();
                break;
            case 'uploadvideo':
                $result =  $this->_upload('video');
                break;
            case 'uploadscrawl':
                $result =  $this->_scrawl();
                break;
        }
        return $result;
    }


    /**
     *  Ueditor图片视频上传按钮
     */
    protected function _upload($type = 'image')
    {
        $config = ["rootPath" => $this->_imageSavePath, "savePath" => $this->_savePath];
        if ($type == 'image') {
            $config['maxSize'] = 2048000;
            $config['allowFiles'] = [".gif", ".png", ".jpg", ".jpeg", ".bmp", ".webp"];
        } else {
            $config['maxSize'] = 10000000000;
            $config['allowFiles'] = [".mp4"];
        }
        $upInfo = new Uploader("upfile", $config);
        $upResult = $upInfo->getFileInfo();
        return $upResult;
    }

    /**
     *  Ueditor附件上传按钮
     */
    protected function _attach()
    {
        $config = [
            "rootPath" => $this->_imageSavePath,
            "savePath" => $this->_savePath,
            "allowFiles" => array(".rar", ".doc", ".docx", ".zip", ".pdf", ".txt", ".swf", ".wmv", ".xls", ".xlsx", ".mp3", ".mp4"),
            "maxSize" => 204800000
        ];
        $upInfo = new Uploader("upfile", $config);
        $upResult = $upInfo->getFileInfo();
        $upResult["original"] = $upResult["originalName"];
        return $upResult;
    }

    /**
     *  Ueditor涂鸦上传按钮
     */
    protected function _scrawl()
    {
        $config = [
            "rootPath" => $this->_imageSavePath,
            "savePath" => $this->_savePath,
            "maxSize" => 2048000,
            "allowFiles" => array(".gif", ".png", ".jpg", ".jpeg", ".bmp")
        ];
        $base64 = "base64";
        $upInfo = new Uploader("upfile", $config, $base64);
        $upResult = $upInfo->getFileInfo();
        return $upResult;
    }

    /**
     * 自动下载编辑器的远程图片到本地服务器
     */
    protected function _remote()
    {
        set_time_limit(0);
        $config = array(
            "rootPath" => $this->_imageSavePath,
            "savePath" => $this->_savePath,
            "allowFiles" => array(".gif", ".png", ".jpg", ".jpeg", ".bmp", ".webp"),
            "maxSize" => 2048000
        );
        $fieldName = 'source';
        $list = array();
        if (isset($_POST[$fieldName])) {
            $source = $_POST[$fieldName];
        } else {
            $source = $_GET[$fieldName];
        }
        foreach ($source as $imgUrl) {
            $imgUrl = htmlspecialchars($imgUrl);
            $imgUrl = str_replace("&amp;", "&", $imgUrl);
            $excludeDomain = false;
            if (is_array(Yii::$app->params['excludeDomain'])) {
                foreach (Yii::$app->params['excludeDomain'] as $v) {
                    if (stripos($imgUrl, $v) !== false) {
                        $excludeDomain = true;
                        break;
                    }
                }
            }
            if ($excludeDomain === false) {
                if (strpos($imgUrl, "http") !== 0) {
                    array_push($list, array("state" => '链接不是http链接'));
                    continue;
                }
                $heads = get_headers($imgUrl);
                if (!(stristr($heads[0], "200") && stristr($heads[0], "OK"))) {
                    array_push($list, array("state" => '链接不可用'));
                    continue;
                }
                $ext = pathinfo($imgUrl, PATHINFO_EXTENSION);
                if (!$ext || strlen($ext) > 4) {
                    if (array_search("Content-Type: image/png", $heads) !== false) {
                        $ext = 'png';
                    } else if (array_search("Content-Type: image/jpeg", $heads) !== false) {
                        $ext = 'jpg';
                    } else if (array_search("Content-Type: image/gif", $heads) !== false) {
                        $ext = 'gif';
                    } else if (array_search("Content-Type: image/webp", $heads) !== false) {
                        $ext = 'webp';
                    }
                }
                $fileType = ".$ext";
                if (!in_array($fileType, $config['allowFiles'])) {
                    // array_push($list, array("state" => '链接contentType不正确'));
                    // continue;
                    $ext = 'jpg';
                }
                ob_start();
                $context = stream_context_create(
                    array(
                        'http' => array(
                            'follow_location' => false
                        )
                    )
                );
                readfile($imgUrl, false, $context);
                $img = ob_get_contents();
                ob_end_clean();
                $uriSize = strlen($img);
                $allowSize = 1024 * $config['maxSize'];
                if ($uriSize > $allowSize) {
                    array_push($list, array("state" => '文件大小超出限制'));
                    continue;
                }
                $savePath = $this->_imageSavePath . $config['savePath'] . '/' . date("Ymd");
                $relativeUrl = "/uploaded/" . $config['savePath'] . '/' . date("Ymd") . '/';
                if (!file_exists($savePath)) {
                    mkdir("$savePath", 0777, true);
                }
                $filename = time() . rand(1, 100000) . '.' . $ext;
                $tmpName = $savePath . '/' . $filename;
                $relativeUrl .= $filename;
                try {
                    $fp2 = @fopen($tmpName, "a");
                    fwrite($fp2, $img);
                    fclose($fp2);
                    $tmpName = $relativeUrl;
                    array_push($list, array(
                        "state" => 'SUCCESS',
                        "url" => $tmpName,
                        "size" => strlen($img),
                        "title" => '',
                        "original" => $imgUrl,
                        "source" => $imgUrl,
                    ));
                } catch (\Exception $e) {
                    array_push($list, array("state" => '无法保存文件'));
                }
            } else {
                array_push($list, array("state" => '链接不可用'));
            }
        }
        return ['state' => count($list) ? 'SUCCESS' : 'ERROR', 'list' => $list];
    }
}
