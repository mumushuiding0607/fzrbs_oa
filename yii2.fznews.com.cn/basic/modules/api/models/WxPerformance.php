<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "".
 *
 * @property int $id
 * @property string $opt_name 
 * @property string $title 
 * @property string $msg 
 * @property string $created 
 * @property int $tp 
 */
class WxPerformance extends \yii\db\ActiveRecord
{
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_performance';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'updated','created','path','img_path','check_remark','remark','title','deduct_reason'], 'safe'],
            [['userid,the_month,username,month'], 'string', 'max' => 50,'on'=>['update','create']],
            [['username,project,tencent,opt_userid,opt_name','video_class'], 'string', 'max' => 200,'on'=>['update','create']],
            [['day_id,dep_id,shift,st,read_num','tp','nums','marking_standard','grade','design_grades','subclass','product_id','fans','site','month_other_options','month_other_standard','month_client_standard','month_internet_standard','client_grade','internet_grade','other_grade','one_grade','month_internet_num','month_client_num','month_other_num','month_total_num','month_internet_read_num','month_client_read_num','deduct_grade'], 'number','on'=>['update','create']],
            [['day'], 'date','on'=>['update','create']],
            [['daytime'], 'public_time','on'=>['update','create']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'day_id' => '主表ID',
            'dep_id' => '部门',
            'total_grade' => '总分',
            'day' => '绩效所属日期',
            'the_month' => '绩效月份',
            'shift' => '班次',
            'userid' => '员工',
            'username' => '员工',
            'opt_userid' => '操作人',
            'opt_name' => '操作人',
            'created' => '创建时间',
            'updated' => '更新时间',
            'read_num' => '阅读数 | 点击数',
            'path' => '链接',
            'img_path' => '图片链接',
            'tp' => '类别',
            'nums' => '数量',
            'check_remark' => '审核备注',
            'remark' => '备注',
            'marking_standard' => '评分标准',
            'grade' => '得分',
            'design_grades' => '设计得分',
            'project' => '项目',
            'subclass' => '细类',
            'tencent' => '公众号|详情',
            'product_id' => '视频或者视频产品ID',
            'title' => '标题或其他事项',
            'public_time' => '发布时间',
            'fans' => '粉丝数',
            'site' => '位置或者岗位',
            'video_class' => '类型',
            'month_other_options' => '月总结其他工作选项',
            'month_other_standard' => '月总结其他工作评分标准',
            'month_client_standard' => '月总结客户端评分标准',
            'month_internet_standard' => '月总结网端评分标准',
            'client_grade' => '月总结客户端得分',
            'internet_grade' => '月总结网端得分',
            'other_grade' => '月总结其他工作得分',
            'one_grade' => '月总结得分1',
            'month' => '月总结月份',
            'month_internet_num' => '网站发稿量',
            'month_client_num' => '客户端发稿量',
            'month_total_num' => '其他发稿量',
            'month_total_num' => '发稿量总计',
            'month_internet_read_num' => '网站阅读量',
            'month_client_read_num' => '客户端阅读量',
            'deduct_grade' => '扣分',
            'deduct_reason' => '扣分原因',
            'st' => '状态',
        ];
    }
   
}
