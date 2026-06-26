import type { ActionType, ProFormColumnsType, ProFormInstance } from '@ant-design/pro-components';
import {
  LightFilter,
  ProTable,
} from '@ant-design/pro-components';
import { Button, Input, Modal } from 'antd';
import { useRef, useState } from 'react';
import { delfinanceflow, financetemplatelist } from './service';
import AddFinanceTemplate from './addfinance';
import Dictselect from '../budget/dict/dictselect';
const FinanceTemplatelist:React.FC<{}> = ({}) => {
  const proTableFormRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  const [role,setRole] = useState({})
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
      hideInTable: true,
      hideInDescriptions: true,
    },

    {
      title: '名称',
      dataIndex: 'templatename',
      width:200,
    },
    {
      title: '流程id',
      dataIndex: 'templateid',
      width:200,
      
    },
    {
      title: '流程名',
      dataIndex: 'tname',
      width:300,
    },
    {
      title: '金额大于',
      dataIndex: 'lamount',
      width:80,
    },
    {
      title: '金额小于',
      dataIndex: 'hamount',
      width:80,
    },
    {
      title: '创建人',
      dataIndex: 'creatorname',
      width:80,
    },
    {
      title: '更新人',
      dataIndex: 'updator',
      width:80,
    },
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      width:120,
      render: (_:any, entity:any) => [
        <a
          key="edit"
          onClick={() => {
            if (entity.dept && typeof entity.dept === 'string') entity.dept = entity.dept.split(',')
            entity.amount = [entity.lamount||0,entity.hamount||0]
            
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
                  return delfinanceflow({id:entity.id}).then((res:any)=>{
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
    params.current = 1
    setParams(params)
    
    // 设置当前页为1
    
    proTableFormRef.current?.setFieldsValue({keyword:e.target.value,current:1})

    
  }


  return (
    <>
    <ProTable
      style={{minHeight:'90vh'}}
      headerTitle="财务流程设置"
      actionRef={actionRef}
      params={params}
      formRef={proTableFormRef}
      rowKey={(record:any)=>record.id}
      search={false}
      columns={columns}

      request={(params, sorter, filter) => {
        
        document.body.scrollTop = document.documentElement.scrollTop = 0;

        return financetemplatelist(params);
      }}
      toolbar={{

        filter: (
          <>
            <Dictselect style={{ width: '150px' }}  type={"付款审批类型"} multiple={false}  needAddItem={false} onChange={(value:any)=>{
              params.type=value
              setParams(params)
            }}/>
            <Input style={{ width: '200px' }} onChange={handleInputChange}  placeholder="名称、id" />


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
    <AddFinanceTemplate data={role} key={refresh} onChange={addonchange} visible={showModal} onVisibleChange={setShowModal}/>
  </>);
};
export default FinanceTemplatelist