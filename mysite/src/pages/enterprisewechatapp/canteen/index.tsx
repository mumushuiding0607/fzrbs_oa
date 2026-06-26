import { PageContainer } from '@ant-design/pro-components';
import { Button, Modal, Tabs } from 'antd';
import React, { useEffect, useRef, useState } from 'react';
import { getConfigData } from './service';
import { history, useModel } from 'umi';
import styles from '../style.less';
import MyList from './components/list';
import { UserOutlined } from '@ant-design/icons';
import CartDropdown from './components/CartDropdown';
import UserCenterModal from './components/UserCenterModal';
export const MyContext = React.createContext(null);

const Canteen: React.FC = () => {
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;
  const query = history.location.query;
  const [tabs, setTabs] = useState<any[]>([]);
  const [activeTabKey, setActiveTabKey] = useState<string>('');
  const [cartMenus, setCartMenus] = useState<object>({});
  const [menuCount, setMenuCount] = useState<number>(0);
  const [totalMoney, setTotalMoney] = useState<number>(0.0);
  const [typeMenus, setTypeMenus] = useState<object>({});
  const [configData, setConfigData] = useState<object>({});
  const modalRef = useRef<any>();
  const [tomorrowMenus, setTomorrowMenus] = useState<object[]>([]);

  const onTabChange = (key: string) => {
    setActiveTabKey(key);
  };

  useEffect(() => {
    if (!currentUser.wxuserid || currentUser.wxuserid == '') {
      Modal.warning({
        content: '您还未绑定微信企业号，请先绑定微信企业号',
        okButtonProps: { disabled: true },
      });
    }
    if (query.iframe) {
      const header = document.getElementsByTagName('header');
      if (header.length > 0) {
        header.forEach((element: any) => {
          element.style.display = 'none';
        });
      }
    }
    getConfigData().then((data: any) => {
      if (data.data) {
        const tempTypeMenus = {};
        data.data.types.forEach((element, index) => {
          const key = 'tab_' + element.id.toString() + (element.flag ? '_' + element.flag : '');
          if (index == 1) {
            setActiveTabKey(key);
          }
          tempTypeMenus['type' + element.id.toString()] = [];
        });
        setTabs(data.data.types);
        setTypeMenus(tempTypeMenus);
        setConfigData(data.data);
      }
    });
  }, []);

  return (
    <>
      <MyContext.Provider
        value={{
          configData,
          typeMenus,
          setTypeMenus,
          cartMenus,
          setCartMenus,
          menuCount,
          setMenuCount,
          totalMoney,
          setTotalMoney,
          tomorrowMenus,
          setTomorrowMenus,
          activeTabKey,
          currentUser,
        }}
      >
        <PageContainer
          header={{
            title: (
              <>
                {query.icon && (
                  <img
                    src={query.icon}
                    width={40}
                    height={40}
                    style={{ borderRadius: 20, marginRight: 5 }}
                  />
                )}
                {query.title ? query.title : '企业应用'}
              </>
            ),
          }}
          className={query.iframe ? styles.canteenIframePage : styles.myPage}
        >
          <Tabs
            tabPosition="top"
            onChange={onTabChange}
            activeKey={activeTabKey}
            tabBarExtraContent={
              <>
                <div style={{ float: 'left' }}>
                  <CartDropdown key="cartBadgeDropdown" />
                </div>
                <div style={{ float: 'left' }}>
                  <Button
                    key="userCenterBtn"
                    type="primary"
                    size="small"
                    onClick={() => {
                      modalRef.current.setVisible(true);
                    }}
                    style={{ marginLeft: 10, marginRight: 10 }}
                  >
                    <UserOutlined /> 用户中心
                  </Button>
                </div>
              </>
            }
          >
            {tabs.map((row) => {
              const id = row.id.toString();
              const key = 'tab_' + id + (row.flag ? '_' + row.flag : '');
              return (
                <Tabs.TabPane tab={row.title} key={key} forceRender={key == activeTabKey}>
                  <MyList flag={key} iframe={query.iframe ? true : false} />
                </Tabs.TabPane>
              );
            })}
          </Tabs>
        </PageContainer>
      </MyContext.Provider>
      <UserCenterModal ref={modalRef} />
    </>
  );
};

export default Canteen;
