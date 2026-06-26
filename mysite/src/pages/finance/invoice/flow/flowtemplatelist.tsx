import type { ActionType, ProFormInstance } from '@ant-design/pro-components';
import {
  ProTable,
} from '@ant-design/pro-components';
import { Button, Modal } from 'antd';
import { useRef, useState } from 'react';
import { deltemplate, gettmplatelist } from './service';
import Add from './add';
import { render } from 'react-dom';
import Dictselect from '../../budget/dict/dictselect';







const Flowtemplatelist:React.FC<{agentid?:any}> = ({agentid}) => {
  const proTableFormRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  const [data,setData]=useState<any>({});
  var [params,setParams] = useState<any>({})
  var [refresh,setRefresh] = useState(0)

  const [showModal,setShowModal]=useState(false)
  const columns = [
    {
      title: 'ID',
      dataIndex: 'id',
      hideInSearch: true,
      hideInForm: true,
      hideInDescriptions: true,
    },
  
    {
      title: '流程名称',
      dataIndex: 'templatename',
    },
    {
      title: '流程id',
      dataIndex: 'templateid',
    },
    {
      title: '审批类型',
      dataIndex: 'types',

    },
    {
      title: '合同业务',
      dataIndex: 'contract',
      // 该字段对应字典表中的key值，渲染时显示字典对应的label值
      renderFormItem: () => <Dictselect type={"合同业务类型"} multiple={true} needAddItem={false}/>,


    },
    {
      title: '有无合同',
      dataIndex: 'hascontract',
      render: (text:any,record:any)=>{
        return text==1?'有':'无'
      }
    },
    {
      title: '金额大于',
      dataIndex: 'lamount',
    },
    {
      title: '金额小于',
      dataIndex: 'hamount',
    },
   
  
    {
      title: '操作',
      dataIndex: 'option',
      valueType: 'option',
      render: (_:any, entity:any) => [
        <a
          key="edit"
          onClick={() => {
            setData(entity)
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
                  deltemplate({id:entity.id}).then((res:any)=>{
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
  
  
  
  const  onAddSuc = (e:any)=>{

    actionRef.current?.reload()
    setShowModal(false)
  } 
  return (
    <>
    <ProTable
      style={{minHeight:'90vh'}}
      headerTitle="流程列表"
      actionRef={actionRef}
      params={params}
      formRef={proTableFormRef}
      rowKey={(record:any)=>record.id}
      search={false}
      columns={columns}
      request={(params, sorter, filter) => {
        
        document.body.scrollTop = document.documentElement.scrollTop = 0;
        params.agentid = agentid
        return gettmplatelist(params);
      }}
      toolbar={{

        filter: (
          <>
           

            
          
          </>
        ),
        actions: [
          <Button
            key="primary"
            type="primary"
            onClick={() => {
              setData({})
              setRefresh(++refresh)
              setShowModal(true)
            }}
          >
            添加
          </Button>,

        ],
      }}
   
      
    />

    <Add key={refresh} visible={showModal} data={data} onVisibleChange={setShowModal} onChange={onAddSuc}/>
  </>);
};
export default Flowtemplatelist