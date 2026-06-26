import type { ActionType, ProFormColumnsType, ProFormInstance } from '@ant-design/pro-components';
import {
  LightFilter,
  ProTable,
} from '@ant-design/pro-components';
import { Button, Input, Modal } from 'antd';
import { useRef, useState } from 'react';
import { delpayer, financetemplatelist, payerlist } from './service';
import AddFinanceTemplate from './addfinance';
import AddPayer from './addpayer';
const Payerlist:React.FC<{}> = ({}) => {
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
      title: '付款单位',
      dataIndex: 'company',
    },
    {
      title: '负责人',
      dataIndex: 'username',
    },
    {
      title: '所跨部门',
      dataIndex: 'crossdeptname',
      render: (text:any,record:any)=>(
        <>
  
            {text}
        </>
      )
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
                return delpayer({id:entity.id}).then((res:any)=>{
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
      headerTitle="付款单位设置"
      actionRef={actionRef}
      params={params}
      formRef={proTableFormRef}
      rowKey={(record:any)=>record.id}
      search={false}
      columns={columns}
      request={(params, sorter, filter) => {
        
        document.body.scrollTop = document.documentElement.scrollTop = 0;
 
        return payerlist(params);
      }}
      toolbar={{

        filter: (
          <>
            <Input style={{ width: '200px' }} onChange={handleInputChange}  placeholder="名称" />


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
          </Button>,

        ],
      }}
   
      
    />
    <AddPayer data={role} key={refresh} onChange={addonchange} visible={showModal} onVisibleChange={setShowModal}/>
  </>);
};
export default Payerlist