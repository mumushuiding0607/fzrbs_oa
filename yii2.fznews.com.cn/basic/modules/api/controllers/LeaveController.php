<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\commons\WorkflowParse;
use app\modules\api\models\WeixinOaApprovalLog;
use app\modules\api\models\WeixinOaNotifyLog;
use app\modules\api\models\WeixinLeaveInfoUndobak;
use app\modules\api\models\WeixinHolidays;
use app\modules\api\models\WeixinOaUserinfo;
use app\modules\api\models\WeixinLeaveTemplate;
use app\modules\api\models\WxDepartment;
use app\modules\api\commons\WxQyhJk;
use Yii;

/**
 * 我的请销假申请信息管理相关接口类
 */
class LeaveController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinLeaveInfo';
    protected $_orderBy = 'inserttime desc';
    protected $_isout = ['', '出省','市内','出市'];
    protected $_status = ['', '审批中', '已通过', '已驳回', '已取消', '销假中', '已销假'];
    protected $_statusIco = ['', 'Processing', 'Success', 'Error', 'Default', 'Processing', 'Success'];
    protected $_ahtTypes = ["病假","调休","产假","陪产假","婚假","公务","独生子女护理假", "育儿假"];
    protected $_leaveTypes = array("非工作日","年假","事假","病假","调休","公务","产假","陪产假","婚假","丧假","探亲","工伤","独生子女护理假", "育儿假");
	protected $_noHolidayTypes = array("产假","陪产假","婚假","探亲");
    protected $_times = ['', '上午','下午','晚上'];
    protected $_agentId = 1000037;
    protected $_afterstart = '14:30';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }

    public function init()
    {
        parent::init();
        // $this->_permissionDeny();
        $thismonth = intval(date('m'));
		if($thismonth > 5 && $thismonth < 10){
			$this->_afterstart = '15:00';
		}else{
			$this->_afterstart = '14:30';
		}
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
        if($this->_request['module']=='own'){
            $data = $this->getOwnListData($limit,$offset);
        }else if($this->_request['module']=='approval'){
            $data = $this->getApprovalData($limit,$offset);
        }else if($this->_request['module']=='notify'){
            $data = $this->getNotifyData($limit,$offset);
        }else if($this->_request['module']=='manage'){
            $data = $this->getManageData($limit,$offset);
        }
        
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $data['total']?intval($data['total']):0;
        $this->_result['data'] = $data['data'];
        $this->_result['module'] = $this->_request['module'];

        return $this->_result;
    }

    private function getOwnListData($limit,$offset)
    {
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        $where[] = ['>', 'status', 0];
        // $this->_adminInfo['wxuserid'] = 'huangxi';
        $where[] = ['=', 'userId', $this->_adminInfo['wxuserid']];
        if ($this->_request['leaveType']) {
            $where[] = ['=', 'leaveType', $this->_request['leaveType']];
        }
        if ($this->_request['leaveStarttime']) {
            $leaveStarttime = explode(',', $this->_request['leaveStarttime']);
            $starTime = $leaveStarttime[0] . ' 00:00:00';
            $endTime = $leaveStarttime[1] . ' 23:59:59';
            $where[] = ['between', 'leaveStarttime', $starTime, $endTime];
        }
        if ($this->_request['leaveEndtime']) {
            $leaveEndtime = explode(',', $this->_request['leaveEndtime']);
            $starTime = $leaveEndtime[0] . ' 00:00:00';
            $endTime = $leaveEndtime[1] . ' 23:59:59';
            $where[] = ['between', 'leaveEndtime', $starTime, $endTime];
        }
        if ($this->_request['inserttime']) {
            $inserttime = explode(',', $this->_request['inserttime']);
            $starTime = $inserttime[0] . ' 00:00:00';
            $endTime = $inserttime[1] . ' 23:59:59';
            $where[] = ['between', 'inserttime', $starTime, $endTime];
        }
        if ($this->_request['status']) {
            $where[] = ['=', 'status', $this->_request['status']];
        }
        $model = $this->modelClass;
        $model = $model::find()->where($where);
        if (isset($this->_request['tree'])) {
            $data = [];
            $res = $model->orderBy($this->_orderBy)->all();
            foreach ($res as $row) {
                $data[] = ['title' => $row->realname, 'key' => $row->username, 'isLeaf' => true];
            }
            $ret['data'] = $data;
        } else {
            $total = $model->count();
            $data = [];
            $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->asArray()->all();
            $workflow = new WorkflowParse($this->_agentId);
            foreach ($res as $row) {
                if(in_array($row['leaveType'],$this->_ahtTypes) && $row['attachment']=='' && !in_array($row['status'],[3,4])){
                    $row['attachment'] = '缺';
                }else{
                    $row['files'] = explode(';',$row['attachment']);
                    $row['attachment'] = ' ';
                }
                $row['flow'] = $workflow->flowViewdata($row['thirdNo']);
                $data[] = $row;
            }
            $ret["total"] = $total;
            $ret['data'] = $data;
        }
        return $ret;
    }

    private function getApprovalData($limit,$offset)
    {
        $infowhere = '';
        $logwhere = '';
        // $where[] = ['=', 'userId', $this->_adminInfo['wxuserid']];
        if ($this->_request['userName']) {
            $infowhere .= " and userName like '%".$this->_request['userName']."%'";
            $logwhere .= " and b.userName like '%".$this->_request['userName']."%'";
        }
        if ($this->_request['leaveType']) {
            $infowhere .= " and leaveType='".$this->_request['leaveType']."'";
            $logwhere .= " and b.leaveType='".$this->_request['leaveType']."'";
        }
        if ($this->_request['department']) {
            $infowhere .= " and department like '%".$this->_request['department']."%'";
            $logwhere .= " and b.department like '%".$this->_request['department']."%'";
        }
        if ($this->_request['leaveStarttime']) {
            $leaveStarttime = explode(',', $this->_request['leaveStarttime']);
            $starTime = $leaveStarttime[0] . ' 00:00:00';
            $endTime = $leaveStarttime[1] . ' 23:59:59';
            $infowhere .= " and leaveStarttime between '$starTime' and '$endTime'";
            $logwhere .= " and b.leaveStarttime between '$starTime' and '$endTime'";
        }
        if ($this->_request['leaveEndtime']) {
            $leaveEndtime = explode(',', $this->_request['leaveEndtime']);
            $starTime = $leaveEndtime[0] . ' 00:00:00';
            $endTime = $leaveEndtime[1] . ' 23:59:59';
            $infowhere .= " and leaveEndtime between '$starTime' and '$endTime'";
            $logwhere .= " and b.leaveEndtime between '$starTime' and '$endTime'";
        }
        if ($this->_request['inserttime']) {
            $inserttime = explode(',', $this->_request['inserttime']);
            $starTime = $inserttime[0] . ' 00:00:00';
            $endTime = $inserttime[1] . ' 23:59:59';
            $infowhere .= " and inserttime between '$starTime' and '$endTime'";
            $logwhere .= " and b.inserttime between '$starTime' and '$endTime'";
        }
        if ($this->_request['leaveTimes']) {
            $infowhere .= " and leaveTimes=" . $this->_request['leaveTimes'];
            $logwhere .= ' and b.leaveTimes=' . $this->_request['leaveTimes'];
        }
        if ($this->_request['isout']) {
            $infowhere .= " and isout=" . intval($this->_request['isout']);
            $logwhere .= ' and b.isout=' . intval($this->_request['isout']);
        }
        if ($this->_request['status']) {
            $logwhere .= ' and b.status=' . $this->_request['status'];
        }
        // $this->_adminInfo['wxuserid'] = 'fanxiong';
        $infoSql = "select * from weixin_leave_info where status=1 and LOCATE('|".$this->_adminInfo['wxuserid']."|',CONCAT('|',approvalUserid,'|')) $infowhere order by inserttime desc";
        $infoModel = $this->modelClass;
        $infoData = $infoModel::findBySql($infoSql)->asArray()->all();
        $infoCnt = count($infoData);
        $logwhere .= $infoCnt?' and b.thirdNo not in ('.implode(',',array_column($infoData, 'thirdNo')).')':'';
        $sql = "select b.* from weixin_oa_approval_log a left join weixin_leave_info b on a.thirdNo=b.thirdNo where a.agentid='".$this->_agentId."' and a.userId ='".$this->_adminInfo['wxuserid']."' $logwhere order by b.inserttime desc";
        $model = WeixinOaApprovalLog::findBySql($sql);
        $total = $model->count()+$infoCnt;
        $data = [];

        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->asArray()->all();
        $infoData = array_merge($infoData,$res);
        $workflow = new WorkflowParse($this->_agentId);

        foreach ($infoData as $row) {
            if(in_array($row['leaveType'],$this->_ahtTypes) && $row['attachment']=='' && !in_array($row['status'],[3,4])){
                $row['attachment'] = '缺';
            }else{
                $row['attachment'] = ' ';
            }
            $row['flow'] = $workflow->flowViewdata($row['thirdNo']);
            $data[] = $row;
        }
        $ret["total"] = $total;
        $ret['data'] = $data;
        return $ret;
    }

    private function getNotifyData($limit,$offset)
    {
        $where = '';
        if ($this->_request['userName']) {
            $where .= " and b.userName like '%".$this->_request['userName']."%'";
        }
        if ($this->_request['leaveType']) {
            $where .= " and b.leaveType='".$this->_request['leaveType']."'";
        }
        if ($this->_request['department']) {
            $where .= " and b.department like '%".$this->_request['department']."%'";
        }
        if ($this->_request['leaveStarttime']) {
            $leaveStarttime = explode(',', $this->_request['leaveStarttime']);
            $starTime = $leaveStarttime[0] . ' 00:00:00';
            $endTime = $leaveStarttime[1] . ' 23:59:59';
            $where .= " and b.leaveStarttime between '$starTime' and '$endTime'";
        }
        if ($this->_request['leaveEndtime']) {
            $leaveEndtime = explode(',', $this->_request['leaveEndtime']);
            $starTime = $leaveEndtime[0] . ' 00:00:00';
            $endTime = $leaveEndtime[1] . ' 23:59:59';
            $where .= " and b.leaveEndtime between '$starTime' and '$endTime'";
        }
        if ($this->_request['inserttime']) {
            $inserttime = explode(',', $this->_request['inserttime']);
            $starTime = $inserttime[0] . ' 00:00:00';
            $endTime = $inserttime[1] . ' 23:59:59';
            $where .= " and b.inserttime between '$starTime' and '$endTime'";
        }
        if ($this->_request['leaveTimes']) {
            $where .= ' and b.leaveTimes=' . $this->_request['leaveTimes'];
        }
        if ($this->_request['isout']) {
            $where .= ' and b.isout=' . intval($this->_request['isout']);
        }
        if ($this->_request['destination']) {
            $where .= " and b.destination like '%".$this->_request['destination']."%'";
        }
        if ($this->_request['status']) {
            $where .= ' and b.status=' . $this->_request['status'];
        }
        // $this->_adminInfo['wxuserid'] = 'wangyu';
        $sql = "select b.* from weixin_oa_notify_log a left join weixin_leave_info b on a.thirdNo=b.thirdNo where a.agentid='".$this->_agentId."' and a.userId ='".$this->_adminInfo['wxuserid']."' $where order by a.inserttime desc";
        $model = WeixinOaNotifyLog::findBySql($sql);
        $total = $model->count();
        $data = [];

        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->asArray()->all();
        foreach ($res as $row) {
            if(in_array($row['leaveType'],$this->_ahtTypes) && $row['attachment']=='' && !in_array($row['status'],[3,4])){
                $row['attachment'] = '缺';
            }else{
                $row['attachment'] = ' ';
            }
            if($row['id']){
                $data[] = $row;
            }
        }
        $ret["total"] = $total;
        $ret['data'] = $data;
        return $ret;
    }

    private function getManageData($limit,$offset)
    {
        $userAuth = $this->getUserAuth();
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        $where[] = ['>', 'status', 0];
        // $where[] = ['in', 'departmentid', $userAuth['childdepts']];
        // $this->_adminInfo['wxuserid'] = 'huangxi';
        if ($this->_request['deptid'] && $this->_request['deptid']) {
            $depts = $this->getMergeChildDepartments([intval($this->_request['deptid'])]);
            $where[] = ['in', 'departmentid', $depts?array_intersect($depts,$userAuth['childdepts']):[intval($this->_request['deptid'])]];
        }
        if ($this->_request['leaveType'] && !$this->_request['athlack']) {
            $where[] = ['in', 'leaveType', explode(',',$this->_request['leaveType'])];
        }else if($this->_request['athlack']){
            $where[] = ['in', 'leaveType', $this->_ahtTypes];
            $where[] = ['=', 'attachment', ''];
            $this->_request['status'] = '1,2,5,6';
        }
        if ($this->_request['userName']) {
            $where[] = ['like', 'userName', $this->_request['userName']];
        }
        if ($this->_request['leaveStarttime']) {
            $starTime = $this->_request['leaveStarttime'] . ' 00:00:00';
            $where[] = ['or',['>=', 'leaveStarttime', $starTime],['>=', 'leaveEndtime', $starTime]];
        }
        if ($this->_request['leaveEndtime']) {
            $endTime = $this->_request['leaveEndtime'] . ' 23:59:59';
            $where[] = ['or',['<=', 'leaveStarttime', $endTime],['<=', 'leaveEndtime', $endTime]];
        }
        if ($this->_request['inserttime']) {
            $inserttime = explode(',', $this->_request['inserttime']);
            $starTime = $inserttime[0] . ' 00:00:00';
            $endTime = $inserttime[1] . ' 23:59:59';
            $where[] = ['between', 'inserttime', $starTime, $endTime];
        }
        if ($this->_request['isout']) {
            $where[] = ['=', 'isout', $this->_request['isout']];
        }
        if ($this->_request['destination']) {
            $where[] = ['like', 'destination', $this->_request['destination']];
        }
        if ($this->_request['status']) {
            $where[] = ['in', 'status', explode(',',$this->_request['status'])];
        }
        // var_dump($this->_request['status']);exit;
        $model = $this->modelClass;
        $model = $model::find()->where($where);
        if (isset($this->_request['tree'])) {
            $data = [];
            $res = $model->orderBy($this->_orderBy)->all();
            foreach ($res as $row) {
                $data[] = ['title' => $row->realname, 'key' => $row->username, 'isLeaf' => true];
            }
            $ret['data'] = $data;
        } else {
            $total = $model->count();
            $data = [];
            $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->asArray()->all();
            $workflow = new WorkflowParse($this->_agentId);
            foreach ($res as $row) {
                if(in_array($row['leaveType'],$this->_ahtTypes) && $row['attachment']=='' && !in_array($row['status'],[3,4])){
                    $row['attachment'] = '缺';
                }
                $row['flow'] = $workflow->flowViewdata($row['thirdNo']);
                $data[] = $row;
            }
            $ret["total"] = $total;
            $ret['data'] = $data;
        }
        return $ret;
    }

    /**
     * 重写create的业务实现动作
     */
    public function actionCreate()
    {
        $postData = $this->_request['values'];
        if ($postData) {
            $userinfo = $this->getUserinfo($this->_adminInfo['wxuserid']);
            $postData['thirdno'] = $this->getMsecTime();
            if ($postData['leaveType'] != '销假') {
                if($postData['leaveTimes']<0){
                    $this->_result = Tools::wrongRules(37001, '请假天数异常！');
                    return $this->_result;
                }
                if($postData['leaveStartD'] && $postData['leaveStartT'] && $postData['leaveEndD'] && $postData['leaveEndT']){
                    if(is_numeric($postData['leaveStartD'])){
                        $starttime = intval($postData['leaveStartD']/1000);
                        $postData['starttime'] = date('Y-m-d',$starttime).' '.($postData['leaveStartT']=='上午' || $postData['leaveStartT']==1?'08:30':($postData['leaveStartT']=='下午' || $postData['leaveStartT']==2?$this->_afterstart:'20:00'));
                    }else{
                        $postData['starttime'] = $postData['leaveStartD'].' '.($postData['leaveStartT']=='上午' || $postData['leaveStartT']==1?'08:30':($postData['leaveStartT']=='下午' || $postData['leaveStartT']==2?$this->_afterstart:'20:00'));
                    }
                    if(is_numeric($postData['leaveEndD'])){
                        $endtime = intval($postData['leaveEndD']/1000);
                        $postData['endtime'] = date('Y-m-d',$endtime).' '.($postData['leaveEndT']=='上午' || $postData['leaveEndT']==1?'12:00':($postData['leaveEndT']=='下午' || $postData['leaveEndT']==2?'18:00':'23:59'));
                    }else{
                        $postData['endtime'] = $postData['leaveEndD'].' '.($postData['leaveEndT']=='上午' || $postData['leaveEndT']==1?'12:00':($postData['leaveEndT']=='下午' || $postData['leaveEndT']==2?'18:00':'23:59'));
                    }
                    if (strtotime($postData['starttime']) >= strtotime($postData['endtime'])) {
                        $this->_result = Tools::wrongRules(37002, '请假时间异常！');
                        return $this->_result;
                    }
                }
                if($postData['leaveType']== '非工作日' && $postData['leaveTimes']>0){
                    $this->_result = Tools::wrongRules(37003, '请选择正确的请假类型！');
                    return $this->_result;
                }
                if (!$postData['isout']) {
                    $this->_result = Tools::wrongRules(37004, '请确认出行范围！');
                    return $this->_result;
                }
                if ($postData['leaveType'] == '非工作日' && $postData['isout'] == 2) {
                    $this->_result = Tools::wrongRules(37005, '当前选择的是非工作日，出行范围在市内，无需提交假条！');
                    return $this->_result;
                }
                if (in_array($postData['isout'], [1, 3]) && !$postData['destination']) {
                    $this->_result = Tools::wrongRules(37006, '请填写出行目的地！');
                    return $this->_result;
                }
                $data = $this->setInfoData($postData,$userinfo);
                $template = $this->getYqTemplate($data, $userinfo);
                if ($postData['pthirdno']) { //续假数据
                    $data['PthirdNo'] = $postData['pthirdno'];
                }
            }else{
                if($postData['leaveTimes']<0){
                    $this->_result = Tools::wrongRules(37001, '天数异常！');
                    return $this->_result;
                }
                if($postData['leaveStartD'] && $postData['leaveStartT'] && $postData['leaveEndD'] && $postData['leaveEndT']){
                    if(is_numeric($postData['leaveStartD'])){
                        $starttime = intval($postData['leaveStartD']/1000);
                        $postData['starttime'] = date('Y-m-d',$starttime).' '.($postData['leaveStartT']=='上午' || $postData['leaveStartT']==1?'08:30':($postData['leaveStartT']=='下午' || $postData['leaveStartT']==2?$this->_afterstart:'20:00'));
                    }else{
                        $postData['starttime'] = $postData['leaveStartD'].' '.($postData['leaveStartT']=='上午' || $postData['leaveStartT']==1?'08:30':($postData['leaveStartT']=='下午' || $postData['leaveStartT']==2?$this->_afterstart:'20:00'));
                    }
                    if(is_numeric($postData['leaveEndD'])){
                        $endtime = intval($postData['leaveEndD']/1000);
                        $postData['endtime'] = date('Y-m-d',$endtime).' '.($postData['leaveEndT']=='上午' || $postData['leaveEndT']==1?'12:00':($postData['leaveEndT']=='下午' || $postData['leaveEndT']==2?'18:00':'23:59'));
                    }else{
                        $postData['endtime'] = $postData['leaveEndD'].' '.($postData['leaveEndT']=='上午' || $postData['leaveEndT']==1?'12:00':($postData['leaveEndT']=='下午' || $postData['leaveEndT']==2?'18:00':'23:59'));
                    }
                    if (strtotime($postData['starttime']) >= strtotime($postData['endtime'])) {
                        $this->_result = Tools::wrongRules(37002, '时间异常！');
                        return $this->_result;
                    }
                }
                $leaveundochk = $this->modelClass::find()->where(['=', 'thirdNo', $postData['lthirdno']])->asArray()->one();
                if ($leaveundochk['status'] == 5) {
                    $this->_result = Tools::wrongRules(37007, '销假申请重复提交！');
                    return $this->_result;
                } else if ($leaveundochk['status'] == 6) {
                    $this->_result = Tools::wrongRules(37008, '该假条已销假！');
                    return $this->_result;
                } else if ($leaveundochk['status'] != 2) {
                    $this->_result = Tools::wrongRules(37009, '该假条不能销假！');
                    return $this->_result;
                }
                $postData['reason'] = '申请销假';
                $postData['undotype'] = 0;
                if ($postData['isahead']) {
                    $postData['leaveReason'] = '申请提前销假';
                    $postData['undotype'] = 1;
                }
                if ($postData['beoverdue']) {
                    $postData['leaveReason'] = '逾期销假申请';
                    $postData['undotype'] = 2;
                    if (!$postData['attachment']) {
                        $this->_result = Tools::wrongRules(37010, '逾期销假申请缺少附件！');
                        return $this->_result;
                    }
                }
                $data = $this->setInfoData($postData,$userinfo);
                if ($postData['beoverdue']) {
                    $template = $this->getUnTemplate(2, $userinfo);
                } else {
                    $template = $this->getUnTemplate(1, $userinfo);
                }
            }
            $leaveinfo =$this->modelClass::find()->where(['=', 'thirdNo', $postData['thirdNo']])->asArray()->one();
            if ($leaveinfo['id']) {
                $this->_result = Tools::wrongRules(37011, '申请失败，重复提交！');
                return $this->_result;
            }
            if ($template) {
                $workflow = new WorkflowParse($this->_agentId);
                $flow = $workflow->flowCreate($postData['thirdno'], $userinfo, $template['templateid']);
                $data['templateId'] = $template['templateid'];
                $approvalUserid = [];
                $approvalUsername = [];
                foreach ($flow['ApprovalNodes']['ApprovalNode'][0]['Items']['Item'] as $item) {
                    $approvalUserid[] = $item['ItemUserId'];
                    $approvalUsername[] = $item['ItemName'];
                }
                $data['approvalUserid'] = implode('|', $approvalUserid);
                $data['approvalUsername'] = implode('|', $approvalUsername);
                $data['status'] = 1;
                //var_dump($data);
                $model = new $this->modelClass(['scenario' => 'create']);
                $model->attributes = $data;
                $ruleResult = Tools::modelRules($model, 37012);
                if ($model->save()) {
                    if ($postData['leaveType'] == '销假') {
                        $this->changeInfoStatus($postData['lthirdno'],5);
                    }
                    if ($postData['pthirdno']) { //续假操作
                        $this->changeInfoStatus($postData['pthirdno'],6);
                    }
                    //发送消息给审批人
                    $msgdata = [
                        'touser' => $data['approvalUserid'],
                        'msgtype' => 'textcard',
                        'agentid' => $this->_agentId,
                        'textcard' => [
                            'title' => $userinfo['name'] . '提交了' . $postData['leaveType'] . '申请',
                            'description' => '<div class="normal">申请类型：' . $postData['leaveType'] . '</div><div class="normal">开始时间：' . $postData['starttime'] . '</div><div class="normal">结束时间：' . $postData['endtime'] . '</div>',
                            'url' => 'https://fzrb.fznews.com.cn/index.php?r=qiyehao/qyApplyleave/view&thirdNo=' . $postData['thirdno'],
                            'btntxt' => '详情'
                        ]
                    ];
                    $this->sendmsg($msgdata);
                    if (intval($flow['NotifyAttr']) == 1 || intval($flow['NotifyAttr']) == 3) {
                        $notifyUserid = [];
                        foreach ($flow['NotifyNodes']['NotifyNode'] as $notify) {
                            $notifyUserid[] = $notify['ItemUserId'];
                            $this->setNotifylog(array('thirdNo' => $_POST['thirdno'], 'userId' => $notify['ItemUserId'], 'userName' => $notify['ItemName']));
                        }
                        if (count($notifyUserid) > 0) {
                            $tonotify = implode('|', $notifyUserid);
                            //发送消息给抄送人
                            $msgdata = [
                                'touser' => $tonotify,
                                'msgtype' => 'textcard',
                                'agentid' => $this->_agentId,
                                'textcard' => [
                                    'title' => $userinfo['name'] . '抄送了' . $postData['leaveType'] . '申请给你',
                                    'description' => '<div class="normal">申请类型：' . $postData['leaveType'] . '</div><div class="normal">开始时间：' . $postData['starttime'] . '</div><div class="normal">结束时间：' . $postData['endtime'] . '</div>',
                                    'url' => 'https://fzrb.fznews.com.cn/index.php?r=qiyehao/qyApplyleave/view&thirdNo=' . $postData['thirdno'],
                                    'btntxt' => '详情'
                                ]
                            ];
                            $this->sendmsg($msgdata);
                        }
                    }
                } else {
                    $this->_result['errorCode'] = $ruleResult['errorCode'];
                    $this->_result['errorMessage'] = $ruleResult['errorMessage'];
                }
            }else{
                $this->_result = Tools::wrongRules(37013, '申请失败，无模版！');
            }
        } else {
            $this->_result = Tools::wrongRules(37014, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 重写update的业务实现动作
     */
    public function actionUpdate()
    {
        $id = intval($this->_request['id']);
        if ($id) {
            // $this->_request['values'] = Tools::setSM3Password($this->_request['values']);
            // $model = $this->modelClass::findOne($id);
            // $model->scenario = 'update';
            // $oldRealname = $model->realname;
            // $oldMobile = $model->mobile;
            // $oldUserType = $model->usertype;
            // $oldStatus = $model->islock;
            // $model->attributes = $this->_request['values'];
            // $ruleResult = Tools::modelRules($model, 2001);
            // if ($ruleResult === true) {
            //     if ($model->save()) {
            //         $action = '修改';
            //         $remark = $action ;
            //         $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            //     }
            // } else {
            //     $this->_result['errorCode'] = $ruleResult['errorCode'];
            //     $this->_result['errorMessage'] = $ruleResult['errorMessage'];
            // }
            $wxuserid = $this->_adminInfo['wxuserid'];
            if(isset($this->_request['thirdNo']) && is_numeric($this->_request['thirdNo'])) {
                $where = [
                    'and',
                    ['=', 'id', $id],
                    ['=', 'thirdNo', $this->_request['thirdNo']],
                ];
                $model = $this->modelClass;
                $res = $model::find()->where($where)->asArray()->one();
                // var_dump($res);
                $approvalUids = explode('|',$res['approvalUserid']);
                if($this->_request['act'] === 'edit'){
                    $ret = $this->modify($res);
                }
                if($this->_request['act'] === 'agree' && in_array($wxuserid,$approvalUids)){
                    $ret = $this->agree($res);
                } 
                if($this->_request['act'] === 'reject' && in_array($wxuserid,$approvalUids)){
                    $ret = $this->reject($res);
                }
                if($this->_request['act'] === 'cancel' && $wxuserid == $res['userId']){
                    $ret = $this->cancel($res);
                } 
                if($this->_request['act'] === 'mcancel' && $this->checkManageAuth($res,'MyLeaveManageCancel')){
                    if(in_array($res['status'],[2,5,6])){
                        $ret = $this->mcancel($res);
                    }else{
                        $this->_result = Tools::wrongRules(37021, '该假条还在审批中，如需撤销应由申请人发起撤销操作！');
                    }
                } 
                if($this->_request['act'] === 'resetflow' && $this->checkManageAuth($res,'MyLeaveManageReset')){
                    if($res['status']==1){
                        $ret = $this->resetflow($res);
                    }else{
                        $this->_result = Tools::wrongRules(37022, '该假条已审批完成，不能重置流程！');
                    }                    
                } 
                if($this->_request['act'] === 'urge' && $wxuserid == $res['userId']){
                    $ret = $this->urge($res);
                } 
                if($ret){
                    $this->_result['message'] = '操作成功';
                }
            }            

        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 修改
     */
    private function modify($leavedata)
    {
        $postData = $this->_request;
        if($postData['leaveTimes']<0){
            $postData['leaveTimes']=0;
            // $this->_result = Tools::wrongRules(37001, '请假天数异常！');
            // return $this->_result;
        }
        if($postData['leaveStartD'] && $postData['leaveStartT'] && $postData['leaveEndD'] && $postData['leaveEndT']){
            if(is_numeric($postData['leaveStartD'])){
                $starttime = intval($postData['leaveStartD']/1000);
                $postData['starttime'] = date('Y-m-d',$starttime).' '.($postData['leaveStartT']=='上午' || $postData['leaveStartT']==1?'08:30':($postData['leaveStartT']=='下午' || $postData['leaveStartT']==2?$this->_afterstart:'20:00'));
            }else{
                $postData['starttime'] = $postData['leaveStartD'].' '.($postData['leaveStartT']=='上午' || $postData['leaveStartT']==1?'08:30':($postData['leaveStartT']=='下午' || $postData['leaveStartT']==2?$this->_afterstart:'20:00'));
            }
            if(is_numeric($postData['leaveEndD'])){
                $endtime = intval($postData['leaveEndD']/1000);
                $postData['endtime'] = date('Y-m-d',$endtime).' '.($postData['leaveEndT']=='上午' || $postData['leaveEndT']==1?'12:00':($postData['leaveEndT']=='下午' || $postData['leaveEndT']==2?'18:00':'23:59'));
            }else{
                $postData['endtime'] = $postData['leaveEndD'].' '.($postData['leaveEndT']=='上午' || $postData['leaveEndT']==1?'12:00':($postData['leaveEndT']=='下午' || $postData['leaveEndT']==2?'18:00':'23:59'));
            }
            if (strtotime($postData['starttime']) >= strtotime($postData['endtime'])) {
                $this->_result = Tools::wrongRules(37002, '请假时间异常！');
                return $this->_result;
            }
        }
        if($postData['leaveType']== '非工作日' && $postData['leaveTimes']>0){
            $this->_result = Tools::wrongRules(37003, '请选择正确的请假类型！');
            return $this->_result;
        }
        if (!$postData['isout']) {
            $this->_result = Tools::wrongRules(37004, '请确认出行范围！');
            return $this->_result;
        }
        if ($postData['leaveType'] == '非工作日' && $postData['isout'] == 2) {
            $this->_result = Tools::wrongRules(37005, '当前选择的是非工作日，出行范围在市内，无需提交假条！');
            return $this->_result;
        }
        if (in_array($postData['isout'], [1, 3]) && !$postData['destination']) {
            $this->_result = Tools::wrongRules(37006, '请填写出行目的地！');
            return $this->_result;
        }
        $leaveModel = $this->modelClass::findOne($leavedata['id']);
        $leaveModel->leaveType = $postData['leaveType'];
        $leaveModel->leaveStarttime = $postData['starttime'];
        $leaveModel->leaveEndtime = $postData['endtime'];
        $leaveModel->leaveTimes = $postData['leaveTimes'];
        $leaveModel->leaveReason = $postData['leaveReason']?$postData['leaveReason']:'';
        if(isset($postData['attachment'])){
            $leaveModel->attachment = $postData['attachment']?implode(';', $postData['attachment']):'';
        }
        $leaveModel->isout = intval($postData['isout']);
        $leaveModel->destination = trim($postData['destination']);
        $leaveModel->save();
        if($leaveModel->leaveType != '销假'){
            $Lleavedata = $this->modelClass::find()->where(['=',"LthirdNo",$leaveModel->thirdNo])->one();
            if($Lleavedata){
                $Lleavedata->leaveStarttime = $postData['starttime'];
                $Lleavedata->leaveEndtime = $postData['endtime'];
                $Lleavedata->save();
            }
        }
        $action = '[请销假]修改操作';
        $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，thirdNo=".$leaveModel->thirdNo."。";
        $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        return true;
    }
    /**
     * 催办
     */
    private function urge($leavedata)
    {
        $msgdata = array(
            'touser' => $leavedata['approvalUserid'],
            'msgtype' => 'textcard',
            'agentid' => $this->_agentId,
            'textcard' => array(
                'title' => $leavedata['userName'].'已发起'.$leavedata['leaveType'].'申请催办',
                'description' => '<div class="normal">申请类型：'.$leavedata['leaveType'].'</div><div class="normal">开始时间：'.$leavedata['leaveStarttime'].'</div><div class="normal">结束时间：'.$leavedata['leaveEndtime'].'</div>',
                'url' => 'https://fzrb.fznews.com.cn/index.php?r=qiyehao/qyApplyleave/view&thirdNo='.$leavedata['thirdNo'],
                'btntxt' => '详情'
            )
        );
        $this->sendmsg($msgdata);
        return true;
    }

    /**
     * 撤销
     */
    private function cancel($leavedata)
    {
        $status = 4;
        $workflow = new WorkflowParse($this->_agentId);
        $ret = $workflow->flowChange($leavedata['thirdNo'],$this->_adminInfo['wxuserid'],$status);
        if($ret){
            $this->changeInfoStatus($leavedata['thirdNo'],$status);
            if($leavedata['leaveType'] == '销假'){
                $this->changeInfoStatus($leavedata['LthirdNo'],2);
            }
            //发送消息给已审过该假条的用户
            $toUser = [];
            $toUser[] = $leavedata['approvalUserid'];
            $leavelog = WeixinOaApprovalLog::find()->where(['=',"thirdNo",$leavedata['thirdNo']])->asArray()->all();
            foreach($leavelog as $r){
                if(!in_array($r['userId'],$toUser)){
                    $toUser[] = $r['userId'];
                }
            }
            //发送消息
            $msgdata = [
                'touser' => implode('|', $toUser),
                'msgtype' => 'textcard',
                'agentid' => $this->_agentId,
                'textcard' => [
                    'title' => $leavedata['userName'].'已撤消'.$leavedata['leaveType'].'申请',
                    'description' => '<div class="normal">申请类型：'.$leavedata['leaveType'].'</div><div class="normal">开始时间：'.$leavedata['leaveStarttime'].'</div><div class="normal">结束时间：'.$leavedata['leaveEndtime'].'</div>',
                    'url' => 'https://fzrb.fznews.com.cn/index.php?r=qiyehao/qyApplyleave/view&thirdNo='.$leavedata['thirdNo'],
                    'btntxt' => '详情'
                ]
            ];
            $this->sendmsg($msgdata);
            return true;
        }
        return false;
    }

    /**
     * 管理员撤销
     */
    private function mcancel($leavedata)
    {
        $status = 4;
        $workflow = new WorkflowParse($this->_agentId);
        $ret = $workflow->flowChange($leavedata['thirdNo'],$leavedata['userId'],$status);
        if($ret){
            $this->changeInfoStatus($leavedata['thirdNo'],$status);
            if($leavedata['leaveType'] == '销假'){
                $leaveinfo =$this->modelClass::find()->where(['=', 'thirdNo', $leavedata['LthirdNo']])->one();
                if($leaveinfo->status==6){
                    $this->changeInfoStatus($leavedata['LthirdNo'],2);
                }
            }
            $action = '[请销假]管理员撤销操作';
            $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，thirdNo=".$leavedata['thirdNo']."。";
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            //发送消息给已审过该假条的用户
            $toUser = [];
            $toUser[] = $leavedata['approvalUserid'];
            $leavelog = WeixinOaApprovalLog::find()->where(['=',"thirdNo",$leavedata['thirdNo']])->asArray()->all();
            foreach($leavelog as $r){
                if(!in_array($r['userId'],$toUser)){
                    $toUser[] = $r['userId'];
                }
            }
            //发送消息
            $msgdata = [
                'touser' => implode('|', $toUser),
                'msgtype' => 'textcard',
                'agentid' => $this->_agentId,
                'textcard' => [
                    'title' => $leavedata['userName'].'的'.$leavedata['leaveType'].'申请已被管理员撤销',
                    'description' => '<div class="normal">申请类型：'.$leavedata['leaveType'].'</div><div class="normal">开始时间：'.$leavedata['leaveStarttime'].'</div><div class="normal">结束时间：'.$leavedata['leaveEndtime'].'</div>',
                    'url' => 'https://fzrb.fznews.com.cn/index.php?r=qiyehao/qyApplyleave/view&thirdNo='.$leavedata['thirdNo'],
                    'btntxt' => '详情'
                ]
            ];
            $this->sendmsg($msgdata);
            return true;
        }
        return false;
    }

    /**
     * 管理权限校验
     */
    private function checkManageAuth($leavedata,$action)
    {
        $userAuth = $this->getUserAuth();
        if(in_array($action,$userAuth['actions']) && in_array($leavedata['departmentid'],$userAuth['childdepts'])){
            return true;
        }
        return false;
    }

    /**
     * 重置流程
     */
    private function resetflow($res)
    {
        $userinfo = $this->getUserinfo($res['userId']);
        if($res['leaveType'] == '销假'){
            $template = $this->getUnTemplate($res['undoType']==2?2:1,$userinfo);
        }else{
            $data = [
                'userId'=> $res['userId'],
                'userName'=> $res['userName'],
                'departmentid'=> $res['departmentid'],
                'department'=> $res['department'],
                'thirdNo'=> $res['thirdNo'],
                'leaveType'=> $res['leaveType'],
                'leaveStarttime'=> $res['leaveStarttime'],
                'leaveEndtime'=> $res['leaveEndtime'],
                'leaveTimes'=> $res['leaveTimes'],
                'leaveReason'=> $res['leaveReason'],
                'attachment'=> $res['attachment'],
                'isout'=> $res['isout'],
                'destination'=> $res['destination']
            ];
            $template = $this->getYqTemplate($data, $userinfo);
        }        
        if ($template) {
            $wfp = new WorkflowParse($this->_agentId);
            $flow = $wfp->flowUpdate($res['thirdNo'], $userinfo, $template['templateid']);
            $updata['templateId'] = $template['templateid'];
            $approvalUserid = [];
            $approvalUsername = [];
            foreach ($flow['ApprovalNodes']['ApprovalNode'][0]['Items']['Item'] as $item) {
                $approvalUserid[] = $item['ItemUserId'];
                $approvalUsername[] = $item['ItemName'];
            }
            $updata['approvalUserid'] = implode('|', $approvalUserid);
            $updata['approvalUsername'] = implode('|', $approvalUsername);
            $updata['approvalStep'] = 0;
            $updata['status'] = 1;
            
            $leaveinfo =$this->modelClass::find()->where(['=', 'id', $res['id']])->one();
            $leaveinfo->templateId = $updata['templateId'];
            $leaveinfo->approvalUserid = $updata['approvalUserid'];
            $leaveinfo->approvalUsername = $updata['approvalUsername'];
            $leaveinfo->approvalStep = $updata['approvalStep'];
            $leaveinfo->status = $updata['status'];
            $leaveinfo->save();
            WeixinOaApprovalLog::deleteAll(['thirdNo'=>$res['thirdNo']]);
            
            $action = '[请销假]管理员重置流程操作';
            $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，thirdNo=".$res['thirdNo']."。";
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);

            // 发送消息给审批人
            $msgdata = array(
                'touser' => $updata['approvalUserid'],
                'msgtype' => 'textcard',
                'agentid' => $this->_agentId,
                'textcard' => array(
                    'title' => $userinfo['name'] . '提交了' . $res['leaveType'] . '申请',
                    'description' => '<div class="normal">申请类型：' . $res['leaveType'] . '</div><div class="normal">开始时间：' . $res['leaveStarttime'] . '</div><div class="normal">结束时间：' . $res['leaveEndtime'] . '</div>',
                    'url' => 'https://fzrb.fznews.com.cn/index.php?r=qiyehao/qyApplyleave/view&thirdNo=' . $res['thirdno'],
                    'btntxt' => '详情'
                )
            );
            $this->sendmsg($msgdata);
            echo json_encode(array('success' => true));
            exit;
        }
    }

    /**
     * 同意
     */
    private function agree($leavedata)
    {
        $status = 2;
        $workflow = new WorkflowParse($this->_agentId);
        $ret = $workflow->flowChange($leavedata['thirdNo'],$this->_adminInfo['wxuserid'],$status,$this->_request['speech']);
        if($ret){
            $leaveModel = $this->modelClass::findOne($leavedata['id']);
            if($ret['nextdata']){
                $leaveModel->approvalUserid = $ret['nextdata']['approvalUserid'];
                $leaveModel->approvalUsername = $ret['nextdata']['approvalUsername'];
                $leaveModel->approvalStep = $ret['nextdata']['approvalStep'];
            }
            if($ret['isfinish']){
                $leaveModel->status = $status;
                //非工作日出市自动销假 2021-12-29
                if($leavedata['leaveType'] == '非工作日' && $leavedata['isout']==3){
                    $leaveModel->status = 6;
                }
                if($leavedata['LthirdNo']){
                    $Lleavedata = $this->modelClass::find()->where(['=',"thirdNo",$leavedata['LthirdNo']])->asArray()->one();
                    $Lleavedata->status = 6;
                    if($leavedata['undoType']==1){
                        $Lleavedata->leaveEndtime = $leavedata['leaveEndtime'];
                        $Lleavedata->leaveTimes = $leavedata['leaveTimes'];
                        $Lleavedata->originalEndtime = $Lleavedata['leaveEndtime'];
                        $Lleavedata->originalTimes = $Lleavedata['leaveTimes'];	
                        $undobakModel = new WeixinLeaveInfoUndobak;
                        $undobakModel->attributes = $Lleavedata;
                        $undobakModel->save();
                    }
                    $Lleavedata->save();
                }
                $msgdata = array(
                    'touser' => $leavedata['userId'],
                    'msgtype' => 'textcard',
                    'agentid' => $this->_agentId,
                    'textcard' => array(
                        'title' => '你的'.$leavedata['leaveType'].'申请已通过',
                        'description' => '<div class="normal">申请类型：'.$leavedata['leaveType'].'</div><div class="normal">开始时间：'.$leavedata['leaveStarttime'].'</div><div class="normal">结束时间：'.$leavedata['leaveEndtime'].'</div>',
                        'url' => 'https://fzrb.fznews.com.cn/index.php?r=qiyehao/qyApplyleave/view&thirdNo='.$leavedata['thirdNo'],
                        'btntxt' => '详情'
                    )
                );
                // $this->sendmsg($msgdata); 发送消息
                if($ret['tonotify']){
                    foreach($ret['tonotify']['userid'] as $k=>$v){
                        $this->setNotifylog(array('thirdNo'=>$leavedata['thirdNo'],'userId'=>$v,'userName'=>$ret['tonotify']['username'][$k]));
                    }
                    $msgdata = array(
                        'touser' => implode('|',$ret['tonotify']['userid']),
                        'msgtype' => 'textcard',
                        'agentid' => $this->_agentId,
                        'textcard' => array(
                            'title' => $leavedata['userName'].'抄送了'.$leavedata['leaveType'].'申请给你',
                            'description' => '<div class="normal">申请类型：'.$leavedata['leaveType'].'</div><div class="normal">开始时间：'.$leavedata['leaveStarttime'].'</div><div class="normal">结束时间：'.$leavedata['leaveEndtime'].'</div>',
                            'url' => 'https://fzrb.fznews.com.cn/index.php?r=qiyehao/qyApplyleave/view&thirdNo='.$leavedata['thirdNo'],
                            'btntxt' => '详情'
                        )
                    );
                    // $this->sendmsg($msgdata); 发送消息
                }
            }else if($ret['touser']){
                $msgdata = array(
                    'touser' => $ret['touser'],
                    'msgtype' => 'textcard',
                    'agentid' => $this->_agentId,
                    'textcard' => array(
                        'title' => $leavedata['userName'].'提交了'.$leavedata['leaveType'].'申请',
                        'description' => '<div class="normal">申请类型：'.$leavedata['leaveType'].'</div><div class="normal">开始时间：'.$leavedata['leaveStarttime'].'</div><div class="normal">结束时间：'.$leavedata['leaveEndtime'].'</div>',
                        'url' => 'https://fzrb.fznews.com.cn/index.php?r=qiyehao/qyApplyleave/view&thirdNo='.$leavedata['thirdNo'],
                        'btntxt' => '详情'
                    )
                );
                // $this->sendmsg($msgdata); 发送消息
            }
            $leaveModel->save();
            return true;
        }
        return false;
    }

    /**
     * 驳回
     */
    private function reject($leavedata)
    {
        $status = 3;
        $workflow = new WorkflowParse($this->_agentId);
        $ret = $workflow->flowChange($leavedata['thirdNo'],$this->_adminInfo['wxuserid'],$status,$this->_request['speech']);
        if($ret){
            $leaveModel = $this->modelClass::findOne($leavedata['id']);
            if ($leavedata['leaveType'] == '销假') {
                $Lleavedata = $this->modelClass::find()->where(['=',"thirdNo",$leavedata['LthirdNo']])->asArray()->one();
                $Lleavedata->status = 2;
                $Lleavedata->save();
            }
            $leaveModel->status = $status;
            $leaveModel->save();
            //发送消息
            $msgdata = array(
                'touser' => $leavedata['userId'],
                'msgtype' => 'textcard',
                'agentid' => $this->_agentId,
                'textcard' => array(
                    'title' => $this->_adminInfo['realname'].'驳回了你的'.$leavedata['leaveType'].'申请',
                    'description' => '<div class="normal">申请类型：'.$leavedata['leaveType'].'</div><div class="normal">开始时间：'.$leavedata['leaveStarttime'].'</div><div class="normal">结束时间：'.$leavedata['leaveEndtime'].'</div>',
                    'url' => 'https://fzrb.fznews.com.cn/index.php?r=qiyehao/qyApplyleave/view&thirdNo='.$leavedata['thirdNo'],
                    'btntxt' => '详情'
                )
            );
            // $this->sendmsg($msgdata);
            //2021-04-22:增加发送消息给已审过该假条的用户
            $toUser = array();
            $leavelog = WeixinOaApprovalLog::find()->where(['=',"thirdNo",$leavedata['thirdNo']])->asArray()->all();
            foreach ($leavelog as $r) {
                if (!in_array($r['userId'], $toUser) && $r['userId'] != $this->_adminInfo['wxuserid']) {
                    $toUser[] = $r['userId'];
                }
            }
            $msgdata = array(
                'touser' => implode('|', $toUser),
                'msgtype' => 'textcard',
                'agentid' => $this->_agentId,
                'textcard' => array(
                    'title' => $this->_adminInfo['realname'] . '驳回了' . $leavedata['userName'] . '的' . $leavedata['leaveType'] . '申请',
                    'description' => '<div class="normal">申请类型：' . $leavedata['leaveType'] . '</div><div class="normal">开始时间：' . $leavedata['leaveStarttime'] . '</div><div class="normal">结束时间：' . $leavedata['leaveEndtime'] . '</div>',
                    'url' => 'https://fzrb.fznews.com.cn/index.php?r=qiyehao/qyApplyleave/view&thirdNo=' . $leavedata['thirdNo'],
                    'btntxt' => '详情'
                )
            );
            // $this->sendmsg($msgdata);
            return true;
        }
        return false;
    }

    private function setNotifylog($data)
    {
        if($data){
            $res = WeixinOaNotifyLog::find()->where(['and',["=","thirdNo",$data['thirdNo']],["=","userId",$data['userId']]])->asArray()->one();
			if(!$res){
                $notifylogModel = new WeixinOaNotifyLog(['scenario' => 'create']);
                $notifylogModel->agentid = $this->_agentId;
                $notifylogModel->thirdNo = $data['thirdNo'];
                $notifylogModel->userId = $data['userId'];
                $notifylogModel->userName = $data['userName'];
                $ruleResult = Tools::modelRules($notifylogModel, 400001);
                if ($ruleResult === true) {
                    $notifylogModel->save();
                }
			}
		}
    }

    /**
     * 重写delete的业务实现动作
     */
    public function actionDelete()
    {
        if ($this->_request['id']) {
            // $this->modelClass::deleteAll(['in', 'id', explode(',', $this->_request['id'])]);
            $ids = explode(',', $this->_request['id']);
            $usernames = [];
            $models = $this->modelClass::find()->where(['in', 'id', $ids])->all();
            foreach ($models as $model) {
                $usernames[] = $model->username;
                $model->delete();
            }
            if ($usernames) {
                $action = '删除';
                $remark = $action . "用户账号。账号名称：" . implode(',', $usernames) . "。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 字典数据
     */
    public function actionDict()
    {
        $years = [date('Y'),date('Y')+1];
        $holiday = $noholiday = [];
        $holidays = WeixinHolidays::find()->where(['in',"year",$years])->asArray()->all();
        foreach($holidays as $r){
            if($r['type'] == 1){
                $tmp = explode(',',$r['days']);
                $noholiday = array_merge($noholiday,$tmp);
            }else{
                $tmp = explode(',',$r['days']);
                $holiday =  array_merge($holiday,$tmp);
            }
        }
        $data['noholiday'] = $noholiday;
        $data['holiday'] = $holiday;
        $data['leaveTypes'] = $this->_leaveTypes;
        $data['noHolidayTypes'] = $this->_noHolidayTypes;
        $data['afterstart'] = $this->_afterstart;
        $statusTmp = [];
        foreach($this->_status as $k=>$v){
            if($k>0){
                $statusTmp[$k]=['text'=>$v,'status'=>$this->_statusIco[$k]];
            }
        }
        $data['status'] = $statusTmp;
        $isoutTmp = [];
        foreach($this->_isout as $k=>$v){
            if($k>0){
                $isoutTmp[$k]=['text'=>$v];
            }
        }
        $data['isout'] = $isoutTmp;
        $timeTmp = [];
        foreach($this->_times as $k=>$v){
            if($k>0){
                $timeTmp[$k]=['text'=>$v];
            }
        }
        $data['times'] = $timeTmp;
        $this->_result['data'] = $data;
        return $this->_result;
    }

    /**
     * 用户权限数据
     */
    public function actionAuth()
    {
        $this->_result['data'] = $this->getUserAuth();
        return $this->_result;
    }

    private function getUserAuth()
    {
        $res = Yii::$app->db->createCommand("SELECT modules,departments,actions FROM `weixin_oa_auth` where agentid='$this->_agentId' and (FIND_IN_SET('".$this->_adminInfo['username']."',sysusers) or FIND_IN_SET('".$this->_adminInfo['wxuserid']."',wxusers)) ")->queryAll();
        $data = [];
        foreach($res as $item){
            $item['modules'] = $item['modules']?explode(',',$item['modules']):[];
            $item['actions'] = $item['actions']?explode(',',$item['actions']):[];
            $item['departments'] = $item['departments']?explode(',',$item['departments']):[];
            if($data){
                $data['modules'] = array_merge($data['modules'],$item['modules']);
                $data['departments'] = array_merge($data['departments'],$item['departments']);
            }else{
                $data = $item;
            }            
        }
        $data['childdepts'] = $this->getMergeChildDepartments($data['departments']);
        $data['departments'] = array_unique(array_merge($this->getMergeParentDepartments($data['departments']),$this->getMergeChildDepartments($data['departments'])));
        $data['departments'] = filter_var_array($data['departments'],FILTER_VALIDATE_INT);
        sort($data['departments']);
        return $data;
    }
    
    /**
     * 请销假数据导出
     */
    public function actionLeaveinfoDownload()
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
                // 'id' => 'ID',
                'userName' => '姓名',
                'department' => '所在部门',
                'leaveType' => '申请类别',
                'leaveStarttime' => '开始时间',
                'leaveEndtime' => '结束时间',
                'leaveTimes' => '天数',
                'status' => '状态',
                'leaveReason' => '事由',
                'isout' => '是否出省',
                'destination' => '出行位置',
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
        if (isset($this->_request['search-name'])) {
            $where[] = ['=', 'userName', $this->_request['search-name']];
        }
        if (isset($this->_request['search-type'])) {
            $where[] = ['in', 'leaveType', $this->_request['search-type']];
        }
        if (isset($this->_request['search-status'])) {
            $where[] = ['in', 'status', $this->_request['search-status']];
        }
        if (isset($this->_request['search-isout'])) {
            $where[] = ['=', 'isout', $this->_request['search-isout']];
        }
        if (isset($this->_request['search-destination'])) {
            $where[] = ['=', 'destination', $this->_request['search-destination']];
        }
        if (isset($this->_request['search-range'])) {
            $where[] = ['and',['or',['>=', 'leaveStarttime', $this->_request['search-range'][0].' 00:00:00'],['>=', 'leaveEndtime', $this->_request['search-range'][0].' 00:00:00']], ['or',['<=', 'leaveStarttime', $this->_request['search-range'][1].' 23:59:59'],['<=', 'leaveEndtime', $this->_request['search-range'][1].' 23:59:59']]];
        }
        $authData = $this->getUserAuth();
        $depts = [];
        if (isset($this->_request['departments']) && is_array($this->_request['departments']) && $this->_request['departments']) {
            $depts = [];
            foreach($this->_request['departments'] as $d){
                $depts[] = $d['value'];
            }
            $childDept = $this->getMergeChildDepartments($depts);
            $childDept = $childDept?$childDept:$depts;
            if($authData['childdepts']){
                $depts = array_intersect($childDept,$authData['childdepts']);
            }else{
                $depts = array_intersect($childDept,$authData['departments']);
            }
        }
        
        $depts = $depts?$depts:($authData['childdepts']?$authData['childdepts']:$authData['departments']);
        if($depts){
            $where[] = ['in', 'departmentid', $depts];
        }
        $model = $this->modelClass;
        $model = $model::find()->where($where)->orderBy('leaveStarttime ASC');
        $res = $model->all();
        $i = 0;
        foreach ($res as $row) {
            $j = 0;
            $item = $row;
            $item['status'] = $this->_status[$item['status']];
            $item['isout'] = $this->_isout[$item['isout']];
            foreach ($columns as $key1 => $value1) {
                if($key1=='leaveReason' || $key1=='destination'){
                    $value = $this->cleanData($item[$key1]);
                }else{
                    $value = $item[$key1];
                }
                $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit(chr(65 + $j) . ($i + 2), $value);
                $j++;
            }
            $i++;
        }

        $objPHPExcel->getActiveSheet()->setTitle('请销假信息');
        $objPHPExcel->setActiveSheetIndex(0);
        header('Expires: ' . date(DATE_RFC1123));
        header('Cache-Control: no-store, no-cache, must-revalidate,' . '  pre-check=0, post-check=0, max-age=0');
        header('Last-Modified: ' . date(DATE_RFC1123));
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="请销假信息'.date('YmdH').'.xls"');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $action = '[请销假]数据导出';
        $remark = $action . "操作人=".$this->_adminInfo['wxuserid'] ;
        $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        $objWriter->save('php://output');
        exit;
    }

    private function cleanData($str)
    {
        $a = @iconv("utf-8","gbk",$str);
        $b= @iconv("gbk","utf-8",$a);
        return $b;
    }

    private function setInfoData($postData,$userinfo)
    {
        $data = [
            'userId' => $userinfo['userid'],
            'userName' => $userinfo['name'],
            'departmentid' => $userinfo['departmentid'],
            'department' => $userinfo['departmentname'],
            'thirdNo' => strval($postData['thirdno']),
            'LthirdNo' => $postData['lthirdno']?$postData['lthirdno']:'',
            'leaveType' => $postData['leaveType'],
            'leaveStarttime' => $postData['starttime'],
            'leaveEndtime' => $postData['endtime'],
            'leaveTimes' => $postData['leaveTimes'],
            'leaveReason' => $postData['leaveReason']?$postData['leaveReason']:'',
            'attachment' => $postData['attachment'] ? implode(';', $postData['attachment']) : '',
            'isout' => intval($postData['isout']),
            'destination' => trim($postData['destination']),
            'undoType' => $postData['undotype']?$postData['undotype'] : 0
        ];
        return $data;
    }

    private function sendmsg($data)
    {
        $result = WxQyhJk::sendMessage($data['agentid'],$data['touser'],$data['textcard'],'textcard');
        $action = '[请销假]发送消息';
        $remark = $action . "msg=" . json_encode($data) . "，result=". json_encode($result);
        $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
    }
    /**
     * 改变假条数据状态
     */
    private function changeInfoStatus($thirdNo,$status)
    {
        $leaveinfo =$this->modelClass::find()->where(['=', 'thirdNo', $thirdNo])->one();
        $leaveinfo->status = $status;
        $leaveinfo->save();
    }

    /**
     * 获取企业微信用户信息
     */
    private function getUserinfo($userid)
    {
        $userinfo = WeixinOaUserinfo::find()->where(['=', 'userid', $userid])->asArray()->one();
        return $userinfo;
    }

    /**
     * 获取请假流程模版
     */
	private function getYqTemplate($data, $userinfo)
	{ //疫情期间获取请假模版
		if ($userinfo) {
			$leaveTimes = $data['leaveTimes'];
			if (in_array($data['isout'], [1, 3])) {
				if ($data['leaveTimes'] <= 1 && $userinfo['level'] == 0) {
					$leaveTimes = 3;
				}
				if ($userinfo['level'] > 0) {
					$leaveTimes = 16;
				}
			}
            $template = WeixinLeaveTemplate::findBySql("select * from weixin_leave_template where type=0 and min<" . $leaveTimes . " and max>=" . $leaveTimes . " and FIND_IN_SET('" . $userinfo['id'] . "',uids)")->asArray()->one();
			if ($template) return $template;
            $template = WeixinLeaveTemplate::findBySql("select * from weixin_leave_template where type=0 and level ='" . $userinfo['level'] . "' and is_company='" . $userinfo['is_company'] . "' and min<" . $leaveTimes . " and max>=" . $leaveTimes . " and FIND_IN_SET('" . $userinfo['departmentid'] . "',dids)")->asArray()->one();
			if ($template) return $template;
		}
		return false;
	}

    /**
     * 获取销假流程模版
     */
	private function getUnTemplate($t = 1,$userinfo){//获取销假模版ID
		if($userinfo){
			$level = $userinfo['level']==2?0:$userinfo['level'];
            $template = WeixinLeaveTemplate::findBySql("select * from weixin_leave_template where type=$t and FIND_IN_SET('".$userinfo['id']."',uids)")->asArray()->one();
			if($template)return $template;
            $template = WeixinLeaveTemplate::findBySql("select * from weixin_leave_template where type=$t and level ='$level' and is_company='".$userinfo['is_company']."' and FIND_IN_SET('".$userinfo['departmentid']."',dids)")->asArray()->one();
			if($template)return $template;
		}
		return false;
	}

    /**
     * 获取毫秒级别的时间戳
     */
    private function getMsecTime()
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
	}

    /**
     * 返回所有上级部门
     */
    private function getMergeParentDepartments($dept)
    {
        $res = WxDepartment::find()->where(['in','id',$dept])->asArray()->all();
        foreach($res as $row){
            $pids = $this->getMergeParentDepartments([$row['parentid']]);
            if($pids){
                $dept = array_merge($dept,$pids);
            }else{
                $dept[] =  $row['parentid'];
            }
        }
        return $dept;
    }

    /**
     * 返回所有下级部门
     */
    private function getMergeChildDepartments($dept)
    {
        $childs = [];
        $res = WxDepartment::find()->where(['in','parentid',$dept])->asArray()->all();
        foreach($res as $row){
            $childs[] = $row['id'];
        }
        if($childs){
            $child = $this->getMergeChildDepartments($childs);
            if($child){
                $childs = array_merge($childs,$child);
            }            
        }else{
            return [];
        }
        return array_merge($dept,$childs);
    }
}
