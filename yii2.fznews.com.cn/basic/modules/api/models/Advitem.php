<?php

namespace app\modules\api\models;

use Yii;


class Advitem extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'advitem';
    }
}