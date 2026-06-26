<?php

namespace app\modules\api\controllers;
use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinOaRole;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\models\WeixinQYAppInterface;
use app\modules\api\commons\Tools;
use app\modules\api\commons\WorkflowParse;
use app\modules\api\models\FznewsFlowProcess;
use app\modules\api\models\FzrbsBudgetProject;
use app\modules\api\models\WeixinAttendanceTemplate;
use app\modules\api\models\WeixinFinanceCompany;
use app\modules\api\models\WeixinFinanceInfo;
use app\modules\api\models\WeixinFinanceTemplate;
use app\modules\api\models\WeixinFlowApprovaldata;
use app\modules\api\models\WeixinFlowProcess;
use app\modules\api\models\WeixinOaApprovaldata;
use app\modules\api\models\WeixinOaApprovalInfo;
use app\modules\api\models\WeixinOaDepartment;
use app\modules\api\models\WeixinOaPrintPosition;
use app\modules\api\models\WeixinOaTemplates;
use app\modules\api\models\WeixinOaUsertag;
use app\modules\api\models\WeixinUsesealTemplate;
use app\modules\api\models\WeixinYxkhTemplate;
use app\modules\api\models\WeixinOrderTemplate;
use Exception;
use yii\db\Expression;

class FinanceroleController extends ApiBase{
  public $modelClass = 'app\modules\api\models\WeixinOaTemplates';
  private $_notifyAttr = [1=>'提交申请时抄送','审批通过后抄送','提交申请时和审批通过后都抄送'];
	private $_levelname = [1=>'直接上级','第二级上级','第三级上级','第四级上级','第五级上级'];
	private $_approvalAttr = [1=>'或签','会签'];
  protected $userinfo = array();
  protected $_orderBy = 'id desc';  
  protected $appids=[1000085,1000080,1000078,1000065,1000066,1000067,1000070,1000063,1000083,];
  protected $statusCn = array('','审批中','已同意','已驳回','已取消');
  public function init()
  {
      parent::init();
  
      $this->userinfo = $this->getUserinfo($this->_adminInfo['wxuserid']);
  }
  public function actionDelrole(){

    
    
    // 判断是否有管理权限
    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }
    $id = $this->_request['id'];
    if(!$id) return array('errorMessage'=>'id 不能为空');
    WeixinOaFlowrole::deleteAll(['id'=>$id]);
    return array('data'=>'删除成功');
  
  }
  public function actionGetapps(){
    $res = WeixinQYAppInterface::find()->where(['>','id',0])->all();
    $_Tmp=[];
    foreach ($res as $row) {
        $_Tmp[$row->appid] = ['text'=>$row->appname];
    }

    return array('data'=>$_Tmp);
  }
  public function actionGetappoptions(){
    $res = WeixinQYAppInterface::find()->where(['>','id',0])->all();
    // array_map
    $res = array_map(function($item){
      return ['text'=>$item->appname,'value'=>$item->appid,'label'=>$item->appname,];
    },$res);

    return $res;
  }

  // 获取用户权限
  public function actionGetpowers(){
    if (!$this->_request['agentid']) return array('errorMessage'=>'agentid不能为空');
    $result = '';
    if ($this->_adminInfo['usertype']==1) $result='管理';
    $res = WeixinOaRole::findBySql("select GROUP_CONCAT(powername SEPARATOR ',') as powername from ".WeixinOaRole::tableName()." WHERE id in (SELECT role FROM ".WeixinOaFlowrole::tableName()." where userid='".$this->_adminInfo['wxuserid']."' and agent=".$this->_request['agentid'].")")->one();
    if($res&&$res['powername']){
      if ($result){
        $result.=','.$res['powername'];
      }else{
        $result=$res['powername'];
      }
    }
    return array('data'=>$result);
  }
  /**
     * 重写delete的业务实现动作
     */
  public function actionDelflowrole()
  {
      $hasauth = $this->hasRole('流程设置','');
      if (!$hasauth) {
        return array('errorMessage'=>'需要【流程设置】角色');
      }

      if ($this->_request['id']) {
          $ids = explode(',', $this->_request['id']);
          $models = WeixinOaTemplates::find()->where(['in', 'id', $ids])->all();
          $names = [];
          foreach ($models as $model) {
              $names[] = $model->templateId;
              $model->delete();
          }
          if ($names) {
              $action = '[流程模板管理]删除';
              $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，流程模板。包含 " . implode(',', $names) . " 全部删除。";
              $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
          }
      } else {
          return array('errorMessage'=>'id不能为空');
      }
      return array('errorMessage'=>'');
  }

  // 用户为某个人添加或更新角色，

  public function actionSaveflowrole(){
    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      // 如果没有权限，根据操作者userid查询自身对应obj['role']角色的记录，检查部门/代理是否涵盖新值
      $obj = $this->_request;
      $currentUserid = $this->_adminInfo['wxuserid'];
      $targetRole = $obj['role'] ?? null;

      if (empty($targetRole)) {
        return array('errorMessage' => '角色不能为空');
      }

      // 查询操作者自身对应目标角色的记录
      $operatorRoleRecord = WeixinOaFlowrole::find()->where(['userid' => $currentUserid, 'role' => $targetRole])->orderBy('id desc')->one();
      if (!$operatorRoleRecord) {
        return array('errorMessage' => '需要【流程设置】权限！');
      }

      // 检查操作者自身的部门/代理是否涵盖新值
      $coversAll = true;
      if (!empty($obj['dept']) && !empty($operatorRoleRecord['dept'])) {
        $operatorDepts = array_filter(explode(',', $operatorRoleRecord['dept']));
        $newDepts = array_filter(is_array($obj['dept']) ? $obj['dept'] : explode(',', $obj['dept']));
        $coversAll = empty(array_diff($newDepts, $operatorDepts));
      }
      if ($coversAll && !empty($obj['agent']) && !empty($operatorRoleRecord['agent'])) {
        $operatorAgents = array_filter(explode(',', $operatorRoleRecord['agent']));
        $newAgents = array_filter(is_array($obj['agent']) ? $obj['agent'] : explode(',', $obj['agent']));
        $coversAll = empty(array_diff($newAgents, $operatorAgents));
      }

      if (!$coversAll) {
        return array('errorMessage' => '操作人权限范围不足以覆盖本次修改');
      }
    }


    $resp = array('errorMessage'=>'');
    $obj = $this->_request;
    $obj['agent']= ''.$obj['agent'];


    try {

      $obj['updator']=$this->userinfo['name'];

      if ($obj['id']){
        $temp = WeixinOaFlowrole::findOne($obj['id']);
        if ($temp && $temp->userid != $obj['userid']) {
          return array('errorMessage' => '禁止更新：userid不能变更');
        }
   
        WeixinOaFlowrole::updateAll($obj, ['id'=>$obj['id']]);
      } else {
        $obj['creator']= $this->userinfo['userid'];
        $c= new WeixinOaFlowrole($obj);
        $c->upatetime=date('Y-m-d H:i:s');
        $c->save();

      }
    } catch (\Throwable $th) {

      return array('errorMessage'=>$th->getMessage());
    }

    $resp['data'] =$c;
    return $resp;
  }
    // ********************************  角色模块 *************************************
  public function actionGetrolelist(){

    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
  
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['>', 'r.id', 0]
    ];
    
    if ($this->_request['username']) {
      $where[] = ['LIKE', 'r.username', $this->_request['username']];
    }
    if ($this->_request['userid']) {
      $where[] = ['=', 'r.userid', $this->_request['userid']];
    }
    if ($this->_request['role']) {
      $where[] = ['=', 'r.role', $this->_request['role']];
    }
    if ($this->_request['power']){
      $where[]= new Expression("r.role in (select  id from ".WeixinOaRole::tableName()." where FIND_IN_SET(".$this->_request['power'].",power)  )");
    }
    if ($this->_request['agentid']){

      $where[] = new Expression("FIND_IN_SET(".$this->_request['agentid'].", r.agent)");
    }
    if ($this->_request['dept']){
      $dept = $this->_request['dept'];
      $where[] = new Expression("FIND_IN_SET($dept, r.dept)");
    }
    
    $model = WeixinOaFlowrole::find()->alias('r')->select('r.*,d.rolename,d.powername,d.power')->leftJoin(['d'=>WeixinOaRole::tableName()],'d.id=r.role')->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('r.id desc')->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }
  public function actionSaverole(){
    

    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }
    $resp = array('errorMessage'=>'');
    $obj = $this->_request;

  
    // 判断是否已经存在
    if (!$obj['rolename']) return array('errorMessage'=>'rolename不能为空');
  
    
    try {
    
      if ($obj['id']){ 
        unset($obj['agentid']);
        WeixinOaRole::updateAll($obj,['id'=>$obj['id']]);
      } else {
        $res = WeixinOaRole::find()->where(['and',['=','rolename',$obj['rolename']]])->one();
        if ($res) {
          $agentid = $res['agentid']?($res['agentid'].','.$obj['agentid']):$obj['agentid'];
          $obj['agentid']= $agentid;
          WeixinOaRole::updateAll($obj,['id'=>$res['id']]);
          $obj['id'] = $res['id'];
          return array('data'=>$obj);
        }
        $c=new WeixinOaRole($obj);
        $c->save();
      }
    } catch (\Throwable $th) {
      
      return array('errorMessage'=>$th->getMessage());
    }
    
    $resp['data'] =$obj;
    return $resp;
  }
  public function actionGetrole(){
    $type = $this->_request['type'];
    $agentid = $this->_request['agentid'];
    $where = [
        'and',
        ['>', 'id', 0],
    ];
    
    // if ($agentid){
    //   $where[]=new Expression("FIND_IN_SET($agentid,agentid)");
    // } else if ($type){
    //   $where[]=['=','type',$type];
    // }
    $res = WeixinOaRole::find()->where($where)->asArray()->all();
    return $res; 
  }
  private function hasRole($rolename,$dept){
    if($this->_adminInfo['usertype']==1) return true;
    $deptsql = '';
    if ($dept)  $deptsql ="and  FIND_IN_SET($dept, dept)";
    $model = WeixinOaFlowrole::findBySql("SELECT * from weixin_oa_flowrole where userid='".$this->_adminInfo['wxuserid']."' $deptsql and role in (select id from weixin_oa_role where rolename='$rolename')")->one();
    
    return $model?true:false;
  
  }
  private function getUserinfo($userid)
  {
      $userinfo = WeixinOAUserInfo::find()->where(['=', 'userid', $userid])->asArray()->one();
      return $userinfo;
  }

 
  // **********************************流程设置***************************************
  public function actionTemplatelist()
  {

      $role = $this->hasRole('流程设置','');
      if ($this->_adminInfo['usertype']||$role||$this->_adminInfo['wxuserid']=='linmaosheng'){
        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        
        $notifyAttr = isset($this->_request['notifyAttr']) ? $this->_request['notifyAttr'] : 0;
        $templateId = isset($this->_request['templateId']) ? $this->_request['templateId'] : '';
        $templateName = isset($this->_request['templateName']) ? $this->_request['templateName'] : '';
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        $appid = $this->appids;
        if ($this->_request['appid']){

        
          if (in_array($this->_request['appid'],$appid)){
            $appid = [$this->_request['appid']];
          }
        }
        if ($appid){ 
          $where[] = ['in', 'appid', $appid];
        }

        if($notifyAttr){
            $where[] = ['=', 'notifyAttr', $notifyAttr];
        }
        if($templateId){
            $where[] = ['=', 'templateId', $templateId];
        }
        if($templateName){
            $where[] = ['like', 'templateName', $templateName];
        }
        $qyapp = WeixinQYAppInterface::find()->where(['>','id',0])->asArray()->all();
        $qyappArr = [];
        foreach($qyapp as $row){
            $qyappArr[$row['appid']] = $row['appname'];
        }
        $role = WeixinOaRole::find()->where(['>','id',0])->asArray()->all();
        $roleArr = [];
        foreach($role as $row){
            $roleArr[$row['id']] = $row['rolename'];
        }
        $model = $this->modelClass;
        $model = $model::find()->where($where);
        $total = $model->count();
        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->asArray()->all();

        $data = [];
        foreach($res as $row){
            $item = $row;
            $item['templateData'] = json_decode($row['templateData'],true);
            $approval = [];
            if ($item['templateData']['approval']){
              foreach($item['templateData']['approval'] as  $k=>$r){
                  $_aitem = $r;
                  $_aitem['key'] = $k+1;
                  $_aitem['title'] = $this->getTitle($_aitem,$roleArr);
                  if($r['type']==1){
                      $uinfo = $this->getUserinfo($r['id'],'id');
                      $_aitem['userid'] = $uinfo['userid'];
                  }
                  $approval[] = $_aitem;
              }
            }
            
            $item['templateData']['approval'] = $approval;
            $notify = [];
            if ($item['templateData']['notify']){
              foreach($item['templateData']['notify'] as  $k=>$r){
                  $_nitem = $r;
                  $_nitem['key'] = $k+1;
                  $_nitem['title'] = $this->getTitle($_nitem,$roleArr);
                  if($r['type']==1){
                      $uinfo = $this->getUserinfo($r['id'],'id');
                      $_nitem['userid'] = $uinfo['userid'];
                  }
                  $notify[] = $_nitem;
              }
            }
            
            $item['templateData']['notify'] = $notify;
            $data[] = $item;
        }
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        $this->_result['data'] = $data;
        
        return $this->_result;
      }
      
  }
  /**
   * 重写create的业务实现动作
   */
  public function actionCreatetemplate()
  {
      if ($this->_request['values']) {
          $model = new $this->modelClass;
          $values = $this->_request['values'];
          $values['templateData'] = json_encode($values['templateData']);
          $row = WeixinQYAppInterface::find()->where(['=','appid',$values['appid']])->one();
          $values['appname'] = $row['appname'];
          $values['templateId'] = md5($values['templateName'].time()).'_'.time();
          $model->attributes = $values;
          $ruleResult = Tools::modelRules($model, 4000);
          if ($ruleResult === true) {
              if ($model->save()) {
                  $this->_result['lastid'] = $model->id;
                  $action = '[流程模板管理]新增';
                  $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，流程模板。ID：".$model->id."名称：" . $model->templateName . '。';
                  $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
              }
          } else {
              $this->_result['errorCode'] = $ruleResult['errorCode'];
              $this->_result['errorMessage'] = $ruleResult['errorMessage'];
          }
      } else {
          $this->_result = Tools::wrongRules(1000, '参数错误');
      }
      return $this->_result;
  }
    //获取所有角色类别
  public function actionGetqyapp()
  {    
      $res = $this->modelClass::find()->select('appid,appname')->where(['>','id',0])->groupBy('appid,appname')->all();
      $data = [];
      foreach ($res as $row) {
          if($row->appid){
              $node = ['title' => $row->appname, 'key' => strval($row->appid), 'isLeaf' => true];
              $data[] = $node;
          }
      }
      $this->_result['data'] = $data;
      return $this->_result;
  }
  public function actionGetdict()
  {    
      $_Tmp = [];
      foreach($this->_notifyAttr as $k=>$v){
          $_Tmp[$k]=['text'=>$v];
      }
      $data['notifyAttr'] = $_Tmp;
      $_Tmp = [];  
      foreach($this->_approvalAttr as $k=>$v){
          $_Tmp[$k]=['text'=>$v];
      }
      $data['approvalAttr'] = $_Tmp;
      $_Tmp = [];
      foreach($this->_levelname as $k=>$v){
          $_Tmp[$k]=['text'=>$v];
      }
      $data['level'] = $_Tmp;
      $_Tmp = [];
      $res = WeixinQYAppInterface::find()->where(['>','id',0])->all();
      foreach ($res as $row) {
          $_Tmp[$row->appid] = ['text'=>$row->appname];
      }
      $data['app'] = $_Tmp;
      $_Tmp = [];
      $res = WeixinOaRole::find()->where(['>','id',0])->all();
      foreach ($res as $row) {
          $_Tmp[$row->id] = ['text'=>$row->rolename];
      }
      $data['role'] = $_Tmp;
      $_Tmp = [];
      $res = WeixinOaUsertag::find()->where(['>','id',0])->all();
      foreach ($res as $row) {
          $_Tmp[$row->id] = ['text'=>$row->tagName];
      }
      $data['tag'] = $_Tmp;
      $_Tmp = [];
      $res = WeixinOaUserinfo::find()->where(['and',['=','status',1],['=','st',1]])->all();
      foreach ($res as $row) {
          $_Tmp[$row->userid] = ['id'=>$row->id,'text'=>$row->name];
      }
      $data['user'] = $_Tmp;
      $this->_result['data'] = $data;
      return $this->_result;
  }
  public function actionGettag(){
    $res = WeixinOaUsertag::find()->where(['>','id',0])->all();
    return $res;
  }
    /**
     * 重写update的业务实现动作
     */
  public function actionUpdatetemplate()
  {
      $id = intval($this->_request['id']);
      if ($id) {
          $model = $this->modelClass::findOne($id);
          $values = $this->_request['values'];
          $values['templateData'] = json_encode($values['templateData']);
          $row = WeixinQYAppInterface::find()->where(['=','appid',$values['appid']])->one();
          $values['appname'] = $row['appname'];
          $values['updatetor'] = $this->userinfo['name'];
          $model->attributes = $values;
          
          $ruleResult = Tools::modelRules($model, 4001);
          if ($ruleResult === true) {
              if ($model->save()) {
                  $action = '[流程模板管理]修改';
                  $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，流程模板。ID：".$model->id."名称：" . $model->templateName . '。';
                  $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
              }
          } else {
              $this->_result['errorCode'] = $ruleResult['errorCode'];
              $this->_result['errorMessage'] = $ruleResult['errorMessage'];
          }
      } else {
          $this->_result = Tools::wrongRules(1000, '参数错误');
      }
      return $this->_result;
  }
  public function actionDeltemplate(){
    $id = $this->_request['id'];
    if (!$id){
      return array('errorMessage'=>'id不能为空');
    }
    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }
    $p = WeixinOaTemplates::findOne($id);
    
    if (!$p){
      return array('errorMessage'=>'');
    }

    $p->delete();
    return array('data'=>$p);
  }
  /**
   * 生成审批人标题
   */
  private function getTitle($data,$role)
  {
      switch($data['type'])
      {
          case 0:
          case 5:
              return $role[$data['role']];
              break;
          case 1:
              return $data['uname'];
              break;
          case 2:
              return $data['tag'].($data['attr']?'（'.$this->_approvalAttr[$data['attr']].'）':'');
              break;
          case 3:
              return $this->_levelname[$data['level']].($data['attr']?'（'.$this->_approvalAttr[$data['attr']].'）':'');
              break;
          case 4:
              return $role[$data['nrole']];
              break;
          case 6:
              return '手动选择';
              break;
          case 7:
              return $role[5];
              break;
          case 8:
              return '主体负责人';
              break;
          case 9:
              return $role[10];
              break;
      }
  }

    // ***************************** 财务付款审批流程 *********************************
  public function actionFinancetemplatelist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
  
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['>', 't.id', 0],
    ];
  
    if ($this->_request['keyword']) {
      $where[] = ['or',['like','t.templatename',$this->_request['keyword']],['=','t.templateid',$this->_request['keyword']]];
    }
    if ($this->_request['type']) {
      $where[] = ['=','t.type',$this->_request['type']];
    }

    
    if ($this->_request['dept']){
      $dept = $this->_request['dept'];
      $where[] = new Expression("FIND_IN_SET($dept, dids)");
    }
    if ($this->_request['userid']){
      $userid = $this->_request['userid'];
      $where[] = new Expression("FIND_IN_SET($userid, uids)");
    }
    
    $model = WeixinFinanceTemplate::find()->alias('t')->select('t.*,wt.templateName as tname')->leftJoin(['wt'=>WeixinOaTemplates::tableName()],'wt.templateId=t.templateid')->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('id desc')->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }
  

  

  public function actionSavefinanceflow(){

    $obj = $this->_request;

    $p = new WeixinFinanceTemplate($obj);
    
    try {
      if ($p['id']){
        $obj['updator']= $this->userinfo['name'];
        $p->updateAll($obj,['id'=>$p['id']]);
      } else {
        $p->creatorname= $this->userinfo['name'];
        $p->creator= $this->userinfo['userid'];
        $p->agentid= 1000066;
        $p->save();
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$p);
  }

  public function actionDelfinanceflow(){
    $id = $this->_request['id'];
    if (!$id){
      return array('errorMessage'=>'id不能为空');
    }
    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }
    $p = WeixinFinanceTemplate::findOne($id);
 
    
    if (!$p){
      return array('errorMessage'=>'');
    }
    $p->delete();
    return array('data'=>$p);
  }
  
  public function actionSavepayer(){

    $obj = $this->_request;
    

    $p = new WeixinFinanceCompany($obj);
    
    try {
      if ($p['id']){
        $obj['updator']= $this->userinfo['name'];
        $obj['updatetime']= date('Y-m-d H:i:s');
        $obj['ctype']= $obj['crossdept']?1:0;
        if(!$obj['crossdept']) !$obj['crossdept']='';
        $p->updateAll($obj,['id'=>$p['id']]);
      } else {
        // company不能重复
        $temp = WeixinFinanceCompany::find()->where(['company'=>$obj['company']])->one();
        if ($temp){
          return array('errorMessage'=>'付款单位已存在');
        }
        $p->ctype = $p->crossdept?1:0;
        $p->updator= $this->userinfo['name'];
        $p->creator= $this->userinfo['userid'];
        $p->creatorname= $this->userinfo['name'];
        $p->save();
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$p);
  }

  private function applydata($condition){
    if(!$condition['userid']){
      throw new Exception('userid不能为空');
    }
    $userinfo = WeixinOAUserInfo::find()->where(['=','userid',$condition['userid']])->asArray()->one();
    $condition['departmentid']=$userinfo['departmentid'];
    if ($condition['partbname']){
      $company = WeixinFinanceCompany::find()->select('id')->where(['=','company',$condition['partbname']])->asArray()->one();
      if ($company){
        $condition['company']=$company['id'];
      }
    }
    
    
    $result = $this->generateApplydata($userinfo,$condition);
    

    return $result;
  
  }
  private function getOrderTemplateid($data,$userinfo){
        $userid = $userinfo['id'];
        $departmentid = $data['departmentid']?$data['departmentid']:$userinfo['departmentid'];
      
        
        $where = ['and',['>','id',0]];
        // if ($data['publicationid']){
        //   $where[] = ['=','publicationid',$data['publicationid']];
        // }
        $order = "id desc";
        
        // 优先级1: 同时匹配部门和用户
        $result = WeixinOrderTemplate::find()
            ->where($where)
            ->andWhere(['and',
                new Expression('FIND_IN_SET('.$departmentid.',dids)'),
                new Expression("FIND_IN_SET('".$userid."',uids)")
            ])
            ->asArray()
            ->orderBy($order)
            ->one();
            
        if (!$result){
            // 优先级2: 只匹配用户
            $result = WeixinOrderTemplate::find()
                ->where($where)
                ->andWhere(new Expression("FIND_IN_SET('".$userid."',uids)"))
                ->asArray()
                ->orderBy($order)
                ->one();
        }
        if (!$result){
            // 优先级3: 只匹配部门
            $result = WeixinOrderTemplate::find()
                ->where($where)
                ->andWhere(new Expression('FIND_IN_SET('.$departmentid.',dids)'))
                ->asArray()
                ->orderBy($order)
                ->one();
        }
        if (!$result){
            // 优先级4: 通用模板
            $result = WeixinOrderTemplate::find()
                ->where($where)
                ->asArray()
                ->orderBy($order)
                ->one();
        }
    
        return $result?$result['templateid']:null;
    }
  private function getAttendatanceTemplate($data,$userinfo){
    $data['expire']=0;
        if($userinfo) {
            $where = ' and agentid='.$data['agentid'].' and isdel=0 and expire='.$data['expire'];
            // 根据用户
			$template = Yii::$app->db->createCommand("select * from ".WeixinAttendanceTemplate::tableName()." where FIND_IN_SET('".$userinfo['id']."',uids) ".$where)->queryOne();
			if($template){
				return $template;
			}
            // 根据部门和职级
            $sql = "select * from ".WeixinAttendanceTemplate::tableName()." where FIND_IN_SET('".$userinfo['level']."',type) and  FIND_IN_SET('".$userinfo['departmentid']."',dids) ".$where;
			$template = Yii::$app->db->createCommand($sql)->queryOne();
			if($template){
				return $template;
			}
            // 根据部门
			$sql = "select * from ".WeixinAttendanceTemplate::tableName()." where type='' and  FIND_IN_SET('".$userinfo['departmentid']."',dids) ".$where;
			$template = Yii::$app->db->createCommand($sql)->queryOne();
			if($template){
			
				return $template;
			}
        }
        return null;
  }
  private function gettemplateid($condition,$userinfo){
    $result=null;
    switch ($condition['agentid']) {
      case 1000067:
        
        $result=$this->getAttendatanceTemplate($condition, $userinfo);	
        break;
      case 1000083:
       
        $result=$this->getOrderTemplateid($condition, $userinfo);	
        break;
      
      default:
        
        break;
    }
    // 如果$result是字符串类型，则返回
    if (is_string($result)){
      return $result;
    }
    return $result?$result['templateid']:null;
  }
  private function generateApplydata($userinfo,$condition){
      
    $condition['roleToUserAll']=true; // 每个角色如果有多人就全部返回
    // 查询流程id
    
    $templateid = $this->gettemplateid($condition,$userinfo);
    

    if (!$templateid) {
      throw new Exception('流程未设置');
    }

    // 生成流程数据
    $wfp = new WorkflowParse($condition['agentid']);
  
    $flowdata = $wfp->flowParse($userinfo['userid'], $templateid,$condition);
    
    if (!$flowdata){
      throw new Exception($templateid.' 解析后审批节点为空，请联系管理员');
    }

    // 完整流程信息
    $flow = array(
      'errcode' => 0,
      'errmsg' => 'ok',
      'data' => array(
        'ThirdNo' => '',
        'OpenTemplateId' => $flowdata['OpenTemplateId'],
        'OpenSpName' => $flowdata['OpenSpName'],
        'OpenSpstatus' => 1,
        'ApplyTime' => time(),
        'ApplyUsername' => $userinfo['name'],
        'ApplyUserParty' => '',
        'ApplyUserImage' => $userinfo['avatar'],
        'ApplyUserId' => $userinfo['userid'],
        'ApprovalNodes' => $flowdata['ApprovalNodes'],
        'NotifyNodes' => $flowdata['NotifyNodes'],
        'approverstep' => 0
      )
    );

    $applydata = array(
      'agentid' => $condition['agentid'],
      'thirdNo' => '',
      'data' => json_encode($flow),
      'step' => 0,
      'status' => 1,
      'notifyAttr' => $flowdata['NotifyAttr']
    );

    $approvalUserid = array();
    $approvalUsername = array();
   
    if ($flowdata['ApprovalNodes']['ApprovalNode'][0]['Items']['Item']){
      foreach ($flowdata['ApprovalNodes']['ApprovalNode'][0]['Items']['Item'] as $item) {
        $approvalUserid[] = $item['ItemUserId'];
        $approvalUsername[] = $item['ItemName'];
      }
    }
    
    
    return array('templateid'=>$templateid,'approvalUserid'=>implode('|',$approvalUserid),'approvalUsername'=>implode('|',$approvalUsername),'applydata'=>$applydata,'flow'=>$flow);
  }
  public function actionGetcommonflow(){
    // agentid 不能为空
    if (!$this->_request['agentid']){
      return array('errorMessage'=>'agentid 不能为空');
    }
    try {
      $approvedata = $this->applydata($this->_request);
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }

    
    $flow = $approvedata['flow'];
    if($flow){
      $approvearr = $flow;
      if($approvearr['data']['ApprovalNodes']['ApprovalNode']){
        foreach ($approvearr['data']['ApprovalNodes']['ApprovalNode'] as $k=>$r) {
          $tmparr = array();
          if(count($r['Items']['Item'])>1){
            $tmparr['title'] = '直接上级';
            if ($r['NodeType']==2 && isset($r['NodeTagid'])){
              $tmparr['title']=$this->getTagName($r['NodeTagid']);
            }else if ($r['NodeType']==0 && isset($r['NodeRoleid'])){
                $tmparr['title'] = $this->getRoleName($r['NodeRoleid']);
            }
            $tmparr['avatar'] = 'https://fzrb.fznews.com.cn/assets/oa/images/approvaltag.png';
            foreach ($r['Items']['Item'] as $key => $value) {
                $itemarr = array();
                $itemarr['title'] = $value['ItemName'];
                $itemarr['date'] = intval($value['ItemOpTime']) > 0?date('m/d',$value['ItemOpTime']):'';
                $itemarr['avatar'] = $value['ItemImage'];
                $itemarr['speech'] = $value['ItemSpeech'];
                $itemarr['status'] = $value['ItemStatus'];
                $tmparr['items'][] = $itemarr;
            }
          }else{
              $tmparr['title'] = $r['Items']['Item'][0]['ItemName'];
              $tmparr['date'] = $r['Items']['Item'][0]['ItemOpTime']?date('m/d',$r['Items']['Item'][0]['ItemOpTime']):'';
              $tmparr['avatar'] = $r['Items']['Item'][0]['ItemImage'];
              $tmparr['speech'] = $r['Items']['Item'][0]['ItemSpeech'];
              $tmparr['status'] = $r['NodeStatus'];
              $tmparr['items'] = '';
          }
          $approvaldata[] = $tmparr;
        }
      }
      
      $notifier = array();
   
      if ($approvearr['data']['NotifyNodes']['NotifyNode']){
        foreach ($approvearr['data']['NotifyNodes']['NotifyNode'] as $r) {
          $notifier[] = $r['ItemName'];
        }
      }
      
      $step = intval($approvearr['data']['approverstep'])-1;

    }
    $tt = WeixinOaTemplates::find()->where(['templateId'=>$approvedata['templateid']])->one();
      if ($tt) $templatename=$tt['templateName'];
    return  array('flow'=>$approvedata['flow'],'viewdata'=>array('step'=>$step,'approval'=>$approvaldata,'notify'=>$notifier,'templatename'=>$templatename,'templateid'=>$approvedata['templateid']),'invoicers'=>$approvedata['invoicers'],'statusCn'=>$this->statusCn);
  }
  private function getRoleName($id)
    {
        $tagdata = '审批组';
        if($id){
            $temp = WeixinOaRole::findOne($id);
            if ($temp) $tagdata = $temp['rolename'];
        }      
        return $tagdata;
    }
  // 根据单号查询审批数据
  public function actionGetflowdata(){
    $thirdNo = $this->_request['thirdNo'];
    if (!$thirdNo){
      return array('errorMessage'=>'thirdNo不能为空');
    }
    $agentid = $this->_request['agentid'];
  
    $viewdata=0;

    $wfp = new WorkflowParse();
    try {
      switch ($agentid) {
        case 1000063:
          $temp = WeixinFlowApprovaldata::find()->where(['thirdNo'=>$thirdNo])->all();
          break;
        
        default:
          $temp = WeixinOaApprovaldata::find()->where(['thirdNo'=>$thirdNo])->all();
          break;
      }
      
      if (!$temp){
        return array('errorMessage'=>'无此单号');
      }
      if (sizeof($temp)>1){
        return array('errorMessage'=>'单号重复');
      }
      $agentid = $temp[0]['agentid'];
      $wfp = new WorkflowParse($agentid);
      $viewdata = $wfp->flowViewdata($thirdNo);


    } catch (\Throwable $th) {
      return array('errorMessage'=> $th->getMessage());
    }
    
    return array('viewdata'=>$viewdata,'statusCn'=>$this->statusCn,'thirdNo'=>$thirdNo,'agentid'=>$agentid);

  }

  public function actionPayerlist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
  
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['>', 'p.id', 0],
    ];
  
    if ($this->_request['keyword']) {
      $where[] = ['or',['like','company',$this->_request['keyword']],['like','p.username',$this->_request['keyword']]];
    }

    
    if ($this->_request['dept']){
      $dept = $this->_request['dept'];
      $where[] = new Expression("FIND_IN_SET($dept, p.dept)");
    }
    if ($this->_request['userid']){
      $userid = $this->_request['userid'];
      $where[] = new Expression("FIND_IN_SET($userid, p.userid)");
    }
    
    $model = WeixinFinanceCompany::find()->alias('p')->select('p.*,d.name as crossdeptname')
      ->leftJoin(['d'=>WeixinOaDepartment::tableName()],"d.id=p.crossdept")
      ->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('p.id desc')->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }
  public function actionDelpayer(){ 
    $id = $this->_request['id'];
    if (!$id){
      return array('errorMessage'=>'id不能为空');
    }
    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }
    $p = WeixinFinanceCompany::findOne($id);
    
    if (!$p){
      return array('errorMessage'=>'');
    }
    $p->delete();
    return array('data'=>$p);
  }
  public function actionFlowalter(){
  
		$thirdNo = $this->_request['thirdNo'];
		$step = $this->_request['step'];
		$userid = $this->_request['userid'];
    $agentid = $this->_request['agentid'];
    if (!$agentid){
      return array('errorMessage'=>'agentid 不能为空');
    }
    if (!$userid){
      return array('errorMessage'=>"userid 不能为空");
    }
		if (!$thirdNo){
      return array('errorMessage'=>'thirdNo 不能为空');
		}
		if(!isset($step)){
      return array('errorMessage'=>'step 不能为空');
		}
		$user = $this->getUserinfo($userid);	//获取用户信息
		if (!$user){
      return array('errorMessage'=>'userid：['.$userid.']不存在');
		}
    switch ($agentid) {
      case 1000066:
        $data = WeixinFinanceInfo::find()->where(['and',['=','thirdNo',$thirdNo]])->one();
        break;
      default:
        $data = WeixinOaApprovalInfo::find()->where(['and',['=','thirdNo',$thirdNo]])->one();
        break;
    }
    if (!$data){
      return array('errorMessage'=>'无此单号');
    }
    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }
 

    $flow = WeixinOaApprovaldata::find()->where(['agentid'=>$agentid,'thirdNo'=>$thirdNo])->one();
		$flowdata = json_decode($flow['data'],true);
		$curstep = $flow['step'];
		$node = $flowdata['data']['ApprovalNodes']['ApprovalNode'][$step];
		$item = array(
			'ItemName' => $user['name'],
			'ItemParty' => '',
			'ItemImage' => $user['avatar'],
			'ItemUserId' => $user['userid'],
			'ItemStatus' => 1,
			'ItemSpeech' => '',
			'ItemOpTime' => 0
		);
		$node['Items']['Item'] = array($item);
    $curuser = $this->getUserinfo($this->_adminInfo['wxuserid']);
    // 修改人
    $node['FromUserid'] = $curuser['userid'];
    $node['FromUsername'] = $curuser['name'];

		$flowdata['data']['ApprovalNodes']['ApprovalNode'][$step]=$node;
		$flow['data'] = json_encode($flowdata);
    
    $transaction = Yii::$app->db->beginTransaction();
    try {
      $flow->save();
      // 判断是否是当前审批步骤
      if ($curstep==$step){
        // 修改当前审批人

        $data->approvalUserid = $user['userid'];
        $data->approvalUsername = $user['name'];
        $data->status = 1;
        $data->save();
        // WeixinFinanceInfo::updateAll($data,['id'=>$data['id']]);

      }
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=> $th->getMessage());
    }
    $transaction->commit();

		
		return array('ret'=>1);

  }
  public function actionFlowalteritem(){
    $thirdNo = $this->_request['thirdNo'];
    $step = $this->_request['step'];
    $itemIndex = $this->_request['itemIndex'];
    $userid = $this->_request['userid'];
    $agentid = $this->_request['agentid'];

    if (!$agentid){
      return array('errorMessage'=>'agentid 不能为空');
    }
    if (!$userid){
      return array('errorMessage'=>"userid 不能为空");
    }
    if (!$thirdNo){
      return array('errorMessage'=>'thirdNo 不能为空');
    }
    if (!isset($step)){
      return array('errorMessage'=>'step 不能为空');
    }
    if (!isset($itemIndex)){
      return array('errorMessage'=>'itemIndex 不能为空');
    }

    $user = $this->getUserinfo($userid);
    if (!$user){
      return array('errorMessage'=>'userid：['.$userid.']不存在');
    }

    switch ($agentid) {
      case 1000066:
        $data = WeixinFinanceInfo::find()->where(['and',['=','thirdNo',$thirdNo]])->one();
        break;
      default:
        $data = WeixinOaApprovalInfo::find()->where(['and',['=','thirdNo',$thirdNo]])->one();
        break;
    }
    if (!$data){
      return array('errorMessage'=>'无此单号');
    }
    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }

    $flow = WeixinOaApprovaldata::find()->where(['agentid'=>$agentid,'thirdNo'=>$thirdNo])->one();
    $flowdata = json_decode($flow['data'],true);
    $curstep = $flow['step'];
    $node = $flowdata['data']['ApprovalNodes']['ApprovalNode'][$step];

    if (!isset($node['Items']['Item'][$itemIndex])){
      return array('errorMessage'=>'itemIndex：['.$itemIndex.']不存在');
    }

    $curuser = $this->getUserinfo($this->_adminInfo['wxuserid']);
    $node['Items']['Item'][$itemIndex] = array(
      'ItemName' => $user['name'],
      'ItemParty' => '',
      'ItemImage' => $user['avatar'],
      'ItemUserId' => $user['userid'],
      'ItemStatus' => 1,
      'ItemSpeech' => '',
      'ItemOpTime' => 0
    );
    $node['FromUserid'] = $curuser['userid'];
    $node['FromUsername'] = $curuser['name'];

    $flowdata['data']['ApprovalNodes']['ApprovalNode'][$step] = $node;
    $flow['data'] = json_encode($flowdata);

          $transaction = Yii::$app->db->beginTransaction();
    try {
      $flow->save();
      if ($curstep == $step) {
        $items = $node['Items']['Item'];
        $approverUserids = array_column($items, 'ItemUserId');
        $approverNames = array_column($items, 'ItemName');
        $data->approvalUserid = implode('|', $approverUserids);
        $data->approvalUsername = implode('|', $approverNames);
        $data->status = 1;
        $data->save();
      }
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=> $th->getMessage());
    }
    $transaction->commit();
    return array('ret'=>1);
  }
  public function actionAlterspeech(){
    $thirdNo = $this->_request['thirdNo'];
		$step = $this->_request['step'];
		$speech = $this->_request['speech'];
    if (!$thirdNo){
      return array('errorMessage'=>"thirdNo 不能为空");
		}
		if($step!=0&&!$step){
			return array('errorMessage'=>"step 不能为空");
		}

    
    try {
      $speech = urldecode($speech);
      $flow = WeixinOaApprovaldata::findBySql("select * from ".WeixinOaApprovaldata::tableName()." where thirdNo ='$thirdNo'")->one();
      $flowdata = json_decode($flow['data'],true);
      $node = $flowdata['data']['ApprovalNodes']['ApprovalNode'][$step];

      if ($node['Items']['Item']&&count($node['Items']['Item'])>0) {
        
        if (count($node['Items']['Item'])==1){
            $node['Items']['Item'][0]['ItemSpeech'] = $speech;
        }else{
          $node['speech']=$speech;
        }
   
        $flowdata['data']['ApprovalNodes']['ApprovalNode'][$step]=$node;
        $flow['data'] = json_encode($flowdata);
        WeixinOaApprovaldata::updateAll($flow,array('id'=>$flow['id']));
        

      } else {

        return array('errorMessage'=>'审批节点['.$step.']为空！');
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    
     return array('errorMessage'=>'');
  }
  public function actionFlowback(){
    

    $thirdNo = $this->_request['thirdNo'];
    if (!$thirdNo){
      return array('errorMessage'=>'thirdNo不能为空');
    }
    $agentid = $this->_request['agentid'];
    switch ($agentid) {
      case 1000063:
        $data = FznewsFlowProcess::find()->where(['and',['=','processInstanceId',$thirdNo]])->one();
        break;
      case 1000066:
        $data = WeixinFinanceInfo::find()->where(['and',['=','thirdNo',$thirdNo]])->one();
        break;
      default:
         $data = WeixinOaApprovalInfo::find()->where(['and',['=','thirdNo',$thirdNo]])->one();
        break;
    }

   
    if(!$data){
      return array('errorMessage'=>'WeixinOaApprovalInfo表无此单号');
    }

    
    switch ($agentid) {
      case 1000063:
         $flow = WeixinFlowApprovaldata::find()->where(['and',['=','thirdNo',$thirdNo]])->one();
         $curstep = $flow['step']-1;
         $newstep = $flow['step']-2;
        break;
      
      default:
         $flow = WeixinOaApprovaldata::find()->where(['thirdNo'=>$thirdNo])->one();
         $curstep = $flow['step'];
         $newstep = $flow['step']-1;
        break;
    }
    if(!$flow){
      return array('errorMessage'=>'WeixinOaApprovaldata表无此单号');
    }
    $agent = $flow['agentid'];
    
    $hasauth = $this->hasRole('流程设置','');
    if ($data['userId']!=$this->_adminInfo['wxuserid']) {
      // 允许流程创建人返回
      if (!$hasauth) return array('errorMessage'=>'只有流程发起人才能返回上一步');
      
    }
   
    
    
    if($flow['step']==0){
      return array('errorMessage'=>'已经是第一步了，无法再回退');
    }
    
    
    
		$flowdata = json_decode($flow['data'],true);
    // 如果已经审批通过，禁止回退
 
    switch ($agentid) {
      case 1000063:
         $flow = WeixinFlowApprovaldata::find()->where(['and',['=','thirdNo',$thirdNo]])->one();
        break;
      
      default:
         $flow = WeixinOaApprovaldata::find()->where(['thirdNo'=>$thirdNo])->one();
        break;
    }
    if($flowdata['data']['OpenSpstatus']!=1&&$agentid!=1000063){
      return array('errorMessage'=>'只有在审批阶段的流程才能撤回');
    }
   
    // 更新当前节点为未审批状态
		
    switch ($agentid) {
      case 1000063:
        $node = $flowdata['data']['ApprovalNodes'][$curstep];
        break;
      
      default:
         $node = $flowdata['data']['ApprovalNodes']['ApprovalNode'][$curstep];
         unset($node['offline']);
         unset($node['fileurls']);
         unset($node['speech']);
         unset($node['date']);
        break;
    }
 
    for ($i=0; $i < sizeof($node['Items']['Item']); $i++) { 
      $node['Items']['Item'][$i]=array(
        'ItemName' => $node['Items']['Item'][$i]['ItemName'],
        'ItemImage' => $node['Items']['Item'][$i]['ItemImage'],
        'ItemUserId' => $node['Items']['Item'][$i]['ItemUserId'],
        'ItemStatus' => 1,
        'ItemOpTime' => 0
        );
    }
    

    
    switch ($agentid) {
      case 1000063:
        $newnode = $flowdata['data']['ApprovalNodes'][$newstep];
        break;
      
      default:
         $newnode = $flowdata['data']['ApprovalNodes']['ApprovalNode'][$newstep];
        
         unset($newnode['fileurls']);
         unset($newnode['speech']);
         unset($newnode['date']);
        break;
    }
    $newnode['NodeStatus']=1;
    unset($newnode['next']);
    $useridarr=[];
    $usernamearr=[];
    for ($i=0; $i < sizeof($newnode['Items']['Item']); $i++) {
      $useridarr[]=$newnode['Items']['Item'][$i]['ItemUserId'];
      $usernamearr[]=$newnode['Items']['Item'][$i]['ItemName'];
      $newnode['Items']['Item'][$i]=array(
        'ItemName' => $newnode['Items']['Item'][$i]['ItemName'],
        'ItemImage' => $newnode['Items']['Item'][$i]['ItemImage'],
        'ItemUserId' => $newnode['Items']['Item'][$i]['ItemUserId'],
        'ItemStatus' => 1,
        'ItemOpTime' => 0
        );
    }
    // 非报项目，当前节点offline=1,意味着需要经办上传线下文件,当前审批人修改成经办人
    $offline=0;
    if ($newnode['offline']){
      $useridarr=[$data['userId']];
      $usernamearr=[$data['userName']];
      $offline=1;
    }

    switch ($agentid) {
      case 1000063:
        $flowdata['data']['ApprovalNodes'][$curstep]=$node;
        $flowdata['data']['ApprovalNodes'][$newstep]=$newnode;
        break;
      
      default:
         $flowdata['data']['ApprovalNodes']['ApprovalNode'][$curstep]=$node;
         $flowdata['data']['ApprovalNodes']['ApprovalNode'][$newstep]=$newnode;
        break;



    }
    
    
    $transaction = Yii::$app->db->beginTransaction();
    try {

       
      
      
      
      switch (''.$flow['agentid']) {
         case 1000063:
          $newstep++;
          $data['candidate'] = implode('|',$useridarr);
          $data['candidatename'] = implode('|',$usernamearr);
          $data['step']=$newstep;
          $flowdata['data']['Approverstep']=$newstep;
          break;
        
        default:
          $data['approvalUserid'] = implode('|',$useridarr);
          $data['approvalUsername'] = implode('|',$usernamearr);
          $flowdata['data']['approverstep']=$newstep;
          break;
      }
      
      
      $flow['step']=$newstep;
      $flow['data'] = json_encode($flowdata);

      
      $flow->save();
      $data->save();

      
      switch (''.$flow['agentid']) {
        case '1000080': // 非报项目
          FzrbsBudgetProject::updateAll(['offline'=>$offline,'offlinenote'=>''],['thirdNo'=>$thirdNo]);
          break;

        
        default:
          # code...
          break;
      }
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=> $th->getMessage());
    }
    $transaction->commit();
    return array('errorMessage'=>'');
  }
  public function actionFlowbackend(){
    $thirdNo = $this->_request['thirdNo'];
    if (!$thirdNo){
      return array('errorMessage'=>'thirdNo不能为空');
    }
    $agentid = $this->_request['agentid'];
    switch ($agentid) {
      case 1000063:
        $data = FznewsFlowProcess::find()->where(['and',['=','processInstanceId',$thirdNo]])->one();
        break;
      case 1000066:
        $data = WeixinFinanceInfo::find()->where(['and',['=','thirdNo',$thirdNo]])->one();
        break;
      default:
        $data = WeixinOaApprovalInfo::find()->where(['and',['=','thirdNo',$thirdNo]])->one();
        break;
    }

    if(!$data){
      return array('errorMessage'=>'WeixinOaApprovalInfo表无此单号');
    }

    switch ($agentid) {
      case 1000063:
        $flow = WeixinFlowApprovaldata::find()->where(['and',['=','thirdNo',$thirdNo]])->one();
        $curstep = $flow['step']-1;
        $newstep = $flow['step']-2;
        break;
      
      default:
        $flow = WeixinOaApprovaldata::find()->where(['thirdNo'=>$thirdNo])->one();
        $curstep = $flow['step'];
        $newstep = $flow['step']-1;
        break;
    }
    if(!$flow){
      return array('errorMessage'=>'WeixinOaApprovaldata表无此单号');
    }
    $agent = $flow['agentid'];
    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }
    
    if($flow['step']==0){
      return array('errorMessage'=>'已经是第一步了，无法再回退');
    }
    
    $flowdata = json_decode($flow['data'],true);

    switch ($agentid) {
      case 1000063:
        $node = $flowdata['data']['ApprovalNodes'][$curstep];
        break;
      
      default:
        $node = $flowdata['data']['ApprovalNodes']['ApprovalNode'][$curstep];
        unset($node['offline']);
        unset($node['fileurls']);
        unset($node['speech']);
        unset($node['date']);
        break;
    }
 
    for ($i=0; $i < sizeof($node['Items']['Item']); $i++) { 
      $node['Items']['Item'][$i]=array(
        'ItemName' => $node['Items']['Item'][$i]['ItemName'],
        'ItemImage' => $node['Items']['Item'][$i]['ItemImage'],
        'ItemUserId' => $node['Items']['Item'][$i]['ItemUserId'],
        'ItemStatus' => 1,
        'ItemOpTime' => 0
        );
    }
    
    switch ($agentid) {
      case 1000063:
        $newnode = $flowdata['data']['ApprovalNodes'][$newstep];
        break;
      
      default:
        $newnode = $flowdata['data']['ApprovalNodes']['ApprovalNode'][$newstep];
        
        unset($newnode['fileurls']);
        unset($newnode['speech']);
        unset($newnode['date']);
        break;
    }
    $newnode['NodeStatus']=1;
    unset($newnode['next']);
    $useridarr=[];
    $usernamearr=[];
    for ($i=0; $i < sizeof($newnode['Items']['Item']); $i++) {
      $useridarr[]=$newnode['Items']['Item'][$i]['ItemUserId'];
      $usernamearr[]=$newnode['Items']['Item'][$i]['ItemName'];
      $newnode['Items']['Item'][$i]=array(
        'ItemName' => $newnode['Items']['Item'][$i]['ItemName'],
        'ItemImage' => $newnode['Items']['Item'][$i]['ItemImage'],
        'ItemUserId' => $newnode['Items']['Item'][$i]['ItemUserId'],
        'ItemStatus' => 1,
        'ItemOpTime' => 0
        );
    }
    $offline=0;
    if ($newnode['offline']){
      $useridarr=[$data['userId']];
      $usernamearr=[$data['userName']];
      $offline=1;
    }

    switch ($agentid) {
      case 1000063:
        $flowdata['data']['ApprovalNodes'][$curstep]=$node;
        $flowdata['data']['ApprovalNodes'][$newstep]=$newnode;
        break;
      
      default:
        $flowdata['data']['ApprovalNodes']['ApprovalNode'][$curstep]=$node;
        $flowdata['data']['ApprovalNodes']['ApprovalNode'][$newstep]=$newnode;
        break;
    }
    
    $transaction = Yii::$app->db->beginTransaction();
    try {

      switch (''.$flow['agentid']) {
        case 1000063:
          $newstep++;
          $data['candidate'] = implode('|',$useridarr);
          $data['candidatename'] = implode('|',$usernamearr);
          $data['step']=$newstep;
          $flowdata['data']['Approverstep']=$newstep;
          break;
        
        default:
          $data['approvalUserid'] = implode('|',$useridarr);
          $data['approvalUsername'] = implode('|',$usernamearr);
          $data['status'] = 1;
          $flowdata['data']['approverstep']=$newstep;
          break;
      }
      
      $flow['step']=$newstep;
      $flow['data'] = json_encode($flowdata);

      $flow->save();
      $data->save();

      switch (''.$flow['agentid']) {
        case '1000080':
          FzrbsBudgetProject::updateAll(['offline'=>$offline,'offlinenote'=>''],['thirdNo'=>$thirdNo]);
          break;

        
        default:
          break;
      }
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=> $th->getMessage());
    }
    $transaction->commit();
    return array('errorMessage'=>'');
  }
  public function actionDelflownode(){
    return array('errorMessage'=>'暂不支持');
    $thirdNo = $this->_request['thirdNo'];
    $step = $this->_request['step'];

    if (!$thirdNo){
      return array('errorMessage'=>'thirdNo不能为空');
    }
    if (!isset($step)){
      return array('errorMessage'=>'step不能为空');
    }

    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }

    $flow = WeixinOaApprovaldata::find()->where(['thirdNo'=>$thirdNo])->one();
    if (!$flow){
      return array('errorMessage'=>'无此单号');
    }

    $flowdata = json_decode($flow['data'],true);
    if (!isset($flowdata['data']['ApprovalNodes']['ApprovalNode'][$step])){
      return array('errorMessage'=>'节点不存在');
    }

    unset($flowdata['data']['ApprovalNodes']['ApprovalNode'][$step]);
    $flowdata['data']['ApprovalNodes']['ApprovalNode'] = array_values($flowdata['data']['ApprovalNodes']['ApprovalNode']);

    $flow->data = json_encode($flowdata);
    $flow->save();

    return array('data'=>'删除成功');
  }
  // *********************************  一线考核流程配置 ******************************************
  public function actionYxkhtemplatelist(){ 
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['>', 't.id', 0],
    ];
  
    if ($this->_request['keyword']) {
      $where[] = ['or',['like','t.templatename',$this->_request['keyword']],['=','t.templateid',$this->_request['keyword']]];
    }

    
    if ($this->_request['dept']){
      $dept = $this->_request['dept'];
      $where[] = new Expression("FIND_IN_SET($dept, dids)");
    }
    if ($this->_request['userid']){
      $userid = $this->_request['userid'];
      $where[] = new Expression("FIND_IN_SET($userid, uids)");
    }
    
    $model = WeixinYxkhTemplate::find()->alias('t')->select('t.*,wt.templateName as tname')->leftJoin(['wt'=>WeixinOaTemplates::tableName()],'wt.templateId=t.templateid')->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('id desc')->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }
  public function actionSaveyxkhflow(){

    $obj = $this->_request;

    $p = new WeixinYxkhTemplate($obj);
    
    try {
      if ($p['id']){
        $obj['updator']= $this->userinfo['name'];
        $p->updateAll($obj,['id'=>$p['id']]);
      } else {
        $p->creatorname= $this->userinfo['name'];
        $p->creator= $this->userinfo['userid'];
        $p->agentid= 1000063;
        $p->save();
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$p);
  }
  public function actionDelyxkhflow(){
    $id = $this->_request['id'];
    if (!$id){
      return array('errorMessage'=>'id不能为空');
    }
    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }
    $p = WeixinYxkhTemplate::findOne($id);
 
    if (!$p){
      return array('errorMessage'=>'');
    }
    $p->delete();
    return array('data'=>$p);
  }
 
  // *********************************  打印位置配置 ******************************************
  public function actionPrintpositionlist(){ 
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['>', 't.id', 0],
    ];

    if ($this->_request['userid']){
      $userid = $this->_request['userid'];
      $where[] = ['=', 't.userid', $userid];
    }
    if ($this->_request['role']){
      $where[] = ['and',['=','type',0],['=', 't.value', $this->_request['role']]];
    }
    if ($this->_request['tag']){
      
      $where[] = ['and',['=','type',2],['=', 't.value', $this->_request['tag']]];
    }
    
    $model = WeixinOaPrintPosition::find()->alias('t')->select('t.*,wt.appname')
      ->leftJoin(['wt'=>WeixinQYAppInterface::tableName()],'wt.appid=t.agentid')
      ->where($where)->with(['valuename']);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('id desc')->asArray()->all();
    foreach ($res as $key => $value) {
      
      if($res[$key]['type']==2){
        $temp = WeixinOaUsertag::findOne($res[$key]['value']);
        if ($temp) $res[$key]['rolename'] = $temp['tagName'];
      }else if($res[$key]['type']==20){
        $u = WeixinOAUserInfo::find()->where(['=', 'userid', $res[$key]['userid']])->asArray()->one();
        if($u)  $res[$key]['rolename'] = $u['name'];
      }else{
        $res[$key]['rolename'] = $value['valuename']['rolename']?$value['valuename']['rolename']:$value['valuename']['tagName'];
      }
    }
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }
  public function actionSaveprintposition(){

    $obj = $this->_request;

    $p = new WeixinOaPrintPosition($obj);
    
    try {
      if ($p['id']){
        $obj['updator']= $this->userinfo['name'];
        $p->updateAll($obj,['id'=>$p['id']]);
      } else {
        $p->creatorname= $this->userinfo['name'];
        $p->creator= $this->userinfo['userid'];
        $p->save();
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$p);
  }
  public function actionDelprintposition(){
    $id = $this->_request['id'];
    if (!$id){
      return array('errorMessage'=>'id不能为空');
    }
    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }
    $p = WeixinOaPrintPosition::findOne($id);

    if (!$p){
      return array('errorMessage'=>'');
    }
    $p->delete();
    return array('data'=>$p);
  }

  // *********************************  考勤异常流程配置 ******************************************
 
  
  public function actionAttendancetemplatelist(){ 
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['>', 't.id', 0],
    ];
  
    if ($this->_request['keyword']) {
      $where[] = ['or',['like','t.templatename',$this->_request['keyword']],['=','t.templateid',$this->_request['keyword']]];
    }

    
    if ($this->_request['dept']){
      $dept = $this->_request['dept'];
      $where[] = new Expression("FIND_IN_SET($dept, dids)");
    }
    if ($this->_request['userid']){
      $userid = $this->_request['userid'];
      $where[] = new Expression("FIND_IN_SET($userid, uids)");
    }
    
    $model = WeixinAttendanceTemplate::find()->alias('t')->select('t.*,wt.templateName as tname')->leftJoin(['wt'=>WeixinOaTemplates::tableName()],'wt.templateId=t.templateid')->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('id desc')->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }
  public function actionSaveattendanceflow(){

    $obj = $this->_request;

    $p = new WeixinAttendanceTemplate($obj);
    
    try {
      if ($p['id']){
        $obj['updator']= $this->userinfo['name'];
        $p->updateAll($obj,['id'=>$p['id']]);
      } else {
        $p->creatorname= $this->userinfo['name'];
        $p->creator= $this->userinfo['userid'];
        $p->agentid= 1000067;
        $p->save();
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$p);
  }
  public function actionDelattendanceflow(){
    $id = $this->_request['id'];
    if (!$id){
      return array('errorMessage'=>'id不能为空');
    }
    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }
    $p = WeixinAttendanceTemplate::findOne($id);
    
    if (!$p){
      return array('errorMessage'=>'');
    }
    $p->delete();
    return array('data'=>$p);
  }

  // ***************************** 用印审批流程 *********************************
  public function actionUsesealtemplatelist(){
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
  
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['>', 't.id', 0],
    ];
  
    if ($this->_request['keyword']) {
      $where[] = ['or',['like','t.templatename',$this->_request['keyword']],['=','t.templateid',$this->_request['keyword']]];
    }
    if ($this->_request['type']) {
      $where[] = ['=','t.type',$this->_request['type']];
    }

    
    if ($this->_request['dept']){
      $dept = $this->_request['dept'];
      $where[] = new Expression("FIND_IN_SET($dept, dids)");
    }
    if ($this->_request['userid']){
      $userid = $this->_request['userid'];
      $where[] = new Expression("FIND_IN_SET($userid, uids)");
    }
    $model = WeixinUsesealTemplate::find()->alias('t')->select('t.*,wt.templateName as tname')->leftJoin(['wt'=>WeixinOaTemplates::tableName()],'wt.templateId=t.templateid')->where($where)->with(['typename']);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('id desc')->asArray()->all();
    foreach ($res as $key => $value) {
      
      $res[$key]['typename'] = $res[$key]['typename']['label'];
      
    }
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }
  

  

  public function actionSaveusesealflow(){

    $obj = $this->_request;

    $p = new WeixinUsesealTemplate($obj);
    
    try {
      if ($p['id']){
        $obj['updator']= $this->userinfo['name'];
        $p->updateAll($obj,['id'=>$p['id']]);
      } else {
        $p->creatorname= $this->userinfo['name'];
        $p->creator= $this->userinfo['userid'];
        $p->agentid= 1000065;
        $p->save();
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$p);
  }

  public function actionDelusesealflow(){
    $id = $this->_request['id'];
    if (!$id){
      return array('errorMessage'=>'id不能为空');
    }
    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }
    $p = WeixinUsesealTemplate::findOne($id);

    if (!$p){
      return array('errorMessage'=>'');
    }
    $p->delete();
    return array('data'=>$p);
  }
  public function actionOrdertemplatelist(){ 
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $where = [
        'and',
        ['>', 't.id', 0],
    ];
  
    if ($this->_request['keyword']) {
      $where[] = ['or',['like','t.templatename',$this->_request['keyword']],['=','t.templateid',$this->_request['keyword']]];
    }

    
    if ($this->_request['dept']){
      $dept = $this->_request['dept'];
      $where[] = new Expression("FIND_IN_SET($dept, dids)");
    }
    if ($this->_request['userid']){
      $userid = $this->_request['userid'];
      $where[] = new Expression("FIND_IN_SET($userid, uids)");
    }
    
    $model = WeixinOrderTemplate::find()->alias('t')->select('t.*,wt.templateName as tname')->leftJoin(['wt'=>WeixinOaTemplates::tableName()],'wt.templateId=t.templateid')->where($where);
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy('id desc')->asArray()->all();
    $this->_result["current"] = $page;
    $this->_result["pageSize"] = $limit;
    $this->_result["total"] = $total;
    $this->_result['data'] = $res;
    return $this->_result;
  }
  public function actionSaveorderflow(){

    $obj = $this->_request;

    $p = new WeixinOrderTemplate($obj);
    
    try {
      if ($p['id']){
        $obj['updator']= $this->userinfo['name'];
        $p->updateAll($obj,['id'=>$p['id']]);
      } else {
        $p->creatorname= $this->userinfo['name'];
        $p->creator= $this->userinfo['userid'];
        $p->agentid= 1000078;
        $p->save();
      }
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('data'=>$p);
  }
  public function actionDelorderflow(){
    $id = $this->_request['id'];
    if (!$id){
      return array('errorMessage'=>'id不能为空');
    }
    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }
    $p = WeixinOrderTemplate::findOne($id);
    
    if (!$p){
      return array('errorMessage'=>'');
    }
    $p->delete();
    return array('data'=>$p);
  }
  

}