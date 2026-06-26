<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;
use app\modules\api\models\FzrbsRouteMenu;

/**
 * 驾驶员派车相关接口类
 */
class TruckDriverController extends ApiBase
{
    public $enableCsrfValidation = false;
    protected $_orderBy = 'id asc';

    public function init()
    {
        parent::init();
    }

    /**
     * 驾驶员去向动作
     */
    public function actionDriverDestination()
    {
        $drawOut = $drawW = $leave = $await = $driversName = [];
        $drivers = (new \yii\db\Query())->select('userid,name')->from('weixin_truck_driver')->all();
        if ($drivers) {
            $driversName = array_combine(array_column($drivers, 'userid'), array_column($drivers, 'name'));
            $userids = array_column($drivers, 'userid');
            //出车状态
            $currentDay = date("Y-m-d");
            $drawOutRows = (new \yii\db\Query())->select('driver,st')->from('weixin_truck_order')->where(['and', ['>=', 'st', 2], ['like', 'start_time', $currentDay]])->all();
            if ($drawOutRows) {
                foreach ($drawOutRows as $key => $item) {
                    if (in_array($item['st'], [2, 3]) && !in_array($item['driver'], $drawW)) {
                        //出车中
                        $drawW[] = $item['driver'];
                        $drawOut[] = $item;
                    } else {
                        //待命中
                    }
                }
            }
            //请假
            $currentTime = date("Y-m-d H:i:s");
            $leave = (new \yii\db\Query())->select('userId')->from('weixin_leave_info')->where(['and', ['=', 'status', 2], ['!=', 'leaveType', '销假'], ['in', 'userId', $userids], ['<=', 'leaveStarttime', $currentTime], ['>=', 'leaveEndtime', $currentTime]])->groupBy('userId')->all();
            $leave = array_column($leave, 'userId');
            foreach ($drivers as $key => $value) {
                //获取状态
                if (!in_array($value['userid'], array_column($drawOut, 'driver')) && !in_array($value['userid'], $leave)) {
                    $await[] = $value['userid'];
                }
            }
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => [
            'drawOut' => $drawOut,
            'leave' => $leave,
            'await' => $await,
            'driversName' => $driversName,
        ]]);
    }
}
