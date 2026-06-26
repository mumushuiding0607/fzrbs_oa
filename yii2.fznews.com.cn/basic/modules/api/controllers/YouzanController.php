<?php

namespace app\modules\api\controllers;

use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use Yii;

/**
 * 好物福州接口类
 */
class YouzanController extends ApiBase
{

    public $modelClass = 'app\modules\api\models\FzrbsAdmin';
    protected $clientId = '937052daba29354b65';
    protected $clientSecret = '0db3fc6766ca408038835ea35f0d5983';
    protected $kdtId = '44755311';
    protected $resp = [];

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
        $where = [
            'and',
            ['>', 'id', 0],
        ];
        if ($this->_request['name']) {
            $where[] = ['like', 'name', $this->_request['name']];
        }
        if (isset($this->_request['status'])) {
            $where[] = ['=', 'status', $this->_request['status']];
        }
        $total = (new \yii\db\Query())->from('fzrbs_hwfz_group')->where($where)->count('*');
        $res = (new \yii\db\Query())->select('*')->from('fzrbs_hwfz_group')->where($where)->limit($limit)->offset($offset)->orderBy('status desc,sort desc')->all();
        $this->_result["current"] = $page;
        $this->_result["pageSize"] = $limit;
        $this->_result["total"] = $total;
        $this->_result['data'] = $res;
        return $this->_result;
    }

    // 同步商品分组接口
    public function actionAsynchronizationGroup()
    {
        $this->_getToken();
        if (isset($this->resp['access_token'])) {
            $accessToken = $this->resp['access_token'];
            $client = new \Youzan\Open\Client($accessToken);
            $method = 'youzan.itemcategories.taglist.search';
            $apiVersion = '3.0.0';
            $params = [
                "page_no" => "1",
                "page_size" => "100"
            ];
            $response = $client->post($method, $apiVersion, $params);
            if ($response['data'] && $response['data']['tags']) {
                $query = new \yii\db\Query();
                foreach ($response['data']['tags'] as $tag) {
                    $tag['groupid'] = $tag['id'];
                    $tag['sort'] = $this->getSort($tag['created']);
                    unset($tag['type']);
                    unset($tag['id']);
                    $row = $query->select('id')->from('fzrbs_hwfz_group')->where(['and', ['=', 'groupid', $tag['groupid']]])->one();
                    if (!$row) {
                        Yii::$app->db->createCommand()->insert('fzrbs_hwfz_group',  $tag)->execute();
                    } else {
                        Yii::$app->db->createCommand()->update('fzrbs_hwfz_group',  $tag, "groupid=:groupid", [':groupid' => $tag['groupid']])->execute();
                    }
                }
            } else {
                $this->_result = Tools::wrongRules(1000, $response['message']);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '接口签名出错');
        }
        return $this->_result;
    }

    /**
     * update商品分组的业务实现动作
     */
    public function actionUpdateGroup()
    {
        $id = intval($this->_request['id']);
        if ($id) {
            $query = new \yii\db\Query();
            $row = $query->select('id')->from('fzrbs_hwfz_group')->where(['and', ['=', 'id', $id]])->one();
            if ($row) {
                $values = $this->_request['values'];
                $icon = $values['icon'] ? $values['icon'] : '';
                Yii::$app->db->createCommand()->update('fzrbs_hwfz_group',  [
                    'status' => intval($values['status']),
                    'icon' => $icon,
                ], "id=:id", [':id' => $id])->execute();
                $action = '修改';
                $remark = $action . "商品分组。状态：" . $values['status'];
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 商品分组拖动排序功能
     */
    public function actionSort()
    {
        $point = $this->_request['point'];
        $updateId = $this->_request['updateId'];
        $targetId = $this->_request['targetId'];
        if ($point && $updateId && $targetId) {
            $targetRow = (new \yii\db\Query())->select('*')->from('fzrbs_hwfz_group')->where(['and', ['=', 'id', $targetId]])->one();
            $updateRow = (new \yii\db\Query())->select('*')->from('fzrbs_hwfz_group')->where(['and', ['=', 'id', $updateId]])->one();
            if ($targetRow && $updateRow) {
                if ($point == 'top') {
                    Yii::$app->db->createCommand()->update('fzrbs_hwfz_group', ['sort' => new \yii\db\Expression('sort + 0.003')], "sort>:sort", [':sort' => $targetRow['sort']])->execute();
                    Yii::$app->db->createCommand()->update('fzrbs_hwfz_group', ['sort' => $targetRow['sort'] + 0.002], "id=:id", [':id' => $updateRow['id']])->execute();
                } else if ($point == 'bottom') {
                    Yii::$app->db->createCommand()->update('fzrbs_hwfz_group', ['sort' => new \yii\db\Expression('sort + 0.003')], "sort>=:sort", [':sort' => $updateRow['sort']])->execute();
                    Yii::$app->db->createCommand()->update('fzrbs_hwfz_group', ['sort' => $updateRow['sort'] + 0.002], "id=:id", [':id' => $targetRow['id']])->execute();
                }
            }
            $action = '拖动排序';
            $remark = $action . "商品分组。相关分组名称：" . $targetRow['name'] . '|||' . $updateRow['name'];
            $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
        }
        return $this->_result;
    }

    protected function _getToken()
    {
        $this->resp = (new \Youzan\Open\Token($this->clientId, $this->clientSecret))->getSelfAppToken($this->kdtId);
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
