import { ActionType, ProColumns, ProDescriptions, ProTable } from '@ant-design/pro-components';
import { Drawer } from 'antd';
import React, { useEffect, useRef, useState } from 'react';
import { type, rechargeLog } from './components/list/service';
import browser from '@/utils/browser';

const RechargeLogTab: React.FC = () => {
  const [showDetail, setShowDetail] = useState<boolean>(false);
  const [currentRow, setCurrentRow] = useState<any>();
  const actionRef = useRef<ActionType>();
  const [userType, setUserType] = useState<any>({});

  const columns: ProColumns<any>[] = [
    {
      title: '姓名',
      dataIndex: 'targetrealname',
      render: (dom, entity) => {
        return (
          <a
            onClick={() => {
              setCurrentRow(entity);
              setShowDetail(true);
            }}
          >
            {dom}
          </a>
        );
      },
    },
    {
      title: '部门',
      dataIndex: 'departmentname',
    },
    {
      title: '充值金额',
      dataIndex: 'rechargemoney',
      valueType: 'money',
      hideInSearch: true,
    },
    {
      title: '操作人',
      dataIndex: 'urealname',
      hideInSearch: true,
    },
    {
      title: '微信充值',
      dataIndex: 'weixinpay',
      valueEnum: {
        0: {
          text: '否',
        },
        1: {
          text: '是',
        },
      },
    },
    {
      title: '用户分类',
      dataIndex: 'usertype',
      valueEnum: userType,
    },
    {
      title: '备注',
      dataIndex: 'intro',
      hideInSearch: true,
    },
    {
      title: '充值全额',
      dataIndex: 'rechargeall',
      valueType: 'money',
      hideInSearch: true,
      hideInTable: true,
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

  useEffect(() => {
    type().then((res) => {
      setUserType(res.data);
    });
  }, []);

  return (
    <>
      <ProTable<any, any>
        headerTitle="充值日志列表"
        actionRef={actionRef}
        rowKey="id"
        request={(params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          return rechargeLog(params);
        }}
        columns={columns}
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
        <ProDescriptions<any>
          column={1}
          title="详情"
          request={async () => ({
            data: currentRow || {},
          })}
          params={{
            id: currentRow?.id,
          }}
          columns={columns}
        />
      </Drawer>
    </>
  );
};

export default RechargeLogTab;
