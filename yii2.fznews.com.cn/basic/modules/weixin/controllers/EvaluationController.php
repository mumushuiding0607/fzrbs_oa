<?php

namespace app\modules\weixin\controllers;
use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Aes;
use app\modules\api\controllers\EvaluationController as ControllersEvaluationController;
use Yii;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;

/**
 * 考评系统微信端控制器
 * 代理到 api/EvaluationController
 */
class EvaluationController extends ApiBase
{
    protected $refController = 'EvaluationController';
    public $enableCsrfValidation = false;

    public function init()
    {
        // 直接调用 parent::init() 使用标准 ApiBase 处理
        parent::init();
    }

    public function actionGetconfig(){
        return $this->_runAction($this->refController, 'getconfig','GET');
    }

    public function actionGetdepartments(){
        return $this->_runAction($this->refController, 'getdepartments','GET');
    }

    public function actionGettasks(){
        return $this->_runAction($this->refController, 'gettasks','GET');
    }

    public function actionDeletetask(){
        return $this->_runAction($this->refController, 'deletetask','POST');
    }

    public function actionGettaskdetail(){
        return $this->_runAction($this->refController, 'gettaskdetail','POST');
    }

    public function actionSubmitscore(){
        return $this->_runAction($this->refController, 'submitscore','POST');
    }

    public function actionBatchsubmitscore(){
        return $this->_runAction($this->refController, 'batchsubmitscore','POST');
    }

    public function actionPersonalsubmitscore(){
        return $this->_runAction($this->refController, 'personalsubmitscore','POST');
    }

    public function actionDeletepersonalscore(){
        return $this->_runAction($this->refController, 'deletepersonalscore','POST');
    }

    public function actionUpdatepersonalscore(){
        return $this->_runAction($this->refController, 'updatepersonalscore','POST');
    }

    public function actionDeletescore(){
        return $this->_runAction($this->refController, 'deletescore','POST');
    }

    public function actionGeneratetasks(){
        return $this->_runAction($this->refController, 'generatetasks','GET');
    }

    public function actionAutoscore(){
        return $this->_runAction($this->refController, 'autoscore','POST');
    }

    public function actionGetpenalties(){
        return $this->_runAction($this->refController, 'getpenalties','GET');
    }

    public function actionGetwarnings(){
        return $this->_runAction($this->refController, 'getwarnings','GET');
    }

    public function actionExportpenalties(){
        return $this->_runAction($this->refController, 'exportpenalties','GET');
    }

    public function actionExportwarnings(){
        return $this->_runAction($this->refController, 'exportwarnings','GET');
    }

    public function actionExporttasks(){
        return $this->_runAction($this->refController, 'exporttasks','GET');
    }

    public function actionCalculatededuction(){
        return $this->_runAction($this->refController, 'calculatededuction','POST');
    }

    public function actionGeneratewarnings(){
        return $this->_runAction($this->refController, 'generatewarnings','POST');
    }

    public function actionSavepenaltyinput(){
        return $this->_runAction($this->refController, 'savepenaltyinput','POST');
    }

    public function actionCalculatepenalty(){
        return $this->_runAction($this->refController, 'calculatepenalty','POST');
    }

    public function actionGetpenaltyresult(){
        return $this->_runAction($this->refController, 'getpenaltyresult','GET');
    }

    public function actionExportpenalty(){
        return $this->_runAction($this->refController, 'exportpenalty','GET');
    }
}
