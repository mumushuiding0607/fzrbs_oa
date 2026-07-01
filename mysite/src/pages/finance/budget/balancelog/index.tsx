import React from 'react';
import { ProColumns } from '@ant-design/pro-components';
import { ProTable } from '@ant-design/pro-components';
import { getbalancelog } from './service';
import moment from 'moment';

const BalanceLog: React.FC<{ projectId: number | string }> = ({ projectId }) => {
  return (
    <ProTable
      scroll={{ x: 'max-content' }}
      search={false}
      rowKey="id"
      size="small"
      request={(params) => {
        return getbalancelog({
          projectid: projectId,
          current: params.current,
          pageSize: params.pageSize,
        }).then((res: any) => ({
          data: res.data || [],
          total: res.total || 0,
          success: true,
        }));
      }}
      columns={columns}
      pagination={{ pageSize: 10 }}
    />
  );
};

const columns: ProColumns[] = [
  {
    title: '时间',
    dataIndex: 'inserttime',
    key: 'inserttime',
    width: 160,
    render: (_, record) => (record.inserttime ? moment(record.inserttime).format('YYYY-MM-DD HH:mm') : '-'),
  },
  {
    title: '操作人',
    dataIndex: 'realname',
    key: 'realname',
    width: 100,
  },
  {
    title: '变更内容',
    dataIndex: 'remark',
    key: 'remark',
    render: (_, record) => (
      <span style={{ color: record.remark?.includes('→') ? '#e67e22' : undefined }}>
        {record.remark || '-'}
      </span>
    ),
  },
];

export default BalanceLog;
