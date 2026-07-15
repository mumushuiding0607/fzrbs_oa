import React from 'react';
import ProColumns from '@ant-design/pro-components';
import { ProTable } from '@ant-design/pro-components';
import { getadvlog } from './service';
import moment from 'moment';

const AdvLog: React.FC<{ advitemId?: number | string; orderId?: number | string }> = ({ advitemId, orderId }) => {
  return (
    <ProTable
      scroll={{ x: 'max-content' }}
      search={false}
      rowKey="id"
      size="small"
      request={(params) => {
        return getadvlog({
          advitemid: advitemId,
          orderid: orderId,
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
    title: '操作类型',
    dataIndex: 'catalog',
    key: 'catalog',
    width: 120,
  },
  {
    title: '变更内容',
    dataIndex: 'remark',
    key: 'remark',
    render: (_, record) => {
      if (!record.remark) return '-';
      // 去掉前缀，只保留变化描述
      let content = record.remark;
      // 去掉 "修改广告订单【xxx】订单【xxx】" 或类似前缀
      content = content.replace(/^(新增|修改)(广告订单|广告|订单)【[^】]*】(订单【[^】]*】)?/g, '').trim();
      if (!content) return '-';
      return <span style={{ color: '#e67e22' }}>{content}</span>;
    },
  },
];

export default AdvLog;
