<?php

namespace app\modules\api\models;

use Yii;

/**
 * 评分记录表
 * @property int $id
 * @property int $task_id 考评任务ID
 * @property int $dept_id 被考评部门ID
 * @property int $score 分数 95/85/70/60/50
 * @property string $opinion 意见建议
 * @property int $is_personal 是否个人评分
 * @property string $target_user_id 个人评分时目标员工ID
 * @property string $target_opinion 个人批评意见
 * @property string $created_at 创建时间
 */
class FzrbsEvaluationScore extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_evaluation_score';
    }



    /**
     * 获取任务
     */
    public function getTask()
    {
        return $this->hasOne(FzrbsEvaluationTask::className(), ['id' => 'task_id']);
    }
}
