<?php

namespace app\modules\api\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "weixin_news".
 *
 * @property int $id
 * @property string $title 主标题
 * @property string|null $subtitle 副标题
 * @property string|null $shorttitle 短标题
 * @property string|null $writer 作者
 * @property string|null $source 来源
 * @property string|null $keywords 关键字
 * @property int $channelid 栏目id
 * @property string|null $image 标题图
 * @property string|null $redirect 跳转网址
 * @property int $click 浏览量
 * @property int $click1 其他统计
 * @property int $state 状态(-1:回收站,0:未审/撤销,1:已签)
 * @property string|null $editor 编辑姓名
 * @property string|null $remark 摘要
 * @property string $inserttime 添加时间
 * @property string $publictime 发布时间
 * @property int|null $displayorder 排序序号
 * @property int|null $templateid 模板id
 * @property string|null $editorname 编辑用户名
 * @property int|null $appid 应用id
 * @property int|null $num 投票总数
 * @property int|null $sort 排序值
 */
class WeixinNews extends \yii\db\ActiveRecord
{
    public $goodnum;
    public $flowernum;
    public $commentnum;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_news';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['title'], 'required', 'message' => '标题必填', 'on' => ['create', 'update']],
            [['channelid', 'click', 'click1', 'state', 'displayorder', 'templateid', 'appid', 'num'], 'integer'],
            [['title', 'subtitle', 'source', 'writer'], 'trim'],
            [['inserttime', 'publictime', 'sort'], 'safe'],
            [['title', 'subtitle'], 'string', 'max' => 150],
            [['shorttitle', 'source', 'keywords', 'editorname'], 'string', 'max' => 50],
            [['writer', 'image'], 'string', 'max' => 100],
            [['redirect'], 'string', 'max' => 250],
            [['editor'], 'string', 'max' => 20],
            [['remark'], 'string', 'max' => 1500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'subtitle' => 'Subtitle',
            'shorttitle' => 'Shorttitle',
            'writer' => 'Writer',
            'source' => 'Source',
            'keywords' => 'Keywords',
            'channelid' => 'Channelid',
            'image' => 'Image',
            'redirect' => 'Redirect',
            'click' => 'Click',
            'click1' => 'Click 1',
            'state' => 'State',
            'editor' => 'Editor',
            'remark' => 'Remark',
            'inserttime' => 'Inserttime',
            'publictime' => 'Publictime',
            'displayorder' => 'Displayorder',
            'templateid' => 'Templateid',
            'editorname' => 'Editorname',
            'appid' => 'Appid',
            'num' => 'Num',
            'sort' => 'Sort',
        ];
    }
}
