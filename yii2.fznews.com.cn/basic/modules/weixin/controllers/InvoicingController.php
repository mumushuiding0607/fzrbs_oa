<?php

namespace app\modules\weixin\controllers;
use app\modules\weixin\commons\ApiBase;

use app\modules\api\commons\WorkflowParse;
use app\modules\api\controllers\InvoicingController as ControllersInvoicingController;
use app\modules\api\models\FzrbsBudgetDict;
use app\modules\api\models\WeixinOaApprovalInfo;
use app\modules\api\models\WeixinOAUserInfo;
use Yii;

use app\modules\weixin\commons\Tools;
use Exception;
use yii\db\Expression;

class InvoicingController extends ApiBase
{

    protected $refController = 'InvoicingController';
    public $enableCsrfValidation = false;
    protected $statusCn = array('','审批中','已同意','已驳回','已取消');
    protected $protectCn = array('预算审批','决算审批','待提交绩效','无效');
    protected $agentId = 1000085;
    protected $savepath = 'canteen/excel';
    protected $userinfo = array();

    // 分管领导角色id
    protected $LEADER_ROLE_ID=6;

    // 会计
    protected $ACCOUNT_ROLE=5;
    public function init()
    {
        parent::init();
    }
    public function actionSave(){
      return $this->_runAction($this->refController, 'save','GET');
    }
    public function actionGetprintinfo(){
      return $this->_runAction($this->refController, 'getprintinfo','GET');
    }

    public function actionSaveinvoice(){
      return $this->_runAction($this->refController, 'saveinvoice','GET');
    }
    public function actionSavepdfinvoice(){
      return $this->_runAction($this->refController, 'savepdfinvoice','GET');
    }
    public function actionGetinvoicelist(){
      return $this->_runAction($this->refController, 'getinvoicelist','GET');
    }
    public function actionStartflow(){
      return $this->_runAction($this->refController, 'startflow','GET');
    }
    public function actionDelinvoice(){
      return $this->_runAction($this->refController, 'delinvoice','GET');
    }
    public function actionDelinvoiceitem(){
      return $this->_runAction($this->refController, 'delinvoiceitem','GET');
    }
    public function actionGetcompany(){
      return $this->_runAction($this->refController, 'getcompany','GET');
    }
    public function actionGetdictbykeyword(){
      return $this->_runAction($this->refController, 'getdictbykeyword','GET');
    }
    public function actionGetflowdata(){
      return $this->_runAction($this->refController, 'getflowdata','GET');
    }
    public function actionGetflow() {
      return $this->_runAction($this->refController, 'getflow','GET');
    }
    public function actionViewflow(){
      return $this->_runAction($this->refController, 'viewflow','GET');
    }
    public function actionFlowact(){
      return $this->_runAction($this->refController, 'flowact','GET');

    }
    public function actionGetbyid(){
      return $this->_runAction($this->refController, 'getbyid','GET');

    }
    public function actionGetcontracts(){
      return $this->_runAction($this->refController, 'getcontracts','GET');
    }
    
    public function actionGetinvoiceitems(){
      return $this->_runAction($this->refController, 'getinvoiceitems','GET');
    }

    public function actionGetlist(){
      return $this->_runAction($this->refController, 'getlist','GET');
    }
    public function actionGetstates(){
      return $this->_runAction($this->refController, 'getstates','GET');
    }
    public function actionGetusers(){
      return $this->_runAction($this->refController, 'getusers','GET');
    }
    public function actionApprovallist(){
      return $this->_runAction($this->refController, 'approvallist','GET');
    }
    public function actionHistorylist(){
      return $this->_runAction($this->refController, 'historylist','GET');
    }
    public function actionGetprolist(){
      
      return $this->_runAction($this->refController, 'getprolist','GET');
    }

    public function actionGetprojectbykeyword(){
      return $this->_runAction($this->refController, 'getprojectbykeyword','GET');
    }
    public function actionGetbudgetinfo(){
      
      return $this->_runAction($this->refController, 'getbudgetinfo','GET');
    }
    public function actionGetbalancefileurls(){
      
      return $this->_runAction($this->refController, 'getbalancefileurls','GET');
    }
    public function actionAddcontract(){
      return $this->_runAction($this->refController, 'addcontract','POST');
    }
   public function actionSavecompany(){
    return $this->_runAction($this->refController, 'savecompany','POST');
   }
   public function actionGettabs(){
    return $this->_runAction($this->refController, 'gettabs','GET');
   }
   public function actionInvoicetypes(){
    return $this->_runAction($this->refController, 'invoicetypes','GET');
   }
   public function actionCanceldelinvoicingnotice(){
    return $this->_runAction($this->refController, 'canceldelinvoicingnotice','GET');
   }
   public function actionDelinvoicingnotice(){
    return $this->_runAction($this->refController, 'delinvoicingnotice','GET');
   }
    
    public function actionFile (){
      $attachment = $this->_request['attachment'];
      $name = $this->_request['name'];
      $savepath = $this->_request['savepath'];
      $imagefile = $savepath.$attachment;
      // Tools::responseJson($imagefile);
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
}

