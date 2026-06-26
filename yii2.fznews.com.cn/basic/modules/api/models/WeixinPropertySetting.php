<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_property_setting".
 *
 * @property int $id
 * @property string $title 名称
 * @property string $created 创建时间
 * @property string|null $updated 修改时间
 * @property string|null $mark 备注
 * @property int $st 状态 0 删除
 * @property int $tp 数据类型：0 分类 1 状态
 * @property int|null $p_id 父id
 * @property string|null $p_ids 父ids
 * @property int $sort_index 排序
 */
class WeixinPropertySetting extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_property_setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title', 'created'], 'required','on'=>['update','create']],
            [['created', 'updated'], 'safe','on'=>['update','create']],
            [['st', 'tp', 'p_id', 'sort_index'], 'integer'],
            [['title', 'mark', 'p_ids'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'created' => 'Created',
            'updated' => 'Updated',
            'mark' => 'Mark',
            'st' => 'St',
            'tp' => 'Tp',
            'p_id' => 'P ID',
            'p_ids' => 'P Ids',
            'sort_index' => 'Sort Index',
        ];
    }

}
