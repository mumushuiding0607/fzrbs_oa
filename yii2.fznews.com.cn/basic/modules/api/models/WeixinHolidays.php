<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_holidays".
 *
 * @property int $id
 * @property int|null $year 年份
 * @property int|null $type 类型(0:假日,1:补班)
 * @property string|null $days 日期
 */
class WeixinHolidays extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_holidays';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['year', 'type'], 'integer'],
            [['days'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'year' => 'Year',
            'type' => 'Type',
            'days' => 'Days',
        ];
    }
}
