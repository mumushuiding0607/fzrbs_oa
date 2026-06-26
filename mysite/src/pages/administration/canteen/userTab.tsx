import DepartmentTree from '@/components/DepartmentTree';
import { Affix, Button, Drawer, Layout, message } from 'antd';
import React, { useRef, useState } from 'react';
import List from './components/list';
import styles from '@/pages/information/channel/index.less';
import { MenuOutlined } from '@ant-design/icons';
import { asynchronization } from './components/list/service';

const UserTab: React.FC = () => {
  const listRef = useRef();
  const treeRef = useRef();
  const [visible, setVisible] = useState(false);
  const [departmentId, setDepartmentId] = useState(0);

  const onSelect = (id: number) => {
    listRef?.current.reload(id);
    setDepartmentId(id);
  };

  const onClose = () => {
    setVisible(false);
  };

  const synchronizationUser = async () => {
    const keys = treeRef.current.getCheckedKeys();
    if (keys.length == 0) {
      message.warn('请从左边勾选要同步的部门或人员');
      return;
    }
    const hide = message.loading('正在同步通讯录用户...', 0);
    const result = await asynchronization({ keys: keys });
    hide();
    if (result.updateCount) {
      listRef?.current.reload(departmentId);
      message.success('通讯录同步成功，更新' + result.updateCount + '个用户信息');
    }
  };
  return (
    <>
      <Affix offsetTop={0} style={{ position: 'fixed', bottom: 0, left: 0, zIndex: 100 }}>
        <Button
          type="primary"
          icon={<MenuOutlined />}
          className={styles.menu}
          onClick={() => setVisible(true)}
        />
      </Affix>
      <Drawer title="部门列表" placement="left" onClose={onClose} visible={visible} width="100vw">
        <DepartmentTree
          showLeafIcon={true}
          selectable={true}
          onSelect={onSelect}
          checkable={true}
          checkStrictly={false}
          showUser={true}
          ref={treeRef}
        />
      </Drawer>
      <Layout>
        <Affix offsetTop={50}>
          <Layout.Sider
            width={300}
            className={styles.sidebar}
            breakpoint={'lg'}
            theme="light"
            collapsedWidth={0}
            trigger={null}
          >
            <DepartmentTree
              showLeafIcon={true}
              selectable={true}
              onSelect={onSelect}
              checkable={true}
              checkStrictly={false}
              showUser={true}
              ref={treeRef}
            />
          </Layout.Sider>
        </Affix>
        <Layout.Content>
          <List ref={listRef} onSynchronization={synchronizationUser} />
        </Layout.Content>
      </Layout>
    </>
  );
};
export default UserTab;
