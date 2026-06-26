<?php

namespace app\modules\weixin\controllers;
use app\modules\weixin\commons\ApiBase;
class DownAnnexController extends ApiBase{
    public $enableCsrfValidation = false;
    protected $browser = array('com.tencent.wework', 'com.tencent.mm');
    
    public function init()
    {
        parent::init();
    }
    public function actionView(){
        // $redis = new RedisCommon();
        // $redis->delete('downannex_test');
        // echo($redis->get('downannex_test'));
        // if(in_array($_SERVER['HTTP_X_REQUESTED_WITH'],$this->browser)){

        // }
        var_dump($_SERVER);
        $this->writeLog(json_encode($_SERVER));
    }
    public function actionFile (){
        $attachment = $this->_request->getQuery('attachment');
        $name = $this->_request->getQuery('name');
        $savepath = $this->getModule()->imagesavepath;
        $temppath = $this->_request->getQuery('savepath');
        if ($temppath) $savepath = $temppath;
        $imagefile = $savepath.$attachment;
        var_dump($imagefile);
        if(is_file($imagefile)){
            $filesize = filesize($imagefile);
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header("Content-Disposition: attachment;filename = ". ($name?trim($name):basename($attachment)));
            header("Content-Length: " . $filesize);
            header("Content-Type: application/octet-stream");
            if($filesize > 50*1024*1024){
                $fp=fopen($imagefile,'r');
                while (!feof($fp)){
                    $str=fread($fp, $filesize/10);//每次读出文件10分之1
                    echo $str;
                }
                fclose($imagefile);
                
            }else{
                echo file_get_contents($imagefile);
            }
        }
        exit;
    }

    private function writeLog($log)
    {
        file_put_contents($this->getModule()->attachmentpath . 'logs/downannex_log_' . date('Ymd') . '.txt', '[DownAnnexController]' . date('Y-m-d H:i:s => ') . $log . "\r\n", FILE_APPEND);
    }	
    
}
