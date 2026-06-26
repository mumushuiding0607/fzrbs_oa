<?php
namespace app\modules\api\commons;

use app\modules\api\models\WeixinFinanceCompany;
use app\modules\api\models\WeixinFlowApprovaldata;
use app\modules\api\models\WeixinOaApprovaldata;
use app\modules\api\models\WeixinOaApprovalInfo;
use app\modules\api\models\WeixinOaApprovalLog;
use app\modules\api\models\WeixinOaDepartment;
use app\modules\api\models\WeixinOaNotifyLog;
use app\modules\api\models\WeixinOAUserInfo;
use Exception;

class WorkflowParse
{
    public $templateModelClass = 'app\modules\api\models\WeixinOaTemplates';    //模板表
    public $userinfoModelClass = 'app\modules\api\models\WeixinOaUserinfo';     //用户表
    public $departmentModelClass = 'app\modules\api\models\WeixinOaDepartment';     //部门表
    public $usertagModelClass = 'app\modules\api\models\WeixinOaUsertag';       //用户标签表
    public $taguserModelClass = 'app\modules\api\models\WeixinOaTaguser';       //标签用户表
    public $companyModelClass = 'app\modules\api\models\WeixinFinanceCompany';  //公司主体表
    public $approvalModelClass = 'app\modules\api\models\WeixinOaApprovaldata';  //流程数据表
    public $approvallogModelClass = 'app\modules\api\models\WeixinOaApprovalLog';  //流程日志数据表
    public $flowroleModelClass = 'app\modules\api\models\WeixinOaFlowrole';  //流程角色表
    public $roleModelClass = 'app\modules\api\models\WeixinOaRole';  //流程角色表
    public $approveAgentModelClass = 'app\modules\api\models\WeixinOaApproveAgent';  //审批代理表
    private $_agentId; 
    public function __construct($agentid=0)
    {
        $this->_agentId = $agentid;
    }
    /**
     * 解析流程模板 
     * @version 1.0.2, 2020/05/08
     * @param $userid   
     * @param $templateid
     * @param $condition = array(
     *      'company' => 1  主体单位ID
     * )    扩展条件组
     * @return array (
     *                  'OpenTemplateId' => '模板ID'
     *                  'OpenSpName' => '模板名称'
     *                  'ApprovalNodes' => 
     *                      array (
     *                          'ApprovalNode' => 
     *                          array (
     *                          0 => 
     *                          array (
     *                              'NodeStatus' => 1,审批操作状态：初始值1-审批中；
     *                              'Items' => 
     *                                 array (
     *                                  'Item' => 
     *                                      array (
     *                                          0 => 
     *                                         array (
     *                                             'ItemName' => '分支审批人姓名',
     *                                             'ItemParty' => '分支审批人所在部门',
     *                                             'ItemImage' => '分支审批人头像',
     *                                             'ItemUserId' => '分支审批人userid',
     *                                             'ItemStatus' => 1,分支审批操作状态：初始值1-审批中；
     *                                             'ItemSpeech' => '分支审批人审批意见',
     *                                             'ItemOpTime' => 0,分支审批人操作时间
     *                                         )
     *                                      ),
     *                                  ),
     *                              'NodeAttr' => 1,分支审批属性：1-或签；2-会签
     *                              'NodeType' => 3,分支审批类型：1-固定成员；2-标签；3-上级；5-领导；6-手动选择;8-主体负责人
     *                              'NodeSkip' => 0,分支是否可跳过：1-可跳过；
     *                              'NodeSpan' => 0,分支是否跨部门：1-跨部门；
     *                              'NodeRoleid' => 6, 领导是哪个类型
     *                              'NodeLevel' => 1, 上级层级：1-直接上级，2-二级上级
     *                              'NodeTagid' =>1, 标签对应的id
     *                          )
     *                          ),
     *                      ),
     *                  'NotifyNodes' => 
     *                      array (
     *                          'NotifyNode' => 
     *                          array (
     *                          0 => 
     *                          array (
     *                              'ItemName' => '抄送人姓名',
     *                              'ItemParty' => '抄送人所在部门',
     *                              'ItemImage' => '抄送人头像',
     *                              'ItemUserId' => '抄送人userid',
     *                          )
     *                          ),
     *                      ),
     *                  )
     *                  'NotifyAttr' => 2 //1：审请时抄送，2：通过后抄送，3：都抄送
     * 
     */

    public function flowParse($userid,$templateid, $condition=array(),$dedu=1,$spe=0,$filt=0){
        $row = $this->templateModelClass::find()->where(['=','templateId', $templateid])->asArray()->one();
        if($row['templateData']){
            $templatedata = json_decode($row['templateData'],true);
            if($spe==1){
                $templatedata = $this->removeLeader($templatedata);
            }
            if($filt==1 || $filt==2){
                $templatedata = $this->filteTemplate($templatedata);
            }
            $ret = array();
            $approvalnode = array();
            $approvalusers = array();
            $approvalTmp = array_reverse($templatedata['approval']);
            foreach($approvalTmp as $approval){//审批
                $item = array();
                if($approval['type'] == 1){//单个成员
                    $wh = [
                        'and',
                        ['=','id',$approval['id']],
                        ['=','status',1],
                        ['=','st',1]                        
                    ];
                    // 派车应用允许审批人=发起人
                    if($this->_agentId != 1000038 && $dedu!=2){
                        $wh[] = ['<>','userid',$userid];
                    }
                    $user = $this->userinfoModelClass::find()->where($wh)->asArray()->one();

                    if($user){
                        if(!in_array($user['userid'],$approvalusers)){
                            $approvalusers[] = $user['userid'];
                            $item[] = $this->getItem($user);
                        }
                    }
                }else if($approval['type'] == 2){//标签
                    $taguser = $this->taguserModelClass::findBySql("select a.*,b.userid,b.`name`,b.avatar from weixin_oauser_taguser a LEFT JOIN weixin_leave_userinfo b on a.uId=b.id where tagId='".$approval['id']."'")->asArray()->all();
                    foreach($taguser as $tu){
                        if($tu['userid'] != $userid || $this->_agentId == 1000038 || $dedu==2){
                            if(!in_array($tu['userid'],$approvalusers)){
                                $approvalusers[] = $tu['userid'];
                                $item[] = $this->getItem($tu);
                            }
                        }
                    }
                }else if($approval['type'] == 3){//上级
                    $user = $this->userinfoModelClass::find()->where(['and',['=','userid',$userid],['=','status',1],['=','st',1]])->asArray()->one();
                    if($user){
                        // if($user['is_leader'] == 1){
						// 	$approval['level'] +=1;
                        // }
                            $departid = $this->getDepartForLevel($user['departmentid'],$approval['level']);
                            $leader = $this->userinfoModelClass::find()->where(['and',['=','is_leader',1],["=","departmentid",$departid],['=','status',1],['=','st',1]])->asArray()->all();
							if(!$leader){
								$departid = $this->getDepartForLevel($user['departmentid'],intval($approval['level'])+1);
                                $leader = $this->userinfoModelClass::find()->where(['and',['=','is_leader',1],["=","departmentid",$departid],['=','status',1],['=','st',1]])->asArray()->all();
							}
                            foreach($leader as $lu){
                                if($lu['userid'] != $userid || $this->_agentId == 1000038 || $dedu==2){
                                    if(!in_array($lu['userid'],$approvalusers)){
                                        $approvalusers[] = $lu['userid'];
                                        $item[] = $this->getItem($lu);
                                    }
                                }
                            }               
                    }
                } else if ($approval['type'] == 8) { //主体负责人
                    
                    $where = isset($condition['company']) ? " and id='" . $condition['company'] . "'" : '';
                    $user = $this->userinfoModelClass::find()->where(['and',["=","userid",$userid],['=','status',1],['=','st',1]])->asArray()->one();
                    if ($user) {
                        $companyrole = $this->companyModelClass::findBySql("select * from weixin_finance_company where id>0 $where order by id desc")->asArray()->one();
                        if ($companyrole) {
                            $company = $this->userinfoModelClass::find()->where(['and',["=","userid",$companyrole['userid']],['=','status',1],['=','st',1]])->asArray()->one();
                            if ($company) {
                                if (!in_array($company['userid'], $approvalusers)) {
                                    $approvalusers[] = $company['userid'];
                                    $item[] = $this->getItem($company);
                                }
                            }
                        }
                    }
                }else{//角色
                    $roleInfo = $this->roleToUser($userid,$approval,$condition);
                    if ($condition['roleToUserAll']){
                      if($roleInfo){
                        $item=array_merge($item,$roleInfo);
                      }
                    } else {
                      if($roleInfo && !in_array($roleInfo['ItemUserId'],$approvalusers)){
                        $approvalusers[] = $roleInfo['ItemUserId'];
                        $item[] = $roleInfo;
                      }
                    }
                    
                }
                
                if(count($item)>0){
                  
                    $e =array(
                        'NodeStatus' => 1,
                        'Items' => array('Item'=>$item),
                        'NodeAttr' => $approval['attr'],
                        'NodeType' => $approval['type'],
                        'NodeSkip' => isset($approval['skip'])?$approval['skip']:0,
                        'NodeSpan' => isset($approval['span'])?$approval['span']:0
                    );
                    // 如果是上级
                    if ($approval['type'] == 3) $e['NodeLevel'] = $approval['level']; // 分辨领导层级
                    // 如果是标签
                    if($approval['type'] == 2) $e['NodeTagid'] = $approval['id']; // 分辨是哪个标签
                    // 如果是领导
                    if(in_array($approval['type'],array(0,5,7,9,10))) $e['NodeRoleid'] = $approval['role']; // 判断是哪个类型的领导
                    $approvalnode[] = $e;
            
                }else{
                  
                  if ($approval['notnull']){
                    if ($approval['role']){
                      // 查询角色名称
                      $role = $this->roleModelClass::find()->where(['and',['=','id',$approval['role']]])->asArray()->one();
                      throw new Exception('流程【'.$templateid.'】,审批节点【'.$role['rolename'].'】未匹配到审批人,请联系财务设置!');
                    }
                   
                  }
                }
            }
            $approvalnode = array_reverse($approvalnode);
            $notifynode = array();
            foreach($templatedata['notify'] as $nodify){//抄送
                if(in_array($nodify['type'],array(0,4))){
                    // $roleInfo = $this->roleToUserAll($userid,$nodify,$condition,1);
                    $roleInfo = $this->roleToUserAll($userid,$nodify,$condition,isset($condition['tp'])?$condition['tp']:1);
      
                    if($roleInfo){
                        foreach($roleInfo as $ri){
                            // if(!in_array($ri['ItemUserId'],$approvalusers)){
                                $notifynode[] = $ri;
                            // }
                        }
                    }
                }else if($nodify['type'] == 2){//标签
                    $taguser = $this->taguserModelClass::findBySql("select a.*,b.userid,b.`name`,b.avatar from weixin_oauser_taguser a LEFT JOIN weixin_leave_userinfo b on a.uId=b.id where tagId='".$nodify['id']."'")->asArray()->all();
                    foreach($taguser as $tu){
                        if($tu['userid'] != $userid){
                            // if(!in_array($tu['userid'],$approvalusers)){
                                $notifynode[] = $this->getItem($tu,1);
                            // }
                        }
                    }
                }else if($nodify['type'] == 3){//上级
                    $user = $this->userinfoModelClass::find()->where(['and',['=','userid',$userid],['=','status',1],['=','st',1]])->asArray()->one();
                    if($user){
                            $departid = $this->getDepartForLevel($user['departmentid'],$nodify['level']);
                            $leader = $this->userinfoModelClass::find()->where(['and',['=','is_leader',1],["=","departmentid",$departid],['=','status',1],['=','st',1]])->asArray()->all();
                            foreach($leader as $lu){
                                if($lu['userid'] != $userid || $this->_agentId == 1000038 || $dedu==2){
                                    // if(!in_array($lu['userid'],$approvalusers)){
                                        $notifynode[] = $this->getItem($lu,1);
                                    // }
                                }
                            }               
                    }
                }else{
                    $user = $this->userinfoModelClass::find()->where(['and',["=","id",$nodify['id']],['=','status',1],['=','st',1]])->asArray()->one();
                    if($user){
                        // if(!in_array($user['userid'],$approvalusers)){
                            $notifynode[] = $this->getItem($user,1);
                        // }
                    }
                }
            }
            // if($spe==0 && $filt==2){  //中层正职公务假条流程 人事经办调整到第2步
            //     $approvalnode = $this->changeTemplate($approvalnode);
            // }
            $ret = array(
                'OpenTemplateId' => $row['templateId'],
                'OpenSpName' => $row['templateName'],
                'ApprovalNodes' => array('ApprovalNode'=>$approvalnode),
                'NotifyNodes' => array('NotifyNode'=>$notifynode),
                'NotifyAttr' => $row['notifyAttr']
            );
            return $this->approveAgent($ret);
        }
    }
  
  /**
   * 中层正职公务假条流程 人事经办调整到第2步
   *  */  
  private function changeTemplate($data)
  {
      $approval = [];
      $tmp = [];
      if($data[0]['Items']['Item'][0]['ItemUserId']=='zhangxiaoxue'){  
          $tmp = $data[0];
          foreach($data as $k=>$a){
              if($k>0){
                  $approval[] = $a;
                  if($k==1){
                  $approval[] = $tmp;
                  }
              }
          }
          $data = $approval;
      }
      return $data;
  }

	/**
	 * 流程过滤 （调休、公务申请不经过人事经办）
	 */
	private function filteTemplate($tmp)
	{
        $approval = [];
        foreach($tmp['approval'] as $a){
            if($a['type']>0 || $a['role']!=2){
                $approval[] = $a;
            }
        }
        $tmp['approval'] = $approval;
        return $tmp;
	}

    /**
     * 请销假申请公务假条时的特殊处理
     * 申请时选择领导已签批，则流程不再延续到社领导
     */
    private function removeLeader($data)
    {
        $tmp = array();
        foreach($data['approval'] as $row){
            if(!in_array($row['role'],array(6,7,8,9))){
                $tmp[] = $row;
            }
        }
        $data['approval'] = $tmp;
        return $data;
    }
    /**
     * 审批代理人检测变更
     * @param $flow  流程数据
     * @param $agentid  应用id
     */
    public function approveAgent($flow,$agentid=0)
    {
        $agentid = $agentid?$agentid:$this->_agentId;
        $t = time();
        if($agentid){
            $agentdata = $this->approveAgentModelClass::findBySql("select * from weixin_oa_approve_agent where FIND_IN_SET('" . intval($agentid) . "',agent) and start_time<='$t' and end_time>='$t'")->asArray()->all();
            if($agentdata){
                $principal2proxy = array_combine(array_column($agentdata, 'principal_userid'), array_column($agentdata, 'proxy_userid'));
                $userdata = $this->userinfoModelClass::find()->where(['and',['=','status',1],['=','st',1]])->asArray()->all();
                $username = array_combine(array_column($userdata, 'userid'), array_column($userdata, 'name'));
                $useravatar = array_combine(array_column($userdata, 'userid'), array_column($userdata, 'avatar'));
                
                foreach($flow['ApprovalNodes']['ApprovalNode'] as $k=>$r){
                    foreach($r['Items']['Item'] as $kk=>$rr){
                        if(isset($principal2proxy[$rr['ItemUserId']])){
                            $flow['ApprovalNodes']['ApprovalNode'][$k]['Items']['Item'][$kk]['ItemName'] = $username[$principal2proxy[$rr['ItemUserId']]];
                            $flow['ApprovalNodes']['ApprovalNode'][$k]['Items']['Item'][$kk]['ItemImage'] = $useravatar[$principal2proxy[$rr['ItemUserId']]];
                            $flow['ApprovalNodes']['ApprovalNode'][$k]['Items']['Item'][$kk]['ItemUserId'] = $principal2proxy[$rr['ItemUserId']];
                            break;
                        }
                    }
                }
            }
        }
        return $flow;
    }
    /**
     * 获取流程预览数据
     * @param $agentid  应用ID
     * @param $thirdNo  流程编号
     * return [
     *      {
     *          "title":"",
     *          "avatar":"",
     *          "date":"",
     *          "speech":"",
     *          "status":1,
     *          "items":[
     *              {
     *                  "title":"",
     *                  "avatar":"",
     *                  "date":"",
     *                  "speech":"",
     *                  "status":1,
     *              }
     *          ]
     *      }
     *  ]
     */
    public function flowViewdata($thirdNo)
    {
        $approvaldata = array();
        switch ($this->_agentId) {
          case 1000063:
            $approvedata = WeixinFlowApprovaldata::find()->where(['and',['=','agentid',$this->_agentId],["=","thirdNo",$thirdNo]])->one();
            break;
          
          default:
            $approvedata = $this->approvalModelClass::find()->where(['and',['=','agentid',$this->_agentId],["=","thirdNo",$thirdNo]])->one();
            break;
        }
        if (!$approvedata) return 0;
       
        
        $approvearr = json_decode($approvedata['data'],true);
        $nodes = $approvearr['data']['ApprovalNodes']['ApprovalNode'];
        switch ($this->_agentId) {
          case 1000063:
            $nodes = $approvearr['data']['ApprovalNodes'];
            break;
        }
        foreach ($nodes as $k=>$r) {
            $tmparr = $r;
            if(count($r['Items']['Item'])>1){
                $tmparr['title'] = '直接上级';
                $tmparr['NodeAttr'] = $r['NodeAttr'];
                if ($r['NodeType']==2 && isset($r['NodeTagid'])){
                  $tmparr['title'] = $this->getUserTagName($r['NodeTagid']);
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
        $notifier = array();
        $notifierUserid = array();
        if ($approvearr['data']['NotifyNodes']&&$approvearr['data']['NotifyNodes']['NotifyNode']){
          foreach ($approvearr['data']['NotifyNodes']['NotifyNode'] as $r) {
              $notifier[] = $r['ItemName'];
              $notifierUserid[] = $r['ItemUserId'];
          }
        }
        
        
        
        switch ($this->_agentId) {
          case 1000063:
            $step = intval($approvearr['data']['Approverstep'])-2;
            break;
          
          default:
            $step = intval($approvearr['data']['approverstep'])-1;
            break;
        }
       
        return array('step'=>$step,'approval'=>$approvaldata,'notify'=>$notifier,'notifierUserid'=>$notifierUserid);
     
    }


    /**
     * 根据角色获取用户
     */
    public function roleToUser($userid,$tmpdata,$condition=array(), $tp=0)
    {
        
        $typeForRole = array(7=>5,9=>10);
        $role = isset($tmpdata['role'])&&$tmpdata['role']?$tmpdata['role']:(isset($tmpdata['nrole'])&&$tmpdata['nrole']?$tmpdata['nrole']:(isset($typeForRole[$tmpdata['type']])?$typeForRole[$tmpdata['type']]:0));
        if($role){
            // ************* 林 6月5号 修改 *************
            // if(in_array($role,array(4,5,14))){//仅公司会计、会计和出纳角色需要对应主体
            //     $where = isset($condition['company']) ? " and FIND_IN_SET(" . $condition['company'] . ",company)" : '';
            // }
            // $where .= $this->_agentId ? " and FIND_IN_SET(" . $this->_agentId . ",agent)" : '';
            // ************* 林 6月5号 修改 *************

            // ************* 林 6月5号 修改 *************
            $company = isset($condition['company']) ? " and FIND_IN_SET(" . $condition['company'] . ",company)" : "";
            $where = $this->_agentId ? " and FIND_IN_SET(" . $this->_agentId . ",agent)" : '';
            // ************* 林 6月5号 修改 *************


            $user = $this->userinfoModelClass::find()->where(['and',["=","userid",$userid],['=','status',1],['=','st',1]])->one();
            $departmentid = $condition['departmentid']?$condition['departmentid']:$user['departmentid'];

            // ************* 林 6月5号 修改 *************
            $order = 'order by id desc';
            
            $sql = "select * from weixin_oa_flowrole where role='$role' and type='$tp' and FIND_IN_SET(" . $departmentid .",dept) $where ";
            // ************* 林 6月5号 修改 *************
            if ($user) {

                  // ************* 林 6月5号 修改 *************
                  // 包含公司，精确查询
                  $flowroleModel = $this->flowroleModelClass::findBySql($sql." $company $order")->asArray();
                  // 包含公司时查询结果为空，尝试查询公司为空的情况，这样更精确
                  if (!$flowroleModel->count()){
                      $company = "and (company is null or company='')";
                      $flowroleModel = $this->flowroleModelClass::findBySql($sql." $company $order")->asArray();
                  }
                  // 忽略公司，查询结果
                  if (!$flowroleModel->count()&&!$company){
                    $flowroleModel = $this->flowroleModelClass::findBySql($sql." $order")->asArray();
                  }
                  // ************* 林 6月5号 修改 *************

                if($condition['roleToUserAll']){

                  // ************* 林 6月5号 修改 *************
                  // $sql = "select * from weixin_oa_flowrole where role='$role' and type='$tp' and FIND_IN_SET(" . $departmentid .",dept) $where order by id desc";
               
                  // $flowrole = $this->flowroleModelClass::findBySql($sql)->asArray()->all();
                  // if($flowrole){
                  //       $item = [];
                  //       foreach($flowrole as $tu){
                  //         $userres = $this->userinfoModelClass::find()->where(['and',["=","userid",$tu['userid']],['=','status',1],['=','st',1]])->one();
                  //         if ($userres) {
                  //           $item[]=$this->getItem($userres);
                  //         }
                  //       }
                  //       return $item;
                  // }
                  // ************* 林 6月5号 修改 *************


                  $flowrole=$flowroleModel->all();
                  

                  if($flowrole){
                        $item = [];
                        foreach($flowrole as $tu){
                          $userres = $this->userinfoModelClass::find()->where(['and',["=","userid",$tu['userid']],['=','status',1],['=','st',1]])->one();
                          if ($userres) {
                            $item[]=$this->getItem($userres);
                          }
                        }
                        return $item;
                  }

                }else{

                  // ************* 林 6月5号 修改 *************
                  // $flowrole = $this->flowroleModelClass::findBySql("select * from weixin_oa_flowrole where role='$role' and type='$tp' and FIND_IN_SET(" . $departmentid .",dept) $where order by id desc")->one();
                  
                  $flowrole=$flowroleModel->one();
                  // ************* 林 6月5号 修改 *************

                  if($flowrole){
                      //**社长出差临时调整流程：转总编审批 */
                      // $thistime = time();
                      // if($thistime<strtotime('2023-04-14 15:00:00') && $flowrole['userid']=='chenbinfeng'){
                      //     $flowrole['userid'] = 'xielianling';
                      // }
                      //********************* */
                      
                      $userres = $this->userinfoModelClass::find()->where(['and',["=","userid",$flowrole['userid']],['=','status',1],['=','st',1]])->one();
                      if ($userres) {
                          return $this->getItem($userres);
                      }
                  }
                }
                
            }
        }
        return 0;
    }

    public function roleToUserAll($userid,$tmpdata,$condition=array(), $tp=0)
    {
        $typeForRole = array(7=>5,9=>10);
        $role = isset($tmpdata['role'])&&$tmpdata['role']?$tmpdata['role']:(isset($tmpdata['nrole'])&&$tmpdata['nrole']?$tmpdata['nrole']:(isset($typeForRole[$tmpdata['type']])?$typeForRole[$tmpdata['type']]:0));
        if($role){
          // ************* 林 6月5号 修改 *************
            // if(in_array($role,array(4,5,14))){//仅公司会计、会计和出纳角色需要对应主体
            //     $where = isset($condition['company']) ? " and FIND_IN_SET(" . $condition['company'] . ",company)" : '';
            // }
            // $where .= $this->_agentId ? " and FIND_IN_SET(" . $this->_agentId . ",agent)" : '';
 

            $company = isset($condition['company']) ? " and FIND_IN_SET(" . $condition['company'] . ",company)" : "";
            // 广告流程
            $publicationid = isset($condition['publicationid']) ? "and (" . implode(' OR ', array_map(function($id) { return "FIND_IN_SET('$id', publicationid)"; }, explode(',', $condition['publicationid']))) . ")" : "";
            
            $where = $this->_agentId ? " and FIND_IN_SET(" . $this->_agentId . ",agent)" : '';
            // ************* 林 6月5号 修改 *************

            $user = $this->userinfoModelClass::find()->where(['and',["=","userid",$userid],['=','status',1],['=','st',1]])->one();
            $departmentid = $condition['departmentid']?$condition['departmentid']:$user['departmentid'];
            if ($user) {
                $ret = [];


                // ************* 林 6月5号 修改 *************
                // $flowrole = $this->flowroleModelClass::findBySql("select * from weixin_oa_flowrole where role='$role' and type='$tp' and FIND_IN_SET(" . $departmentid .",dept) $where ")->asArray()->all();

                $order = 'order by id desc';
                
                $sql = "select * from weixin_oa_flowrole where role='$role' and type='$tp' and FIND_IN_SET(" . $departmentid .",dept) $where ";
                // 包含公司，刊物，精确查询
                $flowrole = $this->flowroleModelClass::findBySql($sql." $company $publicationid $order")->asArray()->all();
              
                // 包含公司时查询结果为空，尝试查询公司为空的情况，这样更精确
                if (!$flowrole){
                    if ($company){
                      $company = "and (company is null or company='')";
                      $flowrole = $this->flowroleModelClass::findBySql($sql." $company $order")->asArray()->all();
                    }
                    if ($publicationid){
                      $publicationid = "and (publicationid is null or publicationid='')";
                      $flowrole = $this->flowroleModelClass::findBySql($sql." $publicationid $order")->asArray()->all();
                    }
                    
                }
                // 忽略公司，查询结果
                if (!$flowrole&&!$company){
                  $flowrole = $this->flowroleModelClass::findBySql($sql." $order")->asArray()->all();
                }
                // ************* 林 6月5号 修改 *************


                
                foreach($flowrole as $fr){
                    $userres = $this->userinfoModelClass::find()->where(['and',["=","userid",$fr['userid']],['=','status',1],['=','st',1]])->one();
                    if ($userres) {
                        $ret[] = $this->getItem($userres);
                    }
                }
                return $ret;
            }
        }
        return 0;
    }

    /**
     * 创建流程
     */
    public function flowCreate($thirdNo,$userinfo,$templateid, $agentid=0,$condition=[])
    {
        $agentid = $agentid?$agentid:$this->_agentId;
        $flow = $this->flowParse($userinfo['userid'],$templateid, $condition);
        $leaveflow = array(
            'errcode' => 0,
            'errmsg' => 'ok',
            'data' => array(
                'ThirdNo' => $thirdNo,
                'OpenTemplateId' => $flow['OpenTemplateId'],
                'OpenSpName' => $flow['OpenSpName'],
                'OpenSpstatus' => 1,
                'ApplyTime' => time(),
                'ApplyUsername' => $userinfo['name'],
                'ApplyUserParty' => '',
                'ApplyUserImage' => $userinfo['avatar'],
                'ApplyUserId' => $userinfo['userid'],
                'ApprovalNodes' => $flow['ApprovalNodes'],
                'NotifyNodes' => $flow['NotifyNodes'],
                'approverstep' => 0
            )
        );
        $applydata = array(
            'agentid' => $agentid,
            'thirdNo' => strval($thirdNo),
            'data' => json_encode($leaveflow),
            'step' => 0,
            'status' => 1,
            'notifyAttr' => intval($flow['NotifyAttr'])
        );
        $approvalModel = new $this->approvalModelClass;
        $approvalModel->attributes = $applydata;
        $approvalModel->save();
        return $flow;
    }

    /**
     * 更新流程
     */
    public function flowUpdate($thirdNo,$userinfo,$templateid, $agentid=0,$condition=[],$dedu=1,$spe=0,$filt=0)
    {
        $agentid = $agentid?$agentid:$this->_agentId;
        $flow = $this->flowParse($userinfo['userid'],$templateid, $condition,$dedu,$spe,$filt);
        $leaveflow = array(
            'errcode' => 0,
            'errmsg' => 'ok',
            'data' => array(
                'ThirdNo' => $thirdNo,
                'OpenTemplateId' => $flow['OpenTemplateId'],
                'OpenSpName' => $flow['OpenSpName'],
                'OpenSpstatus' => 1,
                'ApplyTime' => time(),
                'ApplyUsername' => $userinfo['name'],
                'ApplyUserParty' => '',
                'ApplyUserImage' => $userinfo['avatar'],
                'ApplyUserId' => $userinfo['userid'],
                'ApprovalNodes' => $flow['ApprovalNodes'],
                'NotifyNodes' => $flow['NotifyNodes'],
                'approverstep' => 0
            )
        );
        $applydata = array(
            'data' => json_encode($leaveflow),
            'step' => 0,
            'status' => 1,
            'notifyAttr' => intval($flow['NotifyAttr'])
        );
        $approvalModel = $this->approvalModelClass::find()->where(['and',['=', 'agentid', $agentid],['=', 'thirdNo', $thirdNo]])->one();
        if($approvalModel){
            $approvalModel->attributes = $applydata;
            $approvalModel->save();
        }
        return $flow;
    }

    /**
     * 流程状态变更
     */
    public function flowChange($thirdNo,$userid,$status,$agentid=0,$speech='')
    {
        $agentid = $agentid?$agentid:$this->_agentId;
        $approveres = $this->approvalModelClass::find()->where(['and',["=","agentid",$agentid],["=","thirdNo",$thirdNo]])->one();
        
      if($approveres){
        $optime = time();
        $ret = ['isfinish'=>0];
        $nextdata = [];
        $approvedata = [];
        $step = intval($approveres['step']);
        $approvearr = json_decode($approveres['data'],true);
      
     
        if(in_array($status,[3,4]) || ($status==2 && $step==count($approvearr['data']['ApprovalNodes']['ApprovalNode'])-1&&$approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeAttr']!=2)){
          // throw new Exception('3,4');
          $approvearr['data']['OpenSpstatus'] = $status;
          $nextdata['status'] = $status;
          $approvedata['status'] = $status;

          if($status==2){
            $ret['isfinish']=1;
          }

          if($approveres['notifyAttr']>1){
            $notifyUserid = array();
            $notifyUsername = array();
            foreach($approvearr['data']['NotifyNodes']['NotifyNode'] as $notify){
              $notifyUserid[] = $notify['ItemUserId'];
              $notifyUsername[] = $notify['ItemName'];
            }
            $ret['tonotify'] = array('userid'=>$notifyUserid,'username'=>$notifyUsername);
          }
        }
        // throw new Exception(json_encode($approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item']));
        if(in_array($status,[2,3])){
          // throw new Exception('2,3');
          $logdata = array(
            'agentid'=>$agentid,
            'thirdNo'=>$thirdNo,
            'userId'=>$userid,
            'status'=>$status,
            'speech'=>$speech,
            'opTime'=>$optime
          );
          foreach($approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'] as $k=>$item){
            if($item['ItemUserId'] == $userid){
              $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeStatus'] = $status;

              $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'][$k]['ItemStatus'] = $status;
              
              $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'][$k]['ItemSpeech'] = $speech;
              $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'][$k]['ItemOpTime'] = $optime;
              $logdata['userName'] = $item['ItemName'];
            }
          }
        }
        // throw new Exception(json_encode($approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item']));
        $isNext = true;
        foreach($approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'] as $k=>$item){
          // 当前审批人无法执行，因为ItemStatus=2
          if($item['ItemStatus']==1 && $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeAttr']==2){ 
            // throw new Exception('NodeAttr true');
            $isNext = false;
            if($status==2)$approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeStatus'] = 1;
          }
          
        }
        if($status==2 && $isNext){
          if ($step==count($approvearr['data']['ApprovalNodes']['ApprovalNode'])-1){
            $approvearr['data']['OpenSpstatus'] = $status;
            $nextdata['status'] = $status;
            $approvedata['status'] = $status;

            $ret['isfinish']=1;

            if($approveres['notifyAttr']>1){
              $notifyUserid = array();
              $notifyUsername = array();
              foreach($approvearr['data']['NotifyNodes']['NotifyNode'] as $notify){
                $notifyUserid[] = $notify['ItemUserId'];
                $notifyUsername[] = $notify['ItemName'];
              }
              $ret['tonotify'] = array('userid'=>$notifyUserid,'username'=>$notifyUsername);
            }
          }
          if($step<count($approvearr['data']['ApprovalNodes']['ApprovalNode'])-1)$step++;
          $approvearr['data']['approverstep'] = $step;
          $approvalUserid = array();
          $approvalUsername = array();
          foreach($approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'] as $k=>$item){
            $approvalUserid[] = $item['ItemUserId'];
            $approvalUsername[] = $item['ItemName'];
          }
          $nextdata['approvalUserid'] = implode('|',$approvalUserid);
          $nextdata['approvalUsername'] = implode('|',$approvalUsername);
          $nextdata['approvalStep'] = $step;
          $ret['touser'] = $nextdata['approvalUserid'];
         
        }
        if($nextdata){
            $ret['nextdata'] = $nextdata;
        }
        $approvedata['data'] = json_encode($approvearr);
        $approvedata['step'] = $step;
        $approveres->status = $status;
        $approveres->data = $approvedata['data'];
        $approveres->step = $approvedata['step'];
        $approveres->save();
        if($logdata){
            $approvallogModel = new $this->approvallogModelClass(['scenario' => 'create']);
            $approvallogModel->attributes = $logdata;
            $ruleResult = Tools::modelRules($approvallogModel, 2000);
            if ($ruleResult === true) {
                $approvallogModel->save();
            }
        }
        return $ret;
      }
		return 0;
}
    /**
     * 跨部门审批
     * userid 为申请人
     * condition: array("crossdepartmentid":12) crossdepartmentid为所跨部门
     * 可跳过节点以及之后节点为跨部门节点 
     */

    public function flowParseCrossDepartment($applyuserid,$templateid, $condition=array()){
        $row = $this->templateModelClass::find()->where(['=','templateId', $templateid])->asArray()->one();
        if (!$condition['crossdepartmentid']) {
            throw new Exception('所跨部门id不能为空！');
        }
        $crossdepartmentid = $condition['crossdepartmentid'];
        $crossuser = $this->userinfoModelClass::find()->where(['and',["=","departmentid",$crossdepartmentid],['=','status',1],['=','st',1],['=','level',0]])->one();
        if(!$crossuser){
          $dept=WeixinOaDepartment::findOne($crossdepartmentid);
          $company=WeixinFinanceCompany::findOne($condition['company']);
          throw new Exception('目前是跨部门审批，但付款单位【'.$company['company'].'】所跨部门【'.$dept['name'].'】没有用户，请到【流程模板-付款单位】模块重新配置付款单位【'.$company['company'].'】所跨部门！');
        }
        $crossuserid = $crossuser['userid'];
        $userid = $applyuserid;
        if($row['templateData']){
            $templatedata = json_decode($row['templateData'],true);
            $ret = array();
            $approvalnode = array();
            $approvalusers = array();
            $needremove=array();
            $approvalTmp = $templatedata['approval'];

            for($i=0;$i<count($approvalTmp);$i++){
                $approval = $approvalTmp[$i];
                if ($approval['skip']) { // 碰到跨部门节点后，之后节点全部为跨部门节点
                    $userid = $crossuserid; // 
                }
                $item = array();
                if($approval['type'] == 1){//单个成员
                    $user = $this->userinfoModelClass::find()->where(['and',["=","id",$approval['id']],['<>','userid',$userid]])->one();
                    if($user){
                      $index = array_search($user['userid'],$approvalusers);
                      if($index>-1){
                        array_unshift($needremove,$index);
                      }
                      array_unshift($item,$this->getItem($user));
                    }
                }else if($approval['type'] == 2){//标签
                    $taguser = $this->taguserModelClass::findBySql("select a.*,b.userid,b.`name`,b.avatar from weixin_oauser_taguser a LEFT JOIN weixin_leave_userinfo b on a.uId=b.id where tagId='".$approval['id']."'")->asArray()->all();
                    foreach($taguser as $tu){
                        if($tu['userid'] != $userid){
                          $index = array_search($tu['userid'],$approvalusers);
                          if($index>-1){
                            array_unshift($needremove,$index);
                          }
                          array_unshift($item,$this->getItem($tu));
                        }
                    }
                }else if($approval['type'] == 3){//上级
                    $user = $this->userinfoModelClass::find()->where(['=','userid',$userid])->one();
                    if($user){
                        if($user['is_leader'] == 1){
							$approval['level'] +=1;
                        }
                            $departid = $this->getDepartForLevel($user['departmentid'],$approval['level']);
                            $leader = $this->userinfoModelClass::find()->where(['and',["=","departmentid",$departid],['=','is_leader',1]])->asArray()->all();
							if(!$leader){
								$departid = $this->getDepartForLevel($user['departmentid'],intval($approval['level'])+1);
                                $leader = $this->userinfoModelClass::find()->where(['and',["=","departmentid",$departid],['=','is_leader',1]])->asArray()->all();
							}
                            foreach($leader as $lu){
                                if($lu['userid'] != $userid){
                                    $index = array_search($lu['userid'],$approvalusers);
                                    if($index>-1){
                                      array_unshift($needremove,$index);
                                    }
                                    array_unshift($item,$this->getItem($lu));
                                }
                            }               
                    }
                } else if ($approval['type'] == 8) { //主体负责人
                    $where = isset($condition['company']) ? " and id='" . $condition['company'] . "'" : '';
                    $user = $this->userinfoModelClass::find()->where(['=','userid',$userid])->one();
                    if ($user) {
                        $companyrole = $this->companyModelClass::findBySql("select * from weixin_finance_company where FIND_IN_SET(" . $user['departmentid'] . ",dept) $where order by id desc")->asArray()->one();
                        if ($companyrole) {
                            $company = $this->userinfoModelClass::find()->where(['=','userid',$companyrole['userid']])->one();
                            if ($company) {
                                $index = array_search($company['userid'],$approvalusers);
                                if($index>-1){
                                  array_unshift($needremove,$index);
                                }
                                array_unshift($item,$this->getItem($company));
                            }
                        }
                    }
                }else{//角色
                    $roleInfo = $this->roleToUser($userid,$approval,$condition);
                    if ($roleInfo){
                      $index = array_search($roleInfo['ItemUserId'],$approvalusers);
                      if($index>-1){
                        array_unshift($needremove,$index);
                      }
                      $approvalusers[] = $roleInfo['ItemUserId'];
                      array_unshift($item,$roleInfo);
                      
                    }
                    
                    
                
                }
                
                if(count($item)>0){
                    array_reverse($item);
                    $e =array(
                        'NodeStatus' => 1,
                        'Items' => array('Item'=>$item),
                        'NodeAttr' => $approval['attr'],
                        'NodeType' => $approval['type'],
                        'NodeSkip' => isset($approval['skip'])?$approval['skip']:0
                    );
                    // 如果是上级
                    if ($approval['type'] == 3) $e['NodeLevel'] = $approval['level']; // 分辨领导层级
                    // 如果是标签
                    if($approval['type'] == 2) $e['NodeTagid'] = $approval['id']; // 分辨是哪个标签
                    // 如果是领导
                    if(in_array($approval['type'],[0,5,7,9,10])) $e['NodeRoleid'] = $approval['role']; // 判断是哪个类型的领导
                    $approvalnode[] = $e;
            
                }
                
            }
          
            $notifynode = array();
            foreach($templatedata['notify'] as $nodify){//抄送
                if(in_array($nodify['type'],array(0,4))){
                    $roleInfo = $this->roleToUser($userid,$nodify,$condition,1);
                    // 抄送人员一般是审批结束后发送消息，有特殊意义，即便与审批人重复也要添加
                    // if($roleInfo && !in_array($roleInfo['ItemUserId'],$approvalusers)){
                        $notifynode[] = $roleInfo;
                    // }
                }else if($nodify['type'] == 2){//标签
                    $taguser = $this->taguserModelClass::findBySql("select a.*,b.userid,b.`name`,b.avatar from weixin_oauser_taguser a LEFT JOIN weixin_leave_userinfo b on a.uId=b.id where tagId='".$nodify['id']."'")->asArray()->all();
                    foreach($taguser as $tu){
                        if($tu['userid'] != $userid){
                            // if(!in_array($tu['userid'],$approvalusers)){
                                $notifynode[] = $this->getItem($tu,1);
                            // }
                        }
                    }
                }else{
                    $user = $this->userinfoModelClass::find()->where(['=','id',$nodify['id']])->one();
                    if($user){
                        // if(!in_array($user['userid'],$approvalusers)){
                            $notifynode[] = $this->getItem($user,1);
                        // }
                    }
                }
            }
            // 去除重复节点
            if(count($needremove)>0){
              foreach ($needremove as $index) {
                  unset($approvalnode[$index]);
              }
            }
            $ret = array(
                'OpenTemplateId' => $row['templateId'],
                'OpenSpName' => $row['templateName'],
                'ApprovalNodes' => array('ApprovalNode'=>$approvalnode),
                'NotifyNodes' => array('NotifyNode'=>$notifynode),
                'NotifyAttr' => $row['notifyAttr']
            );
            return $ret;
        }
    }
    /**
     * 流程中插入新审批人
     * 审批人：
     * $inuser = [
     *      'userid' => ,
     *      'NodeType' => 1,
     *      'NodeAttr' => 1,
     *      'NodeSkip' => 0,
     *      'NodeRoleid' => 0,
     *      'NodeLevel' => 0,
     *      'NodeTagid' => 0,
     * ]
     * 参照人：
     * $condition = [
     *      'step' => 0,
     *      'userid' => ,
     *      'position' =>  'before/after'
     * ]
     * 参照步骤为待审批状态方可前置
     * 若指定的参照步骤为已审批状态，则以当前待审批步骤为参照步骤并默认前置
     * 若流程状态为已通过，则新审批人插入后流程状态更改为未通过（1）
     */
    public function insertFlow($flow, $inuser, $condition)
    {
        $approvalNode = $flow['data']['ApprovalNodes']['ApprovalNode'];
        $userid = is_array($inuser)?$inuser['userid']:$inuser;
        foreach($approvalNode as $k=>$n){
            foreach($n['Items']['Item'] as $j=>$i){
                if($i['ItemUserId'] == $userid){
                    return $flow;
                }
            }
        }
        $condition = $this->validationFlow($approvalNode, $condition);
        if (isset($condition['step'])) {
            $user = $this->userinfoModelClass::find()->where(['and',["=","userid",$userid],["=","status",1],["=","st",1]])->one();
            $item = $this->getItem($user);
            $node = $approvalNode[$condition['step']];
            if($node['NodeAttr']==2 && count($node['Items']['Item'])>1){
                $node['Items']['Item'][] = $item;
                $approvalNode[$condition['step']] = $node;
            }else{
                $node['NodeStatus'] = 1;
                $node['NodeAttr'] = isset($inuser['NodeAttr'])?$inuser['NodeAttr']:1;
                $node['NodeType'] = isset($inuser['NodeType'])?$inuser['NodeType']:1;
                $node['NodeSkip'] = isset($inuser['NodeSkip'])?$inuser['NodeSkip']:0;
                if(isset($inuser['NodeRoleid'])){
                    $node['NodeRoleid'] = $inuser['NodeRoleid'];
                }
                if(isset($inuser['NodeLevel'])){
                    $node['NodeLevel'] = $inuser['NodeLevel'];
                }
                if(isset($inuser['NodeTagid'])){
                    $node['NodeTagid'] = $inuser['NodeTagid'];
                }
                $node['Items']['Item'] = array($item);
                $node=array($node);
                if($condition['position'] == 'before'){
                    array_splice($approvalNode, $condition['step'], 0, $node);
                }else if($condition['step'] == count($approvalNode)-1){
                    $approvalNode[] = $node[0];
                }else{
                    array_splice($approvalNode, $condition['step']+1, 0, $node);
                }
            }
        }
        $flow['data']['ApprovalNodes']['ApprovalNode'] = $approvalNode;
        return $flow;
    }
    //流程插入条件验证
    private function validationFlow($node, $condition){
        if(isset($condition['step'])){
            if(!isset($node[$condition['step']])){
                $condition['step']-=1;
                $condition['position']= 'after';
            }else{
                if($node[$condition['step']]['NodeStatus']==2){
                    $condition['step'] +=1;
                    $condition['position'] = 'before';
                    return $this->validationFlow($node, $condition);
                }
            }
        }else{
            foreach($node as $k=>$n){
                foreach($n['Items']['Item'] as $j=>$i){
                    if($i['ItemUserId']== $condition['userid']){
                        if($n['NodeStatus']==2) {
                            $condition['step'] = $k+1;
                            $condition['position'] = 'before';
                            return $this->validationFlow($node, $condition);
                        }else {
                            $condition['step'] = $k;
                            return $condition;
                        }
                    }
                }
            }
        }
        return $condition;
    }

    /**
     * 流程中删除审批人
     * $condition = [
     *      'step' => 0,
     *      'userid' => 
     * ]
     * 若删除后剩余的审批人都为已审批状态，则流程状态设置为已通过（2）
     */
    public function deleteFlow($flow, $condition)
    {
        $approvalNode = $flow['data']['ApprovalNodes']['ApprovalNode'];
        if(isset($condition['step'])){
            array_splice($approvalNode, $condition['step'], 1);
        }else{
            foreach($approvalNode as $k=>$node){
                $items = $node['Items']['Item'];
                if(count($items) == 1 && $items[0]['ItemUserId'] == $condition['userid']){
                    array_splice($approvalNode, $k, 1);
                    break;
                }else{
                    foreach($items as $kk=>$item){
                        if($item['ItemUserId'] == $condition['userid']){
                            array_splice($items, $kk, 1);
                            break;
                        }
                    }
                    $approvalNode[$k]['Items']['Item'] = $items;
                }
            }
        }
        $status = 0;
        $step = 0;
        if ($flow['data']['OpenSpstatus'] == 1) {
            foreach ($approvalNode as $k => $node) {
                $status = $node['NodeStatus'];
                if ($status == 2) {
                    $step++;
                }
            }
        }
        $flow['data']['ApprovalNodes']['ApprovalNode']= $approvalNode;
        if($status && $step){
            $flow['data']['OpenSpstatus'] = $status;
            $flow['data']['approverstep'] = count($approvalNode)==$step?$step-1:$step;
        }
        return $flow;
    }

    public function test($id,$level){
        // echo($this->getDepartForLevel($id,$level));
    }

    private function getItem($user,$t=0){
        return $t?array(
                        'ItemName' => $user['name'],
                        'ItemParty' => '',
                        'ItemImage' => $user['avatar'],
                        'ItemUserId' => $user['userid']
                    ):array(
                        'ItemName' => $user['name'],
                        'ItemParty' => '',
                        'ItemImage' => $user['avatar'],
                        'ItemUserId' => $user['userid'],
                        'ItemStatus' => 1,
                        'ItemSpeech' => '',
                        'ItemOpTime' => 0
                    );
    }

    private function getDepartForLevel($id,$level){
        if($level == 1)return $id;
        $depart = $this->departmentModelClass::findOne($id);
        if($level == 2){
            return $depart['parentid']?$depart['parentid']:$id;
        }else{
            $level--;
            return $this->getDepartForLevel($depart['parentid'],$level);
        }
    }

    /**
     * 获取标签名
     */
    public function getUserTagName($id)
    {
        if($id){
            $tagdata = $this->usertagModelClass::findOne($id);
        }else{
            $tagdata = '审批组';
        }        
        return $tagdata;
    }
    /**
     * 角色名称
     */
    public function getRoleName($id)
    {
        $tagdata = '审批组';
        if($id){
            $temp = $this->roleModelClass::findOne($id);
            if ($temp) $tagdata = $temp['rolename'];
        }      
        return $tagdata;
    }
    // ***********************************************  启动流程  *************************************************

        /**
   * $userid 流程申请人，
   * $thirdNo 流程单号
   * $templateid 模板id
   * $condition 模板条件
   * $agentid 应用id
   */
    public function startFlow($userid,$templateid,$condition,$data){

        if (!$userid){
          throw new Exception('userid is null');
        }
        if (!$templateid){
          throw new Exception('templateid is null');
        }
        list($msec, $sec) = explode(' ', microtime());
        $thirdNo =  substr(sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000).base64_encode($userid),0,20);
        $userinfo = $this->userinfoModelClass::find()->where(['and',["=","userid",$userid]])->one();
        
        try {
          $flow = $this->flowParse($userid, $templateid,$condition);
          $usesealflow = array(
            'errcode' => 0,
            'errmsg' => 'ok',
            'data' => array(
              'ThirdNo' => $thirdNo,
              'OpenTemplateId' => $flow['OpenTemplateId'],
              'OpenSpName' => $flow['OpenSpName'],
              'OpenSpstatus' => 1,
              'ApplyTime' => time(),
              'ApplyUsername' =>$userinfo['name'],
              'ApplyUserParty' => '',
              'ApplyUserImage' => $userinfo['avatar'],
              'ApplyUserId' => $userinfo['userid'],
              'ApprovalNodes' => $flow['ApprovalNodes'],
              'NotifyNodes' => $flow['NotifyNodes'],
              'approverstep' => 0
            )
          );
          $applydata = array(
            'agentid' => $this->_agentId,
            'thirdNo' => ''.$thirdNo,
            'data' => json_encode($usesealflow),
            'step' => 0,
            'status' => 1,
            'notifyAttr' => $flow['NotifyAttr']
          );
          $approvalUserid = array();
          $approvalUsername = array();
          foreach ($flow['ApprovalNodes']['ApprovalNode'][0]['Items']['Item'] as $item) {
            $approvalUserid[] = $item['ItemUserId'];
            $approvalUsername[] = $item['ItemName'];
          }
          $data = array(
          'agentId'=>$this->_agentId,
          'userId' => $userid,
          'userName' => $userinfo['name'],
          'departmentid' => $userinfo['departmentid'],
          'department' => $userinfo['departmentname'],
          'thirdNo' => $thirdNo,
          'type' => 0,
          'data' => json_encode($data)
          );
          $data['approvalUserid'] = implode('|', $approvalUserid);
          $data['approvalUsername'] = implode('|', $approvalUsername);
          $data['status'] = 1;
          // 基础数据
          $temp = new WeixinOaApprovalInfo($data);
          $temp->save();
          // 保存流程
          $applydata = new WeixinOaApprovaldata($applydata);
          $applydata->save();
        } catch (\Throwable $th) {
          
          throw $th;
        }
      
        return array('thirdNo'=>$thirdNo,'approvalUserid'=>$approvalUserid);
    }

    /**
     * 提交前预览流程
     */
    public function previewFlow($userid,$templateid,$condition) {
  
      try {
        $flow = $this->flowParse($userid, $templateid,$condition);
        
      } catch (\Throwable $th) {
        return array('errorMessage'=> $th->getMessage());
      }
  
      if($flow){
          foreach ($flow['ApprovalNodes']['ApprovalNode'] as $k=>$r) {
              $tmparr = array();
              if(count($r['Items']['Item'])>1){
                $tmparr['title'] = '直接上级';
                if ($r['NodeType']==2 && isset($r['NodeTagid'])){
                  
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
          $notifier = array();
          foreach ($flow['NotifyNodes']['NotifyNode'] as $r) {
              $notifier[] = $r['ItemName'];
          }
          $step = intval($flow['approverstep'])-1;

      }


    
      return  array('viewdata'=>array('step'=>$step,'approval'=>$approvaldata,'notify'=>$notifier,'templateid'=>$flow['OpenTemplateId']),'statusCn'=>array('','审批中','已同意','已驳回','已取消'));
    }
    public function flowInfodata($thirdNo) { 
      $flowdata= WeixinOaApprovalInfo::find()->alias('i')->select('u.avatar as avatarUrl,i.*')->where(['and',['=','agentId',$this->_agentId],['thirdNo'=>$thirdNo]])
      ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=i.userId')->asArray()->one();
      return $flowdata;
    }
    // 同意、驳回、撤销等
    public function changeFlow($userid,$status,$par)
    {
        
        
          
        $agentid =$par['agentId']?$par['agentId']:$this->_agentId;
        $thirdNo=$par['thirdNo'];
        $speech=$par['speech'];
        if (!$userid) throw new Exception('userid 不能为空');
        if (!$thirdNo) throw new Exception('thirdNo 不能为空');

        $data = WeixinOaApprovalInfo::find()->where(['and',['=','agentId',$this->_agentId],['=','thirdNo',$thirdNo]])->asArray()->one();
        // 是否是当前审批人
        if($status==4){
          // 只能本人撤消
          if ($data['userId']!=$userid){
            throw new Exception("只能本人撤销");
          }
        }else{
          if ($data['approvalUserid'] && !in_array($userid,explode('|',$data['approvalUserid']))){
           throw new Exception('当前审批人是：'.$data['approvalUsername']);
          }
        }

        $approveres = WeixinOaApprovaldata::find()->where(['and',["=","agentid",$agentid],["=","thirdNo",$thirdNo]])->asArray()->one();
        
        
        if($approveres){
          $optime = time();
          $ret = ['isfinish'=>0];
          $nextdata = [];
          $approvedata = [];
          $step = intval($approveres['step']);
          $approvearr = json_decode($approveres['data'],true);
        
          
      
          if(in_array($status,[3,4]) || ($status==2 && $step==count($approvearr['data']['ApprovalNodes']['ApprovalNode'])-1&&$approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeAttr']!=2)){//不是会签
            
            $approvearr['data']['OpenSpstatus'] = $status;
            $nextdata['status'] = $status;
            $approvedata['status'] = $status;

            if($status==2){
              $ret['isfinish']=1;
            }

            if($approveres['notifyAttr']>1){
              $notifyUserid = array();
              $notifyUsername = array();
              foreach($approvearr['data']['NotifyNodes']['NotifyNode'] as $notify){
                $notifyUserid[] = $notify['ItemUserId'];
                $notifyUsername[] = $notify['ItemName'];
              }
              $ret['tonotify'] = array('userid'=>$notifyUserid,'username'=>$notifyUsername);
            }
          }
          
          if(in_array($status,[2,3])){
            $logdata = array(
              'agentid'=>$agentid,
              'thirdNo'=>$thirdNo,
              'userId'=>$userid,
              'status'=>$status,
              'speech'=>$speech,
              'opTime'=>$optime
            );

            foreach($approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'] as $k=>$item){
              if($item['ItemUserId'] == $userid){
                $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeStatus'] = $status;

                $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'][$k]['ItemStatus'] = $status;
                
                $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'][$k]['ItemSpeech'] = $speech;
                $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'][$k]['ItemOpTime'] = $optime;
                $logdata['userName'] = $item['ItemName'];
                
              }
            }
          }
          $isNext = true;
          foreach($approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'] as $k=>$item){
            // 当前审批人无法执行，因为ItemStatus=2
            // 会签节点审批后不会通知同级审批人
            if($item['ItemStatus']==1 && $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeAttr']==2){//是会签节点 
            
              $isNext = false;
              if($status==2)$approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeStatus'] = 1;
            }
            
          }
          if (!$isNext) $status = 1;



          if($status==2 && $isNext){
            if ($step==count($approvearr['data']['ApprovalNodes']['ApprovalNode'])-1){
              $approvearr['data']['OpenSpstatus'] = $status;
              $nextdata['status'] = $status;
              $approvedata['status'] = $status;

              $ret['isfinish']=1;

              if($approveres['notifyAttr']>1){
                $notifyUserid = array();
                $notifyUsername = array();
                foreach($approvearr['data']['NotifyNodes']['NotifyNode'] as $notify){
                  $notifyUserid[] = $notify['ItemUserId'];
                  $notifyUsername[] = $notify['ItemName'];
                }
                $ret['tonotify'] = array('userid'=>$notifyUserid,'username'=>$notifyUsername);
              }
            }
            
            if($step<count($approvearr['data']['ApprovalNodes']['ApprovalNode'])-1)$step++;
            $approvearr['data']['approverstep'] = $step;
            $approvalUserid = array();
            $approvalUsername = array();
            foreach($approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'] as $k=>$item){
              if ($item['ItemStatus']==1){
                $approvalUserid[] = $item['ItemUserId'];
                $approvalUsername[] = $item['ItemName'];
              }
              
            }
            $nextdata['approvalUserid'] = implode('|',$approvalUserid);
            $nextdata['approvalUsername'] = implode('|',$approvalUsername);
            $nextdata['approvalStep'] = $step;
            $ret['touser'] = $nextdata['approvalUserid'];
          
          }
        
          if($nextdata){
              $ret['nextdata'] = $nextdata;
          }
          
          
          $approvedata['data'] = json_encode($approvearr);
          $approvedata['step'] = $step;
          
          $approveres['status'] = $status;
          $approveres['data'] = $approvedata['data'];
          $approveres['step'] = $approvedata['step'];
          

          $ret['approveres']=$approveres;
          $ret['logdata']=$logdata;
        
          return $ret;
        }
        

      return 0;
    }
    public function updateAfterFlowChange($ret,$userid,$status,$condition,$transaction)
    {

      $thirdNo = $condition['thirdNo'];
      if (!$thirdNo) throw new Exception("thirdNo 不能为空");
      try {
        $noticeuserids=[];
        $project = WeixinOaApprovalInfo::find()->where(['and',['=','agentId',$this->_agentId],['thirdNo'=>$thirdNo]])->asArray()->one();
        if (!$project) throw new Exception("找不到流程信息");
        
        if ($ret){
          if($ret['approveres']){
            WeixinOaApprovaldata::updateAll($ret['approveres'],'id='.$ret['approveres']['id']);
          }
          if($ret['logdata']){
              $log = new WeixinOaApprovalLog($ret['logdata']);
              $log->save();
          }
          
      
          $temp = ['status'=>$status];
          if($ret['isfinish']) {//没有下个审批人,可能是会签，直接结束
            $noticeuserids[]=$project['userId'];
            if($ret['tonotify']){
          
              foreach($ret['tonotify']['userid'] as $k=>$v){
                $this->setNotifylog(array('thirdNo'=>$thirdNo,'userId'=>$v,'userName'=>$ret['tonotify']['username'][$k],'agentid'=>$this->_agentId));
                
              }
              $noticeuserids = array_merge($noticeuserids,$ret['tonotify']['userid']);
            }

          }else if ($ret['nextdata']&&$ret['nextdata']['approvalUserid']) { // 有下个审批人，说明没结束
            if (!$ret['isfinish']) {
              $status = 1;
            }
            $temp = ['status'=>$status,'approvalUserid'=>$ret['nextdata']['approvalUserid'],'approvalUsername'=>$ret['nextdata']['approvalUsername']];

            $noticeuserids = array_merge($noticeuserids,explode('|',$ret['nextdata']['approvalUserid']));

          }else {
            
            if (!$ret['isfinish']) { // 可能是会签
              $status = 1;
              $temp['status']=$status;
            }
          

          }
        }
        switch ($condition['act']) {
          case 'cancel':
            
            $noticeuserids[]=$project['userId'];
            $temp['status']=4;
            break;
          case 'reject':
  
            $noticeuserids[]=$project['userId'];
            $temp['status']=3;
            break;
          default:
            break;
        }

        WeixinOaApprovalInfo::updateAll($temp,["thirdNo"=>$thirdNo,"agentId"=>$this->_agentId]);
        

      } catch (\Throwable $th) {
        $transaction->rollBack();
        throw $th;
      }
      if ($noticeuserids){
        // 去重
        $noticeuserids = array_unique($noticeuserids);
      }
      
      return array('noticeuserids'=>$noticeuserids);
    }
    private function setNotifylog($data){//保存抄送人信息
		if($data){
			
      $res = WeixinOaNotifyLog::find()->where(['agentid'=>$this->_agentId,'thirdNo'=>$data['thirdNo'],'userId'=>$data['userId']])->one();
			if(!$res){
        $temp = new WeixinOaNotifyLog($data);
        $temp->save();
				
			}
		}
	}

}