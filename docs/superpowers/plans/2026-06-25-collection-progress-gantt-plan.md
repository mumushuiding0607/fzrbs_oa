# 收款进度甘特图 — 实施计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 新建一个独立可复用的 React 组件 `CollectionGantt`，传入 `contractId` 即可以甘特图形式对比展示「计划回款 vs 实际回款」进度，并通过状态着色让非财务人员一眼判断提前/正常/逾期。

**Architecture:** 复用现有 `getcontract` API，客户端用纯函数（`compute.ts`）加工计划/实际数据，主组件（`index.tsx`）用 Ant Design `Card`/`Row`/`Col`/`Tooltip` 包裹纯 SVG 渲染坐标轴、计划条、实际条与里程碑。不引入新的图表库。

**Tech Stack:** React 17 + TypeScript + Ant Design 5 + UmiJS 3.5 + moment

## Global Constraints

- 文件路径中所有目录均使用 `mysite/src/pages/finance/contract/collectionGantt/`
- 使用项目已有的 `@/*` 路径别名（指向 `src/*`）
- API 复用 `getcontract`，**不新建后端接口**
- 颜色值与设计文档一致：`#e6f7ff`/`#91d5ff`（计划条）、`#1890ff`（待收/默认）、`#52c41a`（提前/绿色）、`#faad14`（正常/黄色）、`#ff4d4f`（逾期/红色）、`#262626`（实际条底色）
- 时间范围 X 轴 = `[min(签合同日, 最早计划日, 实际首笔日), max(最后计划日, 今天+3月)]`
- 所有金额渲染用 `parseFloat(x).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })`
- 现有 `pages/finance/contract/view.tsx` 引入本组件作为首次落地入口
- 现有 timeline 模块（`pages/finance/budget/project/timeline/`）作为结构参考
- 保持单一职责：`types.ts` 只放类型、`service.ts` 只放 API、`compute.ts` 只放纯函数、`index.tsx` 只做组装与渲染
- 任何 modify 操作只触及为接入组件所必需的最小范围

---

## File Structure

新建 4 个文件：

| 文件 | 职责 |
|------|------|
| `mysite/src/pages/finance/contract/collectionGantt/types.ts` | TypeScript 类型定义（`PlanPoint` / `ActualPoint` / `PlanStatus` / `ActualStatus` / `CollectionGanttProps`） |
| `mysite/src/pages/finance/contract/collectionGantt/service.ts` | 调用 `getcontract` API 并裁剪为组件所需字段 |
| `mysite/src/pages/finance/contract/collectionGantt/compute.ts` | 纯函数：`buildPlanSeries`、`buildActualSeries`、`judgePlanStatus`、`calculateOverdueCount`、`computeAxisRange` |
| `mysite/src/pages/finance/contract/collectionGantt/index.tsx` | 组件入口，导出 `CollectionGantt`，包含汇总卡 + SVG 图表 |

修改 1 个文件：

| 文件 | 改动 |
|------|------|
| `mysite/src/pages/finance/contract/view.tsx` | 在合同详情中插入 `<CollectionGantt contractId={id} compact />`（作为「收款进度」附加展示） |

---

### Task 1: 创建模块目录与类型定义文件

**Files:**
- Create: `mysite/src/pages/finance/contract/collectionGantt/types.ts`

- [ ] **Step 1: 创建 types.ts**

在 `mysite/src/pages/finance/contract/collectionGantt/types.ts` 写入：

```typescript
// 计划节点状态
export type PlanStatus = 'early' | 'overdue' | 'pending';

// 实际回款点状态
export type ActualStatus = 'early' | 'normal' | 'overdue';

// 计划回款节点（截止 date 应累计回款 rate%）
export interface PlanPoint {
  date: string;            // YYYY-MM-DD
  rate: number;            // 累计回款百分比（0-100）
  cumulativeAmount: number; // rate% * contract.amount
  status: PlanStatus;      // 当前节点的颜色状态
  statusColor: string;     // 实际颜色 hex
}

// 实际回款节点（按时间累计的实际收款）
export interface ActualPoint {
  date: string;            // YYYY-MM-DD
  amount: number;          // 当笔回款金额
  cumulativeAmount: number; // 截至该笔累计实收
  cumulativeRate: number;  // 累计实收 / 合同总额 × 100
  status: ActualStatus;
  statusColor: string;
}

// 坐标轴范围
export interface AxisRange {
  xMin: number; // 毫秒
  xMax: number; // 毫秒
  xTicks: { ms: number; label: string }[]; // 月刻度
}

// 组件 props
export interface CollectionGanttProps {
  contractId: number | string;
  height?: number;     // 默认 320
  compact?: boolean;   // true 时隐藏汇总卡
}

// 颜色常量
export const COLORS = {
  PLAN_BG: '#e6f7ff',
  PLAN_BORDER: '#91d5ff',
  PENDING: '#1890ff',
  EARLY: '#52c41a',
  NORMAL: '#faad14',
  OVERDUE: '#ff4d4f',
  ACTUAL_BASE: '#262626',
  GRID: '#f0f0f0',
  AXIS: '#bfbfbf',
  TODAY: '#ff4d4f',
  SIGN_DATE: '#52c41a',
  FIRST_DUE: '#1890ff',
} as const;
```

- [ ] **Step 2: 验证文件可被 TypeScript 解析**

```bash
cd "E:/Workspaces/fzrbs_oa/mysite" && npx tsc --noEmit src/pages/finance/contract/collectionGantt/types.ts
```

Expected: 无错误（可能输出 `Cannot find module 'react'` 之类的提示可以忽略，只要没有类型错误）。

- [ ] **Step 3: 提交**

```bash
cd "E:/Workspaces/fzrbs_oa" && git add mysite/src/pages/finance/contract/collectionGantt/types.ts
git commit -m "feat(contract): add types for collection progress gantt"
```

---

### Task 2: 创建 service.ts 封装 API

**Files:**
- Create: `mysite/src/pages/finance/contract/collectionGantt/service.ts`

- [ ] **Step 1: 创建 service.ts**

在 `mysite/src/pages/finance/contract/collectionGantt/service.ts` 写入：

```typescript
// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

export interface ContractGanttData {
  contract: {
    id: number;
    amount: number;
    signdate: string;
    paycollection: number;
  };
  payconditions: { date: string; rate: number }[];
  paycollections: { date: string; amount: number; state: number; valid: number }[];
}

// 调用现有 getcontract 接口并裁剪为组件所需字段
export async function getContractGantt(
  params: { id: number | string },
  options?: { [key: string]: any },
) {
  return request<{ data: ContractGanttData; errorMessage?: string }>(
    '/api/contract/getcontract',
    {
      method: 'GET',
      params: { ...params },
      ...(options || {}),
    },
  ).then((res: any) => {
    if (res.errorMessage) return { errorMessage: res.errorMessage };
    const d = res.data || {};
    return {
      data: {
        contract: {
          id: d.id,
          amount: parseFloat(d.amount) || 0,
          signdate: d.signdate || '',
          paycollection: parseFloat(d.paycollection) || 0,
        },
        payconditions: (d.payconditions || []).map((p: any) => ({
          date: p.date,
          rate: parseFloat(p.rate) || 0,
        })),
        paycollections: (d.paycollections || []).map((p: any) => ({
          date: p.date,
          amount: parseFloat(p.amount) || 0,
          state: parseInt(p.state, 10) || 0,
          valid: parseInt(p.valid, 10) || 1,
        })),
      },
    };
  });
}
```

- [ ] **Step 2: 验证文件可被 TypeScript 解析**

```bash
cd "E:/Workspaces/fzrbs_oa/mysite" && npx tsc --noEmit src/pages/finance/contract/collectionGantt/service.ts
```

Expected: 无类型错误。

- [ ] **Step 3: 提交**

```bash
cd "E:/Workspaces/fzrbs_oa" && git add mysite/src/pages/finance/contract/collectionGantt/service.ts
git commit -m "feat(contract): add service wrapper for collection gantt"
```

---

### Task 3: 实现 compute.ts 纯函数

**Files:**
- Create: `mysite/src/pages/finance/contract/collectionGantt/compute.ts`

- [ ] **Step 1: 创建 compute.ts**

在 `mysite/src/pages/finance/contract/collectionGantt/compute.ts` 写入：

```typescript
import moment from 'moment';
import { PlanPoint, ActualPoint, PlanStatus, ActualStatus, AxisRange, COLORS } from './types';

// 判定单个计划节点的状态
//   overdue: 今天 > 节点日期 且 累计实收 < rate%
//   early:   累计实收 >= rate%
//   pending: 今天 <= 节点日期 且 累计实收 < rate%
export function judgePlanStatus(
  date: string,
  rate: number,
  actualCumulativeAmount: number,
  totalAmount: number,
  today: moment.Moment,
): PlanStatus {
  const actualRate = totalAmount > 0 ? (actualCumulativeAmount / totalAmount) * 100 : 0;
  if (actualRate >= rate) return 'early';
  if (today.isAfter(moment(date).endOf('day'))) return 'overdue';
  return 'pending';
}

// 把 payconditions 加工为 PlanPoint[]
export function buildPlanSeries(
  contractAmount: number,
  payconditions: { date: string; rate: number }[],
  actualCumulativeAmount: number,
  today: moment.Moment,
): PlanPoint[] {
  return (payconditions || [])
    .map((p) => {
      const rate = Number(p.rate) || 0;
      const status = judgePlanStatus(
        p.date,
        rate,
        actualCumulativeAmount,
        contractAmount,
        today,
      );
      const statusColor =
        status === 'early'
          ? COLORS.EARLY
          : status === 'overdue'
          ? COLORS.OVERDUE
          : COLORS.PENDING;
      return {
        date: p.date,
        rate,
        cumulativeAmount: (rate / 100) * contractAmount,
        status,
        statusColor,
      };
    })
    .sort((a, b) => moment(a.date).valueOf() - moment(b.date).valueOf());
}

// 把 paycollections 过滤+排序+累计求和
export function buildActualSeries(
  paycollections: { date: string; amount: number; state: number; valid: number }[],
): ActualPoint[] {
  const valid = (paycollections || []).filter(
    (p) => (p.state === 1 || p.state === 3) && (p.valid ?? 1) === 1,
  );
  valid.sort((a, b) => moment(a.date).valueOf() - moment(b.date).valueOf());

  const points: ActualPoint[] = [];
  let cumulativeAmount = 0;
  for (const p of valid) {
    // state=0 表示删除，amount 会变为负数（参考 paycollection.tsx），用绝对值
    cumulativeAmount += Math.abs(Number(p.amount) || 0);
    points.push({
      date: p.date,
      amount: Math.abs(Number(p.amount) || 0),
      cumulativeAmount,
      cumulativeRate: 0, // 后续在组件中乘 amount 填充
      status: 'normal',  // 后续在组件中重新计算
      statusColor: COLORS.ACTUAL_BASE,
    });
  }
  return points;
}

// 重新计算 actual 的 cumulativeRate 与 status
// planSeries 用于判断该时间点对应的计划累计
export function decorateActualSeries(
  actual: ActualPoint[],
  contractAmount: number,
  planSeries: PlanPoint[],
): ActualPoint[] {
  if (contractAmount <= 0) return actual;
  return actual.map((p) => {
    const cumulativeRate = (p.cumulativeAmount / contractAmount) * 100;
    // 找该日期之前的最近一个 plan 节点，作为「同期计划累计」
    const planAtTime = planSeries
      .filter((pl) => moment(pl.date).valueOf() <= moment(p.date).valueOf())
      .slice(-1)[0];
    const planRate = planAtTime ? planAtTime.rate : 0;
    let status: ActualStatus = 'normal';
    if (planRate > 0) {
      const ratio = cumulativeRate / planRate;
      if (ratio >= 1) status = 'early';
      else if (ratio < 0.9) status = 'overdue';
      else status = 'normal';
    } else {
      // 没有计划数据时按累计率判定
      if (cumulativeRate >= 100) status = 'early';
      else if (cumulativeRate < 50) status = 'overdue';
    }
    const statusColor =
      status === 'early'
        ? COLORS.EARLY
        : status === 'overdue'
        ? COLORS.OVERDUE
        : COLORS.NORMAL;
    return { ...p, cumulativeRate, status, statusColor };
  });
}

// 计算 X 轴范围与月刻度
export function computeAxisRange(
  signdate: string,
  planSeries: PlanPoint[],
  actualSeries: ActualPoint[],
  today: moment.Moment,
): AxisRange {
  const candidates: moment.Moment[] = [];
  if (signdate) candidates.push(moment(signdate));
  if (planSeries.length) candidates.push(moment(planSeries[0].date));
  if (actualSeries.length) candidates.push(moment(actualSeries[0].date));
  candidates.push(today.clone().add(3, 'month'));

  let xMin = candidates.length
    ? candidates.reduce((a, b) => (a.isBefore(b) ? a : b)).valueOf()
    : today.valueOf();
  let xMax = moment(planSeries[planSeries.length - 1]?.date || today.format('YYYY-MM-DD'))
    .valueOf();
  xMax = Math.max(xMax, today.clone().add(3, 'month').valueOf());

  // 按月对齐 xMin 到月初
  xMin = moment(xMin).startOf('month').valueOf();
  xMax = moment(xMax).endOf('month').valueOf();

  const xTicks: { ms: number; label: string }[] = [];
  let cursor = moment(xMin);
  while (cursor.valueOf() <= xMax) {
    xTicks.push({ ms: cursor.valueOf(), label: cursor.format('YYYY-MM') });
    cursor = cursor.add(1, 'month');
  }
  return { xMin, xMax, xTicks };
}

// 计算逾期期次数量
export function calculateOverdueCount(planSeries: PlanPoint[]): number {
  return planSeries.filter((p) => p.status === 'overdue').length;
}
```

- [ ] **Step 2: 验证类型正确**

```bash
cd "E:/Workspaces/fzrbs_oa/mysite" && npx tsc --noEmit src/pages/finance/contract/collectionGantt/compute.ts
```

Expected: 无类型错误。

- [ ] **Step 3: 提交**

```bash
cd "E:/Workspaces/fzrbs_oa" && git add mysite/src/pages/finance/contract/collectionGantt/compute.ts
git commit -m "feat(contract): add compute functions for collection gantt"
```

---

### Task 4: 实现 CollectionGantt 主组件

**Files:**
- Create: `mysite/src/pages/finance/contract/collectionGantt/index.tsx`

- [ ] **Step 1: 创建 index.tsx 骨架（数据获取 + 汇总卡）**

在 `mysite/src/pages/finance/contract/collectionGantt/index.tsx` 写入第一版（先实现汇总卡与数据流，SVG 留空渲染）：

```typescript
import React, { useEffect, useMemo, useState } from 'react';
import { Card, Col, Row, Spin, Tag, Tooltip, Empty } from 'antd';
import moment from 'moment';
import { getContractGantt, ContractGanttData } from './service';
import {
  buildPlanSeries,
  buildActualSeries,
  decorateActualSeries,
  computeAxisRange,
  calculateOverdueCount,
} from './compute';
import { CollectionGanttProps, COLORS } from './types';

const fmtAmount = (n: number) =>
  n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const fmtRate = (n: number) => n.toFixed(1);

const CollectionGantt: React.FC<CollectionGanttProps> = ({
  contractId,
  height = 320,
  compact = false,
}) => {
  const [loading, setLoading] = useState(false);
  const [data, setData] = useState<ContractGanttData | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!contractId) return;
    setLoading(true);
    getContractGantt({ id: contractId })
      .then((res: any) => {
        if (res.errorMessage) {
          setError(res.errorMessage);
          setData(null);
        } else {
          setData(res.data);
          setError(null);
        }
      })
      .catch((e) => setError(String(e)))
      .finally(() => setLoading(false));
  }, [contractId]);

  const today = useMemo(() => moment().startOf('day'), []);

  const planSeries = useMemo(
    () =>
      data
        ? buildPlanSeries(
            data.contract.amount,
            data.payconditions,
            data.contract.paycollection,
            today,
          )
        : [],
    [data, today],
  );

  const actualRaw = useMemo(
    () => (data ? buildActualSeries(data.paycollections) : []),
    [data],
  );
  const actualSeries = useMemo(
    () => (data ? decorateActualSeries(actualRaw, data.contract.amount, planSeries) : []),
    [actualRaw, data, planSeries],
  );

  const overdueCount = useMemo(() => calculateOverdueCount(planSeries), [planSeries]);
  const actualRate =
    data && data.contract.amount > 0
      ? (data.contract.paycollection / data.contract.amount) * 100
      : 0;

  if (loading) {
    return (
      <Card>
        <div style={{ textAlign: 'center', padding: 40 }}>
          <Spin /> 加载中...
        </div>
      </Card>
    );
  }
  if (error) {
    return (
      <Card>
        <div style={{ color: COLORS.OVERDUE, padding: 20 }}>{error}</div>
      </Card>
    );
  }
  if (!data) return <Card><Empty description="暂无数据" /></Card>;

  const hasPlan = planSeries.length > 0;
  const hasActual = actualSeries.length > 0;
  if (!hasPlan && !hasActual) {
    return <Card><Empty description="暂无回款数据" /></Card>;
  }

  return (
    <Card
      title="收款进度"
      size="small"
      extra={
        <span style={{ color: '#999', fontSize: 12 }}>
          更新于 {today.format('YYYY-MM-DD')}
        </span>
      }
    >
      {!compact && (
        <Row gutter={16} style={{ marginBottom: 16 }}>
          <Col span={6}>
            <Card size="small">
              <div style={{ color: '#999', fontSize: 12 }}>合同总额</div>
              <div style={{ fontSize: 18, fontWeight: 'bold' }}>
                {fmtAmount(data.contract.amount)}元
              </div>
            </Card>
          </Col>
          <Col span={6}>
            <Card size="small">
              <div style={{ color: '#999', fontSize: 12 }}>计划回款期次</div>
              <div style={{ fontSize: 18, fontWeight: 'bold' }}>
                {planSeries.length} 期
              </div>
            </Card>
          </Col>
          <Col span={6}>
            <Card size="small">
              <div style={{ color: '#999', fontSize: 12 }}>实际回款</div>
              <div style={{ fontSize: 18, fontWeight: 'bold', color: COLORS.EARLY }}>
                {fmtRate(actualRate)}% ({fmtAmount(data.contract.paycollection)}元)
              </div>
            </Card>
          </Col>
          <Col span={6}>
            <Card size="small">
              <div style={{ color: '#999', fontSize: 12 }}>逾期期次</div>
              <div
                style={{
                  fontSize: 18,
                  fontWeight: 'bold',
                  color: overdueCount > 0 ? COLORS.OVERDUE : '#999',
                }}
              >
                {overdueCount} 期
              </div>
            </Card>
          </Col>
        </Row>
      )}
      <div style={{ color: '#999', fontSize: 12, marginBottom: 8 }}>
        （图表区占位，下一步填充 SVG）
      </div>
    </Card>
  );
};

export default CollectionGantt;
```

- [ ] **Step 2: 验证类型正确**

```bash
cd "E:/Workspaces/fzrbs_oa/mysite" && npx tsc --noEmit src/pages/finance/contract/collectionGantt/index.tsx
```

Expected: 无类型错误。

- [ ] **Step 3: 提交**

```bash
cd "E:/Workspaces/fzrbs_oa" && git add mysite/src/pages/finance/contract/collectionGantt/index.tsx
git commit -m "feat(contract): add collection gantt shell with summary cards"
```

- [ ] **Step 4: 用 Edit 工具添加 SVG 图表渲染**

把 `(图表区占位，下一步填充 SVG)` 替换为下面这段 SVG 渲染代码。

找到这段：

```tsx
      <div style={{ color: '#999', fontSize: 12, marginBottom: 8 }}>
        （图表区占位，下一步填充 SVG）
      </div>
```

替换为：

```tsx
      <GanttChart
        height={height}
        planSeries={planSeries}
        actualSeries={actualSeries}
        signdate={data.contract.signdate}
        today={today}
      />
    </Card>
  );
};

// ===== SVG 图表子组件 =====
const GanttChart: React.FC<{
  height: number;
  planSeries: any[];
  actualSeries: any[];
  signdate: string;
  today: moment.Moment;
}> = ({ height, planSeries, actualSeries, signdate, today }) => {
  const width = 1000; // viewBox 宽度，配合 preserveAspectRatio 横向自适应
  const padding = { top: 30, right: 20, bottom: 40, left: 50 };
  const chartW = width - padding.left - padding.right;
  const chartH = height - padding.top - padding.bottom;
  const axis = useMemo(
    () => computeAxisRange(signdate, planSeries, actualSeries, today),
    [signdate, planSeries, actualSeries, today],
  );

  const xScale = (ms: number) =>
    padding.left + ((ms - axis.xMin) / (axis.xMax - axis.xMin)) * chartW;
  const yScale = (rate: number) => padding.top + chartH - (rate / 100) * chartH;

  const yTicks = [0, 25, 50, 75, 100];
  const firstDueDate = planSeries[0]?.date;

  return (
    <div style={{ width: '100%', overflowX: 'auto' }}>
      <svg
        viewBox={`0 0 ${width} ${height}`}
        style={{ width: '100%', height, display: 'block' }}
        preserveAspectRatio="xMidYMid meet"
      >
        {/* Y 轴网格 + 标签 */}
        {yTicks.map((t) => (
          <g key={`y-${t}`}>
            <line
              x1={padding.left}
              x2={padding.left + chartW}
              y1={yScale(t)}
              y2={yScale(t)}
              stroke={COLORS.GRID}
              strokeDasharray="3 3"
            />
            <text
              x={padding.left - 8}
              y={yScale(t) + 4}
              textAnchor="end"
              fontSize="11"
              fill="#999"
            >
              {t}%
            </text>
          </g>
        ))}

        {/* X 轴刻度 + 标签 */}
        {axis.xTicks.map((tk) => (
          <g key={`x-${tk.ms}`}>
            <line
              x1={xScale(tk.ms)}
              x2={xScale(tk.ms)}
              y1={padding.top}
              y2={padding.top + chartH}
              stroke={COLORS.GRID}
              strokeDasharray="2 4"
            />
            <text
              x={xScale(tk.ms)}
              y={padding.top + chartH + 16}
              textAnchor="middle"
              fontSize="11"
              fill="#999"
            >
              {tk.label}
            </text>
          </g>
        ))}

        {/* 计划条 - 浅色背景 */}
        <rect
          x={padding.left}
          y={padding.top}
          width={chartW}
          height={chartH / 3}
          fill={COLORS.PLAN_BG}
          stroke={COLORS.PLAN_BORDER}
          strokeWidth={1}
        />

        {/* 计划节点 */}
        {planSeries.map((p, i) => {
          const x = xScale(moment(p.date).valueOf());
          const y = yScale(p.rate);
          return (
            <Tooltip
              key={`plan-${i}`}
              title={`截至 ${moment(p.date).format('YYYY-MM-DD')} 应累计回款 ${p.rate}%（${fmtAmount(p.cumulativeAmount)}元）`}
            >
              <g style={{ cursor: 'pointer' }}>
                <line
                  x1={x}
                  x2={x}
                  y1={y}
                  y2={padding.top + chartH / 3}
                  stroke={p.statusColor}
                  strokeWidth={1.5}
                />
                <circle
                  cx={x}
                  cy={y}
                  r={5}
                  fill={p.statusColor}
                  stroke="#fff"
                  strokeWidth={1.5}
                />
                {p.status === 'overdue' && (
                  <text
                    x={x + 8}
                    y={y - 6}
                    fontSize="10"
                    fill={p.statusColor}
                  >
                    逾期
                  </text>
                )}
              </g>
            </Tooltip>
          );
        })}

        {/* 实际回款 - 阶梯线 */}
        {actualSeries.length > 0 && (
          <g>
            <polyline
              points={[
                [padding.left, padding.top + chartH],
                ...actualSeries.map((a) => [
                  xScale(moment(a.date).valueOf()),
                  yScale(a.cumulativeRate),
                ] as [number, number]),
              ]
                .map(([x, y]) => `${x},${y}`)
                .join(' ')}
              fill="none"
              stroke={COLORS.ACTUAL_BASE}
              strokeWidth={2}
            />
            {actualSeries.map((a, i) => (
              <Tooltip
                key={`act-${i}`}
                title={`${moment(a.date).format('YYYY-MM-DD')} 实收 ${fmtAmount(a.amount)}元（累计 ${fmtAmount(a.cumulativeAmount)}元 / ${fmtRate(a.cumulativeRate)}%）`}
              >
                <rect
                  x={xScale(moment(a.date).valueOf()) - 4}
                  y={yScale(a.cumulativeRate) - 4}
                  width={8}
                  height={8}
                  fill={a.statusColor}
                  style={{ cursor: 'pointer' }}
                />
              </Tooltip>
            ))}
          </g>
        )}

        {/* 合同签订日 */}
        {signdate && (
          <g>
            <line
              x1={xScale(moment(signdate).valueOf())}
              x2={xScale(moment(signdate).valueOf())}
              y1={padding.top - 8}
              y2={padding.top + chartH}
              stroke={COLORS.SIGN_DATE}
              strokeWidth={1.5}
            />
            <polygon
              points={`${xScale(moment(signdate).valueOf()) - 4},${padding.top - 8} ${xScale(moment(signdate).valueOf()) + 4},${padding.top - 8} ${xScale(moment(signdate).valueOf())},${padding.top - 2}`}
              fill={COLORS.SIGN_DATE}
            />
            <text
              x={xScale(moment(signdate).valueOf())}
              y={padding.top - 12}
              textAnchor="middle"
              fontSize="10"
              fill={COLORS.SIGN_DATE}
            >
              签订
            </text>
          </g>
        )}

        {/* 首次应收日 */}
        {firstDueDate && (
          <g>
            <line
              x1={xScale(moment(firstDueDate).valueOf())}
              x2={xScale(moment(firstDueDate).valueOf())}
              y1={padding.top - 8}
              y2={padding.top + chartH}
              stroke={COLORS.FIRST_DUE}
              strokeWidth={1}
            />
            <polygon
              points={`${xScale(moment(firstDueDate).valueOf()) - 4},${padding.top - 8} ${xScale(moment(firstDueDate).valueOf()) + 4},${padding.top - 8} ${xScale(moment(firstDueDate).valueOf())},${padding.top - 2}`}
              fill={COLORS.FIRST_DUE}
            />
            <text
              x={xScale(moment(firstDueDate).valueOf())}
              y={padding.top - 12}
              textAnchor="middle"
              fontSize="10"
              fill={COLORS.FIRST_DUE}
            >
              首次应收
            </text>
          </g>
        )}

        {/* 今天 - 红色虚线 */}
        <g>
          <line
            x1={xScale(today.valueOf())}
            x2={xScale(today.valueOf())}
            y1={padding.top}
            y2={padding.top + chartH}
            stroke={COLORS.TODAY}
            strokeWidth={1.5}
            strokeDasharray="4 2"
          />
          <text
            x={xScale(today.valueOf()) + 4}
            y={padding.top + 12}
            fontSize="10"
            fill={COLORS.TODAY}
          >
            今天
          </text>
        </g>

        {/* 图例 */}
        <g transform={`translate(${padding.left}, ${height - 16})`}>
          <LegendItem x={0} color={COLORS.PENDING} label="待收" />
          <LegendItem x={70} color={COLORS.EARLY} label="提前" />
          <LegendItem x={140} color={COLORS.NORMAL} label="正常" />
          <LegendItem x={210} color={COLORS.OVERDUE} label="逾期" />
        </g>
      </svg>
    </div>
  );
};

const LegendItem: React.FC<{ x: number; color: string; label: string }> = ({ x, color, label }) => (
  <g>
    <rect x={x} y={0} width={10} height={10} fill={color} />
    <text x={x + 14} y={9} fontSize="11" fill="#666">{label}</text>
  </g>
);

export default CollectionGantt;
```

> 注意：上述替换的 right-hand 端要保证 `</Card>`、`);`、`};` 三个闭合依然存在；由于 `GanttChart` 是新加的子组件，需要在 `index.tsx` 文件内 `CollectionGantt` 函数之后追加。

- [ ] **Step 5: 验证类型正确**

```bash
cd "E:/Workspaces/fzrbs_oa/mysite" && npx tsc --noEmit src/pages/finance/contract/collectionGantt/index.tsx
```

Expected: 无类型错误。如有缺少 `moment` 导入，保留原有 import 行。

- [ ] **Step 6: 提交**

```bash
cd "E:/Workspaces/fzrbs_oa" && git add mysite/src/pages/finance/contract/collectionGantt/index.tsx
git commit -m "feat(contract): render SVG gantt chart with plan/actual/milestones"
```

---

### Task 5: 集成到合同详情页

**Files:**
- Modify: `mysite/src/pages/finance/contract/view.tsx`

- [ ] **Step 1: 在 view.tsx 添加 import**

打开 `mysite/src/pages/finance/contract/view.tsx`，在文件顶部 import 区域增加：

```typescript
import CollectionGantt from './collectionGantt';
```

- [ ] **Step 2: 在 View 组件的 return 内插入 CollectionGantt**

定位到 View 组件渲染结构（`return (` 之后找到合适位置），在合同详情 `<Descriptions>` 之后插入：

```tsx
<Divider style={{ margin: '24px 0' }} />
<CollectionGantt contractId={id} compact />
```

> 如果文件里还没引入 `Divider`，需要把 `import { Badge, Descriptions, Modal, Tag } from 'antd';` 改为：
> ```typescript
> import { Badge, Descriptions, Divider, Modal, Tag } from 'antd';
> ```

- [ ] **Step 3: 验证类型正确**

```bash
cd "E:/Workspaces/fzrbs_oa/mysite" && npx tsc --noEmit src/pages/finance/contract/view.tsx
```

Expected: 无类型错误。

- [ ] **Step 4: 启动 dev server 视觉验证**

```bash
cd "E:/Workspaces/fzrbs_oa/mysite" && npm run start:dev
```

浏览器打开一份有 `paycondition` 和 `paycollection` 的合同详情页（合同 ID 通过 `pages/finance/contract/contractsTable.tsx` 列表点击进入），向下滚动到「收款进度」卡片，验证：
- 顶部 4 个汇总卡显示：合同总额 / 计划期次 / 实际回款 / 逾期期次
- SVG 图表中：浅色背景为计划条；阶梯折线为实际回款；绿/蓝/红/黄状态色可见
- 绿色三角=签订日；蓝色三角=首次应收；红色虚线=今天
- 节点 hover 显示 tooltip：「截至 YYYY-MM-DD 应累计回款 X%（金额元）」
- 实际回款点 hover 显示 tooltip：「YYYY-MM-DD 实收 金额元（累计 金额元 / 百分比%）」

- [ ] **Step 5: 提交**

```bash
cd "E:/Workspaces/fzrbs_oa" && git add mysite/src/pages/finance/contract/view.tsx
git commit -m "feat(contract): integrate CollectionGantt into contract detail view"
```

---

### Task 6: 收尾验证

- [ ] **Step 1: 全项目 TypeScript 检查**

```bash
cd "E:/Workspaces/fzrbs_oa/mysite" && npm run tsc
```

Expected: 无错误。

- [ ] **Step 2: 触发 Empty/降级场景**

在浏览器中打开一份**没有 `paycondition`** 或**没有 `paycollection`** 的合同详情页，确认：
- 缺计划时仅显示「暂无回款数据」/「暂无计划回款节点」
- 缺实际时实际回款阶梯线不渲染，但计划节点与里程碑仍正常

- [ ] **Step 3: 确认 git 状态干净**

```bash
cd "E:/Workspaces/fzrbs_oa" && git status
```

Expected: 工作区无未提交的修改（除可能的 .umi 自动生成文件）。

- [ ] **Step 4: 查看提交记录**

```bash
cd "E:/Workspaces/fzrbs_oa" && git log --oneline -8
```

Expected: 至少包含 5 条新 commit（types / service / compute / index×2 / view 集成）。

---

## Self-Review Notes

- **Spec coverage**：
  - § 3 数据来源（getcontract 复用）— Task 2 ✅
  - § 4.2.1 坐标轴 — Task 4 SVG 渲染 ✅
  - § 4.2.2 计划节点 + 状态着色 — `judgePlanStatus` + Task 4 渲染 ✅
  - § 4.2.3 实际回款 + 状态着色 — `decorateActualSeries` + Task 4 阶梯线 ✅
  - § 4.2.4 关键里程碑（签订/首次应收/今天）— Task 4 ✅
  - § 4.2.5 汇总统计 4 卡 — Task 4 步骤 1 ✅
  - § 4.3 状态优先级（空态）— Task 4 + Task 6 ✅
  - § 5 文件结构（types/service/compute/index）— Tasks 1-4 ✅
  - § 5.2 Props — Task 1 types.ts ✅
  - § 5.3 数据加工函数 — Task 3 ✅
  - § 5.4 SVG 渲染 — Task 4 ✅
  - § 6 调用方式 — Task 5 集成 ✅

- **Placeholder scan**：无 TBD/TODO/「类似的 N 步」。所有代码块完整。

- **Type consistency**：
  - `PlanStatus` / `ActualStatus` / `PlanPoint` / `ActualPoint` 在 types.ts 定义，compute.ts 和 index.tsx 复用同一份。
  - `ContractGanttData` 在 service.ts 定义，index.tsx 通过 import 复用。
  - `GanttChart` 子组件 props 与 `planSeries`/`actualSeries`/`signdate`/`today` 字段一致。
