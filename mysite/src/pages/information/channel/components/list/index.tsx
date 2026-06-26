import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useImperativeHandle, useRef, useState } from 'react';
import { rule, addRule, updateRule, removeRule, cut, copy } from './service';
import { Button, message, Modal, Alert } from 'antd';
import {
  DrawerForm,
  ProForm,
  ProFormColumnsType,
  ProFormInstance,
  ProFormSelect,
  ProFormText,
  ProFormTextArea,
} from '@ant-design/pro-components';
import { CopyOutlined, MinusOutlined, PlusOutlined, ScissorOutlined } from '@ant-design/icons';
import browser from '@/utils/browser';
import MyUploadFile from '@/components/MyUploadFile';
import ChannelModal from '@/pages/information/components/ChannelModal';

export type ListProps = {
  onCreate?: (parentId: string, value: any) => void;
  onUpdate?: (id: string, name: string) => void;
  onDelete?: (ids: string[]) => void;
};

const List = React.forwardRef((props: ListProps, ref) => {
  const [currentRow, setCurrentRow] = useState<any>();
  const [selectedRowsState, setSelectedRows] = useState<any[]>([]);
  const actionRef = useRef<ActionType>();
  const [showForm, setShowForm] = useState<boolean>(false);
  const formRef = useRef<ProFormInstance>();
  const [responseState, setResponseState] = useState<API.ErrorResponse>();
  const [parentId, setParentId] = useState<number>(0);
  const [defaultImage, setDefaultImage] = useState<any[]>([]);
  const uploadRef = useRef();
  const channelModalRef = useRef<any>();
  const [action, setAction] = useState('');

  const handleRemove = async (selectedRows: any[], deleteRow: any) => {
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
      message.warn('保存失败！');
      return false;
    }
  };

  const clearSelected = () => {
    setSelectedRows([]);
    actionRef?.current?.clearSelected();
  };

  const cutModalOk = async (ids: number[]) => {
    if (ids.length > 0) {
      const infoIds: number[] = [];
      selectedRowsState.forEach((item) => {
        infoIds.push(item.id);
      });
      const params = {
        fromChannelId: parentId,
        toChannelId: ids.join(','),
        infoIds: infoIds.join(','),
      };
      let result;
      if (action == 'cut') {
        result = await cut(params);
      } else if (action == 'copy') {
        result = await copy(params);
      }
      if (result.errorCode) {
        message.warn(result.errorMessage);
      } else {
        message.success((action == 'cut' ? '移动' : '复制') + '成功');
        setSelectedRows([]);
        actionRef.current?.reloadAndRest?.();
        channelModalRef?.current.setVisible(false);
      }
    }
  };

  const cutModalCancel = () => {
    actionRef.current?.clearSelected?.();
  };

  const columns: ProFormColumnsType<any>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
    },
    {
      title: '名称',
      dataIndex: 'name',
    },
    {
      title: '添加时间',
      dataIndex: 'inserttime',
      hideInForm: true,
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
      <ProTable<any, any>
        headerTitle="栏目列表"
        actionRef={actionRef}
        rowKey="id"
        search={false}
        request={(params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          params.parentid = parentId;
          return rule(params);
        }}
        columns={columns as ProColumns<any>[]}
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
            key="copy"
            onClick={() => {
              if (selectedRowsState.length == 0) {
                message.warn('请先选择要复制的项目');
                return;
              }
              setAction('copy');
              channelModalRef?.current.setVisible(true);
            }}
          >
            <CopyOutlined /> 复制
          </Button>,
          <Button
            type="primary"
            key="cut"
            onClick={() => {
              if (selectedRowsState.length == 0) {
                message.warn('请先选择要移动的项目');
                return;
              }
              setAction('cut');
              channelModalRef?.current.setVisible(true);
            }}
          >
            <ScissorOutlined /> 移动
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
        title="编辑栏目信息"
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
              return false;
            }
            message.success('保存成功！');
            actionRef.current?.reload?.();
          }
          return true;
        }}
        initialValues={{
          ...currentRow,
          navshow: currentRow ? currentRow.navshow + '' : '1',
          display: currentRow ? currentRow.display + '' : '1',
        }}
        layout="vertical"
        grid={true}
      >
        {responseState?.errorCode && (
          <Alert message={responseState?.errorMessage} type="warning" closable={true} showIcon />
        )}
        <ProForm.Group>
          <ProFormText
            name="name"
            width="sm"
            label="栏目名称"
            placeholder="请输入栏目名称"
            rules={[
              {
                required: true,
                message: '请输入栏目名称！',
              },
            ]}
            colProps={{ md: 12, xl: 24 }}
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormText
            name="linkurl"
            width="sm"
            label="链接地址"
            placeholder="请输入链接地址"
            rules={[
              {
                type: 'url',
                message: '请输入正确的网址格式！',
              },
            ]}
            colProps={{ md: 12, xl: 24 }}
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormTextArea colProps={{ md: 12, xl: 24 }} label="栏目说明" name="content" />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormSelect
            width="sm"
            valueEnum={{
              0: { text: '否' },
              1: { text: '是' },
            }}
            name="navshow"
            label="导航显示"
            allowClear={false}
            colProps={{ md: 12, xl: 12 }}
          />
          <ProFormSelect
            width="sm"
            valueEnum={{
              0: { text: '否' },
              1: { text: '是' },
            }}
            name="display"
            label="后台显示"
            allowClear={false}
            colProps={{ md: 12, xl: 12 }}
          />
        </ProForm.Group>
        <MyUploadFile
          name="upload"
          label="栏目图片"
          max={1}
          multiple={false}
          accept="image/*"
          maxSize={1}
          listType="picture-card"
          defaultImage={defaultImage}
          uploadPath="information"
          uploadType={1}
          ref={uploadRef}
        />
      </DrawerForm>
      <ChannelModal
        ref={channelModalRef}
        type="info"
        action={action}
        onOk={cutModalOk}
        onCancel={cutModalCancel}
      />
    </>
  );
});

export default List;
