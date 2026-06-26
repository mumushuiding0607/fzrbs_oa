import { TreeSelect } from 'antd';
import React, { useEffect, useImperativeHandle, useState } from 'react';
import { rule } from '../DepartmentTree/service';

export declare type checkedStrategyType = 'SHOW_ALL' | 'SHOW_PARENT' | 'SHOW_CHILD';

export type DepartmentTreeSelectProps = {
  checkable?: boolean;
  checkStrictly?: boolean;
  showLeafIcon: boolean;
  checkedKeys?: any;
  onChange?: (userValues: any) => void;
  treeDefaultExpandAll?: boolean;
  showCheckedStrategy?: checkedStrategyType;
  // 是否加载用户，只对从企业号接口获取数据有效
  showUser?: boolean;
  // 是否全部加载节点
  showAll?: boolean;
  // 是否显示没有用户的部门名称
  showNoBodyDepartment?: boolean;
  // 是否从本地数据库获取数据
  local?: boolean;
  // 指定根节点id
  rootId?: number;
  // 父节点下需要显示的子节点id
  childrenId?: number[];
  width?: string;
  placeholder?: string;
  allowClear?: boolean;
};

const DepartmentTreeSelect = React.forwardRef((props: DepartmentTreeSelectProps, ref) => {
  const [treeData, setTreeData] = useState([]);
  const [expandedKeys, setExpandedKeys] = useState<any>([]);
  const [checkedKeys, setCheckedKeys] = useState<any>([]);
  const [allLeafKeys, setAllLeafKeys] = useState<any>([]);
  const [initTreeData] = useState<boolean>(true);
  const [firstRequest, setFirstRequest] = useState<number>(1);
  const [newTreeData, setNewTreeData] = useState<any>([]);

  const user = props.showUser == true ? 1 : 0;
  const noBodyDepartment = props.showNoBodyDepartment == true ? 1 : 0;
  const local = props.local == true ? 1 : 0;

  const updateTreeData = (list: any, key: any, children: any) =>
    list.map((node: any) => {
      if (node.key === key) {
        return { ...node, children };
      }
      if (node.children) {
        return { ...node, children: updateTreeData(node.children, key, children) };
      }
      return node;
    });

  const onLoadData = async (treeNode: any) => {
    let parentId = 0;
    if (props.rootId) {
      parentId = props.rootId;
    }
    if (treeNode) {
      parentId = treeNode.key;
    }
    const params = { tree: 1, parentid: parentId, user, noBodyDepartment, local, firstRequest };
    if (props.childrenId) {
      params.childrenId = props.childrenId.join(',');
    }
    const result = await rule(params);
    if (firstRequest == 1) {
      setFirstRequest(0);
    }
    if (result.data && result.data.length > 0) {
      result.data.forEach((item: any) => {
        const tempArray = allLeafKeys;
        tempArray.push(item.key);
        setAllLeafKeys(tempArray);
      });
      if (parentId == 0 || (props.rootId && !treeNode)) {
        setTreeData(result.data);
        const rootKey = result.data[0].key;
        setExpandedKeys([rootKey]);
      } else {
        setTreeData((origin) => updateTreeData(origin, treeNode.key, result.data));
        if (props.checkedKeys) {
          const uniqueChild = _.intersection(props.checkedKeys, allLeafKeys);
          setCheckedKeys(uniqueChild);
        }
      }
    }
  };

  const onChange = (newValue: string[]) => {
    if (props.onChange) {
      props.onChange(newValue);
    }
    setCheckedKeys(newValue);
  };

  const onExpand = (keys: any) => {
    setExpandedKeys(keys);
  };

  const initExpandedKeys: string[] = [];
  let newData: any[] = [];

  const getChildrenKey = (nodes: any) => {
    nodes.forEach((element: any) => {
      initExpandedKeys.push(element.key);
      if (element.children && element.children.length > 0) {
        getChildrenKey(element.children);
      }
    });
  };

  const setDataParentId = (data: any, parentId: any) => {
    const tempParentId = parentId;
    for (let i = 0; i < data.length; i++) {
      data[i].parentId = tempParentId;
      if (data[i].children && data[i].children.length > 0) {
        const newParentId = tempParentId + ',' + data[i].key;
        setDataParentId(data[i].children, newParentId);
      }
    }
    return data;
  };

  const getParentsKey = (key: any, data: any) => {
    let parentKey: any;
    for (let i = 0; i < data.length; i++) {
      const node = data[i];
      if (node.children) {
        if (node.children.some((item) => item.key === key)) {
          const parentIds = node.parentId + ',' + node.key;
          parentKey = parentIds.split(',');
        } else if (getParentsKey(key, node.children)) {
          parentKey = getParentsKey(key, node.children);
        }
      }
    }
    return parentKey!;
  };

  useImperativeHandle(ref, () => ({
    getCheckedKeys: () => checkedKeys,
    setCheckedKeys: (keys: any) => {
      setCheckedKeys(keys);
    },
    getAllKeys: () => {
      let allParentKeys: any[] = [];
      if (props.showAll && checkedKeys.length > 0) {
        checkedKeys.forEach((element) => {
          const parentKeys = getParentsKey(element, newTreeData);
          allParentKeys = [...allParentKeys, ...parentKeys];
        });
        allParentKeys = [...allParentKeys, ...checkedKeys];
        allParentKeys = [...new Set(allParentKeys)];
        allParentKeys.shift();
      }
      return allParentKeys;
    },
  }));

  useEffect(() => {
    if (props.showAll) {
      let parentId = 0;
      if (props.rootId) {
        parentId = props.rootId;
      }
      const params = { tree: 1, parentid: parentId, showAll: 1, user, noBodyDepartment, local };
      if (props.childrenId) {
        params.childrenId = props.childrenId.join(',');
      }
      rule(params).then((res) => {
        if (res.data) {
          setTreeData(res.data);
          newData = setDataParentId(res.data, 0);
          setNewTreeData(newData);
          if (props.treeDefaultExpandAll) {
            res.data.forEach((element) => {
              initExpandedKeys.push(element.key);
              if (element.children && element.children.length > 0) {
                getChildrenKey(element.children);
              }
            });
            setExpandedKeys(initExpandedKeys);
          }
          if (props.checkedKeys) {
            if (!props.treeDefaultExpandAll) {
              setExpandedKeys(props.checkedKeys);
            }
            setCheckedKeys(props.checkedKeys);
          }
        }
      });
    } else {
      onLoadData(undefined);
    }
  }, [initTreeData]);

  return props.showAll ? (
    <TreeSelect
      treeLine={{
        showLeafIcon: props.showLeafIcon,
      }}
      treeData={treeData}
      treeCheckable={props.checkable}
      treeCheckStrictly={props.checkStrictly == true ? true : false}
      onChange={onChange}
      value={checkedKeys}
      treeExpandedKeys={expandedKeys}
      onTreeExpand={onExpand}
      showCheckedStrategy={props.showCheckedStrategy ? props.showCheckedStrategy : 'SHOW_CHILD'}
      style={{ width: props.width ? props.width : '100%' }}
      placeholder={props.placeholder ? props.placeholder : '请选择'}
      allowClear={props.allowClear == true ? true : false}
    />
  ) : (
    <TreeSelect
      treeLine={{
        showLeafIcon: props.showLeafIcon,
      }}
      loadData={onLoadData}
      treeData={treeData}
      treeCheckable={props.checkable}
      treeCheckStrictly={props.checkStrictly == true ? true : false}
      onChange={onChange}
      value={checkedKeys}
      treeExpandedKeys={expandedKeys}
      onTreeExpand={onExpand}
      showCheckedStrategy={props.showCheckedStrategy ? props.showCheckedStrategy : 'SHOW_CHILD'}
      style={{ width: props.width ? props.width : '100%' }}
      placeholder={props.placeholder ? props.placeholder : '请选择'}
      allowClear={props.allowClear == true ? true : false}
    />
  );
});
export default DepartmentTreeSelect;
