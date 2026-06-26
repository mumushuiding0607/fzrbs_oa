import { ClockCircleOutlined } from '@ant-design/icons';
import { Button, Descriptions, InputNumber, Timeline } from 'antd';
import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import Add from '../company/add';
import { set } from 'lodash';

const box:CSSProperties = {
  width:'100%',
  display:'flex',
  flexDirection:'row',
  alignItems:'center'
}
const valueStyle:CSSProperties = {

}
const labelStyle:CSSProperties = {
  color:'lightgray'
}

const Company: React.FC<{data:any,onChange?:Function,update?:boolean}> = ({data={},onChange,update=false}) => {

  const [visible,setVisible] = useState(false)
  var [refreshkey,setRefreshkey] = useState(0)
  const isMounted = React.useRef(true)
  useEffect(() =>{
    console.log('useEffect init')
    if (!isMounted.current){
      return
    }
    isMounted.current = false;
    if (update){
      init()
    }
    
  },[])
  const init = ()=>{
   
   
   
  }

  const onAddChange = (newval:any)=>{
   
    onChange && onChange(newval)

    
  }
  
 
  return (
    <>
      {
        data!=null&& data.company!=null && 
        <div >

            <Descriptions
                bordered
                size={'small'}
                labelStyle={{width:90}}
                title={<div style={{display:'flex',flexDirection:'row',alignItems:'center'}}><span>{data?.company}</span><span>{data?.code}</span></div>}
                extra={<Button type="primary" onClick={()=>{
                  setVisible(true)
              
                  setRefreshkey(++refreshkey)
                }}>更新</Button>}
              >

              </Descriptions>
          
            
          {
            !isMounted.current && <Add key={refreshkey} sign={1} visible={visible} id={data.id||data.value} update={update} onChange={onAddChange} onVisibleChange={setVisible}></Add>
          }
          
          </div>
      }
    </>
  );
}

export default Company;