<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oa_approvaldata".
 *
 * @property int $id
 * @property int $agentid
 * @property string $thirdNo
 * @property string|null $data
 * @property int|null $step
 * @property int|null $status 0 1-审批中；2-已通过；3-已驳回；4-已取消；
 * @property int|null $notifyAttr
 * @property string|null $inserttime
 */
class WeixinOaApprovaldata extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_oa_approvaldata';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['agentid', 'thirdNo'], 'required'],
            [['agentid', 'step', 'status', 'notifyAttr'], 'integer'],
            [['data'], 'string'],
            [['inserttime'], 'safe'],
            [['thirdNo'], 'string', 'max' => 20],
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
            'data' => 'Data',
            'step' => 'Step',
            'status' => 'Status',
            'notifyAttr' => 'Notify Attr',
            'inserttime' => 'Inserttime',
        ];
    }
}
