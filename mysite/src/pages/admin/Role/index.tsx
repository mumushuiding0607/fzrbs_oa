import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useRef, useState } from 'react';
import { rule, addRule, updateRule, removeRule } from './service';
import { Button, message, Modal, Tabs } from 'antd';
import { ModalForm, PageContainer } from '@ant-design/pro-components';
import { ProFormText } from '@ant-design/pro-components';
import { MinusOutlined, PlusOutlined } from '@ant-design/icons';
import MyTree from '../Route/components/tree';
import UserTree from './components/UserTree';
import ChannelTree from '../../information/channel/components/tree';
import styles from './index.less';

const { TabPane } = Tabs;

const Role: React.FC = () => {
  const [currentRow, setCurrentRow] = useState<any>();
  const [selectedRowsState, setSelectedRows] = useState<any[]>([]);
  const actionRef = useRef<ActionType>();
  const [showForm, setShowForm] = useState<boolean>(false);
  const userTreeRef = useRef(undefined);
  const routeTreeRef = useRef(undefined);
  const channelTreeRef = useRef(undefined);
  const [userCheckedKeys, setUserCheckedKeys] = useState<any>([]);
  const [routeCheckedKeys, setRouteCheckedKeys] = useState<any>([]);
  const [channelCheckedKeys, setChannelCheckedKeys] = useState<any>([]);

  const handleRemove = async (selectedRows: any[], deleteRow: any) => {
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

  const deleteItem = (item: React.SetStateAction<any | undefined>) => {
    Modal.confirm({
      title: '删除',
      content: '确定要删除吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        await handleRemove([], item);
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
      return result;
    } catch (error) {
      message.success('保存失败！');
      return false;
    }
  };

  const resetModalFormData = () => {
    setUserCheckedKeys([]);
    setRouteCheckedKeys([]);
    setChannelCheckedKeys([]);
  };

  const clearSelected = () => {
    setSelectedRows([]);
    actionRef?.current?.clearSelected();
  };

  const columns: ProColumns<any>[] = [
    {
      title: '角色名称',
      dataIndex: 'name',
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
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      render: (_, entity) => [
        <a
          key="edit"
          onClick={() => {
            setCurrentRow(entity);
            if (entity.usernames != '') {
              const usernames = entity.usernames.split(',');
              setUserCheckedKeys(usernames);
            }
            if (entity.routes != '') {
              const routes = entity.routes.split(',');
              setRouteCheckedKeys(routes);
            }
            if (entity.channels != '') {
              const channels = entity.channels.split(',');
              setChannelCheckedKeys(channels);
            }
            setTimeout(() => {
              setShowForm(true);
            }, 200);
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
      ],
    },
  ];

  return (
    <PageContainer
      header={{
        breadcrumb: {},
      }}
    >
      <ProTable<any, any>
        headerTitle="角色列表"
        actionRef={actionRef}
        rowKey="id"
        search={false}
        request={(params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
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

      <ModalForm
        title="编辑角色信息"
        visible={showForm}
        onVisibleChange={setShowForm}
        submitter={{ searchConfig: { submitText: '提交' } }}
        modalProps={{
          destroyOnClose: true,
          className: styles.rolemodal,
          onOk: () => {
            resetModalFormData();
          },
          onCancel: () => {
            resetModalFormData();
          },
        }}
        onFinish={async (values) => {
          const usernames = userTreeRef?.current.getCheckedKeys();
          const usernameStr = usernames.filter((item: any) => item != '0').join(',');
          if (usernameStr != '') {
            values.usernames = usernameStr;
          }
          const routes = _.union(
            routeTreeRef?.current.getAllCheckedKeys(),
            routeTreeRef?.current.getCheckedKeys(),
          );
          const routeStr = routes.filter((item: any) => item != '0').join(',');
          if (routeStr != '') {
            values.routes = routeStr;
          }
          const channels = _.union(
            channelTreeRef?.current.getAllCheckedKeys(),
            channelTreeRef?.current.getCheckedKeys(),
          );
          const channelsStr = channels.filter((item: any) => item != '0').join(',');
          if (channelsStr != '') {
            values.channels = channelsStr;
          }
          const result = await handleAddAndUpdate(currentRow, values);
          if (result) {
            if (result.errorCode) {
              message.warn(result.errorMessage);
              return false;
            }
            message.success('保存成功！');
            actionRef.current?.reload?.();
            resetModalFormData();
          }
          return true;
        }}
        initialValues={currentRow}
      >
        <ProFormText
          name="name"
          label="名称"
          placeholder="请输入角色名称"
          rules={[
            {
              required: true,
              message: '请输入姓名！',
            },
          ]}
        />
        <Tabs defaultActiveKey="1">
          <TabPane tab="角色用户账号" key="1" forceRender>
            <UserTree
              checkable={true}
              selectable={false}
              checkStrictly={false}
              showLeafIcon={false}
              ref={userTreeRef}
              checkedKeys={userCheckedKeys}
            />
          </TabPane>
          <TabPane tab="角色路由菜单" key="2" forceRender>
            <MyTree
              checkable={true}
              selectable={false}
              checkStrictly={currentRow ? true : false}
              showLeafIcon={false}
              ref={routeTreeRef}
              checkedKeys={routeCheckedKeys}
            />
          </TabPane>
          <TabPane tab="企业微信平台栏目" key="3" forceRender>
            <ChannelTree
              checkable={true}
              selectable={false}
              checkStrictly={currentRow ? true : false}
              showLeafIcon={false}
              ref={channelTreeRef}
              checkedKeys={channelCheckedKeys}
            />
          </TabPane>
        </Tabs>
      </ModalForm>
    </PageContainer>
  );
};

export default Role;
