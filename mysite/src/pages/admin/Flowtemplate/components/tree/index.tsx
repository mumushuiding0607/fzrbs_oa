import Tree, { TreeProps } from 'antd/lib/tree';
import React, { useEffect, useImperativeHandle, useState } from 'react';
import { getQyapp } from '../list/service';
import { TableQyappItem } from '../list/data';

export type MyTreeProps = {
  onSelect?: (value: number) => void;
  checkable?: boolean;
  selectable?: boolean;
  checkStrictly?: boolean;
  showLeafIcon: boolean;
  checkedKeys?: any;
  showAll?: boolean;
};

const MyTree = React.forwardRef((props: MyTreeProps, ref) => {
  const initTreeData:TableQyappItem[] = [
    {
      title: '企业微信应用',
      key: '0',
      isLeaf: false
    },
  ];
  const [treeData, setTreeData] = useState<TableQyappItem[]>(initTreeData);
  const [selectedKeys, setSelectedKeys] = useState(['0']);
  const [expandedKeys, setExpandedKeys] = useState(['0']);
  const [checkedKeys, setCheckedKeys] = useState<any>([]);
  const [allCheckedKeys, setAllCheckedKeys] = useState<any>([]);
  const [allLeafKeys, setAllLeafKeys] = useState<any>([]);
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
    const params = { tree: 1, parentid: treeNode.key };
    const result = await getQyapp(params);
    if (result.data.length > 0) {
      result.data.forEach((item: any) => {
        const tempArray = allLeafKeys;
        tempArray.push(item.key);
        setAllLeafKeys(tempArray);
      });
      setTreeData((origin) => updateTreeData(origin, treeNode.key, result.data));
      if (props.checkedKeys && props.checkedKeys.length > 0) {
        const allKeys = [...props.checkedKeys, ...checkedKeys];
        const uniqueChild = _.intersection(allKeys, allLeafKeys);
        uniqueChild.push('0');
        setCheckedKeys(uniqueChild);
      }
    }
  };

  const onSelect: TreeProps['onSelect'] = (keys: any) => {
    if (!props.checkable) {
      if (keys.length > 0) {
        setSelectedKeys(keys);
        if (props.onSelect) {
          props.onSelect(keys[0]);
        }
      }
    }
  };

  const onExpand = (keys: any) => {
    setExpandedKeys(keys);
    setAutoExpandParent(false);
  };

  const addTreeNode = (parentId: string, nodeData: any) => {
    const loopTreeData = (list: any) =>
      list.map((node: any) => {
        if (node.key === parentId) {
          if (node.children) {
            node.children.push(nodeData);
          } else {
            delete node.isLeaf;
            node.children = [nodeData];
            const keys = expandedKeys;
            keys.push(node.key);
            setExpandedKeys(keys);
            setAutoExpandParent(false);
          }
          return node;
        }
        if (node.children) {
          return { ...node, children: loopTreeData(node.children) };
        }
        return node;
      });
    setTreeData((origin) => loopTreeData(origin));
  };

  const updateTreeNode = (key: string, title: string) => {
    const loopTreeData = (list: any) =>
      list.map((node: any) => {
        if (node.key === key) {
          node.title = title;
          return node;
        }
        if (node.children) {
          return { ...node, children: loopTreeData(node.children) };
        }
        return node;
      });
    setTreeData((origin) => loopTreeData(origin));
  };

  const deleteTreeNode = (keys: string[]) => {
    const data = [...treeData];
    const loopTreeData = (data: any, keys: any, callback: any, parentData: any) => {
      for (let i = 0; i < data.length; i++) {
        if (keys.indexOf(data[i].key) > -1) {
          callback(data[i], i, data);
          i -= 1;
          if (parentData != null && parentData.children.length == 0) {
            parentData.isLeaf = true;
          }
          continue;
        }
        if (data[i].children) {
          loopTreeData(data[i].children, keys, callback, data[i]);
        }
      }
    };
    loopTreeData(
      data,
      keys,
      (item: any, index: any, arr: any) => {
        arr.splice(index, 1);
      },
      null,
    );
    setTreeData(data);
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
    addNode: addTreeNode,
    updateNode: updateTreeNode,
    deleteNode: deleteTreeNode,
    getCheckedKeys: () => checkedKeys,
    getAllCheckedKeys: () => allCheckedKeys,
    clearChecked: () => {
      setCheckedKeys([]);
    },
    setCheckedKey: (keys: string[]) => {
      setCheckedKeys(keys);
    },
    setExpandedKey: (keys: string[]) => {
      setExpandedKeys(keys);
    },
  }));

  useEffect(() => {
    if (props.showAll) {
      const params = { tree: 1, parentid: 0, showAll: 1 };
      getRole(params).then((res) => {
        if (res.data) {
          setTreeData(res.data);
          let expandedKeys = ['0'];
          if (props.checkedKeys) {
            expandedKeys = [...expandedKeys, ...props.checkedKeys];
          }
          setExpandedKeys(expandedKeys);
          if (props.checkedKeys) {
            setCheckedKeys([...['0'], ...props.checkedKeys]);
          }
        }
      });
    }
  }, []);

  return props.showAll ? (
    <Tree
      showLine={{
        showLeafIcon: props.showLeafIcon,
      }}
      treeData={treeData}
      selectedKeys={selectedKeys}
      onSelect={onSelect}
      selectable={props.selectable}
      expandedKeys={expandedKeys}
      onExpand={onExpand}
      checkable={props.checkable}
      checkedKeys={checkedKeys}
      onCheck={onCheck}
      checkStrictly={props.checkStrictly == true ? true : false}
      autoExpandParent={true}
    />
  ) : (
    <Tree
      showLine={{
        showLeafIcon: props.showLeafIcon,
      }}
      loadData={onLoadData}
      treeData={treeData}
      selectedKeys={selectedKeys}
      onSelect={onSelect}
      selectable={props.selectable}
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

export default MyTree;
