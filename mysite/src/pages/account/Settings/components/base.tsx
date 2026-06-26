import React, { useState } from 'react';
import { LoadingOutlined, UploadOutlined } from '@ant-design/icons';
import { Button, message, Upload } from 'antd';
import { ProDescriptions } from '@ant-design/pro-components';
import { request, useModel } from 'umi';
import styles from './BaseView.less';
import { RcFile } from 'antd/es/upload/interface';
import { random } from 'lodash';

const beforeUpload = (file: RcFile) => {
  const isJpgOrPng = file.type === 'image/jpeg' || file.type === 'image/png';
  if (!isJpgOrPng) {
    message.error('只支持JPG/PNG图片格式');
    return false;
  }
  const isLt1M = file.size / 1024 / 1024 < 1;
  if (!isLt1M) {
    message.error('图片不能大于 1MB!');
    return false;
  }
  return isJpgOrPng && isLt1M;
};

const BaseView: React.FC = () => {
  const { initialState, setInitialState } = useModel('@@initialState');

  const [loading, setLoading] = useState(false);
  const [imageUrl, setImageUrl] = useState<string>();

  const { currentUser } = initialState;

  const getAvatarURL = () => {
    if (currentUser) {
      if (currentUser.avatar) {
        return currentUser.avatar;
      }
      const url = '/default_avatar.png';
      return url;
    }
    return '';
  };

  // 头像组件 方便以后独立，增加裁剪之类的功能
  const AvatarView = ({ avatar }: { avatar: string }) => (
    <>
      <div className={styles.avatar}>
        <img src={imageUrl ? imageUrl : avatar} alt="avatar" />
      </div>
      <Upload
        name="avatar"
        showUploadList={false}
        beforeUpload={beforeUpload}
        customRequest={async (options) => {
          const formData = new FormData();
          formData.append('avatar', options.file);
          formData.append('oldAvatar', currentUser.avatar);
          formData.append('userId', currentUser.id);
          setLoading(true);
          const result = await request('/api/account/avatarUpload', {
            method: 'POST',
            body: formData,
          });
          if (result.success) {
            setLoading(false);
            const newAvatar = result.data.url + '?' + random(100000, 999999).toString();
            setImageUrl(newAvatar);
            const newUserInfo = currentUser;
            newUserInfo.avatar = newAvatar;
            await setInitialState((s) => ({
              ...s,
              currentUser: newUserInfo,
            }));
          } else {
            message.error('头像上传失败');
          }
        }}
      >
        <div className={styles.button_view}>
          <Button>
            {loading ? <LoadingOutlined /> : <UploadOutlined />}
            更换头像
          </Button>
        </div>
      </Upload>
    </>
  );

  return (
    <div className={styles.baseView}>
      <>
        <div className={styles.left}>
          <ProDescriptions column={1}>
            <ProDescriptions.Item label="用户名">
              {currentUser ? currentUser.username : ''}
            </ProDescriptions.Item>
            <ProDescriptions.Item label="姓名">
              {currentUser ? currentUser.realname : ''}
            </ProDescriptions.Item>
            <ProDescriptions.Item label="手机号">
              {currentUser ? currentUser.mobile : ''}
            </ProDescriptions.Item>
            <ProDescriptions.Item label="所在部门">
              {currentUser ? currentUser.department : ''}
            </ProDescriptions.Item>
            <ProDescriptions.Item label="最后登录时间">
              {currentUser ? currentUser.lastlogintime : ''}
            </ProDescriptions.Item>
            <ProDescriptions.Item label="最后登录IP">
              {currentUser ? currentUser.lastloginip : ''}
            </ProDescriptions.Item>
            <ProDescriptions.Item label="登录次数">
              {currentUser ? currentUser.loginnum : ''}
            </ProDescriptions.Item>
            <ProDescriptions.Item label="添加时间">
              {currentUser ? currentUser.inserttime : ''}
            </ProDescriptions.Item>
          </ProDescriptions>
        </div>
        <div className={styles.right}>
          <AvatarView avatar={getAvatarURL()} />
        </div>
      </>
    </div>
  );
};

export default BaseView;
