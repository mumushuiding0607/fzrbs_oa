<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_qy_appinterface".
 *
 * @property int $id
 * @property string|null $appname 应用名称
 * @property int|null $appid 应用id
 * @property string|null $corpid 企业号id
 * @property string|null $secret 应用秘钥
 * @property string|null $url 接口地址
 * @property string|null $token 接口Token
 * @property string|null $encodingaeskey 接口EncodingAESKey
 * @property int|null $checkin 状态(0:未接入过,1:已接入)
 * @property string $inserttime 添加时间
 * @property string|null $sid
 */
class WeixinQYAppInterface extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_qy_appinterface';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['appname', 'corpid', 'secret', 'url', 'token', 'sid'], 'required', 'message' => '{attribute}必填', 'on' => ['create', 'update']],
            [['appname', 'corpid', 'secret', 'url', 'token', 'sid'], 'trim'],
            [['appid', 'checkin'], 'integer'],
            [['inserttime'], 'safe'],
            [['appname', 'corpid', 'secret', 'url', 'token', 'sid'], 'string', 'max' => 250],
            [['encodingaeskey'], 'string', 'max' => 600],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'appname' => 'Appname',
            'appid' => 'Appid',
            'corpid' => 'Corpid',
            'secret' => 'Secret',
            'url' => 'Url',
            'token' => 'Token',
            'encodingaeskey' => 'Encodingaeskey',
            'checkin' => 'Checkin',
            'inserttime' => 'Inserttime',
            'sid' => 'Sid',
        ];
    }
}
