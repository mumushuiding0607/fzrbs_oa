import { Modal,Switch } from 'antd';
import React, { useEffect, useImperativeHandle, useRef } from 'react';
import { useState } from 'react';
import { ProForm, ProFormInstance, ProFormSelect,ProFormText,ProFormDateRangePicker } from '@ant-design/pro-components';
import DeptSel from '@/components/DepartmentTreeSelect';

import tools from '@/utils/tools';

export type RechargeModalProps = {
  deptTreeIds: any;
  leaveTypeArr: any;
  statusArr: any;
  isoutDict: any;
  onOk?: (values: object) => void;
};

const LeaveExportModal = React.forwardRef((props: RechargeModalProps, ref) => {
  const [visible, setVisible] = useState(false);
  const [userType, setUserType] = useState<any>({});
  const deptRef = useRef();
  const formRef = useRef<ProFormInstance>();
  const switchTypeChange = (checked:boolean)=>{
    if(checked){
      let leaveOption = [];
      for(let i in props.leaveTypeArr){
        leaveOption.push(props.leaveTypeArr[i].text);
      }
      formRef?.current?.setFieldsValue({'search-type':leaveOption});
    }else{
      formRef?.current?.setFieldsValue({'search-type':undefined});
    }
  }
  const switchStatusChange = (checked:boolean)=>{
    if(checked){
      formRef?.current?.setFieldsValue({'search-status':['2','5','6']});
    }else{
      formRef?.current?.setFieldsValue({'search-status':undefined});
    }
  }
  const handleCancel = () => setVisible(false);
  const handleOk = async () => {
    const values = formRef?.current?.getFieldsFormatValue();    
    values.departments = deptRef?.current?.getCheckedKeys();
    // console.log(formRef?.current);
    console.log(values);
    let fileName = '请销假信息';
    // if (values.userType && values.userType.length == 1) {
    //   fileName = fileName + '(' + userType[values.userType[0]].text + ')';
    // }
    tools.downloadFile('/api/leave/leaveinfoDownload', values, fileName + '.xls');
    setVisible(false);
  };

  useImperativeHandle(ref, () => ({
    setVisible: (value: boolean) => {
      setVisible(value);
    },
  }));

  useEffect(() => {

  }, []);

  return (
    <Modal visible={visible} title="请销假信息导出" onCancel={handleCancel} onOk={handleOk}>
      <ProForm layout="vertical" formRef={formRef} submitter={false}>
              <ProFormText width="md" key="search-name" name="search-name" label="姓名" />
              <ProForm.Group label="部门">
                <DeptSel local={true} showLeafIcon={true} showAll={true} checkable={true} ref={deptRef} width='300px' childrenId={props.deptTreeIds} showCheckedStrategy='SHOW_PARENT' />
              </ProForm.Group>
              <ProForm.Group label="申请类别">
                <ProFormSelect.SearchSelect
                  width="md"
                  key="search-type"
                  name="search-type"                  
                  fieldProps={{
                  labelInValue: false,
                  }}
                  valueEnum={{...props.leaveTypeArr,销假:{text:'销假'}}}
                  /><Switch key="switch-type" checkedChildren="请假类" unCheckedChildren="所有" onChange={switchTypeChange} />
              </ProForm.Group>
              
              <ProForm.Group label="状态">
                <ProFormSelect.SearchSelect
                    width="md"
                    key="search-status"
                    name="search-status"
                    fieldProps={{
                    labelInValue: false,
                    }}
                    valueEnum={props.statusArr}
                    // value={switchStatusData}
                /><Switch key="switch-status" checkedChildren="已通过" unCheckedChildren="所有" onChange={switchStatusChange} />
              </ProForm.Group>
                
              <ProFormDateRangePicker name="search-range" label="日期区间" />
              <ProForm.Group>
                <ProFormSelect
                  width="xs"
                  key="search-isout"
                  name="search-isout"
                  label="出行范围"
                  valueEnum={props.isoutDict}
                  />
                  <ProFormText width="md" key="search-destination" name="search-destination" label="出行位置" />
              </ProForm.Group>
      </ProForm>
    </Modal>
  );
});
export default LeaveExportModal;
