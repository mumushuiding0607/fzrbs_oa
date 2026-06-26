<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_channel".
 *
 * @property int $id
 * @property string $name 栏目名称
 * @property string|null $parentids 所有父栏目id
 * @property int $parentid 直接父栏目id
 * @property string $inserttime 添加时间
 * @property int $childres 子孙栏目数
 * @property int|null $level 栏目层级
 * @property int|null $displayorder 栏目排序
 * @property string|null $content 栏目说明文字
 * @property string|null $linkurl 栏目链接地址
 * @property string|null $image 栏目图片
 * @property int|null $navshow 是否在导航显示
 * @property int|null $display 是否在后台显示
 */
class WeixinChannel extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_channel';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required', 'message' => '名称必填', 'on' => ['create', 'update']],
            [['parentid', 'childres', 'level', 'displayorder', 'navshow', 'display'], 'integer'],
            [['name'], 'trim'],
            [['inserttime'], 'safe'],
            [['content'], 'string'],
            [['name'], 'string', 'max' => 50],
            [['parentids', 'linkurl', 'image'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'parentids' => 'Parentids',
            'parentid' => 'Parentid',
            'inserttime' => 'Inserttime',
            'childres' => 'Childres',
            'level' => 'Level',
            'displayorder' => 'Displayorder',
            'content' => 'Content',
            'linkurl' => 'Linkurl',
            'image' => 'Image',
            'navshow' => 'Navshow',
            'display' => 'Display',
        ];
    }
}
