<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\commons\Tools;
use app\modules\api\commons\ItemConfig;
use app\modules\api\models\WxDepartment;
use Yii;

/**
 * 工资操作接口类
 */
class SalaryController extends ApiBase
{
    protected $_agentId = 1000022;
    public $modelClass = 'app\modules\api\models\WxSalary';
    protected $_orderBy = 'id desc';
    protected $_configClass;
    public $tableColumn ;
    public $_db;
    public $where;

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }

    public function init()
    {
        parent::init();
        $this->_db =\Yii::$app->db;
        $this->_configClass = new ItemConfig();
        $this->tableColumn = $this->_configClass->salary['tableColumn'];

    }
    protected function search(){
        $depId = isset($this->_request['depId']) ? $this->_request['depId'] : 0;
        $name = isset($this->_request['username']) ? $this->_request['username'] : "";
        $mobile = isset($this->_request['mobile']) ? $this->_request['mobile'] : "";
        $sendSt = isset($this->_request['send_st']) ? $this->_request['send_st'] : "";
        $signSt = isset($this->_request['sign_st']) ? $this->_request['sign_st'] : "";

        $startTime = isset($this->_request['startTime'])?strtotime($this->_request['startTime']):0;
        $endTime = isset($this->_request['endTime'])?strtotime($this->_request['endTime']):0;
        
        $this->where = [
            'and',
            ['=', 'st', 1]
        ];
        if($depId){
            $authData = $this->getUserAuth();
            $childDep = $this->getMergeChildDepartments([$depId]);
            $childDep = array_intersect($childDep,$authData['departments']);
            $this->where[] = ['in', 'dep_id', $childDep];
        }
        if($startTime){
            // $pay_time = $pay_time . '-01 00:00:00';
            $this->where[] = ['>=','pay_time',$startTime];
        }
        if($endTime){
            // $pay_time = $pay_time . '-01 00:00:00';
            $this->where[] = ['<=','pay_time',$endTime];
        }
        if($name){
            $this->where[] = ['like','username',"$name"];
        }
        if($mobile){
            $this->where[] = ['like','mobile',"$mobile"];
        }
        if($sendSt!=''){
            $this->where[] = ['=','send_st',"$sendSt"];
        }
        if($signSt!=''){
            $this->where[] = ['=','sign_st',"$signSt"];
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
      
        $this->search();
        // var_dump($this->where);exit;
        $model = $this->modelClass;
        $model = $model::find()->where($this->where);
        $total = $model->count();
        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();

        // // 获取SQL查询语句
        // $sql = $model->createCommand()->getRawSql();
        // echo $sql;
        
        if($res){
            foreach($res as $key=>$item){
                $res[$key]['pay_time'] = date("Y-m",$item['pay_time']);
                foreach ($item as $k=>$v){
                    if(!in_array($k,['col_a','mobile','dep_id','username','id','created','pay_time','st','sign_st','send_st'])){
                        $res[$key][$k] = $v==0 ? '':floatval($v);
                    }
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
     * 重写update的业务实现动作
     */
    public function actionUpdate()
    {
        $id = intval($this->_request['id']);
        if ($id) {
            $model = $this->modelClass::findOne($id);
            // $model->scenario = 'update';
            $data = $this->_request['values'];//strtotime($pay_time)
            $data['pay_time'] = strtotime($data['pay_time']);
            //查找用户

            $model->attributes = $data;
            $ruleResult = Tools::modelRules($model, 4001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '修改';
                    $remark = $action . "工资管理-编辑：id=$id";
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
     // 签发
     public function actionSign()
     {
        if($this->_request['id']){
            $this->where = ['=', 'id', $this->_request['id']];
            $where = "id='".$this->_request['id']."'";
        }else if ($this->_request['depId']) {
             $payTime = strtotime($this->_request['pay_time']);
             $auth = $this->getUserAuth();
             $depts = array_intersect($this->getMergeChildDepartments([$this->_request['depId']]),$auth['childdepts']);
             $this->where = 
             [   'and',
                 ['=', 'opt_id', $this->_adminInfo['id']],
                 ['=', 'pay_time', $payTime],
                 ['in', 'dep_id', $depts],
             ];
             foreach($this->where as $w){
                 if(is_array($w)){
                    if($w[0]=='in'){
                        $where[] = $w[1]." in (".implode(',',$w[2]).") ";
                    }else{
                        $where[] = $w[1]." = '".$w[2]."' ";
                    }
                 }
             }
             $where = implode(' and ', $where); 

         } else {
            return Tools::wrongRules(1000, '参数错误');
         }
         $updata = [];
         $notify = $this->_request['notify'] ? 1:0;
         $st = $this->_request['st'] ? 1:0;
         if($notify){
            $st = 1;
            $updata['send_st'] = $notify;
         }
         $updata['sign_st'] = $st;
         $model = $this->modelClass;
         $models = $model::find()->where($this->where)->all();
         $this->modelClass::updateAll($updata, $where);
         
         $names = array_column($models,'username');
         if ($names) {
            $action = $st ?'签发':'取消签发';
            $remark = $action . "工资管理-签发数据：" . implode(',', $names) . "";
             $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
         }

          //通知用户
         if ($notify) {
            !$payTime && $payTime = $models[0]['pay_time'];
            $this->sendWeixin($payTime,$models);
         }
         return $this->_result;
     }
     protected function sendWeixin($payTime,$models){
         $content = '您有一条最新工资单,<a href="https://fzrb.fznews.com.cn/index.php?r=qiyehao/salary/index&salaryTime='.date('Y-m',$payTime).'">请点击查看</a>';;

         $sendResult = WxQyhJk::sendMessage($this->_agentId,implode("|",array_column($models,'userid')),$content);
 
         //返回结果出来
         $flag = 1;
         if (!$sendResult['errorMessage']) {
             $flag = 0;
         } 
         $action = '通知';
         $remark = $action . "工资管理-签发数据（".date('Y-m',$payTime)." 月份工资通知成功）：" . implode(',', array_column($models,'name')) . "";

         $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
     }
    public function actionSign1()
    {
        return ;
        if ($this->_request['id']) {
            $ids = explode(',', $this->_request['id']);
            $model = $this->modelClass;
            
            $models = $model::find()->where(['and',['in', 'id', $ids],['=','opt_id',$this->_adminInfo['id']]])->all();
            $this->modelClass::updateAll(['sign_st'=>$this->_request['st']], "id in(".implode(',', $ids).") and opt_id = '".$this->_adminInfo['id']."'");
            
            //通知用户
            if($this->_request['st']){

                $payTime = $models[0]['pay_time'];
                $content = '您有一条最新工资单,<a href="https://fzrb.fznews.com.cn/index.php?r=qiyehao/salary/index&salaryTime='.date('Y-m',$payTime).'">请点击查看</a>';;
                $sendResult = WxQyhJk::sendCode(1000022,implode("|",array_column($models,'userid')),$content);

                //更改通知状态

            }
          
           
            $names = array_column($models,'username');
            $time = array_column($models,'pay_time');
            if ($names) {
                $action = $this->_request['st'] ?'签发':'取消签发';
                $remark = $action . "工资管理-签发数据：" . implode(',', $names) . "";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
            if ($this->_request['st']&&!$sendResult['errorMessage']) {
                $action = '通知';
                $remark = $action . "工资管理-签发数据（$payTime 月份工资通知成功）：" . implode(',', $names) . "";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            } else if($this->_request['st']){
                $this->_result['errorMessage'] = $sendResult['errorMessage'];
            }

        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }
        /**
     * 重写delete的业务实现动作
     */
    public function actionDelete()
    {
        // var_dump($ids);exit;
        if ($this->_request['id']) {
            $ids = explode(',', $this->_request['id']);
            $model = $this->modelClass;
            $models = $model::find()->where(['in', 'id', $ids])->all();
            $this->modelClass::updateAll(['st'=>0], "id in(".implode(',', $ids).")");
            $names = array_column($models,'username');
            if ($names) {
                $action = '删除';
                $remark = $action . "工资管理-删除数据：" . implode(',', $names) . "";
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
        $field = explode(",",$this->_request['field']);
        foreach($field as $f){
            $tableColumn[$f] = $this->tableColumn[$f];
        }
        $tableColumn['pay_time'] = '发放时间';
        unset($tableColumn['id']);
        $model = $this->modelClass;
        $model = $model::find()->where($this->where);
        $total = $model->count();
        $data = $model->orderBy($this->_orderBy)->all();
        if($data){
            foreach($data as $key=>$item){
                $data[$key]['pay_time'] = date("Y-m",$item['pay_time']);
            }
        }
        // var_dump($tableColumn);var_dump($data);exit;
        $this->toBlob($data,$tableColumn,'工资导出'.date('YmdH'));
    }
    protected function toBlob($data=[],$columns=[],$fileName=''){

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
        foreach($columns as $key1=>$value1){
            $index = $this->stringFromColumnIndex($i);
            $phpexcel->setActiveSheetIndex(0)->setCellValue($index.'1', $value1);
            $i++;
        }
        $i=0;
        foreach($data as $row){
            $j=0;
            foreach($columns as $key1=>$value1){
                $columnvalue=$row["$key1"];
                $index = $this->stringFromColumnIndex($j);
                $phpexcel->setActiveSheetIndex(0)->setCellValueExplicit($index.($i+2),$columnvalue);
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
    //导入绩效
    public function actionImport(){
        $month = $this->_request['month'];
        $url = $this->_request['url'];
        if(empty($url)||empty($month)){
            $this->_result = Tools::wrongRules(1000, '参数错误');
            return $this->_result;
        }
        $url = $this->_imageSavePath.str_replace("/uploaded/","",$url);

        $data = $this->getExcelData($url);

        if (empty($data)) {
            unlink($url);
            $this->_result = Tools::wrongRules(1000, '文件内容为空，导入失败');
            return $this->_result;
        }
        $totalSuccess = 1;
        $sheetNum = [];
        //导入数据处理
        foreach ($data as $sheetKey => $sheet) { //多张sheet
            $sheetNum[$sheetKey] = 0;
            $base = [];//表头对对应字段
            foreach ($sheet as $rowKey => $excelRows) { //单张sheet
                $msg = '';
                if($rowKey==0){
                    foreach ($excelRows as $titleK=>$item){
                        $item = trim(str_replace([' ','  '],'',$item));
                        foreach ($this->tableColumn as $cK=>$cV){
                            if (strpos($item, $cV) !== false) {
                                $base[$titleK] = $cK;
                            }
                        }
                        if(empty($base[$titleK])){
                            $msg= "列为[$item]未找到对应项，请检查excel表头数据，本sheet数据不保存。";
                            break;
                        }
                    }
                    if(!empty($msg)){
                        $this->addError("工资导入",'【第'.($sheetKey+1).'张sheet】'.$msg);break;//错误信息记录表中
                    }
                    unset($excelData[0]);
                    continue;
                }

                $mulitName = '';
                // var_dump($excelRows);
                if (count(array_unique(array_flip($excelRows))) > 1) {
                    $insertData = [];
                    foreach ($excelRows as $key => $value) {//数据表头处理
                        if(!$base[$key])
                            continue;
                        $insertData[$base[$key]] = $value ? $value:(in_array($base[$key],['col_a','mobile']) ? $value:0);
                        // if (strtolower($key) == 'v') {
                        //     break;
                        // }
                    }
                    // var_dump($insertData);exit;
                    $name = str_replace([' ', ' '], '', trim($excelRows['A']));
                    $endVal = trim($insertData['mobile']);
                    //判断是否存在该用户
                    list( $getUsers,$backMsg,$backMulitName,$salaryLen ) = $this->handleUser($endVal,$name,$month);

                    $mulitName = $backMulitName;

                     $msg.= $backMsg;
                    //只能导入拥有的权限部门的数据
                    // if($this->_adminUserType!=1&&!in_array($insertData['dep_id'],$this->_accessDep)){
                    //     $msg .= "无权导入用户名为：[$name]的数据,";
                    // }
                    $insertData['pay_time'] = strtotime($month);
                    $insertData['opt_id'] = $this->_adminInfo['id'];
                    $insertData['opt_name'] = $this->_adminInfo['realname'];
                    if (empty($msg)) { //验证通过

                        $insertData['dep_id'] = $getUsers[0]['departmentid'];
                        $insertData['userid'] = $getUsers[0]['userid'];
                        $insertData['username'] = $getUsers[0]['name'];

                        if($salaryLen==1){
                            $this->_db->createCommand()->update('weixin_salary', $insertData,"`userid` = '" . $insertData['userid'] . "' and pay_time = '" . strtotime($month)."' and st=1")->execute();
                        }else{
                            $insertData['created'] = time();
                            $this->_db->createCommand()->insert('weixin_salary', $insertData)->execute();
                        }
                        $sheetNum[$sheetKey]++;
                    } else {
                        $totalSuccess = 0;
                        $this->addError("工资导入","【第 $name 】".$msg);break;//错误信息记录表中
                    }
                }//单条数据出来结束
            }//单张sheet处理结束
        }//多张sheet foreach 处理结束
        unlink($url);
        $this->addError("工资导入",'发放时间：' . $month . ' 状态：' . ($totalSuccess ? '导入成功' : '部分导入失败') .',成功条数：'.implode(',',$sheetNum). $mulitName);//信息记录表中

        $this->_operationlog(['catalog' => 'create', 'remark' => "导入工资条"]);

        if($totalSuccess==0){
            $this->_result = Tools::wrongRules(1000, '部分导入失败');
        }
        return $this->_result;
    }   
    //记录错误数据信息 
    protected function addError($title,$msg){
        $this->_db->createCommand()->insert('weixin_error_record',
        ['opt_name' => $this->_adminInfo['realname'], 'title' => $title,'msg' => $msg, 'tp' => 1, 'created' => date("Y-m-d H:i:s")])->execute();
   
    }
    //处理用户数据，多条用户数据、删除因存在月份工资信息
    protected function handleUser($mobile,$name,$month){
        $msg = $mulitName = "";
        $getUsers = $this->_db->createCommand("select id,userid,`name`,departmentid from weixin_leave_userinfo where `name` = '" . $name . "'")->queryAll();
        if (empty($getUsers)) {
                    //用户未找到
            $msg = "[$name]用户未找到,";
        } else if (count($getUsers) > 1) {
            if (empty($mobile)) {
                $msg = "[$name]存在多条数据,电话号码为空。";
            } else {
                $getUsers = $this->_db->createCommand("select id,userid,`name`,departmentid from weixin_leave_userinfo where `name` = '" . $name . "' and mobile = " . $mobile)->queryAll();
                if (empty($getUsers)) {
                    $msg = "用户名为：[$name],手机号为[$mobile]用户未找到,";
                } else {
                        $mulitName = "[$name]存在多条数据,新增手机号为 $mobile 的数据,";
                }
            }
        }
        if($getUsers){
            //判断是否已经存在该月份的数据
            $getSalary = $this->_db->createCommand("select id,mobile from weixin_salary where
            `userid` = '" . $getUsers[0]['userid'] . "' and pay_time = '" . strtotime($month)."' and st =1 ")->queryAll();
            $salaryLen = count($getSalary);
            if($salaryLen>1){
                foreach ($getSalary as $salaryItem){
                    $this->_db->createCommand()->update('weixin_salary', ['st'=>0,'del_id'=>$this->_adminInfo['id']], "id = ".$salaryItem['id']);
                }
            }
        }
        
        return [$getUsers,$msg,$mulitName,$salaryLen];
    }
    //获取按表头获取数据
    public function getExcelData($file)
    {
        // \Yii::$app::$enableIncludePath = false;
        // \Yii::$app::import('application.extensions.PHPExcel.PHPExcel.IOFactory', 1);
        // require_once str_replace("\\","/",dirname(dirname(dirname(dirname(__FILE__)))).'/vendor/PHPExcel/Classes/PHPExcel/IOFactory.php');

        $fileType = \PHPExcel_IOFactory::identify($file);

        $excelReader = \PHPExcel_IOFactory::createReader($fileType);

        $objPHPExcel = $excelReader->load($file); //加载Excel文件
        $sheetCount = $objPHPExcel->getSheetCount();//获取sheet工作表总个数

        $data = [];
        /*读取表格数据*/
        $dataCount = 0;
        for ($s = 0; $s <= $sheetCount-1; $s++) {
            $phpexcel = $objPHPExcel->getSheet($s);
            $merge = $phpexcel->getMergeCells(); //合并单元格信息
            $find = 0;
            $total_line = $phpexcel->getHighestRow();//总行数
            $total_column = $phpexcel->getHighestColumn();//总列数
            $beginI = 0;
            $excelTitle = [];
            if ($total_line > 1) {
                $highestColumn = ++$total_column;//超过26处理 1
                $dataCount = 0;
                for ($i = $beginI; $i <= $total_line; $i++) { //开始读的行
                    for ($column = 'A'; $column != $highestColumn; $column++) { //超过26处理 2

                        //处理合并问题
                        $mergeI = $find == 0 ? $i : $beginI;
                        $excelT = $phpexcel->getCell($column . $mergeI)->getValue();
                        if ($excelT instanceof PHPExcel_RichText) { //富文本转换字符串
                            $excelT = $excelT->__toString();
                        }
                        $excelT = str_replace([' ','    '],'',trim($excelT));

                        if (($find == 0 && $excelT == '姓名') || $find == 1) {
                            if ($find >= 1 && $excelT) { //单元格未合并
                                $excelTitle[$column] = $excelT;
                                continue;
                            }
//
                            foreach ($merge as $mergeK => $mergeV) {
                                $divCell = explode(':', $mergeV);
                                $firstNum = 0;
                                $firstLetter = 'A';
                                $secondNum = 0;
                                $secondLetter = 'A';
                                if (preg_match('/\d+/', $divCell[0], $arr)) {
                                    $firstNum = $arr[0];
                                }
                                if (preg_match('/[A-Z]*/', $divCell[0], $matches)) {
                                    $firstLetter = $matches[0];
                                }
                                if (preg_match('/\d+/', $divCell[1], $matches)) {
                                    $secondNum = $matches[0];
                                }
                                if (preg_match('/[A-Z]*/', $divCell[1], $matches)) {
                                    $secondLetter = $matches[0];
                                }
                                for ($rowLetter = $firstLetter; $rowLetter <= $secondLetter; $rowLetter++) {
                                    for ($rowNum = $firstNum; $rowNum <= $secondNum; $rowNum++) {
                                        if ($rowLetter . $rowNum == $column . $mergeI) {
                                            if ($find == 0) {
                                                $beginI = $secondNum;
                                            }
                                            $excelT = $phpexcel->getCell($firstLetter . $firstNum)->getValue();
                                            if ($excelT instanceof PHPExcel_RichText) { //富文本转换字符串
                                                $cell = $excelT->__toString();
                                            }else{
                                                $cell = $excelT;
                                            }
                                            $excelTitle[$column] = $cell;
                                            break;
                                        }
                                    }
                                    if (!empty($excelTitle[$column])) {
                                        break;
                                    }
                                }
                                if (!empty($excelTitle[$column])) {
                                    break;
                                }
                            }
                            if($find==0&&$beginI==0){//处理表头不存合并的单元格
                                $excelTitle[$column] = $excelT;
                                $beginI = $i;
                            }
                            $find = $find == 0 ? 1 : $find;
                            continue;
                        }else if ($find < 2) { //读取excel表头未结束
                            continue;
                        }
                        $data[$s][$dataCount][$column] = trim($phpexcel->getCell($column . $i)->getValue());
                        if (mb_substr($data[$s][$dataCount][$column], 0, 1) == '=') {
                            $data[$s][$dataCount][$column] = $phpexcel->getCell($column . $i)->getFormattedValue();
                        }
                    } //A-Z循环结束
                    if ($find == 1) {
                        $data[$s][$dataCount] = $excelTitle;
                        $i = $beginI;//数据开始的地方
                        $find++;
                        $dataCount++;
                    } else if ($find > 1) {
                        $dataCount++;
                    }

                }
            }
        }
        return $data;
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
        // echo "SELECT modules,departments,actions FROM `weixin_oa_auth` where agentid='$this->_agentId' and (FIND_IN_SET('".$this->_adminInfo['username']."',sysusers) or FIND_IN_SET('".$this->_adminInfo['wxuserid']."',wxusers)) ";
        // var_dump($res);
        // exit;

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
     * 根据部门返回表头
     */
    public function actionColumns()
    {
        $id = intval($this->_request['id']);
        if($id){
            $_columns = $this->modelClass::getColumns();
            $keys = array_keys($_columns);
            $depts = [];
            $res = WxDepartment::find()->asArray()->all();
            foreach($res as $row){
                $depts[$row['id']] = $row['parentid'];
            }
            return $this->getColumns($id,$keys,$depts,$_columns);
        }
    }
    private function getColumns($id,$keys,$depts,$_columns)
    {
        if(in_array($id,$keys)){
            return ['id'=>$id,'columns'=>$_columns[$id]];
        }else{
            return $this->getColumns($depts[$id],$keys,$depts,$_columns);
        }
    }

    
    /**
     * @param int $pColumnIndex
     * @return mixed 解决导出数据大于26列
     */
    private function stringFromColumnIndex($pColumnIndex = 0)
    {
        $_indexCache = array();
        if (!isset($_indexCache[$pColumnIndex])) {
            if ($pColumnIndex < 26) {
                $_indexCache[$pColumnIndex] = chr(65 + $pColumnIndex);
            } elseif ($pColumnIndex < 702) {
                $_indexCache[$pColumnIndex] = chr(64 + ($pColumnIndex / 26)) . chr(65 + $pColumnIndex % 26);
            } else {
                $_indexCache[$pColumnIndex] = chr(64 + (($pColumnIndex - 26) / 676)) . chr(65 + ((($pColumnIndex - 26) % 676) / 26)) . chr(65 + $pColumnIndex % 26);
            }
        }
        return $_indexCache[$pColumnIndex];
    }

    /**
     * 个人年度汇总
     */
    public function actionPerson()
    {
        $year = intval($this->_request['year']);
        $userId = $this->_request['userid'];
        if($year && $userId){
            $dep_wbfxzx = [27];//晚报发行中心
            $dep_byfxzx = [60];//报业发行公司
            $dep_yy = [20,26];//日、晚报运营
            $dep_ldsz = [31,65];//领导、社直、社总编室
            $dep_ldsz = array_merge($dep_ldsz,$this->getMergeChildDepartments([2]));
            $dep_xmt = $this->getMergeChildDepartments([6,40]);//新媒体
            $dep_rwb = array_diff($this->getMergeChildDepartments([3,5]),[20,26,95]);//日、晚报、视觉
            $userInfo = $this->_db->createCommand("select departmentid,`name` from weixin_leave_userinfo where status = 1 and userid = '" . $userId . "'")->queryOne();
            if(empty($userInfo)){
                return Tools::wrongRules(1000, '用户不存在或者部门不存在');
            }
            $username = $userInfo['name'];
            $this_year = $year?$year:date('Y');
            $prev_year = $this_year-1;
            if(in_array($userInfo['departmentid'],$dep_yy)){//日、晚报运营
                $prev_salary_sql = "SELECT userid,username,sum(col_i) as col_yf,sum(col_ad) as col_kk,sum(col_ae) as col_sf FROM `weixin_salary` where userid='".$userId."' and st=1 and sign_st=1 and DATE_FORMAT(from_unixtime(pay_time), '%Y')='$prev_year' GROUP BY userid,username";
                $this_salary_sql = "SELECT userid,username,sum(col_i) as col_yf,sum(col_ad) as col_kk,sum(col_ae) as col_sf FROM `weixin_salary` where userid='".$userId."' and st=1 and sign_st=1 and DATE_FORMAT(from_unixtime(pay_time), '%Y')='$this_year' GROUP BY userid,username";
            }else if(in_array($userInfo['departmentid'],$dep_ldsz)){//领导、社直
                $prev_salary_sql = "SELECT userid,username,sum(col_i)+sum(col_as) as col_yf,sum(col_y)+sum(col_ad) as col_kk,sum(col_ar)+sum(col_at) as col_sf FROM `weixin_salary` where userid='".$userId."' and st=1 and sign_st=1 and DATE_FORMAT(from_unixtime(pay_time), '%Y')='$prev_year' GROUP BY userid,username";
                $this_salary_sql = "SELECT userid,username,sum(col_i)+sum(col_as) as col_yf,sum(col_y)+sum(col_ad) as col_kk,sum(col_ar)+sum(col_at) as col_sf FROM `weixin_salary` where userid='".$userId."' and st=1 and sign_st=1 and DATE_FORMAT(from_unixtime(pay_time), '%Y')='$this_year' GROUP BY userid,username";
            }else if(in_array($userInfo['departmentid'],$dep_xmt)){//新媒体
                $prev_salary_sql = "SELECT userid,username,sum(col_r) as col_yf,sum(col_ad) as col_kk,sum(col_ae) as col_sf FROM `weixin_salary` where userid='".$userId."' and st=1 and sign_st=1 and DATE_FORMAT(from_unixtime(pay_time), '%Y')='$prev_year' GROUP BY userid,username";
                $this_salary_sql = "SELECT userid,username,sum(col_r) as col_yf,sum(col_ad) as col_kk,sum(col_ae) as col_sf FROM `weixin_salary` where userid='".$userId."' and st=1 and sign_st=1 and DATE_FORMAT(from_unixtime(pay_time), '%Y')='$this_year' GROUP BY userid,username";
            }else if(in_array($userInfo['departmentid'],$dep_rwb)){//日、晚报
                $prev_salary_sql = "SELECT userid,username,sum(col_r) as col_yf,sum(col_y)+sum(col_z)+sum(col_ac) as col_kk,sum(col_ae) as col_sf FROM `weixin_salary` where userid='".$userId."' and st=1 and sign_st=1 and DATE_FORMAT(from_unixtime(pay_time), '%Y')='$prev_year' GROUP BY userid,username";
                $this_salary_sql = "SELECT userid,username,sum(col_r) as col_yf,sum(col_y)+sum(col_z)+sum(col_ac) as col_kk,sum(col_ae) as col_sf FROM `weixin_salary` where userid='".$userId."' and st=1 and sign_st=1 and DATE_FORMAT(from_unixtime(pay_time), '%Y')='$this_year' GROUP BY userid,username";
            }else if(in_array($userInfo['departmentid'],$dep_wbfxzx)){//晚报发行中心
                $prev_salary_sql = "SELECT userid,username,sum(col_r) as col_yf,sum(col_ad) as col_kk,sum(col_ae) as col_sf FROM `weixin_salary` where userid='".$userId."' and st=1 and sign_st=1 and DATE_FORMAT(from_unixtime(pay_time), '%Y')='$prev_year' GROUP BY userid,username";
                $this_salary_sql = "SELECT userid,username,sum(col_r) as col_yf,sum(col_ad) as col_kk,sum(col_ae) as col_sf FROM `weixin_salary` where userid='".$userId."' and st=1 and sign_st=1 and DATE_FORMAT(from_unixtime(pay_time), '%Y')='$this_year' GROUP BY userid,username";
            }else if(in_array($userInfo['departmentid'],$dep_byfxzx)){//报业发行公司
                $prev_salary_sql = "SELECT userid,username,sum(col_r) as col_yf,sum(col_ai)+sum(col_x)+sum(col_v)+sum(col_s)+sum(col_bm)+sum(col_ab)+sum(col_y)+sum(col_bn) as col_kk,sum(col_ae) as col_sf FROM `weixin_salary` where userid='".$userId."' and st=1 and sign_st=1 and DATE_FORMAT(from_unixtime(pay_time), '%Y')='$prev_year' GROUP BY userid,username";
                $this_salary_sql = "SELECT userid,username,sum(col_r) as col_yf,sum(col_ai)+sum(col_x)+sum(col_v)+sum(col_s)+sum(col_bm)+sum(col_ab)+sum(col_y)+sum(col_bn) as col_kk,sum(col_ae) as col_sf FROM `weixin_salary` where userid='".$userId."' and st=1 and sign_st=1 and DATE_FORMAT(from_unixtime(pay_time), '%Y')='$this_year' GROUP BY userid,username";
            }else{
                return Tools::wrongRules(1000, '无数据');
            }
            $salary=[];
            $prev_salary = $this->_db->createCommand($prev_salary_sql)->queryOne();
            if($prev_salary){
                $salary['prev'] = $prev_salary;
            }
            $this_salary = $this->_db->createCommand($this_salary_sql)->queryOne();
            if($this_salary){
                $salary['this'] = $this_salary;
            }
            
            // 市级奖金
            $cbonus=[];
            $prev_cbonus_sql = "SELECT userid,username,sum(col_b) as col_yf,sum(col_c) as col_kk,sum(col_d) as col_sf FROM `weixin_salary_bonus` where userid='".$userId."' and bonus_year='$prev_year' and bonus_type in(1,2,3) and st=1 and sign_st=1 GROUP BY userid,username";
            $prev_cbonus = $this->_db->createCommand($prev_cbonus_sql)->queryOne();
            if($prev_cbonus){
                $cbonus['prev'] = $prev_cbonus;
            }
            $this_cbonus_sql = "SELECT userid,username,sum(col_b) as col_yf,sum(col_c) as col_kk,sum(col_d) as col_sf FROM `weixin_salary_bonus` where userid='".$userId."' and bonus_year='$this_year' and bonus_type in(1,2,3) and st=1 and sign_st=1 GROUP BY userid,username";
            $this_cbonus = $this->_db->createCommand($this_cbonus_sql)->queryOne();
            if($this_cbonus){
                $cbonus['this'] = $this_cbonus;
            }
            //考核奖
            $assessBonus =[];
            $prev_assessbonus_sql = "SELECT userid,username,sum(col_b) as col_yf,sum(col_c) as col_kk,sum(col_d) as col_sf FROM `weixin_salary_bonus` where userid='".$userId."' and bonus_year='$prev_year' and bonus_type in(4) and st=1 and sign_st=1 GROUP BY userid,username";
            $prev_assessbonus = $this->_db->createCommand($prev_assessbonus_sql)->queryOne();
            if($prev_assessbonus){
                $assessBonus['prev'] = $prev_assessbonus;
            }
            $this_assessbonus_sql = "SELECT userid,username,sum(col_b) as col_yf,sum(col_c) as col_kk,sum(col_d) as col_sf FROM `weixin_salary_bonus` where userid='".$userId."' and bonus_year='$this_year' and bonus_type in(4) and st=1 and sign_st=1 GROUP BY userid,username";
            $this_assessbonus = $this->_db->createCommand($this_assessbonus_sql)->queryOne();
            if($this_assessbonus){
                $assessBonus['this'] = $this_assessbonus;
            }
            //年终奖
            $ybonus=[];
            $prev_ybonus_sql = "SELECT userid,username,col_b as col_yf,col_c as col_kk,col_d as col_sf FROM `weixin_salary_bonus` where userid='".$userId."' and bonus_year='$prev_year' and bonus_type in(0,5) and st=1 and sign_st=1";
            $prev_ybonus = $this->_db->createCommand($prev_ybonus_sql)->queryOne();
            if($prev_ybonus){
                $ybonus['prev'] = $prev_ybonus;
            }
            $this_ybonus_sql = "SELECT userid,username,col_b as col_yf,col_c as col_kk,col_d as col_sf FROM `weixin_salary_bonus` where userid='".$userId."' and bonus_year='$this_year' and bonus_type in(0,5) and st=1 and sign_st=1";
            $this_ybonus = $this->_db->createCommand($this_ybonus_sql)->queryOne();
            if($this_ybonus){
                $ybonus['this'] = $this_ybonus;
            }
            $data = [
                [
                    'key'=> '1',
                    'type'=> '年度工资',
                    'year'=> $prev_year.'年',
                    'yf'=> $salary['prev']?$salary['prev']['col_yf']:0,
                    'dk'=> $salary['prev']?$salary['prev']['col_kk']:0,
                    'sf'=> $salary['prev']?$salary['prev']['col_sf']:0
                ],
                [
                    'key'=> '2',
                    'type'=> '年度工资',
                    'year'=> $this_year.'年',
                    'yf'=> $salary['this']?$salary['this']['col_yf']:0,
                    'dk'=> $salary['this']?$salary['this']['col_kk']:0,
                    'sf'=> $salary['this']?$salary['this']['col_sf']:0
                ],
                [
                    'key'=> '3',
                    'type'=> '市级奖金',
                    'year'=> $prev_year.'年',
                    'yf'=> $cbonus['prev']?$cbonus['prev']['col_yf']:0,
                    'dk'=> $cbonus['prev']?$cbonus['prev']['col_kk']:0,
                    'sf'=> $cbonus['prev']?$cbonus['prev']['col_sf']:0
                ],
                [
                    'key'=> '4',
                    'type'=> '市级奖金',
                    'year'=> $this_year.'年',
                    'yf'=> $cbonus['this']?$cbonus['this']['col_yf']:0,
                    'dk'=> $cbonus['this']?$cbonus['this']['col_kk']:0,
                    'sf'=> $cbonus['this']?$cbonus['this']['col_sf']:0
                ],
                [
                    'key'=> '5',
                    'type'=> '年度综合绩效考核奖',
                    'year'=> $prev_year.'年',
                    'yf'=> $assessBonus['prev']?$assessBonus['prev']['col_yf']:0,
                    'dk'=> $assessBonus['prev']?$assessBonus['prev']['col_kk']:0,
                    'sf'=> $assessBonus['prev']?$assessBonus['prev']['col_sf']:0
                ],
                [
                    'key'=> '6',
                    'type'=> '年度综合绩效考核奖',
                    'year'=> $this_year.'年',
                    'yf'=> $assessBonus['this']?$assessBonus['this']['col_yf']:0,
                    'dk'=> $assessBonus['this']?$assessBonus['this']['col_kk']:0,
                    'sf'=> $assessBonus['this']?$assessBonus['this']['col_sf']:0
                ],
                [
                    'key'=> '7',
                    'type'=> '年终奖',
                    'year'=> $prev_year.'年',
                    'yf'=> $ybonus['prev']?$ybonus['prev']['col_yf']:0,
                    'dk'=> $ybonus['prev']?$ybonus['prev']['col_kk']:0,
                    'sf'=> $ybonus['prev']?$ybonus['prev']['col_sf']:0
                ],
                [
                    'key'=> '8',
                    'type'=> '年终奖',
                    'year'=> $this_year.'年',
                    'yf'=> $ybonus['this']?$ybonus['this']['col_yf']:0,
                    'dk'=> $ybonus['this']?$ybonus['this']['col_kk']:0,
                    'sf'=> $ybonus['this']?$ybonus['this']['col_sf']:0
                ],
                [
                    'key'=> '9',
                    'type'=> '合计',
                    'year'=> $prev_year.'年',
                    'yf'=> $salary_prev_yf + $cbonus_prev_yf + $assessBonus_prev_yf + $ybonus_prev_yf,
                    'dk'=> $salary_prev_kk + $cbonus_prev_kk + $assessBonus_prev_kk + $ybonus_prev_kk,
                    'sf'=> $salary_prev_sf + $cbonus_prev_sf + $assessBonus_prev_sf + $ybonus_prev_sf
                ],
                [
                    'key'=> '10',
                    'type'=> '合计',
                    'year'=> $this_year.'年',
                    'yf'=> $salary_this_yf + $cbonus_this_yf + $assessBonus_this_yf + $ybonus_this_yf,
                    'dk'=> $salary_this_kk + $cbonus_this_kk + $assessBonus_this_kk + $ybonus_this_kk,
                    'sf'=> $salary_this_sf + $cbonus_this_sf + $assessBonus_this_sf + $ybonus_this_sf
                ]
            ];
            $this->_result['data'] = $data;
        }
        return $this->_result;
    }
}