import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useImperativeHandle, useRef, useState } from 'react';
import { rule, asynchronization } from './service';
import { Button, message } from 'antd';
import { ProFormColumnsType } from '@ant-design/pro-components';
import { CloudDownloadOutlined } from '@ant-design/icons';

export type ListProps = {
  onSynchronization?: () => void;
};

const List = React.forwardRef((props: ListProps, ref) => {
  const actionRef = useRef<ActionType>();
  const [parentId, setParentId] = useState<number>(0);

  const columns: ProFormColumnsType<any>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInForm: true,
      hideInDescriptions: true,
    },
    {
      title: '部门名称',
      dataIndex: 'name',
    },
    {
      title: '排序',
      dataIndex: 'order',
    },
  ];

  useImperativeHandle(ref, () => ({
    reload: (id: number) => {
      setParentId(id);
      actionRef.current?.reload();
    },
  }));

  return (
    <>
      <ProTable<any, any>
        headerTitle="部门列表"
        actionRef={actionRef}
        rowKey="id"
        search={false}
        request={(params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          params.parentid = parentId;
          return rule(params);
        }}
        columns={columns as ProColumns<any>[]}
        tableAlertRender={false}
        toolBarRender={() => [
          <Button
            type="primary"
            key="synchronization"
            onClick={async () => {
              const hide = message.loading('正在同步企业号通讯录部门...', 0);
              const result = await asynchronization();
              hide();
              if (result.errorMessage) {
                message.warn(result.errorMessage);
                return;
              }
              message.success('同步成功');
              actionRef.current?.reload();
              if (props.onSynchronization) {
                props.onSynchronization();
              }
            }}
          >
            <CloudDownloadOutlined /> 同步企业号通讯录部门
          </Button>,
        ]}
      />
    </>
  );
});

export default List;
