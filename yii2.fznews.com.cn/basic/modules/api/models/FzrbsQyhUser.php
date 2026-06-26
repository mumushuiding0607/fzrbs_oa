<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "fzrbs_qyh_user".
 *
 * @property int $id
 * @property string|null $userid
 * @property string|null $username
 * @property string|null $mobile
 * @property string|null $avatar
 * @property int|null $departmentid
 * @property string|null $departmentname
 * @property int|null $gender
 * @property int|null $balance
 * @property int|null $weixinbalance
 * @property int|null $usemoney
 * @property int|null $usenum
 * @property int|null $weixinpay
 * @property int|null $weixinrefund
 * @property int|null $newdepartmentid
 * @property string|null $newdepartmentname
 * @property int|null $usertype
 */
class FzrbsQyhUser extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_qyh_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
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
