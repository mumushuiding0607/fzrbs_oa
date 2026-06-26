import { PageContainer } from '@ant-design/pro-components';
import { Modal, Tabs } from 'antd';
import React, { useEffect, useRef, useState } from 'react';
import { history, useModel } from 'umi';
import styles from '../style.less';
import NewsList from '../components/NewsList';
import NewsSearch from '../components/NewsList/Search';

const List: React.FC = () => {
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;
  const query = history.location.query;
  const [activeTabKey, setActiveTabKey] = useState<string>('zhongxinzu');
  const [channelId, setChannelId] = useState<number>(13376);
  const newsListRef1 = useRef();
  const newsListRef2 = useRef();
  const newsListRef3 = useRef();
  const newsListRef4 = useRef();

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
  }, []);

  const onTabChange = (key: string) => {
    switch (key) {
      case 'zhongxinzu':
        setChannelId(13376);
        break;
      case 'dajiangtang':
        setChannelId(13390);
        break;
      case 'zuanyewu':
        setChannelId(13378);
        break;
      case 'juelilun':
        setChannelId(13377);
        break;
    }
    setActiveTabKey(key);
  };

  const onSearchData = (value: any) => {
    switch (activeTabKey) {
      case 'zhongxinzu':
        newsListRef1?.current.search(value);
        break;
      case 'dajiangtang':
        newsListRef2?.current.search(value);
        break;
      case 'zuanyewu':
        newsListRef3?.current.search(value);
        break;
      case 'juelilun':
        newsListRef4?.current.search(value);
        break;
    }
  };

  const content = (
    <div className={styles.pageHeaderContent}>
      <p>
        这里是福州日报社员工学习交流园地。“中心组”主要刊发中心组学习资料；
        “大讲坛”主要刊发报社重点培训资料；“钻业务”主要刊发“两报一新”编蚕会（或采编、经营部门）业务培训，及其他新闻、经营业务学习资料；
        “嚼理论”主要刊发各支部理论学习培训有关资料。欢迎大家在这里交流学习，踊跃发言。
      </p>
    </div>
  );

  return (
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
        extra: [<NewsSearch key="search" onSearch={onSearchData} />],
      }}
      className={query.iframe ? styles.iframePage : ''}
      content={content}
    >
      <Tabs onChange={onTabChange} activeKey={activeTabKey}>
        <Tabs.TabPane tab="中心组" key="zhongxinzu" forceRender>
          <NewsList
            channelId={13376}
            ref={newsListRef1}
            showView={true}
            showGoodNum={true}
            showCommentNum={true}
            inside={true}
          />
        </Tabs.TabPane>
        <Tabs.TabPane tab="大讲坛" key="dajiangtang">
          <NewsList
            channelId={13390}
            ref={newsListRef2}
            showView={true}
            showGoodNum={true}
            showCommentNum={true}
            inside={true}
          />
        </Tabs.TabPane>
        <Tabs.TabPane tab="钻业务" key="zuanyewu">
          <NewsList
            channelId={13378}
            ref={newsListRef3}
            showView={true}
            showGoodNum={true}
            showCommentNum={true}
            inside={true}
          />
        </Tabs.TabPane>
        <Tabs.TabPane tab="嚼理论" key="juelilun">
          <NewsList
            channelId={13377}
            ref={newsListRef4}
            showView={true}
            showGoodNum={true}
            showCommentNum={true}
            inside={true}
          />
        </Tabs.TabPane>
      </Tabs>
    </PageContainer>
  );
};

export default List;
