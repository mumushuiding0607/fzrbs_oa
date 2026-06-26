import { PageContainer } from '@ant-design/pro-components';
import { Layout } from 'antd';
import React, { useRef } from 'react';
import List from './list';
// import MyTree from './tree';
import DepTree from '../depTree';


const { Content, Sider } = Layout;

const OauserIndex: React.FC = () => {
  const listRef = useRef();
  const treeRef = useRef();

  const onSelect = (item: any) => {
    listRef?.current.reload(item);
  };

  const onCreate = (parentId: number, data: any) => {
    treeRef?.current.addNode(parentId, data);
  };

  const onUpdate = (key: number, title: string) => {
    treeRef?.current.updateNode(key, title);
  };

  const onDelete = (keys: number[]) => {
    treeRef?.current.deleteNode(keys);
  };

  return (
    // <PageContainer
    //   header={{
    //     title: '',
    //     breadcrumb: {},
    //   }}
    // >
      <Layout>
        <Sider width="18%" style={{ background: '#fff', minHeight: '80vh' }}>
          {/* <MyTree showLeafIcon={true} selectable={true} onSelect={onSelect} ref={treeRef} /> */}
          <DepTree
          showLeafIcon={true}
          selectable={true}
          onSelect={onSelect}
          checkStrictly={false}
          ref={treeRef}
        />
        </Sider>
        <Content style={{ margin: '0 16px' }}>
          <List onCreate={onCreate} onUpdate={onUpdate} onDelete={onDelete} ref={listRef} />
        </Content>
      </Layout>
    // </PageContainer>
  );
};

export default OauserIndex;
