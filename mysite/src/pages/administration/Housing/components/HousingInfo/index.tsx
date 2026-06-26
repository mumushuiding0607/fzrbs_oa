import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import React, { useRef, useState } from 'react';
import type { ProDescriptionsItemProps } from '@ant-design/pro-descriptions';
import ProDescriptions from '@ant-design/pro-descriptions';
import { rule, addRule,updateRule, removeRule,download,getProject } from './service';
import type { TableListItem } from './data';
import { Drawer, Button, message, Modal, Space } from 'antd';
import {  ProFormInstance,DrawerForm,ProForm,ProFormSelect, 
  ProFormText,ProFormDatePicker,ProFormDigit,ProFormCheckbox } from '@ant-design/pro-components';

import { VerticalAlignBottomOutlined,PlusOutlined } from '@ant-design/icons';


export type ListProps = {
  authData?: any;
  onCreate?: (depId: number, value: any) => void;
  onUpdate?: (id: number, name: string) => void;
  onDelete?: (ids: number[]) => void;
};


const HousingInfo = React.forwardRef((props: ListProps, ref) => {
  const [currentRow, setCurrentRow] = useState<TableListItem>();
  const [selectedRowsState, setSelectedRows] = useState<TableListItem[]>([]);
  const actionRef = useRef<ActionType>();
  const [showForm, setShowForm] = useState<boolean>(false);
  const formRef = useRef<ProFormInstance>();
  const [showDetail, setShowDetail] = useState<boolean>(false);
  const [addr, setAddr] = useState(false);//编辑框-项目信息
  const [project, setProject] = useState(false);//编辑框-项目信息

  console.log('info:',props.authData);

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
  const handleAddOrUpdate = async (updateRow: any, values: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      // result = await updateRule({
      //   id: updateRow.id,
      //   values,
      // });

      if (updateRow == undefined) {
        result = await addRule({
          values
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
  // 导出数据
const handleDownload = async (params:{}) => {
  download(params);
};


  //默认
  const columns: ProColumns<any>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      // width: 120,
      hideInSearch: true,
      hideInForm: true,
      hideInDescriptions: true,
      order:2
    },
    {
      title: '项目ID',
      dataIndex: 'tp_id',
      hideInSearch: true,
      hideInForm: true,
      hideInTable: true,
      hideInDescriptions: true,
    },
    {
      title: '房产项目',
      dataIndex: 'project',
      ellipsis: true,
      copyable: true,
      hideInForm: true,
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
    },
    {
      title: '承租人',
      dataIndex: 'lessee',
      // width: 120,
      formItemProps: {
        rules: [
          {
            required: true,
            message: '此项为必填项',
          },
        ],
      },
    },
    {
      title: '联系方式',
      dataIndex: 'mobile',
      formItemProps: {
        rules: [
          {
            required: true,
            message: '此项为必填项',
          },
        ],
      },
    },
    {
        title: '起租日期',
        dataIndex: 'start_time',
        hideInSearch: true,
        valueType: 'date',

    },
    {
        title: '到期日期',
        dataIndex: 'end_time',
        hideInSearch: true,
        valueType: 'date',

    },{
        title: '月租金（元）',
        dataIndex: 'monthly_rent',
        hideInSearch: true,

    },
    {
        title: '收租日（号）',
        dataIndex: 'rent_date',
        hideInSearch: true,

    },
    {
        title: '项目地址',
        dataIndex: 'addr',
        ellipsis: true,
        copyable: true,

    },
    {
        title: '收租通知',
        dataIndex: 'notice',
        hideInSearch: true,
        valueEnum: {
          0: { text: '不通知', status: 'Error' },
          1: { text: '通知', status: 'Success' }
        },

    },
    {
        title: '创建时间',
        dataIndex: 'created',
        hideInSearch: true,
        hideInForm: true,

    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      fixed: 'right',
    //  width: 120,
      render: (_, entity) => [
        props.authData && props.authData.actions.includes('HousingInfoEdit') && (<a
          key="edit"
          onClick={() => {
            setShowDetail(false);
            setCurrentRow(entity);
            setShowForm(true);
          }}
        >
          修改
        </a>),
        props.authData && props.authData.actions.includes('HousingInfoDelete') && (<a
          key="delete"
          onClick={() => {
            deleteItem(entity);
          }}
        >
          删除
        </a>),
      ],
    },
  ];




  //钩子
//   useImperativeHandle(ref, () => ({
//     reload: (item: any) => {
//       setDepId(item.id);
//       actionRef.current?.reload();
//     },
//   }));

  return (
    <>
      <ProTable<any, any>
        headerTitle="租房列表"
        actionRef={actionRef}
        rowKey="id"
        search={{
          defaultCollapsed: false,
          labelWidth: 120,
          optionRender: (searchConfig, formProps, dom) => [
            ...dom.reverse(),
            props.authData && props.authData.actions.includes('HousingInfoDownload') && (<Button
              key="out" icon={<VerticalAlignBottomOutlined />} type="primary"
              onClick={() => {
                const values = searchConfig?.form?.getFieldsValue();
                handleDownload(values);

              }}
            >
              导出
            </Button>),
          ],
        }}
        request={(params, sorter, filter) => {
          return rule(params);
        }}
        columns={columns}
        rowSelection={{
          onChange: (_, selectedRows) => {
            setSelectedRows(selectedRows);
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
          return[ 
            props.authData && props.authData.actions.includes('HousingInfoDelete') && (<Button
              key="del"
              type="primary"
              onClick={async () => {
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
              批量删除
            </Button>)
            ];
        }}
        toolBarRender={() => [
          props.authData && props.authData.actions.includes('HousingInfoEdit') && (<Button
            type="primary"
            key="primary"
            onClick={() => {
              setCurrentRow(undefined);
              setShowForm(true);
            }}
          >
            <PlusOutlined /> 新建
          </Button>),
        ]}
        scroll={{ x: 1300 }}
      />
      <Drawer
        width={600}
        title="查看租房信息"
        visible={showDetail}
        onClose={() => {
          setCurrentRow(undefined);
          setShowDetail(false);
        }}
        closable={true}  
      >
       {currentRow?.id && (
          <ProDescriptions<TableListItem>
            column={1}
            request={async () => ({
              data: currentRow || {},
            })}
            params={{
              id: currentRow?.id,
            }}
            columns={columns as ProDescriptionsItemProps<TableListItem>[]}
          />
        )}
      </Drawer>
      {/* 编辑页面 */}
      <DrawerForm
        onVisibleChange={setShowForm}
        title="编辑租房信息"
        visible={showForm}
        formRef={formRef}
        autoFocusFirstInput
        drawerProps={{
          destroyOnClose: true,
          onClose: () => {
            // setResponseState(undefined);
          },
        }}
        onFinish={async (values) => {
          console.log(values);
          message.success('提交成功');
          const result = await handleAddOrUpdate(currentRow, values);
          if (result) {
            if (result.errorCode) {
              return false;
            }
            message.success('保存成功！');
            actionRef.current?.reloadAndRest?.();//刷新数据
          }
        }}
        initialValues={{...currentRow
        }}
      >
    <ProForm.Group>
         <ProFormSelect width="xl" name="tp_id" label="房产项目" showSearch debounceTime={300}
            request={async ({ keyWords }) => {
              let data = await getProject();
              let obj ={};
              let prj = {};
              if(data.data.length){
                let tempDriver = data.data;
                
                tempDriver.map(function(e,item){
                  obj[e.value] = e.addr;
                  prj[e.value] = e.label;
                })
              }
              setAddr(obj);
              setProject(prj);
              // console.log(obj);
              return data.data;
            }}
            onChange={(value: string)=>{
              console.log(value);
                formRef?.current?.setFieldsValue({
                  addr: addr[value],
                  project: project[value],
                });
            }}
            placeholder="请选择项目"
            rules={[{ required: true, message: '请选择项目' }]}
          /> 
    </ProForm.Group>
    <ProForm.Group  style={{display:'none'}}>
      <ProFormText name="project" disabled /> 
    </ProForm.Group>
    <ProForm.Group>
        <ProFormText  width="xl" name="addr" label="项目地址"  disabled />

    </ProForm.Group>
    <ProForm.Group>

        <ProFormText  width="md" name="lessee" label="承租人"  />
        <ProFormText  width="md" name="mobile" label="联系方式"  />

      </ProForm.Group>

      <ProForm.Group>
          <ProFormDatePicker  width="md"  name="start_time" label="起租时间" fieldProps={{
              format: (value) => value.format('YYYY-MM-DD'),
            }} rules={[{ required: true, message: '请输入起租时间' }]}/>        
          <ProFormDatePicker width="md"  name="end_time" label="到租时间" fieldProps={{
              format: (value) => value.format('YYYY-MM-DD'),
            }} rules={[{ required: true, message: '请输入到租时间' }]}/>     
      </ProForm.Group>

      <ProForm.Group>
        <ProFormDigit width="md" label="月租金（元）" name="monthly_rent" min={0} max={99999999999} fieldProps={{ precision: 2 }}  />
        <ProFormSelect
            options={[
              {value: '1',label: '1',},{value: '2',label: '2',},{value: '3',label: '3',},{value: '4',label: '4',},{value: '5',label: '5',},
              {value: '6',label: '6',}, {value: '7',label: '7',},{value: '8',label: '8',},{value: '9',label: '9',},{value: '10',label: '10',},
              {value: '11',label: '11',}, {value: '12',label: '12',},{value: '13',label: '13',},{value: '14',label: '14',},{value: '15',label: '15',},
              {value: '16',label: '16',}, {value: '17',label: '17',},{value: '18',label: '18',},{value: '19',label: '19',},{value: '20',label: '21',},
              {value: '21',label: '21',},{value: '22',label: '22',},{value: '23',label: '23',},{value: '24',label: '24',},{value: '25',label: '25',},
              {value: '26',label: '26',}, {value: '27',label: '27',},{value: '28',label: '28',},{value: '29',label: '29',},{value: '30',label: '30',},
            ]}
            width="md"
            name="rent_date"
            label="收租日"
          />
        </ProForm.Group>
        <ProFormCheckbox.Group
            name="notice"
            label="收租通知"
            options={[{ label: '通知', value: '1' }]}
          />
  </DrawerForm>
    </>
  );
});

export default HousingInfo;
