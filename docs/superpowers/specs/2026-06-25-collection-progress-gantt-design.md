# 收款进度甘特图 — 设计

## 1. 概述

在 `finance/contract/` 目录下新建 `collectionGantt/` 独立组件，通过传入 `contractId` 调用，以甘特图形式对比展示**计划回款 vs 实际回款**的进度，视觉表现力强、让非财务人员一眼看懂当前合同回款是提前、正常还是逾期。

> 与历史 `Timeline` 列表式（按时间顺序列条目）相比，本组件以**时间-金额二维坐标系**展示回款节奏。

## 2. 目标与场景

- **单合同视图**：传入 `contractId` 渲染单份合同的回款甘特图。
- **复用性**：作为独立模块供合同详情、首页看板、逾期提醒等多个入口调用。
- **可读性**：让非财务人员一眼判断——哪些期次已收/未收/逾期。

## 3. 数据来源

### 3.1 数据表

| 表 | 字段 | 用途 |
|----|------|------|
| `fzrbs_contract` | `id`, `amount`, `signdate`, `paycollection`, `state` | 合同总额、签订日期、已回款合计 |
| `fzrbs_contract_paycondition` | `contractid`, `date`, `rate` | 计划回款节点：截止某日累计回款百分比 |
| `fzrbs_contract_paycollection` | `contractid`, `date`, `amount`, `state`, `valid` | 实际回款记录：日期+金额；`state=1/3` 为有效，`state=0` 为已删除 |

### 3.2 接口

复用现有接口 `GET /api/contract/getcontract?id={id}`，该接口已返回 `amount`、`signdate`、`payconditions[]`、`paycollections[]`、`paycollection` 字段，**无需新建后端接口**。

仅在客户端按以下规则加工即可：

| 维度 | 计划（Plan） | 实际（Actual） |
|------|--------------|----------------|
| 时间 | `paycondition.date` | `paycollection.date`（仅 `state=1/3` 且 `valid=1`） |
| 金额 | 累计 `rate% * amount` | 单笔 `amount`，按时间累计 |

## 4. 视觉设计

### 4.1 整体布局

```
              ┌──── 计划回款（Plan） ────┐    ┌─── 实际回款（Actual） ───┐
              浅色背景 + 节点圆点 marker         深色背景（按状态着色）
              ┌─────────────────────────┐    ┌──────────────────────────┐
              │ 计划累计回款百分比曲线  │    │ 实际累计回款百分比条     │
              └─────────────────────────┘    └──────────────────────────┘
 100% ─ ─ ─ ─ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄ ┄
  80%            ●━━━●
  60%                  ●━━━●
  40%                        ●━━━●━━━━━━● ← 实际
  20% ─────●━━━●─────────● ← 计划
   0% ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
       2026-01   2026-03   2026-05   2026-07   2026-09
                       合同签订↑
                                     ┊ ← 今天 (红色虚线)
                                     ┊
                                     今天
```

### 4.2 视觉规范

#### 4.2.1 坐标轴

- **X 轴**：时间（按月分格，跨度 = `min(计划最后期日期, 今天+3月)` ~ `max(计划最后期日期, 今天+3月)`）。
- **Y 轴**：累计回款百分比（0%–100%），5 个主刻度。
- **网格**：浅灰色虚线，弱化显示。

#### 4.2.2 计划回款（Plan）—— 上方

- **样式**：浅色填充矩形（`#e6f7ff` 背景，描边 `#91d5ff`），节点用实心圆 `●` 标记。
- **高度**：占图表区上 1/3。
- **节点 hover**：tooltip 显示「截至 {date} 应累计回款 {rate}%（{amount}元）」。
- **节点状态着色**（基于「当前累计实收 vs 节点 rate%」判定）：
  - **逾期**：今天 > 节点日期 且 累计实收 < `rate%` → 红色 `#ff4d4f`
  - **提前**：累计实收 ≥ `rate%`（无论是否到期）→ 绿色 `#52c41a`
  - **待收**：今天 ≤ 节点日期 且 累计实收 < `rate%` → 蓝色 `#1890ff`（默认）

#### 4.2.3 实际回款（Actual）—— 下方

- **样式**：深色填充条（`#262626`），按时间累计绘制折线/矩形。
- **高度**：占图表区下 1/3。
- **状态着色**（按 `实际累计 / 同期计划累计` 比例判定）：
  - **提前**（比例 ≥ 100%）→ 绿色 `#52c41a`
  - **正常**（90% ≤ 比例 < 100%）→ 黄色 `#faad14`
  - **逾期**（比例 < 90%）→ 红色 `#ff4d4f`
- **节点 hover**：tooltip 显示「{date} 实收 {amount}元（累计 {cumulativeAmount}元 / {cumulativeRate}%）」。

#### 4.2.4 关键里程碑

| 标记 | 样式 | 含义 |
|------|------|------|
| 合同签订日 | 绿色实线 `▼` 向下三角 | `contract.signdate` |
| 首次应收日 | 蓝色实线 `▼` | 最早一个 `paycondition.date` |
| 今天 | 红色虚线 + 文字「今天」 | 当前日期 |

#### 4.2.5 汇总统计（卡片）

组件上方展示 4 个汇总卡：

| 卡片 | 内容 | 计算方式 |
|------|------|---------|
| 合同总额 | `{amount}元` | `contract.amount` |
| 计划回款期次 | `{n} 期` | `payconditions.length` |
| 实际回款 | `{actualRate}%（{paycollection}元）` | `paycollection / amount` |
| 逾期期次 | `{overdueCount} 期` | 满足逾期判定的计划节点数 |

### 4.3 状态优先级

当数据缺失时，组件降级为：
- 无计划节点：仅显示实际回款条 + 提示「暂无计划回款节点」
- 无实际回款：仅显示计划节点（灰色 + 提示「尚无回款」）
- 两者皆无：显示空态卡片「暂无数据」

## 5. 组件设计

### 5.1 文件结构

```
mysite/src/pages/finance/contract/collectionGantt/
├── index.tsx           # 组件入口，导出 CollectionGantt
├── service.ts          # 调用 getcontract API
├── compute.ts          # 纯函数：数据加工（计划/实际/状态）
├── styles.css          # 甘特图样式
└── types.ts            # TypeScript 类型定义
```

### 5.2 Props

```typescript
interface CollectionGanttProps {
  contractId: number | string;
  height?: number;        // 可选，图表高度，默认 320
  compact?: boolean;      // 可选，紧凑模式（隐藏汇总卡），默认 false
}
```

### 5.3 数据加工

`compute.ts` 导出纯函数：

```typescript
// 加工计划数据：把 payconditions 转换成 { date, rate, cumulativeRate, cumulativeAmount, status }
function buildPlanSeries(contract, payconditions): PlanPoint[];

// 加工实际数据：把 paycollections 过滤后按时间排序、累计求和
function buildActualSeries(contract, paycollections): ActualPoint[];

// 判定单个计划节点的状态（提前/正常/逾期）
function judgePlanStatus(planPoint, actualSeries, today): 'early' | 'normal' | 'overdue' | 'pending';
```

### 5.4 渲染方案

**不引入新图表库**。使用纯 SVG + 现有 Ant Design 组件实现：

- 外层 `Card` 包住整个组件
- 顶部 `Row` 4 个 `Col` 渲染汇总卡
- 主体用 SVG（`<svg viewBox="...">`）绘制坐标轴、网格、计划条、实际条、关键线
- 每个 `PlanPoint` 与 `ActualPoint` 用 `<rect>` + `<circle>` + `<text>` 渲染
- 悬停提示用 Ant Design `Tooltip` 包住 SVG 元素

理由：仅一个图表、样式需与项目 UI 风格一致、避免引入 g2/g6 等大型库带来的 bundle 体积成本。

## 6. 调用方式

```typescript
// 在其他模块中引入
import CollectionGantt from '@/pages/finance/contract/collectionGantt';

// 完整模式（带汇总卡）
<CollectionGantt contractId={1234} />

// 紧凑模式（用于弹窗/侧栏）
<CollectionGantt contractId={1234} compact height={240} />
```

预期调用点（不限于）：
- `pages/finance/contract/view.tsx`（合同详情页，附加展示）
- `pages/finance/contract/debt/*`（欠款管理页，每个逾期合同缩略图）
- 后续首页/看板可平铺多张

## 7. 实现要点

1. **数据获取**：复用 `getcontract` 接口。
2. **纯函数加工**：`compute.ts` 中所有计算函数为纯函数，便于单测。
3. **时间跨度**：X 轴范围 = `[min(签合同日, 最早计划日, 实际首笔日), max(最后计划日, 今天 + 3 个月)]`，自动适配短期/长期合同。
4. **金额精度**：所有金额用 `parseFloat(x).toFixed(2)`，百分比保留 1 位小数。
5. **空态处理**：根据 `buildPlanSeries` / `buildActualSeries` 返回长度决定渲染分支。
6. **响应式**：组件宽度跟随父容器，X 轴文字过多时自动倾斜或省略。
7. **复用 timeline 模式**：保持与 `project/timeline` 一致的「service + index + 独立目录」结构，方便其他模块按相同模式扩展。

## 8. 后续可选扩展（不在本次范围）

- 多合同对比视图
- 导出 PNG / PDF
- 接入甘特图交互（拖动节点调整计划）
