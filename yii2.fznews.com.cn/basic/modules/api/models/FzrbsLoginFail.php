<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "fzrbs_login_fail".
 *
 * @property string $ip 后台用户登录ip
 * @property int $count 后台用户登录次数
 * @property int $lastupdate 后台用户登录时间
 */
class FzrbsLoginFail extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_login_fail';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ip'], 'required'],
            [['count', 'lastupdate'], 'integer'],
            [['ip'], 'string', 'max' => 15],
            [['ip'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'ip' => 'Ip',
            'count' => 'Count',
            'lastupdate' => 'Lastupdate',
        ];
    }
}
