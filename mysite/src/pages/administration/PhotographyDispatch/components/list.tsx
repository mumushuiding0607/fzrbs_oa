import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useRef, useState } from 'react';
import { rule, addRule, updateRule, getReport, download } from './service';
import type { TableListItem, TableListPagination, ErrorResponse } from './data';
import { Button, Table, message } from 'antd';
import type { ProFormInstance } from '@ant-design/pro-components';
import { DrawerForm, ProForm, ProFormSelect, ProFormText, ProFormTextArea, ProFormDateTimePicker } from '@ant-design/pro-components';
import { PlusOutlined, VerticalAlignBottomOutlined } from '@ant-design/icons';
import browser from '@/utils/browser';
import moment from 'moment';
import ReactToPrint from "react-to-print"; //打印



const ListTable: React.FC = () => {
  const [showForm, setShowForm] = useState<boolean>(false);

  const [currentRow, setCurrentRow] = useState<TableListItem>();
  const [selectedRowsState, setSelectedRows] = useState<TableListItem[]>([]);
  const actionRef = useRef<ActionType>();
  const formRef = useRef<ProFormInstance>();
  const [responseState, setResponseState] = useState<ErrorResponse>();
  const stText = {
    0: { text: '驳回', status: 'Default' },
    1: { text: '审核中', status: 'Default' },
    2: { text: '任务中', status: 'Processing' },
    3: { text: '任务暂时保存', status: 'Success' },
    4: { text: '结束派车', status: 'Error' },
    5: { text: '确认结束', status: 'Error' },
  };

  // 导出数据
  const handleDownload = async (params: {}) => {
    download(params);
  };

  const handleAddAndUpdate = async (updateRow: any, values: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      if (updateRow == undefined) {
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
      return result;
    } catch (error) {
      message.warn('保存失败！');
      return false;
    }
  };

  const columns: ProColumns<any>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInSearch: true,
      width: 80
    },
    {
      title: '申请人',
      dataIndex: 'opt_name',
      hideInSearch: true,
      width: 80
    },
    {
      title: '审批编号',
      dataIndex: 'order_no',
      hideInSearch: true,
      width: 150, ellipsis: true,

    },
    {
      title: '创建时间',
      dataIndex: 'created',
      width: 140, ellipsis: true,
      hideInSearch: true,

    },
    {
      title: '所在部门',
      dataIndex: 'dep_name',
      hideInSearch: true,
      width: 120

    },
    {
      title: '派工事由',
      dataIndex: 'reason',
      hideInSearch: true,
      width: 220,
      ellipsis: true,
      copyable: true,

    },
    {
      title: '开始时间',
      dataIndex: 'begin_time',
      valueType: 'dateTime',
      order: 2,
      ellipsis: true,
      width: 150,

      // renderText: (val: string) =>{return val.format("YYYY-MM-DD HH:mm")}
      // renderText: (val: string) =>{console.log(val);return moment(val).format("YYYY-MM-DD HH:mm")}
      // dateFormatter: (value) => value.format('YYYY-MM-DD HH:mm')
    },
    {
      title: '结束时间',
      dataIndex: 'end_time',
      valueType: 'dateTime',
      order: 1,
      width: 150,
    },

    {
      title: '派工时长',
      dataIndex: 'diffTime',
      hideInSearch: true,
      width: 120

    },

    {
      title: '状态',
      dataIndex: 'st',
      hideInSearch: true,
      valueEnum: stText,
      width: 120
    },
    {
      title: '记者',
      dataIndex: 'dispatch_name',
      order: 3, width: 80
    },
    {
      title: '记者电话',
      dataIndex: 'dispatch_mobile',
      hideInSearch: true, width: 100

    },
    {
      title: '得分',
      dataIndex: 'grade',
      hideInSearch: true,
      width: 80
    },
    {
      title: '评语',
      dataIndex: 'command',
      hideInSearch: true,
      ellipsis: true,
      copyable: true,
      width: 200

    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      fixed: 'right',
      width: 80,
      render: (_, entity) => [
        <a
          key="edit"
          onClick={() => {
            setCurrentRow(entity);
            setShowForm(true);
          }}
        >
          修改
        </a>
      ],
    },
  ];

  return (
    <>
      <ProTable<TableListItem, TableListPagination>
        headerTitle="摄影派工列表"
        actionRef={actionRef}
        rowKey="id"
        search={{
          defaultCollapsed: false,
          labelWidth: 120,
          optionRender: (searchConfig, formProps, dom) => [
            ...dom.reverse(),
            <Button
              key="out" icon={<VerticalAlignBottomOutlined />} type="primary"
              onClick={() => {
                const values = searchConfig?.form?.getFieldsValue();
                values.selId = selectedRowsState;
                handleDownload(values);
                console.log(values);
              }}
            >
              导出
            </Button>,
          ],
        }}
        scroll={{ x: 1300 }}
        request={rule}
        columns={columns}
        rowSelection={{
          selections: [Table.SELECTION_ALL, Table.SELECTION_INVERT],
          defaultSelectedRowKeys: [],
          onChange: (_selRowKey, selectedRows) => {
            setSelectedRows(_selRowKey);
          },
        }}
      />

      {/* 编辑用户信息 */}
      <DrawerForm
        title="编辑派工信息"
        // width={browser.mobile() ? '100vw' : 600}
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
          ...currentRow
        }}
      // layout="vertical"
      // grid={true}
      >
        {/* {responseState?.errorCode && <AdminMessage content={responseState?.errorMessage} />} */}
        <ProForm.Group>

          <ProFormText name="opt_name" width="md" label="申请人" disabled={currentRow != undefined} />
          <ProFormText name="dep_name" width="md" label="所在部门" disabled={currentRow != undefined} />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormText name="order_no" width="md" label="审批编号" disabled={currentRow != undefined} />
          <ProFormText name="created" width="md" label="创建时间" disabled={currentRow != undefined} />

        </ProForm.Group>
        <ProForm.Group>
          <ProFormSelect width="md" valueEnum={stText} name="st" label="状态" disabled allowClear={false} />
          <ProFormTextArea width="md" label="派工事由" name="reason" />
        </ProForm.Group>
        <ProForm.Group>
          <ProForm.Group>
            <ProFormDateTimePicker width="md" name="begin_time" label="开始时间" fieldProps={{
              format: (value) => value.format('YYYY-MM-DD HH:mm'),
            }}
              rules={[{ required: true, message: '请输入开始时间' }]}
              onChange={(value: string) => {
                let begin = moment(value).format('YYYY-MM-DD HH:mm');
                let end_time = formRef.current?.getFieldsFormatValue?.().end_time;
                let date3 = moment(end_time).diff(begin, 'minute');//计算相差的分钟数
                let d = Math.floor(date3 / (60 * 24));//相差的天数
                let h = Math.floor(date3 % (60 * 24) / 60);//相差的小时数
                let mm = date3 % 60;//计算相差小时后余下的分钟数
                formRef?.current?.setFieldsValue({
                  diffTime: (d + '天' + h + "时" + mm + '分'),
                });
              }} />
            <ProFormDateTimePicker width="md" name="end_time" label="结束时间" fieldProps={{
              format: (value) => value.format('YYYY-MM-DD HH:mm'),
            }} rules={[{ required: true, message: '请输入结束时间' }]}
              onChange={(value: string) => {
                let end_time = moment(value).format('YYYY-MM-DD HH:mm');
                let begin_time = formRef.current?.getFieldsFormatValue?.().begin_time;
                let date3 = moment(end_time).diff(begin_time, 'minute');//计算相差的分钟数
                let d = Math.floor(date3 / (60 * 24));//相差的天数
                let h = Math.floor(date3 % (60 * 24) / 60);//相差的小时数
                let mm = date3 % 60;//计算相差小时后余下的分钟数
                formRef?.current?.setFieldsValue({
                  diffTime: (d + '天' + h + "时" + mm + '分'),
                });
              }} />
          </ProForm.Group>
          <ProFormText name="diffTime" width="md" label="派工时长" disabled={currentRow != undefined} />

          <ProFormSelect width="md" name="dispatch_userid" label="记者" showSearch debounceTime={300}
            dependencies={['begin_time', 'end_time']}
            request={async ({ keyWords }) => {
              let begin_time = formRef.current?.getFieldsFormatValue?.().begin_time;
              let end_time = formRef.current?.getFieldsFormatValue?.().end_time;
              let id = formRef.current?.getFieldsFormatValue?.().id;

              let data = await getReport({ id: currentRow.id, begin_time: begin_time, end_time: end_time });
              return data.data;
            }}
            placeholder="请选择记者"
            rules={[{ required: true, message: '请选择记者' }]}
          />
        </ProForm.Group>
      </DrawerForm>
    </>
  );
};

export default ListTable;
