<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WxQyhJk;
use Yii;

/**
 * 群众评议接口类
 */
class VoteController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinStaff';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }

    /**
     * 重写index的业务实现
     */
    public function actionIndex()
    {
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        $where = ['and', ['>', 'state', -1]];
        if ($this->_adminInfo['usertype'] == 0) {
            $where[] = ['=', 'editor', $this->_adminInfo['wxuserid']];
        }
        if ($this->_request['title']) {
            $where[] = ['like', 'title', $this->_request['title']];
        }

        $res = (new \yii\db\Query())->select('id,title,starttime,endtime,editor,state,inserttime')->from('weixin_qzpy_vote_item')->where($where)->limit($limit)->offset($offset)->orderBy('id desc')->all();
        $total = (new \yii\db\Query())->select(['id'])->from('weixin_qzpy_vote_item')->where($where)->count();
        // 创建者  S 
        $arrUserName = $this->getUserInfo();
        if ($res) {
            foreach ($res as $k => $val) {
                $editorName = $arrUserName[$val['editor']];
                $res[$k]['editor'] = $editorName;
            }
        }
        // 创建者  E 
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total > 0 ? $total : 0;
        $this->_result['data'] = $res;
        return $this->_result;
    }

    /**
     * 新增/修改保存动作
     */
    public function actionSave()
    {
        $id = intval($this->_request['id']);
        $title = trim($this->_request['title']);
        $participant = trim($this->_request['participant']);
        $inviter = trim($this->_request['inviter']);
        $starttime = trim($this->_request['starttime']);
        $endtime = trim($this->_request['endtime']);
        $remark = trim($this->_request['remark']);

        if ($participant && $inviter) {
            $participantExp = explode(',', $participant);
            $inviterExp = explode(',', $inviter);
            $arrIntersect = array_intersect($participantExp, $inviterExp);
            if (count($arrIntersect) > 0) {
                $arrUserName = $this->getUserInfo();
                foreach ($arrIntersect as $v) {
                    $arrName[] = $arrUserName[$v];
                }
                $this->_result['errorMessage'] = '参与评议人员、邀请参与评议人员不能同时存在：'.implode(',', $arrName);
            }
            return $this->_result;
        }
        
        $data = [
            'title' => $title,
            'starttime' => $starttime,
            'endtime' => $endtime,
            'remark' => $remark,
            'participant' => $participant,
            'inviter' => $inviter
        ];
        $operatID = 0;
        try {
            if ($id) {
                Yii::$app->db->createCommand()->update('weixin_qzpy_vote_item', $data, 'id=:id', [':id' => $id])->execute();
                $operatID = $id;
            } else {
                $data['inserttime'] = date('Y-m-d H:i:s');
                $data['editor'] = $this->_adminInfo['wxuserid'];
                $res = Yii::$app->db->createCommand()->insert('weixin_qzpy_vote_item', $data)->execute();
                $isert_id  = Yii::$app->db->getLastInsertID();
                $operatID = $isert_id;
            }
            $subListsSave = $this->_request['sublists'];
            $pre_userid = [];
            foreach ($subListsSave as $v) {
                $users = $v['users'];
                if ($pre_userid) {
                    foreach ($pre_userid as $v1) {
                        $users = array_diff($users, $v1);
                    }
                }
                $data = [
                    'iid' => $operatID,
                    'stitle' => $v['stitle'],
                    'snum' => $v['snum'],
                    'userid' => implode(',', $users),
                ];
                if ($v['sid']) {
                    Yii::$app->db->createCommand()->update('weixin_qzpy_vote_item_sub', $data, 'sid=:sid', [':sid' => $v['sid']])->execute();
                } else {
                    Yii::$app->db->createCommand()->insert('weixin_qzpy_vote_item_sub', $data)->execute();
                }
                $pre_userid[] = $users;
            }
            $action = $isert_id ? '添加' : '修改';
            $remark = $action . "评议项目。名称：" . $title . '，id为：' . ($isert_id ? $isert_id : $id);
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        } catch (\Exception $e) {
            $this->_result['errorMessage'] = '操作失败';
        }
        return $this->_result;
    }

    /**
     * delete 删除子项
     */
    public function actionDelSub()
    {
        $sid = $this->_request['id'];
        $res = Yii::$app->db->createCommand()->delete('weixin_qzpy_vote_item_sub', 'sid=:sid', [':sid' => $sid])->execute();

        if ($res) {
            $action = '删除';
            $remark = $action . "评议项目。sid：" . $sid;
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        } else {
            $this->_result['errorMessage'] = '操作失败'.$sid;
        }
        return $this->_result;
    }

    /**
     * 重写delete的业务实现动作
     */
    public function actionDelete()
    {
        $id = $this->_request['id'];
        if ($id) {
            $res = Yii::$app->db->createCommand()->update('weixin_qzpy_vote_item', ['state' => -1], 'id in (:id)', [':id' => $id])->execute();
            if ($res) {
                $action = '删除';
                $remark = $action . "评议项目。id：" . $id;
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            } else {
                $this->_result['errorMessage'] = '操作失败';
            }
            return $this->_result;
        }
    }

    /**
     * 重写view的业务实现
     */
    public function actionView()
    {
        $id = intval($this->_request['id']);
        $preview = intval($this->_request['preview']);
        if ($id) {
            if (!$preview) {
                $sublists = [];
                $row = (new \yii\db\Query())->select('remark,participant,inviter')->from('weixin_qzpy_vote_item')->where(['=', 'id', $id])->one();
                if ($row) {
                    $res = (new \yii\db\Query())->select('*')->from('weixin_qzpy_vote_item_sub')->where(['=', 'iid', $id])->all();
                    foreach ($res as $v) {
                        $item = $v;
                        $item['users'] = explode(',', $v['userid']);
                        unset($item['userid']);
                        $sublists[] =  $item;
                    }
                }
                $this->_result['data'] = $row;
                $this->_result['sublists'] = $sublists;
            } else {
                $query = new \yii\db\Query();
                $row = $query->select(['title', 'participant', 'inviter'])->from('weixin_qzpy_vote_item')->where(['=', 'id', $id])->one();
                if ($row) {
                    $this->_result['title'] = $row['title'];
                    $this->_result['id'] = $id;
                    $arr_user = $this->getUserInfo();
                    $resSub = $query->select(['sid', 'stitle', 'userid', 'snum'])->from('weixin_qzpy_vote_item_sub')->where(['=', 'iid', $id])->orderBy('sid asc')->all();
                    if ($resSub) {
                        $pre_userid = [];
                        foreach ($resSub as $k => $val) {
                            $explode_val_userid = explode(',', $val['userid']);
                            if ($pre_userid) {
                                foreach ($pre_userid as $v) {
                                    $explode_val_userid = array_diff($explode_val_userid, $v);
                                }
                            }
                            $result_userinfo = [];
                            foreach ($explode_val_userid as $v) {
                                $result_userinfo[] = ['name' => $arr_user[$v], 'userid' => $v, 'l3' => '', 'l2' => '', 'l1' => '', 'l0' => ''];
                            }
                            $subInfo[$k]['stitle'] = $val['stitle'];
                            $subInfo[$k]['snum'] = $val['snum'];
                            $subInfo[$k]['sid'] = $val['sid'];
                            $subInfo[$k]['info'] = $result_userinfo;
                            $pre_userid[] = $explode_val_userid;
                        }
                        $this->_result['data'] = $subInfo;
                    }
                }
            }
        }
        return $this->_result;
    }

    /**
     *人员排序动作
     */
    public function actionSort()
    {
        $sid = $this->_request['sid'];
        $userid = $this->_request['userid'];
        if (is_array($sid) && is_array($userid) && $sid && $userid) {
            foreach ($sid as $index => $id) {
                Yii::$app->db->createCommand()->update('weixin_qzpy_vote_item_sub', ['userid' => implode(',', $userid[$index])], 'sid=:sid', [':sid' => $id])->execute();
            }
            $action = '拖动排序';
            $remark = $action . "评议项目。项目id：" . implode(',', $sid);
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        }
        return $this->_result;
    }

    /**
     * 发布撤销动作
     */
    public function actionPush()
    {
        $id = intval($this->_request['id']);
        $state = intval($this->_request['state']);
        if ($id) {
            $res = Yii::$app->db->createCommand()->update('weixin_qzpy_vote_item', ['state' => $state], 'id in (:id)', [':id' => $id])->execute();
            if ($res) {
                // 人员发布通知
                $this->sendMessage($state, $id);

                $action = $state ? '发布' : '撤销';
                $remark = $action . "评议项目。id：" . $id;
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            } else {
                $errorMessage = $state ? '发布失败' : '撤销失败';
                $this->_result['errorMessage'] = $errorMessage;
            }
        }
        return $this->_result;
    }

    /**
     * 发送微信企业应用消息通知
     * @param 
     */
    protected function sendMessage($state, $id)
    {
        $res = (new \yii\db\Query())->select('id,title,participant,inviter')->from('weixin_qzpy_vote_item')->where(['=', 'id', $id])->one();

        if ($res) {
            $participantArr = [];
            $inviterArr = [];

            if ($res['participant']) {
                $participantArr = explode(',', $res['participant']);
            }

            if ($res['inviter']) {
                $inviterArr = explode(',', $res['inviter']);
            }

            $allName = array_merge($participantArr, $inviterArr);

            $appId = '1000072';
            $userid = implode('|', $allName);
            $content = $state ? '你有一条新发布的群众评议投票任务：' . $res['title'] . '；前往<a href="https://fzrb.fznews.com.cn/index.php?r=qiyehao/qyqzpytp/edit&id=' . $res['id'] . '">查看详情</a>。' : '已撤销群众评议投票任务：' . $res['title'];

            WxQyhJk::sendMessage($appId, $userid, $content);
        }
    }

    /**
     * 统计动作
     */
    public function actionCount()
    {
        $id = $this->_request['id'];
        $getUserType = $this->_request['sid'];
        if ($id) {
            $rows = $this->getUserVoteData($id, $getUserType);
            $this->_result['data'] = $rows;
        }
        return $this->_result;
    }

    /**
     * 下载动作
     */
    public function actionDownloadExcel()
    {
        $id = intval($this->_request['selValues']);
        if ($id) {
            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
                ->setLastModifiedBy("Maarten Balliauw")
                ->setTitle("Office 2007 XLSX Test Document")
                ->setSubject("Office 2007 XLSX Test Document")
                ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                ->setKeywords("office 2007 openxml php")
                ->setCategory("Test result file");
            $columns = [
                'no' => '序号',
                'name' => '姓名',
                'type1' => '优秀',
                'type2' => '合格',
                'type3' => '基本合格',
                'type4' => '不合格',
            ];
          
            $i = 0;
            foreach ($columns as $key1 => $value1) {
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $i) . '2', $value1);
                $i++;
            }
            // 设置每列宽度
            $ABCDE = ['A', 'B', 'C', 'D', 'E', 'F'];
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(6);
            for ($i = 1; $i < 6; $i++) {
                $objPHPExcel->getActiveSheet()->getColumnDimension($ABCDE[$i])->setWidth(20);
            }
            // 保护cell
            $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
            $objPHPExcelSetSize = 14; // 字体大小

            $rowsArr = $this->getUserVoteData($id);

            $sheetIndex = 0;
            if ($rowsArr) {
                // 第一行标题
                $title = Yii::$app->db->createCommand("select title from weixin_qzpy_vote_item where id=" . $id)->queryOne();
                foreach ($rowsArr as $rows_k => $rows) {
                    if ($rows_k > 0) {
                        $objPHPExcel->createSheet();

                        $i=0;
                        foreach($columns as $key1=>$value1){
                            $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValue(chr(65+$i).'2', $value1);
                            $i++;
                        }

                        // 设置每列宽度
                        $ABCDE = ['A', 'B', 'C', 'D', 'E', 'F'];
                        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(6);
                        for ($i = 1; $i < 6; $i++) {
                            $objPHPExcel->getActiveSheet()->getColumnDimension($ABCDE[$i])->setWidth(20);
                        }
                        // 保护cell
                        $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
                        $objPHPExcelSetSize = 14; // 字体大小

                        $objPHPExcel->setactivesheetindex($sheetIndex);
                    }

                    // 第一行标题
                    $excelTitle = $title['title'];
                    $objPHPExcel->getActiveSheet()->mergeCells('A1:F1'); // 合并单元格
                    $objPHPExcel->getActiveSheet()->setCellValue('A1', $excelTitle.($rows_k == 2 ? '（邀请人员）' : '')); //第A列 第1行
                    $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
                    $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

                    $start_row_num = 3;
                    $i = 4;
                    foreach ($rows as $k => $v) {
                        $objPHPExcel->getActiveSheet()->mergeCells('A' . $start_row_num . ':F' . $start_row_num); // 合并单元格
                        $objPHPExcel->getActiveSheet()->setCellValue('A' . $start_row_num, $v['title']); //第A列 第1行
                        $objPHPExcel->getActiveSheet()->getStyle('A' . $start_row_num)->getFont()->setSize($objPHPExcelSetSize);
                        $objPHPExcel->getActiveSheet()->getStyle('A' . $start_row_num)->getFont()->setBold(true);
                        $objPHPExcel->getActiveSheet()->getStyle('A' . $start_row_num)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $objPHPExcel->getActiveSheet()->getStyle('A' . $start_row_num)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

                        foreach ($v['users'] as $k1 => $v1) {
                            $j = 0;
                            foreach ($columns as $key1 => $value1) {
                                $v1['no'] = $k1 + 1;
                                $value = $v1[$key1];
                                $objPHPExcel->setActiveSheetIndex($sheetIndex)->setCellValueExplicit(chr(65 + $j) . $i, $value);
                                $j++;
                            }
                            $i++;
                        }
                        $count_rows_1 = count($v['users']) > 0 ? count($v['users']) + 1 : 0;
                        $start_row_num = $count_rows_1 + 3;
                        $i = 4 + $count_rows_1;
                    }
                    $objPHPExcel->getActiveSheet()->setTitle($excelTitle.($rows_k == 2 ? '_邀请人员' : ''));
                    $sheetIndex++;
                }
            }

            $objPHPExcel->setActiveSheetIndex(0);
            header('Expires: ' . date(DATE_RFC1123));
            header('Cache-Control: no-store, no-cache, must-revalidate,' . '  pre-check=0, post-check=0, max-age=0');
            header('Last-Modified: ' . date(DATE_RFC1123));
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $excelTitle . '.xls"');
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
        }
        exit;
    }

    public function actionDownloadExcelbak()
    {
        $id = intval($this->_request['selValues']);
        if ($id) {
            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
                ->setLastModifiedBy("Maarten Balliauw")
                ->setTitle("Office 2007 XLSX Test Document")
                ->setSubject("Office 2007 XLSX Test Document")
                ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                ->setKeywords("office 2007 openxml php")
                ->setCategory("Test result file");
            $columns = [
                'no' => '序号',
                'name' => '姓名',
                'type1' => '优秀',
                'type2' => '合格',
                'type3' => '基本合格',
                'type4' => '不合格',
            ];
            $i = 0;
            foreach ($columns as $key1 => $value1) {
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $i) . '2', $value1);
                $i++;
            }
            // 设置每列宽度
            $ABCDE = ['A', 'B', 'C', 'D', 'E', 'F'];
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(6);
            for ($i = 1; $i < 6; $i++) {
                $objPHPExcel->getActiveSheet()->getColumnDimension($ABCDE[$i])->setWidth(18);
            }
            // 保护cell
            $objPHPExcel->getActiveSheet()->getProtection()->setSheet(true);
            $objPHPExcelSetSize = 14; // 字体大小

            $rows = $this->getUserVoteData($id);
            if ($rows) {
                $start_row_num = 3;
                $ii = 4;
                foreach ($rows as $k => $v) {
                    $objPHPExcel->getActiveSheet()->mergeCells('A' . $start_row_num . ':F' . $start_row_num); // 合并单元格
                    $objPHPExcel->getActiveSheet()->setCellValue('A' . $start_row_num, $v['title']); //第A列 第1行
                    $objPHPExcel->getActiveSheet()->getStyle('A' . $start_row_num)->getFont()->setSize($objPHPExcelSetSize);
                    $objPHPExcel->getActiveSheet()->getStyle('A' . $start_row_num)->getFont()->setBold(true);
                    $objPHPExcel->getActiveSheet()->getStyle('A' . $start_row_num)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle('A' . $start_row_num)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

                    foreach ($v['users'] as $k1 => $v1) {
                        $j = 0;
                        foreach ($columns as $key1 => $value1) {
                            $v1['no'] = $k1 + 1;
                            $value = $v1[$key1];
                            // $this->writeLog($ii.'，'.$v1['no']);
                            $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit(chr(65 + $j) . $ii, $value);
                            $j++;
                        }
                        $ii++;
                    }
                    $count_rows_1 += count($v['users']) > 0 ? count($v['users']) + 1 : 0;
                    $start_row_num = $count_rows_1 + 3;
                   
                    $ii = 4 + $count_rows_1;
                }
            }

            // 第一行标题
            $title = Yii::$app->db->createCommand("select title from weixin_qzpy_vote_item where id=" . $id)->queryOne();
            $excelTitle = $title['title'];
            $objPHPExcel->getActiveSheet()->mergeCells('A1:F1'); // 合并单元格
            $objPHPExcel->getActiveSheet()->setCellValue('A1', $excelTitle); //第A列 第1行
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(20);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::VERTICAL_CENTER);

            $objPHPExcel->getActiveSheet()->setTitle($excelTitle);
            $objPHPExcel->setActiveSheetIndex(0);
            header('Expires: ' . date(DATE_RFC1123));
            header('Cache-Control: no-store, no-cache, must-revalidate,' . '  pre-check=0, post-check=0, max-age=0');
            header('Last-Modified: ' . date(DATE_RFC1123));
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment; filename="' . $excelTitle . '.xls"');
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            $objWriter->save('php://output');
        }
        exit;
    }

    // 调用 
    private function writeLog($log){
        file_put_contents('/www/web/fzrb.fznews.com.cn/attachment/logs/weixin_oa_'.date('Ymd').'.txt', '[vote]'.date('Y-m-d H:i:s => ').$log."\r\n", FILE_APPEND);
    }

    /**
     * 评议管理员列表数据
     */
    public function actionAdminIndex()
    {
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        $where = ['and', ['>', 'id', 0]];
        if ($this->_request['username']) {
            $where[] = ['like', 'username', $this->_request['username']];
        }
        $res = (new \yii\db\query())->select('*')->from('weixin_qzpy_vote_admin')->where($where)->orderBy('id desc')->limit($limit)->offset($offset)->all();
        $total = (new \yii\db\query())->select('id')->from('weixin_qzpy_vote_admin')->where($where)->count();
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total > 0 ? $total : 0;
        $this->_result['data'] = $res;
        return $this->_result;
    }

    /**
     * 评议管理员保存动作
     */
    public function actionAdminSave()
    {
        $id = intval($this->_request['id']);
        $userid = $this->_request['userid'];
        $username = $this->_request['username'];
        $department = $this->_request['department'];
        $parentdepartment = $this->_request['parentdepartment'];
        $invite = $this->_request['invite'];
        $state = $this->_request['state'];
        $data = [
            'userid' => $userid,
            'username' => $username,
            'department' => $department,
            'parentdepartment' => $parentdepartment,
            'invite' => $invite,
            'state' => $state
        ];
        if ($id) {
            Yii::$app->db->createCommand()->update('weixin_qzpy_vote_admin', $data, 'id=:id', [':id' => $id])->execute();
            $res = true;
        } else {
            $data['inserttime'] = date('Y-m-d H:i:s');
            $res = Yii::$app->db->createCommand()->insert('weixin_qzpy_vote_admin', $data)->execute();
            $isert_id  = Yii::$app->db->getLastInsertID();
        }
        if ($res) {
            $action = $isert_id ? '新增' : '修改';
            $remark = $action . "评议管理员。姓名：" . $username . '，id：' . ($isert_id ? $isert_id : $id);
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        } else {
            $errorMessage = $isert_id ? '添加失败' : '修改失败';
            $this->_result = ['success' => false, 'errorCode' => 501, 'errorMessage' => $errorMessage];
        }
        return $this->_result;
    }

    /**
     * 评议管理员删除动作
     */
    public function actionDelAdmin()
    {
        $id = $this->_request['id'][0];
        if ($id) {
            $res = Yii::$app->db->createCommand()->delete('weixin_qzpy_vote_admin', 'id=:id', [':id' => $id])->execute();
            if ($res) {
                $action = '删除';
                $remark = $action . "评议管理员。id：" . $id;
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            } else {
                $errorMessage = '删除失败';
                $this->_result = ['success' => false, 'errorCode' => 501, 'errorMessage' => $errorMessage];
            }
        }
        return $this->_result;
    }

    /**
     * 获取管理员用户信息
     */
    public function actionManager()
    {
        $row = (new \yii\db\Query())->select('*')->from('weixin_qzpy_vote_admin')->where(['and', ['=', 'state', 1], ['=', 'userid', $this->_adminInfo['wxuserid']]])->one();
        $this->_result['data'] = $row;
        return $this->_result;
    }

    /**
     * tab访问权限
     */
    public function actionAccessTab()
    {
        $tabs = $this->_getRouteMenuChildren(183);
        foreach ($tabs as $tab) {
            $this->_result['data'][] = $tab['path'];
        }
        return $this->_result;
    }

    /**
     * 获取用户信息
     */
    protected function getUserInfo()
    {
        $query = new \yii\db\Query();
        $res_user = $query->select(['userid', 'name'])->from('weixin_leave_userinfo')->where(['>', 'id', 0])->all();
        $arr_user = [];
        if ($res_user) {
            foreach ($res_user as $v) {
                $arr_user[$v['userid']] = $v['name'];
            }
        }
        return $arr_user;
    }

    /**
     * 获取用户评议结果信息
     * @param int $id 项目id
     * @return array
     */
    protected function getUserVoteData($id = 0, $userType=0)
    {
        
        $userLog = $userData = $rows = [];
        $res = (new \yii\db\query())->select('userid,type,usertype,count(id) as total')->from('weixin_qzpy_vote_item_user_vote_log')->where(['=', 'iid', $id])->groupBy('userid,type,usertype')->all();
        
        $typeMaxNum = 1;
        if ($res) {
            foreach ($res as $k => $val) {
                if ($typeMaxNum < $val['usertype']) {
                    $typeMaxNum = $val['usertype'];
                }
                $userLog[$val['usertype']][$val['userid']][$val['type']] = $val['total'];
               
            }
            $res_sub = (new \yii\db\query())->select('*')->from('weixin_qzpy_vote_item_sub')->where(['=', 'iid', $id])->orderBy('sid asc')->all();
            if ($res_sub) {
                $arrUserName = $this->getUserInfo();
                for ($i=1; $i < $typeMaxNum + 1; $i++) { 
                    foreach ($res_sub as $k => $v) {
                        $userData = [];
                        $row = [
                            'title' => $v['stitle'] . '(可评优秀 ' . $v['snum'] . ' 人)',
                        ];
                        $userids = explode(',', $v['userid']);
                        foreach ($userids as $uid) {
                            $userData[] = [
                                'userid' => $uid,
                                'name' => $arrUserName[$uid],
                                'type1' => isset($userLog[$i][$uid][1]) ? $userLog[$i][$uid][1] : '-',
                                'type2' => isset($userLog[$i][$uid][2]) ? $userLog[$i][$uid][2] : '-',
                                'type3' => isset($userLog[$i][$uid][3]) ? $userLog[$i][$uid][3] : '-',
                                'type4' => isset($userLog[$i][$uid][4]) ? $userLog[$i][$uid][4] : '-',
                            ];
                        }
                        $row['users'] = $userData;
                        $rows[$i][] = $row;
                    }
                }
            }
        }
        if ($typeMaxNum < $userType) {
            return [];
        }else if ($userType) {
            return $rows[$userType];
        }else{
            return $rows;
        }
    }

    protected function getUserVoteDatabak($id = 0)
    {
        $userLog = $userData = $rows = [];
        $res = (new \yii\db\query())->select('userid,type,count(id) as total')->from('weixin_qzpy_vote_item_user_vote_log')->where(['=', 'iid', $id])->groupBy('userid,type')->all();
        if ($res) {
            foreach ($res as $k => $val) {
                $userLog[$val['userid']][$val['type']] = $val['total'];
            }
            $res_sub = (new \yii\db\query())->select('*')->from('weixin_qzpy_vote_item_sub')->where(['=', 'iid', $id])->orderBy('sid asc')->all();
            if ($res_sub) {
                $arrUserName = $this->getUserInfo();
                foreach ($res_sub as $k => $v) {
                    $userData = [];
                    $row = [
                        'title' => $v['stitle'] . '(可评优秀 ' . $v['snum'] . ' 人)',
                    ];
                    $userids = explode(',', $v['userid']);
                    foreach ($userids as $uid) {
                        $userData[] = [
                            'userid' => $uid,
                            'name' => $arrUserName[$uid],
                            'type1' => isset($userLog[$uid][1]) ? $userLog[$uid][1] : 0,
                            'type2' => isset($userLog[$uid][2]) ? $userLog[$uid][2] : 0,
                            'type3' => isset($userLog[$uid][3]) ? $userLog[$uid][3] : 0,
                            'type4' => isset($userLog[$uid][4]) ? $userLog[$uid][4] : 0,
                        ];
                    }
                    $row['users'] = $userData;
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * 记录投票及为投票人员
     */
    public function actionVoteUser()
    {
        $id = $this->_request['id'];
        if ($id) {
            $rowsTitle = [];
            $voteUserArr = $resParticipantArr = $resInviterArr = $rows = [];
            $row = (new \yii\db\query())->select('participant,inviter')->from('weixin_qzpy_vote_item')->where(['=', 'id', $id])->one();
            // 参与评议人员
            if ($row['participant']) {
                $resParticipant = $row['participant'];
                $resParticipantArr = explode(',', $resParticipant);
                $rowsTitle[] = '已投票人员';
            }
            
            // 邀请参与评议人员 
            if ($row['inviter']) {
                $resInviter = $row['inviter'];
                $resInviterArr = explode(',', $resInviter);
                $resInviterArr = array_diff($resInviterArr, $resParticipantArr);
                $rowsTitle[] = '已投票人员（邀请）';
            }

            // 投票总人员
            $voteUserArr = array_unique(array_merge($resParticipantArr, $resInviterArr));
            
            // 已投票人员
            $res_vote = (new \yii\db\query())->select('*')->from('weixin_qzpy_vote_item_user_vote')->where(['=', 'iid', $id])->all();
            $resVoteUser = array_column($res_vote, 'userid');

            // 未投票人员
            $noVoteUser = array_diff($voteUserArr, $resVoteUser);
            $rowsTitle[] = '未投票人员';

            $arrUserName = $this->getUserInfo();

            foreach ($rowsTitle as $val) {
                $userData = [];
                $row = ['title' => $val];
                if ($val == '已投票人员') {
                    $newResParticipantArr = array_intersect($resParticipantArr, $resVoteUser);
                    if (count($newResParticipantArr) > 0) {
                        foreach ($newResParticipantArr as $v_1) {
                            $userData[] = ['userid' => $v_1, 'name' => $arrUserName[$v_1]];
                        }
                    }
                }elseif ($val == '已投票人员（邀请）') {
                    $newResInviterArr = array_intersect($resInviterArr, $resVoteUser);
                    if (count($newResInviterArr) > 0) {
                        foreach ($newResInviterArr as $v_1) {
                            $userData[] = ['userid' => $v_1, 'name' => $arrUserName[$v_1]];
                        }
                    }
                }elseif ($val == '未投票人员') {
                    if (count($noVoteUser) > 0) {
                        foreach ($noVoteUser as $v_1) {
                            $userData[] = ['userid' => $v_1, 'name' => $arrUserName[$v_1]];
                        }
                    }
                }
                $row['users'] = $userData;
                $rows[] = $row; 
            }

            $this->_result['data'] = $rows;
            return $this->_result;     
        }
    }
}
