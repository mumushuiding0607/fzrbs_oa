import React, { CSSProperties, useEffect, useRef, useState } from 'react';

import { Button, Divider, Form, Input, InputRef, Modal, Select, Space } from 'antd';
import { getappoptions, getapps } from '../role/service';


const row:CSSProperties = {
  display:'flex',
  flexDirection: 'row',
  width:'100%',
  padding:'0 10px',

}
const formitem:CSSProperties={
  flex:2
}
const AppSelect:React.FC<{needPower?:boolean,type?:string,value?:any,onChange?:any, onSelect?:Function,disabled?:boolean,needAddItem?:boolean,showall?:boolean,userid?:string,creator?:string,multiple?:boolean,agentid?:any,initialValue?:any,returnLabel?:boolean,style?:any}> =  ({returnLabel=false,style,needPower=true,agentid,showall=false,type,value,onChange,onSelect,disabled,needAddItem=true,userid,multiple=false,creator,initialValue})=>{

  const [options ,setOptions] = useState<any['options']>([])
  const [data, setData] = useState<any>({id:0});
  const [form] = Form.useForm();
  var [fkey,setFkey]=useState(0)
  const getdata = ()=>{
    getappoptions().then((res:any)=>{


      setOptions(res)
      

    })
  }
  useEffect( ()=>{
    getdata()
  },[])

  const handleSelect = (e:any)=>{
    
    
    
    if (!multiple) {
      
      const indx = options.findIndex((x:any)=>x.value==e.value)
      setData(options[indx])
      setFkey(++fkey)
      form.setFieldsValue(options[indx])
      onChange && onChange(e,options[indx])
    }
     
  }
  const onValChange = (e:any)=>{
    
    if (multiple){
      e = (e||[]).filter((x:any)=>x.value||x.value==0)
    }
    onChange && onChange(e)
  }

 

  return (
    <div>

        <Select
            disabled={disabled}
            style={style||{width:'100%'}}
            mode={multiple?'multiple':undefined}
            showSearch
            maxTagCount={1}
            placeholder={'选择应用'}
            filterOption={(input, option:any) => (option?.label ?? '').includes(input)}
            options={options}
            labelInValue = {multiple?true:false}
            value={multiple?((value && value.split?value.split(',').map((e:any)=>parseFloat(e)):(value||undefined))):(Number.isFinite(value)?parseFloat(value):value)}
            onSelect={handleSelect}

            allowClear
            autoClearSearchValue
            onChange={onValChange}
 
          />

    </div>
  )
}
export default AppSelect
