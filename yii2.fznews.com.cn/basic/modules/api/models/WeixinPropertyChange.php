<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_property_change".
 *
 * @property int $id
 * @property int|null $property_id 资产ID
 * @property string|null $out_no 出库编号
 * @property int|null $out_stid 出库状态（对应setting表状态）
 * @property int|null $nums 数量
 * @property float|null $price 出售单价/出租金额/报废价格
 * @property string|null $scrap_file 报废文件地址
 * @property string|null $scrap_section 回收单位
 * @property string|null $take_uid 领用/借用人(对应职员表)
 * @property int|null $operdator_id 经办人(对应系统用户ID)
 * @property string|null $operdator 经办人(对应系统用户名称)
 * @property int|null $department_id 部门（对应一级部门ID）废弃了
 * @property string|null $outdate 出库时间/报废时间
 * @property string|null $estimatedindate 预计归库时间
 * @property string|null $indate 归库时间
 * @property string|null $remark 备注
 * @property string|null $inserttime 添加时间
 * @property int $scrap_way 报废途径 1 '本单位报废',2 '主管部门报废',3'财政局报废'
 * @property int $usage_mode 1 闲置 2 个人领用 3 部门领用
 * @property int|null $pid 上一级id
 * @property int|null $state -1 删除  1 正常
 */
class WeixinPropertyChange extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_property_change';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['property_id', 'out_stid', 'nums', 'operdator_id', 'department_id', 'scrap_way', 'usage_mode', 'pid', 'state'], 'integer','on'=>['update','create']],
            [['price'], 'number','on'=>['update','create']],
            [['scrap_file'], 'string','on'=>['update','create']],
            [['outdate', 'estimatedindate', 'indate', 'inserttime'], 'safe','on'=>['update','create']],
            [['out_no', 'take_uid', 'operdator'], 'string', 'max' => 50,'on'=>['update','create']],
            [['scrap_section'], 'string', 'max' => 255,'on'=>['update','create']],
            [['remark'], 'string', 'max' => 500,'on'=>['update','create']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'property_id' => 'Property ID',
            'out_no' => 'Out No',
            'out_stid' => 'Out Stid',
            'nums' => 'Nums',
            'price' => 'Price',
            'scrap_file' => 'Scrap File',
            'scrap_section' => 'Scrap Section',
            'take_uid' => 'Take Uid',
            'operdator_id' => 'Operdator ID',
            'operdator' => 'Operdator',
            'department_id' => 'Department ID',
            'outdate' => 'Outdate',
            'estimatedindate' => 'Estimatedindate',
            'indate' => 'Indate',
            'remark' => 'Remark',
            'inserttime' => 'Inserttime',
            'scrap_way' => 'Scrap Way',
            'usage_mode' => 'Usage Mode',
            'pid' => 'Pid',
            'state' => 'State',
        ];
    }


}
