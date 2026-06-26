<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_leave_template".
 *
 * @property int $id
 * @property string|null $templateid
 * @property string|null $templatename
 * @property int|null $level
 * @property int|null $is_company
 * @property string|null $dids
 * @property string|null $uids
 * @property float|null $min
 * @property int|null $max
 * @property int|null $type 0：请假；1：销假；2：逾期销假
 * @property int|null $agentid
 * @property int|null $isdel
 */
class WeixinLeaveTemplate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_leave_template';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['templateid', 'level', 'type'], 'required', 'message' => '{attribute}必填', 'on' => ['create','update']],
            [['level', 'is_company', 'min', 'max', 'type', 'agentid', 'isdel'], 'integer'],
            [['templateid', 'templatename'], 'string', 'max' => 100],
            [['dids', 'uids'], 'string', 'max' => 250],
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
            'level' => 'Level',
            'is_company' => 'Is Company',
            'dids' => 'Dids',
            'uids' => 'Uids',
            'min' => 'Min',
            'max' => 'Max',
            'type' => 'Type',
            'agentid' => 'Agentid',
            'isdel' => 'Isdel',
        ];
    }
}
