<?php

namespace app\modules\api\controllers\apps;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use Yii;

/**
 * 资讯新闻类相关接口类
 */
class NewsController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinNews';
    protected $_orderBy = 'id desc';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete'], $actions['options']);
        return $actions;
    }

    public function init()
    {
        parent::init();
        $this->_checkUserBindWx();
    }

    /**
     * 重写index的业务实现动作
     */
    public function actionIndex()
    {
        $channelId = isset($this->_request['channelid']) ? $this->_request['channelid'] : 0;
        $current = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $pageSize = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $postData = ['channelid' => $channelId, 'current' => $current, 'pageSize' => $pageSize];
        if (isset($this->_request['group'])) {
            $postData['group'] = $this->_request['group'];
        }
        if (isset($this->_request['showCommentNum'])) {
            $postData['showCommentNum'] = $this->_request['showCommentNum'];
        }
        if (isset($this->_request['showGoodNum'])) {
            $postData['showGoodNum'] = $this->_request['showGoodNum'];
        }
        if (isset($this->_request['showGiftNum'])) {
            $postData['showGiftNum'] = $this->_request['showGiftNum'];
        }
        if (isset($this->_request['type'])) {
            $postData['type'] = $this->_request['type'];
        }
        if (isset($this->_request['keywords'])) {
            $postData['keywords'] = $this->_request['keywords'];
        }
        $url = Yii::$app->params['apiPrefix'] . 'weixin/news/list';
        $this->_result['data']['current'] = $current;
        $this->_result['data']['pageSize'] = $pageSize;
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $this->_result['data']['data'] = $result['data'];
            $this->_result['data']['total'] = $result['total'];
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }

    /**
     * 重写view的业务实现
     */
    public function actionView()
    {
        $id = $this->_request['id'];
        $postData = ['id' => $id];
        if (isset($this->_request['saveView'])) {
            $postData['wxuserid'] =  $this->_adminInfo['wxuserid'];
            $postData['saveView'] =  1;
        }
        $url = Yii::$app->params['apiPrefix'] . 'weixin/news/view';
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $this->_result['data'] = $result;
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }

    /**
     * 评论点赞数据
     */
    public function actionComments()
    {
        $flag = isset($this->_request['flag']) ? $this->_request['flag'] : 1;
        $newsId = isset($this->_request['newsId']) ? $this->_request['newsId'] : 0;
        $current = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $pageSize = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $postData = ['flag' => $flag, 'newsId' => $newsId, 'current' => $current, 'pageSize' => $pageSize];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/news/comments';
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $this->_result['data'] = $result;
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
            $this->_result['data'] = [];
        }
        return $this->_result;
    }

    /**
     * 更新评论点赞
     */
    public function actionUpdateComment()
    {
        $id = $this->_request['id'];
        $flag = $this->_request['flag'];
        $commnet = $this->_request['commnet'];
        $postData = ['wxuserid' => $this->_adminInfo['wxuserid'], 'id' => $id, 'flag' => $flag, 'commnet' => $commnet];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/news/update-comment';
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $this->_result['data'] = $result;
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }

    /**
     * 获取我的评论
     */
    public function actionMyComment()
    {
        $id = $this->_request['id'];
        $postData = ['wxuserid' => $this->_adminInfo['wxuserid'], 'id' => $id];
        $url = Yii::$app->params['apiPrefix'] . 'weixin/news/my-comment';
        $result = Tools::locaApi($postData, $url);
        if (!isset($result['errorMessage'])) {
            $this->_result['data'] = $result;
        } else {
            $this->_result['errorCode'] = $result['errorCode'];
            $this->_result['errorMessage'] = $result['errorMessage'];
        }
        return $this->_result;
    }
}
