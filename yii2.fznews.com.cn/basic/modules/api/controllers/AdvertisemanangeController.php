<?php

namespace app\modules\api\controllers;
use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WorkflowParse;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\models\FzrbsCompany;
use app\modules\api\models\FsysNode;
use app\modules\api\models\WeixinOaApprovaldata;
use app\modules\api\models\WeixinOaApprovalInfo;
use app\modules\api\models\WeixinOaApprovalLog;
use app\modules\api\models\WeixinOaAttachment;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinOaNotifyLog;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\models\WeixinOaDepartment;
use app\modules\api\models\Advorder;
use app\modules\api\models\Advitem;
use app\modules\api\models\FzrbsBudgetDict;
use app\modules\api\models\WeixinOaRole;
use app\modules\api\models\WeixinOaTemplates;
use app\modules\api\models\WeixinOauserTaguser;
use app\modules\api\models\WeixinOrderTemplate;
use app\modules\api\models\advsize;
use Exception;
use PHPExcel;
use PHPExcel_IOFactory;
use yii\db\Expression;


class AdvertisemanangeController extends ApiBase{
  public $modelClass = 'app\modules\api\models\WeixinOAUserInfo';
  protected $agentId= 1000083;
  
  protected $statusCn = array('','审批中','已同意','已驳回','已取消');
  protected $userinfo = array();
  protected $LeaderRoleid = 17;
	protected $DirectorRoleid = 18;

  public function init()
  {
      parent::init();
      $this->userinfo = $this->getUserinfo($this->_adminInfo['wxuserid']);
  }
  private function getUserinfo($userid)
  {
      $userinfo = WeixinOAUserInfo::find()->where(['=', 'userid', $userid])->asArray()->one();
      return $userinfo;
  }
  
/**
    * 检查是否是本人操作
    * @param string $authors SYS_AUTHORS字段值
    * @return bool
    */
   private function checkAuthor($authors)
   {
       $currentUserid = $this->_adminInfo['wxuserid'] ?? '';
       // 如果 authors 为空，则允许操作（可能是历史数据）
       if (empty($authors)) {
           return true;
       }
       return $authors == $currentUserid;
   }

/**
     * 检查用户是否有指定角色
     * @param string $roleName 角色名称（如'广告审核'）
     * @param int|null $dept 指定部门（可选，不传则使用用户的部门权限）
     * @return bool
     */
    private function checkRole($roleName, $dept = null)
    {
        $userid = $this->_adminInfo['wxuserid'];
        $dept=$dept?$dept:$this->userinfo['departmentid'];
        $deptsql = "and FIND_IN_SET($dept, dept)";
        $sql = "SELECT userid,dept from weixin_oa_flowrole where  userid='" . $userid . "' and  FIND_IN_SET(" . $this->agentId . ",agent)  and role in (select id from weixin_oa_role where rolename = '$roleName') $deptsql";
        $result = WeixinOaFlowrole::findBySql($sql)->asArray()->all();
        return !empty($result);
    }

  /**
     * 获取订单详情
     */
  public function actionGetorderbyid(){
      $orderid = $this->_request['orderid'];
      $order = Yii::$app->paymentdb->createCommand("SELECT * FROM advorder WHERE SYS_DOCUMENTID=:id", [':id'=>$orderid])->queryOne();
      return array('data'=>$order);
  }


  /**
   * 获取订单列表（分页多条件筛选）
   * @return array
   */
  public function actionGetorderlist()
  {
      try {
          // 分页参数
          $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
          $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
          $offset = $limit * ($page - 1);

          // 获取表名
          $tableName = Advorder::tableName();

          // 构建WHERE条件
          $where = 'SYS_DOCUMENTID>0';
          if ($this->_request['withdeleted']) {
          }else{
            if ($this->_request['SYS_DELETEFLAG']){
              $where.= " and SYS_DELETEFLAG=1";
            }else{
              $where.= " and SYS_DELETEFLAG=0";
            }
            
          }
          $params = [];

          // 合同编号筛选
          if (!empty($this->_request['contractserial'])) {
              $where .= " AND contractserial LIKE :contractserial";
              $params[':contractserial'] = '%' . $this->_request['contractserial'] . '%';
          }
          if (!empty($this->_request['contractid'])) {

              $where .= " AND contractid = :contractid";
              $params[':contractid'] = $this->_request['contractid'];
          }
          if (!empty($this->_request['departmentid'])) {

              $where .= " AND departmentid = :departmentid";
              $params['departmentid'] = $this->_request['departmentid'];
          }
          

          // 主体筛选
          if (!empty($this->_request['partb'])) {
              $partb = is_array($this->_request['partb']) ? implode(',', $this->_request['partb']) : $this->_request['partb'];
              $where .= " AND FIND_IN_SET(:partb, partb)";
              $params[':partb'] = $partb;
          }

          // 客户筛选
          if (!empty($this->_request['AO_Customer_ID'])) {
              $customerId = is_array($this->_request['AO_Customer_ID']) ? implode(',', $this->_request['AO_Customer_ID']) : $this->_request['AO_Customer_ID'];
              $where .= " AND FIND_IN_SET(:AO_Customer_ID, AO_Customer_ID)";
              $params[':AO_Customer_ID'] = $customerId;
          }

          // 部门筛选
          if (!empty($this->_request['AO_Org_ID'])) {
              $orgIds = is_array($this->_request['AO_Org_ID']) ? $this->_request['AO_Org_ID'] : explode(',', $this->_request['AO_Org_ID']);
              $orgIds = array_filter($orgIds);
              if (!empty($orgIds)) {
                  $orgIdsStr = implode(',', $orgIds);
                  $where .= " AND AO_Org_ID IN ($orgIdsStr)";
              }
          }

          // 业务员筛选
          if (!empty($this->_request['AO_Salesman_ID'])) {
              $salesmanId = is_array($this->_request['AO_Salesman_ID']) ? implode(',', $this->_request['AO_Salesman_ID']) : $this->_request['AO_Salesman_ID'];
              $where .= " AND FIND_IN_SET(:AO_Salesman_ID, AO_Salesman_ID)";
              $params[':AO_Salesman_ID'] = $salesmanId;
          }
          if (!empty($this->_request['SYS_DELETEFLAG'])) {

              $where .= " AND SYS_DELETEFLAG = :SYS_DELETEFLAG";
              $params[':SYS_DELETEFLAG'] = $this->_request['SYS_DELETEFLAG'];
          }
          
          // 刊物筛选
          if (!empty($this->_request['publication'])) {

              $where .= " AND publicationid = :publication";
              $params['publication'] = $this->_request['publication'];
          }
          if (!empty($this->_request['publicationid'])) {
              $pubId = is_array($this->_request['publicationid']) ? implode(',', $this->_request['publicationid']) : $this->_request['publicationid'];
              $where .= " AND publicationid = :publicationid";
              $params[':publicationid'] = $pubId;
          }

          // 版位筛选
          if (!empty($this->_request['AI_Field_ID'])) {
              $fieldId = is_array($this->_request['AI_Field_ID']) ? implode(',', $this->_request['AI_Field_ID']) : $this->_request['AI_Field_ID'];
              $where .= " AND AI_Field_ID = :AI_Field_ID";
              $params[':AI_Field_ID'] = $fieldId;
          }

          // 创建时间范围筛选
          if (!empty($this->_request['SYS_CREATED_START'])) {
              $where .= " AND SYS_CREATED >= :SYS_CREATED_START";
              $params[':SYS_CREATED_START'] = $this->_request['SYS_CREATED_START'];
          }
          if (!empty($this->_request['SYS_CREATED_END'])) {
              $where .= " AND SYS_CREATED <= :SYS_CREATED_END";
              $params[':SYS_CREATED_END'] = $this->_request['SYS_CREATED_END'];
          }

          // 权限控制：只允许查看与本人相关的订单，或本人有权限查看的部门
          $userId = $this->_adminInfo['wxuserid'];
          $depts = $this->getDepts();

          // 构建与我相关的条件
          // 原来从订单表查assistant，现在改为从广告项表查
          $myRelatedCondition = "(o.AO_Salesman_ID = '{$userId}' ";
          // $myRelatedCondition .= " OR EXISTS(SELECT 1 FROM advitem WHERE AI_OrderID = o.SYS_DOCUMENTID AND FIND_IN_SET('{$userId}', assistant)) ";
          $myRelatedCondition .= " OR  o.SYS_AUTHORS = '{$userId}' ";
          $myRelatedCondition .= " OR  o.AO_Salesman_ID = '{$userId}' ";
          $myRelatedCondition .= ")";
          

          // 构建权限条件
          if (sizeof($depts) > 0) {
              // 没有指定部门时，需要查询我创建的或有权限的部门
              if (empty($this->_request['departmentid'])) {
                  $permissionCondition = "(".$myRelatedCondition.") OR (departmentid IN (" . implode(',', $depts) . "))";
              } else {
                  $permissionCondition = "(departmentid IN (" . implode(',', $depts) . "))";
              }
          } else {
              // 没有部门权限时，只能查询与我相关的
              $permissionCondition = $myRelatedCondition;
          }
          $where .= " AND ({$permissionCondition})";

          // 排序
          $order = 'SYS_DOCUMENTID DESC';
          if ($this->_request['orderby']) {
              $orderby = $this->_request['orderby'];
              $orderParts = explode(' ', trim($orderby));
              if (count($orderParts) == 2) {
                  $field = $orderParts[0];
                  $direction = strtoupper($orderParts[1]) == 'ASC' ? 'ASC' : 'DESC';
                  $allowedFields = ['SYS_DOCUMENTID', 'contractserial', 'partbname', 'AO_Customer', 'AO_Salesman', 'SYS_CREATED'];
                  if (in_array($field, $allowedFields)) {
                      $order = $field . ' ' . $direction;
                  }
              }
          }

          // 查询总数
          $sqlCount = "SELECT COUNT(*) as total FROM {$tableName} o WHERE {$where}";
          $countResult = Yii::$app->paymentdb->createCommand($sqlCount)->bindValues($params)->queryOne();
          $total = $countResult['total'] ?? 0;

          // 查询数据
          $sql = "SELECT * FROM {$tableName} o WHERE {$where} ORDER BY {$order} LIMIT {$offset}, {$limit}";
          $list = Yii::$app->paymentdb->createCommand($sql)->bindValues($params)->queryAll();
          
          // 构建返回结果
          $this->_result['data'] = $list;
          // $this->_result['rows'] = $list;
          $this->_result['total'] = intval($total);
          $this->_result['current'] = intval($page);
          $this->_result['pageSize'] = intval($limit);
          $this->_result['success'] = true;

          return $this->_result;
      } catch (Exception $e) {
          return [
              'data' => [],
              'total' => 0,
              'success' => false,
              'errorMessage' => $e->getMessage()
          ];
      }
  }

  /**
   * 保存订单
   * @return array
   */
  public function actionSaveorder()
  {
      try {
          $obj = $this->_request;
          $tableName = Advorder::tableName();

          // 验证必填字段
          if (empty($obj['contractid'])) {
              return ['errorMessage' => '合同不能为空'];
          }
          if (empty($obj['partb'])) {
              return ['errorMessage' => '主体不能为空'];
          }
          if (empty($obj['AO_Customer_ID'])) {
              return ['errorMessage' => '客户不能为空'];
          }
          if (empty($obj['AO_Org_ID'])) {
              return ['errorMessage' => '部门不能为空'];
          }

          // 处理数组字段
          if (isset($obj['partb']) && is_array($obj['partb'])) {
              $partbOriginal = $obj['partb'];
              $partbValues = array_map(function($item) {
                  return $item['value'] ?? $item['id'] ?? $item;
              }, $partbOriginal);
              $obj['partb'] = implode(',', array_filter($partbValues));
              
              $partbNames = array_map(function($item) {
                  return $item['label'] ?? $item['company'] ?? $item;
              }, $partbOriginal);
              $obj['partbname'] = implode(',', array_filter($partbNames));
          }

          if (isset($obj['AO_Customer_ID']) && is_array($obj['AO_Customer_ID'])) {
              $obj['AO_Customer'] = $obj['AO_Customer_ID']['label'] ?? $obj['AO_Customer_ID']['company'] ?? '';
              $obj['AO_Customer_ID'] = $obj['AO_Customer_ID']['value'] ?? $obj['AO_Customer_ID']['id'] ?? '';
          }

          if (isset($obj['AO_Salesman_ID']) && is_array($obj['AO_Salesman_ID'])) {
              $obj['AO_Salesman'] = $obj['AO_Salesman_ID']['label'] ?? '';
              $obj['AO_Salesman_ID'] = $obj['AO_Salesman_ID']['value'] ?? '';
          }

          if (isset($obj['assistant']) && is_array($obj['assistant'])) {
              $obj['assistantname'] = $obj['assistant']['label'] ?? '';
              $obj['assistant'] = $obj['assistant']['value'] ?? '';
          }

          if (isset($obj['publicationid']) && is_array($obj['publicationid'])) {
              $obj['publication'] = $obj['publicationid']['label'] ?? '';
              $obj['publicationid'] = $obj['publicationid']['value'] ?? '';
          }

          // 如果是新增，设置创建信息
          if (empty($obj['SYS_DOCUMENTID'])) {
              $obj['SYS_DELETEFLAG'] = 1;
              $obj['SYS_CREATED'] = date('Y-m-d H:i:s');
              $obj['SYS_AUTHORS'] = $this->_adminInfo['wxuserid'] ?? '';
          }

          // 保存数据
          if (!empty($obj['SYS_DOCUMENTID'])) {
              // 检查是否是本人操作
              $checkSql = "SELECT SYS_AUTHORS FROM {$tableName} WHERE id = :id";
              $existingOrder = Yii::$app->paymentdb->createCommand($checkSql)->bindValues([':id' => $obj['SYS_DOCUMENTID']])->queryOne();
              
              if (!$this->checkAuthor($existingOrder['SYS_AUTHORS'])) {
                  return ['errorMessage' => '只有本人才能操作'];
              }
              
              // 更新
              $id = $obj['SYS_DOCUMENTID'];
              unset($obj['SYS_DOCUMENTID']);
              
              // 构建更新字段
              $updateFields = [];
              $updateParams = [];
              foreach ($obj as $key => $value) {
                  if ($key !== 'id') {
                      $updateFields[] = "`$key` = :$key";
                      $updateParams[":$key"] = $value;
                  }
              }
              $updateParams[':id'] = $id;
              
              $sql = "UPDATE {$tableName} SET " . implode(', ', $updateFields) . " WHERE id = :id";
              Yii::$app->paymentdb->createCommand($sql)->bindValues($updateParams)->execute();
              
              $model = ['id' => $id] + $obj;
          } else {
              // 新增
              $columns = array_keys($obj);
              $values = array_values($obj);
              $placeholders = array_map(function($col) { return ":$col"; }, $columns);
              
              $sql = "INSERT INTO {$tableName} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
              Yii::$app->paymentdb->createCommand($sql)->bindValues($obj)->execute();
              
              $insertId = Yii::$app->paymentdb->getLastInsertID();
              $obj['id'] = $insertId;
              $model = $obj;
          }

          return ['data' => $model, 'success' => true];
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage()];
      }
  }

  /**
   * 更新订单
   * @return array
   */
  public function actionUpdateorder()
  {
      try {
          $par = json_decode(Yii::$app->request->getRawBody(), true);
        
          
          // 检查是否是本人操作
          $checkSql = "SELECT SYS_AUTHORS FROM " . Advorder::tableName() . " WHERE SYS_DOCUMENTID = :id";
          $existingOrder = Yii::$app->paymentdb->createCommand($checkSql)->bindValues([':id' => $par['SYS_DOCUMENTID']])->queryOne();
          
          if (!$this->checkAuthor($existingOrder['SYS_AUTHORS'])) {
              return ['errorMessage' => '只有本人才能操作'];
          }
          
          // 检查是否有回款，如果有回款则禁止更新
          $checkReceivedSql = "SELECT AO_ReceivedMoney FROM " . Advorder::tableName() . " WHERE SYS_DOCUMENTID = :id";
          $receivedResult = Yii::$app->paymentdb->createCommand($checkReceivedSql)->bindValues([':id' => $par['SYS_DOCUMENTID']])->queryOne();
          $amountReceived = floatval($receivedResult['AO_ReceivedMoney'] ?? 0);
          if ($amountReceived > 0) {
              return ['errorMessage' => '该订单已有回款，无法更新'];
          }
          
          $par['SYS_LASTMODIFIED']=date('Y-m-d H:i:s');
         
          Yii::$app->paymentdb->createCommand()->update(Advorder::tableName(), $par, ['SYS_DOCUMENTID'=>$par['SYS_DOCUMENTID']])->execute();
          
          // 客户信息（甲方）
          $customerId = $par['AO_Customer_ID'] ?? '';
          $customerName = $par['AO_Customer'] ?? '';

          $temp = [
                'AI_Customer_ID' => $customerId, 
                'AI_Customer' => $customerName,
                'AI_Salesman' => $par['AO_Salesman'], 
                'AI_Salesman_ID' => $par['AO_Salesman_ID'],
                'AI_Org' => $par['AO_Org'], 
                'AI_Org_ID' => $par['AO_Org_ID'],
            ];

          // 同步更新广告表的客户信息
          Yii::$app->paymentdb->createCommand()->update(
              'advitem',
              $temp,
              ['AI_OrderID' => $par['SYS_DOCUMENTID']]
          )->execute();
          $this->updateOrderAmounts($par['SYS_DOCUMENTID']);
          
          return ['errorMessage' => ''];
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage()];
      }
  }

  /**
   * 删除订单
   * @return array
   */
  public function actionDeleteorder()
  {
      $transaction = Yii::$app->paymentdb->beginTransaction();
      try {
          $id = $this->_request['SYS_DOCUMENTID'] ?? $this->_request['id'] ?? null;

          if (empty($id)) {
              return ['errorMessage' => '订单ID不能为空'];
          }

          $tableName = Advorder::tableName();
          
          // 检查是否是本人操作并获取订单当前状态
          $checkSql = "SELECT SYS_DOCUMENTID,SYS_AUTHORS, SYS_DELETEFLAG FROM {$tableName} WHERE SYS_DOCUMENTID = :id";
          $existingOrder = Yii::$app->paymentdb->createCommand($checkSql)->bindValues([':id' => $id])->queryOne();
          
          if (!$existingOrder) {
              return ['errorMessage' => '订单不存在'];
          }
          
          if (!$this->checkAuthor($existingOrder['SYS_AUTHORS'])) {
              return ['errorMessage' => '只有本人才能操作'];
          }
          
          // 检查是否有回款，如果有回款则禁止删除
          $checkReceivedSql = "SELECT AO_ReceivedMoney FROM " . Advorder::tableName() . " WHERE SYS_DOCUMENTID = :id";
          $receivedResult = Yii::$app->paymentdb->createCommand($checkReceivedSql)->bindValues([':id' => $id])->queryOne();
          $amountReceived = floatval($receivedResult['AO_ReceivedMoney'] ?? 0);
          if ($amountReceived > 0) {
              return ['errorMessage' => '该订单已有回款，无法删除'];
          }

          $advitemTable = Advitem::tableName();

          // 根据当前状态决定软删除还是硬删除
          if ($existingOrder['SYS_DELETEFLAG'] == 1) {
              // 已经是删除状态，执行硬删除
              // 先硬删除关联的广告
              $deleteAdvSql = "DELETE FROM {$advitemTable} WHERE AI_OrderID = :orderId";
              Yii::$app->paymentdb->createCommand($deleteAdvSql)->bindValues([':orderId' => $id])->execute();

              // 再硬删除订单
              $sql = "DELETE FROM {$tableName} WHERE SYS_DOCUMENTID = :id";
              Yii::$app->paymentdb->createCommand($sql)->bindValues([':id' => $id])->execute();
          } else {
              // 正常状态，执行软删除
              // 先软删除关联的广告
              $deleteAdvSql = "UPDATE {$advitemTable} SET SYS_DELETEFLAG = 1 WHERE AI_OrderID = :orderId";
              Yii::$app->paymentdb->createCommand($deleteAdvSql)->bindValues([':orderId' => $id])->execute();

              // 再软删除订单
              $sql = "UPDATE {$tableName} SET SYS_DELETEFLAG = 1 WHERE SYS_DOCUMENTID = :id";
              Yii::$app->paymentdb->createCommand($sql)->bindValues([':id' => $id])->execute();
          }

          $transaction->commit();
          return ['success' => true];
      } catch (Exception $e) {
          $transaction->rollBack();
          return ['errorMessage' => $e->getMessage()];
      }
  }

  /**
   * 根据ID获取订单详情
   * @return array
   */
  public function actionGetbyid()
  {
      try {
          $id = $this->_request['SYS_DOCUMENTID'] ?? $this->_request['id'] ?? null;

          if (empty($id)) {
              return ['errorMessage' => '订单ID不能为空'];
          }

          $tableName = Advorder::tableName();
          $sql = "SELECT * FROM {$tableName} WHERE id = :id AND SYS_DELETEFLAG > 0";
          $model = Yii::$app->paymentdb->createCommand($sql)->bindValues([':id' => $id])->queryOne();

          if (!$model) {
              return ['errorMessage' => '订单不存在'];
          }

          return ['data' => $model];
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage()];
      }
  }

  /**
   * 根据合同ID获取相关订单
   * @return array
   */
  public function actionGetbycontract()
  {
      try {
          $contractid = $this->_request['contractid'] ?? null;

          if (empty($contractid)) {
              return ['errorMessage' => '合同ID不能为空'];
          }

          $tableName = Advorder::tableName();
          $sql = "SELECT * FROM {$tableName} WHERE SYS_DELETEFLAG > 0 AND contractid LIKE :contractid ORDER BY SYS_CREATED DESC";
          $list = Yii::$app->paymentdb->createCommand($sql)->bindValues([':contractid' => '%' . $contractid . '%'])->queryAll();

          return ['data' => $list];
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage()];
      }
  }

  /**
   * 获取组织架构树形结构
   * @return array
   */
  public function actionOrgs()
  {
      try {
          // 查询所有组织节点
          $tableName = FsysNode::tableName();
          $nodes = Yii::$app->paymentdb->createCommand("SELECT * FROM {$tableName}")->queryAll();
          
          // 将数据转换成树形结构
          $tree = $this->buildTree($nodes);
          
          return $tree;
      } catch (Exception $e) {
          return [];
      }
  }

  /**
   * 递归构建树形结构
   * @param array $nodes 扁平节点数组
   * @param int $pid 父节点ID
   * @return array
   */
  private function buildTree($nodes, $pid = 0)
  {
      $tree = [];
      foreach ($nodes as $node) {
          if ($node['NPARENTID'] == $pid) {
              $children = $this->buildTree($nodes, $node['NNODEID']);
              $nodeData = [
                  'id' => (string)$node['NNODEID'],
                  'label' => $node['STRNODENAME'],
                  'title' => $node['STRNODENAME'],
                  'value' => (string)$node['NNODEID'],
                  'NPARENTID' => $node['NPARENTID'],
              ];
              if (!empty($children)) {
                  $nodeData['children'] = $children;
              }
              $tree[] = $nodeData;
          }
      }
      return $tree;
  }

  /**
   * 获取用户有权限查看的部门列表
   * @return array
   */
  private function getDepts(){
      $power = '查看';
      $userid = $this->_adminInfo['wxuserid'];
      $arr = array();

      // 领导可以查看下属部门所有的订单
      $deptid = $this->userinfo['departmentid'];
      if ($this->userinfo['is_leader']){
          $depts = WeixinOaDepartment::findBySql("SELECT GROUP_CONCAT(id SEPARATOR ',') as ids from weixin_oa_department where id=$deptid or FIND_IN_SET($deptid,parentids)")->asArray()->one();
          if ($depts['ids']) {
              $arr = array_merge($arr,explode(',',$depts['ids']));
          }
      }

      // 查询角色权限
      $sql = "SELECT userid,dept from weixin_oa_flowrole where  userid='".$userid."' and  FIND_IN_SET(".$this->agentId.",agent)  and role in (select id from weixin_oa_role where FIND_IN_SET('$power',powername))";
      $result = WeixinOaFlowrole::findBySql($sql)->asArray()->all();

      if ($result) {
          foreach ($result as $e) {
              $arr = array_merge($arr,explode(',',$e['dept']));
          }
          $arr = array_unique($arr);
      }

      return $arr;
  }
  
  /**
   * 获取行业树形结构
   * @return array
   */
  public function actionTrades()
  {
      try {
          // 查询所有行业节点
          $sql = "SELECT id, label, parentid, depth FROM org ";
          $nodes = Yii::$app->paymentdb->createCommand($sql)->queryAll();
          
          // 将数据转换成树形结构
          $tree = $this->buildTradeTree($nodes);
          
          return $tree;
      } catch (Exception $e) {
          return [

              'message' => '获取行业数据失败: ' . $e->getMessage(),

          ];
      }
  }

  /**
   * 递归构建行业树形结构
   * @param array $nodes 扁平节点数组
   * @param int $parentid 父节点ID
   * @return array
   */
  private function buildTradeTree($nodes, $parentid = 0)
  {
      $tree = [];
      foreach ($nodes as $node) {
          if ((int)$node['parentid'] === (int)$parentid) {
              $children = $this->buildTradeTree($nodes, $node['id']);
              $nodeData = [
                  'id' => (string)$node['id'],
                  'label' => $node['label'],
                  'title' => $node['label'],
                  'value' => (string)$node['id'],
                  'parentid' => (int)$node['parentid'],
                  'depth' => (int)$node['depth'],
              ];
              if (!empty($children)) {
                $nodeData['children'] = $children;
              }
              $tree[] = $nodeData;
          }
      }
      return $tree;
  }
  

  /**
   * 根据广告类型获取规格列表
   * @return array
   */
  public function actionGetsizesbyadtype()
  {
      try {
          $adTypeId = $this->_request['adTypeId'] ?? null;

          // 构建WHERE条件
          $where = "SYS_DELETEFLAG = 0";
          $params = [];

          // 如果提供了广告类型ID，则添加过滤条件
          if (!empty($adTypeId)) {
              $where .= " AND E_AdType_ID = :adTypeId";
              $params[':adTypeId'] = $adTypeId;
          }

          // 查询广告规格表
          $sql = "SELECT * FROM advsize WHERE {$where} ORDER BY E_Order ASC, SYS_DOCUMENTID desc";
          $list = Yii::$app->paymentdb->createCommand($sql)->bindValues($params)->queryAll();

          return ['data' => $list, 'success' => true];
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage()];
      }
  }

  
  /**
   * 获取刊例价列表（分页多条件筛选）
   * @return array
   */
  public function actionPricelist()
  {
      try {
          // 分页参数
          $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
          $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
          $offset = $limit * ($page - 1);

          // 构建WHERE条件
          $where = 'p.SYS_DELETEFLAG = 0';
          $params = [];

          // 刊物筛选
          if (!empty($this->_request['E_PID'])) {
              $where .= ' AND p.E_PID = :E_PID';
              $params[':E_PID'] = $this->_request['E_PID'];
          }

          // 投放日
          if (!empty($this->_request['E_MID'])) {
              $where .= ' AND p.E_MID = :E_MID';
              $params[':E_MID'] = $this->_request['E_MID'];
          }

          // 版位筛选
          if (!empty($this->_request['E_AdField_ID'])) {
              $where .= ' AND p.E_AdField_ID = :E_AdField_ID';
              $params[':E_AdField_ID'] = $this->_request['E_AdField_ID'];
          }

          // 颜色筛选
          if (!empty($this->_request['E_Color_ID'])) {
              $where .= ' AND p.E_Color_ID = :E_Color_ID';
              $params[':E_Color_ID'] = $this->_request['E_Color_ID'];
          }

          // 规格筛选
          if (!empty($this->_request['E_AdSize_ID'])) {
              $where .= ' AND p.E_AdSize_ID = :E_AdSize_ID';
              $params[':E_AdSize_ID'] = $this->_request['E_AdSize_ID'];
          }

          // 查询总数
          $sqlCount = "SELECT COUNT(*) as total FROM pricelistitem p WHERE {$where}";
          $countResult = Yii::$app->paymentdb->createCommand($sqlCount)->bindValues($params)->queryOne();
          $total = $countResult['total'] ?? 0;

          // 查询数据
          $sql = "SELECT p.*,a.E_Name as E_AdSize_ID_label FROM pricelistitem p left join advsize a on a.SYS_DOCUMENTID=p.E_AdSize_ID WHERE {$where} ORDER BY p.SYS_DOCUMENTID DESC LIMIT {$offset}, {$limit}";
          $list = Yii::$app->paymentdb->createCommand($sql)->bindValues($params)->queryAll();

          // 查询字典表获取标签
          $dictTypes = ['刊物', '投放日', '版位', '颜色'];
          $dictLabels = [];
          foreach ($dictTypes as $type) {
              $dictSql = "SELECT id, label, value FROM ".FzrbsBudgetDict::tableName()." WHERE type = :type";
              $dictResult = Yii::$app->db->createCommand($dictSql)->bindValue(':type', $type)->queryAll();
              foreach ($dictResult as $item) {
                  $dictLabels[$type][$item['value']] = $item['label'];
              }
          }


          // 为每个记录添加标签
          foreach ($list as &$item) {
              // 刊物
              $item['E_PID_label'] = $dictLabels['刊物'][$item['E_PID']] ?? '';
              // 投放日
              $item['E_MID_label'] = $dictLabels['投放日'][$item['E_MID']] ?? '';
              // 版位
              $item['E_AdField_ID_label'] = $dictLabels['版位'][$item['E_AdField_ID']] ?? '';
              // 颜色
              $item['E_Color_ID_label'] = $dictLabels['颜色'][$item['E_Color_ID']] ?? '';
          }
          unset($item);

          // 构建返回结果
          $this->_result['data'] = $list;
          $this->_result['total'] = intval($total);
          $this->_result['current'] = intval($page);
          $this->_result['pageSize'] = intval($limit);
          $this->_result['success'] = true;

          return $this->_result;
      } catch (Exception $e) {
          return [
              'data' => [],
              'total' => 0,
              'success' => false,
              'errorMessage' => $e->getMessage()
          ];
      }
  }

  /**
   * 根据条件获取刊例价（返回ID最大的那条）
   * @return array
   */
  public function actionGetprice()
  {
      try {
          // 构建WHERE条件
          $where = 'SYS_DELETEFLAG = 0';
          $params = [];

          // 刊物筛选
          if (!empty($this->_request['E_PID'])) {
              $where .= ' AND E_PID = :E_PID';
              $params[':E_PID'] = $this->_request['E_PID'];
          }

          // 投放日筛选
          if (!empty($this->_request['E_MID'])) {
              $where .= ' AND E_MID = :E_MID';
              $params[':E_MID'] = $this->_request['E_MID'];
          }

          // 版位筛选
          if (!empty($this->_request['E_AdField_ID'])) {
              $where .= ' AND E_AdField_ID = :E_AdField_ID';
              $params[':E_AdField_ID'] = $this->_request['E_AdField_ID'];
          }

          // 颜色筛选
          if (!empty($this->_request['E_Color_ID'])) {
              $where .= ' AND E_Color_ID = :E_Color_ID';
              $params[':E_Color_ID'] = $this->_request['E_Color_ID'];
          }

          // 规格筛选
          if (!empty($this->_request['E_AdSize_ID'])) {
              $where .= ' AND E_AdSize_ID = :E_AdSize_ID';
              $params[':E_AdSize_ID'] = $this->_request['E_AdSize_ID'];
          }

          // 查询并按ID降序排列，取第一条（即ID最大的）
          $sql = "SELECT * FROM pricelistitem WHERE $where ORDER BY SYS_DOCUMENTID DESC LIMIT 1";
          $result = Yii::$app->paymentdb->createCommand($sql)->bindValues($params)->queryOne();

          return ['data' => $result, 'success' => true];
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage(), 'success' => false];
      }
  }

  /**
   * 保存或更新刊例价
   * @return array
   */
  public function actionSavepricelist()
  {
      try {
          $obj = $this->_request;
          
          $currentUserId = $this->_adminInfo['wxuserid'];
          
          // 处理ID字段
          if (isset($obj['E_PID']) && is_array($obj['E_PID'])) {
              $obj['E_PID'] = $obj['E_PID']['value'] ?? $obj['E_PID']['id'] ?? null;
          }
          if (isset($obj['E_MID']) && is_array($obj['E_MID'])) {
              $obj['E_MID'] = $obj['E_MID']['value'] ?? $obj['E_MID']['id'] ?? null;
          }
          if (isset($obj['E_AdField_ID']) && is_array($obj['E_AdField_ID'])) {
              $obj['E_AdField_ID'] = $obj['E_AdField_ID']['value'] ?? $obj['E_AdField_ID']['id'] ?? null;
          }
          if (isset($obj['E_Color_ID']) && is_array($obj['E_Color_ID'])) {
              $obj['E_Color_ID'] = $obj['E_Color_ID']['value'] ?? $obj['E_Color_ID']['id'] ?? null;
          }
          if (isset($obj['E_AdSize_ID']) && is_array($obj['E_AdSize_ID'])) {
              $obj['E_AdSize_ID'] = $obj['E_AdSize_ID']['value'] ?? $obj['E_AdSize_ID']['id'] ?? null;
          }
          
          // 如果是更新
          if (!empty($obj['SYS_DOCUMENTID'])) {
              $id = $obj['SYS_DOCUMENTID'];
              
              // 检查记录是否存在
              $existingRecord = Yii::$app->paymentdb->createCommand(
                  "SELECT creator,E_Price FROM pricelistitem WHERE SYS_DOCUMENTID = :id"
              )->bindValue(':id', $id)->queryOne();
              
              if (!$existingRecord) {
                  return ['errorMessage' => '记录不存在', 'success' => false];
              }
            
              // 检查是否是创建人
              if ($existingRecord['creator'] != $currentUserId&&$existingRecord['E_Price']) {
                  return ['errorMessage' => '只有创建人才能修改此记录', 'success' => false];
              }
              
              unset($obj['SYS_DOCUMENTID']);
              
              // 构建更新字段
              $updateFields = [];
              $updateParams = [];
              foreach ($obj as $key => $value) {
                  $updateFields[] = "`$key` = :$key";
                  $updateParams[":$key"] = $value;
              }
              $updateParams[':id'] = $id;
              
              $sql = "UPDATE pricelistitem SET " . implode(', ', $updateFields) . " WHERE SYS_DOCUMENTID = :id";
              Yii::$app->paymentdb->createCommand($sql)->bindValues($updateParams)->execute();
              
              return ['data' => ['id' => $id], 'success' => true];
          } else {
              // 新增 - 设置创建人为当前用户
              $obj['creator'] = $currentUserId;
              $obj['SYS_DELETEFLAG'] = 0;
              $columns = array_keys($obj);
              $values = array_values($obj);
              $placeholders = array_map(function($col) { return ":$col"; }, $columns);
              
              $sql = "INSERT INTO pricelistitem (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
              Yii::$app->paymentdb->createCommand($sql)->bindValues($obj)->execute();
              
              $insertId = Yii::$app->paymentdb->getLastInsertID();
              return ['data' => ['id' => $insertId], 'success' => true];
          }
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage(), 'success' => false];
      }
  }

  /**
   * 保存广告规格（新增/编辑）
   * 新增时creator为wxuserid，修改时只能修改本人的
   * @return array
   */
  public function actionSaveadvsize()
  {
      if (!$this->checkRole('广告审核')) {
          return ['errorMessage' => '无权操作，需要广告审核角色', 'success' => false];
      }
      try {
          $obj = $this->_request;
          
          $currentUserId = $this->_adminInfo['wxuserid'];
          
          // 处理ID字段 - 如果是对象则提取value
          if (isset($obj['E_AdType_ID']) && is_array($obj['E_AdType_ID'])) {
              $obj['E_AdType_ID'] = $obj['E_AdType_ID']['value'] ?? $obj['E_AdType_ID']['id'] ?? null;
          }
          
          // 如果是更新
          if (!empty($obj['SYS_DOCUMENTID'])) {
              $id = $obj['SYS_DOCUMENTID'];
              
              // 检查记录是否存在
              $existingRecord = Yii::$app->paymentdb->createCommand(
                  "SELECT creator FROM advsize WHERE SYS_DOCUMENTID = :id"
              )->bindValue(':id', $id)->queryOne();
              
              if (!$existingRecord) {
                  return ['errorMessage' => '记录不存在', 'success' => false];
              }
              
              // 检查是否是创建人
              if ($existingRecord['creator'] != $currentUserId) {
                  return ['errorMessage' => '只有创建人才能修改此记录', 'success' => false];
              }
              
              unset($obj['SYS_DOCUMENTID']);

              
              // 构建更新字段
              $updateFields = [];
              $updateParams = [];
              foreach ($obj as $key => $value) {
                  $updateFields[] = "`$key` = :$key";
                  $updateParams[":$key"] = $value;
              }
              $updateParams[':id'] = $id;
              
              $sql = "UPDATE advsize SET " . implode(', ', $updateFields) . " WHERE SYS_DOCUMENTID = :id";
              Yii::$app->paymentdb->createCommand($sql)->bindValues($updateParams)->execute();
              
              return ['data' => ['id' => $id], 'success' => true];
          } else {
              // 新增 - 检查名称是否重复
              $eName = $obj['E_Name'] ?? '';
              // 判断规格是否是包含数字，支持小数
              // if (!preg_match('/^\d+(\.\d+)?\*\d+(\.\d+)?$/', $eName)){
              //   return ['errorMessage' => '规格的命名，正确的格式为：25*69、25.5*69.6、1024*500 之类的', 'success' => false];
              // }

              $eAdTypeId = $obj['E_AdType_ID'] ?? null;
              
              if (!empty($eName)) {
                  // 构建查询条件：同一广告类型下名称不能重复
                  $checkWhere = "SYS_DELETEFLAG = 0 AND E_Name = :eName ";
                  $checkParams = [':eName' => $eName];
                  
                  if (!empty($eAdTypeId)) {
                      $checkWhere .= " AND E_AdType_ID = :eAdTypeId";
                      $checkParams[':eAdTypeId'] = $eAdTypeId;
                  }
                  
                  $existingCheck = Yii::$app->paymentdb->createCommand(
                      "SELECT SYS_DOCUMENTID FROM advsize WHERE {$checkWhere}"
                  )->bindValues($checkParams)->queryOne();
                  
                  if ($existingCheck) {
                      return ['errorMessage' => '该规格名称已存在', 'success' => false];
                  }
              }
              
              // 新增 - 设置创建人为当前用户
              $obj['creator'] = $currentUserId;
              $obj['SYS_DELETEFLAG'] = 0;

              
              $columns = array_keys($obj);
              $values = array_values($obj);
              $placeholders = array_map(function($col) { return ":$col"; }, $columns);
              
              $sql = "INSERT INTO advsize (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
              Yii::$app->paymentdb->createCommand($sql)->bindValues($obj)->execute();
              
              $insertId = Yii::$app->paymentdb->getLastInsertID();
              return ['data' => ['id' => $insertId], 'success' => true];
          }
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage(), 'success' => false];
      }
  }

  /**
   * 删除规格
   * @return array
   */
  public function actionDeletesize()
  {
      if (!$this->checkRole('广告审核')) {
          return ['errorMessage' => '无权操作，需要广告审核角色', 'success' => false];
      }
      try {
          $id = $this->_request['id'] ?? $this->_request['SYS_DOCUMENTID'] ?? null;
          if (!$id) {
              return ['errorMessage' => '规格ID不能为空', 'success' => false];
          }

          $currentUserId = $this->_adminInfo['wxuserid'];

          $existingRecord = Yii::$app->paymentdb->createCommand(
              "SELECT creator FROM advsize WHERE SYS_DOCUMENTID = :id"
          )->bindValue(':id', $id)->queryOne();

          if (!$existingRecord) {
              return ['errorMessage' => '记录不存在', 'success' => false];
          }

          if ($existingRecord['creator'] != $currentUserId) {
              return ['errorMessage' => '只有创建人才能删除此记录', 'success' => false];
          }

          $sql = "UPDATE advsize SET SYS_DELETEFLAG = 1 WHERE SYS_DOCUMENTID = :id";
          Yii::$app->paymentdb->createCommand($sql)->bindValue(':id', $id)->execute();

          return ['data' => ['id' => $id], 'success' => true];
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage(), 'success' => false];
      }
  }
  
  public function actionSaveadvitem(){
    try {
        $obj = $this->_request;
        
        if ($obj['AI_Type']){
          return $this->actionSaveorderwithadvitem();
        }
        
        // 获取当前时间
        $now = date('Y-m-d H:i:s');
        
        // 处理广告数据 - 计算星期
        if (isset($obj['AI_PublishTime']) && !empty($obj['AI_PublishTime'])) {
            // 判断星期几,并转换成中文
            $weekdayMap = [
                '0' => '日',
                '1' => '一',
                '2' => '二',
                '3' => '三',
                '4' => '四',
                '5' => '五',
                '6' => '六',
            ];

            $startWeekday = date('w', strtotime($obj['AI_PublishTime']));

            // 判断结束日期是否为空或与开始日期相同
            $endTime = isset($obj['AI_PublishEndTime']) && !empty($obj['AI_PublishEndTime']) ? $obj['AI_PublishEndTime'] : $obj['AI_PublishTime'];
            $endWeekday = date('w', strtotime($endTime));

            if ($startWeekday == $endWeekday) {
                // 日期相同，显示单个星期
                $obj['AI_Week'] = $weekdayMap[$startWeekday];
            } else {
                // 日期不同，显示范围
                $obj['AI_Week'] = $weekdayMap[$startWeekday] . '~' . $weekdayMap[$endWeekday];
            }
        }
        var_dump($obj['AI_Week']);exit;
        
        // 计算投放天数
        if (empty($obj['AI_PublishEndTime'])) {
            $obj['AI_PublishEndTime'] = $obj['AI_PublishTime'];
            $obj['AI_PublishDayCount'] = 1;
            
        } else {
            $startTime = strtotime($obj['AI_PublishTime']);
            $endTime = strtotime($obj['AI_PublishEndTime']);
            $obj['AI_PublishDayCount'] = ceil(($endTime - $startTime) / 86400) + 1;
        }
        
        
  
        // 开启事务
        $transaction = Yii::$app->paymentdb->beginTransaction();
        try {
          // 如果是新增
            if (empty($obj['SYS_DOCUMENTID'])) {
            
              if (!$obj['AI_OrderID']){
                return ['errorMessage' => '订单ID不能为空'];
              
              }
              
              $obj['AI_AmountReceived']=0;
              
              // 计算未核销金额
              if (!isset($obj['AI_UnbalancedMoney']) || $obj['AI_UnbalancedMoney'] === null) {
                  $obj['AI_UnbalancedMoney'] = ($obj['AI_AmountReceivable'] ?? 0) - ($obj['AI_BalancedMoney'] ?? 0);
              }
              
              // 计算未开票金额
              if (!isset($obj['AI_UninvoicedMoney']) || $obj['AI_UninvoicedMoney'] === null) {
                  $obj['AI_UninvoicedMoney'] = ($obj['AI_AmountReceivable'] ?? 0) - ($obj['AI_InvoicedMoney'] ?? 0);
              }
              
              // 计算欠款金额
              if (!isset($obj['AI_Debt']) || $obj['AI_Debt'] === null) {
                  $obj['AI_Debt'] = ($obj['AI_AmountReceivable'] ?? 0) - ($obj['AI_AmountReceived'] ?? 0);
              }
              if (!isset($obj['AI_AmountPaid']) || $obj['AI_AmountPaid'] === null) {
                  $obj['AI_AmountPaid'] = $obj['AI_AmountReceivable'] ?? 0;
              }
              if (!isset($obj['AI_Price']) || $obj['AI_Price'] === null) {
                  $obj['AI_Price'] = $obj['AI_AmountReceivable'] ?? 0;
              }
              $obj['AI_UnitPrice'] = $obj['AI_Price'] ?? 0;
              $obj['SYS_DELETEFLAG'] = 1;
              $obj['SYS_CREATED'] = $now;
              $obj['AI_PayStatus'] = 0;
              
              // 如果已付金额为空，则设为应收金额
              if (!isset($obj['AI_AmountPaid']) || $obj['AI_AmountPaid'] === null) {
                  $obj['AI_AmountPaid'] = $obj['AI_AmountReceivable'] ?? 0;
              }
              $obj['SYS_AUTHORS']=$this->userinfo['userid'];
              $obj['SYS_CURRENTUSERNAME']= $this->userinfo['name'];
       
              Yii::$app->paymentdb->createCommand()->insert('advitem', $obj)->execute();

          } else {

              // 更新广告时检查权限并获取原有日期字段
              $checkSql = "SELECT SYS_AUTHORS, AI_PublishTime, AI_PublishEndTime FROM advitem WHERE SYS_DOCUMENTID = :id";
              $existingAdvitem = Yii::$app->paymentdb->createCommand($checkSql)->bindValues([':id' => $obj['SYS_DOCUMENTID']])->queryOne();

              if (!$this->checkAuthor($existingAdvitem['SYS_AUTHORS'])) {
                  return ['errorMessage' => '只有本人才能操作'];
              }

              // 如果请求中没有 AI_PublishTime，使用数据库原有值来重新计算 AI_Week
              if (!isset($obj['AI_PublishTime']) || empty($obj['AI_PublishTime'])) {
                  $obj['AI_PublishTime'] = $existingAdvitem['AI_PublishTime'];
              }
              if (!isset($obj['AI_PublishEndTime']) || empty($obj['AI_PublishEndTime'])) {
                  $obj['AI_PublishEndTime'] = $existingAdvitem['AI_PublishEndTime'];
              }

              // 重新计算 AI_Week
              $weekdayMap = [
                  '0' => '日',
                  '1' => '一',
                  '2' => '二',
                  '3' => '三',
                  '4' => '四',
                  '5' => '五',
                  '6' => '六',
              ];
              $startWeekday = date('w', strtotime($obj['AI_PublishTime']));
              $endTime = !empty($obj['AI_PublishEndTime']) ? $obj['AI_PublishEndTime'] : $obj['AI_PublishTime'];
              $endWeekday = date('w', strtotime($endTime));
              if ($startWeekday == $endWeekday) {
                  $obj['AI_Week'] = $weekdayMap[$startWeekday];
              } else {
                  $obj['AI_Week'] = $weekdayMap[$startWeekday] . '~' . $weekdayMap[$endWeekday];
              }
              
              if (isset($obj['AI_AmountReceivable'])){
                $obj['AI_Price'] = $obj['AI_AmountReceivable'] ?? 0;
                $obj['AI_AmountPaid'] = $obj['AI_AmountReceivable'] ?? 0;

                $obj['AI_Debt'] = ($obj['AI_AmountReceivable'] ?? 0) - ($obj['AI_AmountReceived'] ?? 0);
                if ($obj['AI_Debt']<0) $obj['AI_Debt']=0;
                // 计算未核销金额
                $obj['AI_UnbalancedMoney'] = ($obj['AI_AmountReceivable'] ?? 0) - ($obj['AI_BalancedMoney'] ?? 0);
                // 计算未开票金额
                $obj['AI_UninvoicedMoney'] = ($obj['AI_AmountReceivable'] ?? 0) - ($obj['AI_InvoicedMoney'] ?? 0);
              }
              
  
              // 只更新需要的字段，避免覆盖不需要更新的字段如fileurls
              $updateFields = array_diff_key($obj, ['SYS_DOCUMENTID' => '']); // 排除主键字段
              Yii::$app->paymentdb->createCommand()->update('advitem', $updateFields, 'SYS_DOCUMENTID = :id', [':id' => $obj['SYS_DOCUMENTID']])->execute();
              
          }
          // 更新关联的订单金额
          if (!empty($obj['AI_OrderID'])) {
              $this->updateOrderAmounts($obj['AI_OrderID']);
          }
        } catch (\Throwable $th) {
          $transaction->rollBack();
          return ['errorMessage' => $th->getMessage()];
        }
        $transaction->commit();
        
     
        
        
        return ['data' => $obj, 'success' => true];
    } catch (Exception $e) {
        return ['errorMessage' => $e->getMessage()];
    }
  }
  
  /**
   * 更新订单金额（根据广告数据重新计算）
   * @param int $orderId 订单ID
   */
  private function updateOrderAmounts($orderId)
  {
      try {
          // 使用SQL直接合计订单下所有广告相应字段的总额
          $advitemTable = 'advitem';
          $sumSql = "SELECT 
              COALESCE(SUM(AI_AmountReceivable), 0) as totalAmountReceivable,
              COALESCE(SUM(AI_AmountPaid), 0) as totalAmountPaid,
              COALESCE(SUM(AI_AmountReceived), 0) as totalAmountReceived
          FROM {$advitemTable} 
          WHERE AI_OrderID = :orderId";
          
          $sumResult = Yii::$app->paymentdb->createCommand($sumSql)->bindValues([':orderId' => $orderId])->queryOne();
          
          if (!$sumResult) {
              return;
          }
          
          // 更新订单
          $orderTable = Advorder::tableName();
          $updateSql = "UPDATE {$orderTable} SET 
              AO_AllMoney = :aoAllMoney, 
              AO_AmountPaid = :aoAmountPaid, 
              AO_DebtMoney = :aoDebtMoney, 
              AO_ReceivedMoney = :aoReceivedMoney,
              SYS_LASTMODIFIED = :now
          WHERE SYS_DOCUMENTID = :orderId";
          Yii::$app->paymentdb->createCommand($updateSql)->bindValues([
              ':aoAllMoney' => $sumResult['totalAmountReceivable'],
              ':aoAmountPaid' => $sumResult['totalAmountPaid'],
              ':aoDebtMoney' => $sumResult['totalAmountReceivable']-$sumResult['totalAmountReceived'],
              ':aoReceivedMoney' => $sumResult['totalAmountReceived'],
              ':now' => date('Y-m-d H:i:s'),
              ':orderId' => $orderId
          ])->execute();
          
      } catch (Exception $e) {
          throw new Exception('更新订单金额失败: ' . $e->getMessage());
      }
  }

  /**
   * 更新广告项状态为已审，并检查订单下是否所有广告都已通过
   * @param int $advitemId 广告项ID
   * @param int $orderId 订单ID
   * @return bool 订单是否也已更新为已审
   */
  public function updateAdvitemAndCheckOrder($advitemId, $orderId)
  {
      // 更新该广告为已审
      Yii::$app->paymentdb->createCommand()->update('advitem', ['SYS_DELETEFLAG'=>0], 'SYS_DOCUMENTID=:id', [':id'=>$advitemId])->execute();

      // 检查订单下是否还有未审的广告(SYS_DELETEFLAG=1)
      $stillHasUnapproved = Yii::$app->paymentdb->createCommand(
          "SELECT SYS_DOCUMENTID FROM advitem WHERE AI_OrderID=:orderId AND SYS_DELETEFLAG=1 AND SYS_DOCUMENTID<>:docId LIMIT 1",
          [':orderId' => $orderId, ':docId' => $advitemId]
      )->queryOne();

      if (!$stillHasUnapproved) {
          Yii::$app->paymentdb->createCommand()->update('advorder', ['SYS_DELETEFLAG'=>0], 'SYS_DOCUMENTID=:orderId', [':orderId'=>$orderId])->execute();
          return true;
      }
      return false;
  }



  /**
   * 删除广告
   * @return array
   */
  public function actionDeleteadvitem()
  {
      try {
          $id = $this->_request['SYS_DOCUMENTID'] ?? $this->_request['id'] ?? null;

          if (empty($id)) {
              return ['errorMessage' => '广告ID不能为空'];
          }

          // 先获取广告信息，以便获取订单ID
          $advitemSql = "SELECT * FROM advitem WHERE SYS_DOCUMENTID = :id";
          $advitem = Yii::$app->paymentdb->createCommand($advitemSql)->bindValues([':id' => $id])->queryOne();
          
          // 检查是否是本人操作
          if (!$this->checkAuthor($advitem['SYS_AUTHORS'])) {
              return ['errorMessage' => '只有本人才能操作'];
          }
          // 检查是否有回款，如果有回款则禁止删除
          $amountReceived = floatval($advitem['AI_AmountReceived'] ?? 0);
          if ($amountReceived > 0) {
              return ['errorMessage' => '该广告已有回款，无法删除'];
          }

          // 已审广告(SYS_DELETEFLAG=0)禁止删除
          if ($advitem['SYS_DELETEFLAG'] == 0) {
              return ['errorMessage' => '已审批的广告无法删除'];
          }

      
          // 硬删除,根据SYS_DOCUMENTID进行删除
            $sql = "DELETE FROM advitem WHERE SYS_DOCUMENTID = :id";
            Yii::$app->paymentdb->createCommand($sql)->bindValues([':id' => $id])->execute();
          

          // 更新关联订单金额
          if ($advitem && !empty($advitem['AI_OrderID'])) {
              $this->updateOrderAmounts($advitem['AI_OrderID']);
          }

          return ['success' => true];
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage()];
      }
  }

  /**
   * 保存订单和广告（福州日报社小额业务确认单/福州日报社广告以及纯服务收入登记表）
   * @return array
   */
  public function actionSaveorderwithadvitem()
  {
      try {
          $obj = $this->_request;
          if(!$obj['AI_Salesman']){
            return ['errorMessage' => '业务员不能为空'];
          
          }
          if ($obj['AI_SalesmanName']){
            $obj['AI_Salesman'] = $obj['AI_SalesmanName'];
            unset($obj['AI_SalesmanName']);
          }
          if(!$obj['AI_Publication']){
            return ['errorMessage' => 'AI_Publication不能为空'];
          
          }
          // AO_Org_ID 如果是数组需要转换一下
          if (isset($obj['AO_Org_ID']) && is_array($obj['AO_Org_ID'])) {
              $obj['AO_Org'] = $obj['AO_Org_ID']['label'] ?? '';
              $obj['AO_Org_ID'] = $obj['AO_Org_ID']['value'] ?? $obj['AO_Org_ID']['id'] ?? '';
          }
      
          $now = date('Y-m-d H:i:s');
          
          // 转换日期格式（ISO 8601转MySQL格式）
          $dateFields = ['SYS_CREATED', 'AI_PublishTime', 'AI_PublishEndTime', 'AI_PayTime'];
          foreach ($dateFields as $field) {
              if (!empty($obj[$field]) && is_string($obj[$field])) {
                  // 移除ISO 8601格式中的T和Z
                  $obj[$field] = preg_replace('/[TZ]/', ' ', $obj[$field]);
                  $obj[$field] = trim($obj[$field]);
              }
           }
           
           // 日期校验
           $currentYearMonth = date('Y-m');

           // 计算星期 AI_Week
           if (isset($obj['AI_PublishTime']) && !empty($obj['AI_PublishTime'])) {
               $weekdayMap = [
                   '0' => '日',
                   '1' => '一',
                   '2' => '二',
                   '3' => '三',
                   '4' => '四',
                   '5' => '五',
                   '6' => '六',
               ];
               $startWeekday = date('w', strtotime($obj['AI_PublishTime']));
               $endTime = !empty($obj['AI_PublishEndTime']) ? $obj['AI_PublishEndTime'] : $obj['AI_PublishTime'];
               $endWeekday = date('w', strtotime($endTime));
               if ($startWeekday == $endWeekday) {
                   $obj['AI_Week'] = $weekdayMap[$startWeekday];
               } else {
                   $obj['AI_Week'] = $weekdayMap[$startWeekday] . '~' . $weekdayMap[$endWeekday];
               }
           }

           
            // 校验结束日期必须大于等于开始日期(只比较年月日)
            if (!empty($obj['AI_PublishEndTime']) && !empty($obj['AI_PublishTime'])) {
                $startDate = date('Y-m-d', strtotime($obj['AI_PublishTime']));
                $endDate = date('Y-m-d', strtotime($obj['AI_PublishEndTime']));
                if ($endDate < $startDate) {
                    return ['errorMessage' => '结束日期不能早于开始日期'];
                }
            }
           
           $transaction = Yii::$app->paymentdb->beginTransaction();
          try {
              // obj对应advitem数据，SYS_DOCUMENTID对应广告id，AI_OrderID对应订单id
              $isNewAdvitem = empty($obj['SYS_DOCUMENTID']);
              $orderId = $obj['AI_OrderID'] ?? null;
              
              // 如果是更新广告，需要获取订单ID
              if (!$isNewAdvitem && empty($orderId)) {
                  $advitemSql = "SELECT AI_OrderID FROM advitem WHERE SYS_DOCUMENTID = :id";
                  $advitemInfo = Yii::$app->paymentdb->createCommand($advitemSql)->bindValues([':id' => $obj['SYS_DOCUMENTID']])->queryOne();
                  $orderId = $advitemInfo['AI_OrderID'] ?? null;
              }
              
              $isNewOrder = empty($orderId);
              
              // ========== Order数据 ==========
              $amountReceivable = $obj['AI_AmountReceivable'] ?? 0;
     
          
              $amountPaid = $obj['AI_AmountPaid'] ?? $amountReceivable;
              
              $orderData = [
                  'partb' => $obj['partb'] ?? null,
                  'partbname' => $obj['partbname'] ?? '',
                  'AO_Customer_ID' => $obj['AI_Customer_ID'] ?? null,
                  'AO_Customer' => $obj['AI_Customer'] ?? '',
                  'AO_Salesman_ID' => $obj['AI_Salesman_ID'] ?? '',
                  'AO_Salesman' => $obj['AI_Salesman'] ?? '',
                  'AO_Type' => $obj['AI_Type'] ?? '',
                  'publicationid' => $obj['AI_Publication_ID'] ?? null,
                  'publication' => $obj['AI_Publication'] ?? '',
                  'contractid' => $obj['contractid'] ?? null,
                  'contractserial' => $obj['contractserial'] ?? '',
                  'AO_AllMoney' => $amountReceivable,
                  'AO_AmountPaid' => $amountPaid,
                  'AO_Memo' => $obj['AI_Memo'] ?? '',
                  'AI_TradeID'=> $obj['AI_TradeID'] ?? '',
                  'AI_Trade'=> $obj['AI_Trade'] ?? ''
              ];
              
              if ($isNewOrder) {
                  $orderData['SYS_DELETEFLAG'] = 1;
                  $orderData['AO_DebtMoney'] = $amountReceivable;
                  $orderData['SYS_CREATED'] = $now;
                  $orderData['SYS_AUTHORS'] = $this->userinfo['userid'] ?? '';
                  $orderData['SYS_CURRENTUSERNAME'] = $this->userinfo['name'] ?? '';
                  $orderData['departmentid'] = $this->userinfo['departmentid'] ?? '';
                  $orderData['departmentname'] = $this->userinfo['departmentname'] ?? '';
                  // 确保获取到AO_Org_ID和AO_Org的值，前端可能传递AI_Org_ID或AO_Org_ID
                  $orderData['AO_Org_ID'] = $obj['AO_Org_ID'] ?? $obj['AI_Org_ID'] ?? '';
                  $orderData['AO_Org'] = $obj['AO_Org'] ?? $obj['AI_Org'] ?? '';
                  
                  // 如果AO_Org_ID或AO_Org为空，使用备选逻辑
                  if (empty($orderData['AO_Org_ID']) && !empty($obj['AI_Org_ID'])) {
                      $orderData['AO_Org_ID'] = $obj['AI_Org_ID'];
                  }
                  if (empty($orderData['AO_Org']) && !empty($obj['AI_Org'])) {
                      $orderData['AO_Org'] = $obj['AI_Org'];
                  }
                  
                  $orderData['fileurls'] = $obj['fileurls'] ?? '';
                  $orderData['serial'] = $orderData['serial'] ? $orderData['serial'] : $this->getThirdNo();
                  
                  // 检查AO_Org是否为空
                  if (empty($orderData['AO_Org']) && empty($orderData['AO_Org_ID'])) {
                      throw new Exception('AO_Org不能为空！');
                  }
                  Yii::$app->paymentdb->createCommand()->insert(Advorder::tableName(), $orderData)->execute();
                  $orderId = Yii::$app->paymentdb->getLastInsertID();
                  
              } else {
                  $checkSql = "SELECT * FROM " . Advorder::tableName() . " WHERE SYS_DOCUMENTID = :id";
                  $existingOrder = Yii::$app->paymentdb->createCommand($checkSql)->bindValues([':id' => $orderId])->queryOne();
                  // if (!$this->checkAuthor($existingOrder['SYS_AUTHORS'])) {
                  //     return ['errorMessage' => '只有本人才能操作'];
                  // }
                  
                  $orderData['AO_DebtMoney'] = $amountReceivable - ($existingOrder['AO_ReceivedMoney'] ?? 0);
                  
                  $orderData['SYS_LASTMODIFIED'] = $now;
                  Yii::$app->paymentdb->createCommand()->update(Advorder::tableName(), $orderData, ['SYS_DOCUMENTID' => $orderId])->execute();
              }
              
              // ========== Advitem数据（直接使用前端数据） ==========
              // 保存合同字段供后续检查（这些字段只保存到order表）
              $objContractid = $obj['contractid'] ?? null;
              $objContractserial = $obj['contractserial'] ?? null;
              // 移除不需要保存到advitem的字段（这些字段只保存到order表）
              $orderOnlyFields = ['contractserial', 'contractid','partb', 'partbname','AO_Org','AO_Org_ID'];
              foreach ($orderOnlyFields as $field) {
                  unset($obj[$field]);
              }
              // 宽高不为空且都大于0
              if (isset($obj['AI_Width']) && isset($obj['AI_Height'])) {
                  if($obj['AI_Width'] > 0 && $obj['AI_Height'] > 0){
                    // 刊物为福州日报或福州晚报时,计算AI_AdvPages值，（高*宽）/整版面积，福州日报：(高*宽)/(49.5*33），福州晚报：(高*宽)/（34*24），最终结果保留4位小数
                    $area = 0;
                    if ($obj['AI_Publication']=='福州日报'||$obj['AI_Publication']=='福州晚报') {
                        $area = $obj['AI_Publication']=='福州日报'?49.5*33:34*24;
                    }
                    if ($area>0){
                      $obj['AI_AdvPages'] = round(($obj['AI_Height']*$obj['AI_Width'])/floatval($area),4);
                      // if ($obj['AI_AdvPages']>1){
                      //   return ['errorMessage' => '广告版面数大于1，应该是规格对应的宽高有误，请修改宽和高，宽和高对应的单位为厘米'];
                      // }
                    }
                  }else{
                    $obj['AI_AdvPages'] = 0;
                  }
                  
              }
          
       
              if ($isNewAdvitem) {
                  // 新增时设置金额
                  $obj['AI_OrderID']=$orderId;
                  $amountReceivable = $obj['AI_AmountReceivable'] ?? 0;
                  $obj['AI_AmountReceived'] = 0;
                  $obj['AI_UnbalancedMoney'] = $amountReceivable - ($obj['AI_BalancedMoney'] ?? 0);
                  $obj['AI_UninvoicedMoney'] = $amountReceivable - ($obj['AI_InvoicedMoney'] ?? 0);
                  $obj['AI_Debt'] = $amountReceivable;
                  $obj['AI_AmountPaid'] = $obj['AI_AmountPaid'] ?? $obj['AI_Price'] ?? $amountReceivable;
                  $obj['AI_UnitPrice'] = $obj['AI_Price'] ?? $amountReceivable;
                  $obj['AI_Price'] = $obj['AI_Price'] ?? $amountReceivable;
                
                  // 新增时设置系统字段
                  $obj['SYS_DELETEFLAG'] = 1;
                  $obj['AI_PayStatus'] = 0;
                  $obj['SYS_AUTHORS'] = $this->userinfo['userid'] ?? '';
                  $obj['SYS_CURRENTUSERNAME'] = $this->userinfo['name'] ?? '';
                  // throw new Exception(json_encode($obj,JSON_UNESCAPED_UNICODE));
                  Yii::$app->paymentdb->createCommand()->insert('advitem', $obj)->execute();
                  $advitemId = Yii::$app->paymentdb->getLastInsertID();
                  Yii::$app->paymentdb->createCommand()->update(Advorder::tableName(), ['SYS_DELETEFLAG'=>1,'thirdNo'=>''], ['SYS_DOCUMENTID' => $orderId])->execute();
                  
              } else {
                  if($existingOrder){
                    // 判断是否已经生效
                    if ($existingOrder['SYS_DELETEFLAG']==0) {
                      // 判断附件是否一致
                      $advitemId = $obj['SYS_DOCUMENTID'];
                      $checkAdvitemSql = "SELECT * FROM advitem WHERE SYS_DOCUMENTID = :id";
                      $existingAdvitem = Yii::$app->paymentdb->createCommand($checkAdvitemSql)->bindValues([':id' => $advitemId])->queryOne();
                      $fileurlsChanged = $existingAdvitem['fileurls'] != $obj['fileurls'];
                      // 从advorder表检查合同字段变化（contractid和contractserial只存在于advorder表）
                      $existingContractid = isset($existingOrder['contractid']) ? $existingOrder['contractid'] : '';
                      $existingContractserial = isset($existingOrder['contractserial']) ? $existingOrder['contractserial'] : '';
                      $contractidChanged = $existingContractid != ($objContractid ?? '');
                      $contractserialChanged = $existingContractserial != ($objContractserial ?? '');
                      // 调试日志
                      Yii::info("订单已生效检查: fileurlsChanged=$fileurlsChanged, contractidChanged=$contractidChanged, contractserialChanged=$contractserialChanged");
                      Yii::info("existingOrder contractid=$existingContractid, contractserial=$existingContractserial");
                      Yii::info("obj contractid=" . ($obj['contractid'] ?? 'null') . ", contractserial=" . ($obj['contractserial'] ?? 'null'));
                      if ($fileurlsChanged || $contractidChanged || $contractserialChanged) {
                          $updateFields = [];
                          if ($fileurlsChanged) {
                              $updateFields['fileurls'] = $obj['fileurls'];
                          }
                          // 合同字段更新到advorder表
                          $orderUpdateFields = [];
                          if ($contractidChanged) {
                              $orderUpdateFields['contractid'] = $objContractid;
                          }
                          if ($contractserialChanged) {
                              $orderUpdateFields['contractserial'] = $objContractserial;
                          }
                          if (!empty($orderUpdateFields)) {
                              Yii::$app->paymentdb->createCommand()->update(Advorder::tableName(), $orderUpdateFields, 'SYS_DOCUMENTID = :id', [':id' => $existingOrder['SYS_DOCUMENTID']])->execute();
                          }
                          if ($fileurlsChanged) {
                              Yii::$app->paymentdb->createCommand()->update('advitem', $updateFields, 'SYS_DOCUMENTID = :id', [':id' => $advitemId])->execute();
                          }
                          $transaction->commit();
                          return ['errorMessage' => '附件或合同已更新，但其它内容不能修改！若要修改，请点击订单编号重新审批'];
                      }



                      return ['errorMessage' => '该订单已生效，不能修改2！请点击订单编号重新审批，审批期间允许修改'];
                    }
                  }
                  // 更新时金额计算
                  if (isset($obj['AI_AmountReceivable'])) {
                      $amountReceivable = $obj['AI_AmountReceivable'];
                      $obj['AI_AmountPaid'] = $amountReceivable;
                      if (!isset($obj['AI_Price'])){
                        $obj['AI_Price'] = $obj['AI_AmountReceivable'];
                      }
                  }
                  if (isset($obj['AI_Price'])) {
                      $obj['AI_UnitPrice'] = $obj['AI_Price'];
                      $obj['AI_AmountPaid'] = $obj['AI_Price'];
                  }
                  
                  // 更新时检查权限
                  $advitemId = $obj['SYS_DOCUMENTID'];
                  $checkAdvitemSql = "SELECT * FROM advitem WHERE SYS_DOCUMENTID = :id";
                  $existingAdvitem = Yii::$app->paymentdb->createCommand($checkAdvitemSql)->bindValues([':id' => $advitemId])->queryOne();
                  if ($existingAdvitem['SYS_DELETEFLAG']==0) {
                    $fileurlsChanged = $existingAdvitem['fileurls'] != $obj['fileurls'];
                    // 从advorder表检查合同字段变化
                    $existingContractid = isset($existingOrder['contractid']) ? $existingOrder['contractid'] : '';
                    $existingContractserial = isset($existingOrder['contractserial']) ? $existingOrder['contractserial'] : '';
                    $contractidChanged = $existingContractid != ($objContractid ?? '');
                    $contractserialChanged = $existingContractserial != ($objContractserial ?? '');
                    if ($fileurlsChanged || $contractidChanged || $contractserialChanged) {
                        $updateFields = [];
                        if ($fileurlsChanged) {
                            $updateFields['fileurls'] = $obj['fileurls'];
                        }
                        // 合同字段更新到advorder表
                        $orderUpdateFields = [];
                        if ($contractidChanged) {
                            $orderUpdateFields['contractid'] = $objContractid;
                        }
                        if ($contractserialChanged) {
                            $orderUpdateFields['contractserial'] = $objContractserial;
                        }
                        if (!empty($orderUpdateFields)) {
                            Yii::$app->paymentdb->createCommand()->update(Advorder::tableName(), $orderUpdateFields, 'SYS_DOCUMENTID = :id', [':id' => $existingOrder['SYS_DOCUMENTID']])->execute();
                        }
                        if ($fileurlsChanged) {
                            Yii::$app->paymentdb->createCommand()->update('advitem', $updateFields, 'SYS_DOCUMENTID = :id', [':id' => $advitemId])->execute();
                        }
                        $transaction->commit();
                        return ['errorMessage' => '附件或合同已更新，但其它内容不能修改！若要修改，请点击广告编号重新审批'];
                    }
                    return array('errorMessage'=>'广告已审批无法修改，点击广告编号重审后，才能修改！');
                  }
                  if (!$this->checkAuthor($existingAdvitem['SYS_AUTHORS'])) {
                      return ['errorMessage' => '只有本人才能操作'];
                  }
                  $obj['AI_Debt'] = max(0, $amountReceivable - ($existingAdvitem['AI_AmountReceived'] ?? 0));
                  // 计算未核销金额
                  $obj['AI_UnbalancedMoney'] =max(0, $amountReceivable - ($existingAdvitem['AI_BalancedMoney'] ?? 0));
                  // 计算未开票金额
                  $obj['AI_UninvoicedMoney'] = max(0, $amountReceivable - ($existingAdvitem['AI_InvoicedMoney'] ?? 0));
                  $obj['SYS_LASTMODIFIED'] = $now;
                  // 只更新需要的字段，避免覆盖不需要更新的字段如fileurls
                  $updateFields = array_diff_key($obj, ['SYS_DOCUMENTID' => '']); // 排除主键字段
                  Yii::$app->paymentdb->createCommand()->update('advitem', $updateFields, 'SYS_DOCUMENTID = :id', [':id' => $advitemId])->execute();
              }
              
              // 只有更新订单时才重新计算订单金额
              if (!$isNewOrder) {
                  $this->updateOrderAmounts($orderId);
              }
              
              $transaction->commit();
              
              return [
                  'data' => [
                      'SYS_DOCUMENTID' => $orderId,
                      'AI_advitem_ID' => $advitemId
                  ],
                  'success' => true
              ];
              
          } catch (\Throwable $th) {
              $transaction->rollBack();
              return ['errorMessage' => $th->getMessage()];
          }
          
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage()];
      }
  }

   /**
    * 根据订单ID获取合并后的打印数据
    * @return array
    */
   public function actionPrintorder()
   {
       try {
           $orderId = $this->_request['orderid'] ?? $this->_request['orderId'] ?? null;
           $orderIds = $this->_request['orderids'] ?? null;

           // 如果提供了orderids参数，则批量处理
           if (!empty($orderIds)) {
               $orderIdList = explode(',', $orderIds);
               $results = [];

               foreach ($orderIdList as $singleOrderId) {
                   $singleOrderId = trim($singleOrderId);
                   if (empty($singleOrderId)) continue;

                   try {
                       $result = $this->getOrderPrintData($singleOrderId);
                       if ($result) {
                           $results[] = $result;
                       }
                   } catch (Exception $e) {
                       // 单个订单处理失败，继续处理其他订单
                       continue;
                   }
               }

               return ['data' => $results, 'success' => true];
           }

           // 单个订单处理
           if (empty($orderId)) {
               return ['errorMessage' => '订单ID不能为空'];
           }

           $result = $this->getOrderPrintData($orderId);
           if (!$result) {
               return ['errorMessage' => '获取订单打印数据失败'];
           }

           return ['data' => $result, 'success' => true];
       } catch (Exception $e) {
           return ['errorMessage' => $e->getMessage()];
       }
   }

   /**
    * 获取单个订单的打印数据
    * @param string $orderId 订单ID
    * @return array|null
    */
   private function getOrderPrintData($orderId)
   {
       try {
           // 获取订单信息
           $orderTable = Advorder::tableName();
           $orderSql = "SELECT * FROM {$orderTable} WHERE SYS_DOCUMENTID = :orderId";
           $order = Yii::$app->paymentdb->createCommand($orderSql)->bindValues([':orderId' => $orderId])->queryOne();

           if (!$order) {
               return null;
           }

           // 获取该订单下的所有广告（只获取未删除的）
           $advitemSql = "SELECT * FROM advitem WHERE AI_OrderID = $orderId";
           $advitems = Yii::$app->paymentdb->createCommand($advitemSql)->queryAll();

           if (empty($advitems)) {
               return null;
           }

           // 需要相加的字段
           $sumFields = ['AI_Price', 'AI_PublishDayCount', 'AI_AmountReceivable'];

           // 需要合并的字段（不同则用中文逗号串联，相同则返回一个）
           $mergeFields = [
               'SYS_DOCUMENTID','AI_Customer', 'AI_Publication', 'AI_Trade', 'AI_Size', 'AI_Field', 'AI_Color',
               'AI_Width', 'AI_Height', 'AI_PayMode', 'AI_Salesman', 'discount', 'paytime', 'AI_AmountReceived',
               'SYS_CREATED','customerinfo','salemantel','assistantname','assistantdepartmentname','content','AI_Content',
           ];

           // 初始化合并结果
           $merged = [];
           // 根据AO_Saleman_ID查询销售人员所在部门的名称
           $dept = WeixinOAUserInfo::find()->where(['userid' => $order['AO_Salesman_ID']])->one();
           if ($dept){

             $merged['departmentname'] = $dept['departmentname'];
           }


           // 处理需要相加的字段
           foreach ($sumFields as $field) {
               $total = 0;
               foreach ($advitems as $item) {
                   $value = isset($item[$field]) ? floatval($item[$field]) : 0;
                   $total += $value;
               }
               $merged[$field] = $total;
           }

           // 处理需要合并的字段（普通字段用中文逗号分割）
           foreach ($mergeFields as $field) {
               $values = [];
               foreach ($advitems as $item) {
                   if (isset($item[$field]) && $item[$field] !== '' && $item[$field] !== null) {
                       $values[] = $item[$field];
                   }
               }

               if (empty($values)) {
                   $merged[$field] = '';
               } else {
                   // 去重
                   $uniqueValues = array_unique($values);
                   if (count($uniqueValues) === 1) {
                       // 只有一个值，直接返回
                       $merged[$field] = reset($uniqueValues);
                   } else {
                       // 多个不同值，用中文逗号串联
                       $merged[$field] = implode('，', $uniqueValues);
                   }
               }
           }

           // 合作内容(AI_Content)使用中文分号分割并换行
           $contents = [];
           foreach ($advitems as $item) {
               if (isset($item['AI_Content']) && $item['AI_Content'] !== '' && $item['AI_Content'] !== null) {
                   $contents[] = $item['AI_Content'];
               }
           }
           if (!empty($contents)) {
               $merged['AI_Content'] = implode("；\n", $contents);
           } else {
               $merged['AI_Content'] = '';
           }

           // 备注(AI_Memo)使用中文分号分割并换行
           $memos = [];
           foreach ($advitems as $item) {
               if (isset($item['AI_Memo']) && $item['AI_Memo'] !== '' && $item['AI_Memo'] !== null) {
                   $memos[] = $item['AI_Memo'];
               }
           }
           if (!empty($memos)) {
               $merged['AI_Memo'] = implode("；\n", $memos);
           } else {
               $merged['AI_Memo'] = '';
           }

            // 处理日期范围（AI_PublishTime 至 AI_PublishEndTime，多个用逗号分割）
            $times = [];
            $mediaItems = [];
            foreach ($advitems as $item) {
                // 只取日期部分，去掉时间部分
                $startTime = isset($item['AI_PublishTime']) ? substr($item['AI_PublishTime'], 0, 10) : '';
                $endTime = isset($item['AI_PublishEndTime']) ? substr($item['AI_PublishEndTime'], 0, 10) : '';
                if (!empty($startTime) || !empty($endTime)) {
                    // 如果开始日期等于结束日期，只返回开始日期
                    if ($startTime === $endTime) {
                        $times[] = $startTime;
                    } else {
                        $times[] = $startTime . '至' . $endTime;
                    }
                }

                // 处理福州日报社广告类型 media 字段
                if ($item['AI_Type'] == 1) { // 1=福州日报广告
                    $mediaParts = [];

                    // 发布日期
                    if (!empty($startTime) || !empty($endTime)) {
                        if ($startTime === $endTime) {
                            $mediaParts[] = $startTime;
                        } else {
                            $mediaParts[] = $startTime . '至' . $endTime;
                        }
                    }

                    // 版位
                    if (!empty($item['AI_Field'])) {
                        $mediaParts[] = $item['AI_Field'];
                    }

                    // 规格
                    if (!empty($item['AI_Size'])) {
                        $mediaParts[] = $item['AI_Size'];
                    }

                    if (!empty($mediaParts)) {
                        $mediaItems[] = implode('、', $mediaParts);
                    }
                }
                // 处理小额业务确认单类型 media 字段
                elseif ($item['AI_Type'] == 2) { // 2=小额业务
                    $mediaParts = [];

                    // 版位
                    if (!empty($item['AI_Field'])) {
                        $mediaParts[] = $item['AI_Field'];
                    }

                    // 规格
                    if (!empty($item['AI_Size'])) {
                        $mediaParts[] = $item['AI_Size'];
                    }

                    // 颜色
                    if (!empty($item['AI_Color'])) {
                        $mediaParts[] = $item['AI_Color'];
                    }

                    // 次数
                    if (!empty($item['AI_PublishDayCount'])) {
                        $mediaParts[] = $item['AI_PublishDayCount'] . '次';
                    }

                    if (!empty($mediaParts)) {
                        $mediaItems[] = implode('、', $mediaParts);
                    }
                }
            }
            $merged['times'] = implode('；', $times);

            // 处理media字段
            if (!empty($mediaItems)) {
                if (count($mediaItems) === 1) {
                    $merged['media'] = $mediaItems[0];
                } else {
                    $merged['media'] = implode('；', $mediaItems);
                }
            } else {
                $merged['media'] = '';
            }

           // 处理规格（AI_Width * AI_Height，多个用中文逗号分割）
           $sizes = [];
           foreach ($advitems as $item) {
               $width = $item['AI_Width'] ?? '';
               $height = $item['AI_Height'] ?? '';
               if (!empty($width) || !empty($height)) {
                   $sizes[] = $width . '*' . $height;
               }
           }
           $merged['sizes'] = implode('，', $sizes);

           $merged['AI_OrderID'] = $orderId;

           $merged['AI_AmountReceivable_Cap'] = $this->convertAmountToCn($merged['AI_AmountReceivable']);


           if($order) {
              $thirdNo = $order['thirdNo'];
              $merged['approvers']=$this->getUsersignes($thirdNo);
              if(!$merged['approvers']){
                $merged['approvers']="业务中心主任：;分管主任：;业务部门组长：; ;";
              }
            }

           return $merged;
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage()];
      }
  }
  private function getUsersignes($thirdNo){
    if (!$thirdNo) return '';
    $approvers='';
    $approveres = WeixinOaApprovaldata::find()->where(['and',["=","agentid",$this->agentId],["=","thirdNo",$thirdNo]])->one();
              if($approveres){
                $temparr = [];
                $approvearr = json_decode($approveres['data'],true);
                foreach ($approvearr['data']['ApprovalNodes']['ApprovalNode'] as $node) {
                  $items = $node['Items']['Item'];
                  $roleid = $node['NodeRoleid'];
                  
                  $ttt = $this->getApproverAndDate($items);
                  if ($ttt){
                    if ($roleid) {
                      $role=WeixinOaRole::find()->where(['id'=>$roleid])->one();
                      if  ($role) {
                        $rolename = $role['rolename'];
                        if ($rolename === '部门负责人') {
                          $rolename = '业务中心主任';
                        } elseif ($rolename === '部门副职') {
                          $rolename = '分管主任';
                        }
                        $ttt = $rolename.'：'.$ttt;
                      }
                    } else {
                      $ttt = '业务部门组长：'.$ttt;
                    }
                    $ele = explode(';',$ttt);
                    if (count($ele)) {
                      foreach ($ele as $e) {
                        
                        $temparr[] = $e;
                        
                      }
                    }
                  }

                }
                // 去掉重复的审批人,保留后面的
                
                if ($temparr){
                  $tempstr = '';
        
                  for ($ij=sizeof($temparr)-1; $ij>-1; $ij--) {
                    $arr = explode(' ',$temparr[$ij]);
                    if ($tempstr&&$arr[0]&&strpos($tempstr,$arr[0])>-1){ // 有重复
                    }else{
                      $tempstr.=';'.$temparr[$ij];
                    }
                  }
                  $approvers = $tempstr?substr($tempstr,1):'';
                  
                }
          
              }
              return $approvers;
  }
  private function getApproverAndDate($items=[]){
    $res = '';
    foreach ($items as $ele) {
      if($ele['ItemStatus']==2){
        $res.=';'.$ele['ItemName'].' '.(intval($ele['ItemOpTime']) > 0?date('m/d',$ele['ItemOpTime']):'');
      }
      
    }
    return $res?substr($res,1):'';

  }
  /**
   * 设置订单和广告的生效状态
   * @return array
   */
  public function actionSetorderflag()
  {
      try {
          $orderId = $this->_request['orderid'] ?? null;
          $flag = $this->_request['flag'] ?? null;

          if (empty($orderId)) {
              return ['errorMessage' => '订单ID不能为空'];
          }

          if ($flag === null) {
              return ['errorMessage' => 'flag不能为空'];
          }

          $orderCheckSql = "SELECT * FROM " . Advorder::tableName() . " WHERE SYS_DOCUMENTID = :id";
          $orderResult = Yii::$app->paymentdb->createCommand($orderCheckSql)->bindValues([':id' => $orderId])->queryOne();

          if ($flag==0&&$orderResult['AO_Type']==1){
            return array('errorMessage'=>'请在【广告列表】发起审批');
            // return $this->actionStartflow($this->_request);
          }
        

          // 开启事务
          $transaction = Yii::$app->paymentdb->beginTransaction();
          try {
              // 检查订单是否有回款
              
              $orderReceived = floatval($orderResult['AO_ReceivedMoney'] ?? 0);
              if ($orderReceived > 0) {
                  return ['errorMessage' => '该订单已有回款，无法设置生效状态'];
              }
              if (!$this->checkAuthor($orderResult['SYS_AUTHORS'])) {
                  return ['errorMessage' => '只有本人才能操作'];
              }
              
              // 检查广告是否有回款
              $advCheckSql = "SELECT SUM(AI_AmountReceived) as totalReceived FROM advitem WHERE AI_OrderID = :orderId";
              $advResult = Yii::$app->paymentdb->createCommand($advCheckSql)->bindValues([':orderId' => $orderId])->queryOne();
              $advReceived = floatval($advResult['totalReceived'] ?? 0);
              if ($advReceived > 0) {
                  return ['errorMessage' => '该订单下的广告已有回款，无法设置生效状态'];
              }
              
              // 更新订单的SYS_DELETEFLAG
              $orderTable = Advorder::tableName();
              Yii::$app->paymentdb->createCommand()
                  ->update($orderTable, ['SYS_DELETEFLAG' => $flag,'thirdNo'=>''], ['SYS_DOCUMENTID' => $orderId])
                  ->execute();

              // 更新该订单下所有广告的SYS_DELETEFLAG
              Yii::$app->paymentdb->createCommand()
                  ->update('advitem', ['SYS_DELETEFLAG' => $flag,'thirdNo'=>''], ['AI_OrderID' => $orderId])
                  ->execute();

              $transaction->commit();

              return ['success' => true, 'message' => '更新成功'];
          } catch (\Throwable $th) {
              $transaction->rollBack();
              return ['errorMessage' => $th->getMessage()];
          }
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage()];
      }
  }

  /**
   * 设置单个广告的生效/未生效状态(只改变自己，不影响订单和其他广告)
   * @return array
   */
  public function actionSetadvitemflag()
  {
      try {
          $advitemId = $this->_request['advitemid'] ?? null;
          $flag = $this->_request['flag'] ?? null;

          if (empty($advitemId)) {
              return ['errorMessage' => '广告ID不能为空'];
          }

          if ($flag === null) {
              return ['errorMessage' => 'flag不能为空'];
          }

          $advitemSql = "SELECT * FROM advitem WHERE SYS_DOCUMENTID = :id";
          $advitemResult = Yii::$app->paymentdb->createCommand($advitemSql)->bindValues([':id' => $advitemId])->queryOne();

          if (!$advitemResult) {
              return ['errorMessage' => '广告不存在'];
          }

          if (!$this->checkAuthor($advitemResult['SYS_AUTHORS'])) {
              return ['errorMessage' => '只有本人才能操作'];
          }

          // 检查广告是否有回款
          if (floatval($advitemResult['AI_AmountReceived']) > 0) {
              return ['errorMessage' => '该广告已有回款，无法设置生效状态'];
          }

          // 更新该广告的SYS_DELETEFLAG和thirdNo
          Yii::$app->paymentdb->createCommand()
              ->update('advitem', ['SYS_DELETEFLAG' => $flag, 'thirdNo' => ''], ['SYS_DOCUMENTID' => $advitemId])
              ->execute();

          // 如果是重新提交(flag=1)，同步设置订单的SYS_DELETEFLAG=1
          if ($flag == 1 && !empty($advitemResult['AI_OrderID'])) {
              Yii::$app->paymentdb->createCommand()
                  ->update('advorder', ['SYS_DELETEFLAG' => 1, 'thirdNo' => ''], ['SYS_DOCUMENTID' => $advitemResult['AI_OrderID']])
                  ->execute();
          }

          return ['success' => true, 'message' => '更新成功'];
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage()];
      }
  }

  /**
   * 根据advitem或order的ID获取广告完整信息（包括Order表中部分字段）
   * @return array
   */
  public function actionGetadvitem()
  {
      try {
       
          $advitemId = $this->_request['advitemId'] ?? $this->_request['SYS_DOCUMENTID'] ?? null;
          $orderId = $this->_request['orderId'] ?? null;

          if (empty($advitemId) && empty($orderId)) {
              return ['errorMessage' => '广告ID或订单ID不能为空'];
          }

          // order表字段
          $orderFields = 'advorder.serial,advorder.contractserial, advorder.contractid, advorder.partb, advorder.partbname';
          $where = 'advitem.SYS_DOCUMENTID>0';
          if (!empty($orderId)) {
              $where .= " AND AI_OrderID in($orderId)";
          }
          if (!empty($advitemId)) {
              $where .= " AND advitem.SYS_DOCUMENTID in ($advitemId)";
          }

          $sql = "SELECT advitem.*, {$orderFields} 
                      FROM advitem 
                      LEFT JOIN advorder ON advorder.SYS_DOCUMENTID = advitem.AI_OrderID 
                      WHERE $where order by advitem.SYS_DOCUMENTID desc";

          $advitem = Yii::$app->paymentdb->createCommand($sql)->queryAll();
          
          // 遍历获取的advitem，fileurls中提取出name,并用逗号分割
          // fileurls内容如：/uploaded/advertisement/20260228/17722634274047.pdf?name=17660360856860.pdf&time=1772263429151&size=298146
          if (!empty($advitem)) {
              
              foreach ($advitem as &$item) {
                  
                   $item['AI_AmountReceivable_Cap'] = $this->convertAmountToCn($item['AI_AmountReceivable']);
                   $approvers = $this->getUsersignes($item['thirdNo']);
                    if(!$approvers){
                      $approvers="业务中心主任：;分管主任：;业务部门组长：; 广告审核：;";
                    }
                   $item['approvers'] = $approvers;
                   // 添加media字段
                   $mediaParts = [];
                   
                   if ($item['AI_Type'] == 1) { // 福州日报广告
                       // 发布日期
                       $startTime = isset($item['AI_PublishTime']) ? substr($item['AI_PublishTime'], 0, 10) : '';
                       $endTime = isset($item['AI_PublishEndTime']) ? substr($item['AI_PublishEndTime'], 0, 10) : '';
                       if (!empty($startTime) || !empty($endTime)) {
                           if ($startTime === $endTime) {
                               $mediaParts[] = $startTime;
                           } else {
                               $mediaParts[] = $startTime . '至' . $endTime;
                           }
                       }
                       // 版位
                       if (!empty($item['AI_Field'])) {
                           $mediaParts[] = $item['AI_Field'];
                       }
                        // 规格
                       
                      if (!empty($item['AI_Size'])) {
                       
                        if($item['AI_Size']=='异形广告'){

                          $mediaParts[] =$item['AI_Size']."(".$item['AI_Height']." * ".$item['AI_Width'].")";

                        }else{
                          $mediaParts[] = $item['AI_Size'];
                        }
                      }
                     



                   } elseif ($item['AI_Type'] == 2) { // 小额业务确认单
                       // 版位
                       if (!empty($item['AI_Field'])) {
                           $mediaParts[] = $item['AI_Field'];
                       }
                        // 规格
                        if (!empty($item['AI_Size'])) {
                          if($item['AI_Size']=='异形广告'){
                            $mediaParts[] =$item['AI_Size']."(".$item['AI_Height']."*".$item['AI_Width'].")";
                          }else{
                            $mediaParts[] = $item['AI_Size'];
                          }

                        }
                       
                       // 颜色
                       if (!empty($item['AI_Color'])) {
                           $mediaParts[] = $item['AI_Color'];
                       }
                       // 次数
                       if (!empty($item['AI_PublishDayCount'])) {
                           $mediaParts[] = $item['AI_PublishDayCount'] . '次';
                       }
                   }
                   
                   $item['media'] = implode('、', $mediaParts);
               }
           }
           
           return ['data' => $advitem];
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage()];
      }
  }
  public function actionGetadvitemsbyorderid(){
    $orderid = $this->_request['orderid'];
    $res = Yii::$app->paymentdb->createCommand("select * from advitem where AI_OrderID=$orderid")->queryAll();
    return $res;
  }
  public function actionAdvitemslist(){
    $par = json_decode(Yii::$app->request->getRawBody(), true);
    $total = 0;
    $page = isset($par['current']) ? intval($par['current']) : 1;
    $limit = isset($par['pageSize']) ? intval($par['pageSize']) : 10;
    $offset = $limit * ($page - 1);
    $where = $this->getAdvitemWhere($par);
    $orderby = 'order by a.SYS_DOCUMENTID desc';
    if ($par['orderby']) {
      $orderby = " order by " . $par['orderby'];
    }
      // 分页查询
  
    $conditions = join(' and ', $where);

    

		$temp = " (select * from advitem where ".$conditions." ) a  LEFT JOIN advorder adv ON adv.SYS_DOCUMENTID=a.AI_OrderID LEFT JOIN fsys_node n ON n.NNODEID=adv.AO_Org_ID ";



		$sql = "select a.*,adv.partbname,adv.partb,n.STRNODENAME as AI_Org, adv.contractid as contractid,adv.contractserial as contractserial,adv.AO_Salesman as AI_Salesman  from  $temp";


    

    $cnt = Yii::$app->paymentdb->createCommand("select count(*) as cnt from advitem where ".$conditions."")->queryOne();
    if ($cnt) {
      $total = $cnt['cnt'];
    }
    $sql .= " ".$orderby." limit $offset,$limit";
 
    // 如果 pageSize为10000 且 columns 和 colnames 不为空，则生成Excel文件
    if ($limit == 10000 && !empty($par['columns']) && !empty($par['colnames'])) {
   
        if (sizeof($par)<=3){
          return array('errorMessage'=>"未设置筛选条件");
        }
        // 清除任何之前的输出缓冲
        if (ob_get_level()) {
            ob_clean();
        }
        
        // 先执行查询获取所有数据
        $res = Yii::$app->paymentdb->createCommand($sql)->queryAll();
  
        
        // 解析列配置
        $columns = explode(',', $par['columns']);
        $colnames = explode(',', $par['colnames']);
        
        // 创建PHPExcel对象
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getActiveSheet()->setTitle('广告明细');
        
        // 设置表头
        $colIndex = 0;
        foreach ($colnames as $colname) {
            $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($colIndex, 1, trim($colname));
            $colIndex++;
        }
        // 填充数据
        $rowIndex = 2;
        foreach ($res as $rowData) {
            $colIndex = 0;
            foreach ($columns as $column) {
                $columnName = trim($column);
                $value = isset($rowData[$columnName]) ? $rowData[$columnName] : '';
                
                // 处理特殊字符，防止Excel格式问题
                if (is_string($value)) {
                    // 确保UTF-8编码
                    $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                }
                
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($colIndex, $rowIndex, $value);
                $colIndex++;
            }
            $rowIndex++;
        }
        
        // 清除任何可能的输出缓冲，确保输出纯净的Excel文件
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // 设置HTTP头信息
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="广告明细导出.xlsx"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        header('Expires: 0');
        
        // 输出Excel文件
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        
        // 禁用输出缓冲并直接发送文件
        $objWriter->save('php://output');
        
        // 立即退出，确保不输出其他内容
        exit;
    }
    
    $res = Yii::$app->paymentdb->createCommand($sql)->queryAll();
    
    $result["pageIndex"] = intval($page);
    $result["pageSize"] = intval($limit);
    $result["total"] = intval($total);
    $result['rows'] = $res;

    // 如果 pageSize为10000
    // columns  不为空，比如：AI_Debt,AI_Salesman,AI_Customer,contractserial,AI_OrderID,SYS_DOCUMENTID,assistantname,parbname
    // colnames 不为空，比如: "欠款,业务员,客户,合同,订单号,广告号,协助人员"
    // 则将查询结果按照 columns中字段的顺序返回，xlsx文件

    return $result;

  }
  private function getAdvitemWhere($json)
  {
      $where = [];

    
      

      if (!empty($json['withdeleted'])) {
        $where[] = "SYS_DOCUMENTID>0";
      }else{
        if ($json['SYS_DELETEFLAG']){
          $where[] = "SYS_DELETEFLAG=1";
        }else{
          $where[] = "SYS_DELETEFLAG=0";
        }
        

      }
      // 检查是否为数组或可访问对象
      if (!is_array($json) && !($json instanceof \ArrayAccess)) {
          return $where;
      }
      $advwhere = '';

      $userId = $this->_adminInfo['wxuserid'];
   
      
      $depts = $this->getDepts();
      
      // 构建与我相关的条件

      $myRelatedCondition = "(AO_Salesman_ID = '{$userId}' ";
      $myRelatedCondition .= " OR AI_TradeID in (SELECT orgid from user_org WHERE userid='{$userId}') OR  SYS_AUTHORS = '{$userId}' ";
      $myRelatedCondition .= " OR  AO_Salesman_ID = '{$userId}' ";
      $myRelatedCondition .= ")";        
      // 构建权限条件
      if (sizeof($depts) > 0) {
          // 没有指定部门时，需要查询我创建的或有权限的部门
          if (empty($this->_request['departmentid'])) {
              $permissionCondition = "(".$myRelatedCondition.") OR (departmentid IN (" . implode(',', $depts) . "))";
          } else {
              $permissionCondition = "(departmentid IN (" . implode(',', $depts) . "))";
          }
      } else {
          // 没有部门权限时，只能查询与我相关的
          $permissionCondition = $myRelatedCondition;
      }
      $advwhere .= " and ({$permissionCondition})";

  
      // 构建 advorder 子查询条件
      if (!empty($json['contractid'])) {
          $advwhere .= " and contractid in (" . $json['contractid'] . ")";
      }
      if (!empty($json['partb'])) {
          $advwhere .= " and partb in (" . $json['partb'] . ")";
      }
      if (!empty($json['orgids'])) {
          $advwhere .= " and AO_Org_ID in (" . $json['orgids'] . ")";
      }

      if (!empty($json['AI_Org_ID'])) {
          $advwhere .= " and AO_Org_ID in (" . $json['AI_Org_ID'] . ")";
      }
      
      if (!empty($json['AI_Salesman'])) {
          $values = array_map(function ($v) {
              return "'" . $this->escape(trim($v)) . "'";
          }, explode(',', $json['AI_Salesman']));
          $advwhere.= " and AO_Salesman in (" . implode(',', $values) . ")";
      }
      if (!empty($json['AI_Salesman_ID'])) {
          $values = array_map(function ($v) {
              return "'" . $this->escape(trim($v)) . "'";
          }, explode(',', $json['AI_Salesman_ID']));
          $advwhere.= " and AO_Salesman_ID in (" . implode(',', $values) . ")";
      }

      if (strlen($advwhere) > 0) {
          $subQuery = "select SYS_DOCUMENTID from advorder where " . ltrim($advwhere, ' and');
          $where[] = "AI_OrderID in ({$subQuery})";
      }

      if (!empty($json['assistant'])) {
          $where[] = "`assistant`='" . $json['assistant'] . "'";
      }
      if (!empty($json['assistantdepartmentid'])) {
          $where[] = "assistantdepartmentid in (" . $json['assistantdepartmentid'] . ")";
      }

 
      if (!empty($json['AI_Customer_ID'])) {
          $where[] = "AI_Customer_ID in (" . $json['AI_Customer_ID'] . ")";
      }
      if (!empty($json['AI_OrderID'])) {
          $where[] = "AI_OrderID in (" . $json['AI_OrderID'] . ")";
      }
      if (!empty($json['AI_Type'])) {
          $where[] = "AI_Type in (" . $json['AI_Type'] . ")";
      }
      
      if (!empty($json['AI_Size_ID'])) {
          $where[] = "AI_Size_ID in (" . $json['AI_Size_ID'] . ")";
      }
      

      if (!empty($json['AI_CustomerLike'])) {
          $like = '%' . $this->escape($json['AI_CustomerLike']) . '%';
          $where[] = "AI_Customer like '{$like}'";
      }
      if (!empty($json['SYS_AUTHORS'])) {
          $like = '%' . $this->escape($json['SYS_AUTHORS']) . '%';
          $where[] = "SYS_AUTHORS like '{$like}'";
      }
      if (!empty($json['AI_Customer'])) {
          $where[] = "AI_Customer='" . $this->escape($json['AI_Customer']) . "'";
      }

      if (!empty($json['AI_PublishTimeStart'])) {
          $where[] = "AI_PublishTime>='{$json['AI_PublishTimeStart']}'";
      }
      if (!empty($json['AI_PublishTime'])) {
          $where[] = "AI_PublishTime>='{$json['AI_PublishTime']}'";
      }
      if (!empty($json['AI_PublishEndTime'])) {
          $where[] = "AI_PublishEndTime<='".($json['AI_PublishEndTime'].' 23:59:59')."'";
      }

      
      if (!empty($json['AI_Content'])) {
          $like = '%' . $this->escape($json['AI_Content']) . '%';
          $where[] = "AI_Content like '{$like}'";
      }
      if (!empty($json['AI_PayMode'])) {
          $where[] = "AI_PayMode='" . $this->escape($json['AI_PayMode']) . "'";
      }
      if (!empty($json['AI_InvoiceNo'])) {
          $like = '%' . $this->escape($json['AI_InvoiceNo']) . '%';
          $where[] = "AI_InvoiceNo like '{$like}'";
      }

      if (isset($json['isBalance']) && $json['isBalance'] === true) {
          $where[] = "AI_UnbalancedMoney != 0.0";
      }

      if (!empty($json['AI_Memo'])) {
          $like = '%' . $this->escape($json['AI_Memo']) . '%';
          $where[] = "AI_Memo like '{$like}'";
      }
       if (!empty($json['AI_Field'])) {
           $like = '%' . $this->escape($json['AI_Field']) . '%';
           $where[] = "AI_Field like '{$like}'";
       }
       if (!empty($json['AI_Field_ID'])) {
           $where[] = "AI_Field_ID in (" . $json['AI_Field_ID'] . ")";
       }
       if (!empty($json['AI_Publication_ID'])) {
           $where[] = "AI_Publication_ID in (" . $json['AI_Publication_ID'] . ")";
       }
       if (!empty($json['AI_PubMemo'])) {
          $like = '%' . $this->escape($json['AI_PubMemo']) . '%';
          $where[] = "AI_PubMemo like '{$like}'";
      }

      if (!empty($json['matchDebt'])) {
          $x1 = substr($json['matchDebt'], 0, 1);
          $x2 = substr($json['matchDebt'], 1);
          $where[] = "AI_Debt{$x1}{$x2}";
      }
      if (!empty($json['matchReceived'])) {
          $x1 = substr($json['matchReceived'], 0, 1);
          $x2 = substr($json['matchReceived'], 1);
          $where[] = "AI_AmountReceived{$x1}{$x2}";
      }

      if (isset($json['haszgf']) && $json['haszgf']) {
          $where[] = "zgf > 0";
      }
      if (isset($json['unzgf']) && $json['unzgf']) {
          $where[] = "zgf = 0";
      }

      if (!empty($json['AI_Publication'])) {
          $values = array_map(function ($v) {
              return "'" . $this->escape(trim($v)) . "'";
          }, explode(',', $json['AI_Publication']));
          $where[] = "AI_Publication in (" . implode(',', $values) . ")";
      }
      
      

      if (!empty($json['AI_TradeID'])) {
          $where[] = "AI_TradeID in (" . $json['AI_TradeID'] . ")";
      }
      if (!empty($json['SYS_DOCUMENTID'])) {
          $where[] = "SYS_DOCUMENTID in (" . $json['SYS_DOCUMENTID'] . ")";
      }
      

      return $where;
  }

  private function escape($string)
{
    // 如果有数据库连接，使用实际转义函数
    // return mysqli_real_escape_string($this->connection, $string);

    // 否则简单处理
    return addslashes((string)$string);
}
  private function convertAmountToCn($num) {

      $c1 = "零壹贰叁肆伍陆柒捌玖";

      $c2 = "分角元拾佰仟万拾佰仟亿";

      //精确到分后面就不要了，所以只留两个小数位

      $num = round($num, 2);

      //将数字转化为整数

      $num = $num * 100;
      $num = floatval(sprintf('%.2f', $num));
      
      if (strlen($num) > 10) {    

          return "金额太大，请检查";

      }

      $i = 0;

      $c = "";

      while (1) {    

          if ($i == 0) {    

                //获取最后一位数字        

                $n = substr($num, strlen($num) - 1, 1);    

          } else {        

                $n = $num % 10;    

          }    //每次将最后一位数字转化为中文    

          $p1 = substr($c1, 3 * $n, 3);    

          $p2 = substr($c2, 3 * $i, 3);    

          if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {        

                $c = $p1 . $p2 . $c;    

          } else {        

                $c = $p1 . $c;    

          }    

          $i = $i + 1;    

          //去掉数字最后一位了    

          $num = $num / 10;    
          $num = (int) (''.$num);   

          //结束循环    

          if ($num == 0) {        

                break;    

          }

      }

      $j = 0;

      $slen = strlen($c);

      while ($j < $slen) {    

          //utf8一个汉字相当3个字符    

          $m = substr($c, $j, 6);    

          //处理数字中很多0的情况,每次循环去掉一个汉字“零”    

          if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {        

                $left = substr($c, 0, $j);        

                $right = substr($c, $j + 3);        

                $c = $left . $right;        

                $j = $j - 3;        

                $slen = $slen - 3;    

          }    

          $j = $j + 3;

      }

      //这个是为了去掉类似23.0中最后一个“零”字

      if (substr($c, strlen($c) - 3, 3) == '零') {    

          $c = substr($c, 0, strlen($c) - 3);

      }

      if (empty($c)) {    

          return "零元整";

      } else {    

          return $c . "整";

      }

    }


  /**
   * 保存或更新组织架构（合并saveorg和updateorg）
   * @return array
   */
  public function actionSaveorg()
  {
      try {
          $obj = $this->_request;
          
          $tableName = FsysNode::tableName();
          
          if (empty($obj['STRNODENAME'])) {
              return ['errorMessage' => '组织名称不能为空'];
          }

          // 检查重名（同级下不能有同名组织）
          $parentId = $obj['NPARENTID'] ?? 0;
          $duplicateCheck = Yii::$app->paymentdb->createCommand(
              "SELECT NNODEID, STRNODENAME FROM {$tableName} WHERE STRNODENAME = :name AND NPARENTID = :parentId"
          )->bindValue(':name', $obj['STRNODENAME'])
           ->bindValue(':parentId', $parentId);

          if (!empty($obj['id'])) {
              // 更新时排除自身
              $duplicateCheck = $duplicateCheck->andWhere('NNODEID != :excludeId')
                  ->bindValue(':excludeId', $obj['id']);
          }

          $duplicateRecord = $duplicateCheck->queryOne();
          if ($duplicateRecord) {
              return ['errorMessage' => '该组织名称已存在', 'success' => false];
          }

          if (!empty($obj['id'])) {
              $id = $obj['id'];
  
              $existingRecord = Yii::$app->paymentdb->createCommand(
                  "SELECT * FROM fsys_node WHERE NNODEID = :id"
              )->bindValue(':id', $id)->queryOne();
              
              if (!$existingRecord) {
                  return ['errorMessage' => '记录不存在', 'success' => false];
              }
            
              // 检查是否是创建人
              if ($existingRecord['creator'] != $this->_adminInfo['wxuserid']) {
                  $user = WeixinOAUserInfo::find()->where(['userid'=>$existingRecord['creator']])->one();
                  return ['errorMessage' => '只有创建人['.$user['name'].']才能修改此记录', 'success' => false];
              }
              unset($obj['id']);
              
              $updateFields = [];
              $updateParams = [];
              foreach ($obj as $key => $value) {
                  $updateFields[] = "`$key` = :$key";
                  $updateParams[":$key"] = $value;
              }
              $updateParams[':id'] = $id;
              
              
              
              $sql = "UPDATE {$tableName} SET " . implode(', ', $updateFields) . " WHERE NNODEID = :id";
              Yii::$app->paymentdb->createCommand($sql)->bindValues($updateParams)->execute();
              
              return ['data' => ['id' => $id], 'success' => true, 'message' => '更新成功'];
          } else {
              // 
              $obj['NPARENTID'] = $obj['NPARENTID'] ?? 0;
              // 添加creator
              $obj['creator']= $this->_adminInfo['wxuserid'];
              $columns = array_keys($obj);
              $values = array_values($obj);
              $placeholders = array_map(function($col) { return ":$col"; }, $columns);
              
              $sql = "INSERT INTO {$tableName} (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
              Yii::$app->paymentdb->createCommand($sql)->bindValues($obj)->execute();
              
              $insertId = Yii::$app->paymentdb->getLastInsertID();
              return ['data' => ['id' => $insertId], 'success' => true, 'message' => '新增成功'];
          }
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage(), 'success' => false];
      }
  }

  /**
   * 删除组织架构
   * @return array
   */
  public function actionDeleteorg()
  {
      try {
          $id = $this->_request['id'] ?? null;
          
          if (empty($id)) {
              return ['errorMessage' => 'ID不能为空'];
          }
          
          $tableName = FsysNode::tableName();
          
          $sql = "DELETE FROM {$tableName} WHERE NNODEID = :id";
          Yii::$app->paymentdb->createCommand($sql)->bindValues([':id' => $id])->execute();
          
          return ['success' => true, 'message' => '删除成功'];
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage(), 'success' => false];
      }
  }

  /**
   * 保存或更新行业（合并savetrade和updatetrade）
   * @return array
   */
  public function actionSavetrade()
  {
      try {
          $obj = $this->_request;
          
          if (empty($obj['label'])) {
              return ['errorMessage' => '行业名称不能为空'];
          }
          
          if (!empty($obj['id'])) {
              $id = $obj['id'];
              unset($obj['id']);
              
              $updateFields = [];
              $updateParams = [];
              foreach ($obj as $key => $value) {
                  $updateFields[] = "`$key` = :$key";
                  $updateParams[":$key"] = $value;
              }
              $updateParams[':id'] = $id;
              
              $sql = "UPDATE org SET " . implode(', ', $updateFields) . " WHERE id = :id";
              Yii::$app->paymentdb->createCommand($sql)->bindValues($updateParams)->execute();
              
              return ['data' => ['id' => $id], 'success' => true, 'message' => '更新成功'];
          } else {
              $obj['parentid'] = $obj['parentid'] ?? 0;
              $obj['depth'] = $obj['depth'] ?? 1;
              
              $columns = array_keys($obj);
              $values = array_values($obj);
              $placeholders = array_map(function($col) { return ":$col"; }, $columns);
              
              $sql = "INSERT INTO org (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
              Yii::$app->paymentdb->createCommand($sql)->bindValues($obj)->execute();
              
              $insertId = Yii::$app->paymentdb->getLastInsertID();
              return ['data' => ['id' => $insertId], 'success' => true, 'message' => '新增成功'];
          }
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage(), 'success' => false];
      }
  }

  /**
   * 删除行业
   * @return array
   */
  public function actionDeletetrade()
  {
      try {
          $id = $this->_request['id'] ?? null;
          
          if (empty($id)) {
              return ['errorMessage' => 'ID不能为空'];
          }
          
          $sql = "DELETE FROM org WHERE id = :id";
          Yii::$app->paymentdb->createCommand($sql)->bindValues([':id' => $id])->execute();
          
          return ['success' => true, 'message' => '删除成功'];
      } catch (Exception $e) {
          return ['errorMessage' => $e->getMessage(), 'success' => false];
      }
  }


    public function actionGetthirdno(){
      return $this->getThirdNo();
    }
    private function getThirdNo()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime =  substr(sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000).$this->_adminInfo['id'],0,20);
        return $msectime;
    }
    /**
     * 启动流程
     */
    public function actionStartflow(){
        $userinfo = $this->userinfo;
        $postdatas = $this->_request;
        $orderid = $postdatas['orderid'];

        // 只有经办人才能提交流程

        $p = Yii::$app->paymentdb->createCommand("
                SELECT o.*,
                       GROUP_CONCAT(DISTINCT i.AI_Publication_ID) as publication_ids
                FROM advorder o
                LEFT JOIN advitem i ON o.SYS_DOCUMENTID = i.AI_OrderID
                WHERE o.SYS_DOCUMENTID=:id
                GROUP BY o.SYS_DOCUMENTID
            ", [':id'=>$orderid])->queryOne();


 
        if($p['SYS_AUTHORS']!=$userinfo['userid']){
            return array('errorMessage'=>'只有经办才能提交流程');
        }
        


        // 获取流程模板
        $condition = [
            'departmentid' => $p['departmentid'],
            'userid' => $p['SYS_AUTHORS'],
            'publicationid' => $p['publication_ids'],
            'tp'=>'0',
        ];
        $templateid = $this->gettemplateid($condition);
        
        if(!$templateid){
            return array('errorMessage'=>'未找到对应审批流程配置');
        }

        $wfp = new WorkflowParse($this->agentId);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $condition = [
                'deptid' => $p['departmentid'],
                'userid' => $p['creator'],
                'amount' => $p['AO_AllMoney'],
                'publicationid' => $p['publication_ids'],
                'tp'=>'0'
            ];
            $flowdata = $wfp->startFlow($userinfo['userid'],$templateid, $condition, ['infoid'=>$orderid]);
            
            Yii::$app->paymentdb->createCommand()->update('advorder', [
                'thirdNo' => $flowdata['thirdNo'],
            ], 'SYS_DOCUMENTID='.$orderid)->execute();
            
            $title = '【广告审批】'.$p['SYS_CURRENTUSERNAME'].'的订单审批申请';
           $p['thirdNo']=$flowdata['thirdNo'];
           
            $this->send($flowdata['approvalUserid'], $title, $p);
            
            
            
        } catch (\Throwable $th) {
            $transaction->rollBack();
            return array('errorMessage'=>$th->getMessage());
        }
        $transaction->commit();
        return ['thirdNo' => $flowdata['thirdNo'], 'approvalUserid' => $flowdata['approvalUserid']];

    }

    /**
     * 启动广告审批流程
     */
    public function actionStartadvflow(){
        $userinfo = $this->userinfo;
        $postdatas = $this->_request;
        $advitemid = $postdatas['advitemid'];

        // 获取广告信息(联合order表获取departmentid)
        $p = Yii::$app->paymentdb->createCommand("
            SELECT a.*, o.departmentid
            FROM advitem a
            LEFT JOIN advorder o ON a.AI_OrderID = o.SYS_DOCUMENTID
            WHERE a.SYS_DOCUMENTID=:id
        ", [':id'=>$advitemid])->queryOne();

        if(!$p){
            return array('errorMessage'=>'广告不存在');
        }

        // 只有创建人能提交流程
        if($p['SYS_AUTHORS'] != $userinfo['userid']){
            return array('errorMessage'=>'只有创建人才能提交流程');
        }

        // 如果是小额订单直接设置为已审
        if($p['AI_Type'] == 2){
            $this->updateAdvitemAndCheckOrder($advitemid, $p['AI_OrderID']);
            return array('thirdNo'=>'', 'approvalUserid'=>'', 'isSmallAmount'=>true);
        }


        // 检查广告是否已经有在审批的流程
        if(!empty($p['thirdNo'])){
            return array('errorMessage'=>'该广告已经在审批流程中');
        }

        // 获取流程模板
        $condition = [
            'departmentid' => $p['departmentid'],
            'userid' => $p['SYS_AUTHORS'],
            'publicationid' => $p['AI_Publication_ID'],
            'tp'=>'0',
        ];
        $templateid = $this->gettemplateid($condition);

        if(!$templateid){
            return array('errorMessage'=>'未找到对应审批流程配置');
        }

        $wfp = new WorkflowParse($this->agentId);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $condition = [
                'deptid' => $p['departmentid'],
                'userid' => $p['SYS_AUTHORS'],
                'amount' => $p['AI_AmountReceivable'],
                'publicationid' => $p['AI_Publication_ID'],
                'tp'=>'0'
            ];
            $flowdata = $wfp->startFlow($userinfo['userid'],$templateid, $condition, ['infoid'=>$advitemid]);

            Yii::$app->paymentdb->createCommand()->update('advitem', [
                'thirdNo' => $flowdata['thirdNo'],
            ], 'SYS_DOCUMENTID='.$advitemid)->execute();

            $title = '【广告审批】'.$p['SYS_CURRENTUSERNAME'].'的广告审批申请';
            $p['thirdNo'] = $flowdata['thirdNo'];

            $this->sendadv($flowdata['approvalUserid'], $title, $p);

        } catch (\Throwable $th) {
            $transaction->rollBack();
            return array('errorMessage'=>$th->getMessage());
        }
        $transaction->commit();
        return ['thirdNo' => $flowdata['thirdNo'], 'approvalUserid' => $flowdata['approvalUserid']];

    }

    /**
     * 获取广告审批流程预览
     */
    public function actionViewadvflow(){
        $postdatas = $this->_request;
        $advitemid = $postdatas['advitemid'];

        if(empty($advitemid)){
            return array('errorMessage'=>'广告ID不能为空');
        }

        // 获取广告信息(联合order表获取departmentid)
        $p = Yii::$app->paymentdb->createCommand("
            SELECT a.*, o.departmentid
            FROM advitem a
            LEFT JOIN advorder o ON a.AI_OrderID = o.SYS_DOCUMENTID
            WHERE a.SYS_DOCUMENTID=:id
        ", [':id'=>$advitemid])->queryOne();

        if(!$p){
            return array('errorMessage'=>'广告不存在');
        }

        // 获取流程模板
        $condition = [
            'departmentid' => $p['departmentid'],
            'userid' => $p['SYS_AUTHORS'],
            'publicationid' => $p['AI_Publication_ID'],
            'tp'=>'0',
        ];
        $templateid = $this->gettemplateid($condition);

        if(!$templateid){
            return array('errorMessage'=>'未找到对应审批流程配置');
        }

        $wfp = new WorkflowParse($this->agentId);
        $approvedata = $wfp->previewFlow($p['SYS_AUTHORS'], $templateid, [
            'deptid' => $p['departmentid'],
            'userid' => $p['SYS_AUTHORS'],
            'amount' => $p['AI_AmountReceivable'],
            'publicationid' => $p['AI_Publication_ID'],
            'tp'=>'0',
        ]);

        if ($approvedata['errorMessage']){
            return array('errorMessage'=>$approvedata['errorMessage']);
        }

        return $approvedata;
    }

    private function sendadv($approvalUserid,$title,$data){
      $url = "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/order/view?infoid=".$data['SYS_DOCUMENTID']."&thirdNo=".$data['thirdNo'];

      if (!$approvalUserid) return array('errorMessage' => '审批人为空');
      // 如果是数组，要改成|分隔的字符串（企业微信API要求）
      if (is_array($approvalUserid)) {
        $approvalUserid = implode('|', $approvalUserid);
      }

      $msgdata = [
        'touser' => $approvalUserid,
        'msgtype' => 'textcard',
        'agentid' => $this->agentId,
        'textcard' => [
            'title' => $title,
            'description' => '<div class="normal">客户名称：' . $data['AI_Customer'].'</div><div class="normal">广告金额：' . $data['AI_AmountReceivable'].'</div><div class="normal">发布平台：' . $data['AI_Publication'].'</div>',
            'url' => $url,
            'btntxt' => '详情'

        ]
      ];
      return $this->sendmsg($msgdata);
    }
    private function send($approvalUserid,$title,$data){
      $url = "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/order/view?orderid=".$data['SYS_DOCUMENTID']."&thirdNo=".$data['thirdNo'];

      if (!$approvalUserid) return array('errorMessage' => '审批人为空');
      // 如果是数组，要改成|分隔的字符串（企业微信API要求）
      if (is_array($approvalUserid)) {
        $approvalUserid = implode('|', $approvalUserid);
      }

      $msgdata = [
        'touser' => $approvalUserid,
        'msgtype' => 'textcard',
        'agentid' => $this->agentId,
        'textcard' => [
            'title' => $title,
            'description' => '<div class="normal">主体名称：' . $data['partbname'].'</div><div class="normal">客户名称：' . $data['AO_Customer'].'</div><div class="normal">订单金额：' . $data['AO_AllMoney'].'</div>',
            'url' => $url,
            'btntxt' => '详情'

        ]
      ];
      return $this->sendmsg($msgdata);
    }
    private function sendmsg($data)
    {
        return WxQyhJk::sendMessage($data['agentid'],$data['touser'],$data['textcard'],'textcard');
    }

    public function actionAgree(){
      $postdatas = $this->_request;
      $userid = $this->_adminInfo['wxuserid'];
      $thirdNo = $postdatas['thirdNo'];

      $transaction = Yii::$app->db->beginTransaction();
      try {
          $wfp = new WorkflowParse($this->agentId);
          $ret = $wfp->changeFlow($userid, 2, $postdatas);

          $noticeUserids = $wfp->updateAfterFlowChange($ret, $userid, 2, $postdatas, $transaction);

          // 检查是订单审批还是广告审批
          $order = Yii::$app->paymentdb->createCommand("SELECT * FROM advorder WHERE thirdNo=:thirdNo", [':thirdNo'=>$thirdNo])->queryOne();
          $advitem = null;
          if(!$order){
              // 可能是广告审批,查找广告(联合order获取partbname)
              $advitem = Yii::$app->paymentdb->createCommand("
                  SELECT a.*, o.partbname
                  FROM advitem a
                  LEFT JOIN advorder o ON a.AI_OrderID = o.SYS_DOCUMENTID
                  WHERE a.thirdNo=:thirdNo
              ", [':thirdNo'=>$thirdNo])->queryOne();
          }

          if($ret['isfinish'] == 1){
              if($order){
                  // 订单审批通过
                  Yii::$app->paymentdb->createCommand()->update('advorder', ['SYS_DELETEFLAG'=>0], 'thirdNo=:thirdNo', [':thirdNo'=>$thirdNo])->execute();
                  // 更新该订单下所有广告的SYS_DELETEFLAG
                  Yii::$app->paymentdb->createCommand()
                      ->update('advitem', ['SYS_DELETEFLAG' => 0], ['AI_OrderID' => $order['SYS_DOCUMENTID']])
                      ->execute();
                  $this->send($noticeUserids['noticeuserids'], '【广告审批】审批已通过', $order);
              } else if($advitem){
                   // 广告审批通过,只更新该广告
                   Yii::$app->paymentdb->createCommand()->update('advitem', ['SYS_DELETEFLAG'=>0], 'thirdNo=:thirdNo', [':thirdNo'=>$thirdNo])->execute();
                   // 检查订单下是否还有未审的广告(SYS_DELETEFLAG=1)
                   $stillHasUnapproved = Yii::$app->paymentdb->createCommand(
                       "SELECT SYS_DOCUMENTID FROM advitem WHERE AI_OrderID=:orderId AND SYS_DELETEFLAG=1 AND SYS_DOCUMENTID<>:docId LIMIT 1",
                       [':orderId' => $advitem['AI_OrderID'], ':docId' => $advitem['SYS_DOCUMENTID']]
                   )->queryOne();
                   if (!$stillHasUnapproved) {
                       Yii::$app->paymentdb->createCommand()->update('advorder', ['SYS_DELETEFLAG'=>0], 'SYS_DOCUMENTID=:orderId', [':orderId'=>$advitem['AI_OrderID']])->execute();
                   }
                   $this->sendadv($noticeUserids['noticeuserids'], '【广告审批】广告审批已通过', $advitem);
               }
          }else if($noticeUserids){

              if($order){
                  $this->send($noticeUserids['noticeuserids'], '【广告审批】您有订单需要审批', $order);
              } else if($advitem){
                  $this->sendadv($noticeUserids['noticeuserids'], '【广告审批】您有广告需要审批', $advitem);
              }
          }
          

          $transaction->commit();

          return ['data'=>$ret];
      } catch (\Throwable $th) {
          $transaction->rollBack();
          return ['errorMessage'=>$th->getMessage()];
      }
    }

    /**
     * 驳回审批
     */
    public function actionReject(){
        $postdatas = $this->_request;
        $userid = $this->_adminInfo['wxuserid'];
        $thirdNo = $postdatas['thirdNo'];

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $wfp = new WorkflowParse($this->agentId);
            $ret = $wfp->changeFlow($userid, 3, $postdatas);

            $noticeUserids = $wfp->updateAfterFlowChange($ret, $userid, 3, $postdatas, $transaction);

            $order = Yii::$app->paymentdb->createCommand("SELECT * FROM advorder WHERE thirdNo=:thirdNo", [':thirdNo'=>$thirdNo])->queryOne();
            $advitem = null;
            if(!$order){
                $advitem = Yii::$app->paymentdb->createCommand("
                    SELECT a.*, o.partbname
                    FROM advitem a
                    LEFT JOIN advorder o ON a.AI_OrderID = o.SYS_DOCUMENTID
                    WHERE a.thirdNo=:thirdNo
                ", [':thirdNo'=>$thirdNo])->queryOne();
            }

            if($order){
                Yii::$app->paymentdb->createCommand()->update(
                    'advorder',
                    ['thirdNo' => '',],
                    'thirdNo=:thirdNo',
                    [':thirdNo' => $thirdNo]
                )->execute();
                $this->send($noticeUserids['noticeuserids'], '【广告审批】订单审批申请被驳回', $order);
            } else if($advitem){
                Yii::$app->paymentdb->createCommand()->update(
                    'advitem',
                    ['thirdNo' => ''],
                    'thirdNo=:thirdNo',
                    [':thirdNo' => $thirdNo]
                )->execute();
                $this->sendadv($noticeUserids['noticeuserids'], '【广告审批】广告审批被驳回', $advitem);
            }
            $transaction->commit();

            return ['data'=>$ret];
        } catch (\Throwable $th) {
            $transaction->rollBack();
            return ['errorMessage'=>$th->getMessage()];
        }
    }

    /**
     * 撤销审批
     */
    public function actionCancel(){
      $postdatas = $this->_request;
      $userid = $this->_adminInfo['wxuserid'];
      $thirdNo = $postdatas['thirdNo'];

      $transaction = Yii::$app->db->beginTransaction();
      try {
          $wfp = new WorkflowParse($this->agentId);
          $ret = $wfp->changeFlow($userid, 4, $postdatas);

          $noticeUserids = $wfp->updateAfterFlowChange($ret, $userid, 4, $postdatas, $transaction);

          $order = Yii::$app->paymentdb->createCommand("SELECT * FROM advorder WHERE thirdNo=:thirdNo", [':thirdNo'=>$thirdNo])->queryOne();
          $advitem = null;
          if(!$order){
              $advitem = Yii::$app->paymentdb->createCommand("SELECT * FROM advitem WHERE thirdNo=:thirdNo", [':thirdNo'=>$thirdNo])->queryOne();
          }

          if($order){
              Yii::$app->paymentdb->createCommand()->update(
                  'advorder',
                  ['thirdNo' => '',],
                  'thirdNo=:thirdNo',
                  [':thirdNo' => $thirdNo]
              )->execute();
              $transaction->commit();
              $title = '【广告审批撤销】'.$order['SYS_CURRENTUSERNAME'].'的订单审批申请';
              $this->send($noticeUserids['noticeuserids'], $title, $order);
          } else if($advitem){
              Yii::$app->paymentdb->createCommand()->update(
                  'advitem',
                  ['thirdNo' => ''],
                  'thirdNo=:thirdNo',
                  [':thirdNo' => $thirdNo]
              )->execute();
              $transaction->commit();
              $advitem['partbname'] = $advitem['AI_Customer'];
              $advitem['AO_Customer'] = $advitem['AI_Customer'];
              $advitem['AO_AllMoney'] = $advitem['AI_AmountReceivable'];
              $this->sendadv($noticeUserids['noticeuserids'], '【广告审批撤销】广告审批已撤销', $advitem);
          } else {
              $transaction->commit();
          }

          return ['data'=>$ret];
      } catch (\Throwable $th) {
          $transaction->rollBack();
          return ['errorMessage'=>$th->getMessage()];
      }
    }

    /**
     * 催办
     */
    public function actionUrge(){
        $postdatas = $this->_request;
        $thirdNo = $postdatas['thirdNo'];
        $userid = $this->_adminInfo['wxuserid'];

        $info = WeixinOaApprovalInfo::find()->where(['thirdNo'=>$thirdNo,'agentId'=>$this->agentId])->one();
        if(!$info) return array('errorMessage'=>'流程不存在');

        if($info['userId'] != $userid){
            return array('errorMessage'=>'只有申请人才能催办');
        }
        if($info['status'] != 1) return array('errorMessage'=>'流程已完成，不能催办');

        $approvers = explode('|', $info['approvalUserid']);
        $order = Yii::$app->paymentdb->createCommand("SELECT * FROM advorder WHERE thirdNo=:thirdNo", [':thirdNo'=>$thirdNo])->queryOne();
        $advitem = null;
        if(!$order){
            $advitem = Yii::$app->paymentdb->createCommand("SELECT * FROM advitem WHERE thirdNo=:thirdNo", [':thirdNo'=>$thirdNo])->queryOne();
        }

        if($order){
            $title = '【广告审批催办】'.$info['userName'].'的订单审批申请';
            $sendResult = $this->send($approvers, $title, $order);
        } else if($advitem){
            $advitem['partbname'] = $advitem['AI_Customer'];
            $advitem['AO_Customer'] = $advitem['AI_Customer'];
            $advitem['AO_AllMoney'] = $advitem['AI_AmountReceivable'];
            $title = '【广告审批催办】'.$info['userName'].'的广告审批申请';
            $sendResult = $this->sendadv($approvers, $title, $advitem);
        } else {
            return array('errorMessage' => '未找到相关订单或广告记录');
        }

        if (!empty($sendResult['errorMessage'])) {
            return array('errorMessage' => '发送消息失败：' . $sendResult['errorMessage']);
        }

        return array('data'=>array('ret'=>1));
    }




    /**
     * 待审批列表
     */
    public function actionInglist(){
        $userid = $this->_request['wxuserid'] ? $this->_request['wxuserid'] : $this->_adminInfo['wxuserid'];
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;
        $offset = $limit * ($page - 1);
        
        $model = WeixinOaApprovalInfo::find()
            ->alias('a')
            ->select('a.*,p.*')
            ->leftJoin(['p'=>'advorder'],"p.thirdNo=a.thirdNo")
            ->where(['and',
                ['=','a.agentId',$this->agentId],
                ['=','a.status',1],
                new Expression("FIND_IN_SET('$userid', a.approvalUserid)")
            ])
            ->orderBy('a.id desc');
            
        $total = $model->count();
        $data = $model->limit($limit)->offset($offset)->asArray()->all();
        
        return array('data'=>$data,'total'=>$total,'current'=>$page,'pageSize'=>$limit);
    }

    

    /**
     * 抄送列表
     */
    public function actionGetnotifydata(){
        $userid = $this->_request['wxuserid'] ? $this->_request['wxuserid'] : $this->_adminInfo['wxuserid'];
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;
        $offset = $limit * ($page - 1);
        
        $model = WeixinOaApprovalInfo::find()
            ->alias('a')
            ->select('a.*,p.*')
            ->leftJoin(['p'=>'advorder'],"p.thirdNo=a.thirdNo")
            ->leftJoin(['d'=>WeixinOaApprovaldata::tableName()],"d.thirdNo=a.thirdNo")
            ->where(['and',
                ['=','a.agentId',$this->agentId],
                new Expression("JSON_CONTAINS(d.data->'$.notifiers', '\"$userid\"')")
            ])
            ->orderBy('a.id desc');
            
        $total = $model->count();
        $data = $model->limit($limit)->offset($offset)->asArray()->all();
        
        return array('data'=>$data,'total'=>$total,'current'=>$page,'pageSize'=>$limit);
    }

    /**
     * 获取tabs
     */
    public function actionGettabs(){
        return array('data'=>[]);
    }

    





    /**
     * 获取流程配置
     */
  private function getflow($data){
  
    
    if (!$data) {
      throw new Exception('data参数为空，{amount:5000,payerid:24,payer:日报,amountsType:3,userid:cainig}');
		}

    $userid = $this->_adminInfo['wxuserid'];
    if ($data['userid']){
      $userid = $data['userid'];
    }else if ($data['userId']){
      $userid = $data['userId'];
    }

    $template_userinfo=$this->getUserinfo($userid);
    $template = $this->getTemplate($data, $template_userinfo);	

    if (!$template) {
 
      throw new Exception('申请失败,无模版,部门【'.$template_userinfo['departmentname'].'】,用户【'.$template_userinfo['name'].'】');
    }
    $wfp = new WorkflowParse;
		$flowdata = $wfp->flowParse($userid, $template['templateid'],array());
		
    
  
 

    return $flowdata;
  }

    /**
     * 更新审批状态
     */
    private function changeStatus($userid, $status, $postdatas){
        $wfp = new WorkflowParse($this->agentId);
        
        return $wfp->changeFlow($userid, $status, $postdatas);
    }



    /**
     * 流程预览
     */
    public function actionViewflow(){
        $act = $this->_request['act'] ?? 1;
        $infoid = $this->_request['infoid'];
        
        if (!$infoid) {
            return array('errorMessage'=>'infoid 不能为空');
        }

        try {
            $p = Yii::$app->paymentdb->createCommand("
                SELECT o.*,
                       GROUP_CONCAT(DISTINCT i.AI_Publication_ID) as publication_ids
                FROM advorder o
                LEFT JOIN advitem i ON o.SYS_DOCUMENTID = i.AI_OrderID
                WHERE o.SYS_DOCUMENTID=:id
                GROUP BY o.SYS_DOCUMENTID
            ", [':id'=>$infoid])->queryOne();


           
            // 获取流程模板
            $condition = [
                'userid' => $p['AO_Salesman_ID'],
                'departmentid' => $p['departmentid'],
                'amount' => $p['AO_AllMoney'],
                'publicationid' => $p['publication_ids'],
            ];
       
            $templateid = $this->gettemplateid($condition);
                
            if(!$templateid){
                return array('errorMessage'=>'未找到对应审批流程配置');
            }
            
            $wfp = new WorkflowParse($this->agentId);
            $approvedata = $wfp->previewFlow($p['AO_Salesman_ID'], $templateid, [
                'deptid' => $p['departmentid'],
                'userid' => $p['AO_Salesman_ID'],
                'amount' => $p['AO_AllMoney'],
                'publicationid' => $p['publication_ids'],
                'tp'=>'0',// 这个要加，否则会匹配失败
                // 'publicationid'=>
            ]);
            if ($approvedata['errorMessage']){
              return array('errorMessage'=>$approvedata['errorMessage']);
            }
            

            return $approvedata;
            
        } catch (\Throwable $th) {
            return array('errorMessage'=>$th->getMessage());
        }
    }
    
    /**
     * 获取流程数据
     */
    public function actionGetflowdata(){
        $thirdNo = $this->_request['thirdNo'];
        $infoid = $this->_request['infoid'];

        $info = false;
        $viewdata = 0;
        $basic = null;

        if($infoid){
            

            // 订单
            if ($infoid<105503){
                $info = Yii::$app->paymentdb->createCommand("
                  SELECT a.*, o.departmentid,
                        GROUP_CONCAT(DISTINCT TRIM(i.fileurls)) as fileurls
                  FROM advitem a
                  LEFT JOIN advorder o ON a.AI_OrderID = o.SYS_DOCUMENTID
                  LEFT JOIN advitem i ON o.SYS_DOCUMENTID = i.AI_OrderID
                  WHERE a.AI_OrderID=:id
                  GROUP BY a.SYS_DOCUMENTID
              ", [':id'=>$infoid])->queryOne();
            }else{
              $info = Yii::$app->paymentdb->createCommand("
                  SELECT a.*, o.departmentid 
                  FROM advitem a
                  LEFT JOIN advorder o ON a.AI_OrderID = o.SYS_DOCUMENTID
                  WHERE a.SYS_DOCUMENTID=:id
                  GROUP BY a.SYS_DOCUMENTID
              ", [':id'=>$infoid])->queryOne();
            }

            // 从审批信息获取thirdNo
            if ($info && isset($info['thirdNo']) && $info['thirdNo']){
                $thirdNo = $info['thirdNo'];
            } else if ($info && isset($info['AI_OrderID'])){
                // 广告可能还没有thirdNo,检查订单是否有
                $orderThirdNo = Yii::$app->paymentdb->createCommand(
                    "SELECT thirdNo FROM advorder WHERE SYS_DOCUMENTID=:id", [':id'=>$info['AI_OrderID']]
                )->queryScalar();
                if ($orderThirdNo){
                    $thirdNo = $orderThirdNo;
                }
            }
        }

        $wfp = new WorkflowParse($this->agentId);
        try {
            if ($thirdNo){
                $viewdata = $wfp->flowViewdata($thirdNo);
                $basic = WeixinOaApprovalInfo::find()->alias('a')->select('a.*,u.avatar')
                    ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'a.userId=u.userid')
                    ->where(['a.thirdNo'=>$thirdNo])->asArray()->one();
            }
        } catch (\Throwable $th) {
            return array('errorMessage'=> $th->getMessage());
        }

        return array(
            'viewdata'=>$viewdata,
            'basic'=>$basic,
            'info'=>$info,
            'statusCn'=>[0=>'待提交',1=>'审批中',2=>'已同意',3=>'已驳回',4=>'已取消']
        );
    }
    
    /**
     * 流程动作统一接口
     */
    public function actionFlowact(){
        $postdatas = $this->_request;
        $act = $postdatas['act'];
        $thirdNo = $postdatas['thirdNo'];
     
        
        if (!$thirdNo) return array('errorMessage'=>'thirdNo不能为空');
        
        
        try {
            switch ($act) {
                case 'agree':
                    return $this->actionAgree();
                case 'reject':
                    return $this->actionReject();
                case 'cancel':
                   return $this->actionCancel();
                case 'urge':
                    return $this->actionUrge();
                default:
                    return array('errorMessage'=>'未知操作类型');
            }
            
            return array('data'=>$ret);
        } catch (\Throwable $th) {
            return array('errorMessage'=>$th->getMessage());
        }
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
    
    private function getTagName($tagid) {
        $t = WeixinOauserTaguser::findOne($tagid);
        return $t?$t['tagName']:'';
    }
    /**
     * 获取规格列表（分页）
     */
    public function actionGetsizeslist(){
        $userid = $this->_adminInfo['wxuserid'];

        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;
        $offset = $limit * ($page - 1);

        $where = ['and', ['=', 'SYS_DELETEFLAG', 0]];

        // 按广告类型过滤
        if (isset($this->_request['adTypeId']) && $this->_request['adTypeId']) {
            $where[] = ['=', 'E_AdType_ID', $this->_request['adTypeId']];
        }

        $whereStr = '';
        foreach ($where as $condition) {
            if (is_array($condition) && count($condition) >= 2) {
                if ($whereStr !== '') $whereStr .= ' AND ';
                $whereStr .= $condition[1] . ' ' . $condition[0] . ' ' . (is_string($condition[2]) ? "'" . $condition[2] . "'" : $condition[2]);
            }
        }
        if ($whereStr) $whereStr = ' WHERE ' . $whereStr;

        $sqlCount = "SELECT COUNT(*) as cnt FROM advsize" . $whereStr;
        $countResult = Yii::$app->paymentdb->createCommand($sqlCount)->queryOne();
        $total = $countResult['cnt'] ?? 0;

        $sql = "SELECT * FROM advsize" . $whereStr . " ORDER BY SYS_DOCUMENTID desc LIMIT $limit OFFSET $offset";
        $data = Yii::$app->paymentdb->createCommand($sql)->queryAll();

        $_result = array();
        $_result["current"] = $page;
        $_result["pageSize"] = $limit;
        $_result["total"] = $total;
        $_result['data'] = $data;
        return $_result;
    }

    /**
     * 获取订单流程模板ID
     */
    private function gettemplateid($condition){
        $userid = $condition['userid']?$condition['userid']:$this->_adminInfo['wxuserid'];
        $departmentid = $condition['departmentid']?$condition['departmentid']:$this->userinfo['departmentid'];
      
        
        $where = ['and',['>','id',0]];
        // if ($condition['publicationid']){
        //   $where[] = ['=','publicationid',$condition['publicationid']];
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
    /**
     * 已审批列表
     */
    public function actionFinishlist(){
      $userid = $this->_adminInfo['wxuserid'];
      $total = 0;
      $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
      $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
      $offset = $limit * ($page - 1);
      $orderby = 'id desc';
      if (isset($this->_request['orderby'])){
        $orderby = $this->_request['orderby'];
      }
      
      $where = ['and',new Expression("FIND_IN_SET('".$userid."', i.approvalUserid)"),['=','agentId',$this->agentId]];




      $model = WeixinOaApprovalInfo::find()->alias('i')->where($where);
      

      $total = $model->count();
      $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();    
      $_result = array();
      $_result["current"] = $page;
      $_result["pageSize"] = $limit;
      $_result["total"] = $total;
      $_result['data'] = $res;
      return $_result;
    }
    public function actionApprovallist(){



      $userid = $this->_adminInfo['wxuserid'];

      $total = 0;
      $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
      $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
      $offset = $limit * ($page - 1);
      $orderby = 'id desc';
      if (isset($this->_request['orderby'])){
        $orderby = $this->_request['orderby'];
      }
      $advorderSql = "SELECT thirdNo FROM advitem WHERE thirdNo IS NOT NULL AND thirdNo != '' AND SYS_DELETEFLAG != 0";
      $validThirdNos = Yii::$app->paymentdb->createCommand($advorderSql)->queryColumn();

      $where = ['and',
        new Expression("FIND_IN_SET('".$userid."', i.approvalUserid)"),
        ['=','i.status',1],
        ['=','i.agentId',$this->agentId]
      ];

      // 添加筛选条件
      if (!empty($this->_request['SYS_DOCUMENTID'])) {
        $sysDocIds = explode(',', $this->_request['SYS_DOCUMENTID']);
        $infoidExprs = [];
        foreach ($sysDocIds as $docId) {
          $infoidExprs[] = new Expression("i.data->'$.infoid' = '".trim($docId)."'");
        }
        $where[] = ['or', ...$infoidExprs];
      }
      if (!empty($this->_request['thirdNo'])) {
        $where[] = ['in', 'i.thirdNo', explode(',', $this->_request['thirdNo'])];
      }
      if (!empty($this->_request['userId'])) {
        $where[] = ['in', 'i.userId', explode(',', $this->_request['userId'])];
      }
      if (!empty($this->_request['approvalUserid'])) {
        $approvalUserids = explode(',', $this->_request['approvalUserid']);
        $approvalExprs = [];
        foreach ($approvalUserids as $auid) {
          $approvalExprs[] = new Expression("FIND_IN_SET('".trim($auid)."', i.approvalUserid)");
        }
        $where[] = ['or', ...$approvalExprs];
      }

      // 如果有有效的thirdNo，则添加过滤条件
      if (!empty($validThirdNos)) {
        $where[] = ['in', 'i.thirdNo', $validThirdNos];
      } else {
        // 没有有效的thirdNo，直接返回空结果
        $_result = array();
        $_result["current"] = $page;
        $_result["pageSize"] = $limit;
        $_result["total"] = 0;
        $_result['data'] = array();
        return $_result;
      }

      $model = WeixinOaApprovalInfo::find()
        ->alias('i')
        ->where($where);


      $total = $model->count();
      $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
      $_result = array();
      $_result["current"] = $page;
      $_result["pageSize"] = $limit;
      $_result["total"] = $total;
      $_result['data'] = $res;
       return $_result;
     }


    /**
     * 补全广告版面数：查询AI_AdvPages为空或0的福州日报/晚报广告，根据规格自动计算并更新
     * @return array
     */
    public function actionFixadvpages()
    {
        try {
            // 1. 查询符合条件的广告：刊物为福州日报或福州晚报，且AI_AdvPages为空或0
            $where = [
                'and',
                ['in', 'AI_Publication', ['福州日报', '福州晚报']],
                ['or',
                    ['AI_AdvPages' => null],
                    ['AI_AdvPages' => 0],
                    ['AI_AdvPages' => '']
                ],
                'AI_Size_ID > 0' // 确保有规格ID
            ];
            
            $advitems = Yii::$app->paymentdb->createCommand()
                ->select('*')
                ->from('advitem')
                ->where($where)
                ->queryAll();
            
            if (empty($advitems)) {
                return ['data' => '没有需要处理的广告', 'success' => true];
            }
            
            $updatedCount = 0;
            $errorCount = 0;
            $messages = [];
            
            foreach ($advitems as $item) {
                try {
                    $advitemId = $item['SYS_DOCUMENTID'];
                    $sizeId = $item['AI_Size_ID'];
                    
                    // 2. 根据AI_Size_ID从advsize表查询规格信息
                    $sizeInfo = Yii::$app->paymentdb->createCommand()
                        ->select('E_Width, E_Height, E_Name')
                        ->from('advsize')
                        ->where(['SYS_DOCUMENTID' => $sizeId])
                        ->queryOne();
                    
                    if (!$sizeInfo) {
                        $errorCount++;
                        $messages[] = "广告ID {$advitemId}: 规格ID {$sizeId} 不存在";
                        continue;
                    }
                    
                    $width = floatval($sizeInfo['E_Width']);
                    $height = floatval($sizeInfo['E_Height']);
                    
                    if ($width <= 0 || $height <= 0) {
                        $errorCount++;
                        $messages[] = "广告ID {$advitemId}: 规格宽高值无效 (宽:{$width}, 高:{$height})";
                        continue;
                    }
                    
                    // 3. 计算AI_AdvPages
                    $advPages = $this->calculateAdvPages($item);
                    
                    if ($advPages <= 0) {
                        $errorCount++;
                        $messages[] = "广告ID {$advitemId}: 版面数计算错误";
                        continue;
                    }
                    
                    // 4. 更新advitem表
                    Yii::$app->paymentdb->createCommand()
                        ->update('advitem', [
                            'AI_Width' => $width,
                            'AI_Height' => $height,
                            'AI_AdvPages' => $advPages
                        ], ['SYS_DOCUMENTID' => $advitemId])
                        ->execute();
                    
                    $updatedCount++;
                    $messages[] = "广告ID {$advitemId}: 更新成功，版面数={$advPages} (宽:{$width}cm, 高:{$height}cm)";
                    
                } catch (Exception $e) {
                    $errorCount++;
                    $messages[] = "广告ID {$item['SYS_DOCUMENTID']}: 处理失败 - {$e->getMessage()}";
                }
            }
            
            return [
                'success' => true,
                'total' => count($advitems),
                'updated' => $updatedCount,
                'failed' => $errorCount,
                'messages' => $messages
            ];
            
        } catch (Exception $e) {
            return ['errorMessage' => $e->getMessage()];
        }
    }

    /**
     * 根据广告类型计算版面数
     * @param array $item 广告数据
     * @return float 版面数
     */
    private function calculateAdvPages($item)
    {
        $width = floatval($item['AI_Width'] ?? 0);
        $height = floatval($item['AI_Height'] ?? 0);
        
        if ($width <= 0 || $height <= 0) {
            return 0;
        }
        
        $area = 0;
        if ($item['AI_Publication'] == '福州日报') {
            $area = 49.5 * 33;
        } elseif ($item['AI_Publication'] == '福州晚报') {
            $area = 34 * 24;
        }
        
        if ($area <= 0) {
            return 0;
        }
        
        return round(($height * $width) / $area, 4);
    }
  

}
