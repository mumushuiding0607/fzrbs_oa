<?php

namespace app\modules\api\models;

use app\modules\api\models\WeixinPropertySetting;
use app\modules\api\models\WeixinPropertyExtend;
use Yii;

/**
 * This is the model class for table "weixin_property".
 *
 * @property int $id
 * @property string|null $property_no 资产编号
 * @property string|null $property_name 资产名称
 * @property int|null $property_tpid 资产类别(对应setting表类别)
 * @property string|null $property_brand 资产品牌/房屋地址
 * @property string|null $property_model  资产规格 （型号/排量/户型）
 * @property int|null $buynums 购买数量
 * @property int|null $stock 库存数量
 * @property string|null $unit 单位
 * @property float|null $price 单价
 * @property float|null $total_price 总价
 * @property int|null $funds_source 资金来源
 * @property string|null $buydate 购置日期
 * @property string|null $buyway 购置方式
 * @property int|null $department_id 所属部门(对应一级部门ID)
 * @property string|null $extend 扩展项(保存json数据,数据项对应extend表)
 * @property string|null $remark 备注
 * @property string|null $inserttime 添加时间
 * @property int $st 0 删除 1 正常
 * @property int $opt_id 操作者id
 * @property string|null $username 使用人暂时没用
 * @property string|null $code 机身编码
 * @property string|null $financial_code 财务编码
 * @property string|null $img_url 图片
 */
class WeixinProperty extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_property';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['property_tpid', 'buynums', 'stock', 'funds_source', 'department_id', 'st', 'opt_id'], 'integer','on'=>['update','create']],
            [['price', 'total_price'], 'number','on'=>['update','create']],
            [['buydate', 'inserttime'], 'safe','on'=>['update','create']],
            [['extend', 'remark'], 'string','on'=>['update','create']],
            [['property_no', 'property_name', 'unit', 'buyway'], 'string', 'max' => 50,'on'=>['update','create']],
            [['property_brand', 'property_model'], 'string', 'max' => 500,'on'=>['update','create']],
            [['username', 'code', 'financial_code', 'img_url'], 'string', 'max' => 255,'on'=>['update','create']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'property_no' => 'Property No',
            'property_name' => 'Property Name',
            'property_tpid' => 'Property Tpid',
            'property_brand' => 'Property Brand',
            'property_model' => 'Property Model',
            'buynums' => 'Buynums',
            'stock' => 'Stock',
            'unit' => 'Unit',
            'price' => 'Price',
            'total_price' => 'Total Price',
            'funds_source' => 'Funds Source',
            'buydate' => 'Buydate',
            'buyway' => 'Buyway',
            'department_id' => 'Department ID',
            'extend' => 'Extend',
            'remark' => 'Remark',
            'inserttime' => 'Inserttime',
            'st' => 'St',
            'opt_id' => 'Opt ID',
            'username' => 'Username',
            'code' => 'Code',
            'financial_code' => 'Financial Code',
            'img_url' => 'Img Url',
        ];
    }

}
