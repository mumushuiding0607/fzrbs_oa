import { Avatar, Card, Modal, Row } from 'antd';
import React, { useEffect, useState } from 'react';
import { getapps } from './service';
import { useHistory } from 'umi';

const gridStyle: React.CSSProperties = {
  width: '300px',
  textAlign: 'center',
  border:'none'
};

const Finance: React.FC = () => {
  const history = useHistory() as any;
  const [datas,setDatas]=useState<any[]>([])
  useEffect(()=>{
    getapps({}).then((res:any)=>{
      if (res.errorMessage){
        Modal.error({title:res.errorMessage})
      }else{
        setDatas(res.datas||[])
      }
      
    })
  },[])
  return (
    <Card >
      {
        datas.map((e:any,index:number)=>{
          return <Card.Grid style={gridStyle}>

          <div style={{display:'flex',alignItems:'center'}} onClick={()=>{
               history.push({pathname:e.url})
             }}>
           
             <Avatar shape="square" size={64} src={e.icon}/>
             
             <div style={{fontWeight:'bold',fontSize:'20px',marginLeft:'20px'}}>{e.title}</div>
   
          </div>
         
       </Card.Grid>
        })
        
      }
      
  
    </Card>
  );
}

export default Finance