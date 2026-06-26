<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "fzrbs_operation_log_params".
 *
 * @property int $id
 * @property int|null $logid 操作日志id
 * @property string|null $params 请求参数
 */
class FzrbsOperationLogParams extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_operation_log_params';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['logid'], 'integer'],
            [['params'], 'string'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'logid' => 'Logid',
            'params' => 'Params',
        ];
    }
}
