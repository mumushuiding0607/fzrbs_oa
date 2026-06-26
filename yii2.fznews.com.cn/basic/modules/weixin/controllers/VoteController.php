<?php

namespace app\modules\weixin\controllers;

use Yii;

use app\modules\weixin\commons\ApiBase;
use app\modules\weixin\commons\Tools;

/**
 * 员工评议相关接口类
 */
class VoteController extends ApiBase
{
    public $enableCsrfValidation = false;
    protected $_orderBy = 'id desc';

    public function init()
    {
        parent::init();
    }

    /**
     * 员工评议列表动作
     */
    public function actionList()
    {
        $total = 0;
        $page = isset($this->_request['current']) ? intval($this->_request['current']) : 1;
        $limit = isset($this->_request['pageSize']) ? intval($this->_request['pageSize']) : 20;
        $offset = $limit * ($page - 1);
        $table = 'weixin_qzpy_vote_item';
        $where = "state > 0 and (INSTR( CONCAT( ',', participant, ',' ), '," . $this->_UserId . ",' ) > 0  or INSTR( CONCAT( ',', inviter, ',' ), '," . $this->_UserId . ",' ) > 0)";
        $query = new \yii\db\Query();
        $total = $query->from($table)->where($where)->count('*');
        $res = $query->select('id,title,starttime,endtime')->from($table)->where($where)->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
        $arr_vote_item = [];
        if ($res) {
            foreach ($res as $k => $val) {
                $item = $val;
                $res_my_vote = $query->select('id')->from('weixin_qzpy_vote_item_user_vote')->where(['and', ['=', 'iid', $val['id']], ['=', 'userid', $this->_UserId]])->one();
                $item['voteflag'] = $res_my_vote ? '已评议' : '未评议';
                $item['over'] = (strtotime($val['endtime']) > time() ? '0' : '1');
                $arr_vote_item[] = $item;
            }
        }
        $result['total'] = $total;
        $result['data'] = $arr_vote_item;
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result]);
    }

    /**
     * 评议详情动作
     */
    public function actionViewInfo()
    {
        $id = $this->_request['id'];
        $result = [];
        $query = new \yii\db\Query();
        $row = $query->select(['title', 'participant', 'inviter'])->from('weixin_qzpy_vote_item')->where(['and', ['=', 'state', 1], ['=', 'id', $id]])->one();
        if ($row) {
            $participant = explode(',', $row['participant']);
            $inviter = explode(',', $row['inviter']);
            if (!in_array($this->_UserId, $participant) && !in_array($this->_UserId, $inviter)) {
                Tools::responseJson(['success' => true, 'errorMessage' => '暂无权限参与此评议项目', 'errorCode' => 1000, 'data' => []]);
            }
            $result['title'] = $row['title'];
            $result['id'] = $id;
            $arr_user = $this->getUserInfo();
            $resSub = $query->select(['sid', 'stitle', 'userid', 'snum'])->from('weixin_qzpy_vote_item_sub')->where(['=', 'iid', $id])->orderBy('sid asc')->all();
            if ($resSub) {
                $pre_userid = [];
                foreach ($resSub as $k => $val) {
                    $explode_val_userid = explode(',', $val['userid']);
                    if ($pre_userid) {
                        foreach ($pre_userid as $v) {
                            $explode_val_userid = array_diff($explode_val_userid, $v);
                        }
                    }
                    $result_userinfo = [];
                    foreach ($explode_val_userid as $v) {
                        $result_userinfo[] = ['name' => $arr_user[$v], 'userid' => $v];
                    }
                    $subInfo[$k]['stitle'] = $val['stitle'];
                    $subInfo[$k]['snum'] = $val['snum'];
                    $subInfo[$k]['info'] = $result_userinfo;
                    $pre_userid[] = $explode_val_userid;
                }
                $result['data'] = $subInfo;
            }
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => '暂无数据', 'errorCode' => 1000, 'data' => []]);
        }
        Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => $result]);
    }

    /**
     * 保存评议记录
     */
    public function actionSaveVote()
    {
        $id = intval($this->_request['id']);
        $data = $this->_request['data'];
        $inserttime = date('Y-m-d H:i:s');
        if ($id && $data) {
            // 判断用户是否评议过
            $query = new \yii\db\Query();
            $resUserVote = $query->select(['id'])->from('weixin_qzpy_vote_item_user_vote')->where(['and', ['=', 'iid', $id], ['=', 'userid', $this->_UserId]])->one();
            if ($resUserVote) {
                Tools::responseJson(['success' => true, 'errorMessage' => '已评议过', 'errorCode' => 1000]);
            }
            $postDataUserArr = array_keys($data);

            // 判断是否邀请参与评议人员 投票
            $usertype = 1;// 默认 参与评议人员 为 1
            $inviterUserIdArr = [];
            $resInviterUserid = $query->select(['inviter'])->from('weixin_qzpy_vote_item')->where(['=', 'id', $id])->one();
            if ($resInviterUserid['inviter']) {
                $inviterUserIdArr = explode(',', $resInviterUserid['inviter']);
                if ( in_array($this->_UserId, $inviterUserIdArr) ) {
                    $usertype = 2;// inviter 邀请参与评议人员 为 2
                }
            }
            
            // 获取参评人员并保存评议数据
            $resSub = $query->select(['sid', 'stitle', 'snum', 'userid'])->from('weixin_qzpy_vote_item_sub')->where(['=', 'iid', $id])->all();
            $arr_insert_sql = [];
            $count_type_1 = [];
            if ($resSub) {
                $pre_userid = [];
                foreach ($resSub as $k => $val) {
                    $userGetArr = explode(',', $val['userid']);
                    if ($pre_userid) {
                        foreach ($pre_userid as $v) {
                            $userGetArr = array_diff($userGetArr, $v);
                        }
                    }
                    foreach ($userGetArr as $v) {
                        $getVoteData = [$id, $v, 2, $usertype, $inserttime];
                        if (in_array($v, $postDataUserArr)) {
                            $getVoteData[2] = 1;
                            // 获取优秀人数
                            if ($data[$v] == 1) {
                                $count_type_1[$val['sid']][] = ['stitle' => $val['stitle'], 'snum' => $val['snum']];
                            }
                        }
                        $arr_insert_sql[$val['sid']][] = $getVoteData;
                    }
                    $pre_userid[] = $userGetArr;
                }
                // 判断是否满足优秀员工人数条件
                foreach ($count_type_1 as $k => $v) {
                    $thisCount = count($v);
                    if ($thisCount > $v[0]['snum']) {
                        Tools::responseJson(['success' => true, 'errorMessage' => $v[0]['stitle'] . '最多只能选' . $v[0]['snum'] . '个优秀', 'errorCode' => 1000]);
                    }
                }
                $transaction = Yii::$app->getDb()->beginTransaction();
                try {
                    // 个人评议数据保存
                    $voteUserInfoData = ['iid' => $id, 'userid' => $this->_UserId, 'usertype' => $usertype, 'inserttime' => $inserttime];
                    Yii::$app->db->createCommand()->insert('weixin_qzpy_vote_item_user_vote', $voteUserInfoData)->execute();
                    // 记录被评议人信息
                    foreach ($arr_insert_sql as $val) {
                        Yii::$app->db->createCommand()->batchInsert('weixin_qzpy_vote_item_user_vote_log', ['iid', 'userid', 'type', 'usertype', 'inserttime'], $val)->execute();
                    }
                    $transaction->commit();
                } catch (\Exception $e) {
                    $transaction->rollBack();
                    Tools::responseJson(['success' => true, 'errorCode' => 65000, 'errorMessage' => $e->getMessage()]);
                }
            }
            Tools::responseJson(['success' => true, 'errorMessage' => '', 'errorCode' => 0, 'data' => []]);
        } else {
            Tools::responseJson(['success' => true, 'errorMessage' => '参数错误', 'errorCode' => 1000]);
        }
    }

    /**
     * 获取用户信息
     */
    protected function getUserInfo()
    {
        $query = new \yii\db\Query();
        $res_user = $query->select(['userid', 'name'])->from('weixin_leave_userinfo')->where(['>', 'id', 0])->all();
        $arr_user = [];
        if ($res_user) {
            foreach ($res_user as $v) {
                $arr_user[$v['userid']] = $v['name'];
            }
        }
        return $arr_user;
    }
}
