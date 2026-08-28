<?php

namespace app\modules\api\models;

use Yii;

/**
 * 个人评分记录表
 * @property int $id
 * @property int $task_id 任务ID
 * @property int $dept_id 部门ID
 * @property string $target_user_id 被评价人ID
 * @property int $score 评分
 * @property string $opinion 意见建议
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class FzrbsEvaluationPersonalScore extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_evaluation_personal_score';
    }

   

    /**
     * 获取任务
     */
    public function getTask()
    {
        return $this->hasOne(FzrbsEvaluationTask::className(), ['id' => 'task_id']);
    }

    /**
     * 获取被评价人
     */
    public function getTargetUser()
    {
        return $this->hasOne(WeixinOAUserInfo::className(), ['userid' => 'target_user_id']);
    }
}
