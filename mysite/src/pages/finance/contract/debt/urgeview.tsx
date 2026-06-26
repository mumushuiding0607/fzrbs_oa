
import { Avatar, Button, Card, Descriptions, Divider, Form,  Modal, Popover, Row, Table, Tabs, Tag, Typography } from "antd";

import { useEffect, useState } from "react";
import Flow from "../../budget/budget/flow";
import ViewFlow from "../../Flowtemplate/viewflow";
import { debtflowact, delurge, viewdebt } from "./service";
import Urgelogs from "./urgelogs";
import TextArea from "antd/lib/input/TextArea";
import AddUrgeLog from "./AddUrgeLog";
import FinishUrge from "./FinishUrge";
import View from "../view";
import AddFile from "./AddFile";
import AddDebtUrge from "./AddDebtUrge";
import Filescard from "../filescard";


const row:React.CSSProperties = {
  display: 'flex',
  flexDirection: 'row',
  width: '100%'
}
const UrgeView:React.FC<{contractid:any,thirdNo?:any,debturgeid?:any,urgeserial?:any,onVisibleChange?:Function,visible:boolean}> = ({urgeserial,thirdNo,contractid,debturgeid,visible=false,onVisibleChange}) =>{
  const [showModal,setShowModal] = useState(visible)
  var [refresh,setRefresh]=useState(0)
  const [data,setData]=useState<any>({})
  const[viewflowmodal,setViewflowmodal]=useState(false)
  const [speech,setSpeech]=useState('')
  const [showAddLog,setShowAddLog]=useState(false)
  const { Title } = Typography;
  const [logkey,setLogkey]=useState(0)
  const [urgelog,setUrgelog]=useState<any>({})
  const [showFinishUrge,setShowFinishUrge]=useState(false)
  const [contract, setContract] = useState<any>({})
  const [viewmodal,setViewmodal] = useState(false)
  const [showAddFile,setShowAddFile]=useState(false)
  const [addView,setAddView]=useState(false)
  const [filekey,setFilekey]=useState(0)
  const actfun = (act:any)=>{
    var actname = ''
    switch (act) {
      case 'agree':
        actname = '同意'
        break;
      // 驳回
      case 'reject':
        actname = '驳回'
        break;
      // 撤销
      case 'cancel':
        actname = '撤销'
        break;
      // 催办
      case 'urge':
        actname = '催办'
        break;
      default:
        break;
    }
    
    Modal.confirm({
      title: '确认'+actname+'吗？',
      onOk:()=>{
        var flow:any = {thirdNo:data.flowdata?.thirdNo,act:act,debturgeid:data.contract?.debturgeid}
        if (speech){
          flow.speech = speech
        }
        debtflowact(flow).then((flowres:any)=>{
          if (flowres.errorMessage){
            Modal.error({
              title: flowres.errorMessage,
            });
          }else{
            Modal.info({title:'操作成功！'})
            getdata()
            
          }
        })
      }
    })
    
  }
  useEffect(()=>{
    setShowModal(visible)
    if (visible&&(contractid||debturgeid)){
      getdata()
              
    }
  },[visible,debturgeid,contractid])

  const getdata=  ()=>{
    
    viewdebt({
      id:contractid,
      thirdNo,
      debturgeid,
      urgeserial,
    }).then((res:any)=>{
      setData(res)
      setRefresh(refresh+1)
    })
  }




  
  return (

  <div >
  <Modal
        title={
          <>
              <span>催款审批</span>
              <span style={{color:'#1890FF',marginLeft:'10px'}} onClick={()=>{
                setContract({debturgeid:data.urge?.id,contractid})
                setAddView(true)
              }}>编辑</span>
              <span style={{color:'red',marginLeft:'10px'}} onClick={()=>{
                Modal.confirm({
                  title: '确认删除吗？',
                  onOk:()=>{
                    delurge({id:data.urge?.id}).then((res:any)=>{
                      if (res.errorMessage){
                        Modal.error({
                          title: res.errorMessage,
                        });
                      }else{
                        Modal.info({title:'删除成功！'})
                      }
                    })
                  }
                })
              }}>删除</span>
              </>
        }
        style={{ top: 20, }}
        visible={visible}
        width={800}
        onOk={() => {
          onVisibleChange && onVisibleChange(false)
        }}
        onCancel={() => onVisibleChange && onVisibleChange(false)}
        footer={
          <div style={{ textAlign: 'left',padding:'20px 0 0 0' }}>
   

            

            {/* 同意 & 驳回 - 当前审批人可见 */}
            {data.flowdata?.isCurrentApprover && (
              <>
                <Button onClick={()=>actfun('agree')} type="primary">
                  同意
                </Button>
                <Button onClick={()=>actfun('reject')} danger style={{ marginRight: 8 }}>
                  驳回
                </Button>
                
              </>
            )}
            {/* 催办按钮 - 审批中，非当前处理人 */}
            {data.flowdata?.isApproving && (
              <>
              <Button onClick={()=>actfun('urge')} type="dashed" style={{ marginRight: 8 }}>
                催办
              </Button>
              <Button onClick={()=>actfun('cancel')} style={{ marginRight: 8 }}>
                撤销
              </Button>
              
              </>
            )}
            {/* 催收中 */}
            {data?.urgestate!=5 && (
              <>
              <Button onClick={()=>{
                setShowAddLog(true)
                setUrgelog({contractid,debturgeid:data?.contract?.debturgeid||debturgeid||data?.urge?.id,urgetype:data?.urge?.urgetype})
              }} type="primary" style={{ marginRight: 8 }}>
                每月反馈
              </Button>
              <Button type="default" danger onClick={()=>{
                setShowAddFile(true)
                setUrgelog({contractid,debturgeid:data?.contract?.debturgeid||debturgeid||data?.urge?.id,urgetype:data?.urge?.urgetype,type:1})
              }}  style={{ marginRight: 8 }}>
                清欠措施
              </Button>

              <Button onClick={()=>{
                Modal.confirm({
                  title: '确定结束催收吗？结束催收后，无法添加清欠措施和处置结果',
                  okText: '确定',
                  cancelText: '取消',
                  onOk: () => {
                    setShowFinishUrge(true)
                    setUrgelog({id:data?.contract?.debturgeid})
                  }
                })
              }} style={{ marginRight: 8 }}>
                结束催收
              </Button>
              
              </>
            )}
          </div>
        }
      >
        
        <div key={refresh} style={{position:'relative'}}>
  
          {
            !data.viewdata &&
            <>
            
            <Card>
              暂无审批流程
            </Card>
            <Descriptions
                        bordered
                        size={'small'}
                        column={2}
                        labelStyle={{width:120}}
                      >
                        {
                          data.flowdata?.thirdNo&&
                          <Descriptions.Item label="审批单号"><span style={{color:"#1890FF"}} onClick={()=>{
                                  setViewflowmodal(true)
                                  
                              }}>{data.flowdata?.thirdNo}</span></Descriptions.Item>
                        }
  
                        <Descriptions.Item label="催款编号">{data.urge?.serial}</Descriptions.Item>
                        <Descriptions.Item label="清欠方式">{data.urge?.urgetypename}</Descriptions.Item>
                        <Descriptions.Item label="合同名称" span={2} ><span style={{color:"#1890FF"}} onClick={()=>{
                          setViewmodal(true)
                          setContract(data.contract)
                        }}>{data.contract?.title}</span></Descriptions.Item>
                        <Descriptions.Item label="合同金额">{data.contract?.amount}</Descriptions.Item>
                        <Descriptions.Item label="合同回款">{data.contract?.paycollection||0}</Descriptions.Item>
                        <Descriptions.Item label="催款金额">{data.urge?.debtamount||(data.contract?.amount-(data.contract?.paycollection)||0)}</Descriptions.Item>
  
                        <Descriptions.Item label="逾期时间">{data.contract?.paydate||data.urge?.overduedate}</Descriptions.Item>
                        <Descriptions.Item label="账龄">{data.contract?.age||data.urge?.age}</Descriptions.Item>
                        <Descriptions.Item label="债务方信息" span={2}>
                          <>
                          <p>{data.contract?.partaname}</p>
                          <p>{data.urge?.contactor+" "+data.urge?.mobile}</p>
                          <p>{data.urge?.address}</p>
                          </>
                        </Descriptions.Item>
                        <Descriptions.Item label="拖欠原因" span={2}>{data.urge?.reason}</Descriptions.Item>
                        <Descriptions.Item label="备注" span={2}>{data.urge?.note}</Descriptions.Item>
                        <Descriptions.Item label="处置措施" >{data.urge?.dealresultname}</Descriptions.Item>
                        <Descriptions.Item label="处置备注" >{data.urge?.dealresultnote}</Descriptions.Item>
                        {
                          data.urge?.fileurls&&data.urge?.fileurls.length>0&&<Descriptions.Item label="附件" span={2}><Filescard  mode='list' urls={data.urge?.fileurls}/></Descriptions.Item>
                        }
                        {
                          data.urge?.dealresultfileurls&&data.urge?.dealresultfileurls.length>0&&<Descriptions.Item label="处置附件" span={2}><Filescard  mode='list' urls={data.urge?.dealresultfileurls}/></Descriptions.Item>
                        }
                        
<Descriptions.Item label="清欠措施" span={2}><Urgelogs key={'urgelogs'+filekey} contractid={data.contract?.id} debturgeid={debturgeid} type={1}/></Descriptions.Item>
               
                        <Descriptions.Item label="每月反馈" span={2}><Urgelogs key={'urgelogs'+logkey} contractid={data.contract?.id} debturgeid={debturgeid} type={0} /></Descriptions.Item>
                    </Descriptions>
             
 

            
            </>
            
          }

        {
          data.viewdata?.step !== undefined &&
            <div >

                {
                  data.flowdata?.thirdNo &&
                  <div>
                    <div style={{...row,alignItems:'center'}}>
                      <Avatar src={data.flowdata.avatarUrl} size="large" />
                      <Title style={{height:'100%',display:'flex',alignItems:'center'}} level={4} >
                          {data.flowdata.userName+'的审批申请'}
                          
                          <Tag color="red">{data.statusCn[data.flowdata?.status]}</Tag>
                          

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
                          data.flowdata?.thirdNo&&
                          <Descriptions.Item label="审批单号"><span style={{color:"#1890FF"}} onClick={()=>{
                                  setViewflowmodal(true)
                                  
                              }}>{data.flowdata?.thirdNo}</span></Descriptions.Item>
                        }
  
                        <Descriptions.Item label="催款编号">{data.urge?.serial}</Descriptions.Item>
                        <Descriptions.Item label="清欠方式">{data.urge?.urgetypename}</Descriptions.Item>
                        <Descriptions.Item label="合同名称" span={2} ><span style={{color:"#1890FF"}} onClick={()=>{
                          setViewmodal(true)
                          setContract(data.contract)
                        }}>{data.contract?.title}</span></Descriptions.Item>
                        <Descriptions.Item label="合同金额">{data.contract?.amount}</Descriptions.Item>
                        <Descriptions.Item label="合同回款">{data.contract?.paycollection||0}</Descriptions.Item>
                        <Descriptions.Item label="催款金额">{data.urge?.debtamount||(data.contract?.amount-(data.contract?.paycollection)||0)}</Descriptions.Item>
                        <Descriptions.Item label="逾期时间">{data.contract?.paydate||data.urge?.overduedate}</Descriptions.Item>
                        <Descriptions.Item label="账龄">{data.contract?.age||data.urge?.age}</Descriptions.Item>
                        <Descriptions.Item label="债务方信息" span={2}>
                          <>
                          <p>{data.urge?.partaname}</p>
                          <p>{data.urge?.contactor+" "+data.urge?.mobile}</p>
                          <p>{data.urge?.address}</p>
                          </>
                        </Descriptions.Item>
                        <Descriptions.Item label="拖欠原因" span={2}>{data.urge?.reason}</Descriptions.Item>
                        <Descriptions.Item label="备注" span={2}>{data.urge?.note}</Descriptions.Item>
                        <Descriptions.Item label="处置措施" >{data.urge?.dealresultname}</Descriptions.Item>
                        <Descriptions.Item label="处置备注" >{data.urge?.dealresultnote}</Descriptions.Item>
                        {
                          data.urge?.fileurls&&data.urge?.fileurls.length>0&&<Descriptions.Item label="附件" span={2}><Filescard  mode='list' urls={data.urge?.fileurls}/></Descriptions.Item>
                        }
                        {
                          data.urge?.dealresultfileurls&&data.urge?.dealresultfileurls.length>0&&<Descriptions.Item label="处置附件" span={2}><Filescard  mode='list' urls={data.urge?.dealresultfileurls}/></Descriptions.Item>
                        }
<Descriptions.Item label="清欠措施" span={2}><Urgelogs key={'urgelogs'+filekey} contractid={data.contract?.id} debturgeid={debturgeid}  type={1}/></Descriptions.Item>
                        
                        <Descriptions.Item label="每月反馈" span={2}><Urgelogs key={'urgelogs'+logkey} contractid={data.contract?.id} debturgeid={debturgeid} type={0} /></Descriptions.Item>
                    </Descriptions>
                  </div>
                }

              

              <Flow data={data.viewdata}  statusCn={data.statusCn} step={data.viewdata.step+1} ></Flow>
              {
                data.flowdata?.isCurrentApprover  && <>
                
                  <TextArea placeholder="审批意见" autoSize={{ minRows: 2, maxRows: 4 }} value={speech} onChange={(e) => setSpeech(e.target.value)}/>
                  <Divider/>
                  </>
                }
 
             
              <ViewFlow onVisibleChange={setViewflowmodal} visible={viewflowmodal} thirdNo={data.flowdata?.thirdNo}/>


          </div>
        }
 
       </div>
       
      </Modal>
     <AddUrgeLog visible={showAddLog} data={urgelog} onClose={()=>setShowAddLog(false)}  onChange={()=>{
        
        setShowAddLog(false)
        setLogkey(logkey+1)
     }}/>
     <AddFile visible={showAddFile} data={urgelog} onClose={()=>setShowAddFile(false)}  onChange={()=>{
        setShowAddFile(false)
        setFilekey(filekey+1)
     }}/>
     <FinishUrge visible={showFinishUrge} data={urgelog} onClose={()=>setShowFinishUrge(false)}  onChange={()=>{
        setShowFinishUrge(false)
     
     }}/>
     <AddDebtUrge key={'addurge'+contract.debturgeid} data={contract} action="update" visible={addView}  onVisibleChange={(visible:any)=>setAddView(visible)} onSuccess={()=>{
       getdata()
    }}/>
     <Modal
      width={850}
      style={{ top: 0}}
      visible={viewmodal}
      onOk={() => setViewmodal(false)}
      onCancel={() => setViewmodal(false)}
      footer= {null}
    >
      
      <View id={contract.id} key={'contract'+contract.id} paystate={contract.paystate} attachNumber = {contract.attachNumber}/>
    </Modal>
  </div>
  )
}

export default UrgeView