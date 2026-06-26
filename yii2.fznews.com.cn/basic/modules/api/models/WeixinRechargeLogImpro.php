<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_rechargelog_impro".
 *
 * @property int $id
 * @property int $uid 操作人id
 * @property string $uname 操作人账号
 * @property string $urealname 操作人姓名
 * @property string $targetuname 充值账号
 * @property string $targetrealname 充值姓名
 * @property string $departmentname 充值账号部门
 * @property string|null $intro 充值备注
 * @property int $inserttime 充值时间
 * @property int|null $rechargemoney 充值金额
 * @property int|null $rechargeall 充值全额
 * @property string|null $rechargeusers 充值全账号
 * @property int|null $usertype 用户结算分类(同食堂用户账号表)
 */
class WeixinRechargeLogImpro extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_rechargelog_impro';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'inserttime', 'rechargemoney', 'rechargeall', 'usertype'], 'integer'],
            [['intro', 'rechargeusers'], 'string'],
            [['uname', 'urealname', 'targetuname', 'targetrealname', 'departmentname'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => 'Uid',
            'uname' => 'Uname',
            'urealname' => 'Urealname',
            'targetuname' => 'Targetuname',
            'targetrealname' => 'Targetrealname',
            'departmentname' => 'Departmentname',
            'intro' => 'Intro',
            'inserttime' => 'Inserttime',
            'rechargemoney' => 'Rechargemoney',
            'rechargeall' => 'Rechargeall',
            'rechargeusers' => 'Rechargeusers',
            'usertype' => 'Usertype',
        ];
    }
}
