import React, { useEffect, useRef, useState } from 'react';
import {getdicttypes} from './service'
import { Button, Divider, Input, InputRef, Modal, Select, Space } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
const Dicttypeselect:React.FC<{type?:string,value?:any,onChange?:any, onSelect?:Function,disabled?:boolean}> =  ({type,value,onChange,onSelect,disabled})=>{

  const [options ,setOptions] = useState<any['options']>([])
  const [name, setName] = useState('');
  const inputRef = useRef<InputRef>(null);
  useEffect( ()=>{
    getdicttypes({keyword:type}).then((res: any)=>{
      // umi request 可能返回 {data: [...]} 或直接是数组
      const list = res?.data || res || [];
      if (list && Array.isArray(list)) {
        const mapped = list.map((e: any)=>{
          e.label = e.type
          if (!e.value && e.value!=0) e.value = e.type
          return e
        })
        setOptions(mapped)
      } else {
        setOptions([])
      }
      console.log('getdicttypes result:', res)
    })

  },[type])

  const handleChange = (e: any) => {
    const selectedOption = options.find((x: any) => x.value === e);
    if (selectedOption) {
      onChange?.(e, selectedOption.label);
    }
  };


  return (
    <div>

        <Select
            disabled={disabled}
            key={value}
            showSearch
            placeholder={type}
            optionFilterProp="children"
            options={options}
            value={value}
            onChange={handleChange}
          />

    </div>
  )
}
export default Dicttypeselect
