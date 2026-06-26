import type { ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import { UploadOutlined } from '@ant-design/icons';
import React, { useImperativeHandle, useRef, useState } from 'react';
import { rule, updateRule, removeRule,download,signRule,tableHead } from './service';
import type { TableListItem, TableListPagination } from './data';
import { Button, message, Modal, Space,DatePicker } from 'antd';
import { BetaSchemaForm, ProFormColumnsType, ProFormInstance,ColumnsState } from '@ant-design/pro-components';

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
    'date': moment().format("YYYY-MM"),
    'send': {
      0:{text:"未通知",status:'Default'},
      1:{text:"已通知",status:'Processing'},
    },
    'sign': {
      0: { text: '未签发', status: 'Default' },
      1: { text: '已签发', status: 'Processing'},
    }
  }

  const optRender = (_, entity) => [
    props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('SalaryEdit') && <a
      key="edit"
      onClick={() => {
        setCurrentRow(entity);
        setShowForm(true);
      }}
    >
      修改
    </a>,
     props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('SalarySign') && <a
     key="sign"
     onClick={() => {
        Modal.confirm({
          title: '签发工资条',
          content: '确定签发选中的工资条吗？',
          okText: '确认',
          cancelText: '取消',
          onOk: async () => {
            signItem(entity);
          },
        });      
       
     }}
   >
     签发
   </a>,
     props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('SalarySign') && <a
     key="send"
     onClick={() => {
        Modal.confirm({
          title: '发送通知',
          content: '确定签发工资条并发送通知吗？',
          okText: '确认',
          cancelText: '取消',
          onOk: async () => {
            sendItem(entity);
          },
        });       
     }}
   >
     通知
   </a>,
    props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('SalaryDelete') && <a
      key="delete"
      onClick={() => {
        deleteItem(entity);
      }}
    >
      删除
    </a>,
  ];

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
const handleDownload = async (params:{},paramsFiled:{},depId:number) => {
  download(params,paramsFiled,depId);
};
//设置表头
const setTableHead = async (id: number) => {
  let result = await tableHead({
    id: id,
  });
  let tempColumn = [];
  for(let i in result.columns){
    let tmp = result.columns[i];
    if(tmp.hasOwnProperty('formItemProps')){
      tmp.formItemProps = formRules[tmp.formItemProps];
    }
    if(tmp.hasOwnProperty('initialValue')){
      tmp.initialValue = valFormat[tmp.initialValue];
    }
    if(tmp.hasOwnProperty('valueEnum')){
      tmp.valueEnum = valFormat[tmp.valueEnum];
    }
    tempColumn.push(tmp);
  }

  let _extF = [
    {
      title: '月份区间',
      dataIndex: 'monthRange',
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
            <RangePicker picker="month" placeholder={['开始月份','结束月份']} />
          );
        }
        return defaultRender(_);
      },
      search: {
        transform: (value) => {
          return {
            startTime: value[0].substr(0, 7),
            endTime: value[1].substr(0, 7),
          };
        },
      },
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      fixed: 'right',
      width: 150,
      render: optRender,
    }
  ];
  tempColumn=[...tempColumn,..._extF];
      
  if(result.id==31||result.id==2){//社直、社领导
    setLabelName("所属时间");
  }
  
//更新头部显示字段
  let columnMap = {};
  tempColumn.map(function(item,index){
      if(item.hideInTable||item.fixed=='left'||item.fixed=='right'){
      }else{
        columnMap[item.dataIndex] = {show:true};
      }
  });
  setColumnsStateMap(columnMap);
  setColumns(tempColumn);
}

  //默认
  const columnsDefault: ProFormColumnsType<any>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      width: 120,
      hideInSearch: true,
      hideInForm: false,
      order:2
    },
    {
      title: '姓名',
      dataIndex: 'col_a',
      width: 120,
      fixed: 'left',
      order:1
    },
    {
      title: '发放月份',
      dataIndex: 'pay_time',
      width: 120,
      hideInSearch: true,
      valueType: 'dateMonth',
      initialValue: valFormat['date'],
      formItemProps: formRules['require'],
    },
    {
      title: '实发工资',
      dataIndex: 'col_ae',
      width: 120,
      hideInSearch: true,
      formItemProps: formRules['number'],
    },
    {
      title: '　手机号',
      dataIndex: 'mobile',
      width: 120,
    },
    {
      title: '通知',
      dataIndex: 'send_st',
      fixed: 'right',
      valueEnum: valFormat['send'],
      width: 120,
    },{
      title: '签发',
      dataIndex: 'sign_st',
      fixed: 'right',
      valueEnum: valFormat['sign'],
      width: 120,
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      fixed: 'right',
      width: 120,
      render: optRender,
    },
  ];

  const [columnsStateMap, setColumnsStateMap] = useState<Record<string, ColumnsState>>();


  const [columns, setColumns] = useState<any>(columnsDefault);
  const [labelName, setLabelName] = useState<string>('发放时间');//年月名称

  //钩子
  useImperativeHandle(ref, () => ({
    reload: (id: number) => {

      setDepId(id);
      setLabelName("发放时间");
      setTableHead(id);
      
      actionRef.current?.reload();
    },
  }));

  return (
    <>
      <ProTable<TableListItem, TableListPagination>
        headerTitle="工资列表"
        actionRef={actionRef}
        columnsState={{
          value: columnsStateMap,
          onChange: setColumnsStateMap,
        }}
        rowKey="id"
        search={{
          defaultCollapsed: false,
          labelWidth: 120,
          optionRender: (searchConfig, formProps, dom) => [
            ...dom.reverse(),
            props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('SalaryExport') && <Button
              key="out" icon={<VerticalAlignBottomOutlined />} type="primary"
              onClick={() => {
                const values = searchConfig?.form?.getFieldsValue();
                if(values.monthRange){
                  values.startTime = values.monthRange[0].format("YYYY-MM");
                  values.endTime = values.monthRange[1].format("YYYY-MM");
                }
                let columnsState = [];
                Object.keys(columnsStateMap).forEach(function(key){
                  columnsState.push(key);
                })
                handleDownload(values,columnsState,depId);
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
            props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('SalaryDelete') && <Button
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
            </Button>,
            ];
        }}
        scroll={{ x: 1300 }}
        // columnsState={{value:columnsStateMap,setColumnsStateMap}}
        toolBarRender={() => [
          props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('SalaryImport') && <Button
          // type="primary"
          key="download"
          onClick={() => {
            importFormRef?.current.setLabelName(labelName);
            importFormRef?.current.setVisible(true);
          }}
          icon={<UploadOutlined />}
        >
          导入工资
        </Button>,
        props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('SalarySign') && <Button
        key="sign"
        onClick={() => {
          signFormRef?.current.setContent(1,depId,labelName);
          signFormRef?.current.setVisible(true);
        }}
        // icon={<UploadOutlined />}
      >
        签发
      </Button>,
       props.userAuth && props.userAuth.actions && props.userAuth.actions.includes('SalarySign') && <Button
       key="no-sign"
       onClick={() => {
         signFormRef?.current.setContent(0,depId,labelName);
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
        title="编辑工资信息"
        layoutType="DrawerForm"
        // width={800}
        layout="horizontal"
        visible={showForm}
        onVisibleChange={setShowForm}
        formRef={formRef}
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
          ...currentRow
        }}
      />
    </>
  );
});

export default List;
