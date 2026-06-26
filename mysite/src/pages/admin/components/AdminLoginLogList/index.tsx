import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useRef } from 'react';
import { rule } from './service';

type AdminLoginLogListProps = {
  showSearchForm?: boolean;
  username?: string;
};

const AdminLoginLogList: React.FC<AdminLoginLogListProps> = (props) => {
  const { showSearchForm, username } = props;

  const actionRef = useRef<ActionType>();

  const columns: ProColumns[] = [
    {
      title: '用户名',
      dataIndex: 'username',
    },
    {
      title: '姓名',
      dataIndex: 'realname',
    },
    {
      title: '登录方式',
      dataIndex: 'logintype',
      valueEnum: {
        账号密码登录: {
          text: '账号密码登录',
        },
        手机号动态码登录: {
          text: '手机号动态码登录',
        },
      },
    },
    {
      title: '登录IP',
      dataIndex: 'ip',
    },
    {
      title: '日志类型',
      dataIndex: 'logtype',
      valueEnum: {
        登录: {
          text: '登录',
        },
        退出: {
          text: '退出',
        },
      },
    },
    {
      title: '备注',
      dataIndex: 'remark',
    },
    {
      title: '日志时间',
      dataIndex: 'inserttime',
      sorter: true,
      valueType: 'dateRange',
      render: (_, entity) => {
        return entity.inserttime;
      },
    },
  ];

  return (
    <>
      <ProTable<any, any>
        headerTitle="用户登录登出日志列表"
        actionRef={actionRef}
        rowKey="id"
        search={
          showSearchForm
            ? {
                labelWidth: 120,
              }
            : false
        }
        request={(params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          if (username) {
            params.username = username;
          }
          // eslint-disable-next-line no-param-reassign
          params = { ...params, sorter, filter };
          return rule(params);
        }}
        columns={columns}
      />
    </>
  );
};

export default AdminLoginLogList;
