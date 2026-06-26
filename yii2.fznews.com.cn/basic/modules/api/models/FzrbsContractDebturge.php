<?php

namespace app\modules\api\models;

use Yii;


class FzrbsContractDebturge extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_contract_debturge';
    }
    public function getUrgetypename()
    {
        return $this->hasOne(FzrbsBudgetDict::class, ['value' => 'urgetype'])
            ->onCondition(['fzrbs_budget_dict.type' => '清欠方式']);
    }
    public function getUrgeresultname()
    {
        return $this->hasOne(FzrbsBudgetDict::class, ['value' => 'urgeresult'])
            ->onCondition(['fzrbs_budget_dict.type' => '清欠结果']);
    }
}