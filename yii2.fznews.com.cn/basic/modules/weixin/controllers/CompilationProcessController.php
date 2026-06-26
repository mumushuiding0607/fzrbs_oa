<?php

namespace app\modules\weixin\controllers;

use Yii;
use Exception;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;
use app\modules\api\models\WeixinOaApprovaldata;
use app\modules\api\models\WeixinOaApprovalInfo;
use app\modules\api\models\WeixinOaApprovalLog;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\api\commons\WorkflowParse;

/**
 * 采编流程相关接口类
 */
class CompilationProcessController extends ApiBase
{
    public $enableCsrfValidation = false;
    protected $_orderBy = 'id desc';
    protected $_agentId = 1000081;
    protected $_departments = [];
    protected $_department_name = [];
    protected $_questions = [];
    protected $_director_users = [];
    protected $_infoData;
    // 指挥中心值班主任
    protected $_user2 = [
        ['text' => '陈永章', 'id' => 'chenyongzhang'],
        ['text' => '周甬', 'id' => 'zhouyong'],
        ['text' => '陈宜孝', 'id' => 'ChenYiXiao'],
    ];
    // 值班总指挥
    protected $_user3 = [
        ['text' => '吴金垵', 'id' => 'WuJinAn'],
        ['text' => '金麦子', 'id' => 'jinmaizi'],
        ['text' => '朱福星', 'id' => 'ZhuFuXing'],
        ['text' => '林洪相', 'id' => 'linhongxiang'],
        ['text' => '陈永章', 'id' => 'chenyongzhang'],
        ['text' => '林伟', 'id' => 'linwei'],
        ['text' => '黎伦俊', 'id' => 'lilunjun'],
        ['text' => '黄戎杰', 'id' => 'huangrongjie'],
        ['text' => '卓良辉', 'id' => 'ZhuoLiangHui'],
        ['text' => '林雨夏', 'id' => 'linyuxia'],
        ['text' => '赵莹', 'id' => 'zhaoying'],
        ['text' => '雷岩平', 'id' => 'leiyanping'],
        ['text' => '危砖黄', 'id' => 'weizhuanhuang'],
        ['text' => '张浩清', 'id' => 'zhanghaoqing'],
        ['text' => '陈宜孝', 'id' => 'ChenYiXiao'],
    ];
    protected $_roleId = [1 => 18, 2 => 48, 3 => 49];

    protected $DEBUG = false;
    protected $db;
    protected $agentId = 1000081;
    protected $controllerName = 'qypress';
    protected $statusCn = array('', '审核中', '已通过', '已驳回', '已取消');
    protected $nodeStatus = array('', '审核中', '已同意', '已驳回', '已转审', '已跳过');
    protected $nodeAttr = array('', '或签', '会签', '跳过');
    protected $tagClass = array('', 'primary', 'success', 'warning', 'warning');
    protected $infoTable = 'weixin_oa_approval_info';
    protected $approvalLogTable = 'weixin_oa_approval_log';
    protected $notifyLogTable = 'weixin_oa_notify_log';
    protected $approvaldataTable = 'weixin_oa_approvaldata';
    protected $DEPARTMENT_TABLE = 'weixin_leave_department';
    protected $FlowroleTable = 'weixin_oa_flowrole';
    protected $AdminRoleid = 16;
    protected $LeaderRoleid = 17;
    protected $DirectorRoleid = 18;
    protected $APPROVAL_TYPE = 1;
    protected $TEMPLATE = '3596b8b82d9eb42225237f65c14b8c80_1680076143';
    protected $NEWSPAPERS = array(24 => '日报', 25 => '晚报');

    public function init()
    {
        parent::init();
        $hour = intval(date('H'));
        $user1_rb = $user1_wb = $user1_xmt = [];
        $this->_user2 = $this->_user3 = [];
        foreach ($this->_roleId as $k => $v) {
            $res = (new \yii\db\Query())->select('userid,username,dept')->from('weixin_oa_flowrole')->where(['and', ['=', 'role', $v], ['like', 'agent', $this->_agentId]])->orderBy('id asc')->all();
            foreach ($res as $row) {
                $dept = explode(',', $row['dept']);
                if ($v == 18) {
                    // 值班主任
                    if (in_array(3, $dept)) {
                        // 日报
                        $user1_rb[] = ['text' => $row['username'], 'id' => $row['userid']];
                    } else if (in_array(5, $dept)) {
                        // 晚报
                        $user1_wb[] = ['text' => $row['username'], 'id' => $row['userid']];
                    } else if (in_array(6, $dept)) {
                        // 新媒体
                        $user1_xmt[] = ['text' => $row['username'], 'id' => $row['userid']];
                    }
                } else if ($v == 48) {
                    $this->_user2[] = ['text' => $row['username'], 'id' => $row['userid']];
                } else if ($v == 49) {
                    $this->_user3[] = ['text' => $row['username'], 'id' => $row['userid']];
                }
            }
        }

        // 测试用户信息
        // $user1_rb[] = ['text' => '陈宜孝', 'id' => 'ChenYiXiao'];
        // $user1_rb[] = ['text' => '郭惠峰', 'id' => 'guohuifeng'];
        // $user1_rb[] = ['text' => '林汀', 'id' => 'linting'];
        // $user1_rb[] = ['text' => '王晨', 'id' => 'wangchen'];
        // $user1_rb[] = ['text' => '黄一清', 'id' => 'huangyiqing'];

        // $user1_wb[] = ['text' => '陈宜孝', 'id' => 'ChenYiXiao'];
        // $user1_wb[] = ['text' => '郭惠峰', 'id' => 'guohuifeng'];
        // $user1_wb[] = ['text' => '林汀', 'id' => 'linting'];
        // $user1_wb[] = ['text' => '王晨', 'id' => 'wangchen'];
        // $user1_wb[] = ['text' => '黄一清', 'id' => 'huangyiqing'];

        // $user1_xmt[] = ['text' => '陈宜孝', 'id' => 'ChenYiXiao'];
        // $user1_xmt[] = ['text' => '郭惠峰', 'id' => 'guohuifeng'];
        // $user1_xmt[] = ['text' => '林汀', 'id' => 'linting'];
        // $user1_xmt[] = ['text' => '王晨', 'id' => 'wangchen'];
        // $user1_xmt[] = ['text' => '黄一清', 'id' => 'huangyiqing'];

        // $this->_user2[] = ['text' => '陈宜孝', 'id' => 'ChenYiXiao'];
        // $this->_user2[] = ['text' => '郭惠峰', 'id' => 'guohuifeng'];
        // $this->_user2[] = ['text' => '林汀', 'id' => 'linting'];
        // $this->_user2[] = ['text' => '王晨', 'id' => 'wangchen'];
        // $this->_user2[] = ['text' => '黄一清', 'id' => 'huangyiqing'];

        // $this->_user3[] = ['text' => '陈宜孝', 'id' => 'ChenYiXiao'];
        // $this->_user3[] = ['text' => '郭惠峰', 'id' => 'guohuifeng'];
        // $this->_user3[] = ['text' => '林汀', 'id' => 'linting'];
        // $this->_user3[] = ['text' => '王晨', 'id' => 'wangchen'];
        // $this->_user3[] = ['text' => '黄一清', 'id' => 'huangyiqing'];

        $res = (new \yii\db\Query())->select('id,name')->from('fzrbs_cblc_cd')->where(['=', 'typeid', 2])->orderBy('id asc')->all();
        foreach ($res as $row) {
            $this->_departments[] = ['value' => $row['id'], 'text' => $row['name']];
            $this->_department_name[$row['id']] = $row['name'];
            if ($row['name'] == '福州日报') {
                $this->_director_users[$row['id']] = [
                    // 值班主任
                    // 1 => [
                    //     ['text' => '黎伦俊', 'id' => 'lilunjun'],
                    //     ['text' => '谢星星', 'id' => 'xiexingxing'],
                    //     ['text' => '覃作权', 'id' => 'qinzuoquan'],
                    //     ['text' => '林玉和', 'id' => 'linyuhe'],
                    //     ['text' => '黄戎杰', 'id' => 'huangrongjie'],
                    //     ['text' => '程仁山', 'id' => 'chengrenshan'],
                    //     ['text' => '王新', 'id' => 'wangxin'],
                    //     ['text' => '杨韬', 'id' => 'yangtao'],
                    //     ['text' => '陈宜孝', 'id' => 'ChenYiXiao'],
                    // ],
                    1 => $user1_rb,
                    // 指挥中心值班主任
                    2 => $this->_user2,
                    // 值班总指挥
                    3 => $this->_user3,
                ];
            } else  if ($row['name'] == '福州晚报') {
                $this->_director_users[$row['id']] = [
                    // 值班主任
                    // 1 => [
                    //     ['text' => '侯宗焜', 'id' => 'houzongkun'],
                    //     ['text' => '邱泉盛', 'id' => 'qiuquansheng'],
                    //     ['text' => '何佳媛', 'id' => 'hejiayuan'],
                    //     ['text' => '危砖黄', 'id' => 'weizhuanhuang'],
                    //     ['text' => '林亦敏', 'id' => 'linyimin'],
                    //     ['text' => '王臻', 'id' => 'wangzhen'],
                    //     ['text' => '雷岩平', 'id' => 'leiyanping'],
                    //     ['text' => '管慧', 'id' => 'guanhui'],
                    //     ['text' => '兰超', 'id' => 'lanchao'],
                    //     ['text' => '陈宜孝', 'id' => 'ChenYiXiao'],
                    // ],
                    1 => $user1_wb,
                    // 指挥中心值班主任
                    2 => $this->_user2,
                    // 值班总指挥
                    3 => $this->_user3,
                ];
            } else  if ($row['name'] == '新媒体中心') {
                $this->_director_users[$row['id']] = [
                    // 值班主任
                    // 1 => [
                    //     ['text' => '吴文霖', 'id' => 'wuwenlin'],
                    //     ['text' => '范雄', 'id' => 'fanxiong'],
                    //     ['text' => '贺鹏', 'id' => 'hepeng'],
                    //     ['text' => '黄而海', 'id' => 'huangerhai'],
                    //     ['text' => '林敏勇', 'id' => 'LinMinYong01'],
                    //     ['text' => '陈宜孝', 'id' => 'ChenYiXiao'],
                    // ],
                    1 => $user1_xmt,
                    // 指挥中心值班主任
                    2 => $this->_user2,
                    // 值班总指挥
                    3 => $this->_user3,
                ];
            }
        }
        $res = (new \yii\db\Query())->select('id,name,state')->from('fzrbs_cblc_cd')->where(['=', 'typeid', 1])->orderBy('id asc')->all();
        foreach ($res as $row) {
            $disabled = (($row['state'] == 1 && ($hour >= 18 || $hour < 6)) || ($row['state'] == 2 && ($hour < 18 && $hour >= 6)));
            $this->_questions[] = ['id' => $row['id'], 'name' => $row['name'], 'problem' => false, 'remark' => '', 'state' => $row['state'], 'disabled' => $disabled];
        }
    }

    /**
     * 列表动作
     */
    public function actionList()
    {
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 100;
        $offset = $limit * ($page - 1);
        $flag = $this->_request['flag'];
        $where = [
            'and',
            ['>', 'id', 0],
            ['=', 'agentId', $this->_agentId],
        ];
        if ($flag == 'my') {
            $where[] = ['=', 'userId', $this->_UserId];
        } else if ($flag == 'receive') {
            if ($this->_request['status'] == 1) {
                $where[] = ['=', 'status', 1];
                $where[] = ['like', "CONCAT('|', approvalUserid, '|')", '|' . $this->_UserId . '|'];
            } else if ($this->_request['status'] == 2) {
                $subQuery = (new \yii\db\Query())->select('thirdNo')->from($this->approvalLogTable)->where(['and', ['=', 'agentid', $this->_agentId], ['=', 'userId', $this->_UserId], ['=', 'status', 2]]);
                $where[] = ['thirdNo' => $subQuery];
            }
        } else {
            $where[] = ['=', 'id', 0];
        }
        $data = [];
        $res = (new \yii\db\Query())->select('*')->from($this->infoTable)->where($where)->limit($limit)->offset($offset)->orderBy('id desc')->all();
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        foreach ($res as $k => $row) {
            $flowdata = json_decode($row['data'], true);
            $item = [
                'title' => $this->_department_name[$flowdata['typeid']] . '-采编流程重点环节督查内容表',
                'formdate' => $flowdata['formdate'],
                'formtype' => $flowdata['formtype'] == 1 ? '白班' : '夜班',
                'status' => $this->statusCn[$row['status']],
                'statustype' => $this->tagClass[$row['status']],
                'userName' => $row['userName'],
                'thirdNo' => $row['thirdNo'],
            ];
            $data[] = $item;
        }
        $this->_result['data']['data'] = $data;
        Tools::responseJson($this->_result);
    }

    /**
     * 获取编辑内容
     */
    public function actionInfo()
    {
        if ($this->_request['thirdNo']) {
            $thirdNo = $this->_request['thirdNo'];
            $data = (new \yii\db\Query())->select('*')->from("weixin_oa_approval_info")->where(['and', ['=', 'agentId', $this->_agentId], ['=', 'thirdNo', $thirdNo]])->one();
            $approvedata = (new \yii\db\Query())->select('*')->from("weixin_oa_approvaldata")->where(['and', ['=', 'agentId', $this->_agentId], ['=', 'thirdNo', $thirdNo]])->one();
            $flowdata = array();
            $userlimit = array();
            if ($data && $approvedata) {
                $userlimit[] = $data['userId'];
                $data['Tag'] = $this->statusCn[$approvedata['status']];
                $data['Tagclass'] = $this->tagClass[$approvedata['status']];
                $approvearr = json_decode($approvedata['data'], true);
                $notifier = array();

                $data['Notifier'] = implode('、', $notifier);
                $data['Avater'] = $approvearr['data']['ApplyUserImage'];
                $data['Step'] = intval($approvearr['data']['approverstep']) - 1;
                foreach ($approvearr['data']['ApprovalNodes']['ApprovalNode'] as $k => $r) {
                    $tmparr = array();
                    $statusStr = $k <= intval($approvearr['data']['approverstep']) ? ' · ' . $this->nodeStatus[$r['NodeStatus']] : '';
                    if (count($r['Items']['Item']) > 1) {
                        $r['NodeType'] = 2;
                        $tmparr['NodeType'] = $r['NodeType'];
                        $tmparr['NodeAttr'] = $this->nodeAttr[$r['NodeAttr']];
                        $tmparr['title'] = ($r['NodeType'] == 2 ? '多人' . ($r['NodeAttr'] == 1 ? '或签' : '会签') : '直接上级') . $statusStr;
                        $tmparr['avatar'] = 'https://fzrb.fznews.com.cn/assets/oa/images/approvaltag.png';
                        $items = [];
                        foreach ($r['Items']['Item'] as $key => $value) {
                            $tmpdate = $tmparr['date'] = '';
                            if (intval($value['ItemOpTime']) > 0) {
                                $tmpdate = $tmparr['date'] = date('m/d', $value['ItemOpTime']);
                            }
                            $items[] = ['avatar' => $value['ItemImage'], 'name' => $value['ItemName'] . ($value['ItemStatus'] > 1 ? ' · ' . $this->nodeStatus[$value['ItemStatus']] : ''),  'date' => $tmpdate, 'ItemSpeech' => ($value['ItemSpeech'] ? '审核意见：' . $value['ItemSpeech'] . '' : ''), 'userid' => $value['ItemUserId']];
                            $userlimit[] = $value['ItemUserId'];
                        }
                        $tmparr['items'] = $items;
                    } else {
                        $tmparr['title'] = $r['Items']['Item'][0]['ItemName'] . $statusStr;
                        $tmparr['date'] = $r['Items']['Item'][0]['ItemOpTime'] ? date('m/d', $r['Items']['Item'][0]['ItemOpTime']) : '';
                        $tmparr['avatar'] = $r['Items']['Item'][0]['ItemImage'];
                        $tmparr['userid'] = $r['Items']['Item'][0]['ItemUserId'];
                        $tmparr['speech'] = $r['Items']['Item'][0]['ItemSpeech'];
                        $tmparr['items'] = [];
                        $userlimit[] = $r['Items']['Item'][0]['ItemUserId'];
                    }
                    if (strpos($tmparr['speech'], '无须我审核') === false) {
                        $flowdata[] = $tmparr;
                    }
                }
            }

            $approveuids = array();
            $curnode = $approvearr['data']['ApprovalNodes']['ApprovalNode'][$approvedata['step']];
            if ($curnode['Items']['Item']) {
                foreach ($curnode['Items']['Item'] as $item) {
                    if ($item['ItemStatus'] == 1) {
                        $approveuids[] = $item['ItemUserId'];
                    }
                }
            }
            $canpass = false;
            // 当前步骤是否可以跳过
            if ($curnode['NodeSkip'] == 1) {
                $canpass = true;
            }
            $this->_result = array('data' => $data, 'info' => json_decode($data['data'], true), 'flowdata' => $flowdata, 'approvedata' => $approvedata, 'approveuids' => $approveuids, 'canpass' => $canpass, 'userid' => $this->_UserId);
        }
        Tools::responseJson($this->_result);
    }

    /**
     * 保存内容
     */
    public function actionSave()
    {
        if (isset($this->_request['wxuserid'])) {
            $thirdNo = Tools::getThirdNo();
            $request = $this->_request;
            $action = $request['action'];
            $request['thirdNo'] = $thirdNo;
            $request['creator'] = $this->_userInfo->name;
            $request['creator_userid'] = $this->_UserId;
            $annex = str_replace(['/', '-'], ['', ''], $request['formdate']) . $request['formtype'] . $request['typeid'];
            $row = (new \yii\db\Query())->select('*')->from("weixin_oa_approval_info")->where(['and', ['=', 'agentid', $this->_agentId], ['=', 'annex', $annex], ['=', 'status', [1, 2]]])->one();
            if (!$action) {
                if ($row) {
                    Tools::responseJson(['success' => true, 'errorMessage' => '表格已存在', 'errorCode' => 1000]);
                    exit;
                }
                // 生成流程数据
                $data = array(
                    'agentId' => $this->_agentId,
                    'userId' => $this->_UserId,
                    'userName' => $this->_userInfo->name,
                    'avatar' => $this->_userInfo->avatar,
                    'departmentid' => $this->_userInfo->departmentid,
                    'department' => $this->_userInfo->departmentname,
                    'thirdNo' => $thirdNo,
                    'annex' => $annex,
                    'data' => json_encode($request)
                );
                // 创建流程
                $flowdata = array('ApprovalNodes' => array('ApprovalNode' => []));
                // 审核人去重
                $approvers = [$this->_UserId];
                $temparr = ['general_director_userid', 'center_director_userid', 'director_userid'];
                for ($i = 0; $i < count($temparr); $i++) {
                    if ($request[$temparr[$i]]) { // 审核人不为空
                        // 去掉已经出现过的审核人，保留后面的
                        $userarr = array_values(array_diff(explode(',', $request[$temparr[$i]]), $approvers));
                        if (count($userarr) > 0) {
                            // 更新已出现的审核人
                            $approvers = array_merge($approvers, $userarr);
                            array_unshift($flowdata['ApprovalNodes']['ApprovalNode'], array(
                                'NodeStatus' => 1,
                                'Items' => array('Item' => $this->generateApprovers(implode(',', $userarr))),
                                'NodeAttr' => 2, //会签
                                'Position' => $i,
                                'NodeType' => 2,
                            ));
                        }
                    }
                }
                $flow = array(
                    'errcode' => 0,
                    'errmsg' => 'ok',
                    'data' => array(
                        'ThirdNo' => $thirdNo,
                        'OpenTemplateId' => $flowdata['OpenTemplateId'],
                        'OpenSpName' => $flowdata['OpenSpName'],
                        'OpenSpstatus' => 1,
                        'ApplyTime' => time(),
                        'ApplyUsername' => $this->_userInfo->name,
                        'ApplyUserParty' => '',
                        'ApplyUserImage' => $this->_userInfo->avatar,
                        'ApplyUserId' => $this->_UserId,
                        'ApprovalNodes' => $flowdata['ApprovalNodes'],
                        'NotifyNodes' => $flowdata['NotifyNodes'],
                        'approverstep' => 0
                    )
                );
                $applydata = array(
                    'agentid' => $this->_agentId,
                    'thirdNo' => $thirdNo,
                    'data' => json_encode($flow),
                    'step' => 0,
                    'status' => 1,
                    'notifyAttr' => $flowdata['NotifyAttr']
                );
                $approvalUserid = array();
                $approvalUsername = array();
                foreach ($flowdata['ApprovalNodes']['ApprovalNode'][0]['Items']['Item'] as $item) {
                    $approvalUserid[] = $item['ItemUserId'];
                    $approvalUsername[] = $item['ItemName'];
                }
                $data['approvalUserid'] = implode('|', $approvalUserid);
                $data['approvalUsername'] = implode('|', $approvalUsername);
                $data['status'] = 1;
                // 保存流程
                $transaction = Yii::$app->getDb()->beginTransaction();
                try {
                    $temparr = ['director', 'center_director', 'general_director'];
                    // 保存流程数据
                    $tempdata = new WeixinOaApprovalInfo($data);
                    $tempdata->save();
                    // 保存执行流
                    $tempapplydata = new WeixinOaApprovaldata($applydata);
                    $tempapplydata->save();
                } catch (\Throwable $th) {
                    $transaction->rollback();
                    Tools::responseJson(['success' => true, 'errorMessage' => '失败：' . $th->getMessage(), 'errorCode' => 1000]);
                }
                $transaction->commit();

                // 发送消息
                $touser1 = array($data['approvalUserid']);
                $touserstr = implode('|', $touser1);
                $textcard = $this->getTextCard('采编流程每日重点环节督查内容审核申请', $request, $thirdNo);
                try {
                    $sendResult = Tools::sendWxMessage($this->_agentId, $touserstr, $textcard, '', 'textcard');
                } catch (\Exception $e) {
                }
            }
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'thirdNo' => $thirdNo]);
    }

    /**
     * 同意审核内容
     */
    public function actionAgree()
    {
        $thirdNo = $this->_request['thirdNo'];
        if ($thirdNo) {
            $status = 2;
            $speech = $this->_request['speech'];
            $data = (new \yii\db\Query())->select('*')->from($this->infoTable)->where(['and', ['=', 'agentId', $this->_agentId], ['=', 'thirdNo', $thirdNo]])->one();
            $info = json_decode($data['data'], true);
            $this->_infoData = $info;
            $ret = $this->changeStatus($data['id'], $thirdNo, $status, $speech);
            if ($ret) {
                //发送消息
                if ($ret['isfinish']) {
                    $touserstr = $data['userId'];
                    $textcard = $this->getTextCard('您提交的采编流程每日重点环节督查内容表格申请已通过', $info, $thirdNo);
                    foreach ($this->_infoData['datas'] as $question) {
                        if ($question['disabled'] === false && $question['remark'] != '') {
                            $insertData = [
                                'typeid' => $this->_infoData['typeid'],
                                'typeid1' => $question['id'],
                                'problem' => $question['problem'] ? 1 : 0,
                                'remark' => $question['problem'] ? $question['remark'] : '',
                                'formdate' => $this->_infoData['formdate'],
                                'formtype' => $this->_infoData['formtype'],
                                'director_userid' => $this->_infoData['director_userid'],
                                'director_username' => $this->_infoData['director_username'],
                                'center_director_userid' => $this->_infoData['center_director_userid'],
                                'center_director_username' => $this->_infoData['center_director_username'],
                                'general_director_userid' => $this->_infoData['general_director_userid'],
                                'general_director_username' => $this->_infoData['general_director_username'],
                                'creator' => $this->_infoData['creator'],
                                'creator_userid' => $this->_infoData['creator_userid'],
                                'thirdNo' => $thirdNo,
                            ];
                            Yii::$app->db->createCommand()->insert('fzrbs_cblc_mrbw', $insertData)->execute();
                        }
                    }
                    try {
                        $sendResult = Tools::sendWxMessage($this->_agentId, $touserstr, $textcard, '', 'textcard');
                    } catch (\Exception $e) {
                    }
                } else if ($ret['touser']) {
                    $touserstr = $ret['touser'];
                    $textcard = $this->getTextCard($data['userName'] . '提交的采编流程每日重点环节督查内容表格申请需要您审核', $info, $thirdNo);
                    try {
                        $sendResult = Tools::sendWxMessage($this->_agentId, $touserstr, $textcard, '', 'textcard');
                    } catch (\Exception $e) {
                    }
                }
                Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $ret]);
            }
        }
        exit;
    }

    /**
     * 驳回审核内容
     */
    public function actionReject()
    {
        $thirdNo = $this->_request['thirdNo'];
        if ($thirdNo) {
            $speech = $this->_request['speech'];
            $data = (new \yii\db\Query())->select('*')->from($this->infoTable)->where(['and', ['=', 'agentId', $this->_agentId], ['=', 'thirdNo', $thirdNo]])->one();
            $info = json_decode($data['data'], true);
            $ret = $this->changeStatus($data['id'], $thirdNo, 3, $speech);
            if ($ret) {
                //发送消息
                $touserstr = $data['userId'];
                $textcard = $this->getTextCard($this->_userInfo->name . '驳回了您提交的采编流程每日重点环节督查内容表格申请', $info, $thirdNo);
                try {
                    $sendResult = Tools::sendWxMessage($this->_agentId, $touserstr, $textcard, '', 'textcard');
                } catch (\Exception $e) {
                }
                $toUser = array();
                $log = (new \yii\db\Query())->select('userId')->from($this->approvalLogTable)->where(['and', ['=', 'agentid', $this->_agentId], ['=', 'thirdNo', $thirdNo]])->all();
                foreach ($log as $r) {
                    if (!in_array($r['userId'], $toUser)) {
                        if ($r['userId'] != $this->_UserId) $toUser[] = $r['userId'];
                    }
                }
                if ($toUser) {
                    $touserstr = implode('|', $toUser);
                    $textcard = $this->getTextCard($this->_userInfo->name . '驳回了' . $data['userName'] . '提交的采编流程每日重点环节督查内容表格申请', $info, $thirdNo);
                    try {
                        $sendResult = Tools::sendWxMessage($this->_agentId, $touserstr, $textcard, '', 'textcard');
                    } catch (\Exception $e) {
                    }
                }
                Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $ret]);
            }
        }
        exit;
    }

    /**
     * 撤消审核内容
     */
    public function actionCancel()
    {
        $thirdNo = $this->_request['thirdNo'];
        if ($thirdNo) {
            $data = (new \yii\db\Query())->select('*')->from($this->infoTable)->where(['and', ['=', 'agentId', $this->_agentId], ['=', 'thirdNo', $thirdNo]])->one();
            $ret = $this->changeStatus($data['id'], $thirdNo, 4, '');
            if ($ret) {
                //发送消息给已审过的用户
                $toUser = array();
                $toUser[] = $data['approvalUserid'];
                $log = (new \yii\db\Query())->select('userId')->from($this->approvalLogTable)->where(['and', ['=', 'agentid', $this->_agentId], ['=', 'thirdNo', $thirdNo]])->all();
                foreach ($log as $r) {
                    if (!in_array($r['userId'], $toUser)) {
                        $toUser[] = $r['userId'];
                    }
                }
                $info = json_decode($data['data'], true);
                if ($toUser) {
                    $touserstr = implode('|', $toUser);
                    $textcard = $this->getTextCard('撤消采编流程每日重点环节督查内容表格申请', $info, $thirdNo);
                    try {
                        $sendResult = Tools::sendWxMessage($this->_agentId, $touserstr, $textcard, '', 'textcard');
                    } catch (\Exception $e) {
                    }
                }
                // 发送一条纪录给申请人，申请人点击修改后，重新生成执行
                $info['method'] = 'edit';
                $touserstr = $data['userId'];
                $textcard = $this->getTextCard('您撤消了采编流程每日重点环节督查内容表格申请，点击可重新申请', $info, $thirdNo);
                try {
                    $sendResult = Tools::sendWxMessage($this->_agentId, $touserstr, $textcard, '', 'textcard');
                } catch (\Exception $e) {
                }
                Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $ret]);
            }
        }
        exit;
    }

    /**
     * 催办审核内容
     */
    public function actionUrge()
    {
        $thirdNo = $this->_request['thirdNo'];
        if ($thirdNo) {
            $data = (new \yii\db\Query())->select('*')->from($this->infoTable)->where(['and', ['=', 'agentId', $this->_agentId], ['=', 'thirdNo', $thirdNo], ['=', 'userId', $this->_UserId]])->one();
            $info = json_decode($data['data'], true);
            if ($data) {
                $touserstr = $data['approvalUserid'];
                $textcard = $this->getTextCard('您有流程申请要审核!', $info, $thirdNo);
                try {
                    $sendResult = Tools::sendWxMessage($this->_agentId, $touserstr, $textcard, '', 'textcard');
                } catch (\Exception $e) {
                }
                Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $sendResult]);
            }
        }
        exit;
    }


    /**
     * 配置信息
     */
    public function actionConfig()
    {
        $data = [
            'questions' => $this->_questions,
            'departments' => $this->_departments,
            'directorUsers' => [],
        ];
        foreach ([1, 2, 3] as $row) {
            foreach ($data['departments'] as $row1) {
                $data['directorUsers'][$row][$row1['value']] = [['text' => '值班人员', 'children' => $this->_director_users[$row1['value']][$row]]];
            }
        }
        $xmtbmids = Tools::getDepartmentChildren(6);
        $rbbmids = Tools::getDepartmentChildren(3);
        $wbbmids = Tools::getDepartmentChildren(5);
        if (in_array($this->_userInfo->departmentid, $xmtbmids['allDepartIds'])) {
            $data['userdepartmentid'] = 10;
        } else if (in_array($this->_userInfo->departmentid, $rbbmids['allDepartIds'])) {
            $data['userdepartmentid'] = 11;
        } else if (in_array($this->_userInfo->departmentid, $wbbmids['allDepartIds'])) {
            $data['userdepartmentid'] = 12;
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $data]);
    }

    private function generateApprovers($userid)
    {
        if (!$userid) {
            Tools::responseJson(['success' => true, 'errorMessage' => 'userid不能为空', 'errorCode' => 1000]);
        }
        $users = WeixinOAUserInfo::find()->where(['and', ['in', 'userid', explode(',', $userid)]])->asArray()->all();
        return array_map(function ($u) {
            return array(
                'ItemName' => $u['name'],
                'ItemImage' => $u['avatar'],
                'ItemUserId' => $u['userid'],
                'ItemStatus' => 1,
                'ItemOpTime' => 0
            );
        }, $users);
    }

    private function getTextCard($title, $data, $thirdNo)
    {
        $method = 'view';
        if (isset($data['method'])) $method = $data['method'];
        return array(
            'title' => $title,
            'description' => '<div class="normal">部门：' . $this->_department_name[$data['typeid']] . '</div><div class="normal">日期：' . $data['formdate'] . '</div><div class="normal">班次：' . ($data['formtype'] == 1 ? '白班' : '夜班') . '</div><div class="normal">申请人：' . $data['creator'] . '</div>',
            'url' => 'https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/mrbw/edit?method=' . $method . '&thirdNo=' . $thirdNo,
            'btntxt' => '详情'
        );
    }

    private function changeStatus($id, $thirdNo, $status, $speech = '')
    {
        $wfp = new WorkflowParse;
        $transaction = Yii::$app->getDb()->beginTransaction();
        $ret = 0;
        try {
            $ret = $wfp->flowChange($thirdNo, $this->_UserId, $status, $this->agentId, $speech);
            if ($ret) {
                if ($ret['nextdata'] && $ret['nextdata']['approvalUserid']) {
                    if (!$ret['isfinish']) {
                        $status = 1;
                    }
                    Yii::$app->db->createCommand()->update($this->infoTable, ['status' => $status, 'approvalUserid' => $ret['nextdata']['approvalUserid'], 'approvalUsername' => $ret['nextdata']['approvalUsername']], 'id=:id', [':id' => $id])->execute();
                } else {
                    Yii::$app->db->createCommand()->update($this->infoTable, ['status' => $status], 'id=:id', [':id' => $id])->execute();
                }
            }
        } catch (\Throwable $th) {
            $transaction->rollback();
            Tools::responseJson(['success' => true, 'errorMessage' => $th->getMessage(), 'errorCode' => 1000]);
        }
        $transaction->commit();
        return $ret;
    }
}
