<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "fzrbs_admin".
 *
 * @property int $id
 * @property string $username 用户名
 * @property string $password 密码
 * @property string $salt 密码盐值
 * @property string $realname 姓名
 * @property string $mobile 手机号
 * @property string|null $department 部门
 * @property string|null $avatar 用户头像
 * @property int $usertype 用户类型(0:普通用户,1:管理员)
 * @property int $loginnum 登录次数
 * @property string $lastlogintime 最后登录时间
 * @property string|null $lastloginip 最后登录ip
 * @property int $islock 是否锁定(0:否,1:是)
 * @property string $wxopenid 微信openid
 * @property string $wxuserid 微信企业号id
 * @property string $inserttime 添加时间
 */
class FzrbsAdmin extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_admin';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'realname'], 'required', 'message' => '{attribute}必填', 'on' => ['create', 'update']],
            [['password'], 'required', 'message' => '密码必填', 'on' => ['create']],
            [['username'], 'unique', 'message' => '用户名已存在', 'on' => 'create'],
            // [['mobile'], 'unique', 'message' => '手机号已存在', 'on' => ['create', 'update']],
            [['username', 'realname', 'mobile'], 'trim'],
            [['usertype', 'loginnum', 'islock', 'classify'], 'integer'],
            [['lastlogintime', 'inserttime'], 'safe'],
            [['username', 'realname', 'department', 'wxopenid', 'wxuserid'], 'string', 'max' => 250],
            [['password'], 'string', 'max' => 100],
            [['salt'], 'string', 'max' => 6],
            [['mobile'], 'string', 'max' => 50],
            [['avatar'], 'string', 'max' => 255],
            [['lastloginip'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Username',
            'password' => 'Password',
            'salt' => 'Salt',
            'realname' => 'Realname',
            'mobile' => 'Mobile',
            'department' => 'Department',
            'avatar' => 'Avatar',
            'usertype' => 'Usertype',
            'loginnum' => 'Loginnum',
            'lastlogintime' => 'Lastlogintime',
            'lastloginip' => 'Lastloginip',
            'islock' => 'Islock',
            'wxopenid' => 'Wxopenid',
            'wxuserid' => 'Wxuserid',
            'classify' => 'Classify',
            'inserttime' => 'Inserttime',
        ];
    }

    public function fields()
    {
        $fields = parent::fields();
        unset($fields['password'], $fields['salt']);
        return $fields;
    }
}
