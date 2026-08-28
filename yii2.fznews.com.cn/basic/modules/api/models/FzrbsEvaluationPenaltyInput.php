<?php

namespace app\modules\api\models;

use Yii;

/**
 * 扣罚录入表
 * @property int $id
 * @property int $year 年份
 * @property int $quarter 季度
 * @property int $dept_id 部门ID
 * @property string $dept_name 部门名称
 * @property float $total_deduct 部门总扣罚金额
 * @property int $dept_person_count 部门总人数
 * @property string $input_by 录入人
 * @property string $created_at 创建时间
 */
class FzrbsEvaluationPenaltyInput extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_evaluation_penalty_input';
    }

   

    /**
     * 获取个人扣罚列表
     */
    public function getPersons()
    {
        return $this->hasMany(FzrbsEvaluationPenaltyPerson::className(), ['input_id' => 'id']);
    }
}
