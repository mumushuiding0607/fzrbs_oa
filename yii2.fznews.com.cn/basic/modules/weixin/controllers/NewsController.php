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
use app\modules\weixin\commons\SimpleHtmlDom;

/**
 * 资讯新闻类相关接口类
 */
class NewsController extends ApiBase
{
    public $enableCsrfValidation = false;
    protected $_orderBy = 'id desc';

    public function init()
    {
        parent::init();
    }

    /**
     * 新闻列表动作
     */
    public function actionList()
    {
        $list = $newsId = [];
        $fields = 'id,title,shorttitle,writer,inserttime,publictime,image,click,click1,remark,num,redirect,channelid';
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
        $model = new WeixinNews;
        if (!isset($this->_request['group'])) {
            $where[] = ['=', 'channelid', $channelId];
            $model = $model::find()->select($fields)->where($where);
            $total = $model->count();
            $result['total'] = $total;
            $res = $model->limit($limit)->offset($offset)->orderBy('displayorder desc, sort desc')->all();
            $list = $res;
            if ($res && (isset($this->_request['showCommentNum']) || isset($this->_request['showGoodNum']) || isset($this->_request['showGiftNum']))) {
                $newsId = array_column($res, 'id');
                $commmentData = $this->_getCommmentNum($newsId);
                if ($commmentData) {
                    foreach ($list as $k => $row) {
                        $list[$k] = $this->_setItemCommmentNum($row, $commmentData);
                    }
                }
            }
            foreach ($list as $k => $row) {
                $item = ['goodnum' => $row->goodnum, 'flowernum' => $row->flowernum, 'commentnum' => $row->commentnum];
                foreach ($fieldsArray as $field) {
                    $item[$field] = $row[$field];
                }
                if (strpos($row->image, 'assets') !== false) {
                    $item['image'] = 'https://fzrb.fznews.com.cn/' . trim($row->image, '/');
                }
                // 表单下载处理
                if ($channelId == 119) {
                    $modelContent = WeixinNewsContent::find()->where(['=', 'newsid', $row['id']])->one();
                    if ($modelContent && $modelContent->content) {
                        $dom = new SimpleHtmlDom(null, true, true, 'utf-8', true, "\r\n", " ");
                        $dom->load($modelContent->content);
                        foreach ($dom->find('a') as $element) {
                            $fileUrl = $element->href;
                        }
                        $item['fileurl'] = $fileUrl;
                        if ($fileUrl) {
                            $ext = strtolower(strrchr($fileUrl, '.'));
                            if (in_array($ext, [".doc", ".docx"])) {
                                $item['filetype'] = 'https://fzrb.fznews.com.cn/assets/images/word.png';
                            } else if (in_array($ext, [".xls", ".xlsx"])) {
                                $item['filetype'] = 'https://fzrb.fznews.com.cn/assets/images/excel.png';
                            } else if (in_array($ext, [".pdf"])) {
                                $item['filetype'] = 'https://fzrb.fznews.com.cn/assets/images/pdf.png';
                            } else {
                                $item['filetype'] = 'https://fzrb.fznews.com.cn/assets/images/txt.png';
                            }
                        }
                    }
                }
                $list[$k] = $item;
            }
            $result['data'] = $list;
        } else {
            $where1 = ['=', 'parentid', $channelId];
            $channelModal = new WeixinChannel;
            $channelModal = $channelModal::find()->select('id, name')->where($where1);
            $total = $channelModal->count();
            $res = $channelModal->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
            $channelData = [];
            foreach ($res as $row) {
                $channelData[$row->id] = $row->name;
            }
            $result['total'] = $total;
            $news = [];
            $channelIds = [];
            $where[] = ['in', 'channelid', array_keys($channelData)];
            $res = $model::find()->select($fields)->where($where)->orderBy('channelid desc, displayorder desc, sort desc')->all();
            $list = $res;
            if ($res && (isset($this->_request['showCommentNum']) || isset($this->_request['showGoodNum']) || isset($this->_request['showGiftNum']))) {
                $newsId = array_column($res, 'id');
                $commmentData = $this->_getCommmentNum($newsId);
                if ($commmentData) {
                    foreach ($list as $k => $row) {
                        $list[$k] = $this->_setItemCommmentNum($row, $commmentData);
                    }
                }
            }
            foreach ($list as $row) {
                $item = ['goodnum' => $row->goodnum, 'flowernum' => $row->flowernum, 'commentnum' => $row->commentnum];
                foreach ($fieldsArray as $field) {
                    $item[$field] = $row[$field];
                }
                if (strpos($item['image'], 'assets') !== false) {
                    $item['image'] = 'https://fzrb.fznews.com.cn/' . trim($item['image'], '/');
                }
                if (!in_array($row->channelid, $channelIds)) {
                    $news[$row->channelid] = ['id' => $row->channelid, 'title' => $channelData[$row->channelid], 'children' => [$item]];
                    $channelIds[] = $row->channelid;
                } else {
                    $news[$row->channelid]['children'][] = $item;
                }
            }
            $result['data'] = array_values($news);
        }
        $documentTitle = Tools::getChannelName($channelId);
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result, 'documentTitle' => $documentTitle]);
    }

    /**
     * 新闻详情动作
     */
    public function actionView()
    {
        $id = $this->_request['id'];
        $where = ['and', ['=', 'state', 1], ['=', 'id', $id]];
        $model = new WeixinNews;
        $model = $model::find()->where($where)->one();
        if ($model) {
            $model->click = $model->click + 1;
            $model->save();
            $result = $model->attributes;
            $modelContent = WeixinNewsContent::find()->where(['=', 'newsid', $id])->one();
            $result['content'] = $modelContent ? Tools::handleMedia($modelContent->content, $this->_request['mobile']) : '';
            if ($this->_UserId && isset($this->_request['saveView'])) {
                $commentModel = WeixinNewsComment::find()->where(['and', ['=', 'newsid', $id], ['=', 'userid', $this->_UserId], ['=', 'view', 1]])->one();
                if (!$commentModel) {
                    $commentModel = new WeixinNewsComment();
                    $commentModel->view =  1;
                    $commentModel->newsid = $id;
                    $commentModel->userid = $this->_UserId;
                    $commentModel->username = $this->_userInfo->name;
                    $commentModel->inserttime = date('Y-m-d H:i:s');
                    $commentModel->save();
                }
            }
            $documentTitle = '详情';
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result, 'documentTitle' => $documentTitle]);
    }

    /**
     * 评论点赞数据
     */
    public function actionComments()
    {
        $flag = isset($this->_request['flag']) ? $this->_request['flag'] : 1;
        $newsId = isset($this->_request['newsId']) ? $this->_request['newsId'] : 0;
        if ($newsId) {
            $table = WeixinNewsComment::tableName();
            $table1 = WeixinOAUserInfo::tableName();
            $where = [
                'and',
                ['=', "$table.newsid", $newsId],
            ];
            if ($flag == 1) {
                $where[] = ['=', "$table.flower", 1];
            } else if ($flag == 2) {
                $where[] = ['=', "$table.good", 1];
            } else if ($flag == 3) {
                $where[] = ['!=', "$table.content_P", ''];
            } else if ($flag == 4) {
                $where[] = ['=', "$table.view", 1];
            }
            $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
            $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
            $offset = $limit * ($page - 1);
            $query = new \yii\db\Query();
            $res = $query->select(["$table.*", "$table1.avatar"])->from($table)->join('LEFT JOIN', $table1, "$table1.userid=$table.userid")->where($where)->limit($limit)->offset($offset)->orderBy("$table.id desc")->all();
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
            $model = WeixinNewsComment::find()->where(['and', ['=', 'newsid', $id], ['=', 'userid', $this->_UserId]])->one();
            if ($model) {
                $update = false;
                if ($flag == 1 && !$model->flower) {
                    $model->flower = $model->flower + 1;
                    $update = true;
                }
                if ($flag == 2 && !$model->good) {
                    $model->good = $model->good + 1;
                    $update = true;
                }
                if ($flag == 3) {
                    $model->content_P = $commnet;
                    $update = true;
                }
            } else {
                $update = true;
                $model = new WeixinNewsComment();
                $flag == 1 && $model->flower = $model->flower + 1;
                $flag == 2 && $model->good = $model->good + 1;
                $flag == 3 && $model->content_P = $commnet;
                $model->newsid = $id;
                $model->userid = $this->_UserId;
                $model->username = $this->_userInfo->name;
                $model->inserttime = date('Y-m-d H:i:s');
            }
            $model->save();
            $this->_request['showGoodNum'] = $this->_request['showGiftNum'] = $this->_request['showCommentNum'] = 1;
            $commmentData = $this->_getCommmentNum([$id]);
            $commmentData['update'] = $update;
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $commmentData]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
        }
    }

    /**
     * 获取我的评论
     */
    public function actionMyComment()
    {
        $id = $this->_request['id'];
        if ($this->_UserId && $id) {
            $model = WeixinNewsComment::find()->where(['and', ['=', 'newsid', $id], ['=', 'userid', $this->_UserId]])->one();
            $comment = $model ? $model->content_P : '';
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $comment]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
        }
    }

    /**
     * 获取栏目名称
     */
    public function actionChannelName()
    {
        $documentTitle = '';
        $id = intval($this->_request['channelid']);
        if ($id) {
            $documentTitle = Tools::getChannelName($id);
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'documentTitle' => $documentTitle]);
    }

    /**
     * 用户上传信息保存
     */
    public function actionUpload()
    {
        $channelid = $this->_request['value']['channelid'];
        if ($this->_UserId && in_array($channelid, [40])) {
            $currenttime = date('Y-m-d H:i:s');
            if (isset($this->_request['value']['worktime'])) {
                $currenttime = $this->_request['value']['worktime'] . ":00";
            }
            $result = ['success' => true, 'errorMessage' => '', 'errorCode' => 0];
            if (isset($this->_request['value'])) {
                $content = $this->_request['value']['content'];
                $data = array(
                    'title' => $this->_request['value']['title'],
                    'redirect' => $currenttime,
                    'shorttitle' => $this->_request['value']['address'],
                    'writer' => $this->_userInfo['name'],
                    'channelid' => 40,
                    'state' => 1,
                );
                if ($this->_request['value']['image']) {
                    $imgs = '';
                    $attachments = explode(',', $this->_request['value']['image']);
                    foreach ($attachments as $k => $v) {
                        $imgs .= '<p align="center"><img src="' . $v . '"></p>';
                    }
                    $content .= $imgs;
                }
                Yii::$app->db->createCommand()->insert('weixin_news', $data)->execute();
                $sid = Yii::$app->db->getLastInsertID();
                if ($sid) {
                    Yii::$app->db->createCommand()->insert('weixin_newscontent', [
                        'content' => $content,
                        'newsid' => $sid,
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
            $res = $query->select(['newsid', 'sum(good) as goodnum', 'sum(flower) as flowernum'])->from(WeixinNewsComment::tableName())->where(['in', 'newsid', $newsId])->groupBy('newsid')->all();
            foreach ($res as $row) {
                $commnetNum[$row['newsid']]['goodnum'] = $row['goodnum'];
                $commnetNum[$row['newsid']]['flowernum'] = $row['flowernum'];
            }
        }
        if (isset($this->_request['showCommentNum'])) {
            $res = $query->select(['newsid', 'count(id) as commentnum'])->from(WeixinNewsComment::tableName())->where(['and', ['in', 'newsid', $newsId], ['!=', 'content_P', '']])->groupBy('newsid')->all();
            foreach ($res as $row) {
                $commnetNum[$row['newsid']]['commentnum'] = $row['commentnum'];
            }
        }
        return $commnetNum;
    }

    /**
     * 设置新闻评论点赞数
     * @param object $item 单条新闻数据
     * @param array $commmentData 获取到的新闻评论点赞数
     * @return array
     */
    protected function _setItemCommmentNum($item, $commmentData)
    {
        $goodnum = $flowernum = $commentnum = 0;
        if (isset($commmentData[$item->id])) {
            $goodnum = $commmentData[$item->id]['goodnum'];
            $flowernum = $commmentData[$item->id]['flowernum'];
            isset($commmentData[$item->id]['commentnum']) && $commentnum = $commmentData[$item->id]['commentnum'];
        }
        $item->goodnum = $goodnum;
        $item->flowernum = $flowernum;
        $item->commentnum = $commentnum;
        return $item;
    }
}
