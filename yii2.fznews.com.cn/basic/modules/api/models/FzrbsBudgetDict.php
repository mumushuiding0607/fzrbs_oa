<?php

namespace app\modules\api\models;

use Yii;


class FzrbsBudgetDict extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_budget_dict';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type','value'], 'string', 'max' => 80],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'value' => 'Value'
        ];
    }
}