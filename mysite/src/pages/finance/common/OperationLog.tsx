import React from 'react';
import { ProColumns } from '@ant-design/pro-components';
import { ProTable } from '@ant-design/pro-components';
import dayjs from 'dayjs';

const OperationLog: React.FC<{
  api: (params: { bizId: any; current?: number; pageSize?: number }) => Promise<any>;
  bizId: string | number;
}> = ({ api, bizId }) => {
  return (
    <div style={{ width: '100%', overflowX: 'auto' }}>
      <style>{`
        .operation-log-remark table {
          width: auto !important;
          max-width: 100% !important;
          overflow-x: visible !important;
        }
        .operation-log-remark table th,
        .operation-log-remark table td {
          white-space: pre-wrap !important;
          word-break: break-word !important;
        }
      `}</style>
      <ProTable
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
    </div>
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
    className: 'operation-log-remark',
    render: (text: string) => {
      if (!text) return '';
      // 保留"变更内容："之后的内容
      const match = text.match(/变更内容：([\s\S]*)$/);
      const content = match ? match[1] : text;
      return (
        <div style={{ whiteSpace: 'pre-wrap', wordBreak: 'break-word' }}>
          {content.trim()}
        </div>
      );
    },
  },
];

export default OperationLog;