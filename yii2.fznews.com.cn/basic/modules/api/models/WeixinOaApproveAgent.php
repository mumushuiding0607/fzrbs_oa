<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oa_approve_agent".
 *
 * @property int $id
 * @property int|null $principal_userid 被代理人userid
 * @property string|null $principal_name 被代理人姓名
 * @property string|null $proxy_userid 代理人userid
 * @property string|null $proxy_name 代理人姓名
 * @property string|null $start_time 代理开始时间
 * @property string|null $end_time 代理结束时间
 * @property string|null $agent 关联应用
 */
class WeixinOaApproveAgent extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_oa_approve_agent';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['principal_userid', 'proxy_userid'], 'string', 'max' => 20, 'on' => ['create', 'update']],
            [['principal_name','proxy_name'], 'string', 'max' => 50],
            [['agent'], 'string', 'max' => 255],
            [['start_time','end_time'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'principal_userid' => 'Principal userid',
            'proxy_userid' => 'Proxy userid',
            'principal_name' => 'Principal name',
            'proxy_name' => 'Proxy name',
            'agent' => 'Agent',
            'start_time' => 'Start time',
            'end_time' => 'End time',
            'inserttime' => 'Inserttime',
        ];
    }
}
