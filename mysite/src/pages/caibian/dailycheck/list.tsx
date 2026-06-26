import Dictselect from "@/pages/finance/budget/dict/dictselect";
import { PlusOutlined } from "@ant-design/icons";
import { ActionType, PageContainer, ProFormInstance, ProTable } from "@ant-design/pro-components";
import { Button, Modal, Radio, Tag } from "antd"
import { useRef, useState } from "react";
import { getdailychecklist } from "./service";
import moment from "moment";
import Add from "./add";
import Addproblem from "./addproblem";
import Listproblems from "./list_problems";
import View from "./view";
import { render } from "react-dom";

const List:React.FC = () =>{
  const ref = useRef<ActionType>();
  const formRef = useRef<ProFormInstance>();
  const _tagColors = ['default','blue','green','error','default']
  const [tabtype,setTabtype]=useState('我的申请')
  const [tabtypes,setTabtypes] = useState<any>([])
  const [statusAll,setStatusAll]=useState<any>([])
  
  const [modal1, setModal1] = useState(false)
  const [modal2, setModal2] = useState(false)
  const [modal3, setModal3] = useState(false)
  const [record, setRecord] = useState<any>({})
  var [resfreshkey,setResfreshkey] = useState(0)
  let columns:any = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      search:false,
      width: 50,
      render:(_:any,record:any,index:number)=>`${index+1}`
    },
    {
      title: '编号',
      key:'thirdNo',
      dataIndex: 'thirdNo',
      width: 100,
      render:(_:any,record:any,index:number)=><a href="#" onClick={()=>{
        setModal3(true)
        setResfreshkey(++resfreshkey)
        setRecord(record)
      }}>{record.thirdNo}</a>
    },
    {
      title: '名称',
      key:'annex',
      dataIndex: 'annex',
      width: 130,
      render:(_:any,record:any,index:number)=><span onClick={()=>{
        setModal3(true)
        setResfreshkey(++resfreshkey)
        setRecord(record)
      }}>{(record.annex||'').substring(0,8)+' '+(['2',2].includes((record.annex||'').substring(8,9))?'夜班':'白班')}</span>
    },
    
    {
      title: '状态',
      key:'status',
      dataIndex: 'status',
      width:50,
      render:(_:any,record:any,index:number)=><Tag color={_tagColors[record.status]} style={{margin: '0 5px 0 0',padding: '0px 4px',borderRadius: '15%',}}>{statusAll.length>=record.status?statusAll[record.status]:record.status}</Tag>
    },
   
    {
      title: '申请日期',
      dataIndex: 'inserttime',
      key: 'inserttime',
      valueType: 'dateRange',
      width: 120,
      fieldProps: {
        // 你可以在这里自定义时间选择器的行为
        format: 'YYYY-MM-DD',
        
      },
      render: (_:any, record:any) => {
        return record.inserttime?moment(record.signdate).format('YYYY-MM-DD'):''
      },
    },
    {
      title: '申请人',
      key:'userName',
      dataIndex: 'userName',
    },
    {
      title: '部门',
      key:'department',
      dataIndex: 'department',
      width:200
    },
    {
      title: '审批人',
      key:'approvalUsername',
      dataIndex: 'approvalUsername',
    },



  ]
  const onAddcSuc = (e:any)=>{
    
    ref.current?.reload()
    setModal1(false)

  }
  const onViewChange = (e:any)=>{
    
    ref.current?.reload()
    setModal3(false)

  }
  
  const onTabtypeChange =(e:any)=>{
   
    setTabtype(e.target.value)

    ref.current?.reload(true)
  }
  return (<>
  <PageContainer title="每日必问" header={{breadcrumb: {},}}>
        <ProTable
          tableLayout={'fixed'}

          scroll={{x:'max-content'}}
 
          
          pagination={{pageSize:20}}
          rowKey={record=>record.id}
          request={(params, sorter, filter) => {
            document.body.scrollTop = document.documentElement.scrollTop = 0;
            
            if (tabtype) params.tabtype = tabtype
            var res =  getdailychecklist(params)
            res.then((e:any)=>{
              setTabtypes(e.tabtypes)
              setStatusAll(e.statusAll)
            })
            return res;
          }}
          actionRef={ref}
          formRef={formRef}
          columns={columns}
          
      
          form={{
           
          }}
          headerTitle={
            <Radio.Group value={tabtype} onChange={onTabtypeChange} optionType="button" size='large' buttonStyle="solid" style={{ margin: 0 }}>
              {
                tabtypes && tabtypes.length &&tabtypes.length>0 && tabtypes.map((item:any,index:any)=>(
                  <Radio.Button key={index} value={item}>{item}</Radio.Button>
                ))
              }
   

            </Radio.Group>
          }
          
          title={()=>[]}
          toolBarRender={() => [
          
            <Button
              type="primary"
              key="primary"
              
              onClick={() => {
                setModal1(true)
                setResfreshkey(++resfreshkey)
              }}
            >
              <PlusOutlined /> 发起申请
            </Button>,
            <Button
            onClick={() => {
              setModal2(true)
            }}
          >
            <PlusOutlined /> 必问条目
          </Button>,
          ]}
          
        />
        <Modal
          title='发起申请'
           
          maskClosable={false}
          width={850}
          style={{ top: 20}}
          visible={modal1}
          onOk={() => setModal1(false)}
          onCancel={() => setModal1(false)}
          footer= {null}
        >
          
          <Add key={resfreshkey} onChange={onAddcSuc}/>
        </Modal>
        <Modal
         
          width='100vw'
          style={{top:0,right:0}}
          visible={modal2}
          onOk={() => setModal2(false)}
          onCancel={() => setModal2(false)}
          footer= {null}
        >
          <Listproblems/>
          
        </Modal>
        <Modal
          title='流程'
           
          maskClosable={false}
          width={850}
          style={{ top: 20}}
          visible={modal3}
          onOk={() => setModal3(false)}
          onCancel={() => setModal3(false)}
          footer= {null}
        >
          
          <View key={resfreshkey} onChange={onViewChange} thirdNo={record.thirdNo}/>
        </Modal>
      </PageContainer>
    
  </>)
}
export default List