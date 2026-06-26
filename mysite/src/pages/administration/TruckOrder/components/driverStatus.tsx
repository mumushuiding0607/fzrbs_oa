import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useRef, useState } from 'react';
import { driverStatus } from './service';
import { Drawer } from 'antd';

const DriverStatus: React.FC = () => {
  const [showDetail, setShowDetail] = useState<boolean>(false);
  const [currentRow, setCurrentRow] = useState<any>();
  //   const [requestParams, setRequestParams] = useState<any>(undefined);
  const actionRef = useRef<ActionType>();

  const columns: ProColumns<any>[] = [
    {
      title: '司机',
      dataIndex: 'name',
      hideInSearch: true,
    },
    {
      title: '状态',
      dataIndex: 'st',
      hideInSearch: true,
    },
    // {
    //   title: '总公里数',
    //   dataIndex: 'mile',
    //   hideInSearch: true,
    // },
    // {
    //   title: '总停车费',
    //   dataIndex: 'park_fee',
    //   hideInSearch: true,
    // },

  ];

  return (
    <>
      <ProTable<any, any>
        // headerTitle="月度统计"
        actionRef={actionRef}
        rowKey="userid"
        search={false}
        pagination={{
          pageSize: 100,
          // onChange: (page) => console.log(page),
        }}
        // toolBarRender={false}
        request={driverStatus}
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

export default DriverStatus;
