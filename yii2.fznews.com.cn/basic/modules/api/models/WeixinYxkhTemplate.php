<?php

namespace app\modules\api\models;

use Yii;

// 指标设置
class WeixinYxkhTemplate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_yxkh_template';
    }
}