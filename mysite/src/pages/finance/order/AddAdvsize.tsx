import { Button, Form, Input, InputNumber, Modal, Space } from 'antd';
import React, { useEffect } from 'react';

import Dictselect from '../budget/dict/dictselect';
import { saveAdvsize } from './service';

const tailLayout = {
  wrapperCol: { offset: 6, span: 16 },
};

const AddAdvsize: React.FC<{ data?: any; onChange?: Function }> = ({ data, onChange }) => {
  const [form] = Form.useForm();

  useEffect(() => {
    if (data) {
      form.setFieldsValue(data);
    }
  }, [data]);

  const handleSubmit = async (values: any) => {
    try {
      const res: any = await saveAdvsize(values);

      if (res.errorMessage) {
        Modal.error({ title: res.errorMessage });
      } else {
        Modal.success({ title: '提交成功' });
        onChange && onChange(res.data);
      }
    } catch (error) {
      console.error(error);
    }
  };

  const handleReset = () => {
    form.resetFields();
  };

  return (
    <Form
      id="addAdvsize"
      form={form}
      onFinish={handleSubmit}
      initialValues={data}
      layout="horizontal"
      labelCol={{ span: 6 }}
      wrapperCol={{ span: 16 }}
    >
      {/* 隐藏字段 */}
      {data?.SYS_DOCUMENTID && (
        <Form.Item name="SYS_DOCUMENTID" style={{ display: 'none' }}>
          <Input />
        </Form.Item>
      )}

      {/* 规格名称 */}
      <Form.Item
        label="规格"
        name="E_Name"
        rules={[{ required: true, message: '请输入规格' }]}
      >
        <Input placeholder="请输入规格" />
      </Form.Item>

      {/* 广告类型 */}
      <Form.Item
        label="刊物"
        name="E_AdType_ID"
        rules={[{ required: true, message: '请输入' }]}
      >
        <Dictselect
          type="刊物"
          multiple={false}
          needAddItem={true}
          placeholder="请选择刊物"
        />
      </Form.Item>

      {/* 宽度 */}
      <Form.Item
        label="宽度"
        name="E_Width"
      >
        <InputNumber
          style={{ width: '100%' }}
          placeholder="请输入宽度"
          min={0}
          addonAfter="厘米"
        />
      </Form.Item>

      {/* 高度 */}
      <Form.Item
        label="高度"
        name="E_Height"
      >
        <InputNumber
          style={{ width: '100%' }}
          placeholder="请输入高度"
          min={0}
          addonAfter="厘米"
        />
      </Form.Item>

      {/* 版面费 */}
      <Form.Item
        label="版面费"
        name="E_LayoutAmount"
      >
        <InputNumber
          style={{ width: '100%' }}
          placeholder="请输入版面费"
          min={0}
          precision={2}
          addonAfter="元"
        />
      </Form.Item>

      {/* 排序 */}
      <Form.Item
        label="排序"
        name="E_Order"
      >
        <InputNumber
          style={{ width: '100%' }}
          placeholder="请输入排序号"
          min={0}
        />
      </Form.Item>

      {/* 提交按钮 */}
      <Form.Item {...tailLayout}>
        <Space>
          <Button type="primary" htmlType="submit">
            {data?.SYS_DOCUMENTID ? '更新' : '提交'}
          </Button>
          <Button htmlType="button" onClick={handleReset}>
            清空
          </Button>
        </Space>
      </Form.Item>
    </Form>
  );
};

export default AddAdvsize;
