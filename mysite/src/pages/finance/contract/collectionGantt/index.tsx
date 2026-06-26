import React, { useEffect, useMemo, useState } from 'react';
import { Card, Col, Empty, Progress, Row, Spin } from 'antd';
import moment from 'moment';
import { getContractGantt, ContractGanttData } from './service';
import { buildPlanSeries, calculateOverdueCount } from './compute';
import { CollectionGanttProps, COLORS } from './types';

type DashboardStatus = 'green' | 'yellow' | 'red';

const fmtAmount = (n: number) =>
  n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const CollectionGantt: React.FC<CollectionGanttProps> = ({
  contractId,
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
      <Card size="small">
        <div style={{ textAlign: 'center', padding: 40 }}>
          <Spin /> 加载中...
        </div>
      </Card>
    );
  }
  if (error) {
    return (
      <Card size="small">
        <div style={{ color: COLORS.OVERDUE, padding: 20 }}>{error}</div>
      </Card>
    );
  }
  if (!data) return <Card size="small"><Empty description="暂无数据" /></Card>;

  const hasPlan = planSeries.length > 0;
  const hasActual = data.contract.paycollection > 0;
  if (!hasPlan && !hasActual) {
    return <Card size="small"><Empty description="暂无回款数据" /></Card>;
  }

  return (
    <Card
      title={<span style={{ fontSize: 14, fontWeight: 600 }}>合同收款进度</span>}
      size="small"
      bodyStyle={{ padding: '20px 24px' }}
      extra={
        <span style={{ fontSize: 12, display: 'inline-flex', alignItems: 'center' }}>
          <span
            style={{
              display: 'inline-block',
              width: 8,
              height: 8,
              borderRadius: '50%',
              background: statusColor,
              marginRight: 6,
            }}
          />
          <span style={{ color: statusColor, fontWeight: 600 }}>{statusText}</span>
        </span>
      }
    >
      <Row gutter={20} align="middle">
        <Col style={{ width: 220, flexShrink: 0 }}>
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <Progress
              type="dashboard"
              percent={Math.round(actualRate)}
              strokeColor={statusColor}
              trailColor="rgba(0, 0, 0, 0.06)"
              strokeWidth={8}
              style={{ width: 180 }}
            />
          </div>
        </Col>
        <Col style={{ flex: 1 }}>
          <div style={{ display: 'flex', gap: 32, alignItems: 'flex-start' }}>
            <div>
              <div style={{ fontSize: 12, color: '#999', marginBottom: 2 }}>已收</div>
              <div style={{ fontSize: 24, fontWeight: 600, color: COLORS.EARLY, lineHeight: 1.2 }}>
                ¥{fmtAmount(data.contract.paycollection)}
              </div>
            </div>
            <div>
              <div style={{ fontSize: 12, color: '#999', marginBottom: 2 }}>还欠</div>
              <div style={{ fontSize: 24, fontWeight: 600, color: '#595959', lineHeight: 1.2 }}>
                ¥{fmtAmount(remaining)}
              </div>
            </div>
            <div>
              <div style={{ fontSize: 12, color: '#999', marginBottom: 2 }}>总额</div>
              <div style={{ fontSize: 24, fontWeight: 600, color: '#262626', lineHeight: 1.2 }}>
                ¥{fmtAmount(data.contract.amount)}
              </div>
            </div>
          </div>
          {!compact && (
            <div style={{ marginTop: 16, fontSize: 13, color: '#595959' }}>
              <span style={{ marginRight: 20 }}>
                <span style={{ color: '#999' }}>提前 </span>
                <span style={{ color: earlyCount > 0 ? COLORS.EARLY : '#bfbfbf', fontWeight: 600 }}>
                  {earlyCount}
                </span>
                <span style={{ color: '#999' }}> 期</span>
              </span>
              <span style={{ marginRight: 20 }}>
                <span style={{ color: '#999' }}>逾期 </span>
                <span style={{ color: overdueCount > 0 ? COLORS.OVERDUE : '#bfbfbf', fontWeight: 600 }}>
                  {overdueCount}
                </span>
                <span style={{ color: '#999' }}> 期</span>
              </span>
              <span>
                <span style={{ color: '#999' }}>待收 </span>
                <span style={{ color: pendingCount > 0 ? COLORS.PENDING : '#bfbfbf', fontWeight: 600 }}>
                  {pendingCount}
                </span>
                <span style={{ color: '#999' }}> 期</span>
              </span>
            </div>
          )}
        </Col>
      </Row>
    </Card>
  );
};

export default CollectionGantt;
