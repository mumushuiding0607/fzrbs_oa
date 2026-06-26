# 收款进度仪表盘 — 设计 v2（替换原甘特图方案）

## 1. 概述

将 `CollectionGantt` 重构为 `CollectionDashboard`，采用「仪表盘」式布局：中央大数字 + 进度环 + 计划/实际对比条 + 状态灯 + 期次统计。任何非财务人员（不需懂"横轴时间/纵轴百分比"）都能在 3 秒内看出合同回款到了哪一步、是否健康。

> 替代原 `2026-06-25-collection-progress-gantt-design.md` 的二维坐标方案。原 `types.ts` / `service.ts` / `compute.ts` 保留复用，仅重写 `index.tsx`。

## 2. 目标与场景

- **单合同视图**：传入 `contractId` 渲染单份合同的回款仪表盘。
- **可读性优先**：3 秒读懂当前合同收款是否健康。
- **复用性**：作为独立模块供合同详情、首页看板、逾期提醒等调用。
- **代替原甘特图**：原 SVG gantt 不再使用，文件 `index.tsx` 完全重写。

## 3. 数据来源

完全复用 v1 已有数据加工逻辑：
- `service.ts` → `getContractGantt` 调用 `/api/contract/getcontract`
- `compute.ts` → `buildPlanSeries` / `buildActualSeries` / `decorateActualSeries` / `calculateOverdueCount` / `judgePlanStatus`
- 字段：`contract.amount`、`contract.paycollection`、`payconditions[]`、`paycollections[]`

仅新增一个派生函数 `computeDashboardMetrics()`，输出仪表盘所需的核心数字（不修改 `compute.ts`，在 `index.tsx` 内联或新建 `dashboard.ts`）。

## 4. 视觉设计

### 4.1 整体布局

```
┌────────────────────────────────────────────────────────┐
│  合同收款进度                          ●绿灯 正常        │  ← 标题 + 状态灯
│                                                        │
│         ╭───────╮                                      │
│        │   60%   │   ← 中央大数字 + 进度环 (AntD Progress.circle) │
│        │  ▓▓▓▓▓▓  │                                     │
│         ╰───────╯                                      │
│   已收 ¥60,000.00      还欠 ¥40,000.00                │
│                                                        │
│   计划进度   ▓▓▓▓▓▓▓░░░░  50%   (节点 4)              │
│   实际进度   ▓▓▓▓▓▓▓▓▓░  60%                          │
│                                                        │
│   😊 提前 1 期   ⚠ 逾期 0 期   ○ 待收 3 期            │  ← 三个状态小卡片
└────────────────────────────────────────────────────────┘
```

### 4.2 视觉规范

#### 4.2.1 顶部状态灯
- 圆点 + 文字：
  - **🟢 绿灯 正常** — 实际进度 ≥ 计划进度
  - **🟡 黄灯 关注** — 实际进度落后计划 0%~10%
  - **🔴 红灯 逾期** — 至少一个计划节点已到期且累计实收不足
- 状态判定（用现成 `calculateOverdueCount` + 简单比较）：
  - `overdueCount > 0` → 红灯
  - `actualRate < planRate` → 黄灯
  - 其他 → 绿灯

#### 4.2.2 中央进度环
- 用 AntD `<Progress type="circle" percent={actualRate} strokeColor={...} />`
- 直径 180px
- 环内显示大号数字（自动）
- 颜色按状态灯颜色：
  - 绿：`#52c41a`
  - 黄：`#faad14`
  - 红：`#ff4d4f`
- 数字精度：1 位小数

#### 4.2.3 已收/还欠 文本
- `<Statistic title="已收" value={...} precision={2} prefix="¥" />` × 2
- 居中显示在进度环下方
- 字号 16-18px

#### 4.2.4 计划 vs 实际 对比条
- 用 AntD `<Progress type="line" percent={...} strokeColor={...} />` × 2
- 左侧标签：「计划进度」「实际进度」
- 右侧标签：`{percent}%（节点 N）` / `{percent}%`
- 颜色：
  - 计划：固定蓝色 `#1890ff`
  - 实际：与状态灯同步（绿/黄/红）
- 高度 14px

#### 4.2.5 三个期次状态卡
- 横向三栏 `Row gutter={16}` + `Col span={8}`
- 每栏一个小卡片 `Card size="small"`：
  - 😊 / ⚠ / ○ 大图标
  - `提前 X 期` / `逾期 X 期` / `待收 X 期`
  - 数字 ≥ 1 时用对应颜色高亮（绿/红/蓝）
- 数据来源：
  - 提前：`planSeries.filter(p => p.status === 'early').length`
  - 逾期：`calculateOverdueCount(planSeries)`
  - 待收：`planSeries.filter(p => p.status === 'pending').length`

#### 4.2.6 紧凑模式（compact）
- 隐藏顶部统计卡（4 张）和 3 个期次状态卡
- 仅保留：状态灯 + 进度环 + 已收/还欠 + 计划/实际对比条
- 高度自适应

### 4.3 降级处理

- 无计划节点：隐藏「计划进度」条；状态灯默认按 actualRate 判定（≥50% 绿，<50% 黄）
- 无实际回款：进度环显示 0%；状态灯按「待收节点数 > 0」显示黄灯
- 两者皆无：显示 `<Empty description="暂无回款数据" />`

## 5. 组件设计

### 5.1 文件结构

```
mysite/src/pages/finance/contract/collectionGantt/
├── index.tsx           # ← 重写为仪表盘（保持默认导出名 CollectionGantt 兼容调用方）
├── service.ts          # 复用不变
├── compute.ts          # 复用不变
└── types.ts            # 复用不变
```

> **导出名保留** `CollectionGantt` 以避免 `view.tsx` 改动。如果用户更名需要，可全局替换。本期保持兼容。

### 5.2 Props

```typescript
interface CollectionDashboardProps {
  contractId: number | string;
  height?: number;     // 默认 360
  compact?: boolean;   // 默认 false
}
```

> 保持与 v1 相同 props，调用方零改动。

### 5.3 数据加工

不新建 `dashboard.ts`，在 `index.tsx` 中用 `useMemo` 计算三个仪表盘指标：

```typescript
const planRate = planSeries.length > 0
  ? planSeries[planSeries.length - 1].rate  // 最后一期累计
  : 0;

const overdueCount = useMemo(() => calculateOverdueCount(planSeries), [planSeries]);
const earlyCount = useMemo(() => planSeries.filter(p => p.status === 'early').length, [planSeries]);
const pendingCount = useMemo(() => planSeries.filter(p => p.status === 'pending').length, [planSeries]);

// 整体状态
const status: 'green' | 'yellow' | 'red' =
  overdueCount > 0 ? 'red'
  : actualRate < planRate ? 'yellow'
  : 'green';
```

### 5.4 渲染方案

完全使用 Ant Design 组件，**不再使用 SVG**：

| UI 元素 | 组件 |
|--------|------|
| 整体容器 | `<Card title="合同收款进度" extra={状态灯}>` |
| 进度环 | `<Progress type="circle" percent={...} strokeColor={...} />` |
| 已收/还欠 | `<Statistic prefix="¥" precision={2} />` |
| 计划/实际条 | `<Progress type="line" percent={...} strokeColor={...} />` |
| 期次状态卡 | `<Card size="small"> + Emoji + 文字` |
| 整体布局 | `<Row gutter={24}>` + `<Col span={12}>` |

理由：仪表盘风格本身就是 AntD 默认风格；不写 SVG 直接复用 AntD 组件，开发量最小、视觉一致。

## 6. 调用方式

不变：
```typescript
import CollectionGantt from '@/pages/finance/contract/collectionGantt';
<CollectionGantt contractId={id} compact />
```

## 7. 实现要点

1. **数据加工零改动**：复用 v1 的 service / compute / types。
2. **重写 index.tsx**：移除原 `GanttChart` 子组件和所有 SVG；改用 AntD `Progress` + `Statistic` + `Card` 组合。
3. **状态判定**：`overdueCount > 0` 优先 → 红；否则 `actualRate < planRate` → 黄；其他 → 绿。
4. **颜色同步**：进度环 / 实际条 / 期次卡片的「逾期」数字使用同一红色，保持视觉一致。
5. **空态处理**：见 § 4.3。
6. **响应式**：`<Row>` + `<Col span={...}>` 在小屏自动换行。
7. **保留高度参数**：`height` prop 仍控制中心区域高度（影响 `<Progress type="circle">` 直径），默认 360。

## 8. 后续可选扩展（不在本次范围）

- 仪表盘导出为图片
- 多合同对比仪表盘
- 接入推送提醒（根据状态变化触发）
