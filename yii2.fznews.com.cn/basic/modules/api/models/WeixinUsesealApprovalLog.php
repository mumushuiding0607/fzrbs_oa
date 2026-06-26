<?php

namespace app\modules\api\models;

use Yii;


class WeixinUsesealApprovalLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_useseal_approval_log';
    }
}