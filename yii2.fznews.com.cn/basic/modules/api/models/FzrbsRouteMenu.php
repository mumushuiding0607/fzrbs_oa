<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "fzrbs_route_menu".
 *
 * @property int $id
 * @property string $name 名称
 * @property string|null $path 路由
 * @property string $icon 图标
 * @property string|null $access 预先配置的权限
 * @property int|null $hidechildreninmenu 用于隐藏不需要在菜单中展示的子路由
 * @property int|null $hideinmenu 可以在菜单中不展示这个路由，包括子路由
 * @property int|null $hideinbreadcrumb 可以在面包屑中不展示这个路由，包括子路由
 * @property int|null $headerrender 当前路由不展示顶栏
 * @property int|null $menurender 当前路由不展示菜单
 * @property int|null $menuheaderrender 当前路由不展示菜单顶栏
 * @property string|null $parentids 所有父节点id
 * @property int $parentid 直接父节点id
 * @property int $childrenmenunum 子节点数
 * @property int|null $displayorder 显示序号
 * @property string|null $image 路由图片
 * @property string $inserttime 添加时间
 */
class FzrbsRouteMenu extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fzrbs_route_menu';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'path'], 'required', 'message' => '{attribute}必填', 'on' => ['create', 'update']],
            [['name', 'path'], 'trim'],
            [['hidechildreninmenu', 'hideinmenu', 'hideinbreadcrumb', 'headerrender', 'menurender', 'menuheaderrender', 'parentid', 'childrenmenunum', 'displayorder'], 'integer'],
            [['inserttime'], 'safe'],
            [['name', 'icon'], 'string', 'max' => 50],
            [['path', 'parentids'], 'string', 'max' => 250],
            [['access', 'image'], 'string', 'max' => 255],
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
            'path' => 'Path',
            'icon' => 'Icon',
            'access' => 'Access',
            'hidechildreninmenu' => 'Hidechildreninmenu',
            'hideinmenu' => 'Hideinmenu',
            'hideinbreadcrumb' => 'Hideinbreadcrumb',
            'headerrender' => 'Headerrender',
            'menurender' => 'Menurender',
            'menuheaderrender' => 'Menuheaderrender',
            'parentids' => 'Parentids',
            'parentid' => 'Parentid',
            'childrenmenunum' => 'Childrenmenunum',
            'displayorder' => 'Displayorder',
            'image' => 'Image',
            'inserttime' => 'Inserttime',
        ];
    }
}
