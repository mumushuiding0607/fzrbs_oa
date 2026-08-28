import React from 'react';
import { ProColumns } from '@ant-design/pro-components';
import { ProTable } from '@ant-design/pro-components';
import dayjs from 'dayjs';

const OperationLog: React.FC<{
  api: (params: { bizId: any; current?: number; pageSize?: number }) => Promise<any>;
  bizId: string | number;
}> = ({ api, bizId }) => {
  return (
    <ProTable
      scroll={{ x: 'max-content' }}
      search={false}
      rowKey="id"
      size="small"
      request={(params) => {
        return api({
          bizId,
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
    width: 180,
    render: (text: number | string) => {
      if (!text) return '';
      // 如果是 Unix 时间戳（10位数字），转换为日期格式
      const num = Number(text);
      if (!isNaN(num) && String(num).length === 10) {
        return dayjs.unix(num).format('YYYY-MM-DD HH:mm:ss');
      }
      // 如果已经是格式化日期字符串，直接返回
      if (typeof text === 'string') {
        return text;
      }
      return String(text);
    },
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
    render: (text: string) => {
      if (!text) return '';
      // 移除 [contractID:xxx] [invoicingID:xxx] [budgetID:xxx] 前缀
      let content = text.replace(/\[(contract|invoicing|budget)ID:\d+\]\s*/, '');
      // 移除 合同【xxx】 或 开票申请【xxx】 等业务标识
      content = content.replace(/合同【[^】]*】/, '');
      content = content.replace(/开票申请【[^】]*】/, '');
      content = content.replace(/非报项目【[^】]*】/, '');
      return content.trim();
    },
  },
];

export default OperationLog;