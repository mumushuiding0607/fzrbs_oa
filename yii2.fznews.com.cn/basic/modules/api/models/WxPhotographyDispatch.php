<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_photography_dispatch".
 *
 * @property int $id
 * @property string $dispatch_userid 派工userid
 * @property string|null $dispatch_name 派工名字
 * @property string|null $opt_userid 申请人userid
 * @property string|null $opt_name 申请人名字
 * @property int $begin_time 派工开始时间
 * @property int $end_time 派工结束时间
 * @property int $st 1撤回 2 驳回（作废） 3 审核中 4 任务中 5任务结束
 * @property string|null $created 派工创建时间
 * @property string|null $updated 派工修改时间
 * @property string|null $reason 派工事由
 * @property string|null $order_no 编号
 * @property string|null $command 评论
 * @property int $grade 得分

 */
class WxPhotographyDispatch extends \yii\db\ActiveRecord
{
    public $_orderSt = [1=>'撤回','驳回','审核中','任务中','结束'];
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_photograph_dispatch';//摄影派工表 weixin_photograph_dispatch weixin_photography_dispatch
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['dispatch_userid','dispatch_name','opt_userid','opt_name','begin_time','end_time','st','order_no','reason'], 'required', 'message' => '{attribute}必填', 'on' => ['create', 'update']],
            [['begin_time', 'end_time', 'st', 'grade'], 'integer'],
            [['command', 'created','updated','reason'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'dispatch_userid' => '记者',
            'dispatch_name' => '记者',
            'opt_userid' => '申请人',
            'opt_name' => '申请人',
            'begin_time' => '开始时间',
            'end_time' => '结束时间',
            'created' => '创建时间',
            'updated' => '更新时间',
            'st' => '状态',
            'reason' => '派工事由',
            'order_no' => '审批编号',
            'command' => '评语',
            'grade' => '分值',
        ];
    }
}
