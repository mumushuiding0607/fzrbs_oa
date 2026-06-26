import Footer from '@/components/Footer';
import { login, getFakeCaptcha } from './service';
import { LockOutlined, UserOutlined, MobileOutlined } from '@ant-design/icons';
import {
  LoginForm,
  ProFormText,
  ProFormCaptcha,
  ProFormInstance,
} from '@ant-design/pro-components';
import { Alert, message, Tabs } from 'antd';
import React, { useRef, useState } from 'react';
import { FormattedMessage, history, request, useIntl, useModel } from 'umi';
import styles from './index.less';
import CaptchaInput from '@/components/CaptchaInput';
import token from '@/utils/token';
import tools from '@/utils/tools';
import CryptoJS from 'crypto-js';

const LoginMessage: React.FC<{
  content: string;
}> = ({ content }) => (
  <Alert
    style={{
      marginBottom: 24,
    }}
    message={content}
    type="error"
    showIcon
  />
);

const Login: React.FC = () => {
  const [userLoginState, setUserLoginState] = useState<API.LoginResult>({});
  const [type, setType] = useState<string>('account');
  const captchaInputRef = useRef();
  const formRef = useRef<ProFormInstance>();
  const { initialState, setInitialState } = useModel('@@initialState');
  const [imageUrl, setImageUrl] = useState<string>('');
  const [scanResult, setScanResult] = useState<string>('请使用绑定的微信号或企业号扫描登录');

  const intl = useIntl();

  const fetchUserInfo = async () => {
    const userInfo = await initialState?.fetchUserInfo?.();
    if (userInfo) {
      const menusAndRoutes = await initialState?.fetchUserMenusRoutes?.();
      const menuData = menusAndRoutes.data;
      let routes: string[] = [];
      if (menusAndRoutes.routes) {
        routes = menusAndRoutes.routes;
      }
      await setInitialState((s) => ({
        ...s,
        currentUser: userInfo,
        menuData,
        routes,
      }));
    }
  };

  const handleSubmit = async (values: API.LoginParams) => {
    try {
      // 登录
      console.log('登录。。。。。。。。。')
      const msg = await login({ ...values, type });
      if (msg.status === 'ok') {
        const defaultLoginSuccessMessage = intl.formatMessage({
          id: 'pages.login.success',
          defaultMessage: '登录成功！',
        });
        token.save(msg.token);
        message.success(defaultLoginSuccessMessage);
        await fetchUserInfo();
        /** 此方法会跳转到 redirect 参数所在的位置 */
        if (!history) return;
        const { query } = history.location;
        const { redirect } = query as { redirect: string };
        history.push(redirect || '/');
        return;
      }
      // console.log(msg);
      // 如果失败去设置用户错误信息
      setUserLoginState(msg);
      if (loginType === 'account') {
        captchaInputRef?.current.reload();
        formRef?.current.resetFields(['imagecaptcha']);
      }
    } catch (error) {
      const defaultLoginFailureMessage = intl.formatMessage({
        id: 'pages.login.failure',
        defaultMessage: '登录失败，请重试！',
      });
      message.error(defaultLoginFailureMessage);
    }
  };
  const { status, type: loginType } = userLoginState;

  const channel = tools.md5String();
  const loadLoginQrcode = async () => {
    tools.createWebSocketChannel(channel, messageReceived);
    const result = await request('/api/login/qrcode', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      data: {
        channel: channel,
        host: window.location.host.indexOf(':') != -1 ? window.location.host.substring(0, window.location.host.indexOf(':')) : window.location.host,
        port: window.location.port,
        useSSL: window.location.protocol == 'https:' ? 1 : 0
      }
    });
    if (result.data) {
      setImageUrl(result.data);
    }

  }

  const messageReceived = async (text, id, channel) => {
    // console.log('messageReceived');
    var values = JSON.parse(text);
    if (values.message == 'login') {
      setScanResult('正在登录');
      const key = CryptoJS.enc.Utf8.parse('PT3ZOOSWtolC7fMJ');
      const iv = CryptoJS.enc.Utf8.parse('r3uvSv17RfsPwd3J');
      let username = values.username;
      const encryptUsername = CryptoJS.AES.encrypt(username, key, {
        iv: iv,
        mode: CryptoJS.mode.CBC,
        padding: CryptoJS.pad.Pkcs7,
      });
      username = encryptUsername.ciphertext.toString().toUpperCase();
      const msg = await request('/api/login/qrcode-login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        data: { username, checkmd5: values.checkmd5, type: values.type }
      });
      if (msg.status === 'ok') {
        const defaultLoginSuccessMessage = intl.formatMessage({
          id: 'pages.login.success',
          defaultMessage: '登录成功！',
        });
        token.save(msg.token);
        message.success(defaultLoginSuccessMessage);
        await fetchUserInfo();
        /** 此方法会跳转到 redirect 参数所在的位置 */
        if (!history) return;
        const { query } = history.location;
        const { redirect } = query as { redirect: string };
        history.push(redirect || '/');
        return;
      }
      message.warn(msg.msg);
      setScanResult('请使用绑定的微信号或企业号扫描登录');
    } else {
    }
  }

  return (
    <div className={styles.container}>
      <div className={styles.content}>
        <LoginForm
          initialValues={{
            autoLogin: true,
          }}
          onFinish={async (values) => {
            setUserLoginState({});
            if (type === 'account' && !tools.passwordStrength(values.password)) {
              message.warn('登录密码不符合安全要求');
              return;
            }
            await handleSubmit(values as API.LoginParams);
          }}
          formRef={formRef}
        >
          <Tabs activeKey={type} onChange={setType}>
            <Tabs.TabPane
              key="account"
              tab={intl.formatMessage({
                id: 'pages.login.accountLogin.tab',
                defaultMessage: '账户密码登录',
              })}
            />
            <Tabs.TabPane
              key="mobile"
              tab={intl.formatMessage({
                id: 'pages.login.phoneLogin.tab',
                defaultMessage: '手机号登录',
              })}
            />
          </Tabs>

          {type === 'account' && (
            <>
              {status === 'error' && loginType === 'account' && (
                <LoginMessage content={userLoginState.msg} />
              )}
              <ProFormText
                name="username"
                fieldProps={{
                  size: 'large',
                  prefix: <UserOutlined className={styles.prefixIcon} />,
                }}
                placeholder={'请输入用户名或手机号'}
                rules={[
                  {
                    required: true,
                    message: (
                      <FormattedMessage
                        id="pages.login.username.required"
                        defaultMessage="请输入用户名"
                      />
                    ),
                  },
                ]}
              />
              <ProFormText.Password
                name="password"
                fieldProps={{
                  size: 'large',
                  prefix: <LockOutlined className={styles.prefixIcon} />,
                }}
                placeholder={'请输入密码'}
                rules={[
                  {
                    required: true,
                    message: (
                      <FormattedMessage
                        id="pages.login.password.required"
                        defaultMessage="请输入密码"
                      />
                    ),
                  },
                ]}
              />
              {/* <CaptchaInput ref={captchaInputRef} /> */}
            </>
          )}

          {type === 'mobile' && (
            <>
              {status === 'error' && loginType === 'mobile' && (
                <LoginMessage content={userLoginState.msg} />
              )}
              <ProFormText
                fieldProps={{
                  size: 'large',
                  prefix: <MobileOutlined className={styles.prefixIcon} />,
                }}
                name="phone"
                placeholder={'请输入企业号绑定手机号'}
                rules={[
                  {
                    required: true,
                    message: (
                      <FormattedMessage
                        id="pages.login.phoneNumber.required"
                        defaultMessage="请输入手机号！"
                      />
                    ),
                  },
                  {
                    pattern: /^1\d{10}$/,
                    message: (
                      <FormattedMessage
                        id="pages.login.phoneNumber.invalid"
                        defaultMessage="手机号格式错误！"
                      />
                    ),
                  },
                ]}
              />
              <ProFormCaptcha
                fieldProps={{
                  size: 'large',
                  prefix: <LockOutlined className={styles.prefixIcon} />,
                }}
                captchaProps={{
                  size: 'large',
                }}
                placeholder={intl.formatMessage({
                  id: 'pages.login.captcha.placeholder',
                  defaultMessage: '请输入验证码',
                })}
                captchaTextRender={(timing, count) => {
                  if (timing) {
                    return `${count} ${intl.formatMessage({
                      id: 'pages.getCaptchaSecondText',
                      defaultMessage: '获取验证码',
                    })}`;
                  }
                  return intl.formatMessage({
                    id: 'pages.login.phoneLogin.getVerificationCode',
                    defaultMessage: '获取验证码',
                  });
                }}
                phoneName="phone"
                name="captcha"
                rules={[
                  {
                    required: true,
                    message: (
                      <FormattedMessage
                        id="pages.login.captcha.required"
                        defaultMessage="请输入验证码！"
                      />
                    ),
                  },
                ]}
                onGetCaptcha={async (phone) => {
                  const result = await getFakeCaptcha({
                    phone,
                  });
                  if (result.status == 'ok') {
                    message.success('验证码发送成功！');
                  } else {
                    message.error(result.msg);
                    throw new Error(result.msg);
                  }
                }}
              />
            </>
          )}
          <div className={styles.saomacontainer} style={{ display: imageUrl != '' ? 'block' : 'none' }}>
            <span className={styles.accounticon} onClick={() => { setImageUrl('') }} title="账号登录"></span>
            <img src={imageUrl} width="200" height="200" id="qrcodecodeimg" style={{ marginTop: 30 }} />
            <p onClick={() => { loadLoginQrcode() }} style={{ cursor: 'pointer' }}>刷新二维码</p>
            <p>{scanResult}</p>
          </div>
          <div className={styles.accountcontainer} style={{ display: imageUrl != '' ? 'none' : 'block' }}>
            <span className={styles.saomaicon} onClick={() => { loadLoginQrcode() }} title="扫描登录"></span>
          </div>
        </LoginForm>
        <Footer />
      </div>
    </div>
  );
};

export default Login;
