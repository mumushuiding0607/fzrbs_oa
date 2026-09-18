<?php

namespace app\modules\api\models;

use Yii;

class ResEvaluation extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'res_evaluation';
    }

    public static function primaryKey()
    {
        return ['eId'];
    }
}
