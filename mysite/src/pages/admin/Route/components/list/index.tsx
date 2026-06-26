import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import type { ProDescriptionsItemProps } from '@ant-design/pro-descriptions';
import ProDescriptions from '@ant-design/pro-descriptions';
import React, { useImperativeHandle, useRef, useState } from 'react';
import { rule, addRule, updateRule, removeRule } from './service';
import type { TableListItem, TableListPagination, ErrorResponse } from './data';
import { Button, Drawer, message, Modal, Alert } from 'antd';
import { BetaSchemaForm, ProFormColumnsType, ProFormInstance } from '@ant-design/pro-components';
import { createFromIconfontCN, MinusOutlined, PlusOutlined } from '@ant-design/icons';
import browser from '@/utils/browser';
import MyUploadFile from '@/components/MyUploadFile';

export type ListProps = {
  onCreate?: (parentId: string, value: any) => void;
  onUpdate?: (id: string, name: string) => void;
  onDelete?: (ids: string[]) => void;
};

const IconFont = createFromIconfontCN({
  scriptUrl: '/icons/iconfont.js',
});

const List = React.forwardRef((props: ListProps, ref) => {
  const [showDetail, setShowDetail] = useState<boolean>(false);
  const [currentRow, setCurrentRow] = useState<TableListItem>();
  const [selectedRowsState, setSelectedRows] = useState<TableListItem[]>([]);
  const actionRef = useRef<ActionType>();
  const [showForm, setShowForm] = useState<boolean>(false);
  const formRef = useRef<ProFormInstance>();
  const [responseState, setResponseState] = useState<ErrorResponse>();
  const [parentId, setParentId] = useState<number>(0);
  const [defaultImage, setDefaultImage] = useState<any[]>([]);
  const uploadRef = useRef();

  const handleRemove = async (selectedRows: TableListItem[], deleteRow: any) => {
    const hide = message.loading('正在删除');
    if (!selectedRows && !deleteRow) return true;

    try {
      let deleteIds = [];
      let result;
      if (selectedRows.length > 0) {
        deleteIds = selectedRows.map((row) => row.id.toString());
        result = await removeRule({
          id: deleteIds,
        });
      } else if (deleteRow) {
        deleteIds = [deleteRow].map((row) => row.id.toString());
        result = await removeRule({
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
        values.parentid = parentId;
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
      if (!result.errorMessage) {
        if (props.onCreate) {
          if (updateRow == undefined) {
            const data = { title: values.name, key: result.lastid.toString(), isLeaf: true };
            props.onCreate(values.parentid.toString(), data);
          }
        }
        if (props.onUpdate) {
          if (updateRow && updateRow.name != values.name) {
            props.onUpdate(updateRow.id.toString(), values.name);
          }
        }
      }
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
      hideInForm: true,
      hideInDescriptions: true,
    },
    {
      title: '名称',
      dataIndex: 'name',
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
      fieldProps: {
        placeholder: '请输入名称',
      },
      formItemProps: {
        rules: [
          {
            required: true,
            message: '请输入名称！',
          },
        ],
      },
      width: 'md',
      colProps: {
        xs: 24,
        md: 12,
      },
    },
    {
      title: '路由',
      dataIndex: 'path',
      fieldProps: {
        placeholder: '请输入路由',
      },
      formItemProps: {
        rules: [
          {
            required: true,
            message: '请输入路由！',
          },
        ],
      },
      width: 'md',
      colProps: {
        xs: 24,
        md: 12,
      },
    },
    {
      title: '图标',
      dataIndex: 'icon',
      render: (_, entity) => <IconFont type={entity.icon != '' ? entity.icon : 'icon-caidan'} />,
      fieldProps: {
        placeholder: '请输入图标名',
      },
      width: 'md',
      colProps: {
        xs: 24,
        md: 12,
      },
    },
    {
      title: '预设访问权限',
      dataIndex: 'access',
      hideInTable: true,
      valueEnum: {
        admin: {
          text: '管理员',
        },
        '': {
          text: '普通用户',
        },
      },
      fieldProps: {
        allowClear: false,
      },
      width: 'md',
      colProps: {
        xs: 24,
        md: 12,
      },
    },
    {
      title: '不展示子路由',
      dataIndex: 'hidechildreninmenu',
      hideInTable: true,
      valueEnum: {
        0: {
          text: '否',
        },
        1: {
          text: '是',
        },
      },
      fieldProps: {
        allowClear: false,
      },
      width: 'md',
      colProps: {
        xs: 24,
        md: 12,
      },
    },
    {
      title: '不展示在菜单',
      dataIndex: 'hideinmenu',
      hideInTable: true,
      valueEnum: {
        0: {
          text: '否',
        },
        1: {
          text: '是',
        },
      },
      fieldProps: {
        allowClear: false,
      },
      width: 'md',
      colProps: {
        xs: 24,
        md: 12,
      },
    },
    {
      title: '不展示在面包屑',
      dataIndex: 'hideinbreadcrumb',
      hideInTable: true,
      valueEnum: {
        0: {
          text: '否',
        },
        1: {
          text: '是',
        },
      },
      fieldProps: {
        allowClear: false,
      },
      width: 'md',
      colProps: {
        xs: 24,
        md: 12,
      },
    },
    {
      title: '不展示在顶栏',
      dataIndex: 'headerrender',
      hideInTable: true,
      valueEnum: {
        0: {
          text: '否',
        },
        1: {
          text: '是',
        },
      },
      fieldProps: {
        allowClear: false,
      },
      width: 'md',
      colProps: {
        xs: 24,
        md: 12,
      },
    },
    {
      title: '路由不展示菜单',
      dataIndex: 'menurender',
      hideInTable: true,
      valueEnum: {
        0: {
          text: '否',
        },
        1: {
          text: '是',
        },
      },
      fieldProps: {
        allowClear: false,
      },
      width: 'md',
      colProps: {
        xs: 24,
        md: 12,
      },
    },
    {
      title: '路由不展示菜单顶栏',
      dataIndex: 'menuheaderrender',
      hideInTable: true,
      valueEnum: {
        0: {
          text: '否',
        },
        1: {
          text: '是',
        },
      },
      fieldProps: {
        allowClear: false,
      },
      width: 'md',
      colProps: {
        xs: 24,
        md: 12,
      },
    },
    {
      title: '添加时间',
      dataIndex: 'inserttime',
      hideInForm: true,
    },
    {
      title: '配图',
      dataIndex: 'image',
      hideInTable: true,
      renderFormItem: (_, { type, defaultRender, formItemProps, fieldProps, ...rest }, form) => {
        return (
          <MyUploadFile
            name="upload"
            label=""
            max={1}
            multiple={false}
            accept="image/*"
            maxSize={1}
            listType="picture-card"
            defaultImage={defaultImage}
            uploadPath="icons"
            uploadType={1}
            ref={uploadRef}
          />
        );
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
            if (entity.image != '') {
              const image = {
                uid: entity.id.toString(),
                name: entity.name,
                status: 'done',
                url: entity.image,
                thumbUrl: entity.image,
              };
              setDefaultImage([image]);
            } else {
              setDefaultImage([]);
            }
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
      setParentId(id);
      actionRef.current?.reload();
    },
  }));

  return (
    <>
      <ProTable<TableListItem, TableListPagination>
        headerTitle="路由菜单列表"
        actionRef={actionRef}
        rowKey="id"
        search={false}
        request={(params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          params.parentid = parentId;
          return rule(params);
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
              setDefaultImage([]);
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
          column={2}
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
      <BetaSchemaForm<any>
        layoutType="DrawerForm"
        title="编辑路由菜单信息"
        width={browser.mobile() ? '100vw' : 600}
        visible={showForm}
        onVisibleChange={setShowForm}
        formRef={formRef}
        submitter={{ searchConfig: { submitText: '提交' } }}
        autoFocusFirstInput
        drawerProps={{
          destroyOnClose: true,
          onClose: () => {
            setResponseState(undefined);
          },
          extra: (
            <>
              {responseState?.errorCode && (
                <Alert
                  style={{
                    position: 'absolute',
                    top: 7,
                    right: 25,
                  }}
                  message={responseState?.errorMessage}
                  type="error"
                  closable={true}
                  showIcon
                />
              )}
            </>
          ),
        }}
        submitTimeout={2000}
        rowProps={{
          gutter: [16, 16],
        }}
        colProps={{
          span: 12,
        }}
        layout="vertical"
        grid={true}
        onFinish={async (values) => {
          setResponseState(undefined);
          if (values.upload && values.upload.length > 0) {
            values.image = values.upload[0].response.data.url;
          } else {
            const uploads = uploadRef?.current.getFileList();
            if (uploads.length > 0) {
              values.image = uploads[0].url;
            } else {
              values.image = '';
            }
          }
          delete values.upload;
          const result = await handleAddAndUpdate(currentRow, values);
          if (result) {
            if (result.errorCode) {
              setResponseState(result);
              return false;
            }
            message.success('保存成功！');
            actionRef.current?.reload?.();
          }
          return true;
        }}
        columns={columns}
        initialValues={{
          ...currentRow,
          hidechildreninmenu: currentRow ? currentRow.hidechildreninmenu + '' : '0',
          hideinmenu: currentRow ? currentRow.hideinmenu + '' : '0',
          hideinbreadcrumb: currentRow ? currentRow.hideinbreadcrumb + '' : '0',
          headerrender: currentRow ? currentRow.headerrender + '' : '0',
          menurender: currentRow ? currentRow.menurender + '' : '0',
          menuheaderrender: currentRow ? currentRow.menuheaderrender + '' : '0',
        }}
      />
    </>
  );
});

export default List;
