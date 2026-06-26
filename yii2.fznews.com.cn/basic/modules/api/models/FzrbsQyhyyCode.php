<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "fzrbs_qyhyy_code".
 *
 * @property int $id
 * @property string|null $appid 企业号应用id
 * @property int|null $mobile 手机号
 * @property int|null $code 验证码
 * @property int|null $sendtime 发送时间
 */
class FzrbsQyhyyCode extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_qyhyy_code';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['mobile', 'code', 'sendtime'], 'integer'],
            [['appid'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'appid' => 'Appid',
            'mobile' => 'Mobile',
            'code' => 'Code',
            'sendtime' => 'Sendtime',
        ];
    }
}
