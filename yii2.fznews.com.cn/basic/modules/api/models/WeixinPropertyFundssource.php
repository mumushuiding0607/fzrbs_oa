<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_property_fundssource".
 *
 * @property int $id
 * @property string|null $title
 * @property int|null $st
 */
class WeixinPropertyFundssource extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_property_fundssource';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['st'], 'integer','on'=>['update','create']],
            [['title'], 'string', 'max' => 50,'on'=>['update','create']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'st' => 'St',
        ];
    }

}
