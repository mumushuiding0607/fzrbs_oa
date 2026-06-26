# 流程加签 — 设计 v1

## 1. 概述

在「流程设置」管理员页面（`Flowtemplate/viewflow.tsx`）的审批节点上提供「加签」能力——在指定节点之后**插入**一个新审批节点，新节点包含 1 个加签人，必须该人审批后才能进入原后续节点。

「修改审批人」（`flowalteritem`，原 UI 名「修改审批人」= 内部术语「转交」）已存在，本设计只新增「加签」，与之平行。**UI 上保持原名「修改审批人」**以兼容老用户习惯。

## 2. 目标与范围

**目标**：管理员在审批流程任意当前或未来节点之后插入一个加签节点。

**范围**：
- 后端：`FinanceroleController.php` 新增 `actionAddsigner`
- 前端：`Flowtemplate/viewflow.tsx` 加签入口（下拉菜单）
- 数据库结构：0 改动（数据存于 `WeixinOaApprovaldata.data` JSON 字段）
- 不动：`flow.tsx` 主审批 UI 不暴露加签

**非目标**：
- 不支持批量加签（每次 1 人，多次操作）
- 不支持撤销加签
- 不推送企业微信通知
- 不修改原节点任何字段

## 3. 数据结构

流程数据存于 `WeixinOaApprovaldata.data`（JSON 字段），关键结构：

```json
{
  "data": {
    "OpenSpstatus": 1,
    "ApprovalNodes": {
      "ApprovalNode": [
        {
          "NodeType": 0,
          "NodeAttr": 1,
          "NodeStatus": 2,        // 1=待审批 2=已审批
          "Items": { "Item": [
            { "ItemUserId": "zhangsan", "ItemName": "张三", "ItemStatus": 2, "ItemOpTime": 1234567890, ... }
          ]},
          "FromUserid": "",
          "FromUsername": ""
        },
        { "NodeType": 0, "NodeStatus": 1, "Items": { "Item": [{ "ItemUserId": "lisi", ... }] } },
        { "NodeType": 0, "NodeStatus": 1, "Items": { "Item": [{ "ItemUserId": "wangwu", ... }] } }
      ]
    }
  }
}
```

**当前审批人信息**存于 `WeixinOaApprovaldata.step`（索引 0 起）。

## 4. 后端设计 — `actionAddsigner`

### 4.1 接口

**URL**：`/api/financerole/addsigner`

**HTTP**：GET

**请求参数**：
| 参数 | 必填 | 说明 |
|------|------|------|
| `thirdNo` | 是 | 流程单号 |
| `step` | 是 | 加签的目标节点索引（≥ flow.step） |
| `userid` | 是 | 加签的目标用户 wxuserid |
| `agentid` | 是 | 应用 ID |

### 4.2 权限

复用 `actionFlowalter` 的权限校验：
```php
$hasauth = $this->hasRole('流程设置', '');
if (!$hasauth) return ['errorMessage' => '需要【流程设置】角色'];
```

### 4.3 校验逻辑

1. **agentid 必填**：缺失 → errorMessage
2. **userid 必填**：缺失 → errorMessage
3. **thirdNo 必填**：缺失 → errorMessage
4. **step 必填**：缺失 → errorMessage
5. **step 范围**：
   - `step < flow.step` → 拒绝（不能在已审批节点之前加签）
   - `step >= count(ApprovalNodes.ApprovalNode)` → 拒绝（索引越界）
6. **userid 存在**：通过 `getUserinfo($userid)` 校验

### 4.4 核心逻辑

```php
// 1. 加载 approval info（与 flowalter 一致的 switch case）
switch ($agentid) {
  case 1000063:  $data = FznewsFlowProcess::find()->...;   break;
  case 1000066:  $data = WeixinFinanceInfo::find()->...;   break;
  default:       $data = WeixinOaApprovalInfo::find()->...; break;
}
if (!$data) return ['errorMessage' => '无此单号'];

// 2. 权限检查（同 4.2）
// 3. 加载 flow JSON
$flow = WeixinOaApprovaldata::find()->where(['agentid'=>$agentid,'thirdNo'=>$thirdNo])->one();
$flowdata = json_decode($flow['data'], true);
$curstep = $flow['step'];
$nodes = &$flowdata['data']['ApprovalNodes']['ApprovalNode'];

// 4. step 范围校验
if ($step < $curstep) return ['errorMessage' => '只能在当前或之后的审批节点加签'];
if ($step >= count($nodes)) return ['errorMessage' => 'step 超出范围'];

// 5. 构造新节点
$user = $this->getUserinfo($userid);
$curuser = $this->getUserinfo($this->_adminInfo['wxuserid']);

$newNode = [
  'NodeType'   => 0,
  'NodeAttr'   => 1,                          // 或签：1 人通过即可
  'NodeStatus' => 1,                          // 待审批
  'Items' => ['Item' => [[
    'ItemName'    => $user['name'],
    'ItemParty'   => '',
    'ItemImage'   => $user['avatar'],
    'ItemUserId'  => $user['userid'],
    'ItemStatus'  => 1,
    'ItemSpeech'  => '',
    'ItemOpTime'  => 0,
  ]]],
  'FromUserid'   => $curuser['userid'],       // 加签操作人
  'FromUsername' => $curuser['name'],
];

// 6. 在 step 之后插入
array_splice($nodes, $step + 1, 0, [$newNode]);

// 7. 保存
$flowdata['data']['ApprovalNodes']['ApprovalNode'] = $nodes;
$flow->data = json_encode($flowdata);

$transaction = Yii::$app->db->beginTransaction();
try {
  $flow->save();
  // 注意：即使 $step == $curstep 也不更新 approvalUserid。
  // 加签节点在原节点之后插入，原节点（curstep）仍由原审批人继续审批；
  // 原审批人通过后，flowChange 自然将 step 推进到新加签节点。
} catch (\Throwable $th) {
  $transaction->rollBack();
  return ['errorMessage' => $th->getMessage()];
}
$transaction->commit();

return ['ret' => 1];
```

**关键设计点**：
- `array_splice` 插入新节点，原 `step` 之后的所有节点索引自动 +1
- 加签节点默认 `NodeAttr=1`（或签）。如果业务需要会签（`NodeAttr=2`），后续可加参数
- **不**调用 `send()` 通知企业微信（按需求）
- **不**修改 `WeixinOaApprovalInfo.approvalUserid`——加签节点在原节点之后，原审批人继续审批直至通过；通过后 `flowChange` 自然推进 step 到新节点

### 4.5 与 `actionFlowalteritem` 的对比

| 维度 | flowalteritem（修改审批人） | addsigner（加签） |
|------|---------------------------|------------------|
| UI 名 | 修改审批人 | 加签 |
| 操作 | 替换节点 | 插入节点 |
| 数据变更 | `$nodes[$step]['Items']['Item'] = [$newItem]` | `array_splice($nodes, $step+1, 0, [$newNode])` |
| 原节点状态 | **被替换**（清空后重写） | **保持不变** |
| flow.step 调整 | 仅当 `step==curstep` 时更新 approvalUserid | **不调整**（加签节点在原节点之后，curstep 保持指向原节点；原审批人继续审批，审批完后自动进入新加签节点） |
| 后续节点索引 | 不变 | +1 |

## 5. 前端设计 — `viewflow.tsx`

### 5.1 改动文件

| 文件 | 改动 |
|------|------|
| `mysite/src/pages/finance/Flowtemplate/service.ts` | 新增 `addsigner()` API 函数 |
| `mysite/src/pages/finance/Flowtemplate/viewflow.tsx` | 在现有「修改审批人」弹窗加「加签 / 转交」模式切换 |

### 5.2 service.ts 新增

```typescript
export async function addsigner(params:any){
  return request<{errorMessage:string,ret?:number}>(
    '/api/financerole/addsigner',
    { method: 'GET', params:{...params} }
  );
}
```

### 5.3 viewflow.tsx 改动

**新增 state**：
```typescript
const [alterMode, setAlterMode] = useState<'transfer' | 'addsigner'>('transfer');
```

**onAlterApprover 触发逻辑**（原 92-97 行）：
```typescript
onAlterApprover={(item, index, idx) => {
  // 只允许当前和未来步骤加签（与后端校验一致）
  if (index < (data.viewdata?.step ?? 0)) {
    Modal.warning({ title: '已审批节点无法加签' });
    return;
  }
  Modal.confirm({
    title: '选择操作',
    content: (
      <Radio.Group
        defaultValue="transfer"
        onChange={(e) => setAlterMode(e.target.value)}
      >
        <Radio.Button value="transfer">修改审批人（替换原审批人）</Radio.Button>
        <Radio.Button value="addsigner">加签（插入新节点）</Radio.Button>
      </Radio.Group>
    ),
    onOk: () => {
      setStepSelect(index);
      setItemSelect(idx !== undefined ? idx : 0);
      setRowSelect(item);
      setShowu(true);   // 打开「选择新审批人」弹窗
    },
  });
}}
```

**「选择新审批人」弹窗**（原 143-168 行）：
```typescript
<UserAutocomplete multiple={false} onChange={(e) => {
  if (!e) return;
  const isAdd = alterMode === 'addsigner';
  const api = isAdd ? addsigner : flowalteritem;
  const params = isAdd
    ? { thirdNo: data.thirdNo, agentid, step: stepSelect, userid: e.value }
    : { thirdNo: data.thirdNo, agentid, step: stepSelect,
        userid: e.value, itemIndex: itemSelect };

  api(params).then((res: any) => {
    if (res.errorMessage) Modal.error({ title: res.errorMessage });
    else {
      setAlterMode('transfer');   // 重置回默认「修改审批人」
      getflowdata({ thirdNo: data.thirdNo }).then(setData);
    }
  });
}} />
```

**关键不变量**：
- 加签入口**仅** `viewflow.tsx`（在 `Flowtemplate/` 目录下）。主审批 UI（`budget/budget/flow.tsx`）**不**改动。
- 默认选项是「修改审批人」（保留老用户习惯），加签是次选项。
- 加签成功后 UI 回到「修改审批人」模式，避免下次操作误用。
- 重置 `alterMode` 在成功回调里，不在 onCancel（用户取消时也保留选择，便于复看）。

### 5.4 `flow.tsx` 零改动

主审批 UI 不暴露加签按钮，与需求一致。

## 6. 数据流（一次加签操作）

```
[管理员 viewflow.tsx]
  1. 在 Flow 步骤列表点击 avatar
  2. 弹出 Modal.confirm 选择「修改审批人 / 加签」
  3. 选择「加签」 → onOk → 弹出「选择新审批人」弹窗
  4. 选中加签人 → addsigner() 调用
     ↓
[POST /api/financerole/addsigner]
  5. 权限校验（流程设置角色）
  6. 加载 flow JSON
  7. step 范围校验（≥ curstep）
  8. array_splice 插入新节点
  9. 事务保存：WeixinOaApprovaldata.save()
     （**不**更新 WeixinOaApprovalInfo.approvalUserid——加签节点在原 curstep 之后）
  10. 返回 {ret:1}
     ↓
[viewflow.tsx]
  11. getflowdata({thirdNo}) 重新拉取流程数据
  12. 流程图自动刷新，显示新插入的节点
```

## 7. 错误处理

| 场景 | 后端响应 | 前端行为 |
|------|---------|---------|
| 无「流程设置」角色 | `errorMessage: '需要【流程设置】角色'` | `Modal.error` |
| `thirdNo` 不存在 | `errorMessage: '无此单号'` | `Modal.error` |
| `userid` 不存在 | `errorMessage: 'userid：[xxx]不存在'` | `Modal.error` |
| `step < curstep` | `errorMessage: '只能在当前或之后的审批节点加签'` | `Modal.error` |
| `step` 越界 | `errorMessage: 'step 超出范围'` | `Modal.error` |
| 加签步骤已审批（前端拦截） | — | `Modal.warning('已审批节点无法加签')` |

## 8. 测试场景（手动）

1. **基线单步加签**：3 步流程（张三→李四→王五），当前 step=0（张三审批中），在步骤 0 后加签赵六 → 验证流程变为「张三→赵六→李四→王五」，张三仍为当前审批人（赵六为下一步）
2. **未来步骤加签**：同上流程，当前 step=0，在步骤 1 后加签赵六 → 验证流程变为「张三→李四→赵六→王五」
3. **最后一步加签**：在步骤 2（王五）后加签 → 验证流程变为「张三→李四→王五→赵六」
4. **权限拦截**：非「流程设置」角色调用 → 应返回权限错误
5. **已审批节点**：尝试在 step<curstep 节点加签 → 应被拒绝（前端 + 后端双重校验）
6. **加签后审批**：加签赵六 → 赵六审批 → 流程继续到原 step+1 节点
7. **取消流程**：加签后撤销流程 → 验证后续节点状态正确

## 9. 实现要点

1. **`array_splice` 是核心**：PHP 的 `array_splice($arr, $offset, $length, $replacement)` 在 `$offset` 后插入 `$replacement` 数组，原数组元素后移。
2. **JSON 字段直接读写**：所有变更在内存中完成，一次性 `json_encode` 写回。
3. **状态机一致性**：`WeixinOaApprovalInfo.approvalUserid` 必须与 `flow.step` 指向的节点的 `Items.Item[0].ItemUserId` 一致。加签**不**改变 curstep 指向，所以**不**需要更新 `approvalUserid`——原审批人继续审批。
4. **前端模式状态**：避免加签/修改审批人混淆，每次操作成功后重置为 `transfer`（即「修改审批人」）。

## 10. 不在范围内（后续可选）

- 加签后通知（企业微信 / 站内信）—— 已确认不做
- 加签撤销 —— 可做但本期省略
- 加签审批记录日志 —— 可写 `WeixinOaApprovalLog`（参考 `flowChange` 第 2522 行），本期省略
- 批量加签、多人会签节点 —— 需求未提
- `flowalter` 等其他 endpoint 的 `agentid=1000063` (新流程引擎) 适配 —— 现有 `flowalter` 也不适配，本期保持一致