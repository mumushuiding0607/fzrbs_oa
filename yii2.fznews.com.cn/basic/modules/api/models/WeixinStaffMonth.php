<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_staff_month".
 *
 * @property int $id
 * @property string|null $userid 食堂账号id
 * @property string|null $username 食堂账号姓名
 * @property string|null $mobile 手机号
 * @property int|null $departmentid 部门id
 * @property string|null $departmentname 部门名称
 * @property int|null $startbalance 月初账号餐补余额
 * @property int|null $startbalancewx 月初账号微信余额
 * @property int|null $endbalance 月末账号餐补余额
 * @property int|null $endbalancewx 月末账号微信余额
 * @property string|null $howmonth 年月份
 * @property int|null $transfermoney 月内餐补充值金额
 * @property int|null $transfermoneywx 月内微信充值金额
 * @property int|null $ordermoney 月内餐补消费金额
 * @property int|null $wxpay 月内微信消费金额
 * @property int|null $acountpay
 * @property int|null $newdepartmentid
 * @property int|null $typeid
 * @property int|null $usertype 用户结算分类(同食堂用户账号表)
 */
class WeixinStaffMonth extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_staff_month';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['departmentid', 'startbalance', 'startbalancewx', 'endbalance', 'endbalancewx', 'transfermoney', 'transfermoneywx', 'ordermoney', 'wxpay', 'acountpay', 'newdepartmentid', 'typeid', 'usertype'], 'integer'],
            [['userid'], 'string', 'max' => 20],
            [['username', 'mobile', 'departmentname'], 'string', 'max' => 250],
            [['howmonth'], 'string', 'max' => 6],
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
            'username' => 'Username',
            'mobile' => 'Mobile',
            'departmentid' => 'Departmentid',
            'departmentname' => 'Departmentname',
            'startbalance' => 'Startbalance',
            'startbalancewx' => 'Startbalancewx',
            'endbalance' => 'Endbalance',
            'endbalancewx' => 'Endbalancewx',
            'howmonth' => 'Howmonth',
            'transfermoney' => 'Transfermoney',
            'transfermoneywx' => 'Transfermoneywx',
            'ordermoney' => 'Ordermoney',
            'wxpay' => 'Wxpay',
            'acountpay' => 'Acountpay',
            'newdepartmentid' => 'Newdepartmentid',
            'typeid' => 'Typeid',
            'usertype' => 'Usertype',
        ];
    }
}
