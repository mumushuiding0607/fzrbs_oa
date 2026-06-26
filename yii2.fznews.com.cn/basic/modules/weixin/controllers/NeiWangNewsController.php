<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;
use app\modules\api\models\WeixinNews;
use app\modules\api\models\WeixinNewsContent;
use app\modules\api\models\WeixinChannel;
use app\modules\api\models\WeixinNewsComment;
use app\modules\api\models\WeixinOAUserInfo;

/**
 * 报社内网资讯新闻类相关接口类
 */
class NeiWangNewsController extends ApiBase
{
    public $enableCsrfValidation = false;
    protected $_orderBy = 'id DESC';
    protected $_db = null;
    // 稿件表名
    protected $_table = 'fz_public_manuscript';
    // 稿件内容表名
    protected $_tableContent = 'fz_content';
    // 稿件点赞评论表名
    protected $_tableComment = 'weixin_xlxtcomment';

    public function init()
    {
        parent::init();
        $this->_db = Yii::$app->get('fzrbsnwdb');
    }

    /**
     * 新闻列表动作
     */
    public function actionList()
    {
        $list = $newsId = [];
        $fields = "id,title,shorttitle,writer,inserttime,publictime,image,click,remark,redirect,channelid";
        $fieldsArray = explode(',', $fields);
        $channelId = isset($this->_request['channelid']) ? $this->_request['channelid'] : 0;
        $where = [
            'and',
            ['=', 'state', 1],
        ];
        // 搜索
        $keywords = trim($this->_request['keywords']);
        if ($keywords) {
            $type = intval($this->_request['type']);
            $titleWhere = $contentWhere = [];
            if ($type) {
                $keywords = explode(',', str_replace(array('　', ' '), ',', $keywords));
                $titleWhere = ['or like', 'title', $keywords];
            }
            // 同时搜索内容
            if ($type == 2) {
                $contentWhere = ['or', $titleWhere];
                $newsId = [];
                $res = WeixinNewsContent::find()->where(['or like', 'content', $keywords])->all();
                foreach ($res as $row) {
                    $newsId[] = $row->newsid;
                }
                if (!$newsId) {
                    $newsId = [0];
                }
                $contentWhere[] = ['in', 'id', $newsId];
                $where[] = $contentWhere;
            } else {
                $where[] = $titleWhere;
            }
        }
        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        $where[] = ['=', 'channelid', $channelId];
        $query = new \yii\db\Query();
        $total = $query->from($this->_table)->where($where)->count('*', $this->_db);
        $res = $query->select($fieldsArray)->from($this->_table)->where($where)->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all($this->_db);
        if ($res) {
            $newsId = array_column($res, 'id');
            $commmentData = $this->_getCommmentNum($newsId);
            foreach ($res as $row) {
                $row['redirect'] = $row['redirect'] ? $row['redirect'] : '';
                $row['goodnum'] = isset($commmentData[$row['id']]) && isset($commmentData[$row['id']]['goodnum']) ? $commmentData[$row['id']]['goodnum'] : 0;
                $row['commentnum'] = isset($commmentData[$row['id']]) && isset($commmentData[$row['id']]['commentnum']) ? $commmentData[$row['id']]['commentnum'] : 0;
                $list[] = $row;
            }
        }
        $result['total'] = $total;
        $result['data'] = $list;
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result]);
    }

    /**
     * 新闻详情动作
     */
    public function actionView()
    {
        $id = $this->_request['id'];
        $where = ['and', ['=', 'state', 1], ['=', 'id', $id]];
        $query = new \yii\db\Query();
        $row = $query->select('*')->from($this->_table)->where($where)->one($this->_db);
        if ($row) {
            Yii::$app->fzrbsnwdb->createCommand()->update($this->_table, ['click' => new \yii\db\Expression('click + 1')], "id=:id", [":id" => $id])->execute();
            $row['click'] = $row['click'] + 1;
            $result = $row;
            $row1 = $query->select('*')->from($this->_tableContent)->where(['=', 'sid', $row['sid']])->one($this->_db);
            $result['content'] = $row1 ? Tools::handleMedia($row1['content'], $this->_request['mobile'], $this->_request['from']) : '';
            if (isset($this->_request['saveView'])) {
                $row2 = $query->select('*')->from($this->_tableComment)->where(['and', ['=', 'xid', $id], ['=', 'userid', $this->_UserId], ['=', 'view', 1]])->one();
                if (!$row2) {
                    Yii::$app->fzrbsnwdb->createCommand()->insert($this->_tableComment, [
                        'view' => 1,
                        'xid' => $id,
                        'userid' => $this->_UserId,
                        'username' => $this->_userInfo->name,
                        'inserttime' => date('Y-m-d H:i:s'),
                    ])->execute();
                }
            }
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result]);
    }

    /**
     * 评论点赞数据
     */
    public function actionComments()
    {
        $flag = isset($this->_request['flag']) ? $this->_request['flag'] : 1;
        $newsId = isset($this->_request['newsId']) ? $this->_request['newsId'] : 0;
        if ($newsId) {
            $table = $this->_tableComment;
            $table1 = WeixinOAUserInfo::tableName();
            $where = [
                'and',
                ['=', "$table.xid", $newsId],
            ];
            if ($flag == 2) {
                $where[] = ['=', "$table.goodnum", 1];
            } else if ($flag == 3) {
                $where[] = ['and', ['!=', "$table.comment", 'NULL'], ['!=', "$table.comment", '']];
            } else if ($flag == 4) {
                $where[] = ['=', "$table.view", 1];
            }
            $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
            $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
            $offset = $limit * ($page - 1);
            $query = new \yii\db\Query();
            $res = $query->select(["$table.id", "$table.xid", "$table.goodnum", "$table.comment", "$table.state", "$table.inserttime", "$table1.avatar", "$table1.name as username"])->from($table)->join('LEFT JOIN', $table1, "$table1.userid=$table.userid")->where($where)->limit($limit)->offset($offset)->orderBy("$table.id desc")->all();
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $res]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
        }
    }

    /**
     * 更新评论点赞数
     */
    public function actionUpdateComment()
    {
        $id = $this->_request['id'];
        $flag = $this->_request['flag'];
        $commnet = $this->_request['commnet'];
        if ($this->_UserId && $id) {
            $query = new \yii\db\Query();
            $row = $query->select('*')->from($this->_tableComment)->where(['and', ['=', 'xid', $id], ['=', 'userid', $this->_UserId]])->one();
            if ($row) {
                $update = false;
                if ($flag == 2 && !$row['goodnum']) {
                    Yii::$app->db->createCommand()->update($this->_tableComment, ['goodnum' => 1], "xid=:xid", [":xid" => $id])->execute();
                    $update = true;
                }
                if ($flag == 3) {
                    if (!$row['comment']) {
                        Yii::$app->db->createCommand()->update($this->_tableComment, ['comment' => $commnet], "xid=:xid", [":xid" => $id])->execute();
                    } else {
                        $data = ['inserttime' => date('Y-m-d H:i:s'), 'userid' => $this->_UserId, 'username' => $this->_userInfo->name, 'xid' => $id, 'comment' => $commnet];
                        Yii::$app->db->createCommand()->insert($this->_tableComment, $data)->execute();
                    }
                    $update = true;
                }
            } else {
                $update = true;
                $data = ['inserttime' => date('Y-m-d H:i:s'), 'userid' => $this->_UserId, 'username' => $this->_userInfo->name, 'xid' => $id];
                if ($flag == 2) {
                    $data['goodnum'] = 1;
                }
                if ($flag == 3) {
                    $data['comment'] = $commnet;
                }
                Yii::$app->db->createCommand()->insert($this->_tableComment, $data)->execute();
            }
            $this->_request['showGoodNum'] = $this->_request['showCommentNum'] = 1;
            $commmentData = $this->_getCommmentNum([$id]);
            $commmentData['update'] = $update;
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $commmentData]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
        }
    }


    /**
     * 小柳学堂信息上传
     */
    public function actionUpload()
    {
        $channelid = $this->_request['value']['channelid'];
        if ($this->_UserId && in_array($channelid, [13376, 13390, 13378, 13377])) {
            $currenttime = date('Y-m-d H:i:s');
            $result = ['success' => true, 'errorMessage' => '', 'errorCode' => 0];
            if (isset($this->_request['value'])) {
                $filenamepre = 'http://129.0.99.30/node/' . $channelid;
                $arr_filename = array(
                    "13376" => "http://129.0.99.30/xxyd/zxzxx",
                    "13390" => "http://129.0.99.30/xxyd/djt",
                    "13378" => "http://129.0.99.30/xxyd/bsxx",
                    "13377" => "http://129.0.99.30/xxyd/zbxx"
                );
                if (in_array($channelid, array_keys($arr_filename))) {
                    $filenamepre = $arr_filename[$channelid];
                }
                $data = array(
                    'channelid' => $channelid,
                    'title' => $this->_request['value']['title'],
                    'state' => '0',
                    'editorname' => $this->_userInfo['name'],
                    'editor' => $this->_UserId,
                    'inserttime' => $currenttime,
                    'updatetime' => $currenttime,
                    'publictime' => $currenttime,
                    'filename' => $filenamepre . '/' . date('Ymd') . '/' . uniqid() . '.shtml',
                    'type' => 0,
                    'click' => 0,
                    'oid' => 0,
                    'templateid' => '1312'
                );
                Yii::$app->fzrbsnwdb->createCommand()->insert('fz_manuscript', $data)->execute();
                $sid = Yii::$app->fzrbsnwdb->getLastInsertID();
                if ($sid) {
                    $data['sid'] = $sid;
                    Yii::$app->fzrbsnwdb->createCommand()->insert('fz_public_manuscript', $data)->execute();
                    Yii::$app->fzrbsnwdb->createCommand()->insert('fz_content', [
                        'content' => $this->_request['value']['content'],
                        'sid' => $sid,
                    ])->execute();
                }
            } else {
                $result['errorMessage'] = '参数错误';
                $result['errorCode'] = 1000;
            }
            Tools::responseJson($result);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
        }
    }

    /**
     * 获取新闻评论点赞数
     * @param array $newsId 新闻id
     * @return array
     */
    protected function _getCommmentNum($newsId)
    {
        $commnetNum = [];
        foreach ($newsId as $id) {
            $commnetNum[$id] = ['goodnum' => 0, 'flowernum' => 0, 'commentnum' => 0];
        }
        $query = new \yii\db\Query();
        if (isset($this->_request['showGoodNum']) || isset($this->_request['showGiftNum'])) {
            $res = $query->select(['xid', 'sum(goodnum) as goodnum'])->from($this->_tableComment)->where(['in', 'xid', $newsId])->groupBy('xid')->all();
            foreach ($res as $row) {
                $commnetNum[$row['xid']]['goodnum'] = $row['goodnum'];
            }
        }
        if (isset($this->_request['showCommentNum'])) {
            $res = $query->select(['xid', 'count(id) as commentnum'])->from($this->_tableComment)->where(['and', ['in', 'xid', $newsId], ['and', ['!=', "comment", 'NULL'], ['!=', "comment", '']]])->groupBy('xid')->all();
            foreach ($res as $row) {
                $commnetNum[$row['xid']]['commentnum'] = $row['commentnum'];
            }
        }
        return $commnetNum;
    }
}
