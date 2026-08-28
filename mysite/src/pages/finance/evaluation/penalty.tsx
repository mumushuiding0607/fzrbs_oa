import React, { useEffect, useState } from 'react';
import { Card, Table, Button, DatePicker, Row, Col, Modal, message } from 'antd';
import { ProColumns } from '@ant-design/pro-components';
import { ProTable } from '@ant-design/pro-components';
import { getPenalties, exportPenalties, calculateDeduction } from './service';
import { useModel } from 'umi';
import moment from 'moment';

const { RangePicker } = DatePicker;

/**
 * 扣款报表页面
 */
const PenaltyReport: React.FC = () => {
  const [loading, setLoading] = useState(false);
  const [penalties, setPenalties] = useState<any[]>([]);
  const [printVisible, setPrintVisible] = useState(false);
  const [printData, setPrintData] = useState<any>({});
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;

  const currentYear = new Date().getFullYear();
  const currentQuarter = Math.ceil((new Date().getMonth() + 1) / 3);

  const handleExport = () => {
    exportPenalties({ year: currentYear, quarter: currentQuarter });
  };

  const handleCalculate = async () => {
    try {
      const res: any = await calculateDeduction({
        year: currentYear,
        quarter: currentQuarter,
      });
      if (!res.message) {
        message.success('扣款计算完成');
        // 刷新列表
        const listRes: any = await getPenalties({ year: currentYear, quarter: currentQuarter });
        if (!listRes.message) {
          setPenalties(listRes.data || []);
        }
      } else {
        message.error(res.message || '计算失败');
      }
    } catch (e) {
      message.error('计算失败');
    }
  };

  const handlePrint = (record: any) => {
    setPrintData(record);
    setPrintVisible(true);
  };

  const columns: ProColumns[] = [
    {
      title: '部门',
      dataIndex: 'dept_name',
      key: 'dept_name',
      width: 200,
    },
    {
      title: '部门平均分',
      dataIndex: 'avg_score',
      key: 'avg_score',
      width: 120,
      render: (val: any) => val?.toFixed(2) || '-',
    },
    {
      title: '扣款金额(元)',
      dataIndex: 'total_deduct',
      key: 'total_deduct',
      width: 120,
      render: (val: any) => val?.toFixed(2) || '-',
    },
    {
      title: '操作',
      key: 'action',
      width: 100,
      render: (_, record) => (
        <Button type="link" onClick={() => handlePrint(record)}>
          打印
        </Button>
      ),
    },
  ];

  return (
    <div style={{ padding: 24 }}>
      <Card
        title={`${currentYear}年第${currentQuarter}季度扣款报表`}
        extra={
          <Row gutter={[8, 8]}>
            <Col>
              <Button onClick={handleCalculate}>重新计算扣款</Button>
            </Col>
            <Col>
              <Button type="primary" onClick={handleExport}>
                导出Excel
              </Button>
            </Col>
          </Row>
        }
      >
        <ProTable
          search={false}
          rowKey="id"
          loading={loading}
          request={async (params) => {
            const res: any = await getPenalties({
              year: currentYear,
              quarter: currentQuarter,
            });
            return {
              data: res.data || [],
              total: res.data?.length || 0,
              success: true,
            };
          }}
          columns={columns}
          pagination={{ pageSize: 10 }}
        />
      </Card>

      {/* 打印预览弹窗 */}
      <Modal
        title="打印预览"
        visible={printVisible}
        onOk={() => setPrintVisible(false)}
        onCancel={() => setPrintVisible(false)}
        width={600}
        footer={null}
      >
        <div id="print-area" style={{ padding: 20 }}>
          <h2 style={{ textAlign: 'center', marginBottom: 20 }}>
            服务对象评价统计表
          </h2>
          <p style={{ textAlign: 'center', marginBottom: 20 }}>
            期间: {currentYear}年度 第{currentQuarter}季度
          </p>
          <p style={{ marginBottom: 8 }}>
            部门: {printData.dept_name}
          </p>

          <table style={{ width: '100%', borderCollapse: 'collapse', marginBottom: 20 }}>
            <thead>
              <tr>
                <th style={tableThStyle}>评分选项</th>
                <th style={tableThStyle}>得分</th>
                <th style={tableThStyle}>小计</th>
                <th style={tableThStyle}>占比</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td style={tableTdStyle}>满意</td>
                <td style={tableTdStyle}>95分</td>
                <td style={tableTdStyle}>-</td>
                <td style={tableTdStyle}>-</td>
              </tr>
              <tr>
                <td style={tableTdStyle}>基本满意</td>
                <td style={tableTdStyle}>70分</td>
                <td style={tableTdStyle}>-</td>
                <td style={tableTdStyle}>-</td>
              </tr>
              <tr>
                <td style={tableTdStyle}>不满意</td>
                <td style={tableTdStyle}>50分</td>
                <td style={tableTdStyle}>-</td>
                <td style={tableTdStyle}>-</td>
              </tr>
            </tbody>
          </table>

          <div style={{ marginBottom: 20 }}>
            <p>统计: 应评人数 - 已评人数 - 未评人数 - 平均分 {printData.avg_score?.toFixed(2)}</p>
          </div>

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

export default PenaltyReport;
