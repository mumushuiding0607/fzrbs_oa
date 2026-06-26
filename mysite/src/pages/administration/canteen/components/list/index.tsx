import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useEffect, useImperativeHandle, useRef, useState } from 'react';
import { rule, type, removeRule } from './service';
import { Button, message, Image, Modal } from 'antd';
import {
  CloudDownloadOutlined,
  DollarOutlined,
  ExportOutlined,
  MinusOutlined,
  ScissorOutlined,
} from '@ant-design/icons';
import DepartmentModal from '../DepartmentModal';
import RechargeModal from '../RechargeModal';
import UserTypeModal from '../UserTypeModal';
import BalanceExportModal from '../BalanceExportModal';

export type ListProps = {
  onSynchronization?: () => void;
};

const List = React.forwardRef((props: ListProps, ref) => {
  const [currentRow, setCurrentRow] = useState<any>();
  const [selectedRowsState, setSelectedRows] = useState<any[]>([]);
  const actionRef = useRef<ActionType>();
  const [departmentId, setDepartmentId] = useState<number>(0);
  const [searchFlag, setSearchFlag] = useState<boolean>(false);
  const [userType, setUserType] = useState<any>({});
  const modalRef = useRef<any>();
  const modalRef1 = useRef<any>();
  const modalRef2 = useRef<any>();
  const modalRef3 = useRef<any>();

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
      message.warn('删除失败，请重试');
      return false;
    }
  };

  const deleteItem = (item: React.SetStateAction<any | undefined>) => {
    Modal.confirm({
      title: '系统提示',
      content: '确定要删除吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        await handleRemove([], item);
        actionRef.current?.reload?.();
      },
    });
  };

  const clearSelected = () => {
    setSelectedRows([]);
    actionRef?.current?.clearSelected();
  };

  const columns: ProColumns<any>[] = [
    {
      title: '头像',
      dataIndex: 'avatar',
      hideInSearch: true,
      render: (dom, entity) => {
        return <Image width={40} height={40} src={entity.avatar} preview={false} />;
      },
    },
    {
      title: '姓名',
      dataIndex: 'username',
    },
    {
      title: '手机号',
      dataIndex: 'mobile',
    },
    {
      title: '部门',
      dataIndex: 'departmentname',
      hideInSearch: true,
    },
    {
      title: '餐补余额',
      dataIndex: 'balance',
      valueType: 'money',
      hideInSearch: true,
    },
    {
      title: '微信余额',
      dataIndex: 'weixinbalance',
      valueType: 'money',
      hideInSearch: true,
    },
    {
      title: '用户分类',
      dataIndex: 'usertype',
      valueEnum: userType,
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      render: (_, entity) => [
        <a
          key="edit"
          onClick={async () => {
            clearSelected();
            setCurrentRow(entity);
            setTimeout(() => {
              modalRef1?.current.setVisible(true);
            }, 200);
          }}
        >
          充值
        </a>,
        <a
          key="delete"
          onClick={() => {
            clearSelected();
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
      setDepartmentId(id);
      actionRef.current?.reload();
    },
  }));

  useEffect(() => {
    type().then((res) => {
      setUserType(res.data);
    });
  }, []);

  const cutModalOk = async (ids: number[]) => {
    setSelectedRows([]);
    actionRef.current?.reloadAndRest?.();
  };

  const cutModalCancel = () => {
    actionRef.current?.clearSelected?.();
  };

  const rechargeModalOk = async (value: string | number) => {
    actionRef.current?.reloadAndRest?.();
  };

  const userTypeModalOk = async (value: number) => {
    setSelectedRows([]);
    actionRef.current?.reloadAndRest?.();
  };

  const userTypeModalCancel = () => {
    actionRef.current?.clearSelected?.();
  };

  return (
    <>
      <ProTable<any, any>
        headerTitle="食堂账号列表"
        actionRef={actionRef}
        rowKey="id"
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
        request={(params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          params.departmentid = departmentId;
          if (searchFlag) {
            params.search = 1;
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
            key="synchronization"
            onClick={() => {
              if (props.onSynchronization) {
                props.onSynchronization();
              }
            }}
          >
            <CloudDownloadOutlined /> 同步企业通讯录账号
          </Button>,
          <Button
            type="primary"
            key="move"
            onClick={() => {
              if (selectedRowsState.length == 0) {
                message.warn('请选择要操作的食堂账号');
                return;
              }
              modalRef?.current.setVisible(true);
            }}
          >
            <ScissorOutlined />
            部门移动
          </Button>,
          <Button
            type="primary"
            key="usertype"
            onClick={() => {
              if (selectedRowsState.length == 0) {
                message.warn('请选择要操作的食堂账号');
                return;
              }
              modalRef2?.current.setVisible(true);
            }}
          >
            <DollarOutlined />
            分类设置
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
          <Button
            type="primary"
            key="export"
            onClick={async () => modalRef3?.current.setVisible(true)}
          >
            <ExportOutlined /> 余额导出
          </Button>,
        ]}
      />
      <DepartmentModal
        ref={modalRef}
        type="info"
        action="cut"
        onOk={cutModalOk}
        onCancel={cutModalCancel}
        fromId={departmentId}
        selectedRows={selectedRowsState}
      />
      {currentRow && (
        <RechargeModal
          ref={modalRef1}
          onOk={rechargeModalOk}
          username={currentRow.username}
          userid={currentRow.id}
        />
      )}
      <UserTypeModal
        ref={modalRef2}
        types={userType}
        selectedRows={selectedRowsState}
        onOk={userTypeModalOk}
        onCancel={userTypeModalCancel}
      />
      <BalanceExportModal ref={modalRef3} />
    </>
  );
});

export default List;
