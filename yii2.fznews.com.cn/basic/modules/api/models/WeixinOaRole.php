<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oa_role".
 *
 * @property int $id
 * @property string|null $rolename
 */
class WeixinOaRole extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_oa_role';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['rolename'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'rolename' => 'Rolename',
        ];
    }
}
