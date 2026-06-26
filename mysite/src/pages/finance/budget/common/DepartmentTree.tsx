import { DepartmentTreeSelectProps } from '@/components/DepartmentTreeSelect';
import { Tree } from 'antd';
import type { DataNode, TreeProps } from 'antd/es/tree';
import { template } from 'lodash';
import React, { useEffect, useState } from 'react';
import { request } from 'umi';

export type DepartmentTreeProps = {
  onSelect?: any;
  checkable?: boolean;
  selectable?: boolean;
  checkStrictly?: boolean;
  showLeafIcon: boolean;
  checkedKeys?: any;
  disableValues?:string; // 被禁用的选项
  hideValues?:string; //需要隐藏的部门
  // 是否加载用户，只对从企业号接口获取数据有效
  showUser?: boolean;
  // 是否全部加载节点
  showAll?: boolean;
  // 是否从本地数据库获取数据
  local?: boolean;
  // 指定根节点id
  rootId?: number;
  // 父节点下需要显示的子节点id
  childrenId?: number[];
};

const DepartmentTree = React.forwardRef((props: DepartmentTreeProps, ref) => {
  const [treeData, setTreeData] = useState([]);
  const [autoExpandParent, setAutoExpandParent] = useState(false)
  var [refreshkey,setRefreshKey] = useState(0)
  useEffect(()=>{
    const params = { tree: 1, parentid: 0, showAll: 1,user:0,noBodyDepartment:0,local:1 };

    request<{
      data: any[];
      total?: number;
      success?: boolean;
    }>('/api/common/department', {
      method: 'GET',
      params: {
        ...params,
      },
    }).then((res)=>{
      if (res.success) {
        if (props.disableValues) {
          
          var arr = props.disableValues.split(',')
          res.data = disableSomeValue(res.data,arr)
        
        }
        if (props.hideValues) {
          var arr = props.hideValues.split(',')
          res.data = hideSomeValue(res.data,arr)
        }
        console.log('after hide:',res.data)
        setRefreshKey(++refreshkey)
        setTreeData(res.data)
        setAutoExpandParent(true)

      }
    })
  },[])
  const hideSomeValue = (nodes:{value:any,disabled:boolean,children:[]}[],values:any[])=>{
    return nodes.filter(node=>{
      if (values.includes(node.value)) {
        return false
      } else {
        node.children = node.children ? hideSomeValue(node.children,values):undefined
      }
      return true
    })
  }
  const disableSomeValue = (nodes:{value:any,disabled:boolean,children:[]}[],values:any[])=>{
    return nodes.map(node=>{
      node.disabled = values.includes(node.value)?true:false
      node.disableCheckbox = node.disabled
      node.children = node.children ?  disableSomeValue(node.children,values) : undefined
      return node
    })
  }
  const onSelect: TreeProps['onSelect'] = (selectedKeys, info) => {
    
    props.onSelect(selectedKeys, info)
  };

  const onCheck: TreeProps['onCheck'] = (checkedKeys, info) => {
    console.log('onCheck', checkedKeys, info);
  };

  return (
    <Tree
      key={refreshkey}
      checkable
      onSelect={onSelect}
      onCheck={onCheck}
      defaultExpandAll={autoExpandParent}
      treeData={treeData}
      
    />
  );
})

export default DepartmentTree;