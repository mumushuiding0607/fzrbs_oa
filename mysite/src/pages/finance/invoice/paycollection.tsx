import { Button, DatePicker, Input, InputNumber, InputRef, Modal, Popover, Space, Timeline } from "antd"

import { useEffect, useRef, useState } from "react"
import { DeleteOutlined } from "@ant-design/icons"
import { delpaycollection, getpaycollection } from "./service"
import ContractsWithProjects from "../budget/project/contractsWithProjects"






const PayCollection:React.FC<{EIid?:any,contractids?:any,params?:any}> = ({EIid,contractids,params})=>{

  const [paycollections,setPaycollections] = useState<any>([])
  var [refreshKey, setRefreshKey]= useState(0)
  const [record,setRecord]= useState<any>([])
  const [showProjects,setShowProjects] = useState(false)
  const [patype,setPaytype]=useState('回款')

  
  const del = (e:any)=>{

    Modal.confirm({
      title: '确定要删除吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        delpaycollection({id:e.id}).then((res:any)=>{
          if (res.errorMessage) {
            Modal.error({title:res.errorMessage})
          } else {
            get()
          }
        })
      },
    });
  }
  const get=()=>{

    getpaycollection({EIid:EIid,contractids,...params}).then((res:any)=>{
       if (res.errorMessage) {
         Modal.error({title:res.errorMessage})
      } else {
         setPaycollections(res.data)
         setRefreshKey(++refreshKey)
      }
       
    })
  }
  useEffect(()=>{
    get()

  },[EIid,contractids])
  return (
  

  <div >

      
      <Timeline key={'paycollection-timeline'}>


 
          {
            (paycollections||[]).map((e:any,index:any)=>{
              return (<div key={'div1'+index} >
              <Timeline.Item key={'timeline1'+index} color={e.state?'green':'red'}>
                  <span style={(e.state>0&&e.valid==1)?{}:{textDecoration:'line-through',color:'#b9b6b6'}}>
                    <span>{ e.valid&&e.amount>0?<DeleteOutlined style={{fontSize:'18px'}} size={100} color="red" onClick={()=>{ del(e)}}/>:''} </span>
                    <span>{(e.sysnote||'')+' '+e.date.substring(0,10)}</span>
                    <span color={e.state?'green':'red'}>{e.state?(' '+patype):' 删除'+patype}</span>
                    <span style={{color:e.state?'green':'red',fontWeight:'bolder'}}>{e.amount>0?e.amount:-e.amount}元</span>
                    {
                      e.updator!=null &&
                      <span style={{marginLeft:'15px'}}>更新人：{e.updator}</span>
                    }
                  </span>
                  {
                    e.title &&
                    <div style={{color:'#1890FF'}} onClick={()=>{
                      setRecord(e)
                      setShowProjects(true)
                    }}>《{e.title}》 </div>
                    

                  }

                  
              </Timeline.Item>
                
              </div>)
            })
          }
         
      </Timeline>
      
      <ContractsWithProjects key={'cp'+record.contractid}  contractids={record.contractid} visible={showProjects} onClose={()=>setShowProjects(false)}/>

      
  </div>
  
  )
}

export default PayCollection