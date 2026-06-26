<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_truck_template".
 *
 * @property int $id
 * @property string|null $templateid
 * @property string|null $templatename
 * @property int|null $type 0：普通派车；1：领导专车；
 * @property int|null $attr 0：报社派车；1：工程派车；
 * @property string|null $dids
 * @property string|null $uids
 * @property int|null $agentid
 * @property int|null $isdel
 */
class WeixinTruckTemplate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_truck_template';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'attr', 'agentid', 'isdel'], 'integer','on'=>['update','create']],
            [['templateid', 'templatename'], 'string', 'max' => 100,'on'=>['update','create']],
            [['dids', 'uids'], 'string', 'max' => 250,'on'=>['update','create']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'templateid' => 'Templateid',
            'templatename' => 'Templatename',
            'type' => 'Type',
            'attr' => 'Attr',
            'dids' => 'Dids',
            'uids' => 'Uids',
            'agentid' => 'Agentid',
            'isdel' => 'Isdel',
        ];
    }

}
