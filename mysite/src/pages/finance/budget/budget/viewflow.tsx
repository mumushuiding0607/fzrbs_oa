import React, { useEffect, useState } from 'react';
import { Avatar, Typography,Steps,Card, Divider, Modal, Button, Tag, Descriptions,Tabs  } from 'antd';
import './costom.css'
import { getflowinfo,flowact, getfileurlsbycontractids } from './service';
import { currentUser } from '@/services/ant-design-pro/api';
import { useModel } from 'umi';
import TextArea from 'antd/lib/input/TextArea';
import { set } from 'lodash';
import Filescard from '../../contract/filescard';
import { FlowStateEunm, ProjectStatesEnum } from '../config';
import Flow from './flow';
import Offlineagree from './offlineagree';
import Print from './print';
import Budgetdetail from './budgetdetail';
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
const Viewflow:React.FC<{thirdno?:any,onchange?:Function,state?:any,projectid?:any}> = ({thirdno,onchange,state=-1,projectid})=> {

  const [speech, setSpeech] = useState('')
  
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const [basic,setBasic]= useState<any>({})
  const [viewdata,setViewdata] = useState<any>({})
  const [statusCn,setStatusCn] = useState([])
  const [step,setStep] = useState(0)
  const [incomecontracts,setIncomecontracts]=useState('')
  const [expendcontracts,setExpendcontracts]=useState('')
  const Step = Steps.Step;
  const [printModal,setPrintModal]=useState(false)
  const [offlineModal,setOfflineModal] = useState(false)
  const [modal2, setModal2] = useState(false)
  const [urls, setUrls] = useState('')
  var [refresh,setRefresh]=useState(0)
  const [approve,setApprove]=useState(false)
  const[viewflowmodal,setViewflowmodal]=useState(false)
  const [reset,setReset]=useState(false)
  useEffect(()=>{
    getflowinfo({thirdNo:thirdno,projectid,state}).then((res:any)=>{
      
      if (res.errorMessage) {
        Modal.error({
          title: '报错',
          content: res.errorMessage,
        });
      } else {
      
        setBasic(res.basic)
        setViewdata(res.viewdata)
        setStatusCn(res.statusCn)
        setIncomecontracts(res.incomecontracts)
        setExpendcontracts(res.expendcontracts)
        
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
    
  },[])
  type Flow = {
    offline?: number;
    act: string;
    thirdNo?:string
    fileurls?:string
    speech?:string
    // 不通知指定人员|分割
    notNotice?:string
    continuous?:number
  
  };
  const onOfflineAgree = ()=>{
    setOfflineModal(true)
    setBasic(basic)
  }
  const offlineAgree = (values:Flow)=>{

    values.act='offlineAgree'
    values.offline=0
    
    act(values)
  }

  
  const actReject = (flow:Flow)=>{
    Modal.confirm({
      title: '确定【 驳回】吗？',
      onOk() {
        act(flow)
      },
    });
  }
  const actAgree = (flow:Flow)=>{
    Modal.confirm({
      title: '确定【同意】吗？',
      onOk() {
        act(flow)
      },
    });
  }
  const actCancel = (flow:Flow)=>{
    Modal.confirm({
      title: '确定要【撤销】吗？',
      onOk() {
        act(flow)
      },
    });
  }
  const act = (flow:Flow)=>{
 
    if (!flow.thirdNo) flow.thirdNo = thirdno
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
          flow.continuous = 1;
          act(flow)
        }else{
          getflowinfo({thirdNo:thirdno,projectid,state}).then((res:any)=>{
       
            if (res.errorMessage) {
              Modal.error({
                title: '报错',
                content: res.errorMessage,
              });
            } else {
              setBasic(res.basic)
              setViewdata(res.viewdata)
              setStatusCn(res.statusCn)
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
              

              if (onchange) onchange(res)
            }
          })
        }
        
      }
    })
    
  }
  var income = 0
  var expend = 0
  var profit = 0
  if (basic.finalincome>0){
    income = basic.finalincome
    expend = basic.realfinalexpend
  }else{
    income = basic.budgetincome
    expend = basic.realbudgetexpend
  }
  profit = income-expend
  profit = profit>0?profit:0
  return (
    <div key={refresh} style={{position:'relative'}}>
      {
        viewdata==0 &&
        <Card>
          请先提交审批
        </Card>
      }
      {
        viewdata!=0 &&
        <div >
           {

            ['已取消'].includes(statusCn[basic.status]) &&
            <div style={mask}>
              <div style={{color:'black',fontSize:'40px'}}>{statusCn[basic.status]}</div>
            </div>
           }
           {
            !['已取消'].includes(statusCn[basic.status]) && basic.reject==1 &&
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
            {/* <Card   style={{ width: '100%' }}> */}
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
                          basic.state>=ProjectStatesEnum.START && (basic.typename||basic.statename)!='提交计量' && <Button style={{padding:'0 10px',height: '23px',fontSize: '12px'}} type='primary' onClick={()=>{
                          
                            setPrintModal(true)
                            setBasic(basic)
                          }}>打印</Button>
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
                        basic?.thirdNo&&
                        <Descriptions.Item label="审批单号"><span style={{color:"#1890FF"}} onClick={()=>{
                                setViewflowmodal(true)
                                
                            }}>{basic?.thirdNo}</span></Descriptions.Item>
                      }
                      <Descriptions.Item label="项目名称">{basic.title}</Descriptions.Item>
                      <Descriptions.Item label="项目类型">{basic?.protypename}</Descriptions.Item>
                      {
                        basic.submitdate!=null &&
                        <Descriptions.Item label="提交日期">{basic?.submitdate.substr(0,7)}</Descriptions.Item>
                      }
                      <Descriptions.Item label="申请类型">{basic.typename||basic.statename}{basic.statename&&basic.statename!=basic.typename&&basic.statename!='立项'?"（未"+basic.statename+"）":""}</Descriptions.Item>
                      <Descriptions.Item label="项目负责人">{basic.chargername}</Descriptions.Item>
                      <Descriptions.Item label="立项部门">{basic.pdepartment}</Descriptions.Item>
                      <Descriptions.Item label="立项主体">{basic.partbname}</Descriptions.Item>
                      <Descriptions.Item label="申请部门">{basic.department}</Descriptions.Item>
                      <Descriptions.Item label="总收入">{income.toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,})}</Descriptions.Item>
                      <Descriptions.Item label="总支出">{expend.toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,})}</Descriptions.Item>
                      <Descriptions.Item label="毛利润">{profit.toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,})}</Descriptions.Item>
                      <Descriptions.Item label="合同状态">
                        {
                          basic.contractids && 
                          <a href='#' onClick={()=>{
                            getfileurlsbycontractids({contractids:basic.contractids}).then((res=>{
                              setUrls(res.data||'')
                              setModal2(true)
                            }))
                          }}>
                            已签
                          </a>
                        }
                        {
                          !basic.contractids && '未签'
                        }
                      </Descriptions.Item>
                      
                  </Descriptions>
 
                    <Descriptions
                      bordered
                      size={'small'}
                      column={1}
                      labelStyle={{width:120}}
                    >
                     {
                        basic.content &&basic.content!='' &&
                        <Descriptions.Item label="项目备注">{basic.content}</Descriptions.Item>
                      }
                      {
                        basic.fileurls &&
                        <Descriptions.Item label="预算附件">
                          <Filescard key={basic.id} mode='list' urls={basic.fileurls}/>
                        </Descriptions.Item>
                      }
                      {
                        basic.finalfileurls &&
                        <Descriptions.Item label="决算附件">
                          <Filescard key={basic.id} mode='list' urls={basic.finalfileurls}/>
                        </Descriptions.Item>
                      }
                    </Descriptions>
                    
                </div>
              }
              
              

              
            {/* </Card> */}


            <Flow data={viewdata} condition={basic} statusCn={statusCn} step={step} offlineAgree={onOfflineAgree}></Flow>
            
            

            {
              incomecontracts&&incomecontracts!="" &&

              <Descriptions.Item label="收款合同">
                <Filescard  urls={incomecontracts} mode='list'/>
            </Descriptions.Item>
            }
            {
              expendcontracts&&expendcontracts!="" && 
              <Descriptions.Item label="付款合同">
                <Filescard  urls={expendcontracts} mode='list'/>
            </Descriptions.Item>
            }
            {
              basic.offline==1 && basic.offlinenote &&
              <div style={{margin:'0 0 20px 20px',fontWeight:'bold'}}>{basic.offlinenote}</div>
            }
            {
              state==-1 && 
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
                  
                      <Button type="primary" onClick={()=>actAgree({act:'agree'})}>
                        {
                          (viewdata && viewdata.showSpecialBtn&&['预算','决算'].indexOf(basic.typename)>-1)?'同意线上会签':'同意'
                        }
                      </Button>
                      <Button type="default" onClick={() =>actReject({act:'reject'})}>
                        驳回
                      </Button>
                      {
                        ['预算','决算'].indexOf(basic.typename)>-1&&basic.offline==0&&
                        <Button type="link" onClick={()=>{
                          
                          if (viewdata && viewdata.approval){
                            if (viewdata.approval.length<=(step+1)){ // 无下个节点
                               Modal.error({'title':'禁止操作'})
                               return
                            }else{
                              
                              var node = viewdata.approval[step+1]
                              if (node.NodeAttr!=2||node.Items.Item.length<2){
                                Modal.error({title:'下个节点为会签且多人审批才能上会'})
                                return
                              }
                            }
                            
                            
                          }
                          Modal.confirm({
                            title: '确定要【线下上会】吗？',
                            content: '提交后，将进入下一流程',
                            onOk() {
                              act({act:'agree',offline:1})
                            },
                          });
                          
                        }}>
                          线下上会
                        </Button>
                      }
                  </>
                }
                {
                (basic.userId||'').includes(currentUser.wxuserid) && basic.status!=FlowStateEunm.CANCEL && <>
                
                    <Button type="primary" onClick={() => {
                      
                      act({act:'urge'})
                      }}>
                      催办
                    </Button>
                    <Button type="default" onClick={() =>actCancel({act:'cancel'})}>
                      撤销
                    </Button>
      


                    {
                      [ProjectStatesEnum.BUDGET,ProjectStatesEnum.FINAL].indexOf(basic.state)>-1&&basic.offline==1&&
                      <Button type="link" onClick={()=>{
                        setOfflineModal(true)
                        setBasic(basic)
                        
                      }}>
                        线下上会通过
                      </Button>
                    }

                </>
                
              }

              </div>
            }
            <ViewFlow onVisibleChange={setViewflowmodal} visible={viewflowmodal} thirdNo={thirdno}/>
            <Modal
              key="m1"
              title="线下上会通过"
              style={{ top: 20 }}
              width="60vw"
              visible={offlineModal}
              onOk={() => setOfflineModal(false)}
              onCancel={() => setOfflineModal(false)}
              footer={null}
            >
              <Offlineagree key={basic.id} data={basic} onChange={offlineAgree}/>
            </Modal>
            <Modal
              title=""
              style={{ top: 0,left:0, aspectRatio: '210/297'}}
              width={'60vw'}
              visible={printModal}
              onOk={() => setPrintModal(false)}
              onCancel={() => setPrintModal(false)}
              footer={null}
            >
              <Print record={basic} key={basic} typename={basic.typename}/>
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
              
              <Filescard key={urls} urls={urls}/>
            </Modal>
        </div>
        }
    </div>
    
  )
}

export default Viewflow