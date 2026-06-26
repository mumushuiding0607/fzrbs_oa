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
