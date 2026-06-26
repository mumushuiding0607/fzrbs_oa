import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useRef, } from 'react';
import { errorRule } from './service';
import browser from '@/utils/browser';
import moment from 'moment';



const ErrorTable: React.FC = () => {
  const actionRef = useRef<ActionType>();


  const columns: ProColumns<any>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInSearch: true,
      width: 80
    },
    {
      title: '操作人',
      dataIndex: 'opt_name',
      // hideInSearch: true,
      width: 80
    },
    {
      title: '错误提示',
      dataIndex: 'title',
      hideInSearch: true,
      ellipsis: true,
      width: 150,
      ellipsis: true,

    },
    {
      title: '错误信息',
      dataIndex: 'msg',
      hideInSearch: true,
      ellipsis: true,
      // width:120

    },
    {
      title: '创建时间',
      dataIndex: 'created',
      width: 200, ellipsis: true,
      hideInSearch: true,

    }
  ];

  return (
    <>
      <ProTable<any, any>
        headerTitle="错误提示列表"
        actionRef={actionRef}
        rowKey="id"
        search={{
          defaultCollapsed: false,
          labelWidth: 120,
        }}
        scroll={{ x: 1300 }}
        request={errorRule}
        columns={columns}

      />

    </>
  );
};

export default ErrorTable;
