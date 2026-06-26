import React, { useRef, useState } from 'react';
import { ProForm, ProFormInstance, ProFormText } from '@ant-design/pro-components';
import { changePasswordRule } from '../service';
import { useModel } from 'umi';
import { Alert, message } from 'antd';
import tools from '@/utils/tools';

const SecurityView: React.FC = () => {
  const { initialState } = useModel('@@initialState');
  const [responseState, setResponseState] = useState<API.ErrorResponse>();

  const { currentUser } = initialState;

  const formRef = useRef<ProFormInstance>();

  const handleFinish = async (values: Record<string, any>) => {
    const hide = message.loading('正在保存更新');
    try {
      const data = Object.assign({ id: currentUser.id }, values);
      const result = await changePasswordRule(data);
      hide();
      setResponseState(result);
      if (!result.errorCode) {
        message.success('密码更新成功');
        formRef?.current?.setFieldsValue({
          oldpassword: '',
          newpassword: '',
          confirmpassword: '',
        });
      }
      return result;
    } catch (error) {
      message.success('更新失败！');
      return false;
    }
  };

  return (
    <>
      <ProForm
        layout="vertical"
        formRef={formRef}
        onFinish={async (values) => {
          setResponseState(undefined);
          await handleFinish(values);
        }}
        submitter={{
          searchConfig: {
            submitText: '更新密码',
          },
          render: (_, dom) => dom[1],
        }}
        hideRequiredMark
      >
        {responseState?.errorCode && (
          <Alert
            style={{ marginBottom: 24 }}
            message={responseState?.errorMessage}
            type="error"
            closable={true}
            showIcon
          />
        )}
        <ProFormText.Password
          width="md"
          name="oldpassword"
          label="旧密码"
          rules={[
            {
              required: true,
              message: '请输入您的旧密码！',
            },
          ]}
        />
        <ProFormText.Password
          width="md"
          name="newpassword"
          label="新密码"
          rules={[
            {
              required: true,
              message: '请输入您的新密码！',
            },
            {
              validator: (_, val) => {
                if (val && !tools.passwordStrength(val)) {
                  return Promise.reject(
                    '密码为数字，小写字母，大写字母，特殊符号 至少包含三种，长度10位及以上！',
                  );
                }
                if (formRef?.current?.getFieldValue('oldpassword') == val) {
                  return Promise.reject('新密码不能跟旧密码完全一样！');
                }
                return Promise.resolve();
              },
            },
          ]}
        />
        <ProFormText.Password
          width="md"
          name="confirmpassword"
          label="新密码确认"
          rules={[
            {
              required: true,
              message: '请输入密码确认！',
            },
            {
              validator: (_, val) => {
                if (val !== formRef?.current?.getFieldValue('newpassword')) {
                  return Promise.reject('密码确认不正确！');
                }
                return Promise.resolve();
              },
            },
          ]}
        />
      </ProForm>
    </>
  );
};

export default SecurityView;
