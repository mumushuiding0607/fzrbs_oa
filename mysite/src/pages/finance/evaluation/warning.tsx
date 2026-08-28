import React, { useEffect, useState } from 'react';
import { Card, Table, Button, Row, Col, Modal, Tag, message } from 'antd';
import { ProColumns } from '@ant-design/pro-components';
import { ProTable } from '@ant-design/pro-components';
import { getWarnings, exportWarnings, generateWarnings } from './service';
import { useModel } from 'umi';
import moment from 'moment';
import { WarningLevelText } from './config';

/**
 * 预警管理页面
 */
const WarningManagement: React.FC = () => {
  const [loading, setLoading] = useState(false);
  const [warnings, setWarnings] = useState<any[]>([]);
  const [printVisible, setPrintVisible] = useState(false);
  const [printData, setPrintData] = useState<any>({});
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;

  const currentYear = new Date().getFullYear();

  const handleExport = () => {
    exportWarnings({ year: currentYear });
  };

  const handleGenerate = async () => {
    try {
      const res: any = await generateWarnings({ year: currentYear });
      if (!res.message) {
        message.success('预警生成完成');
        // 刷新列表
        const listRes: any = await getWarnings({ year: currentYear });
        if (!listRes.message) {
          setWarnings(listRes.data || []);
        }
      } else {
        message.error(res.message || '生成失败');
      }
    } catch (e) {
      message.error('生成失败');
    }
  };

  const handlePrint = (record: any) => {
    setPrintData(record);
    setPrintVisible(true);
  };

  const columns: ProColumns[] = [
    {
      title: '员工姓名',
      dataIndex: 'user_name',
      key: 'user_name',
      width: 150,
    },
    {
      title: '累计50分次数',
      dataIndex: 'score_50_count',
      key: 'score_50_count',
      width: 120,
      render: (val: any) => <Tag color={val >= 2 ? 'red' : 'orange'}>{val}次</Tag>,
    },
    {
      title: '预警级别',
      dataIndex: 'warning_level',
      key: 'warning_level',
      width: 100,
      render: (val: any) => (
        <Tag color={val === 2 ? 'red' : 'orange'}>
          {WarningLevelText[val] || '未知'}
        </Tag>
      ),
    },
    {
      title: '触发周期',
      dataIndex: 'cycle_ids',
      key: 'cycle_ids',
      width: 200,
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
        title={`${currentYear}年度 50分预警名单`}
        extra={
          <Row gutter={[8, 8]}>
            <Col>
              <Button onClick={handleGenerate}>重新生成预警</Button>
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
            const res: any = await getWarnings({ year: currentYear });
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
        title="预警通知单"
        visible={printVisible}
        onOk={() => setPrintVisible(false)}
        onCancel={() => setPrintVisible(false)}
        width={500}
        footer={null}
      >
        <div id="print-area" style={{ padding: 20 }}>
          <h2 style={{ textAlign: 'center', marginBottom: 20 }}>
            预警通知单
          </h2>
          <p style={{ marginBottom: 16 }}>
            员工姓名: {printData.user_name}
          </p>
          <p style={{ marginBottom: 16 }}>
            累计50分次数: {printData.score_50_count}次
          </p>
          <p style={{ marginBottom: 16 }}>
            预警级别: <Tag color={printData.warning_level === 2 ? 'red' : 'orange'}>
              {WarningLevelText[printData.warning_level] || '未知'}
            </Tag>
          </p>
          <p style={{ marginBottom: 16 }}>
            触发周期: {printData.cycle_ids}
          </p>

          <div style={{ marginTop: 30, padding: 16, border: '1px solid #ccc' }}>
            <p style={{ fontWeight: 'bold', marginBottom: 8 }}>处理意见:</p>
            <p style={{ height: 60 }}></p>
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

export default WarningManagement;
