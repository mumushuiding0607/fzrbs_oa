<?php

namespace app\modules\api\models;

use Yii;

/**
 * 扣款结果表
 * @property int $id
 * @property int $year 年份
 * @property int $quarter 季度
 * @property int $dept_id 部门ID
 * @property string $user_id 员工ID
 * @property string $user_name 员工姓名
 * @property float $deduct_amount 扣罚金额
 * @property int $is_personal 是否个人追责(1=是)
 * @property string $created_at 创建时间
 */
class FzrbsEvaluationPenaltyResult extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_evaluation_penalty_result';
    }

}
