import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useRef, useState } from 'react';
import { statistics, monthDownload, monthDetailDownload } from './service';
import { Drawer, Button } from 'antd';
import moment from 'moment';

const StaticsTable: React.FC = () => {
  const [showDetail, setShowDetail] = useState<boolean>(false);
  const [currentRow, setCurrentRow] = useState<any>();
  //   const [requestParams, setRequestParams] = useState<any>(undefined);
  const actionRef = useRef<ActionType>();
  //处理导出数据
  const handleDownload = async (params: {}) => {
    monthDownload(params);
  };
  //处理导出明细数据
  const handleMonthDetailDownload = async (params: {}) => {
    monthDetailDownload(params);
  };

  const columns: ProColumns<any>[] = [
    {
      title: '年月',
      dataIndex: 't_month',
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
      title: '车牌号',
      dataIndex: 'car_licence',
    },
    {
      title: '总公里数',
      dataIndex: 'mile',
      hideInSearch: true,
    },
    {
      title: '总停车费',
      dataIndex: 'park_fee',
      hideInSearch: true,
    },

  ];

  return (
    <>
      <ProTable<any, any>
        // headerTitle="月度统计"
        actionRef={actionRef}
        rowKey="car_licence"
        // search={{
        //   labelWidth: 120,
        // }}
        form={{
          ignoreRules: false,
        }}
        search={{
          labelWidth: 120,
          defaultCollapsed: false,
          optionRender: (searchConfig, formProps, dom) => [
            ...dom.reverse(),
            <Button
              key="download"
              type="primary" ghost
              onClick={() => {
                const values = searchConfig?.form?.getFieldsValue();
                handleDownload(values);
                // console.log(values);
              }}
            >
              导出数据
            </Button>,
            <Button
              key="downloadMonth"
              type="primary" ghost
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
        request={statistics}
        columns={columns}
      />
      <Drawer
        width={600}
        visible={showDetail}
        onClose={() => {
          setCurrentRow(undefined);
          setShowDetail(false);
        }}
        closable={true}
      >
        {/* {currentRow && (
          <ProDescriptions<any> column={2} title="详情">
            <ProDescriptions.Item label="用户名" valueType="text">
              {currentRow.username}
            </ProDescriptions.Item>
            <ProDescriptions.Item label="姓名" valueType="text">
              {currentRow.realname}
            </ProDescriptions.Item>
            <ProDescriptions.Item label="操作类型" valueEnum={{ catalogText }}>
              {currentRow.realname}
            </ProDescriptions.Item>
            <ProDescriptions.Item label="操作时间" valueType="text">
              {currentRow.inserttime}
            </ProDescriptions.Item>
            <ProDescriptions.Item span={2} valueType="text" ellipsis label="操作描述">
              {currentRow.remark}
            </ProDescriptions.Item>
            <ProDescriptions.Item span={2} valueType="text" ellipsis label="操作url">
              {currentRow.url}
            </ProDescriptions.Item>
            {requestParams != '' && (
              <ProDescriptions.Item
                label="请求参数"
                valueType="text"
                contentStyle={{
                  maxWidth: '80%',
                }}
              >
                {requestParams}
              </ProDescriptions.Item>
            )}
          </ProDescriptions>
        )} */}
      </Drawer>
    </>
  );
};

export default StaticsTable;
