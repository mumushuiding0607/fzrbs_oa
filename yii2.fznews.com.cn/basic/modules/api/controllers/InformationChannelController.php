<?php

namespace app\modules\api\controllers;

use Yii;
use app\modules\api\commons\ApiBase;
use app\modules\api\commons\Tools;
use app\modules\api\models\FzrbsRole;

/**
 * 信息发布栏目管理相关接口类
 */
class InformationChannelController extends ApiBase
{
    public $modelClass = 'app\modules\api\models\WeixinChannel';
    protected $_orderBy = 'inserttime asc';

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
        $parentId = isset($this->_request['parentid']) ? $this->_request['parentid'] : 0;
        $where = [
            'and',
            ['>', 'id', 0],
            ['=', 'parentid', $parentId],
        ];
        if ($this->_adminInfo['usertype'] == 0) {
            $where[] = ['in', 'id', $this->_channels];
        }
        $model = $this->modelClass;
        $model = $model::find()->where($where);
        if (isset($this->_request['tree'])) {
            $data = [];
            $res = $model->orderBy($this->_orderBy)->all();
            foreach ($res as $row) {
                $isLeaf = $row->childres > 0 ? false : true;
                $node = ['title' => $row->name, 'key' => strval($row->id), 'isLeaf' => $isLeaf];
                if ($this->_request['showAll'] && !$isLeaf) {
                    $children  = $this->_getChannelChildren($row->id);
                    if ($children) {
                        $node['children'] = $children;
                    }
                }
                $data[] = $node;
            }
            if (!$this->_request['showAll'] && $res && $res[0]['level'] == 4) {
                $data = array_reverse($data);
            }
            $this->_result['data'] = $data;
        } else {
            if ($parentId) {
                $parentModel = $this->modelClass::findOne($parentId);
                if ($parentModel->level == 3) {
                    $this->_orderBy = 'inserttime desc';
                }
            }
            $total = $model->count();
            $res = $model->limit($limit)->offset($offset)->orderBy($this->_orderBy)->all();
            $this->_result["current"] = $page;
            $this->_result["pageSize"] = $limit;
            $this->_result["total"] = $total;
            $this->_result['data'] = $res;
        }
        return $this->_result;
    }

    /**
     * 重写create的业务实现动作
     */
    public function actionCreate()
    {
        if ($this->_request['values']) {
            $this->_request['values']['parentids'] = "0";
            if ($this->_request['values']['parentid'] > 0) {
                $parentModel = $this->modelClass::findOne($this->_request['values']['parentid']);
                if ($parentModel) {
                    $this->_request['values']['parentids'] = $parentModel->parentids . ',' . $this->_request['values']['parentid'];
                    $this->_request['values']['level'] = $parentModel->level + 1;
                }
            }
            $model = new $this->modelClass(['scenario' => 'create']);
            $model->attributes = $this->_request['values'];
            $ruleResult = Tools::modelRules($model, 7000);
            if ($ruleResult === true) {
                if ($model->save()) {
                    if ($parentModel) {
                        $parentIds = explode(',', $this->_request['values']['parentids']);
                        $this->modelClass::updateAllCounters(['childres' => 1], ['in', 'id', $parentIds]);
                    }
                    $this->_result['lastid'] = $model->id;
                    $action = '新增';
                    $remark = $action . "信息栏目。名称：" . $model->name . '。';
                    // 非管理员新增的栏目添加到用户的角色中
                    if ($this->_adminInfo['usertype'] == 0) {
                        $where = [
                            'and',
                            ['>', 'id', 0],
                            ['like', "CONCAT(',', usernames, ',')", ',' . $this->_adminInfo['username'] . ','],
                            ['not in', 'id', [1, 2, 9]]
                        ];
                        $roleModel = FzrbsRole::find()->where($where)->orderBy('id asc')->one();
                        if ($roleModel) {
                            $roleModel->channels = $roleModel->channels ? $roleModel->channels . ',' . $model->id : $model->id;
                            $roleModel->save();
                        }
                    }
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
            $model = $this->modelClass::findOne($id);
            $model->scenario = 'update';
            $oldName = $model->name;
            $model->attributes = $this->_request['values'];
            $ruleResult = Tools::modelRules($model, 7001);
            if ($ruleResult === true) {
                if ($model->save()) {
                    $action = '修改';
                    $remark = $action . "信息栏目。" . ($oldName != $model->name ? '名称由 ' . $oldName . ' 改为 ' . $model->name . '。' : '');
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
            $models = $this->modelClass::find()->where(['in', 'id', $ids])->all();
            $parentIds = 0;
            $num = count($models);
            $names = [];
            foreach ($models as $model) {
                $parentIds = $model->parentids;
                $childrenNum = $this->modelClass::deleteAll(['like', "CONCAT(',', parentids, ',')", ',' . $model->id . ',']);
                if ($childrenNum) {
                    $num = $num + $childrenNum;
                }
                $names[] = $model->name;
                if ($model->image) {
                    Tools::deleteFile($this->_imageSavePath, $model->image);
                }
                $model->delete();
            }
            if ($parentIds && $num) {
                $this->modelClass::updateAllCounters(['childres' => -$num], ['in', 'id',  explode(',', $parentIds)]);
            }
            if ($names) {
                $action = '删除';
                $remark = $action . "信息栏目。包含 " . implode(',', $names) . " 栏目及子栏目全部删除。";
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        } else {
            $this->_result = Tools::wrongRules(1000, '参数错误');
        }
        return $this->_result;
    }

    /**
     * 栏目复制功能
     */
    public function actionCopy()
    {
        $sourceId = $this->_request['fromChannelId'];
        $targetId = $this->_request['toChannelId'];
        $Ids = $this->_request['infoIds'];
        $model = new $this->modelClass;
        $field = $model->attributes;
        if ($sourceId && $targetId && $Ids) {
            $targetId = explode(',', $targetId);
            $Ids = explode(',', $Ids);
            $fromChannelModal = $this->modelClass::find()->where(['=', 'id', $sourceId])->one();
            $fromName = $fromChannelModal ? $fromChannelModal->name : '';
            $toName = $copyName = [];
            // 复制到的栏目
            $res = $this->modelClass::find()->where(['in', 'id', $targetId])->all();
            foreach ($res as $kt => $row) {
                $toName[] = $row->name;
                // 要复制的栏目
                $res1 =  $this->modelClass::find()->where(['in', 'id', $Ids])->all();
                foreach ($res1 as $row1) {
                    if ($kt == 0) {
                        $copyName[] = $row1->name;
                    }
                    $field1 = [];
                    foreach ($field as $k => $v) {
                        if ($k == 'inserttime') {
                            $field1[$row1->id][$k] = date('Y-m-d H:i:s');
                        } else {
                            $field1[$row1->id][$k] = $row1->$k;
                        }
                    }
                    $childres = $this->_getCopyChildres($row1->id, $field);
                    $insertId = $this->_copyChannels(['id' => $row->id, 'parentids' => $row->parentids, 'level' => $row->level], $field1);
                    if (count($childres) > 0) {
                        $this->_copyChannels(array('id' => $insertId, 'parentids' => $row->parentids . ',' . $row->id, 'level' => intval($row->level) + 1), $childres);
                    }
                    Yii::$app->db->createCommand()->update($this->modelClass::tableName(), ['childres' => new \yii\db\Expression("childres+" . ($row1->childres + 1))], ['in', "id", explode(',', $row->parentids . "," . $row->id)])->execute();
                }
            }
            if ($toName && $copyName) {
                $action = '复制';
                $remark = $action . "栏目。" . ($fromName ? '从 ' . $fromName . ' 栏目' : '') . ($toName ? '复制到 ' . implode(',', $toName) . ' 栏目' : '') . '，所复制的栏目为：' . implode(',', $copyName) . '，及其子栏目';
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        }
        return $this->_result;
    }

    /**
     * 栏目移动功能
     */
    public function actionCut()
    {
        $sourceId = $this->_request['fromChannelId'];
        $targetId = $this->_request['toChannelId'];
        $Ids = $this->_request['infoIds'];
        if ($sourceId && $targetId && $Ids) {
            $table = $this->modelClass::tableName();
            $childres = 0;
            $targetId = explode(',', $targetId);
            $Ids = explode(',', $Ids);
            $fromChannelModal = $this->modelClass::find()->where(['=', 'id', $sourceId])->one();
            $fromName = $fromChannelModal ? $fromChannelModal->name : '';
            $toChannelModal = $this->modelClass::find()->where(['=', 'id', $targetId[0]])->one();
            $toName = $toChannelModal ? $toChannelModal->name : '';
            $cutName = [];
            $res = $this->modelClass::find()->where(['in', 'id', $Ids])->all();
            foreach ($res as $row) {
                if ($row->id != $targetId[0]) {
                    $cutName[] = $row->name;
                    $childres = $row->childres + 1;
                    Yii::$app->db->createCommand()->update($table, ['childres' => new \yii\db\Expression("childres+" . $childres)], ['=', "id", $targetId[0]])->execute();
                    $target = $this->modelClass::find()->where(['=', 'id',  $targetId[0]])->one();
                    if ($target->parentids) {
                        Yii::$app->db->createCommand()->update($table, ['childres' => new \yii\db\Expression("childres+" . $childres)], ['in', "id", explode(',', $target->parentids)])->execute();
                    }
                    if ($row->parentids) {
                        Yii::$app->db->createCommand()->update($table, ['childres' => new \yii\db\Expression("childres-" . $childres)], ['in', "id", explode(',', $row->parentids)])->execute();
                    }
                    $parentids = new \yii\db\Expression("CONCAT((SELECT a.parentids FROM (SELECT a1.* FROM " . $table . " a1) a WHERE a.id=" . $targetId[0] . "),'," . $targetId[0] . "')");
                    $level = new \yii\db\Expression("(SELECT b.level FROM (SELECT b1.* FROM " . $table . " b1) b WHERE b.id=" . $targetId[0] . ")+1");
                    Yii::$app->db->createCommand()->update($table, ['parentids' => $parentids, 'level' => $level, 'parentid' => $targetId[0]], ['=', "id", $row->id])->execute();
                    $parentids = new \yii\db\Expression("TRIM(BOTH ',' FROM REPLACE(CONCAT(',',parentids,','),SUBSTRING(CONCAT(',',parentids,','),1,INSTR(CONCAT(',',parentids,','),'," . $row->id . ",')+1+" . strlen($row->id) . "),CONCAT(',',(SELECT a.parentids FROM (SELECT a1.* FROM " . $table . " a1) a WHERE a.id=" . $targetId[0] . "),'," . $targetId[0] . "," . $row->id . ",')))");
                    $level = new \yii\db\Expression("level+((SELECT b.level FROM (SELECT b1.* FROM " . $table . " b1) b WHERE b.id=" . $targetId[0] . ")+1-" . $row->level . ")");
                    Yii::$app->db->createCommand()->update($table, ['parentids' => $parentids, 'level' => $level], "INSTR(CONCAT(',',parentids,','),'," . $row->id . ",')>0")->execute();
                }
            }
            if ($toName && $cutName) {
                $action = '移动';
                $remark = $action . "栏目。" . ($fromName ? '从 ' . $fromName . ' 栏目' : '') . ($toName ? '移动到 ' . $toName . ' 栏目' : '') . '，所移动的栏目为：' . implode(',', $cutName) . '，及其子栏目';
                $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
            }
        }
        return $this->_result;
    }

    /**
     * 获取栏目子节点
     * @param int $parentId 父节点id
     * @return array 子节点信息
     */
    protected function _getChannelChildren($parentId)
    {
        $where = [
            'and',
            ['=', 'parentid', $parentId],
        ];
        $res = $this->modelClass::find()->where($where)->orderBy('inserttime asc')->all();
        $routes = [];
        foreach ($res as $row) {
            $isLeaf = $row->childres > 0 ? false : true;
            $node = ['title' => $row->name, 'key' => strval($row->id), 'isLeaf' => $isLeaf];
            if ($this->_request['showAll'] && !$isLeaf) {
                $children  = $this->_getChannelChildren($row->id);
                if ($children) {
                    $node['children'] = $children;
                }
            }
            $routes[] = $node;
        }
        return $routes;
    }

    /**
     * 要复制栏目子节点
     * @param int $id 父节点id
     * @param array $field 字段信息
     */
    protected function _getCopyChildres($id, $field)
    {
        $field1 = [];
        $res = $this->modelClass::find()->where(['=', 'parentid', $id])->all();
        foreach ($res as $row) {
            foreach ($field as $k => $v) {
                if ($k == 'inserttime') {
                    $field1[$row->id][$k] = date('Y-m-d H:i:s');
                } else {
                    $field1[$row->id][$k] = $row->$k;
                }
            }
            if ($row->childres > 0) {
                $childres = $this->_getCopyChildres($row->id, $field);
                $field1[$row->id]['child'] = $childres;
                return $field1;
            }
        }
        return $field1;
    }

    /** 进行栏目复制
     * @param array $targetChannel 复制到的栏目信息
     * @param array $sourceChannel 要复制的栏目信息
     */
    protected function _copyChannels($targetChannel, $sourceChannel)
    {
        $table = $this->modelClass::tableName();
        foreach ($sourceChannel as $k => $v) {
            $v['parentid'] = isset($targetChannel['id']) ? $targetChannel['id'] : 0;
            $v['parentids'] = isset($targetChannel['id']) ? $targetChannel['parentids'] . ',' . $targetChannel['id'] : 0;
            $v['level'] = isset($targetChannel['id']) ? intval($targetChannel['level']) + 1 : 1;
            if (!isset($v['child'])) {
                unset($v['id']);
                Yii::$app->db->createCommand()->insert($table, $v)->execute();
                $insertId = Yii::$app->db->getLastInsertID();
            } else {
                $id = $v['id'];
                $child = $v['child'];
                unset($v['id']);
                unset($v['child']);
                Yii::$app->db->createCommand()->insert($table, $v)->execute();
                $insertId = Yii::$app->db->getLastInsertID();
                $this->_copyChannels(array('id' => $insertId, 'parentids' => $v['parentids'], 'level' => intval($v['level'])), $child);
            }
        }
        return $insertId;
    }
}
