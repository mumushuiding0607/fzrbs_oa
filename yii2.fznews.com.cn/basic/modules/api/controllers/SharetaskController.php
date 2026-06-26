<?php
namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\commons\Tools;
use yii\helpers\Json;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * 传播任务接口类
 */
class SharetaskController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinStaff';
    protected $_source = ['0' => '请选择', '1' => '福州日报', '2' => '福州晚报', '3' => '福州新闻网', '4' => '掌上福州'];
    protected $_level = ['0' => '否', '1' => '是'];
    public $_userAdminInfo = [];
    public $_manager = ['zhangling','guohuifeng'];

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
        $where = ['and', ['>', 'id', 0]];
        if (isset($this->_request['state'])) {
            $where[] = ['=', 'state', $this->_request['state']];
        } else {
            $where[] =  ['>', 'state', -1];
        }
        if ($this->_request['title']) {
            $where[] = ['like', 'title', $this->_request['title']];
        }
        $total = (new \yii\db\Query())->select(['id'])->from('weixin_share_wx_task')->where($where)->count();
        $res = (new \yii\db\Query())->select('*')->from('weixin_share_wx_task')->where($where)->limit($limit)->offset($offset)->orderBy('id desc')->all();
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        $this->_result['data'] = $res;
        return $this->_result;
    }

    /**
     * 新增、修改传播信息动作
     */
    public function actionSave()
    {
        $id = intval($this->_request['id']);
        $title = trim($this->_request['title']);
        $link = trim($this->_request['link']);
        $remark = trim($this->_request['remark']);
        $source = intval($this->_request['source']);
        $endtime = $this->_request['endtime'];
        $level = intval($this->_request['level']);
        $image = trim($this->_request['image']);
        $imagename = '';
        $inData = array(
            'title' => $title,
            'link' => $link,
            'remark' => $remark,
            'imagename' => $imagename,
            'image' => $image,
            'source' => $source,
            'endtime' => $endtime,
            'level' => $level,
        );
        if ($id) {
            Yii::$app->db->createCommand()->update('weixin_share_wx_task', $inData, ['=', 'id', $id])->execute();
            $action = '修改';
        } else {
            $inData['editor'] =  $this->_adminInfo['realname'];
            $inData['userid'] = $this->_adminInfo['wxuserid'];
            $inData['inserttime'] =  date('Y-m-d H:i:s');
            Yii::$app->db->createCommand()->insert('weixin_share_wx_task', $inData)->execute();
            $id  = Yii::$app->db->getLastInsertID();
            $action = '添加';
        }
        $remark = $action . "传播任务信息。标题：" . $title . "，Id：" . $id;
        $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        return $this->_result;
    }

    /**
     * 状态更新接口动作
     */
    public function actionUpdateState()
    {
        $id = intval($this->_request['id']);
        $st = $this->_request['st'];
        if ($id) {
            Yii::$app->db->createCommand()->update('weixin_share_wx_task', ['state' => $st], ['=', 'id', $id])->execute();
            $row = (new \yii\db\Query())->select(['title', 'id', 'state', 'level', 'sendmessage'])->from('weixin_share_wx_task')->where(['=', 'id', $id])->one();
            if ($st == 1 && $row['state'] == 1) {
                $this->sendMessage($row);
            }
            $action = ($st == 1 ? '发布' : ($st == -1 ? '删除' : '撤销'));
            $remark = $action . "传播任务信息。标题：" . $row['title'] . "，Id：" . $id;
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        }
        return $this->_result;
    }

    /**
     * 获取阅读量接口动作
     */
    public function actionShareUser()
    {
        $id = $this->_request['id'];
        if ($id) {
            $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
            $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
            $offset = $limit * ($page - 1);
            $data = $this->getUserShareData(1, $id, $limit, $offset);
            $this->_result["current"] = $page;
            $this->_result["pageSize"] = $limit;
            $this->_result["total"] = $data['total'];
            $this->_result['data'] = $data['data'];
        }
        return $this->_result;
    }

    /**
     * 重写列表导出数据业务实现动作
     */
    public function actionTaskDownload()
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

        $columns = [
            'title' => '标题',
            'link' => '连接',
            'source' => '来源',
            'level' => '等级',
            'editor' => '发布人',
            'sharenum' => '转发量',
            'clicknum' => '阅读量',
            'endtime' => '截止时间',
            'state' => '状态',
            'inserttime' => '创建时间'
        ];
        $i = 0;
        foreach ($columns as $key1 => $value1) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $i) . '1', $value1);
            $i++;
        }

        $where = ['and', ['>', 'state', -1]];
        if ($this->_request['title']) {
            $where[] = ['like', 'title', $this->_request['title']];
        }

        $res = (new \yii\db\Query())->select('*')->from('weixin_share_wx_task')->where($where)->orderBy('id desc')->all();
        $i = 0;
        foreach ($res as $row) {
            $j = 0;
            $item = $row;
            $item['source'] = $this->_source[$row['source']];
            $item['level'] = $this->_level[$row['level']];
            $item['state'] = $row['state'] == 1 ? '已发布' : '未发布';
            foreach ($columns as $key1 => $value1) {
                $value = $item["$key1"];
                $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit(chr(65 + $j) . ($i + 2), $value);
                $j++;
            }
            $i++;
        }
        $objPHPExcel->getActiveSheet()->setTitle('传播任务');
        $objPHPExcel->setActiveSheetIndex(0);
        header('Expires: ' . date(DATE_RFC1123));
        header('Cache-Control: no-store, no-cache, must-revalidate,' . '  pre-check=0, post-check=0, max-age=0');
        header('Last-Modified: ' . date(DATE_RFC1123));
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="传播任务.xls"');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * 用户传播数据列表动作
     */
    public function actionUserTaskDownload()
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

        $columns = [
            'name' => '转发人',
            'departmentname' => '部门',
            'clicknum' => '阅读量',
            'inserttime' => '创建时间'
        ];
        $i = 0;
        foreach ($columns as $key1 => $value1) {
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue(chr(65 + $i) . '1', $value1);
            $i++;
        }
        $id = $this->_request['t_id'];
        $data = $this->getUserShareData(2, $id);
        $i = 0;
        foreach ($data['data'] as $row) {
            $j = 0;
            foreach ($columns as $key1 => $value1) {
                $value = $row["$key1"];
                $objPHPExcel->setActiveSheetIndex(0)->setCellValueExplicit(chr(65 + $j) . ($i + 2), $value);
                $j++;
            }
            $i++;
        }
        $objPHPExcel->getActiveSheet()->setTitle('传播阅读数据');
        $objPHPExcel->setActiveSheetIndex(0);
        header('Expires: ' . date(DATE_RFC1123));
        header('Cache-Control: no-store, no-cache, must-revalidate,' . '  pre-check=0, post-check=0, max-age=0');
        header('Last-Modified: ' . date(DATE_RFC1123));
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="传播转发任务.xls"');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    /**
     * 管理员列表数据动作
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
        $res = (new \yii\db\query())->select('*')->from('weixin_share_wx_task_admin')->where($where)->orderBy('id desc')->limit($limit)->offset($offset)->all();
        $total = (new \yii\db\query())->select('id')->from('weixin_share_wx_task_admin')->where($where)->count();
        foreach ($res as $key => $val) {
            $res[$key]['department'] = explode(',', $val['department']);
            $res[$key]['parentdepartment'] = explode(',', $val['parentdepartment']);
        }
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total > 0 ? $total : 0;
        $this->_result['data'] = $res;
        return $this->_result;
    }

    /**
     * 获取管理员信息动作
     */
    public function actionGetAdminInfo()
    {
        $res = (new \yii\db\query())->select(['userid'])->from('weixin_share_wx_task_admin')->all();
        $arr = [];
        if ($res) {
            $arr = array_column($res, 'userid');
        }
        $this->_result['data'] = $arr;
        return $this->_result;
    }

    /**
     * 保存管理员动作
     */
    public function actionAdminSave()
    {
        $id = intval($this->_request['id']) ? intval($this->_request['id']) : 0;
        $userid = $this->_request['getFormRefTree'];
        if (!$this->_request['getFormRefTreeDep']) {
            $this->_result['errorCode'] = 501;
            $this->_result['errorMessage'] = '请选择管理部门';
        }
        $department = implode(',', $this->_request['getFormRefTreeDep']);
        $parentdepartment = implode(',', $this->_request['getFormRefTreeParentDep']);

        if ($id) {
            $up_data = [
                        'department' => $department,
                        'parentdepartment' => $parentdepartment
                    ];
            Yii::$app->db->createCommand()->update('weixin_share_wx_task_admin', $up_data, ['=', 'id', $id])->execute();
            $action = '更新';
            $remark = $action . "传播任务管理员ID：" . $id . "，部门ID：" . $department;
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            return $this->_result;
        }

        if (!$userid) {
            $this->_result['errorCode'] = 501;
            $this->_result['errorMessage'] = '请选择管理员';
        } else {
            $username = Tools::getUserNameDepartment();
            $res = (new \yii\db\query())->select(['userid'])->from('weixin_share_wx_task_admin')->all();
            $resArrUserid = array_column($res, 'userid');

            $names = [];
            foreach ($userid as $v) {
                if (!is_numeric($v)) {
                    if (!in_array($v, $resArrUserid)) {
                        $data = [
                            'userid' => $v,
                            'username' => $username[$v]['name'],
                            'department' => $department,
                            'parentdepartment' => $parentdepartment,
                            'inserttime' => date('Y-m-d H:i:s')
                        ];
                        Yii::$app->db->createCommand()->insert('weixin_share_wx_task_admin', $data)->execute();
                            $names[] =  $username[$v]['name'];
                    }
                }
            }
            if ($names) {
                $action = '添加';
                $remark = $action . "传播任务管理员：姓名：" . implode(',', $names);
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        }
        return $this->_result;
    }

    /**
     * 删除管理员动作
     */
    public function actionDelAdmin()
    {
        $id = $this->_request['id'][0];
        if ($id) {
            $row = (new \yii\db\query())->select(['username'])->from('weixin_share_wx_task_admin')->where(['=', 'id', $id])->one();
            $res = Yii::$app->db->createCommand()->delete('weixin_share_wx_task_admin', 'id=:id', [':id' => $id])->execute();
            if ($res) {
                $action = '删除';
                $remark = $action . "传播任务管理员。姓名：" . $row['username'];
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            } else {
                $this->_result['errorMessage'] = '删除失败';
            }
        }
        return $this->_result;
    }

    /**
     * tab访问权限
     */
    public function actionAccessTab()
    {
        $tabs = $this->_getRouteMenuChildren(187);
        foreach ($tabs as $tab) {
            $this->_result['data'][] = $tab['path'];
        }
        return $this->_result;
    }

    /**
     * 相关配置数据接口
     */
    public function actionConfig()
    {
        $data = $source = $level = [];
        foreach ($this->_source as $k => $v) {
            $source[$k] = ['text' => $v];
        }
        if ($source) {
            $data['source'] = $source;
        }
        foreach ($this->_level as $k => $v) {
            $level[$k] = ['text' => $v];
        }
        if ($level) {
            $data['level'] = $level;
        }
        $this->_result['data'] = $data;
        return $this->_result;
    }

    /**
     * 发送微信企业应用消息通知
     * @param array $data 传播信息
     */
    protected function sendMessage($data)
    {
        if ($data && $data['state'] == 1 && $data['level'] == 1 && $data['sendmessage'] == 0) 
        {
            $resDepId = (new \yii\db\Query())->select('department')->from('weixin_share_wx_task_admin')->where(['=', 'userid', $this->_adminInfo['wxuserid']])->one();
            if ($resDepId) 
            {
                // 获取部门信息 及 用户userid
                $arr_depID = explode(',', $resDepId['department']);
                $where = ['in', 'departmentid', $arr_depID];
                $resUserid = (new \yii\db\query())->select('userid')->from('weixin_leave_userinfo')->where($where)->all();
                $getUserIdArr = array_column($resUserid, 'userid');

                $appId = '1000074';
                $userid = "'" . implode('|', $getUserIdArr) . "'";
                $content = '您有一条转发新任务：' . $data['title'] . '；前往<a href="https://fzrb.fznews.com.cn/index.php?r=qiyehao/qysharewxtask/viewuser&id=' . $data['id'] . '">查看详情</a>。';
               
                $sendResult = WxQyhJk::sendMessage($appId, $userid, $content);
                if (!$sendResult['errorMessage']) 
                {
                    Yii::$app->db->createCommand()->update('weixin_share_wx_task', ['sendmessage' => 1], ['=', 'id', $data['id']])->execute();
                }
            }
        }
    }

    /**
     * 拼接用户传播数据
     * @param int $action 动作(1:列表，2:下载)
     * @param int $id 传播任务id
     * @param int $limit 列表条数
     * @param int $offset 列表数据偏移量
     */
    protected function getUserShareData($action, $id, $limit = 0, $offset = 0)
    {
        $usernames = Tools::getUserNameDepartment();

        $where = ['and', ['=', 't_id', $id]];
        $name = $this->_request['name'];
        if ($name) {
            $findUserId = [];
            foreach ($usernames as $k => $v) {
                if (strpos($v['name'], $name) !== false) {
                    $findUserId[] = $k;
                }
            }
            if ($findUserId) {
                $where[] = ['in', 'userid', $findUserId];
            }
        }
        $departmentname = $this->_request['departmentname'];
        if ($departmentname) {
            $departmentId = Tools::getDepartmentIds($departmentname);
            if ($departmentId) {
                $departmentusers = Tools::getUserNameDepartment(['in', 'departmentid', $departmentId]);
                $findUserId = array_keys($departmentusers);
                if ($findUserId) {
                    $where[] = ['in', 'userid', $findUserId];
                }
            } else {
                $where[] = ['=', 'id', 0];
            }
        }
        $orderBy = 'inserttime desc';
        $this->_request = ArrayHelper::htmlDecode($this->_request);
        $sorter = Json::decode($this->_request['sorter'], true);
        if ($sorter) {
            if (isset($sorter['clicknum'])) {
                $orderBy = "clicknum " . str_replace('end', '', $sorter['clicknum']);
            }
        }
        $res = $rows = [];
        $total = 0;
        $query = "userid,count(*) as clicknum,min(inserttime) as inserttime";
        if ($action == 1) {
            $res = (new \yii\db\query())->select($query)->from('weixin_share_wx_task_user_log')->where($where)->orderBy($orderBy)->groupBy('userid')->limit($limit)->offset($offset)->all();
            $total = (new \yii\db\query())->select($query)->from('weixin_share_wx_task_user_log')->where($where)->groupBy('userid')->count();
        } else {
            $res = (new \yii\db\query())->select($query)->from('weixin_share_wx_task_user_log')->where($where)->orderBy($orderBy)->groupBy('userid')->all();
        }
        foreach ($res as $val) {
            $item = $val;
            $item['clicknum'] = $val['clicknum'] - 1;
            $item['name'] = $usernames[$val['userid']]['name'];
            $item['departmentname'] = $usernames[$val['userid']]['departmentname'];
            $rows[] = $item;
        }
        return [
            'data' => $rows,
            'total' => $total,
        ];
    }

    private function getDepartForParent($id){
        $depart = (new \yii\db\query())->select('*')->from('weixin_leave_department')->where(['=', 'id', $id])->one();
        if($depart['parentid']==1){
            return $id;
        }else{
            return $this->getDepartForParent($depart['parentid']);
        }
    }

    private function getDepartForChilds($id){
        $ret = [];
        $depart = (new \yii\db\query())->select('id')->from('weixin_leave_department')->where(['=', 'parentid', $id])->all();
       
        if($depart){
            foreach($depart as $row){
                $ret[] = $row['id'];
                $child = $this->getDepartForChilds($row['id']);
                if(is_array($child)){
                    $ret = array_merge($ret,$child);
                }
            }
            return $ret;
        }else{
            return 0;
        }
    }

    /**
     * 按人数统计
     */
    public function actionUtotalList()
    {
            $maindepart=0;  
            $ures = (new \yii\db\query())->select('a.userid,a.parentdepartment,b.departmentid')->from('weixin_share_wx_task_admin as a')->join('LEFT JOIN', 'weixin_leave_userinfo as b', 'a.userid=b.userid')->where(['=', 'a.userid', $this->_adminInfo['wxuserid']])->one();

            if($ures){
                $maindepart=$this->getDepartForParent($ures['departmentid']);
                $maindepart=$maindepart==65?1:$maindepart;
            }
            if(!$maindepart){
                return [
                    'data' => [],
                    'total' => 0,
                ];
            }
            
            $items = [];
            if($maindepart > 1){
                $departs = $this->getDepartForChilds($maindepart);
                $departs[] = $maindepart;
            }else{
                $departs = explode(',',$ures['parentdepartment']);
            }


            $year = $_REQUEST['year'] ? $_REQUEST['year'] : date('Y');
            $month = $_REQUEST['month'] ?  ($_REQUEST['month'] < 10 ? '0'.$_REQUEST['month'] : $_REQUEST['month']) : date('m');
            $yearMonth = $year . '-' . $month;
            $monthWhere = " and DATE_FORMAT(b.inserttime,'%Y-%m')='$yearMonth'";

            $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
            $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
            $offset = $limit * ($page - 1);

           
            $tasktot = Yii::$app->db->createCommand("SELECT e.id,e.userid,e.`name`,d.task,d.clicks FROM weixin_leave_userinfo e LEFT JOIN (select c.userid,count(c.t_id) task,sum(c.cnt) clicks from (SELECT a.userid,a.t_id,count(a.id) cnt FROM `weixin_share_wx_task_user_log` a LEFT JOIN weixin_share_wx_task b on a.t_id=b.id where a.tp=0 $monthWhere GROUP BY a.userid,a.t_id) c GROUP BY c.userid) d on e.userid=d.userid where e.`status`=1 and e.st=1 and e.departmentid in (".implode(',',$departs).") ORDER BY d.task desc,d.clicks desc,e.id asc limit $offset,$limit")->queryAll();

            $total = Yii::$app->db->createCommand("SELECT count(e.id) FROM weixin_leave_userinfo e LEFT JOIN (select c.userid,count(c.t_id) task,sum(c.cnt) clicks from (SELECT a.userid,a.t_id,count(a.id) cnt FROM `weixin_share_wx_task_user_log` a LEFT JOIN weixin_share_wx_task b on a.t_id=b.id where a.tp=0 $monthWhere GROUP BY a.userid,a.t_id) c GROUP BY c.userid) d on e.userid=d.userid where e.`status`=1 and e.st=1 and e.departmentid in (".implode(',',$departs).")")->queryScalar();
            
            foreach($tasktot as $row){
                $item = $row;
                $item['task'] = $item['task']? $item['task']:0;
                $item['clicks'] = $item['clicks']? $item['clicks']:0;
                $item['year'] = $year;
                $item['month'] = $month;
                array_push($items, $item);
            }
            
            return [
                'data' => $items,
                'total' => $total,
            ];
    }

    /**
     * 统计列表
     */
    public function actionTotalList (){
        $maindepart=0;  
        $ures = (new \yii\db\Query())->select('a.userid,b.departmentid')->from('weixin_share_wx_task_admin as a')->join('LEFT JOIN', 'weixin_leave_userinfo as b', 'a.userid=b.userid')->where(['=', 'a.userid', $this->_adminInfo['wxuserid']])->one();
       
        if($ures){
            $maindepart=$this->getDepartForParent($ures['departmentid']);
            $maindepart=$maindepart==65?1:$maindepart;
        }
        if(!$maindepart){
            $result["lists"] = [];
            echo json_encode($result);
            exit;
        }

        $title = trim($_REQUEST['title']);
        $titleWhere = isset($title) ? ' and title like \'%'. $title .'%\'' : '';

        $year = $_REQUEST['year'] ? $_REQUEST['year'] : date('Y');
        $month = $_REQUEST['month'] ?  ($_REQUEST['month'] < 10 ? '0'.$_REQUEST['month'] : $_REQUEST['month']) : date('m');
        $yearMonth = $year . '-' . $month;
        $monthWhere = " and DATE_FORMAT(inserttime,'%Y-%m')='$yearMonth'";

        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);

        $items = array();
        $inwhere = $maindepart==1 || in_array($this->_adminInfo['wxuserid'],$this->_manager) ?" ":" and userid in (select userid from weixin_share_wx_task_admin where FIND_IN_SET('$maindepart',parentdepartment) and not FIND_IN_SET('65',parentdepartment))";

        $res = yii::$app->db->createCommand("select * from weixin_share_wx_task where state=1 $titleWhere $monthWhere $inwhere order by inserttime desc limit $offset, $limit ")->queryAll();

        $total = yii::$app->db->createCommand("select count(id) from weixin_share_wx_task where state=1 $titleWhere $monthWhere $inwhere")->queryScalar();
        if(count($res) > 0)
        {
            foreach($res as $row)
            {                   
                $item = $row;
                $item['inserttime']=date('Y-m-d',strtotime($item['inserttime']));
                array_push($items, $item);
            }
        }
        $taskNumRes = yii::$app->db->createCommand("select sum(sharenum) shareTotal,sum(clicknum) clickTotal from weixin_share_wx_task where state=1 $titleWhere $monthWhere $inwhere ")->queryOne();
            
        return [
            'data' => $items,
            'total' => $total,
            'shareTotal' => $taskNumRes['shareTotal'],
            'clickTotal' => $taskNumRes['clickTotal'],
        ];
    }









}
