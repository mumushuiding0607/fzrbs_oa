# 收款进度仪表盘 — 实施计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 将现有 `CollectionGantt`（二维甘特图）重写为 `CollectionDashboard`（仪表盘），用 AntD `Progress` 环 + 进度条 + 状态灯实现"小学生都能看懂"的回款进度可视化。

**Architecture:** 复用 v1 已有的 `types.ts` / `service.ts` / `compute.ts`（数据加工逻辑零改动），完全重写 `index.tsx`：移除所有 SVG，改用 AntD `Progress.type="dashboard"` + `Progress.type="line"` + `Statistic` + `Card` 组合。导出名 `CollectionGantt` 保持不变以兼容现有 `view.tsx` 调用。

**Tech Stack:** React 17 + TypeScript + Ant Design 5 + UmiJS 3.5

## Global Constraints

- 唯一被修改的文件：`mysite/src/pages/finance/contract/collectionGantt/index.tsx`
- 不修改 `types.ts` / `service.ts` / `compute.ts`
- 不修改 `view.tsx`（`CollectionGantt` 导出名 + Props 不变）
- 不引入新的 npm 依赖；用 AntD 现有组件
- 颜色与设计文档一致：`#52c41a`（绿/提前/正常）、`#faad14`（黄/关注）、`#ff4d4f`（红/逾期）、`#1890ff`（蓝/计划/待收）
- 状态判定优先级：overdueCount > 0 → 红；actualRate < planRate → 黄；其他 → 绿
- 金额渲染用 `parseFloat(x).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })`
- 百分比统一用 `Math.round()` 取整后传给 AntD `Progress`（避免小数精度问题）

---

## File Structure

仅修改 1 个文件：

| 文件 | 改动 |
|------|------|
| `mysite/src/pages/finance/contract/collectionGantt/index.tsx` | 完全重写为仪表盘组件；移除原 `GanttChart` 子组件和所有 SVG |

无任何其他文件改动。

---

### Task 1: 重写 index.tsx 为仪表盘

**Files:**
- Modify: `mysite/src/pages/finance/contract/collectionGantt/index.tsx`（完全重写）

- [ ] **Step 1: 用 Write 工具完全重写 index.tsx**

把 `mysite/src/pages/finance/contract/collectionGantt/index.tsx` 完全替换为以下内容：

```typescript
import React, { useEffect, useMemo, useState } from 'react';
import { Card, Col, Empty, Progress, Row, Spin, Statistic } from 'antd';
import moment from 'moment';
import { getContractGantt, ContractGanttData } from './service';
import {
  buildPlanSeries,
  buildActualSeries,
  decorateActualSeries,
  calculateOverdueCount,
} from './compute';
import { CollectionGanttProps, COLORS } from './types';

const fmtAmount = (n: number) =>
  n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

type DashboardStatus = 'green' | 'yellow' | 'red';

const CollectionGantt: React.FC<CollectionGanttProps> = ({
  contractId,
  height = 360,
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

  const actualSeries = useMemo(
    () =>
      data
        ? decorateActualSeries(
            buildActualSeries(data.paycollections),
            data.contract.amount,
            planSeries,
          )
        : [],
    [data, planSeries],
  );

  const overdueCount = useMemo(() => calculateOverdueCount(planSeries), [planSeries]);
  const earlyCount = useMemo(
    () => planSeries.filter((p) => p.status === 'early').length,
    [planSeries],
  );
  const pendingCount = useMemo(
    () => planSeries.filter((p) => p.status === 'pending').length,
    [planSeries],
  );

  const planRate = planSeries.length > 0 ? planSeries[planSeries.length - 1].rate : 0;
  const actualRate =
    data && data.contract.amount > 0
      ? (data.contract.paycollection / data.contract.amount) * 100
      : 0;

  const status: DashboardStatus =
    overdueCount > 0 ? 'red' : actualRate < planRate ? 'yellow' : 'green';
  const statusColor =
    status === 'red' ? COLORS.OVERDUE : status === 'yellow' ? COLORS.NORMAL : COLORS.EARLY;
  const statusText = status === 'red' ? '逾期' : status === 'yellow' ? '关注' : '正常';
  const remaining = data
    ? Math.max(data.contract.amount - data.contract.paycollection, 0)
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
      title="合同收款进度"
      size="small"
      extra={
        <span style={{ fontSize: 13 }}>
          <span
            style={{
              display: 'inline-block',
              width: 10,
              height: 10,
              borderRadius: '50%',
              background: statusColor,
              marginRight: 6,
              verticalAlign: 'middle',
            }}
          />
          {statusText}
        </span>
      }
    >
      <Row gutter={24} align="middle">
        <Col span={10} style={{ textAlign: 'center' }}>
          <Progress
            type="dashboard"
            percent={Math.round(actualRate)}
            strokeColor={statusColor}
            trailColor="rgba(0, 0, 0, 0.06)"
            strokeWidth={10}
            style={{ height }}
          />
        </Col>
        <Col span={14}>
          <Row gutter={16}>
            <Col span={12}>
              <Statistic
                title="已收"
                value={data.contract.paycollection}
                prefix="¥"
                precision={2}
                valueStyle={{ color: COLORS.EARLY, fontSize: 20, fontWeight: 'bold' }}
              />
            </Col>
            <Col span={12}>
              <Statistic
                title="还欠"
                value={remaining}
                prefix="¥"
                precision={2}
                valueStyle={{ color: '#666', fontSize: 20, fontWeight: 'bold' }}
              />
            </Col>
          </Row>
          <div style={{ marginTop: 24 }}>
            {hasPlan && (
              <div style={{ marginBottom: 16 }}>
                <span style={{ marginRight: 8, color: '#666', fontSize: 13 }}>计划进度</span>
                <Progress
                  percent={Math.round(planRate)}
                  strokeColor={COLORS.PENDING}
                  format={(p) => `${p}%（节点 ${planSeries.length}）`}
                />
              </div>
            )}
            <div>
              <span style={{ marginRight: 8, color: '#666', fontSize: 13 }}>实际进度</span>
              <Progress
                percent={Math.round(actualRate)}
                strokeColor={statusColor}
                format={(p) => `${p}%`}
              />
            </div>
          </div>
        </Col>
      </Row>

      {!compact && (
        <Row gutter={16} style={{ marginTop: 24 }}>
          <Col span={8}>
            <Card size="small" style={{ textAlign: 'center' }}>
              <div style={{ fontSize: 28, lineHeight: 1.2 }}>😊</div>
              <div
                style={{
                  color: earlyCount > 0 ? COLORS.EARLY : '#999',
                  fontWeight: 'bold',
                  marginTop: 4,
                }}
              >
                提前 {earlyCount} 期
              </div>
            </Card>
          </Col>
          <Col span={8}>
            <Card size="small" style={{ textAlign: 'center' }}>
              <div style={{ fontSize: 28, lineHeight: 1.2 }}>⚠️</div>
              <div
                style={{
                  color: overdueCount > 0 ? COLORS.OVERDUE : '#999',
                  fontWeight: 'bold',
                  marginTop: 4,
                }}
              >
                逾期 {overdueCount} 期
              </div>
            </Card>
          </Col>
          <Col span={8}>
            <Card size="small" style={{ textAlign: 'center' }}>
              <div style={{ fontSize: 28, lineHeight: 1.2 }}>○</div>
              <div
                style={{
                  color: pendingCount > 0 ? COLORS.PENDING : '#999',
                  fontWeight: 'bold',
                  marginTop: 4,
                }}
              >
                待收 {pendingCount} 期
              </div>
            </Card>
          </Col>
        </Row>
      )}
    </Card>
  );
};

export default CollectionGantt;
```

- [ ] **Step 2: TypeScript 验证**

```bash
cd "E:/Workspaces/fzrbs_oa/mysite" && npm run tsc 2>&1 | grep -E "collectionGantt" | head -20
```

Expected: 无错误。

- [ ] **Step 3: 提交**

```bash
cd "E:/Workspaces/fzrbs_oa" && git add mysite/src/pages/finance/contract/collectionGantt/index.tsx
git commit -m "feat(contract): rewrite CollectionGantt as intuitive dashboard

Replace 2D gantt chart with dashboard-style visualization:
- Central Progress.dashboard ring with big percentage
- Plan/actual line bars side-by-side
- Status light (red/yellow/green) in card extra
- Three period stat cards: early/overdue/pending

Reuses existing types/service/compute; no backend changes.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

### Task 2: 视觉验证

- [ ] **Step 1: 启动 dev server**

```bash
cd "E:/Workspaces/fzrbs_oa/mysite" && npm run start:dev
```

- [ ] **Step 2: 浏览器检查**

打开任一**有 `paycondition` 和 `paycollection` 数据**的合同详情页，验证仪表盘卡片：

- 标题「合同收款进度」+ 右上角状态灯（绿/黄/红圆点 + 文字）
- 左侧 dashboard 进度环：显示整数百分比，颜色与状态灯一致
- 右侧两栏：「已收 ¥xx」「还欠 ¥xx」（金额保留 2 位小数）
- 「计划进度」蓝色进度条 + 「（节点 N）」后缀
- 「实际进度」彩色进度条（与状态灯颜色一致）
- 底部三个小卡片：「😊 提前 N 期」「⚠️ 逾期 N 期」「○ 待收 N 期」
- 当 `overdueCount > 0` 时，整体变为红灯，逾期数字为红色
- 当 `actualRate < planRate` 且无逾期时，整体变为黄灯
- 当 `actualRate >= planRate` 且无逾期时，整体为绿灯

- [ ] **Step 3: 触发降级场景**

打开**无 `paycondition` 或无 `paycollection`** 的合同详情页，确认：
- 缺计划时：隐藏「计划进度」条
- 缺实际时：进度环显示 0%，状态灯按剩余计划节点数显示
- 两者皆无：显示「暂无回款数据」空态

- [ ] **Step 4: 确认 git 状态干净**

```bash
cd "E:/Workspaces/fzrbs_oa" && git status --short | grep -v -E "^\?\?" | head -10
```

Expected: 无未提交修改（除 .umi 自动生成）。

---

## Self-Review Notes

- **Spec coverage**：
  - § 3 数据来源（复用 v1）— 零改动，Task 1 复用 ✅
  - § 4.1 整体布局 — Task 1 ✅
  - § 4.2.1 状态灯 — Task 1 `extra` 节点 ✅
  - § 4.2.2 进度环（180px 直径 → 默认 height 360）— Task 1 `<Progress type="dashboard">` ✅
  - § 4.2.3 已收/还欠 — Task 1 `<Statistic>` × 2 ✅
  - § 4.2.4 计划/实际对比条 — Task 1 `<Progress type="line">` × 2 ✅
  - § 4.2.5 三个期次状态卡 — Task 1 底部 `<Row>` + `<Col span={8}>` ✅
  - § 4.2.6 紧凑模式（compact）— Task 1 `!compact && (...)` ✅
  - § 4.3 降级处理 — Task 1 + Task 2 步骤 3 ✅
  - § 5.1 文件结构（仅改 index.tsx）— Task 1 ✅
  - § 5.2 Props（不变）— Task 1 ✅
  - § 5.3 数据加工 — Task 1 useMemo ✅
  - § 5.4 渲染方案（AntD 组件）— Task 1 ✅
  - § 6 调用方式（不变）— Task 1 导出名保留 ✅

- **Placeholder scan**：无 TBD/TODO；所有代码块完整。

- **Type consistency**：
  - `DashboardStatus` 在 index.tsx 内部定义（局部类型），仅用于本文件。
  - 复用 `CollectionGanttProps`、`COLORS`、`PlanStatus`（'early' | 'overdue' | 'pending'）— 与 types.ts/compute.ts 完全一致。
  - `getContractGantt`、`ContractGanttData` 来自 service.ts，无改动。
  - `buildPlanSeries` / `buildActualSeries` / `decorateActualSeries` / `calculateOverdueCount` 来自 compute.ts，签名一致。
