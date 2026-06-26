import {
  FullscreenOutlined,
  HeartTwoTone,
  MinusCircleOutlined,
  PlusOutlined,
} from '@ant-design/icons';
import { PageContainer } from '@ant-design/pro-components';
import { Avatar, Button, Card, Drawer, List, message, Modal } from 'antd';
import Meta from 'antd/lib/card/Meta';
import React, { useEffect, useRef, useState } from 'react';
import { useModel, history } from 'umi';
import AppsModal from './AppsModal';
import { rule, Myapps } from './service';
import styles from './style.less';

const Apps: React.FC = () => {
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;
  const [tabs, setTabs] = useState<any[]>([]);
  const [activeTabKey, setActiveTabKey] = useState<string>('');
  const [tabContent, setTabContent] = useState<any>();
  const [visible, setVisible] = useState<boolean>(false);
  const [loadUrl, setLoadUrl] = useState<string>('');
  const [appName, setAppName] = useState<string>('应用名称');
  const [appIcon, setAppIcon] = useState<string>('');
  const [myAppIds, setMyAppIds] = useState<number[]>([]);
  const [tabsData, setTabsData] = useState<any[]>([]);
  const myAppIdsRef = useRef(myAppIds);
  const tabsDataRef = useRef(tabsData);
  const tabContentRef = useRef(tabContent);
  const activeTabKeyRef = useRef(activeTabKey);
  myAppIdsRef.current = myAppIds;
  tabsDataRef.current = tabsData;
  tabContentRef.current = tabContent;
  activeTabKeyRef.current = activeTabKey;

  const appsModalRef = useRef();

  const onTabChange = (key: string) => {
    setActiveTabKey(key);
  };

  const tabContentData = (tabData: any, index: number) => {
    let listData = tabData;
    if (index == 0) {
      listData = [...tabData, {}];
    }
    return (
      <List<any>
        grid={{
          gutter: 16,
          xs: 1,
          sm: 2,
          md: 3,
          lg: 3,
          xl: 4,
          xxl: 4,
        }}
        dataSource={listData}
        pagination={false}
        renderItem={(item) => {
          if (item && item.id) {
            return (
              <List.Item>
                <a
                  onClick={() => {
                    setAppName(item.name);
                    setAppIcon(item.image);
                    setLoadUrl(
                      '/oa' + item.path + '?title=' + item.name + '&icon=' + item.image + '&iframe=true',
                    );
                    setVisible(true);
                    return false;
                  }}
                >
                  <Card>
                    <Meta avatar={<Avatar src={item.image} />} title={item.name} />
                  </Card>
                </a>
                <a
                  style={{ position: 'absolute', right: 15, top: 5 }}
                  href={'/oa' + item.path + '?title=' + item.name + '&icon=' + item.image}
                  target="_blank"
                  rel="noreferrer"
                  title="新窗口打开应用"
                >
                  <FullscreenOutlined />
                </a>
                <a
                  style={{ position: 'absolute', right: 15, bottom: 5 }}
                  title={
                    myAppIdsRef.current.includes(item.id) ? '从我的应用删除' : '添加到我的应用'
                  }
                  onClick={() => {
                    const flagText = myAppIdsRef.current.includes(item.id) ? '删除' : '添加';
                    Modal.confirm({
                      title: flagText + '应用',
                      content: myAppIdsRef.current.includes(item.id)
                        ? '确定要从我的应用删除吗？'
                        : '确定要添加到我的应用吗？',
                      okText: '确认',
                      cancelText: '取消',
                      onOk: async () => {
                        const newMyAppIds = myAppIdsRef.current;
                        const newTabsData = tabsDataRef.current;
                        const newTabContent = tabContentRef.current;
                        const newActiveTabKey = activeTabKeyRef.current;
                        const currentTabIndex = parseInt(newActiveTabKey.substring(3));
                        const action = newMyAppIds.includes(item.id) ? 'remove' : 'add';
                        const result = await Myapps({ action, appId: item.id });
                        Modal.destroyAll();
                        if (result.errorMessage) {
                          message.warn(result.errorMessage);
                          return;
                        }
                        message.success(flagText + '成功');
                        if (action == 'add') {
                          newMyAppIds.push(item.id);
                          newTabsData[0].children.splice(0, 0, {
                            id: item.id,
                            name: item.name,
                            path: item.path,
                            image: item.image,
                            icon: item.icon,
                          });
                          newTabContent.tab0 = tabContentData(newTabsData[0].children, 0);
                          newTabContent[newActiveTabKey] = tabContentData(
                            newTabsData[currentTabIndex].children,
                            currentTabIndex,
                          );
                          setMyAppIds([...newMyAppIds]);
                          setTabsData(newTabsData);
                          setTabContent(newTabContent);
                          setActiveTabKey(newActiveTabKey);
                        } else {
                          const newAppIds = newMyAppIds.filter((id) => id != item.id);
                          const newChildren = newTabsData[0].children.filter(
                            (item1: any) => item1.id != item.id,
                          );
                          newTabsData[0].children = newChildren;
                          newTabContent.tab0 = tabContentData(newChildren, 0);
                          newTabContent[newActiveTabKey] = tabContentData(
                            newTabsData[currentTabIndex].children,
                            currentTabIndex,
                          );
                          setMyAppIds(newAppIds);
                          setTabsData(newTabsData);
                          setTabContent(newTabContent);
                          setActiveTabKey(newActiveTabKey);
                        }
                      },
                    });
                  }}
                >
                  {index == 0 ? (
                    <MinusCircleOutlined />
                  ) : myAppIdsRef.current.includes(item.id) ? (
                    <HeartTwoTone twoToneColor="#ff0000" />
                  ) : (
                    <HeartTwoTone />
                  )}
                </a>
              </List.Item>
            );
          }
          return (
            <List.Item>
              <Button
                type="dashed"
                className={styles.newButton}
                onClick={() => {
                  appsModalRef?.current.setVisible(true, tabsDataRef.current);
                }}
              >
                <PlusOutlined /> 添加应用
              </Button>
            </List.Item>
          );
        }}
      />
    );
  };

  const addMyApps = async (item: any) => {
    const newMyAppIds = myAppIdsRef.current;
    if (!newMyAppIds.includes(item.id)) {
      const result = await Myapps({ action: 'add', appId: item.id });
      if (result.errorMessage) {
        message.warn(result.errorMessage);
        return;
      }
      const newTabsData = tabsDataRef.current;
      const newTabContent = tabContentRef.current;
      const newActiveTabKey = activeTabKeyRef.current;
      const currentTabIndex = parseInt(newActiveTabKey.substring(3));
      newMyAppIds.push(item.id);
      newTabsData[0].children.splice(0, 0, {
        id: item.id,
        name: item.name,
        path: item.path,
        image: item.image,
        icon: item.icon,
      });
      newTabContent.tab0 = tabContentData(newTabsData[0].children, 0);
      newTabContent[newActiveTabKey] = tabContentData(
        newTabsData[currentTabIndex].children,
        currentTabIndex,
      );
      setMyAppIds([...newMyAppIds]);
      setTabsData(newTabsData);
      setTabContent(newTabContent);
      setActiveTabKey(newActiveTabKey);
    }
  };

  useEffect(() => {
    if (!currentUser.wxuserid || currentUser.wxuserid == '') {
      Modal.confirm({
        content: '您还未绑定企业微信号，请先绑定企业微信号',
        okText: '去绑定',
        onOk: () => {
          history.push('/account/settings/?key=binding');
        }
      });
    }
    rule({}).then((res) => {
      const tabList: object[] = [];
      let tabListContent = {};
      let defaultKey = '';
      const myIds: number[] = [];
      if (res?.data) {
        res.data.forEach((element, index) => {
          const tabId = 'tab' + index.toString();
          if (index == 0) {
            defaultKey = tabId;
            element.children.forEach((element1: any) => {
              myIds.push(element1.id);
            });
          }
          tabList.push({
            key: tabId,
            tab: element.name,
          });
          if (element.children && element.children.length > 0) {
            tabListContent[tabId] = tabContentData(element.children, index);
          } else {
            tabListContent[tabId] = tabContentData([], index);
          }
        });
        setMyAppIds(myIds);
        setTabsData(res.data);
        setTabs(tabList);
        setTabContent(tabListContent);
        setActiveTabKey(defaultKey);
      }
    });
  }, []);

  return (
    <PageContainer
      header={{
        breadcrumb: {},
      }}
    >
      <Card
        style={{ width: '100%' }}
        tabList={tabs}
        activeTabKey={activeTabKey}
        onTabChange={(key) => {
          onTabChange(key);
        }}
      >
        {activeTabKey != '' && tabContent[activeTabKey]}
      </Card>
      <Drawer
        title={
          <>
            {appIcon != '' && (
              <img
                src={appIcon}
                width={40}
                height={40}
                style={{ borderRadius: 20, marginRight: 5 }}
              />
            )}
            {appName}
          </>
        }
        width="100vw"
        visible={visible}
        onClose={() => {
          setVisible(false);
        }}
        closable={true}
        className={styles.iframePage}
      >
        {loadUrl && (
          <iframe
            src={loadUrl}
            scrolling="auto"
            frameBorder="no"
            style={{ height: '100%', width: '100%' }}
          />
        )}
      </Drawer>
      <AppsModal ref={appsModalRef} add={addMyApps} ids={myAppIdsRef.current} />
    </PageContainer>
  );
};

export default Apps;
