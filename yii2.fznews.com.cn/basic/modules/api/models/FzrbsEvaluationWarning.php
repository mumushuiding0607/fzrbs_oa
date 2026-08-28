<?php

namespace app\modules\api\models;

use Yii;

/**
 * 50分预警表
 * @property int $id
 * @property int $year 年份
 * @property string $user_id 被预警员工ID
 * @property string $user_name 员工姓名
 * @property string $cycle_ids 触发预警的周期（如2024Q1,2024Q2）
 * @property int $score_50_count 累计50分次数
 * @property int $warning_level 1预警 2调离
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 */
class FzrbsEvaluationWarning extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_evaluation_warning';
    }


}
