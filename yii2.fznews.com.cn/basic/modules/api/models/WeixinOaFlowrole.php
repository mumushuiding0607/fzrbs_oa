<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oa_flowrole".
 *
 * @property int $id
 * @property int|null $role 角色ID
 * @property string|null $userid
 * @property string|null $username
 * @property string|null $level 职级组合
 * @property string|null $company 主体组合
 * @property string|null $dept 部门组合
 * @property string|null $agent 应用组合
 * @property int|null $type 0-审批，1-抄送
 */
class WeixinOaFlowrole extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_oa_flowrole';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['role', 'type'], 'integer'],
            [['userid'], 'string', 'max' => 20],
            [['username'], 'string', 'max' => 50],
            [['level', 'company', 'agent'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'role' => 'Role',
            'userid' => 'Userid',
            'username' => 'Username',
            'level' => 'Level',
            'company' => 'Company',
            'dept' => 'Dept',
            'agent' => 'Agent',
            'type' => 'Type',
        ];
    }
}
