<?php

namespace app\modules\api\models;

use Yii;


class FzrbsBudgetTemplate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_budget_template';
    }
}