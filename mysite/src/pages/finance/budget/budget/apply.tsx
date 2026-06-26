import React, { createContext, useEffect, useRef, useState } from 'react';
import { Button, Descriptions, Form, Menu, message, Modal, Row, Steps, Tabs } from 'antd';
import Budgetdetail from './budgetdetail';
import Viewflow from './viewflow';
import { useModel } from 'umi';
import { getthirdno,startflow, viewflow } from './service';
import { BalanceTypes, needBudgetCheck, ProjectStatesEnum, ProjectTypesEnum } from '../config';
import ReportView from '../project/reportview';
import Flow from './flow';
import ProlistTable from '../project/prolistTable';
import Allfiles from '../project/allfiles';


// data
const { TabPane } = Tabs;
const Apply:React.FC<{data:any,onchange?: Function,onPayCheck?:Function,showReport?:boolean}> = ({showReport=true,data,onchange,onPayCheck}) =>{
  // data
  const [current, setCurrent] = useState(0);
  const [thirdno,setThirdno] = useState(data.thirdno);
  var [data1,setData1] = useState(data)
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const [form] = Form.useForm();

  const [modal, setModal] = useState(false)
  var [refresh,setRefresh]=useState(0)
  const [flowdata,setFlowdata]=useState<any>({})
  const [showview,setShowview]=useState(true)
  const [report,setReport] = useState('') 
const [activeKey, setActiveKey] = useState('1');
  
  // method
  if (!data1.thirdno) {
    useEffect(()=>{
      setShowview(false)
      getthirdno().then((e:any)=>setThirdno(e))
    },[])
  } 
  const tabChange = (e:any)=>{
    setCurrent(e)
  }
  const handleMenuClick = (e:any) => {
    setActiveKey(e.key);
  };
  const onViewchange = (e:any)=>{
 
  }

  const onFinish = (act:any) => {
    
    viewflow({projectid:data1.id,act}).then((res:any)=>{
      
      if (res.errorMessage) {
        Modal.error({
          title: '报错',
          content: res.errorMessage,
        });
      } else {
        Modal.confirm({
          title:"请确认流程是否正确",
          bodyStyle:{marginLeft:0},
          width: '600px',
          centered:false,
          content:(
            <div style={{marginLeft:'0!important'}}>
              <Flow key={data1.act}  data={res.viewdata} statusCn={res.statusCn} step={res.step}></Flow>
            </div>
          ),
          onOk:()=>{
            const par = {flowtype:data1.state,thirdno,projectid:data1.id,act:data1.act,report}
            var warning = ''
            
            if (data1.act==ProjectStatesEnum.FINAL){
              warning = '决算需材料齐全，提交后将无法关联合同和上传附件。（若是直接提交计量，请忽略）'
            }
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
  const start = (par:{})=>{
    startflow(par).then((res:any)=>{
      if (res.errorMessage) {
   
        Modal.error({
          title: res.errorMessage
        });
      } else {
        data1.thirdno = res.flow.data.ThirdNo
        
        onchange && onchange(data1)
      }
    })
  }

  const getflow = ()=>{
    viewflow({projectid:data1.id,act:data1.act}).then((res:any)=>{
      if (res.errorMessage) {
        Modal.error({
          title: '报错',
          content: res.errorMessage,
        });
      } else {
        setFlowdata(res)
        setModal(true)
      }
    })
  }
  
  // dom
  return (
    <Form   form={form}  >
      <div style={{ display: 'flex' }}>

        <div style={{marginBottom:'20px'}}>
          <Menu mode="horizontal" style={{minWidth:'500px'}} selectedKeys={[activeKey]} onClick={handleMenuClick}>
            <Menu.Item key='1'>审批</Menu.Item>
            {
              needBudgetCheck(data1.type)&&
              <>
                <Menu.SubMenu title="项目决算">
                  <Menu.Item key='20'>决算报告</Menu.Item>
                  <Menu.Item key='21'>收支</Menu.Item>
                  <Menu.Item key='22'>打印报告</Menu.Item>
                </Menu.SubMenu>
                <Menu.SubMenu title="项目预算">
                  <Menu.Item key='30'>预算报告</Menu.Item>
                  <Menu.Item key='31'>收支</Menu.Item>
                  <Menu.Item key='32'>打印报告</Menu.Item>
                </Menu.SubMenu>
              </>
            }
            {
              !needBudgetCheck(data1.type)&&
              <>
                <Menu.Item key="6">项目</Menu.Item>

              </>
            }
            
            <Menu.Item key="4">项目附件</Menu.Item>
            <Menu.Item key="5">历史审批记录</Menu.Item>
            
          </Menu>
        </div>


      </div>
      {
        activeKey=='1'&&<Viewflow  key={refresh} thirdno={data1.thirdno||data1.thirdNo} projectid={data1.id} onchange={onViewchange} ></Viewflow>
      }
      {
        activeKey=='20'&&
          <ReportView key={'finalreport'} id={data1.id} field={'finalreport'} edit={data1.creator==currentUser.wxuserid && data1.state<=ProjectStatesEnum.FINAL}  onChange={(text:any)=>{
      
              data1.finalreport = text
              setData1(data1)
              setReport(text)
            }}/>
      }
      {
        activeKey=='21'&&
        <Budgetdetail key='决算收支详情' id={data1.id} showTab={false} show={'final'}></Budgetdetail>
      }
      {
        activeKey=='22'&&
        <Viewflow key={'决算审批'+data1.id}  projectid={data1.id} state={ProjectStatesEnum.FINAL} ></Viewflow>
      }
      {
          activeKey=='30'&&
          <ReportView key={'budgetreport'+data1.id} id={data1.id} field={'budgetreport'} edit={data1.creator==currentUser.wxuserid && (data1.state<ProjectStatesEnum.FINAL||[ProjectTypesEnum.QITA].includes(data1.type))} onChange={(text:any)=>{
              data1.budgetreport = text
              setReport(text)
              setData1(data1)
            }}/>
        }
        {
          activeKey=='31'&&<Budgetdetail key={'预算收支'+data1.id} id={data1.id} showTab={false} show={'budget'}></Budgetdetail>
        }
        {
          activeKey=='32'&&<Viewflow key={'预算审批'+data1.id}  projectid={data1.id} state={ProjectStatesEnum.BUDGET}  ></Viewflow>
        }
        {
          activeKey=='4'&&<Allfiles projectid={data1.id}></Allfiles>
        }
        {
          activeKey=='5'&& <ProlistTable key={data1.id}  projectid={data1.id}   ></ProlistTable>
        }
        {
          activeKey=='6'&& <Budgetdetail key={'收支'+data1.id} id={data1.id} showTab={false} show={'all'}></Budgetdetail>
        }


    <div >
      
 
  
    

      <div style={{ marginTop: 24 }}>
          {
            (data1.thirdno==null||data1.thirdno=='')  && (data1.creator==currentUser.wxuserid) && 
            
            
            <div>
              
                
                {
                  
                  <Row>
                    
                      {
                        (data1.state==null||data1.state<ProjectStatesEnum.READYTOSUBMIT) &&
                        <>
                        <Button type="primary" onClick={()=>{
                          data1.act=data1.state||ProjectStatesEnum.BUDGET
                          if (data1.act==ProjectStatesEnum.START) {
                            
                            data1.act=ProjectStatesEnum.BUDGET
                          }
              
                          onFinish(data1.act)
                        }}>
                          提交{data1.state<ProjectStatesEnum.FINAL?'预算':'决算'}
                        </Button>

                        </>
                      }
                      {
                        (data1.state==null||data1.state==ProjectStatesEnum.START) &&
                        <Button  onClick={()=>{
                          data1.act=ProjectStatesEnum.START
                          
                          onFinish(data1.act)
                          
                        }}>
                          仅立项
                        </Button>
                      }
                      {
                        (data1.state!=ProjectStatesEnum.SUBMITTED&&data1.state>ProjectStatesEnum.BUDGET&&data1.directsubmit!=1) &&
                        <Button  type="dashed"  onClick={()=>{
                          data1.act=ProjectStatesEnum.READYTOSUBMIT
                     
                          
                          onFinish(data1.act)
                        }}>
                          提交计量
                        </Button>
                      }
                      {
                        (data1.state==ProjectStatesEnum.SUBMITTED||data1.directsubmit==1) &&
                        <Button  type="primary" danger  onClick={()=>{
                          data1.act=ProjectStatesEnum.WITHDRAW
                     
                          
                          onFinish(data1.act)
                        }}>
                          撤回提交计量
                        </Button>
                      }

                  </Row>
                }
                
                

            

            </div>
            
          }

          
      </div>
      <Modal
        key="流程审批m1"
        title="流程审批"
        style={{ top: 20 }}
        width="60vw"
        visible={modal}
        onOk={() => setModal(false)}
        onCancel={() => setModal(false)}
        footer={null}
      >
        <Flow key={refresh} data={flowdata.viewdata} statusCn={flowdata.statusCn} step={flowdata.step}></Flow>
        <div>流程id：{flowdata.viewdata?.templateid}</div>
      </Modal>
      
    </div>
    </Form>
  )
}
export default Apply