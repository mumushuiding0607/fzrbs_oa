<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oa_templates".
 *
 * @property int $id
 * @property string|null $templateId
 * @property string|null $templateName
 * @property int|null $notifyAttr
 * @property string|null $templateData  审核
 * attr：1-或签；2-会签；
 * type：3上级，2标签，1单个成员
 * level(type=3)：1直接上级 2 第二级。。。。
 * 抄送
 * attr：1提交申请时抄送；2审批通过后抄送；3提交申请时和审批通过后都抄送
 * type：3上级，2标签，1单个成员
 * level(type=3)：1直接上级 2 第二级。。。。
 * @property int|null $appid
 * @property string|null $appname
 */
class WeixinOaTemplates extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_templates';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['notifyAttr', 'appid'], 'integer'],
            [['templateData'], 'string'],
            [['templateId', 'templateName','updatetor'], 'string', 'max' => 50],
            [['appname'], 'string', 'max' => 250]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'templateId' => 'Template ID',
            'templateName' => 'Template Name',
            'notifyAttr' => 'Notify Attr',
            'templateData' => 'Template Data',
            'appid' => 'Appid',
            'appname' => 'Appname',
        ];
    }
}
