import React, { useEffect, useMemo, useState } from 'react';
import { Card, Col, Row, Spin, Tooltip, Empty } from 'antd';
import moment from 'moment';
import { getContractGantt, ContractGanttData } from './service';
import {
  buildPlanSeries,
  buildActualSeries,
  decorateActualSeries,
  computeAxisRange,
  calculateOverdueCount,
} from './compute';
import { CollectionGanttProps, PlanPoint, ActualPoint, COLORS } from './types';

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
  planSeries: PlanPoint[];
  actualSeries: ActualPoint[];
  signdate: string;
  today: moment.Moment;
}> = ({ height, planSeries, actualSeries, signdate, today }) => {
  const width = 1000;
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
                ...actualSeries.map(
                  (a) =>
                    [xScale(moment(a.date).valueOf()), yScale(a.cumulativeRate)] as [number, number],
                ),
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

const LegendItem: React.FC<{ x: number; color: string; label: string }> = ({
  x,
  color,
  label,
}) => (
  <g>
    <rect x={x} y={0} width={10} height={10} fill={color} />
    <text x={x + 14} y={9} fontSize="11" fill="#666">
      {label}
    </text>
  </g>
);

export default CollectionGantt;
