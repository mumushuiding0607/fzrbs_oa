<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oa_attachment".
 *
 * @property int $id
 * @property string $userId 企业微信userid
 * @property int $appId 企业微信应用id
 * @property string|null $baseName 原文件名
 * @property string|null $saveAs 现存路径名称
 * @property string|null $fileType 文件扩展名
 * @property string|null $mimeType 文件类型
 * @property int|null $fileSize 文件大小
 * @property string|null $savePath 存储物理路径
 * @property string|null $inserttime
 * @property int|null $state -1：删除
 */
class WeixinOaAttachment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_oa_attachment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['userId'], 'required'],
            [['appId', 'fileSize', 'state'], 'integer'],
            [['inserttime'], 'safe'],
            [['userId'], 'string', 'max' => 20],
            [['baseName', 'savePath'], 'string', 'max' => 250],
            [['saveAs'], 'string', 'max' => 50],
            [['fileType'], 'string', 'max' => 5],
            [['mimeType'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userId' => 'User ID',
            'appId' => 'App ID',
            'baseName' => 'Base Name',
            'saveAs' => 'Save As',
            'fileType' => 'File Type',
            'mimeType' => 'Mime Type',
            'fileSize' => 'File Size',
            'savePath' => 'Save Path',
            'inserttime' => 'Inserttime',
            'state' => 'State',
        ];
    }
}
