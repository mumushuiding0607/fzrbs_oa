<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_staff".
 *
 * @property int $id
 * @property string|null $userid 用户企业号id
 * @property string|null $username 用户企业号姓名
 * @property string|null $mobile 用户企业号手机
 * @property string|null $avatar 用户企业号头像
 * @property int|null $departmentid 用户企业号部门id
 * @property string|null $departmentname 用户企业号部门名称
 * @property int|null $gender 用户企业号姓别
 * @property int|null $balance 餐补余额
 * @property int|null $weixinbalance 微信余额
 * @property int|null $usemoney 餐补使用额
 * @property int|null $usenum 餐补使用次数
 * @property int|null $weixinpay 微信使用额
 * @property int|null $weixinrefund 餐补退回额
 * @property int|null $newdepartmentid 本地部门id
 * @property string|null $newdepartmentname 本地部门名称
 * @property int|null $usertype 用户分类(-1:辞职,0:未设置,1:无补贴,2:社新媒体中心,3:晚报运营中心,4:日报运营中心,5:社直,6:日报,7:晚报,8:报社其他,9:福小子体育文化传播有限公司,10:众创孵化中心,11:福州新闻图片社,12:晚报运营中心(一碗福州),13:晚报发行中心,14:市宣教中心)
 */
class WeixinStaff extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_staff';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['userid', 'username'], 'required', 'message' => '{attribute}必填', 'on' => ['create', 'update']],
            [['departmentid', 'gender', 'balance', 'weixinbalance', 'usemoney', 'usenum', 'weixinpay', 'weixinrefund', 'newdepartmentid', 'usertype'], 'integer'],
            [['userid'], 'string', 'max' => 20],
            [['username', 'mobile', 'avatar', 'departmentname', 'newdepartmentname'], 'string', 'max' => 250],
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
            'avatar' => 'Avatar',
            'departmentid' => 'Departmentid',
            'departmentname' => 'Departmentname',
            'gender' => 'Gender',
            'balance' => 'Balance',
            'weixinbalance' => 'Weixinbalance',
            'usemoney' => 'Usemoney',
            'usenum' => 'Usenum',
            'weixinpay' => 'Weixinpay',
            'weixinrefund' => 'Weixinrefund',
            'newdepartmentid' => 'Newdepartmentid',
            'newdepartmentname' => 'Newdepartmentname',
            'usertype' => 'Usertype',
        ];
    }
}
