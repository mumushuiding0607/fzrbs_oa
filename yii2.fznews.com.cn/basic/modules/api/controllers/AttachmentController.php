<?php

namespace app\modules\api\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\Json;
use app\modules\api\commons\Tools;

/**
 * 附件操作类
 */
class AttachmentController extends Controller
{
    public $enableCsrfValidation = false;
    protected $_request = null;

    public function init()
    {
        parent::init();
        Tools::waf();
        $this->_request = Yii::$app->request->queryParams;
    }

    public function actionPreviewimg()
    {
        $attachment = $this->_request['attachment'];
        if($attachment){
            $weburlpre = 'https://fzrb.fznews.com.cn';
            if(substr($attachment,0,10)=='/uploaded/'){
                $attachmentpath = '/www/web/fzrbs_oa/web';
                $imagefile = $attachmentpath.$attachment;
            }else{
                $attachmentpath = '/www/web/fzrb.fznews.com.cn/attachment/';
                $imagefile = $attachmentpath.'upload/leave/'.$attachment;
            }
            // $imagefile = 'd:\\test\\leave/'.$attachment;
            if(is_file($imagefile)){
                $ext = strtolower(strrchr($imagefile,'.'));
                if(in_array($ext, array('.doc','.docx'))){
                        $imagefile = $weburlpre.'/assets/img/word.png';
                }else if(in_array($ext, array('.xls','.xlsx'))){
                        $imagefile = $weburlpre.'/assets/img/excel.png';
                }else if(in_array($ext, array('.txt'))){
                        $imagefile = $weburlpre.'/assets/img/txt.png';
                }else if(in_array($ext, array('.pdf'))){
                        $imagefile = $weburlpre.'/assets/img/pdf.png';
                }
                $imageinfo=getimagesize($imagefile);
                $imagetype=$imageinfo['mime'];
                switch ($imagetype){
                        case 'image/jpeg':
                                header ("Content-type: image/jpeg");
                                $imageres=imagecreatefromjpeg($imagefile);
                                imagejpeg($imageres);
                                break;
                        case 'image/gif':
                                header ("Content-type: image/gif");
                                $imageres=imagecreatefromgif($imagefile);
                                imagegif($imageres);
                                break;
                        case 'image/png':
                                header ("Content-type: image/png");
                                $imageres=imagecreatefrompng($imagefile);
                                imagepng($imageres);
                                break;
                }
            }else{
                echo('文件不存在！');
            }
        }
    }
}
