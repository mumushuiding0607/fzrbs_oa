import React, { useEffect, useRef, useState } from 'react';
import {getdicttypes} from './service'
import { Button, Divider, Input, InputRef, Modal, Select, Space } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
const Dicttypeselect:React.FC<{type?:string,value?:any,onChange?:any, onSelect?:Function,disabled?:boolean}> =  ({type,value,onChange,onSelect,disabled})=>{

  const [options ,setOptions] = useState<any['options']>([])
  const [name, setName] = useState('');
  const inputRef = useRef<InputRef>(null);
  useEffect( ()=>{
    getdicttypes({keyword:type}).then((res)=>{

      if (res) {
        res.map((e)=>{
          e.label = e.type
          if (!e.value && e.value!=0) e.value = e.type
          return e
        })
   

      }
      console.log(res)
      setOptions(res)
    })
    
  },[])

  const handleSelect = (e:any)=>{
    
    const indx = options.findIndex((x:any)=>x.value==e)
 
    onChange(e,options[indx].label)
  }


  return (
    <div>

        <Select
            disabled={disabled}
            key={value}
            showSearch
            placeholder={type}
            optionFilterProp="children"
            options={options}
            value={value}  onSelect={handleSelect}
          />

    </div>
  )
}
export default Dicttypeselect
