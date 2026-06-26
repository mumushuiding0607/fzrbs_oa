import React, { useState, useEffect } from 'react';
// import OauserIndex from './components/oauser';
import { Tabs } from 'antd';
import { PageContainer } from '@ant-design/pro-components';
import OauserIndex from './components/oauser';
import ErrorTable from './components/oauser/errorTable';
import DepList from './components/oauserDepartment';

import { accessTab } from './components/oauser/service';

const Index: React.FC = () => {
  const [tabs, setTabs] = useState<string[]>([]);
  useEffect(() => {
    accessTab({ tabId: 137 }).then((res) => {
      console.log(res.data)
      setTabs(res.data);
    });
  }, []);

  return (
    <PageContainer
      fixedHeader
      header={{ breadcrumb: {} }}
    >
      <Tabs type="line">
        {tabs.includes('oauser-list/') && (
          <Tabs.TabPane tab="职员列表" key="oauser-list">
            <OauserIndex />
          </Tabs.TabPane>
        )}
        {tabs.includes('oauser-department/') && (
          <Tabs.TabPane tab="部门列表" key="oauser-department">
            <DepList />
          </Tabs.TabPane>
        )}
        {tabs.includes('oauser-error/') && (
          <Tabs.TabPane tab="错误信息" key="oauser-error">
            <ErrorTable />
          </Tabs.TabPane>
        )}
      </Tabs>
    </PageContainer>
  );
};

export default Index;
