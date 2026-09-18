<?php

namespace app\modules\weixin\controllers;

use app\modules\weixin\commons\ApiBase;
use app\modules\api\controllers\YxkhController as ApiYxkhController;

/**
 * 月度考核微信端控制器
 * 代理到 api/YxkhController
 */
class YxkhController extends ApiBase
{
    protected $refController = 'YxkhController';
    public $enableCsrfValidation = false;

    public function init()
    {
        parent::init();
    }

    public function actionGetlist()
    {
        return $this->_runAction($this->refController, 'getlist', 'POST');
    }

    public function actionGetuserinfo()
    {
        return $this->_runAction($this->refController, 'getuserinfo', 'POST');
    }

    public function actionSave()
    {
        return $this->_runAction($this->refController, 'save', 'POST');
    }

    public function actionStartflow()
    {
        return $this->_runAction($this->refController, 'startflow', 'POST');
    }

    public function actionGetflow()
    {
        return $this->_runAction($this->refController, 'getflow', 'POST');
    }

    public function actionFlowact()
    {
        return $this->_runAction($this->refController, 'flowact', 'POST');
    }

    public function actionDelflow()
    {
        return $this->_runAction($this->refController, 'delflow', 'POST');
    }

    public function actionViewflow()
    {
        return $this->_runAction($this->refController, 'viewflow', 'POST');
    }

    public function actionSavesproject()
    {
        return $this->_runAction($this->refController, 'savesproject', 'POST');
    }

    public function actionDelproject()
    {
        return $this->_runAction($this->refController, 'delproject', 'POST');
    }

    public function actionGetprojects()
    {
        return $this->_runAction($this->refController, 'getprojects', 'POST');
    }

    public function actionSavemark()
    {
        return $this->_runAction($this->refController, 'savemark', 'POST');
    }

    public function actionDelmark()
    {
        return $this->_runAction($this->refController, 'delmark', 'POST');
    }
}
