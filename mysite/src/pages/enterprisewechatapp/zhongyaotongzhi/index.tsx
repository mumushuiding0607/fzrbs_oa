import { PageContainer } from '@ant-design/pro-components';
import { Modal } from 'antd';
import React, { useEffect, useRef } from 'react';
import { history, useModel } from 'umi';
import NewsList from '../components/NewsList';
import NewsSearch from '../components/NewsList/Search';
import styles from '../style.less';

const List: React.FC = () => {
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;
  const query = history.location.query;
  const channelId = 114;
  const newsListRef = useRef();

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

  const onSearchData = (value: any) => {
    newsListRef?.current.search(value);
  };

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
    >
      <NewsList channelId={channelId} ref={newsListRef} showView={true} saveView={true} />
    </PageContainer>
  );
};

export default List;
