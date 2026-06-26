import { DeleteOutlined, MenuOutlined } from '@ant-design/icons';
import { PageContainer } from '@ant-design/pro-components';
import { Affix, Button, Layout, Drawer } from 'antd';
import React, { useRef, useState } from 'react';
import MyTree from './channel/components/tree';
import List from './components/list';
import RecyclebinDrawer from './components/RecyclebinDrawer';
import styles from './index.less';

const Information: React.FC = () => {
  const listRef = useRef();
  const treeRef = useRef();
  const [visible, setVisible] = useState(false);
  const drawerRef = useRef<any>();

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
      extra={[
        <Button key="recyclebin" type="primary" onClick={() => {
          drawerRef.current.setVisible(true);
        }}>
          <DeleteOutlined />
          回收站
        </Button>,
      ]}
    >
      <Affix offsetTop={0} style={{ position: 'fixed', bottom: 0, left: 0, zIndex: 100 }}>
        <Button
          type="primary"
          icon={<MenuOutlined />}
          className={styles.menu}
          onClick={() => setVisible(true)}
        />
      </Affix>
      <Drawer title="栏目列表" placement="left" onClose={onClose} visible={visible} width="100vw">
        <MyTree showLeafIcon={true} selectable={true} onSelect={onSelect} ref={treeRef} />
      </Drawer>

      <Layout>
        <Affix offsetTop={50}>
          <Layout.Sider
            width={250}
            className={styles.sidebar}
            breakpoint={'lg'}
            theme="light"
            collapsedWidth={0}
            trigger={null}
          >
            <MyTree showLeafIcon={true} selectable={true} onSelect={onSelect} ref={treeRef} />
          </Layout.Sider>
        </Affix>
        <Layout>
          <Layout.Content>
            <List ref={listRef} />
          </Layout.Content>
        </Layout>
      </Layout>
      <RecyclebinDrawer ref={drawerRef} />
    </PageContainer>
  );
};

export default Information;
