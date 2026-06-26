<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_truck_order".
 *
 * @property int $id
 * @property string $order_no 审批编号
 * @property string $companyUserid 同行人
 * @property string $companyNames 同行人员
 * @property int $order_tp 派车类型 1 普通派车 2 专车
 * @property string $opt_userid 申请人
 * @property string $opt_name 申请人名称
 * @property string $created 创建时间
 * @property int $updated 修改时间
 * @property int $start_place 出发地
 * @property string $destination 目的地
 * @property int $tp 车辆类型
 * @property string $start_time 派车开始时间
 * @property string $end_time 结束时间
 * @property string $dep_name 部门
 * @property int $dep_id 所在部门
 * @property string $driver_name 司机用户名
 * @property string $driver 司机userid
 * @property string $driver_mobile 司机电话号码
 * @property int $car_id 车辆id
 * @property string $car_licence 车牌
 * @property int $start_mile 出发里程
 * @property int $end_mile 结束里程
 * @property int $mile 本次里程
 * @property string $park_fee 停车费
 * @property int $toll 过路费
 * @property string $total_fee 总费用
 * @property int $st 状态-1 撤销 0 驳回 1 待审核 2 任务中 3 任务暂时保存 4任务结束 5确认结束
 * @property string $reason 用车事由
 * @property string $remark 审批意见
 * @property string $comment 评论内容
 * @property int $comment_tp 1 满意 2 一般 3 不满意
 * @property int $driver_isview 记录司机是否阅读
 * @property string $view_time 司机阅读时间
 * @property int $usertype 1 新媒体  0 报社
 */
class WxTruckOrder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_truck_order';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // [['username', 'realname', 'mobile'], 'trim'],
            [['order_tp', 'tp', 'dep_id','car_id','start_mile','end_mile','mile','st','comment_tp','driver_isview','usertype','toll'], 'integer'],
            [['created', 'updated','start_time','end_time','park_fee','remark','comment','view_time'], 'safe'],
            [['companyNames', 'realname', 'mobile'], 'required', 'message' => '{attribute}必填'],
            [['companyUserid', 'start_place', 'destination', 'dep_name', 'car_licence','reason'], 'string', 'max' => 250],
            [['order_no','driver_mobile'], 'string', 'max' => 20],
            [['opt_userid','driver_name','driver'], 'string', 'max' => 50],
            [['opt_name'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_no' => 'order_no',
            'companyUserid' => 'companyUserid',
            'companyNames' => 'companyNames',
            'order_tp' => 'order_tp',
            'opt_userid' => 'opt_userid',
            'opt_name' => 'opt_name',
            'created' => 'created',
            'updated' => 'updated',
            'start_place' => 'start_place',
            'destination' => 'destination',
            'tp' => 'tp',
            'start_time' => 'start_time',
            'end_time' => 'end_time',
            'dep_name' => 'dep_name',
            'dep_id' => 'dep_id',
            'driver_name' => 'driver_name',
            'driver' => 'driver',
            'driver_mobile' => 'driver_mobile',
            'car_id' => 'car_id',
            'car_licence' => 'car_licence',
            'start_mile' => 'start_mile',
            'end_mile' => 'end_mile',
            'mile' => 'mile',
            'park_fee' => 'park_fee',
            'toll' => 'toll',
            'total_fee' => 'total_fee',
            'st' => 'st',
            'reason' => 'reason',
            'remark' => 'remark',
            'comment' => 'comment',
            'comment_tp' => 'comment_tp',
            'driver_isview' => 'driver_isview',
            'view_time' => 'view_time',
            'usertype' => 'usertype',
        ];
    }

    // public function fields()
    // {
    //     $fields = parent::fields();
    //     unset($fields['password'], $fields['salt']);
    //     return $fields;
    // }
}
