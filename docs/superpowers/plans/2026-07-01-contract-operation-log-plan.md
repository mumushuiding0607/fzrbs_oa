# 合同模块操作日志补全（P0）实施计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal**: 在 `ContractController` 的所有 P0 高危 action 末尾添加 `_operationlog` 调用，使全局审计表 `fzrbs_operation_log` 记录所有关键合同操作。

**Architecture**: 复用 `ApiBase::_operationlog()` 基类方法（已存在且自带密码脱敏），按现有约定（`RoleController` 等的写法）在每个 action 的成功路径末尾插入一行 `$this->_operationlog(['catalog' => ..., 'remark' => ...]);`。不动数据库 schema，不改前端。

**Tech Stack**: PHP 5.6+ (Yii2) + MySQL

## 现有约定（参考）

- 调用位置：每个 action 的成功路径（try 块内、commit 之前）
- 写法：
  ```php
  $this->_operationlog(['catalog' => $action, 'remark' => $remark]);
  ```
- `$action` 统一为 `新增` / `修改` / `删除`（必要时带业务前缀，如 `合同删除`、`合同存档`）
- `$remark` 含业务主键（合同ID/编号/金额/对方）+ 关键变更前后对比
- 已有调用样例：`RoleController.php:81, 124, 152`、`BudgetController.php:744, 1343, 1392, 4447`

## Global Constraints

- **零数据库改动**：不新增表/字段，复用 `fzrbs_operation_log` + `fzrbs_operation_log_params`
- **不动 `savelog()` 私有方法**：`ContractController.php:1446` 的 `savelog`（写入 `fzrbs_contract_log`）是合同子表历史，`_operationlog` 是全局审计表，**两者并存不重复**
- **不修改 `actionSavecontract` 已有逻辑**：仅在末尾 `savelog` 调用旁补一行 `_operationlog`
- **失败路径不记日志**：只在 try 块成功路径、transaction commit 之前记
- **HTTP 方法不动**：不修改路由/参数
- **PHP 5.6 兼容**：不用 null coalescing 运算符（`??`）、不用箭头函数
- **每个 action 一个 commit**：粒度最小化，便于回滚

---

## File Structure

| 文件 | 改动 |
|------|------|
| `yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php` | 在 11 个 action 末尾添加 `$this->_operationlog(...)` 调用 |

无任何其他文件改动。零数据库迁移，零前端改动。

---

### Task 1: actionDelcontract 留痕

**Files:**
- Modify: `yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php:551-583`

- [ ] **Step 1: 定位插入点**

打开 `ContractController.php`，找到 `actionDelcontract` 方法（551 行）。在 `$transaction->commit();` 之前（约 580 行）插入日志调用。

- [ ] **Step 2: 插入日志调用**

在 `$transaction->commit();` 之前（即 580 行 `}` 之前），加入：

```php
    $this->_operationlog([
      'catalog' => '合同删除',
      'remark' => '删除合同【' . $old['serial'] . '】名称【' . $old['title'] . '】金额【' . $old['amount'] . '】'
    ]);
```

- [ ] **Step 3: 验证 PHP 语法**

```bash
cd E:/Workspaces/fzrbs_oa/yii2.fznews.com.cn/basic
php -l modules/api/controllers/ContractController.php
```

预期：`No syntax errors detected`

- [ ] **Step 4: 手动验证**

1. 启动 yii2 dev server，确保数据库可连
2. 用 Postman / curl 调用 `GET /api/contract/delcontract?id=<可删除的合同id>`
3. 查 `SELECT * FROM fzrbs_operation_log WHERE catalog='合同删除' ORDER BY id DESC LIMIT 1;`
4. 预期：remark 含被删合同的 serial/title/amount

- [ ] **Step 5: 提交**

```bash
cd E:/Workspaces/fzrbs_oa
git add yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php
git commit -m "feat(contract): log contract deletion to operation log"
```

---

### Task 2: actionLock 留痕

**Files:**
- Modify: `yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php:233-256`

- [ ] **Step 1: 定位插入点**

找到 `actionLock`（233 行）。在 `FzrbsContract::updateAll(['state'=>$state],['id'=>$id]);`（254 行）之后、`return true;` 之前插入。

- [ ] **Step 2: 插入日志调用**

在 254 行后插入：

```php
    $this->_operationlog([
      'catalog' => $state == 0 ? '合同解档' : '合同存档',
      'remark' => '合同【' . $old['serial'] . '】名称【' . $old['title'] . '】' . ($state == 0 ? '解档' : '存档（state=1）')
    ]);
```

- [ ] **Step 3: 验证 PHP 语法**

```bash
cd E:/Workspaces/fzrbs_oa/yii2.fznews.com.cn/basic
php -l modules/api/controllers/ContractController.php
```

- [ ] **Step 4: 手动验证**

调用 `GET /api/contract/lock?id=<id>&state=1`（存档）和 `&state=0`（解档），查 `fzrbs_operation_log` 确认两条 catalog 分别为「合同存档」「合同解档」。

- [ ] **Step 5: 提交**

```bash
cd E:/Workspaces/fzrbs_oa
git add yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php
git commit -m "feat(contract): log contract lock/unlock to operation log"
```

---

### Task 3: actionNullify 留痕

**Files:**
- Modify: `yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php:258-277`

- [ ] **Step 1: 定位插入点**

找到 `actionNullify`（258 行）。在 `$old->save();`（275 行）之后、`return true;` 之前插入。

- [ ] **Step 2: 插入日志调用**

在 275 行后插入：

```php
    $this->_operationlog([
      'catalog' => '合同作废',
      'remark' => '作废合同【' . $old['serial'] . '】名称【' . $old['title'] . '】金额【' . $old['amount'] . '】'
    ]);
```

- [ ] **Step 3: 验证 PHP 语法**

```bash
cd E:/Workspaces/fzrbs_oa/yii2.fznews.com.cn/basic
php -l modules/api/controllers/ContractController.php
```

- [ ] **Step 4: 手动验证**

调用 `GET /api/contract/nullify?id=<id>&nullifyurls=<url>`，查 `fzrbs_operation_log` 确认 catalog='合同作废'。

- [ ] **Step 5: 提交**

```bash
cd E:/Workspaces/fzrbs_oa
git add yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php
git commit -m "feat(contract): log contract nullification to operation log"
```

---

### Task 4: actionSavepaycollection 留痕

**Files:**
- Modify: `yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php:1226-1280`

- [ ] **Step 1: 定位插入点**

找到 `actionSavepaycollection`（1226 行）。在 `FzrbsContract::updateAll(['paycollection'=>$paycollection],['id'=>$c['id']]);`（1270 行）之后、catch 块之前插入。

- [ ] **Step 2: 插入日志调用**

在 1270 行后插入：

```php
      $this->_operationlog([
        'catalog' => $obj['id'] ? '修改回款' : '新增回款',
        'remark' => '合同【' . $c['serial'] . '】' . ($obj['id'] ? '修改' : '新增') . '回款：金额【' . $obj['amount'] . '】，累计【' . $paycollection . '】'
      ]);
```

- [ ] **Step 3: 验证 PHP 语法**

```bash
cd E:/Workspaces/fzrbs_oa/yii2.fznews.com.cn/basic
php -l modules/api/controllers/ContractController.php
```

- [ ] **Step 4: 手动验证**

调用 `GET /api/contract/savepaycollection?contractid=<id>&amount=<amount>`，查 `fzrbs_operation_log` 确认 catalog='新增回款'。再调一次带 `id` 参数，确认 catalog='修改回款'。

- [ ] **Step 5: 提交**

```bash
cd E:/Workspaces/fzrbs_oa
git add yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php
git commit -m "feat(contract): log pay collection save/update to operation log"
```

---

### Task 5: actionSaveinvoice 留痕

**Files:**
- Modify: `yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php:1383-1442`

- [ ] **Step 1: 定位插入点**

找到 `actionSaveinvoice`（1383 行）。在 `for` 循环（1430 行）的 save 之后、`$transaction->commit();` 之前插入。

- [ ] **Step 2: 插入日志调用**

在 1434 行 `}` 之后、1436 行 `catch` 之前插入：

```php
        $this->_operationlog([
          'catalog' => $temp ? '发票关联合同' : '新增发票',
          'remark' => '合同【' . $c['serial'] . '】' . ($temp ? '关联发票：' : '开票：') . '发票号【' . $invoice['number'] . '】金额【' . $invoice['amount'] . '】'
        ]);
```

- [ ] **Step 3: 验证 PHP 语法**

```bash
cd E:/Workspaces/fzrbs_oa/yii2.fznews.com.cn/basic
php -l modules/api/controllers/ContractController.php
```

- [ ] **Step 4: 手动验证**

调用 `GET /api/contract/saveinvoice?contractid=<id>&EIid=<>&number=<>&amount=<>`，查 `fzrbs_operation_log` 确认 catalog 正确。

- [ ] **Step 5: 提交**

```bash
cd E:/Workspaces/fzrbs_oa
git add yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php
git commit -m "feat(contract): log invoice save/link to operation log"
```

---

### Task 6: actionSaveledger + actionDelledger 留痕

**Files:**
- Modify: `yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php:527-549 (Delledger), 725-? (Saveledger)`

- [ ] **Step 1: 定位 Delledger 插入点**

找到 `actionDelledger`（527 行）。在 `$transaction->commit();` 之前（约 548 行）插入。

- [ ] **Step 2: 在 Delledger 插入日志**

```php
    $this->_operationlog([
      'catalog' => '删除台账',
      'remark' => '删除合同【' . ($ledger['serial'] ?? '') . '】台账 ID【' . $id . '】'
    ]);
```

- [ ] **Step 3: 定位 Saveledger 插入点**

找到 `actionSaveledger`（725 行）。先读完整个方法，找到 `try` 块成功路径的 `$transaction->commit();` 之前。

- [ ] **Step 4: 在 Saveledger 插入日志**

```php
    $this->_operationlog([
      'catalog' => $obj['id'] ? '修改台账' : '新增台账',
      'remark' => '合同【' . ($c['serial'] ?? '') . '】' . ($obj['id'] ? '修改' : '新增') . '台账：金额【' . ($obj['amount'] ?? '') . '】'
    ]);
```

- [ ] **Step 5: 验证 PHP 语法**

```bash
cd E:/Workspaces/fzrbs_oa/yii2.fznews.com.cn/basic
php -l modules/api/controllers/ContractController.php
```

- [ ] **Step 6: 手动验证**

分别调用 Saveledger 和 Delledger，查 `fzrbs_operation_log` 确认。

- [ ] **Step 7: 提交**

```bash
cd E:/Workspaces/fzrbs_oa
git add yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php
git commit -m "feat(contract): log ledger save/delete to operation log"
```

---

### Task 7: actionAgree + actionReject + actionCancel 留痕

**Files:**
- Modify: `yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php:1763-1900`

- [ ] **Step 1: 读完三个方法**

分别读 `actionAgree`（1763）、`actionReject`（1860）、`actionCancel`（1882）的完整代码，定位每个方法 `try` 块成功路径中 `WorkflowParse` 调用之后、`$transaction->commit();` 之前的插入点。

- [ ] **Step 2: 在 actionAgree 插入日志**

在 `$wfp->updateAfterFlowChange(...)`（1776 行）之后、try 块结束前插入：

```php
      $this->_operationlog([
        'catalog' => '合同审批通过',
        'remark' => '合同【' . ($d['serial'] ?? $d['contractid'] ?? '') . '】审批通过'
      ]);
```

- [ ] **Step 3: 在 actionReject 插入日志**

按同模式，remark 写「审批驳回」：

```php
      $this->_operationlog([
        'catalog' => '合同审批驳回',
        'remark' => '合同【' . ($postdatas['serial'] ?? $postdatas['contractid'] ?? $postdatas['thirdNo'] ?? '') . '】审批驳回'
      ]);
```

- [ ] **Step 4: 在 actionCancel 插入日志**

```php
      $this->_operationlog([
        'catalog' => '合同审批撤销',
        'remark' => '合同【' . ($postdatas['serial'] ?? $postdatas['contractid'] ?? $postdatas['thirdNo'] ?? '') . '】审批撤销'
      ]);
```

- [ ] **Step 5: 验证 PHP 语法**

```bash
cd E:/Workspaces/fzrbs_oa/yii2.fznews.com.cn/basic
php -l modules/api/controllers/ContractController.php
```

- [ ] **Step 6: 手动验证**

如果有可走完审批流的测试合同，依次触发同意/驳回/撤销操作，查 `fzrbs_operation_log` 确认三条记录。

- [ ] **Step 7: 提交**

```bash
cd E:/Workspaces/fzrbs_oa
git add yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php
git commit -m "feat(contract): log agree/reject/cancel approval actions"
```

---

### Task 8: actionStartdeal + actionAltercharger 留痕

**Files:**
- Modify: `yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php:2162-2200`

- [ ] **Step 1: 读完两个方法**

读 `actionAltercharger`（2162）和 `actionStartdeal`（2187）的完整代码。

- [ ] **Step 2: 在 actionAltercharger 插入日志**

在 `updateAll` 成功后插入：

```php
    $this->_operationlog([
      'catalog' => '修改合同负责人',
      'remark' => '合同【' . ($old['serial'] ?? '') . '】负责人由【' . $oldcharger . '】改为【' . $newcharger . '】'
    ]);
```

（若代码里已有 $oldcharger/$newcharger 变量；否则用 `$old['charger']` 和 `$this->_request['charger']`）

- [ ] **Step 3: 在 actionStartdeal 插入日志**

```php
    $this->_operationlog([
      'catalog' => '启动合同催收',
      'remark' => '合同【' . ($contract['serial'] ?? '') . '】启动催收'
    ]);
```

- [ ] **Step 4: 验证 PHP 语法**

```bash
cd E:/Workspaces/fzrbs_oa/yii2.fznews.com.cn/basic
php -l modules/api/controllers/ContractController.php
```

- [ ] **Step 5: 手动验证**

分别调用 `altercharger` 和 `startdeal` 接口，查 `fzrbs_operation_log`。

- [ ] **Step 6: 提交**

```bash
cd E:/Workspaces/fzrbs_oa
git add yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php
git commit -m "feat(contract): log altercharger and startdeal actions"
```

---

### Task 9: actionSavecontract 补 `_operationlog`（与现有 savelog 并存）

**Files:**
- Modify: `yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php:440`

- [ ] **Step 1: 定位插入点**

`actionSavecontract` 已调用 `$this->savelog(...)`（440 行）。在其后立即插入一行 `_operationlog`。

- [ ] **Step 2: 插入日志**

```php
      $this->_operationlog([
        'catalog' => $action == 'update' ? '修改合同' : '新增合同',
        'remark' => ($action == 'update' ? '修改' : '新增') . '合同【' . $obj['serial'] . '】名称【' . $obj['title'] . '】金额【' . $obj['amount'] . '】对方【' . ($obj['partbname'] ?? '') . '】'
      ]);
```

- [ ] **Step 3: 验证 PHP 语法**

```bash
cd E:/Workspaces/fzrbs_oa/yii2.fznews.com.cn/basic
php -l modules/api/controllers/ContractController.php
```

- [ ] **Step 4: 手动验证**

1. 新增一份合同 → 查 `fzrbs_operation_log` 应有「新增合同」catalog，`fzrbs_contract_log` 也应有 savelog 记录
2. 修改该合同 → 两条表都应有新记录

- [ ] **Step 5: 提交**

```bash
cd E:/Workspaces/fzrbs_oa
git add yii2.fznews.com.cn/basic/modules/api/controllers/ContractController.php
git commit -m "feat(contract): log savecontract to operation log alongside contract log"
```

---

## Self-Review

**1. Spec coverage**: 调研中 P0 列出的 15 个 action 中 8 个是合同相关（Savecontract / Delcontract / Lock / Nullify / Savepaycollection / Saveinvoice / Saveledger+Delledger / Agree+Reject+Cancel / Startdeal+Altercharger），全部覆盖。

**2. Placeholder scan**: 无 TBD / TODO / "implement later" / "类似 Task N"。每个步骤含具体代码。

**3. Type consistency**: `$action` 字符串值约定为 `新增/修改/删除`（必要时带前缀如 `合同删除`），与 `RoleController` 现有写法一致；`_operationlog` 调用签名 `array('catalog'=>str, 'remark'=>str)` 与基类 `ApiBase.php:204` 签名匹配。

**4. Scope 检查**:
- Savecontract 的 savelog 是合同子表历史（写 `fzrbs_contract_log`），与 `_operationlog`（写 `fzrbs_operation_log`）目标不同，**并存不冲突**
- 失败路径不记日志（仅 commit 前记），避免回滚后产生脏日志
- 不动 schema、零迁移
