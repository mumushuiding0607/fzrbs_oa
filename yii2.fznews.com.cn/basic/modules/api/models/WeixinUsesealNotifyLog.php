<?php

namespace app\modules\api\models;

use Yii;


class WeixinUsesealNotifyLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_useseal_notify_log';
    }
}