import {
  ActionType,
  DrawerForm,
  ProColumns,
  ProForm,
  ProFormInstance,
  ProFormSelect,
  ProFormText,
  ProTable,
} from '@ant-design/pro-components';
import { Alert, Button, message, Modal } from 'antd';
import React, { useRef, useState } from 'react';
import { groupRule, asynchronizationGroup, updateGroup, sort } from './service';
import browser from '@/utils/browser';
import MyUploadFile from '@/components/MyUploadFile';
import { ArrowDownOutlined, ArrowUpOutlined, CloudDownloadOutlined, MenuOutlined } from '@ant-design/icons';
import { arrayMoveImmutable } from 'array-move';
import type { SortableContainerProps, SortEnd } from 'react-sortable-hoc';
import { SortableContainer, SortableElement, SortableHandle } from 'react-sortable-hoc';
import styles from '../../information/components/list/index.less';

const DragHandle = SortableHandle(() => <MenuOutlined style={{ cursor: 'grab', color: '#999' }} />);

const SortableItem = SortableElement((props: React.HTMLAttributes<HTMLTableRowElement>) => (
  <tr {...props} />
));
const SortableBody = SortableContainer((props: React.HTMLAttributes<HTMLTableSectionElement>) => (
  <tbody {...props} />
));

const GroupTab: React.FC = () => {
  const [showForm, setShowForm] = useState<boolean>(false);
  const [currentRow, setCurrentRow] = useState<any>();
  const actionRef = useRef<ActionType>();
  const [defaultImage, setDefaultImage] = useState<any[]>([]);
  const formRef = useRef<ProFormInstance>();
  const [responseState, setResponseState] = useState<API.ErrorResponse>();
  const [selectedRowsState, setSelectedRows] = useState<any[]>([]);
  const [dataSource, setDataSource] = useState([]);

  const handleAddAndUpdate = async (updateRow: any, values: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      result = await updateGroup({
        id: updateRow.id,
        values,
      });
      hide();
      setResponseState(result);
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

  const changeStatus = async (status) => {
    if (selectedRowsState.length == 0) {
      message.warn('请选择要操作的项目！');
      return;
    }
    const title = status == 0 ? '下线' : '上线';
    Modal.confirm({
      title: '系统提示',
      content: '确定' + title + '选中的项目吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        let updateIds = [];
        updateIds = selectedRowsState.map((row) => row.id);
        await updateMenuStatus({ id: updateIds, values: { status } });
        clearSelected();
        actionRef.current?.reload?.();
      },
      onCancel: async () => clearSelected(),
    });
  };

  const onSortEnd = ({ oldIndex, newIndex }: SortEnd) => {
    if (oldIndex !== newIndex) {
      const newData = arrayMoveImmutable(dataSource.slice(), oldIndex, newIndex).filter(
        (el: any) => !!el,
      );
      // console.log('oldIndex: ', oldIndex);
      // console.log('newIndex: ', newIndex);
      // console.log('Sorted items: ', newData);
      let point = '';
      let targetId = 0;
      let updateId = 0;
      if (newIndex < oldIndex) {
        point = 'top';
        targetId = dataSource[newIndex].id;
        updateId = dataSource[oldIndex].id;
      } else {
        point = 'bottom';
        targetId = dataSource[oldIndex].id;
        updateId = dataSource[newIndex].id;
      }
      sort({ point, targetId, updateId });
      setDataSource(newData);
    }
  };

  const DraggableContainer = (props: SortableContainerProps) => (
    <SortableBody
      useDragHandle
      disableAutoscroll
      helperClass={styles.rowdragging}
      onSortEnd={onSortEnd}
      {...props}
    />
  );

  const DraggableBodyRow: React.FC<any> = ({ className, style, ...restProps }) => {
    // function findIndex base on Table rowKey props and should always be a right array index
    const index = dataSource.findIndex(x => x.id === restProps['data-row-key']);
    return <SortableItem index={index} {...restProps} className={className} />;
  };

  const columns: ProColumns<any>[] = [
    {
      title: '排序',
      dataIndex: 'sort',
      width: 50,
      className: styles.dragvisible,
      render: () => <DragHandle />,
      hideInSearch: true,
    },
    {
      title: '分组名称',
      dataIndex: 'name',
    },
    {
      title: '商品数量',
      dataIndex: 'item_num',
      hideInSearch: true,
    },
    {
      title: '创建时间',
      dataIndex: 'created',
      hideInSearch: true,
    },
    {
      title: '是否显示',
      dataIndex: 'status',
      valueEnum: {
        0: {
          text: '否',
          status: 'Default',
        },
        1: {
          text: '是',
          status: 'Success',
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
            if (entity.icon != '') {
              const url = entity.icon;
              const image = {
                uid: entity.id.toString(),
                name: entity.name,
                status: 'done',
                url: url,
                thumbUrl: url,
              };
              setDefaultImage([image]);
            } else {
              setDefaultImage([]);
            }
            clearSelected();
            setShowForm(true);
          }}
        >
          修改
        </a>,
      ],
    },
  ];

  return (
    <>
      <ProTable<any, any>
        headerTitle="商品分组列表"
        actionRef={actionRef}
        rowKey="id"
        components={{
          body: {
            wrapper: DraggableContainer,
            row: DraggableBodyRow,
          },
        }}
        dataSource={dataSource}
        request={async (params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          params = { ...params, sorter, filter };
          const result = await groupRule(params)
          setDataSource(result.data);
          return result;
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
            key="synchronization"
            onClick={async () => {
              const hide = message.loading('正在同步...', 0);
              const result = await asynchronizationGroup();
              hide();
              if (result.errorMessage) {
                message.warn(result.errorMessage);
              } else {
                message.success('同步成功');
                actionRef?.current?.reload()
              }
            }}
          >
            <CloudDownloadOutlined /> 同步好物福州商品分组
          </Button>,
          <Button
            type="primary"
            key="online"
            onClick={async () => {
              changeStatus(1);
            }}
          >
            <ArrowUpOutlined /> 显示
          </Button>,
          <Button
            type="primary"
            key="offline"
            onClick={async () => {
              changeStatus(0);
            }}
          >
            <ArrowDownOutlined /> 隐藏
          </Button>,
        ]}
      />
      <DrawerForm
        title="编辑商品分组信息"
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
            values.icon = values.upload[0].response.data.url;
          } else {
            if (!currentRow) {
              values.icon = '';
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
          status: currentRow ? currentRow.status + '' : '1',
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
            label="分组名称"
            placeholder="请输入分组名称"
            rules={[
              {
                required: true,
                message: '请输入分组名称！',
              },
            ]}
            colProps={{ md: 12, xl: 24 }}
            readonly={true}
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormSelect
            name="status"
            label="是否显示"
            valueEnum={{
              0: '否',
              1: '是',
            }}
            colProps={{ md: 12, xl: 8 }}
            placeholder="请选择状态"
            rules={[
              {
                required: true,
                message: '请选择状态！',
              },
            ]}
          />
        </ProForm.Group>
        <MyUploadFile
          name="upload"
          label="图标"
          max={1}
          multiple={false}
          accept="image/*"
          maxSize={3}
          listType="picture-card"
          defaultImage={defaultImage}
          uploadPath="information"
          uploadType={1}
        />
      </DrawerForm>
    </>
  );
};

export default GroupTab;
