<?php

namespace app\modules\api\models;

use Yii;


class WeixinUsesealApprovaldata extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_useseal_approvaldata';
    }
}