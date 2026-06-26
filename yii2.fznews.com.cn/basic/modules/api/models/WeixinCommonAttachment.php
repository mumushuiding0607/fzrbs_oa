<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_common_attachment".
 *
 * @property int $id
 * @property string $url
 * @property string|null $created
 * @property string|null $filename
 */
class WeixinCommonAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_common_attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['url'], 'required'],
            [['created'], 'safe','on'=>['update','create']],
            [['url', 'filename'], 'string', 'max' => 255,'on'=>['update','create']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'url' => 'Url',
            'created' => 'Created',
            'filename' => 'Filename',
        ];
    }

}
