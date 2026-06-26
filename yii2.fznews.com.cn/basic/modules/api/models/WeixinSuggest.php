<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_suggest".
 *
 * @property int $id
 * @property string|null $message 意见建议内容
 * @property string|null $username 提交用户姓名
 * @property string|null $userid 提交用户Id
 * @property string|null $img 头像
 * @property string|null $inserttime 提交时间
 * @property int|null $type 分类
 */
class WeixinSuggest extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_suggest';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['message'], 'string'],
            [['inserttime'], 'safe'],
            [['type'], 'integer'],
            [['username'], 'string', 'max' => 50],
            [['userid', 'img'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'message' => 'Message',
            'username' => 'Username',
            'userid' => 'Userid',
            'img' => 'Img',
            'inserttime' => 'Inserttime',
            'type' => 'Type',
        ];
    }
}
