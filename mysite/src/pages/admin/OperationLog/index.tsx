import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import ProDescriptions from '@ant-design/pro-descriptions';
import React, { useEffect, useRef, useState } from 'react';
import { rule, params, operationType } from './service';
import { Drawer } from 'antd';
import { PageContainer } from '@ant-design/pro-components';
import browser from '@/utils/browser';
import styles from './index.less';

const ListTable: React.FC = () => {
  const [showDetail, setShowDetail] = useState<boolean>(false);
  const [currentRow, setCurrentRow] = useState<any>();
  const [requestParams, setRequestParams] = useState<any>(undefined);
  const [actionType, setActionType] = useState<any>({});
  const actionRef = useRef<ActionType>();

  const columns: ProColumns<any>[] = [
    {
      title: '用户名',
      dataIndex: 'username',
      render: (dom, entity) => {
        return (
          <a
            onClick={async () => {
              setCurrentRow(entity);
              const logParams = await params({ logid: entity.id });
              if (logParams.data && logParams.data != '') {
                setRequestParams(logParams.data);
              } else {
                setRequestParams(undefined);
              }
              setShowDetail(true);
            }}
          >
            {dom}
          </a>
        );
      },
    },
    {
      title: '姓名',
      dataIndex: 'realname',
    },
    {
      title: '操作类型',
      dataIndex: 'catalog',
      valueEnum: actionType,
    },
    {
      title: '操作描述',
      dataIndex: 'remark',
    },
    {
      title: '操作url',
      dataIndex: 'url',
      hideInTable: true,
      hideInSearch: true,
    },
    {
      title: 'IP',
      dataIndex: 'ip',
    },
    {
      title: '操作时间',
      dataIndex: 'inserttime',
      valueType: 'dateRange',
      render: (_, entity) => {
        return entity.inserttime;
      },
    },
  ];

  useEffect(() => {
    operationType().then((res) => {
      setActionType(res.data);
    });
  }, []);

  return (
    <PageContainer
      header={{
        breadcrumb: {},
      }}
    >
      <ProTable<any, any>
        headerTitle="操作日志列表"
        actionRef={actionRef}
        rowKey="id"
        search={{
          labelWidth: 120,
        }}
        request={(params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          return rule(params);
        }}
        columns={columns}
        className={styles.logTable}
      />
      <Drawer
        width={browser.mobile() ? '100vw' : 600}
        visible={showDetail}
        onClose={() => {
          setCurrentRow(undefined);
          setShowDetail(false);
        }}
        closable={true}
      >
        {currentRow && (
          <ProDescriptions<any> column={2} title="详情">
            <ProDescriptions.Item label="用户名" valueType="text">
              {currentRow.username}
            </ProDescriptions.Item>
            <ProDescriptions.Item label="姓名" valueType="text">
              {currentRow.realname}
            </ProDescriptions.Item>
            <ProDescriptions.Item label="操作类型">
              {currentRow.catalog}
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
        )}
      </Drawer>
    </PageContainer>
  );
};

export default ListTable;
