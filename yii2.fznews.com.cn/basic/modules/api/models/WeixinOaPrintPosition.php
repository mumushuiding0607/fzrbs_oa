<?php

namespace app\modules\api\models;

use vierbergenlars\SemVer\expression;
use Yii;


class WeixinOaPrintPosition extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_oa_print_position';
    }
    public function getValuename()
    {
        

        return $this->hasOne(WeixinOaRole::class, ['id' => 'value']);
    }
}