import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useRef, useState } from 'react';
import { reportRule } from './service';
import type { TableListItem, TableListPagination } from './data';
import { Button } from 'antd';
import browser from '@/utils/browser';
import moment from 'moment';


const ReportTable: React.FC = () => {
  const actionRef = useRef<ActionType>();
  const columns: ProColumns<any>[] = [

    {
      title: '姓名',
      dataIndex: 'name',
      hideInSearch: true,
      width: 200
    },
    {
      title: '状态',
      dataIndex: 'st',
      hideInSearch: true,
      width: 100,
      ellipsis: true,
    },
    {
      title: '时段',
      dataIndex: 'time',
      ellipsis: true,
      hideInSearch: true,

    }
  ];

  return (
    <>
      <ProTable<TableListItem, TableListPagination>
        headerTitle="今日记者去向"
        actionRef={actionRef}
        rowKey="userid"
        search={false}


        // scroll={{ x: 1300 }}
        request={reportRule}
        columns={columns}

      />


    </>
  );
};

export default ReportTable;
