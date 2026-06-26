import { Button, DatePicker, Input, InputNumber, Modal, Space, Timeline } from "antd"
import moment from "moment"
import { useEffect, useState } from "react"

import { DeleteOutlined } from "@ant-design/icons"
import { delenteraccount, getenteraccount, saveenteraccount } from "./service"





const Enteraccount:React.FC<{bid:any,projectid:any,type:any,onChange?:Function,editable?:boolean,showAll?:boolean}> = ({showAll=false,type,projectid,bid,onChange,editable=true})=>{

  const [datas,setDatas] = useState<any>([])
  var [refreshKey, setRefreshKey]= useState(0)
  const [amount,setAmount]=useState(0)
  const [content,setContent]=useState('')
  const [loading, setLoading] = useState(false)
  const [total,setTotal]=useState(0)
  const addCollection = ()=>{
    if (!amount||!content){
      Modal.error({title:'入账凭证号和金额不能为空'})
      return
    }
    setLoading(true)
    setTimeout(() => {
      setLoading(false)
    }, 3000);
    
    saveenteraccount({amount,bid,content,projectid,type}).then(res=>{
      setLoading(false)
      if (res.errorMessage){
        Modal.error({title:res.errorMessage})

      } else {
        get()
      }
    })
  }
  const del = (e:any)=>{
    if (e.state!=1){
      Modal.error({title:'已删除，不要重复操作'})
      return
    }
    Modal.confirm({
      title: '确定要删除吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        delenteraccount({id:e.id}).then((res:any)=>{
          if (res.errorMessage){
            Modal.error({title:res.errorMessage})
 
          } else {
            get()
          }
        })
      },
    });
  }
  useEffect(()=>{
    console.log('bid:',bid)
    if (bid||projectid) {
      get()
    }

  },[bid,projectid])

  const get=()=>{
    getenteraccount({bid,projectid,showAll,type}).then((res:any)=>{
      if (res.errorMessage){
        Modal.error({title:res.errorMessage})
      } else {

        setDatas(res.data)
        if (res.data && res.data.length>0){

          setTotal(res.data.reduce((accumulator:any, current:any) => {
            return accumulator + (current.state==1?current.amount:0);
          }, 0))
        }
        setRefreshKey(++refreshKey)
        
        
      }
    })
  }
  return (
  

  <div key={'paycollectionKey'}>

      
      <Timeline key={'paycollection-timeline'}>

          <div style={{marginBottom:'20px'}}>累计入账金额：{total}元</div>
          {
            (datas||[]).map((e:any,index:any)=>{
              return (<div key={'div1'+index} >
              <Timeline.Item key={'timeline1'+index} color={e.state?'green':'red'}>
                  <span style={e.state==1?{}:{textDecoration:'line-through',color:'#b9b6b6'}}>
                    <span>{ e.state?<DeleteOutlined style={{fontSize:'18px'}} size={100} color="red" onClick={()=>{ editable && del(e)}}/>:''} </span>


                    <span>{e.name+'确认 '+e.inserttime.substring(0,10)}</span>
                    <span color={e.state?'green':'red'}>{e.state?(' 入账'):' 删除入账金额'}</span>
                    <span style={{color:e.state?'green':'red',fontWeight:'bolder'}}>{e.amount>0?e.amount:-e.amount}元</span>
                    {
                      e.content&&
                      <span style={{marginLeft:'20px'}}>入账凭证号：{e.content}</span>
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
          
          <InputNumber prefix="￥" placeholder="金额" style={{width:'250px'}} onChange={(e:any)=>{
          
            setAmount(e)
          }} />
          <Input   placeholder="入账凭证号" onChange={(e:any)=>{
            setContent(e.target.value)
          }}/>
          <Button style={{ width: 80 }} type="primary" loading={loading} onClick={()=>{
            Modal.confirm({
              title: '确定要入账吗？',
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

export default Enteraccount