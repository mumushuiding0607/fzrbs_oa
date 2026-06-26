<?php

namespace app\modules\api\controllers;
use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\WorkflowParse;
use app\modules\api\commons\WxQyhJk;
use app\modules\api\models\FzrbsCompany;
use app\modules\api\models\FzrbsManuscriptscoringInfo;
use app\modules\api\models\FzrbsManuscriptscoringScore;
use app\modules\api\models\WeixinOaApprovaldata;
use app\modules\api\models\WeixinOaApprovalInfo;
use app\modules\api\models\WeixinOaApprovalLog;
use app\modules\api\models\WeixinOaAttachment;
use app\modules\api\models\WeixinOaDepartment;
use app\modules\api\models\WeixinOaFlowrole;
use app\modules\api\models\WeixinOaNotifyLog;
use app\modules\api\models\WeixinOAUserInfo;
use app\modules\weixin\Weixin;
use Exception;
use yii\db\Expression;



class ManuscriptscoringController extends ApiBase{
  public $modelClass = 'app\modules\api\models\WeixinOAUserInfo';
  protected $agentId= 1000086;
  protected $TEMPLATE = '77984eaf577295927e4fdeb78bd15a4c_1758702155';
  protected $statusCn = array('','审批中','已同意','已驳回','已取消');
  protected $userinfo = array();
  protected $LeaderRoleid = 17;
	protected $DirectorRoleid = 18;
  protected $appname='记者稿分';
  public function init()
  {
      parent::init();
      $this->userinfo = $this->getUserinfo($this->_adminInfo['wxuserid']);
  }



  public function actionCommit(){
    $datas= $this->_request['data'];
    if (!$datas) return array('errorMessage'=>'只能提交已全部评分的，无须评分的记者请删除或设置为0');
    $errorMessage='';


   
    foreach ($datas as $temp) {
      $data = FzrbsManuscriptscoringInfo::find()->where(['id'=>$temp['id'],'state'=>1])->one();
      if(!$data){
        $errorMessage .= '['.$temp['title'].']提交报错:该记录已打分<br>';
        continue;
      }
      $data['scores']=$temp['scores'];
      if($data['scores']) {
        $data['scores'] = json_encode($data['scores'],true);
      }else{
        $errorMessage .= '['.$temp['title'].']提交报错:记者打分部分内容为空<br>';
        continue;
      }
      try {
        $this->startflow($data);
      } catch (\Throwable $th) {
        $errorMessage .= '['.$data['title'].']提交报错:'.$th->getMessage().'<br>';
        continue;
      }
      
    }

    
  
    return array('errorMessage'=>$errorMessage);
  }
  public function actionCommitapply(){
    
    $datas= $this->_request['data'];
    if (!$datas) return array('errorMessage'=>'data为空');
    $errorMessage='';
    $marker = [];
    
    foreach ($datas as $temp) {
      $data = FzrbsManuscriptscoringInfo::findOne($temp['id']);
      $data['scores']=$temp['scores'];
      if($data['scores']) {
        $data['scores'] = json_encode($data['scores'],true);
      }else{
        $errorMessage .= '['.$temp['title'].']提交报错:记者打分部分内容为空<br>';
        continue;
      }
      try {
        
        if($data->state==0){
          $data->state=1;
          if (!in_array($temp['approvalUserid'],$marker)){
            $marker[]=$temp['approvalUserid'];
          }
        }

        $data->save();
      } catch (\Throwable $th) {
        $errorMessage .= '['.$data['title'].']提交报错:'.$th->getMessage().'<br>';
        continue;
      }
      
    }
    if ($marker){
      $this->send(implode('|',$marker),$this->userinfo['name'].'上传了记者打分项目，请及时处理',$data);
    }
    

    
  
    return array('errorMessage'=>$errorMessage);
  }

  public function actionDelbycatogory(){
    $datas= $this->_request['datas'];
    if (!$datas) return array('errorMessage'=>'datas为空');
    $errorMessage='';
    
    foreach ($datas as $temp) {
      if (!$temp['date']) {
        $errorMessage .= 'date不能为空<br>';
        continue;
      }
      try {
       
        FzrbsManuscriptscoringInfo::deleteAll(['date'=>$temp['date'],'state'=>0,'userId'=>$this->userinfo['userid']]);
      } catch (\Throwable $th) {
        $errorMessage .= '['.$temp['date'].']删除报错:'.$th->getMessage().'<br>';
        continue;
      }

    }
      
    return array('errorMessage'=>$errorMessage);
  }
  public function actionCommitbycatogory(){
    $datas= $this->_request['data'];
    if (!$datas) return array('errorMessage'=>'data为空');
    $errorMessage='';
 
    foreach ($datas as $temp) {
      if (!$temp['date']) {
        $errorMessage .= 'date不能为空<br>';
        continue;
      }
      // 判断打分人是否存在
      if ($temp['approvalUserid']){
        $temp['state']=1;
        $this->send($temp['approvalUserid'],$this->userinfo['name'].'上传了记者打分项目，请及时处理',$temp);
      }
      try {
        FzrbsManuscriptscoringInfo::updateAll(['state'=>1],['date'=>$temp['date'],'state'=>0,'userId'=>$this->userinfo['userid']]);
      } catch (\Throwable $th) {
        $errorMessage .= '['.$temp['date'].']提交报错:'.$th->getMessage().'<br>';
        continue;
      }

    }

      
    return array('errorMessage'=>$errorMessage);
  }

  
  public function actionDelinfo(){
    $ids= $this->_request['ids'];
    try {
      FzrbsManuscriptscoringInfo::deleteAll(['in','id',explode(',',$ids)]);
    } catch (\Throwable $th) {
      return array('errorMessage'=>$th->getMessage());
    }
    return array('errorMessage'=>'');
  }
  
  private function startflow($data){


        
        $transaction = Yii::$app->getDb()->beginTransaction();

        try {
          // 基本信息
          $data=new FzrbsManuscriptscoringInfo($data);
          $data['state']=2;
          FzrbsManuscriptscoringInfo::updateAll($data,['id'=>$data['id']]);
          $this->insertScore($data);


        } catch (\Throwable $th) {
          $transaction->rollBack();
          throw new Exception($th->getMessage());
        }
        $transaction->commit();
      
  }

  public function actionSave(){
    $obj = $this->_request;
    if ($this->_request['obj']){
      $obj = $this->_request['obj'];
    }
    try {
      if ($obj['id']){
      
      if ($obj['scores']){
        // 如果是数组，则转换成json字符串
        if (is_array($obj['scores'])){
          $obj['scores'] = json_encode($obj['scores'],true);
        }
      }
      FzrbsManuscriptscoringInfo::updateAll($obj,['id'=>$obj['id']]);
    }
    } catch (\Throwable $th) {
 
      return array('errorMessage'=>$th->getMessage());
    }
    return array('errorMessage'=>'');
    
  }
  public function actionUploaddatas(){
    $items = $this->_request['items'];
    $header = $this->_request['header'];
    $leader=$this->_request['leader'];
    // 较验数据不能为空
    if (!$items){
      return array('errorMessage'=>'上传数据为空');
    }
    if(!$header){
      return array('errorMessage'=>'行头为空');
    }
    if(!$leader){
      return array('errorMessage'=>'值班领导为空');
    }
    // 解析数据，获取发布时间、版次、正题、作者所在列的索引值
    $dateKey=-1;$titleKey=-1;$authorKey=-1;$editionKey=-1;$contentKey=-1;
    // 遍历header
    foreach ($header as $key=>$value) {
      if ($value=='发布时间'){
        $dateKey = $key;
      }else if ($value=='正题'){
        $titleKey = $key;
      }else if ($value=='作者'){
        $authorKey = $key;
      }else if ($value=='版次'){
        $editionKey = $key;
      }else if ($value=='正文'){
        $contentKey = $key;
      }
    }

    if($dateKey==-1){
      return array('errorMessage'=>'没有找到"发布时间"这一列');
    }
    if($titleKey==-1){
      return array('errorMessage'=>'没有找到"正题"这一列');
    }
    if($authorKey==-1){
      return array('errorMessage'=>'没有找到"作者"这一列');
    }
    if($editionKey==-1){
      return array('errorMessage'=>'没有找到"版次"这一列');
    }
   

    $errorMessage='';
    // 遍历items
    foreach ($items as $key => $row) {
      // 如果包含本报记者组
      if (preg_match('/本报记者组/u', $row[$authorKey])) {
          $ttt = array(
            'date'=>$row[$dateKey],
            'title'=>$row[$titleKey],
            'authorid'=>'本报记者组',
            'authorname'=>'本报记者组',
            'edition'=>$row[$editionKey],
            'approvalUserid'=>$leader['approvalUserid'],
            'approvalUsername'=>$leader['approvalUsername'],
            'userId'=>$this->userinfo['userid'],
            'userName'=>$this->userinfo['name'],
            'departmentid'=>$this->userinfo['departmentid'],
            'departmentname'=>$this->userinfo['departmentname'],
            
          );
          if($contentKey>-1){
              $ttt['content'] = $row[$contentKey];
          }
          $ttt['scores'] = json_encode([array('name'=>'本报记者组','userid'=>'本报记者组')]);
         
          $model = new FzrbsManuscriptscoringInfo($ttt);
          try {
            $model->save();
          } catch (\Throwable $th) {
            $errorMessage .= '第'.($key+1).'行保存失败：'.substr($th->getMessage(),0,20).'<br>';
          }

            continue;
        }
      $temp = $this->getReporter($row[$authorKey]);
      $authors = [];
      // 判断是否是本报记者
      if($temp){
        // 只获取与经办相关部门的记者
        $where = ['and',['in','name',$temp]];
        $authors =  WeixinOAUserInfo::find()->select('userid,name')->where($where)->all();
      }
      
      // 获取作者名称
      
      if(!$temp||!$authors){
        // $errorMessage .= '第'.($key+1).'行作者为【'.$row[$authorKey].'】，非本报记者<br>';
      }else{
        
        $ttt = array(
          'date'=>$row[$dateKey],
          'title'=>$row[$titleKey],
          'authorid'=>implode(',',array_column($authors,'userid')),
          'authorname'=>implode(',',array_column($authors,'name')),
          'edition'=>$row[$editionKey],
          'approvalUserid'=>$leader['approvalUserid'],
          'approvalUsername'=>$leader['approvalUsername'],
          'userId'=>$this->userinfo['userid'],
          'userName'=>$this->userinfo['name'],
          'departmentid'=>$this->userinfo['departmentid'],
          'departmentname'=>$this->userinfo['departmentname'],
        );
        // 判断是否已经存在
        $flag = FzrbsManuscriptscoringInfo::find()->where(['and',['=','date',$row[$dateKey]],['=','title',$row[$titleKey]]])->one();
        if ($flag){
          // $errorMessage .= '第'.($key+1).'行数据已存在,不要重复导入<br>';
          continue;
        }
        if($contentKey>-1){
          $ttt['content'] = $row[$contentKey];
          $photographers = $this->getPhotographer($ttt['content']);
          
          if ($photographers){
            $ttt['photographerid'] = implode(',',array_column($photographers,'userid'));
            $ttt['photographer'] = implode(',',array_column($photographers,'name'));
          }
        }
        $userids = explode(',',$ttt['authorid']);
        $ps = [];
        if ($ttt['photographerid']){
          $ps = explode(',',$ttt['photographerid']);
          $userids = array_merge($userids,$ps);
        }
        $where = ['and',['in','userid',$userids]];
        $dept = $this->getDepts();
        if ($dept){
          $where[] = ['in','departmentid',$dept];
        }
        $temp = WeixinOAUserInfo::find()->select('userid,name,mobile,departmentname,departmentid')->where($where)->asArray()->all();
        if (!$temp){
          continue;
        }
        for ($i=0; $i < count($temp); $i++) { 
          if (count($ps)){
      
            if (in_array($temp[$i]['userid'],$ps)){
 
              $temp[$i]['typename']='摄影记者';
            }
          }
        }
        
        $ttt['scores'] = json_encode($temp);
        $model = new FzrbsManuscriptscoringInfo($ttt);
        try {
          $model->save();
        } catch (\Throwable $th) {
          $errorMessage .= '第'.($key+1).'行保存失败：'.substr($th->getMessage(),0,20).'<br>';
        }
      }

    }


    
    // 保存$result
    return array('errorMessage'=>$errorMessage);
  }
  private function getPhotographer($text){
    if (!$text) return [];
    $pattern = '/(?:本报)?记者\s*([\x{4e00}-\x{9fa5}]{2,4})\s*摄/u';
    preg_match_all($pattern, $text, $matches);
    $names = $matches[1] ?? [];
    // 根据查询记者名字查询用户，并过滤掉影像中心的记者not in 

    if ($names){
      $where = ['and',['in','name',$names]];
      return WeixinOAUserInfo::find()->select('userid,name')->where($where)->asArray()->all();
    }
    return $names;
  }
   private function getflow($data){
  
    $userid = $this->_adminInfo['wxuserid'];
    $wfp = new WorkflowParse;
		$flowdata = $wfp->flowParse($userid, $this->TEMPLATE,array());
		$approvalids = array();
		foreach ($flowdata['ApprovalNodes']['ApprovalNode']  as $node){
			foreach($node['Items']['Item'] as $approval){
				$approvalids[]=$approval['ItemUserId'];
			}
		}
    $leaderid = $data['approvalUserid'];
		if(!in_array($leaderid,$approvalids)){
			$u = $this->getUserinfo($leaderid);
			$item = array(
				'ItemName' => $u['name'],
				'ItemImage' => $u['avatar'],
				'ItemUserId' => $u['userid'],
				'ItemStatus' => 1,
				'ItemOpTime' => 0
			);
			array_unshift($flowdata['ApprovalNodes']['ApprovalNode'],array(
				'NodeStatus' => 1,
				'Items' => array('Item'=>array($item)),
				'NodeAttr' => 1,
			));
		}


    return $flowdata;
  }
  public function actionGetflowdata(){
    $thirdNo = $this->_request['thirdNo'];
    $id=$this->_request['id'];
    if (!$thirdNo&&!$id){
      return array('errorMessage'=>'id和thirdNo不能都为空');
    }
    
    $viewdata=0;

    $wfp = new WorkflowParse($this->agentId);
    try {
     
      $where = [];
      if ($id){
        $where = ['i.id'=>$id];
      }else{
        $where = ['thirdNo'=>$thirdNo];
      }
      // 申请表信息
      $info = FzrbsManuscriptscoringInfo::find()->alias('i')->select('u.avatar,i.*')
        ->where($where)
        ->leftJoin(['u'=>WeixinOAUserInfo::tableName()],'u.userid=i.userId')
        ->asArray()->one();
      if (!$info['scores']){
        $temp = WeixinOAUserInfo::find()->select('userid,name,departmentid,departmentname')->where(['in','userid',explode(',',$info['authorid'])])->asArray()->all();
        // $temp所有选项添加score字段并设置值为0
        $temp = array_map(function($item) {
          $item['score'] = 0;
          return $item;
        }, $temp);
        $info['scores'] = $temp;
      }else{
        $info['scores'] = json_decode($info['scores'],true);
      }
      
      
      $viewdata = $wfp->flowViewdata($thirdNo);


    } catch (\Throwable $th) {
      return array('errorMessage'=> $th->getMessage());
    }
    
    return array('viewdata'=>$viewdata,'info'=>$info,'statusCn'=>$this->statusCn);

  }

  public function actionFlowact(){
    
    $postdatas = $this->_request;
    if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空');
    if (!$postdatas['act']) return array('errorMessage'=>'act为空,可选：agree,cancel,reject');
    
    switch ($postdatas['act']) {
      case 'agree':
        return $this->actionAgree();
      case 'cancel':
        return $this->actionCancel();
      case 'reject':
        return $this->actionReject();
      case 'urge':
        return $this->actionUrge();
      default:
        # code...
        break;
    }
  }
  public function actionReject(){//驳回
    $userid = $this->_adminInfo['wxuserid'];
    $postdatas = $this->_request;
    if (!$postdatas['speech']) return array('errorMessage'=>'审批意见不能为空');
    $data = FzrbsManuscriptscoringInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']]])->asArray()->one();

    // 是否是当前审批人
    if ($data['approvalUserid'] && !in_array($userid,explode('|',$data['approvalUserid']))){
      return array('errorMessage'=>'当前审批人是：'.$data['approvalUsername']);
    }
    
    $transaction = Yii::$app->getDb()->beginTransaction();
    try {
      $status=3;
      $ret = $this->changeStatus($userid,$data['id'],$postdatas['thirdNo'],$status,$postdatas);
      $this->updateAfterFlowChange($ret,$postdatas['thirdNo'],$status,$postdatas);
      
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=>$th->getMessage());
    }
    $transaction->commit();
    
    return array('data'=>array('ret'=>1));
  }
  public function actionAgree(){
    
    $postdatas = $this->_request;
   
    $userid = $this->_adminInfo['wxuserid'];
    
    if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空');
    $data = FzrbsManuscriptscoringInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']]])->asArray()->one();
 
    // 是否是当前审批人
    if ($data['approvalUserid'] && !in_array($userid,explode('|',$data['approvalUserid']))){
      return array('errorMessage'=>'当前审批人是：'.$data['approvalUsername']);
    }
    
    $status = 2;

    try {
      $ret = $this->changeStatus($userid,$data['id'],$postdatas['thirdNo'],$status,$postdatas);
      
      $this->updateAfterFlowChange($ret,$postdatas['thirdNo'],$status,$postdatas);


		
    } catch (\Throwable $th) {
      
      return array('errorMessage'=>$th->getMessage());
      // throw $th;
    }
    
    
    return array('data'=>$ret);
  }
  public function actionUrge(){
    $thirdNo=$this->_request['thirdNo'];
    if (!$thirdNo) return array('errorMessage'=>'thirdNo为空');

      $data = FzrbsManuscriptscoringInfo::find()->where(array('thirdNo'=>$thirdNo))->one();
     
      if($data){
   
        $this->send($data['approvalUserid'],'【催办】您有流程要审批!',$data);
      }
      return array('data'=>'催办成功');

  }
  public function actionCancel(){//撤消


      $userid = $this->_adminInfo['wxuserid'];
      $postdatas = $this->_request;
      if (!$postdatas['thirdNo']) return array('errorMessage'=>'thirdNo为空');
      
      $data = FzrbsManuscriptscoringInfo::find()->where(['and',['=','thirdNo',$postdatas['thirdNo']]])->one();

      // 只能本人撤消
      if ($data['userId']!=$userid){
        return array('errorMessage'=>'只能本人撤销');
      }


      $transaction = Yii::$app->getDb()->beginTransaction();
      try {
        $status=4;
        $ret = $this->changeStatus($userid,$data['id'],$postdatas['thirdNo'],$status,$postdatas);
        $this->updateAfterFlowChange($ret,$postdatas['thirdNo'],$status,$postdatas);
        
      } catch (\Throwable $th) {
        $transaction->rollBack();
        return array('errorMessage'=>$th->getMessage());
      }
      $transaction->commit();

      return array('data'=>array('ret'=>1));
	
	}
  private function getReporter($text){
    if (!$text) return [];
    $pattern = '/(?:本报)?记者\s*([^通讯员]+)(?=\s*通讯员|$)/u';
    preg_match($pattern, $text, $matches);
    if (isset($matches[1])) {
        $nameStr = trim($matches[1]);
        // 匹配2-4个中文字符作为姓名
        preg_match_all('/[\x{4e00}-\x{9fa5}]{2,4}/u', $nameStr, $nameMatches);
        return $nameMatches[0];
    }
    return [];
  }
  
  
  private function insertScore($data){
    
    $score = json_decode($data['scores'],true);
    try {
      foreach ($score as $key => $value) {
        $s = doubleval($score[$key]['score']);
        if ($s==0) continue;
        $score[$key]['infoid']=$data['id'];
        
        $score[$key]['date']=$data['date'];
        $score[$key]['score']=$s;
        $score[$key]['marker']=$this->userinfo['name'];
        $score[$key]['marktime']=date('Y-m-d H:i:s');
        $model = new FzrbsManuscriptscoringScore($score[$key]);
        $model->save();
      }
    } catch (\Throwable $th) {
      throw $th;
    }
  }

  private function changeStatus($userid,$id,$thirdNo,$status,$condition=array()){

    $ret = 0;
    $condition['agentId']=$this->agentId;
    $condition['userid']=$userid;
    $condition['status']=$status;
    $condition['thirdNo']=$thirdNo;

    $ret = $this->flowChange($condition);
    
    

    return $ret;
  }
  public function flowChange($par)
  {
       
        
      $agentid =$par['agentId']?$par['agentId']:$this->agentId;
      $thirdNo=$par['thirdNo'];
      $userid=$par['userid'];
      $status=$par['status'];
      $speech=$par['speech'];
      $act=$par['act'];

      if (!$thirdNo) throw new Exception('thirdNo 不能为空');
      $approveres = WeixinOaApprovaldata::find()->where(['and',["=","agentid",$agentid],["=","thirdNo",$thirdNo]])->asArray()->one();
      
      
      if($approveres){
        $optime = time();
        $ret = ['isfinish'=>0];
        $nextdata = [];
        $approvedata = [];
        $step = intval($approveres['step']);
        $approvearr = json_decode($approveres['data'],true);
      
        
     
        if(in_array($status,[3,4]) || ($status==2 && $step==count($approvearr['data']['ApprovalNodes']['ApprovalNode'])-1&&$approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeAttr']!=2)){//不是会签
          
          $approvearr['data']['OpenSpstatus'] = $status;
          $nextdata['status'] = $status;
          $approvedata['status'] = $status;

          if($status==2){
            $ret['isfinish']=1;
          }

          if($approveres['notifyAttr']>1){
            $notifyUserid = array();
            $notifyUsername = array();
            foreach($approvearr['data']['NotifyNodes']['NotifyNode'] as $notify){
              $notifyUserid[] = $notify['ItemUserId'];
              $notifyUsername[] = $notify['ItemName'];
            }
            $ret['tonotify'] = array('userid'=>$notifyUserid,'username'=>$notifyUsername);
          }
        }
        
        if(in_array($status,[2,3])){
          $logdata = array(
            'agentid'=>$agentid,
            'thirdNo'=>$thirdNo,
            'userId'=>$userid,
            'status'=>$status,
            'speech'=>$speech,
            'opTime'=>$optime
          );

          foreach($approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'] as $k=>$item){
            if($item['ItemUserId'] == $userid){
              $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeStatus'] = $status;

              $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'][$k]['ItemStatus'] = $status;
              
              $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'][$k]['ItemSpeech'] = $speech;
              $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'][$k]['ItemOpTime'] = $optime;
              $logdata['userName'] = $item['ItemName'];
              
            }
          }
        }
        $isNext = true;
        foreach($approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'] as $k=>$item){
          // 当前审批人无法执行，因为ItemStatus=2
          // 会签节点审批后不会通知同级审批人
          if($item['ItemStatus']==1 && $approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeAttr']==2){//是会签节点 
          
            $isNext = false;
            if($status==2)$approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['NodeStatus'] = 1;
          }
          
        }
        if (!$isNext) $status = 1;



        if($status==2 && $isNext){
          if ($step==count($approvearr['data']['ApprovalNodes']['ApprovalNode'])-1){
            $approvearr['data']['OpenSpstatus'] = $status;
            $nextdata['status'] = $status;
            $approvedata['status'] = $status;

            $ret['isfinish']=1;

            if($approveres['notifyAttr']>1){
              $notifyUserid = array();
              $notifyUsername = array();
              foreach($approvearr['data']['NotifyNodes']['NotifyNode'] as $notify){
                $notifyUserid[] = $notify['ItemUserId'];
                $notifyUsername[] = $notify['ItemName'];
              }
              $ret['tonotify'] = array('userid'=>$notifyUserid,'username'=>$notifyUsername);
            }
          }
          
          if($step<count($approvearr['data']['ApprovalNodes']['ApprovalNode'])-1)$step++;
          $approvearr['data']['approverstep'] = $step;
          $approvalUserid = array();
          $approvalUsername = array();
          foreach($approvearr['data']['ApprovalNodes']['ApprovalNode'][$step]['Items']['Item'] as $k=>$item){
            if ($item['ItemStatus']==1){
              $approvalUserid[] = $item['ItemUserId'];
              $approvalUsername[] = $item['ItemName'];
            }
            
          }
          $nextdata['approvalUserid'] = implode('|',$approvalUserid);
          $nextdata['approvalUsername'] = implode('|',$approvalUsername);
          $nextdata['approvalStep'] = $step;
          $ret['touser'] = $nextdata['approvalUserid'];
         
        }
       
        if($nextdata){
            $ret['nextdata'] = $nextdata;
        }
        
        
        $approvedata['data'] = json_encode($approvearr);
        $approvedata['step'] = $step;
        
        $approveres['status'] = $status;
        $approveres['data'] = $approvedata['data'];
        $approveres['step'] = $approvedata['step'];
        

        $ret['approveres']=$approveres;
        $ret['logdata']=$logdata;
      
        return $ret;
      }
      

		return 0;
  }

  
  public function actionGetdata(){
    $thirdNo = $this->_request['thirdNo'];
    $id=$this->_request['id'];
    $where=['and'];
    if ($thirdNo){
      $where[] = ['=','thirdNo',$thirdNo];
    }
    if ($id){
      $where[] = ['=','id',$id];
    }
    if (sizeof($where)==1){
      return array('errorMessage'=>'参数id或thirdNo不能都为空');
    }

    $data = FzrbsManuscriptscoringInfo::find()->where($where)->one();
    if(!$data){
      return array('errorMessage'=>'数据不存在');
    }
    return array('info'=>$data);
  }
  public function actionGetleaders(){
    $dept = $this->userinfo['departmentid'];
    $leaders=WeixinOaFlowrole::findBySql("SELECT  userid,username,userid as value,username as name  from ".WeixinOaFlowrole::tableName()." where FIND_IN_SET($dept,dept)  and role=".$this->LeaderRoleid)->asArray()->all();
    return array('list'=>$leaders);
  }


  private function getDepts(){
    $userid = $this->_adminInfo['wxuserid'];
    $power = '查看';
    $arr = array();
    $sql = "SELECT userid,dept from weixin_oa_flowrole where  userid='".$userid."' and  FIND_IN_SET(".$this->agentId.",agent)  and role in (select id from weixin_oa_role where FIND_IN_SET('$power',powername))";
    $result = WeixinOaFlowrole::findBySql($sql)->asArray()->all();

    if ($result) {
      
      foreach ($result as $e) {

        $arr = array_merge($arr,explode(',',$e['dept']));
      }
      $arr = array_unique($arr);
  
      $arr = array_map('intval', $arr);
      

    }
   
    
    return $arr;
  }



  public function actionListcatogory(){
    $userid = $this->_adminInfo['wxuserid'];
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = 20;
    $offset = $limit * ($page - 1);
    $orderby = 'date desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    $where = [
      'and',['>', 'id', 0],
    ];
    $state = 0;
    if (isset($this->_request['state'])){
      $state =$this->_request['state'];
    }
    $where[] = ['state' => $state];
    
    // 判断用户是否有查看权限
    $dept = [];$dept = $this->getDepts();
    
    switch (intval($state)) {
      case 0:
        # 查询我上传的
        $where[] = ['userId' => $userid];
        break;
      case 1:
        # 需要要打分的
        if (sizeof($dept)){
          
          $where[] = ['or',['in' , 'departmentid' , $dept],['approvalUserid'=>$userid]];
        }else {
          $where[] = ['approvalUserid'=>$userid];
        }
        break;
      case 2:
        # 查询与我相关的已打分的
        if (sizeof($dept)){
          $where[] = ['or',['in' , 'departmentid' , $dept],['approvalUserid'=>$userid],['userId' => $userid]];
        }else {
          $where[] = ['or',['approvalUserid'=>$userid],['userId' => $userid]];
        }
        
        break;
      default:
        # code...
        break;
    }

    if (isset($this->_request['startdate'])){
      $where[] = ['=','date', $this->_request['startdate']];
    }

    $model = FzrbsManuscriptscoringInfo::find()->select('date,userId,userName,approvalUserid,approvalUsername,state')->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->groupBy('date')->orderBy($orderby)->asArray()->all();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;

    foreach ($res as $k => $r) {
      $res[$k]['title']=$r['approvalUsername'];
      $res[$k]['scores'] = json_decode($r['scores'],true);
    }
    $_result['data'] = $res;
    return $_result;
  }


  public function actionList(){

    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = 100;
    $offset = $limit * ($page - 1);
    $orderby = 'edition asc,inserttime desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    $where = [
      'and',['>', 'id', 0],
    ];
    
    $state = 0;
    if (isset($this->_request['state'])){
      $state =$this->_request['state'];
    }
    $where[] = ['state' => $state];
    $dept = [];$dept = $this->getDepts();
    switch (intval($state)) {
      case 0:
        # 查询我上传的
        $where[] = ['userId' => $userid];
        break;
      case 1:
        # 需要要打分的
        if (sizeof($dept)){
          $where[] = ['or',['in' , 'departmentid' , $dept],['approvalUserid'=>$userid]];
        }else {
          $where[] = ['approvalUserid'=>$userid];
        }
        break;
      case 2:
        # 查询与我相关的已打分的
        if (sizeof($dept)){
          $where[] = ['or',['in' , 'departmentid' , $dept],['approvalUserid'=>$userid],['userId' => $userid]];
        }else {
          $where[] = ['or',['approvalUserid'=>$userid],['userId' => $userid]];
        }
      default:
        # code...
        break;
    }
    if (isset($this->_request['date'])){
      $where[] = ['=','date', $this->_request['date']];
    }
    if (isset($this->_request['startdate'])){
      $where[] = ['>=','date', $this->_request['startdate']];
    }
    if ($this->_request['keyword']){
      $keyword = $this->_request['keyword'];
      $where[] = new Expression("title like '%$keyword%' or authorname like '%$keyword%' or userName like '%$keyword%'");
    }

    
    $model = FzrbsManuscriptscoringInfo::find()->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;

    foreach ($res as $k => $r) {
      $res[$k]['scores'] = json_decode($r['scores'],true);
    }
    $_result['data'] = $res;
    return $_result;
  }
  public function actionExport(){
    $where = [
      'and',['>', 's.id', 0],
    ];
    $nowstr = date('Y-m-d');
    $wherestr = '';
    if (isset($this->_request['startdate'])){
      $where[] = ['>=','s.date', $this->_request['startdate']];
      $wherestr = " and date>='".$this->_request['startdate']."'";
    }else{
      $where[] = ['=','s.date', $nowstr];
      $wherestr = " and date='".$nowstr."'";
    }
    if (isset($this->_request['enddate'])){
      $where[] = ['<=','s.date', $this->_request['enddate']];
      $wherestr .= " and date<='".$this->_request['enddate']."'";
    }else if(!isset($this->_request['startdate'])){
  
      $where[] = ['=','s.date', $nowstr];
      $wherestr .= " and date='".$nowstr."'";
    }
    if ($this->_request['userid']){
      // 记者
      $where[] = $where[] = ['=','a.userid', $this->_request['userid']];
      $wherestr .= " and userid=".$this->_request['userid'];
    
    }
    if (sizeof($where)==2){
      return array('errorMessage'=>'请选择开始时间和结束时间或记者');
    }

    $res = FzrbsManuscriptscoringScore::find()->alias('s')->select("s.*,i.title,i.editor,i.edition,t.total")->where($where)
    ->leftJoin(FzrbsManuscriptscoringInfo::tableName().' i', 'i.id=s.infoid')
    ->leftJoin("(SELECT userid,sum(score) as total from fzrbs_manuscriptscoring_score where id>0 $wherestr GROUP BY userid) t",'t.userid=s.userid')
    ->orderBy('s.date asc,i.edition asc,s.name desc')->asArray()->all();

    $header = array(
      '日期','版次','篇目',
      '作者','稿分','总分'
    );
		$data = array_map(function($row) {

        return [
            $row['date'],
            $row['edition'],
            $row['title'],
            $row['name'],
            $row['score'],
            $row['total']
            
        ];
    }, $res);
    // 在最前面插入
    array_unshift($data, $header);
    return array('data'=>$data,'header'=>$header);
  }

  public function actionInglist(){

    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'updatetime desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    $where = ['and',new Expression("status=1 and LOCATE('|".$userid."|',CONCAT('|',approvalUserid,'|'))")];


    $model = FzrbsManuscriptscoringInfo::find()->where($where);
    
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    foreach ($res as $k => $r) {
      $res[$k]['scores'] = json_decode($r['scores'],true);
    }
    $_result['data'] = $res;
    return $_result;
  }
  public function actionFinishlist(){

    $userid = $this->_adminInfo['wxuserid'];
    
    $total = 0;
    $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
    $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
    $offset = $limit * ($page - 1);
    $orderby = 'updatetime desc';
    if (isset($this->_request['orderby'])){
      $orderby = $this->_request['orderby'];
    }
    $where = ['and',new  Expression("thirdNo in (SELECT distinct thirdNo FROM ".WeixinOaApprovalLog::tableName()." where userId='$userid'  and agentid=".$this->agentId.")")];

    $model = FzrbsManuscriptscoringInfo::find()->where($where);

  
    $total = $model->count();
    $res = $model->limit($limit)->offset($offset)->orderBy($orderby)->asArray()->all();
    $_result["current"] = $page;
    $_result["pageSize"] = $limit;
    $_result["total"] = $total;
    foreach ($res as $k => $r) {
      $res[$k]['scores'] = json_decode($r['scores'],true);
    }
    $_result['data'] = $res;
    return $_result;
  }

  public function actionGettabs(){
    return array(
      'activeTab'=>1,
      'data'=>[
            array(
              'name'=>'上传稿件',
              'route'=>'/manuscriptscoring/add'
            ),

            array(
              'name'=>'任务列表',
              'route'=>'/manuscriptscoring/mylist?tab=1'
            )
            ]
    );
  }
  
  private function getUserinfo($userid)
  {
      $userinfo = WeixinOAUserInfo::find()->where(['=', 'userid', $userid])->asArray()->one();
      return $userinfo;
  }
  private function send($approvalUserid,$title,$data){
    $url = "https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/manuscriptscoring/mylist&tab=1";
  

    if (!$approvalUserid) return;
    $msgdata = [
      'touser' => $approvalUserid,
      'msgtype' => 'textcard',
      'agentid' => $this->agentId,
      'textcard' => [
          'title' => $title,
          'description' => '<div class="normal">刊期：' . $data['date'].'</div>',
          'url' => $url,
          'btntxt' => '详情'
          
      ]
    ];
    $this->sendmsg($msgdata);
  }
  private function sendmsg($data)
  {
      // $content = '应用【'.$this->appname.'】有一条新的打分消息，请登录掌上福州查看';
      // WxQyhJk::sendMessage($data['agentid'],$data['touser'],$content,'text');
      WxQyhJk::sendMessage($data['agentid'],$data['touser'],$data['textcard'],'textcard');
  }
}