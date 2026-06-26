<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\commons\Uploader;
use app\modules\api\models\FzrbsRouteMenu;
use app\modules\api\models\WxDepartment;
use app\modules\api\models\WeixinTruckTemplate;
use app\modules\api\models\WeixinOaUserinfo;
use PHPExcel;
use PHPExcel\IOFactory;
use Yii;
/**
 * 报社派车接口类
 */
class TruckOrderController extends ApiBase
{
    protected $_agentId = 1000038;
    public $modelClass = 'app\modules\api\models\WxTruckOrder';
    public $_orderBy = " id desc";
    public $_db;
    public $_req='';
    public $_condition = ' st>=0 and usertype=0';//usertype = 0 报社 
    public $_where = [];//usertype = 0 报社 
    public $_carType = [1=>'5座','7座','11座','14座'];
    public $_truckStatus = [1=>'空闲','繁忙'];
    public $_orderStatus = ['驳回', '审核中', '任务中', '任务暂时保存', '结束派车','确认结束'];
    public $_commentLevel = [1=>'满意','一般','不满意'];
    public $_tollType = [0=>'无',1 =>'有ETC费用', 2 =>'无'];
    public $weekArray=["星期日","星期一","星期二","星期三","星期四","星期五","星期六"];
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }
    public function init(){
        parent::init();
        $this->_db =\Yii::$app->db;
        $this->_req = \Yii::$app->request;

    }
    protected function search(){
        $driver_name = trim($this->_request['driver_name']);
        $car_licence = trim($this->_request['car_licence']);
        $start_time = trim($this->_request['start_time']);
        $end_time = trim($this->_request['end_time']);
        $st = trim($this->_request['st']);
        $selId = $this->_request['selId'];
        $tp = isset($this->_request['tp'])?intval($this->_request['tp']):0;
        // echo $st;exit;
        // $this->_condition .= $driver_name ? " and driver_name like '%$driver_name%'":"";
        // $this->_condition .= $car_licence ? " and car_licence like '%$car_licence%'":"";
        // $this->_condition .= $start_time ? " and start_time >='$start_time 00:00:00'":"";
        // $this->_condition .= $end_time ? " and end_time <='$end_time 23:59:59'":"";
        // $this->_condition .= $st!='' ? " and st=$st":"";

        $this->_where = ['and',['=', 'usertype', $tp]];
        $this->_where[] = ['>', 'st', -1];
        if($driver_name){
            $this->_where[] = ['like', 'driver_name', $driver_name];
        }
        if($car_licence){
            $this->_where[] = ['like', 'car_licence', $car_licence];
        }
        if($start_time){
            $this->_where[] = ['>=', 'start_time', $start_time];
        }
        if($end_time){
            $this->_where[] = ['<=', 'end_time', $end_time];
        }
        if($st!=''){
            $this->_where[] = ['=', 'st', $st];
        }
        if($selId){
            $this->_where[] = ['in', 'id', $selId];
        }
        $userAuth = $this->getUserAuth();
        if($userAuth['childdepts']){
            $this->_where[] = ['in', 'dep_id', $userAuth['childdepts']]; 
        }
    }
    // 设置时间
    protected function setEndTime($startTime, $endTime){
        $overTime = '';
        if ($endTime) {
            $end_start_time = strtotime($endTime) - strtotime($startTime);
            if ($end_start_time > 14400) {
                $over_time = $end_start_time -14400;//超出4小时时间
                $over_time_H = intval($over_time/3600);

                if ($over_time_H>0) {
                    $over_time_i = intval(($over_time-$over_time_H*3600)/60);
                }else{
                    $over_time_i = intval($over_time/60);
                }
                $overTime = ($over_time_H>0 ? $over_time_H.'小时':'').($over_time_i>0 ? $over_time_i.'分':'');
            }
        }
        return $overTime;
    }
    /** 派车列表显示 */
    public function actionIndex(){
        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $this->search();
        $offset = $limit * ($page - 1);
       
        // $total = $this->_db->createCommand("select count(1) from weixin_truck_order where $this->_condition")->queryScalar();
        // $sql = "select * from weixin_truck_order where " . $this->_condition. "  order by id desc limit $offset,$limit";
        // $data = $this->_db->createCommand($sql)->queryAll();

       
        $model = $this->modelClass;
        $model = $model::find()->where($this->_where);
        $total = $model->count();
        $data = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->asArray()->all();

        if(count($data)){
            //审核人
            $flow = $this->_db->createCommand("select belong_key,GROUP_CONCAT(user_name) username from weixin_flow_process where belong_to = 2 and belong_key in (".implode(",",array_column($data,'id')).") and tp =1  group by belong_key")->queryAll();
            $flow = array_combine(array_column($flow,'belong_key'),array_column($flow,'username'));
            //用电话
            $optMobile = $this->_db->createCommand("select userid,mobile from weixin_oauser_userinfo where userid in ('".implode("','",array_column($data,'opt_userid'))."')")->queryAll();
            $optMobile = array_combine(array_column($optMobile,'userid'),array_column($optMobile,'mobile'));

            foreach ($data as $key => $value) {
                // $data[$key]['st'] = $this->_orderStatus[$value['st']];
                $data[$key]['tp'] = $this->_carType[$value['tp']];
                $data[$key]['comment_tp'] = $this->_commentLevel[$value['comment_tp']];
                $data[$key]['toll'] = $value['toll'] ? $this->_tollType[$value['toll']<3 ? $value['toll']:1]:$this->_tollType[2];
                $data[$key]['end_time1'] = $value['end_time'] ? $value['end_time']."(".$this->weekArray[date("w",strtotime($value['end_time']))].")":$value['end_time'];
                $data[$key]['start_time1'] = $value['start_time'] ? $value['start_time']."(".$this->weekArray[date("w",strtotime($value['start_time']))].")":$value['start_time'];
                $data[$key]['over_time'] = $this->setEndTime($value['start_time'],$value['end_time']);
                $data[$key]['checkMan'] = $flow[$value['id']];
                $data[$key]['opt_mobile'] = $optMobile[$value['opt_userid']];
            
            }
        }
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        $this->_result['data'] = $data;
        return $this->_result;
    }
    /** 打印信息获取 */
    public function actionGetView(){
        // echo 344;exit;
        $id = intval($this->_request['id']);
        $this->_where = ['and',['=', 'usertype', 0]];
        
        $this->_where[] = ['=', 'id', $id];
        $model = $this->modelClass;
        $data = $model::find()->where($this->_where)->asArray()->One();
        if($data){
            $data['end_time1'] = $data['end_time'] ? $data['end_time']."(".$this->weekArray[date("w",strtotime($data['end_time']))].")":$data['end_time'];
            $data['start_time1'] = $data['start_time'] ? $data['start_time']."(".$this->weekArray[date("w",strtotime($data['start_time']))].")":$data['start_time'];
            $data['over_time'] = $this->setEndTime($data['start_time'],$data['end_time']);
            //申请人电话
            //审批人信息
            $flow = $this->_db->createCommand("select * from weixin_flow_process where belong_to=2 and belong_key=$id and tp=1 order by step")->queryAll();
            $data['checkMan'] = implode(",",array_column($flow,'user_name'));
            $data['opt_mobile'] = $this->_db->createCommand("select mobile from weixin_oauser_userinfo where userid = '".$data['opt_userid']."'")->queryScalar();
        
        }

        $this->_result['data'] = $data;
        return $this->_result;
       

        // $data = $model->asArray()->all();
    }
    //月度统计列表
    public function actionStatistics(){
            $month = trim($this->_request["t_month"]);
                // echo date("Y-m",$month/1000);exit;
            $car_licence = trim($this->_request["car_licence"]);
            $month = $month ? $month:date("Y-m");
            $this->_condition .= " and start_time like '$month%'";
            $this->_condition .= $car_licence ? " and car_licence ='$car_licence'":"";
    
            $total = $this->_db->createCommand("select count(1) from weixin_truck_order where st>=4 and $this->_condition "." group by car_licence")->queryScalar();
            $total = 0;
            $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
            $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
            $offset = $limit * ($page - 1);
            $this->_result["total"] = $total;
            $sql = "select '$month' t_month,car_licence,sum(mile) mile,sum(park_fee) park_fee,sum(toll) toll from weixin_truck_order where st>=4 and " . $this->_condition . " group by car_licence limit $offset,$limit";
    
            $data = $this->_db->createCommand($sql)->queryAll();
            $this->_result["current"] = $page;
            $this->_result["pageSize"] = $limit;
            $this->_result["data"] = $data;
            return $this->_result;
            
        }
    //驾驶员去向表
    public function actionDriverStatus(){
         //姓名 状态 待命，出车，请假
         $drivers = $this->_db->createCommand("select userid,`name` from weixin_truck_driver where usertype=0 ")->queryAll();
         $data = [];
         if($drivers){
             $driversName = array_combine(array_column($drivers,'userid'),array_column($drivers,'name'));
             $userids = array_column($drivers,'userid');
 
             //出车状态
             $currentDay = date("Y-m-d");
             $drawOutRows = $this->_db->createCommand("select driver,st from weixin_truck_order where st>= 2 and start_time like '%$currentDay%' and usertype=0")->queryAll();
             $drawOut = [];
             $drawW = [];
             if($drawOutRows){
                 foreach ($drawOutRows as $key=>$item){
                     if(in_array($item['st'],[2,3])&&!in_array($item['driver'],$drawW)){
                         //出车中
                         $drawW[] = $item['driver'];
                         $drawOut[] = $item;
                     }else{
                         //待命中
                     }
                 }
             }
 
             //请假
             $useridStr = "'".implode("','",$userids)."'";
             $currentTime = date("Y-m-d H:i:s");
           
             $leave = $this->_db->createCommand("select userId from weixin_leave_info 
                                                   where status=2 and leaveType!='销假' and userId in($useridStr)
                                                    and ('$currentTime' between leaveStarttime and leaveEndtime) group by userId")->queryAll();
 
             $leave = array_column($leave,'userId');
             $await = [];//待命
             foreach ($drivers as $key=>$value){
                 //获取状态
                 if(!in_array($value['userid'],array_column($drawOut,'driver'))&&!in_array($value['userid'],$leave)){
                     $await[] = $value['userid'];
                 }
             }
             //组合数据
             if($await){  //待命
                foreach($await as $userid){
                    $data[]= ['userid'=>$userid,'name'=>$driversName[$userid],'st'=>'待命'];
                }
             }
             if($drawW){  //出车
                foreach($drawW as $userid){
                    $data[]= ['userid'=>$userid,'name'=>$driversName[$userid],'st'=>'出车'];
                }
             }
             if($leave){  //请假
                foreach($leave as $userid){
                    $data[]= ['userid'=>$userid,'name'=>$driversName[$userid],'st'=>'请假'];
                }
             }
         }
         $this->_result["current"] = 1;
         $this->_result["pageSize"] = 100;
         $this->_result["total"] = count($data);
         $this->_result["data"] = $data;
         return $this->_result;
    }
    //获取同行人信息
    public function actionGetStaff(){
        $depId = intval($this->_request['depId']);
        $addWhere ='';
        if($depId&&$depId!=1){
            $addWhere = " and departmentid = ".$depId;
        }
        $ret = [];
        $users = $this->_db->createCommand("select userid,id,departmentname,name,departmentid from weixin_oauser_userinfo where st=1 $addWhere order by id desc ")->queryAll();

        foreach ($users as $r) {
            // if($this->_adminUserType!=1&&!in_array($r['departmentid'],$this->_accessDep)){
            //     continue;
            // }
            $ret[] = ['value' => $r['userid'], 'label' => $r['name']];
        }
        $this->_result['data'] = $ret;
        return $this->_result;
    }
    //获取所有司机
    public function actionGetDriver(){
        $users = $this->_db->createCommand("select distinct(userid),mobile,`name`,id from weixin_truck_driver order by id")->queryAll();
        foreach ($users as $r) {
            $ret[] = ['value' => $r['userid'], 'label' => $r['name'],'mobile'=>$r['mobile']];
        }
        $this->_result['data'] = $ret;
        return $this->_result;
    }
    //获取车牌号
    public function actionGetLicence(){
        $cars = $this->_db->createCommand("select id,licence from weixin_truck where usertype = 0")->queryAll();
        foreach ($cars as $r) {
            $ret[] = ['value' => $r['id'], 'label' => $r['licence']];
        }
        $this->_result['data'] = $ret;
        return $this->_result;
    }
    //获取该车最后的公里
    public function actionGetCarEndMiles(){
        $carID = intval($this->_request['carId']);
        // 中 4任务结束 5确认结束
        $car_end_mile = $this->_db->createCommand("select end_mile from weixin_truck_order WHERE st between 4 and 5 and car_id=" . $carID . " ORDER BY id desc LIMIT 1")->queryOne();
        
        $this->_result['data'] = $car_end_mile;
        return $this->_result;
    }
    // 修改出发地
    public function actionNewStartPlace(){
        $id = intval($this->_request['id']);
        $newPlace = trim($this->_request['newPlace']);
        $oldPlace = trim($this->_request['oldPlace']);
        if($newPlace==$oldPlace || !$newPlace){
            return $this->_result;
        }
        $startPlaceRes = $this->_db->createCommand()->update('weixin_truck_order', ['start_place' => $newPlace], 'id=:id',[':id'=>$id])->execute();
        if(!$startPlaceRes){
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        $action = '更新';
        $remark = $action . "车辆派单ID：" .$id.'，修改出发地为：'.$newPlace.'，原出发地为：'.$oldPlace;
        $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        return $this->_result;
    }
    //更新数据
    public function actionUpdate(){
        $data = $this->_request['values'];
        $id = intval($this->_request['id']);
        $row = $this->modelClass::find()->where(['and',['=','id',$id]])->asArray()->one();

        //获取车牌号
        $data['car_licence'] = $this->_db->createCommand("select licence from weixin_truck where id=".$data['car_id'])->queryScalar();
        //转换同行人信息
        $company = $this->_db->createCommand("select userid,`name` from weixin_oauser_userinfo where userid in('".implode("','",$data['companyUserid'])."')")->queryAll();
        $data['companyNames'] = implode(",",array_column($company,'name'));
        $data['companyUserid'] = implode(",",array_column($company,'userid'));
        $ret = $this->_db->createCommand()->update('weixin_truck_order', $data, "id = $id")->execute();

            
        $action = '更新';
        $remark = $action . "车辆管理：$id" .$data['opt_name'].$data['start_time'].$data['remark'].$data['reason'];
        $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        if(!$ret){
            $this->_result = Tools::wrongRules(1000, '保存失败');
        }
        //判断是否修改司机通知司机
        if($row['driver']!=$data['driver']&&in_array($row['st'],[2,3])){
            //新订单
            $sendResult = WxQyhJk::sendCode(1000038,$data['driver'], '您有一条最新派车任务，请及时处理,<a href="https://fzrb.fznews.com.cn/index.php?r=qiyehao/truckOrder/orderView&id=' . $id. '">请点击查看</a>');
            //旧订单
            $sendResult = WxQyhJk::sendCode(1000038,$row['driver'], "您的派车编号为【".$row['order_no']."】已由他人接单，当前派车已取消。【后台操作】");
            //发起人
            $sendResult = WxQyhJk::sendCode(1000038,$row['opt_userid'], "您的派车信息已发生更改，请及时查看最新信息");

        }
       


        return $this->_result;
    }
    // 删除
    public function actionDelete(){
        if ($this->_request['id']) {
            $ids = $this->_request['id'];

            $usernames = [];
            $data = $this->_db->createCommand("select reason,opt_name from weixin_truck_order where id in($ids)")->queryAll();
            if($data){
                foreach($data as $key => $value){
                    $usernames[] = '申请人：'.$value['opt_name'].' 用车事由：'.$value['reason'].PHP_EOL;
                }
            }
            $this->modelClass::updateAll(['st'=>0], "id in($ids)");

            if ($usernames) {
                $action = '删除';
                $remark = $action . "车辆管理：" . implode(',', $usernames) . "。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }
    /**
     * 数据导出
     */
    public function actionExport(){
        $this->search();
        $tableColumn = ['opt_name'=>'申请人','order_no'=>'审批编号','created'=>'创建时间','dep_name'=>'所在部门','reason'=>'用车事由',
            'companyNames'=>'同行人', 'start_time'=>'开始时间','end_time'=>'结束时间','over_time'=>'超时','destination'=>'目的地','tp'=>'车辆类型'
            ,'st'=>'状态','remark'=>'审批意见','car_licence'=>'指派车牌号','driver_name'=>'司机','driver_mobile'=>'司机电话'
            ,'start_mile'=>'出发里程','end_mile'=>'结束里程','mile'=>'本次里程','park_fee'=>'停车费'
            ,'toll'=>'过路费','total_fee'=>'总费用','comment_tp'=>'满意度','comment'=>'评论'];
        // $sql = "	SELECT
        //               opt_name,order_no,created,dep_name,reason,companyNames,start_time,end_time,destination,tp,st,remark,car_licence,driver_name
        //               ,driver_mobile,start_mile,end_mile,mile,park_fee,toll,total_fee,comment_tp,comment
        //             FROM
        //                weixin_truck_order
        //             WHERE $this->_condition order by id desc";

        // $data = $this->_db->createCommand($sql)->queryAll();

        $model = $this->modelClass;
        $model = $model::find()->where($this->_where);
        $data = $model->orderBy($this->_orderBy)->asArray()->all();

        if(count($data)){
            foreach ($data as $key => $value) {
                $data[$key]['st'] = $this->_orderStatus[$value['st']];
                $data[$key]['tp'] = $this->_carType[$value['tp']];
                $data[$key]['comment_tp'] = $this->_commentLevel[$value['comment_tp']];
                $data[$key]['toll'] = $value['toll'] ? $this->_tollType[$value['toll']<3 ? $value['toll']:1]:$this->_tollType[2];
                $data[$key]['end_time'] = $value['end_time'] ? $value['end_time']."(".$this->weekArray[date("w",strtotime($value['end_time']))].")":$value['end_time'];
                $data[$key]['start_time'] = $value['start_time'] ? $value['start_time']."(".$this->weekArray[date("w",strtotime($value['start_time']))].")":$value['start_time'];

                if ($value['end_time']) {
                    $end_start_time = strtotime($value['end_time']) - strtotime($value['start_time']);
                    if ($end_start_time > 14400) {
                        $over_time = $end_start_time -14400;//超出4小时时间
                        $over_time_H = intval($over_time/3600);

                        if ($over_time_H>0) {
                            $over_time_i = intval(($over_time-$over_time_H*3600)/60);
                        }else{
                            $over_time_i = intval($over_time/60);
                        }
                        $data[$key]['over_time'] = ($over_time_H>0 ? $over_time_H.'小时':'').($over_time_i>0 ? $over_time_i.'分':'');
                    }
                }
            }
        }
        $this->toBlob($data,$tableColumn,'报社派车信息导出'.date('YmdH'));
    }
    /**
     * 月度数据导出
     */
    public function actionExportMonth(){
        $tableColumn = ['this_month'=>'月份','car_licence'=>'车牌号','mile'=>'总里程','park_fee'=>'总停车费'];
        $month = trim($this->_request["t_month"]);
       

        $carLicence = trim($this->_request["car_licence"]);
        $month = $month ? date('Y-m',strtotime($month)):date("Y-m");
        $this->_condition .= " and start_time like '$month%'";
        $this->_condition .= $carLicence ? " and car_licence ='$carLicence'":"";

        $thisWhere = str_replace('st>=0', '', $this->_condition);

        $sql = "select '$month' this_month,car_licence,sum(mile) mile,sum(park_fee) park_fee from weixin_truck_order where st>=4 ". $thisWhere ." group by car_licence ";//,sum
        $data = $this->_db->createCommand($sql)->queryAll();
        $this->toBlob($data,$tableColumn,date('YmdH'));
    }
        /**
     * 月度数据-明细导出
     */
    public function actionExportMonthDetail(){
        
        $tableColumn = ['car_licence'=>'车牌号','start_mile'=>'出发里程','end_mile'=>'结束里程','mile'=>'本次里程','park_fee'=>'停车费','created'=>'派车单创建时间','start_time'=>'派车单出发时间','end_time'=>'派车单结束时间','driver_name'=>'司机'];//,'toll'=>'过路费'
        $month = trim($this->_request["t_month"]);
        $carLicence = trim($this->_request["car_licence"]);
        $month = $month ? date('Y-m',strtotime($month)):date("Y-m");
        $this->_condition .= " and start_time like '$month%'";
        $this->_condition .= $carLicence ? " and car_licence ='$carLicence'":"";

        $thisWhere = str_replace('st>=0', '', $this->_condition);

        $sql = "select car_licence,start_mile,end_mile,mile,park_fee,toll,created,start_time,end_time,driver_name from weixin_truck_order where st>=4 ". $thisWhere ." order by car_licence,start_time ;";
        // echo $sql;exit;
        $data = $this->_db->createCommand($sql)->queryAll();
        $this->toBlob($data,$tableColumn,date('YmdH'));
    }
    protected function toBlob($data=[],$columns=[],$fileName=''){
        // Yii::$enableIncludePath = false;

        // Yii::import('application.extensions.PHPExcel.PHPExcel', 1); .'/vendor/Classes/PHPExcel.php'
        // require_once str_replace("\\","/",dirname(dirname(dirname(dirname(__FILE__)))).'/vendor/PHPExcel/Classes/PHPExcel.php');
        // require_once dirname(dirname(dirname(__FILE__)));exit;

        // $phpexcel = new PHPExcel;
        set_time_limit(0);
        ini_set('memory_limit', '5120M');
        ob_end_clean();
        $phpexcel =  new \PHPExcel();
        $phpexcel->getProperties()->setCreator("Maarten Balliauw")
            ->setLastModifiedBy("Maarten Balliauw")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");
        $i=0;
        foreach($columns as $key1=>$value1){
            $phpexcel->setActiveSheetIndex(0)->setCellValue(chr(65+$i).'1', $value1);
            $i++;
        }
        $i=0;
        foreach($data as $row){
            $j=0;
            foreach($columns as $key1=>$value1){
                $columnvalue=$row["$key1"];
                $phpexcel->setActiveSheetIndex(0)->setCellValueExplicit(chr(65+$j).($i+2),$columnvalue);
                $j++;
            }
            $i++;
        }
        $phpexcel->setActiveSheetIndex(0);
        header('Expires: ' . date(DATE_RFC1123));
        header('Cache-Control: no-store, no-cache, must-revalidate,'. '  pre-check=0, post-check=0, max-age=0');
        header('Last-Modified: ' . date(DATE_RFC1123));
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="'.$fileName.'.xls"');
        // $objWriter = PHPExcel_IOFactory::createWriter($phpexcel, 'Excel5');
        $objWriter= \PHPExcel_IOFactory::createWriter($phpexcel,'Excel5');

        $objWriter->save('php://output');
        exit;
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
        }
        return array_merge($dept,$childs);
    }

    /**
     * 流程列表
     */
    public function actionFlowlist()
    {
        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);

        $where = [
            'and',
            ['=', 'isdel', 0],
        ];
        if ($this->_request['templateid']) {
            $where[] = ['=', 'templateid', $this->_request['templateid']];
        }
        if ($this->_request['templatename']) {
            $where[] = ['like', 'templatename', $this->_request['templatename']];
        }
        if ($this->_request['attr']) {
            $where[] = ['=', 'attr', $this->_request['attr']];
        }
        if (isset($this->_request['type']) && intval($this->_request['type'])>=0) {
            $where[] = ['=', 'type', $this->_request['type']];
        }
        $model = new WeixinTruckTemplate;
        $model = $model::find()->where($where);

        $total = $model->count();
        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
        $data = [];
        foreach($res as $item){
            if($item['uids']){
                $uids = explode(',',$item['uids']);
                $userids = $this->getUserinfo($uids,'in');
                $item['uids'] = implode(',',$userids);
            }
            $data[] = $item;
        }
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        $this->_result['data'] = $data;

        return $this->_result;
    }

    /**
     * 添加流程 
     */
    public function actionAddflow()
    {
        $values = $this->_request['values'];
        if ($values) {
            $model = new WeixinTruckTemplate(['scenario' => 'create']);
            $values['dids'] = implode(',',$values['dids']);
            $values['uids'] = implode(',',$values['uids']);

            $model->attributes = $values;
            $ruleResult = Tools::modelRules($model, 4000);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $this->_result['lastid'] = $model->id;
                    $action = '[车辆管理]流程新增';
                    $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，ID=" .$model->id . "，模板名称=" . $model->templatename . '。';
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
     * 修改流程 
     */
    public function actionUpdateflow()
    {
        $id = intval($this->_request['id']);
        if ($id) {
            $values = $this->_request['values'];
            if($values['dids']){
                $values['dids'] = implode(",",$values['dids']);
            }
            if($values['uids']){
                $values['uids'] = implode(",",$values['uids']);
            }
            $model = WeixinTruckTemplate::findOne($id);
            $model->scenario = 'update';
            $oldName = $model->templatename;
            $model->attributes = $values;
            $ruleResult = Tools::modelRules($model, 4001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '[车辆管理]流程修改';
                    $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，ID=" . $id . "，" . ($oldName != $model->templatename ? '流程名称由 ' . $oldName . ' 改为 ' . $model->templatename . '。' : $model->templatename);
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
     * 删除流程 
     */
    public function actionRemoveflow()
    {
        $id = intval($this->_request['id']);
        if ($id) {
            $model = WeixinTruckTemplate::findOne($id);
            $model->isdel = 1;
            if ($model->save()) {
                $action = '[车辆管理]流程删除';
                $remark = $action . "操作人=" . $this->_adminInfo['wxuserid'] . "，ID=" . $id . "";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 获取企业微信用户信息
     */
    private function getUserinfo($ids,$op='=')
    {
        $userinfo = WeixinOaUserinfo::find()->where([$op, 'id', $ids])->asArray()->all();
        $userids = [];
        foreach($userinfo as $row){
            $userids[] = $row['userid'];
        }
        return $userids;
    }
}