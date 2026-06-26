<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "shitang_canteenmenu".
 *
 * @property int $id
 * @property string $name 菜单名称
 * @property int|null $typeid 分类(1:午餐,2:晚餐,3:早餐,4:其他,5:代购,6:面对面,7:咖啡,100:现煮)
 * @property string $image 图片
 * @property int|null $price 价格
 * @property int|null $buynum 总预订数
 * @property int|null $todaynum 当天预订数
 * @property int|null $inserttime 添加时间
 * @property int|null $support 喜欢数
 * @property string|null $star 评星
 * @property int|null $status 状态(0:下线,1:上线)
 * @property string|null $menudate 星期
 * @property string|null $menudate1 日期
 * @property int|null $menudate2 时段(0:全天,1:午餐,2:晚餐,3:早餐)
 * @property string|null $introduce 详情
 * @property int|null $sid 原菜单id
 * @property int|null $buylimit 预订限制数
 * @property int|null $totallimit 库存数量
 * @property int|null $displayorder 排序序号
 */
class ShitangCanteenMenu extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'shitang_canteenmenu';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'menudate', 'menudate1', 'price'], 'required', 'message' => '{attribute}必填', 'on' => ['create', 'update']],
            [['menudate'], 'checkDay', 'on' => ['create', 'update']],
            [['name', 'menudate', 'menudate1'], 'trim'],
            [['typeid', 'price', 'buynum', 'todaynum', 'inserttime', 'support', 'status', 'menudate2', 'sid', 'buylimit', 'totallimit', 'displayorder'], 'integer'],
            [['name', 'image', 'star'], 'string', 'max' => 250],
            [['menudate', 'menudate1'], 'string', 'max' => 8],
            [['introduce'], 'string', 'max' => 500],
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
            'typeid' => 'Typeid',
            'image' => 'Image',
            'price' => 'Price',
            'buynum' => 'Buynum',
            'todaynum' => 'Todaynum',
            'inserttime' => 'Inserttime',
            'support' => 'Support',
            'star' => 'Star',
            'status' => 'Status',
            'menudate' => 'Menudate',
            'menudate1' => 'Menudate 1',
            'menudate2' => 'Menudate 2',
            'introduce' => 'Introduce',
            'sid' => 'Sid',
            'buylimit' => 'Buylimit',
            'totallimit' => 'Totallimit',
            'displayorder' => 'Displayorder',
        ];
    }

    /**
     * 自定义验证选择日期是否和选择星期一致
     */
    public function checkDay($attribute, $params)
    {
        $day = substr($this->menudate1, 0, 4) . '-' . substr($this->menudate1, 4, 2) . '-' . substr($this->menudate1, 6, 2);
        $week = date('w', strtotime($day));
        $week = $week == 0 ? 7 : $week;
        if ($week != $this->menudate) {
            $this->addError($attribute, "选择的星期与日期不符");
        }
    }
}
