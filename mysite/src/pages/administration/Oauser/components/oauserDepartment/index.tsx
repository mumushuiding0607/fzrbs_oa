import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import { UploadOutlined } from '@ant-design/icons';
import type { ProDescriptionsItemProps } from '@ant-design/pro-descriptions';
import ProDescriptions from '@ant-design/pro-descriptions';
import React, { useImperativeHandle, useRef, useState } from 'react';
import { rule, createRule, updateRule, visiableRule, download, moveRule } from './service';
import type { TableListItem, TableListPagination, ErrorResponse } from './data';
import { Button, Drawer, message, Modal, Alert, Space, DatePicker } from 'antd';
import {
  ProFormColumnsType, ProFormInstance, DrawerForm, ProForm, ProFormSelect,
  ProFormText, ProFormDateTimePicker, ProFormDatePicker, ProFormDigit, ProFormCheckbox
} from '@ant-design/pro-components';

// import { createFromIconfontCN,VerticalAlignBottomOutlined,PlusOutlined } from '@ant-design/icons';
import moment from 'moment';
import browser from '@/utils/browser';
import DepartmentModal from '../DepartmentModal';//导入页面

export type ListProps = {
  onCreate?: (depId: number, value: any) => void;
  onUpdate?: (id: number, name: string) => void;
  onDelete?: (ids: number[]) => void;
};

const DepList = React.forwardRef((props: ListProps, ref) => {
  const [currentRow, setCurrentRow] = useState<TableListItem>();
  const [addPid, setAddPid] = useState<TableListItem>(0);//新增是的父节点
  const actionRef = useRef<ActionType>();
  // 编辑框信息
  const [showForm, setShowForm] = useState<boolean>(false);
  const formRef = useRef<ProFormInstance>();
  const modalRef = useRef<any>();//移动成员ref

  // const [defaultExpanded, setDefaultExpanded] = useState([])

  // const [treeData, setTreeData] = useState([])

  // 更新
  const handleUpdate = async (updateRow: any, values: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      if (addPid == 0) {
        result = await updateRule({
          id: updateRow.id,
          values,
        });
      } else {
        result = await createRule({
          pid: addPid,
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
  // 更新
  const handleVisiable = async (updateRow: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      result = await visiableRule(updateRow);

      hide();

      return result;
    } catch (error) {
      message.success('保存失败！');
      return false;
    }
  };
  // 更新
  const moveModelOk = async (updateRow: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      result = await moveRule(updateRow);

      hide();
      return result;
    } catch (error) {
      message.success('保存失败！');
      return false;
    }
  };
  //移动部门
  const cutModalOk = async (ids: number[]) => {
    console.log('ddd');
    actionRef.current?.reloadAndRest?.();
  };

  const cutModalCancel = () => {
    actionRef.current?.clearSelected?.();
  };
  //默认
  const columns: ProColumns<any>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      // width: 120,
      hideInSearch: true,
      hideInForm: true,
      hideInTable: true,
      order: 2
    },
    {
      title: '部门名称',
      dataIndex: 'name',
    },
    {
      title: '排序',
      dataIndex: 'order',
    },
    {
      title: '状态',
      dataIndex: 'st',
      valueEnum: {
        1: { text: "显示" }, 2: { text: "隐藏" },
      }
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      fixed: 'right',
      //  width: 120,
      render: (_, entity) => [
        <a
          key="edit"
          onClick={() => {
            setCurrentRow(entity);
            setAddPid(0);
            setShowForm(true);
          }}
        >
          修改
        </a>,
        <a
          key="add"
          onClick={() => {
            setCurrentRow(undefined);
            setAddPid(entity.id);
            setShowForm(true);
          }}
        >
          添加
        </a>,
        <a
          key="move"
          onClick={() => {
            setCurrentRow(entity);
            modalRef?.current.setVisible(true);
          }}
        >
          移动
        </a>,
        entity.st == 2 && <a key="show" onClick={async () => {
          // deleteItem(entity);
          const result = await handleVisiable({ id: entity.id, st: 1 });
          if (result) {
            if (result.errorCode) {
              return false;
            }
            message.success('保存成功！');
            actionRef.current?.reload?.();
          }

        }} >显示</a>,
        entity.st == 1 && <a key="hide" onClick={async () => {
          const result = await handleVisiable({ id: entity.id, st: 2 });
          if (result) {
            if (result.errorCode) {
              return false;
            }
            message.success('保存成功！');
            actionRef.current?.reload?.();
          }
        }}>隐藏</a>

      ],
    },
  ];

  return (
    <>
      <ProTable<any, any>
        headerTitle="部门列表"
        actionRef={actionRef}
        rowKey="id"
        // search={{
        //   defaultCollapsed: false,
        //   labelWidth: 120,
        //   optionRender: (searchConfig, formProps, dom) => [
        //     ...dom.reverse(),
        //   ],
        // }}
        search={false}
        request={(params, sorter, filter) => {
          return rule(params);
          // const result = await rule(params);
          // setTreeData(result.data);
          // return new Promise((resolve, reject) => {
          //   const newExpandedKeys = []
          //   const render = (treeDatas) => { // 获取到所有可展开的父节点
          //     treeDatas.map(item => {
          //       if (item.children) {
          //         console.log('newExpandedKeys',newExpandedKeys);
          //         newExpandedKeys.push(item.id)
          //         render(item.children);
          //       }
          //     })
          //     return newExpandedKeys
          //   }
          //   setDefaultExpanded(render(result.data))
          //   resolve(result)
          // })

          // return result;
        }}
        columns={columns}
        rowSelection={{
          onChange: (_, selectedRows) => {
          },
        }}
        tableAlertRender={({ selectedRowKeys, selectedRows, onCleanSelected }) => (
          <Space size={24}>
            <span>已选 {selectedRowKeys.length} 项</span>
            <span>
              <a style={{ marginLeft: 8 }} onClick={onCleanSelected}>
                取消选择
              </a>
            </span>
          </Space>
        )}
        tableAlertOptionRender={() => {
          return [

          ];
        }}
        toolBarRender={() => [

        ]}
        scroll={{ x: 1300 }}

      // expandable={{defaultExpandedRowKeys: defaultExpanded}}

      />

      {/* 编辑页面 */}
      <DrawerForm
        title="编辑部门信息"
        width={browser.mobile() ? '100vw' : 600}
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

          const result = await handleUpdate(currentRow, values);
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
          // record: currentRow ? currentRow.record + '' : '',
        }}
        layout="vertical"
        grid={true}
      >
        <ProForm.Group>
          <ProFormText
            name="name"
            width="sm"
            label="部门名称"
            placeholder="请输入部门名称"
            rules={[
              {
                required: true,
                message: '请输入部门名称！',
              },
            ]}
            colProps={{ md: 24, xl: 24 }}
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormText
            name="order"
            width="sm"
            label="排序Id"
            placeholder="排序Id"
            rules={[
              {
                // required: true,
                message: '请输入排序Id！',
              },
            ]}
            colProps={{ md: 24, xl: 24 }}
          />
        </ProForm.Group>
      </DrawerForm>
      {/* 移动部门 */}

      {/* 移动成员 */}
      <DepartmentModal
        ref={modalRef}
        type="info"
        action="cut"
        onOk={cutModalOk}
        onCancel={cutModalCancel}
        selectedRows={[currentRow]}
        callbackOk={moveModelOk}
      />

    </>
  );
});

export default DepList;
