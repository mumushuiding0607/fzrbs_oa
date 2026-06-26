<?php

namespace app\modules\weixin\models;

use Yii;

/**
 * This is the model class for table "fzrbs_qyhyy_access_token".
 *
 * @property int $id
 * @property string|null $corpid 企业号id
 * @property string|null $secret 应用secret
 * @property string|null $token token
 * @property int|null $expires 过期时间
 * @property string $inserttime 添加时间
 */
class FzrbsQyhyyAccessToken extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_qyhyy_access_token';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('wxdb');
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['expires'], 'integer'],
            [['inserttime'], 'safe'],
            [['corpid', 'secret'], 'string', 'max' => 250],
            [['token'], 'string', 'max' => 600],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'corpid' => 'Corpid',
            'secret' => 'Secret',
            'token' => 'Token',
            'expires' => 'Expires',
            'inserttime' => 'Inserttime',
        ];
    }
}
