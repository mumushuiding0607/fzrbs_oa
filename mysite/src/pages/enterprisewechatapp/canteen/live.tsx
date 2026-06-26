import { PageContainer } from '@ant-design/pro-components';
import { Modal } from 'antd';
import React, { useEffect } from 'react';
import { history, useModel } from 'umi';
import styles from '../style.less';
// import ReactPlayer from 'react-player';
// import browser from '@/utils/browser';
import MyVideo from '@/components/MyVideo';

const Live: React.FC = () => {
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;
  const query = history.location.query;
  const videoRef = React.useRef(null);

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

  return (
    <>
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
        className={query.iframe ? styles.iframePage : ''}
      >
        {/* <ReactPlayer
          url="http://218.5.3.82:45194/live/hello.m3u8"
          style={{ margin: '0 auto', padding: '50px 0' }}
          width={browser.mobile() ? '100%' : '740px'}
          height={browser.mobile() ? 'auto' : '460px'}
          playing={true}
          loop={true}
          muted={true}
          controls
        /> */}
        <div style={{ margin: '0 auto', padding: '50px 0' }}>
          <MyVideo
            options={{
              controls: true,
              autoplay: true,
              muted: true,
              loop: true,
              preload: 'auto',
              sources: [
                {
                  src: 'http://218.5.3.82:45194/live/hello.m3u8',
                  type: 'application/x-mpegURL',
                },
              ],
            }}
            onReady={(play: any) => {
              play.play();
            }}
          />
        </div>
      </PageContainer>
    </>
  );
};

export default Live;
