import React, { useEffect, useState } from 'react';
import { Avatar, Typography,Steps,Card, Divider, Modal, Button, Tag, Descriptions,Tabs, Row  } from 'antd';

import TextArea from 'antd/lib/input/TextArea';
import { FlowStateEunm } from '../../budget/config';
import { canceldelinvoicingnotice, delinvoicingnotice, flowact, getflowdata, getthirdno, startflow, viewflow } from './service';
import Flow from '../../budget/budget/flow';
import { ContractTypeEnum, InvoicingStatesEnum } from '../config';
import { useModel } from 'umi';
import { saveinvoicing } from '../service';
import ContractsTable from '../../contract/contractsTable';
import ViewFlow from '../../Flowtemplate/viewflow';


const { TabPane } = Tabs;
// style
const row:React.CSSProperties = {
  display: 'flex',
  flexDirection: 'row',
  width: '100%'
}
const col:React.CSSProperties = {
  display: 'flex',
  flexDirection: 'column',
  justifyContent: 'center'
}
const label:React.CSSProperties = {
  color: 'gray'
}
const mask:React.CSSProperties = {
  position: 'absolute',
  top: 0,
  left: 0,
  width: '100%',
  height: '100%',
  background: 'linear-gradient(to bottom, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.5))',
  display: 'flex',
  justifyContent: 'center',
  alignItems: 'center',
  zIndex: 9999,
  color: 'white',
  fontSize: '20px',
  fontWeight: 'bold',
  
}
// data
const { Title } = Typography;

/**
 * state 参数对应项目的state
 * @param param0 
 * @returns 
 */
const Viewflow:React.FC<{thirdNo?:any,onchange?:Function,state?:any,infoid?:any}> = ({thirdNo,onchange,state=-1,infoid})=> {

  const [speech, setSpeech] = useState('')
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const [viewdata,setViewdata] = useState<any>({})
  const [statusCn,setStatusCn] = useState([])
  const [basic,setBasic]=useState<any>({})
  const [info,setInfo]=useState<any>({})
  const [step,setStep] = useState(0)
  const Step = Steps.Step;
  const [printModal,setPrintModal]=useState(false)
  var [refresh,setRefresh]=useState(0)
  const [approve,setApprove]=useState(false)
  const [newThirdNo,setNewThirdNo]=useState('')
  const [contractids,setContractids]=useState('')
  const [showContracts,setShowContracts]=useState(false)
  var [refreshKey,setRefreshKey] = useState(0)
  const[viewflowmodal,setViewflowmodal]=useState(false)
  
  useEffect(()=>{
    if (!thirdNo) {
      
      getthirdno().then((e:any)=>setNewThirdNo(e))
    }
    getdata(thirdNo,state,infoid)

  },[])
  type Flow = {
    act: string;
    thirdNo?:string
    fileurls?:string
    speech?:string
    // 不通知指定人员|分割
    notNotice?:string
  
  };
  const getdata=(thirdNo:any,state:any,infoid:any)=>{
    if (!infoid&&!thirdNo){
      Modal.error({title:'infoid和thirdNo不能同时为空'})
      return
    }
    
    getflowdata({thirdNo,state,infoid}).then((res:any)=>{
      
      if (res.errorMessage) {
        Modal.error({
          title: '报错',
          content: res.errorMessage,
        });
      } else {
      
        setViewdata(res.viewdata)
        setStatusCn(res.statusCn)
        setBasic(res.basic)
        setInfo(res.info||{})
        
        if (res.viewdata) {
          // 判断当前审批人是否已经审过
          setStep(res.viewdata.step+1)
          var node = res.viewdata.approval[res.viewdata.step+1]
          var temp = false
          if (node.NodeStatus==2){
            temp=true
          }else{
            var inx = node.Items.Item.findIndex((item:any)=>item.ItemUserId==currentUser.wxuserid)
            if (node.Items.Item[inx] && node.Items.Item[inx].ItemStatus==2) temp=true
          }
          setApprove(temp)
        }
        setRefresh(++refresh)
      }
    })
  }
  const act = (flow:Flow)=>{

    if (!flow.thirdNo) flow.thirdNo = thirdNo||info.thirdNo
    if (!flow.speech) flow.speech=speech
    
    flowact(flow).then((flowres:any)=>{
      if (flowres.errorMessage){
        Modal.error({
          title: '报错',
          content: flowres.errorMessage,
        });
      }else{
        Modal.info({title:'操作成功！'})
        if (flowres.data && flowres.data.touser&&flowres.data.touser.indexOf(currentUser.wxuserid)>-1){
          flow.notNotice = currentUser.wxuserid
          act(flow)
        }else{
          getdata(thirdNo,state,infoid)
        }
        
      }
    })
    
  }
  
    const start = (par:{})=>{
      startflow(par).then((res:any)=>{
        if (res.errorMessage) {
     
          Modal.error({
            title: res.errorMessage
          });
        } else {
          info.thirdno = res.flow.data.ThirdNo
          
          onchange && onchange(info)
        }
      })
    }
  const onFinish = (act:any) => {
    viewflow({infoid:info.id,act}).then((res:any)=>{
      if (res.errorMessage||res.message) {
        Modal.error({
          title: res.errorMessage||res.message,
        });
      } else {
        Modal.confirm({
          title:"请确认流程是否正确",
          bodyStyle:{marginLeft:0},
          width: '600px',
          centered:false,
          content:(
            <div style={{marginLeft:'0!important'}}>
              <Flow key={act}  data={res.viewdata} statusCn={res.statusCn} step={res.step}></Flow>
              <p>开票人：{res.invoicers?res.invoicers:'未设置开票人，请联系管员'}</p>
            </div>
          ),
          onOk:()=>{
            const par = {flowtype:info.state,thirdNo:thirdNo||newThirdNo,infoid:info.id,act}
            var warning = ''
            if (warning) {
              Modal.confirm({
                title:warning,
                onOk:()=>{
                  start(par)
                }
              })
            } else{
              start(par)
            }
            
          },
        })
      }
    })
    
    
  }
  return (
    <div key={refresh} style={{position:'relative'}}>
  
      {
        !viewdata &&
        <>
        
        <Card>
          暂无审批流程
        </Card>
        
        </>
        
      }

      {
        viewdata!=0 &&
        <div >
              {

              ['已取消'].includes(statusCn[basic.status]) &&
              <div style={mask}>
                <div style={{color:'black',fontSize:'20px',margin:"10px"}}>{statusCn[basic.status]}</div>
                <div  style={{color:'cadetblue',fontSize:'20px',margin:"10px"}} onClick={()=>{
                  info.act = InvoicingStatesEnum.INVOICED
                  setInfo(info)
                  onFinish(info.act)
                }}>
                  提交审批
                </div>
              </div>
              }
              
              {
              !['已取消'].includes(statusCn[basic.status]) && info.reject==1 &&
              <div style={mask}>
                <div style={{color:'black',fontSize:'30px'}}>
                      <span style={{marginRight:'20px',color:'cadetblue',borderBottom:'2px solid cadetblue'}} onClick={() => {
                        
                        
                          Modal.confirm({
                            title: '确定要提交审批吗？',
                            onOk() {
                              act({act:'continue'})
                            },
                          });

                        }}>
                        提交审批
                      </span>
                      <span style={{color:'black',borderBottom:'2px solid black'}} onClick={() =>act({act:'cancel'})}>
                        撤销
                      </span>
                </div>
              </div>
              }
              {
                basic.userName &&
                <div>
                  <div style={{...row,alignItems:'center'}}>
                    <Avatar src={basic.avatar} size="large" />
                    <Title style={{height:'100%',display:'flex',alignItems:'center'}} level={4} >
                        {basic.userName+'的审批申请'}
                        {
                          basic.status!=FlowStateEunm.NONE && <Tag color="red">{statusCn[basic.status]}</Tag>
                        }
                        
                       
                        {
                          ['已同意'].includes(statusCn[basic.status])&&info.state==InvoicingStatesEnum.INVOICED &&
                          <Button  danger style={{padding:'0 10px',height: '23px',fontSize: '12px'}} type='primary' onClick={()=>{
                            
                            Modal.confirm({
                              title: '确定要作废该开票申请吗？',
                              okText: '确定',
                              cancelText: '取消',
                              onOk: () => {
                                
                                delinvoicingnotice({id:info.id}).then(res=>{
                                  if (res.errorMessage){
                                    Modal.error({
                                      title:res.errorMessage
                                    })
                                  }else{
                                    Modal.info({
                                      title:'操作成功'
                                    })
                                    getdata(thirdNo,state,infoid)
                                  }
                                })
                              }
                            })
                            
                          }}>作废</Button>
                        }
                        {
                          ['已同意'].includes(statusCn[basic.status])&&info.state==InvoicingStatesEnum.WAITFORDELETE &&
                          <Button  danger style={{padding:'0 10px',height: '23px',fontSize: '12px'}} type='primary' onClick={()=>{
                            
                            Modal.confirm({
                              title: '确定要取消作废吗？',
                              okText: '确定',
                              cancelText: '取消',
                              onOk: () => {
                                
                                canceldelinvoicingnotice({id:info.id}).then(res=>{
                                  if (res.errorMessage){
                                    Modal.error({
                                      title:res.errorMessage
                                    })
                                  }else{
                                    Modal.info({
                                      title:'操作成功'
                                    })
                                    getdata(thirdNo,state,infoid)
                                  }
                                })
                              }
                            })
                            
                          }}>取消作废</Button>
                        }
                        {
                          ['已同意'].includes(statusCn[basic.status])&&!info.invoiceids&&info.state==InvoicingStatesEnum.INVOICED &&
                          <Button   style={{padding:'0 10px',height: '23px',fontSize: '12px'}} type='primary' onClick={()=>{
                            
                            Modal.confirm({
                              title: '确定要撤销该开票申请吗？',
                              okText: '确定',
                              cancelText: '取消',
                              onOk: () => {
                                
                                saveinvoicing({id:info.id,state:0}).then(res=>{
                                  if (res.errorMessage){
                                    Modal.error({
                                      title:res.errorMessage
                                    })
                                  }else{
                                    Modal.info({
                                      title: '撤销成功'
                                    });
                                    getdata(thirdNo,state,infoid)
                                  }
                                })
                                
                              },
                              onCancel: () => {
                               
                              }
                            })
                            
                          }}>撤销</Button>
                        }
               
                  
                        
                          
                      </Title>
                  </div>
                  <Divider></Divider>
                  <Descriptions
                      bordered
                      size={'small'}
                      column={2}
                      labelStyle={{width:120}}
                    >
                      {
                        info?.thirdNo&&
                        <Descriptions.Item label="审批单号"><span style={{color:"#1890FF"}} onClick={()=>{
                                setViewflowmodal(true)
                                
                            }}>{info?.thirdNo}</span></Descriptions.Item>
                      }
                      <Descriptions.Item label="申请类型">{info?.approvaltypename}</Descriptions.Item>
                      <Descriptions.Item label="申请部门">{info?.department}</Descriptions.Item>
                      <Descriptions.Item label="业务类型">{info?.businesstype}</Descriptions.Item>
                      <Descriptions.Item label="开票金额">{(info?.amount||0).toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,})}</Descriptions.Item>
                      <Descriptions.Item label="合同业务">

                        {
                          info.contract==ContractTypeEnum.NOCONTRACT &&
                          <span>无合同</span>
                        }
                        {
                          !info.contractid && info.contract!=ContractTypeEnum.NOCONTRACT &&
                          <span>未签</span>
                        }
                        {
                            info.contractid&&
                            <span style={{color:"#1890FF"}} onClick={()=>{
                                setContractids(info.contractid)
                                setShowContracts(true)
                                setRefreshKey(++refreshKey)
                            }}>已签</span>
                          }
                        </Descriptions.Item>
                      <Descriptions.Item label="发票类型">{info?.type?'专票':'普票'}</Descriptions.Item>
                      <Descriptions.Item label="销售方名称">{info?.partbname}</Descriptions.Item>
                      <Descriptions.Item label="客户名称">{info?.partaname}</Descriptions.Item>
                      <Descriptions.Item label="备注信息">{info?.content}</Descriptions.Item>
                      {
                        info?.othercontent&&<Descriptions.Item label="其他说明">{info?.othercontent}</Descriptions.Item>
                      }
                      
                  </Descriptions>
                </div>
              }
              


            <Flow data={viewdata} condition={basic} statusCn={statusCn} step={step} ></Flow>
            
            


            {
              info?.thirdNo!=null && info?.thirdNo!='' && 
              <div>
                {
                (basic.approvalUserid||'').includes(currentUser.wxuserid) && basic.status!=FlowStateEunm.PASS  && <>
                
                  <TextArea placeholder="审批意见" autoSize={{ minRows: 2, maxRows: 4 }} value={speech} onChange={(e) => setSpeech(e.target.value)}/>
                  <Divider/>
                  </>
                }
              
                {
                !approve && basic.status==FlowStateEunm.ING  &&
                 <>
                  
                      <Button type="primary" onClick={()=>act({act:'agree'})}>
                        同意
                      </Button>
                      <Button type="default" onClick={() =>act({act:'reject'})}>
                        驳回
                      </Button>

                  </>
                }
                
                {
                (basic.userId||'').includes(currentUser.wxuserid) && [FlowStateEunm.ING].includes(basic.status) && <>
                
                    <Button type="primary" onClick={() => {
                      
                      act({act:'urge'})
                      }}>
                      催办
                    </Button>
                    <Button type="default" onClick={() =>act({act:'cancel'})}>
                      撤销
                    </Button>
      


   

                </>
                
              }
              

              </div>
            }
            
    
            <Modal
              title=""
              style={{ top: 0,left:0, aspectRatio: '210/297'}}
              width={'60vw'}
              visible={printModal}
              onOk={() => setPrintModal(false)}
              onCancel={() => setPrintModal(false)}
              footer={null}
            >
              {/* <Print record={basic} key={basic} typename={basic.typename}/> */}
            </Modal>
            <ViewFlow onVisibleChange={setViewflowmodal} visible={viewflowmodal} thirdNo={thirdNo}/>

        </div>
        }
        <div style={{ marginTop: 24 }}>
              {
                (info.thirdNo==null||info.thirdNo=='')  && (info.creator==currentUser.wxuserid) && 
                
                
                <div>
                  
                    
                    {
                      (info.state==null||info.state==0) &&
                      <Row>
                        
                          <Button type="primary" onClick={()=>{
                            info.act = InvoicingStatesEnum.INVOICED
                            setInfo(info)
                            onFinish(info.act)
                            }}>
                            提交审批
                          </Button>


                      </Row>
                      
                      
                    }
                    
                    

                

                </div>
                
              }

              
          </div>
          <ContractsTable key={'合同'+contractids}  contractids={contractids} visible={showContracts} onClose={()=>setShowContracts(false)}/>
    </div>
    
  )
}

export default Viewflow

