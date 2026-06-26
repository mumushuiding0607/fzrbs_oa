<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;

/**
 * 工作日志相关接口类
 */
class DailyWorkingLogController extends ApiBase
{
    public $enableCsrfValidation = false;
    protected $_orderBy = 'id desc';

    public function init()
    {
        parent::init();
        if (!$this->_UserId) {
            Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
        }
    }

    /**
     * 今日工作日志
     */
    public function actionToday()
    {
        if ($this->_UserId) {
            $today = date("Y-m-d");
            $result = ['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'today' => $today];
            $where = [
                'and',
                ['=', 'log_date', $today],
                ['=', 'userid', $this->_UserId],
            ];
            $row = (new \yii\db\Query())->select('*')->from('weixin_daily_working_log')->where($where)->one();
            if ($row) {
                $row['log_content'] = str_replace("<br>", "\n", $row['log_content']);
                $result['data'] = $row;
            }
            Tools::responseJson($result);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
        }
    }

    /**
     * 保存工作日志
     */
    public function actionLogSave()
    {
        if ($this->_UserId) {
            $today = date("Y-m-d");
            $result = ['success' => true, 'errorMessage' => '', 'errorCode' => 0];
            if (isset($this->_request['value'])) {
                $data = $this->_request['value'];
                if ($data['log_date'] != $today) {
                    $data['log_date'] = $today;
                }
                $data['log_content'] = str_replace(["\r\n", "\n"], "<br>", $data['log_content']);
                $where = [
                    'and',
                    ['=', 'log_date', $today],
                    ['=', 'userid', $this->_UserId],
                ];

                $row = (new \yii\db\Query())->select('*')->from('weixin_daily_working_log')->where($where)->one();
                if ($row) {
                    Yii::$app->db->createCommand()->update('weixin_daily_working_log', $data, 'id=:id', [':id' => $row['id']])->execute();
                } else {
                    $data['created'] = date("Y-m-d H:i:s");
                    $data['userid'] = $this->_UserId;
                    $data['dep_id'] =  $this->_userInfo['departmentid'];
                    $data['username'] = $this->_userInfo['name'];
                    Yii::$app->db->createCommand()->insert('weixin_daily_working_log', $data)->execute();
                }
            } else {
                $result['errorMessage'] = '参数错误';
                $result['errorCode'] = 1000;
            }
            Tools::responseJson($result);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
        }
    }

    /**
     * 我的工作日志
     */
    public function actionMyLog()
    {
        $userId = $this->_UserId;
        if ($this->_request['showUserId']) {
            $userId = $this->_request['showUserId'];
        }
        $where = [
            'and',
            ['=', 'userid', $userId],
        ];
        $thismonth = date('Y-m');
        if (!isset($this->_request['flag'])) {
            if (isset($this->_request['month'])) {
                $month = $this->_request['month'];
            } else {
                $month = $thismonth;
            }
            if ($month) {
                $where[] = ['like', 'log_date', $month];
            }
        } else {
            $flag = intval($this->_request['flag']);
            $where1 = ['and', ['>', 'id', 0]];
            if ($flag == 3) {
                $where1[] = ['in', 'tp', [1, 2]];
            } else {
                $where1[] = ['=', 'tp', $flag];
            }
            $subQuery = (new \yii\db\Query())->select('l_id')->from('weixin_daily_working_comment')->where($where1);
            $where[] = ['id' => $subQuery];
        }
        $keyword = trim($this->_request['keyword']);
        if ($keyword) {
            $where[] = ['like', 'log_content', $keyword];
        }
        $data = [];
        $res = (new \yii\db\Query())->select('id,log_date,log_content')->from('weixin_daily_working_log')->where($where)->orderBy('log_date desc')->all();
        foreach ($res as $row) {
            $comment =  (new \yii\db\Query())->select('content,tp')->from('weixin_daily_working_comment')->where(['=', 'l_id', $row['id']])->orderBy('id desc')->all();
            $comment = array_combine(array_column($comment, 'tp'), array_column($comment, 'content'));
            if ($comment) {
                $row['comment'] = $comment[1];
                $row['reply'] = $comment[2];
            }
            $data[] = $row;
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $data, 'thismonth' => $thismonth]);
    }

    /**
     * 工作日志点评回复
     */
    public function actionComment()
    {
        $content = trim($this->_request['content']);
        $tp = intval($this->_request['tp']);
        $l_id = intval($this->_request['l_id']);
        if ($content && $tp && $l_id) {
            $data['content'] = str_replace(["\r\n", "\n"], "<br>", $content);
            $data['tp'] = $tp;
            $data['l_id'] = $l_id;
            $data['userid'] = $this->_UserId;
            $data['username'] = $this->_userInfo['name'];
            $data['created'] = date("Y-m-d H:i:s");
            $flag = Yii::$app->db->createCommand()->insert('weixin_daily_working_comment', $data)->execute();
            if ($flag) {
                $row = (new \yii\db\Query())->select('log_date,userid')->from('weixin_daily_working_log')->where(['=', 'id', $l_id])->one();
                if ($data['tp'] == 1) {
                    // 点评
                    $userid = $row['userid'];
                    $text = $this->_userInfo['name'] . "点评了你 " . $row['log_date'] . " 的工作日志哦,请及时查看。";
                } else {
                    //回复
                    $row1 = (new \yii\db\Query())->select('userid')->from('weixin_daily_working_comment')->where(['and', ['=', 'id', $l_id], ['=', 'tp', 1]])->one();
                    $userid = $row1['userid'];
                    $text = $this->_userInfo['name'] . "回复了您对他 " . $row['log_date'] . " 工作日志的点评。";
                }
                try {
                    Tools::sendWxMessage(1000055, $userid, $text);
                } catch (\Exception $e) {
                }
                Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0]);
            } else {
                Tools::responseJson(['success' => true, 'errorMessage' => '操作失败', 'errorCode' => 1000]);
            }
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
    }

    /**
     * 工作日志点评回复内容
     */
    public function actionCommentInfo()
    {
        $tp = intval($this->_request['tp']);
        $l_id = intval($this->_request['l_id']);
        if ($tp && $l_id) {
            $row = (new \yii\db\Query())->select('content')->from('weixin_daily_working_comment')->where(['and', ['=', 'tp', $tp], ['=', 'l_id', $l_id]])->one();
            if ($row) {
                $this->_result['content'] = str_replace('<br>', "\r\n", $row['content']);
            }
            Tools::responseJson($this->_result);
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
    }

    /**
     * 工作日志下载
     */
    public function actionDownload()
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
            'log_date' => '日期',
            'log_content' => '日志内容',
        );
        $i = 0;
        foreach ($columns as $value1) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $i) . '1', $value1);
            $i++;
        }
        $where = [
            'and',
            ['=', 'userid', $this->_UserId],
        ];
        $month = $this->_request['month'];
        $where[] = ['like', 'log_date', $month];
        $res = (new \yii\db\Query())->select('id,log_date,log_content')->from('weixin_daily_working_log')->where($where)->orderBy('log_date desc')->all();
        $i = 0;
        foreach ($res as $row) {
            $j = 0;
            foreach ($columns as $key1 => $value1) {
                $value = $row["$key1"];
                if ($key1 == 'log_content') {
                    $value = str_replace('<br>', "\r\n", $value);
                    $objPHPExcel->getActiveSheet()->getStyle(chr(65 + $j) . ($i + 2))->getAlignment()->setWrapText(true);
                }
                $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit(chr(65 + $j) . ($i + 2), $value);
                $j++;
            }
            $i++;
        }
        $objPHPExcel->getActiveSheet()->setTitle('工作日志');
        $objPHPExcel->setActiveSheetIndex(0);
        header('Expires: ' . date(DATE_RFC1123));
        header('Cache-Control: no-store, no-cache, must-revalidate,' . '  pre-check=0, post-check=0, max-age=0');
        header('Last-Modified: ' . date(DATE_RFC1123));
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="工作日志.xls"');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * 工作日志配置数据
     */
    public function actionConfig()
    {
        $leader = $userDepartment = [];
        $res = (new \yii\db\Query())->select('*')->from('weixin_module_power')->where(['and', ['=', 'module_type', 1], ['>', "INSTR(CONCAT(',',user_ids,','),'," . $this->_UserId . ",')", 0]])->all();
        if ($res) {
            $dep_ids = array_column($res, 'dep_ids');
            foreach ($dep_ids as $key => $val) {
                $p = explode(",", $val);
                foreach ($p as $v) {
                    if (!in_array($v, $userDepartment)) {
                        $userDepartment[] = $v;
                    }
                }
            }
        }
        $res = (new \yii\db\Query())->select('*')->from('weixin_module_power')->where(['and', ['=', 'module_type', 1]])->all();
        if ($res) {
            $user_ids = array_column($res, 'user_ids');
            foreach ($user_ids as $key => $val) {
                $p = explode(",", $val);
                foreach ($p as $v) {
                    $leader[] = $v;
                }
            }
            $leader = array_values(array_unique($leader));
        }
        $this->_result['data'] = [
            'leader' => $leader,
            'userDepartment' => $userDepartment,
        ];
        if ($this->_request['showUserId']) {
            $userInfo = $this->_getUserInfo($this->_request['showUserId']);
            $this->_result['data']['departmentid'] = $userInfo['departmentid'];
            $this->_result['data']['name'] = $userInfo['name'];
        }
        Tools::responseJson($this->_result);
    }
}
