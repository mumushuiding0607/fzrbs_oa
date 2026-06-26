<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_newscontent".
 *
 * @property int $id
 * @property int $newsid 信息id
 * @property string|null $content 信息内容
 */
class WeixinNewsContent extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_newscontent';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['newsid'], 'integer'],
            [['content'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'newsid' => 'Newsid',
            'content' => 'Content',
        ];
    }
}
