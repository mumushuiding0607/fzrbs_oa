<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oa_department".
 *
 * @property int $id
 * @property string|null $name 部门名称
 * @property int|null $parentid 父部门id
 * @property int|null $order 排序序号
 * @property int $st 状态(0:删除,1:有效,2:隐藏)
 * @property int $changed 变动状态(0:无变动,1:变动)
 * @property int|null $level 层级
 * @property int|null $attribute 分类(1:采编经营类,2:行政后勤类)
 * @property string|null $leader 部门领导
 * @property string|null $parentids 所有父部门id
 */
class WxDepartment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_leave_department';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['parentid', 'order', 'st', 'changed', 'level', 'attribute'], 'integer'],
            [['name'], 'string', 'max' => 50],
            [['leader', 'parentids'], 'string', 'max' => 255],
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
            'parentid' => 'Parentid',
            'order' => 'Order',
            'st' => 'St',
            'changed' => 'Changed',
            'level' => 'Level',
            'attribute' => 'Attribute',
            'leader' => 'Leader',
            'parentids' => 'Parentids',
        ];
    }

    /**
     * Undocumented function
     *
     * @param [type] $pid
     * @return void
     */
    public function getChildIds($pid)
    {
        $allDepartIds = explode(',', $pid);
        $childArr = [];
        $firstChild = [];
        $first = 1;
        do {
            $ids = '';
            $child = WxDepartment::find()->where(['and' => ['in', 'parentid', $pid]])->all();
            if (count($child) > 0) {
                foreach ($child as $item) {
                    $childArr[] = $item['id'];
                    $allDepartIds[] = $item['id'];
                    $first && $firstChild[] = $item['id'];
                    $ids .= ',' . $item['id'];
                }
                $first = 0;
                $ids = substr($ids, 1, strlen($ids));
                $pid = $ids;
            }
        } while (!empty($child));
        return ['all' => $allDepartIds, 'child' => $childArr, 'subChild' => $firstChild];
    }
}
