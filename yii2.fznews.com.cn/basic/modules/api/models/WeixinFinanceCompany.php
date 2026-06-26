<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_finance_company".
 *
 * @property int $id
 * @property string|null $company
 * @property string|null $dept
 * @property string|null $userid 主体负责人userid
 * @property string|null $username 主体负责人
 */
class WeixinFinanceCompany extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_finance_company';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['company', 'dept', 'userid', 'username'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'company' => 'Company',
            'dept' => 'Dept',
            'userid' => 'Userid',
            'username' => 'Username',
        ];
    }
}
