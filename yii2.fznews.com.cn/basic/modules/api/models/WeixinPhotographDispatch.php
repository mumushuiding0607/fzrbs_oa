<?php

namespace app\modules\api\models;

use Yii;


class WeixinPhotographDispatch extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_photograph_dispatch';
    }
}