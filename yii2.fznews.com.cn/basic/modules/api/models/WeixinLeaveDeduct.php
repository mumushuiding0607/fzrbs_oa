<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_leave_deduct".
 *
 * @property int $id
 * @property int|null $uid
 * @property int|null $theyear
 * @property float|null $deduct
 */
class WeixinLeaveDeduct extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_leave_deduct';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['uid', 'theyear'], 'integer'],
            [['deduct'], 'number'],
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
            'theyear' => 'Theyear',
            'deduct' => 'Deduct',
        ];
    }
}
