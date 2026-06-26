<?php

namespace app\modules\api\models;

use Yii;


class FzrbsInvoicingItem extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_invoicing_item';
    }
}