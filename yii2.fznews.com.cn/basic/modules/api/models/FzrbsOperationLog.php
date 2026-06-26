<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "fzrbs_operation_log".
 *
 * @property int $id
 * @property int $userid 用户id
 * @property string $username 用户名
 * @property string $realname 用户姓名
 * @property string $catalog 操作类别
 * @property string $url 操作url
 * @property string|null $remark 操作备注
 * @property string $ip ip
 * @property int $inserttime 操作时间
 */
class FzrbsOperationLog extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_operation_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['userid', 'inserttime'], 'integer'],
            [['remark'], 'string'],
            [['username', 'realname', 'catalog', 'url'], 'string', 'max' => 250],
            [['ip'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userid' => 'Userid',
            'username' => 'Username',
            'realname' => 'Realname',
            'catalog' => 'Catalog',
            'url' => 'Url',
            'remark' => 'Remark',
            'ip' => 'Ip',
            'inserttime' => 'Inserttime',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function fields()
    {
        $fields =  parent::fields();
        $fields['inserttime'] = function () {
            return date('Y-m-d H:i:s', $this->inserttime);
        };
        return $fields;
    }
}
