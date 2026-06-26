<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "shitang_canteenorder".
 *
 * @property int $id
 * @property string|null $userid 用户食堂账号(用户企业号id)
 * @property string|null $userid1 代领同事的用户id
 * @property string $realname 用户姓名
 * @property string|null $mobile 用户手机号
 * @property int $ordermoney 订单金额
 * @property int $ordernum 订单商品数量
 * @property int $ordertime 下单时间
 * @property string|null $orderinfo 订单商品信息
 * @property int|null $status 订单状态(0:未使用,1:已使用,2:已取消)
 * @property int|null $wxpay 是否微信支付订单(0:否,1:是)
 * @property string|null $orderid 订单id号
 * @property string|null $qrcodefile 订单二维码图片文件地址
 * @property string|null $menudate 用餐日期
 * @property string|null $touserid 被转让人用户id
 * @property string|null $torealname 被转让人用户姓名
 * @property int|null $totime 转让时间
 * @property int|null $status1 转让状态(0:正在进行中,1:转让成功)
 * @property int|null $typeid 订单类别(1:午餐,2:晚餐,3:早餐,4:其他,5:代购,6:面对面,7:咖啡,100:现煮)
 * @property int|null $public 用餐时间段(1:11:30,2:11:50,3:12:10,4:12:30,5:18:00,6:18:20,7:18:40,8:19:00)
 * @property int|null $guige 规格
 * @property int|null $scantime 扫码使用时间
 * @property string|null $timeend 微信支付成功通知时间
 * @property string|null $transactionid 微信支付交易id号
 * @property int|null $flag
 * @property string|null $menuids 订单所有商品id号
 * @property string|null $remark 其他备注
 */
class ShitangCanteenOrder extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shitang_canteenorder';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['realname'], 'required'],
            [['ordermoney', 'ordernum', 'ordertime', 'status', 'wxpay', 'totime', 'status1', 'typeid', 'public', 'guige', 'scantime', 'flag'], 'integer'],
            [['userid', 'userid1', 'orderid', 'touserid'], 'string', 'max' => 20],
            [['realname', 'mobile', 'qrcodefile', 'torealname', 'menuids'], 'string', 'max' => 250],
            [['orderinfo'], 'string', 'max' => 1000],
            [['menudate'], 'string', 'max' => 8],
            [['timeend', 'transactionid'], 'string', 'max' => 50],
            [['remark'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userid' => 'Userid',
            'userid1' => 'Userid 1',
            'realname' => 'Realname',
            'mobile' => 'Mobile',
            'ordermoney' => 'Ordermoney',
            'ordernum' => 'Ordernum',
            'ordertime' => 'Ordertime',
            'orderinfo' => 'Orderinfo',
            'status' => 'Status',
            'wxpay' => 'Wxpay',
            'orderid' => 'Orderid',
            'qrcodefile' => 'Qrcodefile',
            'menudate' => 'Menudate',
            'touserid' => 'Touserid',
            'torealname' => 'Torealname',
            'totime' => 'Totime',
            'status1' => 'Status 1',
            'typeid' => 'Typeid',
            'public' => 'Public',
            'guige' => 'Guige',
            'scantime' => 'Scantime',
            'timeend' => 'Timeend',
            'transactionid' => 'Transactionid',
            'flag' => 'Flag',
            'menuids' => 'Menuids',
            'remark' => 'Remark',
        ];
    }
}
