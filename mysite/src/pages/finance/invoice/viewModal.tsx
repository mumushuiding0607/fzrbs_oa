
import { Button, Descriptions, Form,  Modal, Popover, Row, Table, Tabs } from "antd";

import { useEffect, useState } from "react";

import { addcontract, delcontract, getinvoicing, saveinvoicing } from "./service";

import Viewflow from "./flow/Viewflow";

import View from "../contract/view";
import Apply from "../budget/budget/apply";
import Add from "../company/add";

import Addinvoice from "../contract/invoice/addinvoice";
import InvoiceView from "../contract/invoice/invoiceView";
import ProjectSelect from "../budget/project/projectSelect";
import Filescard from "../contract/filescard";
import ContractSelect from "../contract/contract-select";
import { InvoicingStatesEnum } from "./config";
import InvoicingItemsList from "./invoicing_items_list";
import { copyTextToClipboard } from "../utils";
import AddPdfInvoice from "./addPdfInvoice";
import ContractsTable from "../contract/contractsTable";




// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
const formItemLayout = {
  labelCol: {
    xs: { span: 3 },
    sm: { span: 3 },
  },
  wrapperCol: {
    xs: { span: 24 },
    sm: { span: 24 },
  },
};
const ViewModal:React.FC<{id:any,thirdNo?:any,onVisibleChange?:Function,visible:boolean,onApplyChange?:Function,defaultActiveKey?:any}> = ({id,thirdNo,visible=false,onVisibleChange,onApplyChange,defaultActiveKey}) =>{
  const [showModal,setShowModal] = useState(visible)
  const [contract, setContract] = useState<any>({})
  const [viewmodal,setViewmodal] = useState(false)
  const [project, setProject] = useState<any>({id:0})
  const [modal2, setModal2] = useState(false)
  var [tabkey,setTabkey]=useState(defaultActiveKey)
  var [applyKey,setApplyKey]=useState(0)
  const [obj,setObj] = useState<any>({})

  const [dataSource, setDataSource] = useState<any[]>([]);
  const [pdataSource, setPdataSource] = useState<any[]>([]);
  var [refreshKey,setRefreshKey] = useState(0)
  const [updateCompanyModal,setUpdateCompanyModal]=useState(false)
  const [company,setCompany]=useState<any>({})
  const [addinvoiceModal,setAddinvoiceModal] = useState(false)
  const [invoicingid,setInvoicingid]=useState(0)
  const [selectmodal,setSelectmodal]=useState(false)
  const [contractModal,setContractModal] = useState(false)
  const [customer,setCustomer]=useState<any>({})
  const [isinvoicer,setIsinvoicer]=useState(false)
  const [contractids,setContractids]=useState('')
  const [showContracts,setShowContracts]=useState(false)
  useEffect(()=>{
    setShowModal(visible)
    if (visible){
      getdata()
              
    }
  },[visible])

  const onItemChange=()=>{
    getdata()
  }
  const getdata=  ()=>{
    getinvoicing({id,show:'all'}).then((res:any)=>{
      if (res && res.data){
        setObj(res.data)
        setIsinvoicer(res.isinvoicer)
        setRefreshKey(++refreshKey)
        setDataSource(res.contracts||[])
        setPdataSource(res.projects||[])
        if (res.data.customer){
          // 判断是否为对象
          if (typeof res.data.customer === 'object') {
            setCustomer(res.data.customer)
          } else {
            setCustomer(JSON.parse(res.data.customer))
          }
        }
        setTabkey(defaultActiveKey?defaultActiveKey:((obj.thirdNo!=null&&obj.thirdNo!='')?'1':'2'))
      }
      
    })
    
  }
  const onAddinvoiceChange = (e:any)=>{
    getdata()
  }
  const onMenuClick = (action:String,record:any) => {
      switch (action) {
        case '上传PDF发票':
          
          break
        case '上传发票':
          if (record.state==InvoicingStatesEnum.DELETEED){
            Modal.error({title:'已作废，无法操作'})
            return
          }
          setInvoicingid(record.id)
          setAddinvoiceModal(true)
          break
        case '关联合同':
          if (record.state==InvoicingStatesEnum.DELETEED){
            Modal.error({title:'已作废，无法操作'})
            return
          }
          setContractModal(true)
          break
        case '关联项目':
          setSelectmodal(true)
          break
        case '删除合同':
          Modal.confirm({
            title: '确定要删除吗？',
            okText: '确认',
            cancelText: '取消',
            onOk: () => {
              delcontract({id:obj.id,contractid:record.id}).then((res:any)=>{
                if (res.errorMessage){
                  Modal.error({title:res.errorMessage})
                } else {
                  getdata()
                }
              })
            } 
          })
          break
        case '删除项目':
          Modal.confirm({
            title: '确定要删除吗？',
            okText: '确认',
            cancelText: '取消',
            onOk: () => {
              obj.projectids = obj.projectids.split(',').filter((item:any)=>item!=record.id).join(',')
              saveinvoicing(obj).then((res:any)=>{
                if (res.errorMessage){
                  Modal.error({title:res.errorMessage})
                } else {
                  getdata()
                
                } 
              })
            } 
          })
          break
        default:
          break;
      }
    };
  const onChange = (e:any)=>{
    setApplyKey(++applyKey)
    onApplyChange && onApplyChange()
  }

  const defaultColumns: any[] = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      width: 65,
      render:(_:any,record:any,index:number)=>`${index+1}`
    },
    {
      title:'合同编号',
      dataIndex:'serial',
      key:'serial',
      width: 100,
      render: (text:any,record:any)=>{
        return <span style={{color:'#1890FF'}} onClick={()=>{
          setViewmodal(true)
          setContract(record)
          setRefreshKey(+refreshKey)
        }}>{text}</span>
      }
    },
    {
      title:'付款方',
      dataIndex:'partaname',
      key:'partaname',
      width: 200
    },
    {
      title:'合同金额',
      dataIndex:'amount',
      key:'amount',
      width: 120,
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
      title: '开票金额',
      dataIndex: 'realinvoiceamount',
      key: 'realinvoiceamount',
      width: 120,
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
      title: '开票次数',
      dataIndex: 'count',
      key: 'count',
      width: 100
    },

    {
          title: '操作',
          fixed:'right',
          dataIndex: 'operation',
          width:100,
          onHeaderCell:()=>({
            style:{
              right:'-5px!important'
            }
          }),
          render: (_:any, record:any,index:Number) => (
            <>
                <Popover
                  placement="topLeft"
                  trigger={'click'}
                  content={(<>
      
                    <Button type="text" onClick={()=>{onMenuClick('删除合同',record)}}>删除</Button>

                    
                    
    
                    </>)
                  }
              
                
                >
                    <Button>操作</Button>
                </Popover>
            </>
          ),
    
        }
  ];
  const pColumns: any[] = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      width: 65,
      render:(_:any,record:any,index:number)=>`${index+1}`
    },
    {
      title:'项目编号',
      dataIndex:'serial',
      key:'serial',
      width: 100
    },
    {
      title:'项目名称',
      dataIndex:'title',
      key:'title',
      width: 200,
      render: (text:any,record:any)=>{
        return <span style={{color:'#1890FF'}} onClick={()=>{
          record.act = record.state
          setRefreshKey(++refreshKey)
          setProject(record)
          setModal2(true)
        }}>{text}</span>
      }
    },
    {
      title:'预算金额',
      dataIndex:'budgetincome',
      key:'budgetincome',
      width: 120,
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
      title:'决算金额',
      dataIndex:'finalincome',
      key:'finalincome',
      width: 120,
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
      title: '操作',
      fixed:'right',
      dataIndex: 'operation',
      width:100,
      onHeaderCell:()=>({
        style:{
          right:'-5px!important'
        }
      }),
      render: (_:any, record:any,index:Number) => (
        <>
            <Popover
              placement="topLeft"
              trigger={'click'}
              content={(<>
  
                <Button type="text" onClick={()=>{onMenuClick('删除项目',record)}}>删除项目</Button>

                
                

                </>)
              }
          
            
            >
                <Button>操作</Button>
            </Popover>
        </>
      ),

    }
  ];


  const onFinish = (e:any)=>{
  
    if (e) {
      e.id = obj.id
      if (e.contractid&&e.contractid.id) e.contractid = e.contractid.id
      addcontract(e).then((res:any)=>{
        if (res.errorMessage){
          Modal.error({title:res.errorMessage})
        }else{
          Modal.info({title:'关联成功'})
          setContractModal(false)
          getdata()
        }
      })
    }
  }
  
  const onSelProject = (e:any)=>{
  
   if (!e&&e.value)return
   if (!obj.projectids){
    obj.projectids = e.value
   }else{
    var temp = obj.projectids.split(',')
    if (temp.indexOf(e.value)<0){
      temp.push(e.value)
    }
    obj.projectids = temp.join(',')
   }
   saveinvoicing(obj).then((res:any)=>{
    setSelectmodal(false)
    if (res.errorMessage){
      Modal.error({title:res.errorMessage})
    }else{
      Modal.info({title:'关联成功'})
      setContractModal(false)
      getdata()
    }
   })
  }
  return (

  <div >
  <Modal
        title={
          <>
          
          <Tabs key={tabkey}   defaultActiveKey={tabkey}>
            <Tabs.TabPane tab="审批" key="1">
                
                <Viewflow key={'审批'+applyKey} infoid={id} thirdNo={thirdNo} onchange={onChange}/>
            </Tabs.TabPane>
            <Tabs.TabPane tab="开票信息" key="2">
              <div key={'Descriptions'+refreshKey}>
                <Descriptions
                    bordered
                    size={'small'}
                    column={2}
                    labelStyle={{width:120}}
                  >

                    <Descriptions.Item label="业务类型">{obj?.businesstype}</Descriptions.Item>
                    <Descriptions.Item label="媒体">{obj?.publication}</Descriptions.Item>
                    
                    <Descriptions.Item label="发票类型">{obj.type?'专票':'普票'}</Descriptions.Item>
                    <Descriptions.Item label="合同业务">
                      <span>{obj.contracttypename}</span>
                      {
                        obj.contractid&&
                        <span style={{color:"#1890FF"}} onClick={()=>{
                          setContractids(obj.contractid)
                          setShowContracts(true)
                          setRefreshKey(++refreshKey)
                      }}>（已签）</span>
                      }
                      
                    </Descriptions.Item>
                    <Descriptions.Item label="销售方名称">
                      <span style={{color:"#1890FF"}} onClick={()=>{
                          setCompany({id:obj.partb,company:obj.partbname})
                          setUpdateCompanyModal(true)
                          setRefreshKey(++refreshKey)
                      }}>{obj?.partbname}</span>
                      
                      </Descriptions.Item>
                    
                      
                    <Descriptions.Item label="开票金额">{(obj?.amount||0).toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,})}</Descriptions.Item>
                    
                    <Descriptions.Item label="客户名称">
                    <span style={{color:"#1890FF"}} onClick={()=>{
                          setCompany({id:obj.parta,company:obj.partaname})
                          setUpdateCompanyModal(true)
                          setRefreshKey(++refreshKey)
                      }}>{obj?.partaname}</span></Descriptions.Item>
                      <Descriptions.Item label="发票抬头">
                    <span style={{color:"#1890FF"}} onClick={()=>{
                          setCompany({id:obj.receiverid,company:obj.receiver})
                          setUpdateCompanyModal(true)
                          setRefreshKey(++refreshKey)
                      }}>{obj?.receiver}</span></Descriptions.Item>
                    <Descriptions.Item label="开票日期" ><span onClick={()=>{
                      
                    }}>{obj.date}</span></Descriptions.Item>
                    <Descriptions.Item label="纳税识别号" ><span onClick={()=>{
                      copyTextToClipboard(customer.code)
                    }}>{customer.code}</span></Descriptions.Item>
                    <Descriptions.Item label="客户地址"><span onClick={()=>{
                      copyTextToClipboard(customer.address)
                    }}>{customer.address}</span></Descriptions.Item>
                    <Descriptions.Item label="客户电话"><span onClick={()=>{
                      copyTextToClipboard(customer.contacts)
                    }}>{customer.contacts}</span></Descriptions.Item>
                    <Descriptions.Item label="开户行及账号"><span onClick={()=>{
                      copyTextToClipboard(customer.bankaccount)
                    }}>{customer.bankaccount}</span></Descriptions.Item>
                    <Descriptions.Item label="邮箱地址"><span onClick={()=>{
                      copyTextToClipboard(customer.email)
                    }}>{customer.email}</span></Descriptions.Item>
                    <Descriptions.Item label="发票备注"><span onClick={()=>{
                      copyTextToClipboard(obj?.content)
                    }}>{obj?.content}</span></Descriptions.Item>
                    {
                      obj?.othercontent&&<Descriptions.Item label="其他说明"><span onClick={()=>{
                      copyTextToClipboard(obj?.othercontent)
                    }}>{obj?.othercontent}</span></Descriptions.Item>
                    }
                    
                    
                </Descriptions>

                <Descriptions
                    bordered
                    size={'small'}
                    column={1}
                    labelStyle={{width:120}}
                  >
                    {
                        obj.fileurls&&obj.fileurls!="" && 
                        <Descriptions.Item label="附件">
                          <Filescard  urls={obj.fileurls} mode='list'/>
                      </Descriptions.Item>
                      }
                  </Descriptions>
                 <InvoicingItemsList invoicingid={obj.id} onChange={onItemChange}/>
                {
                  obj.id>0 && isinvoicer &&
                  
                  <div className="ant-table-title" style={{width:'100%',display:'flex',alignItems:'center',borderLeft:'1px solid #edecec',borderRight:'1px solid #edecec'}}>
                      <div ><Button type="link" onClick={()=>{onMenuClick('上传发票',obj)}}>上传XML发票</Button></div>
                  </div>
                }
                {
                  !isinvoicer &&
                  <div className="ant-table-title" style={{width:'100%',display:'flex',alignItems:'center',borderLeft:'1px solid #edecec',borderRight:'1px solid #edecec'}}>
                      <div ><Button type="link" onClick={()=>setAddinvoiceModal(true)}>XML发票</Button></div>
                  </div>
                }
                {
                  obj.id>0&&
                  <InvoiceView invoicingid={obj.id}/>
                }
                <AddPdfInvoice invoicingid={obj.id} pdffileurls={obj.pdffileurls} isinvoicer={isinvoicer}/>
                <Table
                    title={()=>{
                      return <div style={{width:'100%',display:'flex',alignItems:'center'}}>
                    

                          <div ><Button type="link" onClick={()=>{
                            setContractModal(true)
                          }}>关联合同</Button></div>


                      </div>
                    }}
                    rowKey={(record:any) => {
                      return 'contract'+record.serial
                    }}
  
                    bordered
                    dataSource={dataSource}
                    columns={defaultColumns}
                    pagination={false}
                    locale={{emptyText:'未关联合同'}}
                  />
                <Table
                  title={()=>{
                    return <div style={{width:'100%',display:'flex',alignItems:'center'}}>
                          <div ><Button type="link" onClick={()=>{
                            setSelectmodal(true)
                          }}>相关项目</Button></div>
                      </div>
                    }}
                    rowKey={(record:any) => {
                      return 'project'+record.id
                    }}
                    bordered
                    dataSource={pdataSource}
                    columns={pColumns}
                    pagination={false}
                    locale={{emptyText:'未关联项目'}}
                />
                {
                  obj.thirdNo==null&&<Row>
                    <Button type="primary" onClick={()=>{
                      setTabkey(tabkey+'1')
                    }}>提交审批</Button>
                  </Row>
                }
                {
                  obj.thirdNo!=null&&<Row>
                  <Button type="primary" onClick={()=>{
                    setTabkey(tabkey+'2')
                  }}>审批</Button>
                </Row>
                }
                
              </div>
            </Tabs.TabPane>
          </Tabs>
          
          </>
        }
        style={{ top: 20, }}
        visible={visible}
        width={800}
        onOk={() => {
          onVisibleChange && onVisibleChange(false)
        }}
        onCancel={() => onVisibleChange && onVisibleChange(false)}
        footer={null}
      >
        
        
      </Modal>
      <Modal
          width={400}
          centered
          title="选择项目"
          style={{ top: 0}}
          visible={selectmodal}
          onOk={() => setSelectmodal(false)}
          onCancel={() => setSelectmodal(false)}
          footer= {null}
        >
          
          <ProjectSelect  onChange={onSelProject}></ProjectSelect>
        </Modal>
        <Modal
          width={400}
          centered
          title="选择合同"
          style={{ top: 0}}
          visible={contractModal}
          onOk={() => setContractModal(false)}
          onCancel={() => setContractModal(false)}
          footer= {null}
        >
          <Form  onFinish={onFinish}>
            <Form.Item  label="合同："  name="contractid"  rules={[{ required: true, message: 'Please input!' }]}>
              <ContractSelect multiple={false} showupload={false}   />
            </Form.Item>

            <Form.Item {...tailLayout}>
          <Button type="primary" htmlType="submit">
            提交
          </Button>

        </Form.Item>
          </Form>
          
          
        </Modal>
      <Modal
          width={850}
          style={{ top: 0}}
          visible={viewmodal}
          onOk={() => setViewmodal(false)}
          onCancel={() => setViewmodal(false)}
          footer= {null}
        >
          
          <View id={contract.id} key={'view'+refreshKey} paystate={contract.paystate} attachNumber = {contract.attachNumber}/>
        </Modal>
        <Modal
          key={'project'+refreshKey}
          title="项目信息"
          style={{ top: 20 }}
          width="60vw"
          visible={modal2}
          onOk={() => setModal2(false)}
          onCancel={() => setModal2(false)}
          footer={null}
        >
          <Apply key={'apply'+refreshKey} data={project}  onchange={onChange}/>
        </Modal>
        <Add key={'add'+refreshKey}  visible={updateCompanyModal} id={company.id} company={company.company}  onVisibleChange={setUpdateCompanyModal}></Add>
        <Modal
          title={null}
          style={{ top: 20 }}
          width={850}
          visible={addinvoiceModal}
          onOk={() => {

          }}
          onCancel={() => setAddinvoiceModal(false)}
          footer={null}
        >
          
          <Addinvoice key={'invoicingid'+invoicingid} invoicingid={invoicingid} url={'/api/invoicing/saveinvoice'} onChange={onAddinvoiceChange}/>
        </Modal>
        <ContractsTable key={'合同'+contractids}  contractids={contractids} visible={showContracts} onClose={()=>setShowContracts(false)}/>
  </div>
  )
}

export default ViewModal