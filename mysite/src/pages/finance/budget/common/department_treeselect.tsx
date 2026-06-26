import React, { useEffect, useState } from 'react';
import { Button, Row, Switch, TreeSelect } from 'antd';
import { request } from 'umi';



const DepartmentTreeSelect: React.FC<{multiple?:boolean,disabled?:boolean,style?:{},value?:any,onChange?:any,defaultValue?:any,parentid?:any,disableValues?:string,maxTagCount?:any,childrenId?:any,showTreeCheckStrictly?:boolean,placeholder?:any,fieldProps?:any}> = ({placeholder,showTreeCheckStrictly=false,multiple=true,disabled,value,onChange,defaultValue,disableValues,style,maxTagCount=8,childrenId,parentid=0,fieldProps}) => {
  

  const [treeData, setTreeData] = useState([]);
  const [treeCheckStrictly,setTreeCheckStrictly]=useState(false)
  var [refreshkey,setRefreshKey] = useState(0)



  if (defaultValue && (''+defaultValue).includes(',') ) {
    if (defaultValue.split){
      defaultValue = defaultValue.split(',')
    }
   
  }
 
  if (value && (''+value).includes(',') ) {
    if (value.split) {
      value = value.split(',')
    }
    
  }
  
  


  const valuechange = (newValue: string[]) => {

    if (newValue&&multiple){
      onChange && onChange(newValue.map((e:any)=>e.value?e.value:e))
    }else{
      onChange && onChange(newValue)
    }
   
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
    var temp = ''
    if (childrenId && Array.isArray(childrenId)){
      temp=childrenId.join(',')
    } else {
      temp = childrenId
    }
    
    const params = { tree: 1, parentid, showAll: 1,user:0,noBodyDepartment:0,local:1,childrenId:temp };

    if (treeData.length==0){
      request<{
        data: any[];
        total?: number;
        success?: boolean;
      }>('/api/common/department', {
        method: 'GET',
        params: {
          ...params,
        },
      }).then((res:any)=>{
        if (res.success) {
          if (disableValues) {
            var arr = []
            if (Array.isArray(disableValues)) {
              arr = disableValues
            } else {
              arr = disableValues.split(',')
            }

            res.data = disableSomeValue(res.data,arr)
          
          }

          setRefreshKey(++refreshkey)
    
          setTreeData(res.data)
        }
      })
    }
    
  },[])


  return <div style={{display:'flex',flexDirection:'row',alignItems:'center'}}>
    <TreeSelect  showSearch={true} treeNodeFilterProp='title' disabled={disabled} maxTagCount={maxTagCount} maxTagTextLength={50} style={{...style,width:showTreeCheckStrictly?'80%':'100%'}} allowClear value={value} defaultValue={defaultValue} treeData={treeData} onChange={valuechange}  treeCheckable={multiple} multiple={multiple} treeCheckStrictly={treeCheckStrictly}   showCheckedStrategy={TreeSelect.SHOW_ALL} placeholder={placeholder||'选择部门'} />
    {/* <Button type="default" style={{width:'22%'}} onClick={(e)=>{
        
      }}>上传合同</Button> */}
      {
        showTreeCheckStrictly &&
        <Switch
          style={{width:'70px',fontSize:'11px'}}
          checkedChildren="不关联"
          unCheckedChildren="关联"
          checked={treeCheckStrictly}
          onChange={() => setTreeCheckStrictly(!treeCheckStrictly)}
        />
      }
      
  </div>;
};

export default DepartmentTreeSelect;
