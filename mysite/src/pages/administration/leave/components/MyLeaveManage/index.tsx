import { Layout } from 'antd';
import React, { useRef, useState, useEffect } from 'react';
import DeptTree from '@/components/DepartmentTree';
import List from './components/list';
import styles from './index.less';
import { auth } from '../service';

const LeaveManage: React.FC = () => {
  const [userAuth, setUserAuth] = useState<any>({});
  const listRef = useRef();
  const treeRef = useRef();

  const onSelect = (id: number) => {
    listRef?.current.reload(id);
  };
  useEffect(() => {
    auth().then((res) => {
      console.log('auth:',res);
     setUserAuth(res.data);
    }); 
    
  },[])
  return (
    <>  
      <Layout>
        <Layout.Sider
          key="layout_leavedepttree"
          width={250}
          className={styles.sidebar}
          breakpoint={'lg'}
          theme="light"
          collapsedWidth={0}
          trigger={null}
        >
          {userAuth.departments && <DeptTree local={ true } showLeafIcon={true} selectable={true} onSelect={onSelect} ref={treeRef} childrenId={userAuth.departments} />}
        </Layout.Sider>
        <Layout key="layout_leavemanagelist">
          <Layout.Content>
          {userAuth && <List key="leavemanagelist" ref={listRef} userAuth = {userAuth} />}
          </Layout.Content>
        </Layout>
      </Layout>
    </>
  );
};

export default LeaveManage;
