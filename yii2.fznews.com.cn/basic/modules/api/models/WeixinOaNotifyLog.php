<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oa_notify_log".
 *
 * @property int $id
 * @property int $agentid
 * @property string|null $thirdNo
 * @property string|null $userId
 * @property string|null $userName
 * @property string|null $inserttime
 */
class WeixinOaNotifyLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_oa_notify_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['agentid','thirdNo', 'userId'], 'required', 'message' => '{attribute}必填', 'on' => ['create']],
            [['agentid'], 'integer'],
            [['inserttime'], 'safe'],
            [['thirdNo', 'userId'], 'string', 'max' => 20],
            [['userName'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'agentid' => 'Agentid',
            'thirdNo' => 'Third No',
            'userId' => 'User ID',
            'userName' => 'User Name',
            'inserttime' => 'Inserttime',
        ];
    }
}
