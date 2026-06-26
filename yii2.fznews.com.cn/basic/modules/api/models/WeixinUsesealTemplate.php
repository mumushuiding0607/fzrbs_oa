<?php

namespace app\modules\api\models;

use Yii;


class WeixinUsesealTemplate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_useseal_template';
    }
    public function getTypename()
    {
        return $this->hasOne(FzrbsBudgetDict::class, ['value' => 'type'])
            ->onCondition(['fzrbs_budget_dict.type' => '用印申请类别']);
    }
    
}

