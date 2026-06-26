<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;
use app\modules\api\models\FzrbsRouteMenu;

/**
 * 领导信箱相关接口类
 */
class LeaderMailController extends ApiBase
{
    public $enableCsrfValidation = false;
    protected $_orderBy = 'id asc';
    // ChenYiXiao
    protected $_receiveUsers = ["linwei"];

    public function init()
    {
        parent::init();
        if (!$this->_UserId) {
            Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
        }
    }

    /**
     * 添加动作
     */
    public function actionAdd()
    {
        if (isset($this->_request['value']) && $this->_request['value']['content']) {
            $content = str_replace(["\r\n", "\n"], "<br>", $this->_request['value']['content']);
            $data = [
                'content' => $content,
                'userid' => $this->_UserId,
                'username' => $this->_userInfo['name'],
                'created' => date("Y-m-d"),
                'receiveId' => implode(',', $this->_receiveUsers),
            ];
            $flag = Yii::$app->db->createCommand()->insert('weixin_leader_mail', $data)->execute();
            $lastId = Yii::$app->db->getLastInsertID();
            if ($flag) {
                $text = $this->_userInfo['name'] . '向您发送了一封邮件，记得<a href="https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=http://fzrb.fznews.com.cn/v2/lingdaoxinxiang/message?flag=receive&id=' . $lastId . '">查看</a>哦';
                try {
                    Tools::sendWxMessage(1000060, implode('|', $this->_receiveUsers), $text);
                } catch (\Exception $e) {
                    Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0]);
                }
            }
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
        }
    }

    /**
     * 投递接收的信息动作
     */
    public function actionMessage()
    {
        $userId = $this->_UserId;
        if ($this->_request['showUserId']) {
            $userId = $this->_request['showUserId'];
        }
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if ($this->_request['flag'] == 'receive') {
            $where[] = ['=', 'receiveId', $userId];
        } else {
            $where[] = ['=', 'userid', $userId];
        }
        $keyword = trim($this->_request['keyword']);
        $month = trim($this->_request['month']);
        $comment = intval($this->_request['comment']);
        $id = intval($this->_request['id']);
        if ($keyword) {
            $where[] = ['like', 'content', $keyword];
        }
        if ($month) {
            $where[] = ['like', 'created', $month];
        }
        if ($comment) {
            $where1 = ['and', ['>', 'id', 0]];
            if ($this->_request['flag'] == 'receive') {
                $where1[] = ['=', 'tp', 1];
            } else {
                $where1[] = ['=', 'tp', 2];
            }
            $subQuery = (new \yii\db\Query())->select('l_id')->from('weixin_leader_mail_comment')->where($where1);
            $where[] = ['id' => $subQuery];
        }
        if ($id) {
            $where[] = ['=', 'id', $id];
        }
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        $data = [];
        $res = (new \yii\db\Query())->select('*')->from('weixin_leader_mail')->where($where)->limit($limit)->offset($offset)->orderBy('id desc')->all();
        foreach ($res as $row) {
            $comment =  (new \yii\db\Query())->select('content,tp,username')->from('weixin_leader_mail_comment')->where(['=', 'p_id', $row['id']])->orderBy('id desc')->all();
            $commentCon = array_combine(array_column($comment, 'tp'), array_column($comment, 'content'));
            $commentPerson = array_combine(array_column($comment, 'tp'), array_column($comment, 'username'));
            $row['comment'] = $commentCon[1];
            $row['commentPerson'] = $commentPerson[1];
            $row['reply'] = $commentCon[2];
            $row['replyPerson'] = $commentPerson[2];
            $data[] = $row;
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $data]);
    }

    /**
     * 工作日志点评回复
     */
    public function actionComment()
    {
        $content = trim($this->_request['content']);
        $tp = intval($this->_request['tp']);
        $p_id = intval($this->_request['p_id']);
        if ($content && $tp && $p_id) {
            $data['content'] = str_replace(["\r\n", "\n"], "<br>", $content);
            $data['tp'] = $tp;
            $data['p_id'] = $p_id;
            $data['userid'] = $this->_UserId;
            $data['username'] = $this->_userInfo['name'];
            $data['created'] = date("Y-m-d H:i:s");
            $flag = Yii::$app->db->createCommand()->insert('weixin_leader_mail_comment', $data)->execute();
            if ($flag) {
                $row = (new \yii\db\Query())->select('created,userid')->from('weixin_leader_mail')->where(['=', 'id', $p_id])->one();
                if ($data['tp'] == 1) {
                    // 回复
                    $userid = $row['userid'];
                    $text = $this->_userInfo['name'] . '回复了你' . $row['log_date'] . '的信件哦,请及时<a href="https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=http://fzrb.fznews.com.cn/v2/lingdaoxinxiang/message?id=' . $p_id . '">查看</a>。';
                } else {
                    // 回复
                    $row1 = (new \yii\db\Query())->select('userid')->from('weixin_leader_mail_comment')->where(['and', ['=', 'id', $p_id], ['=', 'tp', 1]])->one();
                    $userid = $row1['userid'];
                    $text =  $this->_userInfo['name'] . '回复了您对他' . $row['log_date'] . '信件的答复,请及时<a href="https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=http://fzrb.fznews.com.cn/v2/lingdaoxinxiang/message?flag=receive&id=' . $p_id . '">查看</a>。';
                }
                try {
                    Tools::sendWxMessage(1000060, $userid, $text);
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
     * 回复内容
     */
    public function actionCommentInfo()
    {
        $tp = intval($this->_request['tp']);
        $p_id = intval($this->_request['p_id']);
        if ($tp && $p_id) {
            $row = (new \yii\db\Query())->select('content')->from('weixin_leader_mail_comment')->where(['and', ['=', 'tp', $tp], ['=', 'p_id', $p_id]])->one();
            if ($row) {
                $this->_result['content'] = str_replace('<br>', "\r\n", $row['content']);
            }
            Tools::responseJson($this->_result);
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
    }
}
