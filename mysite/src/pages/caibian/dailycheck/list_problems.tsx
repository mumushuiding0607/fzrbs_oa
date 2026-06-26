import type { ActionType, ProFormColumnsType, ProFormInstance } from '@ant-design/pro-components';
import {

  ProTable,
} from '@ant-design/pro-components';
import { Button, Modal } from 'antd';
;
import { useRef, useState } from 'react';

import Problemstates from './problem_states';
import Addproblem from './addproblem';
import { delproblem, getproblems } from './service';

const Listproblems:React.FC<{}> = ({}) => {
  const proTableFormRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  const [entity,setEntity]=useState<any>({})
  var [params,setParams] = useState<any>({})
  var [refresh,setRefresh] = useState(0)
  const [states,setStates]=useState<any>([])

  const [showModal,setShowModal]=useState(false)

  const columns: ProFormColumnsType<any>[] = [
    
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      width: 50,
      render:(_:any,record:any,index:number)=>`${index+1}`
    },
    {
      title: '内容',
      dataIndex: 'name',
      key: 'name',
      width: 500,
    },
    {
      title: '类型',
      dataIndex: 'state',
      key: 'state',
      width: 120,
      render:(_:any,record:any,index:number)=>`${states&&states.length&&states.length>record.state+1?states[record.state]:record.state}`
    },
    {
      title: '创建人',
      key:'creator',
      dataIndex: 'creator',
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
            setEntity(entity)
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
                  delproblem({id:entity.id}).then((res:any)=>{
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
  const onStatehange = (e:any)=>{
    params.state = e
    setParams(params)
    proTableFormRef.current?.setFieldsValue({state:e})
    actionRef.current?.reload()
  }

  return (
    <>
    <ProTable
      style={{height:'90vh'}}
      headerTitle="必问列表"
      actionRef={actionRef}
      params={params}
      formRef={proTableFormRef}
      rowKey={(record:any)=>record.id}
      search={false}
      columns={columns}
      request={(params, sorter, filter) => {
        
        document.body.scrollTop = document.documentElement.scrollTop = 0;
  
        var res=getproblems(params)
        res.then((e:any)=>{
          setStates(e.states)
        })
        return res;
      }}
      toolbar={{

        filter: (
          <>

            <Problemstates onChange={onStatehange}/>
            
          
          </>
        ),
        actions: [
          <Button
            key="primary"
            type="primary"
            onClick={() => {
              setEntity({state:0})
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
          title='必问更新'
           
          maskClosable={false}
          width={850}
          style={{ top: 20}}
          visible={showModal}
          onOk={() => setShowModal(false)}
          onCancel={() => setShowModal(false)}
          footer= {null}
        >
          
          <Addproblem key={refresh} data={entity} onChange={addonchange} />
        </Modal>
    
  </>);
};
export default Listproblems