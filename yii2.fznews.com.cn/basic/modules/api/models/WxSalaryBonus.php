<?php
namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_salary_bonus".
 *
 * @property int $id
 * @property string $userid 用户名
 * @property string $username 用户名
 * @property string $opt_id 密码
 * @property string $opt_name 密码盐值
 * @property string $del_id 密码盐值
 * @property string $dep_id 姓名
 * @property string $sign_st 手机号
 * @property string $send_st 部门
 * @property string $mobile 用户头像
 * @property int $col_a 姓名
 * @property string $col_b 奖金总额
 * @property string $col_c 代扣代缴
 * @property string $col_d 实发总额
 * @property string $created 时间
 * @property string $bonus_year 所属年份
 * @property string $bonus_month 发放月份
 * @property string $bonus_type 奖金类型：0年终奖,1文明奖,2创城奖,3综治奖
 * @property string $st 1 有效 0 删除
 */
class WxSalaryBonus extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_salary_bonus';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','opt_id', 'del_id', 'dep_id','sign_st','send_st','created','bonus_year','bonus_month','bonus_type','st'], 'integer'],
            [['col_b','col_c','col_d'], 'number'],
            // [['lastlogintime', 'inserttime'], 'safe'],
            [['username','opt_name','col_a'], 'string', 'max' => 255],
            [['userid'], 'string', 'max' => 50],
            [['mobile'], 'string', 'max' => 12],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userid' => 'userid',
            'username' => 'username',
            'opt_id' => 'opt_id',
            'opt_name' => 'opt_name',
            'del_id' => 'del_id',
            'dep_id' => 'dep_id',
            'sign_st' => 'sign_st',
            'send_st' => 'send_st',
            'mobile' => 'mobile',
            'col_a' => 'col_a',
            'col_b' => 'col_b',
            'col_c' => 'col_c',
            'col_d' => 'col_d',
            'created' => 'created',
            'bonus_year' => 'bonus_year',
            'bonus_month' => 'bonus_month',
            'bonus_type' => 'bonus_type',
            'st' => 'st'
        
        ];
    }

    
}
