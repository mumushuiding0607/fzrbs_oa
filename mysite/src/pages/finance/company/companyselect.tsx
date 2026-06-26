import React, { useEffect, useMemo, useRef, useState } from 'react';
import { Button, Divider, Input, Modal, Select, Space, Spin } from 'antd';
import type { InputRef, SelectProps } from 'antd';
import debounce from 'lodash/debounce';
import { request } from 'umi';
import { EditFilled, PlusOutlined } from '@ant-design/icons';
import Add from './add';

// 添加 isMounted ref 来跟踪组件挂载状态，防止内存泄漏
const useMountedRef = () => {
  const mountedRef = useRef(true);
  useEffect(() => {
    return () => {
      mountedRef.current = false;
    };
  }, []);
  return mountedRef;
};


export interface DebounceSelectProps
  extends Omit<SelectProps<any | any[]>, 'options' | 'children'> {
  fetchOptions: (search: string) => Promise<any[]>;
  debounceTimeout?: number;
  agentid:any;

}

function DebounceSelect({ fetchOptions, debounceTimeout = 400,onChange,sign,style,multiple,value, ...props }: any) {
  const [fetching, setFetching] = useState(false);
  const [options, setOptions] = useState<any[]>([]);
  const fetchRef = useRef(0);
  const [info,setInfo] = useState<any>({})
  const [data,setData] = useState<any>({})
  const [visible,setVisible] = useState(false)
  var [refreshkey,setRefreshkey] = useState(0)
  const [search,setSearch]=useState('')
  const isFirstRender = useRef(true);
  const isInternalChange = useRef(false);
  // 使用 mounted ref 防止内存泄漏
  const mountedRef = useMountedRef();

  useEffect(()=>{
    
    if (isFirstRender.current){
      isFirstRender.current = false
      
      getData(value)
    }
  },[])

  // 监听 value 变化，仅打印外部传入值的变化
  useEffect(() => {
    if (isInternalChange.current) {
      // 下拉框选中触发的变化，重置标记并跳过打印
      isInternalChange.current = false;
    } else {
      // 调用远程接口查询数据，查询参数为 id
      if (value) {
        getDataById(value);
      }
    }
  }, [value]);
  const dropdownRender = (menu:any)=>{
    return (<>
    
      {menu}
      <Divider style={{ margin: '8px 0' }} />
      <Space style={{ display:'flex',alignItems:'center',justifyContent:'center' }}>

        <Button type="default" icon={<PlusOutlined />} onClick={()=>{
          setVisible(true)
          
          setData({company:search})
        }}>
          添加
        </Button>
        <Button type="primary" icon={<EditFilled />} onClick={()=>{
          if (!info){
            alert('请点击下方【公司信息】进行修改')
            return
          }
          console.log('info',info)
          var temp = Array.isArray(info)?info[0]:info
          const index = options.findIndex((ee:any)=>ee.value==temp.value)
     
          if (index==-1){
            alert('请点击下方【公司信息】进行修改')
            return
          }
          setData(options[index])
          setRefreshkey(++refreshkey)
          setVisible(true)
        }}>
          编辑
        </Button>
      </Space>
    </>)
  }

  const debounceFetcher = useMemo(() => {
    const loadOptions = (value: string) => {
      if (!value) return;
      setSearch(value)
      if (value && value.length>1) {
        getData(value)
      }
      
    };

    return debounce(loadOptions, debounceTimeout);
  }, [fetchOptions, debounceTimeout]);
  const onAddChange = (data:any)=>{

    
    data.label = data.company
    data.value = data.id
    data.key=data.id
  
    var index = options.findIndex((e:any)=>e.id==data.id)
    if (index>-1) {
      options[index] = data
      setOptions(options)

    } else {
      setOptions([data,...options])
    }
  
    setVisible(false)
    handleChange(data)
  }
  const handleChange = (cv:any)=>{
    // 标记为内部变化（下拉框选中）
    isInternalChange.current = true;
    
    setInfo(cv)
    if (multiple){
      var temp = cv
      if (!Array.isArray(cv)){
        temp = [cv]
      }
      if (temp.length>0){

        temp = temp.filter((x:any)=>x&&x.value).map((x:any)=>{
          var index = options.findIndex((o:any)=>o.value==x.value)
          if (index==-1){
            setOptions([x,...options])
            return x
          }else{
            return options[index]
          }
          
        })
      }
     
      onChange && onChange(temp)
    } else {
      if (cv){
        var index = options.findIndex((o:any)=>o.value==cv.value)
        cv = options[index]
      }
      onChange && onChange(cv)
    }
    
    
  }
  const getData = (keyword:any)=>{
    
    fetchOptions(keyword).then((newOptions:any) => {
      // 检查组件是否已卸载，防止内存泄漏
      if (!mountedRef.current) return;
      
      fetchRef.current += 1;
      const fetchId = fetchRef.current;
      setOptions([]);
      setFetching(true);
      if (fetchId !== fetchRef.current) {

        return;
      }

      setOptions(newOptions.map((o:any)=>{
        o.key = o.value || o.id
        o.value = o.value || o.id
        return o
      }));

      if(newOptions.length>0){
        
        setFetching(false);
      } else {
        setSearch(keyword)
        setTimeout(() => {
          // 检查组件是否已卸载，防止内存泄漏
          if (!mountedRef.current) return;
          setFetching(false);
        }, 10000);
      }
    });
  }
  
  // 根据 id 查询数据（用于外部传入值时）
  const getDataById = (idValue:any)=>{
    // 处理 id 值，支持多选（逗号分隔的字符串或数组）
    let ids = idValue;
    if (typeof idValue === 'object' && !Array.isArray(idValue) && idValue !== null) {
      ids = idValue.value || idValue.id;
    }
    if (!ids) return;
    
    // 转换为逗号分隔的字符串
    const idParam = Array.isArray(ids) ? ids.join(',') : ids;
    
    request<{
      data:any[]
    }>('/api/company/getcompany',{
      method:'GET',
      params:{ id: idParam }
    }).then((results:any)=>{
      // 检查组件是否已卸载，防止内存泄漏
      if (!mountedRef.current) return;
      
      const newOptions = results.map((e:any)=>({
        key:e.id,
        label:e.label||e.company,
        value:e.value||e.id,
        ...e
      }));
      setOptions(newOptions);
      
      // 选中返回的所有值
      if (newOptions.length > 0) {
        // 标记为内部变化，避免触发 useEffect 循环
        isInternalChange.current = true;
        
        if (multiple) {
          // 多选情况，返回所有选项
          onChange && onChange(newOptions);
        } else {
          // 单选情况，返回第一个选项
          onChange && onChange(newOptions[0]);
        }
      }
    });
  }
  return (
    <>
      <Select
        allowClear
        autoClearSearchValue={false}
        labelInValue
        filterOption={false}
        onSearch={debounceFetcher}
        notFoundContent={fetching ? <Spin size="small" /> : null}
        {...props}
        value={multiple?((value && value.split?value.split(',').map((e:any)=>parseFloat(e)):(value||undefined))):(Number.isFinite(value)?parseFloat(value):value)}
        options={options}
        onChange={handleChange}
        onClear={handleChange}
        style={style}
        dropdownRender={dropdownRender}
      />
      <Add key={refreshkey} sign={sign} visible={visible} id={data.id||data.value} company={data.company}  onChange={onAddChange} onVisibleChange={setVisible}></Add>
    </>
  );
}





const Companyselect: React.FC<{value?:any,onChange?:any,multiple?:Boolean,url?:string,agentid?:boolean,sign?:any,placeholder?:any,style?:any,preloadInvoicingPartb?:any}> = ({style,sign=0,preloadInvoicingPartb,value,onChange,multiple=true,url,placeholder}) =>{
  async function fetchUserList(keyword: string) {
    var params:any = {keyword}
    if(preloadInvoicingPartb&&!keyword) {
      params = {preloadInvoicingPartb}
    }
    return request<{
      data:any[]
    }>(url||'/api/company/getcompany',{
      method:'GET',
      params
    }).then((results:any)=> results.map((e:any)=>{
      return {key:e.id,label:e.label||e.company,value:e.value||e.id,...e}
    }))
  }
  var m = <>
  <DebounceSelect
    mode={multiple?'multiple':undefined}
    value={value}
    showSearch
    optionFilterProp="children"
    placeholder={placeholder||"搜索合作公司"}
    fetchOptions={fetchUserList}
    onChange={onChange}
    sign={sign}
    multiple={multiple}
    style={style?style:{ width: '100%' }}
  /></>

  return (m);
};

export default Companyselect;