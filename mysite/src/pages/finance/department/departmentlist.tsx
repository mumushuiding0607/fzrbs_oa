import type { ActionType, ProFormColumnsType, ProFormInstance } from '@ant-design/pro-components';
import {
  LightFilter,
  ProFormDatePicker,
  ProTable,
} from '@ant-design/pro-components';
import { Button, Input, Modal } from 'antd';
import { useRef, useState } from 'react';
import DeptcodeModal from './deptcodeModal';
import { getdeptlist } from './service';
import DepartmentTreeSelect from '../budget/common/department_treeselect';







const Departmentlist:React.FC<{}> = ({}) => {
  const proTableFormRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  var [params,setParams] = useState<any>({})
  const [deptcodeModal,setDeptcodeModal]=useState(false)
  const [department,setDepartment]=useState<any>({})
  const [departmentid,setDepartmentid]=useState('')
  const columns: ProFormColumnsType<any>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInSearch: true,
      hideInForm: true,
      hideInDescriptions: true,
    },
    {
      title: '部门名称',
      dataIndex: 'name',
    },
    {
      title: '部门简码',
      dataIndex: 'code',
      render: (text:any, entity:any) => [
        <a
          key="edit"
          onClick={() => {
            setDeptcodeModal(true)
            setDepartment(entity)
          }}
        >
          {text&&text!='-'?text:'设置'}
        </a>,

      ],
    },
 

  ];
  const onDeartChange = (e:any)=>{
      
    setDepartmentid(e.join(','))
    actionRef.current?.reload()
  }

  return (
    <>
    <ProTable
      style={{minHeight:'90vh'}}
      headerTitle="部门"
      actionRef={actionRef}
      params={params}
      formRef={proTableFormRef}
      rowKey={(record:any)=>record.id}
      search={false}
      columns={columns}
      request={(params, sorter, filter) => {
        document.body.scrollTop = document.documentElement.scrollTop = 0;
        if (departmentid) params.departmentid = departmentid
        return getdeptlist(params);
      }}
      toolbar={{

        filter: (
          <>
            
            <LightFilter name='id'><DepartmentTreeSelect   maxTagCount={1}  style={{minWidth:'300px'}} onChange={onDeartChange}/></LightFilter>
          </>
        ),
      }}
   
      
    />
    <DeptcodeModal visible={deptcodeModal} department={department} onVisibleChange={()=>setDeptcodeModal(false)}/>
  </>);
};
export default Departmentlist