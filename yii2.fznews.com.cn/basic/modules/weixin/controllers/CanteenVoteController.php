<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;

/**
 * 食堂调查投票相关接口类
 */
class CanteenVoteController extends ApiBase
{
    public $enableCsrfValidation = false;
    protected $_orderBy = 'id desc';
    // 食堂开饭了应用id
    protected $_agentId = 1000002;

    public $_lingdao = ['陈滨峰', '谢联灵', '庄永强', '吴金垵', '卓良辉', '金麦子', '朱福星', '林雨夏', '赵 莹'];

    public function init()
    {
        parent::init();
        if (!$this->_UserId) {
            Tools::responseJson(['success' => true, 'errorMessage' => '用户信息获取失败', 'errorCode' => 1000]);
        }
    }

    /**
     * 投票弹窗动作
     */
    public function actionCheck()
    {
        $voteStartTime = '2024-11-26 00:00:00';
        $voteEndTime = '2024-12-05 23:59:59';

        // 领导不投票
        if (in_array($this->_userInfo['name'], $this->_lingdao)) {
            $voteStartTime = '';
            $voteEndTime = '';
        }

        $currentTime = time();
        // 投票期数 / 食堂评论期数
        $type = 17;
        // 投票通知期数
        $noticeVoteNum = 8;
        // 通知期数，不能与noticeVoteNum值相同
        $noticeNum = 7;

        // $url = '';
        // $voteMessage = '';
        $url = 'http://fzrb.fznews.com.cn/index.php?r=qiyehao/stpc/index2023';
        $voteMessage = '<h3 style="margin-bottom:15px">通知</h3><p style="text-align: left;">报社职工食堂2024年9月—2024年11月服务满意度调查从今日起至12月5日进行。为全面收集员工就餐需求情况，进一步提高食堂菜品质量，更好服务员工，请大家抽空对食堂调查栏目进行评分、留言。</p><p style="text-align: right;">报社膳食委员会</p><p style="text-align: right;">2024年11月26日</p>';
        

        $noticeMessage = '';
        // $noticeMessage = '<h3 style="margin-bottom:15px">通知</h3><p style="text-align: left;">今日菜单已上线，午餐采用现场点餐方式，带来的不便敬请谅解。</p><p style="text-align: left;"></p><p style="text-align: right;">社膳食委员会</p><p style="text-align: right;">2024年7月26日</p>';

        // $noticeMessage = '<h3 style="margin-bottom:15px">通知</h3><p style="text-align: left;">报社职工食堂试运营晚餐套餐点餐制（一周时间）。因线上点餐人数极少，为了节约成本，暂停运营套餐点餐制，同时，食堂将根据晚餐用餐人数情况，继续为员工提供现炒菜品、特色面食等。</p><p style="text-align: right;">报社膳食委员会</p><p style="text-align: right;">2024年7月5日</p>';

        // 弹窗是否可以忽略不在显示
        $ignore = true;
        $showCancelButton = true;
        $confirmButtonText = '知道了';
        $cancelButtonText = '忽略';
        $this->_result = ['success' => true, 'errorMessage' => '', 'errorCode' => 0];
        if ($voteStartTime && $voteEndTime && $currentTime > strtotime($voteStartTime)  && $currentTime < strtotime($voteEndTime)) {
            $confirmButtonText = '评分';
            
            // 是否投过
            $voteRow = (new \yii\db\Query())->select('id')->from('weixin_shitang_pinglun_2021')->where(['and', ['=', 'userid', $this->_UserId], ['>=', 'inserttime', $voteStartTime], ['<=', 'inserttime', $voteEndTime]])->one();
            // 是否忽略不在弹窗
            $noticeRow = (new \yii\db\Query())->select('id')->from('weixin_shitang_tongzhi_user')->where(['and', ['=', 'userid', $this->_UserId], ['=', 'no', $noticeVoteNum]])->one();
            if (!$voteRow && !$noticeRow) {
                $this->_result['data'] = [
                    'type' => 'vote',
                    'url' => $url,
                    'message' => $voteMessage,
                    'ignore' => $ignore,
                    'showCancelButton' => $showCancelButton,
                    'confirmButtonText' => $confirmButtonText,
                    'cancelButtonText' => $cancelButtonText,
                    'noticeNum' => $noticeVoteNum,
                ];
            }
        }
        if (!isset($this->_result['data']) && $noticeMessage) {
            $noticeRow = (new \yii\db\Query())->select('id')->from('weixin_shitang_tongzhi_user')->where(['and', ['=', 'userid', $this->_UserId], ['=', 'no', $noticeNum]])->one();
            if (!$noticeRow) {
                $this->_result['data'] = [
                    'type' => 'notice',
                    'url' => $url,
                    'message' => $noticeMessage,
                    'ignore' => $ignore,
                    'showCancelButton' => $showCancelButton,
                    'confirmButtonText' => $confirmButtonText,
                    'cancelButtonText' => $cancelButtonText,
                    'noticeNum' => $noticeNum,
                ];
            }
        }
        Tools::responseJson($this->_result);
    }

    /**
     * 投票弹窗状态记录动作
     */
    public function actionState()
    {
        $noticeNum = $this->_request['noticeNum'];
        if ($noticeNum) {
            $noticeRow = (new \yii\db\Query())->select('id')->from('weixin_shitang_tongzhi_user')->where(['and', ['=', 'userid', $this->_UserId], ['=', 'no', $noticeNum]])->one();
            if (!$noticeRow) {
                Yii::$app->db->createCommand()->insert('weixin_shitang_tongzhi_user', [
                    'userid' => $this->_UserId,
                    'no' => $noticeNum,
                    'state' => 2,
                    'inserttime' => date('Y-m-d H:i:s'),
                ])->execute();
            }
        }
        Tools::responseJson($this->_result);
    }
}
