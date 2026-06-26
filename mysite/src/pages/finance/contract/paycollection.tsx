import { Button, DatePicker, Input, InputNumber, InputRef, Modal, Popover, Space, Timeline } from "antd"
import moment from "moment"
import { useEffect, useRef, useState } from "react"
import { delpaycollection, delpaycollectioncheck, delpaycondition, getcontract, paycollectioncheck, paycollectionnotice, savepaycollection } from "./service"
import { DeleteOutlined } from "@ant-design/icons"
import { CONTRACT_AGENTID, ContractStatesEnum } from "./config"
import { BalanceTypes } from "../budget/config"
import TextArea from "antd/lib/input/TextArea"




const PayCollection:React.FC<{contractid:any,onChange?:Function,editable?:boolean,financechek?:boolean,onPayCheck?:Function}> = ({contractid,onChange,editable=true,financechek=false,onPayCheck})=>{

  const [obj,setObj] = useState<any>({})
  var [refreshKey, setRefreshKey]= useState(0)
  const [amount,setAmount]=useState(0)
  const [date,setDate]=useState('')
  const [loading, setLoading] = useState(false)
  const [patype,setPaytype]=useState('回款')
  const inputRef=useRef<InputRef>(null)
  const addCollection = ()=>{
    if (!date||!amount){
      Modal.error({title:'日期或金额不能为空'})
      return
    }
    setLoading(true)
    savepaycollection({date,amount,contractid,agentid:CONTRACT_AGENTID}).then(res=>{
      setLoading(false)
      if (res.errorMessage){
        Modal.error({title:res.errorMessage})

      } else {
        getcontract({id:contractid}).then((res:any)=>{
          if (res.errorMessage){
            Modal.error({title:res.errorMessage})
          } else {
            setObj(res.data)
            setRefreshKey(++refreshKey)
            onChange && onChange(res.data?.paycollection)
            
          }
        })
      }
    })
  }
  const del = (e:any)=>{
    if (e.state==0){
      Modal.error({title:'该'+patype+'已被删除，不要重复操作'})
      return
    }
    Modal.confirm({
      title: '确定要删除吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        delpaycollection({id:e.id,agentid:CONTRACT_AGENTID,signdeptid:e.signdeptid}).then((res:any)=>{
          if (res.errorMessage){
            Modal.error({title:res.errorMessage})
 
          } else {
            getcontract({id:contractid}).then((res:any)=>{
              if (res.errorMessage){
                Modal.error({title:res.errorMessage})
              } else {
      
                setObj(res.data)
                setRefreshKey(++refreshKey)
                onChange && onChange(res.data?.paycollection)
                
              }
            })
          }
        })
      },
    });
  }
  const get=()=>{
    if (contractid) {
      getcontract({id:contractid}).then((res:any)=>{
        if (res.errorMessage){
          Modal.error({title:res.errorMessage})
        } else {

          setObj(res.data)
          if(res.data.type==BalanceTypes.EXPEND){
            setPaytype('付款')
          }
          setRefreshKey(++refreshKey)
          
          
        }
      })
    }
  }
  useEffect(()=>{
    get()

  },[contractid])
  return (
  

  <div key={'paycollectionKey'}>

      
      <Timeline key={'paycollection-timeline'}>
        <div style={{marginBottom:'20px'}}>履约条件：（合同总额：{obj.amount}元，累计{patype}：{((obj.paycollection||0)/obj.amount*100).toFixed(1)}%）</div>
          {
            (obj.payconditions||[]).map((e:{date:string,rate:Number,current:10},index:any)=>{
              return (<div key={'div2'+index} >
              
              <Timeline.Item key={'timeline2'+index}>
                  <span >{e.date.substring(0,10)}前累计{patype}</span>
                  <span style={{color:'red',fontWeight:'bolder'}}>{e.rate}%</span>
         
              </Timeline.Item>
                
              </div>)
            })
          }
          <div style={{marginBottom:'20px'}}>{patype}纪录：（{patype}金额：{obj.paycollection||0}元）</div>
          {
            (obj.paycollections||[]).map((e:any,index:any)=>{
              return (<div key={'div1'+index} >
              <Timeline.Item key={'timeline1'+index} color={e.state?'green':'red'}>
                  <span style={(e.state>0&&e.valid==1)?{}:{textDecoration:'line-through',color:'#b9b6b6'}}>
                    <span>{ e.state&& editable?<DeleteOutlined style={{fontSize:'18px'}} size={100} color="red" onClick={()=>{ del(e)}}/>:''} </span>


                    <span>{e.name+'确认 '+e.date.substring(0,10)}</span>
                    <span color={e.state?'green':'red'}>{e.state?(' '+patype):' 删除'+patype}</span>
                    <span style={{color:e.state?'green':'red',fontWeight:'bolder'}}>{e.amount>0?e.amount:-e.amount}元</span>
                    {
                      financechek &&
                      <span>
                        
                        {
                          e.state==1&&e.valid==1&&
                          <Popover
                          title={null}
                          content={<>
                          
                              <div style={{padding:'20px'}}>
                                  <Input  ref={inputRef} placeholder="输入财务备注.."/>
                                  <Button type="primary" style={{width:'100%'}} onClick={()=>{
                                    Modal.confirm({
                                      title: '提交后无法撤回，确认已收到款项吗？',
                                      okText: '确认',
                                      cancelText: '取消',
                                      onOk: async () => {
                                        var note = inputRef.current?.input?.value;
                    
                                  
                                        paycollectioncheck({id:e.id,note,agentid:CONTRACT_AGENTID}).then((res:any)=>{
                                          if(res.errorMessage){
                                            Modal.error(res.errorMessage)
                                          }else{
                                            Modal.info({title:'成功'})
                                            onPayCheck&&onPayCheck()
                                            get()
                                          }
                                        })

                                      },
                                    })

                                  }}>确 认</Button>
                              </div>
                          
                          </>}
                        
                          trigger="click"
                        >
                          <Button type="link">财务确认</Button>
                        </Popover>
                        }
                        {
                          e.state==3&&
                          <Space>
                            <span style={{marginLeft:'10px'}}>{e.note?'财务确认:'+e.note:'财务已确认'}</span>
                            <Button danger type="link" onClick={()=>{
                              Modal.confirm({
                                title: '要撤销回款确认吗？',
                                okText: '确认',
                                cancelText: '取消',
                                onOk: async () => {
                                  delpaycollectioncheck({id:e.id,agentid:CONTRACT_AGENTID}).then((res:any)=>{
                                    if(res.errorMessage){
                                      Modal.error({title:res.errorMessage})
                                    }else{
                                      Modal.info({title:'成功'})
                                      onPayCheck&&onPayCheck()
                                      get()
                                    }
                                  })
                                }
                              })
                            }}>撤销确认</Button>
                          </Space>
                        }
                      </span>
                    }
                    {
                      e.state==1&&e.valid==1&&
                      <Button type="link" onClick={()=>{
                        Modal.confirm({
                          title: '该笔回款要通知财务进行确认吗？',
                          okText: '确认',
                          cancelText: '取消',
                          onOk: async () => {
                            paycollectionnotice({id:e.id}).then((res:any)=>{
                              if(res.errorMessage){
                                Modal.error({title:res.errorMessage})
                              }else{
                                Modal.info({title:'通知成功'})
                              }
                            })
                          }
                        })
                      }}>通知财务</Button>
                    }
                  </span>

                  
              </Timeline.Item>
                
              </div>)
            })
          }
         
      </Timeline>
      {
        editable && 
        <Space direction="horizontal" style={{marginBottom:'20px'}}>
          <DatePicker   format="YYYY-MM-DD" placeholder="日期" onChange={(e:any)=>setDate(e.format('YYYY-MM-DD'))}/>
          <InputNumber prefix="￥" placeholder="金额" style={{width:'250px'}} onChange={(e:any)=>{
          
            setAmount(e)
          }} />
          <Button style={{ width: 80 }} type="primary" loading={loading} onClick={()=>{
            Modal.confirm({
              title: '确定要'+patype+'吗？',
              okText: '确认',
              cancelText: '取消',
              onOk: () => {
                addCollection()
              },
            });

          }}>
            确认
          </Button>
        </Space>
      }
      
  </div>
  
  )
}

export default PayCollection