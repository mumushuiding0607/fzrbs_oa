<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oa_usertag".
 *
 * @property int $id
 * @property string|null $tagName
 * @property string|null $type
 * @property string|null $describe
 * @property int|null $ord
 */
class WeixinOaUsertag extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_oauser_tag';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['ord'], 'integer'],
            [['tagName'], 'string', 'max' => 50],
            [['type', 'describe'], 'string', 'max' => 255],
            [['tagName'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tagName' => 'Tag Name',
            'type' => 'Type',
            'describe' => 'Describe',
            'ord' => 'Ord',
        ];
    }
}
