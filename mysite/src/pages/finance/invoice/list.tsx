import { ActionType, PageContainer, ProCard, ProColumns, ProFormInstance, ProTable } from '@ant-design/pro-components';
import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import { Avatar, Badge, Button, Card,ConfigProvider,DatePicker,Menu,Modal ,Popover,Radio,Segmented,Space, Tabs, Tag} from 'antd';




import { PlusOutlined, SearchOutlined } from '@ant-design/icons';
import Companyselect from '../company/companyselect';

import moment from 'moment';
import DepartmentTreeSelect from '../budget/common/department_treeselect';

import { useLocation, useModel } from 'umi';
import { BalanceTypes } from '../budget/config';





import './common.css'



import Rolelist from '../role/rolelist';
import { ContractTypeEnum, INVOICE_AGENTID, InvoicedStates, InvoicingStatesEnum } from './config';
import { getpowers } from '../role/service';
import { delinvoicing, getheaders, getlist } from './service';
import Add from './add';
import ViewModal from './viewModal';
import Flowtemplatelist from './flow/flowtemplatelist';
import Filescard from '../contract/filescard';
import ContractsTable from '../contract/contractsTable';
import CommonDownloadModal from '../common/CommonDownloadModal';
import Invoicelist from './invoicelist';
import ProStat from '../budget/home/prostat';
import Print from './print';
import Todolist from '../budget/home/todolist';
import Dictselect from '../budget/dict/dictselect';
import Addinvoice from '../contract/invoice/addinvoice';
import EmailSetting from '../common/EmailSetting';
import InvoicerList from './invoicer/list';
import UserAutocomplete from '../budget/common/userAutocomplete';
import BusinesstypeTree from './Businesstype_Tree';
import { delinvoicingnotice } from './flow/service';
import TableScrollSync from '../common/TableScrollSync';


// style
const tag:CSSProperties = {
  margin: '2px',
  padding: '0px 2px',
  width: '46px',
  display: 'flex',
  justifyContent:'space-evenly'
}

// data



// dom
const Listc:React.FC = () =>{



  var [refreshKey, setRefreshKey]= useState(0)
  const [modal1, setModal1] = useState(false)

  const [rolemodal,setRolemodal] = useState(false)
  const [templateModal,setTemplateModal]=useState(false)
  const [balancetype, setBalancetype] = useState(BalanceTypes.INCOME)
  const [state,setState]=useState(-1)
  const [selectedRows, setSelectedRows]=useState<any>([])
  const [addinvoiceModal,setAddinvoiceModal] = useState(false)
  const [data,setData]=useState<any>({})
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const [powers,setPowers] = useState<any>([])
  const ref = useRef<ActionType>();
  const formRef = useRef<ProFormInstance>();
  const [obj,setObj]=useState<any>({})
  const [view,setView]=useState(false)
  const [urls, setUrls] = useState('')
  const [modal2, setModal2] = useState(false)  
  const [contractids,setContractids]=useState('')
  const [showContracts,setShowContracts]=useState(false)
  const [hmodal,setHmodal] = useState(false)
  const [params,setParams]=useState<any>({})
  var [headers,setHeaders]=useState<any>([])
  const [showInvoicelist,setShowInvoicelist]=useState(false)
  const [stat,setStat]=useState<any>([])
  const [printModal,setPrintModal]=useState(false)
  const [ids,setIds]=useState('')
  const [defaultActiveKey,setDefaultActiveKey]=useState('')
  const [currentState,setCurrentState]=useState(0)
  const [showEmailSetting, setShowEmailSetting] = useState(false);
  const [invoicerModal,setInvoicerModal]=useState(false)
  const [query,setQuery]=useState<any>({})
  const onMenuClick = (action:String,record:any) => {

    switch (action) {
      case '打印':
        setIds(''+record.id)
        setPrintModal(true)
        break
      case '更新':
        setData(record)
        setModal1(true)
        setRefreshKey(++refreshKey)
        break;
      case '作废':
        setView(true)
        setObj(record)
        setDefaultActiveKey('1')
        break;
      case '撤销':
        setView(true)
        setObj(record)
        setDefaultActiveKey('1')
        break;
      case '查看关联合同':
        setContractids(record.contractid)
        setShowContracts(true)
        setRefreshKey(++refreshKey)
        break;
      case '删除':
        Modal.confirm({
          title: '确定要删除吗？',
          okText: '确认',
          cancelText: '取消',
          onOk: async () => {
            delinvoicing({id:record.id}).then((res:any)=>{
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


  // 列标
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
      title: '开票状态',
      dataIndex: 'state',
      hideInSearch:true,
      width: 75,
      render:(_:any,record:any)=>{
              var text = '暂存'
              var color = 'default'
              if (record.state==InvoicingStatesEnum.DELETEED){
                text = '已作废';color='default';
              } else if (record.state==InvoicingStatesEnum.WAITFORDELETE){
                text = '待作废';color='red';
              } else if (record.invoiceids!=null&&!record.realinvoiceamount){
                text = '已红冲';color='red';
              }else if (record.invoiceids!=null&&record.realinvoiceamount>0){
                text = '已开票';color='green';
              } else if (record.state==InvoicingStatesEnum.INVOICED&&record.invoiceids==null){
                text = '待开票';color='lime';
              } else if (record.thirdNo!=null&&record.thirdNo!=''){
                text = '审批中';color='red';
              }
              return (<div style={{textAlign:'left',display:'flex'}} onClick={()=>{
                setView(true)
                setObj(record)
              }}>
                <Tag color={color} style={tag}>{text}</Tag>

                
              </div>)
            }
    },
    {
      title: '合同',
      dataIndex: 'contractname',
      key: 'contractname', 
      width: 120,
      render: (_:any,record:any)=>(
        <>
          <span>{record.contractname}</span>
          {
            ![ContractTypeEnum.NOCONTRACT,ContractTypeEnum.SMALLAMOUNTNOTICE].includes(record.contract) &&
            <span>
              {
                record.contractid!=null && record.contractid!= "" &&
                <span style={{color:'#1890FF'}} onClick={()=>{onMenuClick('查看关联合同',record)}}>(已签)</span>
              }
              {
                !record.contractid &&
                <span >(未签)</span>
              }
            </span>
          }
          {/* {
            record.contract==ContractTypeEnum.NOCONTRACT && !record.contractid&&
            <span>无合同</span>
          }
          {
            !record.contractid && ![ContractTypeEnum.NOCONTRACT,ContractTypeEnum.SMALLAMOUNTNOTICE].includes(record.contract) &&
            <span>{record.contractname}(未签)</span>
          }
          {
            record.contractid!=null && record.contractid!= "" &&
            <>{record.contractname}<span style={{color:'#1890FF'}} onClick={()=>{onMenuClick('查看关联合同',record)}}>(已签)</span></>
          } */}
        </>
      ),
      renderFormItem: () => {

        return (
          <Dictselect type={"合同业务类型"}  needAddItem={false}/>
        )
      }
    },

    {
      title: '发票类别',
      dataIndex: 'type',
      key: 'type', 
      width: 75,
      valueEnum: {
        0: {
          text: '普票',
        },
        1: {
          text: '专票',
        },
      },
    },
    {
      title: '业务类型',
      dataIndex: 'businesstype',
      key: 'businesstype', 
      renderFormItem: () => {

        return (
          <BusinesstypeTree   />
        )
      }
    },
    {
      title: '开票单位',
      dataIndex: 'partbname',
      key: 'partbname',
      sorter: true,
      width: 200,
      render:(text:any,record:any)=>{
    
             
        return (<div style={{textAlign:'left',color:'#1890FF'}} onClick={()=>{
          setView(true)
          setObj(record)
        }}>
      
          {text}

          
        </div>)
      },
      renderFormItem: () => {

        return (
          <Companyselect  multiple={false}></Companyselect>
        )
      }
    },
    {
      title: '客户名称',
      dataIndex: 'partaname',
      key: 'partaname',
      sorter: true,
      width: 200,
      renderFormItem: () => {

        return (
          <Companyselect  multiple={false}></Companyselect>
        )
      }
    },
    {
      title: '开票项目',
      dataIndex: 'title',
      key: 'title',
      sorter: true,
      hideInSearch:true,
      width: 150,
    },
    {
      title: '项目金额合计',
      dataIndex: 'amount',
      key: 'amount',
      width: 120,
      sorter: true,
      className:'right',
      search:false,
      render: (text:any,record:any)=>(
        <>
       
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
         
        </>
      )
    },
    
    
    {
      title:'申请部门',
      dataIndex:'departmentid',
      key:'departmentid',
      hideInTable: true,
      renderFormItem: () => {

        return (
          <DepartmentTreeSelect  maxTagCount={1} />
        )
      }
    },
    {
      title:'经办',
      dataIndex:'creator',
      key:'creator',
      hideInTable: true,
      renderFormItem: () => {

        return (
          <UserAutocomplete multiple={true} />

        )
      }
    },
    {
      title: '部门/经办',
      dataIndex: 'name',
      width:150,
      search:false,
      sorter: true,
      render:(_:any,record:any)=>(
        <div style={{display:'flex',flexDirection:'column'}}>
          <span style={{color:'gray',fontSize:'12px'}}>{record.department}</span>
          <span>{record.name}</span>

        </div>
      ),
      
    },
    
    
    
    {
      title: '申请日期',
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
      title: '开票日期',
      dataIndex: 'date',
      key: 'date',
      sorter: true,
      valueType: 'dateRange',
      width: 120,
      fieldProps: {
        // 你可以在这里自定义时间选择器的行为
        format: 'YYYY-MM-DD',
        
      },
      render: (_:any, record:any) => {
        return record.date?moment(record.date).format('YYYY-MM-DD'):''
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

           {
            record.state!= InvoicingStatesEnum.DELETEED &&
            <Popover
              placement="topLeft"
              trigger={'click'}
              
              key={'popover'+index}
              overlay={<div key={'overlay'+index} id={'overlay'+index}></div>}
              content={(<>
                
                
                {
                  record.invoiceids==null&&<Button type="text" onClick={()=>{onMenuClick('更新',record)}}>更新</Button>
                }
                
                {
                  record.state==InvoicingStatesEnum.INVOICED &&
                  <Button danger type="text" onClick={()=>{
                    Modal.confirm({
                      title: '确定要作废吗？',
                      okText: '确定',
                      cancelText: '取消',
                      onOk: () => {
                        
                        delinvoicingnotice({id:record.id}).then(res=>{
                          if (res.errorMessage){
                            Modal.error({
                              title:res.errorMessage
                            })
                          }else{
                            Modal.info({
                              title:'操作成功'
                            })
                            ref.current?.reload()
                          }
                        })
                      }
                    })
                  }}>作废</Button>
                }
                {
                  record.state==InvoicingStatesEnum.INVOICED &&
                  <Button danger type="text" onClick={()=>{onMenuClick('撤销',record)}}>撤销</Button>
                }
                <br></br>
                {
                  record.state==InvoicingStatesEnum.START &&
                  <Button danger type="text" onClick={()=>{onMenuClick('删除',record)}}>删除</Button>
                }
                <Button type="text" onClick={()=>{onMenuClick('打印',record)}}>打印</Button>

                </>)
              }
          
            
            >
              <Button key={'button'+index}>操作</Button>
            </Popover>
           }
        </>
      ),
    },

  ]


  // method

  const onAddcSuc = (e:any)=>{
    
    ref.current?.reload()
    setModal1(false)
    setObj(e)
    setView(true)


  }
  const onCurrentStateChange = (e:any)=>{
    setCurrentState(e)
    ref.current?.reload(true)
  }
 

  useEffect(()=>{
    getpowers({agentid:INVOICE_AGENTID}).then((res:any)=>{
      if (res.errorMessage){
        Modal.error({title:res.errorMessage})
        return
      }
      res.data = res.data||''
      if (res.data.split){
        var power = res.data.split(',')
        setPowers(power)
        
        
        

   



      }
       

    })
  },[])
  return (
    <ConfigProvider >
      <PageContainer title="开票列表" header={{breadcrumb: {},}} extra={powers && powers.includes('管理')?[
              
              <Button   onClick={()=>{
                setRolemodal(true)
              }}>角色设置</Button>,
              <Button   onClick={()=>{
                setInvoicerModal(true)
              }}>开票员设置</Button>,
              <Button  onClick={()=>{
                setTemplateModal(true)
              }}>流程设置</Button>,
              <Button onClick={()=>{
                setShowEmailSetting(true)
              }}>发件邮箱</Button>,
            ]:[]
        
      }>
        
        <ProTable
          tableLayout={'fixed'}
          id="invoicingTable"
          scroll={{x:'max-content'}}
        
          rowSelection={{
            onChange: (_, selectedRows) => {
              setSelectedRows(selectedRows);
              if (selectedRows.length>0){
                setIds(selectedRows.map((e:any)=>e.id).join(','))
              }else{
                setIds('')
              }
            },
          }}
          
          pagination={{pageSize:20}}
          rowKey={record=>'invoice'+record.id}
        
          request={(params, sorter, filter) => {
            
            document.body.scrollTop = document.documentElement.scrollTop = 0;
            if (sorter){
              Object.keys(sorter).forEach((key)=>{
                var order = sorter[key]=='ascend'?'asc':'desc'
                params.orderby=key+" " + order
              })
            }


            params.state = state

            if (params.date){
              params.datestart = params.date[0];
              params.dateend = params.date[1]?params.date[1]+' 23:59:59':'';
            }else{
              params.datestart = null
              params.dateend = null
            }
            if (params.inserttime){
              params.inserttimestart = params.inserttime[0];
              params.inserttimeend = params.inserttime[1]?params.inserttime[1]+' 23:59:59':'';
            }else{
              params.inserttimestart = null
              params.inserttimeend = null
            }
            params.currentState = currentState

            
            params.partb = params.partbname?params.partbname.value:null
            params.parta = params.partaname?params.partaname.value:null

            if (params.departmentid&&params.departmentid.length){
              params.departmentid = params.departmentid.join(',')
            }else{
              params.departmentid = null
            }
            if(params.creator&&params.creator.length){
              params.creator = params.creator.map((e:any)=>e.value).join(',')
            }else{
              params.creator = null
            }
            
            params.contract = params.contractname?params.contractname:null

            setParams(params)
        
            var result = getlist(params)
            result.then((res:any)=>{
              
              if (params.current==1){

                setStat(res.stat||[])
              }
              
            })
            return result;
    
          }}
          actionRef={ref}
          formRef={formRef}
          columns={columns}
          
        
          form={{
           
          }}
          onSubmit={(params)=>{
            if (Object.keys(params).length>0 && balancetype!=BalanceTypes.ALL){

              setBalancetype(BalanceTypes.ALL)
            }
          }}
          headerTitle={
            <Todolist url={'/api/invoicing/todolist'} onClick={(e:any)=>{
                  
                  switch (e.title) {
                    case '全部':
                      setCurrentState(InvoicingStatesEnum.ALL)
                      ref.current?.reload(true)
                      break;
                    case '待开票':
                      setCurrentState(InvoicedStates.WaitForInvoiced)
                      ref.current?.reload(true)
                      break;
                    case '待作废':
                      setCurrentState(InvoicingStatesEnum.WAITFORDELETE)
                      ref.current?.reload(true)
                      break;
                    case '合同未签':
                      setCurrentState(InvoicingStatesEnum.CONTRACTNOTSIGN)
                      ref.current?.reload(true)
                      break
                    case '小额公告业务':
                      setCurrentState(InvoicingStatesEnum.SMALLAMOUNTNOTICE)
                      ref.current?.reload(true)
                      break
                      
                    default:
                      break;
                  }
              }
            }/>
          }
          title={()=>[
            
            <ProStat  data={stat} />
          ]}
          toolBarRender={() => [
            <Dictselect type={"开票状态"}  needAddItem={false} onChange={onCurrentStateChange}/>,
            <Button
              type="primary"
              
              onClick={() => {
                setData({contract:2,type:1})
                setRefreshKey(++refreshKey)
                setModal1(true)
              }}
            >
              <PlusOutlined /> 开票申请
            </Button>,
            <Button
              type="default"
              onClick={() => {
                setRefreshKey(++refreshKey)
                setAddinvoiceModal(true)
              }}
            >
              上传发票
            </Button>,
            <Button danger onClick={()=>{
              setShowInvoicelist(true)
            }}>发票列表</Button>,
            <Button onClick={()=>{
                    
              setHmodal(true)
                
              }}>导出查询结果</Button>,
              
              <Button danger type='primary' onClick={()=>{
              
                if (!ids){
                  Modal.error({title:'请选择要打印的项'})
                  return
                }
                setPrintModal(true)

              }}>打印</Button>
          ]}
          
        />
        <TableScrollSync tableId="invoicingTable" onScroll={(scroll:any)=>{
                      const tableContent = document.querySelector('#invoicingTable .ant-table-content');
                      if (tableContent){
                        tableContent.scrollLeft = scroll;
                      }
          
                }} />
        <ViewModal key={'viewmodal'+obj.id} id={obj.id} thirdNo={obj.thirdNo} visible={view} onVisibleChange={setView} defaultActiveKey={defaultActiveKey}></ViewModal>
        <Modal
          title="开票"
          maskClosable={false}
          width={850}
          style={{ top: 20}}
          visible={modal1}
          onOk={() => setModal1(false)}
          onCancel={() => setModal1(false)}
          footer= {null}
        >
          
          <Add key={'add'+refreshKey} data={data} onChange={onAddcSuc}/>
        </Modal>

        <Modal
         
         
          visible={rolemodal}
          width='100vw'
          style={{top:0,right:0}}
          onOk={() => setRolemodal(false)}
          onCancel={() => setRolemodal(false)}
          footer= {null}
        >
          <Rolelist type='发票管理' agentid={INVOICE_AGENTID}></Rolelist>
          
        </Modal>
        <Modal
         
         
          visible={invoicerModal}
          width='100vw'
          style={{top:0,right:0}}
          onOk={() => setInvoicerModal(false)}
          onCancel={() => setInvoicerModal(false)}
          footer= {null}
        >
          <InvoicerList type='开票员列表' ></InvoicerList>
          
        </Modal>
        <Modal
         
         
          visible={showInvoicelist}
          width='100vw'
          style={{top:0,right:0}}
          onOk={() => setShowInvoicelist(false)}
          onCancel={() => setShowInvoicelist(false)}
          footer= {null}
        >
          <Invoicelist/>
          
        </Modal>
        <Modal
        
          
            visible={templateModal}
            width='100vw'
            style={{top:0,right:0}}
            onOk={() => setTemplateModal(false)}
            onCancel={() => setTemplateModal(false)}
            footer= {null}
          >
          <Flowtemplatelist ></Flowtemplatelist>
        </Modal> 
        <Modal
          title={null}
          style={{ top: 20 }}
          width={650}
          visible={modal2}
          onOk={() => {

          }}
          onCancel={() => setModal2(false)}
          footer={null}
        >
          
          <Filescard key={'file'+refreshKey} urls={urls}/>
        </Modal>
        <CommonDownloadModal url="/api/invoicing/getlist" headersUrl={'/api/invoicing/getheaders'} params={params} headers={headers} visible={hmodal} onVisibleChange={setHmodal}/>
        <ContractsTable key={'合同'+contractids}  contractids={contractids} visible={showContracts} onClose={()=>setShowContracts(false)}/>
        <EmailSetting agentid={INVOICE_AGENTID} visible={showEmailSetting} onVisibleChange={setShowEmailSetting}/>
        <Modal
              title=""
              style={{ top: 0,left:0, aspectRatio: '210/297'}}
              
              width={'225mm'}
              visible={printModal}
              onOk={() => setPrintModal(false)}
              onCancel={() => setPrintModal(false)}
              footer={null}
            >
              <Print key={'打印'+ids} ids={ids} />
            </Modal>
            <Modal
              title={null}
              style={{ top: 20 }}
              width={1000}
              visible={addinvoiceModal}
              onOk={() => {

              }}
              onCancel={() => setAddinvoiceModal(false)}
              footer={null}
            >
              
              <Addinvoice key={refreshKey} invoicingid={0} url={'/api/invoicing/saveinvoice'} />
            </Modal>
      </PageContainer>
    </ConfigProvider>
  )
  
}
export default Listc