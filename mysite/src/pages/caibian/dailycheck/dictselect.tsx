import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import { Button, Divider, Form, Input, InputRef, Modal, Select, Space } from 'antd';
import { DeleteOutlined, EditOutlined, PlusOutlined } from '@ant-design/icons';
import { getdict } from './service';
const row:CSSProperties = {
  display:'flex',
  flexDirection: 'row',
  width:'100%',
  padding:'0 10px',

}
const formitem:CSSProperties={
  flex:2
}
const Dictselect:React.FC<{typeid?:any,value?:any,onChange?:any, onSelect?:Function,disabled?:boolean,needAddItem?:boolean,showall?:boolean,userid?:string,creator?:string,multiple?:boolean,agentid?:any,style?:any}> =  ({agentid,showall=false,typeid,value,onChange,onSelect,disabled,needAddItem=true,userid,multiple=false,creator,style})=>{

  const [options ,setOptions] = useState<any['options']>([])
  const [data, setData] = useState<any>({});
  const [form] = Form.useForm();
  var [fkey,setFkey]=useState(0)
  const getdata = ()=>{
    getdict({typeid}).then((res:any)=>{
      if (res) {
        res.map((e:any)=>{
          if (!e.value && e.value!=0) e.value = e.id
          e.label=e.name
          return e
        })
      }
      
      setOptions(res)
      

    })
  }
  useEffect( ()=>{
    getdata()
  },[])

  const handleSelect = (e:any)=>{
    
   
    if (!multiple) {
      
      const indx = options.findIndex((x:any)=>x.value==e)
      setData(options[indx])
      setFkey(++fkey)
      form.setFieldsValue(options[indx])
      onChange && onChange(e,options[indx])
    }
     
  }
  const onValChange = (e:any)=>{
    
    onChange && onChange(e)
  }


  return (
    <div>

        <Select
            disabled={disabled}
            mode={multiple?'multiple':undefined}
            showSearch
            filterOption={(input, option:any) => (option?.label ?? '').includes(input)}
            options={options}
            labelInValue = {multiple?true:false}
            value={Number.isFinite(value)?parseFloat(value):(value && value.indexOf && value.indexOf(',')>-1?value.split(',').map((e:any)=>parseFloat(e)):value)}
            onSelect={handleSelect}
            allowClear
            autoClearSearchValue
            style={style}
            onChange={onValChange}
 
          />

    </div>
  )
}
export default Dictselect
