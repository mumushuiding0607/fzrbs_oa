import React, { useEffect, useState } from 'react';
import { Button, Row, Switch, TreeSelect } from 'antd';
import { request } from 'umi';



const BusinesstypeTree: React.FC<{multiple?:boolean,style?:{},value?:any,onChange?:any,defaultValue?:any,type?:any,placeholder?:any}> = ({placeholder,type,multiple=false,value,onChange,defaultValue,style}) => {
  

  const [treeData, setTreeData] = useState([]);
  const [treeCheckStrictly,setTreeCheckStrictly]=useState(false)
  var [refreshkey,setRefreshKey] = useState(0)


 
  if (value && (''+value).includes(',') ) {
    if (value.split) {
      value = value.split(',')
    }
    
  }
  
  


  const valuechange = (value:any, label:any) => {

    onChange&&onChange(label?label[0]:'')
    
   
  };




  
  const disableSomeValue = (nodes:{value:any,disabled:boolean,children:[]}[],values:any[])=>{
    return nodes.map((node:any)=>{
      node.disabled = values.includes(node.value)?true:false
      node.disableCheckbox = node.disabled
      node.children = node.children ?  disableSomeValue(node.children,values) : undefined
      return node
    })
  }
 
  useEffect(()=>{

    
    const params = { type: type};

    if (treeData.length==0){
      request<{
        data: any[];
        total?: number;
        success?: boolean;
      }>('/api/invoicing/invoicetypes', {
        method: 'GET',
        params: {
          ...params,
        },
      }).then((res:any)=>{
        if (res) {

          setRefreshKey(++refreshkey)
    
          setTreeData(res)
        }
      })
    }
    
  },[])


  return <div style={{display:'flex',flexDirection:'row',alignItems:'center'}}>
    <TreeSelect fieldNames={{label:'text'}}  showSearch={true} treeNodeFilterProp='title'  style={{...style}} allowClear value={value} defaultValue={defaultValue} treeData={treeData} onChange={valuechange}  treeCheckable={multiple} multiple={multiple} treeCheckStrictly={treeCheckStrictly}   showCheckedStrategy={TreeSelect.SHOW_ALL} placeholder={placeholder||'选择业务类型'} />

  </div>;
};

export default BusinesstypeTree;
