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
