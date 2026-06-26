<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;
use app\modules\api\models\ShitangCanteenOrder;

/**
 * 食堂大屏数据显示相关接口类
 */
class CanteenScreenController extends ApiBase
{
    public $enableCsrfValidation = false;
    protected $_orderBy = 'id desc';
    // 食堂开饭了应用id
    protected $_agentId = 1000002;

    public function init()
    {
        parent::init();
    }

    /**
     * 各时段订单统计动作
     */
    public function actionOrderCount()
    {
        $tableName = ShitangCanteenOrder::tableName();
        $where = [
            'and',
            ['<', 'status', 2],
            ['>', 'public', 0],
            ['=', 'typeid', 1],
            ['=', 'menudate', date('Ymd')],
        ];
        $res = (new \yii\db\Query())->select('public, count(id) as total')->from($tableName)->where($where)->groupBy('public')->all();
        if ($res) {
            $this->_result['data'] = $res;
        }
        Tools::responseJson($this->_result);
    }

    /**
     * 投票弹窗动作
     */
    public function actionMenuNum()
    {
        $h = intval(date('H'));
        $where = [
            'and',
            ['=', 'status', 0],
            ['!=', 'typeid', 5],
            ['=', 'menudate', date('Ymd')],
        ];
        if ($h >= 11) {
            $where[] = ['!=', 'typeid', 3];
        } else if ($h <= 10) {
            $where[] = ['=', 'typeid', 3];
        }
        $modals = ShitangCanteenOrder::find()->where($where)->all();
        $menu = $menuInfo = [];
        foreach ($modals as $row) {
            $orderInfo  = explode(',', $row->orderinfo);
            foreach ($orderInfo as $v) {
                $itemInfo = explode('|', $v);
                $id = $itemInfo[0];
                if (!in_array($id, $menu)) {
                    $menu[] = $id;
                    $menuInfo[$id]['count'] = $itemInfo[3];
                    $menuInfo[$id]['name'] = $itemInfo[1];
                    if (in_array($row->typeid, array(1, 2, 3))) {
                        $menuInfo[$id]['type'][$row->typeid] = $itemInfo[3];
                    }
                } else {
                    $menuInfo[$id]['count'] = $itemInfo[3] + $menuInfo[$id]['count'];
                    if (in_array($row->typeid, array(1, 2, 3))) {
                        $menuInfo[$id]['type'][$row->typeid] = intval($menuInfo[$id]['type'][$row->typeid]) + $itemInfo[3];
                    }
                }
            }
        }
        $tableName = ShitangCanteenOrder::tableName();
        $where1 = $where;
        $where1[] = ['=', 'typeid', 1];
        $a = (new \yii\db\Query())->select('count(*)')->from($tableName)->where($where1)->createCommand()->queryScalar();
        $this->_result['menuInfo'] = $menuInfo;
        $this->_result['a'] = $a;
        Tools::responseJson($this->_result);
    }
}
