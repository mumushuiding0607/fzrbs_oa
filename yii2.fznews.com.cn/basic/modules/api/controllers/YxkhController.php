<?php

namespace app\modules\api\controllers;

use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\models\ResEvaluation;
use app\modules\api\models\ResProject;
use app\modules\api\models\ResMark;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\models\FznewsFlowProcess;
use app\modules\api\models\WeixinFlowApprovaldata;
use Exception;

/**
 * 月度考核接口
 */
class YxkhController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinOAUserInfo';
    protected $agentId = 1000063; // 一线考核应用ID
    protected $statusCn = ['', '审批中', '已同意', '已驳回', '已取消', '结束'];
    protected $userinfo = [];

    // 审批状态常量
    const STATUS_ING = 1;
    const STATUS_PASS = 2;
    const STATUS_REJECT = 3;
    const STATUS_CANCEL = 4;
    const STATUS_FINISH = 5;

    public function init()
    {
        parent::init();
        $this->userinfo = $this->getUserinfo($this->_adminInfo['wxuserid']);
    }

    /**
     * 获取用户信息
     */
    private function getUserinfo($userid)
    {
        if (empty($userid)) {
            return [];
        }
        return WeixinOAUserInfo::find()
            ->where(['=', 'userid', $userid])
            ->asArray()
            ->one();
    }

    /**
     * 获取yxkh流程模板ID
     * 根据用户职级和部门确定模板
     * @param int $level 职级（0=普通员工, 1=中层正职, 2=中层副职）
     * @param int $departmentid 部门ID
     * @param int $uid 用户ID
     * @param int $assesstype 考核类型（0=月度考核, 1=年度考核, 2=季度考核, 3=加减分）
     */
    private function getTemplateid($level, $departmentid, $uid, $assesstype = 0)
    {
        // 先根据用户ID查询
        $template = \app\modules\api\models\WeixinYxkhTemplate::find()
            ->where(['=', 'agentid', $this->agentId])
            ->andWhere(new \yii\db\Expression("FIND_IN_SET(:uid, uids)", [':uid' => $uid]))
            ->andWhere(new \yii\db\Expression("FIND_IN_SET(:type, assesstype)", [':type' => $assesstype]))
            ->orderBy('id desc')
            ->asArray()
            ->one();

        if ($template) {
            return $template['templateid'];
        }

        // 再根据部门和职级查询
        $template = \app\modules\api\models\WeixinYxkhTemplate::find()
            ->where(['=', 'agentid', $this->agentId])
            ->andWhere(new \yii\db\Expression("FIND_IN_SET(:did, dids)", [':did' => $departmentid]))
            ->andWhere(new \yii\db\Expression("FIND_IN_SET(:type, type)", [':type' => $level]))
            ->andWhere(new \yii\db\Expression("FIND_IN_SET(:assesstype, assesstype)", [':assesstype' => $assesstype]))
            ->orderBy('id desc')
            ->asArray()
            ->one();

        if ($template) {
            return $template['templateid'];
        }

        // 最后根据部门查询（不限制职级，作为最终兜底）
        $template = \app\modules\api\models\WeixinYxkhTemplate::find()
            ->where(['=', 'agentid', $this->agentId])
            ->andWhere(new \yii\db\Expression("FIND_IN_SET(:did, dids)", [':did' => $departmentid]))
            ->andWhere(new \yii\db\Expression("FIND_IN_SET(:type, assesstype)", [':type' => $assesstype]))
            ->orderBy('id desc')
            ->asArray()
            ->one();

        if ($template) {
            return $template['templateid'];
        }

        return null;
    }

    /**
     * 生成第三方编号
     */
    private function genThirdNo()
    {
        list($msec, $sec) = explode(' ', microtime());
        return substr(sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000) . strtolower($this->_adminInfo['wxuserid']), 0, 20);
    }

    // ==================== 用户信息 ====================

    /**
     * 获取当前用户信息
     */
    public function actionGetuserinfo()
    {
        $userid = $this->_adminInfo['wxuserid'] ?? '';
        $userinfo = $this->getUserinfo($userid);
        return ['data' => $userinfo, 'debug_userid' => $userid];
    }

    /**
     * 获取单个考核记录（用于编辑）
     * GET/POST ?eid=
     */
    public function actionGetext()
    {
        $eid = isset($this->_request['eid']) ? intval($this->_request['eid']) : 0;
        if (!$eid) {
            return ['errorMessage' => 'eid 不能为空'];
        }

        $evaluation = ResEvaluation::findOne($eid);
        if (!$evaluation) {
            return ['errorMessage' => '考核记录不存在'];
        }

        return ['data' => $evaluation->toArray()];
    }

    // ==================== 考核记录 ====================

    /**
     * 考核列表（只显示当前用户相关的）
     * GET/POST ?businessType=&state=&keyword=&current=&pageSize=
     */
    public function actionGetlist()
    {
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 10;
        $offset = $limit * ($page - 1);
        $userid = $this->_adminInfo['wxuserid'];

        // 构建 fznews_flow_process 查询条件（只查当前用户的）
        $flowWhere = ['and', ['=', 'f.userId', $userid]];

        // 按业务类型筛选
        if (!empty($this->_request['businessType'])) {
            $flowWhere[] = ['=', 'f.businessType', $this->_request['businessType']];
        } else {
            // 默认查询月度考核
            $flowWhere[] = ['=', 'f.businessType', '月度考核'];
        }

        // 按流程状态筛选
        if (isset($this->_request['state']) && $this->_request['state'] != -1 && $this->_request['state'] != 0) {
            $state = intval($this->_request['state']);
            if ($state == self::STATUS_FINISH) {
                $flowWhere[] = ['=', 'f.completed', 1];
            } else {
                $flowWhere[] = ['=', 'f.completed', 0];
            }
        }

        // 统计总数
        $total = FznewsFlowProcess::find()->alias('f')->where($flowWhere)->count();

        // 查询流程列表
        $flowList = FznewsFlowProcess::find()
            ->alias('f')
            ->select('f.*')
            ->where($flowWhere)
            ->orderBy('f.requestedDate desc')
            ->limit($limit)
            ->offset($offset)
            ->asArray()
            ->all();

        // 获取流程关联的考核记录
        $processInstanceIds = array_column($flowList, 'processInstanceId');
        $evaluations = [];
        if ($processInstanceIds) {
            $evalList = ResEvaluation::find()
                ->where(['in', 'processInstanceId', $processInstanceIds])
                ->asArray()
                ->all();
            foreach ($evalList as $e) {
                $evaluations[$e['processInstanceId']] = $e;
            }
        }

        // 合并数据
        $list = [];
        foreach ($flowList as $flow) {
            $item = array_merge($flow, $evaluations[$flow['processInstanceId']] ?? []);
            $list[] = $item;
        }

        $this->_result['current'] = $page;
        $this->_result['pageSize'] = $limit;
        $this->_result['total'] = $total;
        $this->_result['data'] = $list;
        return $this->_result;
    }

    /**
     * 保存考核记录（不含流程）
     * POST {startDate, endDate, sparation, selfEvaluation, attendance, shortComesAndPlan, totalMark, ...}
     */
    public function actionSave()
    {
        $post = $this->_request;
        $userid = $this->_adminInfo['wxuserid'];

        $eid = isset($post['eid']) ? intval($post['eid']) : 0;

        $transaction = Yii::$app->getDb()->beginTransaction();
        try {
            if ($eid > 0) {
                $model = ResEvaluation::findOne($eid);
                if (!$model) {
                    return ['errorMessage' => '考核记录不存在'];
                }
            } else {
                $model = new ResEvaluation();
                $model->uid = $userid;
                $model->username = $this->userinfo['name'] ?? '';
                $model->department = $this->userinfo['departmentname'] ?? '';
                $model->departmentid = $this->userinfo['departmentid'] ?? 0;
                $model->position = $this->userinfo['position'] ?? '';
                $model->createTime = date('Y-m-d H:i:s');
            }

            $model->startDate = $post['startDate'] ?? '';
            $model->endDate = $post['endDate'] ?? '';
            $model->sparation = $post['sparation'] ?? '';
            $model->selfEvaluation = $post['selfEvaluation'] ?? '';
            $model->attendance = $post['attendance'] ?? '';
            $model->shortComesAndPlan = $post['shortComesAndPlan'] ?? '';
            $model->totalMark = $post['totalMark'] ?? '';
            $model->marks = $post['marks'] ?? '';
            $model->evaluationType = $post['evaluationType'] ?? '月度考核';

            if (!$model->save()) {
                throw new Exception('保存失败：' . json_encode($model->getErrors()));
            }

            $transaction->commit();
            return ['data' => ['eid' => $model->eId, 'processInstanceId' => $model->processInstanceId]];
        } catch (\Throwable $th) {
            $transaction->rollBack();
            return ['errorMessage' => $th->getMessage()];
        }
    }

    /**
     * 启动考核审批流
     * POST {eid?, templateId, title, startDate, endDate, sparation, selfEvaluation, attendance, shortComesAndPlan}
     */
    public function actionStartflow()
    {
        $post = $this->_request;
        $userid = $this->_adminInfo['wxuserid'];

        if (empty($post['templateId'])) {
            return ['errorMessage' => 'templateId 不能为空'];
        }

        $transaction = Yii::$app->getDb()->beginTransaction();
        try {
            $thirdNo = $this->genThirdNo();

            // 如果没有eid，先创建考核记录
            $eid = isset($post['eid']) ? intval($post['eid']) : 0;
            if ($eid > 0) {
                $evaluation = ResEvaluation::findOne($eid);
                if (!$evaluation) {
                    return ['errorMessage' => '考核记录不存在'];
                }
                // 检查是否已发起流程
                if ($evaluation->processInstanceId) {
                    $flow = FznewsFlowProcess::find()
                        ->where(['processInstanceId' => $evaluation->processInstanceId])
                        ->asArray()
                        ->one();
                    if ($flow && $flow['completed'] == 0) {
                        return ['errorMessage' => '该考核已发起审批流程'];
                    }
                }
            } else {
                // 检查是否已有相同考核周期和用户的记录
                $sparation = $post['sparation'] ?? '';
                $uid = $this->userinfo['id'] ?? 0;
                $existing = ResEvaluation::find()
                    ->where(['uid' => $uid])
                    ->andWhere(['sparation' => $sparation])
                    ->one();
                if ($existing) {
                    return ['errorMessage' => '该考核周期已提交，请勿重复提交'];
                }

                // 新建考核记录
                $evaluation = new ResEvaluation();
                $evaluation->uid = $this->userinfo['id'] ?? 0;
                $evaluation->username = $this->userinfo['name'] ?? '';
                $evaluation->department = $this->userinfo['departmentname'] ?? '';
                $evaluation->departmentid = $this->userinfo['departmentid'] ?? 0;
                $evaluation->position = $this->userinfo['position'] ?? '';
                $evaluation->createTime = date('Y-m-d H:i:s');
                $evaluation->startDate = $post['startDate'] ?? '';
                $evaluation->endDate = $post['endDate'] ?? '';
                $evaluation->sparation = $post['sparation'] ?? '';
                $evaluation->selfEvaluation = $post['selfEvaluation'] ?? '';
                $evaluation->attendance = $post['attendance'] ?: '旷工(0)天，请假(0)天';
                $evaluation->shortComesAndPlan = $post['shortComesAndPlan'] ?? '';
                $evaluation->totalMark = $post['totalMark'] ?? '';
                $evaluation->marks = $post['marks'] ?? '';
                $evaluation->evaluationType = $post['evaluationType'] ?? '月度考核';
            }

            // 调用 YxkhWorkflowParse 启动真正的审批流程（Node JSON 格式）
            $templateId = $this->getTemplateid($this->userinfo['level'] ?? 0, $this->userinfo['departmentid'] ?? 0, $this->userinfo['id'] ?? 0, 0);
            if (!$templateId) {
                return ['errorMessage' => '未找到流程模板，请联系管理员配置'];
            }
            $wfp = new \app\modules\api\commons\YxkhWorkflowParse($this->agentId);
            $flowResult = $wfp->startFlow($userid, $templateId, [
                'khzKey' => $post['khzKey'] ?? '',
                'zjKey' => $this->userinfo['level'] ?? 0,
                'isleader' => $this->userinfo['is_leader'] ?? 0,
                'departmentKey' => $this->userinfo['departmentid'] ?? 0,
            ], [
                'eid' => $evaluation->eId ?? 0,
                'title' => $post['title'] ?? ($evaluation->username . '-' . $evaluation->sparation),
            ]);
            $candidate = is_array($flowResult['approvalUserid']) ? implode('|', $flowResult['approvalUserid']) : ($flowResult['approvalUserid'] ?? '');
            $candidatename = is_array($flowResult['approvalUsername']) ? implode('|', $flowResult['approvalUsername']) : ($flowResult['approvalUsername'] ?? '');

            // 创建流程记录（fznews_flow_process）
            $flow = new FznewsFlowProcess();
            $flow->processInstanceId = $flowResult['thirdNo'];
            $flow->uid = $this->userinfo['id'] ?? 0;
            $flow->userId = $userid;
            $flow->requestedDate = date('Y-m-d H:i:s');
            $flow->title = $post['title'] ?? ($evaluation->username . '-' . $evaluation->sparation);
            $flow->businessType = $evaluation->evaluationType ?? '月度考核';
            $flow->completed = 0; // 审批中
            $flow->deptName = $this->userinfo['departmentname'] ?? '';
            $flow->username = $this->userinfo['name'] ?? '';
            $flow->step = 1;
            $flow->candidate = $candidate;
            $flow->candidatename = $candidatename;
            $flow->save();

            // 更新 evaluation 的 processInstanceId
            $evaluation->processInstanceId = $flowResult['thirdNo'];
            if (!$evaluation->save()) {
                throw new Exception('保存考核记录失败：' . json_encode($evaluation->getErrors()));
            }

            $transaction->commit();
            $this->_result['data'] = [
                'thirdNo' => $flowResult['thirdNo'],
                'processInstanceId' => $flowResult['thirdNo'],
                'eid' => $evaluation->eId,
            ];
            return $this->_result;
        } catch (\Throwable $th) {
            $transaction->rollBack();
            return ['errorMessage' => '启动流程失败：' . $th->getMessage()];
        }
    }

    /**
     * 获取流程预览
     * POST {} - 根据当前用户信息生成流程预览
     */
    public function actionGetflow()
    {
        $post = $this->_request;
        $userid = $this->_adminInfo['wxuserid'] ?? '';
        $uid = $this->userinfo['id'] ?? 0;
        $level = $this->userinfo['level'] ?? 0;
        $departmentid = $this->userinfo['departmentid'] ?? 0;

        // 解析 khzKey：从数据库查询用户的考核组标签（匹配 Go FindKHZByUid 逻辑）
        $khzKey = $this->resolveKhzKey($uid);

        // 获取流程模板ID
        $templateid = $this->getTemplateid($level, $departmentid, $uid, 0);
        if (!$templateid) {
            return ['errorMessage' => '未找到流程模板，请联系管理员配置'];
        }

        // 使用 YxkhWorkflowParse 获取审批流程（Node JSON 格式）
        $wfp = new \app\modules\api\commons\YxkhWorkflowParse($this->agentId);
        $flowdata = $wfp->parseFlow($userid, $templateid, [
            'khzKey' => $khzKey,
            'zjKey' => $level,
            'isleader' => $this->userinfo['is_leader'] ?? 0,
            'departmentKey' => $departmentid,
        ]);

        // 使用统一方法转换审批数据
        $transformed = $wfp->transformApprovalData($flowdata);
        $approval = $transformed['approval'];

        // 如果没有审批节点，显示发起人
        if (empty($approval)) {
            $approval[] = [
                'title' => '发起人',
                'avatar' => $this->userinfo['avatar'] ?? '',
                'items' => '',
                'status' => 0,
                'Items' => [
                    'Item' => [
                        [
                            'ItemName' => $this->userinfo['name'] ?? '',
                            'ItemUserId' => $this->_adminInfo['wxuserid'] ?? '',
                            'ItemImage' => $this->userinfo['avatar'] ?? '',
                        ]
                    ]
                ],
            ];
        }

        return [
            'viewdata' => [
                'step' => -1,
                'thirdNo' => '',
                'approval' => $approval,
                'notify' => [],
            ],
            'statusCn' => $this->statusCn,
            'templateid' => $templateid,
            'debug' => [
                'khzKey' => $khzKey,
                'zjKey' => $level,
                'departmentKey' => $departmentid,
                'uid' => $uid,
            ],
        ];
    }

    /**
     * 审批操作（同意/驳回/撤销）
     * POST {thirdNo, act: agree/reject/cancel, speech}
     */
    public function actionFlowact()
    {
        $post = $this->_request;
        if (empty($post['thirdNo'])) {
            return ['errorMessage' => 'thirdNo 不能为空'];
        }
        if (empty($post['act'])) {
            return ['errorMessage' => 'act 不能为空'];
        }

        switch ($post['act']) {
            case 'agree':
                return $this->doAgree($post);
            case 'reject':
                return $this->doReject($post);
            case 'cancel':
                return $this->doCancel($post);
            default:
                return ['errorMessage' => '不支持的操作'];
        }
    }

    private function doAgree($post)
    {
        $userid = $this->_adminInfo['wxuserid'];
        $thirdNo = $post['thirdNo'];
        $speech = $post['speech'] ?? '';

        $flowInfo = FznewsFlowProcess::find()
            ->where(['processInstanceId' => $thirdNo])
            ->asArray()
            ->one();
        if (!$flowInfo) {
            return ['errorMessage' => '流程不存在'];
        }
        // 权限检查 - 检查是否是当前审批人
        if ($flowInfo['candidate'] && !in_array($userid, explode(',', $flowInfo['candidate']))) {
            return ['errorMessage' => '当前审批人是：' . $flowInfo['candidatename']];
        }

        try {
            // 更新流程状态为已通过(completed=1表示结束)
            // 由于是简化的直接通过模式，这里直接标记为完成
            FznewsFlowProcess::updateAll(['completed' => 1], ['processInstanceId' => $thirdNo]);

            $this->_result['data'] = ['success' => true, 'thirdNo' => $thirdNo];
            return $this->_result;
        } catch (\Throwable $th) {
            return ['errorMessage' => $th->getMessage()];
        }
    }

    private function doReject($post)
    {
        $userid = $this->_adminInfo['wxuserid'];
        $thirdNo = $post['thirdNo'];
        $speech = $post['speech'] ?? '';

        if (empty($speech)) {
            return ['errorMessage' => '驳回意见不能为空'];
        }

        $flowInfo = FznewsFlowProcess::find()
            ->where(['processInstanceId' => $thirdNo])
            ->asArray()
            ->one();
        if (!$flowInfo) {
            return ['errorMessage' => '流程不存在'];
        }

        try {
            // 驳回 - 标记流程结束(completed=1表示结束，但状态不同)
            // 实际应用中可能需要额外字段区分同意/驳回，此处简化处理
            FznewsFlowProcess::updateAll(['completed' => 1], ['processInstanceId' => $thirdNo]);

            $this->_result['data'] = ['success' => true, 'thirdNo' => $thirdNo];
            return $this->_result;
        } catch (\Throwable $th) {
            return ['errorMessage' => $th->getMessage()];
        }
    }

    private function doCancel($post)
    {
        $userid = $this->_adminInfo['wxuserid'];
        $thirdNo = $post['thirdNo'];
        $speech = $post['speech'] ?? '';

        $flowInfo = FznewsFlowProcess::find()
            ->where(['processInstanceId' => $thirdNo])
            ->asArray()
            ->one();
        if (!$flowInfo) {
            return ['errorMessage' => '流程不存在'];
        }
        // 只能本人撤销
        if ($flowInfo['userId'] != $userid) {
            return ['errorMessage' => '只能本人撤销'];
        }

        try {
            // 撤销 - 标记流程结束
            FznewsFlowProcess::updateAll(['completed' => 1], ['processInstanceId' => $thirdNo]);

            $this->_result['data'] = ['success' => true, 'thirdNo' => $thirdNo];
            return $this->_result;
        } catch (\Throwable $th) {
            return ['errorMessage' => $th->getMessage()];
        }
    }

    /**
     * 删除流程
     * POST {processInstanceId}
     */
    public function actionDelflow()
    {
        $post = $this->_request;
        if (empty($post['processInstanceId'])) {
            return ['errorMessage' => 'processInstanceId 不能为空'];
        }

        $thirdNo = $post['processInstanceId'];
        $userid = $this->_adminInfo['wxuserid'];

        $flowInfo = FznewsFlowProcess::find()
            ->where(['processInstanceId' => $thirdNo])
            ->asArray()
            ->one();

        if ($flowInfo && $flowInfo['userId'] != $userid) {
            return ['errorMessage' => '只能删除自己发起的流程'];
        }

        $transaction = Yii::$app->getDb()->beginTransaction();
        try {
            // 删除流程记录
            FznewsFlowProcess::deleteAll(['processInstanceId' => $thirdNo]);

            // 更新 evaluation 清除 processInstanceId
            ResEvaluation::updateAll(['processInstanceId' => ''], ['processInstanceId' => $thirdNo]);

            $transaction->commit();
            return ['data' => '删除成功'];
        } catch (\Throwable $th) {
            $transaction->rollBack();
            return ['errorMessage' => $th->getMessage()];
        }
    }

    /**
     * 流程详情
     * GET/POST ?processInstanceId=
     */
    public function actionViewflow()
    {
        if (empty($this->_request['processInstanceId'])) {
            return ['errorMessage' => 'processInstanceId 不能为空'];
        }

        $thirdNo = $this->_request['processInstanceId'];
        $userid = $this->_adminInfo['wxuserid'];

        // 流程信息
        $flowInfo = FznewsFlowProcess::find()
            ->where(['processInstanceId' => $thirdNo])
            ->asArray()
            ->one();

        if (!$flowInfo) {
            return ['errorMessage' => '流程不存在'];
        }

        // 考核记录
        $evaluation = ResEvaluation::find()
            ->where(['processInstanceId' => $thirdNo])
            ->asArray()
            ->one();

        // 项目列表（含加减分）
        $projects = ResProject::find()
            ->where(['userId' => $evaluation['uid'] ?? 0])
            ->andWhere(['>=', 'startDate', $evaluation['startDate'] ?? ''])
            ->andWhere(['<=', 'endDate', $evaluation['endDate'] ?? ''])
            ->asArray()
            ->all();

        $projectIds = array_column($projects, 'projectId');
        $marks = [];
        if ($projectIds) {
            $marks = ResMark::find()
                ->where(['in', 'projectId', $projectIds])
                ->asArray()
                ->all();
        }

        // 判断操作权限
        $isApplier = ($flowInfo['userId'] == $userid);
        $isApprover = $flowInfo['candidate'] && in_array($userid, explode(',', $flowInfo['candidate']));
        $isIng = ($flowInfo['completed'] == 0);

        // 从 weixin_flow_approvaldata 获取实际审批数据，使用统一转换方法
        $approvalData = WeixinFlowApprovaldata::find()
            ->where(['and', ['=', 'agentid', $this->agentId], ['=', 'thirdNo', $thirdNo]])
            ->one();
        $approval = [];
        $currentStep = 0;
        $applyUserImage = '';
        if ($approvalData && !empty($approvalData['data'])) {
            $flowdata = json_decode($approvalData['data'], true);
            // 使用统一方法转换审批数据
            $wfp = new \app\modules\api\commons\YxkhWorkflowParse($this->agentId);
            $transformed = $wfp->transformApprovalData($flowdata);
            $approval = $transformed['approval'];
            $currentStep = $transformed['currentStep'];
            $applyUserImage = $transformed['applyUserImage'];
        }

        return [
            'data' => [
                'flowInfo' => $flowInfo,
                'evaluation' => $evaluation,
                'projects' => $projects,
                'marks' => $marks,
                'flowData' => [
                    'step' => $currentStep,
                    'thirdNo' => $thirdNo,
                    'approval' => $approval,
                    'notify' => [],
                    'applyUserImage' => $applyUserImage ?? '',
                ],
            ],
            'isIng' => $isIng,
            'isApplier' => $isApplier,
            'isApprover' => $isApprover,
            'statusCn' => $this->statusCn,
        ];
    }

    // ==================== 项目管理 ====================

    /**
     * 保存项目
     * POST {projectId?, projectContent, progress, startDate, endDate, userId?}
     */
    public function actionSavesproject()
    {
        $post = $this->_request;
        $userid = $this->_adminInfo['wxuserid'];

        $projectId = isset($post['projectId']) ? intval($post['projectId']) : 0;

        try {
            if ($projectId > 0) {
                $model = ResProject::findOne($projectId);
                if (!$model) {
                    return ['errorMessage' => '项目不存在'];
                }
            } else {
                $model = new ResProject();
                $model->userId = !empty($post['userId']) ? intval($post['userId']) : ($this->userinfo['id'] ?? 0);
                $model->creator = $this->userinfo['name'] ?? '';
                $model->createTime = date('Y-m-d H:i:s');
            }

            $model->projectContent = $post['projectContent'] ?? '';
            $model->progress = $post['progress'] ?? '';
            $model->startDate = $post['startDate'] ?? '';
            $model->endDate = $post['endDate'] ?? '';

            if (!$model->save()) {
                throw new Exception('保存项目失败：' . json_encode($model->getErrors()));
            }

            return ['data' => ['projectId' => $model->projectId]];
        } catch (\Throwable $th) {
            return ['errorMessage' => $th->getMessage()];
        }
    }

    /**
     * 删除项目
     * POST {ids: [1,2,3]}
     */
    public function actionDelproject()
    {
        $post = $this->_request;
        if (empty($post['ids']) || !is_array($post['ids'])) {
            return ['errorMessage' => 'ids 不能为空'];
        }

        try {
            ResProject::deleteAll(['in', 'projectId', $post['ids']]);
            return ['data' => '删除成功'];
        } catch (\Throwable $th) {
            return ['errorMessage' => $th->getMessage()];
        }
    }

    /**
     * 获取项目列表（含加减分）
     * GET ?userId=&startDate=&endDate=
     */
    public function actionGetprojects()
    {
        // wxuserid 是字符串，需要转换为数字 id
        $wxuserid = $this->_request['wxuserid'] ?? $this->_adminInfo['wxuserid'];
        $userInfo = WeixinOAUserInfo::find()->where(['=', 'userid', $wxuserid])->asArray()->one();
        $userId = $userInfo['id'] ?? 0;
        $startDate = $this->_request['startDate'] ?? '';
        $endDate = $this->_request['endDate'] ?? '';

        $where = ['and', ['=', 'userId', $userId]];
        if ($startDate) {
            $where[] = ['>=', 'startDate', $startDate];
        }
        if ($endDate) {
            $where[] = ['<=', 'endDate', $endDate];
        }

        $projects = ResProject::find()->where($where)->asArray()->all();
        $projectIds = array_column($projects, 'projectId');

        $marks = [];
        if ($projectIds) {
            $marks = ResMark::find()
                ->where(['in', 'projectId', $projectIds])
                ->asArray()
                ->all();
        }

        // 按 projectId 分组
        $marksByProject = [];
        foreach ($marks as $mark) {
            $marksByProject[$mark['projectId']][] = $mark;
        }

        $result = [];
        foreach ($projects as &$pro) {
            $pro['marks'] = $marksByProject[$pro['projectId']] ?? [];
            $result[] = $pro;
        }

        return ['data' => $result];
    }

    /**
     * 解析用户的考核组标签（匹配 Go FindKHZByUid 逻辑）
     * 从 weixin_oauser_taguser 和 weixin_oauser_tag 查询用户所属的考核组
     * @param int $uid 用户ID
     * @return string 考核组标签名（如"第一考核组成员"）
     */
    private function resolveKhzKey($uid)
    {
        if (empty($uid)) {
            return '';
        }

        // 查询用户的考核组标签
        // SQL: SELECT * FROM weixin_oauser_tag WHERE id IN (
        //       SELECT tagId FROM weixin_oauser_taguser WHERE uId={uid}
        //       AND tagId IN (SELECT id FROM weixin_oauser_tag WHERE type='考核组')
        //     )
        $tag = \app\modules\api\models\WeixinOaUsertag::find()
            ->innerJoin('weixin_oauser_taguser tu', 'tu.tagId = weixin_oauser_tag.id')
            ->where(['tu.uId' => $uid])
            ->andWhere(['weixin_oauser_tag.type' => '考核组'])
            ->one();

        return $tag ? ($tag->tagName ?? '') : '';
    }

    // ==================== 加减分管理 ====================

    /**
     * 保存加减分
     * POST {markId?, projectId, markNumber, markReason, accordingly, startDate, endDate}
     */
    public function actionSavemark()
    {
        $post = $this->_request;
        $userid = $this->_adminInfo['wxuserid'];

        if (empty($post['projectId'])) {
            return ['errorMessage' => 'projectId 不能为空'];
        }

        $markId = isset($post['markId']) ? intval($post['markId']) : 0;

        try {
            if ($markId > 0) {
                $model = ResMark::findOne($markId);
                if (!$model) {
                    return ['errorMessage' => '加减分记录不存在'];
                }
            } else {
                $model = new ResMark();
                $model->userId = $userid;
                $model->username = $this->userinfo['name'] ?? '';
                $model->department = $this->userinfo['departmentname'] ?? '';
                $model->departmentname = $this->userinfo['departmentname'] ?? '';
                $model->createTime = date('Y-m-d H:i:s');
                $model->checked = '0'; // 待生效
            }

            $model->projectId = intval($post['projectId']);
            $model->markNumber = $post['markNumber'] ?? '0';
            $model->markReason = $post['markReason'] ?? '';
            $model->accordingly = $post['accordingly'] ?? '';
            $model->startDate = $post['startDate'] ?? '';
            $model->endDate = $post['endDate'] ?? '';

            if (!$model->save()) {
                throw new Exception('保存加减分失败：' . json_encode($model->getErrors()));
            }

            return ['data' => ['markId' => $model->markId]];
        } catch (\Throwable $th) {
            return ['errorMessage' => $th->getMessage()];
        }
    }

    /**
     * 删除加减分
     * POST {ids: [1,2,3]}
     */
    public function actionDelmark()
    {
        $post = $this->_request;
        if (empty($post['ids']) || !is_array($post['ids'])) {
            return ['errorMessage' => 'ids 不能为空'];
        }

        try {
            ResMark::deleteAll(['in', 'markId', $post['ids']]);
            return ['data' => '删除成功'];
        } catch (\Throwable $th) {
            return ['errorMessage' => $th->getMessage()];
        }
    }
}
