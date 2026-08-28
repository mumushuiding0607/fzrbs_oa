<?php

namespace app\modules\api\controllers;

use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\models\FzrbsBudgetDict;
use app\modules\api\models\FzrbsEvaluationTask;
use app\modules\api\models\FzrbsEvaluationScore;
use app\modules\api\models\FzrbsEvaluationPenalty;
use app\modules\api\models\FzrbsEvaluationWarning;
use app\modules\api\models\FzrbsEvaluationPersonalScore;
use app\modules\api\models\FzrbsEvaluationPenaltyInput;
use app\modules\api\models\FzrbsEvaluationPenaltyPerson;
use app\modules\api\models\FzrbsEvaluationPenaltyResult;
use app\modules\api\models\WeixinOaDepartment;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\models\WeixinOaRole;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\FzrbsOperationLog;
use yii\db\Expression;

/**
 * 考评系统接口
 */
class EvaluationController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinOAUserInfo';
    protected $agentId = 1000091;

    /**
     * 校验考评管理员权限
     * 只有"考评管理员"角色的用户才能操作
     */
    protected function _checkEvaluationAdmin()
    {
        $userid = $this->_adminInfo['wxuserid'] ?? null;
        if (!$userid) {
            return ['message' => '无法获取用户信息'];
        }

        // 管理员直接通过
        if ($this->_adminInfo['usertype'] != 0) {
            return null;
        }

        $role = WeixinOaRole::find()->where(['rolename' => '考评管理员'])->select('id')->scalar();
        if (!$role) {
            return ['message' => '系统中不存在"考评管理员"角色，请联系管理员'];
        }

        $hasRole = WeixinOaFlowrole::find()
            ->where(['role' => $role, 'userid' => $userid])
            ->exists();

        if (!$hasRole) {
            return ['message' => '无权限操作，需要"考评管理员"角色'];
        }

        return null; // 校验通过
    }

    /**
     * 获取评分参数配置（从dict表）
     */
    public function actionGetconfig()
    {
        $type = Yii::$app->request->get('type');

        $query = FzrbsBudgetDict::find();

        if ($type) {
            $query->andWhere(['type' => $type]);
        }

        $configs = $query->all();

        $result = [];
        foreach ($configs as $config) {
            if (!isset($result[$config->type])) {
                $result[$config->type] = [];
            }
            $result[$config->type][] = [
                'label' => $config->label,
                'value' => $config->value,
            ];
        }

        return ['message' => '', 'data' => $result];
    }

    /**
     * 获取被考评部门列表
     */
    public function actionGetdepartments()
    {
        // 从dict表获取被考评部门IDs（dept 字段存储的是部门 IDs）
        $deptConfig = FzrbsBudgetDict::findOne(['type' => '被考评部门', 'label' => '被考评部门']);

        if (!$deptConfig || empty($deptConfig->dept)) {
            return ['message' => '未配置被考评部门'];
        }

        $deptIds = explode(',', $deptConfig->dept);

        // 获取部门信息
        $departments = WeixinOaDepartment::find()
            ->where(['id' => $deptIds])
            ->orderBy('id')
            ->all();

        $result = [];
        foreach ($departments as $dept) {
            $result[] = [
                'id' => $dept->id,
                'name' => $dept->name,
                'parentid' => $dept->parentid,
            ];
        }

        return ['message' => '', 'data' => $result];
    }

    /**
     * 获取考评任务列表（支持分页）
     */
    public function actionGettasks()
    {
        $scorerId = $this->_adminInfo->wxuserid ?? Yii::$app->request->post('wxuserid');
        $page = Yii::$app->request->post('current', 1);
        $pageSize = Yii::$app->request->post('pageSize', 20);
        $status = Yii::$app->request->post('status');
        $offset = ($page - 1) * $pageSize;

        $query = FzrbsEvaluationTask::find()
            ->select(['fzrbs_evaluation_task.*', 'u.name as scorer_name'])
            ->leftJoin([WeixinOAUserInfo::tableName() . ' u'], 'u.userid = fzrbs_evaluation_task.scorer_id')
            ->orderBy('fzrbs_evaluation_task.created_at desc');

        if (!empty($scorerId)) {
            $query->andWhere(['scorer_id' => $scorerId]);
        }

        if ($status !== null && $status !== '') {
            $query->andWhere(['fzrbs_evaluation_task.status' => intval($status)]);
        }

        $total = $query->count();
        $tasks = $query->limit($pageSize)->offset($offset)->asArray()->all();

        return array('message' => '', 'data' => $tasks, 'total' => $total, 'current' => $page, 'pageSize' => $pageSize);
    }

    /**
     * 删除考评任务
     */
    public function actionDeletetask()
    {
        $check = $this->_checkEvaluationAdmin();
        if ($check) return $check;

        $taskId = Yii::$app->request->post('task_id');
        if (empty($taskId)) {
            $rawBody = Yii::$app->request->getRawBody();
            if (!empty($rawBody)) {
                $data = json_decode($rawBody, true);
                $taskId = $data['task_id'] ?? null;
            }
        }

        if (empty($taskId)) {
            return ['message' => '请提供任务ID'];
        }

        try {
            $taskIdInt = intval($taskId);
            $task = FzrbsEvaluationTask::findOne($taskIdInt);

            if (!$task) {
                return ['message' => '任务不存在，ID: ' . $taskIdInt];
            }

            FzrbsEvaluationScore::deleteAll(['task_id' => $taskIdInt]);
            FzrbsEvaluationPersonalScore::deleteAll(['task_id' => $taskIdInt]);
            $task->delete();

            $this->_operationlog([
                'catalog' => '删除考评任务',
                'remark' => "删除考评任务：{$task->year}年第{$task->quarter}季度（task_id={$taskIdInt}）",
            ]);
            return ['message' => ''];
        } catch (\Exception $e) {
            return ['message' => '删除失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取单个任务的详细信息（含部门列表、评分状态和个人评分）
     * 注意：会过滤掉评分人自己所在的部门
     */
    public function actionGettaskdetail()
    {
        $taskId = Yii::$app->request->post('task_id');
        if (empty($taskId)) {
            $rawBody = Yii::$app->request->getRawBody();
            if (!empty($rawBody)) {
                $data = json_decode($rawBody, true);
                $taskId = $data['task_id'] ?? null;
            }
        }

        if (empty($taskId)) {
            return ['message' => '请提供任务ID'];
        }

        $task = FzrbsEvaluationTask::findOne($taskId);
        if (!$task) {
            return ['message' => '任务不存在'];
        }

        // 解析部门IDs
        $deptIds = $task->dept_ids ? array_map('intval', explode(',', $task->dept_ids)) : [];

        // 获取评分人所在部门ID，用于过滤（评分人不能评价自己所在部门）
        $scorerDeptId = null;
        $scorerUser = WeixinOAUserInfo::find()->where(['userid' => $task->scorer_id])->one();
        if ($scorerUser) {
            $scorerDeptId = $scorerUser->departmentid;
        }

        // 获取所有部门信息
        $departments = WeixinOaDepartment::find()
            ->select(['id', 'name'])
            ->where(['id' => $deptIds])
            ->indexBy('id')
            ->asArray()
            ->all();

        // 获取主评分
        $mainScores = FzrbsEvaluationScore::find()
            ->select(['dept_id', 'score', 'opinion'])
            ->where(['task_id' => $taskId, 'dept_id' => $deptIds])
            ->indexBy('dept_id')
            ->asArray()
            ->all();

        // 获取个人评分（从独立表）
        $personalScoresRaw = FzrbsEvaluationPersonalScore::find()
            ->select(['id', 'task_id', 'dept_id', 'target_user_id', 'score', 'opinion', 'created_at'])
            ->where(['task_id' => $taskId, 'dept_id' => $deptIds])
            ->orderBy('created_at desc')
            ->asArray()
            ->all();

        // 获取相关用户信息
        $targetUserIds = array_filter(array_column($personalScoresRaw, 'target_user_id'));
        $users = [];
        if (!empty($targetUserIds)) {
            $usersRaw = WeixinOAUserInfo::find()
                ->select(['userid', 'name', 'avatar', 'mobile'])
                ->where(['userid' => $targetUserIds])
                ->indexBy('userid')
                ->asArray()
                ->all();
            $users = $usersRaw;
        }

        // 按部门ID分组个人评分
        $personalScoresByDept = [];
        foreach ($personalScoresRaw as $ps) {
            $deptId = $ps['dept_id'];
            if (!isset($personalScoresByDept[$deptId])) {
                $personalScoresByDept[$deptId] = [];
            }
            $user = $users[$ps['target_user_id']] ?? null;
            $personalScoresByDept[$deptId][] = [
                'id' => intval($ps['id']),
                'target_user_id' => $ps['target_user_id'],
                'target_user_name' => $user ? $user['name'] : '',
                'target_user_avatar' => $user ? ($user['avatar'] ?? '') : '',
                'target_user_mobile' => $user ? ($user['mobile'] ?? '') : '',
                'score' => intval($ps['score']),
                'opinion' => $ps['opinion'] ?: '',
                'created_at' => $ps['created_at'],
            ];
        }

        // 组装部门列表，过滤掉评分人自己所在的部门
        $deptList = [];
        foreach ($deptIds as $deptId) {
            // 跳过评分人自己所在的部门
            if ($scorerDeptId && $deptId == $scorerDeptId) {
                continue;
            }

            $dept = $departments[$deptId] ?? null;
            $mainScore = $mainScores[$deptId] ?? null;
            $deptList[] = [
                'dept_id' => $deptId,
                'dept_name' => $dept ? $dept['name'] : '',
                'task_id' => $task->id,
                'score' => $mainScore ? intval($mainScore['score']) : null,
                'opinion' => $mainScore ? ($mainScore['opinion'] ?? '') : '',
                'status' => $mainScore ? 1 : 0,
                'personal_scores' => $personalScoresByDept[$deptId] ?? [],
            ];
        }

        return [
            'message' => '',
            'data' => [
                'task_id' => $task->id,
                'year' => $task->year,
                'quarter' => $task->quarter,
                'start_date' => $task->start_date,
                'end_date' => $task->end_date,
                'scorer_id' => $task->scorer_id,
                'status' => $task->status,
                'submitted_at' => $task->submitted_at,
                'departments' => $deptList,
            ]
        ];
    }

    /**
     * 生成季度考评任务（管理员调用）
     * 只给有"部门考评人"角色的用户生成任务，每人每季度只生成一条 task
     */
    public function actionGeneratetasks()
    {
        $check = $this->_checkEvaluationAdmin();
        if ($check) return $check;

        $year = $this->_request['year'] ?? date('Y');
        $quarter = $this->_request['quarter'] ?? ceil(date('n') / 3);
        $startDate = $this->_request['start_date'] ?? null;
        $endDate = $this->_request['end_date'] ?? null;

        if (!$startDate || !$endDate) {
            return ['message' => '请提供开始日期和结束日期'];
        }

        // 获取被考评部门配置（dept 字段存储的是部门 IDs）
        $deptConfig = FzrbsBudgetDict::find()->where(['type' => '被考评部门', 'label' => '被考评部门'])->asArray()->one();
        if (!$deptConfig || empty($deptConfig['dept'])) {
            return ['message' => '未配置被考评部门'];
        }
        $deptIds = explode(',', $deptConfig['dept']);

        // 检查"部门考评人"角色是否存在
        $roleId = WeixinOaRole::find()->where(['rolename' => '部门考评人'])->select('id')->scalar();
        if (!$roleId) {
            return ['message' => '系统中不存在"部门考评人"角色，请先在角色管理中添加'];
        }

        // 一次性查询所有有"部门考评人"角色的用户（高效）
        $evaluators = WeixinOaFlowrole::find()
            ->alias('fr')
            ->select('fr.userid, u.departmentid, u.name as username')
            ->leftJoin(['u' => WeixinOAUserInfo::tableName()], 'fr.userid = u.userid')
            ->where(['fr.role' => $roleId])
            ->andWhere(['u.status' => 1])
            ->andWhere(['u.st' => 1])
            ->groupBy('fr.userid')
            ->asArray()
            ->all();

        if (empty($evaluators)) {
            return ['message' => '没有找到需要评分的用户（请确认已给用户分配"部门考评人"角色）'];
        }

        $createdCount = 0;
        $notifyUsers = [];
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            foreach ($evaluators as $evaluator) {
                // 检查是否已存在该用户的 task
                $exists = FzrbsEvaluationTask::findOne([
                    'year' => $year,
                    'quarter' => $quarter,
                    'scorer_id' => $evaluator['userid'],
                ]);

                if ($exists) {
                    continue;
                }

                // 创建任务
                $task = new FzrbsEvaluationTask();
                $task->year = $year;
                $task->quarter = $quarter;
                $task->start_date = $startDate;
                $task->end_date = $endDate;
                $task->scorer_id = $evaluator['userid'];
                $task->dept_ids = implode(',', $deptIds);
                $task->status = 0;
                $task->created_at = date('Y-m-d H:i:s');

                if ($task->save()) {
                    $createdCount++;
                    $notifyUsers[] = [
                        'userid' => $evaluator['userid'],
                        'username' => $evaluator['username'],
                    ];
                }
            }

            $transaction->commit();

            // 批量发送通知（只发一句话）
            $this->_batchSendNotify($notifyUsers, $year, $quarter);

            // 记录操作日志
            $this->_operationlog([
                'catalog' => '生成考评任务',
                'remark' => "生成考评任务：{$year}年第{$quarter}季度，共生成{$createdCount}个任务，涉及" . count($deptIds) . "个部门",
            ]);

            return ['message' => '', 'created' => $createdCount];

        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['message' => '生成失败：' . $e->getMessage()];
        }
    }

    /**
     * 批量发送通知
     */
    private function _batchSendNotify($notifyUsers, $year, $quarter)
    {
        if (empty($notifyUsers)) return;

        $content = "您有{$year}年第{$quarter}季度考评任务待完成，请使用[掌上福州->社直部门考评]及时评分。";
        $touser = implode('|', array_column($notifyUsers, 'userid'));
        WxQyhJk::sendMessage($this->agentId, $touser, $content, 'text');
    }



    /**
     * 批量提交主评分
     * 一次性提交整个task的所有部门评分
     */
    public function actionBatchsubmitscore()
    {
        $taskId = Yii::$app->request->post('task_id');
        $scores = Yii::$app->request->post('scores', []);

        if (!$taskId) {
            return ['message' => '请提供任务ID'];
        }

        if (empty($scores) || !is_array($scores)) {
            return ['message' => '请提供评分数据'];
        }

        // 获取任务
        $task = FzrbsEvaluationTask::findOne($taskId);
        if (!$task) {
            return ['message' => '任务不存在'];
        }

        // 10号之后禁止修改评分
        if ($deadlineError = $this->_checkDeadline()) {
            return $deadlineError;
        }

        $taskDeptIds = $task->dept_ids ? array_map('intval', explode(',', $task->dept_ids)) : [];

        // 获取评分档次配置
        $validScores = $this->_getValidScores();

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $results = [];
            $successCount = 0;
            $failCount = 0;

            foreach ($scores as $item) {
                $deptId = intval($item['dept_id'] ?? 0);
                $score = intval($item['score'] ?? 0);
                $opinion = $item['opinion'] ?? '';

                // 验证
                if (!$deptId) {
                    $results[] = ['dept_id' => $deptId, 'success' => false, 'message' => '部门ID无效'];
                    $failCount++;
                    continue;
                }

                if (!in_array($deptId, $taskDeptIds)) {
                    $results[] = ['dept_id' => $deptId, 'success' => false, 'message' => '该部门不在考评范围内'];
                    $failCount++;
                    continue;
                }

                if (!in_array($score, $validScores)) {
                    $results[] = ['dept_id' => $deptId, 'success' => false, 'message' => '无效的分数值'];
                    $failCount++;
                    continue;
                }

                // 分数小于85必须填写意见
                if ($score < 85 && empty(trim($opinion))) {
                    $deptName = WeixinOaDepartment::findOne($deptId)->name ?? "ID:{$deptId}";
                    $results[] = ['dept_id' => $deptId, 'success' => false, 'message' => "【{$deptName}】评分低于85分，必须填写意见"];
                    $failCount++;
                    continue;
                }

                // 检查是否已存在主评分，存在则更新
                $existing = FzrbsEvaluationScore::find()
                    ->where(['task_id' => $taskId, 'dept_id' => $deptId])
                    ->one();

                if ($existing) {
                    $existing->score = $score;
                    $existing->opinion = $opinion;
                    $existing->save();
                    $scoreId = $existing->id;
                } else {
                    $scoreRecord = new FzrbsEvaluationScore();
                    $scoreRecord->task_id = $taskId;
                    $scoreRecord->dept_id = $deptId;
                    $scoreRecord->score = $score;
                    $scoreRecord->opinion = $opinion;
                    $scoreRecord->created_at = date('Y-m-d H:i:s');
                    $scoreRecord->save();
                    $scoreId = $scoreRecord->id;
                }

                $results[] = ['dept_id' => $deptId, 'success' => true, 'score_id' => $scoreId];
                $successCount++;
            }

            // 检查是否所有部门都已评分
            $scoredDeptIds = array_filter(array_column($results, 'dept_id'));
            $allScored = count(array_intersect($taskDeptIds, $scoredDeptIds)) == count($taskDeptIds);

            if ($allScored && count($taskDeptIds) > 0) {
                $task->status = 1;
                $task->submitted_at = date('Y-m-d H:i:s');
                $task->save();
            }

            $transaction->commit();

            $this->_operationlog([
                'catalog' => '批量提交评分',
                'remark' => "批量提交评分：task_id={$taskId}，成功{$successCount}个，失败{$failCount}个",
            ]);

            if ($failCount > 0) {
                $failedMessages = [];
                foreach ($results as $r) {
                    if (!empty($r['message'])) {
                        $failedMessages[] = $r['message'];
                    }
                }
                $failedText = implode("\n", $failedMessages);
                if (empty($failedText)) {
                    $failedText = "共{$failCount}项评分存在问题";
                }
                return ['message' => $failedText, 'data' => ['fail_count' => $failCount, 'success_count' => $successCount, 'results' => $results]];
            }

            return ['message' => '', 'data' => ['success_count' => $successCount]];

        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['message' => '批量提交失败：' . $e->getMessage()];
        }
    }

    /**
     * 提交个人评分（独立接口）
     */
    public function actionPersonalsubmitscore()
    {
        $taskId = Yii::$app->request->post('task_id');
        $deptId = Yii::$app->request->post('dept_id');
        $targetUserId = Yii::$app->request->post('target_user_id');
        $score = Yii::$app->request->post('score');
        $opinion = Yii::$app->request->post('opinion', '');

        if (!$taskId || !$deptId || !$targetUserId || !$score) {
            return ['message' => '请提供完整参数'];
        }

        // 获取评分档次配置
        $validScores = $this->_getValidScores();
        if (!in_array($score, $validScores)) {
            return ['message' => '无效的分数值'];
        }

        // 分数小于85必须填写意见
        if ($score < 85 && empty(trim($opinion))) {
            $deptName = WeixinOaDepartment::findOne($deptId)->name ?? "ID:{$deptId}";
            return ['message' => "【{$deptName}】评分低于85分，必须填写意见"];
        }

        // 个人评分必须填写意见
        if (empty(trim($opinion))) {
            $deptName = WeixinOaDepartment::findOne($deptId)->name ?? "ID:{$deptId}";
            return ['message' => "【{$deptName}】个人评分必须填写意见"];
        }

        // 获取任务
        $task = FzrbsEvaluationTask::findOne($taskId);
        if (!$task) {
            return ['message' => '任务不存在'];
        }

        $taskDeptIds = $task->dept_ids ? explode(',', $task->dept_ids) : [];
        if (!in_array($deptId, $taskDeptIds)) {
            return ['message' => '该部门不在考评范围内'];
        }

        // 部门评分已提交则禁止修改个人评分
        if ($this->_isDeptScoreSubmitted($taskId, $deptId)) {
            return ['message' => '该部门评分已提交，无法修改个人评分'];
        }

        // 10号之后禁止修改评分
        if ($deadlineError = $this->_checkDeadline()) {
            return $deadlineError;
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            // 检查是否已存在同一个人对同一个部门的评分
            $existing = FzrbsEvaluationPersonalScore::find()
                ->where([
                    'task_id' => $taskId,
                    'dept_id' => $deptId,
                    'target_user_id' => $targetUserId,
                ])
                ->one();

            if ($existing) {
                $existing->score = $score;
                $existing->opinion = $opinion;
                $existing->updated_at = date('Y-m-d H:i:s');
                $existing->save();
                $scoreId = $existing->id;
            } else {
                $scoreRecord = new FzrbsEvaluationPersonalScore();
                $scoreRecord->task_id = $taskId;
                $scoreRecord->dept_id = $deptId;
                $scoreRecord->target_user_id = $targetUserId;
                $scoreRecord->score = $score;
                $scoreRecord->opinion = $opinion;
                $scoreRecord->created_at = date('Y-m-d H:i:s');
                $scoreRecord->updated_at = date('Y-m-d H:i:s');
                $scoreRecord->save();
                $scoreId = $scoreRecord->id;
            }

            $transaction->commit();

            $this->_operationlog([
                'catalog' => '提交个人评分',
                'remark' => "提交个人评分：task_id={$taskId}，部门{$deptId}，分数{$score}",
            ]);

            return ['message' => ''];

        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['message' => '提交失败：' . $e->getMessage()];
        }
    }

    /**
     * 删除个人评分
     */
    public function actionDeletepersonalscore()
    {
        $scoreId = Yii::$app->request->post('id');

        if (!$scoreId) {
            return ['message' => '请提供评分记录ID'];
        }

        $scoreRecord = FzrbsEvaluationPersonalScore::findOne($scoreId);
        if (!$scoreRecord) {
            return ['message' => '评分记录不存在'];
        }

        // 部门评分已提交则禁止删除个人评分
        if ($this->_isDeptScoreSubmitted($scoreRecord->task_id, $scoreRecord->dept_id)) {
            return ['message' => '该部门评分已提交，无法删除个人评分'];
        }

        // 10号之后禁止删除个人评分
        if ($deadlineError = $this->_checkDeadline('删除')) {
            return $deadlineError;
        }

        $scoreRecord->delete();

        $this->_operationlog([
            'catalog' => '删除个人评分',
            'remark' => "删除个人评分：score_id={$scoreId}",
        ]);

        return ['message' => ''];
    }

    /**
     * 提交评分（向后兼容，仅处理主评分）
     */
    public function actionSubmitscore()
    {
        $taskId = Yii::$app->request->post('task_id');
        $deptId = Yii::$app->request->post('dept_id');
        $score = Yii::$app->request->post('score');
        $opinion = Yii::$app->request->post('opinion', '');

        if (!$taskId || !$deptId || !$score) {
            return ['message' => '请提供任务ID、部门ID和分数'];
        }

        // 获取评分档次配置
        $validScores = $this->_getValidScores();
        if (!in_array($score, $validScores)) {
            return ['message' => '无效的分数值'];
        }

        // 分数小于85必须填写意见
        if ($score < 85 && empty(trim($opinion))) {
            $deptName = WeixinOaDepartment::findOne($deptId)->name ?? "ID:{$deptId}";
            return ['message' => "【{$deptName}】评分低于85分，必须填写意见"];
        }

        // 获取任务
        $task = FzrbsEvaluationTask::findOne($taskId);
        if (!$task) {
            return ['message' => '任务不存在'];
        }

        // 部门评分已提交则禁止再次修改
        if ($this->_isDeptScoreSubmitted($taskId, $deptId)) {
            return ['message' => '该部门评分已提交，无法修改'];
        }

        // 10号之后禁止修改评分
        if ($deadlineError = $this->_checkDeadline()) {
            return $deadlineError;
        }

        // 检查该部门是否在 task 的评分公司列表中
        $taskDeptIds = $task->dept_ids ? explode(',', $task->dept_ids) : [];
        if (!in_array($deptId, $taskDeptIds)) {
            return ['message' => '该部门不在考评范围内'];
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            // 检查是否已存在，存在则更新
            $existing = FzrbsEvaluationScore::find()
                ->where(['task_id' => $taskId, 'dept_id' => $deptId])
                ->one();

            if ($existing) {
                $existing->score = $score;
                $existing->opinion = $opinion;
                $existing->save();
                $scoreId = $existing->id;
            } else {
                $scoreRecord = new FzrbsEvaluationScore();
                $scoreRecord->task_id = $taskId;
                $scoreRecord->dept_id = $deptId;
                $scoreRecord->score = $score;
                $scoreRecord->opinion = $opinion;
                $scoreRecord->created_at = date('Y-m-d H:i:s');
                $scoreRecord->save();
                $scoreId = $scoreRecord->id;
            }

            // 检查是否所有部门都已评分
            $mainScoresCount = FzrbsEvaluationScore::find()
                ->where(['task_id' => $taskId])
                ->count();

            if ($mainScoresCount == count($taskDeptIds)) {
                $task->status = 1;
                $task->submitted_at = date('Y-m-d H:i:s');
                $task->save();
            }

            $transaction->commit();

            $this->_operationlog([
                'catalog' => '提交评分',
                'remark' => "提交评分：task_id={$taskId}，部门{$deptId}，分数{$score}",
            ]);

            return ['message' => ''];

        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['message' => '提交失败：' . $e->getMessage()];
        }
    }

    /**
     * 删除评分记录（仅支持主评分，向后兼容）
     */
    public function actionDeletescore()
    {
        $scoreId = Yii::$app->request->post('score_id');

        if (!$scoreId) {
            return ['message' => '请提供评分记录ID'];
        }

        $scoreRecord = FzrbsEvaluationScore::findOne($scoreId);
        if (!$scoreRecord) {
            return ['message' => '评分记录不存在'];
        }

        $scoreRecord->delete();

        $this->_operationlog([
            'catalog' => '删除评分',
            'remark' => "删除评分：score_id={$scoreId}",
        ]);

        return ['message' => ''];
    }

    /**
     * 更新个人评分
     */
    public function actionUpdatepersonalscore()
    {
        $scoreId = Yii::$app->request->post('score_id');
        $score = Yii::$app->request->post('score');
        $opinion = Yii::$app->request->post('opinion', '');

        if (!$scoreId) {
            return ['message' => '请提供评分记录ID'];
        }

        $scoreRecord = FzrbsEvaluationPersonalScore::findOne($scoreId);
        if (!$scoreRecord) {
            return ['message' => '评分记录不存在'];
        }

        // 10号之后禁止修改评分
        if ($deadlineError = $this->_checkDeadline()) {
            return $deadlineError;
        }

        $scoreRecord->score = $score;
        $scoreRecord->opinion = $opinion;
        $scoreRecord->updated_at = date('Y-m-d H:i:s');
        $scoreRecord->save();

        return ['message' => ''];
    }


    /**
     * 超时自动打分（定时任务）
     */
    public function actionAutoscore()
    {
        $check = $this->_checkEvaluationAdmin();
        if ($check) return $check;

        $year = $this->_request['year'] ?? date('Y');
        $quarter = $this->_request['quarter'] ?? ceil(date('n') / 3);

        // 查询所有超时未评分的任务
        $tasks = FzrbsEvaluationTask::find()
            ->where(['year' => $year, 'quarter' => $quarter, 'status' => 0])
            ->andWhere(['<', 'end_date', date('Y-m-d')])
            ->all();

        if (empty($tasks)) {
            return ['message' => ''];
        }

        $count = 0;
        $defaultScore = 95; // 默认95分

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            foreach ($tasks as $task) {
                // 解析该任务下的所有被考评部门
                $deptIds = $task->dept_ids ? array_map('intval', explode(',', $task->dept_ids)) : [];

                // 为每个部门创建评分记录
                foreach ($deptIds as $deptId) {
                    $scoreRecord = new FzrbsEvaluationScore();
                    $scoreRecord->task_id = $task->id;
                    $scoreRecord->dept_id = $deptId;
                    $scoreRecord->score = $defaultScore;
                    $scoreRecord->opinion = '';
                    $scoreRecord->created_at = date('Y-m-d H:i:s');
                    $scoreRecord->save();

                    $count++;
                }

                // 更新任务状态
                $task->status = 2;
                $task->submitted_at = date('Y-m-d H:i:s');
                $task->save();
            }

            $transaction->commit();

            // 记录操作日志
            $this->_operationlog([
                'catalog' => '超时自动评分',
                'remark' => "超时自动评分：{$year}年第{$quarter}季度，共处理{$count}个任务",
            ]);

            return ['message' => '', 'count' => $count];

        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['message' => '自动评分失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取扣款明细
     */
    public function actionGetpenalties()
    {
        $year = Yii::$app->request->get('year', date('Y'));
        $quarter = Yii::$app->request->get('quarter', ceil(date('n') / 3));

        $penalties = FzrbsEvaluationPenalty::find()
            ->where(['year' => $year, 'quarter' => $quarter])
            ->orderBy('dept_id')
            ->all();

        $result = [];
        foreach ($penalties as $penalty) {
            $result[] = [
                'id' => $penalty->id,
                'year' => $penalty->year,
                'quarter' => $penalty->quarter,
                'dept_id' => $penalty->dept_id,
                'dept_name' => $penalty->dept_name,
                'avg_score' => $penalty->avg_score,
                'total_deduct' => $penalty->total_deduct,
            ];
        }

        return ['message' => '', 'data' => $result];
    }

    /**
     * 获取预警名单
     */
    public function actionGetwarnings()
    {
        $year = Yii::$app->request->get('year', date('Y'));

        $warnings = FzrbsEvaluationWarning::find()
            ->where(['year' => $year])
            ->orderBy('score_50_count DESC')
            ->all();

        $result = [];
        foreach ($warnings as $warning) {
            $result[] = [
                'id' => $warning->id,
                'year' => $warning->year,
                'user_id' => $warning->user_id,
                'user_name' => $warning->user_name,
                'cycle_ids' => $warning->cycle_ids,
                'score_50_count' => $warning->score_50_count,
                'warning_level' => $warning->warning_level,
            ];
        }

        return ['message' => '', 'data' => $result];
    }

    /**
     * 导出扣款明细（Excel）
     */
    public function actionExportpenalties()
    {
        $year = Yii::$app->request->get('year', date('Y'));
        $quarter = Yii::$app->request->get('quarter', ceil(date('n') / 3));

        $penalties = FzrbsEvaluationPenalty::find()
            ->where(['year' => $year, 'quarter' => $quarter])
            ->orderBy('dept_id')
            ->all();

        $data = [];
        $data[] = ['部门', '部门平均分', '扣款金额'];

        foreach ($penalties as $penalty) {
            $data[] = [
                $penalty->dept_name,
                $penalty->avg_score,
                $penalty->total_deduct,
            ];
        }

        return $this->_exportExcel($data, "扣款明细_{$year}Q{$quarter}");
    }

    /**
     * 导出预警名单（Excel）
     */
    public function actionExportwarnings()
    {
        $check = $this->_checkEvaluationAdmin();
        if ($check) return $check;

        $year = Yii::$app->request->get('year', date('Y'));

        $warnings = FzrbsEvaluationWarning::find()
            ->where(['year' => $year])
            ->orderBy('score_50_count DESC')
            ->all();

        $data = [];
        $data[] = ['员工姓名', '累计50分次数', '预警级别', '触发周期'];

        foreach ($warnings as $warning) {
            $levelText = $warning->warning_level == 1 ? '预警' : '调离';
            $data[] = [
                $warning->user_name,
                $warning->score_50_count,
                $levelText,
                $warning->cycle_ids,
            ];
        }

        $this->_operationlog([
            'catalog' => '导出预警名单',
            'remark' => "导出预警名单：{$year}年，共" . count($warnings) . "人",
        ]);
        return $this->_exportExcel($data, "预警名单_{$year}");
    }

    /**
     * 导出评分任务汇总（Excel）
     */
    public function actionExporttasks()
    {
        $year = Yii::$app->request->get('year', date('Y'));
        $quarter = Yii::$app->request->get('quarter', ceil(date('n') / 3));

        $tasks = FzrbsEvaluationTask::find()
            ->where(['year' => $year, 'quarter' => $quarter])
            ->all();

        $data = [];
        $data[] = ['评分人', '被考评部门', '状态', '提交时间'];

        foreach ($tasks as $task) {
            $dept = $task->department;
            $statusText = $task->status == 0 ? '未打分' : ($task->status == 1 ? '已打分' : '超时默认');
            $data[] = [
                $task->scorer_id,
                $dept ? $dept->name : '',
                $statusText,
                $task->submitted_at,
            ];
        }

        return $this->_exportExcel($data, "评分任务汇总_{$year}Q{$quarter}");
    }

    /**
     * 计算扣款（供定时任务调用）
     */
    public function actionCalculatededuction()
    {
        $year = Yii::$app->request->post('year', date('Y'));
        $quarter = Yii::$app->request->post('quarter', ceil(date('n') / 3));

        // 获取扣款配置
        $thresholdConfig = FzrbsBudgetDict::findOne(['type' => '扣款阈值', 'label' => '触发扣款阈值']);
        $threshold = $thresholdConfig ? floatval($thresholdConfig->value) : 80;

        $deductConfigs = FzrbsBudgetDict::find()->where(['type' => '扣款比例'])->all();
        $deductRates = [];
        foreach ($deductConfigs as $config) {
            $deductRates[$config->label] = floatval($config->value);
        }

        // 获取被考评部门IDs（dept 字段存储的是部门 IDs）
        $deptConfig = FzrbsBudgetDict::findOne(['type' => '被考评部门', 'label' => '被考评部门']);
        $deptIds = $deptConfig ? explode(',', $deptConfig->dept) : [];

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            // 一次性查询所有部门的平均分（使用 GROUP BY）
            $avgScores = FzrbsEvaluationScore::find()
                ->select(['dept_id', 'AVG(score) as avg_score'])
                ->where(['dept_id' => $deptIds])
                ->groupBy('dept_id')
                ->indexBy('dept_id')
                ->asArray()
                ->all();

            // 一次性查询所有部门名称
            $departments = WeixinOaDepartment::find()
                ->select(['id', 'name'])
                ->where(['id' => $deptIds])
                ->indexBy('id')
                ->asArray()
                ->all();

            foreach ($deptIds as $deptId) {
                $avgScore = $avgScores[$deptId]['avg_score'] ?? null;

                if ($avgScore === null) {
                    continue;
                }

                // 计算扣款
                $deduct = 0;
                if ($avgScore < 60) {
                    $deduct = $deductRates['<60分区间'] ?? 30;
                } elseif ($avgScore < 70) {
                    $deduct = $deductRates['60-70分区间'] ?? 10;
                } elseif ($avgScore < $threshold) {
                    $deduct = $deductRates['70-80分区间'] ?? 5;
                }

                // 保存扣款记录
                $penalty = new FzrbsEvaluationPenalty();
                $penalty->year = $year;
                $penalty->quarter = $quarter;
                $penalty->dept_id = $deptId;
                $penalty->dept_name = $departments[$deptId]['name'] ?? '';
                $penalty->avg_score = $avgScore;
                $penalty->total_deduct = $deduct;
                $penalty->created_at = date('Y-m-d H:i:s');
                $penalty->save();
            }

            $transaction->commit();

            // 记录操作日志
            $this->_operationlog([
                'catalog' => '计算扣款',
                'remark' => "计算扣款：{$year}年第{$quarter}季度",
            ]);

            return ['message' => ''];

        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['message' => '扣款计算失败：' . $e->getMessage()];
        }
    }

    /**
     * 生成50分预警（供定时任务调用）
     */
    public function actionGeneratewarnings()
    {
        $check = $this->_checkEvaluationAdmin();
        if ($check) return $check;

        $year = Yii::$app->request->post('year', date('Y'));

        // 查找本年内所有50分的评分
        $score50Records = FzrbsEvaluationScore::find()
            ->joinWith('task')
            ->where(['fzrbs_evaluation_task.year' => $year, 'fzrbs_evaluation_score.score' => 50])
            ->all();

        // 按评分人分组
        $userScores = [];
        foreach ($score50Records as $record) {
            $task = $record->task;
            if ($task) {
                $userId = $task->scorer_id;
                if (!isset($userScores[$userId])) {
                    $userScores[$userId] = [
                        'count' => 0,
                        'cycles' => [],
                    ];
                }
                $userScores[$userId]['count']++;
                $userScores[$userId]['cycles'][] = "{$year}Q{$task->quarter}";
            }
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            // 一次性查询所有相关用户信息
            $userIds = array_keys($userScores);
            $users = WeixinOAUserInfo::find()
                ->select(['userid', 'name'])
                ->where(['userid' => $userIds])
                ->indexBy('userid')
                ->asArray()
                ->all();

            foreach ($userScores as $userId => $data) {
                $user = $users[$userId] ?? null;

                $existing = FzrbsEvaluationWarning::findOne(['year' => $year, 'user_id' => $userId]);

                if ($existing) {
                    $existing->score_50_count = $data['count'];
                    $existing->cycle_ids = implode(',', $data['cycles']);
                    $existing->warning_level = $data['count'] >= 2 ? 2 : 1;
                    $existing->updated_at = date('Y-m-d H:i:s');
                    $existing->save();
                } else {
                    $warning = new FzrbsEvaluationWarning();
                    $warning->year = $year;
                    $warning->user_id = $userId;
                    $warning->user_name = $user ? $user['name'] : '';
                    $warning->cycle_ids = implode(',', $data['cycles']);
                    $warning->score_50_count = $data['count'];
                    $warning->warning_level = $data['count'] >= 2 ? 2 : 1;
                    $warning->created_at = date('Y-m-d H:i:s');
                    $warning->updated_at = date('Y-m-d H:i:s');
                    $warning->save();
                }
            }

            $transaction->commit();

            // 记录操作日志
            $this->_operationlog([
                'catalog' => '生成50分预警',
                'remark' => "生成50分预警：{$year}年度，共" . count($userScores) . "人",
            ]);

            return ['message' => ''];

        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['message' => '预警生成失败：' . $e->getMessage()];
        }
    }

    /**
     * 保存扣罚录入（管理员录入）
     */
    public function actionSavepenaltyinput()
    {
        $check = $this->_checkEvaluationAdmin();
        if ($check) return $check;

        $year = Yii::$app->request->post('year');
        $quarter = Yii::$app->request->post('quarter');
        $items = Yii::$app->request->post('items', []);

        if (!$year || !$quarter || empty($items)) {
            return ['message' => '请提供完整的录入数据'];
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $results = [];
            foreach ($items as $item) {
                $deptId = intval($item['dept_id'] ?? 0);
                $totalDeduct = floatval($item['total_deduct'] ?? 0);
                $personCount = intval($item['dept_person_count'] ?? 0);
                $persons = $item['persons'] ?? [];

                if (!$deptId || $totalDeduct <= 0 || $personCount <= 0) {
                    continue;
                }

                // 保存录入记录
                $input = new FzrbsEvaluationPenaltyInput();
                $input->year = $year;
                $input->quarter = $quarter;
                $input->dept_id = $deptId;
                $input->dept_name = $item['dept_name'] ?? '';
                $input->total_deduct = $totalDeduct;
                $input->dept_person_count = $personCount;
                $input->input_by = $this->_adminInfo->wxuserid ?? 'system';
                $input->created_at = date('Y-m-d H:i:s');
                $input->save();

                $inputId = $input->id;

                // 保存个人扣罚记录
                foreach ($persons as $person) {
                    $personRecord = new FzrbsEvaluationPenaltyPerson();
                    $personRecord->input_id = $inputId;
                    $personRecord->user_id = $person['user_id'] ?? '';
                    $personRecord->user_name = $person['user_name'] ?? '';
                    $personRecord->deduct_type = intval($person['deduct_type'] ?? 0);
                    $personRecord->post_performance = floatval($person['post_performance'] ?? 0);
                    $personRecord->fixed_deduct = floatval($person['fixed_deduct'] ?? 0);
                    $personRecord->created_at = date('Y-m-d H:i:s');
                    $personRecord->save();
                }

                $results[] = [
                    'dept_id' => $deptId,
                    'input_id' => $inputId,
                ];
            }

            $transaction->commit();

            $this->_operationlog([
                'catalog' => '保存扣罚录入',
                'remark' => "扣罚录入：{$year}年第{$quarter}季度，共" . count($items) . "项",
            ]);
            return ['message' => ''];

        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['message' => '保存失败：' . $e->getMessage()];
        }
    }

    /**
     * 计算分摊并保存结果
     */
    public function actionCalculatepenalty()
    {
        $check = $this->_checkEvaluationAdmin();
        if ($check) return $check;

        $year = Yii::$app->request->post('year');
        $quarter = Yii::$app->request->post('quarter');

        if (!$year || !$quarter) {
            return ['message' => '请提供年份和季度'];
        }

        // 获取所有录入记录
        $inputs = FzrbsEvaluationPenaltyInput::find()
            ->where(['year' => $year, 'quarter' => $quarter])
            ->all();

        if (empty($inputs)) {
            return ['message' => '没有找到录入数据'];
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            $results = [];

            foreach ($inputs as $input) {
                $totalDeduct = floatval($input->total_deduct);
                $personCount = intval($input->dept_person_count);

                // 获取该录入的个人扣罚记录
                $persons = FzrbsEvaluationPenaltyPerson::find()
                    ->where(['input_id' => $input->id])
                    ->all();

                // 计算被批评者的个人扣罚总额
                $personalDeductTotal = 0;
                foreach ($persons as $person) {
                    $personalDeduct = $person->getPersonalDeduct();
                    $personalDeductTotal += $personalDeduct;
                }

                // 计算其他人人均
                $criticizedCount = count($persons);
                $othersCount = $personCount - $criticizedCount;
                $othersPerPerson = 0;
                if ($othersCount > 0) {
                    $othersPerPerson = ($totalDeduct - $personalDeductTotal) / $othersCount;
                }

                // 保存被批评者的结果
                foreach ($persons as $person) {
                    $personalDeduct = $person->getPersonalDeduct();

                    $result = new FzrbsEvaluationPenaltyResult();
                    $result->year = $year;
                    $result->quarter = $quarter;
                    $result->dept_id = $input->dept_id;
                    $result->user_id = $person->user_id;
                    $result->user_name = $person->user_name;
                    $result->deduct_amount = $personalDeduct;
                    $result->is_personal = 1;
                    $result->created_at = date('Y-m-d H:i:s');
                    $result->save();

                    $results[] = $result->attributes;
                }

                // 如果有其他人（没有被批评的），生成他们的分摊记录
                // 需要获取该部门的所有其他员工
                // 这里简化处理：其他人的人均分摊暂时不生成具体名单
                // 如需生成，需要部门员工名单接口
            }

            $transaction->commit();

            $this->_operationlog([
                'catalog' => '计算扣款分摊',
                'remark' => "计算扣款分摊：{$year}年第{$quarter}季度，共" . count($results) . "项",
            ]);
            return ['message' => ''];

        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['message' => '计算失败：' . $e->getMessage()];
        }
    }

    /**
     * 获取扣款结果
     */
    public function actionGetpenaltyresult()
    {
        $year = Yii::$app->request->get('year');
        $quarter = Yii::$app->request->get('quarter');
        $deptId = Yii::$app->request->get('dept_id');

        $query = FzrbsEvaluationPenaltyResult::find()
            ->where(['year' => $year, 'quarter' => $quarter]);

        if ($deptId) {
            $query->andWhere(['dept_id' => $deptId]);
        }

        $results = $query->orderBy('dept_id', 'is_personal desc')->asArray()->all();

        return ['message' => '', 'data' => $results];
    }

    /**
     * 导出扣款结果Excel
     */
    public function actionExportpenalty()
    {
        $year = Yii::$app->request->get('year', date('Y'));
        $quarter = Yii::$app->request->get('quarter', ceil(date('n') / 3));

        $results = FzrbsEvaluationPenaltyResult::find()
            ->where(['year' => $year, 'quarter' => $quarter])
            ->orderBy('dept_id', 'is_personal desc')
            ->asArray()
            ->all();

        $data = [];
        $data[] = ['年份', '季度', '部门ID', '员工姓名', '扣罚金额', '是否个人追责'];

        foreach ($results as $row) {
            $data[] = [
                $row['year'],
                $row['quarter'],
                $row['dept_id'],
                $row['user_name'],
                $row['deduct_amount'],
                $row['is_personal'] ? '是' : '否',
            ];
        }

        $filename = "扣款结果_{$year}Q{$quarter}";

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');

        $str = '';
        foreach ($data as $row) {
            $str .= implode("\t", $row) . "\n";
        }

        echo $str;
        exit;
    }

    /**
     * 获取打印数据（所有评分人对所有部门的评分汇总）
     */
    public function actionGetprintdata()
    {
        $year = Yii::$app->request->get('year', date('Y'));
        $quarter = Yii::$app->request->get('quarter', ceil(date('n') / 3));

        // 获取被考评部门配置
        $deptConfig = FzrbsBudgetDict::findOne(['type' => '被考评部门', 'label' => '被考评部门']);
        if (!$deptConfig || empty($deptConfig->dept)) {
            return ['message' => '未配置被考评部门'];
        }
        $deptIds = array_map('intval', explode(',', $deptConfig->dept));

        // 获取所有部门信息
        $departments = WeixinOaDepartment::find()
            ->select(['id', 'name'])
            ->where(['id' => $deptIds])
            ->orderBy('id')
            ->asArray()
            ->all();

        // 部门ID索引
        $departmentsById = array_column($departments, null, 'id');

        // 获取该年份/季度的所有任务，按 weixin_oa_flowrole.seq 排序
        $tasks = FzrbsEvaluationTask::find()
            ->alias('t')
            ->leftJoin('weixin_oa_flowrole fr', 't.scorer_id = fr.userid AND fr.role = (SELECT id FROM weixin_oa_role WHERE rolename = "部门考评人")')
            ->where(['t.year' => $year, 't.quarter' => $quarter])
            ->orderBy('fr.seq ASC')
            ->all();

        if (empty($tasks)) {
            return [
                'message' => '',
                'data' => [
                    'year' => $year,
                    'quarter' => $quarter,
                    'departments' => $departments,
                    'avg_scores' => [],
                    'scorers' => []
                ]
            ];
        }

        // 一次性查询所有相关的评分记录
        $taskIds = array_column($tasks, 'id');
        $scorerIds = array_column($tasks, 'scorer_id');

        // 查询主评分记录
        $mainScores = FzrbsEvaluationScore::find()
            ->where(['task_id' => $taskIds])
            ->asArray()
            ->all();

        // 计算每个部门的平均分
        $deptScoreSum = [];
        $deptScoreCount = [];
        foreach ($mainScores as $score) {
            $deptId = $score['dept_id'];
            if (!isset($deptScoreSum[$deptId])) {
                $deptScoreSum[$deptId] = 0;
                $deptScoreCount[$deptId] = 0;
            }
            $deptScoreSum[$deptId] += intval($score['score']);
            $deptScoreCount[$deptId]++;
        }
        $deptAvgScores = [];
        foreach ($departments as $dept) {
            $deptId = $dept['id'];
            $avgScore = ($deptScoreCount[$deptId] ?? 0) > 0
                ? round($deptScoreSum[$deptId] / $deptScoreCount[$deptId], 1)
                : null;
            $deptAvgScores[$deptId] = $avgScore;
        }

        // 查询个人评分记录
        $personalScores = FzrbsEvaluationPersonalScore::find()
            ->where(['task_id' => $taskIds])
            ->asArray()
            ->all();

        // 查询被评价人姓名
        $targetUserIds = array_unique(array_filter(array_column($personalScores, 'target_user_id')));
        $targetUsersInfo = [];
        if (!empty($targetUserIds)) {
            $targetUsersInfo = WeixinOAUserInfo::find()
                ->select(['userid', 'name'])
                ->where(['userid' => $targetUserIds])
                ->indexBy('userid')
                ->asArray()
                ->all();
        }

        // 一次性查询所有评分人信息
        $scorersInfo = WeixinOAUserInfo::find()
            ->select(['userid', 'name'])
            ->where(['userid' => $scorerIds])
            ->indexBy('userid')
            ->asArray()
            ->all();

        // 按 task_id 分组评分记录
        $scoresByTask = [];
        foreach ($mainScores as $score) {
            $taskId = $score['task_id'];
            if (!isset($scoresByTask[$taskId])) {
                $scoresByTask[$taskId] = [];
            }
            $scoresByTask[$taskId][$score['dept_id']] = $score;
        }

        // 按 task_id 分组个人评分记录
        $personalScoresByTask = [];
        foreach ($personalScores as $ps) {
            $taskId = $ps['task_id'];
            if (!isset($personalScoresByTask[$taskId])) {
                $personalScoresByTask[$taskId] = [];
            }
            $personalScoresByTask[$taskId][] = $ps;
        }

        // 构建返回数据
        $scorers = [];
        foreach ($tasks as $task) {
            $scorerId = $task->scorer_id;
            $scorerInfo = $scorersInfo[$scorerId] ?? null;

            // 获取该任务的评分记录
            $taskScores = $scoresByTask[$task->id] ?? [];
            $taskPersonalScores = $personalScoresByTask[$task->id] ?? [];

            // 构建 scores 对象（key 为 dept_id）
            $scores = [];
            foreach ($departments as $dept) {
                $deptId = $dept['id'];
                $scoreRecord = $taskScores[$deptId] ?? null;
                $scores[$deptId] = [
                    'score' => $scoreRecord ? intval($scoreRecord['score']) : null
                ];
            }

            // 汇总部门意见（所有部门的 opinion，格式：部门名称：意见）
            $deptOpinions = [];
            foreach ($taskScores as $deptId => $score) {
                if (!empty($score['opinion'])) {
                    $deptName = $departmentsById[$deptId]['name'] ?? $deptId;
                    $deptOpinions[] = "{$deptName}：{$score['opinion']}";
                }
            }
            $deptOpinion = implode("\n\n", $deptOpinions);

            // 汇总个人意见（所有个人评分的 opinion，格式：被评价人：分数，意见）
            $personalOpinions = [];
            foreach ($taskPersonalScores as $ps) {
                if (!empty($ps['opinion'])) {
                    $targetName = $targetUsersInfo[$ps['target_user_id']]['name'] ?? $ps['target_user_id'];
                    $score = $ps['score'];
                    $personalOpinions[] = "{$targetName}：{$score}分，{$ps['opinion']}";
                }
            }
            $personalOpinion = implode("\n\n", $personalOpinions);

            $scorers[] = [
                'scorer_id' => $scorerId,
                'scorer_name' => $scorerInfo ? $scorerInfo['name'] : '',
                'scores' => $scores,
                'dept_opinion' => $deptOpinion,
                'personal_opinion' => $personalOpinion
            ];
        }

        return [
            'message' => '',
            'data' => [
                'year' => $year,
                'quarter' => $quarter,
                'departments' => $departments,
                'avg_scores' => $deptAvgScores,
                'scorers' => $scorers
            ]
        ];
    }

    /**
     * 导出打印数据（Excel）
     */
    public function actionExportprintdata()
    {
        $year = Yii::$app->request->get('year', date('Y'));
        $quarter = Yii::$app->request->get('quarter', ceil(date('n') / 3));

        // 获取被考评部门配置
        $deptConfig = FzrbsBudgetDict::findOne(['type' => '被考评部门', 'label' => '被考评部门']);
        if (!$deptConfig || empty($deptConfig->dept)) {
            echo '未配置被考评部门';
            exit;
        }
        $deptIds = array_map('intval', explode(',', $deptConfig->dept));

        // 获取所有部门信息
        $departments = WeixinOaDepartment::find()
            ->select(['id', 'name'])
            ->where(['id' => $deptIds])
            ->orderBy('id')
            ->asArray()
            ->all();

        $departmentsById = array_column($departments, null, 'id');

        // 获取该年份/季度的所有任务
        $tasks = FzrbsEvaluationTask::find()
            ->alias('t')
            ->leftJoin('weixin_oa_flowrole fr', 't.scorer_id = fr.userid AND fr.role = (SELECT id FROM weixin_oa_role WHERE rolename = "部门考评人")')
            ->where(['t.year' => $year, 't.quarter' => $quarter])
            ->orderBy('fr.seq ASC')
            ->all();

        // 一次性查询所有相关的评分记录
        $taskIds = array_column($tasks, 'id');
        $mainScores = FzrbsEvaluationScore::find()
            ->where(['task_id' => $taskIds])
            ->asArray()
            ->all();

        // 计算每个部门的平均分
        $deptScoreSum = [];
        $deptScoreCount = [];
        foreach ($mainScores as $score) {
            $deptId = $score['dept_id'];
            if (!isset($deptScoreSum[$deptId])) {
                $deptScoreSum[$deptId] = 0;
                $deptScoreCount[$deptId] = 0;
            }
            $deptScoreSum[$deptId] += intval($score['score']);
            $deptScoreCount[$deptId]++;
        }
        $deptAvgScores = [];
        foreach ($departments as $dept) {
            $deptId = $dept['id'];
            $avgScore = ($deptScoreCount[$deptId] ?? 0) > 0
                ? round($deptScoreSum[$deptId] / $deptScoreCount[$deptId], 1)
                : null;
            $deptAvgScores[$deptId] = $avgScore;
        }

        // 一次性查询所有评分人信息
        $scorerIds = array_column($tasks, 'scorer_id');
        $scorersInfo = WeixinOAUserInfo::find()
            ->select(['userid', 'name'])
            ->where(['userid' => $scorerIds])
            ->indexBy('userid')
            ->asArray()
            ->all();

        // 按 task_id 分组评分记录
        $scoresByTask = [];
        foreach ($mainScores as $score) {
            $taskId = $score['task_id'];
            if (!isset($scoresByTask[$taskId])) {
                $scoresByTask[$taskId] = [];
            }
            $scoresByTask[$taskId][$score['dept_id']] = $score;
        }

        // 构建Excel数据
        $data = [];

        // 表头行1：空 + 部门名称 + 部门意见 + 个人意见
        $header1 = [''];
        foreach ($departments as $dept) {
            $header1[] = $dept['name'];
        }
        $header1[] = '部门意见';
        $header1[] = '个人意见';
        $data[] = $header1;

        // 表头行2：服务对象/考评部门 + 各部门平均分
        $header2 = ['评分人'];
        foreach ($departments as $dept) {
            $avg = $deptAvgScores[$dept['id']] ?? null;
            $header2[] = $avg !== null ? "{$avg}分" : '-';
        }
        $header2[] = '';
        $header2[] = '';
        $data[] = $header2;

        // 数据行
        foreach ($tasks as $task) {
            $scorerId = $task->scorer_id;
            $scorerInfo = $scorersInfo[$scorerId] ?? null;
            $taskScores = $scoresByTask[$task->id] ?? [];

            $row = [$scorerInfo ? $scorerInfo['name'] : ''];
            foreach ($departments as $dept) {
                $deptId = $dept['id'];
                $scoreRecord = $taskScores[$deptId] ?? null;
                $row[] = $scoreRecord ? "{$scoreRecord['score']}分" : '-';
            }

            // 汇总部门意见
            $deptOpinions = [];
            foreach ($taskScores as $deptId => $score) {
                if (!empty($score['opinion'])) {
                    $deptName = $departmentsById[$deptId]['name'] ?? $deptId;
                    $deptOpinions[] = "{$deptName}：{$score['opinion']}";
                }
            }
            $row[] = implode("\n", $deptOpinions);

            // 汇总个人意见（这里简化处理，实际可能需要单独查询）
            $row[] = '';

            $data[] = $row;
        }

        $filename = "行政后勤部门考评汇总_{$year}年第{$quarter}季度";

        return $this->_exportExcel($data, $filename);
    }

    /**
     * 获取考评操作日志
     */
    public function actionGetoperationlogs()
    {
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);

        $catalogs = [
            '生成考评任务', '删除考评任务', '超时自动评分',
            '扣罚录入', '计算扣款', '生成50分预警', '导出预警名单',
            '批量提交评分', '提交个人评分', '删除个人评分',
            '提交评分', '删除评分', '计算扣款分摊',
            '新增考评字典', '更新考评字典', '删除考评字典',
        ];

        $query = FzrbsOperationLog::find()
            ->select('id,catalog,remark,realname,inserttime,username')
            ->where(['in', 'catalog', $catalogs])
            ->orderBy('inserttime desc');

        $total = $query->count();
        $res = $query->limit($limit)->offset($offset)->asArray()->all();

        return ['data' => $res, 'total' => $total];
    }

    // ========== 私有方法 ==========

    /**
     * 获取有效的分数列表
     */
    private function _getValidScores()
    {
        $configs = FzrbsBudgetDict::find()->where(['type' => '评分档次'])->all();
        $scores = [];
        foreach ($configs as $config) {
            $scores[] = intval($config->value);
        }
        return $scores;
    }

    /**
     * 检查是否超过评分截止日期（每月10日）
     */
    protected function _isDeadlinePassed()
    {
        return date('j') > 10;
    }

    /**
     * 获取截止日期检查错误信息
     * @param string $action 操作类型：修改/删除，默认修改
     * @return array|null 如果超过截止日期返回错误数组，否则返回null
     */
    protected function _checkDeadline($action = '修改')
    {
        if ($this->_isDeadlinePassed()) {
            return ['message' => "每月10日之后无法{$action}评分"];
        }
        return null;
    }

    /**
     * 检查部门评分是否已提交
     */
    protected function _isDeptScoreSubmitted($taskId, $deptId)
    {
        return FzrbsEvaluationScore::find()
            ->where(['task_id' => $taskId, 'dept_id' => $deptId])
            ->exists();
    }

    /**
     * 统一返回格式
     */
    private function _result($data = null, $message = null)
    {
        $result = ['success' => true];

        if ($data !== null) {
            $result['data'] = $data;
        }

        if ($message !== null) {
            $result['message'] = $message;
        }

        return $result;
    }

    /**
     * 导出Excel
     */
    private function _exportExcel($data, $filename)
    {
        // 使用现有的Excel导出方式
        $phpExcelPath = Yii::$app->basePath . '/../vendor/phpoffice/phpexcel/Classes/PHPExcel';
        if (!is_dir($phpExcelPath)) {
            $phpExcelPath = Yii::$app->basePath . '/../vendor/phpoffice/php spreadsheet/Classes/PHPExcel';
        }

        // 简单实现，实际使用时请根据项目现有的Excel导出方式
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
        header('Cache-Control: max-age=0');

        $str = '';
        foreach ($data as $row) {
            $str .= implode("\t", $row) . "\n";
        }

        echo $str;
        exit;
    }

    /**
     * 获取评分人排序列表
     */
    public function actionGetevaluatorseq()
    {
        $roleId = WeixinOaRole::find()->where(['rolename' => '部门考评人'])->select('id')->scalar();
        if (!$roleId) {
            return ['message' => '系统中不存在"部门考评人"角色'];
        }

        $evaluators = WeixinOaFlowrole::find()
            ->alias('fr')
            ->leftJoin(['u' => WeixinOAUserInfo::tableName()], 'fr.userid = u.userid')
            ->where(['fr.role' => $roleId])
            ->orderBy('fr.seq ASC')
            ->select(['fr.userid', 'u.name', 'fr.seq'])
            ->asArray()
            ->all();

        return ['data' => $evaluators];
    }

    /**
     * 保存评分人排序
     */
    public function actionSaveevaluatorseq()
    {
        $orders = $this->_request['orders'] ?? [];
        if (empty($orders)) {
            return ['message' => '参数orders不能为空'];
        }

        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();

        try {
            foreach ($orders as $order) {
                $db->createCommand()
                    ->update('weixin_oa_flowrole', ['seq' => $order['seq']], ['userid' => $order['userid']])
                    ->execute();
            }
            $transaction->commit();
            return ['message' => ''];
        } catch (\Exception $e) {
            $transaction->rollBack();
            return ['message' => '保存失败：' . $e->getMessage()];
        }
    }
}
