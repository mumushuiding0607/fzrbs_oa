<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_housing".
 *
 * @property int $id
 * @property string $opt_name 
 * @property string $title 
 * @property string $msg 
 * @property string $created 
 * @property int $tp 
 */
class WxHousing extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_housing';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // [['username', 'realname', 'mobile'], 'trim'],
            [['start_time','tp_id','start_time','end_time','rent_date','notice','st'], 'integer','on'=>['update','create']],
            [['msg', 'addr','monthly_rent','created'], 'safe'],
            [['project'], 'string', 'max' => 255,'on'=>['update','create']],
            [['mobile'], 'string', 'max' => 11,'on'=>['update','create']],
            [['lessee'], 'string', 'max' => 50,'on'=>['update','create']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'project' => 'project',
            'tp_id' => 'tp_id',
            'addr' => 'addr',
            'lessee' => 'lessee',
            'mobile' => 'mobile',
            'start_time' => 'start_time',
            'end_time' => 'end_time',
            'monthly_rent' => 'monthly_rent',
            'rent_date' => 'rent_date',
            'created' => 'created',
            'notice' => 'notice',
            'st' => 'st',
        ];
    }
   
}
