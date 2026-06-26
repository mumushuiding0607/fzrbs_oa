<?php

namespace app\modules\api\models;

use Yii;


class FzrbsBudgetBalance extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_budget_balance';
    }
}