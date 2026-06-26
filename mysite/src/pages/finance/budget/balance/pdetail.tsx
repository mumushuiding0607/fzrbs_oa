import { EditOutlined, PlusOutlined, PlusSquareFilled } from '@ant-design/icons';
import { PageContainer } from '@ant-design/pro-components';
import { Avatar, Badge, Breadcrumb, Button, Card, Descriptions, Modal, Segmented, Space, Table, TableProps } from 'antd';
import ButtonGroup from 'antd/lib/button/button-group';
import React, { useEffect, useState } from 'react';
import Uploadmodule from '../common/upload';
import { delbalance, getbalancedetails, save, updateattachment } from './service';
import { hasrole } from '../role/service';
// style
import './common.css'
import { useHistory } from 'react-router-dom';
import { useLocation, useModel } from 'umi';
import Add from './add';
import Updatefinance from './updatefinance';
import AddInvoice from '../invoice/addinvoice';
import moment from 'moment';
import AddC from '../contract/addc';
import { delcontract } from '../contract/service';
import { AGENTID, ProjectStatesEnum } from '../config';
import Addbalance from './add';
import { getpowers } from '../../contract/service';

const container:React.CSSProperties = {
  maxWidth: '1000px',
  display: 'flex',
  flexDirection: 'column',
  justifyContent:'center',
  alignItems:'center'
}
const row:React.CSSProperties = {
  width: '100%',
  background: 'green',
  display: 'flex',
  flexDirection: 'row',
  alignItems:'center',
  justifyContent:'center'
}
const flexitem:React.CSSProperties = {
  'flex':1,padding:'5px'
}
const btngroup:React.CSSProperties = {
  position: 'absolute',
  right: '10px',top: '5px'
}






const Pdetail:React.FC<{id?:any,needRoute?:boolean,contractid?:any,projectid?:any,relatedcontractid?:any}> = ({id,needRoute=true,contractid,projectid,relatedcontractid}) => {
  const history = useHistory();
  const location = useLocation<any>();
  const query = location?.query;
  var [basic, setBasic] = useState<any>({})
  const [bmodal,setBmodal] = useState(false)
  const [fmodal,setFmodal] = useState(false)
  const [cmodal,setCmodal] = useState(false)
  const [dmodal,setDmodal] = useState(false)
  const [contracts, setContracts] = useState([])
  const [contract, setContract] = useState({})
  const [invoices, setInvoices] = useState([])
  const [invoice, setInvoice] = useState({})
  const [balance, setBalance] = useState({})
  const [balances,setBalances] = useState([])
  const [imodal, setImodal] = useState(false)
  var [refreshKey, setRefreshKey] = useState(0)
  var [refreshKeyUpload, setRefreshKeyUpload] = useState(0)
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const [powers,setPowers] = useState<any>([])
  
  id = id || location.query.id || 0; // 收支表对应的id
  relatedcontractid = relatedcontractid || location.query.relatedcontractid || 0; // 合同系统fzrbs_contract对应的合同id
  contractid = contractid || location.query.contractid || 0; // 对应fzrbs_budget_contract表对应的id
  projectid = projectid || location.query.projectid || 0;
  // data
const icols: TableProps<any>['columns'] = [
  {
    title:'创建人',
    dataIndex:'creator',
    key:'creator',
    
    width:'100',
    render:(_,record,index)=>(<>
        <Segmented
          options={[
            {
              label: (
                <div style={{ padding: 4 }}>
                  <Avatar src={record.avatar} />
                  <div>{record.creatorname}</div>
                </div>
              ),
              value: 'user1',
            }
          ]}
        />
    
    </>)
  },
  {
    title: '发票号码',
    dataIndex: 'invoiceno',
    key: 'invoiceno',
    render: (text) => <a>{text}</a>,
  },
  {
    title: '开票日期',
    dataIndex: 'date',
    key: 'date',
    render:(text:string)=>moment(text).format('YYYY-MM-DD')
  },
  {
    title: '开票项目',
    dataIndex: 'content',
    key: 'content',
  },
  {
    title: '开票金额',
    dataIndex: 'amount',
    key: 'amount',
  },
  {
    title: contractid?'到账金额':'付款金额',
    dataIndex: 'redeem',
    key: 'redeem',
  },
  
  {
    title: '税率',
    dataIndex: 'taxrate',
    key: 'taxrate',
  },
  {
    title: '发票备注',
    dataIndex: 'note',
    key: 'note',
  },
  {
    title: '操作',
    key: 'action',
    render: (_, record) => (
      <>
        {
        basic.projectstate!=ProjectStatesEnum.FINISH && basic.creator==currentUser.wxuserid &&<Space size="middle">
        <a onClick={()=>{
          setInvoice(record)
          setImodal(true)
          setRefreshKey(++refreshKey)
        }}>更新</a>
        <a onClick={async ()=>{
          Modal.confirm({
            title:'确定删除吗？',
            onOk(){
        
    
            }
          })
        }}>删除</a>
      </Space>
      }
      </>
    ),
  },
];
const pcols: TableProps<any>['columns'] = [

  {
    title: '合同名称',
    dataIndex: 'title',
    key: 'title'
  },


  {
    title: '合同总价',
    dataIndex: 'amount',
    key: 'amount',
  },
  {
    title: '签订日期',
    dataIndex: 'signdate',
    key: 'signdate',
    render:(text:string)=>text?moment(text).format('YYYY-MM-DD'):""
  },
  {
    title: '操作',
    key: 'action',
    render: (_, record) => (
      <>
      
      {
        basic.projectstate!=ProjectStatesEnum.FINISH  && basic.creator==currentUser.wxuserid && <Space size="middle">
 
        <a onClick={async ()=>{
          Modal.confirm({
            title:'确定删除吗？',
            onOk(){
              if (currentUser.wxuserid!=record.creator){
                Modal.error({title:'只有创建人才能删除'})
                return
              }
              delcontract({id:record.id}).then(res=>{
                if (res.errorMessage){
                  Modal.error({title:res.errorMessage})
                  return
                } else {
                  setContracts((contracts||[]).filter((item:any)=>item.id!=record.id))
            
                  basic.final = res.amount
                  setBasic({...basic})
                }
              })
            }
          })
        }}>删除</a>
      </Space>
      }
      </>
      
    ),
  },
];
const bcols: TableProps<any>['columns'] = [
  {
    title:'创建人',
    dataIndex:'creator',
    key:'creator',
    
    width:'100',
    render:(_,record,index)=>(<>
        <Segmented
          options={[
            {
              label: (
                <div style={{ padding: 4 }}>
                  <Avatar src={record.avatar} />
                  <div>{record.creatorname}</div>
                </div>
              ),
              value: 'user1',
            }
          ]}
        />
    
    </>)
  },


  {
    title: '名称',
    dataIndex: 'title',
    key: 'title',
  },
  {
    title: '预算',
    dataIndex: 'budget',
    key: 'budget',
  },

  {
    title: '决算',
    dataIndex: 'final',
    key: 'final',
  },
  {
    title: '税率',
    dataIndex: 'tax',
    key: 'tax',
  },
  
  {
    title:'收入类型',
    dataIndex:'moneytypename'
  },
  {
    title: '操作',
    key: 'action',
    render: (_, record) => (
      <>
      
      {
        basic.projectstate!=ProjectStatesEnum.FINISH  && basic.creator==currentUser.wxuserid && <Space size="middle">
        <a onClick={()=>{
          setBalance(record)
          setDmodal(true)
          setRefreshKey(++refreshKey)
        }}>更新</a>
        <a onClick={async ()=>{
          Modal.confirm({
            title:'确定删除吗？',
            onOk(){
              if (currentUser.wxuserid!=record.creator){
                Modal.error({title:'只有创建人才能删除'})
                return
              }
              delbalance({id:record.id}).then((res:any)=>{
                if (res.errorMessage){
                  Modal.error({title:res.errorMessage})
                  return
                } else {
                  setBalances(balances.filter((item:any)=>item.id!=record.id))
                  
                }
              })
            }
          })
        }}>删除</a>
      </Space>
      }
      </>
      
    ),
  },
];

  useEffect(()=>{
    getpowers({agentid:AGENTID}).then((res:any)=>{
      if (res.errorMessage){
        Modal.error({title:res.errorMessage})
        return
      }
      res.data = res.data||''
      res.data.split && setPowers(res.data.split(','))

    })
    getbalancedetails({id,projectid,relatedcontractid}).then((res:any)=>{
      if (res){
        setBasic(res.basic||{})
        setInvoices((res.invoices||[]).map((item:any, index:any) => ({...item,key: item.id+index, })))
        setContracts((res.contracts||[]).map((item:any, index:any) => ({...item,key: item.id+index, })))
        setBalances((res.balances||[]).map((item:any, index:any) => ({...item,key: item.id+index, })))
        setRefreshKeyUpload(++refreshKeyUpload)
      }
    })
    
  },[])
  const onChangebasic = (e:any) =>{
    var flag = 0
    if (bmodal) setBmodal(false)
    if (fmodal) setFmodal(false)
    if (imodal) setImodal(false)
    if (cmodal) {
      flag = 3 
      setCmodal(false)
    }
    if (dmodal) setDmodal(false)
 
      getbalancedetails({id,projectid,relatedcontractid}).then((res:any)=>{
      if (res){
 
        setBasic(res.basic||{})
        setInvoices((res.invoices||[]).map((item:any, index:any) => ({...item,key: item.id+index, })))
        setContracts((res.contracts||[]).map((item:any, index:any) => ({...item,key: item.id+index, })))
        setBalances((res.balances||[]).map((item:any, index:any) => ({...item,key: item.id+index, })))
        setRefreshKeyUpload(++refreshKeyUpload)
        if (flag==3) {
          checkFinal(res.basic)
        }
      }
    })
  }
  const checkFinal = (basic:any)=>{
    if (basic.final>basic.budget){
      Modal.confirm({
        content: '决算金额大于预算金额',
        okText: '请更新备注',
        onOk: () => {
          setBmodal(true)
          setRefreshKey(++refreshKey)  
        }
    });
      
    }
  }
  const onFilesChange = (e:any)=>{

    var attatchments = e.map((item:any) => item.url||'').join(',')
    var temp = basic.attatchments
    basic.attatchments = attatchments
    
    updateattachment(basic).then((res)=>{
      if (res.errorMessage) {
        Modal.error({title:res.errorMessage})
        basic.attatchments = temp
      }
      setRefreshKeyUpload(++refreshKeyUpload)
      setBasic(basic)
    })
  }
  return (
    <>
    <PageContainer title={basic.title} header={{breadcrumb: {
      routes: needRoute?[
        {
          path: '',
          breadcrumbName: '上一页',
        },
        {
          path: '/finance/budget/index/',
          breadcrumbName: '首页',
        }
        
      ]:[],itemRender(route, params, routes, paths) {
        if (route.breadcrumbName=='上一页'){
          return <a href='#' onClick={()=>history.goBack()}>{route.breadcrumbName}</a>
        } else {
          return <a href={`/${paths.join("/")}`}>{route.breadcrumbName}</a>
        }
        
      
      },

    },}}>
      <div style={container}>
      {
        id!=0 && <div style={row} key="d1">
          <div style={flexitem}>
            <Badge.Ribbon key={'r1'} text="支出" color="cyan" placement='start'>
              <Card style={{paddingTop: '10px'}}>
                    <ButtonGroup style={btngroup}>
                      <Button  icon={<EditOutlined />} onClick={()=>{
                        if (basic.projectstate==ProjectStatesEnum.FINISH){
                          Modal.error({title:'对应项目已提交考核，不能再更新！'})
                          return
                        }
                        setBmodal(true)
                        setRefreshKey(++refreshKey)
                      }} />
                    </ButtonGroup>
                    
                    <Descriptions bordered layout='horizontal'>
                      <Descriptions.Item label="创建人">{basic.creatorname}</Descriptions.Item>
                      <Descriptions.Item label="预算金额">{basic.budget}</Descriptions.Item>
                      <Descriptions.Item label="决算金额">{basic.final}</Descriptions.Item>
                      <Descriptions.Item label="开票金额">{basic.invoiced}</Descriptions.Item>
                      <Descriptions.Item label="税费">{(basic.final?(basic.final/100*basic.tax):(basic.budget/100*basic.tax)).toFixed(2)}</Descriptions.Item>
                      <Descriptions.Item label="经营绩效">{basic.performance}</Descriptions.Item>
                      <Descriptions.Item label="备注">
                        {basic.note}
                      </Descriptions.Item>
                  </Descriptions>
                </Card>
            </Badge.Ribbon>
          </div>
      </div>
      }
        <div style={row} key="d2">
            <div style={flexitem}>
              <Badge.Ribbon key={'r2'} text="财务信息" color="magenta" placement='start'>
              <Card style={{paddingTop: '10px'}}>
                    <ButtonGroup style={btngroup}>
                      <Button  icon={<EditOutlined />} onClick={()=>{
                        if (basic.projectstate==ProjectStatesEnum.SUBMITTED){
                          Modal.error({title:'对应项目已提交考核，不能再更新！'})
                          return
                        }
                        // 判断是否是会计
                        if (powers.includes('财务复核')) {
                          setFmodal(true)
                          setRefreshKey(++refreshKey)
                        } else {
                          Modal.error({title:'需要【财务】权限'})
                        }
                        
                      }} />
                    </ButtonGroup>
                    <Descriptions bordered>
                      <Descriptions.Item label="凭证号码">{basic.voucher}</Descriptions.Item>
                      <Descriptions.Item label="税费复核">{basic.taxcheck}</Descriptions.Item>
                      <Descriptions.Item label="财务备注">{basic.financenote}</Descriptions.Item>
                  </Descriptions>
                </Card>
              </Badge.Ribbon>
            </div>
        </div>

        <div style={row} key="d3">
            <div style={flexitem}>
              <Badge.Ribbon key={'r3'} text="发票" color="cyan" placement='start'>
                <Card style={{paddingTop: '15px'}}>
                    <ButtonGroup style={btngroup}>
                      <Button  icon={<PlusOutlined />} onClick={()=>{
                    
                        if (currentUser.wxuserid == basic.creator || powers.includes('财务')){
                          setInvoice({bid:basic.id,projectid,type:parseInt(location.query.type)})
                          setImodal(true)
                          setRefreshKey(++refreshKey)
                        } else {
                          Modal.error({title:'创建人或有【财务】权限的人才能操作！'})
                        }
                        
                        }}/>
                    </ButtonGroup>
                  <Table rowKey='key' columns={icols} dataSource={invoices} pagination={false}/>
                </Card>
              </Badge.Ribbon>
            </div>
        </div>
        {
          !projectid && <div style={row} key="d4">
          <div style={flexitem}>
                <Badge.Ribbon  key={'r4'} text="合同" color="cyan" placement='start'>
                  <Card style={{paddingTop: '15px'}}>
                    <ButtonGroup style={btngroup}>
                        <Button  icon={<PlusOutlined />} onClick={()=>{

     
                          if (currentUser.wxuserid == basic.creator || powers.includes('财务')){
                       
                            setContract({bid:basic.id,type:parseInt(location.query.type||basic.type)})
                            setCmodal(true)
                            setRefreshKey(++refreshKey)
                          } else {
                            Modal.error({title:'创建人或有【财务】权限的人才能操作！'})
                          }
                          
                          }}/>
                    </ButtonGroup>
                    <Table rowKey='key' columns={pcols} dataSource={contracts} pagination={false}/>
                  </Card>
                </Badge.Ribbon>
              </div>
          </div>
        }
        {
          projectid>0 && <div style={row} key="d5">
            <div style={flexitem}>
              <Badge.Ribbon key={'r5'} text="收入" color="cyan" placement='start'>
                <Card style={{paddingTop: '15px'}}>
                  <ButtonGroup style={btngroup}>
                      <Button  icon={<PlusOutlined />} onClick={()=>{
                        if (basic.projectstate==ProjectStatesEnum.FINISH){
                          Modal.error({title:'对应项目已提交考核，不能再更新！'})
                          return
                        }
                        console.log("currentUser.wxuserid:",currentUser.wxuserid)
                            console.log("basic.creator:",basic.creator)
                        if (currentUser.wxuserid == basic.creator || powers.includes('财务')){
                          
                          setBalance({bid:basic.id,contractid,projectid,type:parseInt(location.query.type),relatedcontractid:location.query.relatedcontractid})
                          setDmodal(true)
                          setRefreshKey(++refreshKey)
                        } else {
                          Modal.error({title:'创建人或有【财务】权限的人才能操作！'})
                        }
                        
                        }}/>
                  </ButtonGroup>
                  <Table  rowKey='key' columns={bcols} dataSource={balances}  pagination={false}/>
                </Card>
              </Badge.Ribbon>
            </div>
        </div>
        }
        {/* <div style={row} key="d6">
            <div style={flexitem}>
              <Badge.Ribbon key={'r6'} text="附件" color="cyan" placement='start'>
                <Card style={{paddingTop: '10px'}}>
                  <Uploadmodule key={refreshKeyUpload} onchange={onFilesChange} urls={basic.attatchments?(basic.attatchments.split(',').map((url:any)=>{
                    return {
                      url
                    }
                  })):[]}></Uploadmodule>
                </Card>
              </Badge.Ribbon>
            </div>
        </div> */}

        
      </div>
      <Modal
          title="收支信息"
          style={{ top: 20 }}
          visible={bmodal}
          onOk={() => setBmodal(false)}
          onCancel={() => setBmodal(false)}
          footer= {null}
        >
          <Add key={refreshKey} data={basic} onChange={onChangebasic}/>
        </Modal>
        <Modal
          title="财务信息"
          style={{ top: 20 }}
          visible={fmodal}
          onOk={() => setFmodal(false)}
          onCancel={() => setFmodal(false)}
          footer= {null}
        >
          <Updatefinance key={refreshKey} data={basic} onChange={onChangebasic}/>
        </Modal>
        <Modal
          title="发票"
          style={{ top: 20 }}
          visible={imodal}
          onOk={() => setImodal(false)}
          onCancel={() => setImodal(false)}
          footer= {null}
        >
          <AddInvoice key={refreshKey} data={invoice} onChange={onChangebasic} isaccountant={powers.includes('财务')}/>
        </Modal>
        <Modal
          title="合同"
          style={{ top: 20 }}
          visible={cmodal}
          onOk={() => setCmodal(false)}
          onCancel={() => setCmodal(false)}
          footer= {null}
        >
          <AddC key={refreshKey} data={contract} onChange={onChangebasic}/>
        </Modal>
        <Modal
          title="收入"
          style={{ top: 20 }}
          visible={dmodal}
          onOk={() => setDmodal(false)}
          onCancel={() => setDmodal(false)}
          footer= {null}
        >
          <Addbalance key={refreshKey} data={balance} onChange={onChangebasic}/>
        </Modal>
    </PageContainer>
    </>
  )
}
export default Pdetail