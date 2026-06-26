import type { ActionType, ProFormColumnsType, ProFormInstance } from '@ant-design/pro-components';
import {
  LightFilter,
  ProFormDatePicker,
  ProTable,
} from '@ant-design/pro-components';
import { Button, Modal } from 'antd';
import { useRef, useState } from 'react';
import Addrole from './add';

import { delinvoicer, getinvoicerlist } from './service';
import UserAutocomplete from '../../budget/common/userAutocomplete';
import Add from './add';






const InvoicerList:React.FC<{agentid?:any,type:string}> = ({agentid,type}) => {
  const proTableFormRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  const [role,setRole] = useState({})
  const [power,setPower] = useState({})
  var [params,setParams] = useState<any>({})
  var [refresh,setRefresh] = useState(0)

  const [showModal,setShowModal]=useState(false)
  const [pmodal,setPmodal]=useState(false)
  const columns: ProFormColumnsType<any>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInSearch: true,
      hideInForm: true,
      hideInDescriptions: true,
    },
    {
      title: '姓名',
      dataIndex: 'username',
    },
    {
      title: '更新人',
      dataIndex: 'updator',
    },
    {
      title: '更新时间',
      dataIndex: 'updatetime',
    },

    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      render: (_, entity:any) => [
        <a
          key="edit"
          onClick={() => {
            if (entity.dept && typeof entity.dept === 'string') entity.dept = entity.dept.split(',')
            setRole(entity)
            setRefresh(++refresh)
            setShowModal(true)
          }}
        >
          修改
        </a>,
        <a
          key="delete"
          onClick={() => {
            Modal.confirm({
                title: '确定要删除吗？',
                okText: '确认',
                cancelText: '取消',
                onOk: async () => {
                  delinvoicer({id:entity.id,agentid}).then((res:any)=>{
                    if (res.errorMessage){
                      Modal.error({title:res.errorMessage})
                    } else {
                      actionRef.current?.reload()
                    }
                  })
                },
              });
           }}
        >
          删除
        </a>,
      ],
    },
  ];
  const  addonchange = (e:any)=>{

    actionRef.current?.reload()
    setShowModal(false)
  } 
  const onPowerchange = (e:any)=>{
    params.power = e
    setParams(params)
    proTableFormRef.current?.setFieldsValue({power:e})
    actionRef.current?.reload()
  }
  const onRolechange = (e:any)=>{

    
    params.role = e
    setParams(params)
    proTableFormRef.current?.setFieldsValue({role:e})

    actionRef.current?.reload()
  }
  const onUserchange = (e:any)=>{
 
    params.username = e?e.label:''

    actionRef.current?.reload() 
  }
  return (
    <>
    <ProTable
      style={{minHeight:'90vh'}}
      headerTitle="开票员列表"
      actionRef={actionRef}
      params={params}
      formRef={proTableFormRef}
      rowKey={(record:any)=>record.id}
      search={false}
      columns={columns}
      request={(params, sorter, filter) => {
        
        document.body.scrollTop = document.documentElement.scrollTop = 0;
      
        return getinvoicerlist(params);
      }}
      toolbar={{

        filter: (
          <>
           
        
            <UserAutocomplete multiple={false} onChange={onUserchange} />
            
          
          </>
        ),
        actions: [
          <Button
            key="primary"
            type="primary"
            onClick={() => {
              setRole({})
              setRefresh(++refresh)
              setShowModal(true)
            }}
          >
            添加
          </Button>
        ],
      }}
   
      
    />
    <Modal
        title="角色"
        style={{ top: 20, }}
        visible={showModal}
        onOk={() => setShowModal(false)}
        onCancel={() => setShowModal(false)}
        footer={null}
      >
        <Add  key={refresh} data={role} onChange={addonchange}></Add>
      </Modal>

  </>);
};
export default InvoicerList