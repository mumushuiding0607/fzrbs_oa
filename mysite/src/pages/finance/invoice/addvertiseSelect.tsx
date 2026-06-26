import React, { useEffect, useMemo, useRef, useState } from 'react';
import {  Button, Row, Select, Spin } from 'antd';
import type { SelectProps } from 'antd';
import debounce from 'lodash/debounce';
import { request } from 'umi';

export interface DebounceSelectProps<ValueType = any>
  extends Omit<SelectProps<ValueType | ValueType[]>, 'options' | 'children'> {
  fetchOptions: (search: string) => Promise<ValueType[]>;
  debounceTimeout?: number;
}

function DebounceSelect<
  ValueType extends { key?: string; label: React.ReactNode; value: string | number } = any,
>({ fetchOptions, debounceTimeout = 800,onChange,multiple,value, ...props }: any) {
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
    fetchRef.current += 1;
    const fetchId = fetchRef.current;
    setFetching(true);
    fetchOptions(keyword).then((newOptions:any) => {
      if (fetchId !== fetchRef.current) {
        // for fetch callback order
        return;
      }

      setOptions(newOptions);
      setFetching(false);
    });
  }
  const debounceFetcher = useMemo(() => {
    const loadOptions = (keyword: string) => {
      getData(keyword)
    };

    return debounce(loadOptions, debounceTimeout);
  }, [fetchOptions, debounceTimeout]);
  const handleChange = (e:any)=>{
    
    if (multiple) {
      if (Array.isArray(e)&&e.length>0){
        e = e.map((x:any)=>{
          var index = options.findIndex((o:any)=>o.value==x.value)
          return options[index]
        })
      }
      
    }
    onChange && onChange(e)
  }
  return (
    <Row>
      <Select
      labelInValue
      showSearch
      showArrow={false}
      filterOption={false}
      onSearch={debounceFetcher}
      value={multiple?((value && value.split?value.split(',').map((e:any)=>parseFloat(e)):(value||undefined))):(Number.isFinite(value)?parseFloat(value):value)}
      notFoundContent={fetching ? <Spin size="small" /> : null}
      {...props}
      style={{width:'100%'}}
      onChange={handleChange}
      options={options}
    />
  
    
    </Row>
  );
}





const AddvertiseSelect: React.FC<{value?:any,onChange?:any,multiple?:Boolean,showupload?:Boolean,style?:any}> = ({showupload=true,value,onChange,multiple,style}) =>{
  async function fetchUserList(keyword: string) {
    if (!keyword) return []
    return request<{
      data:any[]
    }>('/api/invoicing/getadvertise',{
      method:'GET',
      params:{keyword}
    }).then((results:any)=> results.map((e:any)=>{
      return {label:e.title,value:e.id,...e}
    }))
  }
  return (
    <DebounceSelect
      mode={multiple?'multiple':undefined}
      value={value}
      placeholder="输入合同名称或合同编号"
      fetchOptions={fetchUserList}
      onChange={onChange}
      showupoadload={showupload}
      multiple={multiple}
      style={style||{ width: '100%' }}
    />
  );
};

export default AddvertiseSelect;