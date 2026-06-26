import { PageContainer } from '@ant-design/pro-layout';
import React, { useRef, useState, useEffect } from 'react';
import { SettingOutlined,TableOutlined,DownOutlined } from '@ant-design/icons';
import MyLeaveManage from './components/MyLeaveManage';
import LeaveFlowManage from './components/LeaveFlowManage';
import AppAuthManage from '@/components/AppAuthManage';
import { Dropdown, Menu, Button } from 'antd';
import type { MenuProps } from 'antd';
import { auth } from './components/service';

const Leave: React.FC = () => {
  const thisAgent = 1000037;
  const [headerMenu, setHeaderMenu] = useState<object[]>([]);
  const [tabs, setTabs] = useState<object[]>([
    { tab: '信息管理', key: 'myLeaveManageTab', closable: false, forceRender: true },
  ]);
  const tabsRef = useRef(tabs);
  tabsRef.current = tabs;
  const handleMenuClick: MenuProps['onClick'] = e => {
      console.log(e.key);
    if (e.key === '1') {
      addTab('操作权限配置', 'leaveAuthManageTab');
    } else if (e.key === '2') {
      addTab('请销假流程配置', 'leaveFlowManageTab');        
    }
  };


  const [tabKeyState, setTabKeyState] = useState<string>('myLeaveManageTab');

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
    MyLeaveApproval: {
      text: '我的审批',
    },
    MyLeaveNotify: {
      text: '我的抄送',
    },
    MyLeaveManage: {
      text: '信息管理',
    },
    MyLeaveSetting: {
      text: '应用配置',
    },
  };

  const actionsArr = {
    MyLeaveManageModify: {
      text: '信息管理——修改',
    },
    MyLeaveManageCancel: {
      text: '信息管理——撤销',
    },
    MyLeaveManageReset: {
      text: '信息管理——重置流程',
    },
    MyLeaveManageExport: {
      text: '信息管理——数据导出',
    },
  };

  const tabContent = {
    myLeaveManageTab: <MyLeaveManage />,
    leaveFlowManageTab: <LeaveFlowManage />,
    leaveAuthManageTab: <AppAuthManage agentid={thisAgent} modulesArr={modulesArr} actionsArr={actionsArr} />,
    };
 const setmenu = (
        <Menu
        onClick={handleMenuClick}  
          items={[
            {
              label: '操作权限配置',
              key: '1',
            },
            {
              label: '请销假流程配置',
              key: '2',
            },
          ]}
        />
  );
  useEffect(() => {
    auth().then((res) => {
      let authData = res.data;
      let ret = [];
      if (authData.modules.includes('MyLeaveSetting')) {
        ret.push(<Dropdown.Button
              type="primary"
              key="openMyLeaveSettingBtn"
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
