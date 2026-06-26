

import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Button, Divider, Input, Modal, Select, Space, Spin } from 'antd';
import type { InputRef, SelectProps } from 'antd';
import debounce from 'lodash/debounce';
import { request } from 'umi';


const { Option } = Select;
export interface DebounceSelectProps<ValueType = any>
  extends Omit<SelectProps<ValueType | ValueType[]>, 'options' | 'children'> {
  fetchOptions: (search: string) => Promise<ValueType[]>;
  debounceTimeout?: number;
}

function DebounceSelect<
  ValueType extends { key?: string; label: React.ReactNode; value: string | number } = any,
>({ fetchOptions, debounceTimeout = 1,value,multiple, ...props }: any) {
  const [fetching, setFetching] = useState(false);
  const [options, setOptions] = useState<ValueType[]>([]);
  const fetchRef = useRef(0);
  const isFirstRender = useRef(true);
  useEffect(()=>{
    
    if (isFirstRender.current){
      isFirstRender.current = false
      getData(value)
      
    }
  },[])
  const getData = (keyword:any)=>{
    fetchOptions(keyword).then((newOptions:any) => {
      fetchRef.current += 1;
      const fetchId = fetchRef.current;
      if (fetchId !== fetchRef.current) {
        return;
      }

      setOptions(newOptions);
      setFetching(false);
    });
  }
  const debounceFetcher = useMemo(() => {
    const loadOptions = (keyword: string) => {
 
      setOptions([]);
      setFetching(true);
  
      getData(keyword)
    };

    return debounce(loadOptions, debounceTimeout);
  }, [fetchOptions, debounceTimeout]);
  
  return (

    <Select
      allowClear
      labelInValue
      mode={multiple?'multiple':undefined}
      filterOption={false}
      onSearch={debounceFetcher}
      value={multiple?((value && value.split?value.split(',').map((e:any)=>parseFloat(e)):(value||undefined))):(Number.isFinite(value)?parseFloat(value):value)}
      notFoundContent={fetching ? <Spin size="small" /> : null}
      {...props}
      placeholder={props.placeholder?props.placeholder:'请输入主体'}
      optionLabelProp="label"
      >
        {options.map((opt:any) => (
          
           <Option key={opt.value} value={opt.value} label={opt.label}>
             {opt.customContent}
           </Option>
         ))}
      </Select>
  );
}

// Usage of DebounceSelect
interface UserValue {
  id:number
  userid: string;
  name: string;
}



const PayerSelect: React.FC<{value?:any,onChange?:any,multiple?:Boolean,url?:string,placeholder?:string,width?:string}> = ({width,value,onChange,multiple=true,url,placeholder}) =>{
  async function fetchUserList(username: string) {
  
    return request<{
      data:UserValue[]
    }>(url||'/api/qyfinance/getpayers',{
      method:'GET',
      params:{keyword:username}
    }).then((results:any)=> results.map((e:any)=>{
 
      return {value:e.id,label: e.company,customContent: (
        <div>
          {e.company}
        </div>
      ),}
    }))
  }
  var m = <>
  <DebounceSelect
    mode={multiple?'multiple':undefined}
    value={value}
    showSearch
    optionFilterProp="children"
    placeholder={placeholder||"输入主体"}
    fetchOptions={fetchUserList}
    onChange={onChange}
    multiple={multiple}
    style={{ width: width||'100%' }}
  /></>

  return (m);
};

export default PayerSelect;