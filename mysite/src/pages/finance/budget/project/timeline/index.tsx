import React, { useEffect, useState } from 'react';
import { Card, Tooltip } from 'antd';
import { getprotimeline } from './service';

interface TimelineStage {
  key: string;
  label: string;
  state: number;
  enterTime: string | null;
  endTime: string | null;
  durationDays: number | null;
  isCurrent: boolean;
  isFinished: boolean;
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
  endTime: string | null;
  isCurrent: boolean;
  isFinished: boolean;
}> = ({ label, enterTime, endTime, isCurrent, isFinished }) => {
  const isReached = enterTime !== null;

  return (
    <Tooltip title={isReached ? `进入: ${enterTime}` : '未到达此阶段'}>
      <Card
        size="small"
        style={{
          width: 140,
          textAlign: 'center',
          borderColor: isCurrent ? undefined : isReached ? '#d9d9d9' : '#d9d9d9',
          borderStyle: isReached ? 'solid' : 'dashed',
          backgroundColor: isCurrent ? '#1890ff' : isReached ? '#fff' : '#f5f5f5',
          color: isCurrent ? '#fff' : isReached ? '#000' : '#bbb',
        }}
        bodyStyle={{ padding: 12 }}
      >
        <div style={{ fontWeight: 'bold', marginBottom: 8 }}>{label}</div>
        <div style={{ fontSize: 12, marginBottom: 2 }}>
          {isCurrent ? '进行中' : isFinished ? '已完成' : '—'}
        </div>
        <div style={{ fontSize: 11, color: isCurrent ? '#fff' : '#999', marginBottom: 2 }}>
          进入: {enterTime ? enterTime.slice(0, 10) : '—'}
        </div>
        <div style={{ fontSize: 11, color: isCurrent ? '#fff' : '#999' }}>
          结束: {endTime ? endTime.slice(0, 10) : '—'}
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
  const [timelineData, setTimelineData] = useState<TimelineStage[]>([]);

  useEffect(() => {
    if (!projectId) return;

    getprotimeline({ projectid: projectId }).then((res) => {
      if (res.errorMessage) {
        console.error(res.errorMessage);
        setLoading(false);
        return;
      }
      const stages: TimelineStage[] = res.data || [];
      setTimelineData(stages);
      setLoading(false);
    });
  }, [projectId]);

  if (loading) {
    return <div style={{ textAlign: 'center', padding: 20 }}>加载中...</div>;
  }

  return (
    <div style={{ display: 'flex', alignItems: 'center', overflowX: 'auto', padding: '16px 0' }}>
      {timelineData.map((stage, idx) => (
        <React.Fragment key={stage.key}>
          <TimelineCard {...stage} />
          {idx < timelineData.length - 1 && <Arrow />}
        </React.Fragment>
      ))}
    </div>
  );
};

export default ProjectTimeline;
