import { MenuOutlined } from '@ant-design/icons';
import { PageContainer } from '@ant-design/pro-components';
import { Affix, Button, Drawer, Layout } from 'antd';
import React, { useRef, useState } from 'react';
import List from './components/list';
import MyTree from './components/tree';
import styles from './index.less';

const { Content, Sider } = Layout;

const Channel: React.FC = () => {
  const listRef = useRef();
  const treeRef = useRef();
  const [visible, setVisible] = useState(false);

  const onSelect = (id: number) => {
    listRef?.current.reload(id);
  };

  const onCreate = (parentId: string, data: any) => {
    treeRef?.current.addNode(parentId, data);
  };

  const onUpdate = (key: string, title: string) => {
    treeRef?.current.updateNode(key, title);
  };

  const onDelete = (keys: string[]) => {
    treeRef?.current.deleteNode(keys);
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
      <Drawer title="栏目列表" placement="left" onClose={onClose} visible={visible} width="100vw">
        <MyTree showLeafIcon={true} selectable={true} onSelect={onSelect} ref={treeRef} />
      </Drawer>
      <Layout>
        <Affix offsetTop={50}>
          <Sider
            width={250}
            className={styles.sidebar}
            breakpoint={'lg'}
            theme="light"
            collapsedWidth={0}
            trigger={null}
          >
            <MyTree showLeafIcon={true} selectable={true} onSelect={onSelect} ref={treeRef} />
          </Sider>
        </Affix>
        <Content>
          <List onCreate={onCreate} onUpdate={onUpdate} onDelete={onDelete} ref={listRef} />
        </Content>
      </Layout>
    </PageContainer>
  );
};

export default Channel;
