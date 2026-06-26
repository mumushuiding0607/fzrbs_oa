import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import type { ProDescriptionsItemProps } from '@ant-design/pro-descriptions';
import ProDescriptions from '@ant-design/pro-descriptions';
import React, { useRef, useState } from 'react';
import { rule, addRule, updateRule, removeRule, role, saveRole } from './service';
import type { TableListItem, TableListPagination, ErrorResponse } from './data';
import { Button, Drawer, message, Modal, Alert } from 'antd';
import { ModalForm, ProFormCheckbox, ProFormInstance } from '@ant-design/pro-components';
import { DrawerForm, ProForm, ProFormSelect, ProFormText } from '@ant-design/pro-components';
import { MinusOutlined, PlusOutlined } from '@ant-design/icons';
import browser from '@/utils/browser';
import tools from '@/utils/tools';
import styles from '../../Role/index.less';

const AdminMessage: React.FC<{
  content: string;
}> = ({ content }) => (
  <Alert
    style={{
      marginBottom: 24,
    }}
    message={content}
    type="error"
    closable={true}
    showIcon
  />
);

const AdminList: React.FC = () => {
  const [showDetail, setShowDetail] = useState<boolean>(false);
  const [currentRow, setCurrentRow] = useState<TableListItem>();
  const [selectedRowsState, setSelectedRows] = useState<TableListItem[]>([]);
  const actionRef = useRef<ActionType>();
  const [showForm, setShowForm] = useState<boolean>(false);
  const formRef = useRef<ProFormInstance>();
  const [responseState, setResponseState] = useState<ErrorResponse>();
  const [showRoleForm, setShowRoleForm] = useState<boolean>(false);
  const [roles, setRoles] = useState<any>([]);
  const [userRoles, setUserRoles] = useState<any>([]);
  const [roleModalTitle, setRoleModalTitle] = useState<string>('');

  const handleRemove = async (selectedRows: TableListItem[], deleteRow: any) => {
    const hide = message.loading('正在删除');
    if (!selectedRows && !deleteRow) return true;

    try {
      if (selectedRows.length > 0) {
        await removeRule({
          id: selectedRows.map((row) => row.id),
        });
      } else if (deleteRow) {
        await removeRule({
          id: [deleteRow].map((row) => row.id),
        });
      }
      hide();
      message.success('删除成功');
      return true;
    } catch (error) {
      hide();
      message.warn('删除失败，请重试');
      return false;
    }
  };

  const deleteItem = (item: React.SetStateAction<TableListItem | undefined>) => {
    Modal.confirm({
      title: '删除',
      content: '确定要删除吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        await handleRemove([], item);
        setShowDetail(false);
        actionRef.current?.reload?.();
      },
    });
  };

  const handleAddAndUpdate = async (updateRow: any, values: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      if (updateRow == undefined) {
        result = await addRule({
          values,
        });
      } else {
        result = await updateRule({
          id: updateRow.id,
          values,
        });
      }
      hide();
      setResponseState(result);
      return result;
    } catch (error) {
      message.warn('保存失败！');
      return false;
    }
  };

  const setRole = async (item: any) => {
    setCurrentRow(item);
    const result = await role({ username: item.username });
    if (result.roles) {
      setRoleModalTitle('用户姓名：' + item.realname + '，分类：' + (item.classify == 1 ? '企业账号' : '后台账号'));
      setUserRoles(result.userRoles);
      setRoles(result.roles);
    }
    setShowRoleForm(true);
  };

  const clearSelected = () => {
    setSelectedRows([]);
    actionRef?.current?.clearSelected();
  };

  const columns: ProColumns<TableListItem>[] = [
    {
      title: '用户名',
      dataIndex: 'username',
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
      title: '姓名',
      dataIndex: 'realname',
    },
    {
      title: '手机号',
      dataIndex: 'mobile',
    },
    {
      title: '用户类型',
      dataIndex: 'usertype',
      valueEnum: {
        0: {
          text: '普通用户',
        },
        1: {
          text: '管理员',
        },
      },
    },
    {
      title: '所在部门',
      dataIndex: 'department',
      hideInTable: true,
      hideInSearch: true,
    },
    {
      title: '最后登录IP',
      dataIndex: 'lastloginip',
    },
    {
      title: '最后登录时间',
      dataIndex: 'lastlogintime',
      valueType: 'dateRange',
      render: (_, entity) => {
        return entity.lastlogintime;
      },
    },
    {
      title: '登录次数',
      dataIndex: 'loginnum',
      hideInTable: true,
      hideInSearch: true,
      renderText: (val: string) => `${val}次`,
    },
    {
      title: '添加时间',
      dataIndex: 'inserttime',
      valueType: 'dateRange',
      render: (_, entity) => {
        return entity.inserttime;
      },
    },
    {
      title: '状态',
      dataIndex: 'islock',
      hideInForm: true,
      valueEnum: {
        0: {
          text: '正常',
          status: 'Success',
        },
        1: {
          text: '禁止',
          status: 'Default',
        },
      },
      tip: '正常代表账号可以正常登录，禁止代表账号禁止登录',
    },
    {
      title: '分类',
      dataIndex: 'classify',
      valueEnum: {
        0: {
          text: '后台账号',
        },
        1: {
          text: '企业账号',
        },
      },
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      render: (_, entity) => [
        <a
          key="edit"
          onClick={() => {
            setShowDetail(false);
            setCurrentRow(entity);
            setShowForm(true);
          }}
        >
          修改
        </a>,
        <a
          key="delete"
          onClick={() => {
            deleteItem(entity);
          }}
        >
          删除
        </a>,
        entity.usertype == 0 ?
          <a
            key="role"
            onClick={() => {
              setRole(entity);
            }}
          >
            角色
          </a> : '',
      ],
    },
  ];

  return (
    <>
      <ProTable<TableListItem, TableListPagination>
        headerTitle="用户账号列表"
        actionRef={actionRef}
        rowKey="id"
        search={{
          labelWidth: 'auto',
        }}
        request={(params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          if (!params.classify) {
            params.classify = 0;
          }
          return rule(params);
        }}
        columns={columns}
        rowSelection={{
          onChange: (_, selectedRows) => {
            setSelectedRows(selectedRows);
          },
        }}
        tableAlertRender={false}
        toolBarRender={() => [
          <Button
            type="primary"
            key="primary"
            onClick={() => {
              setCurrentRow(undefined);
              setShowForm(true);
            }}
          >
            <PlusOutlined /> 新建
          </Button>,
          <Button
            type="primary"
            key="delete"
            onClick={async () => {
              if (selectedRowsState.length == 0) {
                message.warn('请选择要操作的项目！');
                return;
              }
              Modal.confirm({
                title: '系统提示',
                content: '确定删除选中的项目吗？',
                okText: '确认',
                cancelText: '取消',
                onOk: async () => {
                  await handleRemove(selectedRowsState, undefined);
                  setSelectedRows([]);
                  actionRef.current?.reload?.();
                },
                onCancel: async () => clearSelected(),
              });
            }}
          >
            <MinusOutlined /> 批量删除
          </Button>,
        ]}
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
        <ProDescriptions<TableListItem>
          column={1}
          title="详情"
          request={async () => ({
            data: currentRow || {},
          })}
          params={{
            id: currentRow?.id,
          }}
          columns={columns as ProDescriptionsItemProps<TableListItem>[]}
        />
      </Drawer>
      <DrawerForm
        title="编辑用户信息"
        width={browser.mobile() ? '100vw' : 600}
        visible={showForm}
        onVisibleChange={setShowForm}
        formRef={formRef}
        autoFocusFirstInput
        drawerProps={{
          destroyOnClose: true,
          onClose: () => {
            setResponseState(undefined);
          },
        }}
        submitter={{ searchConfig: { submitText: '提交' } }}
        submitTimeout={2000}
        onFinish={async (values) => {
          setResponseState(undefined);
          const result = await handleAddAndUpdate(currentRow, values);
          if (result) {
            if (result.errorCode) {
              return false;
            }
            message.success('保存成功！');
            actionRef.current?.reload?.();
          }
          return true;
        }}
        initialValues={{
          ...currentRow,
          usertype: currentRow ? currentRow.usertype + '' : '0',
          islock: currentRow ? currentRow.islock + '' : '0',
        }}
        layout="vertical"
        grid={true}
      >
        {responseState?.errorCode && <AdminMessage content={responseState?.errorMessage} />}
        <ProForm.Group>
          <ProFormText
            name="username"
            width="sm"
            label="用户名"
            placeholder="请输入用户名"
            disabled={currentRow != undefined}
            rules={[
              {
                required: true,
                message: '请输入用户名！',
              },
              { max: 20, message: '用户名不能超过20个字符！' },
              {
                validator: (_, val) => {
                  const usernamePattern = new RegExp(
                    "[`~!@#$^&*()=|{}':;',\\[\\].<>《》/?~！@#￥……&*（）——|{}【】‘；：”“'。，、？]",
                  );
                  if (usernamePattern.test(val)) {
                    return Promise.reject('用户名不能包含特殊字符！');
                  }
                  return Promise.resolve();
                },
              },
            ]}
          />
          <ProFormText.Password
            name="password"
            width="sm"
            label="密码"
            placeholder="请输入密码"
            rules={[
              {
                required: currentRow == undefined,
                message: '请输入密码！',
              },
              {
                validator: (_, val) => {
                  if (val && !tools.passwordStrength(val)) {
                    return Promise.reject(
                      '密码为数字，小写字母，大写字母，特殊符号 至少包含三种，长度10位及以上！',
                    );
                  }
                  return Promise.resolve();
                },
              },
            ]}
          />
          <ProFormText
            name="realname"
            width="sm"
            label="姓名"
            placeholder="请输入姓名"
            rules={[
              {
                required: true,
                message: '请输入姓名！',
              },
            ]}
          />
          <ProFormText
            name="mobile"
            width="sm"
            label="手机号"
            placeholder="请输入手机号"
            rules={[
              {
                required: false,
                message: '请输入手机号！',
              },
              {
                pattern: /^1\d{10}$/,
                message: '手机号格式错误！',
              },
            ]}
          />
          <ProFormText name="department" width="sm" label="所在部门" placeholder="请输入部门" />
          <ProFormSelect
            width="sm"
            valueEnum={{
              0: { text: '普通用户' },
              1: { text: '管理员' },
            }}
            name="usertype"
            label="用户类型"
            allowClear={false}
            rules={[
              {
                required: true,
                message: '请选择用户类型！',
              },
            ]}
          />
          <ProFormSelect
            width="sm"
            valueEnum={{
              0: { text: '否' },
              1: { text: '是' },
            }}
            name="islock"
            label="禁止登录"
            allowClear={false}
            rules={[
              {
                required: true,
                message: '请选择是否禁止登录！',
              },
            ]}
          />
        </ProForm.Group>
      </DrawerForm>

      <ModalForm
        title='用户角色设置'
        visible={showRoleForm}
        onVisibleChange={setShowRoleForm}
        submitter={{ searchConfig: { submitText: '提交' } }}
        modalProps={{
          destroyOnClose: true,
          className: styles.rolemodal,
          onOk: () => {
          },
          onCancel: () => {
          },
        }}
        onFinish={async (values) => {
          values.username = currentRow?.username;
          const result = await saveRole(values);
          if (result) {
            if (result.errorCode) {
              return false;
            }
            message.success('角色设置成功！');
          }
          return true;
        }}
        initialValues={{
          userRoleId: userRoles,
        }}
      >
        <p>{roleModalTitle}</p>
        <p>用户角色：</p>
        <ProFormCheckbox.Group name="userRoleId" options={roles} />
      </ModalForm>
    </>
  );
};

export default AdminList;
