import type {  ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import { UploadOutlined } from '@ant-design/icons';
import React, { useImperativeHandle, useRef, useState } from 'react';
import { rule, updateRule, removeRule,download,signRule,types } from './service';
import type { TableListItem, TableListPagination } from './data';
import { Button, message, Modal, Space,DatePicker } from 'antd';
import { BetaSchemaForm, ProFormColumnsType, ProFormInstance } from '@ant-design/pro-components';

import { VerticalAlignBottomOutlined } from '@ant-design/icons';
import moment from 'moment';
import ImportForm from './importForm';//导入页面
import SignForm from './signForm';//签发页面

export type ListProps = {
  userAuth: any;
  onCreate?: (depId: number, value: any) => void;
  onUpdate?: (id: number, name: string) => void;
  onDelete?: (ids: number[]) => void;
};
const RangePicker:any = DatePicker.RangePicker;

const List = React.forwardRef((props: ListProps, ref) => {
  const [currentRow, setCurrentRow] = useState<TableListItem>();
  const [selectedRowsState, setSelectedRows] = useState<TableListItem[]>([]);
  const actionRef = useRef<ActionType>();
  const [showForm, setShowForm] = useState<boolean>(false);
  const formRef = useRef<ProFormInstance>();
  const importFormRef = useRef<any>();//导入
  const signFormRef = useRef<any>();//签发

  const [depId, setDepId] = useState<number>(0);
  const [typeDict, setTypeDict] = useState<any>([]);
  const formRules = {
    'require': {
      rules: [
        {
          required: true,
          message: '此项为必填项',
        },
      ],
    },
    'number': {
      rules: [
        {
          message: '请输入数值型',
          type: 'number',
          transform(value) {
            if(value){
              return Number(value);//将输入框当中的字符串转换成数字类型
            }
          },
        },
      ],
    }
  };
  const valFormat = {
    'year': moment().format("YYYY"),
    'send': {
      0:{text:"未通知",status:'Default'},
      1:{text:"已通知",status:'Processing'},
    },
    'sign': {
      0: { text: '未签发', status: 'Default' },
      1: { text: '已签发', status: 'Processing'},
    }
  }
  const setTypes = async ()=>{
    await types().then((res) => {
      console.log('types:',res);
      let _types = {};
      for(let i in res.data){
        _types[i] = {text:res.data[i],status:''};
      }
      setTypeDict(_types);
    }); 
  }
  const signItem = async (entity:any)=>{
    console.log(entity)
    let values = {
      id: entity.id,
      depId: entity.dep_id,
      st: 1
    };
    let result = await signRule(values);
    if(result){
      actionRef.current?.reload();
    }
  }
  const sendItem = async (entity:any)=>{
    console.log(entity)
    let values = {
      id: entity.id,
      depId: entity.dep_id,
      st: 1,
      notify: 1
    };
    let result = await signRule(values);
    if(result){
      actionRef.current?.reload();
    }
  }
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
        actionRef.current?.reloadAndRest?.();
      },
    });
  };
  const hadleUpdate = async (updateRow: any, values: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      result = await updateRule({
        id: updateRow.id,
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
const handleDownload = async (params:{},depId:number) => {
  download(params,depId);
};


  //默认
  const columns: ProFormColumnsType<TableListItem>[] = [
    {
      title: '姓名',
      dataIndex: 'col_a',
      order:1
    },
    {
      title: '年份区间',
      dataIndex: 'yearRange',
      valueType: 'dateRange',
      hideInTable: true,
      renderFormItem: (_, { type, defaultRender, ...rest }, form) => {
        if (type === 'form') {
          return null;
        }
        const status = form.getFieldValue('state');
        if (status !== 'open') {
          return (
            // value 和 onchange 会通过 form 自动注入。
            <RangePicker picker="year" placeholder={['开始年份','结束年份']} />
          );
        }
        return defaultRender(_);
      },
      search: {
        transform: (value) => {
          return {
            startTime: value[0].substr(0, 4),
            endTime: value[1].substr(0, 4),
          };
        },
      },
    },
    {
      title: '所属年份',
      dataIndex: 'bonus_year',
      hideInSearch: true,
      valueType: 'dateYear',
      initialValue: valFormat['year'],
      formItemProps: formRules['require'],
    },
    {
      title: '奖金类型',
      dataIndex: 'bonus_type',
      valueType: 'select',
      valueEnum: typeDict,
      formItemProps: formRules['require'],
    },
    {
      title: '奖金总额',
      dataIndex: 'col_b',
      hideInSearch: true,
      colProps: {
          xs: 24,
          md: 24,
      },
      formItemProps: formRules['number'],
    },
    {
      title: '代扣代缴',
      dataIndex: 'col_c',
      colProps: {
          xs: 24,
          md: 24,
        },
      hideInSearch: true,
      formItemProps: formRules['number'],
    },
    
    {
      title: '实发总额',
      dataIndex: 'col_d',
      colProps: {
          xs: 24,
          md: 24,
        },
      hideInSearch: true,
      formItemProps: formRules['number'],
    },
    {
      title: '　手机号',
      dataIndex: 'mobile',
      hideInSearch: true,
      hideInForm: true,
      colProps: {
          xs: 24,
          md: 8,
        },
    },
    {
      title: '通知',
      dataIndex: 'send_st',
      hideInForm: true,
      valueEnum: valFormat['send'],
    },{
      title: '签发',
      dataIndex: 'sign_st',
      hideInForm: true,
      valueEnum: valFormat['sign'],
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      fixed: 'right',
      render: (_, entity) => [
        props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('BonusEdit') && <a
          key="edit"
          onClick={() => {
            setShowDetail(false);
            console.log(entity);
            setCurrentRow(entity);
            setShowForm(true);
          }}
        >
          修改
        </a>,
         props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('BonusSign') && <a
         key="sign"
         onClick={() => {
           signItem(entity);
         }}
       >
         签发
       </a>,
         props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('BonusSign') && <a
         key="send"
         onClick={() => {
           sendItem(entity);
         }}
       >
         通知
       </a>,
        props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('BonusDelete') && <a
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
    reload: (id: number) => {
      setTypes();
      
      setDepId(id);
      actionRef.current?.reload();
    },
  }));

  return (
    <>
      <ProTable<TableListItem, TableListPagination>
        headerTitle="奖金列表"
        actionRef={actionRef}
        rowKey="id"
        search={{
          defaultCollapsed: false,
          labelWidth: 120,
          optionRender: (searchConfig, formProps, dom) => [
            ...dom.reverse(),
            props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('BonusExport') && <Button
              key="out" icon={<VerticalAlignBottomOutlined />} type="primary"
              onClick={() => {
                const values = searchConfig?.form?.getFieldsValue();
                if(values.yearRange){
                  values.startTime = values.yearRange[0].format("YYYY");
                  values.endTime = values.yearRange[1].format("YYYY");
                }
                handleDownload(values,depId);
              }}
            >
              导出
            </Button>,
          ],
        }}
        request={(params, sorter, filter) => {
          params.depId = depId;
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
            props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('BonusDelete') && <Button
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
            </Button>
            ];
        }}
        scroll={{ x: 1300 }}
        toolBarRender={() => [
          props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('BonusImport') && <Button
          key="download"
          onClick={() => {
            importFormRef?.current.setVisible(true);
          }}
          icon={<UploadOutlined />}
        >
          导入奖金
        </Button>,
        props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('BonusSign') && <Button
          key="sign"
          onClick={() => {
            signFormRef?.current.setContent(1,depId);
            signFormRef?.current.setVisible(true);
          }}
          // icon={<UploadOutlined />}
        >
          签发
        </Button>,
         props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('BonusSign') && <Button
         key="no-sign"
         onClick={() => {
           signFormRef?.current.setContent(0,depId);
           signFormRef?.current.setVisible(true);
         }}
         // icon={<UploadOutlined />}
       >
         取消签发
       </Button>,
        ]}
      />
      <ImportForm id={depId} ref={importFormRef} />

      <SignForm id={depId} ref={signFormRef} />
      {/* 编辑页面 */}
      <BetaSchemaForm<TableListItem>
        title="编辑奖金信息"
        layoutType="DrawerForm"
        // width={800}
        layout="horizontal"
        visible={showForm}
        onVisibleChange={setShowForm}
        formRef={formRef}
        submitTimeout={2000}
        drawerProps={{
          destroyOnClose: true,
          onClose: () => {
            // setCurrentRow(undefined);

          },
          
        }}
        steps={[
          {
            title: 'ProComponent',
          },
        ]}
        rowProps={{
          gutter: [16, 16],
        }}
        colProps={{
          span: 12,
        }}
        onFinish={async (values) => {
          // setResponseState(undefined);
          const result = await hadleUpdate(currentRow, values);
          if (result) {
            if (result.errorCode) {
              message.error(result.errorMessage);

              return false;
            }
            message.success('保存成功！');
            actionRef.current?.reloadAndRest?.();
          }
          return true;
        }}
        grid={true}
        columns={columns}
        initialValues={{
          ...currentRow,
          bonus_type: currentRow ? currentRow.bonus_type + '' : '0',

        }}
      />
    </>
  );
});

export default List;
