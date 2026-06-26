<?php

namespace app\modules\api\models;

use Yii;

// 指标设置
class WeixinFinanceTemplate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_finance_template';
    }
}