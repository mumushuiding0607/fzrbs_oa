<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oa_approval_log".
 *
 * @property int $id
 * @property int $agentid
 * @property string $thirdNo
 * @property string|null $userId
 * @property string|null $userName
 * @property int|null $status 2-已通过；3-已驳回；
 * @property string|null $speech
 * @property int|null $opTime
 */
class WeixinOaApprovalLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_oa_approval_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['agentid', 'thirdNo'], 'required', 'message' => '{attribute}必填', 'on' => ['create']],
            [['agentid', 'thirdNo'], 'required'],
            [['agentid', 'status', 'opTime'], 'integer'],
            [['thirdNo', 'userId'], 'string', 'max' => 20],
            [['userName'], 'string', 'max' => 50],
            [['speech'], 'string', 'max' => 250],
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
            'status' => 'Status',
            'speech' => 'Speech',
            'opTime' => 'Op Time',
        ];
    }
}
