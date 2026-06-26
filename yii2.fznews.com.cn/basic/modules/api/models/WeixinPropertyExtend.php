<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_property_extend".
 *
 * @property int $id
 * @property int|null $ptpid 资产类别
 * @property string|null $ext_name 扩展项名称
 * @property string|null $ext_alias 扩展项别名
 * @property int $ext_order 排序
 * @property int $st 0 无效 1 有效
 * @property int $is_require 0 否 1 是
 */
class WeixinPropertyExtend extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_property_extend';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ptpid', 'ext_order', 'st', 'is_require'], 'integer','on'=>['update','create']],
            [['ext_name', 'ext_alias'], 'string', 'max' => 50,'on'=>['update','create']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ptpid' => 'Ptpid',
            'ext_name' => 'Ext Name',
            'ext_alias' => 'Ext Alias',
            'ext_order' => 'Ext Order',
            'st' => 'St',
            'is_require' => 'Is Require',
        ];
    }
}
