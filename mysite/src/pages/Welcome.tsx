import { PageContainer } from '@ant-design/pro-components';
import { Alert, Card, Modal } from 'antd';
import React, { useEffect } from 'react';
import { useModel, history } from 'umi';

const Welcome: React.FC = () => {

  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;

  useEffect(() => {
    if (currentUser.wxopenid == '' && currentUser.wxuserid == '') {
      Modal.confirm({
        content: '您还未绑定微信号或企业微信号，绑定后登录时可选择扫码登录，方便用户登录',
        okText: '去绑定',
        onOk: () => {
          history.push('/account/settings/?key=binding');
        }
      });
    }
  }, []);

  return (
    <PageContainer>
      <Card>
        <Alert
          message="欢迎使用福州日报社内部办公管理系统"
          type="success"
          showIcon
          banner
          style={{
            margin: -12,
            marginBottom: 24,
          }}
        />
      </Card>

    </PageContainer>
  );
};

export default Welcome;
