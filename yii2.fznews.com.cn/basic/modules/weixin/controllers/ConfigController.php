<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;
use app\modules\api\models\FzrbsRouteMenu;
use app\modules\weixin\commons\QYJssdk;
use app\modules\weixin\commons\QYGroupJssdk;

/**
 * 配置相关接口类
 */
class ConfigController extends ApiBase
{
    public $enableCsrfValidation = false;
    protected $_orderBy = 'id asc';
    protected $_appLink = [
        147 => '/news/list/?channelid=46&showTime=1&isLink=1&agentid=1000028',
        148 => '/news/list?channelid=44&showTime=1&isLink=1&showView=1&agentid=1000026',
        149 => '/news/list?channelid=45&showTime=1&isLink=1&showView=1&agentid=1000027',
        150 => '/news/info?id=941&agentid=1000047',
        152 => '/news/list?channelid=114&showTime=1&isLink=1&showView=1&showViewUser=1&agentid=1000005',
        153 => '/news/list?channelid=53&showTime=1&isLink=1&showView=1&agentid=1000043',
        154 => '/news/list?channelid=113&showTime=1&isLink=1&showView=1&showViewUser=1&agentid=1000006',
        155 => '/news/list?channelid=2&group=1&goodAction=1&flowerAction=1&commentAction=1&agentid=1000004',
        156 => '/news/list?channelid=67&group=1&goodAction=1&flowerAction=1&commentAction=1&agentid=1000050',
        174 => '/news/list?channelid=121&showTime=1&isLink=1&showView=1&showViewUser=1&agentid=1000056',
        180 => '/news/list?channelid=109&showTime=1&isLink=1&showView=1&agentid=1000069',
        170 => '/download/file?channelid=119&showTime=1&isLink=1&showView=1&agentid=1000012',
        145 => '/shitang/index',
        164 => 'https://api.fznews.com.cn/weixin/work-web-oauth/index?backurl=http://fzrb.fznews.com.cn/v2/shitang/live',
        171 => '/xingzheng/suggest',
        178 => '/xiaoliuxuetang/index',
        157 => '/yixianshengying/index?channelid=40&showTime=1&isLink=1&showView=1&showGoodNum=1&goodAction=1&agentid=1000025',
        172 => '/xingzheng/suggest',
    ];

    public function init()
    {
        parent::init();
    }

    /**
     * 企业微信应用动作
     */
    public function actionApps()
    {
        $apps = [];
        // 企业微信应用菜单id
        $parentId = 140;
        $where = [
            'and',
            ['>', 'id', 0],
            ['=', 'hideinmenu', 0],
            ['=', 'parentid', $parentId],
        ];
        $model = new FzrbsRouteMenu;
        $model = $model::find()->select('id, name, path')->where($where);
        $res = $model->orderBy($this->_orderBy)->all();
        foreach ($res as $row) {
            $route = ['id' => $row->id, 'name' => $row->name, 'path' => '#'];
            if (isset($this->_appLink[$row->id])) {
                $route['path'] = $this->_appLink[$row->id];
            }
            $children = $this->_getRouteMenuChildren($row->id, ['=', 'hideinmenu', 0]);
            if ($children) {
                $route['children'] = $children;
            }
            $apps[] = $route;
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $apps]);
    }

    /**
     * 企业微信 js sdk 动作
     */
    public function actionWeixin()
    {
        $url = $this->_request['url'];
        if ($url) {
            $jsSdk = new QYJssdk();
            $signPackage = $jsSdk->GetSignPackage($url);
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $signPackage]);
    }

    /**
     * 企业微信 js sdk 动作
     */
    public function actionWeixinAgent()
    {
        $url = $this->_request['url'];
        $agentId = $this->_request['agentId'];
        if ($url && $agentId) {
            $jsSdk = new QYJssdk($agentId);
            $signPackage = $jsSdk->getAgentIdSignPackage($url);
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $signPackage]);
    }

    /**
     * 企业微信 js sdk 动作
     */
    public function actionWeixinGroup()
    {
        $url = $this->_request['url'];
        if ($url) {
            $jsSdk = new QYGroupJssdk();
            $signPackage = $jsSdk->GetSignPackage($url);
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $signPackage, 'groupId' => $jsSdk->groupId]);
    }

    /**
     * 获取用户信息动作
     */
    public function actionUserInfo()
    {
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $this->_userInfo]);
    }

    /**
     * 获取路由菜单子节点
     * @param int $parentId 父节点id
     * @return array 子节点信息
     */
    protected function _getRouteMenuChildren($parentId, $otherCondition = [])
    {
        $where = [
            'and',
            ['=', 'parentid', $parentId],
        ];
        if ($otherCondition) {
            $where[] = $otherCondition;
        }
        $res = FzrbsRouteMenu::find()->where($where)->orderBy('inserttime asc')->all();
        $routes = [];
        foreach ($res as $row) {
            $route = ['id' => $row->id, 'name' => $row->name, 'path' => '#', 'image' => 'https://fzrb.fznews.com.cn' . $row->image];
            if (isset($this->_appLink[$row->id])) {
                $route['path'] = $this->_appLink[$row->id];
            }
            $routes[] = $route;
        }
        return $routes;
    }
}
