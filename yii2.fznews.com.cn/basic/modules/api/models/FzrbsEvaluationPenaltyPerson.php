<?php

namespace app\modules\api\models;

use Yii;

/**
 * 个人扣罚表
 * @property int $id
 * @property int $input_id 录入ID
 * @property string $user_id 员工ID
 * @property string $user_name 员工姓名
 * @property int $deduct_type 扣罚方式: 0=岗位绩效×30%, 1=固定金额
 * @property float $post_performance 岗位绩效(方式0用)
 * @property float $fixed_deduct 固定扣罚金额(方式1用)
 * @property string $created_at 创建时间
 */
class FzrbsEvaluationPenaltyPerson extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_evaluation_penalty_person';
    }

   

    /**
     * 获取录入记录
     */
    public function getInput()
    {
        return $this->hasOne(FzrbsEvaluationPenaltyInput::className(), ['id' => 'input_id']);
    }

    /**
     * 计算个人扣罚金额
     */
    public function getPersonalDeduct()
    {
        if ($this->deduct_type == 0) {
            // 岗位绩效×30%
            return $this->post_performance * 0.3;
        } else {
            // 固定金额
            return $this->fixed_deduct;
        }
    }
}
