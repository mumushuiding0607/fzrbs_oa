<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "fzrbs_role".
 *
 * @property int $id
 * @property string $name 角色名称
 * @property string|null $usernames 角色包含用户名
 * @property string|null $routes 角色包含路由菜单id
 * @property string|null $channels 角色包含企业微信平台栏目id
 * @property string $inserttime 添加时间
 */
class FzrbsRole extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_role';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required', 'message' => '{attribute}必填', 'on' => ['create', 'update']],
            [['name'], 'trim'],
            [['usernames', 'routes', 'channels'], 'string'],
            [['inserttime'], 'safe'],
            [['name'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'usernames' => 'Usernames',
            'routes' => 'Routes',
            'channels' => 'Channels',
            'inserttime' => 'Inserttime',
        ];
    }
}
