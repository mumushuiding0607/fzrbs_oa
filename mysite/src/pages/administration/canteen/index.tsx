import { PageContainer } from '@ant-design/pro-components';
import { Tabs } from 'antd';
import React, { useEffect, useState } from 'react';
import AccountChangeTab from './AccountChangeTab';
import ExcelRechargeTab from './excelRechargeTab';
import MenuTab from './MenuTab';
import OrderTab from './OrderTab';
import RechargeLogTab from './rechargeLogTab';
import UserTab from './userTab';
import { accessTab } from './components/list/service';
import MenuRankingTab from './MenuRankingTab';
import SettingTab from './SettingTab';

const Canteen: React.FC = () => {
  const [tabs, setTabs] = useState<string[]>([]);
  useEffect(() => {
    accessTab().then((res) => {
      setTabs(res.data);
    });
  }, []);

  return (
    <PageContainer
      header={{
        breadcrumb: {},
      }}
    >
      <Tabs type="card">
        {tabs.includes('canteen-account') && (
          <Tabs.TabPane tab="食堂账号管理" key="canteen-account" forceRender>
            <UserTab />
          </Tabs.TabPane>
        )}
        {tabs.includes('canteen-menu') && (
          <Tabs.TabPane tab="食堂菜单管理" key="canteen-menu">
            <MenuTab />
          </Tabs.TabPane>
        )}
        {tabs.includes('canteen-order') && (
          <Tabs.TabPane tab="食堂订单管理" key="canteen-order">
            <OrderTab />
          </Tabs.TabPane>
        )}
        {tabs.includes('canteen-recharge-log') && (
          <Tabs.TabPane tab="充值日志管理" key="canteen-recharge-log">
            <RechargeLogTab />
          </Tabs.TabPane>
        )}
        {tabs.includes('canteen-account-change') && (
          <Tabs.TabPane tab="每月账号余额变动管理" key="canteen-account-change">
            <AccountChangeTab />
          </Tabs.TabPane>
        )}
        {tabs.includes('canteen-excel-recharge') && (
          <Tabs.TabPane tab="Excel导入充值管理" key="canteen-excel-recharge">
            <ExcelRechargeTab />
          </Tabs.TabPane>
        )}
        {tabs.includes('canteen-menu-ranking') && (
          <Tabs.TabPane tab="菜品排行管理" key="canteen-menu-ranking">
            <MenuRankingTab />
          </Tabs.TabPane>
        )}
        {tabs.includes('canteen-setting') && (
          <Tabs.TabPane tab="食堂相关设置" key="canteen-setting">
            <SettingTab />
          </Tabs.TabPane>
        )}
      </Tabs>
    </PageContainer>
  );
};

export default Canteen;
