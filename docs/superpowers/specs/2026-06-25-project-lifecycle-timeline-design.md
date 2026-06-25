# 项目全生命周期看板 — 时间轴设计

## 1. 概述

在 `finance/budget/project/` 目录下新建 `timeline/` 独立组件，通过传入 `projectId` 调用，以横向时间轴形式展示项目从立项到归档的全生命周期各阶段状态和耗时。

## 2. 阶段映射

现有系统状态 → 用户可见阶段：

| 阶段 | 对应状态值 | 说明 |
|------|-----------|------|
| 立项 | state=1 | 发起立项审批 |
| 执行 | state=2 | 预算审批通过，开始执行 |
| 验收 | state=3 | 决算提交，进入验收 |
| 决算 | state=4 或 state=5 | 待提交计量 / 已提交计量 |
| 归档 | locked=1 | lockpro() 锁档操作 |

## 3. 数据来源

- **接口**: `/api/budget/getprohistory?projectid={id}`
- **表**: `fzrbs_budget_history`
- 每条 history 记录对应一个审批节点，JSON data 字段中包含项目快照

## 4. 时间计算规则

| 阶段 | 进入时间 | 耗时计算 |
|------|---------|---------|
| 立项 | history 中 state=1 的 createtime | 下一个状态的 createtime - 当前 createtime |
| 执行 | history 中 state=2 的 createtime | 下一个状态的 createtime - 当前 createtime |
| 验收 | history 中 state=3 的 createtime | 下一个状态的 createtime - 当前 createtime |
| 决算 | history 中 state=4 或 5 的 createtime | 若 state=4/5 无后续记录，用当前时间计算 |
| 归档 | 项目表的 locked_time 或 lockdate | locked=1 时才显示归档节点 |

## 5. 组件设计

### 文件结构
```
pages/finance/budget/project/timeline/
├── index.tsx           # 组件入口，接收 projectId
└── service.ts          # 调用 getprohistory API
```

### Props
```typescript
interface TimelineProps {
  projectId: number | string;
}
```

### 视觉设计

**横向时间轴布局：**

```
┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐    ┌─────────┐
│  立项   │───→│  执行   │───→│  验收   │───→│  决算   │───→│  归档   │
│         │    │         │    │         │    │         │    │         │
│2026-01-05│   │2026-02-10│   │2026-03-15│   │2026-04-20│   │2026-05-30│
│  36天   │    │  33天   │    │  36天   │    │   进行中  │   │   —     │
└─────────┘    └─────────┘    └─────────┘    └─────────┘    └─────────┘
```

**每个阶段节点显示：**
- 阶段名称（顶部）
- 进入时间（中部）
- 耗时天数（底部）—— 若为当前阶段，显示"进行中"

**当前阶段的强调：**
- 使用主色调高亮
- 后续未到达阶段显示为灰色虚线

### UI 组件选型

- 使用 Ant Design Timeline 横向模式 或 自定义 Flex 横向排列
- 每个阶段节点为一个 Card，展示阶段名、时间、耗时
- 节点间用 Arrow 连接

## 6. 调用方式

```typescript
// 在其他页面引入
import ProjectTimeline from '@/pages/finance/budget/project/timeline';

// 使用
<ProjectTimeline projectId={8058} />
```

## 7. 实现要点

1. **数据获取**: 调用 `/api/budget/getprohistory?projectid={id}` 获取 history 记录
2. **阶段解析**: 根据 state 值映射到 5 个阶段节点
3. **时间计算**: 相邻两个 state 进入时间的差值即为该阶段耗时
4. **当前状态**: 项目表的 state 字段确定当前处于哪个阶段
5. **归档判断**: 若项目的 locked=1，取 lockdate 作为归档时间
6. **悬停提示**: 鼠标悬停显示更多信息（如审批人、审批备注）
