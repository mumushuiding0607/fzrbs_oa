<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oa_taguser".
 *
 * @property int $id
 * @property int|null $tagId
 * @property int|null $uId
 * @property string|null $tagName
 * @property string|null $creator
 * @property int|null $creatorid
 * @property string|null $createtime
 */
class WeixinOauserTaguser extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_oauser_taguser';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tagId', 'uId', 'creatorid'], 'integer'],
            [['tagName', 'creator', 'createtime'], 'string', 'max' => 255],
            [['tagId', 'uId'], 'unique', 'targetAttribute' => ['tagId', 'uId']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tagId' => 'Tag ID',
            'uId' => 'U ID',
            'tagName' => 'Tag Name',
            'creator' => 'Creator',
            'creatorid' => 'Creatorid',
            'createtime' => 'Createtime',
        ];
    }
}
