import {
  PageContainer,
  ProForm,
  ProFormInstance,
  ProFormSelect,
  ProFormTextArea,
} from '@ant-design/pro-components';
import { message, Modal } from 'antd';
import React, { useEffect, useRef, useState } from 'react';
import { history, useModel } from 'umi';
import browser from '@/utils/browser';
import { getType, save } from './service';
import styles from './index.less';
import styles1 from '../style.less';

const Index: React.FC = () => {
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;
  const query = history.location.query;
  const [type, setType] = useState<any>({});
  const formRef = useRef<ProFormInstance>();

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
    getType().then((res: any) => {
      if (res?.data) {
        setType(res.data);
      }
    });
  }, []);

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
      }}
      className={query.iframe ? styles1.iframePage : ''}
    >
      <div
        style={{
          margin: '0 auto',
          border: '1px solid rgba(0,0,0,.06)',
          width: browser.mobile() ? '100%' : '30%',
          minHeight: 300,
          background: '#fff',
          padding: 15,
        }}
      >
        <ProForm
          layout="vertical"
          formRef={formRef}
          onFinish={async (values) => {
            const result = await save(values);
            if (result.errorMessage) {
              message.warn(result.errorMessage);
              return false;
            }
            message.success('保存成功！');
            formRef?.current?.resetFields();
            return true;
          }}
          className={styles.myPage}
        >
          <ProFormSelect
            name="type"
            label=""
            valueEnum={type}
            placeholder="请选择分类"
            rules={[
              {
                required: true,
                message: '请选择分类！',
              },
            ]}
          />
          <ProFormTextArea
            colProps={{ md: 12, xl: 24 }}
            label=""
            name="content"
            fieldProps={{
              showCount: true,
              allowClear: true,
            }}
            placeholder="请输入您的意见建议或其他线索内容！"
            rules={[
              {
                required: true,
                message: '请输入内容！',
              },
            ]}
          />
        </ProForm>
      </div>
    </PageContainer>
  );
};

export default Index;
