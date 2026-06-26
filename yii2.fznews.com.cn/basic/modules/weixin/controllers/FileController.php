<?php

use yii\base\Controller;

class FileController extends Controller{
  public $enableCsrfValidation = false;
  protected $refController = 'FileController';
  public function actionView (){
    return $this->_runAction($this->refController, 'view','GET');
  }
}