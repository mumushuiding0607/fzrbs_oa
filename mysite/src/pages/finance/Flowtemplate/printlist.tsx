import type { ActionType, ProFormColumnsType, ProFormInstance } from '@ant-design/pro-components';
import {
  LightFilter,
  ProTable,
} from '@ant-design/pro-components';
import { Button, Input, Modal } from 'antd';
import { useRef, useState } from 'react';
import { delpayer, delprintposition, payerlist, printpositionlist } from './service';

import AddPayer from './addpayer';
import AddPrintPosition from './AddPrintPosition';
import { FINANCE_AGENTID } from '../config';
import Roleselect from '../role/roleselect';
import TagSelect from './components/TagSelect';
import User from 'mock/user';
import UserAutocomplete from '../budget/common/userAutocomplete';
const Printlist:React.FC<{}> = ({}) => {
  const proTableFormRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  const [role,setRole] = useState({})
  var [params,setParams] = useState<any>({})
  var [refresh,setRefresh] = useState(0)

  const [showModal,setShowModal]=useState(false)
  const columns: ProFormColumnsType<any>[] = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInSearch: true,
      hideInForm: true,
      hideInTable: true,
      hideInDescriptions: true,
    },

    {
      title: '应用',
      dataIndex: 'appname',
    },
    {
      title: '类型',
      dataIndex: 'type',
      valueType: 'select',
      valueEnum: {
        0: { text: '角色' },
        3: { text: '上级' },
        20: { text: '个人' },
        8: { text: '主体负责人' },
        2: { text: '标签' },
      },

    },
    {
      title: '名称',
      dataIndex: 'rolename',
    },

    {
      title: '创建人',
      dataIndex: 'creatorname',
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
      render: (_:any, entity:any) => [
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
          key="edit"
          onClick={() => {
            Modal.confirm({
              title: '确定删除吗？',
              icon: null,
              onOk: () => {
                return delprintposition({id:entity.id}).then((res:any)=>{
                  if (res.errorMessage) {
                    Modal.error({title: res.errorMessage})
                  } else {
                    actionRef.current?.reload()
                    Modal.info({title: '删除成功'})
                  }
                })
              },
            })
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
  const handleInputChange = (e:any)=>{

    params.keyword = e.target.value
    setParams(params)
    proTableFormRef.current?.setFieldsValue({keyword:e.target.value})

    
  }


  return (
    <>
    <ProTable
      style={{minHeight:'90vh'}}
      headerTitle="角色打印位置"
      actionRef={actionRef}
      params={params}
      formRef={proTableFormRef}
      rowKey={(record:any)=>record.id}
      search={false}
      columns={columns}
      request={(params, sorter, filter) => {
        
        document.body.scrollTop = document.documentElement.scrollTop = 0;
        // setParams(params);
        return printpositionlist(params);
      }}
      toolbar={{

        filter: (
          <>
            <Roleselect needAddItem={false} style={{width:'150px'}} onChange={(value:any)=>{ 
              params.role = value
              params.current =1
              setParams(params)
            }}/>
            <TagSelect style={{width:'150px'}} onChange={(value:any)=>{ 
              params.current =1
              params.tag = value
              setParams(params)
            }}/>
            <UserAutocomplete multiple={false} onChange={(value:any)=>{ 
              if(value){
                params.userid = value.value
              }else{
                params.userid = ''
              
              }
              params.current =1
              setParams(params)
            }}/>
            <Input style={{ width: '200px' }} onChange={handleInputChange}  placeholder="名称" />


          </>
        ),
        actions: [
          <Button
            key="primary"
            type="primary"
            onClick={() => {
              setRole({agentid:FINANCE_AGENTID})
              setRefresh(++refresh)
              setShowModal(true)
            }}
          >
            添加
          </Button>,

        ],
      }}
   
      
    />
    <AddPrintPosition data={role} key={refresh} onChange={addonchange} visible={showModal} onVisibleChange={setShowModal}/>
  </>);
};
export default Printlist