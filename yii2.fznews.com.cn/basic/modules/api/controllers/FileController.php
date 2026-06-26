<?php

use yii\base\Controller;

class FileController extends Controller{
  public function actionView (){
            $attachment = $this->_request['attachment'];
            $imagefile = substr($attachment,0,10)=='/uploaded/'?'/www/web/fzrbs_oa/web'.$attachment:$this->attachmentpath.$attachment;
            if(is_file($imagefile)){
                    $ext = strtolower(strrchr($attachment,'.'));
                    if(in_array($ext, array('.doc','.docx'))){
                            $imagefile = $this->getModule()->weburlpre.'/assets/img/word.png';
                    }else if(in_array($ext, array('.xls','.xlsx'))){
                            $imagefile = $this->getModule()->weburlpre.'/assets/img/excel.png';
                    }else if(in_array($ext, array('.txt'))){
                            $imagefile = $this->getModule()->weburlpre.'/assets/img/txt.png';
                    }else if(in_array($ext, array('.pdf'))){
                            $imagefile = $this->getModule()->weburlpre.'/assets/img/pdf.png';
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
            }
            exit;
    }
}