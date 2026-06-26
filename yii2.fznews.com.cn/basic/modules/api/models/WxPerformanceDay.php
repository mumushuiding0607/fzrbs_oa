<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oauser_userinfo".
 *
 * @property int $id
 * @property string $opt_name 
 * @property string $title 
 * @property string $msg 
 * @property string $created 
 * @property int $tp 
 */
class WxPerformanceDay extends \yii\db\ActiveRecord
{
        // 绩效类型
        public $performanceTp = [
            1 => '稿件编录',
            2 => '传播效果',
            3 => '其他事项',
            4 => '月报总结',
            5 => '综合项',
            6 => '传播效果',//微
            7 => '设计组',
            8 => '视频组',
            9 => '传播效果',//设计
            10 => '技术中心',
            11 => '大数据',
            12 => '设计项',
            13 => '扣分项目',
            14 => '产品',
            15 => '舆情报告',
            16 => '舆情报告',//微
            17 => '基础分-推次',//微
            18 => '基础分',//视 2022-03-12废弃
            19 => '基础分',//网 2022-03-12废弃
            20 => '学习强国',
            21 => '辅助经营及其他事务性工作',
            22 => '原创奖励加分',
            23 => '基础分(社)',//22-03-12废弃
            24 => '基础分',//网
            25 => '基础分',//设计 
            26=>'基础奖励',//视频
            27=>'基础分',//视频
            28=>'原创奖励加分',//视频
            29=>'传播效果',//视频
    
        ];

        public $shift = [
            1=>'正常班',
            '早班',
            '夜班',
            '周末班/节假日',
        ];

        public $stArr = ['未提交','驳回','审核中','评分中','已评分'];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_performance_day';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'updated','created',], 'safe'],
            [['userid,the_month,username,opt_userid,remark'], 'string', 'max' => 50,'on'=>['update','create']],
            [['dep_id,total_grade,shift,st,sup_grade'], 'number','on'=>['update','create']],
            [['day'], 'date','on'=>['update','create']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created' => '创建时间',
            'dep_id' => '部门',
            'total_grade' => '总分',
            'day' => '绩效所属日期',
            'the_month' => '绩效月份',
            'shift' => '班次',
            'userid' => '员工',
            'username' => '员工',
            'opt_userid' => '操作人',
            'updated' => '更新时间',
            'st' => '状态',
            'remark' => '备注',
            'sup_grade' => '辅助分',
        ];
    }
   

}
