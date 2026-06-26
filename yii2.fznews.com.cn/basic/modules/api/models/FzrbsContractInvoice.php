<?php

namespace app\modules\api\models;

use Yii;


class FzrbsContractInvoice extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_contract_invoice';
    }
}