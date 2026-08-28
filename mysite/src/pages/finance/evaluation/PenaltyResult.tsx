import React, { useEffect, useState } from 'react';
import {
  Card,
  Table,
  Button,
  Select,
  Row,
  Col,
  Modal,
  message,
  Space,
  Tag,
  Statistic,
} from 'antd';
import { ProColumns } from '@ant-design/pro-components';
import { ProTable } from '@ant-design/pro-components';
import { getPenaltyResult, exportPenaltyResult } from './service';
import { useModel } from 'umi';
import moment from 'moment';

const { Option } = Select;

/**
 * 扣款结果页面
 */
const PenaltyResult: React.FC = () => {
  const [loading, setLoading] = useState(false);
  const [printVisible, setPrintVisible] = useState(false);
  const [printData, setPrintData] = useState<any[]>([]);
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;

  const currentYear = new Date().getFullYear();
  const currentQuarter = Math.ceil((new Date().getMonth() + 1) / 3);

  const [selectedYear, setSelectedYear] = useState(currentYear);
  const [selectedQuarter, setSelectedQuarter] = useState(currentQuarter);
  const [selectedDept, setSelectedDept] = useState<number | undefined>(undefined);

  const [dataSource, setDataSource] = useState<any[]>([]);

  const loadData = async () => {
    setLoading(true);
    try {
      const res: any = await getPenaltyResult({
        year: selectedYear,
        quarter: selectedQuarter,
        dept_id: selectedDept,
      });
      if (!res.message) {
        setDataSource(res.data || []);
      } else {
        message.error(res.message || '加载失败');
      }
    } catch (e) {
      message.error('加载失败');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadData();
  }, [selectedYear, selectedQuarter, selectedDept]);

  const handleExport = () => {
    exportPenaltyResult({ year: selectedYear, quarter: selectedQuarter });
  };

  const handlePrint = (records: any[]) => {
    setPrintData(records);
    setPrintVisible(true);
  };

  // 按部门分组统计
  const deptStats = dataSource.reduce((acc, item) => {
    const key = `${item.dept_id}-${item.dept_name}`;
    if (!acc[key]) {
      acc[key] = {
        dept_id: item.dept_id,
        dept_name: item.dept_name,
        count: 0,
        total: 0,
        personalCount: 0,
        personalTotal: 0,
      };
    }
    acc[key].count++;
    acc[key].total += parseFloat(item.deduct_amount || 0);
    if (item.is_personal) {
      acc[key].personalCount++;
      acc[key].personalTotal += parseFloat(item.deduct_amount || 0);
    }
    return acc;
  }, {} as Record<string, any>);

  const columns: ProColumns[] = [
    {
      title: '部门',
      dataIndex: 'dept_name',
      key: 'dept_name',
      width: 120,
    },
    {
      title: '员工姓名',
      dataIndex: 'user_name',
      key: 'user_name',
      width: 100,
    },
    {
      title: '扣罚金额(元)',
      dataIndex: 'deduct_amount',
      key: 'deduct_amount',
      width: 120,
      render: (val: any) => val?.toFixed(2) || '-',
    },
    {
      title: '追责类型',
      dataIndex: 'is_personal',
      key: 'is_personal',
      width: 100,
      render: (val: any) => (val ? <Tag color="red">个人追责</Tag> : <Tag>集体分摊</Tag>),
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <Card
        title="扣款结果"
        extra={
          <Space>
            <Select value={selectedYear} onChange={setSelectedYear} style={{ width: 100 }}>
              {Array.from({ length: 5 }, (_, i) => currentYear - i).map((y) => (
                <Option key={y} value={y}>
                  {y}年
                </Option>
              ))}
            </Select>
            <Select value={selectedQuarter} onChange={setSelectedQuarter} style={{ width: 80 }}>
              {[1, 2, 3, 4].map((q) => (
                <Option key={q} value={q}>
                  Q{q}
                </Option>
              ))}
            </Select>
            <Button type="primary" onClick={handleExport}>
              导出Excel
            </Button>
          </Space>
        }
      >
        <Row gutter={16} style={{ marginBottom: 16 }}>
          {Object.values(deptStats).map((stat: any) => (
            <Col key={stat.dept_id} span={6}>
              <Card size="small">
                <Statistic
                  title={stat.dept_name}
                  value={stat.total.toFixed(2)}
                  suffix="元"
                  valueStyle={{ color: '#1890ff' }}
                />
                <div style={{ marginTop: 8, fontSize: 12, color: '#666' }}>
                  个人追责: {stat.personalCount}人 / {stat.personalTotal.toFixed(2)}元
                </div>
              </Card>
            </Col>
          ))}
        </Row>

        <ProTable
          search={false}
          rowKey="id"
          loading={loading}
          dataSource={dataSource}
          columns={columns}
          pagination={{ pageSize: 20 }}
          scroll={{ x: 600 }}
          toolBarRender={() => [
            <Button key="print" onClick={() => handlePrint(dataSource)}>
              打印
            </Button>,
          ]}
        />
      </Card>

      {/* 打印预览弹窗 */}
      <Modal
        title="打印预览"
        open={printVisible}
        onOk={() => setPrintVisible(false)}
        onCancel={() => setPrintVisible(false)}
        width={700}
        footer={null}
      >
        <div id="print-area" style={{ padding: 20 }}>
          <h2 style={{ textAlign: 'center', marginBottom: 20 }}>
            {selectedYear}年第{selectedQuarter}季度扣款明细表
          </h2>

          <table style={{ width: '100%', borderCollapse: 'collapse', marginBottom: 20 }}>
            <thead>
              <tr style={{ background: '#f0f0f0' }}>
                <th style={tableThStyle}>部门</th>
                <th style={tableThStyle}>姓名</th>
                <th style={tableThStyle}>扣罚金额</th>
                <th style={tableThStyle}>类型</th>
              </tr>
            </thead>
            <tbody>
              {printData.map((item, idx) => (
                <tr key={idx}>
                  <td style={tableTdStyle}>{item.dept_name}</td>
                  <td style={tableTdStyle}>{item.user_name}</td>
                  <td style={tableTdStyle}>{parseFloat(item.deduct_amount || 0).toFixed(2)}</td>
                  <td style={tableTdStyle}>{item.is_personal ? '个人追责' : '集体分摊'}</td>
                </tr>
              ))}
            </tbody>
          </table>

          <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: 40 }}>
            <span>打印人: {currentUser?.name || '-'}</span>
            <span>打印时间: {moment().format('YYYY年MM月DD日')}</span>
          </div>
        </div>
      </Modal>
    </div>
  );
};

const tableThStyle: React.CSSProperties = {
  border: '1px solid #000',
  padding: '8px',
  textAlign: 'center',
};

const tableTdStyle: React.CSSProperties = {
  border: '1px solid #000',
  padding: '8px',
  textAlign: 'center',
};

export default PenaltyResult;