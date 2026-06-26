import type { ProColumns, ActionType } from '@ant-design/pro-table';
import ProTable from '@ant-design/pro-table';
import type { ProDescriptionsItemProps } from '@ant-design/pro-descriptions';
import ProDescriptions from '@ant-design/pro-descriptions';
import React, { useImperativeHandle, useRef, useState, useEffect } from 'react';
import { flowtemplate, addFlowtemplate, updateFlowtemplate, removeFlowtemplate,getDict } from './service';
import type { TableListItem, TableListPagination } from './data';
import { Button, Drawer, message, Modal, Tabs,Divider,Table,Space,Steps } from 'antd';
import { DrawerForm, ProFormColumnsType, ProFormInstance, ProForm, ProFormSelect, ProFormText,ModalForm,ProFormDigit,ProFormRadio,ProFormSwitch } from '@ant-design/pro-components';
import { createFromIconfontCN, MinusOutlined, PlusOutlined } from '@ant-design/icons';
import browser from '@/utils/browser';
import DeptSel from '@/components/DepartmentTreeSelect';
const { Step } = Steps;
const { TabPane } = Tabs;
export type ListProps = {
  onCreate?: (parentId: string, value: any) => void;
  onUpdate?: (id: string, name: string) => void;
  onDelete?: (ids: string[]) => void;
};

const IconFont = createFromIconfontCN({
  scriptUrl: '/icons/iconfont.js',
});

const List = React.forwardRef((props: ListProps, ref) => {
  const [showDetail, setShowDetail] = useState<boolean>(false);
  const [currentRow, setCurrentRow] = useState<TableListItem>();
  const [selectedRowsState, setSelectedRows] = useState<TableListItem[]>([]);
  const actionRef = useRef<ActionType>();
  const [showForm, setShowForm] = useState<boolean>(false);
  const [showApprovalStepForm, setShowApprovalStepForm] = useState<boolean>(false);
  const [showNotifyStepForm, setShowNotifyStepForm] = useState<boolean>(false);
  const formRef = useRef<ProFormInstance>();
  const [appid, setAppid] = useState<number>();
  const [approvalAttrDict, setApprovalAttrDict] = useState<any>();
  const [notifyAttrDict, setNotifyAttrDict] = useState<any>();
  const [appDict, setAppDict] = useState<any>();
  const [levelDict, setLevelDict] = useState<any>();
  const [roleDict, setRoleDict] = useState<any>();
  const [tagDict, setTagDict] = useState<any>();
  const [userDict, setUserDict] = useState<any>();
  const [approvalAttrVal, setApprovalAttrVal] = useState<any>();
  const [stepTypeVal, setStepTypeVal] = useState<any>();
  const [currentStep, setCurrentStep] = useState<any>();
  const [wxCheckUserId, setCheckWxUserId] = useState<any>();
  const [approvalData, setApprovalData] = useState<any>([]);
  const [notifyData, setNotifyData] = useState<any>([]);
  const wxuserRef = useRef();

  const handleRemove = async (selectedRows: TableListItem[], deleteRow: any) => {
    const hide = message.loading('正在删除');
    if (!selectedRows && !deleteRow) return true;

    try {
      let deleteIds = [];
      let result;
      if (selectedRows.length > 0) {
        deleteIds = selectedRows.map((row) => row.id.toString());
        result = await removeFlowtemplate({
          id: deleteIds,
        });
      } else if (deleteRow) {
        deleteIds = [deleteRow].map((row) => row.id.toString());
        result = await removeFlowtemplate({
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

  const deleteItem = (item: React.SetStateAction<TableListItem | undefined>) => {
    Modal.confirm({
      title: '删除',
      content: '确定要删除吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        await handleRemove([], item);
        setShowDetail(false);
        actionRef.current?.reload?.();
      },
    });
  };

  const handleAddAndUpdate = async (updateRow: any, values: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      const _approvalData = approvalData.map((item:any) => {
        delete item.title;
        delete item.key;
        return item;
      });
      const _notifyData = notifyData.map((item:any) => {
        delete item.title;
        delete item.key;
        return item;
      });
      const templateData = {approval:_approvalData,notify:_notifyData};
      values['templateData']= templateData;
      console.log(values);
      if (updateRow == undefined) {
        result = await addFlowtemplate({
          values,
        });
      } else {
        result = await updateFlowtemplate({
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

  const clearSelected = () => {
    setSelectedRows([]);
    actionRef?.current?.clearSelected();
  };

  const stepTypeChange = (key: string) => {
    setStepTypeVal(key);
  };

  const modifyApprovalStepItem = async (values:any)=>{
    values['userid'] = wxuserRef?.current?.getCheckedKeys();
    const newData = approvalData||[];
    const index = currentStep?newData.findIndex((item) => currentStep.key === item.key):newData.length;
    const items = currentStep?newData[index]:{};
    items.type=parseInt(stepTypeVal)||0;
    items.attr=parseInt(values['attr']);
    items.level=parseInt(values['level'])||0;
    items.role=parseInt(values['role'])||0;
    if(stepTypeVal==2 && values['tag']){
      items.id=parseInt(values['tag']);
      items.tag=tagDict[parseInt(values['tag'])].text;
    }
    items.skip=values['skip']?1:0;
    items.span=values['span']?1:0;
    if(stepTypeVal==1 && values['userid'].length>0){
      items.userid = values['userid'];
      items.id = userDict[values['userid']].id;
      items.uname = userDict[values['userid']].text;
    }
    switch(items.type)
    {
        case 0:
          items.title = roleDict[values['role']].text;
          break;
        case 1:
          items.title = userDict[values['userid']].text;
          break;
        case 2:
          items.title = tagDict[values['tag']].text+(values['attr']?'（'+approvalAttrDict[values['attr']].text+'）':'');
          break;
        case 3:
          items.title = levelDict[values['level']].text+(values['attr']?'（'+approvalAttrDict[values['attr']].text+'）':'');
          break;
        case 6:
          items.title = '手动选择';
          break;
        case 8:
          items.title = '主体负责人';
          break;
    }
    if(currentStep){
      newData.splice(index, 1, {
            ...items
          });
    }else{
      items.key = index+1;
      newData.push(items);
    }
    console.log(newData);
    setApprovalData([...newData]);
    return true;
  };

  const deleteApprovalStepItem = (item: React.SetStateAction<TableListItem | undefined>) => {
    Modal.confirm({
      title: '删除',
      content: '确定要删除吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        const newData = currentRow;
        newData.templateData.approval = currentRow?.templateData.approval.filter((i) => i.key !== item.key);
        for(var i in newData.templateData.approval){
          newData.templateData.approval[i].key=parseInt(i)+1;
        }
        setApprovalData(newData.templateData.approval);
      },
    });
  };

  const modifyNotifyStepItem = async (values:any)=>{
    console.log(values);
    values['userid'] = wxuserRef?.current?.getCheckedKeys();
    const newData = notifyData||[];
    const index = currentStep?newData.findIndex((item) => currentStep.key === item.key):newData.length;
    const items = currentStep?newData[index]:{};
    items.type=parseInt(stepTypeVal)||0;
    items.role=parseInt(values['role'])||0;
    if(items.type==2 && values['tag']){
      items.id=parseInt(values['tag']);
      items.tag=tagDict[parseInt(values['tag'])].text;
    }
    if(items.type==1 && values['userid'].length>0){
      items.userid = values['userid'];
      items.id = userDict[values['userid']].id;
      items.uname = userDict[values['userid']].text;
    }
    switch(items.type)
    {
        case 0:
          items.title = roleDict[values['role']].text;
          break;
        case 1:
          items.title = userDict[values['userid']].text;
          break;
        case 2:
          items.title = tagDict[values['tag']].text;
          break;
    }
    if(currentStep){
      newData.splice(index, 1, {
            ...items
          });
    }else{
      items.key = index+1;
      newData.push(items);
    }
    console.log(newData);
    setNotifyData([...newData]);
    return true;
  };

  const deleteNotifyStepItem = (item: React.SetStateAction<TableListItem | undefined>) => {
    Modal.confirm({
      title: '删除',
      content: '确定要删除吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        const newData = currentRow;
        newData.templateData.notify = currentRow?.templateData.notify.filter((i) => i.key !== item.key);
        for(var i in newData.templateData.notify){
          newData.templateData.notify[i].key=parseInt(i)+1;
        }
        setNotifyData(newData.templateData.notify);
      },
    });
  };

  const approvalColumns = [
    {
      title: 'Step',
      dataIndex: 'key',
    },
    {
      title: '审批人',
      dataIndex: 'title',
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      render: (_, entity) => 
        <Space size="middle"><a
          key="aedit"
          onClick={() => {
            console.log(entity);
            setCurrentStep(entity);
            setStepTypeVal(entity.type.toString());
            setCheckWxUserId(entity.type==1?entity.userid:0);
            setShowApprovalStepForm(true);
          }}
        >
          修改
        </a>
        <a
          key="adelete"
          onClick={() => {
            deleteApprovalStepItem(entity);
          }}
        >
          删除
        </a></Space>,
    },
  ];
  const notifyColumns = [
    {
      title: 'Step',
      dataIndex: 'key',
    },
    {
      title: '抄送人',
      dataIndex: 'title',
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      render: (_, entity) => <Space size="middle">
        <a
          key="nedit"
          onClick={() => {
            setCurrentStep(entity);
            setStepTypeVal(entity.type.toString());
            setCheckWxUserId(entity.type==1?entity.userid:0);
            setShowNotifyStepForm(true);
          }}
        >
          修改
        </a>
        <a
          key="ndelete"
          onClick={() => {
            deleteNotifyStepItem(entity);
          }}
        >
          删除
        </a></Space>,
    },
  ];
  const columns: ProFormColumnsType<TableListItem>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInSearch: true,
      hideInForm: true,
      hideInDescriptions: true,
    },
    {
      title: '模板ID',
      dataIndex: 'templateId',
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
      title: '模板名称',
      dataIndex: 'templateName',
    },
    {
      title: '应用名称',
      dataIndex: 'appname',
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
            setShowDetail(false);
            setCurrentRow(entity);
            setApprovalData(entity.templateData.approval);
            setNotifyData(entity.templateData.notify);
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

  useImperativeHandle(ref, () => ({
    reload: (id: number) => {
      setAppid(id);
      actionRef.current?.reload();
    },
  }));

  useEffect(() => {
    getDict({ id: -1, type: 'dict' }).then((res) => {
      setApprovalAttrDict(res.data.approvalAttr);
      setNotifyAttrDict(res.data.notifyAttr);
      setAppDict(res.data.app);
      setLevelDict(res.data.level);
      setRoleDict(res.data.role);
      setTagDict(res.data.tag);
      setUserDict(res.data.user);
    }); 
    
  },[])
  return (
    <>
      <ProTable<TableListItem, TableListPagination>
        headerTitle="流程模板列表"
        actionRef={actionRef}
        rowKey="id"
        search={{
          labelWidth: 120,
        }}
        request={(params, sorter, filter) => {
          document.body.scrollTop = document.documentElement.scrollTop = 0;
          params.appid = appid;
          return flowtemplate(params);
        }}
        columns={columns as ProColumns<TableListItem>[]}
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
              setApprovalData(undefined);
              setNotifyData(undefined);
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
      <Drawer
        width={browser.mobile() ? '100vw' : 600}
        visible={showDetail}
        onClose={() => {
          setCurrentRow(undefined);
          setShowDetail(false);
        }}
        closable={true}
      >
        <ProDescriptions<TableListItem>
          column={1}
          title="详情"
          request={async () => ({
            data: currentRow || {},
          })}
          params={{
            id: currentRow?.id,
          }}
          columns={columns as ProDescriptionsItemProps<TableListItem>[]}
        >
          <ProDescriptions.Item label="审批流程" >
            <Steps direction="vertical" size="small" current={0}>
              {currentRow?.templateData.approval.map((item)=>{return <Step key={'step_'+item.key} title={item.title} status='process' />})}
            </Steps>
          </ProDescriptions.Item>
          <ProDescriptions.Item label="抄送" >
            {currentRow?.templateData.notify.map((item)=>{return <span key={'span_'+item.key} style={{marginRight: 10}}>{item.title}</span>})}
          </ProDescriptions.Item>
          <ProDescriptions.Item
            label="抄送通知"
            valueEnum={notifyAttrDict}
          >
            {currentRow?.notifyAttr}
          </ProDescriptions.Item>
        </ProDescriptions>
      </Drawer>
      <DrawerForm
        title="编辑流程模板信息"
        width={browser.mobile() ? '100vw' : 600}
        visible={showForm}
        onVisibleChange={setShowForm}
        formRef={formRef}
        autoFocusFirstInput
        drawerProps={{
          destroyOnClose: true,
          onClose: () => {
            setApprovalData(undefined);
            setNotifyData(undefined);   
            setCurrentRow(undefined);         
          },
        }}
        submitter={{ searchConfig: { submitText: '提交' } }}
        submitTimeout={2000}
        onFinish={async (values) => {
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
          appid:currentRow?.appid?currentRow?.appid:appid||undefined,
          notifyAttr:currentRow?.notifyAttr,
        }}
        layout="vertical"
        grid={true}
      >
      <ProForm.Group>
      <ProFormSelect
        width="md"
        valueEnum={appDict}
        name="appid"
        label="所属应用"
        allowClear={false}
        rules={[
          {
            required: true,
            message: '请选择应用！',
          },
        ]}
      />
          </ProForm.Group>
    <ProForm.Group>
        <ProFormText  width="xl" name="templateName" label="模板名称"  />
    </ProForm.Group>    
    <ProForm.Group>
      
    <Tabs defaultActiveKey="1">
          <TabPane tab="默认审批人" key="1" forceRender>
            <Button
                    type="primary"
                    key="primary"
                    onClick={() => {
                      setCurrentStep(undefined);
                      setStepTypeVal(undefined);
                      setCheckWxUserId(undefined);
                      setShowApprovalStepForm(true);
                    }}
                  >
                    <PlusOutlined /> 新增审批人
                  </Button>
            <Table dataSource={approvalData} columns={approvalColumns} pagination={false} />
          </TabPane>
          <TabPane tab="默认抄送人" key="2" forceRender>
            <Button
                  type="primary"
                  key="primary"
                  onClick={() => {
                    setCurrentStep(undefined);
                    setStepTypeVal(undefined);
                    setCheckWxUserId(undefined);
                    setShowNotifyStepForm(true);
                  }}
                >
                  <PlusOutlined /> 新增抄送人
                </Button>
            <Table dataSource={notifyData} columns={notifyColumns} pagination={false} />
          </TabPane>
        </Tabs>
      </ProForm.Group>     
        <ProForm.Group>        
          <ProFormSelect
            width="sm"
            valueEnum={notifyAttrDict}
            name="notifyAttr"
            label="抄送通知"
            allowClear={false}
            rules={[
              {
                required: true,
                message: '请选择抄送通知！',
              },
            ]}
          />
        </ProForm.Group>
      </DrawerForm>
      <ModalForm
        title="编辑审批流程步骤信息"
        width={browser.mobile() ? '100vw' : 600}
        visible={showApprovalStepForm}
        onVisibleChange={setShowApprovalStepForm}
        autoFocusFirstInput
        modalProps={{
          destroyOnClose: true,
          onClose: () => {
            setCurrentStep(undefined);
            setStepTypeVal(undefined);
            setCheckWxUserId(undefined);            
          },
        }}
        submitter={{ searchConfig: { submitText: '提交' } }}
        submitTimeout={2000}
        onFinish={modifyApprovalStepItem}
        initialValues={{
          ...currentStep,
          role:currentStep && currentStep.role?roleDict[currentStep.role].text:'',
          attr:currentStep && currentStep.attr.toString(),
          skip:currentStep && currentStep.skip==1?true:false,
          span:currentStep && currentStep.span==1?true:false
        }}
        layout="vertical"
        grid={true}
      >
      <ProForm.Group>
      <Tabs activeKey={stepTypeVal} onChange={stepTypeChange}>
          <TabPane tab="角色" key="0" forceRender>
            <ProFormSelect
              width="md"
              valueEnum={roleDict}
              name="role"
              placeholder="请选择角色"
              allowClear={false}
            />
          </TabPane>
          <TabPane tab="个人" key="1" forceRender>
          <DeptSel local={true} showLeafIcon={true} showAll={true} showUser={true} checkable={false} ref={wxuserRef} checkedKeys={wxCheckUserId} width='300px' showCheckedStrategy='SHOW_CHILD' />
          </TabPane>
          <TabPane tab="标签" key="2" forceRender>
            <ProFormSelect
              width="md"
              valueEnum={tagDict}
              name="tag"
              placeholder="请选择标签"
              allowClear={false}
            />
          </TabPane>
          <TabPane tab="上级" key="3" forceRender>
            <ProFormDigit
              label="（自动设置通讯录中的上级领导为审批人）"
              placeholder="请填写领导所在层级(1-5)"
              name="level"
              min={1}
              max={5}
              fieldProps={{ precision: 0 }}
            />
          </TabPane>
          <TabPane tab="手动选择" key="6" forceRender>
            
          </TabPane>
          <TabPane tab="主体负责人" key="8" forceRender>
            
          </TabPane>
        </Tabs>
        <Divider dashed />
          </ProForm.Group>
        <ProForm.Group>        
        <ProFormRadio.Group
          name="attr"
          label="请选择审批方式："
          fieldProps={{
            value: approvalAttrVal,
            onChange: (e) => setApprovalAttrVal(e.target.value),
          }}
          options={[
            {
              label: '会签（ 须所有成员同意 ）',
              value: '2',
            },
            {
              label: '或签（ 一名成员同意即可 ）',
              value: '1',
            },
          ]}
        />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormSwitch name="skip" label="是否可跳过" />
          <ProFormSwitch name="span" label="是否跨部门" />
        </ProForm.Group>
      </ModalForm>
      <ModalForm
        title="编辑抄送信息"
        width={browser.mobile() ? '100vw' : 600}
        visible={showNotifyStepForm}
        onVisibleChange={setShowNotifyStepForm}
        autoFocusFirstInput
        modalProps={{
          destroyOnClose: true,
          onClose: () => {
            setCurrentStep(undefined);
            setStepTypeVal(undefined);
            setCheckWxUserId(undefined);            
          },
        }}
        submitter={{ searchConfig: { submitText: '提交' } }}
        submitTimeout={2000}
        onFinish={modifyNotifyStepItem}
        initialValues={{
          ...currentStep,
          role:currentStep && currentStep.role?roleDict[currentStep.role].text:'',
        }}
        layout="vertical"
        grid={true}
      >
      <ProForm.Group>
      <Tabs activeKey={stepTypeVal} onChange={stepTypeChange}>
          <TabPane tab="角色" key="0" forceRender>
            <ProFormSelect
              width="md"
              valueEnum={roleDict}
              name="role"
              placeholder="请选择角色"
              allowClear={false}
            />
          </TabPane>
          <TabPane tab="个人" key="1" forceRender>
          <DeptSel local={true} showLeafIcon={true} showAll={true} showUser={true} checkable={false} ref={wxuserRef} checkedKeys={wxCheckUserId} width='300px' showCheckedStrategy='SHOW_CHILD' />
          </TabPane>
          <TabPane tab="标签" key="2" forceRender>
            <ProFormSelect
              width="md"
              valueEnum={tagDict}
              name="tag"
              placeholder="请选择标签"
              allowClear={false}
            />
          </TabPane>
        </Tabs>
        </ProForm.Group>
      </ModalForm>
    </>
  );
});

export default List;
