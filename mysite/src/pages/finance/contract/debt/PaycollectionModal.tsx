import { Button, DatePicker, Input, InputNumber, InputRef, Modal, Popover, Space, Timeline } from "antd"

import { useEffect, useRef, useState } from "react"
import { getvalidpaycollections } from "./service"
import ContractsWithProjects from "../../budget/project/contractsWithProjects"






const PayCollectionModal:React.FC<{datestart?:any,dateend?:any,parta?:any,visible:any,onVisibleChange:Function}> = ({datestart,dateend,parta,visible,onVisibleChange})=>{

  const [paycollections,setPaycollections] = useState<any>([])
  var [refreshKey, setRefreshKey]= useState(0)
  const [record,setRecord]= useState<any>([])
  const [showProjects,setShowProjects] = useState(false)
  const [patype,setPaytype]=useState('回款')


  const get=()=>{
    
    if (!datestart&&!parta&&!dateend) return
    getvalidpaycollections({datestart,parta,dateend}).then((res:any)=>{
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

  },[datestart,dateend,parta])
  return (
  
<Modal
      title="回款纪录"
      visible={visible}
      width={500}
      onCancel={() => onVisibleChange(false)}
      footer={null}
    >
 

      
      <Timeline key={'paycollection-timeline'}>


 
          {
            (paycollections||[]).map((e:any,index:any)=>{
              return (<div key={'div1'+index} >
              <Timeline.Item key={'timeline1'+index} color={e.state?'green':'red'}>
                  <span style={(e.state>0&&e.valid==1)?{}:{textDecoration:'line-through',color:'#b9b6b6'}}>
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

      
  </Modal>
  
  )
}

export default PayCollectionModal