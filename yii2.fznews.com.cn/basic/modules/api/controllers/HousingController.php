<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\commons\Uploader;
use app\modules\api\models\FzrbsRouteMenu;
use PHPExcel;
use PHPExcel\IOFactory;
/**
 * 租房管理接口
 */
class HousingController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WxHousing';
    public $_orderBy = " id desc";
    public $_db;
    public $_req='';
    public $_where = []; 
    protected $_agentId = 9000001;
   
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
       
        $this->_where = ['and',['>', 'st', 0]];
        if($this->_request['project']){
            $this->_where[] =['like','project', $this->_request['project']];
        }
        if($this->_request['lessee']){
            $this->_where[] =['like','lessee', $this->_request['lessee']];
        }
        if($this->_request['mobile']){
            $this->_where[] =['like','mobile', $this->_request['mobile']];
        }
        if($this->_request['addr']){
            $this->_where[] =['like','addr', $this->_request['addr']];
        }
    
    }
    /** 列表显示 */
    public function actionIndex(){

        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $this->search();
        
        $offset = $limit * ($page - 1);
        $model = $this->modelClass;
        $model = $model::find()->where($this->_where);
        $total = $model->count();
        $data = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->asArray()->all();
        $newData=[];
        if($data){
            foreach($data as $key=>$item){
                $newItem = $item;
                $newItem['start_time'] = $item['start_time']==0 ? "":date("Y-m-d",$item['start_time']);
                $newItem['end_time'] = $item['start_time']==0 ? "":date("Y-m-d",$item['end_time']);
                array_push($newData,$newItem);
            }
        }

        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        $this->_result['data'] = $newData;
        return $this->_result;
    }
    public function actionCreate()
    {     
        if ($this->_request['values']) {
           
            $model = new $this->modelClass(['scenario' => 'create']);
            $data = $this->_request['values'];
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
            $data['created'] = date("y-m-d H:i:s");
            $model->attributes = $data;
            $ruleResult = Tools::modelRules($model, 4000);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $this->_result['lastid'] = $model->id;
                    $action = '[租房管理]新增';
                    $remark = $action . "租房管理-房屋租赁信息(id=$model->id)".implode(",",$data);
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
    //更新数据
    public function actionUpdate(){
        $data = $this->_request['values'];
       
        $id = intval($this->_request['id']);
        if($id){
           
            //转换同行人信息
            //验证数据
            $data['start_time'] = strtotime($data['start_time']);
            $data['end_time'] = strtotime($data['end_time']);
            $model = $this->modelClass::findOne($id);
          
            $data['notice'] = $data['notice'] ? 1:0; 
            $model = $this->modelClass::findOne($id);
            $model->scenario = 'update';
            $model->attributes = $data;
            $ruleResult = Tools::modelRules($model, 2001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '[租房管理]更新';
                    $remark = $action . "租房管理-房屋租赁信息(id=$id)".implode(",",$data);
                    $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
                }
            } else {
                $this->_result['errorCode'] = $ruleResult['errorCode'];
                $this->_result['errorMessage'] = $ruleResult['errorMessage'];
            }  
        }else{
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }
    //删除
    public function actionDelete()
    {
        if ($this->_request['id']) {
            $ids = explode(',', $this->_request['id']);
            $model = $this->modelClass;
            $models = $model::find()->where(['in', 'id', $ids])->all();
            $this->modelClass::updateAll(['st'=>0], "id in(".implode(',', $ids).")");
            $names = array_column($models,'lessee');
            if ($names) {
                $action = '[租房管理]删除';
                $remark = $action . "租房管理-删除数据：" . implode(',', $names) . "";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }
    //导出
    public function actionExport(){
        $this->search();
        $model = $this->modelClass;
        $model = $model::find()->where($this->_where);
        $data = $model->orderBy($this->_orderBy)->all();
        $newData = [];
        if($data){
            foreach($data as $key=>$item){
                $newItem = $item;
                $newItem['notice'] = $item['notice']==0 ? "否":"是";
                $newItem['start_time'] = $item['start_time']==0 ? "":date("Y-m-d",$item['start_time']);
                 $newItem['end_time'] = $item['start_time']==0 ? "":date("Y-m-d",$item['end_time']);
                array_push($newData,$newItem);
            }
        }
        $fieldColumns = ['project', 'lessee', 'mobile', 'start_time', 'end_time','monthly_rent', 'rent_date','addr','created'];
        $fieldName = ['房产项目', '承租人', '联系方式', '起租日期', '到租日期','月租金（元）', '收租日（号）','项目地址','创建时间'];
        $columns = array_combine($fieldColumns, $fieldName);
        $this->toBlob($newData,$columns,'租房信息导出'.date('YmdH'));
    }
    //获取所有项目
    public function actionGetProject(){
    
        $setting = $this->_db->createCommand("select id from weixin_property_setting where INSTR(CONCAT(',',p_ids,','),',39,')>0 or id = 39")->queryAll();
        $data = $this->_db->createCommand("select id,property_name,property_brand from weixin_property where property_tpid in(".implode(",",array_column($setting,"id")).")")->queryAll();

        foreach ($data as $r) {
            $ret[] = ['value' => $r['id'], 'label' => $r['property_name']."(".$r['property_brand'].")",'addr'=>$r['property_brand']];
        }
        $this->_result['data'] = $ret;
        return $this->_result;
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
        $res = $this->_db->createCommand("SELECT modules,departments,actions FROM `weixin_oa_auth` where agentid='$this->_agentId' and (FIND_IN_SET('".$this->_adminInfo['username']."',sysusers) or FIND_IN_SET('".$this->_adminInfo['wxuserid']."',wxusers)) ")->queryAll();
        $data = [];
        if($res){
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
            $data['departments'] = $data['departments']?array_unique(array_merge($this->getMergeParentDepartments($data['departments']),$this->getMergeChildDepartments($data['departments']))):[];
            $data['departments'] = $data['departments']?filter_var_array($data['departments'],FILTER_VALIDATE_INT):[];
            sort($data['departments']);
        }
        return $data;
    }
    
    /**
     * 返回所有上级部门
     */
    private function getMergeParentDepartments($dept)
    {
        $res = $this->_db->createCommand("SELECT id,parentids FROM `weixin_oa_department` where id in (".implode(',',$dept).") ")->queryAll();
        foreach($res as $row){
            $dept = array_merge($dept,explode(',',$row['parentids']));
        }
        return $dept;
    }

    /**
     * 返回所有下级部门
     */
    private function getMergeChildDepartments($dept)
    {
        $childs = [];
        $res = $this->_db->createCommand("SELECT id FROM `weixin_oa_department` where parentid in (".implode(',',$dept).") ")->queryAll();
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