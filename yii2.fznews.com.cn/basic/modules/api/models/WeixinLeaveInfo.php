<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_leave_info".
 *
 * @property int $id
 * @property string|null $userId
 * @property string|null $userName
 * @property int|null $departmentid
 * @property string|null $department
 * @property string|null $thirdNo
 * @property string|null $leaveType
 * @property string|null $leaveStarttime
 * @property string|null $leaveEndtime
 * @property float|null $leaveTimes
 * @property string|null $leaveReason
 * @property string|null $attachment
 * @property string|null $templateId
 * @property string|null $LthirdNo
 * @property string|null $approvalUserid
 * @property string|null $approvalUsername
 * @property int|null $approvalStep
 * @property int|null $status 1-审批中；2-已通过；3-已驳回；4-已取消；5-销假中；6-已销假
 * @property string|null $inserttime
 * @property int|null $issend
 * @property int|null $undoType
 * @property string|null $PthirdNo
 * @property int|null $approvalType
 * @property string|null $opUser
 * @property int|null $isout 是否出省：1出省
 * @property string|null $destination
 * @property string|null $originalEndtime
 * @property float|null $originalTimes
 */
class WeixinLeaveInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_leave_info';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['userId', 'thirdNo', 'leaveType'], 'required', 'message' => '{attribute}必填', 'on' => ['create']],
            [['departmentid', 'approvalStep', 'status', 'issend', 'undoType', 'approvalType', 'isout'], 'integer'],
            [['leaveStarttime', 'leaveEndtime', 'inserttime', 'originalEndtime'], 'safe'],
            [['leaveTimes', 'originalTimes'], 'number'],
            [['userId', 'thirdNo', 'leaveType', 'LthirdNo', 'PthirdNo'], 'string', 'max' => 20],
            [['userName', 'templateId', 'approvalUserid', 'approvalUsername', 'opUser', 'destination'], 'string', 'max' => 50],
            [['department', 'leaveReason', 'attachment'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userId' => 'User ID',
            'userName' => 'User Name',
            'departmentid' => 'Departmentid',
            'department' => 'Department',
            'thirdNo' => 'Third No',
            'leaveType' => 'Leave Type',
            'leaveStarttime' => 'Leave Starttime',
            'leaveEndtime' => 'Leave Endtime',
            'leaveTimes' => 'Leave Times',
            'leaveReason' => 'Leave Reason',
            'attachment' => 'Attachment',
            'templateId' => 'Template ID',
            'LthirdNo' => 'Lthird No',
            'approvalUserid' => 'Approval Userid',
            'approvalUsername' => 'Approval Username',
            'approvalStep' => 'Approval Step',
            'status' => 'Status',
            'inserttime' => 'Inserttime',
            'issend' => 'Issend',
            'undoType' => 'Undo Type',
            'PthirdNo' => 'Pthird No',
            'approvalType' => 'Approval Type',
            'opUser' => 'Op User',
            'isout' => 'Isout',
            'destination' => 'Destination',
            'originalEndtime' => 'Original Endtime',
            'originalTimes' => 'Original Times',
        ];
    }
}
