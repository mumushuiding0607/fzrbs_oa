<?php

namespace app\modules\api\models;

use Yii;


class FzrbsContract extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_invoice';
    }
}