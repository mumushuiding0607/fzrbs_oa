<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_advsize".
 *
 * @property int $SYS_DOCUMENTID
 * @property string $E_Name 规格名称
 * @property int $E_AdType_ID 广告类型ID
 * @property float $E_Width 宽度
 * @property float $E_Height 高度
 * @property int $E_LayoutAmount 版数
 * @property int $SYS_DELETEFLAG 删除标志
 * @property string $SYS_AUTHORS 创建者
 * @property string $SYS_CREATED 创建时间
 * @property string $SYS_LASTMODIFIED 最后修改时间
 */
class WeixinAdvsize extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_advsize';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['E_Name'], 'required'],
            [['E_AdType_ID', 'E_LayoutAmount', 'SYS_DELETEFLAG'], 'integer'],
            [['E_Width', 'E_Height'], 'number'],
            [['SYS_CREATED', 'SYS_LASTMODIFIED'], 'safe'],
            [['E_Name'], 'string', 'max' => 100],
            [['SYS_AUTHORS'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'SYS_DOCUMENTID' => 'ID',
            'E_Name' => '规格名称',
            'E_AdType_ID' => '广告类型ID',
            'E_Width' => '宽度',
            'E_Height' => '高度',
            'E_LayoutAmount' => '版数',
            'SYS_DELETEFLAG' => '删除标志',
            'SYS_AUTHORS' => '创建者',
            'SYS_CREATED' => '创建时间',
            'SYS_LASTMODIFIED' => '最后修改时间',
        ];
    }
}