import React, { useEffect, useMemo, useRef, useState } from 'react';
import {  Button, Row, Select, Spin } from 'antd';
import type { SelectProps } from 'antd';
import debounce from 'lodash/debounce';
import { request } from 'umi';
import { getprojectbyid } from './service';

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
  const [defaultValue,setDefaultValue]=useState<any>({})
  useEffect(()=>{
    
    if (isFirstRender.current){
      isFirstRender.current = false
      
      if (value&&!multiple){
        // 单选且有默认值
        request<{
          data:any[]
        }>('/api/budget/getprojectbykeyword',{
          method:'GET',
          params:{id:value}
        }).then((results:any)=> {
          var temp = results.map((e:any)=>{
            return {label:e.title,value:e.id,...e}
          })
          setDefaultValue(temp[0])
          setOptions(temp)
        })
      }else{
        getData(value)
      }
      
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
      
    }else{
      setDefaultValue({value:e.value,label:e.label})
    }
    onChange && onChange(e)
  }
  
  return (
    <Row>
      <Select
          style={{width:'100%'}}
          mode={multiple?'multiple':undefined}
          showSearch
          maxTagCount={2}
          filterOption={(input, option:any) => {
            const label = option?.label ?? '';
            const serial = option?.serial ?? '';
            return label.includes(input) || serial.toLowerCase().includes(input.toLowerCase());
          }}
          options={options}
          onSearch={debounceFetcher}
          labelInValue = {true}
          value={multiple?((value && value.split?value.split(',').map((e:any)=>parseFloat(e)):(value||undefined))):defaultValue}

          notFoundContent={fetching ? <Spin size="small" /> : null}
          {...props}
          allowClear
          autoClearSearchValue
          onChange={handleChange}
          
    />

    
    </Row>
  );
}





const ProjectSelect: React.FC<{value?:any,type?:Number,onChange?:any,multiple?:Boolean,style?:any}> = ({value,type,onChange,multiple,style}) =>{
  async function fetchUserList(keyword: string) {
    if (!keyword) return []
    return request<{
      data:any[]
    }>('/api/budget/getprojectbykeyword',{
      method:'GET',
      params:{keyword,type}
    }).then((results:any)=> results.map((e:any)=>{
      return {label:e.title,value:e.id,...e}
    }))
  }
  return (
    <DebounceSelect
      
      mode={multiple?'multiple':undefined}
      value={value}
      placeholder="输入项目名称或项目编号"
      fetchOptions={fetchUserList}
      onChange={onChange}
      multiple={multiple}
      style={style||{ width: '100%' }}
    />
  );
};

export default ProjectSelect;


