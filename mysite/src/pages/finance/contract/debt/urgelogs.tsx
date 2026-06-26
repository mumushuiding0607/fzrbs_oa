import {  Avatar, Modal, Timeline } from "antd"
import { useEffect, useRef, useState } from "react"
import { geturgelogs } from "./service"
import Filescard from "../filescard"
import AddUrgeLog from "./AddUrgeLog"
import AddFile from "./AddFile"




const row:React.CSSProperties = {
  display: 'flex',
  flexDirection: 'row',
  width: '100%'
}
const Urgelogs:React.FC<{contractid:any,debturgeid?:any,type:any}> = ({type,contractid,debturgeid})=>{
  const [datas,setDatas] = useState<any>([])
  const [showAddLog,setShowAddLog]=useState(false)
  const [logkey,setLogkey]=useState(0)
  const [urgelog,setUrgelog]=useState<any>({})
  const [showAddFile,setShowAddFile]=useState(false)
  const get=()=>{
    if (contractid||debturgeid) {
      geturgelogs({contractid,debturgeid,type}).then((res:any)=>{
        setDatas(res.data)
      })
    }
  }
  useEffect(()=>{
    get()

  },[contractid,debturgeid])
  return (<>

        <Timeline key={'paycollection-timeline'} style={{marginTop:'20px'}}>

            
            {
              (datas||[]).map((item:any,index:any)=>{
                return (<div >
                <Timeline.Item key={'timeline1'+index} >
                    <div style={row} onClick={()=>{
                      setUrgelog(item)
                      
                      if (item.type==1){
                        setShowAddFile(true)
                      }else{
                        setShowAddLog(true)
                      }
                    }}>
                      {/* <Avatar src={item.avatar} style={{ marginRight: 8 }} /> */}
             
                      <span>
                        {(item.updatetime||item.date||item.inserttime).substring(0,10)+" "+(item.urgetypename||'')+" "+item.uploadername||item.creatorname}
                      </span>
                    </div>
                    {
                        item.note && <div style={{color:'gray'}}>
                         
                          {item.note}
                        </div>
                      }
                      <div style={{padding:'3px 0 0 0px'}}>
                      
                     {
                        item.fileurls && item.fileurls.length>0 && (
                          <div >
                            <Filescard key={logkey}  mode='list' urls={item.fileurls}/>
                          </div>
                        )
                      }
                    </div>
                    <div style={row} >
                      {
                        item.dealdate &&
                        <>
            
                          <span>{(item.dealdate).substring(0,10)+" "+(item.urgeresultname||'')+" "}</span>
                          
                        </>
                        
                      }
                    </div>
                    <div style={{color:'gray'}}>{item.dealnote}</div> 
                    
                    <div style={{padding:'3px 0 0 0px'}}>
                      
                     {
                        item.dealfileurls && item.dealfileurls.length>0 && (
                          <div >
                            <Filescard key={logkey}  mode='list' urls={item.dealfileurls}/>
                          </div>
                        )
                      }
                    </div>
                </Timeline.Item>
                  
                </div>)
              })
            }
          
        </Timeline>
        <AddUrgeLog visible={showAddLog} data={urgelog} onClose={()=>setShowAddLog(false)}  onChange={()=>{
            setShowAddLog(false)
            
            geturgelogs({contractid,debturgeid,type}).then((res:any)=>{
              setDatas(res.data)
              setLogkey(logkey+1)
            })
        }}/>
        <AddFile visible={showAddFile} data={urgelog} onClose={()=>setShowAddFile(false)}  onChange={()=>{
            setShowAddLog(false)
            
            geturgelogs({contractid,debturgeid,type}).then((res:any)=>{
              setDatas(res.data)
              setLogkey(logkey+1)
            })
        }}/>
      </>

  
  )
}

export default Urgelogs