import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useRef, useState } from 'react';
import { monthDownload, monthDetailDownload, statisticsRule } from './service';
import type { TableListItem, TableListPagination } from './data';
import { Button } from 'antd';
import { PlusOutlined, VerticalAlignBottomOutlined } from '@ant-design/icons';
import browser from '@/utils/browser';
import moment from 'moment';
import ReactToPrint from "react-to-print"; //打印


const StatisticsTable: React.FC = () => {
  const actionRef = useRef<ActionType>();

  const handleDownload = async (params: {}) => {
    monthDownload(params);
  };
  //处理导出明细数据
  const handleMonthDetailDownload = async (params: {}) => {
    monthDetailDownload(params);
  };


  const columns: ProColumns<TableListItem>[] = [
    {
      title: '年月',
      dataIndex: 'month',
      width: 80,
      valueType: 'dateMonth',
      initialValue: moment().format("YYYY-MM"),
      formItemProps: {
        rules: [
          {
            required: true,
            message: '此项为必填项',
          },
        ],
      },
    },
    {
      title: '记者',
      dataIndex: 'dispatch_name',
      hideInSearch: true,
      width: 80
    },
    {
      title: '总派工次数',
      dataIndex: 'num',
      hideInSearch: true,
      width: 150,
      ellipsis: true,
    },
    {
      title: '总分',
      dataIndex: 'grade',
      width: 140, ellipsis: true,
      hideInSearch: true,

    },
    {
      title: '平均数',
      dataIndex: 'avg',
      hideInSearch: true,
      width: 120

    }
  ];

  return (
    <>
      <ProTable<TableListItem, TableListPagination>
        headerTitle="摄影派工列表"
        actionRef={actionRef}
        rowKey="dispatch_userid"
        search={{
          defaultCollapsed: false,
          labelWidth: 120,
          optionRender: (searchConfig, formProps, dom) => [
            ...dom.reverse(),
            <Button
              key="out1" icon={<VerticalAlignBottomOutlined />} type="primary" ghost
              onClick={() => {
                const values = searchConfig?.form?.getFieldsValue();
                handleDownload(values);
                // console.log(values);
              }}
            >
              导出
            </Button>,
            <Button
              key="out2" icon={<VerticalAlignBottomOutlined />} type="primary" ghost
              onClick={() => {
                const values = searchConfig?.form?.getFieldsValue();
                handleMonthDetailDownload(values);
                // console.log(values);
              }}
            >
              导出月度明细
            </Button>
          ],
        }}
        form={{
          ignoreRules: false,
        }}
        // scroll={{ x: 1300 }}
        request={statisticsRule}
        columns={columns}

      />


    </>
  );
};

export default StatisticsTable;
