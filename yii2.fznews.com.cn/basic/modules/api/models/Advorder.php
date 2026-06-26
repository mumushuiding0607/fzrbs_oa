<?php

namespace app\modules\api\models;

use Yii;


class Advorder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'advorder';
    }
}