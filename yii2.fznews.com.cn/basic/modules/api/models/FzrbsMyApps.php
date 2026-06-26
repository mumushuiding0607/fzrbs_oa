<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "fzrbs_myapps".
 *
 * @property int $id
 * @property int $appid 应用id
 * @property string $userid 用户id
 * @property string $inserttime 添加时间
 */
class FzrbsMyApps extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_myapps';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['appid'], 'integer'],
            [['inserttime'], 'safe'],
            [['userid'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'appid' => 'Appid',
            'userid' => 'Userid',
            'inserttime' => 'Inserttime',
        ];
    }
}
