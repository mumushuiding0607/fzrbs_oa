<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\commons\Tools;
use app\modules\api\models\WxDepartment;
use Yii;

/**
 * 奖金操作接口类
 */
class SalaryBonusController extends ApiBase
{
    protected $_agentId = 1000022;
    public $modelClass = 'app\modules\api\models\WxSalaryBonus';
    protected $_orderBy = 'id desc';
    public $tableColumn = [ //tableColumn 修改微信端的也要相应的修改
        'col_a'=>'姓名',
        'col_b'=>'奖金总额',
        'col_c'=>'代扣代缴',
        'col_d'=>'实发总额',
        'mobile'=>'手机号',
        'bonus_year'=>'所属年份',
        'bonus_type'=>'奖金类型',
    ];
    protected $bonusTp = ['年终奖','文明奖','创城奖','综治奖','年度综合绩效考核奖','年终奖（第二笔）','年终奖（第三笔）'];
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

    }
    public function search(){
        $depId = isset($this->_request['depId']) ? $this->_request['depId'] : 0;
        $bonusType = $this->_request['bonus_type']!='' ? $this->_request['bonus_type'] : '';
        $signSt = $this->_request['sign_st']!='' ? $this->_request['sign_st'] : '';
        $sendSt = $this->_request['send_st']!='' ? $this->_request['send_st'] : '';
        // $bonusYear = $this->_request['bonus_year']!='' ? $this->_request['bonus_year'] : '';
        $col_a = $this->_request['col_a']!='' ? trim($this->_request['col_a']) : '';

        $startTime = isset($this->_request['startTime'])?intval($this->_request['startTime']):0;
        $endTime = isset($this->_request['endTime'])?intval($this->_request['endTime']):0;
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
        if($bonusType!=''){
            $this->where[] = ['=','bonus_type',$bonusType];
        }
        if($signSt!=''){
            $this->where[] = ['=','sign_st',$signSt];
        }
        if($sendSt!=''){
            $this->where[] = ['=','send_st',$sendSt];
        }
        if($startTime){
            $this->where[] = ['>=','bonus_year',$startTime];
        }
        if($endTime){
            $this->where[] = ['<=','bonus_year',$endTime];
        }
        if($col_a){
            $this->where[] = ['like','col_a',$col_a];
        }
    }
        /**
     * 重写index的业务实现动作
     */
    public function actionIndex()
    {$total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
      
        $this->search();
        $model = $this->modelClass;
        $model = $model::find()->where($this->where);
        $total = $model->count();
        $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
        if($res){
            foreach($res as $key=>$value){
                $res[$key]['bonus_year'] = date("Y",strtotime($value['bonus_year']."-01-01"));
                foreach ($value as $k=>$v){
                    if(!in_array($k,['col_a','mobile','dep_id','username','id','created','bonus_year','st','bonus_type','sign_st','send_st'])){
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
            // var_dump($data);exit;
            //查找用户

            $model->attributes = $data;
            $ruleResult = Tools::modelRules($model, 4001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '修改';
                    $remark = $action . "奖金管理-编辑：id=$id";
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
                $remark = $action . "奖金管理-删除数据：" . implode(',', $names) . "";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }
    // 类型
    public function actionTypes()
    {
        $this->_result['data'] = $this->bonusTp;
        return $this->_result;
    }
    // 签发
    public function actionSign()
    {
        if($this->_request['id']){
            $this->where = ['=', 'id', $this->_request['id']];
            $where = "id='".$this->_request['id']."'";
        }else if ($this->_request['depId']) {
            $auth = $this->getUserAuth();
            $depts = array_intersect($this->getMergeChildDepartments([$this->_request['depId']]),$auth['childdepts']);
            $this->where = 
            [   'and',
                ['=', 'opt_id', $this->_adminInfo['id']],
                ['=', 'bonus_year', $this->_request['bonus_year']],
                ['=','bonus_type',$this->_request['bonus_type']],
                ['in', 'dep_id', $depts],
            ];
            foreach($this->where as $w){
                if(is_array($w)){
                    if($w[0]=='in'){
                        if(is_array($w[2])){
                            $where[] = $w[1]." in (".implode(',',$w[2]).") ";
                        }else{
                            $where[] = $w[1]." in (".$w[2].") ";
                        }
                        
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
            $action = $st  ?'签发':'取消签发';
            $remark = $action . '奖金 部门id:'.$this->_request['depId'].'： 年份：' . $this->_request['bonus_year']." 奖金类型:".$this->bonusTp[$this->_request['bonus_type']];
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        }

        //通知用户
        if ($notify) {
            !isset($this->_request['bonus_type']) && $this->_request['bonus_type'] = $models[0]['bonus_type'];
            !isset($this->_request['bonus_year']) && $this->_request['bonus_year'] = $models[0]['bonus_year'];
            $this->sendWeixin($this->_request['bonus_type'],$this->_request['bonus_year'],$models);
        }

        return $this->_result;
    }
    protected function sendWeixin($bonus_type,$bonus_year,$models){
        $content = '您有一笔新奖金,<a href="https://fzrb.fznews.com.cn/index.php?r=qiyehao/salary/bonus&bonusYear='.$bonus_year.'&bonusType='.$bonus_type.'">请点击查看</a>';

        $sendResult = WxQyhJk::sendMessage($this->_agentId,implode("|",array_column($models,'userid')),$content);

        //返回结果出来
        $flag = 1;
        if (!$sendResult['errorMessage']) {
            $flag = 0;
        } 
        $action = '通知';
        $remark = $action . "奖金管理-签发数据（".$bonus_year." 年份奖金通知".($flag ? "成功":"失败")."）：" . implode(',', array_column($models,'name')) . "";
        $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
    }
        /**
     * 数据导出
     */
    public function actionExport(){
        $this->search();
        $model = $this->modelClass;
        $model = $model::find()->where($this->where);
        $data = $model->orderBy($this->_orderBy)->all();
        if($data){
            foreach($data as $key=>$item){
                $data[$key]['bonus_type'] = $this->bonusTp[$item['bonus_type']];
            }
        }
        $this->toBlob($data,$this->tableColumn,'奖金导出'.date('YmdH'));
    }
    //导入奖金
    public function actionImport(){
        $bonusYear = $this->_request['bonus_year'];
        $bonusType = $this->_request['bonus_type'];
        $bonusType = $bonusType['value'];
        $url = $this->_request['url'];
        if(empty($url)||empty($bonusYear)||$bonusType==''){
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
                            if (strpos($item, $cV) !== false&&!in_array($cK,['bonus_type','bonus_year'])) {
                                $base[$titleK] = $cK;
                            }
                        }
                        if(empty($base[$titleK])){
                            $msg= "列为[$item]未找到对应项，请检查excel表头数据，本sheet数据不保存。";
                            break;
                        }
                    }
                    if(!empty($msg)){
                        $this->addError("奖金导入",'【第'.($sheetKey+1).'张sheet】'.$msg);break;//错误信息记录表中
                    }
                    unset($excelData[0]);
                    continue;
                }

                $mulitName = '';

                if (count(array_unique(array_flip($excelRows))) > 1) {
                    $insertData = [];
                    foreach ($excelRows as $key => $value) {//数据表头处理
                        if(!$base[$key])
                            continue;
                        $insertData[$base[$key]] = $value ? $value:(in_array($base[$key],['col_a','mobile']) ? $value:0);
                        if (strtolower($key) == 'v') {
                            break;
                        }
                    }

                    $name = str_replace([' ', ' '], '', trim($excelRows['A']));
                    $endVal = trim($insertData['mobile']);
                    //判断是否存在该用户
                    list( $getUsers,$backMsg,$backMulitName,$salaryLen ) = $this->handleUser($endVal,$name,$bonusType,$bonusYear);

                    $mulitName = $backMulitName;

                    $msg.= $backMsg;
                    //只能导入拥有的权限部门的数据
    //                            if($this->_adminUserType!=1&&!in_array($insertData['dep_id'],$this->_accessDep)){
    //                                $msg .= "无权导入用户名为：[$name]的数据,";
    //                            }
                    // $insertData['pay_time'] = strtotime($bonusYear);
                    $insertData['opt_id'] = $this->_adminInfo['id'];
                    $insertData['opt_name'] = $this->_adminInfo['realname'];
                    if (empty($msg)) { //验证通过

                        $insertData['dep_id'] = $getUsers[0]['departmentid'];
                        $insertData['userid'] = $getUsers[0]['userid'];
                        $insertData['username'] = $getUsers[0]['name'];
                        $insertData['bonus_type'] = $bonusType;
                        $insertData['bonus_year'] = $bonusYear;
                        // var_dump($insertData);exit;
                        if($salaryLen==1){
                            // $this->modelClass::updateAll($insertData, "`userid` = '" . $insertData['userid'] . "' and bonus_year = '" . $bonusYear."' and bonus_type = '" . $bonusType."' and st=1");

                            $this->_db->createCommand()->update('weixin_salary_bonus', $insertData,"`userid` = '" . $insertData['userid'] . "' and bonus_year = '" . $bonusYear."' and bonus_type = '" . $bonusType."' and st=1")->execute();
                        }else{
                            $insertData['created'] = time();
                            $this->_db->createCommand()->insert('weixin_salary_bonus', $insertData)->execute();
                        }
                        $sheetNum[$sheetKey]++;
                    } else {
                        $totalSuccess = 0;
                        $this->addError("奖金导入","【第 $name 】".$msg);break;//错误信息记录表中
                    }
                }//单条数据出来结束
            }//单张sheet处理结束
        }//多张sheet foreach 处理结束
        unlink($url);
        $this->addError("奖金导入",'发放时间：' . $bonusYear . ' 状态：' . ($totalSuccess ? '导入成功' : '部分导入失败') .',成功条数：'.implode(',',$sheetNum). $mulitName);//信息记录表中

        $this->_operationlog(['catalog' => 'create', 'remark' => "导入奖金"]);

        if($totalSuccess==0){
            $this->_result = Tools::wrongRules(1000, '部分导入失败');
        }
        return $this->_result;
    }
        //处理用户数据，多条用户数据、删除因存在月份工资信息
        protected function handleUser($mobile,$name,$bonusTp,$bonusYear){
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
                $getSalary = $this->_db->createCommand("select id,mobile from weixin_salary_bonus where
                                              `userid` = '" . $getUsers[0]['userid'] . "' and bonus_year = '" . $bonusYear."' and bonus_type = '" . $bonusTp."' and st =1 ")->queryAll();
                $salaryLen = count($getSalary);
                if($salaryLen>1){
                    foreach ($getSalary as $salaryItem){
                        $this->_db->createCommand()->update('weixin_salary_bonus', ['st'=>0,'del_id'=>$this->_adminInfo['id']], "id = ".$salaryItem['id']);
                    }
                }
            }
            
            return [$getUsers,$msg,$mulitName,$salaryLen];
        }
    //记录错误数据信息 
    protected function addError($title,$msg){
        $this->_db->createCommand()->insert('weixin_error_record',
        ['opt_name' => $this->_adminInfo['realname'], 'title' => $title,'msg' => $msg, 'tp' => 3, 'created' => date("Y-m-d H:i:s")])->execute();
   
    }
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
 //获取按表头获取数据
 public function getExcelData($file)
 {
     // \Yii::$app::$enableIncludePath = false;
     // \Yii::$app::import('application.extensions.PHPExcel.PHPExcel.IOFactory', 1);
    //  require_once str_replace("\\","/",dirname(dirname(dirname(dirname(__FILE__)))).'/vendor/PHPExcel/Classes/PHPExcel/IOFactory.php');

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
}