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
