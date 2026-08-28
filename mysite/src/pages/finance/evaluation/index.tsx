import React, { useRef, useState } from 'react';
import { Button, Card, Space, Tag, message, Modal, Drawer, Select } from 'antd';
import { PageContainer } from '@ant-design/pro-components';
import { ProColumns } from '@ant-design/pro-components';
import { ProTable } from '@ant-design/pro-components';
import { getTasks, deleteTask, generateTasks, autoScore, getPrintData, getOperationLogs, exportPrintDataXlsx } from './service';
import { ActionType } from '@ant-design/pro-table';
import TaskDetailModal from './components/TaskDetailModal';
import ConfigDrawer from './components/ConfigDrawer';
import SortEvaluator from './components/SortEvaluator';
import Rolelist from '../role/rolelist';
import WarningManagement from './warning';
import PenaltyInput from './PenaltyInput';
import PenaltyResult from './PenaltyResult';
import OperationLog from '../common/OperationLog';
import './print.css';

// 计算上一季度（自然季度：Q1=1-3月, Q2=4-6月, Q3=7-9月, Q4=10-12月）
const getLastQuarter = () => {
  const now = new Date();
  const currentMonth = now.getMonth(); // 0-11
  const currentYear = now.getFullYear();

  // 根据月份判断当前季度
  let currentQuarter;
  if (currentMonth <= 2) currentQuarter = 1; // 1-3月 -> Q1
  else if (currentMonth <= 5) currentQuarter = 2; // 4-6月 -> Q2
  else if (currentMonth <= 8) currentQuarter = 3; // 7-9月 -> Q3
  else currentQuarter = 4; // 10-12月 -> Q4

  let year = currentYear;
  let quarter;

  if (currentQuarter === 1) {
    // Q1的上一季度是去年Q4
    year -= 1;
    quarter = 4;
  } else {
    quarter = currentQuarter - 1;
  }

  // 计算季度起始和结束月份
  const startMonth = (quarter - 1) * 3; // 0, 3, 6, 9
  const endMonth = startMonth + 2; // 2, 5, 8, 11
  const startDate = `${year}-${String(startMonth + 1).padStart(2, '0')}-01`;
  const endDate = `${year}-${String(endMonth + 1).padStart(2, '0')}-${new Date(year, endMonth + 1, 0).getDate()}`;

  return { year, quarter, startDate, endDate };
};

const EvaluationIndex: React.FC = () => {
  const actionRef = useRef<ActionType>();

  const [viewModalVisible, setViewModalVisible] = useState(false);
  const [currentTaskId, setCurrentTaskId] = useState<number | null>(null);

  // 配置抽屉状态
  const [configDrawerVisible, setConfigDrawerVisible] = useState(false);
  const [configType, setConfigType] = useState('');

  // 评分对象设置抽屉状态
  const [evaluatorConfigVisible, setEvaluatorConfigVisible] = useState(false);
  // 考核对象排序弹窗状态
  const [sortEvaluatorVisible, setSortEvaluatorVisible] = useState(false);

  // 扣罚录入弹窗
  const [penaltyInputVisible, setPenaltyInputVisible] = useState(false);
  // 扣款报表弹窗
  const [penaltyResultVisible, setPenaltyResultVisible] = useState(false);
  // 预警管理弹窗
  const [warningVisible, setWarningVisible] = useState(false);

  // 日志弹窗
  const [logModalVisible, setLogModalVisible] = useState(false);

  // 打印预览弹窗状态
  const [printModalVisible, setPrintModalVisible] = useState(false);
  const [printYear, setPrintYear] = useState(() => getLastQuarter().year);
  const [printQuarter, setPrintQuarter] = useState(() => getLastQuarter().quarter);
  const [printData, setPrintData] = useState<any>(null);
  const printRef = useRef<HTMLDivElement>(null);

  // 删除任务
  const handleDelete = (record: any) => {
    Modal.confirm({
      title: '确认删除',
      content: `确定删除 ${record.year}年第${record.quarter}季度 的评分任务吗？`,
      onOk: async () => {
        try {
          const res: any = await deleteTask({ task_id: record.id });
          if (res.message) {
            message.error(res.message);
          } else {
            message.success('删除成功');
            actionRef.current?.reload();
          }
        } catch (e) {
          message.error('删除失败');
        }
      },
    });
  };

  // 查看任务
  const handleView = (record: any) => {
    setCurrentTaskId(record.id);
    setViewModalVisible(true);
  };

  // 打开配置抽屉
  const openConfig = (type: string) => {
    setConfigType(type);
    setConfigDrawerVisible(true);
  };

  // 生成任务
  const handleGenerateTasks = () => {
    const { year, quarter, startDate, endDate } = getLastQuarter();
    Modal.confirm({
      title: '确认生成任务',
      content: `确定生成 ${year}年第${quarter}季度 的评分任务吗？（${startDate} 至 ${endDate}）`,
      onOk: async () => {
        try {
          const res: any = await generateTasks({ year, quarter, start_date: startDate, end_date: endDate });
          if (res.message) {
            message.error(res.message);
          } else {
            message.success(`成功生成 ${res.created || 0} 个任务`);
            actionRef.current?.reload();
          }
        } catch (e) {
          message.error('生成失败');
        }
      },
    });
  };

  // 自动评分
  const handleAutoScore = () => {
    const { year, quarter } = getLastQuarter();
    Modal.confirm({
      title: '确认自动评分',
      content: `确定对 ${year}年第${quarter}季度 超时未评分的任务执行自动评分吗？（默认95分）`,
      onOk: async () => {
        try {
          const res: any = await autoScore({ year, quarter });
          if (res.message) {
            message.error(res.message);
          } else {
            message.success('自动评分完成');
            actionRef.current?.reload();
          }
        } catch (e) {
          message.error('自动评分失败');
        }
      },
    });
  };

  // 打开打印预览弹窗
  const handlePrintPreview = () => {
    const { year, quarter } = getLastQuarter();
    setPrintYear(year);
    setPrintQuarter(quarter);
    setPrintModalVisible(true);
    loadPrintData(year, quarter);
  };

  // 加载打印数据
  const loadPrintData = async (year?: number, quarter?: number) => {
    const selectedYear = year ?? printYear;
    const selectedQuarter = quarter ?? printQuarter;
    try {
      const res: any = await getPrintData({ year: selectedYear, quarter: selectedQuarter });
      if (res.message) {
        message.error(res.message);
        setPrintData(null);
      } else {
        setPrintData(res.data);
      }
    } catch (e) {
      message.error('加载打印数据失败');
      setPrintData(null);
    }
  };

  // 打印函数
  const triggerPrint = () => {
    const printContent = document.getElementById('printArea');
    if (!printContent) return;

    const iframe = document.createElement('iframe');
    iframe.style.position = 'absolute';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = 'none';
    document.body.appendChild(iframe);
    const iframeDoc = iframe.contentWindow?.document;

    iframeDoc?.open();
    iframeDoc?.write(`
      <html>
        <head>
          <title>社直行政后勤部门考评表</title>
          <style>
            @page {
              size: A4 landscape;
              margin: 10mm;
            }
            body {
              margin: 0;
              padding: 10px;
              font-family: Arial, sans-serif;
              font-size: 12px;
            }
            .print-title {
              text-align: center;
              font-size: 20px;
              font-weight: bold;
              margin-bottom: 15px;
            }
            .print-table {
              width: 100%;
              border-collapse: collapse;
            }
            .print-table th, .print-table td {
              border: 1px solid #333;
              padding: 8px 4px;
              text-align: center;
              font-size: 12px;
            }
            .print-table th {
              background-color: #f0f0f0;
              font-weight: bold;
            }
            .print-footer {
              display: flex;
              justify-content: space-between;
              margin-top: 20px;
              font-size: 12px;
            }
            .scorer-cell {
              width: 80px;
            }
            .dept-opinion-cell, .personal-opinion-cell {
              width: 150px;
              white-space: pre-wrap;
              text-align: left;
              vertical-align: top;
            }
            .header-cell-split {
              position: relative;
              width: 100%;
              height: 67px;
            }
            .header-cell-split .corner-text-top {
              position: absolute;
              top: -2px;
              right: 2px;
              z-index: 2;
              font-size: 11px;
            }
            .header-cell-split .corner-text-bottom {
              position: absolute;
              bottom: -2px;
              left: 2px;
              z-index: 2;
              font-size: 11px;
            }
            .header-cell-split svg {
              position: absolute;
              top: 0;
              left: 0;
              width: 100%;
              height: 100%;
              z-index: 1;
            }
            .header-cell-split svg line {
              stroke: #333;
              stroke-width: 1.5px;
            }
            .print-table th {
              height: 67px;
              vertical-align: top;
            }
            .print-table th:not(.scorer-cell) {
              text-align: center;
              vertical-align: middle;
            }
            .scorer-cell {
              width: 80px;
              height: 67px;
              text-align: center;
            }
          </style>
        </head>
        <body>
          ${printContent.innerHTML}
        </body>
      </html>
    `);
    iframeDoc?.close();

    iframe.onload = function() {
      iframe.contentWindow?.print();
      setTimeout(() => document.body.removeChild(iframe), 1000);
    };
  };

  const columns: any= [
    {
      title: '年份',
      dataIndex: 'year',
      key: 'year',
      width: 80,
    },
    {
      title: '季度',
      dataIndex: 'quarter',
      key: 'quarter',
      width: 80,
      render: (val: number) => `Q${val}`,
    },
    {
      title: '评分人',
      dataIndex: 'scorer_name',
      key: 'scorer_name',
      width: 100,
      render: (val: string) => val || '-',
    },
    {
      title: '状态',
      dataIndex: 'status',
      key: 'status',
      width: 100,
      render: (val: number) => {
        if (val === 0) return <Tag>未评分</Tag>;
        if (val === 1) return <Tag color="green">已评分</Tag>;
        if (val === 2) return <Tag color="orange">超时默认95</Tag>;
        return '-';
      },
    },
    {
      title: '开始日期',
      dataIndex: 'start_date',
      key: 'start_date',
      width: 120,
    },
    {
      title: '截止日期',
      dataIndex: 'end_date',
      key: 'end_date',
      width: 120,
    },
    {
      title: '提交时间',
      dataIndex: 'submitted_at',
      key: 'submitted_at',
      width: 180,
    },
    {
      title: '操作',
      key: 'action',
      width: 150,
      render: (_, record) => (
        <Space>
          <Button type="link" onClick={() => handleView(record)}>
            查看
          </Button>
          <Button type="link" danger onClick={() => handleDelete(record)}>
            删除
          </Button>
        </Space>
      ),
    },
  ];

  return (
    <PageContainer
      header={{
        breadcrumb: {},
      }}
      extra={
        <Space>
          <Button type="primary" onClick={handleGenerateTasks}>生成任务</Button>
          <Button onClick={handleAutoScore}>自动评分</Button>
          <Button type="default" onClick={handlePrintPreview}>导出或打印</Button>
          <Button onClick={() => setEvaluatorConfigVisible(true)}>评分对象设置</Button>
          <Button onClick={() => setSortEvaluatorVisible(true)}>评分对象排序</Button>
          <Button onClick={() => openConfig('被考评部门')}>被考评部门</Button>
          <Button onClick={() => openConfig('评分档次')}>评分档次</Button>
          {/* <Button onClick={() => setPenaltyInputVisible(true)}>扣罚录入</Button> */}
          {/* <Button onClick={() => setPenaltyResultVisible(true)}>扣款报表</Button> */}
          {/* <Button onClick={() => setWarningVisible(true)}>预警管理</Button> */}
          <Button onClick={() => setLogModalVisible(true)}>查看日志</Button>
        </Space>
      }
    >
      <Card title="评分任务列表">
        <ProTable
          actionRef={actionRef}
          search={false}
          rowKey="id"
          request={async (params) => {
            try {
              const res: any = await getTasks({});
              if (res.message) {
                message.error(res.message);
                return {
                  data: [],
                  total: 0,
                  success: false,
                };
              }
              return {
                data: res.data || [],
                total: res.total || res.data?.length || 0,
                success: true,
              };
            } catch (e) {
              message.error('加载失败');
              return {
                data: [],
                total: 0,
                success: false,
              };
            }
          }}
          columns={columns}
          pagination={{ pageSize: 20 }}
        />
      </Card>

      <TaskDetailModal
        visible={viewModalVisible}
        taskId={currentTaskId}
        onClose={() => setViewModalVisible(false)}
      />

      {configDrawerVisible && (
        <ConfigDrawer
          visible={configDrawerVisible}
          type={configType}
          onClose={() => setConfigDrawerVisible(false)}
        />
      )}

      <SortEvaluator
        visible={sortEvaluatorVisible}
        onClose={() => setSortEvaluatorVisible(false)}
      />

      <Drawer
        title="评分对象设置"
        placement="right"
        width={800}
        closable
        onClose={() => setEvaluatorConfigVisible(false)}
       visible={evaluatorConfigVisible}
      >
        <Rolelist type="部门考评人" />
      </Drawer>

      {/* 打印预览弹窗 */}
      <Modal
        title="打印预览"
        visible={printModalVisible}
        onCancel={() => setPrintModalVisible(false)}
        width={1200}
        footer={[
          <Select
            key="year"
            value={printYear}
            onChange={(val) => {
              setPrintYear(val);
              loadPrintData(val, printQuarter);
            }}
            style={{ width: 100, marginRight: 8 }}
          >
            {[0,1,2,3].map(i => new Date().getFullYear() - i).map(y => (
              <Select.Option key={y} value={y}>{y}年</Select.Option>
            ))}
          </Select>,
          <Select
            key="quarter"
            value={printQuarter}
            onChange={(val) => {
              setPrintQuarter(val);
              loadPrintData(printYear, val);
            }}
            style={{ width: 80, marginRight: 16 }}
          >
            <Select.Option value={1}>Q1</Select.Option>
            <Select.Option value={2}>Q2</Select.Option>
            <Select.Option value={3}>Q3</Select.Option>
            <Select.Option value={4}>Q4</Select.Option>
          </Select>,
          <Button key="refresh" onClick={loadPrintData}>刷新</Button>,
          <Button key="export" type="default" onClick={() => exportPrintDataXlsx({ year: printYear, quarter: printQuarter })}>导出xlsx</Button>,
          <Button key="print" type="primary" onClick={triggerPrint}>打印</Button>,
        ]}
      >
        <div ref={printRef} style={{ maxHeight: '70vh', overflow: 'auto' }}>
          {printData ? (
            <div id="printArea">
              <div className="print-title">
                {printData.year}年第{printData.quarter}季度社直行政后勤部门考评表
              </div>
              <table className="print-table">
                <thead>
                  <tr>
                    <th className="scorer-cell">
                      <div className="header-cell-split">
                        <span className="corner-text-top">考评部门</span>
                        <span className="corner-text-bottom">服务对象</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none">
                          <line x1="0" y1="0" x2="100" y2="100" />
                        </svg>
                      </div>
                    </th>
                    {printData.departments?.map((dept: any) => (
                      <th key={dept.id}>{dept.name}</th>
                    ))}
                    <th className="dept-opinion-cell">对社直部门的批评意见（列出具体事例、提出意见建议）</th>
                    <th className="personal-opinion-cell">对社直员工的批评意见（含对其个人的考评分数）</th>
                  </tr>
                </thead>
                <tbody>
                  {printData.scorers?.map((scorer: any) => (
                    <tr key={scorer.scorer_id}>
                      <td>{scorer.scorer_name}</td>
                      {printData.departments?.map((dept: any) => {
                        const scoreInfo = scorer.scores?.[dept.id];
                        return (
                          <td key={dept.id}>
                            {scoreInfo?.score != null ? `${scoreInfo.score}分` : '-'}
                          </td>
                        );
                      })}
                      <td className="dept-opinion-cell">{scorer.dept_opinion}</td>
                      <td className="personal-opinion-cell">{scorer.personal_opinion}</td>
                    </tr>
                  ))}
                  <tr>
                    <td style={{ fontWeight: 'bold' }}>平均分</td>
                    {printData.departments?.map((dept: any) => {
                      const avg = printData.avg_scores?.[dept.id];
                      return (
                        <td key={dept.id} style={{ fontWeight: 'bold' }}>
                          {avg != null ? `${avg}分` : '-'}
                        </td>
                      );
                    })}
                    <td></td>
                    <td></td>
                  </tr>
                </tbody>
              </table>
              <div className="print-footer">
                <span>打印时间：{new Date().toLocaleString()}</span>
              </div>
            </div>
          ) : (
            <div style={{ textAlign: 'center', padding: '40px' }}>
              暂无数据
            </div>
          )}
        </div>
      </Modal>

      {/* 扣罚录入弹窗 */}
      <Modal
        title="扣罚录入"
        visible={penaltyInputVisible}
        onCancel={() => setPenaltyInputVisible(false)}
        width={1200}
        footer={null}
        destroyOnClose
      >
        <PenaltyInput />
      </Modal>

      {/* 扣款报表弹窗 */}
      <Modal
        title="扣款报表"
        visible={penaltyResultVisible}
        onCancel={() => setPenaltyResultVisible(false)}
        width={1200}
        footer={null}
        destroyOnClose
      >
        <PenaltyResult />
      </Modal>

      {/* 预警管理弹窗 */}
      <Modal
        title="预警管理"
        visible={warningVisible}
        onCancel={() => setWarningVisible(false)}
        width={1200}
        footer={null}
        destroyOnClose
      >
        <WarningManagement />
      </Modal>

      {/* 操作日志弹窗 */}
      <Modal
        title="考评操作日志"
        visible={logModalVisible}
        onCancel={() => setLogModalVisible(false)}
        width={900}
        footer={null}
        destroyOnClose
      >
        <OperationLog api={getOperationLogs} bizId={0} />
      </Modal>
    </PageContainer>
  );
};

export default EvaluationIndex;