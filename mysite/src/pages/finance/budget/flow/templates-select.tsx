import React, { useMemo, useRef, useState } from 'react';
import {  Button, Row, Select, Spin } from 'antd';
import type { SelectProps } from 'antd';
import debounce from 'lodash/debounce';
import { request } from 'umi';
import { useHistory } from 'react-router-dom';

export interface DebounceSelectProps<ValueType = any>
  extends Omit<SelectProps<ValueType | ValueType[]>, 'options' | 'children'> {
  fetchOptions: (search: string) => Promise<ValueType[]>;
  debounceTimeout?: number;
}

function DebounceSelect<
  ValueType extends { key?: string; label: React.ReactNode; value: string | number } = any,
>({ fetchOptions, debounceTimeout = 800, ...props }: DebounceSelectProps<ValueType>) {
  const [fetching, setFetching] = useState(false);
  const [options, setOptions] = useState<ValueType[]>([]);
  const fetchRef = useRef(0);
  const history = useHistory();

  const debounceFetcher = useMemo(() => {
    const loadOptions = (value: string) => {
      fetchRef.current += 1;
      const fetchId = fetchRef.current;
      setOptions([]);
      setFetching(true);

      fetchOptions(value).then((newOptions:any) => {
        if (fetchId !== fetchRef.current) {
          // for fetch callback order
          return;
        }

        setOptions(newOptions);
        setFetching(false);
      
      });
    };

    return debounce(loadOptions, debounceTimeout);
  }, [fetchOptions, debounceTimeout]);

  return (
    <Row>
      <Select
      labelInValue
      showSearch
      showArrow={false}
      filterOption={false}
      onSearch={debounceFetcher}
      notFoundContent={fetching ? <Spin size="small" /> : null}
      {...props}
      style={{width:'78%'}}
    
      options={options}
    />
    <Button type="default" style={{width:'22%'}} onClick={(e)=>{
       history.push({pathname:'/admin/flowtemplate/'})
     }}>添加流程</Button>
    </Row>
  );
}





const TemplatesSelect: React.FC<{value?:any,onChange?:any,multiple?:Boolean,agentId?:any}> = ({agentId,value,onChange,multiple}) =>{
  async function fetchUserList(keyword: string) {
    if(!keyword) return []
    return request<{
      data:any[]
    }>('/api/budget/gettemplate',{
      method:'GET',
      params:{keyword,agentId}
    })
  }
  return (
    <DebounceSelect
      mode={multiple?'multiple':undefined}
      value={value}
      placeholder="输入模板名称或模板id"
      fetchOptions={fetchUserList}
      onChange={onChange}
      style={{ width: '100%' }}
    />
  );
};

export default TemplatesSelect;