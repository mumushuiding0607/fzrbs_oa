<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oa_department".
 *
 * @property int $id
 * @property string|null $name
 * @property int|null $parentid
 * @property int|null $order
 * @property int $st 0 删除 1 有效  2 隐藏
 * @property int $changed 0 无变动 1 变动
 * @property int|null $level
 * @property int|null $attribute 1-采编经营类,2-行政后勤类
 * @property string|null $leader
 */
class WeixinOaDepartment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_leave_department';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parentid', 'order', 'st', 'changed', 'level', 'attribute'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['leader'], 'string', 'max' => 255],
        ];
    }


}
