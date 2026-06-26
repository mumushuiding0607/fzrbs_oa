<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_leave_auth".
 *
 * @property int $id
 * @property string|null $userId
 * @property string|null $userName
 * @property string|null $modules 可操作模块
 * @property string|null $departments 可操作部门
 * @property int $agentid
 */
class WeixinOaAuth extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_oa_auth';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['authName','agentid'], 'required', 'message' => '{attribute}必填', 'on' => ['create','update']],
            [['authName'], 'string', 'max' => 50],
            [['sysusers','wxusers','departments'], 'string', 'max' => 1000],
            [['modules','actions'], 'string', 'max' => 255],
            [['agentid'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'authName' => 'Auth Name',
            'sysusers' => 'Sysusers',
            'wxusers' => 'Wxusers',
            'modules' => 'Modules',
            'departments' => 'Departments',
            'actions' => 'Actions',
            'agentid' => 'Agentid',
        ];
    }
}
