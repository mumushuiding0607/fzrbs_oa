/* eslint-disable @typescript-eslint/ban-types */
/* eslint-disable @typescript-eslint/no-unused-vars */
import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import { UploadOutlined, ScissorOutlined, MinusOutlined } from '@ant-design/icons';
import React, { useImperativeHandle, useRef, useState } from 'react';
import { rule, updateRule, removeRule, download, createRule, getValueEnum, getQualificationList, saveQualificationRule, cut } from './service';
import { Button, Drawer, message, Modal } from 'antd';
import type { ProFormInstance } from '@ant-design/pro-components';
import { DrawerForm, ProFormText, ProDescriptions, ProFormDigit, ProForm, ProFormSelect, ProFormDatePicker, ProFormTextArea, ModalForm, ProFormList, ProFormItem } from '@ant-design/pro-components';
import browser from '@/utils/browser';
import { PlusOutlined, VerticalAlignBottomOutlined } from '@ant-design/icons';
import ImportForm from './importForm';//导入页面
import DepartmentModal from '../DepartmentModal';//导入页面
import moment from 'moment';
import type { TableListItem } from '@/pages/admin/Department/data';

// import ImportForm from './importForm';//导入页面
// import SignForm from './signForm';//签发页面

export type ListProps = {
  onCreate?: (depId: number, value: any) => void;
  onUpdate?: (id: number, name: string) => void;
  onDelete?: (ids: number[]) => void;
};

const List = React.forwardRef((props: ListProps, ref) => {
  const [showDetail, setShowDetail] = useState<boolean>(false);
  const [currentRow, setCurrentRow] = useState<any>();
  const [eNum, setENum] = useState<any>([]);
  const [selectedRowsState, setSelectedRows] = useState<any>([]);
  const actionRef = useRef<ActionType>();
  const modalRef = useRef<any>();//移动成员ref

  const qualificationActionRef = useRef<any>();
  const [showForm, setShowForm] = useState<boolean>(false);
  const formRef = useRef<ProFormInstance>();
  const [showListForm, setShowListForm] = useState<boolean>(false);//职务资格弹窗
  const listFormRef = useRef<ProFormInstance>();//职务资格弹窗ref
  const [qualificationList, setQualificationList] = useState<any>([]);//职务资格弹窗ref
  const importFormRef = useRef<any>();//导入

  const [depId, setDepId] = useState<number>(0);
  const [depItem, setDepItem] = useState<any>([]);
  const [addFlag, setAddFlag] = useState<any>([]);//新增标识
  const depChildEnum = {
    1: { text: "　", },
  }
  const allEnum = ['全部'];



  const handleRemove = async (selectedRows: TableListItem[], deleteRow: any) => {
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
  //删除数据
  const deleteItem = (item: React.SetStateAction<TableListItem | undefined>) => {
    Modal.confirm({
      title: '删除',
      content: '确定要删除吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        await handleRemove([], item);
        setShowDetail(false);
        actionRef.current?.reloadAndRest?.();
      },
    });
  };
  // 更新
  const handleUpdate = async (updateRow: any, values: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      if (updateRow.id) {
        result = await updateRule({
          id: updateRow.id,
          values,
        });
      } else {
        result = await createRule({
          id: 0,
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
  //移动成员
  const cutModalOk = async (ids: number[]) => {
    setSelectedRows([]);
    actionRef.current?.reloadAndRest?.();
  };

  const cutModalCancel = () => {
    actionRef.current?.clearSelected?.();
  };

  const moveModelOk = async (updateRow: any) => {
    const hide = message.loading('正在保存');
    try {
      const result = await cut(updateRow);
      hide();
      return result;
    } catch (error) {
      message.success('保存失败！');
      return false;
    }

  }
  //获取职务资格列表
  const getQualificationListFunc = async () => {

    const result = await getQualificationList({ userId: currentRow.id });
    return result;
  }
  //保存职务资格列表
  const handleSaveQualificationFunc = async (values: any) => {
    const hide = message.loading('正在保存');
    try {
      const result = await saveQualificationRule({
        id: currentRow.id,
        values,
      });

      hide();

      return result;
    } catch (error) {
      message.success('保存失败！');
      return false;
    }
  };

  // 导出数据
  const handleDownload = async (params: {}) => {
    download(params);
    // tools.downloadFile('/api/oauser/export', params, '职员信息.xls');
  };
  const getValueEnumFunc = async () => {
    if (eNum.length == 0) {
      const result = await getValueEnum({});
      // data['recordEnum'][0] = {'text':'全部'};
      // data['stEnum'][0] = {'text':'全部'};
      // data['genderEnum'][data['genderEnum'].length] = {'text':'全部'};
      setENum(result.data);
    }
  }

  //默认
  const columns: ProColumns<any>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInSearch: true,
      hideInForm: true,
      hideInDescriptions: true,
      width: 80,
      // sorter:2
    },
    {
      title: '含子部门',
      dataIndex: 'isChild',
      // hideInSearch: true,
      width: 80,
      valueType: 'checkbox',
      hideInTable: true,
      hideInForm: true,
      hideInDescriptions: true,
      valueEnum: depChildEnum,
      colSize: 1,
      order: 30
      //  sorter:1
    },//含子部门
    {
      title: '姓名',
      dataIndex: 'name',
      width: 100,
      order: 30,
      render: (dom, entity) => {
        return (
          <a
            onClick={() => {
              setCurrentRow(entity);
              // clearSelected();
              setShowDetail(true);
            }}
          >
            {dom}
          </a>
        );
      },
    },
    {
      title: '邮箱',
      dataIndex: 'email',
      width: 100,
      order: 15,
      hideInTable: true,
      // hideInForm:true,
      hideInSearch: true,
    },
    {
      title: '本职级认定时间',
      dataIndex: 'positions',
      width: 100,
      order: 1,
      hideInTable: true,
      hideInForm: true,
      hideInSearch: true,
    },
    {
      title: '现单位以及职务',
      dataIndex: 'position',
      width: 100,
      order: 1,
      hideInTable: true,
      hideInForm: true,
      hideInSearch: true,
    },
    {
      title: '辞职原因',
      dataIndex: 'resign_reason',
      width: 100,
      order: 1,
      hideInTable: true,
      hideInForm: true,
      hideInSearch: true,
    },
    {
      title: '年龄范围',
      dataIndex: 'age',
      valueType: 'digitRange',
      order: 25,
      colSize: 1,
      hideInTable: true,
      hideInDescriptions: true,
      hideInForm: true,
      fieldProps: { min: 18 },
    },
    // {
    //   title: '现部门以及职务',
    //   dataIndex: 'position',
    //   // hideInSearch: true,
    //   width: 150,
    // //  sorter:1
    // },
    {
      title: '性别',
      dataIndex: 'gender',
      // hideInSearch: true,
      width: 80,
      valueType: 'radio',
      valueEnum: { ...eNum.genderEnum, allEnum },
      colSize: 2,
      order: 16
      //  sorter:1
    },
    {
      title: '职务',
      dataIndex: 'class_positions',
      // hideInSearch: true,
      width: 80,
      valueType: 'checkbox',
      hideInTable: true,
      hideInForm: true,
      valueEnum: { ...eNum.positionEnum, allEnum },
      colSize: 2,
      order: 15
      //  sorter:1
    },
    {
      title: '民族',
      dataIndex: 'nation',
      // hideInSearch: true,
      width: 80,
      order: 25
      //  sorter:1
    },
    {
      title: '籍贯',
      dataIndex: 'province',
      // hideInSearch: true,
      width: 100,
      order: 25
      //  sorter:1
    },
    {
      title: '出生年月',
      dataIndex: 'birth',
      hideInSearch: true,
      width: 120,
      valueType: 'dateMonth',
      order: 2
      //  sorter:1
    },
    {
      title: '出生地',
      dataIndex: 'birth_place',
      // hideInSearch: true,
      width: 120,
      order: 25
      //  sorter:1
    },
    {
      title: '入党年月',
      dataIndex: 'party_time',
      hideInSearch: true,
      valueType: 'dateMonth',
      width: 120,
      // sorter: '1'
    },
    {
      title: '学历',
      dataIndex: 'record',
      // hideInSearch: true,
      width: 120,
      valueType: 'radio',
      valueEnum: { ...eNum.recordEnum, allEnum },
      colSize: 2,
      order: 15
    },
    {
      title: '毕业院校以及专业',
      dataIndex: 'school',
      // hideInSearch: true,
      width: 120,
      ellipsis: true,
      copyable: true,
      hideInDescriptions: true,
      order: 20
    },
    {
      title: '毕业时间',
      dataIndex: 'graduation_time',
      hideInSearch: true,
      valueType: 'dateMonth',
      width: 120,
      //  sorter:1
    },
    {
      title: '专业技术职务资格',
      dataIndex: 'job_qualification',
      hideInSearch: true,
      width: 120,
      ellipsis: true,
      copyable: true,
      order: 20
    },

    {
      title: '专业技术确认时间',
      dataIndex: 'job_qualification_time',
      valueType: 'dateMonth',
      hideInSearch: true,
      width: 130,
      //  sorter:1
    },
    {
      title: '专业技术聘任时间',
      dataIndex: 'job_qualification_time2',
      hideInSearch: true,
      valueType: 'dateMonth',
      width: 130,
      //  sorter:1
    },
    {
      title: '部门',
      dataIndex: 'departmentname',
      hideInSearch: true,
      width: 200,
      ellipsis: true,
      copyable: true,
      order: 30
      //  sorter:1
    },
    {
      title: '任现职时间',
      dataIndex: 'curr_job_time',
      hideInSearch: true,
      valueType: 'dateMonth',
      width: 120,
      //  sorter:1
    },
    {
      title: '本职级',
      dataIndex: 'positions',
      hideInSearch: true,
      valueType: 'dateMonth',
      width: 120,
      //  sorter:1
    },
    {
      title: '参加工作时间',
      dataIndex: 'work_time',
      hideInSearch: true,
      valueType: 'dateMonth',
      width: 120,
      //  sorter:1
    },
    {
      title: '入编时间',
      dataIndex: 'authorized_time',
      hideInSearch: true,
      valueType: 'dateMonth',
      width: 120,
      //  sorter:1
    },
    {
      title: '社聘时间',
      dataIndex: 'social_time',
      hideInSearch: true,
      valueType: 'dateMonth',
      width: 120,
      //  sorter:1
    },
    {
      title: '转集体编制时间',
      dataIndex: 'team_time',
      hideInSearch: true,
      valueType: 'dateMonth',
      width: 150,
      //  sorter:1
    },
    {
      title: '公司聘时间',
      dataIndex: 'company_time',
      hideInSearch: true,
      valueType: 'dateMonth',
      width: 120,
      //  sorter:1
    },
    {
      title: '试用时间',
      dataIndex: 'entrytime',
      hideInSearch: true,
      valueType: 'dateMonth',
      width: 120,
      //  sorter:1
    },
    {
      title: '离职时间',
      dataIndex: 'resign_time',
      hideInSearch: true,
      valueType: 'dateMonth',
      width: 120,
      order: 1
      //  sorter:2
    },
    {
      title: '聘用形式',
      dataIndex: 'employ_type',
      valueType: 'checkbox',
      valueEnum: eNum.employTypeEnum,
      width: 120,
      colSize: 3,
      order: 15
    },
    {
      title: '状态',
      dataIndex: 'st',
      //  width: 120,
      // valueType: 'select',
      valueEnum: { ...eNum.stEnum, allEnum },
      valueType: 'radio',
      width: 120,
      colSize: 2,
      order: 16
    },
    {
      title: '手机',
      dataIndex: 'mobile',
      width: 120,
      order: 29
    },
    {
      title: '专业技术职务资格',
      dataIndex: 'job_qualification',
      //  width: 120,
      valueType: 'select',
      valueEnum: eNum.qualificationEnum,
      width: 120,
      order: 20,
      hideInTable: true,
      hideInForm: true,
    },
    {
      title: '入党年月起始时间',
      // key: 'dateRange',
      dataIndex: 'party_time',
      valueType: 'dateRange',
      hideInTable: true,
      hideInForm: true,
      hideInDescriptions: true,
      // order:10

    },
    {
      title: '出生年月起始时间',
      // key: 'dateRange',
      dataIndex: 'birth',
      valueType: 'dateRange',
      hideInTable: true,
      hideInForm: true,
      hideInDescriptions: true,
      order: 10

    },
    {
      title: '专业技术确认时间',
      // key: 'dateRange',
      dataIndex: 'job_qualification_time',
      valueType: 'dateRange',
      hideInTable: true,
      hideInForm: true,

    },
    {
      title: '专业技术聘任时间',
      // key: 'dateRange',
      dataIndex: 'job_qualification_time2',
      valueType: 'dateRange',
      hideInTable: true,
      hideInForm: true,
    },
    {
      title: '毕业时间起始时间',
      // key: 'dateRange',
      dataIndex: 'graduation_time',
      valueType: 'dateRange',
      hideInForm: true,
      hideInTable: true,
      hideInDescriptions: true,
      order: 10

    },
    {
      title: '任现职起始时间',
      // key: 'dateRange',
      dataIndex: 'curr_job_time',
      valueType: 'dateRange',
      hideInForm: true,
      hideInDescriptions: true,
      hideInTable: true, order: 8
    },
    {
      title: '参加工作起始时间',
      // key: 'dateRange',
      dataIndex: 'work_time',
      valueType: 'dateRange',
      hideInForm: true,
      hideInTable: true,
      hideInDescriptions: true,
      order: 10
    },
    {
      title: '入编起始时间',
      // key: 'dateRange',
      dataIndex: 'authorized_time',
      valueType: 'dateRange',
      hideInTable: true,
      hideInForm: true,
      hideInDescriptions: true,
    },
    {
      title: '社聘起始时间',
      // key: 'dateRange',
      dataIndex: 'social_time',
      valueType: 'dateRange',
      hideInTable: true,
      hideInForm: true,
      hideInDescriptions: true,
    },
    {
      title: '转集体编制时间',
      // key: 'dateRange',
      dataIndex: 'team_time',
      valueType: 'dateRange',
      hideInTable: true,
      hideInForm: true,
      hideInDescriptions: true,
    },
    {
      title: '公司聘起始时间',
      // key: 'dateRange',
      dataIndex: 'company_time',
      valueType: 'dateRange',
      hideInTable: true,
      hideInForm: true,
      hideInDescriptions: true,
    },
    {
      title: '试用起始时间',
      // key: 'dateRange',
      dataIndex: 'entrytime',
      valueType: 'dateRange',
      hideInTable: true,
      hideInForm: true,
      hideInDescriptions: true,
      order: 7
    },
    {
      title: '离职起始时间',
      // key: 'dateRange',
      dataIndex: 'resign_time',
      valueType: 'dateRange',
      hideInTable: true,
      hideInForm: true,
      hideInDescriptions: true,
      order: 7
    },
    {
      title: '退休起始时间',
      // key: 'dateRange',
      dataIndex: 'retire_time',
      valueType: 'dateRange',
      hideInTable: true,
      hideInForm: true,
      hideInDescriptions: true,
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      fixed: 'right',
      hideInDescriptions: true,
      hideInForm: true,
      //  width: 120,
      render: (_, entity) => [
        <a
          key="edit"
          onClick={() => {
            setAddFlag(true);
            setShowDetail(false);
            eNum.dateColumn.forEach((item: any) => {
              entity[item] = entity[item] ? entity[item] : null;
            });
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




  //钩子
  useImperativeHandle(ref, () => ({
    reload: (item: any) => {
      getValueEnumFunc();
      setDepId(item.id);
      setDepItem(item);
      actionRef.current?.reloadAndRest();
    },
  }));

  return (
    <>
      <ProTable<any, any>
        headerTitle="职员列表"
        actionRef={actionRef}
        rowKey="id"
        search={{
          defaultCollapsed: true,
          labelWidth: 120,
          optionRender: (searchConfig, formProps, dom) => [
            ...dom.reverse(),
            <Button
              key="out" icon={<VerticalAlignBottomOutlined />}
              // type="primary"
              onClick={() => {
                const values = searchConfig?.form?.getFieldsValue();
                values.depId = depId;
                handleDownload(values);
              }}
            >
              导出
            </Button>,
          ],
        }}
        request={(params) => {
          params.depId = depId;
          if (eNum.length > 0) {
            eNum.dateColumn.forEach((item: any) => {
              params[item] = params[item].join(',');
            });
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
        scroll={{ x: 1300 }}
        toolBarRender={() => [
          <Button
            key="del"
            type="primary"
            onClick={async () => {
              if (selectedRowsState.length == 0) {
                message.warn('请选择要操作的项目！');
                return;
              }
              Modal.confirm({
                title: '批量删除',
                content: '确定删除选中的项目吗？',
                okText: '确认',
                cancelText: '取消',
                onOk: async () => {
                  await handleRemove(selectedRowsState, undefined);
                  setSelectedRows([]);
                  actionRef.current?.reloadAndRest?.();
                },
              });
            }}
          >
            <MinusOutlined />批量删除
          </Button>,

          <Button
            type="primary"
            key="download"
            onClick={() => {
              importFormRef?.current.setVisible(true);
            }}
            icon={<UploadOutlined />}
          >
            导入数据
          </Button>,
          <Button
            type="primary"
            key="new"
            onClick={() => {
              setAddFlag(false);
              setCurrentRow(undefined);
              setCurrentRow({ departmentname: depItem.title, gender: '', class_positions: '', record: '', birth: null, graduation_time: null, work_time: null, job_qualification_time: null, job_qualification_time2: null, positions: null, curr_job_time: null, authorized_time: null, social_time: null, team_time: null, company_time: null, entrytime: null, resign_time: null, retire_time: null });
              setShowForm(true);
            }}
          >
            <PlusOutlined /> 新建
          </Button>,
          <Button
            key="move"
            type="primary"
            onClick={() => {
              if (selectedRowsState.length == 0) {
                message.warn('请选择要操作的成员');
                return;
              }
              modalRef?.current.setVisible(true);
            }}
          >
            <ScissorOutlined />
            移动成员
          </Button>,
        ]}
      />

      {/* 详情页面 */}
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
          columns={columns}
        />
      </Drawer>

      {/* 编辑页面 */}
      <DrawerForm
        title="编辑职员信息"
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
          record: currentRow ? currentRow.record + '' : '',
          gender: currentRow ? currentRow.gender + '' : '',
          class_positions: currentRow ? currentRow.class_positions + '' : '',
          employTypeEnum: currentRow ? currentRow.employTypeEnum + '' : '',
          // resign_time: currentRow ? (currentRow.resign_time ? currentRow.resign_time + '':null) : null,
        }}
        layout="vertical"
        grid={true}
      >
        <ProForm.Group>
          <ProFormText
            name="name"
            width="sm"
            label="姓名"
            placeholder="请输入姓名"
            rules={[
              {
                // required: true,
                message: '请输入姓名！',
              },
            ]}
            colProps={{ md: 12, xl: 24 }}
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormText
            disabled
            name="departmentname"
            width="sm"
            label="部门"
            placeholder="请输入部门"
            rules={[
              {
                required: true,
                message: '请输入部门！',
              },
            ]}
            colProps={{ md: 12, xl: 12 }}
          />
          <ProFormSelect
            name="gender"
            label="性别"
            valueEnum={{
              1: '男',
              2: '女',
            }}
            colProps={{ md: 12, xl: 12 }}
            placeholder="请选择性别"
            rules={[
              {
                required: true,
                message: '请选择性别！',
              },
            ]}
          />

        </ProForm.Group>
        <ProForm.Group>
          <ProFormText
            name="email"
            width="sm"
            label="邮箱"
            placeholder="请输入邮箱"
            rules={[
              {
                // required: true,
                message: '请输入邮箱！',
              },
            ]}
            colProps={{ md: 12, xl: 12 }}
          />
          <ProFormText
            name="mobile"
            width="sm"
            label="电话"
            placeholder="请输入电话"
            rules={[
              {
                required: true,
                message: '请输入电话！',
              },
            ]}
            colProps={{ md: 12, xl: 12 }}
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormText
            name="birth_place"
            width="sm"
            label="出生地"
            placeholder="请输入出生地"
            rules={[
              {
                // required: true,
                message: '请输入出生地！',
              },
            ]}
            colProps={{ md: 12, xl: 12 }}
          />
          <ProFormDatePicker
            picker="month"
            name="party_time"
            label="入党年月"
            // format={'YYYY-MM'}
            fieldProps={{
              format: (value) => value.format('YYYY-MM'),
            }}
            value={
              moment().format("YYYY-MM")
            }
            colProps={{ md: 12, xl: 12 }} />
        </ProForm.Group>

        <ProForm.Group>
          <ProFormText
            name="nation"
            width="sm"
            label="民族"
            placeholder="请输入民族"
            rules={[
              {
                // required: true,
                message: '请输入民族！',
              },
            ]}
            colProps={{ md: 12, xl: 12 }}
          />
          <ProFormText
            name="province"
            width="sm"
            label="籍贯"
            placeholder="请输入籍贯"
            rules={[
              {
                // required: true,
                message: '请输入籍贯！',
              },
            ]}
            colProps={{ md: 12, xl: 12 }}
          />
        </ProForm.Group>

        <ProForm.Group>

          <ProFormDatePicker picker="month" name="birth" label="出生年月" fieldProps={{
            format: (value) => value.format('YYYY-MM'),
          }} value={moment().format("YYYY-MM")} colProps={{ md: 12, xl: 12 }} />
          <ProFormSelect
            name="record"
            label="学历"
            valueEnum={eNum.recordEnum}
            colProps={{ md: 12, xl: 12 }}
            placeholder="请选择学历"
            rules={[
              {
                required: true,
                message: '请选择学历！',
              },
            ]}
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormText
            name="school"
            width="sm"
            label="毕业院校以及专业"
            placeholder="请输入毕业院校以及专业"
            rules={[
              {
                // required: true,
                message: '请输入毕业院校以及专业！',
              },
            ]}
            colProps={{ md: 12, xl: 12 }}
          />
          <ProFormDatePicker picker="month" name="graduation_time" label="毕业时间" fieldProps={{
            format: (value) => value.format('YYYY-MM'),
          }} value={moment().format("YYYY-MM")} colProps={{ md: 12, xl: 12 }} />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormDatePicker picker="month" name="work_time" label="参加工作时间" fieldProps={{
            format: (value) => value.format('YYYY-MM'),
          }} value={moment().format("YYYY-MM")} colProps={{ md: 6, xl: 6 }} />
          <ProFormText
            // addFlag && disabled
            disabled={addFlag}
            name="job_qualification"
            width="sm"
            label="专业技术职务资格"
            placeholder="请输入专业技术职务资格"
            rules={[
              {
                // required: true,
                message: '请输入专业技术职务资格！',
              },
            ]}
            colProps={{ md: 18, xl: 18 }}
          />
        </ProForm.Group>

        <ProForm.Group>
          <ProFormDatePicker disabled={addFlag} picker="month" name="job_qualification_time" label="专业技术确认时间" fieldProps={{
            format: (value) => value.format('YYYY-MM'),
          }} value={moment().format("YYYY-MM")} colProps={{ md: 8, xl: 8 }} />
          <ProFormDatePicker disabled={addFlag} picker="month" name="job_qualification_time2" label="专业技术聘任时间" fieldProps={{
            format: (value) => value.format('YYYY-MM'),
          }} value={moment().format("YYYY-MM")} colProps={{ md: 8, xl: 8 }} />
          <ProFormItem name="" label="职务资格列表" hidden={!addFlag}>
            <Button
              type="primary"
              ghost
              key="SET"
              onClick={async (values) => {
                //获取职务资格信息
                const result = await getQualificationListFunc();
                setQualificationList(result.data);
                setShowListForm(true);

                if (result.data.length) {

                }

              }}
            >
              编辑职务资格
            </Button>
          </ProFormItem>
        </ProForm.Group>
        <ProForm.Group>

          <ProFormDatePicker picker="month" name="positions" label="本职级认定时间" fieldProps={{
            format: (value) => value.format('YYYY-MM'),
          }} value={moment().format("YYYY-MM")} colProps={{ md: 12, xl: 12 }} />
          <ProFormText name="work_job" width="sm"
            label="现单位以及职务"
            placeholder="请输入现单位以及职务"
            rules={[
              {
                // required: true,
                message: '请输入现单位以及职务！',
              },
            ]}
            colProps={{ md: 12, xl: 12 }}
          />
        </ProForm.Group>

        <ProForm.Group>
          <ProFormSelect
            name="class_positions"
            label="本职级（职级名称）"
            valueEnum={eNum.positionEnum}
            colProps={{ md: 12, xl: 12 }}
            placeholder="请选择本职级（职级名称）"
            rules={[
              {
                required: true,
                message: '请选择本职级（职级名称）！',
              },
            ]}
          />
          <ProFormDatePicker picker="month" name="curr_job_time" label="任现职时间" fieldProps={{
            format: (value) => value.format('YYYY-MM'),
          }} value={moment().format("YYYY-MM")} colProps={{ md: 12, xl: 12 }} />
        </ProForm.Group>
        <ProForm.Group>

          <ProFormDatePicker picker="month" name="authorized_time" label="入编时间" fieldProps={{
            format: (value) => value.format('YYYY-MM'),
          }} value={moment().format("YYYY-MM")} colProps={{ md: 12, xl: 12 }} />
          <ProFormDatePicker picker="month" name="social_time" label="社聘时间" fieldProps={{
            format: (value) => value.format('YYYY-MM'),
          }} value={moment().format("YYYY-MM")} colProps={{ md: 12, xl: 12 }} />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormDatePicker picker="month" name="team_time" label="转集体编制时间" fieldProps={{
            format: (value) => value.format('YYYY-MM'),
          }} value={moment().format("YYYY-MM")} colProps={{ md: 12, xl: 12 }} />
          <ProFormDatePicker picker="month" name="company_time" label="公司聘时间" fieldProps={{
            format: (value) => value.format('YYYY-MM'),
          }} value={moment().format("YYYY-MM")} colProps={{ md: 12, xl: 12 }} />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormDatePicker picker="month" name="entrytime" label="试用时间" fieldProps={{
            format: (value) => value.format('YYYY-MM'),
          }} value={moment().format("YYYY-MM")} colProps={{ md: 12, xl: 12 }} />
          <ProFormSelect
            name="employ_type"
            label="聘用形式"
            valueEnum={eNum.employTypeEnum}
            colProps={{ md: 12, xl: 12 }}
            placeholder="请选择分类"
            rules={[
              {
                required: true,
                message: '请选择分类！',
              },
            ]}
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormDatePicker picker="date" name="resign_time" label="辞职时间" fieldProps={{
            format: (value) => { return value ? value.format('YYYY-MM-DD') : "" },
          }} value={moment().format("YYYY-MM-DD")} colProps={{ md: 8, xl: 8 }} />
          <ProFormText
            name="resign_reason"
            width="sm"
            label="辞职原因"
            placeholder="请输入民族"
            rules={[
              {
                // required: true,
                message: '请输入民族！',
              },
            ]}
            colProps={{ md: 16, xl: 16 }}
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormDatePicker picker="month" name="retire_time" label="退休时间" fieldProps={{
            format: (value) => value.format('YYYY-MM'),
          }} value={moment().format("YYYY-MM")} colProps={{ md: 12, xl: 12 }} />
          <ProFormDigit
            label="排序ID"
            name="displayorder"
            fieldProps={{ precision: 0 }}
            colProps={{ md: 12, xl: 12 }}
            tooltip="请输入数字排序ID越高排越前"
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormTextArea colProps={{ md: 12, xl: 24 }} label="备注" name="mark" />
        </ProForm.Group>
      </DrawerForm>
      {/* 导入数据 */}
      <ImportForm ref={importFormRef} />

      {/* 职务资格列表 */}
      <ModalForm<any>
        title="职务资格列表"
        width={browser.mobile() ? '100vw' : 800}
        visible={showListForm}
        onVisibleChange={setShowListForm}
        formRef={listFormRef}
        autoFocusFirstInput
        modalProps={{
          destroyOnClose: true,
          onCancel: () => { },

        }}
        submitter={{ searchConfig: { submitText: '提交' } }}
        submitTimeout={500}
        onFinish={async (values) => {
          console.log(values);
          const result = await handleSaveQualificationFunc(values);
          if (result) {
            if (result.errorCode) {
              return false;
            }

            message.success('保存成功！');
            actionRef.current?.reloadAndRest();
            setShowForm(false);
            // formRef.current?.reload?.();
          }
          return true;
        }}
        initialValues={{

        }}
        layout="vertical"
        grid={true}
      >
        <ProFormList
          name='list'
          label="职务资格列表"
          initialValue={qualificationList}
          alwaysShowItemLabel
          actionRef={qualificationActionRef}
        >
          <ProForm.Group key="group">
            <ProFormText name="job_qualification" label="专业技术职务资格" colProps={{ md: 12, xl: 12 }} initialValue="" />
            <ProFormText name="id" label="" hidden initialValue="0" />

            <ProFormDatePicker picker="month" name="job_qualification_time" label="专业技术确认时间" fieldProps={{
              format: (value) => value.format('YYYY-MM'),
            }} value={moment().format("YYYY-MM")} colProps={{ md: 6, xl: 6 }} initialValue={null} />
            <ProFormDatePicker picker="month" name="job_qualification_time2" label="专业技术任聘时间" fieldProps={{
              format: (value) => value.format('YYYY-MM'),
            }} value={moment().format("YYYY-MM")} colProps={{ md: 6, xl: 6 }} initialValue={null} />
          </ProForm.Group>
        </ProFormList>
      </ModalForm>

      {/* 移动成员 */}
      <DepartmentModal
        ref={modalRef}
        type="info"
        action="cut"
        onOk={cutModalOk}
        onCancel={cutModalCancel}
        fromId={depId}
        selectedRows={selectedRowsState}
        callbackOk={moveModelOk}
      />
    </>
  );
});

export default List;
