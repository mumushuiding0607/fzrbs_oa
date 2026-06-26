<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\commons\Tools;
use app\modules\api\commons\Uploader;
use app\modules\api\commons\ConfigData;
use app\modules\api\models\WeixinRechargeLog;
use app\modules\api\models\ShitangCanteenOrder;
use app\modules\api\models\WeixinStaffMonth;
use app\modules\api\models\ShitangCanteenMenu;
use app\modules\api\models\WeixinRechargeLogImpro;
use app\modules\api\models\WeixinOAUserInfo;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * 食堂管理相关接口类
 */
class CanteenController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinStaff';
    protected $_orderBy = 'id desc';
    protected $_departmentIds = [];
    protected $_departmentNames = [];
    // 用户账号结算类别
    protected $_userType = [];
    // 食堂菜单类别
    protected $_orderType = [];
    // 用户订单状态类别
    protected $_orderStatus = [];
    // 用户订单支付类别
    protected $_orderPayType = [];
    // 供应时段
    protected $_timeInterval = [];
    // 晚报运营中心-社聘的人员单独一张表
    protected $_wbyyspry = ['LiJingSi', 'ChenLin', 'XiaoJunJie'];
    // 晚报-公司聘的人员单独一张表
    protected $_wbgspry = ['ZhouTaoMao', 'ChenXiaoQin', 'LiDanNa', 'ZhengWei', 'ChiJuanJuan', 'WuYanXin', 'JinQingHua', 'ZhengJinLuan', 'WangYing', 'LuYi'];
    // 日报运营中心-社聘的人员单独一张表
    protected $_rbyyspry = ['caocong', 'linxiaoshan', 'liqiang', 'luoxiaoli', 'chenbeixi', 'chenweichao', 'yushaolin'];
    // 退单超时时间
    protected $_timeout = 86400;

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }

    public function init()
    {
        parent::init();
    }

    /**
     * 重写index的业务实现动作
     */
    public function actionIndex()
    {
        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        $departmentId = isset($this->_request['departmentid']) ? intval($this->_request['departmentid']) : 0;
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if ($departmentId > 1 && !isset($this->_request['search'])) {
            $this->_getDepartmentIds($departmentId);
            if ($this->_departmentIds) {
                $where[] = ['in', 'departmentid', $this->_departmentIds];
            }
        }
        if ($this->_request['username']) {
            $where[] = ['like', 'username', $this->_request['username']];
        }
        if ($this->_request['mobile']) {
            $where[] = ['=', 'mobile', $this->_request['mobile']];
        }
        if (isset($this->_request['usertype'])) {
            $where[] = ['=', 'usertype', $this->_request['usertype']];
        }
        $model = $this->modelClass;
        $model = $model::find()->where($where);
        $total = $model->count();
        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        foreach ($res as $row) {
            $info = $row->attributes;
            $info['balance'] = number_format(($row->balance / 100), 2, '.', '');
            $info['weixinbalance'] = number_format(($row->weixinbalance / 100), 2, '.', '');
            $this->_result['data'][] = $info;
        }
        return $this->_result;
    }

    /**
     * 重写delete的业务实现动作
     */
    public function actionDelete()
    {
        if ($this->_request['id']) {
            $ids = explode(',', $this->_request['id']);
            $names = [];
            $models = $this->modelClass::find()->where(['in', 'id', $ids])->all();
            foreach ($models as $model) {
                $names[] = $model->userid;
                $model->delete();
            }
            if ($names) {
                $action = '删除';
                $remark = $action . "食堂账号。账号名称：" . implode(',', $names) . "。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 同步企业通讯录账号
     */
    public function actionAsynchronization()
    {
        if (isset($this->_request['keys'])) {
            set_time_limit(0);
            $updateCount = 0;
            $keys =  $this->_request['keys'];
            $departments = $users = $tempDepartments = $tempUsers = $tempUserDepartmentIds = $tempUserDepartmentNames = [];
            foreach ($keys as $key) {
                if (is_numeric($key)) {
                    $departments[] = $key;
                } else {
                    $users[] = $key;
                }
            }
            if ($departments) {
                if (array_search(1, $departments) !== false) {
                    $this->_getDepartmentIds(1);
                    $departments = $this->_departmentIds;
                } else {
                    $tempDepartments = $departments;
                    foreach ($departments as $id) {
                        $this->_getDepartmentIds($id);
                        $subDepartmentIds = $this->_departmentIds;
                        if ($subDepartmentIds) {
                            foreach ($subDepartmentIds as $subId) {
                                $tempDepartments[] = $subId;
                            }
                        }
                    }
                    $departments = array_unique($tempDepartments);
                }
                foreach ($departments as $id) {
                    $sendResult = WxQyhJk::departmentUserMore($id);
                    $departmentUsers = $sendResult['data'];
                    if ($departmentUsers) {
                        foreach ($departmentUsers as $user) {
                            if (!in_array($user['userid'], $tempUsers)) {
                                $tempUsers[] = $user['userid'];
                                $userDepartmentId = $user['department'][0];
                                $userDepartmentName = '';
                                if (!in_array($userDepartmentId, $tempUserDepartmentIds)) {
                                    $sendResult = WxQyhJk::department($userDepartmentId);
                                    $tempDepartments = $sendResult['data'];
                                    if (is_array($tempDepartments) && count($tempDepartments) > 0) {
                                        foreach ($tempDepartments as $v) {
                                            if ($v['id'] == $userDepartmentId) {
                                                $userDepartmentName = $v['name'];
                                                $tempUserDepartmentIds[] = $v['id'];
                                                $tempUserDepartmentNames[$v['id']] = $v['name'];
                                                break;
                                            }
                                        }
                                    }
                                } else {
                                    $userDepartmentName = $tempUserDepartmentNames[$userDepartmentId];
                                }
                                $username = trim(str_replace([' ', '  ', '　', '　　'], '', $user['name']));
                                $data = array(
                                    'userid' => $user['userid'],
                                    'username' => $username,
                                    'mobile' => $user['mobile'],
                                    'avatar' => $user['avatar'],
                                    'departmentid' => $userDepartmentId,
                                    'departmentname' => $userDepartmentName,
                                    'gender' => $user['gender']
                                );
                                $model = $this->modelClass::find()->where(['=', 'userid', $user['userid']])->one();
                                if ($model == null) {
                                    $model = new $this->modelClass(['scenario' => 'create']);
                                } else {
                                    $model->scenario = 'update';
                                }
                                $model->attributes = $data;
                                if ($model->save()) {
                                    $updateCount++;
                                };
                            }
                        }
                    }
                }
            }
            if ($users) {
                foreach ($users as $user) {
                    if (!in_array($user, $tempUsers)) {
                        $sendResult =  WxQyhJk::user($user);
                        $info = $sendResult['data'];
                        if ($info) {
                            $userDepartmentId = $info['department'][0];
                            $userDepartmentName = '';
                            if (!isset($tempUserDepartmentIds[$userDepartmentId])) {
                                $sendResult = WxQyhJk::department($userDepartmentId);
                                $tempDepartments = $sendResult['data'];
                                if (is_array($tempDepartments) && count($tempDepartments) > 0) {
                                    foreach ($tempDepartments as $v) {
                                        if ($v['id'] == $userDepartmentId) {
                                            $userDepartmentName = $v['name'];
                                            $tempUserDepartmentIds[] = $v['id'];
                                            $tempUserDepartmentNames[$v['id']] = $v['name'];
                                            break;
                                        }
                                    }
                                }
                            }
                            $username = trim(str_replace([' ', '  ', '　', '　　'], '', $info['name']));
                            $data = array(
                                'userid' => $info['userid'],
                                'username' => $username,
                                'mobile' => $info['mobile'],
                                'avatar' => $info['avatar'],
                                'departmentid' => $userDepartmentId,
                                'departmentname' => $userDepartmentName,
                                'gender' => $info['gender']
                            );
                            $model = $this->modelClass::find()->where(['=', 'userid', $info['userid']])->one();
                            if ($model == null) {
                                $model = new $this->modelClass(['scenario' => 'create']);
                            } else {
                                $model->scenario = 'update';
                            }
                            $model->attributes = $data;
                            if ($model->save()) {
                                $updateCount++;
                            }
                        }
                    }
                }
            }
            $this->_result['updateCount'] = $updateCount;
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 部门移动
     */
    public function actionCut()
    {
        $fromId = $this->_request['fromId'];
        $toId = $this->_request['toId'];
        $infoIds = $this->_request['infoIds'];
        if ($fromId && $toId && $infoIds) {
            $this->_getDepartmentIds($fromId);
            $index = array_search($fromId, $this->_departmentIds);
            if ($index !== false) {
                $fromDepartmentName = $this->_departmentNames[$index];
            }
            $this->_getDepartmentIds($toId);
            $index = array_search($toId, $this->_departmentIds);
            if ($index !== false) {
                $toDepartmentName = $this->_departmentNames[$index];
            }
            $titles = [];
            $models = $this->modelClass::find()->where(['in', 'id', $infoIds])->all();
            foreach ($models as $model) {
                $titles[] = $model->username . '(' . $model->userid . ')';
                $model->departmentid = $toId;
                $model->departmentname = $toDepartmentName;
                $model->update(false);
            }
            if ($titles) {
                $action = '移动';
                $remark = $action . "食堂账号部门。账号：" . implode(',', $titles) . "。" . ($fromDepartmentName ? '从 ' . $fromDepartmentName : '') . ($toDepartmentName ? '移动到 ' . $toDepartmentName : '');
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 账号充值
     */
    public function actionRecharge()
    {
        $id = $this->_request['id'];
        $value = $this->_request['value'];
        if ($id && $value) {
            $model = $this->modelClass::findOne($id);
            if ($model) {
                $value = $value * 100;
                $model->scenario = 'update';
                $model->balance = $model->balance + $value;
                if ($model->save()) {
                    $formatValue = number_format(($value / 100), 2, '.', '');
                    $intro = '充值金额：' . $formatValue . '，用户：' . $model->username . '，部门：' . $model->departmentname;
                    $data = [
                        'uid' => $this->_adminInfo['id'],
                        'uname' => $this->_adminInfo['username'],
                        'urealname' => $this->_adminInfo['realname'],
                        'targetuname' => $model->userid,
                        'targetrealname' => $model->username,
                        'departmentname' => $model->departmentname,
                        'intro' => $intro,
                        'inserttime' => time(),
                        'rechargemoney' => $value,
                        'rechargeall' => $value,
                        'rechargeusers' => $model->userid,
                        'usertype' => $model->usertype,
                    ];
                    $logModel = new WeixinRechargeLog;
                    $logModel->attributes = $data;
                    $logModel->save();
                    $action = '充值';
                    $remark = $action . "食堂账号。账号：" . $model->userid . '，姓名：' . $model->username . '，充值金额：' . $formatValue;
                    $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
                }
            } else {
                $this->_result = Tools::wrongRules(1000, '参数错误');
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 设置账号结算类别
     */
    public function actionSetUserType()
    {
        $typeId = $this->_request['typeid'];
        $userIds = $this->_request['userIds'];
        if ($typeId && $userIds) {
            $this->_getConfigData();
            $titles = [];
            $models = $this->modelClass::find()->where(['in', 'id', $userIds])->all();
            foreach ($models as $model) {
                $titles[] = $model->username . '(' . $model->userid . ')';
                $model->usertype = $typeId;
                $model->update(false);
            }
            if ($titles) {
                $action = '设置';
                $remark = $action . "食堂账号结算类别。账号：" . implode(',', $titles) . "。分类：" . $this->_userType[$typeId]['text'];
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 食堂账号充值日志
     */
    public function actionRechargeLog()
    {
        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if ($this->_request['targetrealname']) {
            $where[] = ['=', 'targetrealname', $this->_request['targetrealname']];
        }
        if ($this->_request['departmentname']) {
            $where[] = ['=', 'departmentname', $this->_request['departmentname']];
        }
        if (isset($this->_request['usertype'])) {
            $where[] = ['=', 'usertype', $this->_request['usertype']];
        }
        if (isset($this->_request['weixinpay'])) {
            $where[] = ['=', 'weixinpay', $this->_request['weixinpay']];
        }
        if ($this->_request['inserttime']) {
            $insertTime = explode(',', $this->_request['inserttime']);
            $starTime = strtotime($insertTime[0] . ' 00:00:00');
            $endTime = strtotime($insertTime[1] . ' 23:59:59');
            $where[] = ['between', 'inserttime', $starTime, $endTime];
        }
        $model = new WeixinRechargeLog;
        if (isset($this->_request['type']) && $this->_request['type'] == 'excel') {
            if (isset($this->_request['realname'])) {
                $where[] = ['like', 'intro', $this->_request['realname']];
            }
            $model = new WeixinRechargeLogImpro;
        }
        $model = $model::find()->where($where);
        $total = $model->count();
        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        foreach ($res as $row) {
            $info = $row->attributes;
            $info['rechargemoney'] = number_format(($row->rechargemoney / 100), 2, '.', '');
            $info['rechargeall'] = number_format(($row->rechargeall / 100), 2, '.', '');
            $info['inserttime'] = date('Y-m-d H:i:s', $info['inserttime']);
            $this->_result['data'][] = $info;
        }
        return $this->_result;
    }

    /**
     * 食堂订单列表
     */
    public function actionOrders()
    {
        $this->_getConfigData();
        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if (isset($this->_request['status'])) {
            $where[] = ['in', 'status', explode(',', $this->_request['status'])];
        }
        if (isset($this->_request['payType'])) {
            $where[] = ['in', 'wxpay', explode(',', $this->_request['payType'])];
        }
        if ($this->_request['name']) {
            $where[] = ['=', 'realname', $this->_request['name']];
        }
        if ($this->_request['mobile']) {
            $where[] = ['=', 'mobile', $this->_request['mobile']];
        }
        if ($this->_request['keyword']) {
            $where[] = ['like', 'orderinfo', $this->_request['keyword']];
        }
        if ($this->_request['menuType']) {
            $where[] = ['=', 'typeid', $this->_request['menuType']];
        }
        if ($this->_request['orderTime']) {
            $orderTime = explode(',', $this->_request['orderTime']);
            $starTime = strtotime($orderTime[0] . ' 00:00:00');
            $endTime = strtotime($orderTime[1] . ' 23:59:59');
            $where[] = ['between', 'ordertime', $starTime, $endTime];
        }
        $model = new ShitangCanteenOrder;
        $model = $model::find()->where($where);
        $total = $model->count();
        $this->_result['data']['current'] = $page;
        $this->_result['data']['pageSize'] = $limit;
        $this->_result['data']['total'] = $total;
        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
        $now = time();
        foreach ($res as $row) {
            $info = $row->attributes;
            $info['expire'] = 0;
            $info['ordertime'] = date('Y-m-d H:i:s', $row->ordertime);
            $info['menudate'] = substr($row->menudate, 0, 4) . '-' . substr($row->menudate, 4, 2) . '-' . substr($row->menudate, 6, 2);
            $info['type'] = $this->_orderType[$row->typeid];
            if ($row->status == 0 && $now - $row->ordertime > $this->_timeout) {
                // 未使用订单超过1天，直接设为已过期
                $info['expire'] = 1;
            }
            $info['status'] = $this->_orderStatus[$row->status];
            $info['ordermoney'] = number_format(($row->ordermoney / 100), 2, '.', '');
            $menuInfo  = explode(',', $row->orderinfo);
            $tempMenu = [];
            foreach ($menuInfo as $item) {
                $iteminfo = explode('|', $item);
                $totalprice = $iteminfo[3] * $iteminfo[2];
                $tempMenu[] = $iteminfo[1] . '　' . $iteminfo[3] . '份　￥' . $totalprice . '元　';
            }
            $info['orderinfo'] = $tempMenu;
            $this->_result['data']['data'][] = $info;
        }
        return $this->_result;
    }

    /**
     * 菜单订单退单
     */
    public function actionChargeBack()
    {
        $id = $this->_request['id'];
        if ($id) {
            $model = ShitangCanteenOrder::find()->where([
                'and',
                ['=', 'id', $id],
                ['=', 'status', '0'],
            ])->one();
            if ($model) {
                $UserModel = $this->modelClass::find()->where(['=', 'userid', $model->userid])->one();
                if ($UserModel) {
                    if (time() - $model->ordertime > $this->_timeout) {
                        $this->_result['errorCode'] = 7003;
                        $this->_result['errorMessage'] = '订单已过期';
                    } else {
                        if ($model->wxpay) {
                            $UserModel->weixinbalance = $UserModel->weixinbalance + $model->ordermoney;
                        } else {
                            $UserModel->balance = $UserModel->balance + $model->ordermoney;
                        }
                        $model->status = 2;
                        if ($model->save()) {
                            $UserModel->save();
                            $action = '退单';
                            $remark = "菜单订单退单。账号：" . $UserModel->userid . "(" . $UserModel->username . ")，订单号：" . $model->orderid . "，退单金额：" . number_format(($model->ordermoney / 100), 2, '.', '');
                            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
                        }
                    }
                } else {
                    $this->_result['errorCode'] = 7002;
                    $this->_result['errorMessage'] = '订单所属用户未找到';
                }
            } else {
                $this->_result['errorCode'] = 7001;
                $this->_result['errorMessage'] = '订单未找到';
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 订单下载
     */
    public function actionOrderDownload()
    {
        ini_set("memory_limit", "2048M");
        set_time_limit(0);
        $this->_getConfigData();
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $columns = array(
            'realname' => '订餐姓名',
            'mobile' => '联系电话',
            'menudate' => '用餐日期',
            'typeid' => '用餐时段',
            'orderid' => '订单编号',
            'ordermoney' => '订单金额',
            'ordertime' => '下单时间',
            'status' => '订单状态',
            'ordernum' => '菜品数量',
            'orderinfo' => '菜品详情',
        );
        $i = 0;
        foreach ($columns as $value1) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $i) . '1', $value1);
            $i++;
        }
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if (isset($this->_request['orderTime']) && is_array($this->_request['orderTime']) && $this->_request['orderTime']) {
            $startDate = str_replace('-', '', $this->_request['orderTime'][0]);
            $endDate = str_replace('-', '', $this->_request['orderTime'][1]);
            $where[] = ['>=', 'menudate', $startDate];
            $where[] = ['<=', 'menudate', $endDate];
        }
        if (isset($this->_request['menuType']) && is_array($this->_request['menuType']) && $this->_request['menuType']) {
            $where[] = ['in', 'typeid',  $this->_request['menuType']];
        }
        if (isset($this->_request['status']) && is_array($this->_request['status']) && $this->_request['status']) {
            $where[] = ['in', 'status',  $this->_request['status']];
        } else {
            $where[] = ['<', 'status',  2];
        }
        if (isset($this->_request['userType']) && is_array($this->_request['userType']) && $this->_request['userType']) {
            $res = $this->modelClass::find()->select('userid,departmentname')->where(['in', 'usertype',  $this->_request['userType']])->all();
        } else {
            $res = $this->modelClass::find()->select('userid,departmentname')->all();
        }
        $users = $departments = [];
        foreach ($res as $row) {
            $users[] = $row->userid;
            $departments[$row->userid] = $row->departmentname;
        }
        $orderModel = new ShitangCanteenOrder;
        $res = $orderModel::find()->where($where)->all();
        $i = $wxTotalFee = 0;
        $menuId = $menuInfo = $userId = $userOrderInfo = [];
        foreach ($res as $row) {
            $j = 0;
            $item = $row->attributes;
            $item['ordermoney'] = number_format($row->ordermoney / 100, 2, '.', '');
            $item['status'] = $this->_orderStatus[$row->status];
            $item['menudate'] = substr($row->menudate, 0, 4) . '-' . substr($row->menudate, 4, 2) . '-' . substr($row->menudate, 6, 2);
            $item['ordertime'] = date('Y-m-d H:i:s', $row->ordertime);
            $item['typeid'] = $this->_orderType[$row->typeid];
            $menuItemInfo = [];
            $orderMenu = explode(',', $row->orderinfo);

            if (isset($this->_request['userType']) && !in_array($row->userid, $users)) {
                continue;
            }

            foreach ($orderMenu as $v) {
                $tempMenuInfo = explode('|', $v);
                $totalPrice = $tempMenuInfo[3] * $tempMenuInfo[2];
                $menuItemInfo[] = $tempMenuInfo[1] . '　' . $tempMenuInfo[3] . '份　￥' . $totalPrice . '元';
                if ($item['wxpay']) {
                    $wxTotalFee = $wxTotalFee + $totalPrice;
                }
                $id = intval($tempMenuInfo[0]);
                $menuCount = $id == 1 ? 1 : $tempMenuInfo[3];
                if (!in_array($id, $menuId)) {
                    $menuId[] = $id;
                    $menuInfo[$id]['count'] = $menuCount;
                    $menuInfo[$id]['totalfee'] = $totalPrice;
                    $menuInfo[$id]['name'] = $tempMenuInfo[1];
                } else {
                    $menuInfo[$id]['count'] = $menuInfo[$id]['count'] + $menuCount;
                    $menuInfo[$id]['totalfee'] = $menuInfo[$id]['totalfee'] + $totalPrice;
                }
            }

            $orderMoney = $item['ordermoney'];
            if (!in_array($item['userid'], $userId)) {
                $userId[] = $item['userid'];
                $userOrderInfo[$item['userid']] = ['name' => $item['realname'], 'departmentname' => $departments[$item['userid']]];
                if ($item['wxpay']) {
                    $userOrderInfo[$item['userid']]['wxcount'] =  1;
                    $userOrderInfo[$item['userid']]['count'] =  0;
                    $userOrderInfo[$item['userid']]['wxtype' . $row->typeid . 'count'] = 1;
                    $userOrderInfo[$item['userid']]['wxtype' . $row->typeid . 'sum'] = $orderMoney;
                } else {
                    $userOrderInfo[$item['userid']]['wxcount'] =  0;
                    $userOrderInfo[$item['userid']]['count'] =  1;
                    $userOrderInfo[$item['userid']]['type' . $row->typeid . 'count'] = 1;
                    $userOrderInfo[$item['userid']]['type' . $row->typeid . 'sum'] = $orderMoney;
                }
            } else {
                if ($item['wxpay']) {
                    $userOrderInfo[$item['userid']]['wxcount'] =  $userOrderInfo[$item['userid']]['wxcount'] + 1;
                    $userOrderInfo[$item['userid']]['wxtype' . $row->typeid . 'count'] = $userOrderInfo[$item['userid']]['wxtype' . $row->typeid . 'count'] + 1;
                    $userOrderInfo[$item['userid']]['wxtype' . $row->typeid . 'sum'] = $userOrderInfo[$item['userid']]['wxtype' . $row->typeid . 'sum'] + $orderMoney;
                } else {
                    $userOrderInfo[$item['userid']]['count'] =  $userOrderInfo[$item['userid']]['count'] + 1;
                    $userOrderInfo[$item['userid']]['type' . $row->typeid . 'count'] = $userOrderInfo[$item['userid']]['type' . $row->typeid . 'count'] + 1;
                    $userOrderInfo[$item['userid']]['type' . $row->typeid . 'sum'] = $userOrderInfo[$item['userid']]['type' . $row->typeid . 'sum'] + $orderMoney;
                }
            }
            $item['orderinfo'] = implode('，', $menuItemInfo);
            foreach ($columns as $key1 => $value1) {
                $columnvalue = $item["$key1"];
                $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit(chr(65 + $j) . ($i + 2), $columnvalue);
                $j++;
            }
            $i++;
        }
        $objPHPExcel->getActiveSheet()->setTitle('订单信息');
        if ($menuInfo) {
            $i = 0;
            $objPHPExcel->createSheet();
            $columns = ['name' => '名称', 'totalnum' => '总份数', 'totalfee' => '总金额'];
            foreach ($columns as $key1 => $value1) {
                $objPHPExcel->setActiveSheetIndex(1)->setCellValue(chr(65 + $i) . '1', $value1);
                $i++;
            }
            $objPHPExcel->setactivesheetindex(1);
            $i = $allTotalFee = $allTotalNum = $allWXTotalFee = $allWXTotalNum =  $feeSum = $numSum = 0;
            foreach ($menuInfo as $m) {
                $j = 0;
                $allTotalFee = $allTotalFee + $m['totalfee'];
                $allTotalNum = $allTotalNum + $m['count'];
                $numSum = $numSum + $m['count'];
                $feeSum = $feeSum + $m['totalfee'];
                foreach ($columns as $key1 => $value1) {
                    if ($key1 == 'name') {
                        $columnvalue = $m['name'];
                    } else if ($key1 == 'totalnum') {
                        $columnvalue = $m['count'];
                    } else if ($key1 == 'totalfee') {
                        $columnvalue = $m['totalfee'];
                    }
                    $objPHPExcel->setActiveSheetIndex(1)->setCellValue(chr(65 + $j) . ($i + 2), $columnvalue);
                    $j++;
                }
                $i++;
            }
            $objPHPExcel->setActiveSheetIndex(1)->setCellValue(chr(65 + 1) . ($i + 2), $numSum);
            $objPHPExcel->setActiveSheetIndex(1)->setCellValue(chr(65 + 2) . ($i + 2), $feeSum);
            $objPHPExcel->getActiveSheet()->setTitle('订单菜单信息汇总');
        }

        if ($userOrderInfo) {
            uasort($userOrderInfo, array($this, '_sortByCuston'));
            $i = 0;
            $objPHPExcel->createSheet();
            $columns = ['name' => '姓名', 'departmentname' => '部门', 'total' => '消费总次数', 'count' => '餐补余额次数', 'wxcount' => '微信余额次数', 'type1count' => '午餐金额/次数', 'type2count' => '晚餐金额/次数', 'type3count' => '早餐金额/次数', 'type6count' => '面对面下单金额/次数', 'type5count' => '代购登记下单金额/次数', 'type7count' => '咖啡下单金额/次数', 'sum' => '餐补总金额(元)', 'wxsum' => '微信总金额(元)'];
            foreach ($columns as $key1 => $value1) {
                $objPHPExcel->setActiveSheetIndex(2)->setCellValue(chr(65 + $i) . '1', $value1);
                $i++;
            }
            $i = $t1 = $t2 = $t3 = $sumTotal = $sumWXTotal = 0;
            $sumInfo = $singleTableUsers = [];
            $objPHPExcel->setactivesheetindex(2);
            foreach ($userOrderInfo as $uid => $m) {
                $j = 0;
                if ($this->_request['userType'][0] == '3' && in_array($uid, $this->_wbyyspry)) {
                    // 晚报运营中心-社聘的人员单独一张表
                    $singleTableUsers[$uid] = $m;
                    continue;
                } else if ($this->_request['userType'][0] == '7' && in_array($uid, $this->_wbgspry)) {
                    // 晚报-公司聘的人员单独一张表
                    $singleTableUsers[$uid] = $m;
                    continue;
                } else if ($this->_request['userType'][0] == '4' && in_array($uid, $this->_rbyyspry)) {
                    // 日报运营中心-社聘的人员单独一张表
                    $singleTableUsers[$uid] = $m;
                    continue;
                }
                foreach ($columns as $key1 => $value1) {
                    if ($key1 == 'name') {
                        $columnvalue = $m['name'];
                    } else if ($key1 == 'count') {
                        $columnvalue = $m['count'];
                        $t2 = $t2 + $columnvalue;
                    } else if ($key1 == 'wxcount') {
                        $columnvalue = $m['wxcount'];
                        $t3 = $t3 + $columnvalue;
                    } else if ($key1 == 'total') {
                        $columnvalue = $m['count'] + $m['wxcount'];
                        $t1 = $t1 + $columnvalue;
                    } else if ($key1 == 'departmentname') {
                        $columnvalue = $departments[$uid];
                    } else if ($key1 == 'sum') {
                        $tempSum = 0;
                        foreach ($m as $mKey => $mValue) {
                            if (strripos($mKey, 'type') !== false && strripos($mKey, 'wx') === false && strripos($mKey, 'count') === false) {
                                $tempSum = $tempSum +  ($m[$mKey] ? $m[$mKey] : 0);
                            }
                        }
                        $columnvalue = $tempSum;
                        $sumTotal = number_format($sumTotal + $columnvalue, 2, '.', '');
                    } else if ($key1 == 'wxsum') {
                        $tempSum = 0;
                        foreach ($m as $mKey => $mValue) {
                            if (strripos($mKey, 'wxtype') !== false && strripos($mKey, 'count') === false) {
                                $tempSum = $tempSum +  ($m[$mKey] ? $m[$mKey] : 0);
                            }
                        }
                        $columnvalue = $tempSum;
                        $sumWXTotal = number_format($sumWXTotal + $columnvalue,  2, '.', '');
                    } else if (strripos($key1, 'type') !== false) {
                        $tempTypeId = str_replace(['type', 'count'], '', $key1);
                        $tempTypeMoney = number_format((($m['type' . $tempTypeId . 'sum'] ? $m['type' . $tempTypeId . 'sum'] : 0) + ($m['wxtype' . $tempTypeId . 'sum'] ? $m['wxtype' . $tempTypeId . 'sum'] : 0)), 2, '.', '');
                        $tempTypeNum =  ($m['type' . $tempTypeId . 'count'] ? $m['type' . $tempTypeId . 'count'] : 0) + ($m['wxtype' . $tempTypeId . 'count'] ? $m['wxtype' . $tempTypeId . 'count'] : 0);
                        $columnvalue =  $tempTypeMoney . '/' . $tempTypeNum;
                        $tempSum = ($m['type' . $tempTypeId . 'sum'] ? $m['type' . $tempTypeId . 'sum'] : 0) + ($m['wxtype' . $tempTypeId . 'sum'] ? $m['wxtype' . $tempTypeId . 'sum'] : 0);
                        $tempCount = ($m['type' . $tempTypeId . 'count'] ? $m['type' . $tempTypeId . 'count'] : 0) + ($m['wxtype' . $tempTypeId . 'count'] ? $m['wxtype' . $tempTypeId . 'count'] : 0);
                        if (isset($sumInfo[$tempTypeId])) {
                            $sumInfo[$tempTypeId]['sum'] = $sumInfo[$tempTypeId]['sum'] + $tempSum;
                            $sumInfo[$tempTypeId]['count'] = $sumInfo[$tempTypeId]['count'] + $tempCount;
                        } else {
                            $sumInfo[$tempTypeId]['sum'] = $tempSum;
                            $sumInfo[$tempTypeId]['count'] = $tempCount;
                        }
                        $sumInfo[$tempTypeId]['total'] = number_format(round($sumInfo[$tempTypeId]['sum'], 2), 2, '.', '') . '/' . $sumInfo[$tempTypeId]['count'];
                    }
                    $objPHPExcel->setActiveSheetIndex(2)->setCellValue(chr(65 + $j) . ($i + 2), $columnvalue);
                    $j++;
                }
                $i++;
            }
            $objPHPExcel->setActiveSheetIndex(2)->setCellValue(chr(65) . ($i + 2), '合计');
            $objPHPExcel->setActiveSheetIndex(2)->setCellValue(chr(65 + 2) . ($i + 2), $t1);
            $objPHPExcel->setActiveSheetIndex(2)->setCellValue(chr(65 + 3) . ($i + 2), $t2);
            $objPHPExcel->setActiveSheetIndex(2)->setCellValue(chr(65 + 4) . ($i + 2), $t3);
            $objPHPExcel->setActiveSheetIndex(2)->setCellValue(chr(65 + 11) . ($i + 2), $sumTotal);
            $objPHPExcel->setActiveSheetIndex(2)->setCellValue(chr(65 + 12) . ($i + 2), $sumWXTotal);
            $sumIndex = [1 => 5, 2 => 6, 3 => 7, 6 => 8, 5 => 9, 7 => 10];
            if ($sumInfo) {
                foreach ($sumInfo as $k => $v) {
                    $objPHPExcel->setActiveSheetIndex(2)->setCellValue(chr(65 + $sumIndex[$k]) . ($i + 2), $v['total']);
                }
            }
            $objPHPExcel->getActiveSheet()->setTitle('订单个人信息汇总');
            if ($singleTableUsers) {
                $i = 0;
                $objPHPExcel->createSheet();
                foreach ($columns as $key1 => $value1) {
                    $objPHPExcel->setActiveSheetIndex(3)->setCellValue(chr(65 + $i) . '1', $value1);
                    $i++;
                }
                $i = 0;
                $objPHPExcel->setactivesheetindex(3);
                $i = $t1 = $t2 = $t3 = $sumTotal = $sumWXTotal = 0;
                $sumInfo = [];
                foreach ($singleTableUsers as $uid => $m) {
                    $j = 0;
                    foreach ($columns as $key1 => $value1) {
                        if ($key1 == 'name') {
                            $columnvalue = $m['name'];
                        } else if ($key1 == 'count') {
                            $columnvalue = $m['count'];
                            $t2 = $t2 + $columnvalue;
                        } else if ($key1 == 'wxcount') {
                            $columnvalue = $m['wxcount'];
                            $t3 = $t3 + $columnvalue;
                        } else if ($key1 == 'total') {
                            $columnvalue = $m['count'] + $m['wxcount'];
                            $t1 = $t1 + $columnvalue;
                        } else if ($key1 == 'departmentname') {
                            $columnvalue = $departments[$uid];
                        } else if ($key1 == 'sum') {
                            $tempSum = 0;
                            foreach ($m as $mKey => $mValue) {
                                if (strripos($mKey, 'type') !== false && strripos($mKey, 'wx') === false && strripos($mKey, 'count') === false) {
                                    $tempSum = $tempSum +  ($m[$mKey] ? $m[$mKey] : 0);
                                }
                            }
                            $columnvalue = $tempSum;
                            $sumTotal = number_format($sumTotal + $columnvalue, 2, '.', '');
                        } else if ($key1 == 'wxsum') {
                            $tempSum = 0;
                            foreach ($m as $mKey => $mValue) {
                                if (strripos($mKey, 'wxtype') !== false && strripos($mKey, 'count') === false) {
                                    $tempSum = $tempSum +  ($m[$mKey] ? $m[$mKey] : 0);
                                }
                            }
                            $columnvalue = $tempSum;
                            $sumWXTotal = number_format($sumWXTotal + $columnvalue,  2, '.', '');
                        } else if (strripos($key1, 'type') !== false) {
                            $tempTypeId = str_replace(['type', 'count'], '', $key1);
                            $tempTypeMoney = number_format((($m['type' . $tempTypeId . 'sum'] ? $m['type' . $tempTypeId . 'sum'] : 0) + ($m['wxtype' . $tempTypeId . 'sum'] ? $m['wxtype' . $tempTypeId . 'sum'] : 0)), 2, '.', '');
                            $tempTypeNum =  ($m['type' . $tempTypeId . 'count'] ? $m['type' . $tempTypeId . 'count'] : 0) + ($m['wxtype' . $tempTypeId . 'count'] ? $m['wxtype' . $tempTypeId . 'count'] : 0);
                            $columnvalue =  $tempTypeMoney . '/' . $tempTypeNum;
                            $tempSum = ($m['type' . $tempTypeId . 'sum'] ? $m['type' . $tempTypeId . 'sum'] : 0) + ($m['wxtype' . $tempTypeId . 'sum'] ? $m['wxtype' . $tempTypeId . 'sum'] : 0);
                            $tempCount = ($m['type' . $tempTypeId . 'count'] ? $m['type' . $tempTypeId . 'count'] : 0) + ($m['wxtype' . $tempTypeId . 'count'] ? $m['wxtype' . $tempTypeId . 'count'] : 0);
                            if (isset($sumInfo[$tempTypeId])) {
                                $sumInfo[$tempTypeId]['sum'] = $sumInfo[$tempTypeId]['sum'] + $tempSum;
                                $sumInfo[$tempTypeId]['count'] = $sumInfo[$tempTypeId]['count'] + $tempCount;
                            } else {
                                $sumInfo[$tempTypeId]['sum'] = $tempSum;
                                $sumInfo[$tempTypeId]['count'] = $tempCount;
                            }
                            $sumInfo[$tempTypeId]['total'] = number_format(round($sumInfo[$tempTypeId]['sum'], 2), 2, '.', '') . '/' . $sumInfo[$tempTypeId]['count'];
                        }
                        $objPHPExcel->setActiveSheetIndex(3)->setCellValue(chr(65 + $j) . ($i + 2), $columnvalue);
                        $j++;
                    }
                    $i++;
                }
                $objPHPExcel->setActiveSheetIndex(3)->setCellValue(chr(65) . ($i + 2), '合计');
                $objPHPExcel->setActiveSheetIndex(3)->setCellValue(chr(65 + 2) . ($i + 2), $t1);
                $objPHPExcel->setActiveSheetIndex(3)->setCellValue(chr(65 + 3) . ($i + 2), $t2);
                $objPHPExcel->setActiveSheetIndex(3)->setCellValue(chr(65 + 4) . ($i + 2), $t3);
                $objPHPExcel->setActiveSheetIndex(3)->setCellValue(chr(65 + 11) . ($i + 2), $sumTotal);
                $objPHPExcel->setActiveSheetIndex(3)->setCellValue(chr(65 + 12) . ($i + 2), $sumWXTotal);
                $sumIndex = [1 => 5, 2 => 6, 3 => 7, 6 => 8, 5 => 9, 7 => 10];
                if ($sumInfo) {
                    foreach ($sumInfo as $k => $v) {
                        $objPHPExcel->setActiveSheetIndex(3)->setCellValue(chr(65 + $sumIndex[$k]) . ($i + 2), $v['total']);
                    }
                }
                if ($this->_request['userType'][0] == '3') {
                    $singleTableTitle = '(社聘)';
                } else if ($this->_request['userType'][0] == '7') {
                    $singleTableTitle = '(公司聘)';
                } else if ($this->_request['userType'][0] == '4') {
                    $singleTableTitle = '(社聘)';
                }
                $objPHPExcel->getActiveSheet()->setTitle($singleTableTitle . '订单个人信息汇总');
            }
        }

        header('Expires: ' . date(DATE_RFC1123));
        header('Cache-Control: no-store, no-cache, must-revalidate,' . '  pre-check=0, post-check=0, max-age=0');
        header('Last-Modified: ' . date(DATE_RFC1123));
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . date("YmdHis") . '.xls"');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * 账号结算类别
     */
    public function actionUserType()
    {
        $this->_getConfigData();
        $this->_result['data'] = $this->_userType;
        return $this->_result;
    }

    /**
     * 菜单类别
     */
    public function actionMenuType()
    {
        $this->_getConfigData();
        $this->_result['data'] = $this->_orderType;
        return $this->_result;
    }

    /**
     * 每月账号余额变动列表
     */
    public function actionAccountChange()
    {
        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if ($this->_request['username']) {
            $where[] = ['=', 'username', $this->_request['username']];
        }
        if (isset($this->_request['userType'])) {
            $where[] = ['=', 'usertype', $this->_request['userType']];
        }
        if (isset($this->_request['year']) && isset($this->_request['month'])) {
            $howMonth = $this->_request['year'] . $this->_request['month'];
            $where[] = ['=', 'howmonth', $howMonth];
        } else if (isset($this->_request['year'])) {
            $where[] = ['=', "left(howmonth,4)", $this->_request['year']];
        } else if (isset($this->_request['month'])) {
            $where[] = ['=', "right(howmonth,2)", $this->_request['month']];
        }
        $model = new WeixinStaffMonth;
        $model = $model::find()->where($where);
        $total = $model->count();
        $res = $model->limit($limit)->offset($offset)->orderBy("howmonth desc,id desc")->all();
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        foreach ($res as $row) {
            $item = $row->attributes;
            $item['howmonth'] = substr($item['howmonth'], 0, 4) . '-' . substr($item['howmonth'], 4);
            $item['startbalance'] = number_format(($row['startbalance'] / 100), 2, '.', '');
            $item['startbalancewx'] = number_format(($row['startbalancewx'] / 100), 2, '.', '');
            $all_startbalance = $row['startbalance'] + $row['startbalancewx'];
            $item['all_startbalance'] = (number_format($all_startbalance / 100, 2, '.', '')) . '(' . $item['startbalance'] . '+' . $item['startbalancewx'] . ')';

            $item['transfermoney'] = number_format(($row['transfermoney'] / 100), 2, '.', '');
            $item['transfermoneywx'] = number_format(($row['transfermoneywx'] / 100), 2, '.', '');
            $all_acountpay = $row['transfermoney'] + $row['transfermoneywx'];
            $item['all_acountpay'] = (number_format($all_acountpay / 100, 2, '.', '')) . '(' . $item['transfermoney'] . '+' . $item['transfermoneywx'] . ')';

            $item['ordermoney'] = number_format(($row['ordermoney'] / 100), 2, '.', '');
            $item['wxpay'] = number_format(($row['wxpay'] / 100), 2, '.', '');
            $all_use = $row['ordermoney'] + $row['wxpay'];
            $item['all_use'] = (number_format($all_use / 100, 2, '.', '')) . '(' . $item['ordermoney'] . '+' . $item['wxpay'] . ')';

            $item['endbalance'] = number_format(($row['endbalance'] / 100), 2, '.', '');
            $item['endbalancewx'] = number_format(($row['endbalancewx'] / 100), 2, '.', '');
            $all_endbalance = $row['endbalance'] + $row['endbalancewx'];
            $item['all_endbalance'] = (number_format($all_endbalance / 100, 2, '.', '')) . '(' . $item['endbalance'] . '+' . $item['endbalancewx'] . ')';

            $this->_result['data'][] = $item;
        }
        return $this->_result;
    }

    /**
     * 每月账号余额变动下载
     */
    public function actionAccountChangeDownload()
    {
        ini_set("memory_limit", "2048M");
        set_time_limit(0);
        $this->_getConfigData();
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $columns = array(
            'username' => '姓名',
            'departmentname' => '所在部门',
            'howmonth' => '月份',
            'allstartbalance' => '月初账户总余额',
            'startbalance' => '月初账户餐补余额',
            'startbalancewx' => '月初账户微信余额',
            'alltransfermoney' => '月内账户充值总金额',
            'transfermoneywx' => '月内账户微信充值金额',
            'transfermoney' => '月内账户餐补充值金额',
            'allpay' => '月内消费总金额',
            'ordermoney' => '月内餐补消费金额',
            'wxpay' => '月内微信消费金额',
            'allendbalance' => '月末账户总余额',
            'endbalance' => '月末账户餐补余额',
            'endbalancewx' => '月末账户微信余额',
        );
        $i = 0;
        $sheetIndex = 0;
        $allType = false;
        $typeRes = [];
        foreach ($columns as $key1 => $value1) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $i) . '1', $value1);
            $i++;
        }
        $where = [
            'and',
            ['>', 'id', 0],
            ['!=', 'userid', 'CeShi'],
        ];
        if ($this->_request['username']) {
            $where[] = ['=', 'username', $this->_request['username']];
        }
        if (isset($this->_request['userType'])) {
            $where[] = ['=', 'usertype', $this->_request['userType']];
        } else {
            $allType = true;
        }
        if (isset($this->_request['year']) && isset($this->_request['month'])) {
            $howMonth = $this->_request['year'] . $this->_request['month'];
            $where[] = ['=', 'howmonth', $howMonth];
        } else if (isset($this->_request['year'])) {
            $where[] = ['=', "left(howmonth,4)", $this->_request['year']];
        } else if (isset($this->_request['month'])) {
            $where[] = ['=', "right(howmonth,2)", $this->_request['month']];
        }
        $model = new WeixinStaffMonth;
        $model = $model::find()->where($where);
        $res = $model->orderBy($this->_orderBy)->all();
        $i = 0;
        $sum1 = $sum2 = $sum3 = $sum4 = $sum5 = $sum6 = $sum7 = $sum8 = $sum9 = $sum10 = $sum11 = $sum12 = 0;
        $singleTableUsers = [];
        foreach ($res as $row) {
            $j = 0;
            $item = $row->attributes;
            if ($allType) {
                $typeRes[$item['usertype']][] = $item;
            } else {
                if ($this->_request['userType'] == '3' && in_array($row['userid'], $this->_wbyyspry)) {
                    // 晚报运营中心-社聘的人员单独一张表
                    $singleTableUsers[] = $item;
                    continue;
                } else if ($this->_request['userType'] == '7' && in_array($row['userid'], $this->_wbgspry)) {
                    // 晚报-公司聘的人员单独一张表
                    $singleTableUsers[] = $item;
                    continue;
                } else if ($this->_request['userType'] == '4' && in_array($row['userid'], $this->_rbyyspry)) {
                    // 日报运营中心-社聘的人员单独一张表
                    $singleTableUsers[] = $item;
                    continue;
                }
            }
            $item['startbalance'] = number_format(($row['startbalance'] / 100), 2, '.', '');
            $item['startbalancewx'] = number_format(($row['startbalancewx'] / 100), 2, '.', '');
            $all_startbalance = $row['startbalance'] + $row['startbalancewx'];
            $item['allstartbalance'] = (number_format($all_startbalance / 100, 2, '.', ''));
            $sum1 = $sum1 + $row['startbalance'];
            $sum2 = $sum2 + $row['startbalancewx'];
            $sum3 = $sum3 + $all_startbalance;

            $item['transfermoney'] = number_format(($row['transfermoney'] / 100), 2, '.', '');
            $item['transfermoneywx'] = number_format(($row['transfermoneywx'] / 100), 2, '.', '');
            $all_acountpay = $row['transfermoney'] + $row['transfermoneywx'];
            $item['alltransfermoney'] = (number_format($all_acountpay / 100, 2, '.', ''));
            $sum4 = $sum4 + $row['transfermoney'];
            $sum5 = $sum5 + $row['transfermoneywx'];
            $sum6 = $sum6 + $all_acountpay;

            $item['ordermoney'] = number_format(($row['ordermoney'] / 100), 2, '.', '');
            $item['wxpay'] = number_format(($row['wxpay'] / 100), 2, '.', '');
            $all_use = $row['ordermoney'] + $row['wxpay'];
            $item['allpay'] = (number_format($all_use / 100, 2, '.', ''));
            $sum7 = $sum7 + $row['ordermoney'];
            $sum8 = $sum8 + $row['wxpay'];
            $sum9 = $sum9 + $all_use;

            $item['endbalance'] = number_format(($row['endbalance'] / 100), 2, '.', '');
            $item['endbalancewx'] = number_format(($row['endbalancewx'] / 100), 2, '.', '');
            $all_endbalance = $row['endbalance'] + $row['endbalancewx'];
            $item['allendbalance'] = (number_format($all_endbalance / 100, 2, '.', ''));
            $sum10 = $sum10 + $row['endbalance'];
            $sum11 = $sum11 + $row['endbalancewx'];
            $sum12 = $sum12 + $all_endbalance;

            $item['howmonth'] = substr($item['howmonth'], 0, 4) . '-' . substr($item['howmonth'], 4);
            foreach ($columns as $key1 => $value1) {
                $columnvalue = $item["$key1"];
                $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValueExplicit(chr(65 + $j) . ($i + 2), $columnvalue);
                $j++;
            }
            $i++;
        }
        $title = $allType ? '每月账户余额变动情况(全部)' : '每月账户余额变动情况(' . $this->_userType[$this->_request['userType']]['text'] . ')';
        $objPHPExcel->getActiveSheet()->setTitle($title);
        $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65) . ($i + 2), '合计');
        $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 3) . ($i + 2), (number_format($sum3 / 100, 2, '.', '')));
        $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 4) . ($i + 2), (number_format($sum1 / 100, 2, '.', '')));
        $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 5) . ($i + 2), (number_format($sum2 / 100, 2, '.', '')));
        $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 6) . ($i + 2), (number_format($sum6 / 100, 2, '.', '')));
        $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 7) . ($i + 2), (number_format($sum5 / 100, 2, '.', '')));
        $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 8) . ($i + 2), (number_format($sum4 / 100, 2, '.', '')));
        $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 9) . ($i + 2), (number_format($sum9 / 100, 2, '.', '')));
        $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 10) . ($i + 2), (number_format($sum7 / 100, 2, '.', '')));
        $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 11) . ($i + 2), (number_format($sum8 / 100, 2, '.', '')));
        $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 12) . ($i + 2), (number_format($sum12 / 100, 2, '.', '')));
        $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 13) . ($i + 2), (number_format($sum10 / 100, 2, '.', '')));
        $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 14) . ($i + 2), (number_format($sum11 / 100, 2, '.', '')));
        if ($singleTableUsers) {
            $sheetIndex++;
            $objPHPExcel->createSheet();
            $i = 0;
            foreach ($columns as $key1 => $value1) {
                $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + $i) . '1', $value1);
                $i++;
            }
            $objPHPExcel->setactivesheetindex($sheetIndex);
            $i = 0;
            $sum1 = $sum2 = $sum3 = $sum4 = $sum5 = $sum6 = $sum7 = $sum8 = $sum9 = $sum10 = $sum11 = $sum12 = 0;
            foreach ($singleTableUsers as $row) {
                $j = 0;
                $item = $row;
                $item['startbalance'] = number_format(($row['startbalance'] / 100), 2, '.', '');
                $item['startbalancewx'] = number_format(($row['startbalancewx'] / 100), 2, '.', '');
                $all_startbalance = $row['startbalance'] + $row['startbalancewx'];
                $item['allstartbalance'] = (number_format($all_startbalance / 100, 2, '.', ''));
                $sum1 = $sum1 + $row['startbalance'];
                $sum2 = $sum2 + $row['startbalancewx'];
                $sum3 = $sum3 + $all_startbalance;

                $item['transfermoney'] = number_format(($row['transfermoney'] / 100), 2, '.', '');
                $item['transfermoneywx'] = number_format(($row['transfermoneywx'] / 100), 2, '.', '');
                $all_acountpay = $row['transfermoney'] + $row['transfermoneywx'];
                $item['alltransfermoney'] = (number_format($all_acountpay / 100, 2, '.', ''));
                $sum4 = $sum4 + $row['transfermoney'];
                $sum5 = $sum5 + $row['transfermoneywx'];
                $sum6 = $sum6 + $all_acountpay;

                $item['ordermoney'] = number_format(($row['ordermoney'] / 100), 2, '.', '');
                $item['wxpay'] = number_format(($row['wxpay'] / 100), 2, '.', '');
                $all_use = $row['ordermoney'] + $row['wxpay'];
                $item['allpay'] = (number_format($all_use / 100, 2, '.', ''));
                $sum7 = $sum7 + $row['ordermoney'];
                $sum8 = $sum8 + $row['wxpay'];
                $sum9 = $sum9 + $all_use;

                $item['endbalance'] = number_format(($row['endbalance'] / 100), 2, '.', '');
                $item['endbalancewx'] = number_format(($row['endbalancewx'] / 100), 2, '.', '');
                $all_endbalance = $row['endbalance'] + $row['endbalancewx'];
                $item['allendbalance'] = (number_format($all_endbalance / 100, 2, '.', ''));
                $sum10 = $sum10 + $row['endbalance'];
                $sum11 = $sum11 + $row['endbalancewx'];
                $sum12 = $sum12 + $all_endbalance;

                $item['howmonth'] = substr($item['howmonth'], 0, 4) . '-' . substr($item['howmonth'], 4);
                foreach ($columns as $key1 => $value1) {
                    $columnvalue = $item["$key1"];
                    $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValueExplicit(chr(65 + $j) . ($i + 2), $columnvalue);
                    $j++;
                }
                $i++;
            }
            $singleTableName = [3 => '(社聘)', 7 => '(公司聘)', 4 => '(社聘)'];
            $objPHPExcel->getActiveSheet()->setTitle($singleTableName[$this->_request['userType']] . '每月账户余额变动情况(' . $this->_userType[$this->_request['userType']]['text'] . ')');
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65) . ($i + 2), '合计');
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 3) . ($i + 2), (number_format($sum3 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 4) . ($i + 2), (number_format($sum1 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 5) . ($i + 2), (number_format($sum2 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 6) . ($i + 2), (number_format($sum6 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 7) . ($i + 2), (number_format($sum4 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 8) . ($i + 2), (number_format($sum5 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 9) . ($i + 2), (number_format($sum9 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 10) . ($i + 2), (number_format($sum7 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 11) . ($i + 2), (number_format($sum8 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 12) . ($i + 2), (number_format($sum12 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 13) . ($i + 2), (number_format($sum10 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 14) . ($i + 2), (number_format($sum11 / 100, 2, '.', '')));
        }
        $sheetIndex++;
        foreach ($typeRes as $k => $v) {
            $objPHPExcel->createSheet();
            $i = 0;
            foreach ($columns as $key1 => $value1) {
                $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + $i) . '1', $value1);
                $i++;
            }
            $objPHPExcel->setactivesheetindex($sheetIndex);
            $i = 0;
            $sum1 = $sum2 = $sum3 = $sum4 = $sum5 = $sum6 = $sum7 = $sum8 = $sum9 = $sum10 = $sum11 = $sum12 = 0;
            foreach ($typeRes[$k] as $row) {
                $j = 0;
                $item = $row;
                $item['startbalance'] = number_format(($row['startbalance'] / 100), 2, '.', '');
                $item['startbalancewx'] = number_format(($row['startbalancewx'] / 100), 2, '.', '');
                $all_startbalance = $row['startbalance'] + $row['startbalancewx'];
                $item['allstartbalance'] = (number_format($all_startbalance / 100, 2, '.', ''));
                $sum1 = $sum1 + $row['startbalance'];
                $sum2 = $sum2 + $row['startbalancewx'];
                $sum3 = $sum3 + $all_startbalance;

                $item['transfermoney'] = number_format(($row['transfermoney'] / 100), 2, '.', '');
                $item['transfermoneywx'] = number_format(($row['transfermoneywx'] / 100), 2, '.', '');
                $all_acountpay = $row['transfermoney'] + $row['transfermoneywx'];
                $item['alltransfermoney'] = (number_format($all_acountpay / 100, 2, '.', ''));
                $sum4 = $sum4 + $row['transfermoney'];
                $sum5 = $sum5 + $row['transfermoneywx'];
                $sum6 = $sum6 + $all_acountpay;

                $item['ordermoney'] = number_format(($row['ordermoney'] / 100), 2, '.', '');
                $item['wxpay'] = number_format(($row['wxpay'] / 100), 2, '.', '');
                $all_use = $row['ordermoney'] + $row['wxpay'];
                $item['allpay'] = (number_format($all_use / 100, 2, '.', ''));
                $sum7 = $sum7 + $row['ordermoney'];
                $sum8 = $sum8 + $row['wxpay'];
                $sum9 = $sum9 + $all_use;

                $item['endbalance'] = number_format(($row['endbalance'] / 100), 2, '.', '');
                $item['endbalancewx'] = number_format(($row['endbalancewx'] / 100), 2, '.', '');
                $all_endbalance = $row['endbalance'] + $row['endbalancewx'];
                $item['allendbalance'] = (number_format($all_endbalance / 100, 2, '.', ''));
                $sum10 = $sum10 + $row['endbalance'];
                $sum11 = $sum11 + $row['endbalancewx'];
                $sum12 = $sum12 + $all_endbalance;

                $item['howmonth'] = substr($item['howmonth'], 0, 4) . '-' . substr($item['howmonth'], 4);
                foreach ($columns as $key1 => $value1) {
                    $columnvalue = $item["$key1"];
                    $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValueExplicit(chr(65 + $j) . ($i + 2), $columnvalue);
                    $j++;
                }
                $i++;
            }
            $objPHPExcel->getActiveSheet()->setTitle('每月账户余额变动情况(' . $this->_userType[$k]['text'] . ')');
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65) . ($i + 2), '合计');
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 3) . ($i + 2), (number_format($sum3 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 4) . ($i + 2), (number_format($sum1 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 5) . ($i + 2), (number_format($sum2 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 6) . ($i + 2), (number_format($sum6 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 7) . ($i + 2), (number_format($sum4 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 8) . ($i + 2), (number_format($sum5 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 9) . ($i + 2), (number_format($sum9 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 10) . ($i + 2), (number_format($sum7 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 11) . ($i + 2), (number_format($sum8 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 12) . ($i + 2), (number_format($sum12 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 13) . ($i + 2), (number_format($sum10 / 100, 2, '.', '')));
            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65 + 14) . ($i + 2), (number_format($sum11 / 100, 2, '.', '')));
            $sheetIndex++;
        }
        $objPHPExcel->setActiveSheetIndex(0);
        header('Expires: ' . date(DATE_RFC1123));
        header('Cache-Control: no-store, no-cache, must-revalidate,' . '  pre-check=0, post-check=0, max-age=0');
        header('Last-Modified: ' . date(DATE_RFC1123));
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="食堂账户余额变动情况.xls"');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $action = '导出';
        $remark = $action . "食堂每月账号余额变动情况数据。" . (!$allType ? '用户类别：' . $this->_userType[$k]['text'] : '');
        $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        $objWriter->save('php://output');
        exit;
    }

    /**
     * 菜单列表
     */
    public function actionMenus()
    {
        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if ($this->_request['name']) {
            $where[] = ['like', 'name', $this->_request['name']];
        }
        if (isset($this->_request['status'])) {
            $where[] = ['=', 'status', $this->_request['status']];
        }
        if (isset($this->_request['typeid'])) {
            $where[] = ['=', 'typeid', $this->_request['typeid']];
        }
        $this->_orderBy = 'menudate1 desc, id desc';
        $this->_request = ArrayHelper::htmlDecode($this->_request);
        $sorter = Json::decode($this->_request['sorter'], true);
        if ($sorter) {
            if (isset($sorter['price'])) {
                $this->_orderBy = "price " . str_replace('end', '', $sorter['price']);
            }
            if (isset($sorter['buynum'])) {
                $this->_orderBy = "buynum " . str_replace('end', '', $sorter['buynum']);
            }
            if (isset($sorter['support'])) {
                $this->_orderBy = "support " . str_replace('end', '', $sorter['support']);
            }
            if (isset($sorter['star'])) {
                $this->_orderBy = "star " . str_replace('end', '', $sorter['star']);
            }
        }
        $model = new ShitangCanteenMenu;
        $model = $model::find()->where($where);
        $total = $model->count();
        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        $this->_result['data'] = $res;
        return $this->_result;
    }

    /**
     * create菜单的业务实现动作
     */
    public function actionCreateMenu()
    {
        if ($this->_request['values']) {
            $model = new ShitangCanteenMenu(['scenario' => 'create']);
            $data = $this->_formatMenuData($this->_request['values']);
            $data['inserttime'] = time();
            $model->attributes = $data;
            $ruleResult = Tools::modelRules($model, 8000);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '新增';
                    $remark = $action . "食堂菜单。名称：" . $model->name . '，价格：' . number_format(($model->price / 100), 2, '.', '') . '。';
                    $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
                }
            } else {
                $this->_result['errorCode'] = $ruleResult['errorCode'];
                $this->_result['errorMessage'] = $ruleResult['errorMessage'];
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * update菜单的业务实现动作
     */
    public function actionUpdateMenu()
    {
        $id = intval($this->_request['id']);
        if ($id) {
            $model = ShitangCanteenMenu::findOne($id);
            $model->scenario = 'update';
            $oldName = $model->name;
            $oldPrice = $model->price;
            $data = $this->_formatMenuData($this->_request['values']);
            $model->attributes = $data;
            $ruleResult = Tools::modelRules($model, 8001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '修改';
                    $remark = $action . "食堂菜单。" . ($oldName != $model->name ? '名称由 ' . $oldName . ' 改为 ' . $model->name . '。' : '名称：' . $model->name) . ($oldPrice != $model->price ? '价格由 ' . number_format(($oldPrice / 100), 2, '.', '') . ' 改为 ' . number_format(($model->price / 100), 2, '.', '') . '。' : '');
                    $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
                }
            } else {
                $this->_result['errorCode'] = $ruleResult['errorCode'];
                $this->_result['errorMessage'] = $ruleResult['errorMessage'];
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }


    /**
     * delete菜单的业务实现动作
     */
    public function actionDeleteMenu()
    {
        if ($this->_request['id']) {
            $ids = explode(',', $this->_request['id']);
            $names = [];
            $models = ShitangCanteenMenu::find()->where(['in', 'id', $ids])->all();
            foreach ($models as $model) {
                $names[] = $model->name;
                $model->delete();
            }
            if ($names) {
                $action = '删除';
                $remark = $action . "食堂菜单。名称：" . implode(',', $names) . "。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 设置菜单状态的业务实现动作
     */
    public function actionUpdateMenuStatus()
    {
        if ($this->_request['id']) {
            $ids = $this->_request['id'];
            $names = [];
            $models = ShitangCanteenMenu::find()->where(['in', 'id', $ids])->all();
            foreach ($models as $model) {
                $names[] = $model->name;
                $model->status = intval($this->_request['values']['status']);
                $model->save();
            }
            if ($names) {
                $action = '更新';
                $remark = $action . "食堂菜单状态。状态：" . ($this->_request['values']['status'] ? '上线' : '下线') . "。名称：" . implode(',', $names) . "。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 食堂订单统计列表
     */
    public function actionOrderSum()
    {
        $where = [
            'and',
            ['>', 'id', 0],
            ['<', 'status', 2],
        ];
        $orderDay = date('Ymd', strtotime("+1 day"));
        if ($this->_request['orderTime']) {
            $orderDay = date('Ymd', strtotime($this->_request['orderTime']));
        }
        $where[] = ['=', 'menudate', $orderDay];
        $where1 = $where;
        $menuType = [];
        if (isset($this->_request['menuType']) && $this->_request['menuType'] != '') {
            $menuType = explode(',', $this->_request['menuType']);
            $where[] = ['in', 'typeid', $menuType];
        }
        $model = new ShitangCanteenOrder;
        $model = $model::find()->where($where);
        $res = $model->orderBy($this->_orderBy)->all();
        $menu = $menuInfo = [];
        $orderTotalPrice = 0;
        $orderTotalNum = count($res);
        foreach ($res as $row) {
            $orderItem  = explode(',', $row->orderinfo);
            $ordertime = date('G', $row->ordertime);
            foreach ($orderItem as $v) {
                $itemInfo = explode('|', $v);
                $orderTotalPrice = $orderTotalPrice + ($itemInfo[2] * $itemInfo[3]);
                $id = $itemInfo[0];
                if (!in_array($id, $menu)) {
                    $menu[] = $id;
                    $menuInfo[$id]['count'] = $itemInfo[3];
                    $menuInfo[$id]['name'] = $itemInfo[1];
                    if (in_array($row->typeid, array(1, 2, 3))) {
                        $menuInfo[$id]['type'][$row->typeid] = $itemInfo[3];
                        $menuInfo[$id]['type' . $row->typeid . 'price'] = ($itemInfo[2] * $itemInfo[3]) * 100;
                    } else if ($row->typeid == 6) {
                        // 面对面订单按时间分早中晚
                        if ($ordertime < 10) {
                            $menuInfo[$id]['type'][3] = 1;
                            $menuInfo[$id]['type3price'] = $row->ordermoney;
                        } else if ($ordertime >= 10 && $ordertime < 15) {
                            $menuInfo[$id]['type'][1] = 1;
                            $menuInfo[$id]['type1price'] = $row->ordermoney;
                        } else if ($ordertime >= 15) {
                            $menuInfo[$id]['type'][2] = 1;
                            $menuInfo[$id]['type2price'] = $row->ordermoney;
                        }
                    } else if ($row->typeid == 5) {
                        $menuInfo[$id]['typeprice'] = ($itemInfo[2] * $itemInfo[3]) * 100;
                    }
                    $menuInfo[$id]['order'] = 1;
                } else {
                    $menuInfo[$id]['count'] = $itemInfo[3] + $menuInfo[$id]['count'];
                    if (in_array($row->typeid, array(1, 2, 3))) {
                        $menuInfo[$id]['type'][$row->typeid] = intval($menuInfo[$id]['type'][$row->typeid]) + $itemInfo[3];
                        $menuInfo[$id]['type' . $row->typeid . 'price'] = intval($menuInfo[$id]['type' . $row->typeid . 'price']) + (($itemInfo[2] * $itemInfo[3]) * 100);
                    } else if ($row->typeid == 6) {
                        if ($ordertime < 10) {
                            $menuInfo[$id]['type'][3] = intval($menuInfo[$id]['type'][3]) + 1;
                            $menuInfo[$id]['type3price'] = intval($menuInfo[$id]['type3price']) + $row->ordermoney;
                        } else if ($ordertime >= 10 && $ordertime < 15) {
                            $menuInfo[$id]['type'][1] = intval($menuInfo[$id]['type'][1]) + 1;
                            $menuInfo[$id]['type1price'] = intval($menuInfo[$id]['type1price']) + $row->ordermoney;
                        } else if ($ordertime >= 15) {
                            $menuInfo[$id]['type'][2] = intval($menuInfo[$id]['type'][2]) + 1;
                            $menuInfo[$id]['type2price'] = intval($menuInfo[$id]['type2price']) + $row->ordermoney;
                        }
                    } else if ($row->typeid == 5) {
                        $menuInfo[$id]['typeprice'] = intval($menuInfo[$id]['typeprice']) + (($itemInfo[2] * $itemInfo[3]) * 100);
                    }
                    $menuInfo[$id]['order'] = $menuInfo[$id]['order'] + 1;
                }
            }
        }
        // 统计早中晚餐时，额外统计面对面订单金额
        $diffArr = array_diff($menuType, [1, 2, 3]);
        if ($menuType && !$diffArr) {
            $where1[] = ['=', 'typeid', 6];
            $model = new ShitangCanteenOrder;
            $model = $model::find()->where($where1);
            $res = $model->orderBy($this->_orderBy)->all();
            foreach ($res as $row) {
                $orderItem  = explode(',', $row->orderinfo);
                $ordertime = date('G', $row->ordertime);
                foreach ($orderItem as $v) {
                    $itemInfo = explode('|', $v);
                    $id = $itemInfo[0];
                    if (!in_array($id, $menu)) {
                        $menu[] = $id;
                        $menuInfo[$id]['name'] = $itemInfo[1];
                        foreach ($menuType as $v1) {
                            if ($ordertime < 10 && $v1 == 3) {
                                $orderTotalNum = $orderTotalNum + 1;
                                $orderTotalPrice = $orderTotalPrice + ($itemInfo[2] * $itemInfo[3]);
                                $menuInfo[$id]['count'] = 1;
                                $menuInfo[$id]['order'] = 1;
                                $menuInfo[$id]['type'][3] = 1;
                                $menuInfo[$id]['type3price'] = $row->ordermoney;
                            } else if ($ordertime >= 10 && $ordertime < 15 && $v1 == 1) {
                                $orderTotalNum = $orderTotalNum + 1;
                                $orderTotalPrice = $orderTotalPrice + ($itemInfo[2] * $itemInfo[3]);
                                $menuInfo[$id]['count'] = 1;
                                $menuInfo[$id]['order'] = 1;
                                $menuInfo[$id]['type'][1] = 1;
                                $menuInfo[$id]['type1price'] = $row->ordermoney;
                            } else if ($ordertime >= 15 && $v1 == 2) {
                                $orderTotalNum = $orderTotalNum + 1;
                                $orderTotalPrice = $orderTotalPrice + ($itemInfo[2] * $itemInfo[3]);
                                $menuInfo[$id]['count'] = 1;
                                $menuInfo[$id]['order'] = 1;
                                $menuInfo[$id]['type'][2] = 1;
                                $menuInfo[$id]['type2price'] = $row->ordermoney;
                            }
                        }
                    } else {
                        foreach ($menuType as $v1) {
                            if ($ordertime < 10 && $v1 == 3) {
                                $orderTotalNum = $orderTotalNum + 1;
                                $orderTotalPrice = $orderTotalPrice + ($itemInfo[2] * $itemInfo[3]);
                                $menuInfo[$id]['count'] = $menuInfo[$id]['count'] + 1;
                                $menuInfo[$id]['order'] = $menuInfo[$id]['order'] + 1;
                                $menuInfo[$id]['type'][3] = intval($menuInfo[$id]['type'][3]) + 1;
                                $menuInfo[$id]['type3price'] = intval($menuInfo[$id]['type3price']) + $row->ordermoney;
                            } else if ($ordertime >= 10 && $ordertime < 15 && $v1 == 1) {
                                $orderTotalNum = $orderTotalNum + 1;
                                $orderTotalPrice = $orderTotalPrice + ($itemInfo[2] * $itemInfo[3]);
                                $menuInfo[$id]['count'] = $menuInfo[$id]['count'] + 1;
                                $menuInfo[$id]['order'] = $menuInfo[$id]['order'] + 1;
                                $menuInfo[$id]['type'][1] = intval($menuInfo[$id]['type'][1]) + 1;
                                $menuInfo[$id]['type1price'] = intval($menuInfo[$id]['type1price']) + $row->ordermoney;
                            } else if ($ordertime >= 15 && $v1 == 2) {
                                $orderTotalNum = $orderTotalNum + 1;
                                $orderTotalPrice = $orderTotalPrice + ($itemInfo[2] * $itemInfo[3]);
                                $menuInfo[$id]['count'] = $menuInfo[$id]['count'] + 1;
                                $menuInfo[$id]['order'] = $menuInfo[$id]['order'] + 1;
                                $menuInfo[$id]['type'][2] = intval($menuInfo[$id]['type'][2]) + 1;
                                $menuInfo[$id]['type2price'] = intval($menuInfo[$id]['type2price']) + $row->ordermoney;
                            }
                        }
                    }
                }
            }
        }
        $data = [];
        foreach ($menuInfo as $menu) {
            $tempInfo = ['name' => $menu['name'], 'total' => $menu['count'] . '份', 'type1' => '', 'type2' => '', 'type3' => '', 'order' => 0];
            if (isset($menu['typeprice'])) {
                $tempInfo['total'] = $tempInfo['total'] . '(' . strval(number_format(($menu['typeprice'] / 100), 2, '.', '')) . ')';
            }
            isset($menu['type'][1]) && $tempInfo['type1'] = intval($menu['type'][1]) . "份，" . strval(number_format(($menu['type1price'] / 100), 2, '.', ''));
            isset($menu['type'][2]) && $tempInfo['type2'] = intval($menu['type'][2]) . "份，" . strval(number_format(($menu['type2price'] / 100), 2, '.', ''));
            isset($menu['type'][3]) && $tempInfo['type3'] = intval($menu['type'][3]) . "份，" . strval(number_format(($menu['type3price'] / 100), 2, '.', ''));
            $menu['order'] && $tempInfo['order'] = $menu['order'];
            $data[] = $tempInfo;
        }
        $this->_result['data']['orderTotalPrice'] = number_format($orderTotalPrice, 2, '.', '');
        $this->_result['data']['orderTotalNum'] = $orderTotalNum;
        $this->_result['data']['orderDay'] = substr($orderDay, 0, 4) . '-' . substr($orderDay, 4, 2) . '-' . substr($orderDay, 6, 2);
        $this->_result['data']['data'] = $data;
        return $this->_result;
    }

    /**
     * 用餐人数统计列表
     */
    public function actionPeopleSum()
    {
        $where = [
            'and',
            ['>', 'id', 0],
            ['<', 'status', 2],
        ];
        $orderDay = date('Ymd');
        if ($this->_request['orderTime']) {
            $orderDay = date('Ymd', strtotime($this->_request['orderTime']));
        }
        $where[] = ['=', 'menudate', $orderDay];
        $model = new ShitangCanteenOrder;
        $model = $model::find()->where($where);
        $res = $model->orderBy($this->_orderBy)->all();
        $menuType = [];
        foreach ($res as $row) {
            if (in_array($row->typeid, array(1, 2, 3))) {
                if (!isset($menuType[$row->typeid])) {
                    $menuType[$row->typeid] = ['num' => 1, 'num1' => 0];
                } else {
                    $menuType[$row->typeid]['num'] =   $menuType[$row->typeid]['num'] + 1;
                }
            } else if ($row->typeid == 6) {
                $h = date('G', $row->ordertime);
                if ($h <= 10) {
                    $typeid = 3;
                } else if ($h > 10 && $h <= 14) {
                    $typeid = 1;
                } else if ($h > 14) {
                    $typeid = 2;
                }
                if (!isset($menuType[$typeid])) {
                    $menuType[$typeid] = ['num' => 0, 'num1' => 1];
                } else {
                    $menuType[$typeid]['num1'] =   $menuType[$typeid]['num1'] + 1;
                }
            }
        }
        $data = [];
        $this->_getConfigData();
        foreach ($menuType as $k => $menu) {
            $tempInfo = ['name' => $this->_orderType[$k], 'num' => $menu['num'], 'num1' => $menu['num1']];
            $data[] = $tempInfo;
        }
        $this->_result['data']['orderDay'] = substr($orderDay, 0, 4) . '-' . substr($orderDay, 4, 2) . '-' . substr($orderDay, 6, 2);
        $this->_result['data']['data'] = $data;
        return $this->_result;
    }

    /**
     * 生成每月账号余额变动
     */
    public function actionAccountChangeCreate()
    {
        $createYear = $this->_request['year'];
        $createMonth = $this->_request['month'];
        $userType = intval($this->_request['userType']);
        $howMonth = "$createYear$createMonth";
        if ($howMonth < '202112') {
            $this->_result = Tools::wrongRules(1000, '只能生成2021年12月份以后的数据');
        } else {
            $model = new WeixinStaffMonth;
            $tableName = $model->tableName();
            $preMonth = intval($createMonth) - 1;
            strlen($preMonth) < 2 && $preMonth = "0$preMonth";
            if ($preMonth == '00') {
                $createYear = $createYear - 1;
                $preMonth = '12';
            }
            $lastMonth = $createYear . $preMonth;
            $where = [
                'and',
                ['=', 'howmonth', $lastMonth],
            ];
            if ($userType != '') {
                $where[] = ['=', 'usertype', $userType];
            }
            $lastData = $model::find()->where($where)->one();
            if (!$lastData) {
                $this->_result = Tools::wrongRules(1000, '请先生成前一个月数据');
            } else {
                $where = [
                    'and',
                    ['=', 'howmonth', $howMonth],
                ];
                if ($userType != '') {
                    $where[] = ['=', 'usertype', $userType];
                }
                $row = $model::find()->where($where)->one();
                $query = "insert into $tableName (userid,username,mobile,departmentid,departmentname,howmonth,usertype) select userid,username,mobile,departmentid,departmentname,'$howMonth',usertype from weixin_staff" . ($userType != '' ? " where usertype='$userType'" : "");
                if (!$row) {
                    Yii::$app->db->createCommand($query)->execute();
                } else {
                    $condition = 'howmonth=:howmonth';
                    $params = [':howmonth' => $howMonth];
                    if ($userType != '') {
                        $condition .= " and usertype=:usertype";
                        $params[':usertype'] = $userType;
                    }
                    Yii::$app->db->createCommand()->delete($tableName, $condition, $params)->execute();
                    Yii::$app->db->createCommand($query)->execute();
                }
                $condition = 'howmonth=:howmonth';
                $params = [':howmonth' => $lastMonth];
                if ($userType != '') {
                    $condition .= " and usertype=:usertype";
                    $params[':usertype'] = $userType;
                }
                $lastMonthUser = [];
                $res = Yii::$app->db->createCommand("SELECT * FROM $tableName WHERE $condition", $params)->queryAll();
                foreach ($res as $row) {
                    $lastMonthUser[$row['userid']] = ['endbalance' => $row['endbalance'], 'endbalancewx' => $row['endbalancewx']]; //上个月末余额当新一个月初余额
                }
                $menuDateStart = $howMonth . '01';
                $menuDateEnd = $howMonth . '31';
                $startTime = strtotime($this->_request['year'] . "-" . $this->_request['month'] . '-01 00:00:00');
                $BeginDate = date('Y-m-01', $startTime);
                $EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));
                $endTime = $EndDate . ' 23:59:59';
                $endTime = strtotime($endTime);
                $query = [];
                $condition = 'howmonth=:howmonth';
                $params = [':howmonth' => $howMonth];
                if ($userType != '') {
                    $condition .= " and usertype=:usertype";
                    $params[':usertype'] = $userType;
                }
                $res = Yii::$app->db->createCommand("SELECT * FROM $tableName WHERE $condition", $params)->queryAll();
                foreach ($res as $row) {
                    // 餐补消费订单统计
                    $total = Yii::$app->db->createCommand("select sum(ordermoney) from shitang_canteenorder where wxpay=0 and userid=:userid and status<2 and menudate>=:menudate1 and menudate<=:menudate2", [':userid' => $row['userid'], ':menudate1' => $menuDateStart, ':menudate2' => $menuDateEnd])->queryScalar();
                    // 微信消费订单统计
                    $total1 = Yii::$app->db->createCommand("select sum(ordermoney) from shitang_canteenorder where wxpay=1 and userid=:userid and status<2 and menudate>=:menudate1 and menudate<=:menudate2", [':userid' => $row['userid'], ':menudate1' => $menuDateStart, ':menudate2' => $menuDateEnd])->queryScalar();
                    // 餐补充值
                    $total2 = Yii::$app->db->createCommand("select sum(rechargemoney) from weixin_rechargelog where INSTR(CONCAT(',',rechargeusers,','),'," . $row['userid'] . ",')>0 and weixinpay=0 and inserttime>=:time1 and inserttime<=:time2", ['time1' => $startTime, 'time2' => $endTime])->queryScalar();
                    // 微信充值
                    $total3 = Yii::$app->db->createCommand("select sum(rechargemoney) as total from weixin_rechargelog where INSTR(CONCAT(',',rechargeusers,','),'," . $row['userid'] . ",')>0 and weixinpay=1 and inserttime>=:time1 and inserttime<=:time2", ['time1' => $startTime, 'time2' => $endTime])->queryScalar();

                    !$total && $total = 0;
                    !$total1 && $total1 = 0;
                    !$total2 && $total2 = 0;
                    !$total3 && $total3 = 0;

                    if (isset($lastMonthUser[$row['userid']])) {
                        $row['startbalance'] = $lastMonthUser[$row['userid']]['endbalance'];
                        $row['startbalancewx'] = $lastMonthUser[$row['userid']]['endbalancewx'];
                    } else {
                        $row['startbalance'] = 0;
                        $row['startbalancewx'] = 0;
                    }
                    $endbalance =  $row['startbalance'] + $total2 - $total;
                    $endbalancewx = $row['startbalancewx'] + $total3 - $total1;

                    if ($endbalance < 0) {
                        $userinfo = Yii::$app->db->createCommand("select balance from weixin_staff where userid=:userid", [':userid' => $row['userid']])->queryOne();
                        $endbalance = $userinfo['balance'];
                    }

                    if ($endbalancewx < 0) {
                        $endbalancewx = 0;
                    }

                    Yii::$app->db->createCommand()->update($tableName, ['transfermoney' => $total2, 'transfermoneywx' => $total3, 'ordermoney' => $total, 'wxpay' => $total1, 'startbalance' => $row['startbalance'], 'endbalance' => $endbalance, 'startbalancewx' => $row['startbalancewx'], 'endbalancewx' => $endbalancewx], 'id=:id', [':id' => $row['id']])->execute();
                }
                if ($userType != '') {
                    $this->_getConfigData();
                }
                $action = '生成';
                $remark = $action . "食堂每月账号余额变动。" . ($userType != '' ? '用户类别：' . $this->_userType[$userType]['text'] : '');
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        }
        return $this->_result;
    }

    /**
     * excel导入充值
     */
    public function actionAccountExcelRecharge()
    {
        if (isset($_FILES['upfile']) && isset($this->_request['userType'])) {
            $this->_getConfigData();
            $userType =  $this->_request['userType'];
            $config = array(
                "rootPath" => $this->_fileSavePath,
                "savePath" => 'canteen/excel',
                "maxSize" => 2048000,
                "allowFiles" => array(".xls", ".xlsx"),
            );
            $upInfo = new Uploader("upfile", $config);
            $upResult = $upInfo->getFileInfo();
            if (isset($upResult["url"])) {
                $localFile = $this->_fileSavePath . $upResult["url"];
                $data = Tools::getExcelData($localFile);
                if ($data) {
                    $successNum = 0;
                    $count = count($data);
                    $startRowNum = 3;
                    for ($i = $startRowNum; $i <= $count; $i++) {
                        // 序号
                        $xuhao = intval($data[$i][0]);
                        // 姓名
                        $xingming = trim(str_replace([' ', '　'], '', $data[$i][1]));
                        // 基础餐补
                        $jichucanbu = $data[$i][2];
                        // 请假缺勤天数
                        $queqintianshu = $data[$i][3];
                        // 当月核发
                        $dangyuehefa = $data[$i][4];
                        // 手机号
                        $mobile = $data[$i][5];
                        if ($xuhao && $xingming && $dangyuehefa) {
                            $sameName = '';
                            $dangyuehefa = $dangyuehefa * 100;
                            $res = $this->modelClass::find()->where(['=', 'username', $xingming])->all();
                            if (count($res) > 1) {
                                // 同姓名用户，再通过手机号匹配
                                $where = [
                                    'and',
                                    ['=', 'username', $xingming],
                                    ['=', 'mobile', $mobile],
                                ];
                                $res = $this->modelClass::find()->where($where)->all();
                                $sameName = '原因是存在同姓名用户，但导入数据未设置手机号。';
                            }
                            if (count($res) == 1) {
                                $userInfo = $res[0];
                                // 检查当月是否充值过
                                $BeginDate = date('Y-m-01');
                                $EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));
                                $where = [
                                    'and',
                                    ['=', 'targetrealname', $userInfo->username],
                                    ['=', 'targetuname', $userInfo->userid],
                                    ['between', 'inserttime', strtotime("$BeginDate 00:00:00"), strtotime("$EndDate 23:59:59")],
                                ];
                                $row = WeixinRechargeLogImpro::find()->where($where)->one();
                                if (!$row) {
                                    $userInfo->balance = $userInfo->balance + $dangyuehefa;
                                    if ($userInfo->save()) {
                                        $successNum++;
                                        $formatValue = number_format(($dangyuehefa / 100), 2, '.', '');
                                        $intro = '序号ID：' . $xuhao . '，充值金额：' . $formatValue . '，用户：' . $userInfo->username . '，部门：' . $userInfo->departmentname;
                                        $insertData = [
                                            'uid' => $this->_adminInfo['id'],
                                            'uname' => $this->_adminInfo['username'],
                                            'urealname' => $this->_adminInfo['realname'],
                                            'targetuname' => $userInfo->userid,
                                            'targetrealname' => $userInfo->username,
                                            'departmentname' => $userInfo->departmentname,
                                            'intro' => $intro,
                                            'inserttime' => time(),
                                            'rechargemoney' => $dangyuehefa,
                                            'rechargeall' => $dangyuehefa,
                                            'rechargeusers' => $userInfo->userid,
                                            'usertype' => $userInfo->usertype,
                                        ];
                                    }
                                    $logModel = new WeixinRechargeLogImpro;
                                    $logModel->attributes = $insertData;
                                    if ($logModel->save()) {
                                        $insertData['intro'] = '充值金额：' . $formatValue . '，用户：' . $userInfo->username . '，部门：' . $userInfo->departmentname;
                                        $logModel = new WeixinRechargeLog;
                                        $logModel->attributes = $insertData;
                                        $logModel->save();
                                    }
                                }
                            } else {
                                $intro = '序号ID：' . $xuhao . '，姓名为 ' . $xingming . ' 的用户未找到。' . $sameName;
                                $insertData = [
                                    'uid' => $this->_adminInfo['id'],
                                    'uname' => $this->_adminInfo['username'],
                                    'urealname' => $this->_adminInfo['realname'],
                                    'targetuname' => '',
                                    'targetrealname' => $xingming,
                                    'departmentname' => '',
                                    'intro' => $intro,
                                    'inserttime' => time(),
                                    'rechargemoney' => $dangyuehefa,
                                    'rechargeall' => $dangyuehefa,
                                    'rechargeusers' => '',
                                    'usertype' => $userType,
                                ];
                                $logModel = new WeixinRechargeLogImpro;
                                $logModel->attributes = $insertData;
                                $logModel->save();
                            }
                        }
                    }
                    $this->_result['successNum'] = $successNum;
                    $action = '充值';
                    $remark = $action . "食堂账号。excel文件导入充值，充值类型：" . $this->_userType[$userType]['text'] . "。成功充值 " . $successNum . " 个账号";
                    $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
                } else {
                    $this->_result = Tools::wrongRules(1003, 'excel数据获取失败');
                }
            } else {
                $this->_result = Tools::wrongRules(1002, '上传文件保存失败');
            }
        }
        return $this->_result;
    }

    /**
     * 食堂账号余额导出下载
     */
    public function actionAccountBalanceDownload()
    {
        ini_set("memory_limit", "2048M");
        set_time_limit(0);
        $this->_getConfigData();
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $columns = array(
            'username' => '姓名',
            'departmentname' => '所在部门',
            'balance' => '餐补余额',
            'weixinbalance' => '微信余额',
            'usertype' => '结算分类',
        );
        $i = 0;
        foreach ($columns as $key1 => $value1) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $i) . '1', $value1);
            $i++;
        }
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if (isset($this->_request['userType'])) {
            $where[] = ['=', 'usertype', $this->_request['userType']];
        }
        $model = $this->modelClass;
        $model = $model::find()->where($where);
        $res = $model->all();
        $i = $totalBalance = $totalWeixinBalance = 0;
        foreach ($res as $row) {
            $j = 0;
            $item = $row;
            $totalBalance = $totalBalance + $row->balance;
            $totalWeixinBalance = $totalWeixinBalance + $row->weixinbalance;
            $item['balance'] = number_format(($row->balance / 100), 2, '.', '');
            $item['weixinbalance'] = number_format(($row->weixinbalance / 100), 2, '.', '');
            $item['usertype'] = $this->_userType[$row->usertype]['text'];
            foreach ($columns as $key1 => $value1) {
                $value = $item["$key1"];
                $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit(chr(65 + $j) . ($i + 2), $value);
                $j++;
            }
            $i++;
        }
        $totalBalance = number_format(($totalBalance / 100), 2, '.', '');
        $totalWeixinBalance = number_format(($totalWeixinBalance / 100), 2, '.', '');
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + 2) . ($i + 2), $totalBalance);
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + 3) . ($i + 2), $totalWeixinBalance);

        $objPHPExcel->getActiveSheet()->setTitle('食堂账户余额情况');
        $objPHPExcel->setActiveSheetIndex(0);
        header('Expires: ' . date(DATE_RFC1123));
        header('Cache-Control: no-store, no-cache, must-revalidate,' . '  pre-check=0, post-check=0, max-age=0');
        header('Last-Modified: ' . date(DATE_RFC1123));
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="食堂账户余额情况.xls"');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $action = '导出';
        $remark = $action . "食堂账号余额情况数据。" . (isset($this->_request['userType']) ? '用户类别：' . $this->_userType[$this->_request['userType']]['text'] : '');
        $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        $objWriter->save('php://output');
        exit;
    }

    /**
     * 本月每个部门excel充值金额
     */
    public function actionExcelRechargeTotal()
    {
        $this->_getConfigData();
        $model = new WeixinRechargeLog;
        $tableName = $model->tableName();
        $BeginDate = date('Y-m-01');
        $EndDate = date('Y-m-d', strtotime("$BeginDate +1 month -1 day"));
        $res = Yii::$app->db->createCommand("select SUM(rechargemoney) as sumMoney,usertype from $tableName WHERE inserttime BETWEEN '" . strtotime("$BeginDate 00:00:00") . "' AND '" . strtotime("$EndDate 23:59:59") . "' and usertype>1 and weixinpay=0 GROUP BY usertype")->queryAll();
        foreach ($res as $row) {
            $userTypeName = $this->_userType[$row['usertype']]['text'];
            $money = number_format(($row['sumMoney'] / 100), 2, '.', '');
            $this->_result['data'][] = ['userTypeName' => $userTypeName, 'money' => $money];
        }
        return $this->_result;
    }

    /**
     * 采购登记订单下载
     */
    public function actionCaigouDownload()
    {
        ini_set("memory_limit", "2048M");
        set_time_limit(0);
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $columns = array(
            'name' => '菜品名称',
            'num' => '订单数量',
            'price' => '单价',
            'allprice' => '总金额',
        );
        $i = 0;
        foreach ($columns as $key1 => $value1) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $i) . '2', $value1);
            $i++;
        }
        $where = [
            'and',
            ['=', 'typeid', 5],
            ['<', 'status', 2],
        ];
        if ($this->_request['orderTime']) {
            $where[] = ['=', 'menudate', str_replace('-', '', $this->_request['orderTime'])];
        }
        $model = new ShitangCanteenOrder;
        $model = $model::find()->where($where);
        $res = $model->orderBy($this->_orderBy)->all();
        $menuIds = $menus = [];
        foreach ($res as $row) {
            $orderMenuInfo = explode(',', $row['orderinfo']);
            foreach ($orderMenuInfo as $row1) {
                $menuInfo = explode('|', $row1);
                $menuId = $menuInfo[0];
                $menuName = $menuInfo[1];
                $menuPrice = number_format($menuInfo[2], 2, '.', '');
                $menuNum = $menuInfo[3];
                if (in_array($menuId, $menuIds)) {
                    $menus[$menuId]['num'] = $menus[$menuId]['num'] + $menuNum;
                    $menus[$menuId]['allprice'] = $menus[$menuId]['allprice'] + $menuNum * $menuPrice;
                } else {
                    $menuIds[] = $menuId;
                    $menus[$menuId]['name'] = $menuName;
                    $menus[$menuId]['price'] = $menuPrice;
                    $menus[$menuId]['num'] = $menuNum;
                    $menus[$menuId]['allprice'] = $menuPrice * $menuNum;
                }
            }
        }
        $i = 1;
        foreach ($menus as $row) {
            $j = 0;
            foreach ($columns as $key1 => $value1) {
                $value = $row["$key1"];
                $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit(chr(65 + $j) . ($i + 2), $value);
                $j++;
            }
            $i++;
        }
        $objPHPExcel->getActiveSheet()->setTitle('采购登记订单情况');
        $objPHPExcel->setActiveSheetIndex(0);
        $objPHPExcel->getActiveSheet()->mergeCells('A1:D1'); //合并单元格
        $objPHPExcel->getActiveSheet()->setCellValue('A1', $this->_request['orderTime'] . '统计表'); //给特定单元格中写入内容
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::VERTICAL_CENTER); //水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER); //垂直居中
        header('Expires: ' . date(DATE_RFC1123));
        header('Cache-Control: no-store, no-cache, must-revalidate,' . '  pre-check=0, post-check=0, max-age=0');
        header('Last-Modified: ' . date(DATE_RFC1123));
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="采购登记订单情况.xls"');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $action = '导出';
        $remark = $action . "采购登记订单情况数据。日期：" . $this->_request['orderTime'];
        $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        $objWriter->save('php://output');
        exit;
    }

    /**
     * 菜品排行
     */
    public function actionMenuRanking()
    {
        $where = [
            'and',
            ['=', 'typeid', 1],
            ['<', 'status', 2],
        ];
        if ($this->_request['ordertime']) {
            $insertTime = explode(',', $this->_request['ordertime']);
            $starTime = date('Ymd', strtotime($insertTime[0] . ' 00:00:00'));
            $endTime = date('Ymd', strtotime($insertTime[1] . ' 23:59:59'));
        } else {
            $starTime =  date('Ym') . '01';
            $endTime = date('Ym') . '31';
        }
        $where[] = ['between', 'menudate', $starTime, $endTime];
        $model = new ShitangCanteenOrder;
        $model = $model::find()->where($where);
        $res = $model->orderBy($this->_orderBy)->all();
        $data = [];
        if ($res) {
            $menuIds = $menus = [];
            foreach ($res as $row) {
                $orderMenuInfo = explode(',', $row['orderinfo']);
                foreach ($orderMenuInfo as $row1) {
                    $menuInfo = explode('|', $row1);
                    $menuId = $menuInfo[0];
                    $menuName = $menuInfo[1];
                    $menuNum = $menuInfo[3];
                    if (!in_array($menuId, $menuIds)) {
                        $menuIds[] = $menuId;
                    }
                    $menus[] = array('id' => $menuId, 'name' => $menuName, 'num' => $menuNum, 'menudate' => $row['menudate']);
                }
            }
            foreach ($menuIds as $mid) {
                $menuDate = $menuNum = [];
                foreach ($menus as $v) {
                    if ($mid == $v['id']) {
                        if (!in_array($v['menudate'], $menuDate)) {
                            $menuDate[] = $v['menudate'];
                        }
                        $menuNum[] = $v['num'];
                        $menuName = $v['name'];
                    }
                }
                $sum = array_sum($menuNum);
                $times = count($menuDate);
                $average = round($sum / $times);
                $data[] = array(
                    'id' => $mid,
                    'name' => $menuName,
                    'sum' => $sum,
                    'times' => $times,
                    'average' => $average,
                );
            }
            $sort = array_column($data, 'average');
            array_multisort($sort, SORT_DESC, $data);
        }
        $this->_result["current"] = 1;
        $this->_result["pageSize"] = 10000;
        $this->_result["total"] = count($data);
        $this->_result['data'] = $data;
        return $this->_result;
    }

    /**
     * tab访问权限
     */
    public function actionAccessTab()
    {
        $tabs = $this->_getRouteMenuChildren(125);
        foreach ($tabs as $tab) {
            $this->_result['data'][] = $tab['path'];
        }
        return $this->_result;
    }

    /**
     * tab访问权限
     */
    public function actionSetting()
    {
        $action = $this->_request['action'];
        $typeId = intval($this->_request['typeid']);
        $types = [
            1 => '账号结算类别设置',
            2 => '食堂菜单类别设置',
            3 => '菜单提示信息设置',
            4 => '11:30点餐人员设置',
            5 => '点餐领导人员设置',
            6 => '点餐其他相关设置',
        ];
        $remark = '';
        switch ($action) {
            case 'show':
                $where = [
                    'and',
                    ['>', 'id', 0],
                    ['=', 'typeid', $typeId],
                ];
                if (in_array($typeId, [1, 2])) {
                    $total = 0;
                    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
                    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
                    $offset = $limit * ($page - 1);
                    $total = (new \yii\db\Query())->from('shitang_setting')->where($where)->count('*');
                    $res = (new \yii\db\Query())->select('*')->from('shitang_setting')->where($where)->limit($limit)->offset($offset)->orderBy('id asc')->all();
                    $this->_result["current"] = $page;
                    $this->_result["pageSize"] = $limit;
                    $this->_result["total"] = $total;
                    $this->_result['data'] = $res;
                } else if (in_array($typeId, [4, 5])) {
                    $row = (new \yii\db\Query())->select('*')->from('shitang_setting')->where($where)->one();
                    if ($typeId == 4) {
                        $this->_result['content'] = $row['varvalue'];
                    } else {
                        $users = explode(',', $row['varvalue']);
                        $userid = $username = [];
                        foreach ($users as $v) {
                            $userinfo = explode('|||', $v);
                            $userid[] = $userinfo[0];
                            $username[] = $userinfo[1];
                        }
                        $this->_result['userid'] = $userid;
                        $this->_result['username'] = $username;
                    }
                } else if (in_array($typeId, [3, 6])) {
                    $res = (new \yii\db\Query())->select('*')->from('shitang_setting')->where($where)->all();
                    $this->_result['data'] = $res;
                }
                break;
            case 'save':
                $id = intval($this->_request['id']);
                if (in_array($typeId, [1, 2])) {
                    $varid = $this->_request['varid'];
                    $varvalue = $this->_request['varvalue'];
                    if ($id) {
                        Yii::$app->db->createCommand()->update('shitang_setting',  [
                            'varid' => $varid,
                            'varvalue' => $varvalue,
                        ], "id=:id", [':id' => $id])->execute();
                        $action = '修改';
                        $remark = $action . $types[$typeId] . "。类型ID：" . $varid . "类型名称：" . $varvalue;
                    } else {
                        Yii::$app->db->createCommand()->insert('shitang_setting',  [
                            'varid' => $varid,
                            'varvalue' => $varvalue,
                            'typeid' => $typeId,
                        ])->execute();
                        $action = '新增';
                        $remark = $action . $types[$typeId] . "。类型ID：" . $varid . "类型名称：" . $varvalue;
                    }
                } else if (in_array($typeId, [4, 5])) {
                    $varvalue = $this->_request['username'];
                    if ($typeId == 5) {
                        $varvalue = [];
                        foreach ($this->_request['username'] as $k => $v) {
                            $varvalue[] = $this->_request['userid'][$k] . "|||" . $v;
                        }
                        $varvalue = implode(',', $varvalue);
                    }
                    $row = (new \yii\db\Query())->select('*')->from('shitang_setting')->where(['=', 'typeid', $typeId])->one();
                    if (!$row) {
                        Yii::$app->db->createCommand()->insert('shitang_setting',  [
                            'varvalue' => $varvalue,
                            'typeid' => $typeId,
                        ])->execute();
                        $action = '新增';
                    } else {
                        Yii::$app->db->createCommand()->update('shitang_setting',  [
                            'varvalue' => $varvalue,
                        ], "id=:id", [':id' => $row['id']])->execute();
                        $action = '修改';
                    }
                    $remark = $action . $types[$typeId];
                } else if (in_array($typeId, [3, 6])) {
                    $values = $this->_request['values'];
                    $row = (new \yii\db\Query())->select('*')->from('shitang_setting')->where(['=', 'typeid', $typeId])->one();
                    if (!$row) {
                        foreach ($values as $k => $v) {
                            Yii::$app->db->createCommand()->insert('shitang_setting',  [
                                'varid' => explode('_', $k)[1],
                                'varvalue' => $v,
                                'typeid' => $typeId,
                            ])->execute();
                        }
                        $action = '新增';
                    } else {
                        foreach ($values as $k => $v) {
                            Yii::$app->db->createCommand()->update('shitang_setting',  [
                                'varvalue' => $v,
                            ], 'varid=:varid and typeid=:typeid', [':varid' => explode('_', $k)[1], ':typeid' => $typeId])->execute();
                        }
                        $action = '修改';
                    }
                }
                break;
            case 'user':
                $users = WeixinOAUserInfo::find()->select('userid,name')->where(['and', ['in', 'userid', $this->_request['userid']]])->all();
                $this->_result['data'] = $users;
                break;
        }
        if ($remark) {
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        }
        return $this->_result;
    }

    /**
     * 获取企业号通讯录子部门
     * @param int $id 指定部门id
     * @return array 指定部门及其下的子部门id(递归)
     */
    protected function _getDepartmentIds($id)
    {
        $departmentIds = $departmentNames = [];
        $sendResult =  WxQyhJk::department($id);
        if (!$sendResult['errorMessage']) {
            $departments = $sendResult['data'];
            if (is_array($departments) && count($departments) > 0) {
                foreach ($departments as $department) {
                    $departmentIds[] = $department['id'];
                    $departmentNames[] = $department['name'];
                }
            }
        }
        $this->_departmentIds = $departmentIds;
        $this->_departmentNames = $departmentNames;
    }

    /**
     * 获取配置数据
     */
    protected function _getConfigData()
    {
        $postData = [];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/canteen/config-data';
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $this->_userType = $result['userType'];
            $this->_orderType = $result['orderType'];
            $this->_orderStatus = $result['orderStatus'];
            $this->_orderPayType = $result['orderPayType'];
            $this->_timeInterval = $result['timeInterval'];
        }
    }

    protected function _sortByCuston($a, $b)
    {
        if ($a['departmentname'] < $b['departmentname']) {
            return 1;
        } else if ($a['departmentname'] == $b['departmentname']) {
            return ($a['name'] > $b['name']) ? 1 : -1;
        }
    }

    protected function _formatMenuData($data)
    {
        $data['price'] = $data['price'] * 100;
        if (isset($data['menudate1'])) {
            $data['menudate1'] = str_replace(['-', '/'], '', $data['menudate1']);
        }
        return $data;
    }
}
