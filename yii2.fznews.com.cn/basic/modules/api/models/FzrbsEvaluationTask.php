<?php

namespace app\modules\api\models;

use Yii;

/**
 * 季度考评任务表
 * @property int $id
 * @property int $year 年份
 * @property int $quarter 季度(1-4)
 * @property string $start_date 开始日期
 * @property string $end_date 截止日期
 * @property string $scorer_id 评分人ID
 * @property string $dept_ids 被考评部门IDs（逗号分隔）
 * @property int $status 0未打分 1已打分 2超时默认95分
 * @property string $submitted_at 提交时间
 * @property string $created_at 创建时间
 */
class FzrbsEvaluationTask extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_evaluation_task';
    }




    /**
     * 获取评分人信息
     */
    public function getScorer()
    {
        return $this->hasOne(WeixinOAUserInfo::className(), ['userid' => 'scorer_id']);
    }

    /**
     * 获取被考评部门
     */
    public function getDepartment()
    {
        return $this->hasOne(WeixinOaDepartment::className(), ['id' => 'dept_id']);
    }
}
