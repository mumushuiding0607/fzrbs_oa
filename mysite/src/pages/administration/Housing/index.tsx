import { PageContainer } from '@ant-design/pro-layout';
import React, { useRef, useState, useEffect } from 'react';
import { SettingOutlined,TableOutlined,DownOutlined } from '@ant-design/icons';
import HousingInfo from './components/HousingInfo';
import AppAuthManage from '@/components/AppAuthManage';
import { Dropdown, Menu, Button } from 'antd';
import type { MenuProps } from 'antd';
import { request } from 'umi';

const Leave: React.FC = () => {
  const thisAgent = 9000001;
  const [headerMenu, setHeaderMenu] = useState<object[]>([]);
  const [tabs, setTabs] = useState<object[]>([
    { tab: '租房管理', key: 'housingInfoTab', closable: false, forceRender: true },
  ]);
  const tabsRef = useRef(tabs);
  tabsRef.current = tabs;
  const handleMenuClick: MenuProps['onClick'] = e => {
      console.log(e.key);
    if (e.key === '1') {
      addTab('操作权限配置', 'housingAuthManageTab'); 
    }
  };

  const [authData, setAuthData] = useState<any>();

  const [tabKeyState, setTabKeyState] = useState<string>('housingInfoTab');

  const onTabChange = (newActiveKey: string) => {
    setTabKeyState(newActiveKey);
  };

  const addTab = (title: string, newActiveKey: string) => {
    for (const value of tabsRef.current) {
      if (value.tab == title) {
        return;
      }
    }
    const newTabs = [...tabsRef.current];

    newTabs.push({ tab: title, key: newActiveKey, closable: true });
    setTabs(newTabs);
    setTabKeyState(newActiveKey);
  };

  const removeTab = (targetKey: string) => {
    const targetIndex = tabs.findIndex((pane) => pane.key === targetKey);
    const newPanes = tabs.filter((pane) => pane.key !== targetKey);
    if (newPanes.length && targetKey === tabKeyState) {
      const { key } = newPanes[targetIndex === newPanes.length ? targetIndex - 1 : targetIndex];
      setTabKeyState(key);
    }
    setTabs(newPanes);
  };
  const modulesArr = {
    HousingInfo: {
      text: '租房管理',
    },
    HousingSetting: {
      text: '应用配置',
    },
  };

  const actionsArr = {
    HousingInfoEdit: {
      text: '租房管理——编辑',
    },
    HousingInfoDownload: {
      text: '租房管理——导出',
    },
    HousingInfoDelete: {
      text: '租房管理——删除',
    },
  };

  const tabContent = {
    housingInfoTab: <HousingInfo authData = {authData} />,
    housingAuthManageTab: <AppAuthManage agentid={thisAgent} modulesArr={modulesArr} actionsArr={actionsArr} />,
    };
 const setmenu = (
        <Menu
        onClick={handleMenuClick}  
          items={[
            {
              label: '操作权限配置',
              key: '1',
            },
          ]}
        />
  );

  
/** 权限数据接口 */
async function auth(options?: { [key: string]: any }) {
  return request<Record<string, any>>('/api/housing/auth', {
    method: 'GET',
    ...(options || {}),
  });
}
  useEffect(() => {
    auth().then((res) => {
      let _authData = res.data;
      setAuthData(_authData);
      let ret = [];
      if (_authData.modules.includes('HousingSetting')) {
        ret.push(<Dropdown.Button
              type="primary"
              key="openHousingSettingBtn"
              icon={<DownOutlined />}
              overlay={setmenu}
          >
          <SettingOutlined />
          应用配置
        </Dropdown.Button>);
      }
      setHeaderMenu([...ret]);
    }); 

  }, []);
  return (
    <PageContainer
      header={{
        breadcrumb: {},
        extra: headerMenu
      }}
      tabList={tabs}
      tabProps={{
        type: 'editable-card',
        hideAdd: true,
        onEdit: (e, action) => {
          if (action == 'remove') {
            removeTab(e);
          }
        },
        onChange: (key) => onTabChange(key),
      }}
      tabActiveKey={tabKeyState}
    >
      {tabContent[tabKeyState]}
    </PageContainer>
  );
};

export default Leave;
