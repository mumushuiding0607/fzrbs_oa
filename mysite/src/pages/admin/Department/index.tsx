import { MenuOutlined } from '@ant-design/icons';
import { PageContainer } from '@ant-design/pro-components';
import { Affix, Button, Drawer, Layout } from 'antd';
import React, { useRef, useState } from 'react';
import List from './list';
import styles from '../../information/index.less';
import DepartmentTree from '@/components/DepartmentTree';

const Department: React.FC = () => {
  const listRef = useRef();
  const treeRef = useRef();
  const [visible, setVisible] = useState(false);

  const onSelect = (id: number) => {
    listRef?.current.reload(id);
  };

  const onClose = () => {
    setVisible(false);
  };

  return (
    <PageContainer
      header={{
        breadcrumb: {},
      }}
    >
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
          checkable={false}
          checkStrictly={false}
          showUser={false}
          local={true}
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
              checkable={false}
              checkStrictly={false}
              showUser={false}
              local={true}
              ref={treeRef}
            />
          </Layout.Sider>
        </Affix>
        <Layout>
          <Layout.Content>
            <List ref={listRef} />
          </Layout.Content>
        </Layout>
      </Layout>
    </PageContainer>
  );
};

export default Department;
