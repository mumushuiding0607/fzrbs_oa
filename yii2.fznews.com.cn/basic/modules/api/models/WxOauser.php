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
class WxOauser extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_oauser_userinfo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'userid','name','departmentid','departmentname','position','mobile','gender','email','is_leader','avatar','telephone','enable','status','st','qr_code','level','is_company','authorized_time','social_time','team_time','company_time','entrytime','retire_time','resign_time','resign_reason','employ_type','noregular','birth','province','birth_place','nation','party_time','record','school','job_qualification','job_qualification_time2','job_qualification_time','work_time','curr_job_time','positions','work_job','changed','class_positions','mark','graduation_time','displayorder','party_birth','party_branch','inserttime'], 'safe'],
            [['userid'], 'string', 'max' => 50,'on'=>['update','create']],
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
            'name' => 'name',
            'departmentid' => 'departmentid',
            'departmentname' => 'departmentname',
            'position' => 'position',
            'mobile' => 'mobile',
            'gender' => 'gender',
            'email' => 'email',
            'is_leader' => 'is_leader',
            'avatar' => 'avatar',
            'telephone' => 'telephone',
            'enable' => 'enable',
            'status' => 'status',
            'st' => 'st',
            'qr_code' => 'qr_code',
            'level' => 'level',
            'is_company' => 'is_company',
            'authorized_time' => 'authorized_time',
            'social_time' => 'social_time',
            'team_time' => 'team_time',
            'company_time' => 'company_time',
            'entrytime' => 'entrytime',
            'retire_time' => 'retire_time',
            'resign_time' => 'resign_time',
            'resign_reason' => 'resign_reason',
            'employ_type' => 'employ_type',
            'noregular' => 'noregular',
            'birth' => 'birth',
            'province' => 'province',
            'birth_place' => 'birth_place',
            'nation' => 'nation',
            'party_time' => 'party_time',
            'record' => 'record',
            'school' => 'school',
            'job_qualification' => 'job_qualification',
            'job_qualification_time2' => 'job_qualification_time2',
            'job_qualification_time' => 'job_qualification_time',
            'work_time' => 'work_time',
            'curr_job_time' => 'curr_job_time',
            'positions' => 'positions',
            'work_job' => 'work_job',
            'changed' => 'changed',
            'class_positions' => 'class_positions',
            'mark' => 'mark',
            'graduation_time' => 'graduation_time',
            'displayorder' => 'displayorder',
            'party_birth' => 'party_birth',
            'party_branch' => 'party_branch',
            'inserttime' => 'inserttime',
        ];
    }
   
}
