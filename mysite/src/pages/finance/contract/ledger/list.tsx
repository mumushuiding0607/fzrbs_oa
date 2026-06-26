import { PlusOutlined } from "@ant-design/icons";
import { ActionType, ProFormInstance, ProTable } from "@ant-design/pro-components";
import { Button, Modal, Popover } from "antd";
import { delledger, downloadpurchase, ledgerlist } from "../service";
import { downloadAsXlSX } from "../../utils";
import { useRef, useState } from "react";
import DepartmentTreeSelect from "../../budget/common/department_treeselect";
import Dictselect from "../../budget/dict/dictselect";
import Companyselect from "../../company/companyselect";
import moment from "moment";
import { useModel } from "umi";
import AddLedger from "./add";
import UserAutocomplete from "../../budget/common/userAutocomplete";
import ViewLedger from "./view";
import TableScrollSync from "../../common/TableScrollSync";


const LedgerList:React.FC<{}> = ({}) =>{

 const [params, setParams] = useState<any>({})
 const ref = useRef<ActionType>();
 const formRef = useRef<ProFormInstance>();
 const { initialState } = useModel<any>('@@initialState');
 const [contract, setContract] = useState<any>({})
 const [viewmodal,setViewmodal] = useState(false)
 var [refreshKey, setRefreshKey]= useState(0)
const { currentUser } = initialState;

const [obj,setObj]=useState<any>({})
const [modal1, setModal1] = useState(false)
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
      title: '部门',
      dataIndex: 'departmentid',
      key: 'departmentid',
      hideInTable:true,
      renderFormItem: () => {

        return (
          <DepartmentTreeSelect maxTagCount={1}/>

        )
      }
    },
    
    {
      title: '采购编号',
      dataIndex: 'ledgerserial',
      key: 'ledgerserial',
      sorter: true,
      width: 150,
      render:(text:any,record:any)=>{
        return (<div style={{textAlign:'left'}} onClick={()=>{
          setViewmodal(true)
          setContract(record)
        }}>
          <p  style={{fontWeight:'bolder',margin:0}}>{text}</p>
          
        </div>)
      }
    },
    {
      title: '采购类别',
      dataIndex: 'typename',
      key: 'typename', 
      hideInSearch: true,
      width: 80,
    },
    {
      title: '项目名称',
      dataIndex: 'title',
      key: 'title',
      width: 200,
      sorter: true,
      render:(text:any,record:any)=>{

       
        return (<div style={{textAlign:'left'}}>
        
          <p  style={{fontWeight:'bolder',margin:0}}>

            <span onClick={()=>{
    
            }}>{text}</span>
          </p>

          
          
        </div>)
      }
    },
    {
      title: '采购内容',
      dataIndex: 'content',
      key: 'content', 
      hideInSearch: true,
    },
    {
      title: '招标代理机构',
      dataIndex: 'agent',
      key: 'agent', 
      renderFormItem: () => {

        return (
          <Companyselect  multiple={false}></Companyselect>

        )
      }
    },

    {
      title: '采购方式',
      dataIndex: 'methodname',
      key: 'methodname', 
      width: 80,
      renderFormItem: () => {

        return (
          <Dictselect type="采购方式" multiple={true} needAddItem={false} ></Dictselect>
        )
      }
    },

    {
      title: '成交供应商',
      dataIndex: 'partbname',
      key: 'partbname',
      hideInSearch:true,
      sorter: true,
      width: 180,
      renderFormItem: () => {

        return (
          <Companyselect  multiple={false}></Companyselect>

        )
      }
    },
    {
      title: '合同金额',
      dataIndex: 'amount',
      key: 'amount',
      hideInSearch:true,
      sorter: true,
      width: 120,
      className:'right',
      render: (text:any)=>!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0,
    },
    {
      title: '是否依约付款',
      dataIndex: 'paydefault',
      key: 'paydefault',
      width: 50,
      hideInSearch:true,
      render: (text:any,record:any)=>{
    
        return (<div style={{textAlign:'left'}} onClick={()=>{
              setViewmodal(true)
              setContract({id:record.contractid})
              setRefreshKey(+refreshKey)
            }}>
          <p  style={{fontWeight:'bolder',margin:0}}>{record.paydefault?'否':"是"}</p>
          
        </div>)
      },
      valueEnum: {
        0: {
          text: '是',
        },
        1: {
          text: '否',
        },
      },
    },
    {
      title: '验收结果',
      dataIndex: 'result',
      key: 'result', 
      width: 80,
      renderFormItem: () => {

        return (
          <Dictselect type="验收结果" multiple={true} needAddItem={false} ></Dictselect>
        )
      }
    },
    {
      title: '文件是否齐全',
      dataIndex: 'file',
      key: 'file',
      width: 120,
      render: (text:any,record:any)=>{

        return (<div style={{textAlign:'left'}}>
          <p  style={{fontWeight:'bolder',margin:0}}>{record.file?'是':"否"}</p>
          
        </div>)
      },
      valueEnum: {
        1: {
          text: '是',
        },
        0: {
          text: '否',
        },
      },
    },
    {
      title: '合同编号',
      dataIndex: 'serial',
      key: 'serial',
      sorter: true,
      width: 150,
      render:(text:any,record:any)=>{
        return (<div style={{textAlign:'left'}} onClick={()=>{
              setViewmodal(true)
        
              setContract({id:record.contractid})
              setRefreshKey(+refreshKey)
            }}>
          <p  style={{fontWeight:'bolder',margin:0}}>{text}</p>
          
        </div>)
      }
    },
    {
      title: '采购人',
      dataIndex:'creator',
      key:'creator',
      width:100,
      sorter: true,
      search:true,
      render:(_:any,record:any)=>record.creatorname,
      renderFormItem: () => {

        return (
          <UserAutocomplete multiple={false}/>

        )
      }
    },
    {
      title: '备注',
      dataIndex: 'notes',
      key: 'notes', 
      width: 300,
    },
    {
      title: '创建日期',
      dataIndex: 'inserttime',
      key: 'inserttime',
      sorter: true,
      valueType: 'dateRange',
      width: 120,
      fieldProps: {
        // 你可以在这里自定义时间选择器的行为
        format: 'YYYY-MM-DD',
        
      },
      render: (_:any, record:any) => {
        return record.inserttime?moment(record.inserttime).format('YYYY-MM-DD'):''
      },
    },
   
    
    {
      title: '操作',
      key: 'action',
      fixed: 'right',

      onHeaderCell:()=>({
        style:{
          right:'-5px!important'
        }
      }),
      search:false,
      render: (_:any, record:any,index:Number) => (
    
        <>

          
            <Popover
              placement="topLeft"
              trigger={'click'}
              
              key={'popover'+index}
              overlay={<div key={'overlay'+index} id={'overlay'+index}></div>}
              content={(<>
                

                
                {
                  currentUser.wxuserid==record.ledger&&
                  <div>
                    <Button type="text" onClick={()=>{onMenuClick('更新',record)}}>更新</Button>
                    <Button danger type="text" onClick={()=>{onMenuClick('删除',record)}}>删除</Button>
                  </div>
                }
             
        

                </>)
              }
          
            
            >
              <Button key={'button'+index}>操作</Button>
            </Popover>
           
        </>
      ),
    },

  ]
  
  const onMenuClick = (action:String,record:any) => {

    switch (action) {
      case '更新':
          setObj(record)

          setRefreshKey(++refreshKey)
          setModal1(true)
        break;
     
      case '删除':
        Modal.confirm({
          title: '确定要删除吗？',
          okText: '确认',
          cancelText: '取消',
          onOk: async () => {
            delledger({id:record.id}).then((res)=>{
              if (res.errorMessage){
                Modal.error({title:res.errorMessage})
              } else {
                ref.current?.reload()
              }
            })
          },
        });
        break;

      default:
        break;
    }
  };
  const onAddcSuc = (e:any)=>{
     
      ref.current?.reload()
      setModal1(false)
  
    }
  return <>
  
  <ProTable
            id="ledgerTable"
            tableLayout={'fixed'}
            scroll={{x:'max-content'}}
            pagination={{pageSize:20}}
            rowKey={record=>'ledger'+record.id}
          
            request={(params, sorter, filter) => {
              
              document.body.scrollTop = document.documentElement.scrollTop = 0;
              if (sorter){
                Object.keys(sorter).forEach((key)=>{
                  var order = sorter[key]=='ascend'?'asc':'desc'
                  params.orderby=key+" " + order
                })
              }
              if (params.departmentid) {
                params.departmentid = params.departmentid.join(',')
              }

              if (params.partbname && params.partbname instanceof Object) {
                params.partb = params.partbname.value
              }
              delete params.partb

              if (params.agent && params.agent instanceof Object) {
                params.agentid = params.agent.value
              }
              delete params.agent

              if (params.methodname && Array.isArray(params.methodname)){
                params.method = params.methodname.map((e:any)=>e.value).join(',')
              }
              delete params.methodname

              if (params.result && Array.isArray(params.result)){
                params.resultid = params.result.map((e:any)=>e.value).join(',')
              }
              delete params.result

              if (params.creator && params.creator instanceof Object) {
                params.creator = params.creator.value
              }

             
 
              if (params.inserttime){
                params.inserttimestart = params.inserttime[0]+' 00:00:00'
                params.inserttimeend = params.inserttime[1]+' 23:59:59'
              }
              delete params.inserttime
 
              console.log('par:',params)

              var res = ledgerlist(params)
              setParams(params)
              return res;
            }}
            actionRef={ref}
            formRef={formRef}
            columns={columns}


            
            title={()=>[
      
            ]}
            toolBarRender={() => [
  
              <Button
                type="primary"
                key="primary"
                
                onClick={() => {
                  setObj({})

                  setRefreshKey(++refreshKey)
                  setModal1(true)
                }}
              >
                <PlusOutlined /> 新建
              </Button>,
 

              <Button onClick={()=>{
                
                downloadpurchase(params).then((res:any)=>{
    
                  if (res.errorMessage){
                    Modal.error({
                      title: res.errorMessage,
                    });
                  }else{
                    downloadAsXlSX(res.data, '采购项目台账'+new Date().toLocaleString())
                  }
                })

              }}>采购项目台账</Button>
            ]}
            
          />
          <TableScrollSync tableId="ledgerTable" onScroll={(scroll:any)=>{
                      const tableContent = document.querySelector('#ledgerTable .ant-table-content');
                      if (tableContent){
                        tableContent.scrollLeft = scroll;
                      }
          
                }} />
  
  <Modal
          title={'台账'}
          maskClosable={false}
          width={850}
          style={{ top: 20}}
          visible={modal1}
          onOk={() => setModal1(false)}
          onCancel={() => setModal1(false)}
          footer= {null}
        >
          
          <AddLedger key={refreshKey} data={obj} onChange={onAddcSuc}/>
        </Modal>
      <Modal
          width={850}
          style={{ top: 0}}
          visible={viewmodal}
          onOk={() => setViewmodal(false)}
          onCancel={() => setViewmodal(false)}
          footer= {null}
        >
          
          <ViewLedger id={contract.id} key={refreshKey} />
        </Modal>
  </>
}
export default LedgerList