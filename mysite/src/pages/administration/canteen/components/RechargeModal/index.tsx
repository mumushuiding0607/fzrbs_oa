import { Form, InputNumber, message, Modal } from 'antd';
import React, { useImperativeHandle } from 'react';
import { useState } from 'react';
import tools from '@/utils/tools';
import { recharge } from '../list/service';

export type RechargeModalProps = {
  onOk?: (value: string | number) => void;
  username: string;
  userid: string;
};

const RechargeModal = React.forwardRef((props: RechargeModalProps, ref) => {
  const [visible, setVisible] = useState(false);
  const [form] = Form.useForm<{ rechargeValue: string | number }>();
  const rechargeValue = Form.useWatch('rechargeValue', form);

  const handleCancel = () => setVisible(false);
  const handleOk = () => {
    if (rechargeValue == '') {
      return;
    }
    Modal.confirm({
      title: '系统提示',
      content:
        '确定要向 ' +
        props.username +
        ' 账号充值：' +
        tools.formatCurrency(rechargeValue) +
        ' 元吗？',
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        const result = await recharge({
          id: props.userid,
          value: rechargeValue,
        });
        if (result.errorCode) {
          message.warn(result.errorMessage);
        } else {
          message.success('充值成功');
        }
        if (props.onOk) {
          props.onOk(rechargeValue);
        }
        setVisible(false);
      },
    });
  };

  useImperativeHandle(ref, () => ({
    setVisible: (value: boolean) => {
      setVisible(value);
      setTimeout(() => {
        form.setFieldsValue({ rechargeValue: '' });
      }, 200);
    },
  }));

  return (
    <Modal visible={visible} title="食堂账号餐补充值" onCancel={handleCancel} onOk={handleOk}>
      <Form form={form} layout="vertical" autoComplete="off">
        <Form.Item name="rechargeValue" label="请输入充值金额，负数为扣除">
          <InputNumber prefix="￥" controls={false} style={{ width: '100%' }} />
        </Form.Item>
      </Form>
    </Modal>
  );
});
export default RechargeModal;
