<?php

namespace app\modules\api\models;

use Yii;

// 指标设置
class FzrbsBudgetTarget extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_budget_target';
    }
}