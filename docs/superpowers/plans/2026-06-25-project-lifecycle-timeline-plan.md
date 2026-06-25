# 项目全生命周期看板 — 时间轴实现计划

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 在 `pages/finance/budget/project/timeline/` 目录下创建独立组件，通过横向时间轴展示项目从立项到归档的全生命周期各阶段状态和耗时。

**Architecture:** 组件接收 `projectId` 作为 props，调用已有的 `/api/budget/getprohistory` 和 `/api/budget/getprojectbyid` 接口获取数据，计算各阶段进入时间和耗时，渲染横向时间轴 UI。

**Tech Stack:** React 17 + Ant Design Pro 5 + TypeScript + umi request

---

## Global Constraints

- 使用 `umi` 的 `request` 方法调用 API
- TypeScript 语法，遵循现有代码风格
- 复用 `pages/finance/budget/project/service.ts` 中的 API 方法
- 使用 Ant Design 组件库（Card、Timeline 等）

---

## 文件结构

```
pages/finance/budget/project/timeline/
├── index.tsx    # 组件入口，接收 projectId，渲染横向时间轴
└── service.ts   # 从父级 service.ts 重新导出需要的 API 方法
```

**接口说明：**
- `timeline/service.ts` 重新导出 `getprohistory` 和 `getprojectbyid`
- 父级已有 `service.ts` 中的 `getprohistory` 签名：`getprohistory(params: { projectid: any, current?: number, pageSize?: number })`
- 父级已有 `service.ts` 中的 `getprojectbyid` 签名：`getprojectbyid(params: { id: any })`

---

## Task 1: 创建 timeline/service.ts

**Files:**
- Create: `E:\Workspaces\fzrbs_oa\mysite\src\pages\finance\budget\project\timeline\service.ts`

**Interfaces:**
- Produces: 重新导出 `getprohistory` 和 `getprojectbyid`

- [ ] **Step 1: 创建 service.ts 文件**

```typescript
// @ts-ignore
/* eslint-disable */
import { request } from 'umi';

// 重新导出父级 service 中的方法供 timeline 组件使用
export { getprohistory, getprojectbyid } from '../../service';
```

- [ ] **Step 2: 提交**

```bash
git add src/pages/finance/budget/project/timeline/service.ts
git commit -m "feat(budget): add timeline service layer"
```

---

## Task 2: 创建 timeline/index.tsx 主组件

**Files:**
- Create: `E:\Workspaces\fzrbs_oa\mysite\src\pages\finance\budget\project\timeline\index.tsx`

**Interfaces:**
- Consumes: `getprohistory` 和 `getprojectbyid` from `./service`
- Props: `{ projectId: number | string }`
- Produces: 横向时间轴 React 组件

**阶段映射常量：**
```typescript
const STAGE_MAP = [
  { key: 'start',    label: '立项', state: 1 },
  { key: 'budget',  label: '执行', state: 2 },
  { key: 'final',   label: '验收', state: 3 },
  { key: 'submit',   label: '决算', state: [4, 5] },
  { key: 'archive',  label: '归档', state: 'locked' },
];
```

**数据类型：**
```typescript
interface HistoryItem {
  state: number;
  statename: string;
  createtime: string;
  data: string; // JSON string
}

interface TimelineStage {
  key: string;
  label: string;
  enterTime: string | null;  // 进入该阶段的时间
  durationDays: number | null; // 耗时天数，null 表示未到达
  isCurrent: boolean;        // 是否当前阶段
  isFinished: boolean;        // 是否已完成（有过该状态）
  isArchived: boolean;        // 是否已归档
}

interface ProjectInfo {
  state: number;
  locked: number;
  starttime?: string;
  lockdate?: string;
  [key: string]: any;
}
```

**渲染逻辑：**
- 调用 `getprojectbyid({ id: projectId })` 获取项目当前状态和 locked 字段
- 调用 `getprohistory({ projectid: projectId })` 获取历史记录列表
- 解析 history 记录，按 state 分组找到每个阶段的进入时间（createtime）
- 耗时 = 下一阶段进入时间 - 当前阶段进入时间
- 当前阶段（state 最大的已通过记录）显示"进行中"
- locked=1 时显示归档节点

**UI 结构：**
```
<Flex horizontal gap="small">
  {STAGE_MAP.map((stage, idx) => (
    <React.Fragment key={stage.key}>
      <TimelineCard {...stage} data={timelineData[stage.key]} />
      {idx < STAGE_MAP.length - 1 && <Arrow />}
    </React.Fragment>
  ))}
</Flex>
```

**TimelineCard Props:**
```typescript
interface TimelineCardProps {
  label: string;           // 阶段名称
  enterTime: string | null;
  durationDays: number | null;
  isCurrent: boolean;
  isFinished: boolean;
  isArchived: boolean;
}
```

**视觉规则：**
- 当前阶段 Card 高亮（主色调背景）
- 未到达阶段 Card 灰色虚线边框
- 已完成阶段 Card 正常显示
- 归档阶段独立判断（locked=1）

- [ ] **Step 1: 创建 index.tsx 文件**

```tsx
import React, { useEffect, useState } from 'react';
import { Card, Tooltip } from 'antd';
import { getprohistory, getprojectbyid } from './service';

const STAGES = [
  { key: 'start',  label: '立项', state: 1 },
  { key: 'budget', label: '执行', state: 2 },
  { key: 'final',  label: '验收', state: 3 },
  { key: 'submit',  label: '决算', state: [4, 5] },
  { key: 'archive', label: '归档', state: 'locked' },
];

interface TimelineStage {
  key: string;
  label: string;
  enterTime: string | null;
  durationDays: number | null;
  isCurrent: boolean;
  isFinished: boolean;
  isArchived: boolean;
}

interface HistoryItem {
  state: number;
  createtime: string;
  statename: string;
  data: string;
}

interface ProjectInfo {
  state: number;
  locked: number;
  lockdate?: string;
  [key: string]: any;
}

const TimelineCard: React.FC<{
  label: string;
  enterTime: string | null;
  durationDays: number | null;
  isCurrent: boolean;
  isFinished: boolean;
}> = ({ label, enterTime, durationDays, isCurrent, isFinished }) => {
  const isReached = enterTime !== null;

  return (
    <Tooltip title={isReached ? `进入时间: ${enterTime}` : '未到达此阶段'}>
      <Card
        size="small"
        style={{
          width: 120,
          textAlign: 'center',
          borderColor: isCurrent ? undefined : isReached ? '#d9d9d9' : '#d9d9d9',
          borderStyle: isReached ? 'solid' : 'dashed',
          backgroundColor: isCurrent ? '#1890ff' : isReached ? '#fff' : '#f5f5f5',
          color: isCurrent ? '#fff' : isReached ? '#000' : '#bbb',
        }}
        bodyStyle={{ padding: 12 }}
      >
        <div style={{ fontWeight: 'bold', marginBottom: 8 }}>{label}</div>
        <div style={{ fontSize: 12, marginBottom: 4 }}>
          {enterTime ? enterTime.slice(0, 10) : '—'}
        </div>
        <div style={{ fontSize: 12 }}>
          {isCurrent ? '进行中' : durationDays !== null ? `${durationDays}天` : '—'}
        </div>
      </Card>
    </Tooltip>
  );
};

const Arrow: React.FC = () => (
  <div style={{ display: 'flex', alignItems: 'center', color: '#bfbfbf', fontSize: 20, padding: '0 4px' }}>
    →
  </div>
);

const ProjectTimeline: React.FC<{ projectId: number | string }> = ({ projectId }) => {
  const [loading, setLoading] = useState(true);
  const [timelineData, setTimelineData] = useState<Record<string, TimelineStage>>({});
  const [projectInfo, setProjectInfo] = useState<ProjectInfo | null>(null);

  useEffect(() => {
    if (!projectId) return;

    Promise.all([
      getprojectbyid({ id: projectId }),
      getprohistory({ projectid: projectId }),
    ]).then(([projRes, histRes]) => {
      const project: ProjectInfo = projRes.data || {};
      const historyList: HistoryItem[] = histRes.data || [];
      setProjectInfo(project);

      // 按 state 分组，取每个 state 最早的一条记录作为进入时间
      const stateMap: Record<number, HistoryItem> = {};
      historyList.forEach((item) => {
        if (!stateMap[item.state]) {
          stateMap[item.state] = item;
        }
      });

      // 计算各阶段数据
      const data: Record<string, TimelineStage> = {};
      const sortedStates = Object.keys(stateMap).map(Number).sort((a, b) => a - b);
      const maxState = sortedStates[sortedStates.length - 1] || 0;
      const currentState = project.state;

      STAGES.forEach((stage, idx) => {
        let enterTime: string | null = null;
        let isFinished = false;
        let isCurrent = false;
        let isArchived = false;

        if (stage.key === 'archive') {
          isArchived = project.locked === 1;
          enterTime = isArchived ? (project.lockdate || null) : null;
          isCurrent = false;
          isFinished = isArchived;
        } else {
          const targetState = Array.isArray(stage.state) ? stage.state : [stage.state];
          const matchedItem = historyList.find((h) => targetState.includes(h.state));
          enterTime = matchedItem ? matchedItem.createtime : null;
          isFinished = enterTime !== null && project.state > (Array.isArray(stage.state) ? stage.state[0] : stage.state);
          isCurrent = currentState === (Array.isArray(stage.state) ? stage.state[0] : stage.state);
        }

        // 计算耗时
        let durationDays: number | null = null;
        if (enterTime) {
          const nextStage = STAGES[idx + 1];
          let nextEnterTime: Date | null = null;

          if (nextStage && nextStage.key === 'archive' && project.locked === 1) {
            nextEnterTime = project.lockdate ? new Date(project.lockdate) : null;
          } else if (nextStage) {
            const nextState = Array.isArray(nextStage.state) ? nextStage.state : [nextStage.state];
            const nextItem = historyList.find((h) => nextState.includes(h.state));
            nextEnterTime = nextItem ? new Date(nextItem.createtime) : null;
          } else {
            // 最后一个阶段，用当前时间
            nextEnterTime = new Date();
          }

          if (nextEnterTime) {
            const start = new Date(enterTime);
            durationDays = Math.ceil((nextEnterTime.getTime() - start.getTime()) / (1000 * 60 * 60 * 24));
          }

          if (isCurrent) {
            durationDays = null; // 进行中不显示天数
          }
        }

        data[stage.key] = { key: stage.key, label: stage.label, enterTime, durationDays, isCurrent, isFinished, isArchived };
      });

      setTimelineData(data);
      setLoading(false);
    });
  }, [projectId]);

  if (loading) {
    return <div style={{ textAlign: 'center', padding: 20 }}>加载中...</div>;
  }

  return (
    <div style={{ display: 'flex', alignItems: 'center', overflowX: 'auto', padding: '16px 0' }}>
      {STAGES.map((stage, idx) => {
        const stageData = timelineData[stage.key];
        if (!stageData) return null;
        return (
          <React.Fragment key={stage.key}>
            <TimelineCard {...stageData} />
            {idx < STAGES.length - 1 && <Arrow />}
          </React.Fragment>
        );
      })}
    </div>
  );
};

export default ProjectTimeline;
```

- [ ] **Step 2: 验证文件创建成功**

Run: `ls -la src/pages/finance/budget/project/timeline/`
Expected: `index.tsx` 和 `service.ts` 两个文件

- [ ] **Step 3: 提交**

```bash
git add src/pages/finance/budget/project/timeline/
git commit -m "feat(budget): add project lifecycle timeline component

- ProjectTimeline component with horizontal timeline view
- Shows stages: 立项→执行→验收→决算→归档
- Displays enter time and duration per stage
- Highlights current stage and shows '进行中' label

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## Task 3: 在项目详情页集成 Timeline 组件

**Files:**
- Modify: `E:\Workspaces\fzrbs_oa\mysite\src\pages\finance\budget\project\projectdetail.tsx`

**Interfaces:**
- Consumes: `ProjectTimeline` from `./timeline`
- Props: `{ id: any }` （已有，通过 props 传入 projectId）

- [ ] **Step 1: 修改 projectdetail.tsx**

在 Descriptions 组件上方添加 Timeline 组件：

```tsx
import React, { useEffect, useState } from 'react';
import { Descriptions } from 'antd';
import { getprojectbyid } from './service';
import ProjectTimeline from './timeline';  // 新增导入

const ProjectDetail: React.FC<{id?:any}> = ({id}) => {
  const [data, setData] = useState({})
  useEffect(()=>{
    getprojectbyid({id}).then(res=>{
      if (res.data) {
        setData(res.data)
      }
    })
  },[])
  return (<>
    {id && <ProjectTimeline projectId={id} />}  {/* 新增时间轴 */}
    <Descriptions bordered layout='horizontal'>
        <Descriptions.Item label="项目内容">{data.title}</Descriptions.Item>
        <Descriptions.Item label="立项主体">{data.entityname}</Descriptions.Item>
        <Descriptions.Item label="项目类别">{data.typename}</Descriptions.Item>
        <Descriptions.Item label="立项时间">{data.starttime}</Descriptions.Item>
        <Descriptions.Item label="项目编号">{data.serial}</Descriptions.Item>
        <Descriptions.Item label="绩效比例">{data.performanceratio}</Descriptions.Item>
        <Descriptions.Item label="项目负责人">{data.chargername}</Descriptions.Item>
        <Descriptions.Item label="执行状态">{data.execstatename}</Descriptions.Item>
        <Descriptions.Item label="报告内容">{data.content}</Descriptions.Item>
        <Descriptions.Item label="预算备注">{data.budgetnote}</Descriptions.Item>
        <Descriptions.Item label="决算备注">{data.finalnote}</Descriptions.Item>
    </Descriptions>
</>)
};

export default ProjectDetail;
```

- [ ] **Step 2: 提交**

```bash
git add src/pages/finance/budget/project/projectdetail.tsx
git commit -m "feat(budget): integrate ProjectTimeline in project detail page

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>"
```

---

## 自检清单

**Spec Coverage:**
- [x] 横向时间轴展示 - Task 2 index.tsx
- [x] 阶段名称 + 进入时间 + 耗时天数 - Task 2 TimelineCard
- [x] 进行中状态显示 - Task 2 TimelineCard
- [x] 单项目查看（传入 projectId）- Task 2 Props 定义
- [x] 独立组件文件 - Task 1, Task 2
- [x] 其他页面调用方式 - Task 3 集成示例

**Placeholder Scan:**
- 无 TBD/TODO
- 无模糊描述

**Type Consistency:**
- `getprohistory` 签名与父级 service.ts 一致
- `getprojectbyid` 签名与父级 service.ts 一致
- Props 类型 `projectId: number | string` 明确
