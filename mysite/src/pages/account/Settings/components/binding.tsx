import { WechatOutlined } from '@ant-design/icons';
import Icon, { CustomIconComponentProps } from '@ant-design/icons/lib/components/Icon';
import { ModalForm } from '@ant-design/pro-components';
import { List, message, Modal, Result } from 'antd';
import React, { Fragment, useState } from 'react';
import { useModel } from 'umi';
import tools from '@/utils/tools';
import { unbind } from '../service';
import { request } from 'umi';
import { ResultStatusType } from 'antd/lib/result';

const WeixinQYHSvg = () => (
  <svg
    t="1657698562473"
    className="icon"
    viewBox="0 0 1228 1024"
    version="1.1"
    xmlns="http://www.w3.org/2000/svg"
    p-id="12683"
    width="1em"
    height="1em"
  >
    <path
      d="M1119.39128889 779.40849778a174.72056889 174.72056889 0 0 0-60.47971556 24.47928889 146.88028445 146.88028445 0 0 1-66.09578666 39.936c3.35985778-22.55985778 14.54421333-43.15136 31.44021333-58.368a217.52718222 217.52718222 0 0 0 30.09649778-70.70378667 64.79985778 64.79985778 0 1 1 65.03992889 64.65649778zM1000.83370667 624.22357333a217.10392889 217.10392889 0 0 0-70.46485334-30.43214222 64.79985778 64.79985778 0 1 1 64.79985778-64.79985778 176.65820445 176.65820445 0 0 0 24.19256889 60.672 147.53109333 147.53109333 0 0 1 39.55143111 66.38478222 97.82385778 97.82385778 0 0 1-57.88785778-31.82364444h-0.23893333z m-106.75313778-228.38385778c-19.53564445-163.63178667-188.97578667-291.84-393.98286222-291.84-218.40099555 0-396.09685333 145.34428445-396.09685334 324.096a303.02435555 303.02435555 0 0 0 141.50428445 245.95228445 400.22698667 400.22698667 0 0 0 43.20028444 27.74357333l-17.56728889 70.12807112c6.33514667 2.97642667 12.47914667 6.144 18.95879112 8.88035555l88.70456888-44.35171555c12.95928889 3.35985778 26.59214222 5.51936 40.12714667 7.82336 8.64028445 1.536 17.28056889 3.11978667 26.16092445 4.17564444a456.38428445 456.38428445 0 0 0 165.60014222-9.50385778 319.72807111 319.72807111 0 0 0 13.05486222 70.75271111 547.44519111 547.44519111 0 0 1-123.648 14.44750223 537.6 537.6 0 0 1-111.07100444-12.24021334L228.07893333 892.25671111a35.52028445 35.52028445 0 0 1-38.54449778-4.03114666 35.99928889 35.99928889 0 0 1-12.43136-36.76842667l28.79943112-115.968a375.552 375.552 0 0 1-173.90250667-307.39342223c0-218.78328889 209.56728889-396.09571555 468.09656889-396.09571555 245.47100445 0 446.49585778 160.03185778 466.07928889 363.45628445a315.07228445 315.07228445 0 0 0-34.03093333-3.35985778c-12.72035555 0.48014222-25.44071111 1.67936-38.06549334 3.69550222zM744.56064 651.34364445c21.55292445-4.32014222 42.04885333-12.57585778 60.48085333-24.4792889a146.88028445 146.88028445 0 0 1 66.14357334-39.84042666 98.25621333 98.25621333 0 0 1-31.488 58.27242666c-14.15964445 21.59957333-24.33592889 45.59985778-30.09536 70.75157334a64.79985778 64.79985778 0 1 1-65.04106667-64.70428444z m116.736 155.904c21.40842667 14.35192889 45.21642667 24.72049778 70.27256889 30.72a64.79985778 64.79985778 0 1 1-64.79985778 64.79985777 175.67971555 175.67971555 0 0 0-24.00028444-60.76757333 147.40821333 147.40821333 0 0 1-39.26471112-66.57592889 97.72145778 97.72145778 0 0 1 57.79228445 32.20821333v-0.38456888z"
      fill="#008bff"
      p-id="12684"
    ></path>
  </svg>
);

const WeixinQYHIcon = (props: Partial<CustomIconComponentProps>) => (
  <Icon component={WeixinQYHSvg} {...props} />
);

const BindingView: React.FC = () => {
  const { initialState, setInitialState } = useModel('@@initialState');
  const [showModal, setShowModal] = useState<boolean>(false);
  const [showModal1, setShowModal1] = useState<boolean>(false);
  const [imageUrl, setImageUrl] = useState<string>();
  const [bindStatus, setBindStatus] = useState<ResultStatusType>('success');
  const [bindResult, setBindResult] = useState<string>('');

  const { currentUser } = initialState;

  const host = window.location.host.indexOf(':') != -1 ? window.location.host.substring(0, window.location.host.indexOf(':')) : window.location.host;
  const port = window.location.port;
  const useSSL = window.location.protocol == 'https:' ? 1 : 0;

  const loadUrl = 'https://api.fznews.com.cn/weixin/work-scan-login/index?user=' + tools.stringForAES(currentUser.username) + '&host=' + host + '&port=' + port + '&useSSL=' + useSSL;

  const Bindqywx = () => {
    if (currentUser.wxuserid == '') {
      tools.createWebSocketChannel("fzrbs_oa_bind_user_" + currentUser.id, messageReceived);
      setShowModal(true);
    } else {
      Modal.confirm({
        title: '系统提示',
        content: '确定要解除绑定吗？',
        okText: '确认',
        cancelText: '取消',
        onOk: async () => {
          const result = await unbind({ flag: 2, username: currentUser.username });
          if (result.success && !result.errorMessage) {
            message.success('解除绑定成功');
            const newUserInfo = currentUser;
            newUserInfo.wxuserid = '';
            await setInitialState((s) => ({
              ...s,
              currentUser: newUserInfo,
            }));
          }
        },
      });
    }

  };

  const Bindwx = async () => {
    if (currentUser.wxopenid == '') {
      tools.createWebSocketChannel("fzrbs_oa_bind_user_" + currentUser.id, messageReceived);
      const result = await request('/api/account/bind-weixin', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        data: {
          host: host,
          port: port,
          useSSL: useSSL
        }
      });
      if (result.data) {
        setImageUrl(result.data);
        setShowModal1(true);
      }
    } else {
      Modal.confirm({
        title: '系统提示',
        content: '确定要解除绑定吗？',
        okText: '确认',
        cancelText: '取消',
        onOk: async () => {
          const result = await unbind({ flag: 1, username: currentUser.username });
          if (result.success && !result.errorMessage) {
            message.success('解除绑定成功');
            const newUserInfo = currentUser;
            newUserInfo.wxopenid = '';
            await setInitialState((s) => ({
              ...s,
              currentUser: newUserInfo,
            }));
          }
        },
      });
    }

  }

  const messageReceived = async (text, id, channel) => {
    // console.log('messageReceived');
    var values = JSON.parse(text);
    if (values.message == 'accept') {
      const newUserInfo = currentUser;
      if (values.type == 'wx') {
        newUserInfo.wxopenid = values.openid;
        setBindStatus('success');
        setBindResult('绑定成功');
      } else if (values.type == 'qywx') {
        newUserInfo.wxuserid = values.wxuserid;
      }
      await setInitialState((s) => ({
        ...s,
        currentUser: newUserInfo,
      }));
    } else {
      setBindStatus('warning');
      setBindResult('取消绑定');
    }
  }

  const getData = () => [
    {
      title: '绑定微信号',
      description: '当前' + (currentUser.wxopenid != '' ? '已' : '未') + '绑定微信账号',
      actions: [<a key="wx" onClick={() => { Bindwx() }}>{currentUser.wxopenid != '' ? '解绑' : '绑定'}</a>],
      avatar: <WechatOutlined className="weixin" />,
    },
    {
      title: '绑定企业微信号',
      description: '当前' + (currentUser.wxuserid != '' ? '已' : '未') + '绑定企业微信号账号',
      actions: [<a key="qywx" onClick={() => { Bindqywx() }}>{currentUser.wxuserid != '' ? '解绑' : '绑定'}</a>],
      avatar: <WeixinQYHIcon className="weixinqyh" />,
    },
  ];

  return (
    <>
      <Fragment>
        <List
          itemLayout="horizontal"
          dataSource={getData()}
          renderItem={(item) => (
            <List.Item actions={item.actions}>
              <List.Item.Meta
                avatar={item.avatar}
                title={item.title}
                description={item.description}
              />
            </List.Item>
          )}
        />
      </Fragment>
      <ModalForm
        title="用户企业微信绑定"
        visible={showModal}
        onVisibleChange={setShowModal}
        submitter={false}
        modalProps={{
          destroyOnClose: true,
        }}
        onFinish={async (values) => {
          return true;
        }}
      >
        <iframe
          src={loadUrl}
          scrolling="no"
          frameBorder="no"
          style={{ height: '300px', width: '100%' }}
        />
      </ModalForm>
      <ModalForm
        title="用户微信绑定"
        visible={showModal1}
        onVisibleChange={setShowModal1}
        submitter={false}
        modalProps={{
          destroyOnClose: true,
        }}
        onFinish={async (values) => {
          return true;
        }}
      >
        <div style={{ textAlign: 'center', display: bindResult == '' ? 'block' : 'none' }}>
          <p>用微信扫码并确定</p>
          <img src={imageUrl}></img>
        </div>
        {
          bindResult != '' && <Result
            status={bindStatus}
            title={bindResult}
          />
        }

      </ModalForm>
    </>

  );
};

export default BindingView;
