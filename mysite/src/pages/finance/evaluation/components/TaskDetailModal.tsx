import React, { useState } from 'react';
import { Modal, Descriptions, Table, Tabs, Tag, message } from 'antd';
import { getTaskDetail } from '../service';

interface TaskDetailModalProps {
  visible: boolean;
  taskId: number | null;
  onClose: () => void;
}

const TaskDetailModal: React.FC<TaskDetailModalProps> = ({ visible, taskId, onClose }) => {
  const [taskDetail, setTaskDetail] = useState<any>(null);
  const [loading, setLoading] = useState(false);

  // 部门评分表格列
  const deptScoreColumns: any[] = [
    {
      title: '部门',
      dataIndex: 'dept_name',
      key: 'dept_name',
    },
    {
      title: '评分',
      dataIndex: 'score',
      key: 'score',
      render: (val: number) => (val ? `${val}分` : '-'),
    },
    {
      title: '意见',
      dataIndex: 'opinion',
      key: 'opinion',
    },
    {
      title: '状态',
      dataIndex: 'status',
      key: 'status',
      render: (val: number) => {
        if (val === 0) return <Tag>未评分</Tag>;
        if (val === 1) return <Tag color="green">已评分</Tag>;
        return '-';
      },
    },
  ];

  // 加载任务详情
  React.useEffect(() => {
    if (visible && taskId) {
      loadTaskDetail(taskId);
    }
  }, [visible, taskId]);

  const loadTaskDetail = async (id: number) => {
    setLoading(true);
    try {
      const res: any = await getTaskDetail({ task_id: id });
      if (res.message) {
        message.error(res.message);
      } else {
        setTaskDetail(res.data);
      }
    } catch (e) {
      message.error('加载失败');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Modal
      title={taskDetail ? `${taskDetail.year}年第${taskDetail.quarter}季度服务对象评价` : '任务详情'}
      visible={visible}
      onCancel={onClose}
      footer={null}
      width={700}
    >
      {taskDetail && (
        <div>
          <Descriptions column={2} bordered size="small" style={{ marginBottom: 16 }}>
            <Descriptions.Item label="年份">{taskDetail.year}</Descriptions.Item>
            <Descriptions.Item label="季度">Q{taskDetail.quarter}</Descriptions.Item>
            <Descriptions.Item label="开始日期">{taskDetail.start_date}</Descriptions.Item>
            <Descriptions.Item label="截止日期">{taskDetail.end_date}</Descriptions.Item>
            <Descriptions.Item label="状态">
              {taskDetail.status === 0 ? (
                <Tag>未评分</Tag>
              ) : taskDetail.status === 1 ? (
                <Tag color="green">已评分</Tag>
              ) : taskDetail.status === 2 ? (
                <Tag color="orange">超时默认95</Tag>
              ) : (
                '-'
              )}
            </Descriptions.Item>
            <Descriptions.Item label="提交时间">{taskDetail.submitted_at || '-'}</Descriptions.Item>
          </Descriptions>

          <Tabs defaultActiveKey="1">
            <Tabs.TabPane tab="部门评分" key="1">
              <Table
                dataSource={taskDetail.departments || []}
                columns={deptScoreColumns}
                rowKey="dept_id"
                pagination={false}
                size="small"
              />
            </Tabs.TabPane>
            <Tabs.TabPane tab="个人评分" key="2">
              {taskDetail.departments?.map((dept: any) => (
                <div key={dept.dept_id} style={{ marginBottom: 16 }}>
                  <h4>{dept.dept_name}</h4>
                  {dept.personal_scores && dept.personal_scores.length > 0 ? (
                    <Table
                      dataSource={dept.personal_scores}
                      columns={[
                        { title: '姓名', dataIndex: 'target_user_name', key: 'target_user_name' },
                        { title: '评分', dataIndex: 'score', key: 'score', render: (v: number) => `${v}分` },
                        { title: '意见', dataIndex: 'opinion', key: 'opinion' },
                      ]}
                      rowKey="id"
                      pagination={false}
                      size="small"
                    />
                  ) : (
                    <span style={{ color: '#999' }}>无个人评分</span>
                  )}
                </div>
              ))}
            </Tabs.TabPane>
          </Tabs>
        </div>
      )}
    </Modal>
  );
};

export default TaskDetailModal;