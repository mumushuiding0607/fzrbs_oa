import type { ActionType, ProFormColumnsType, ProFormInstance } from '@ant-design/pro-components';
import {
  ProTable,
  ProFormDateTimeRangePicker,
  ProFormSelect,
} from '@ant-design/pro-components';
import { Button, Modal, Input, Tag } from 'antd';
import { useRef, useState } from 'react';
import { attendanceList, exportAttendanceList, cancelAttendance } from './service';
import * as XLSX from 'xlsx';

const statusEnum = {
  1: { text: '审批中', status: 'processing' },
  2: { text: '已同意', status: 'success' },
  3: { text: '已驳回', status: 'error' },
  4: { text: '已取消', status: 'default' },
};

const AttendanceList: React.FC = () => {
  const proTableFormRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  const [params, setParams] = useState<any>({});

  const columns: ProFormColumnsType<any>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInSearch: true,
      width: 60,
    },
    {
      title: '申请单号',
      dataIndex: 'thirdNo',
      width: 150,
    },
    {
      title: '申请人',
      dataIndex: 'userName',
      width: 100,
    },
    {
      title: '部门',
      dataIndex: 'department',
      width: 150,
    },
    {
      title: '异常日期',
      dataIndex: 'date',
      width: 120,
    },
    {
      title: '异常类型',
      dataIndex: 'type',
      width: 120,
    },
    {
      title: '异常说明',
      dataIndex: 'reason',
      width: 200,
    },
    {
      title: '状态',
      dataIndex: 'status',
      width: 100,
      valueType: 'select',
      valueEnum: statusEnum,
      render: (_, record) => {
        const item = statusEnum[record.status as keyof typeof statusEnum] || { text: '未知', status: 'default' };
        return <Tag color={item.status}>{item.text}</Tag>;
      },
    },
    {
      title: '当前审批人',
      dataIndex: 'approvalUsername',
      width: 120,
    },
    {
      title: '申请时间',
      dataIndex: 'inserttime',
      width: 150,
      valueType: 'dateTime',
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      width: 120,
      render: (_: any, entity: any) => [
        entity.status === 1 && (
          <a
            key="cancel"
            style={{ color: '#ff4d4f' }}
            onClick={() => {
              Modal.confirm({
                title: '确认撤销',
                content: '确定要撤销此考勤异常申请吗？',
                onOk: async () => {
                  const res = await cancelAttendance({ thirdNo: entity.thirdNo });
                  if (res.errorMessage) {
                    Modal.error({ title: res.errorMessage });
                  } else {
                    Modal.info({ title: '撤销成功' });
                    actionRef.current?.reload();
                  }
                },
              });
            }}
          >
            撤销
          </a>
        ),
      ],
    },
  ];

  const handleExport = () => {
    Modal.confirm({
      title: '确认导出',
      content: '确定要导出当前查询条件下的审批单数据吗？',
      onOk: async () => {
        const res = await exportAttendanceList(params);
        if (res.errorMessage) {
          Modal.error({ title: res.errorMessage });
        } else {
          const ws = XLSX.utils.aoa_to_sheet(res.data);
          const wb = XLSX.utils.book_new();
          XLSX.utils.book_append_sheet(wb, ws, 'Sheet1');
          XLSX.writeFile(wb, `考勤异常审批单_${new Date().toLocaleString()}.xlsx`);
          Modal.info({ title: '导出成功' });
        }
      },
    });
  };

  return (
    <ProTable
      style={{ minHeight: 'calc(100vh - 180px)' }}
      headerTitle="考勤异常审批单"
      actionRef={actionRef}
      params={params}
      formRef={proTableFormRef}
      rowKey={(record: any) => record.id}
      search={{
        labelWidth: 'auto',
        defaultCollapsed: false,
      }}
      columns={columns}
      request={async (params) => {
        const res = await attendanceList(params);
        return {
          data: res.data,
          total: res.total,
          success: true,
        };
      }}
      toolbar={{
        actions: [
  
        ],
      }}
      pagination={{
        pageSize: 20,
        showQuickJumper: true,
        showSizeChanger: true,
      }}
    />
  );
};

export default AttendanceList;
