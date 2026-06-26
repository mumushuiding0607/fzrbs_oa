import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import type { ProDescriptionsItemProps } from '@ant-design/pro-descriptions';
import ProDescriptions from '@ant-design/pro-descriptions';
import React, { useRef, useState } from 'react';
import { appAuthList, addAppAuth, updateAppAuth, removeAppAuth } from './service';
import type { TableListItem, TableListPagination, ErrorResponse } from './data';
import { Button, Drawer, message, Modal, Alert, Space, Select, Spin } from 'antd';
import type { ProFormInstance } from '@ant-design/pro-components';
import { DrawerForm, ProForm, ProFormSelect, ProFormText } from '@ant-design/pro-components';
import { PlusOutlined } from '@ant-design/icons';
import browser from '@/utils/browser';
import DeptSel from '@/components/DepartmentTreeSelect';

type AppAuthProps = {
  agentid?:number,
  modulesArr?:any,
  actionsArr?:any
};
const AppAuth: React.FC<AppAuthProps> = (AppAuthProps) => {
  const [showDetail, setShowDetail] = useState<boolean>(false);
  const [currentRow, setCurrentRow] = useState<TableListItem>();
  const [selectedRowsState, setSelectedRows] = useState<TableListItem[]>([]);
  const actionRef = useRef<ActionType>();
  const [showForm, setShowForm] = useState<boolean>(false);
  const formRef = useRef<ProFormInstance>();
  const [responseState, setResponseState] = useState<ErrorResponse>();
  const deptRef = useRef();
  const sysuserRef = useRef();
  const wxuserRef = useRef();
  const params = {agentid:AppAuthProps.agentid};


  const handleRemove = async (selectedRows: TableListItem[], deleteRow: any) => {
    const hide = message.loading('正在删除');
    if (!selectedRows && !deleteRow) return true;

    try {
      if (selectedRows.length > 0) {
        await removeAppAuth({
          id: selectedRows.map((row) => row.id),
        });
      } else if (deleteRow) {
        await removeAppAuth({
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
        result = await addAppAuth({
          values,
        });
      } else {
        result = await updateAppAuth({
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


  const columns: ProColumns<TableListItem>[] = [
    {
      title: '权限名',
      dataIndex: 'authName',
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
      title: '系统用户',
      dataIndex: 'sysusers',
    },
    {
      title: '企业微信用户',
      dataIndex: 'wxusers',
    },
    {
      title: '模块',
      dataIndex: 'modules',
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
      ],
    },
  ];

  return (
    <>
      <ProTable<TableListItem, TableListPagination>
        headerTitle="配置列表"
        actionRef={actionRef}
        rowKey="id"
        search={{
          labelWidth: 120,
        }}
        params={params}
        request={appAuthList}
        columns={columns}
        rowSelection={{
          onChange: (_, selectedRows) => {
            setSelectedRows(selectedRows);
          },
        }}
        tableAlertRender={({ selectedRowKeys, selectedRows, onCleanSelected }) =>
          selectedRowsState.length > 0 && (
            <Space size={24}>
              <span>已选 {selectedRowKeys.length} 项</span>
              <span>
                <a style={{ marginLeft: 8 }} onClick={onCleanSelected}>
                  取消选择
                </a>
              </span>
            </Space>
          )
        }
        tableAlertOptionRender={() => {
          return (
            <Button
              type="primary"
              onClick={async () => {
                Modal.confirm({
                  title: '批量删除',
                  content: '确定删除选中的项目吗？',
                  okText: '确认',
                  cancelText: '取消',
                  onOk: async () => {
                    await handleRemove(selectedRowsState, undefined);
                    setSelectedRows([]);
                    actionRef.current?.reload?.();
                  },
                });
              }}
            >
              批量删除
            </Button>
          );
        }}
        toolBarRender={() => [
          <Button
            type="primary"
            key="primary"
            onClick={() => {
              setCurrentRow(undefined);
              setShowForm(true);
            }}
          >
            <PlusOutlined /> 添加权限
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
        {currentRow?.id && (
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
        )}
      </Drawer>
      <DrawerForm
        title="编辑权限配置信息"
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
          values.departments = deptRef?.current?.getCheckedKeys();
          values.sysusers = sysuserRef?.current?.getCheckedKeys();
          values.wxusers = wxuserRef?.current?.getCheckedKeys();
          values.agentid = AppAuthProps.agentid;
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
          modules:currentRow?.modules?currentRow?.modules?.split(","):undefined,
          actions:currentRow?.actions?currentRow?.actions?.split(","):undefined
        }}
        layout="vertical"
        grid={true}
      >
          <ProForm.Group label="权限名">
          <ProFormText
            name="authName"
            width="sm"
          />
          </ProForm.Group>
          <ProForm.Group label="系统用户">
            <DeptSel local={true} showLeafIcon={true} showAll={true} showUser={true} checkable={true} ref={sysuserRef} checkedKeys={currentRow?.sysusers?currentRow?.sysusers?.split(","):undefined} width='300px' showCheckedStrategy='SHOW_CHILD' />
        </ProForm.Group>
          <ProForm.Group label="企业微信用户">
            <DeptSel local={true} showLeafIcon={true} showAll={true} showUser={true} checkable={true} ref={wxuserRef} checkedKeys={currentRow?.wxusers?currentRow?.wxusers?.split(","):undefined} width='300px' showCheckedStrategy='SHOW_CHILD' />
        </ProForm.Group>
        <ProForm.Group label="模块">
        <ProFormSelect.SearchSelect
                  name="modules"                  
                  fieldProps={{
                  labelInValue: false,
                  style: {
                      minWidth: 140,
                  },
                  }}
                  valueEnum={AppAuthProps.modulesArr}
              />
        </ProForm.Group>
        <ProForm.Group label="操作">
        <ProFormSelect.SearchSelect
                  name="actions"                  
                  fieldProps={{
                  labelInValue: false,
                  style: {
                      minWidth: 140,
                  },
                  }}
                  valueEnum={AppAuthProps.actionsArr}
              />
        </ProForm.Group>
          <ProForm.Group label="部门">
            <DeptSel local={true} showLeafIcon={true} showAll={true} checkable={true} ref={deptRef} checkedKeys={currentRow?.departments?currentRow?.departments?.split(","):undefined} width='300px' showCheckedStrategy='SHOW_PARENT' />
          </ProForm.Group>
      </DrawerForm>
    </>
  );
};

export default AppAuth;
