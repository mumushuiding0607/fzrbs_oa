<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;

/**
 * 工资条相关接口类
 */
class SalaryController extends ApiBase
{
    public $enableCsrfValidation = false;
    protected $_orderBy = 'id desc';
    protected $_saralyColumn = [
        'col_a' => '姓名',
        'col_af' => '基本工资',
        'col_ag' => '绩效工资',
        'col_b' => '分级底薪',
        'col_c' => '社龄补贴',
        // 2021-01-07添加，by guo
        'col_ca' => '社龄工资',
        'col_d' => '岗位工资',
        'col_e' => '薪级工资',
        'col_f' => '岗位津贴',
        'col_g' => '生活补贴',
        'col_al' => '提租补贴',
        'col_h' => '其它',
        'col_ao' => '本月工资',
        'col_ap' => '扣款',
        'col_i' => '合计',
        'col_j' => '稿分',
        'col_k' => '新媒稿分',
        'col_l' => '稿分绩效',
        'col_n' => '计量绩效',
        'col_o' => '夜班/加班',
        //  2021-01-07添加，by guo
        'col_oa' => '夜班绩效加班绩效',
        'col_p' => '内容质量绩效',
        'col_q' => '其他',
        'col_r' => '应发工资',
        // 原始 协助经营绩效 改 其他加计扣税
        'col_m' => '其他加计扣税',
        'col_am' => '工资总额',
        'col_ai' => '养老保险',
        // 'col_t'=>'退休养老金',
        'col_u' => '职业年金',
        'col_v' => '医保',
        'col_w' => '社保扣款',
        'col_s' => '住房公积金',
        'col_an' => '养公金',
        'col_aj' => '补扣社保',
        'col_ah' => '补扣医保',
        'col_aq' => '补扣年金',
        'col_aw' => '补扣公积金',
        'col_av' => '补扣失业',
        'col_x' => '失业保险',
        'col_y' => '个调税',
        'col_z' => '预发档案工资',
        'col_za' => '已发档案工资',
        'col_aa' => '扣差错',
        'col_ab' => '工会费',
        // 2022-01-25 由 扣其他 改为 其他项
        'col_ac' => '其他项',
        'col_ad' => '扣款合计',
        'col_ar' => '实发档案',
        // 2022-01-25  add by lin
        'col_au' => '融合奖优绩效',
        'col_as' => '应发绩效',
        'col_at' => '实发绩效',
        'col_at' => '实发绩效',
        'col_ba' => '基础绩效奖',
        'col_bc' => '活动绩效',
        'mobile' => '手机号',
    ];

    public function init()
    {
        parent::init();
        if (!$this->_UserId) {
            Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
        }
    }

    /**
     * 每月工资条
     */
    public function actionMonthSalary()
    {
        // $this->_UserId = 'chenhaiping';
        $thismonth = date('Y-m');
        $where = [
            'and',
            ['=', 'userid', $this->_UserId],
            ['=', 'st', 1],
            ['=', 'sign_st', 1],
        ];
        if (isset($this->_request['month'])) {
            $month = $this->_request['month'];
        } else {
            $month = $thismonth;
        }
        if ($month) {
            $where[] = ['=', 'pay_time', strtotime($month)];
        }
        $row = (new \yii\db\Query())->select('*')->from('weixin_salary')->where($where)->one();
        $userInfo = $this->_userInfo;
        if ($userInfo['departmentid'] != $row['dep_id']) {
            $userInfo['departmentid'] = $row['dep_id'];
        }
        // 社直 社领导 社总编室
        $depIds = Tools::getDepartmentChildren('2,31,65');
        // 众创孵化中心
        $combineIds = Tools::getDepartmentChildren(59);
        $combineIds = $combineIds['allDepartIds'];
        // 社直 社领导
        $societyDepChild = $depIds['allDepartIds'];

        // 众创孵化中心 跟着新媒体中心显示，2023年以前的数据保留
        if (in_array($userInfo['departmentid'], $societyDepChild) && (!in_array($userInfo['departmentid'], $combineIds) && strtotime($month) > strtotime('2022-12'))) {
            $this->_saralyColumn['socialTotal'] = '实发合计';
            $this->_saralyColumn['socialShould'] = '应发合计';
            $this->_saralyColumn['col_i'] = '应发档案';
            $classifyTable = [1 => '档案工资', '扣款工资', '绩效工资', '实发工资', '工资合计'];
            // 档案工资
            $classifyData[1] = ['col_d', 'col_e', 'col_f', 'col_g', 'col_al', 'col_h', 'col_i'];
            // 扣款工资 col_z 扣预发档案工资 实发档案工资
            $classifyData[2] = ['col_s', 'col_ai', 'col_u', 'col_v', 'col_x', 'col_ab', 'col_ac', 'col_aj', 'col_aq', 'col_aw', 'col_ah', 'col_av', 'col_ad', 'col_ar'];
            // 绩效工资
            $classifyData[3] = ['col_ag', 'col_q', 'col_as', 'col_y', 'col_at'];
            // 绩效工资
            $classifyData[4] = ['socialShould', 'socialTotal'];
            $row['socialTotal'] = floatval($row['col_ar']) + floatval($row['col_at']);
            $row['socialShould'] = floatval($row['col_i']) + floatval($row['col_as']);
        }

        $classifyData = ['基本工资' => [], '应发工资' => [], '计量绩效' => [], '应发绩效' => [], '档案工资' => [], '合计' => [], '扣款项目' => []];
        // 日报 社视觉设计中心
        $dayDepIds = Tools::getDepartmentChildren('3,95');
        $dayDepIds = $dayDepIds['allDepartIds'];
        foreach ($this->_saralyColumn as $key => $value) {
            if (in_array($key, ['col_b', 'col_c', 'col_ca']) && $row && floatval($row[$key]) != 0) {
                // 基本工资
                $classifyData['基本工资'][$key] = $value;
            } else if ((in_array($key, ['col_af', 'col_ao', 'col_ap', 'col_bc']) || ($key == 'col_i' && !floatval($row['col_am'])) || ($key == 'col_ag' && !floatval($row['col_am']))) && $row && floatval($row[$key]) != 0) {
                // 应发工资
                $classifyData['应发工资'][$key] = $value;
            } else if ((in_array($key, ['col_j', 'col_k', 'col_l', 'col_n', 'col_o', 'col_oa', 'col_aa']) || ($key == 'col_aa' && in_array($row['dep_id'], $dayDepIds))) && $row && floatval($row[$key]) != 0) {
                // 计量绩效
                $classifyData['计量绩效'][$key] = $value;
            } else if (in_array($key, ['col_ba', 'col_ag']) && $row && floatval($row[$key]) != 0) {
                // 应发绩效
                $classifyData['应发绩效'][$key] = $value;
            } else if ((in_array($key, ['col_d', 'col_e', 'col_f', 'col_g', 'col_al', 'col_h']) || ($key == 'col_i' && floatval($row['col_am']))) && $row && floatval($row[$key]) != 0) {
                // 档案工资
                $classifyData['档案工资'][$key] = $value;
            } else if (in_array($key, [
                'col_s', 'col_t', 'col_u', 'col_v', 'col_w', 'col_x', 'col_y',
                'col_z', 'col_ab', 'col_aj', 'col_ak', 'col_ac', 'col_ad', 'col_ai', 'col_ah', 'col_an', 'col_aq'
            ]) && $row && floatval($row[$key]) != 0) {
                // 扣款项目
                $classifyData['扣款项目'][$key] = $value;
            } else if ((in_array($key, ['col_p', 'col_r', 'col_q', 'col_am', 'col_m', 'col_za', 'col_au']) || ($key == 'col_ag' && floatval($row['col_am']))) && $row && floatval($row[$key]) != 0) {
                $classifyData['合计'][$key] = $value;
            }
        }
        foreach ($classifyData as $k => $v) {
            if (count($v) == 0) {
                unset($classifyData[$k]);
            }
        }

        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'userInfo' => $userInfo, 'month' => $month, 'salary' => $row, 'classifyData' => $classifyData]);
    }
}
