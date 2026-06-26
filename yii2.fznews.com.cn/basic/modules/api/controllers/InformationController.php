<?php

namespace app\modules\api\controllers;

use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\models\WeixinNewsContent;
use app\modules\api\models\WeixinChannel;
use app\modules\api\commons\WxQyhJk;

/**
 * 信息发布管理相关接口类
 */
class InformationController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinNews';
    protected $_orderBy = 'displayorder desc, sort desc';
    // 企业号应用通知配置
    protected $_appNoticeConfig = [
        // 身边榜样
        2 => ['agentid' => 1000004, 'url' => 'https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/news/list?channelid=2&group=1&goodAction=1&flowerAction=1&commentAction=1&agentid=1000004'],
        // 党员在一线
        67 => ['agentid' => 1000050, 'url' => 'https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/news/list?channelid=67&group=1&goodAction=1&flowerAction=1&commentAction=1&agentid=1000050'],
        // 重点选题情况
        44 => ['agentid' => 1000026, 'url' => 'https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/news/list?channelid=44&showTime=1&isLink=1&showView=1&agentid=1000026'],
        // 重点稿件传播情况
        45 => ['agentid' => 1000027, 'url' => 'https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/news/list?channelid=45&showTime=1&isLink=1&showView=1&agentid=1000027'],
        // 值班表
        46 => ['agentid' => 1000028, 'url' => 'https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/news/list/?channelid=46&showTime=1&isLink=1&agentid=1000028'],
        // 重要通知
        114 => ['agentid' => 1000005, 'url' => 'https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/news/list?channelid=114&showTime=1&isLink=1&showView=1&showViewUser=1&agentid=1000005'],
        // 文件传阅
        113 => ['agentid' => 1000006, 'url' => 'https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/news/list?channelid=113&showTime=1&isLink=1&showView=1&showViewUser=1&agentid=1000006'],
        // 新媒体重要通知
        121 => ['agentid' => 1000056, 'url' => 'https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=https://fzrb.fznews.com.cn/v2/news/list?channelid=121&showTime=1&isLink=1&showView=1&showViewUser=1&agentid=1000056'],
    ];
    // 特殊栏目发布字段定义
    protected $_fieldsConfig = [
        // 身边榜样
        'channel_2' => ['id' => 2, 'shorttitle' => '部门', 'redirect' => '推荐人', 'remark' => '点评'],
        // 党员在一线
        'channel_67' => ['id' => 67, 'shorttitle' => '部门', 'redirect' => '推荐人', 'remark' => '点评'],
    ];

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }

    public function init()
    {
        parent::init();
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
        $channelId = isset($this->_request['channelid']) ? $this->_request['channelid'] : 0;
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if (isset($this->_request['search'])) {
            // 搜索时是否搜索子栏目信息，默认否
            if ($channelId) {
                $where[] =  ['=', 'channelid', $channelId];
            }
        } else {
            if ($channelId) {
                $where[] =  ['=', 'channelid', $channelId];
            }
        }
        if (isset($this->_request['state'])) {
            $where[] = ['=', 'state', $this->_request['state']];
            if ($this->_request['state'] == -1 && $this->_adminInfo['usertype'] == 0) {
                $where[] = ['=', 'editorname', $this->_adminInfo['username']];
            }
        } else {
            $where[] = ['>', 'state', -1];
        }
        if ($this->_request['searchtitle']) {
            $where[] = ['like', 'title', $this->_request['searchtitle']];
        }
        if ($this->_request['searcheditor']) {
            $where[] = ['=', 'editor', $this->_request['searcheditor']];
        }
        if ($this->_request['inserttime']) {
            $inserttime = explode(',', $this->_request['inserttime']);
            $starTime = $inserttime[0] . ' 00:00:00';
            $endTime = $inserttime[1] . ' 23:59:59';
            $where[] = ['between', 'inserttime', $starTime, $endTime];
        }
        $model = $this->modelClass;
        $model = $model::find()->where($where);
        $total = $model->count();
        $res = $model->select(['id', 'title',  'editor', 'click', 'publictime', 'inserttime', 'displayorder', 'state'])->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        $this->_result['data'] = $res;
        return $this->_result;
    }

    /**
     * 重写create的业务实现动作
     */
    public function actionCreate()
    {
        if ($this->_request['values']) {
            $content = html_entity_decode($this->_request['values']['content']);
            unset($this->_request['values']['content']);
            $model = new $this->modelClass(['scenario' => 'create']);
            $model->attributes = $this->_request['values'];
            $model->editor = $this->_adminInfo['realname'];
            $model->editorname = $this->_adminInfo['username'];
            $model->inserttime = date('Y-m-d H:i:s');
            if (!$model->publictime) {
                $model->publictime = $model->inserttime;
            }
            $model->sort = $this->getSort($model->inserttime);
            $ruleResult = Tools::modelRules($model, 6000);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $modelContent = new WeixinNewsContent;
                    $modelContent->newsid = $model->id;
                    $modelContent->content = $content;
                    $modelContent->save();
                    $action = '新增';
                    $remark = $action . "信息。标题：" . $model->title . '。';
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
     * 重写update的业务实现动作
     */
    public function actionUpdate()
    {
        $id = intval($this->_request['id']);
        if ($id) {
            $content = html_entity_decode($this->_request['values']['content']);
            unset($this->_request['values']['content']);
            $model = $this->modelClass::findOne($id);
            $model->scenario = 'update';
            $oldTitle = $model->title;
            $model->attributes = $this->_request['values'];
            $ruleResult = Tools::modelRules($model, 6001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $modelContent = WeixinNewsContent::find()->where(['=', 'newsid', $id])->one();
                    if ($modelContent) {
                        $modelContent->content = $content;
                        $modelContent->save();
                    }
                    $action = '修改';
                    $remark = $action . "信息。" . ($oldTitle != $model->title ? '标题由 ' . $oldTitle . ' 改为 ' . $model->title . '。' : '');
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
        if ($this->_request['id']) {
            $ids = explode(',', $this->_request['id']);
            $titles = [];
            $models = $this->modelClass::find()->where(['in', 'id', $ids])->all();
            foreach ($models as $model) {
                $titles[] = $model->title;
                $model->state = -1;
                $model->update(false);
            }
            if ($titles) {
                WeixinNewsContent::deleteAll(['newsid' => $ids]);
                $action = '删除';
                $remark = $action . "信息。标题：" . implode('|||', $titles) . "。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 重写view的业务实现
     */
    public function actionView()
    {
        $id = $this->_request['id'];
        $where = ['=', 'id', $id];
        $model = $this->modelClass;
        $model = $model::find()->where($where)->one();
        if ($model) {
            $data = $model->attributes;
            $modelContent = WeixinNewsContent::find()->where(['=', 'newsid', $id])->one();
            $data['content'] = $modelContent ? $modelContent->content : '';
            if (isset($this->_request['flag'])) {
                $data['content'] = Tools::handleMedia($data['content']);
            }
            $this->_result['data'] = $data;
        }
        return $this->_result;
    }

    /**
     * 信息移动功能
     */
    public function actionCut()
    {
        $fromChannelId = $this->_request['fromChannelId'];
        $toChannelId = $this->_request['toChannelId'];
        $infoIds = $this->_request['infoIds'];
        if ($fromChannelId && $toChannelId && $infoIds) {
            $fromChannelModal = WeixinChannel::find()->where(['=', 'id', $fromChannelId])->one();
            $toChannelModal = WeixinChannel::find()->where(['=', 'id', $toChannelId])->one();
            $infoIds = explode(',', $infoIds);
            $titles = [];
            $models = $this->modelClass::find()->where(['in', 'id', $infoIds])->all();
            foreach ($models as $model) {
                $titles[] = $model->title;
                $model->channelid = $toChannelId;
                $model->update(false);
            }
            if ($titles) {
                $action = '移动';
                $remark = $action . "信息。标题：" . implode('|||', $titles) . "。" . ($fromChannelModal ? '从 ' . $fromChannelModal['name'] : '') . ($toChannelModal ? '移动到 ' . $toChannelModal['name'] : '');
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 信息撤销功能
     */
    public function actionRevoke()
    {
        $ids = $this->_request['ids'];
        if ($ids) {
            $ids = explode(',', $ids);
            $titles = [];
            $models = $this->modelClass::find()->where(['in', 'id', $ids])->all();
            foreach ($models as $model) {
                $titles[] = $model->title;
                $model->state = 0;
                $model->update(false);
            }
            if ($titles) {
                $action = '撤销';
                $remark = $action . "信息。标题：" . implode('|||', $titles);
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        }
        return $this->_result;
    }

    /**
     * 信息签发功能
     */
    public function actionIssued()
    {
        $ids = $this->_request['ids'];
        if ($ids) {
            $ids = explode(',', $ids);
            $titles = [];
            $publicTime = date('Y-m-d H:i:s');
            $models = $this->modelClass::find()->where(['in', 'id', $ids])->all();
            foreach ($models as $model) {
                $titles[] = $model->title;
                $model->state = 1;
                $model->publictime = $publicTime;
                $model->update(false);
            }
            if ($titles) {
                $action = '签发';
                $remark = $action . "信息。标题：" . implode('|||', $titles);
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        }
        return $this->_result;
    }

    /**
     * 拖动排序功能
     */
    public function actionSort()
    {
        $point = $this->_request['point'];
        $updateId = $this->_request['updateId'];
        $targetId = $this->_request['targetId'];
        if ($point && $updateId && $targetId) {
            $targetModel = $this->modelClass::find()->where(['=', 'id', $targetId])->one();
            $updateModel = $this->modelClass::find()->where(['=', 'id', $updateId])->one();
            if ($targetModel && $updateModel) {
                if ($point == 'top') {
                    Yii::$app->db->createCommand()->update($this->modelClass::tableName(), ['sort' => new \yii\db\Expression('sort + 0.003')], "sort>:sort", [':sort' => $targetModel->sort])->execute();
                    Yii::$app->db->createCommand()->update($this->modelClass::tableName(), ['sort' => $targetModel->sort + 0.002], "id=:id", [':id' => $updateModel->id])->execute();
                } else if ($point == 'bottom') {
                    Yii::$app->db->createCommand()->update($this->modelClass::tableName(), ['sort' => new \yii\db\Expression('sort + 0.003')], "sort>=:sort", [':sort' => $updateModel->sort])->execute();
                    Yii::$app->db->createCommand()->update($this->modelClass::tableName(), ['sort' => $updateModel->sort + 0.002], "id=:id", [':id' => $targetModel->id])->execute();
                }
            }
            $action = '拖动排序';
            $remark = $action . "信息。相关信息标题：" . $targetModel->title . '|||' . $updateModel->title;
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        }
        return $this->_result;
    }

    /**
     * 置顶/取消功能
     */
    public function actionTop()
    {
        $ids = $this->_request['ids'];
        if ($ids) {
            $titles = $titles1 = [];
            $models = $this->modelClass::find()->where(['in', 'id', $ids])->all();
            foreach ($models as $model) {
                if ($model->displayorder == 0) {
                    $model->displayorder = 1;
                    $titles[] = $model->title;
                } else {
                    $model->displayorder = 0;
                    $titles1[] = $model->title;
                }
                $model->update(false);
            }
            if ($titles || $titles1) {
                $action = '置顶/取消';
                $remark = $action . "信息。" . ($titles ? "置顶标题：" . implode('|||', $titles) . "。" : "") . ($titles1 ? "取消置顶标题：" . implode('|||', $titles1) : "");
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        }
        return $this->_result;
    }

    /**
     * 回收站删除功能
     */
    public function actionRecycleBinDelete()
    {
        if ($this->_request['id']) {
            $ids = explode(',', $this->_request['id']);
            $titles = [];
            $where = [
                'and',
                ['=', 'state', -1],
            ];
            $where[] = ['in', 'id', $ids];
            $models = $this->modelClass::find()->where($where)->all();
            foreach ($models as $model) {
                $titles[] = $model->title;
                $model->delete();
            }
            if ($titles) {
                WeixinNewsContent::deleteAll(['newsid' => $ids]);
                $action = '删除回收站';
                $remark = $action . "信息。标题：" . implode('|||', $titles) . "。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 回收站还原功能
     */
    public function actionRecycleBinReduction()
    {
        if ($this->_request['id']) {
            $ids = explode(',', $this->_request['id']);
            $titles = [];
            $where = [
                'and',
                ['=', 'state', -1],
            ];
            $where[] = ['in', 'id', $ids];
            $models = $this->modelClass::find()->where($where)->all();
            foreach ($models as $model) {
                $titles[] = $model->title;
                $model->state = 0;
                $model->update(false);
            }
            if ($titles) {
                $action = '还原回收站';
                $remark = $action . "信息。标题：" . implode('|||', $titles) . "。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 向微信企业应用发送新信息提醒
     */
    public function actionSendNotice()
    {
        $channelId = $this->_request['channelId'];
        if ($channelId) {
            $agentid = $this->_appNoticeConfig[$channelId]['agentid'];
            if ($agentid) {
                $content = '有新的信息发布。<a href="' . $this->_appNoticeConfig[$channelId]['url'] . '">查看</a>';
                $touser = '@all';
                $sendResult =  WxQyhJk::sendMessage($agentid, $touser, $content);
                if ($sendResult['errorMessage']) {
                    $this->_result['errorMessage'] = $sendResult['errorMessage'];
                }
            }
        }
        return $this->_result;
    }

    /**
     * 扩展工具按钮
     */
    public function actionExtendToolBar()
    {
        $customField = $this->_fieldsConfig;
        // 获取子栏目
        if (is_array($this->_fieldsConfig)) {
            foreach ($this->_fieldsConfig as $k => $v) {
                $channel = explode('_', $k);
                $id = $channel[1];
                if (in_array($id, [2, 67])) {
                    $tempField = $v;
                    $res = WeixinChannel::find()->where("INSTR(CONCAT(',',parentids,','),'," . $id . ",')>0")->all();
                    foreach ($res as  $v1) {
                        $tempField['id'] = $v1->id;
                        $customField['channel_' . $v1->id] = $tempField;
                    }
                }
            }
        }
        $this->_result['data'] = [
            'notice' => array_keys($this->_appNoticeConfig),
            'customfield' => $customField,
        ];
        return $this->_result;
    }

    /**
     * 获取sort值
     * 参数 $inserttime 稿件添加时间
     */
    private function getSort($inserttime)
    {
        $rand = floatval("0." . rand(1, 999));
        return strtotime($inserttime) + $rand;
    }
}
