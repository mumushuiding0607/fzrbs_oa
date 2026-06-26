import { Tree, TreeProps } from 'antd';
import React, { useEffect, useImperativeHandle, useState } from 'react';
import { rule } from './service';

export type DepartmentTreeProps = {
  onSelect?: (value: number) => void;
  checkable?: boolean;
  selectable?: boolean;
  checkStrictly?: boolean;
  showLeafIcon: boolean;
  checkedKeys?: any;
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
  const myProps = props;
  const [treeData, setTreeData] = useState([]);
  const [selectedKeys, setSelectedKeys] = useState();
  const [expandedKeys, setExpandedKeys] = useState<any>([]);
  const [checkedKeys, setCheckedKeys] = useState<any>([]);
  const [allCheckedKeys, setAllCheckedKeys] = useState<any>([]);
  const [allLeafKeys, setAllLeafKeys] = useState<any>([]);
  const [autoExpandParent, setAutoExpandParent] = useState<boolean>(true);
  const [initTreeData] = useState<boolean>(true);
  const [firstRequest, setFirstRequest] = useState<number>(1);

  const user = props.showUser == true ? 1 : 0;
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
    const params = { tree: 1, parentid: parentId, user, local, firstRequest };
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
        setSelectedKeys([rootKey]);
        setExpandedKeys([rootKey]);
        if (props.onSelect) {
          setTimeout(() => {
            myProps.onSelect(rootKey);
          }, 200);
        }
      } else {
        setTreeData((origin) => updateTreeData(origin, treeNode.key, result.data));
        if (props.checkedKeys) {
          const uniqueChild = _.intersection(props.checkedKeys, allLeafKeys);
          setCheckedKeys(uniqueChild);
        }
      }
    }
  };

  const onSelect: TreeProps['onSelect'] = (keys: any) => {
    if (keys.length > 0) {
      setSelectedKeys(keys);
      if (props.onSelect) {
        props.onSelect(keys[0]);
      }
    }
  };

  const onExpand = (keys: any) => {
    setExpandedKeys(keys);
    setAutoExpandParent(false);
  };

  const addTreeNode = (parentId: any, nodeData: any) => {
    const loopTreeData = (list: any) =>
      list.map((node: any) => {
        if (node.key.toString() === parentId.toString()) {
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

  const updateTreeNode = (key: number, title: string) => {
    const loopTreeData = (list: any) =>
      list.map((node: any) => {
        if (node.key.toString() === key.toString()) {
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

  const deleteTreeNode = (keys: number[]) => {
    const data = [...treeData];
    const loopTreeData = (data: any, keys: any, callback: any, parentData: any) => {
      for (let i = 0; i < data.length; i++) {
        if (keys.indexOf(parseInt(data[i].key)) > -1) {
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
      let parentId = 0;
      if (props.rootId) {
        parentId = props.rootId;
      }
      const params = { tree: 1, parentid: parentId, showAll: 1, user, local };
      if (props.childrenId) {
        params.childrenId = props.childrenId.join(',');
      }
      rule(params).then((res) => {
        if (res.data) {
          setTreeData(res.data);
          if (props.checkedKeys) {
            setExpandedKeys(props.checkedKeys);
            setCheckedKeys(props.checkedKeys);
          }
        }
      });
    } else {
      onLoadData(undefined);
    }
  }, [initTreeData]);

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
export default DepartmentTree;
