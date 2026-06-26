<?php

namespace app\modules\api\controllers;

use app\modules\api\models\WxOauserDepartment;
// use app\modules\api\models\WxSalaryBonus;
use app\modules\api\commons\ApiBase;
// use app\modules\api\commons\WxQyhJk;
use app\modules\api\commons\Tools;
use app\modules\api\commons\Uploader;
use app\modules\api\commons\ItemConfig;
use yii\helpers\ArrayHelper;

/**
 * 职员操作接口类
 */
class OauserController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WxOauser';
    protected $_orderBy = 'id desc';
    public $_db;
    public $where;
    protected $_config;
    protected $intDate = ['authorized_time','social_time','team_time','company_time','entrytime','retire_time','resign_time','birth','party_time','job_qualification_time2','job_qualification_time','work_time','curr_job_time','positions','graduation_time','party_birth'];//表中保存为int类型的日期字段
    protected $logPath;//日志文件保存位置
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }

    public function init()
    {
        parent::init();
        $this->_permissionDeny();
        $this->_db =\Yii::$app->db;
        $this->logPath = \Yii::$app->basePath . '/runtimes/logs/';

        $configClass = new ItemConfig();
        $this->_config = $configClass->oausers;
    }
    public function search(){
        $depId = isset($this->_request['depId']) ? $this->_request['depId'] : 0;
        $isChild = isset($this->_request['isChild']) ? $this->_request['isChild'] : 0;
        if($isChild){
            $depModel = new WxOauserDepartment();
            $depIds = $depModel->getChildIds($depId)['all'];
        }else{
            $depIds = [$depId];
        }
        $this->where = [
            'and',
            ['=', 'st', 1],
            ['in', 'departmentid', $depIds]
        ];

        $name = trim($this->_request['name'])!='' ? trim($this->_request['name']) : '';
        $mobile = trim($this->_request['mobile'])!='' ? trim($this->_request['mobile']) : '';
        $gender = trim($this->_request['gender'])!='' ? trim($this->_request['gender']) : '';
        $nation = trim($this->_request['nation'])!='' ? trim($this->_request['nation']) : '';
        $province = trim($this->_request['province'])!='' ? trim($this->_request['province']) : '';
        $birth_place = trim($this->_request['birth_place'])!='' ? trim($this->_request['birth_place']) : '';
        $school = trim($this->_request['school'])!='' ? trim($this->_request['school']) : '';
        $job_qualification = trim($this->_request['job_qualification'])!='' ? trim($this->_request['job_qualification']) : '';

        if($name)   $this->where[] = ['like', 'name', $name];//姓名
        if($mobile) $this->where[] = ['=', 'mobile', $mobile];//电话号码
        if($nation) $this->where[] = ['like', 'nation', $nation];//民族
        if($province) $this->where[] = ['like', 'province', $province];//籍贯
        if($birth_place) $this->where[] = ['like', 'birth_place', $birth_place];//出生地
        if($school) $this->where[] = ['like', 'school', $school];//毕业院校以及专业
        if($gender!=''&&$gender!='allEnum'){ 
            $gender = intval($gender);
            $all = count($this->_config['gender']);
            switch($gender){
                case 0:
                case 1:
                case 2:
                    $this->where[] = ['=', 'gender', $gender];
                    break;
                case $all: 
                    $this->where[] = ['in', 'gender', array_keys($this->_config['gender'])];

                    break;
            }
        }

        if($this->_request['class_positions']){//职务
            $this->where[] = ['in', 'class_positions', explode(",",$this->_request['position'])];

        }
        if($this->_request['employ_type']){//聘用形式
            $this->where[] = ['in', 'employ_type', explode(",",$this->_request['employ_type'])];
        }
        if($this->_request['record']&&$this->_request['record']!='allEnum'){
            $this->where[] = ['=', 'record', $this->_request['record']];
        }
        // echo $this->_request['record'];
        // var_dump($this->where);exit;
        if($this->_request['st']&&$this->_request['st']!='allEnum'){//状态
           // 选择在职1 ，退休2 retire_time ，离职为3 resign_time

            switch($this->_request['st']){
                case 1:
                    $this->where[] = ['=','resign_time',0];
                    $this->where[] = ['=','retire_time',0];
                    break;
                case 2:
                    $this->where[] = ['>','retire_time',0];
                    break;
                case 3:
                    $this->where[] = ['>','resign_time',0];
                    break;

            }
        }
        if($this->_request['age']){
            $temp = explode(",",$this->_request['age']);
            if($temp[0]){
                $old_st = strtotime(date("Y-12-31", strtotime("-".$temp[0]." year")));
                $this->where[] = ['<=', 'birth', $old_st];
            }
            if($temp[1]){
                $old_st = strtotime(date("Y-01-01", strtotime("-".$temp[1]." year")));
                $this->where[] = ['>=', 'birth', $old_st];
            }
        }
        //日期类搜索
        foreach($this->intDate as $value){
            if($this->_request[$value]){
                $temp = explode(",",$this->_request[$value]);
                $temp[0] && $this->where[] = ['>=', $value, strtotime($temp[0])];
                $temp[1] && $this->where[] = ['<=', $value, strtotime($temp[1])];
                // if($value=='curr_job_time'){
                //     var_dump($this->where);exit;
                // }
            }
        }

        if($job_qualification){
            $qUserid = (new \yii\db\Query())->select('user_id')->from('weixin_leave_qualification')->where(['and',[ '=' ,'st', 1],['in', 'job_qualification', $job_qualification]])->groupBy('user_id')->all();

            // $qUserid = $this->_db->createCommand("select user_id from weixin_leave_qualification 
            // where st = 1 and job_qualification in('$job_qualification') group by user_id")->queryAll();
            
            $this->where[] = $qUserid ? ['in', 'id', $job_qualification]:['=', 'id', 0];
        }
        
    }
    //获取页面初始化数据
    public function actionGetValueEnum(){
        $data['dateColumn'] = $this->intDate;
        //职务
        foreach($this->_config['classPositions'] as $key=>$value){
            $data['positionEnum'][$key] = ['text'=>$value];
        }
        foreach($this->_config['gender'] as $key=>$value){
            $data['genderEnum'][$key] = ['text'=>$value];
        }
        // $data['genderEnum'][count($this->_config['gender'])] = ['text'=>'全部'];

        foreach($this->_config['staffRecord'] as $key=>$value){
            $data['recordEnum'][$key] = ['text'=>$value];
        }
        // $data['recordEnum'][0] = ['text'=>'全部'];

        foreach($this->_config['staffSort'] as $key=>$value){
            $data['employTypeEnum'][$key] = ['text'=>$value];
        }
        foreach($this->_config['jobStateArr'] as $key=>$value){
            $data['stEnum'][$key] = ['text'=>$value];
        }
        // $data['stEnum'][0] = ['text'=>'全部'];

        //job_qualification
        $qualification = (new \yii\db\Query())
                            ->select('a.job_qualification')
                            ->from('weixin_leave_qualification a')
                            ->join('LEFT JOIN', 'weixin_oauser_userinfo b', 'b.id = a.user_id AND b.st = 1 AND b.STATUS = 1')
                            ->where(['=', 'a.st', 1])
                            ->groupBy('job_qualification')
                            ->all();

        // $qualification = $this->_db->createCommand("SELECT
        //                         a.job_qualification
        //                     FROM
        //                         weixin_leave_qualification a
        //                         LEFT JOIN weixin_oauser_userinfo b ON b.id = a.user_id 
        //                         AND b.st = 1 
        //                         AND b.STATUS = 1 
        //                     WHERE
        //                         a.st = 1 
        //                     GROUP BY
        //                         a.job_qualification")->queryAll();
        foreach($qualification as $key=>$value){
            $data['qualificationEnum'][$value['job_qualification']] = ['text'=>$value['job_qualification']];
        }

        $this->_result['data'] = $data;
        return $this->_result;
    }
    //列表数据
    public function actionIndex()
    {
        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        // $this->yunwei();
        // $this->yunWeiQualification();
        $this->search();
        $model = $this->modelClass;
        $model = $model::find()->where($this->where);
        $total = $model->count();
        $this->_orderBy = " displayorder desc,is_leader desc,level desc,id asc ";

        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
        if($res){
            foreach($res as $key=>$item){
                $res[$key]['st'] = $item['retire_time']>0 ? 2:($item['resign_time']>0 ? 3:1);
               
                //$this->_config['classPositions']
                //日期格式转换
                foreach($this->intDate as $v){
                    $res[$key][$v] = $item[$v] ==0 ? 0:date("Y-m",$item[$v]);
                }
            }
        }
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        $this->_result['data'] = $res;
        
        
        return $this->_result;
    }
    /**
     * 数据导出
     */
    public function actionExport(){
        $fieldColumns = [
            'no', 'name', 'position', 'gender', 'nation', 'birth', 'province', 'birth_place', 'work_time',
            'party_time', 'record', 'school',
            'graduation_time', 'job_qualification', 'job_qualification_time', 'job_qualification_time2', 'curr_job_time', 'positions', 
            'authorized_time', 'social_time', 'team_time', 'company_time', 'entrytime', 'resign_time', 'employ_type', 'mobile', 'email', 'mark'
        ];
        $fieldName = [
            '序号', '姓名', '现部门以及职务', '性别', '民族', '出生年月', '籍贯', '出生地',
            '参加工作时间', '入党年月', '学历', '毕业院校以及专业', '毕业时间', '专业技术职务资格', '专业技术确认时间', '专业技术任聘时间', '任现职时间', '本职级确认时间', '入编时间', '社聘时间', '转集体编制时间',
            '公司聘时间', '试用时间', '离职时间', '聘用形式', '电话', 'email', '备注'
        ];
        $tableColumn = array_combine($fieldColumns, $fieldName);
        $model = $this->modelClass;
        $this->search();
        $model = $model::find()->where($this->where);
        $data = $model->orderBy($this->_orderBy)->asArray()->all();

        if(count($data)){
            foreach($data as $key=>$item){
                $data[$key]['no'] = $key+1;
                    $data[$key]['st'] = $item['st'] = $item['retire_time']>0 ? 2:($item['resign_time']>0 ? 3:1);
                    $data[$key]['st'] = $this->_config['jobStateArr'][$item['st']];

                    $data[$key]['class_positions'] = $this->_config['classPositions'][$item['class_positions']];

                    $data[$key]['gender'] = $this->_config['gender'][$item['gender']];

                    $data[$key]['record'] = $this->_config['staffRecord'][$item['record']];

                    $data[$key]['employ_type'] = $this->_config['staffSort'][$item['employ_type']];
                    //日期格式转换
                    foreach($this->intDate as $v){
                        $data[$key][$v] = $item[$v] ==0 ? "":date("Y-m",$item[$v]);
                    }
                }
        }
        $this->toBlob($data,$tableColumn,date('YmdH'));
    }
    //移动成员
    public function actionCut(){
        $ids = $this->_request['infoIds'];
        if(!empty($ids)){
            $toDepId = intval($this->_request['toId']);
            // $dep = $this->_db->createCommand("select id,name from weixin_oauser_department where id = $toDepId")->queryOne();

            $dep = (new \yii\db\query())
                    ->select('id,name')
                    ->from('weixin_oauser_department')
                    ->where(['=', 'id', $toDepId])->one();

            if(empty($dep)){
                $this->_result = Tools::wrongRules(1000, '部门未找到'); 
                return $this->_result;

            }
            $data['departmentid'] = $dep['id'];
            $data['departmentname'] = $dep['name'];
            $flag = $this->modelClass::updateAll($data,['in', 'id', $ids]);
           
            $action = '修改';
            $remark = $action . "职员管理-移动成员：id=".implode(",",$ids);
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
          

            
        }else{
            $this->_result = Tools::wrongRules(1000, '参数错误'); 
        }
        return $this->_result;
    }
    //更新数据
    public function actionUpdate(){
        $id = intval($this->_request['id']);
        if ($id) {
            $model = $this->modelClass::findOne($id);
            // $model->scenario = 'update';
            $data = $this->_request['values'];//strtotime($pay_time)
            //查找用户
            foreach($this->intDate as $k=>$field){                
                if($data[$field]){
                    $data[$field] = strtotime($data[$field]);
                }else{
                    $data[$field] = 0;
                }
            }
            $model->attributes = $data;
            $ruleResult = Tools::modelRules($model, 4001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '修改';
                    $remark = $action . "职员管理-编辑：id=$id";
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
    //新增职员
    public function actionCreate(){
        $data = $this->_request['values'];
        //查找用户
        foreach($this->intDate as $k=>$field){                
            if($data[$field]){
                $data[$field] = strtotime($data[$field]);
            }else{
                $data[$field] = 0;
            }
        }
        //设置部门
        // $dep = $this->_db->createCommand("select id,name from weixin_oauser_department where `name` = '".$data['departmentname']."'")->queryOne();
        $dep = (new \yii\db\query())
                    ->select('id,name')
                    ->from('weixin_oauser_department')
                    ->where(['=', 'name', $data['departmentname']])->one();
                    
        // if(empty($dep)){
        // }
        $data['departmentid'] = $dep['id'];
        $data['departmentname'] = $dep['name'];
        $model = new $this->modelClass(['scenario' => 'create']);
        $model->attributes = $data;
        $ruleResult = Tools::modelRules($model, 4001);
        if ($ruleResult === true) {
            if ($model->save()) {
                if(!empty($data['job_qualification'])){
                    $quali['job_qualification_time'] = $data['job_qualification_time'];
                    $quali['job_qualification'] = trim($data['job_qualification']);
                    $quali['job_qualification_time2'] = $data['job_qualification_time2'];
                    $quali['user_id'] = $model->id;
                    $quali['updated'] = date("Y-m-d H:i:s");
                    $this->_db->createCommand()->insert('weixin_leave_qualification', $quali)->execute();
                }
                $action = '新增';
                $remark = $action . "职员管理-新增：id=$model->id";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result['errorCode'] = $ruleResult['errorCode'];
            $this->_result['errorMessage'] = $ruleResult['errorMessage'];
        }
        return $this->_result;
    }
    //删除数据
    public function actionDelete(){
        if ($this->_request['id']) {
            $ids = explode(',', $this->_request['id']);
            $model = $this->modelClass;
            $models = $model::find()->where(['in', 'id', $ids])->all();
            $this->modelClass::updateAll(['st'=>0, 'changed' => 1], "id in(".implode(',', $ids).")");
            $names = array_column($models,'name');
            if ($names) {
                $action = '删除';
                $remark = $action . "职员管理-删除数据：" . implode(',', $names) . "";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }
    //导入数据
    public function actionImport(){
        if (isset($_FILES['upfile'])) {
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
                $excelData = Tools::getExcelData($localFile);
                // var_dump($excelData);exit;
                $configClass = new ItemConfig();

                $excelData = $configClass->getExcelDataABC($localFile);
                // var_dump($excelData);exit;
                if(count($excelData)>0){
                    $totalSuccess = 1;
                    foreach ($excelData as $key=>$row){
                        if($key==1)
                            continue;
                        $msg = '';
                        $name =  str_replace([' ','\''],'',trim($row['A']));//姓名
                        $updateData = $this->setImportColumnByImport($row);//excel数据转换成表字段
                        //判断字符格式是否正确
                        if(empty($name)){
                            $msg .= '姓名为空,';
                        }
                        if($updateData['record']===false){
                            $msg .= $row['J'].'学历不匹配,';
                        }
                        if(!is_numeric($updateData['mobile']) || !$this->isMobile($updateData['mobile'])){
                            $msg .= '电话号码不不正确,';
                        }

                        //获取部门信息   及  对数据进行处理
                        $ExcelB = explode('||',$updateData['position']);
                        $updateData['position'] = $ExcelB[0];// 职务以及部门名称

                        $this->writeLog('判断是否存在该用户');
                        //判断是否存在该用户
                        list($updateData,$id,$msg) = $this->getUsersByImport($name,$ExcelB[1],$updateData,$msg);

                        //日期字段处理
                        $timeItems=[
                            'birth'=>'出生日期', 'work_time'=>'工作时间', 'job_qualification_time'=>'专业技术确认时间',
                            'job_qualification_time2'=>'专业技术聘任时间', 'curr_job_time'=>'任现职年月','graduation_time'=>'毕业时间',
                            'party_time'=>'入党年月', 'positions'=>'本职级认定时间', 'authorized_time'=>'入编时间', 'social_time'=>'社聘时间',
                            'team_time'=>'转集体编制时间', 'company_time'=>'公司聘时间','retire_time'=>'退休时间','resign_time'=>'辞职时间','entrytime'=>'入职时间',
                        ];
                        foreach($timeItems as $key1=>$kName){
                            if($updateData[$key1]){
                                $ret = $this->checkDate($updateData[$key1]);
                                if($ret['code']==0){
                                    $msg .= $kName.'格式不正确,';
                                }else{
                                    $updateData[$key1] = strtotime($ret['data']);
                                }
                            }else{
                                $updateData[$key1] = 0;
                            }
                        }
                        if(empty($msg)){ //验证通过
                            $this->writeLog('验证通过');
                            $quali = [];

                            //职务资格处理
                           $this->handleQualicationByImport($updateData,$id);

                            $this->writeLog('准备更新数据');

                            //更新数据
                            unset($updateData['mobile']);
                            $updateData['st'] = 1;
                            // var_dump($updateData);exit;
                            $this->_db->createCommand()->update('weixin_oauser_userinfo', $updateData, "id = $id")->execute();

                            $this->_operationlog(['catalog' => 'update', 'remark' => '更新职员id：'.$id]);

                            $this->writeLog('更新数据结束,id = '.$id);

                        }else{
                            $totalSuccess = 0;
                            //报错处理 验证未通过 weixin_leave_user_msg
                            $this->addError('职员信息导入',$name.'导入数据第'.($key+1).'条:'.$msg);
                          
                        }
                    } //end foreach
                    // echo $msg;exit;
                    switch ($totalSuccess){
                        case 1:
                            break;
                        case 0:
                            $this->_result = Tools::wrongRules(1000, '部分导入失败');

                            break;
                    }
                }//end if(count($excelData)>0)
                else{
                    $this->_result = Tools::wrongRules(1000, '文件内容为空，导入失败');
                }
            }
        }else{
            $this->_result = Tools::wrongRules(1000, '文件获取错误');
        }

        return $this->_result;
    }
    //导入获取用户信息 导入操作
    protected function getUsersByImport($name,$depName,$updateData,$msg){
         //通过用户姓名查找职员信息
         $getUsers = $this->_db->createCommand("select id,mobile,name,gender,email,departmentid,position,status from weixin_oauser_userinfo where `name` = '".$name."' and st=1")->queryAll();
           //通过用户电话查找职员信息
         $getUsersM = $this->_db->createCommand("select id,mobile,`name`,gender,email,departmentid,`position`,status from weixin_oauser_userinfo where `mobile` = '".$updateData['mobile']."' and st=1")->queryAll();
         //通过部门名称查找部门信息
         $deNameId = $this->_db->createCommand("select id,name from weixin_oauser_department where `name` = '".$depName."'")->queryOne();
         if (empty($deNameId)) {
             $msg .= '部门名称不正确,'; //部门未找到 -报错
         }else{
             $updateData['departmentid'] = $deNameId['id'];
             $updateData['departmentname'] = $deNameId['name'];
         }
        
         if (!$getUsers) {//未找到名字为$name 的职员
             if (!$getUsersM && empty($msg)) {

                 $resInsert = $this->_db->createCommand()->insert('weixin_oauser_userinfo', array('name'=>$name,'mobile'=>$updateData['mobile'],'status'=>1,'st'=>1,'enable'=>1))->execute();
                 $id = $this->_db->getLastInsertID();
                 $this->_operationlog(['catalog' => 'create', 'remark' => '导入职员ID：'.$id]);

             }else{//存在未填，如果msg有报错，getUsersM没有数据呢

                 foreach ($getUsersM as $item){//通过电话号码查找用户id
                     if($item['mobile'] == $updateData['mobile']){
                         $id = $item['id'];
                     }
                 }

             }
         }else if(count($getUsers)==1){//只找到一条$name的用户信息
             $id = $getUsers[0]['id'];
            //  $cidss = $getUsers[0]['id'];
            //  if ($getUsersM) {
            //      $msg .= '存在该电话号码,';
            //  }else{
            //      $resIn = Yii::app()->db1->createCommand()->insert('weixin_oauser_userinfo', array('name'=>$name,'mobile'=>$updateData['mobile'],'status'=>1,'st'=>1,'enable'=>1));
            //      $resInId = yii::app()->db1->getLastInsertID();
            //      $this->_action = 'create';
            //      $this->_actionRemark = '导入职员ID：'.$resInId;
            //      $this->_actionLog();
            //  }
         }

         if(count($getUsers)>1){//存在多个用户信息 -报错
             $msg .= '存在多个用户,';
         }

         return [$updateData,$id,$msg];
    }
    //excel数据转换成表字段 导入操作
    protected function setImportColumnByImport($row){
        $updateData = [];
        $updateData['position'] =  str_replace([' ','\''],'',trim($row['B']));//现部门以及职务
        $updateData['class_positions'] = $this->search_arr($this->_config['classPositions'],str_replace(' ','',trim($row['B'])));
        $updateData['class_positions'] = $updateData['class_positions'] ? $updateData['class_positions']:0;
        $updateData['gender'] = str_replace([' ','\''],'',trim($row['C']));
        $updateData['gender'] = $updateData['gender']=='男'?1:2;//性别
        $updateData['nation'] = str_replace([' ','\''],'',trim($row['D']));//民族
        $updateData['birth'] = str_replace([' ','\''],'',trim($row['E']));//出生年月
        $updateData['province'] = str_replace([' ','\''],'',trim($row['F']));//籍贯
        $updateData['birth_place'] = str_replace([' ','\''],'',trim($row['G']));//出生地
        $updateData['work_time'] = trim($row['H']);//参加工作时间(日期格式,不含中文)
        $updateData['party_time'] = trim($row['I']);//入党年月

        $updateData['record'] = array_search(str_replace(' ','',trim($row['J'])),$this->_config['staffRecord']);//学历
        $updateData['school'] = str_replace([' ','\''],'',trim($row['K']));//毕业院校以及专业
        $updateData['graduation_time'] = str_replace([' ','\''],'',trim($row['L']));//毕业时间
        $updateData['job_qualification'] = str_replace([' ','\''],'',trim($row['M']));//专业技术职务资格
        $updateData['job_qualification_time'] = str_replace([' ','\''],'',trim($row['N']));//专业技术确认时间
        $updateData['job_qualification_time2'] = str_replace([' ','\''],'',trim($row['O']));//专业技术聘任时间
        $updateData['curr_job_time'] = str_replace([' ','\''],'',trim($row['P']));//任现职年月
        $updateData['positions'] = str_replace([' ','\''],'',trim($row['Q']));//本职级
        $updateData['authorized_time'] = str_replace([' ','\''],'',trim($row['R']));//入编（或调入）时间
        $updateData['social_time'] = str_replace([' ','\''],'',trim($row['S']));//社聘时间
        $updateData['team_time'] = str_replace([' ','\''],'',trim($row['T']));//转集体编制时间
        $updateData['company_time'] = str_replace([' ','\''],'',trim($row['U']));//公司聘时间
        $updateData['mobile'] = str_replace([' ','\''],'',trim($row['V']));//联系方式
        $updateData['mark'] = str_replace([' ','\''],'',trim($row['W']));//备注

        // 由于导入的excel表格中没有这个字段  后台也已经设置了 聘用形式  暂时不用该字段
        // $updateData['employ_type'] = array_search(str_replace(' ','',trim($row['X'])),$this->staffSort);//聘用形式
        
        // $updateData['employ_type']   = str_replace([' ','\''],'',trim($row['X']));//聘用形式
        $updateData['retire_time']   = str_replace([' ','\''],'',trim($row['Y']));//退休时间
        $updateData['resign_time']   = str_replace([' ','\''],'',trim($row['Z']));//辞职时间
        $updateData['resign_reason'] = str_replace([' ','\''],'',trim($row['AA']));//辞职原因
        $updateData['entrytime']     = str_replace([' ','\''],'',trim($row['AB']));//入职时间
        return $updateData;
    }
     //记录错误数据信息  导入操作
     protected function addError($title,$msg){
        $this->_db->createCommand()->insert('weixin_error_record',
        ['opt_name' => $this->_adminInfo['realname'], 'title' => $title,'msg' => $msg, 'tp' => 4, 'created' => date("Y-m-d H:i:s")])->execute();
   
    }
    //职务资格处理 导入操作
    protected function handleQualicationByImport($updateData,$id){
        if(!empty( $updateData['job_qualification_time2'])&&!empty( $updateData['job_qualification_time'])&&!empty( $updateData['job_qualification'])){
            $quali['job_qualification_time'] = $updateData['job_qualification_time'];
            $quali['job_qualification'] = trim($updateData['job_qualification']);
            $quali['job_qualification_time2'] = $updateData['job_qualification_time2'];
            $quali['user_id'] = $id;
            $quali['updated'] = date("Y-m-d H:i:s");
            $this->writeLog('技术信息保存或更新操作');
            $in_up_res = $this->_db->createCommand("select * from weixin_leave_qualification where user_id=".$id." and job_qualification='".$quali['job_qualification']."' limit 0,1")->queryRow();
            if ($in_up_res) {
                $this->_db->createCommand()->update('weixin_leave_qualification', $quali, 'id='.$in_up_res['id'])->execute();
                $this->_operationlog(['catalog' => 'update', 'remark' => '更新专业技术职务资格id：'.$in_up_res['id']]);
            }else{
                $this->_db->createCommand()->insert('weixin_leave_qualification', $quali)->execute();
                $this->_operationlog(['catalog' => 'create', 'remark' => '添加专业技术职务资格id：'.$in_up_res['id']]);
            }
        }
    }
    //获取职务资格信息
    public function actionGetQualification(){
        if($this->_request['userId']){
            $data = $this->_db->createCommand("select id,job_qualification_time,job_qualification_time2,st,job_qualification from weixin_leave_qualification where st = 1 and user_id = ".$this->_request['userId']." order by id desc ")->queryAll();
            if($data){
                foreach($data as $key=>$value){
                    $data[$key]['job_qualification_time'] = $value['job_qualification_time'] ? date("Y-m",$value['job_qualification_time']) :null;
                    $data[$key]['job_qualification_time2'] = $value['job_qualification_time2'] ? date("Y-m",$value['job_qualification_time2']) :null;
                }
            }
            $this->_result['data'] = $data;
           
        }
        return $this->_result;
    }
    
    //职务资格信息修改
    public function actionSaveQualification(){
        $userId = intval($this->_request['id']);
        $data = $this->_request['values'];
        if ($userId) {
            $list = $data['list'];
            
            $currentIds = $this->_db->createCommand("select id from weixin_leave_qualification where user_id = $userId and st = 1")->queryAll();
            $updateRow = [];//修改修改的字段
            $deleteRow = [];//修改作废的字段
            $addRow = [];//修改新增的字段
            if($list){
                foreach($list as $item){
                    $id = intval($item['id']);
                    unset($item['id']);
                    $item['job_qualification_time'] = $item['job_qualification_time'] ? strtotime($item['job_qualification_time']):0;
                    $item['job_qualification_time2'] = $item['job_qualification_time2'] ? strtotime($item['job_qualification_time2']):0;
                    ksort($item);
                    if($id){//修改
                        $updateRow[$id] = $item;
                    }else{
                        $addRow[] = $item;
                    }
                }

            }
            $updateRow = $this->array_unique_fb($updateRow);//去重
            $laster = [];


            if($updateRow){//更新的数据
                foreach($updateRow as $rowId=>$row){

                    if(!$laster||$row['job_qualification_time']>$laster['job_qualification_time']){
                        $laster = $row;
                    }
                    $row['st'] = 1;
                    $row['updated'] = date("Y-m-d H:i:s");
                    $this->_db->createCommand()->update('weixin_leave_qualification', $row,"id=$rowId")->execute();
                }
            }



            $deleteRow = array_diff(array_column($currentIds,'id'),array_keys($updateRow));//去重 重复的相同的数据作废掉
            if($deleteRow){//删除的数据
                $this->_db->createCommand()->update('weixin_leave_qualification', ['st'=>0,'updated'=> date("Y-m-d H:i:s")],"id in (".implode(",",$deleteRow).")")->execute();
            }



            if($addRow){//新增数据
                foreach($addRow as $rowId=>$row){

                    if(!$laster||$row['job_qualification_time']>$laster['job_qualification_time']){
                        $laster = $row;
                    }
                    $row['st'] = 1;
                    $row['updated'] = date("Y-m-d H:i:s");
                    $row['user_id'] = $userId;
                    $this->_db->createCommand()->insert('weixin_leave_qualification', $row)->execute();
                }
            }
            $action = '修改';
            $remark = $action . "职员管理-职务资格编辑:修改row-id:".implode(",",array_keys($updateRow))." 删除row-id:".implode(",",$deleteRow)." 新增条数：".count($addRow);
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);



            // 修改主表数据
            if(!$laster){//取得的证书确认时间最靠近当前
                $laster['job_qualification_time'] =0;
                $laster['job_qualification_time2'] =0;
                $laster['job_qualification'] ='';
            }
            $model = $this->modelClass::findOne($userId);//更新最新的资格证书
            if($model){
                $model->attributes = $laster;
                $model->save();
            }
           

        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;

    }
    //运维字段类型 先改表字段类型，先运行代码
    public function yunWei(){
        return;
        $users = $this->_db->createCommand("select * from weixin_oauser_userinfo_copy1 ")->queryAll();
        if($users){
            foreach($users as $key=>$user){
                if($user){
                    $data = [];
                   
                        //字段修改
                        $colunm = ['authorized_time','social_time','team_time','company_time','entrytime','retire_time','resign_time','birth','party_time','job_qualification_time2','job_qualification_time','work_time','curr_job_time','positions','graduation_time','party_birth'];
                        foreach($colunm as $k){
                            $data[$k] = ($user[$k]=='0000-00-00'||$user[$k]==''||$user[$k]==0) ? 0:strtotime($user[$k]);
                        }
                  
                    //更新数据
                    $ret = $this->_db->createCommand()->update('weixin_oauser_userinfo', $data, "id =".$user['id'])->execute();

                }
            }
        }

    }
    //技术资格运维
    public function yunWeiQualification(){
        return;
        $users = $this->_db->createCommand("select * from weixin_leave_qualification_copy1 ")->queryAll();
        if($users){
            foreach($users as $key=>$user){
                if($user){
                    $data = [];
                
                    $data['job_qualification_time'] = ($user['job_qualification_time']=='0000-00-00'||$user['job_qualification_time']==''||$user['job_qualification_time']==0) ? 0:strtotime($user['job_qualification_time']);
                    $data['job_qualification_time2'] = ($user['job_qualification_time2']=='0000-00-00'||$user['job_qualification_time2']==''||$user['job_qualification_time2']==0) ? 0:strtotime($user['job_qualification_time2']);

                    //更新数据
                    $ret = $this->_db->createCommand()->update('weixin_leave_qualification', $data, "id =".$user['id'])->execute();

                }
            }
        }

    }

    //多维数据去重
    protected function array_unique_fb($array2D){
        $temp = [];
        $temp2 = [];
        $fieldKey = [];
        
        foreach($array2D as $key=>$value){
            if(count($fieldKey)==0){
                $fieldKey = array_keys($value);

            }
            $value = implode(",",$value);
            $temp[$key] = $value;
        }
        $temp  = array_unique($temp);
        foreach($temp as $key=>$value){
            $array = explode(",",$value);
            foreach($fieldKey as $k=>$v){
                $temp2[$key][$v] = $array[$k];
            }
            
        }
        return $temp2;
    }
    //数据导出转二进制
    protected function toBlob($data=[],$columns=[],$fileName=''){
        // Yii::$enableIncludePath = false;

        // require_once str_replace("\\","/",dirname(dirname(dirname(dirname(__FILE__)))).'/vendor/PHPExcel/Classes/PHPExcel.php');
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
        $configClass = new ItemConfig();

        foreach($columns as $key1=>$value1){
            $index = $configClass::stringFromColumnIndex($i);//解决导出数据大于26列

            $phpexcel->setActiveSheetIndex(0)->setCellValue($index.'1', $value1);
            $i++;
        }
        $i=0;
        foreach($data as $row){
            $j=0;
            foreach($columns as $key1=>$value1){
                $columnvalue=$row["$key1"];
                $phpexcel->setActiveSheetIndex(0)->setCellValueExplicit($configClass::stringFromColumnIndex($j).($i+2),$columnvalue);
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
        $objWriter= \PHPExcel_IOFactory::createWriter($phpexcel,'Excel5');

        $objWriter->save('php://output');
        exit;
	}

    protected function isMobile($mobile) {
        if (!is_numeric($mobile)) {
            return false;
        }
        return preg_match('#^1[3,4,5,7,6,8,9]{1}[\d]{9}$#', $mobile) ? true : false;
    }
        /** 日期检测 */
    protected function checkDate($date){
        //        $date = str_replace(['年','月','日'],'-',$date);//mb_ereg_replace
        $date = str_replace('月','-',$date);//mb_ereg_replace
        $date = str_replace('年','-',$date);//mb_ereg_replace
        $date = str_replace('日','-',$date);//mb_ereg_replace
        $patten = "/^(\d{4})[\-\.](0?[1-9]|1[012])[\-\.](0?[1-9]|[12][0-9]|3[01])?$/";//2019-09-08 三位数
        $perg =[];
        preg_match($patten, $date,$perg);
        if(empty($perg)){
            //匹配两位
            $patten = "/^(\d{4})[\-\.](0?[1-9]|1[012])?$/";
            preg_match($patten, $date,$perg); //2018-03
            if(empty($perg)){
                return ['code'=>0,'data'=>''];
            }
        }
        if(count($perg)==4){
            $date = $perg[1].'-'.$perg[2].'-'.$perg[3];
        }else if(count($perg)==3){
            $date = $perg[1].'-'.$perg[2].'-'.'01';
        }else{
            return ['code'=>0,'data'=>''];
        }

        return ['code' => 1, 'data' =>$date];            
    }
    /**
     * 在数组中模糊搜索给定的值
     * @param $string
     * @param $search
     * @param int $invert 倒置搜索
     * @param int $first
     * @return bool|int|string
     */
    private function search_arr($string,$search,$invert=0,$first=1){
        $keyId = false;
        foreach($string as $key=>$values ){
            $ret = $invert==1 ? strstr( $values , $search ):strstr( $search , $values );
            if ( $ret!== false ){
                if($first==1)
                    return $key;
                $keyId = $key;
            }
        }
        return $keyId;
    }
    //日志记录
    private function writeLog($log)
    {
        if (!file_exists($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }
        file_put_contents($this->logPath . 'log.txt', date('Y-m-d H:i:s => ') . $log . "\r\n", FILE_APPEND);
    }
}