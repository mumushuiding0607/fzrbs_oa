<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_property_extend_val".
 *
 * @property int $id
 * @property int $property_id
 * @property int $extend_id property_extend 表id
 * @property string $extend_val 扩展内容
 * @property string $created
 */
class WeixinPropertyExtendVal extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_property_extend_val';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['property_id', 'extend_id', 'extend_val', 'created'], 'required','on'=>['update','create']],
            [['property_id', 'extend_id'], 'integer'],
            [['created'], 'safe'],
            [['extend_val'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'property_id' => 'Property ID',
            'extend_id' => 'Extend ID',
            'extend_val' => 'Extend Val',
            'created' => 'Created',
        ];
    }

}
