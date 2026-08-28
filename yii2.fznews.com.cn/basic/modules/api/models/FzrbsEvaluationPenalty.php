<?php

namespace app\modules\api\models;

use Yii;

/**
 * 扣款计算结果表
 * @property int $id
 * @property int $year 年份
 * @property int $quarter 季度
 * @property int $dept_id 部门ID
 * @property string $dept_name 部门名称
 * @property float $avg_score 部门平均分
 * @property float $total_deduct 部门总扣款
 * @property string $created_at 创建时间
 */
class FzrbsEvaluationPenalty extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_evaluation_penalty';
    }

   
}
