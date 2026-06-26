import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useRef, useState } from 'react';
import { rule, addRule, updateRule, removeRule } from './service';
import { Alert, Button, Drawer, message, Modal } from 'antd';
import {
  BetaSchemaForm,
  PageContainer,
  ProDescriptions,
  ProDescriptionsItemProps,
  ProFormColumnsType,
  ProFormInstance,
} from '@ant-design/pro-components';
import { MinusOutlined, PlusOutlined } from '@ant-design/icons';
import browser from '@/utils/browser';
import tools from '@/utils/tools';

const AppInterface: React.FC = () => {
  const [showDetail, setShowDetail] = useState<boolean>(false);
  const [currentRow, setCurrentRow] = useState<any>();
  const [selectedRowsState, setSelectedRows] = useState<any[]>([]);
  const actionRef = useRef<ActionType>();
  const [showForm, setShowForm] = useState<boolean>(false);
  const formRef = useRef<ProFormInstance>();
  const [responseState, setResponseState] = useState<any>();
  const [sid, setSid] = useState<string>('');

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
        values.sid = sid;
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

  const clearSelected = () => {
    setSelectedRows([]);
    actionRef?.current?.clearSelected();
  };

  const columns: ProColumns<any>[] = [
    {
      title: '企业号ID',
      dataIndex: 'corpid',
      hideInTable: true,
      copyable: true,
      fieldProps: {
        placeholder: '请输入企业号ID',
        readOnly: true,
      },
      formItemProps: {
        rules: [
          {
            required: true,
            message: '请输入企业号ID！',
          },
        ],
      },
    },
    {
      title: '应用名称',
      dataIndex: 'appname',
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
        placeholder: '请输入应用名称',
      },
      formItemProps: {
        rules: [
          {
            required: true,
            message: '请输入应用名称！',
          },
        ],
      },
    },
    {
      title: '应用ID',
      dataIndex: 'appid',
      fieldProps: {
        placeholder: '请输入应用ID',
      },
      formItemProps: {
        rules: [
          {
            required: true,
            message: '请输入企应用ID！',
          },
        ],
      },
    },
    {
      title: '应用秘钥',
      dataIndex: 'secret',
      hideInTable: true,
      fieldProps: {
        placeholder: '请输入应用秘钥',
      },
      formItemProps: {
        rules: [
          {
            required: true,
            message: '请输入应用秘钥！',
          },
        ],
      },
    },
    {
      title: '接口地址',
      dataIndex: 'url',
      copyable: true,
      fieldProps: {
        placeholder: '请输入接口地址',
        readOnly: true,
      },
      formItemProps: {
        rules: [
          {
            required: true,
            message: '请输入接口地址！',
          },
        ],
      },
    },
    {
      title: '接口Token',
      dataIndex: 'token',
      copyable: true,
      fieldProps: {
        placeholder: '请输入接口Token',
        readOnly: true,
      },
      formItemProps: {
        rules: [
          {
            required: true,
            message: '请输入接口Token！',
          },
        ],
      },
    },
    {
      title: 'EncodingAESKey',
      dataIndex: 'encodingaeskey',
      copyable: true,
      hideInTable: true,
      fieldProps: {
        placeholder: '请输入应用EncodingAESKey',
      },
      formItemProps: {
        rules: [
          {
            required: true,
            message: '请输入应用EncodingAESKey！',
          },
        ],
      },
    },
    {
      title: '添加时间',
      dataIndex: 'inserttime',
      hideInForm: true,
    },
    {
      title: '应用标识',
      dataIndex: 'sid',
      hideInForm: true,
      hideInDescriptions: true,
      hideInTable: true,
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

  return (
    <PageContainer
      header={{
        breadcrumb: {},
      }}
    >
      <ProTable<any, any>
        headerTitle="应用列表"
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
              setSid(tools.md5String());
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
        <ProDescriptions<any>
          column={1}
          title="详情"
          request={async () => ({
            data: currentRow || {},
          })}
          params={{
            id: currentRow?.id,
          }}
          columns={columns as ProDescriptionsItemProps<any>[]}
        />
      </Drawer>
      <BetaSchemaForm<any>
        layoutType="DrawerForm"
        title="编辑接口信息"
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
        columns={columns as ProFormColumnsType<any>}
        initialValues={{
          ...currentRow,
          token: currentRow ? currentRow.token + '' : tools.md5String(),
          corpid: currentRow ? currentRow.corpid + '' : 'ww36092db762bf3430',
          sid: currentRow ? currentRow.sid + '' : sid,
          url: currentRow
            ? currentRow.url + ''
            : 'https://fzrb.fznews.com.cn/index.php?r=qiyehao/qyweixin/interface&sid=' + sid,
        }}
      />
    </PageContainer>
  );
};

export default AppInterface;
