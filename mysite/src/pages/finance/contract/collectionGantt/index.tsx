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
