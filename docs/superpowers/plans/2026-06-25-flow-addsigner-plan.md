# 流程加签 (Addsigner) 实施计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal**: 在「流程设置」管理员页面（`Flowtemplate/viewflow.tsx`）的审批节点上提供「加签」能力——在指定节点之后插入一个新的审批节点，新节点必须由加签人审批后才能进入原后续节点。

**Architecture**: 新增后端端点 `actionAddsigner`（Yii2，与现有 `actionFlowalteritem` 平行）+ 前端在 `viewflow.tsx` 加签/转交模式切换。前端弹窗按 mode 路由到不同 API。

**Tech Stack**: PHP 5.6+ (Yii2) + React 17 + TypeScript + Ant Design 5 + UmiJS 3.5

## Global Constraints

- **HTTP 方法**：GET（与现有 `flowalter` / `flowalteritem` 一致）
- **接口 URL**：`/api/financerole/addsigner`
- **新加签节点属性**：`NodeType=0`、`NodeAttr=1`（或签）、`NodeStatus=1`（待审批）
- **不推送企业微信通知**
- **不修改** `WeixinOaApprovalInfo.approvalUserid`（加签节点在原 curstep 之后）
- **数据库结构零改动**（数据存于 `WeixinOaApprovaldata.data` JSON 字段）
- **不动** `flow.tsx`（主审批 UI 不暴露加签）
- **不动** 现有 `flowalteritem` / `flowalter`（保留 100% 功能）
- **现有「修改审批人」UI 重命名为「转交」**

---

## File Structure

| 文件 | 改动 |
|------|------|
| `yii2.fznews.com.cn/basic/modules/api/controllers/FinanceroleController.php` | 新增 `actionAddsigner` 方法（插入到 `actionFlowalteritem` 之后、`actionAlterspeech` 之前） |
| `mysite/src/pages/finance/Flowtemplate/service.ts` | 新增 `addsigner()` API 函数 |
| `mysite/src/pages/finance/Flowtemplate/viewflow.tsx` | 新增 `alterMode` state、改造 `onAlterApprover` 触发逻辑、改造 `UserAutocomplete.onChange` 分支调用 |

无任何其他文件改动。

---

### Task 1: 后端 `actionAddsigner`

**Files:**
- Modify: `yii2.fznews.com.cn/basic/modules/api/controllers/FinanceroleController.php`（在 `actionFlowalteritem` 结束后、`actionAlterspeech` 开始前插入新方法，约 1222 行）

**Interfaces:**
- Consumes: 现有 `hasRole()`、`getUserinfo()`、`WeixinOaApprovaldata` 模型、`$this->_request`、`$this->_adminInfo`
- Produces: HTTP GET 端点 `/api/financerole/addsigner`，返回 `{ret:1}` 或 `{errorMessage:string}`

- [ ] **Step 1: 定位插入点**

打开文件，搜索 `public function actionFlowalteritem`，确认方法结束位置在 `return array('ret'=>1);\n  }\n  public function actionAlterspeech()`。新方法应插入到这两个方法之间。

- [ ] **Step 2: 插入新方法**

在 `actionFlowalteritem` 结束的 `}` 之后、`actionAlterspeech` 的 `public function actionAlterspeech(){` 之前，插入以下完整代码：

```php
  public function actionAddsigner(){
    $thirdNo = $this->_request['thirdNo'];
    $step = $this->_request['step'];
    $userid = $this->_request['userid'];
    $agentid = $this->_request['agentid'];

    if (!$agentid){
      return array('errorMessage'=>'agentid 不能为空');
    }
    if (!$userid){
      return array('errorMessage'=>"userid 不能为空");
    }
    if (!$thirdNo){
      return array('errorMessage'=>'thirdNo 不能为空');
    }
    if (!isset($step) && $step !== '0'){
      return array('errorMessage'=>'step 不能为空');
    }

    $user = $this->getUserinfo($userid);
    if (!$user){
      return array('errorMessage'=>'userid：['.$userid.']不存在');
    }

    switch ($agentid) {
      case 1000063:
        $data = FznewsFlowProcess::find()->where(['and',['=','processInstanceId',$thirdNo]])->one();
        break;
      case 1000066:
        $data = WeixinFinanceInfo::find()->where(['and',['=','thirdNo',$thirdNo]])->one();
        break;
      default:
        $data = WeixinOaApprovalInfo::find()->where(['and',['=','thirdNo',$thirdNo]])->one();
        break;
    }
    if (!$data){
      return array('errorMessage'=>'无此单号');
    }

    $hasauth = $this->hasRole('流程设置','');
    if (!$hasauth) {
      return array('errorMessage'=>'需要【流程设置】角色');
    }

    $flow = WeixinOaApprovaldata::find()->where(['agentid'=>$agentid,'thirdNo'=>$thirdNo])->one();
    if (!$flow){
      return array('errorMessage'=>'流程数据不存在');
    }
    $flowdata = json_decode($flow['data'],true);
    $curstep = intval($flow['step']);
    $nodes = &$flowdata['data']['ApprovalNodes']['ApprovalNode'];

    // step 范围校验
    if (intval($step) < $curstep) {
      return array('errorMessage'=>'只能在当前或之后的审批节点加签');
    }
    if (intval($step) >= count($nodes)) {
      return array('errorMessage'=>'step 超出范围');
    }

    $curuser = $this->getUserinfo($this->_adminInfo['wxuserid']);

    $newNode = array(
      'NodeType'   => 0,
      'NodeAttr'   => 1,
      'NodeStatus' => 1,
      'Items' => array('Item' => array(array(
        'ItemName'    => $user['name'],
        'ItemParty'   => '',
        'ItemImage'   => $user['avatar'],
        'ItemUserId'  => $user['userid'],
        'ItemStatus'  => 1,
        'ItemSpeech'  => '',
        'ItemOpTime'  => 0,
      ))),
      'FromUserid'   => $curuser['userid'],
      'FromUsername' => $curuser['name'],
    );

    array_splice($nodes, intval($step) + 1, 0, array($newNode));

    $flowdata['data']['ApprovalNodes']['ApprovalNode'] = $nodes;
    $flow->data = json_encode($flowdata);

    $transaction = Yii::$app->db->beginTransaction();
    try {
      $flow->save();
      // 注意：不更新 $data->approvalUserid。
      // 加签节点在原 curstep 之后插入；原审批人继续审批直至通过，
      // 通过后 flowChange 自然将 step 推进到新加签节点。
    } catch (\Throwable $th) {
      $transaction->rollBack();
      return array('errorMessage'=> $th->getMessage());
    }
    $transaction->commit();

    return array('ret'=>1);
  }
```

- [ ] **Step 3: PHP 语法校验**

```bash
cd "E:/Workspaces/fzrbs_oa/yii2.fznews.com.cn/basic" && php -l modules/api/controllers/FinanceroleController.php
```

Expected: `No syntax errors detected in modules/api/controllers/FinanceroleController.php`

如果报错 → 检查大括号匹配、字符串引号，修复后重跑。

- [ ] **Step 4: 验证方法已添加**

```bash
cd "E:/Workspaces/fzrbs_oa" && grep -n "function actionAddsigner" yii2.fznews.com.cn/basic/modules/api/controllers/FinanceroleController.php
```

Expected: 一行输出，形如 `1222:  public function actionAddsigner(){`

- [ ] **Step 5: 提交**

```bash
cd "E:/Workspaces/fzrbs_oa" && git add yii2.fznews.com.cn/basic/modules/api/controllers/FinanceroleController.php && git commit -m "feat(flow): add actionAddsigner backend endpoint

新增 /api/financerole/addsigner：在指定审批节点后插入新节点。
权限：流程设置角色；范围：当前和未来节点；不推送企业微信通知。

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 2: 前端 `service.ts` 新增 `addsigner()`

**Files:**
- Modify: `mysite/src/pages/finance/Flowtemplate/service.ts`（在 `flowalteritem` 函数之后追加）

**Interfaces:**
- Consumes: UmiJS `request`，调用 `/api/financerole/addsigner`
- Produces: 导出函数 `addsigner(params: any): Promise<{errorMessage?: string, ret?: number}>`

- [ ] **Step 1: 定位插入点**

打开 `mysite/src/pages/finance/Flowtemplate/service.ts`，搜索 `export async function flowalteritem`，确认函数结束位置。`addsigner` 应紧跟其后。

- [ ] **Step 2: 追加新函数**

在 `flowalteritem` 函数结束的 `}` 之后追加：

```typescript
export async function addsigner(
  params:any
){
  return request<{errorMessage:string,ret?:number}>('/api/financerole/addsigner',{
    method: 'GET',
    params:{...params}
  })
}
```

- [ ] **Step 3: TypeScript 验证**

```bash
cd "E:/Workspaces/fzrbs_oa/mysite" && npm run tsc 2>&1 | grep -E "Flowtemplate.*service" | head -20
```

Expected: 无错误输出（任何 tsc 报错都会包含文件路径前缀）。

- [ ] **Step 4: 提交**

```bash
cd "E:/Workspaces/fzrbs_oa" && git add mysite/src/pages/finance/Flowtemplate/service.ts && git commit -m "feat(flow): add addsigner() frontend API function

对应后端 /api/financerole/addsigner。

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 3: `viewflow.tsx` 加签/转交模式切换

**Files:**
- Modify: `mysite/src/pages/finance/Flowtemplate/viewflow.tsx`

**Interfaces:**
- Consumes: 现有 `flowalteritem`、Task 2 新增的 `addsigner`、`UserAutocomplete`、`Flow` 组件
- Produces: 新增 `alterMode` state、改造 `onAlterApprover` 回调、改造 `UserAutocomplete.onChange` 分支调用

- [ ] **Step 1: 添加 import**

打开 `mysite/src/pages/finance/Flowtemplate/viewflow.tsx`，找到 import 行：

```typescript
import { flowalter, flowalteritem, flowback, getflowdata } from "./service";
```

改为：

```typescript
import { addsigner, flowalter, flowalteritem, flowback, getflowdata } from "./service";
```

找到 `import { Button, Input, Modal } from "antd";` 改为：

```typescript
import { Button, Input, Modal, Radio } from "antd";
```

- [ ] **Step 2: 添加 `alterMode` state**

在现有 state 声明之后（约第 22-26 行的 `stepSelect` 等 state 之后）追加：

```typescript
const [alterMode, setAlterMode] = useState<'transfer' | 'addsigner'>('transfer');
```

- [ ] **Step 3: 改造 `onAlterApprover` 回调**

找到 `onAlterApprover={(item:any,index:any,idx:any)=>{...}}` 块（约第 92-97 行），**完全替换**为以下内容：

```typescript
        onAlterApprover={(item:any,index:any,idx:any)=>{
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
                onChange={(e:any)=>setAlterMode(e.target.value)}
              >
                <Radio.Button value="transfer">转交（替换原审批人）</Radio.Button>
                <Radio.Button value="addsigner">加签（插入新节点）</Radio.Button>
              </Radio.Group>
            ),
            onOk:()=>{
              setStepSelect(index)
              setItemSelect(idx !== undefined ? idx : 0)
              setRowSelect(item)
              setShowu(true)
            }
          })
        }}
```

- [ ] **Step 4: 改造 `UserAutocomplete.onChange`**

找到 `<UserAutocomplete multiple={false} onChange={(e:any)=>{ ... }} />`（约第 143-168 行），**完全替换**为以下内容：

```typescript
        <UserAutocomplete multiple={false} onChange={(e:any)=>{
          if (!e) return;
          const isAdd = alterMode === 'addsigner';
          const api = isAdd ? addsigner : flowalteritem;
          const params = isAdd
            ? { thirdNo: data.thirdNo, agentid, step: stepSelect, userid: e.value }
            : { thirdNo: data.thirdNo, agentid, step: stepSelect,
                userid: e.value, itemIndex: itemSelect };

          api(params).then((res:any)=>{
            if (res.errorMessage) {
              Modal.error({
                title: res.errorMessage,
              });
            } else {
              setAlterMode('transfer');   // 重置回默认「转交」
              getflowdata({thirdNo:data.thirdNo}).then(res=>{
                if (res.errorMessage){
                  Modal.error({
                    title: '报错',
                    content: res.errorMessage,
                  });
                }else{
                  setData(res)
                }
              })
            }
          })
        }
      }/>
```

- [ ] **Step 5: TypeScript 验证**

```bash
cd "E:/Workspaces/fzrbs_oa/mysite" && npm run tsc 2>&1 | grep -E "Flowtemplate.*viewflow" | head -20
```

Expected: 无错误输出。

如果报错 → 检查 `Radio` 是否正确 import、`alterMode` state 类型是否一致。

- [ ] **Step 6: 提交**

```bash
cd "E:/Workspaces/fzrbs_oa" && git add mysite/src/pages/finance/Flowtemplate/viewflow.tsx && git commit -m "feat(flow): add addsigner UI mode in viewflow.tsx

点击头像弹窗改为「转交 / 加签」单选；按模式路由到 flowalteritem 或 addsigner。
默认选项「转交」保留原修改审批人行为；加签为新选项。
加签成功后 alterMode 自动重置为 'transfer'。

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 4: 手动端到端验证

**Files:** 无文件改动 — 仅手动测试

**Interfaces:**
- Verifies: 完整数据流（viewflow → addsigner → MySQL JSON → UI 刷新）

- [ ] **Step 1: 启动 dev server**

```bash
cd "E:/Workspaces/fzrbs_oa/mysite" && npm run start:dev
```

Expected: UmiJS dev server 启动，无报错。

- [ ] **Step 2: 场景 1 — 基线单步加签**

1. 浏览器打开一个有 3 步审批流程的合同/财务单（如新建一个开票申请并走完前两步但不通过）
2. 进入「流程设置」→「流程」Tab，输入单号搜索
3. 在第一个步骤（当前 step=0）点击头像
4. 弹出 Modal：「选择操作」+ Radio.Group（默认「转交」）
5. 切换到「加签」→ 点确认
6. 弹出「选择新审批人」→ 选一个人 → 提交
7. **验证**：
   - 流程图刷新后显示 4 步：「张三 → 赵六 → 李四 → 王五」
   - 当前审批人仍是张三（赵六是下一步）
   - 后端 `WeixinOaApprovaldata.data` JSON 中赵六节点插入到 index 1
   - `WeixinOaApprovalInfo.approvalUserid` 未变化（仍是张三）

- [ ] **Step 3: 场景 2 — 未来步骤加签**

1. 同上流程，step=0
2. 点击第二步（李四）的头像
3. 选择「加签」→ 选人 → 提交
4. **验证**：流程变为「张三 → 李四 → 赵六 → 王五」（赵六插入到 step+1=2）

- [ ] **Step 4: 场景 3 — 最后一步加签**

1. 点击第三步（王五）的头像
2. 选择「加签」→ 选人 → 提交
3. **验证**：流程变为「张三 → 李四 → 王五 → 赵六」（赵六在末尾）

- [ ] **Step 5: 场景 4 — 权限拦截**

1. 用非「流程设置」角色的用户登录
2. 直接调用 `GET /api/financerole/addsigner?thirdNo=...&step=0&userid=...&agentid=...`
3. **验证**：返回 `{errorMessage: '需要【流程设置】角色'}`

- [ ] **Step 6: 场景 5 — 已审批节点拒绝**

1. 用「流程设置」角色进入流程图，step=2（前面两步已审批）
2. 尝试点击第一步（已审批）的头像
3. **验证**：前端弹 `Modal.warning('已审批节点无法加签')`，**不**打开选择操作 Modal

后端二次校验（绕过前端）：
```bash
# 在已审批步骤上加签应被后端拒绝
curl "https://your.domain/api/financerole/addsigner?thirdNo=XXX&step=0&userid=YYY&agentid=ZZZ"
```
Expected: `{errorMessage: '只能在当前或之后的审批节点加签'}`

- [ ] **Step 7: 场景 6 — 加签后审批**

1. 完成场景 2（在李四后加签赵六，step=0 是张三）
2. 张三审批 → 流程推进到李四（step=1）
3. 李四审批 → 流程推进到赵六（step=2，加签节点）
4. 赵六审批 → 流程推进到王五（step=3，原 step+2）
5. **验证**：整个流程通过，无节点跳过

- [ ] **Step 8: 场景 7 — 转交功能未受影响**

1. 在「选择操作」Modal 中保持默认「转交」
2. 选人 → 提交
3. **验证**：流程图刷新后该步骤的审批人被替换为新人（与改造前行为一致）
4. 后端 `WeixinOaApprovalInfo.approvalUserid` 更新为新人

- [ ] **Step 9: 提交验证报告**

```bash
cd "E:/Workspaces/fzrbs_oa" && git status --short
```

Expected: 无未提交修改（除 .umi 自动生成目录外）。

如果所有场景通过 → 完成。如果任一场景失败 → 回到对应 Task 修复后重测。

---

## Self-Review Notes

- **Spec coverage**：
  - § 4.1 接口（URL/方法/参数）— Task 1 ✓
  - § 4.2 权限校验 — Task 1 ✓
  - § 4.3 校验逻辑（agentid/userid/thirdNo/step/userid 存在/step 范围）— Task 1 ✓
  - § 4.4 核心逻辑（switch case、加载 flow、step 校验、构造 newNode、array_splice、事务）— Task 1 ✓
  - § 4.5 与 flowalteritem 对比（不更新 approvalUserid）— Task 1 ✓
  - § 5.1 改动文件清单 — Task 1+2+3 ✓
  - § 5.2 service.ts 新增 addsigner() — Task 2 ✓
  - § 5.3 viewflow.tsx（state、onAlterApprover、UserAutocomplete.onChange、重置）— Task 3 ✓
  - § 5.4 flow.tsx 零改动 — Plan 未列出此文件 ✓
  - § 6 数据流 — 全部在 Task 4 验证 ✓
  - § 7 错误处理（6 种错误）— Task 1 已实现，Task 4 验证 ✓
  - § 8 测试场景（7 个）— Task 4 ✓
  - § 9 实现要点（array_splice、JSON 读写、状态机一致性、前端模式状态）— Task 1+3 ✓

- **Placeholder scan**：无 TBD/TODO。所有 PHP/TypeScript 代码块完整。

- **Type consistency**：
  - 后端函数 `actionAddsigner` 在 Task 1 定义，Task 4 引用其 URL `/api/financerole/addsigner` 一致
  - 前端 `addsigner()` 函数在 Task 2 定义，Task 3 import 并调用，签名一致
  - `alterMode` state 在 Task 3 定义为 `'transfer' | 'addsigner'`，Task 3 重置为 `'transfer'` 一致
  - `Radio.Button value="transfer"` 对应 `alterMode === 'transfer'`，`value="addsigner"` 对应 `alterMode === 'addsigner'`，分支逻辑一致