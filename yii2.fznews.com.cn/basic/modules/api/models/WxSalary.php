<?php
namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_salary".
 *
 * @property int $id
 * @property string $userid 用户名
 * @property string $username 用户名
 * @property string $opt_id 密码
 * @property string $opt_name 密码盐值
 * @property string $del_id 密码盐值
 * @property string $dep_id 姓名
 * @property string $sign_st 手机号
 * @property string|null $send_st 部门
 * @property string|null $mobile 用户头像
 * @property int $col_ai 用户类型(0:普通用户,1:管理员)
 * @property int $col_ah 登录次数
 * @property string $col_ag 最后登录时间
 * @property string|null $col_af 最后登录ip
 * @property int $col_a 是否锁定(0:否,1:是)
 * @property string $col_b 微信openid
 * @property string $col_c 微信企业号id
 * @property string $col_ca 添加时间
 * @property string $col_d 添加时间
 * @property string $col_e 添加时间
 * @property string $col_f 添加时间
 * @property string $col_g 添加时间
 * @property string $col_al 添加时间
 * @property string $col_h 添加时间
 * @property string $col_i 添加时间
 * @property string $col_j 稿分
 * @property string $col_k 新媒绩效
 * @property string $col_l 稿分绩效
 * @property string $col_m 协助经营绩效
 * @property string $col_n 计量绩效
 * @property string $col_o 夜班/加班
 * @property string $col_oa 夜班绩效加班绩效
 * @property string $col_p 质量绩效（）
 * @property string $col_q 其他工资
 * @property string $col_r 应发工资
 * @property string $col_s 住房公积金
 * @property string $col_t 退休养老金
 * @property string $col_u 职业年金
 * @property string $col_an 扣养公金
 * @property string $col_v 医保扣款
 * @property string $col_w 社保扣款
 * @property string $col_x 失业保险
 * @property string $col_y 个调税
 * @property string $col_z 扣预发档案工资
 * @property string $col_aa 扣差错
 * @property string $col_ab 工会费
 * @property string $col_aj 补扣社保
 * @property string $col_ak 补扣医保
 * @property string $col_ac 扣其他
 * @property string $col_ad 扣款合计
 * @property string $col_ae 实发工资
 * @property string $created 时间
 * @property string $pay_time 发工资时间
 * @property string $st 1 有效 0 删除
 * @property string $col_am 工资总额
 * @property string $col_ao 本月工资
 * @property string $col_ap 扣款
 * @property string $col_aq 补扣年金
 * @property string $col_ar 实发合计
 * @property string $col_as 应发绩效
 * @property string $col_at 实发绩效
 * @property string $col_au 融合奖优绩效
 */
class WxSalary extends \yii\db\ActiveRecord
{
    public static $columns = [
        0 => [
            [
                'title' => '职工名称',
                'dataIndex' => 'username',
                'fixed' => 'left',
                'width' => 120,
                'hideInForm' => true
            ],
            [
                'title' => '发放月份',
                'dataIndex' => 'pay_time',
                'width' => 120,
                'hideInSearch' => true,
                'valueType' => 'dateMonth',
                'initialValue' => 'date',
                'formItemProps' => 'require'
            ],
            [
                'title' => '姓名',
                'dataIndex' => 'col_a',
                'hideInSearch' => true,
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 12],
                'formItemProps' => 'require'
            ],
            [
                'title' => '实发工资',
                'dataIndex' => 'col_ae',
                'width' => 120,
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '手机号',
                'dataIndex' => 'mobile',
                'hideInSearch' => true,
                'hideInForm' => true,
                'width' => 120
            ],
            [
                'title' => '通知',
                'dataIndex' => 'send_st',
                'hideInForm' => true,
                'fixed' => 'right',
                'valueEnum' => 'send',
                'width' => 80
            ],
            [
                'title' => '签发',
                'dataIndex' => 'sign_st',
                'hideInForm' => true,
                'fixed' => 'right',
                'valueEnum' => 'sign',
                'width' => 80
            ]
        ],
        2 => [
            [
                'title' => '职工名称',
                'dataIndex' => 'username',
                'fixed' => 'left',
                'width' => 120,
                'hideInForm' => true
            ],
            [
                'title' => '所属时间',
                'dataIndex' => 'pay_time',
                'width' => 120,
                'hideInSearch' => true,
                'valueType' => 'dateMonth',
                'colProps' => ['xs' => 24,'md' => 12],
                'initialValue' => 'date',
                'formItemProps' => 'require'
            ],
            [
                'title' => '姓名',
                'dataIndex' => 'col_a',
                'hideInSearch' => true,
                'readonly' => true,
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 12],
                'formItemProps' => 'require'
            ],
            [
                'title' => '岗位工资',
                'dataIndex' => 'col_d',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '薪级工资',
                'dataIndex' => 'col_e',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '岗位津贴',
                'dataIndex' => 'col_f',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '生活补贴',
                'dataIndex' => 'col_g',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '提租补贴',
                'dataIndex' => 'col_al',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '其它',
                'dataIndex' => 'col_h',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '合计',
                'dataIndex' => 'col_i',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '住房公积金',
                'dataIndex' => 'col_s',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '养老保险',
                'dataIndex' => 'col_ai',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '职业年金',
                'dataIndex' => 'col_u',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '医保',
                'dataIndex' => 'col_v',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '失业保险',
                'dataIndex' => 'col_x',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '工会费',
                'dataIndex' => 'col_ab',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣社保',
                'dataIndex' => 'col_aj',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣年金',
                'dataIndex' => 'col_aq',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣公积金',
                'dataIndex' => 'col_aw',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣医保',
                'dataIndex' => 'col_ah',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣失业',
                'dataIndex' => 'col_av',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '其他项',
                'dataIndex' => 'col_ac',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣款合计',
                'dataIndex' => 'col_ad',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '实发合计',
                'dataIndex' => 'col_ar',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '绩效工资',
                'dataIndex' => 'col_ag',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '其他',
                'dataIndex' => 'col_q',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '应发绩效',
                'dataIndex' => 'col_as',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣个调税',
                'dataIndex' => 'col_y',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '实发绩效',
                'dataIndex' => 'col_at',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '手机号',
                'dataIndex' => 'mobile',
                'hideInSearch' => true,
                'hideInForm' => true,
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8]
            ],
            [
                'title' => '通知',
                'dataIndex' => 'send_st',
                'hideInForm' => true,
                'fixed' => 'right',
                'valueEnum' => 'send',
                'width' => 80
            ],
            [
                'title' => '签发',
                'dataIndex' => 'sign_st',
                'hideInForm' => true,
                'fixed' => 'right',
                'valueEnum' => 'sign',
                'width' => 80
            ]
        ],
        3 => [
            [
                'title' => '职工名称',
                'dataIndex' => 'username',
                'fixed' => 'left',
                'width' => 120,
                'hideInForm' => true
            ],
            [
                'title' => '发放月份',
                'dataIndex' => 'pay_time',
                'hideInSearch' => true,
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 12],
                'valueType' => 'dateMonth',
                'initialValue' => 'date',
                'formItemProps' => 'require'
            ],
            [
                'title' => '姓名',
                'dataIndex' => 'col_a',
                'hideInSearch' => true,
                'readonly' => true,
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 12],
                'formItemProps' => 'require'
            ],
            [
                'title' => '分级底薪',
                'dataIndex' => 'col_b',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '社龄补贴',
                'dataIndex' => 'col_c',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '社龄工资',
                'dataIndex' => 'col_ca',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '基本工资',
                'dataIndex' => 'col_af',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '稿分',
                'dataIndex' => 'col_j',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '新媒稿分',
                'dataIndex' => 'col_k',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '稿分绩效',
                'dataIndex' => 'col_l',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣差错',
                'dataIndex' => 'col_aa',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '夜班/加班',
                'dataIndex' => 'col_o',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '夜班绩效加班绩效',
                'dataIndex' => 'col_oa',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '计量绩效',
                'dataIndex' => 'col_n',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '本月工资',
                'dataIndex' => 'col_ao',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣款',
                'dataIndex' => 'col_ap',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '合计',
                'dataIndex' => 'col_i',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '融合奖优绩效',
                'dataIndex' => 'col_au',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '内容质量绩效',
                'dataIndex' => 'col_p',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '应发工资',
                'dataIndex' => 'col_r',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '其他加计扣税',
                'dataIndex' => 'col_m',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣养公金',
                'dataIndex' => 'col_an',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣年金',
                'dataIndex' => 'col_aq',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣个调税',
                'dataIndex' => 'col_y',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '工会费',
                'dataIndex' => 'col_ab',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '住房公积金',
                'dataIndex' => 'col_s',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '养老保险',
                'dataIndex' => 'col_ai',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '失业保险',
                'dataIndex' => 'col_x',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '医保',
                'dataIndex' => 'col_v',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '职业年金',
                'dataIndex' => 'col_u',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣医保',
                'dataIndex' => 'col_ah',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣失业',
                'dataIndex' => 'col_av',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣公积金',
                'dataIndex' => 'col_aw',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣预发档案工资',
                'dataIndex' => 'col_z',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '已发档案工资',
                'dataIndex' => 'col_za',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '其他项',
                'dataIndex' => 'col_ac',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣款合计',
                'dataIndex' => 'col_ad',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '实发工资',
                'dataIndex' => 'col_ae',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '基础绩效奖',
                'dataIndex' => 'col_ba',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '活动绩效',
                'dataIndex' => 'col_bc',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '绩效工资',
                'dataIndex' => 'col_ag',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '手机号',
                'dataIndex' => 'mobile',
                'hideInSearch' => true,
                'hideInForm' => true,
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8]
            ],
            [
                'title' => '通知',
                'dataIndex' => 'send_st',
                'hideInForm' => true,
                'fixed' => 'right',
                'valueEnum' => 'send',
                'width' => 80
            ],
            [
                'title' => '签发',
                'dataIndex' => 'sign_st',
                'hideInForm' => true,
                'fixed' => 'right',
                'valueEnum' => 'sign',
                'width' => 80
            ]
        ],
        5 => [
            [
                'title' => '职工名称',
                'dataIndex' => 'username',
                'fixed' => 'left',
                'width' => 120,
                'hideInForm' => true
            ],
            [
                'title' => '发放月份',
                'dataIndex' => 'pay_time',
                'hideInSearch' => true,
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 12],
                'valueType' => 'dateMonth',
                'initialValue' => 'date',
                'formItemProps' => 'require'
            ],
            [
                'title' => '姓名',
                'dataIndex' => 'col_a',
                'hideInSearch' => true,
                'readonly' => true,
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 12],
                'formItemProps' => 'require'
            ],
            [
                'title' => '分级底薪',
                'dataIndex' => 'col_b',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '社龄补贴',
                'dataIndex' => 'col_c',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '基本工资',
                'dataIndex' => 'col_af',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '稿分',
                'dataIndex' => 'col_j',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '稿分绩效',
                'dataIndex' => 'col_l',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '夜班/加班',
                'dataIndex' => 'col_o',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '计量绩效',
                'dataIndex' => 'col_n',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '本月工资',
                'dataIndex' => 'col_ao',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣款',
                'dataIndex' => 'col_ap',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '合计',
                'dataIndex' => 'col_i',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '内容质量绩效',
                'dataIndex' => 'col_p',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '应发工资',
                'dataIndex' => 'col_r',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '其他',
                'dataIndex' => 'col_q',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣养公金',
                'dataIndex' => 'col_an',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣年金',
                'dataIndex' => 'col_aq',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣个调税',
                'dataIndex' => 'col_y',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '工会费',
                'dataIndex' => 'col_ab',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '住房公积金',
                'dataIndex' => 'col_s',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '养老保险',
                'dataIndex' => 'col_ai',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '失业保险',
                'dataIndex' => 'col_x',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '医保',
                'dataIndex' => 'col_v',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '职业年金',
                'dataIndex' => 'col_u',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣医保',
                'dataIndex' => 'col_ah',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣失业',
                'dataIndex' => 'col_av',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣公积金',
                'dataIndex' => 'col_aw',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣款合计',
                'dataIndex' => 'col_ad',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣预发档案工资',
                'dataIndex' => 'col_z',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '已发档案工资',
                'dataIndex' => 'col_za',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣差错',
                'dataIndex' => 'col_aa',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '其他项',
                'dataIndex' => 'col_ac',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '实发工资',
                'dataIndex' => 'col_ae',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '基础绩效奖',
                'dataIndex' => 'col_ba',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '活动绩效',
                'dataIndex' => 'col_bc',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '绩效工资',
                'dataIndex' => 'col_ag',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '手机号',
                'dataIndex' => 'mobile',
                'hideInSearch' => true,
                'hideInForm' => true,
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8]
            ],
            [
                'title' => '通知',
                'dataIndex' => 'send_st',
                'hideInForm' => true,
                'fixed' => 'right',
                'valueEnum' => 'send',
                'width' => 80
            ],
            [
                'title' => '签发',
                'dataIndex' => 'sign_st',
                'hideInForm' => true,
                'fixed' => 'right',
                'valueEnum' => 'sign',
                'width' => 80
            ]
        ],
        6 => [
            [
                'title' => '职工名称',
                'dataIndex' => 'username',
                'fixed' => 'left',
                'width' => 120,
                'hideInForm' => true
            ],
            [
                'title' => '发放月份',
                'dataIndex' => 'pay_time',
                'hideInSearch' => true,
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 12],
                'valueType' => 'dateMonth',
                'initialValue' => 'date',
                'formItemProps' => 'require'
            ],
            [
                'title' => '姓名',
                'dataIndex' => 'col_a',
                'hideInSearch' => true,
                'readonly' => true,
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 12],
                'formItemProps' => 'require'
            ],
            [
                'title' => '基本工资',
                'dataIndex' => 'col_af',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '绩效工资',
                'dataIndex' => 'col_ag',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '应发工资',
                'dataIndex' => 'col_r',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '住房公积金',
                'dataIndex' => 'col_s',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '养老保险',
                'dataIndex' => 'col_ai',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '失业保险',
                'dataIndex' => 'col_x',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '医保',
                'dataIndex' => 'col_v',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '工会费',
                'dataIndex' => 'col_ab',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '职业年金',
                'dataIndex' => 'col_u',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣社保',
                'dataIndex' => 'col_aj',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '补扣医保',
                'dataIndex' => 'col_ah',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '其他项',
                'dataIndex' => 'col_ac',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣个调税',
                'dataIndex' => 'col_y',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '扣款合计',
                'dataIndex' => 'col_ad',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '实发工资',
                'dataIndex' => 'col_ae',
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8],
                'hideInSearch' => true,
                'formItemProps' => 'number'
            ],
            [
                'title' => '手机号',
                'dataIndex' => 'mobile',
                'hideInSearch' => true,
                'hideInForm' => true,
                'width' => 120,
                'colProps' => ['xs' => 24,'md' => 8]
            ],
            [
                'title' => '通知',
                'dataIndex' => 'send_st',
                'hideInForm' => true,
                'fixed' => 'right',
                'valueEnum' => 'send',
                'width' => 80
            ],
            [
                'title' => '签发',
                'dataIndex' => 'sign_st',
                'hideInForm' => true,
                'fixed' => 'right',
                'valueEnum' => 'sign',
                'width' => 80
            ]
        ]
    ];
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_salary';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id','opt_id', 'del_id', 'dep_id','sign_st','send_st','created','pay_time','st'], 'integer'],
            [['col_ai', 'col_ah', 'col_ag','col_af','col_b','col_c','col_ca','col_d','col_e','col_f','col_g','col_al','col_h','col_i','col_j','col_k','col_l','col_m','col_n','col_o','col_oa','col_p','col_q','col_r','col_s','col_t','col_u','col_an','col_v','col_w','col_x','col_y','col_z','col_za','col_aa','col_ab','col_aj','col_ak','col_ac','col_ad','col_ae','col_am','col_ao','col_ap','col_aq','col_ar','col_as','col_at','col_au','col_av','col_aw','col_ba','col_bc'], 'number'],
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
            'col_ai' => 'col_ai',
            'col_ah' => 'col_ah',
            'col_ag' => 'col_ag',
            'col_af' => 'col_af',
            'col_a' => 'col_a',
            'col_b' => 'col_b',
            'col_c' => 'col_c',
            'col_ca' => 'col_ca',
            'col_d' => 'col_d',
            'col_e' => 'col_e',
            'col_f' => 'col_f',
            'col_g' => 'col_g',
            'col_al' => 'col_al',
            'col_h' => 'col_h',
            'col_i' => 'col_i',
            'col_j' => 'col_j',
            'col_k' => 'col_k',
            'col_l' => 'col_l',
            'col_m' => 'col_m',
            'col_n' => 'col_n',
            'col_o' => 'col_o',
            'col_p' => 'col_p',
            'col_oa' => 'col_oa',
            'col_q' => 'col_q',
            'col_r' => 'col_r',
            'col_s' => 'col_s',
            'col_t' => 'col_t',
            'col_u' => 'col_u',
            'col_an' => 'col_an',
            'col_v' => 'col_v',
            'col_w' => 'col_w',
            'col_x' => 'col_x',
            'col_y' => 'col_y',
            'col_z' => 'col_z',
            'col_za' => 'col_za',
            'col_aa' => 'col_aa',
            'col_ab' => 'col_ab',
            'col_aj' => 'col_aj',
            'col_ak' => 'col_ak',
            'col_ac' => 'col_ac',
            'col_ad' => 'col_ad',
            'col_ae' => 'col_ae',
            'created' => 'created',
            'pay_time' => 'st',
            'st' => 'st',
            'col_am' => 'col_am',
            'col_ao' => 'col_ao',
            'col_ap' => 'col_ap',
            'col_aq' => 'col_aq',
            'col_ar' => 'col_ar',
            'col_as' => 'col_as',
            'col_at' => 'col_at',
            'col_au' => 'col_au',
            'col_av' => 'col_av',
            'col_aw' => 'col_aw',
            'col_ba' => 'col_ba',
            'col_bc' => 'col_bc',
        ];
    }

    public static function getColumns()
    {
        $_columns = self::$columns;
        $_columns[31] = $_columns[2];
        $_columns[40] = $_columns[6];
        $_columns[65] = $_columns[2];
        $_columns[95] = $_columns[3];
        return $_columns;
    }
    
}
