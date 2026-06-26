<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_oauser_department".
 *
 * @property int $id
 * @property string $name 
 * @property string $parentid 
 * @property string $parentids 
 * @property string $order 
 * @property int $st 
 * @property string $changed 
 * @property string $level 
 * @property string $attribute 
 * @property string $leader 

 */
class WxOauserDepartment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_oauser_department';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // [['username', 'realname', 'mobile'], 'trim'],
            [['st', 'parentid','order','changed'], 'integer','on'=>['update','create']],
            [['level', 'attribute','leader','parentids'], 'safe'],
            [['name'], 'string', 'max' => 50,'on'=>['update','create']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'st' => 'st',
            'parentid' => 'parentid',
            'parentids' => 'parentids',
            'order' => 'order',
            'changed' => 'changed',
            'level' => 'level',
            'leader' => 'leader',
            'attribute' => 'attribute',
            'name' => 'name',
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
            // $child = Yii::app()->db1->createCommand("select id from weixin_leave_department where st = 1 and parentid in( $pid ) ")->queryAll();
            
            $child = WxOauserDepartment::find()->where(['and' ,['>','st',0],['in','parentid',$pid]])->all();
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
        return ['all' => $allDepartIds, 'child' => $childArr,'subChild'=>$firstChild];

    }
}
