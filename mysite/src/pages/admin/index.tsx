import { PageContainer } from '@ant-design/pro-layout';
import React, { useState } from 'react';
import { ProfileOutlined } from '@ant-design/icons';
import AdminList from './components/AdminList';
import AdminLoginLogList from './components/AdminLoginLogList';
import { Button, Tabs } from 'antd';

const Admin: React.FC = () => {
  const [activeKey, setActiveKey] = useState<string>('adminListTab');
  const [showLogTab, setShowLogTab] = useState<boolean>(false);

  const tabClick = (targetKey: string) => {
    setActiveKey(targetKey);
  };

  const tabEdit = (e: any, action: string) => {
    if (action == 'remove' && e == 'adminLoginLogTab') {
      setShowLogTab(false);
      setActiveKey('adminListTab');
    }
  };

  return (
    <PageContainer
      header={{
        breadcrumb: {},
        extra: [
          <Button
            type="primary"
            key="openAdminLoginLogBtn"
            onClick={() => {
              setShowLogTab(true);
              setActiveKey('adminLoginLogTab');
            }}
          >
            <ProfileOutlined />
            用户登录登出日志
          </Button>,
        ],
      }}
    >
      <Tabs
        type="editable-card"
        activeKey={activeKey}
        onTabClick={tabClick}
        onEdit={tabEdit}
        hideAdd={true}
      >
        <Tabs.TabPane tab="用户账号管理" key="adminListTab" forceRender closeIcon={<></>}>
          <AdminList />
        </Tabs.TabPane>
        {showLogTab && (
          <Tabs.TabPane tab="用户登录登出日志管理" key="adminLoginLogTab">
            <AdminLoginLogList showSearchForm={true} />
          </Tabs.TabPane>
        )}
      </Tabs>
    </PageContainer>
  );
};

export default Admin;
