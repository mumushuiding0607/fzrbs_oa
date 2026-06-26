import React, { useEffect, useMemo, useRef, useState } from 'react';
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
>({ fetchOptions, debounceTimeout = 800,showupoadload,onChange,multiple,value, ...props }: any) {
  const [fetching, setFetching] = useState(false);
  const [options, setOptions] = useState<ValueType[]>([]);
  const [initialOptions,setInitialOptions]=useState<any>([])
  const fetchRef = useRef(0);
  const history = useHistory();
  const isFirstRender = useRef(true);
  const isInternalChange = useRef(false);
  const [defaultValue,setDefaultValue]=useState<any>({})
  
  useEffect(()=>{
    
    if (isFirstRender.current){
      
      if (value&&!multiple){
        isFirstRender.current = false
        // 单选且有默认值
        request<{
          data:any[]
        }>('/api/contract/getbykeyword',{
          method:'GET',
          params:{id:value}
        }).then((results:any)=> {
          var temp = results.map((e:any)=>{
            return {label:e.title,value:e.id,...e}
          })
          
          if (temp[0]){
            setDefaultValue(temp[0])
            setOptions(temp)
          }
          
          
        })
      }else if (value && multiple && typeof value === 'string' && value.length > 0) {
        // 多选模式：根据ID列表查询多个合同
        isFirstRender.current = false
        request<{
          data:any[]
        }>('/api/contract/getbykeyword',{
          method:'GET',
          params:{id:value}
        }).then((results:any)=> {
          var temp = results.map((e:any)=>{
            return {label:e.title,value:e.id,...e}
          })
          
          if (temp.length > 0){
            setDefaultValue(temp)
            setOptions(temp)
          }
        })
      }else{
        getData(value)
      }
    }
  },[])

  // 监听 value 变化，仅处理外部传入值的变化
  useEffect(() => {
    if (isInternalChange.current) {
      // 下拉框选中触发的变化，重置标记并跳过处理
      isInternalChange.current = false;
    } else {

      if (value) {
        // 单选情况：根据 ID 查询数据
        request<{
          data:any[]
        }>('/api/contract/getbykeyword',{
          method:'GET',
          params:{id:value}
        }).then((results:any)=> {
          var temp = results.map((e:any)=>{
            return {label:e.title,value:e.id,...e}
          })
          
          isInternalChange.current = true;
          setDefaultValue(temp[0])
          setOptions(temp)
          if (multiple){
            setDefaultValue(value && value.split && typeof value === 'string' ? value.split(',') : (Array.isArray(value) ? value : []))
          }else{
            setDefaultValue(temp[0])
          }
        });
      } 
    }
  }, [value]);
  
  const getData = (keyword:any)=>{
    if (typeof keyword!='string') return
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

      if (isFirstRender.current){
        isFirstRender.current = false
        setInitialOptions(newOptions)
   
      }else{
        if (initialOptions.length>0){
          // newOptions 加上 initialOptions
          setOptions([...newOptions,...initialOptions])
        }
      }
    });
  }
  const debounceFetcher = useMemo(() => {
    const loadOptions = (keyword: string) => {
      if (!keyword) return;
      
      getData(keyword)
    };

    return debounce(loadOptions, debounceTimeout);
  }, [fetchOptions, debounceTimeout]);
  
  const handleChange = (e:any)=>{
    // 标记为内部变化（下拉框选中）
    isInternalChange.current = true;
    
    if (!e){
      // 清空值时重置 defaultValue 和 options
      setDefaultValue(multiple ? [] : null);
      setOptions([]);
      onChange && onChange(null)
      return
    }
    if (multiple) {
      if (Array.isArray(e)&&e.length>0){
        e = e.map((x:any)=>{
          var index = options.findIndex((o:any)=>o.value==x.value)
          return options[index]
        })
        setDefaultValue(e)
      } else {
        setDefaultValue([])
      }
      
    }else{
      
      e = options[options.findIndex((o:any)=>o.value==e.value)]
      setDefaultValue({value:e.value,label:e.label})
    }
    onChange && onChange(e)
  }
  return (
    <Row>

      <Select
      mode={multiple?'multiple':undefined}
      allowClear
      clearable={true}
      placeholder="输入合同名称或合同编号"
      autoClearSearchValue
      labelInValue={true}
      filterOption={false}
      onSearch={debounceFetcher}
      notFoundContent={fetching ? <Spin size="small" /> : null}
      {...props}
      value={multiple?((value && value.split && typeof value === 'string' && value.length > 0 ? value.split(',').map((e:any)=>parseFloat(e)):(value && Array.isArray(value) ? value : undefined))):((defaultValue && Object.keys(defaultValue).length > 0) ? defaultValue : undefined)}
      style={{width:showupoadload?'78%':'100%',minWidth:'150px'}}
      onChange={handleChange}
      options={options}
      showSearch
      showArrow={false}
    />
    {
      showupoadload &&
      <Button type="default" style={{width:'22%'}} onClick={(e)=>{
        history.push({pathname:'/finance/contract/listc/'})
      }}>上传合同</Button>
    }
    
    </Row>
  );
}




const ContractSelect: React.FC<{value?:any,type?:Number,onChange?:any,multiple?:Boolean,showupload?:Boolean,style?:any}> = ({showupload=true,value,type,onChange,multiple,style}) =>{
  async function fetchUserList(keyword: string) {
    if (!keyword) return []
    return request<{
      data:any[]
    }>('/api/contract/getbykeyword',{
      method:'GET',
      params:{keyword,type}
    }).then((results:any)=> results.map((e:any)=>{
      e.label = e.title 
      e.value = e.id
      return e
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
      style={style}
    />
  );
};

export default ContractSelect;
