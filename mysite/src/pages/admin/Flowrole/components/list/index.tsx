import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import type { ProDescriptionsItemProps } from '@ant-design/pro-descriptions';
import ProDescriptions from '@ant-design/pro-descriptions';
import React, { useImperativeHandle, useRef, useState, useEffect } from 'react';
import { flowrole, addFlowrole, updateFlowrole, removeFlowrole,getDict } from './service';
import type { TableListItem, TableListPagination, ErrorResponse } from './data';
import { Button, Drawer, message, Modal, Alert } from 'antd';
import { DrawerForm, ProFormColumnsType, ProFormInstance, ProForm, ProFormSelect, ProFormText } from '@ant-design/pro-components';
import { createFromIconfontCN, MinusOutlined, PlusOutlined } from '@ant-design/icons';
import browser from '@/utils/browser';
import DeptSel from '@/components/DepartmentTreeSelect';

export type ListProps = {
  onCreate?: (parentId: string, value: any) => void;
  onUpdate?: (id: string, name: string) => void;
  onDelete?: (ids: string[]) => void;
};

const IconFont = createFromIconfontCN({
  scriptUrl: '/icons/iconfont.js',
});

const List = React.forwardRef((props: ListProps, ref) => {
  const [currentRow, setCurrentRow] = useState<TableListItem>();
  const [selectedRowsState, setSelectedRows] = useState<TableListItem[]>([]);
  const actionRef = useRef<ActionType>();
  const [showForm, setShowForm] = useState<boolean>(false);
  const formRef = useRef<ProFormInstance>();
  const [responseState, setResponseState] = useState<ErrorResponse>();
  const [role, setRoleId] = useState<number>(0);
  const [levelDict, setLevelDict] = useState<any>();
  const [roleDict, setRoleDict] = useState<any>();
  const [companyDict, setCompanyDict] = useState<any>();
  const [appDict, setAppDict] = useState<any>();
  const deptRef = useRef();
  const sysuserRef = useRef();
  const wxuserRef = useRef();

  const handleRemove = async (selectedRows: TableListItem[], deleteRow: any) => {
    const hide = message.loading('正在删除');
    if (!selectedRows && !deleteRow) return true;

    try {
      let deleteIds = [];
      let result;
      if (selectedRows.length > 0) {
        deleteIds = selectedRows.map((row) => row.id.toString());
        result = await removeFlowrole({
          id: deleteIds,
        });
      } else if (deleteRow) {
        deleteIds = [deleteRow].map((row) => row.id.toString());
        result = await removeFlowrole({
          id: deleteIds,
        });
      }
      if (!result.errorMessage) {
        hide();
        message.success('删除成功');
        if (props.onDelete) {
          props.onDelete(deleteIds);
        }
      }
      return true;
    } catch (error) {
      hide();
      message.error('删除失败，请重试');
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
        actionRef.current?.reload?.();
      },
    });
  };

  const handleAddAndUpdate = async (updateRow: any, values: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      if (updateRow == undefined) {
        values.role = role;
        result = await addFlowrole({
          values,
        });
      } else {
        result = await updateFlowrole({
          id: updateRow.id,
          values,
        });
      }
      hide();
      // setResponseState(result);
      // if (!result.errorMessage) {
      //   if (props.onCreate) {
      //     if (updateRow == undefined) {
      //       const data = { title: values.name, key: result.lastid.toString(), isLeaf: true };
      //       props.onCreate(values.parentid.toString(), data);
      //     }
      //   }
      //   if (props.onUpdate) {
      //     if (updateRow && updateRow.name != values.name) {
      //       props.onUpdate(updateRow.id.toString(), values.name);
      //     }
      //   }
      // }
      return result;
    } catch (error) {
      message.success('保存失败！');
      return false;
    }
  };

  const clearSelected = () => {
    setSelectedRows([]);
    actionRef?.current?.clearSelected();
  };

  const columns: ProFormColumnsType<TableListItem>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInSearch: true,
      hideInForm: true,
      hideInDescriptions: true,
    },
    {
      title: '姓名',
      dataIndex: 'username',
    },
    {
      title: '角色',
      dataIndex: 'rolename',
      hideInSearch: true,
    },
    {
      title: '类别',
      dataIndex: 'type',
      valueEnum: {
        0: {
          text: '审批',
        },
        1: {
          text: '抄送',
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

  useImperativeHandle(ref, () => ({
    reload: (id: number) => {
      setRoleId(id);
      actionRef.current?.reload();
    },
  }));

  useEffect(() => {
    getDict({ id: -1, type: 'dict' }).then((res) => {
      setLevelDict(res.data.level);
      setRoleDict(res.data.role);
      setCompanyDict(res.data.company);
      setAppDict(res.data.app);
    }); 
    
  },[])
  return (
    <>
      <ProTable<TableListItem, TableListPagination>
        headerTitle="流程角色用户列表"
        actionRef={actionRef}
        rowKey="id"
        search={{
          labelWidth: 120,
        }}
        request={(params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          params.role = role;
          return flowrole(params);
        }}
        columns={columns as ProColumns<TableListItem>[]}
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
      <DrawerForm
        title="编辑流程角色用户信息"
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
          values.dept = deptRef?.current?.getCheckedKeys();
          values.userid = wxuserRef?.current?.getCheckedKeys();
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
          role:currentRow?.role?currentRow?.role:role,
          type:currentRow?.type,
          level:currentRow?.level?currentRow?.level?.split(","):undefined,
          company:currentRow?.company?currentRow?.company?.split(","):undefined,
          agent:currentRow?.agent?.split(",")
        }}
        layout="vertical"
        grid={true}
      >
      <ProForm.Group>
      <ProFormSelect
        width="md"
        valueEnum={roleDict}
        name="role"
        label="角色"
        allowClear={false}
        rules={[
          {
            required: true,
            message: '请选择角色！',
          },
        ]}
      />
      <ProFormSelect
        width="sm"
        valueEnum={{
          0: {
            text: '审批',
          },
          1: {
            text: '抄送',
          },
        }}
        name="type"
        label="类别"
        allowClear={false}
        rules={[
          {
            required: true,
            message: '请选择类别！',
          },
        ]}
      />
          </ProForm.Group>
          <ProForm.Group label="姓名">
            <DeptSel local={true} showLeafIcon={true} showAll={true} showUser={true} checkable={false} ref={wxuserRef} checkedKeys={currentRow?.userid||undefined} width='300px' showCheckedStrategy='SHOW_CHILD' />
        </ProForm.Group>
          <ProForm.Group label="部门">
            <DeptSel local={true} showLeafIcon={true} showAll={true} checkable={true} ref={deptRef} checkedKeys={currentRow?.dept?currentRow?.dept?.split(","):undefined} width='300px' showCheckedStrategy='SHOW_PARENT' />
          </ProForm.Group>
        <ProForm.Group label="职级">
        <ProFormSelect.SearchSelect
                  name="level"                  
                  fieldProps={{
                  labelInValue: false,
                  style: {
                      minWidth: 140,
                  },
                  }}
                  valueEnum={levelDict}
              />
        </ProForm.Group>
        <ProForm.Group label="主体">
        <ProFormSelect.SearchSelect
                  name="company"                  
                  fieldProps={{
                  labelInValue: false,
                  style: {
                      minWidth: 140,
                  },
                  }}
                  valueEnum={companyDict}
              />
        </ProForm.Group>
        <ProForm.Group label="应用">
        <ProFormSelect.SearchSelect
                  name="agent"                  
                  fieldProps={{
                  labelInValue: false,
                  style: {
                      minWidth: 140,
                  },
                  }}
                  valueEnum={appDict}
              />
        </ProForm.Group>
      </DrawerForm>
    </>
  );
});

export default List;
