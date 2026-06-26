<?php

namespace app\modules\api\models;

use Yii;

/**
 * This is the model class for table "weixin_order_template".
 *
 * @property int $id
 * @property string $templatename 模板名称
 * @property string $templateid 流程id
 * @property string $type 用户职级
 * @property string $dids 部门id
 * @property string $uids 用户id
 * @property string $creator 创建人id
 * @property string $creatorname 创建人
 * @property string $updator 更新人
 * @property int $agentid 应用id
 * @property string $updatetime 更新时间
 * @property int $isdel 是否删除
 */
class WeixinOrderTemplate extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'weixin_order_template';
    }

}
