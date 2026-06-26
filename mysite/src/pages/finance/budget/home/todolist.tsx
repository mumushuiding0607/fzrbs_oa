import { AntDesignOutlined } from "@ant-design/icons";
import { Avatar, Col, Row } from "antd";
import { CSSProperties, useEffect, useState } from "react";
import { todolist } from "../service";
import { request, useHistory } from "umi";


const contaniner:CSSProperties = {
  background: 'white',
  width:'100%',
  // height: '10vw',
  borderRadius:'1vw',
  display: 'flex',
  flexDirection: 'row',
  alignItems: 'center',
  padding: '1vw 2vw',
  boxSizing:'border-box',
  // justifyContent: 'space-between'
}

const Todolist: React.FC<{url:any,onClick?:Function}>= ({url,onClick})=>{
  const history = useHistory<any>();
  const [datas,setDatas]=useState<any>([]) 
  useEffect(()=>{
    if (url){
      request<{projecttypes:[],projects:[],tasks:[]}>(url, {
        method: 'GET',
      }).then((res:any)=>{
        var temps = res
        setDatas(temps)
      })
    }

    
  },[url])
  return (<>
  
    <Col>
    
      <div style={contaniner}>
      
        
      {
            datas.map((e:any,index:number)=>(
              <div key={index} onClick={()=>{
                onClick && onClick(e)
                if (e.url){
                  history.push({pathname:e.url,query:e.query||{}} as any)
                }
              }}>
                {
                  
                  <div style={{display:'flex',flexDirection:'column',width: '180px',borderRight: index<(datas.length-1)? '2px solid #F0F0F0':'none',marginRight: '30px'}}>
                    <div style={{fontSize:'25px',fontWeight:e.count?'bold':''}}>{e.count}</div>
                    <div style={{fontSize:'14px',marginTop:'10px',color:'gray'}}>{e.title}</div>
                  </div>
                }
              </div>
            ))
          }
      </div>
    </Col>
  
  </>)
}

export default Todolist;