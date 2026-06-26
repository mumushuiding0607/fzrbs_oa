import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useRef, useState } from 'react';
import { rule, addRule, updateRule, removeRule } from './service';
import { Alert, Button, Col, message, Modal, Row, Tag } from 'antd';
import {
  ProForm,
  DrawerForm,
  PageContainer,
  ProFormInstance,
  ProFormDatePicker,
} from '@ant-design/pro-components';
import { MinusOutlined, PlusOutlined } from '@ant-design/icons';
import styles from './style.less';
import MyCalendar from './myCalendar';

const Holiday: React.FC = () => {
  const [currentRow, setCurrentRow] = useState<any>();
  const [selectedRowsState, setSelectedRows] = useState<any[]>([]);
  const actionRef = useRef<ActionType>();
  const [showForm, setShowForm] = useState<boolean>(false);
  const formRef = useRef<ProFormInstance>();
  const [responseState, setResponseState] = useState<any>();
  const [whichYear, setWhichYear] = useState<string>(new Date().getFullYear().toString());
  const calendar1 = useRef<any>();
  const calendar2 = useRef<any>();

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
      message.success('保存失败！');
      return false;
    }
  };

  const clearSelected = () => {
    setSelectedRows([]);
    actionRef?.current?.clearSelected();
  };

  const onYearChange = (date, dateString) => {
    setWhichYear(dateString);
  };

  const dayTag = (tag: string) => {
    const tagElem = <Tag>{tag}</Tag>;
    return (
      <span key={tag} style={{ display: 'inline-block', marginBottom: 5 }}>
        {tagElem}
      </span>
    );
  };

  const columns: ProColumns<any>[] = [
    {
      title: '年份',
      dataIndex: 'year',
    },
    // {
    //   title: '类型',
    //   dataIndex: 'type',
    //   valueEnum: {
    //     0: {
    //       text: '假期',
    //     },
    //     1: {
    //       text: '补班',
    //     },
    //   },
    //   tip: '假期：非正常周末假期，补班：正常周末补班',
    // },
    {
      title: '假期',
      dataIndex: 'typeday0',
      tip: '非正常周末假期',
      render: (dom, entity) => {
        return entity.typeday0 != '' ? entity.typeday0.split(',').map(dayTag) : '-';
      },
    },
    {
      title: '补班',
      dataIndex: 'typeday1',
      tip: '正常周末补班',
      render: (dom, entity) => {
        return entity.typeday1 != '' ? entity.typeday1.split(',').map(dayTag) : '-';
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
            setWhichYear(entity.year);
            setShowForm(true);
            if (entity.typeday1 != '') {
              setTimeout(() => {
                calendar1?.current?.setDefaultSelectDays(entity.typeday0.split(','));
                calendar2?.current?.setDefaultSelectDays(entity.typeday1.split(','));
              }, 200);
            }
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
        headerTitle="假期日期设置列表"
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
        title="编辑假期信息"
        width="100vw"
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
          if (calendar1?.current?.getSelectDays().length > 0) {
            values.day1 = calendar1?.current?.getSelectDays().join(',');
          }
          if (calendar2?.current?.getSelectDays().length > 0) {
            values.day2 = calendar2?.current?.getSelectDays().join(',');
          }
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
          year: currentRow ? currentRow.year + '' : whichYear,
        }}
        layout="vertical"
        grid={true}
        className={styles.holidayDatePicker}
      >
        {responseState?.errorCode && (
          <Alert
            style={{
              marginBottom: 24,
            }}
            message={responseState?.errorMessage}
            type="error"
            closable={true}
            showIcon
          />
        )}
        <ProForm.Group>
          <ProFormDatePicker.Year
            name="year"
            label="请选择年份"
            fieldProps={{
              onChange: onYearChange,
            }}
          />
        </ProForm.Group>
        {whichYear != '' && (
          <>
            <Row gutter={[30, 0]}>
              <Col span={12}>
                <MyCalendar year={parseInt(whichYear)} ref={calendar1} />
              </Col>
              <Col span={12}>
                <MyCalendar year={parseInt(whichYear)} title="请选择补班日期" ref={calendar2} />
              </Col>
            </Row>
          </>
        )}
      </DrawerForm>
    </PageContainer>
  );
};

export default Holiday;
