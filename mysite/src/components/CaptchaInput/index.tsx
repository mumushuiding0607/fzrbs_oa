import { PictureOutlined } from '@ant-design/icons';
import { ProFormText } from '@ant-design/pro-components';
import { Input, message } from 'antd';
import React, { useState, useEffect, useImperativeHandle } from 'react';
import { request } from 'umi';
import styles from './index.less';

const getCaptcha = async () => {
  try {
   
    const data = await request('/api/login/captcha/refresh');

    if (data.url) {
      data.url = data.url;
      return data;
    }
  } catch (error) {
    message.error('图片验证码获取失败');
    return [];
  }
  message.error('图片验证码获取失败');
  return [];
};

const CaptchaInput = React.forwardRef((props, ref) => {
  const [imageUrl, setImageUrl] = useState<string>();

  useEffect(() => {
    getCaptcha().then((data: any) => {
      setImageUrl(data.url);
    });
  }, []);

  const onClickImage = () => {
    getCaptcha().then((data: any) => {
      setImageUrl(data.url);
    });
  };

  useImperativeHandle(ref, () => ({
    reload: onClickImage,
  }));

  return (
    <Input.Group compact>
      <ProFormText
        name="imagecaptcha"
        width="sm"
        fieldProps={{
          size: 'large',
          prefix: <PictureOutlined className={styles.prefixIcon} />,
          maxLength: 4,
        }}
        placeholder={'请输入验证码'}
        rules={[
          {
            required: true,
            message: '验证码必填项！',
          },
        ]}
      />
      <img
        src={imageUrl}
        width="70"
        height="40"
        style={{
          marginLeft: 4,
        }}
        onClick={onClickImage}
      />
    </Input.Group>
  );
});

export default CaptchaInput;
