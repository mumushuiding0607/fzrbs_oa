import React, { useEffect, useState, useRef } from 'react';
import { Avatar, Typography,Steps,Card, Divider, Modal, Button, Tag, Descriptions,Tabs, Row  } from 'antd';

import TextArea from 'antd/lib/input/TextArea';
import { FlowStateEunm } from '../../budget/config';
import { flowact, getflowdata, getthirdno, setOrderFlag, startflow, viewflow } from '../service';
import Flow from '../../budget/budget/flow';

import ContractsTable from '../../contract/contractsTable';
import ViewFlow from '../../Flowtemplate/viewflow';
import { useModel } from 'umi';


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
  
   const requestedRef = useRef(false);
   
   useEffect(()=>{
    // 已经请求过就不再请求
    if (requestedRef.current) return;
    
    if (!thirdNo) {
      getthirdno().then((e:any)=>setNewThirdNo(e))
    }
    
    // 两个参数都不为空时才查询，这两个是必须参数
    if (infoid && thirdNo) {
      getdata(thirdNo, state, infoid);
      requestedRef.current = true;
    }

  },[thirdNo, infoid])
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
         // 移除setRefresh避免不必要的重新渲染
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
  
    const start = (par:any)=>{
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
              
            </div>
          ),
          onOk:()=>{
            const par = {flowtype:1,thirdNo:thirdNo||newThirdNo,infoid:info.id,act}
            start(par)
          },
        })
      }
    })
    
    
  }
   return (
     <div style={{position:'relative'}}>
  
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
                      <Descriptions.Item label="申请类型">订单审批</Descriptions.Item>
                      <Descriptions.Item label="申请部门">{info?.departmentname}</Descriptions.Item>
                      <Descriptions.Item label="主体">{info?.partbname}</Descriptions.Item>
                      <Descriptions.Item label="订单金额">{(info?.AO_AllMoney||0).toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,})}</Descriptions.Item>
                      <Descriptions.Item label="客户">{info?.AO_Customer}</Descriptions.Item>
                      <Descriptions.Item label="业务员">{info?.AO_Salesman}</Descriptions.Item>
                      <Descriptions.Item label="备注信息">{info?.content}</Descriptions.Item>
                      
                  </Descriptions>
                </div>
              }
              


            <Flow data={viewdata} condition={basic} statusCn={statusCn} step={step} ></Flow>
            


            {
              
              <div>
                {
                (basic.approvalUserid||'').includes(currentUser.wxuserid) && basic.status!=FlowStateEunm.PASS  && <>
                
                  <TextArea placeholder="审批意见" autoSize={{ minRows: 2, maxRows: 4 }} value={speech} onChange={(e) => setSpeech(e.target.value)}/>
                  <Divider/>
                  </>
                }
              

                
                 <>
                    
                      <Button type="primary" onClick={()=>act({act:'agree'})}>
                        同意
                      </Button>
                      <Button type="default" onClick={() =>act({act:'reject'})}>
                        驳回
                      </Button>

                  </>
                
                
                
                <>
                
                    <Button type="primary" onClick={() => {
                      
                      act({act:'urge'})
                      }}>
                      催办
                    </Button>
                    <Button type="default" onClick={() =>act({act:'cancel'})}>
                      撤销
                    </Button>
      

                </>
                
              
              

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
                          info.act = 1
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
    </div>
    
  )
}

export default Viewflow
