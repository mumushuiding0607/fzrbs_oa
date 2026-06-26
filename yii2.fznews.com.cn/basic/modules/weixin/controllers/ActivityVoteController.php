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
 * 活动投票相关接口类
 */
class ActivityVoteController extends ApiBase
{
    public $enableCsrfValidation = false;
    protected $_orderBy = 'id desc';
    /** 
     * min 选项最少个数
     * max 选项最多个数
     * maxnum 是否必须选到最多个数
     * startTime 开始时间
     * endTime 结束时间
     * votetimes 总投票次数
     */
    protected $_config = [
        122 => [
            'min' => 2,
            'max' => 4,
            'maxnum' => true,
            'startTime' => '2024-07-31 09:30:00',
            'endTime' => '2024-07-31 23:30:00',
            'votetimes' => 1,
        ],
        150 => [
            151 => [
                'min' => 1,
                'max' => 1,
                'maxnum' => true,
            ],
            152 => [
                'min' => 1,
                'max' => 4,
                'maxnum' => false,
            ],
            'startTime' => '2024-06-07 09:30:00',
            'endTime' => '2024-06-07 09:30:00',
            'votetimes' => 1,
        ],
    ];

    protected $_pageConfig = [
        122 => [
            'backgroundColor' => '#53cc93',
            'headerImage' => 'https://fzrb.fznews.com.cn/assets/hnhgc/banner.jpg',
            'submitButtonColor' => '',
        ],
        150 => [
            'backgroundColor' => '#CC0033',
            'titleColor' => '#fff',
            'pagePaddtingTop' => '20px',
        ],
    ];

    public function init()
    {
        parent::init();
    }

    /**
     * 活动投票列表动作
     */
    public function actionList()
    {
        $list = [];
        $fields = 'id,title,shorttitle,writer,inserttime,publictime,image,click,click1,remark,num,redirect,channelid';
        $channelId = isset($this->_request['channelid']) ? $this->_request['channelid'] : 0;
        $where = [
            'and',
            ['=', 'state', 1],
        ];
        $model = new WeixinNews;
        if (!isset($this->_request['group'])) {
            $where[] = ['=', 'channelid', $channelId];
            $model = $model::find()->select($fields)->where($where);
            $res = $model->limit(10000)->offset(0)->orderBy('displayorder desc, sort desc')->all();
            // $channelName = Tools::getChannelName($channelId);
            $item = ['id' => $channelId, 'name' => '', 'list' => $res];
            if (isset($this->_config[$channelId])) {
                $item['config'] = $this->_config[$channelId];
            }
            $list[] = $item;
        } else {
            $where1 = ['=', 'parentid', $channelId];
            $channelModel = new WeixinChannel;
            $channelModels = $channelModel::find()->select('id, name')->where($where1)->limit(100)->offset(0)->orderBy('id asc')->all();
            foreach ($channelModels as $channelModel) {
                $newsModel = $model::find()->select($fields)->where(['and', ['=', 'state', 1], ['=', 'channelid', $channelModel->id]])->limit(10000)->offset(0)->orderBy('displayorder desc, sort desc')->all();
                $item = ['id' => $channelModel->id, 'name' => $channelModel->name, 'list' => $newsModel];
                if (isset($this->_config[$channelId][$channelModel->id])) {
                    $item['config'] = $this->_config[$channelId][$channelModel->id];
                } else {
                    $item['config'] = $this->_config[$channelId];
                }
                $list[] = $item;
            }
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $list]);
    }

    /**
     * 活动投票栏目详情动作
     */
    public function actionInfo()
    {
        $id = $this->_request['channelid'];
        $channelModal = new WeixinChannel;
        $channelModal = $channelModal::find()->select('id,name,image,content')->where(['=', 'id', $id])->one();
        $result = ['success' => true, 'errorMessage' => '', 'errorCode' => 0];
        if ($channelModal) {
            $result['data'] = $channelModal;
        }
        $result['config'] = [
            'limitChannelIds' => array_keys($this->_config),
        ];
        if (isset($this->_pageConfig[$id])) {
            $result['config']['pageConfig'] = $this->_pageConfig[$id];
        }
        Tools::responseJson($result);
    }

    /**
     * 活动投票保存
     */
    public function actionSave()
    {
        $channelId = isset($this->_request['channelid']) ? intval($this->_request['channelid']) : 0;
        $voteids = isset($this->_request['voteids']) ? $this->_request['voteids'] : false;
        $votenames = isset($this->_request['votenames']) ? $this->_request['votenames'] : false;
        $group = intval($this->_request['group']);
        if ($channelId && $this->_UserId && is_array($voteids) && is_array($votenames) && count($voteids) === count($votenames)) {
            $userVoteIds = $res = [];
            $startTime = isset($this->_config[$channelId]['startTime']) ? strtotime($this->_config[$channelId]['startTime']) : '';
            $endTime = isset($this->_config[$channelId]['endTime']) ? strtotime($this->_config[$channelId]['endTime']) : '';
            $now = time();
            if ($startTime && $now < $startTime) {
                Tools::responseJson(['success' => true, 'errorMessage' => '投票未开始', 'errorCode' => 1000]);
            }
            if ($endTime && $now > $endTime) {
                Tools::responseJson(['success' => true, 'errorMessage' => '投票已结束', 'errorCode' => 1000]);
            }
            $config = $this->_config[$channelId];
            foreach ($voteids as $k => $v) {
                if ($group && isset($this->_config[$channelId][$k])) {
                    $config = $this->_config[$channelId][$k];
                }
                if (isset($config['min']) && count($v) < $config['min']) {
                    Tools::responseJson(['success' => true, 'errorMessage' => $votenames[$k] . '至少选择 ' . $config['min'] . ' 个', 'errorCode' => 1000]);
                }
                if (isset($config['max']) && isset($config['maxnum']) && $config['maxnum'] && count($v) != $config['max']) {
                    Tools::responseJson(['success' => true, 'errorMessage' => $votenames[$k] . '选项个数不对，必须选 ' . $config['max'] . ' 个', 'errorCode' => 1000]);
                }
                $userVoteIds = array_merge($userVoteIds, $v);
            }
            // 是否已投过
            $row = (new \yii\db\Query())->select(['id'])->from('weixin_vote_record')->where(['and', ['=', 'user_id', $this->_UserId], ['=', 'channel_id', $channelId]])->one();
            if ($row && isset($this->_config[$channelId]['votetimes']) && $this->_config[$channelId]['votetimes'] == 1) {
                Tools::responseJson(['success' => true, 'errorMessage' => '每人只有一次投票机会', 'errorCode' => 1000]);
            }
            $insert = [
                'created' => date('Y-m-d H:i:s'),
                'user_id' => $this->_UserId,
                'vote_id' => implode(',', $userVoteIds),
                'channel_id' => $channelId
            ];
            $transaction = WeixinChannel::getDb()->beginTransaction();
            try {
                $row = Yii::$app->db->createCommand()->insert('weixin_vote_record', $insert)->execute();
                if ($row) {
                    WeixinNews::updateAll(['num' => new \yii\db\Expression('num + 1')], ['in', 'id', $userVoteIds]);
                }
                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();
                Tools::responseJson(['success' => true, 'errorCode' => 65000, 'errorMessage' => $e->getMessage()]);
            }
            $res = WeixinNews::find()->select(['id', 'num'])->where(['in', 'id', $userVoteIds])->all();
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $res]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
        }
    }
}
