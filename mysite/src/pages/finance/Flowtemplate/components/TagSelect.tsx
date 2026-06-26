import React, { CSSProperties, useEffect, useRef, useState } from 'react';

import { Button, Divider, Form, Input, InputRef, Modal, Select, Space } from 'antd';
import { gettag } from '../service';

const row:CSSProperties = {
  display:'flex',
  flexDirection: 'row',
  width:'100%',
  padding:'0 10px',

}
const formitem:CSSProperties={
  flex:2
}
const TagSelect:React.FC<{type?:string,value?:any,style?:any,onChange?:any, onSelect?:Function,disabled?:boolean}> =  ({style,type,value,onChange,onSelect,disabled})=>{

  const [options ,setOptions] = useState<any['options']>([])
  const [data, setData] = useState<any>({});
  const [form] = Form.useForm();
  var [fkey,setFkey]=useState(0)
  const getdata = ()=>{
    gettag({}).then((res:any)=>{
      if (res) {
        res.map((e:any)=>{
          e.label = e.tagName
          if (!e.value && e.value!=0) e.value = e.id
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
    

    const indx = options.findIndex((x:any)=>x.value==e)
    setData(options[indx])
    setFkey(++fkey)
    form.setFieldsValue(options[indx])
    onChange&&onChange(e,options[indx].label)
  }
  const onValueClear = ()=>{
    onChange&&onChange('',null)
  }

  return (
    <div>

        <Select
            disabled={disabled}
            key={value}
            showSearch
            allowClear
            placeholder={type||'选择标签'}
            optionFilterProp="children"
            options={options}
            value={value}  onSelect={handleSelect}
            onClear={onValueClear}
            style={style||{width:'max(100%,150px)'}}
          />

    </div>
  )
}
export default TagSelect
