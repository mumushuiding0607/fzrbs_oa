<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "fzrbs_login_log".
 *
 * @property int $id
 * @property string $username 登录账号
 * @property string $realname 登录账号姓名
 * @property string|null $logintype 登录方式
 * @property string $ip 登录ip
 * @property string|null $logtype 日志类型
 * @property string|null $remark 备注
 * @property string $inserttime 登录退出时间
 */
class FzrbsLoginLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_login_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['inserttime'], 'safe'],
            [['username', 'realname'], 'string', 'max' => 50],
            [['logintype', 'logtype', 'remark'], 'string', 'max' => 255],
            [['ip'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'realname' => 'Realname',
            'logintype' => 'Logintype',
            'ip' => 'Ip',
            'logtype' => 'Logtype',
            'remark' => 'Remark',
            'inserttime' => 'Inserttime',
        ];
    }
}
