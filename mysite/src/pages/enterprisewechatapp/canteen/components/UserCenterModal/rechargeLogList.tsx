import { ActionType, ProColumns, ProTable } from '@ant-design/pro-components';
import React, { useRef } from 'react';
import { rechargeLog } from '../../service';

export type RechargeLogListProps = {
  backTop?: () => void;
};

const RechargeLogList = React.forwardRef((props: RechargeLogListProps, ref) => {
  const actionRef = useRef<ActionType>();

  const columns: ProColumns<any>[] = [
    {
      title: '充值金额',
      dataIndex: 'rechargemoney',
      valueType: 'money',
      hideInSearch: true,
    },
    {
      title: '备注',
      dataIndex: 'intro',
      hideInSearch: true,
    },
    {
      title: '充值时间',
      dataIndex: 'inserttime',
      valueType: 'dateRange',
      render: (_, entity) => {
        return entity.inserttime;
      },
    },
  ];

  return (
    <>
      <ProTable<any, any>
        headerTitle="充值日志列表"
        actionRef={actionRef}
        search={false}
        rowKey="id"
        request={(params, sorter, filter) => {
          if (props.backTop) {
            props.backTop();
          }
          return rechargeLog(params);
        }}
        columns={columns}
      />
    </>
  );
});

export default RechargeLogList;
