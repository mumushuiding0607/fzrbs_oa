<?php

namespace app\modules\api\models;

use Yii;


class FsysNode extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'fsys_node';
    }
}