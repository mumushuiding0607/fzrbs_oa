<?php

namespace app\modules\weixin\controllers;
use app\modules\weixin\commons\ApiBase;


class ContractController extends ApiBase
{
    public $enableCsrfValidation = false;
    public function actionDebtlist(){
      return $this->_runAction('ContractController', 'debtlist','GET');
    }
    public function actionViewdebt(){
      return $this->_runAction('ContractController', 'viewdebt','GET');
    }
    public function actionStartdebturge(){
      return $this->_runAction('ContractController', 'startdebturge','GET');
    }
    public function actionGeturges(){ 
      return $this->_runAction('ContractController', 'geturges','GET');
    }
    public function actionSetrecoverable(){
      return $this->_runAction('ContractController', 'setrecoverable','GET');
    }
    public function actionUpdateurge(){
      return $this->_runAction('ContractController', 'updateurge','GET');
    }
    public function actionDebtflowact(){
      return $this->_runAction('ContractController', 'debtflowact','GET');
    }
    public function actionPreviewdebtflow(){
      return $this->_runAction('ContractController', 'previewdebtflow','GET');
    }
    public function actionInglist(){
      return $this->_runAction('ContractController', 'inglist','GET');
    }
    public function actionFinishlist(){
      return $this->_runAction('ContractController', 'finishlist','GET');
    }
    public function actionSaveurgelog(){
      return $this->_runAction('ContractController', 'saveurgelog','GET');
    }
    public function actionDelurgelog(){
      return $this->_runAction('ContractController', 'delurgelog','GET');
    }
    public function actionGeturgelogs(){
      return $this->_runAction('ContractController', 'geturgelogs','GET');
    }
    public function actionEndurge(){
      return $this->_runAction('ContractController', 'endurge','GET');
    }
    public function actionUrgelogslist(){
      return $this->_runAction('ContractController', 'urgelogslist','GET');
    }
}