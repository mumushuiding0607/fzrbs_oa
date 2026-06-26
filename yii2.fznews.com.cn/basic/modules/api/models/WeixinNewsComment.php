<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_newscomment".
 *
 * @property int $id
 * @property int|null $newsid 新闻id
 * @property int|null $good 点赞数
 * @property int|null $flower 送花数
 * @property int|null $view 浏览数
 * @property string|null $content_P 评论内容
 * @property string|null $username 姓名
 * @property string|null $userid
 * @property string|null $inserttime 添加时间
 */
class WeixinNewsComment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_newscomment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['newsid', 'good', 'flower', 'view'], 'integer'],
            [['inserttime'], 'safe'],
            [['content_P'], 'string', 'max' => 555],
            [['username'], 'string', 'max' => 32],
            [['userid'], 'string', 'max' => 255],
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
            'good' => 'Good',
            'flower' => 'Flower',
            'view' => 'View',
            'content_P' => 'Content P',
            'username' => 'Username',
            'userid' => 'Userid',
            'inserttime' => 'Inserttime',
        ];
    }
}
