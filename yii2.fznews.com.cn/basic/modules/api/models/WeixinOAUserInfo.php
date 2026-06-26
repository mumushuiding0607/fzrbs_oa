<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oa_userinfo".
 *
 * @property int $id
 * @property string|null $userid 用户id
 * @property string|null $name 姓名
 * @property int|null $departmentid 部门id
 * @property string|null $departmentname 部门名称
 * @property string|null $position 职务
 * @property string|null $mobile 手机号
 * @property int|null $gender 性别(1:男,2:女)
 * @property string|null $email 邮箱地址
 * @property int|null $is_leader 是否领导(0:否,1:是)
 * @property string|null $avatar 头像
 * @property string|null $telephone 固定电话
 * @property int|null $enable 是否可用
 * @property int|null $status 状态(1:已激活,2:已禁用,4:未激活)
 * @property int $st 状态(0:删除,1:正常)
 * @property string|null $qr_code 二维码
 * @property int|null $level 级别
 * @property int|null $is_company 是否下属公司
 * @property string|null $authorized_time 入编时间
 * @property string|null $social_time 社聘时间
 * @property string|null $team_time 转集体编制时间
 * @property string|null $company_time 公司聘时间
 * @property string|null $entrytime 入职时间
 * @property int|null $noregular
 * @property string|null $birth 出生年月
 * @property string|null $province 籍贯
 * @property string|null $birth_place 出生地
 * @property string|null $nation 民族
 * @property string|null $party_time 入党年月
 * @property int|null $record 学历
 * @property string|null $school 毕业院校以及专业
 * @property string|null $job_qualification 专业技术职务资格
 * @property string|null $job_qualification_time2 专业技术任聘时间
 * @property string|null $job_qualification_time 专业技术确认时间
 * @property string|null $work_time 参加工作时间
 * @property string|null $curr_job_time 任现职时间
 * @property string|null $positions 本职级认定时间
 * @property string|null $work_job 现单位以及职务（废弃）
 * @property int $changed 0:未变动,1:变动
 * @property int $class_positions 0:无,1:正科,2:副科,3:正处,4:副处
 * @property string|null $mark 备注
 * @property string|null $graduation_time 毕业时间
 * @property int|null $displayorder
 * @property string|null $party_birth 党员政治生日
 * @property int|null $party_branch 党支部
 * @property int|null $checkin
 */
class WeixinOAUserInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_leave_userinfo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['departmentid', 'gender', 'is_leader', 'enable', 'status', 'st', 'level', 'is_company', 'noregular', 'record', 'changed', 'class_positions', 'displayorder', 'party_branch', 'checkin'], 'integer'],
            [['authorized_time', 'social_time', 'team_time', 'company_time', 'entrytime', 'birth', 'party_time', 'job_qualification_time2', 'job_qualification_time', 'work_time', 'curr_job_time', 'positions', 'graduation_time', 'party_birth'], 'safe'],
            [['mark'], 'string'],
            [['userid', 'name', 'mobile', 'email', 'telephone', 'nation'], 'string', 'max' => 50],
            [['departmentname', 'avatar', 'qr_code'], 'string', 'max' => 250],
            [['position'], 'string', 'max' => 200],
            [['province'], 'string', 'max' => 10],
            [['birth_place', 'school', 'job_qualification', 'work_job'], 'string', 'max' => 255],
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
            'name' => 'Name',
            'departmentid' => 'Departmentid',
            'departmentname' => 'Departmentname',
            'position' => 'Position',
            'mobile' => 'Mobile',
            'gender' => 'Gender',
            'email' => 'Email',
            'is_leader' => 'Is Leader',
            'avatar' => 'Avatar',
            'telephone' => 'Telephone',
            'enable' => 'Enable',
            'status' => 'Status',
            'st' => 'St',
            'qr_code' => 'Qr Code',
            'level' => 'Level',
            'is_company' => 'Is Company',
            'authorized_time' => 'Authorized Time',
            'social_time' => 'Social Time',
            'team_time' => 'Team Time',
            'company_time' => 'Company Time',
            'entrytime' => 'Entrytime',
            'noregular' => 'Noregular',
            'birth' => 'Birth',
            'province' => 'Province',
            'birth_place' => 'Birth Place',
            'nation' => 'Nation',
            'party_time' => 'Party Time',
            'record' => 'Record',
            'school' => 'School',
            'job_qualification' => 'Job Qualification',
            'job_qualification_time2' => 'Job Qualification Time 2',
            'job_qualification_time' => 'Job Qualification Time',
            'work_time' => 'Work Time',
            'curr_job_time' => 'Curr Job Time',
            'positions' => 'Positions',
            'work_job' => 'Work Job',
            'changed' => 'Changed',
            'class_positions' => 'Class Positions',
            'mark' => 'Mark',
            'graduation_time' => 'Graduation Time',
            'displayorder' => 'Displayorder',
            'party_birth' => 'Party Birth',
            'party_branch' => 'Party Branch',
            'checkin' => 'Checkin',
        ];
    }
}
