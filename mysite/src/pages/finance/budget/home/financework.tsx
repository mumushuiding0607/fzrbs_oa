import { AntDesignOutlined } from "@ant-design/icons";
import { Avatar, Col, Row } from "antd";
import { CSSProperties, useEffect, useState } from "react";
import { todolist } from "../service";
import { useHistory } from "umi";


const contaniner:CSSProperties = {
  background: 'white',
  width:'100%',
  height: '10vw',
  borderRadius:'1vw',
  display: 'flex',
  flexDirection: 'row',
  alignItems: 'center',
  padding: '1vw 3vw',
  boxSizing:'border-box',
  // justifyContent: 'space-between'
}

const Financework: React.FC= ()=>{
  const history = useHistory<any>();
  const [datas,setDatas]=useState<any>([]) 
  useEffect(()=>{
    todolist({}).then((res:any)=>{
      var temps = res
      setDatas(temps)
    })
    
  },[])
  return (<>
  
    <Col>
    
      <div style={contaniner}>
      
        
      {
            datas.map((e:any,index:number)=>(
              <div key={index} onClick={()=>{
                if (e.url){
                  history.push({pathname:e.url,query:e.query||{}} as any)
                }
              }}>
                <div style={{display:'flex',flexDirection:'column',width: '220px',borderRight: index<(datas.length-1)? '2px solid #F0F0F0':'none',marginRight: '30px'}}>
                  <div style={{fontSize:'25px'}}>{e.count}</div>
                  <div style={{fontSize:'14px',color:'gray'}}>{e.title}</div>
                </div>
              </div>
            ))
          }
      </div>
    </Col>
  
  </>)
}

export default Financework;