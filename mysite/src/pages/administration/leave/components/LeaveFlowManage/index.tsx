import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useRef, useState, useEffect } from 'react';
import { leaveFlowList, addLeaveFlow, removeLeaveFlow, updateLeaveFlow } from './service';
import type { ProFormInstance } from '@ant-design/pro-components';
import { DrawerForm, ProForm, ProFormSelect, ProFormText, ProFormSwitch,ProFormDigitRange } from '@ant-design/pro-components';
import type { TableListItem, TableListPagination } from './data';
import { Button, Modal, Space, message } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import DeptSel from '@/components/DepartmentTreeSelect';


const LeaveFlow: React.FC = () => {
  const [currentRow, setCurrentRow] = useState<TableListItem>();
  const actionRef = useRef<ActionType>();
  const formRef = useRef<ProFormInstance>();
  const [showForm, setShowForm] = useState<boolean>(false);
  const deptRef = useRef();
  const userRef = useRef();

  const typeDict = [
    {
      label: '请假',
      value: 0,
    },
    {
      label: '销假',
      value: 1,
    },
    {
      label: '逾期销假',
      value: 2,
    }
  ];

  const levelDict = [
    {
      label: '一般工作人员',
      value: 0,
    },
    {
      label: '中层正职（含主持工作的副职）',
      value: 1,
    },
    {
      label: '中层副职',
      value: 2,
    },
    {
      label: '社领导',
      value: 3,
    }
  ];

  const handleAddandUpdate = async (updateRow: any, values: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      console.log(values);
      if (updateRow == undefined) {
        result = await addLeaveFlow({
          values,
        });
      } else {
        result = await updateLeaveFlow({
          id: updateRow.id,
          values,
        });        
      }
      hide();
      return result;
    } catch (error) {
      message.warn('保存失败！');
      return false;
    }
  };
  const handleCancel = () => {
    
  };

  const deleteItem = (item: React.SetStateAction<TableListItem | undefined>) => {
    if (!item) return true;
    Modal.confirm({
      title: '删除',
      content: '确定要删除吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
         let deleteIds = [item].map((row) => row.id.toString());
        await removeLeaveFlow({id:deleteIds});
        actionRef.current?.reload?.();
      },
    });
  };

  const columns: ProColumns<TableListItem>[] = [
    {
      title: '模板ID',
      dataIndex: 'templateid',
    },
    {
      title: '流程名称',
      dataIndex: 'templatename',
    },
    {
      title: '类别',
      dataIndex: 'type',
      valueEnum: {
        0: '请假',
        1: '销假',
        2: '逾期销假'
      },
    },
    {
      title: '级别',
      dataIndex: 'level',
      valueEnum: {
        0: '一般工作人员',
        1: '中层正职（含主持工作的副职）',
        2: '中层副职',
        3: '社领导'
      },
    },
    {
      title: '部聘',
      dataIndex: 'is_company',
      valueEnum: {
        0: {
          text: '否',
        },
        1: {
          text: '是',
        },
      },
    },
    {
      title: '天数（下限）',
      dataIndex: 'min',
      hideInSearch: true,
    },
    {
      title: '天数（上限）',
      dataIndex: 'max',
      hideInSearch: true,
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
            // deptRef?.current?.setCheckedKeys(currentRow?.dids);
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

  useEffect(() => {
    
  }, [])
  
  return (
    <>
      <ProTable<TableListItem, TableListPagination>
        headerTitle="请销假流程列表"
        actionRef={actionRef}
        rowKey="id"
        search={{
          labelWidth: 120,
        }}
        request={leaveFlowList}
        columns={columns}
        toolBarRender={() => [
          <Button
            type="primary"
            key="primary"
            onClick={() => {
              setCurrentRow(undefined);
              setShowForm(true);
            }}
          >
            <PlusOutlined /> 添加流程
          </Button>,
        ]}
      />
      <DrawerForm
        title="编辑请销假流程"
        width={600}
        visible={showForm}
        onVisibleChange={setShowForm}
        formRef={formRef}
        autoFocusFirstInput
        drawerProps={{
          destroyOnClose: true,
          onClose: () => {
            
          },
        }}
        submitter={{ searchConfig: { submitText: '提交' } }}
        submitTimeout={2000}
        onFinish={async (values) => {
          console.log(values);
          values.dids = deptRef?.current?.getCheckedKeys();
          values.uids = userRef?.current?.getCheckedKeys();
          values.is_company = values.is_company ? 1 : 0;
          const result = await handleAddandUpdate(currentRow,values);
          if (result) {
            if (result.errorCode) {
              return false;
            }
            message.success('保存成功！');
            actionRef.current?.reload?.();
          }        
          
          return true;
        }}
        onValuesChange={async (values) => {
          console.log(values)
          
        }}
        initialValues={{
          templateid: currentRow?.templateid,
          templatename: currentRow?.templatename,
          type: currentRow?.type,
          level: currentRow?.level,
          min_max: [0,3],
          is_company: currentRow?.is_company==1?true:false,
          // leaveTimes: currentRow?.leaveTimes,
          // leaveReason: currentRow?.leaveReason||' '
          // islock: currentRow ? currentRow.islock + '' : '0',
        }}
        // layout="vertical"
        // grid={true}
      >
        <ProForm.Group label="模板ID">
        <ProFormText width="md" name="templateid"  />
        </ProForm.Group>
        <ProForm.Group label="模板名称">
        <ProFormText width="md" name="templatename"  />
        </ProForm.Group>
        <ProForm.Group label="类别">
          <ProFormSelect
            width="sm"
            options={typeDict}
            name="type"
            allowClear={false}
          />
        </ProForm.Group>
        <ProForm.Group label="级别">
        <ProFormSelect
          width="sm"
          options={levelDict}
          name="level"
          allowClear={false}
        />
        </ProForm.Group>
        <ProForm.Group label="部门">
          <DeptSel local={true} showLeafIcon={true} showAll={true} checkable={true} ref={deptRef} checkedKeys={currentRow?.dids?currentRow?.dids?.split(","):undefined} width='300px' showCheckedStrategy='SHOW_PARENT' />
        </ProForm.Group>
        <ProForm.Group label="用户">
          <DeptSel local={true} showLeafIcon={true} showAll={true} showUser={true} checkable={true} ref={userRef} checkedKeys={currentRow?.uids?currentRow?.uids?.split(","):undefined} width='300px' showCheckedStrategy='SHOW_CHILD' />
        </ProForm.Group>
        <ProForm.Group label="区间">
          <ProFormDigitRange
            name="min_max"
            separator="-"
            separatorWidth={20}
          />
        </ProForm.Group>
        <ProForm.Group label="是否部聘">
        <ProFormSwitch name="is_company" />
        </ProForm.Group>
      </DrawerForm>
    </>
  );
};

export default LeaveFlow;
