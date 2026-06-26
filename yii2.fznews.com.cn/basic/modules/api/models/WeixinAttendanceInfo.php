<?php

namespace app\modules\api\models;

use Yii;


class WeixinAttendanceInfo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_attendance_info';
    }
}