<?php

namespace app\modules\api\commons;

use Yii;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\models\WeixinOaUserTaguser;
use app\modules\api\models\WeixinYxkhTemplate;
use app\modules\api\models\WeixinOaTemplates;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinFlowSpecial;

/**
 * 月度考核流程解析类
 * 完全按照 Go 代码 flow.go / flow_node.go / weixin_templates.go 的逻辑实现
 */
class YxkhWorkflowParse
{
    protected $agentId;
    protected $userinfoModel;
    protected $tagUserModel;
    protected $roleModel;
    protected $departmentModel;

    public function __construct($agentId = 1000063)
    {
        $this->agentId = $agentId;
        $this->userinfoModel = 'app\modules\api\models\WeixinOAUserInfo';
        $this->tagUserModel = 'app\modules\api\models\WeixinOaUserTaguser';
        $this->roleModel = 'app\modules\api\models\WeixinOaRole';
        $this->departmentModel = 'app\modules\api\models\WeixinOaDepartment';
    }

    /**
     * 启动考核流程
     */
    public function startFlow($userid, $templateid, $condition, $businessData = [])
    {
        if (!$userid) {
            throw new \Exception('userid is null');
        }
        if (!$templateid) {
            throw new \Exception('templateid is null');
        }

        // 生成流水号
        $thirdNo = $this->genThirdNo($userid);

        // 获取用户信息
        $userinfo = $this->userinfoModel::find()->where(['=', 'userid', $userid])->asArray()->one();
        if (!$userinfo) {
            throw new \Exception('用户不存在');
        }

        // 获取流程模板数据
        $template = WeixinOaTemplates::find()->where(['=', 'templateId', $templateid])->asArray()->one();
        if (!$template) {
            throw new \Exception('流程模板不存在');
        }
        $templateData = $template['templateData'] ?? '';
        if (empty($templateData)) {
            throw new \Exception('模板数据为空，templateid: ' . $templateid);
        }

        // 清理模板数据（与 Go 代码一致）
        $templateData = str_replace(["\r", "\n"], '', $templateData);
        $templateData = str_replace('"id":"",', '"id":0,', $templateData);
        $templateData = str_replace('"level":"",', '"level":0,', $templateData);
        // 将字符串数字替换成纯数字（Go 的正则替换）
        $templateData = preg_replace('/"([0-9]+)"/', '$1', $templateData);

        // 解析模板数据
        $nodeData = json_decode($templateData, true);
        if (!$nodeData) {
            throw new \Exception('模板数据解析失败');
        }

        // 构建参数（与 Go 代码完全一致）
        $params = [
            'userid' => $userid,
            'zjKey' => $condition['zjKey'] ?? 0,
            'isleader' => $condition['isleader'] ?? 0,
            'departmentKey' => $condition['departmentKey'] ?? 0,
        ];
        if (isset($condition['khzKey'])) {
            $params['khzKey'] = $condition['khzKey'];
        }

        // 判断模板格式（与 Go 代码一致：用正则匹配 "nodeId":"开始"）
        if (preg_match('/"nodeId"\s*:\s*"开始"/', $templateData)) {
            // 旧格式：Node JSON 树结构
            $approvalNodes = $this->parseOldNode($nodeData, $params);
        } else {
            // 新格式：WxNode 扁平结构
            $approvalNodes = $this->parseWxNode($nodeData, $params);
        }

        // 获取第一节点审批人
        $firstNode = $approvalNodes[0] ?? ['Items' => ['Item' => []]];
        $firstItems = $firstNode['Items']['Item'] ?? [];
        $approvalUserids = array_column($firstItems, 'ItemUserId');
        $approvalUsernames = array_column($firstItems, 'ItemName');

        // 构建执行流数据
        $executionData = [
            'errcode' => 0,
            'errmsg' => 'ok',
            'data' => [
                'ThirdNo' => $thirdNo,
                'OpenTemplateId' => $templateid,
                'OpenSpName' => $template['templateName'] ?? '',
                'OpenSpstatus' => 2,
                'ApplyTime' => time(),
                'ApplyUserId' => $userid,
                'ApplyUsername' => $userinfo['name'] ?? '',
                'ApplyUserParty' => $userinfo['departmentname'] ?? '',
                'ApplyUserImage' => $userinfo['avatar'] ?? '',
                'ApprovalNodes' => $approvalNodes,
                'NotifyNodes' => null,
                'Approverstep' => 1,
            ],
        ];

        // 保存审批数据
        $approvalDataModel = new \app\modules\api\models\WeixinFlowApprovaldata();
        $approvalDataModel->agentid = $this->agentId;
        $approvalDataModel->thirdNo = $thirdNo;
        $approvalDataModel->data = json_encode($executionData);
        $approvalDataModel->step = 1;
        $approvalDataModel->status = 1;
        $approvalDataModel->notifyAttr = 1;
        $approvalDataModel->save();

        // 保存审批信息
        $approvalInfoModel = new \app\modules\api\models\WeixinOaApprovalInfo();
        $approvalInfoModel->agentId = $this->agentId;
        $approvalInfoModel->userId = $userid;
        $approvalInfoModel->userName = $userinfo['name'] ?? '';
        $approvalInfoModel->departmentid = $userinfo['departmentid'] ?? 0;
        $approvalInfoModel->department = $userinfo['departmentname'] ?? '';
        $approvalInfoModel->thirdNo = $thirdNo;
        $approvalInfoModel->type = 0;
        $approvalInfoModel->data = json_encode($businessData);
        $approvalInfoModel->approvalUserid = implode('|', $approvalUserids);
        $approvalInfoModel->approvalUsername = implode('|', $approvalUsernames);
        $approvalInfoModel->status = 1;
        $approvalInfoModel->save();

        return [
            'thirdNo' => $thirdNo,
            'approvalUserid' => $approvalUserids,
            'approvalUsername' => $approvalUsernames,
        ];
    }

    /**
     * 仅解析流程，不保存（用于流程预览）
     */
    public function parseFlow($userid, $templateid, $condition = [])
    {
        if (!$userid) {
            throw new \Exception('userid is null');
        }
        if (!$templateid) {
            throw new \Exception('templateid is null');
        }

        // 获取流程模板
        $template = WeixinOaTemplates::find()->where(['=', 'templateId', $templateid])->asArray()->one();
        if (!$template) {
            throw new \Exception('流程模板不存在');
        }

        $templateData = $template['templateData'] ?? '';
        if (empty($templateData)) {
            throw new \Exception('模板数据为空，templateid: ' . $templateid);
        }

        // 清理模板数据
        $templateData = str_replace(["\r", "\n"], '', $templateData);
        $templateData = str_replace('"id":"",', '"id":0,', $templateData);
        $templateData = str_replace('"level":"",', '"level":0,', $templateData);
        $templateData = preg_replace('/"([0-9]+)"/', '$1', $templateData);

        $nodeData = json_decode($templateData, true);
        if (!$nodeData) {
            throw new \Exception('模板数据解析失败');
        }

        $params = [
            'userid' => $userid,
            'zjKey' => $condition['zjKey'] ?? 0,
            'isleader' => $condition['isleader'] ?? 0,
            'departmentKey' => $condition['departmentKey'] ?? 0,
        ];
        if (isset($condition['khzKey'])) {
            $params['khzKey'] = $condition['khzKey'];
        }

        // 判断模板格式
        if (preg_match('/"nodeId"\s*:\s*"开始"/', $templateData)) {
            $approvalNodes = $this->parseOldNode($nodeData, $params);
        } else {
            $approvalNodes = $this->parseWxNode($nodeData, $params);
        }

        return [
            'errcode' => 0,
            'errmsg' => 'ok',
            'data' => [
                'ThirdNo' => '',
                'OpenTemplateId' => $templateid,
                'OpenSpName' => $template['templateName'] ?? '',
                'OpenSpstatus' => 2,
                'ApplyTime' => time(),
                'ApplyUserId' => $userid,
                'ApplyUsername' => '',
                'ApplyUserParty' => '',
                'ApplyUserImage' => '',
                'ApprovalNodes' => $approvalNodes,
                'NotifyNodes' => null,
                'Approverstep' => 1,
            ],
        ];
    }

    /**
     * 统一转换审批数据为前端格式
     * @param array $flowdata 原始流程数据（包含 errcode, errmsg, data 结构）
     * @return array ['approval' => [], 'applyUserImage' => '', 'currentStep' => 0]
     */
    public function transformApprovalData($flowdata)
    {
        $approval = [];
        $applyUserImage = $flowdata['data']['ApplyUserImage'] ?? '';
        $currentStep = intval($flowdata['data']['Approverstep'] ?? 0);

        if (!empty($flowdata['data']['ApprovalNodes'])) {
            foreach ($flowdata['data']['ApprovalNodes'] as $node) {
                $itemList = [];
                if (!empty($node['Items']['Item'])) {
                    $itemList = is_array($node['Items']['Item']) ? $node['Items']['Item'] : [$node['Items']['Item']];
                }
                if (count($itemList) > 1) {
                    $items = [];
                    foreach ($itemList as $item) {
                        $items[] = [
                            'title' => $item['ItemName'] ?? '',
                            'avatar' => $item['ItemImage'] ?? '',
                            'date' => !empty($item['ItemOpTime']) ? date('m\d', $item['ItemOpTime']) : '',
                            'speech' => $item['ItemSpeech'] ?? '',
                            'status' => $item['ItemStatus'] ?? '',
                        ];
                    }
                    $approval[] = [
                        'title' => $node['NodeRoleName'] ?? '审批人',
                        'items' => $items,
                        'status' => $node['NodeStatus'] ?? 0,
                        'Items' => $node['Items'] ?? ['Item' => []],
                    ];
                } else {
                    $firstItem = $itemList[0] ?? [];
                    $approval[] = [
                        'title' => $firstItem['ItemName'] ?? '审批人',
                        'avatar' => $firstItem['ItemImage'] ?? '',
                        'items' => '',
                        'date' => !empty($firstItem['ItemOpTime']) ? date('m\d', $firstItem['ItemOpTime']) : '',
                        'speech' => $firstItem['ItemSpeech'] ?? '',
                        'status' => $firstItem['ItemStatus'] ?? ($node['NodeStatus'] ?? 0),
                        'Items' => $node['Items'] ?? ['Item' => []],
                    ];
                }
            }
        }

        return [
            'approval' => $approval,
            'applyUserImage' => $applyUserImage,
            'currentStep' => $currentStep,
        ];
    }

    // ===================== 旧格式 Node 解析（与 Go 代码 flow_node.go 完全一致） =====================

    /**
     * 解析旧格式 Node JSON（树结构）
     * 完全对应 Go 的 Node.Parse() 算法
     */
    protected function parseOldNode($node, $params)
    {
        // 使用队列方式遍历（与 Go 代码一致）
        $tempNode = [$node];
        $approvalNodes = [];

        while (!empty($tempNode)) {
            $current = array_pop($tempNode);
            $cn = $current;

            if ($cn && isset($cn['type'])) {
                $an = $this->executeOldNode($cn, $params);
                if ($an !== null) {
                    if ($cn['type'] === 'approver') {
                        $approvalNodes[] = $an;
                    }
                }
                // 子节点
                if (!empty($cn['childNode'])) {
                    $tempNode[] = $cn['childNode'];
                }
                // 条件节点
                if (!empty($cn['conditionNodes'])) {
                    foreach ($cn['conditionNodes'] as $condNode) {
                        if ($this->checkOldConditions($condNode, $params)) {
                            if (!empty($condNode['childNode'])) {
                                $tempNode[] = $condNode['childNode'];
                            }
                            break;
                        }
                    }
                }
            }
        }

        return $approvalNodes;
    }

    /**
     * 执行旧格式节点（对应 Go 的 Node.execute()）
     */
    protected function executeOldNode($node, $params)
    {
        $type = $node['type'] ?? '';
        if ($type !== 'approver' && $type !== 'notifier') {
            return null;
        }

        $properties = $node['properties'] ?? [];
        $actionerRules = $properties['actionerRules'] ?? [];
        if (empty($actionerRules)) {
            throw new \Exception('nodeId为' . ($node['nodeId'] ?? '') . '的节点，properties.actionerRules不能为空');
        }

        $rule = $actionerRules[0];
        $result = [
            'NodeStatus' => 1,
            'NodeAttr' => $rule['attr'] ?? 1,
            'NodeType' => $rule['type'] ?? 0,
            'Items' => ['Item' => []],
        ];

        if (($rule['attr'] ?? 1) == 1) {
            $userid = $params['userid'];
            $zj = $params['zjKey'] ?? 0;
            $isleader = $params['isleader'] ?? 0;
            $departmentid = $params['departmentKey'] ?? 0;

            $items = [];
            switch ($rule['type'] ?? 0) {
                case 3: // 上级
                    $items = $this->resolveOldLeader($userid, $isleader, $zj, $departmentid, $rule['level'] ?? 1, $params);
                    break;
                case 2: // 标签
                    $labelName = $rule['labelName'] ?? '';
                    if ($labelName) {
                        $items = $this->resolveOldLabel($labelName, $userid);
                    }
                    break;
                case 1: // 单个成员
                    $uid = $rule['user_id'] ?? '';
                    if ($uid) {
                        $items = $this->resolveOldMember($uid);
                    }
                    break;
                case 9: // 考核员
                    $items = $this->resolveOldKaoheyuan($params);
                    break;
            }

            $result['Items']['Item'] = $items;
        }

        return $result;
    }

    /**
     * 检查旧格式条件（对应 Go 的 Node.check()）
     */
    protected function checkOldConditions($node, $params)
    {
        $properties = $node['properties'] ?? [];
        $conditions = $properties['conditions'] ?? [];
        // 空条件表示默认分支，始终匹配（与 Go 代码一致）
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (($condition['type'] ?? 0) == 0) {
                $paramKey = $condition['paramKey'] ?? '';
                $paramValues = $condition['paramValues'] ?? [];
                if (empty($paramKey) || empty($paramValues)) {
                    continue;
                }
                $paramValue = $params[$paramKey] ?? null;
                $matched = false;
                foreach ($paramValues as $v) {
                    if ((string)$v === (string)$paramValue) {
                        $matched = true;
                        break;
                    }
                }
                if (!$matched) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 旧格式：解析上级（对应 Go execute 中 type=3）
     */
    protected function resolveOldLeader($userid, $isleader, $zj, $departmentid, $level, $params)
    {
        $items = [];

        // 优先查询特别指定
        $special = WeixinFlowSpecial::find()
            ->where(['=', 'userid', $userid])
            ->andWhere(['=', 'flag', 0])
            ->asArray()
            ->one();
        if ($special && !empty($special['candidate'])) {
            $items[] = $this->makeApprover([
                'name' => $special['candidatename'] ?? '',
                'departmentname' => '',
                'avatar' => $special['avatar'] ?? '',
                'userid' => $special['candidate'],
            ]);
            return $items;
        }

        // 主管领导/分管领导
        $role = ($isleader == 1 || $zj == 1) ? 6 : 5; // 5=主管领导，6=分管领导
        $leaders = $this->findLeadershipByDepartmentAndRole($departmentid, $role, $level);
        foreach ($leaders as $u) {
            $items[] = $this->makeApprover([
                'name' => $u['name'] ?? '',
                'departmentname' => $u['departmentname'] ?? '',
                'avatar' => $u['avatar'] ?? '',
                'userid' => $u['userid'],
            ]);
        }

        return $items;
    }

    /**
     * 旧格式：解析标签（对应 Go execute 中 type=2）
     */
    protected function resolveOldLabel($labelName, $excludeUserid)
    {
        $items = [];
        $sql = "SELECT u.* FROM " . $this->tagUserModel::tableName() . " t
                LEFT JOIN " . $this->userinfoModel::tableName() . " u ON t.uId=u.id
                WHERE t.tagName=:tagName AND u.status=1 AND u.userid<>:excludeUserid";
        $users = \Yii::$app->db->createCommand($sql, [
            ':tagName' => $labelName,
            ':excludeUserid' => $excludeUserid,
        ])->queryAll();
        foreach ($users as $u) {
            if (!empty($u['userid'])) {
                $items[] = $this->makeApprover($u);
            }
        }
        return $items;
    }

    /**
     * 旧格式：解析单个成员（对应 Go execute 中 type=1）
     */
    protected function resolveOldMember($uid)
    {
        $items = [];
        if (is_numeric($uid)) {
            $user = $this->userinfoModel::find()->where(['=', 'id', $uid])->asArray()->one();
        } else {
            $user = $this->userinfoModel::find()->where(['=', 'userid', $uid])->asArray()->one();
        }
        if ($user) {
            $items[] = $this->makeApprover($user);
        }
        return $items;
    }

    /**
     * 旧格式：解析考核员（对应 Go execute 中 type=9）
     */
    protected function resolveOldKaoheyuan($params)
    {
        $items = [];
        $khzKey = $params['khzKey'] ?? '';
        if (empty($khzKey)) {
            return $items;
        }
        // 按部门和职级查询
        $departmentKey = $params['departmentKey'] ?? 0;
        $zjKey = $params['zjKey'] ?? 0;

        $roleWhere = ['and',
            ['=', 'role', 10], // 考核员 role=10
            new \yii\db\Expression("FIND_IN_SET(:agent, agent)", [':agent' => $this->agentId]),
        ];
        $assessors = WeixinOaFlowrole::find()
            ->where($roleWhere)
            ->asArray()
            ->all();

        foreach ($assessors as $a) {
            $depts = explode(',', $a['dept'] ?? '');
            $levels = isset($a['level']) && $a['level'] !== '' ? explode(',', $a['level']) : [];
            $matchDept = in_array($departmentKey, $depts);
            $matchLevel = empty($levels) || in_array($zjKey, $levels);
            if ($matchDept && $matchLevel) {
                $user = $this->userinfoModel::find()->where(['=', 'userid', $a['userid']])->asArray()->one();
                if ($user) {
                    $items[] = $this->makeApprover($user);
                }
            }
        }

        return $items;
    }

    // ===================== 新格式 WxNode 解析（与 Go 代码 weixin_templates.go 完全一致） =====================

    /**
     * 解析新格式 WxNode（扁平结构，approval 数组）
     * 完全对应 Go 的 WxNode.Parse()
     */
    protected function parseWxNode($nodeData, $params)
    {
        $approvalNodes = [];
        $approvals = $nodeData['approval'] ?? [];
        if (empty($approvals)) {
            return $approvalNodes;
        }

        foreach ($approvals as $approval) {
            $type = $approval['type'] ?? 0;
            $items = [];

            switch ($type) {
                case 0: // 角色
                    $items = $this->resolveWxRole($approval, $params);
                    break;
                case 1: // 单个成员
                    $items = $this->resolveWxMember($approval);
                    break;
                case 2: // 标签
                    $items = $this->resolveWxLabel($approval);
                    break;
                case 3: // 上级
                    $items = $this->resolveWxLeader($approval, $params);
                    break;
                case 5: // 用户角色
                    $items = $this->resolveWxRole($approval, $params);
                    break;
                case 9: // 考核员
                    $items = $this->resolveWxKaoheyuan($approval, $params);
                    break;
            }

            if (!empty($items)) {
                $approvalNodes[] = [
                    'NodeStatus' => 1,
                    'NodeAttr' => $approval['attr'] ?? 1,
                    'NodeType' => $type,
                    'Items' => ['Item' => $items],
                ];
            }
        }

        return $approvalNodes;
    }

    /**
     * 新格式：按角色解析审批人（type=0 或 type=5）
     */
    protected function resolveWxRole($approval, $params)
    {
        $items = [];
        $roleId = $approval['role'] ?? 0;
        if ($roleId <= 0) {
            return $items;
        }

        $departmentKey = $params['departmentKey'] ?? 0;

        // 角色10是考核员，走考核员逻辑
        if ($roleId == 10) {
            return $this->resolveWxKaoheyuan($approval, $params);
        }

        // 查询角色
        $roleWhere = ['and',
            ['=', 'role', $roleId],
            new \yii\db\Expression("FIND_IN_SET(:dept, dept)", [':dept' => $departmentKey]),
            new \yii\db\Expression("FIND_IN_SET(:agent, agent)", [':agent' => $this->agentId]),
        ];
        $flowroles = WeixinOaFlowrole::find()->where($roleWhere)->asArray()->all();

        foreach ($flowroles as $fr) {
            $user = $this->userinfoModel::find()->where(['=', 'userid', $fr['userid']])->asArray()->one();
            if ($user) {
                $items[] = $this->makeApprover($user);
            }
        }

        return $items;
    }

    /**
     * 新格式：单个成员（type=1）
     */
    protected function resolveWxMember($approval)
    {
        $items = [];
        $id = $approval['id'] ?? 0;
        if ($id <= 0) {
            return $items;
        }
        $user = $this->userinfoModel::find()->where(['=', 'id', $id])->asArray()->one();
        if ($user) {
            $items[] = $this->makeApprover($user);
        }
        return $items;
    }

    /**
     * 新格式：标签（type=2）
     */
    protected function resolveWxLabel($approval)
    {
        $items = [];
        $tagId = $approval['id'] ?? 0;
        if ($tagId <= 0) {
            return $items;
        }
        // 通过标签查用户（JOIN weixin_oauser_taguser ON tu.uId = u.id）
        $tagUserModel = 'app\modules\api\models\WeixinOaTaguser';
        $users = $this->userinfoModel::find()
            ->alias('u')
            ->leftJoin(["tu" => $tagUserModel::tableName()], "tu.uId=u.id")
            ->where(['=', 'tu.tagId', $tagId])
            ->andWhere(['=', 'u.status', 1])
            ->asArray()
            ->all();
        foreach ($users as $u) {
            if (!empty($u['userid'])) {
                $items[] = $this->makeApprover($u);
            }
        }
        return $items;
    }

    /**
     * 新格式：上级（type=3）
     */
    protected function resolveWxLeader($approval, $params)
    {
        $items = [];
        $userid = $params['userid'];
        $zj = $params['zjKey'] ?? 0;
        $isleader = $params['isleader'] ?? 0;
        $departmentid = $params['departmentKey'] ?? 0;
        $level = $approval['level'] ?? 1;

        // 特别指定
        $special = WeixinFlowSpecial::find()
            ->where(['=', 'userid', $userid])
            ->andWhere(['=', 'flag', 0])
            ->asArray()
            ->one();
        if ($special && !empty($special['candidate'])) {
            $items[] = $this->makeApprover([
                'name' => $special['candidatename'] ?? '',
                'departmentname' => '',
                'avatar' => $special['avatar'] ?? '',
                'userid' => $special['candidate'],
            ]);
            return $items;
        }

        // 如果是部门领导，跳过
        if ($isleader == 1 && $level == 1) {
            return $items;
        }

        // 找上级部门
        $deptId = $this->getDepartForLevel($departmentid, $level);
        $leaders = $this->userinfoModel::find()
            ->where(['=', 'departmentid', $deptId])
            ->andWhere(['=', 'is_leader', 1])
            ->andWhere(['=', 'status', 1])
            ->asArray()
            ->all();
        foreach ($leaders as $l) {
            $items[] = $this->makeApprover($l);
        }

        return $items;
    }

    /**
     * 新格式：考核员（type=9）
     */
    protected function resolveWxKaoheyuan($approval, $params)
    {
        $items = [];
        $departmentKey = $params['departmentKey'] ?? 0;
        $zjKey = $params['zjKey'] ?? 0;

        // 按部门+职级查
        $roleWhere = ['and',
            ['=', 'role', 10],
            new \yii\db\Expression("FIND_IN_SET(:dept, dept)", [':dept' => $departmentKey]),
            new \yii\db\Expression("FIND_IN_SET(:agent, agent)", [':agent' => $this->agentId]),
        ];

        $assessors = WeixinOaFlowrole::find()->where($roleWhere)->asArray()->all();
        foreach ($assessors as $a) {
            $levels = isset($a['level']) && $a['level'] !== '' ? explode(',', $a['level']) : [];
            if (empty($levels) || in_array($zjKey, $levels)) {
                $user = $this->userinfoModel::find()->where(['=', 'userid', $a['userid']])->asArray()->one();
                if ($user) {
                    $items[] = $this->makeApprover($user);
                }
            }
        }

        return $items;
    }

    // ===================== 通用辅助方法 =====================

    /**
     * 按部门和角色查领导（与 Go 的 FindLeadershipByDepartmentIDAndRole 一致）
     */
    protected function findLeadershipByDepartmentAndRole($departmentId, $role, $level)
    {
        $users = $this->userinfoModel::find()
            ->where(['=', 'departmentid', $departmentId])
            ->andWhere(['=', 'is_leader', 1])
            ->andWhere(['=', 'status', 1])
            ->asArray()
            ->all();
        return $users;
    }

    /**
     * 获取指定级别的部门ID（与 Go 的 GetDepartidForLevel 一致）
     */
    protected function getDepartForLevel($departmentId, $level)
    {
        $currentLevel = 0;
        $currentDeptId = $departmentId;
        while ($currentLevel < $level && $currentDeptId) {
            $dept = $this->departmentModel::findOne($currentDeptId);
            if (!$dept || empty($dept['parentid'])) {
                break;
            }
            $currentDeptId = $dept['parentid'];
            $currentLevel++;
        }
        return $currentDeptId ?: $departmentId;
    }

    /**
     * 构建审批人对象
     */
    protected function makeApprover($user)
    {
        return [
            'ItemName' => $user['name'] ?? '',
            'ItemParty' => $user['departmentname'] ?? '',
            'ItemImage' => $user['avatar'] ?? '',
            'ItemUserId' => $user['userid'] ?? '',
            'ItemStatus' => 1,
            'ItemSpeech' => '',
            'ItemOpTime' => 0,
        ];
    }

    /**
     * 生成流水号
     */
    protected function genThirdNo($userid)
    {
        list($msec, $sec) = explode(' ', microtime());
        return substr(sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000) . base64_encode($userid), 0, 20);
    }
}
