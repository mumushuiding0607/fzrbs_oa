<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_error_record".
 *
 * @property int $id
 * @property string $opt_name 
 * @property string $title 
 * @property string $msg 
 * @property string $created 
 * @property int $tp 
 */
class WxErrorRecord extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_error_record';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // [['username', 'realname', 'mobile'], 'trim'],
            [['tp'], 'integer','on'=>['update','create']],
            [['msg', 'created'], 'safe'],
            [['title','opt_name'], 'string', 'max' => 50,'on'=>['update','create']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'st' => 'st',
            'parentid' => 'parentid',
            'parentids' => 'parentids',
            'order' => 'order',
            'changed' => 'changed',
            'level' => 'level',
            'leader' => 'leader',
            'attribute' => 'attribute',
            'name' => 'name',
        ];
    }
   
}
