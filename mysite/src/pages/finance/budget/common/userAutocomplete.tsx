

import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Button, Divider, Input, Modal, Select, Space, Spin } from 'antd';
import type { InputRef, SelectProps } from 'antd';
import debounce from 'lodash/debounce';
import { request } from 'umi';


const { Option } = Select;
export interface DebounceSelectProps<ValueType = any>
  extends Omit<SelectProps<ValueType | ValueType[]>, 'options' | 'children'> {
  fetchOptions: (search: string,ids:any) => Promise<ValueType[]>;
  debounceTimeout?: number;
}

function DebounceSelect<
  ValueType extends { key?: string;url?:any; label: React.ReactNode; value: string | number } = any,
>({ fetchOptions,url, debounceTimeout = 1,value,multiple,onChange, ...props }: any) {
  const [fetching, setFetching] = useState(false);
  const [options, setOptions] = useState<ValueType[]>([]);
  const fetchRef = useRef(0);
  const isFirstRender = useRef(true);
  useEffect(()=>{
    
    if (value&&!Array.isArray(value)&&isFirstRender.current){
        isFirstRender.current = false;
        fetchOptions('',value).then((newOptions:any) => {
          
      fetchRef.current += 1;
      const fetchId = fetchRef.current;
      if (fetchId !== fetchRef.current) {
        return;
      }

      setOptions(newOptions);
      setFetching(false);
    });
      }
  },[value])
  
  const getData = (keyword:any)=>{
    fetchOptions(keyword,0).then((newOptions:any) => {
      fetchRef.current += 1;
      const fetchId = fetchRef.current;
      if (fetchId !== fetchRef.current) {
        return;
      }


      setOptions(newOptions);
      setFetching(false);
    });
  }
  function isPureNumber(e: any): boolean {
  // 如果已经是 number 类型，且不是 NaN 或 Infinity
  if (typeof e === 'number') {
    return Number.isFinite(e);
  }

  // 如果是字符串，尝试解析并比对
  if (typeof e === 'string') {
    const trimmed = e.trim();
    if (trimmed === '') return false;
    const num = parseFloat(trimmed);
    return (
      !isNaN(num) &&
      Number.isFinite(num) &&
      trimmed === num.toString() // 关键：确保字符串形式完全匹配数字表示
    );
  }

  // 其他类型（boolean, object, array 等）都不是纯数字
  return false;
}
  const debounceFetcher = useMemo(() => {
    const loadOptions = (keyword: string) => {
 
    
      setFetching(true);
      if(!keyword) return
      getData(keyword)
    };

    return debounce(loadOptions, debounceTimeout);
  }, [fetchOptions, debounceTimeout]);
  
  // 处理选择变更，返回完整对象
  const handleChange = (selectedValue: any) => {
    if (!selectedValue) {
      onChange && onChange(null);
      return;
    }
    
    if (multiple) {
      // 多选情况：查找每个选中值的完整对象
      const fullValues = selectedValue.map((sv: any) => {
        const fullOption = options.find((opt: any) => opt.value === sv.value);
        return fullOption || sv;
      });
      onChange && onChange(fullValues);
    } else {
      // 单选情况：查找完整对象
      const fullOption = options.find((opt: any) => opt.value === selectedValue.value);
      onChange && onChange(fullOption || selectedValue);
    }
  };
  
  return (

    <Select
      allowClear
      labelInValue
      filterOption={false}
      onSearch={debounceFetcher}
      onClear={()=>{
        console.log('clear')
        onChange && onChange({})
      }}
      onSelect={(v:any)=>{
  
      }}
      onChange={handleChange}
      value={
    multiple
      ? ((value && value.split?value.split(',').map((e:any)=>{
        return isPureNumber(e)?parseFloat(e):e
      }):(value||undefined)))
      : (() => {
          
          if(!value) return undefined
          const opt = (options||[]).find((opt: any) => opt.value === (value.value||value));
          return opt 
        })()
  }
      notFoundContent={fetching ? <Spin size="small" /> : null}
      {...props}
      placeholder={props.placeholder?props.placeholder:'请输入用户姓名'}
      optionLabelProp="label"
      >
        {(options||[]).map((opt:any) => (
          
            <Option
      key={opt.value}
      value={opt.value}
      label={opt.label} // 控制回显文本
    >

      {opt.customContent ? (
        opt.customContent
      ) : (
        <span>{opt.label}</span>
      )}
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



const UserAutocomplete: React.FC<{value?:any,valueKey?:any,onChange?:any,multiple?:Boolean,url?:string,placeholder?:string,width?:string,style?:any}> = ({valueKey='userid',style,width,value,onChange,multiple=true,url,placeholder}) =>{
  async function fetchUserList(username:any,ids:any) {
 
 
    if (!username||!/^[\u4e00-\u9fa5]+$/.test(username)) {
      if(!ids) {
        return
      } else{
        // 判断ids是否为object
        if(ids.value){
          ids = ids.value
        }
      }

    }
    
 
    return request<{
      data:UserValue[]
    }>(url||'/api/budget/getusers',{
      method:'GET',
      params:{keyword:username,ids}
    }).then((results:any)=> results.map((e:any)=>{
      // label:e.name,
      return {value:e[valueKey],userids:e.value,departmentname:e.departmentname,departmentid:e.departmentid,label: e.name,customContent: (
        <div>
          {e.name}@{e.mobile}
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
    placeholder={placeholder||"输入用户名"}
    fetchOptions={fetchUserList}
    onChange={onChange}
    multiple={multiple}
    style={{ width: width||'100%' }}
    url
  /></>

  return (m);
};

export default UserAutocomplete;