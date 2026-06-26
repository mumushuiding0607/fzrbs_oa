import Tree, { TreeProps } from 'antd/lib/tree';
import React, { useImperativeHandle, useState } from 'react';
import { rule } from '@/pages/admin/components/AdminList/service';

export type MyTreeProps = {
  onSelect?: (value: number) => void;
  checkable?: boolean;
  selectable?: boolean;
  checkStrictly?: boolean;
  showLeafIcon: boolean;
  checkedKeys?: any;
};

const UserTree = React.forwardRef((props: MyTreeProps, ref) => {
  const initTreeData = [
    {
      title: '福州日报社OA管理系统',
      key: '0',
    },
  ];
  const [treeData, setTreeData] = useState(initTreeData);
  const [selectedKeys, setSelectedKeys] = useState<any>([]);
  const [checkedKeys, setCheckedKeys] = useState<any>([]);
  const [allCheckedKeys, setAllCheckedKeys] = useState<any>([]);
  const [expandedKeys, setExpandedKeys] = useState<any>(['0']);
  const [autoExpandParent, setAutoExpandParent] = useState<boolean>(true);

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
    const params = { tree: 1 };
    const result = await rule(params);
    if (result.data.length > 0) {
      setTreeData((origin) => updateTreeData(origin, treeNode.key, result.data));
      if (props.checkedKeys) {
        setCheckedKeys(props.checkedKeys);
      }
    }
  };

  const onSelect: TreeProps['onSelect'] = (keys: any) => {
    if (keys.length > 0) {
      if (props.onSelect) {
        setSelectedKeys(keys);
        props.onSelect(keys[0]);
      }
    }
  };

  const onExpand = (keys: any) => {
    setExpandedKeys(keys);
    setAutoExpandParent(false);
  };

  const onCheck: TreeProps['onCheck'] = (keys: any, e: any) => {
    let alltempKeys, tempKeys;
    if (props.checkStrictly == false) {
      alltempKeys = keys.concat(e.halfCheckedKeys);
      tempKeys = keys;
    } else {
      alltempKeys = keys.checked.concat(keys.halfChecked);
      tempKeys = keys.checked;
    }
    setAllCheckedKeys(alltempKeys);
    setCheckedKeys(tempKeys);
  };

  useImperativeHandle(ref, () => ({
    getCheckedKeys: () => checkedKeys,
    getAllCheckedKeys: () => allCheckedKeys,
    clearChecked: () => {
      setCheckedKeys([]);
    },
  }));

  return (
    <Tree
      showLine={{
        showLeafIcon: props.showLeafIcon,
      }}
      loadData={onLoadData}
      treeData={treeData}
      selectedKeys={selectedKeys}
      onSelect={onSelect}
      expandedKeys={expandedKeys}
      onExpand={onExpand}
      autoExpandParent={autoExpandParent}
      checkable={props.checkable}
      checkedKeys={checkedKeys}
      onCheck={onCheck}
      checkStrictly={props.checkStrictly == true ? true : false}
    />
  );
});

export default UserTree;
