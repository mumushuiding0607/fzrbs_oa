<?php

namespace app\modules\api\models;

use Yii;


class FzrbsBudgetProject extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_budget_project';
    }
}