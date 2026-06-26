import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useImperativeHandle, useRef, useState } from 'react';
import { rule, asynchronization } from './service';
import { Button, message } from 'antd';
import { ProFormColumnsType } from '@ant-design/pro-components';
import { CloudDownloadOutlined } from '@ant-design/icons';

export type ListProps = {
  // onSynchronization?: () => void;
};

const DepList = React.forwardRef((props: ListProps, ref) => {
  const actionRef = useRef<ActionType>();
  const [parentId, setParentId] = useState<number>(0);

  const columns: ProFormColumnsType<any>[] = [
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
          params.parentid = 1;
          params.tree = 0;
          return rule(params);
        }}
        columns={columns as ProColumns<any>[]}
        tableAlertRender={false}
        toolBarRender={() => []}
      />
    </>
  );
});

export default DepList;
