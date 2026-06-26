<?php

namespace app\modules\api\controllers;
use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\models\FzrbsCompany;
use app\modules\api\models\FzrbsInvoicing;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinOAUserInfo;
use Exception;
use yii\db\Expression;



class CompanyController extends ApiBase{
  public $modelClass = 'app\modules\api\models\FzrbsCompany';
  protected $userinfo = array();
  public function init()
  {
      parent::init();
      $this->userinfo = $this->getUserinfo($this->_adminInfo['wxuserid']);
  }

  public function add($new){
    if (!$new) return '';
    $arr = array_unique(explode(',',$new));

    return implode(',',$arr);
  }
  public function actionGetcompany(){
    if ($this->_request['preloadInvoicingPartb']){
      $datas = FzrbsInvoicing::findBySql("select distinct partb as id,partbname as company from ".FzrbsInvoicing::tableName()." where state=1 and creator='".$this->userinfo['userid']."' order by partb asc limit 30");
      return $datas->asArray()->all();
    }
    $company = $this->_request['company'];
    $id = $this->_request['id'];

    if ($id&&$id=='[object Object]'){
      return array();
    }
    // 打印到日志中
  

    $keyword = $this->_request['keyword'];
    if(!$company && !$id&&!$keyword) return array();
    $sign = $this->_request['sign'];
    $where = [
      'and',[">","id",0]
    ];
    if ($company){
      $where = ['like', 'company', $company];
    }
    if ($id){
      $where = new Expression("id in ($id)");
    }
    if (is_string($keyword)&&$keyword){
      if (strpos($keyword, ',')!==false){
        $keyword = explode(',', $keyword);
        $where = ['in', 'id', $keyword];
      }else{
        $where = ['or',['like', 'company', $keyword],['=', 'id', $keyword]];
      }
      
    }
    if($sign){
      $where[]=['=','sign',$sign];
    }
    
    $datas = FzrbsCompany::find()->where($where)->orderBy('id desc')->limit(50)->all();
    return $datas;
  }
  private function hasRole($rolename,$dept){
      $deptsql = '';
      if ($dept)  $deptsql ="and  FIND_IN_SET($dept, dept)";
      $model = WeixinOaFlowrole::findBySql("SELECT * from weixin_oa_flowrole where userid='".$this->_adminInfo['wxuserid']."' $deptsql and role in (select id from weixin_oa_role where rolename='$rolename')")->one();
      
      return $model?true:false;
    
    }
  public function actionSave()
  {
   
 
    $obj = $this->_request;

    // 判断是否已经存在
    if (!$obj['company']) return array('errorMessage'=>'company不能为空');
    if (!$obj['sign']) {
      $obj['sign'] = 1;
    }
    // $obj['company'] 将()替换成（）
    $obj['company'] = str_replace('(', '（', $obj['company']);
    $obj['company'] = str_replace(')', '）', $obj['company']);
    $obj['company'] = str_replace(' ', '', $obj['company']);
    $obj['company'] = str_replace('　', '', $obj['company']);
    try {
      if ($obj['id']){
        $old = FzrbsCompany::find()->where(['id'=>$obj['id']])->one();
        // 如果$obj['company']有值且包含"（已作废）",但是原来没有，只有创建人才有权限，采用正则匹配
  
        if ($obj['company']&&preg_match('/已作废/', $obj['company'])&&!preg_match('/已作废/', $old['company'])){
          $hasauth = $this->hasRole('管理员','');
          if ($obj->creator!=$this->_adminInfo['wxuserid']&&!$hasauth){
            return array('errorMessage'=>"只有创建人或【管理员】才能作废！");
          }
          $obj['code'] = '';
        }

        // 判断银行信息
        $obj['bankaccount']=$this->add($obj['bankaccount']);
        // 如果修改了公司名称，需要确认一下修改之后的公司名称是否已经存在
        if ($obj['company']) $obj['company']=str_replace(' ','',$obj['company']);
        if ($old['company']!=$obj['company']){
          $res = FzrbsCompany::find()->where(['and',['=','company',$obj['company']]])->one();
          if ($res) return array('errorMessage'=>"[".$obj['company']."]已经存在");
          // 去掉空格
          
        }
   
        FzrbsCompany::updateAll($obj,['id'=>$obj['id']]);
      } else {
        $res = FzrbsCompany::find()->where(['and',['=','company',$obj['company']]])->one();
        if ($res) return array('errorMessage'=>"[".$obj['company']."]已经存在");
        $obj = new FzrbsCompany($obj);
        // company字段空格全部去掉
        $obj->company = str_replace(' ','',$obj->company);
  
        $obj->creator=$this->_adminInfo['wxuserid'];
        $obj->save();
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    
    return array('data'=>$obj);
  }
  private function getUserinfo($userid)
  {
      $userinfo = WeixinOAUserInfo::find()->where(['=', 'userid', $userid])->asArray()->one();
      return $userinfo;
  }
}