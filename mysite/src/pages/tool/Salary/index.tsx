import React, { useRef, useState, useEffect } from 'react';
import { PageContainer } from '@ant-design/pro-layout';
import { SettingOutlined,DownOutlined } from '@ant-design/icons';
import { Dropdown, Menu } from 'antd';
import type { MenuProps } from 'antd';
import { auth } from './components/salary/service';
import SalaryIndex from './components/salary/index';
import SalaryBonusIndex from './components/salaryBonus/index';
import PersonalTotalIndex from './components/personalTotal/index';
import AppAuthManage from '@/components/AppAuthManage';
import ErrorTable from './components/salary/errorTable';
  
const Salary: React.FC = () => {
  const thisAgent = 1000022;
  const [headerMenu, setHeaderMenu] = useState<object[]>([]);
  const [tabs, setTabs] = useState<object[]>([
    { tab: '工资管理', key: 'salaryTab', closable: false, forceRender: true },
  ]);
  const tabsRef = useRef(tabs);
  tabsRef.current = tabs;
  const handleMenuClick: MenuProps['onClick'] = e => {
      console.log(e.key);
    if (e.key === '1') {
      addTab('操作权限配置', 'salaryAuthManageTab',true,true);
    } else if (e.key === '2') {
      addTab('错误信息', 'errorTableTab',true,true);        
    }
  };


  const [tabKeyState, setTabKeyState] = useState<string>('salaryTab');

  const onTabChange = (newActiveKey: string) => {
    setTabKeyState(newActiveKey);
  };

  const addTab = (title: string, newActiveKey: string,closable: boolean,show: boolean) => {
    for (const value of tabsRef.current) {
      if (value.tab == title) {
        return;
      }
    }
    const newTabs = [...tabsRef.current];

    newTabs.push({ tab: title, key: newActiveKey, closable: closable });
    setTabs(newTabs);
    if(show)setTabKeyState(newActiveKey);
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
    SalaryBonus: {
      text: '奖金管理',
    },
    SalarySetting: {
      text: '应用配置',
    },
    PersonalTotal: {
      text: '个人年度汇总',
    },
  };

  const actionsArr = {
    SalaryImport: {
      text: '工资管理——导入',
    },
    SalaryExport: {
      text: '工资管理——导出',
    },
    SalaryEdit: {
      text: '工资管理——编辑',
    },
    SalaryDelete: {
      text: '工资管理——删除',
    },
    SalarySign: {
      text: '工资管理——签发',
    },
    BonusImport: {
      text: '奖金管理——导入',
    },
    BonusExport: {
      text: '奖金管理——导出',
    },
    BonusEdit: {
      text: '奖金管理——编辑',
    },
    BonusDelete: {
      text: '奖金管理——删除',
    },
    BonusSign: {
      text: '奖金管理——签发',
    },
  };

  const tabContent = {
    salaryTab: <SalaryIndex />,
    salaryBonusTab: <SalaryBonusIndex />,
    personalTotalTab: <PersonalTotalIndex />,
    salaryAuthManageTab: <AppAuthManage agentid={thisAgent} modulesArr={modulesArr} actionsArr={actionsArr} />,
    errorTableTab: <ErrorTable />,
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
              label: '错误信息',
              key: '2',
            },
          ]}
        />
  );
  useEffect(() => {
    auth().then((res) => {
      let authData = res.data;
      let ret = [];
      console.log(authData);
      if (authData.modules.includes('SalarySetting')) {
        ret.push(<Dropdown.Button
              type="primary"
              key="openSalarySettingBtn"
              icon={<DownOutlined />}
              overlay={setmenu}
          >
          <SettingOutlined />
          应用配置
        </Dropdown.Button>);
      }
      if (authData.modules.includes('SalaryBonus')) {
        addTab('奖金管理', 'salaryBonusTab',false,false);
      }
      if (authData.modules.includes('PersonalTotal')) {
        addTab('个人年度汇总', 'personalTotalTab',false,false);
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

export default Salary;
