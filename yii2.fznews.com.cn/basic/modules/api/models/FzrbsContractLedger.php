<?php

namespace app\modules\api\models;

use Yii;


class FzrbsContractLedger extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_contract_ledger';
    }
    public function getTypename()
    {
        return $this->hasOne(FzrbsBudgetDict::class, ['value' => 'type'])
            ->onCondition(['fzrbs_budget_dict.type' => '采购类别']);
    }
 


    public function getMethod()
    {
        return $this->hasOne(FzrbsBudgetDict::class, ['value' => 'method'])
            ->onCondition(['fzrbs_budget_dict.type' => '采购方式']);
    }

    public function getResultid()
    {
        return $this->hasOne(FzrbsBudgetDict::class, ['value' => 'resultid'])
            ->onCondition(['fzrbs_budget_dict.type' => '验收结果']);
    }
}