<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\commons\Uploader;
use app\modules\api\models\FzrbsRouteMenu;
use PHPExcel;
use PHPExcel\IOFactory;
/**
 * 错误提示接口
 */
class ErrorRecordController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WxErrorRecord';
    public $_orderBy = " id desc";
    public $_db;
    public $_req='';
    public $_where = [];//usertype = 0 报社 
   
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
       
        $tp = intval($this->_request['tp']);
        $optname = isset($this->_request['opt_name']) ? $this->_request['opt_name'] : '';
        $msg = isset($this->_request['msg']) ? $this->_request['msg'] : '';
        $this->_where = ['and',['>', 'id', 0]];
        if($tp){
            $this->_where[] =['=','tp', $tp];
        }
        if($optname){
            $this->_where[] =['=','opt_name', $optname];
        }
        if($msg){
            $this->_where[] =['like','msg', $msg];
        }
    
    }
    /** 列表显示 */
    public function actionIndex(){

        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $this->search();
        $type = intval($this->_request['type']);
        if($type){
            if($type == 1){
                $this->_where[] =['in','tp', [1,3]];
            }else{
                $this->_where[] =['=','tp', $type];
            }
        }
        // var_dump($this->_where);exit;
        $offset = $limit * ($page - 1);
        $model = $this->modelClass;
        $model = $model::find()->where($this->_where);
        $total = $model->count();
        $data = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->asArray()->all();
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        $this->_result['data'] = $data;
        return $this->_result;
    }

}