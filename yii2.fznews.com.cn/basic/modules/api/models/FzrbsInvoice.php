<?php

namespace app\modules\api\models;

use Yii;


class FzrbsInvoice extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_invoice';
    }
}