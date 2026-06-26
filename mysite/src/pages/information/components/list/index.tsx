import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useEffect, useImperativeHandle, useRef, useState } from 'react';
import { rule, removeRule, one, cut, sort, top, issued, revoke, extendToolBar, sendNotice } from './service';
import { Button, Drawer, message, Modal } from 'antd';
import { ArrowDownOutlined, ArrowUpOutlined, MenuOutlined, MinusOutlined, PlusOutlined, ScissorOutlined, SendOutlined, VerticalAlignMiddleOutlined } from '@ant-design/icons';
import EditForm from '../edit';
import { ProFormColumnsType } from '@ant-design/pro-components';
import ChannelModal from '../ChannelModal';
import { arrayMoveImmutable } from 'array-move';
import type { SortableContainerProps, SortEnd } from 'react-sortable-hoc';
import { SortableContainer, SortableElement, SortableHandle } from 'react-sortable-hoc';
import styles from './index.less';


const DragHandle = SortableHandle(() => <MenuOutlined style={{ cursor: 'grab', color: '#999' }} />);

const SortableItem = SortableElement((props: React.HTMLAttributes<HTMLTableRowElement>) => (
  <tr {...props} />
));
const SortableBody = SortableContainer((props: React.HTMLAttributes<HTMLTableSectionElement>) => (
  <tbody {...props} />
));

const List = React.forwardRef((props: any, ref) => {
  const [currentRow, setCurrentRow] = useState<any>();
  const [selectedRowsState, setSelectedRows] = useState<any[]>([]);
  const actionRef = useRef<ActionType>();
  const editFormRef = useRef<any>();
  const channelModalRef = useRef<any>();
  const [channelId, setChannelId] = useState<number>(0);
  const [infoId, setInfoId] = useState<number>(0);
  const [searchFlag, setSearchFlag] = useState<boolean>(false);
  const [preview, setPreview] = useState(false);
  const [dataSource, setDataSource] = useState([]);
  const [noticeId, setNoticeId] = useState<number[]>([]);
  const [customField, setCustomField] = useState<any>(undefined);

  const handleRemove = async (selectedRows: any[], deleteRow: any) => {
    const hide = message.loading('正在删除');
    if (!selectedRows && !deleteRow) return true;

    try {
      let deleteIds = [];
      let result;
      if (selectedRows.length > 0) {
        deleteIds = selectedRows.map((row) => row.id);
        result = await removeRule({
          id: deleteIds,
        });
      } else if (deleteRow) {
        deleteIds = [deleteRow].map((row) => row.id);
        result = await removeRule({
          id: deleteIds,
        });
      }
      if (!result.errorMessage) {
        hide();
        message.success('删除成功');
      }
      return true;
    } catch (error) {
      hide();
      message.error('删除失败，请重试');
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

  const editItem = (id: number) => {
    setInfoId(id);
    editFormRef?.current.setVisible(true);
  };

  const clearSelected = () => {
    setSelectedRows([]);
    actionRef?.current?.clearSelected();
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

  const setRowClassName = (record: any) => {
    return record.displayorder == 1 ? styles.topRow : '';
  }

  const columns: ProFormColumnsType<any>[] = [
    {
      title: '排序',
      dataIndex: 'sort',
      width: 50,
      className: styles.dragvisible,
      render: () => <DragHandle />,
      hideInSearch: true,
    },
    {
      title: '标题',
      dataIndex: 'title',
      key: 'searchtitle',
      render: (dom, entity) => {
        return (
          <a
            onClick={async () => {
              const info = await one({ id: entity.id, flag: 'preview' });
              setCurrentRow(info.data);
              setPreview(true);
            }}
            dangerouslySetInnerHTML={{ __html: entity.title }}
          />
        );
      },
    },
    {
      title: '编辑姓名',
      dataIndex: 'editor',
      key: 'searcheditor',
    },
    {
      title: '浏览量',
      dataIndex: 'click',
      hideInSearch: true,
    },
    {
      title: '发布时间',
      dataIndex: 'publictime',
      hideInSearch: true,
    },
    {
      title: '添加时间',
      dataIndex: 'inserttime',
      valueType: 'dateRange',
      key: 'inserttime',
      render: (_, entity) => {
        return entity.inserttime;
      },
    },
    {
      title: '状态',
      dataIndex: 'state',
      valueEnum: {
        0: {
          text: '未签',
          status: 'Default',
        },
        1: {
          text: '已签',
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
          onClick={async () => {
            editItem(entity.id);
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

  const reloadListData = () => {
    setTimeout(() => {
      actionRef.current?.reload?.();
    }, 200);
  };

  const cutModalOk = async (ids: number[]) => {
    if (ids.length > 0) {
      const infoIds: number[] = [];
      selectedRowsState.forEach((item) => {
        infoIds.push(item.id);
      });
      const result = await cut({
        fromChannelId: channelId,
        toChannelId: ids[0],
        infoIds: infoIds.join(','),
      });
      if (result.errorCode) {
        message.warn(result.errorMessage);
      } else {
        message.success('移动成功');
        setSelectedRows([]);
        actionRef.current?.reloadAndRest?.();
        channelModalRef?.current.setVisible(false);
      }
    }
  };

  const cutModalCancel = () => {
    actionRef.current?.clearSelected?.();
  };

  const createToolBar = () => {
    const toolBar = [<Button
      type="primary"
      key="new"
      onClick={() => {
        editItem(0);
      }}
    >
      <PlusOutlined /> 新建
    </Button>,
    <Button
      type="primary"
      key="cut"
      onClick={() => {
        if (selectedRowsState.length == 0) {
          message.warn('请先选择要移动的信息');
          return;
        }
        channelModalRef?.current.setVisible(true);
      }}
    >
      <ScissorOutlined /> 移动
    </Button>,
    <Button
      type="primary"
      key="revoke"
      onClick={async () => {
        if (selectedRowsState.length == 0) {
          message.warn('请选择要操作的项目！');
          return;
        }
        Modal.confirm({
          title: '系统提示',
          content: '确定撤销选中的项目吗？',
          okText: '确认',
          cancelText: '取消',
          onOk: async () => {
            const ids = selectedRowsState.map((row) => row.id);
            const params = {
              ids: ids.join(','),
            };
            const result = await revoke(params);
            if (!result.errorMessage) {
              message.success('操作成功');
              clearSelected();
              setSelectedRows([]);
              actionRef.current?.reload?.();
            }
          },
          onCancel: async () => clearSelected(),
        });
      }}
    >
      <ArrowDownOutlined /> 撤销
    </Button>,
    <Button
      type="primary"
      key="issued"
      onClick={async () => {
        if (selectedRowsState.length == 0) {
          message.warn('请选择要操作的项目！');
          return;
        }
        Modal.confirm({
          title: '系统提示',
          content: '确定签发选中的项目吗？',
          okText: '确认',
          cancelText: '取消',
          onOk: async () => {
            const ids = selectedRowsState.map((row) => row.id);
            const params = {
              ids: ids.join(','),
            };
            const result = await issued(params);
            if (!result.errorMessage) {
              message.success('操作成功');
              clearSelected();
              setSelectedRows([]);
              actionRef.current?.reload?.();
            }
          },
          onCancel: async () => clearSelected(),
        });
      }}
    >
      <ArrowUpOutlined /> 签发
    </Button>,
    <Button
      type="primary"
      key="top"
      onClick={async () => {
        if (selectedRowsState.length == 0) {
          message.warn('请选择要操作的项目！');
          return;
        }
        Modal.confirm({
          title: '系统提示',
          content: '确定置顶/取消置顶选中的项目吗？',
          okText: '确认',
          cancelText: '取消',
          onOk: async () => {
            const ids = selectedRowsState.map((row) => row.id);
            const result = await top({ ids });
            if (!result.errorMessage) {
              message.success('操作成功');
            }
            clearSelected();
            setSelectedRows([]);
            actionRef.current?.reload?.();
          },
          onCancel: async () => clearSelected(),
        });
      }}
    >
      <VerticalAlignMiddleOutlined /> 置顶/取消
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
    </Button>];
    if (noticeId.includes(parseInt(channelId.toString()))) {
      toolBar.push(<Button
        type="primary"
        key="notice"
        onClick={async () => {
          Modal.confirm({
            title: '系统提示',
            content: '确定向微信企业应用发送新信息提醒吗？',
            okText: '确认',
            cancelText: '取消',
            onOk: async () => {
              const result = await sendNotice({ channelId: channelId });
              if (!result.errorMessage) {
                message.success('发送成功');
              } else {
                message.success(result.errorMessage);
              }
            },
          });
        }}
      >
        <SendOutlined /> 发送通知
      </Button>);
    }
    return toolBar;
  }

  useImperativeHandle(ref, () => ({
    reload: (id: number) => {
      setChannelId(id);
      actionRef.current?.reload();
    },
  }));

  useEffect(() => {
    extendToolBar().then((data: any) => {
      if (data?.data.notice) {
        setNoticeId(data?.data.notice);
      }
      if (data?.data.customfield) {
        setCustomField(data?.data.customfield);
      }
    });
  }, []);

  return (
    <>
      <ProTable<any, any>
        headerTitle="信息列表"
        actionRef={actionRef}
        rowKey="id"
        rowClassName={setRowClassName}
        components={{
          body: {
            wrapper: DraggableContainer,
            row: DraggableBodyRow,
          },
        }}
        dataSource={dataSource}
        search={{
          optionRender: (searchConfig, { form }, dom) => [
            <Button
              key="resetText"
              onClick={() => {
                setSearchFlag(false);
                form?.resetFields();
                form?.submit();
              }}
            >
              {searchConfig.resetText}
            </Button>,
            <Button
              key="searchText"
              type="primary"
              onClick={() => {
                setSearchFlag(true);
                form?.submit();
              }}
            >
              {searchConfig.searchText}
            </Button>,
          ],
        }}
        request={async (params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          params.channelid = channelId;
          if (searchFlag) {
            params.search = 1;
          }
          const result = await rule(params)
          setDataSource(result.data);
          return result;
        }}
        columns={columns as ProColumns<any>[]}
        rowSelection={{
          onChange: (_, selectedRows) => {
            setSelectedRows(selectedRows);
          },
        }}
        tableAlertRender={false}
        toolBarRender={() => createToolBar()}
      />
      <EditForm id={infoId} channelId={channelId} ref={editFormRef} reload={reloadListData} customField={customField} />
      <ChannelModal
        ref={channelModalRef}
        type="info"
        action="cut"
        onOk={cutModalOk}
        onCancel={cutModalCancel}
      />
      {currentRow && (
        <Drawer
          title="内容预览"
          width="100vw"
          visible={preview}
          onClose={() => {
            setPreview(false);
          }}
          closable={true}
        >
          <h1 dangerouslySetInnerHTML={{ __html: currentRow.title }} />
          <div dangerouslySetInnerHTML={{ __html: currentRow.content }} />
        </Drawer>
      )}
    </>
  );
});

export default List;
