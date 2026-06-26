import { Button, Form, Input, InputNumber, Modal, Space } from 'antd';
import React, { useEffect } from 'react';

import Dictselect from '../budget/dict/dictselect';
import { savePricelist } from './service';
import Advsize from './advsize';

const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};

const AddPrice: React.FC<{ data?: any; onChange?: Function }> = ({ data, onChange }) => {
  const [form] = Form.useForm();

  useEffect(() => {
    if (data) {
      form.setFieldsValue(data);
    }
  }, [data]);

  const handleSubmit = async (values: any) => {
    try {
      if (values.E_AdSize_ID && values.E_AdSize_ID instanceof Object){
        values.E_AdSize_ID = values.E_AdSize_ID.value
      }
      const res: any = await savePricelist(values);

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
      id="addPrice"
      form={form}
      onFinish={handleSubmit}
      initialValues={data}
    >
      {/* 隐藏字段 */}
      {data?.SYS_DOCUMENTID && (
        <Form.Item name="SYS_DOCUMENTID" style={{ display: 'none' }}>
          <Input />
        </Form.Item>
      )}

      {/* 刊物 */}
      <Form.Item
        label="刊物"
        name="E_PID"
        rules={[{ required: true, message: '请选择刊物' }]}
      >
        <Dictselect
          type="刊物"
          multiple={false}
          needAddItem={true}
          placeholder="请选择刊物"
        />
      </Form.Item>

      {/* 投放日 */}
      <Form.Item
        label="投放日"
        name="E_MID"
      >
        <Dictselect
          type="投放日"
          multiple={false}
          needAddItem={true}
          placeholder="请选择投放日"
        />
      </Form.Item>

      {/* 版位 */}
      <Form.Item
        label="版位"
        name="E_AdField_ID"
      >
        <Dictselect
          type="版位"
          multiple={false}
          needAddItem={true}
          placeholder="请选择版位"
        />
      </Form.Item>

      {/* 颜色 */}
      <Form.Item
        label="颜色"
        name="E_Color_ID"
      >
        <Dictselect
          type="颜色"
          multiple={false}
          needAddItem={true}
          placeholder="请选择颜色"
        />
      </Form.Item>

      {/* 规格 */}
      <Form.Item
        label="规格"
        name="E_AdSize_ID"
      >
        <Advsize
            placeholder="请选择规格"
          />
      </Form.Item>


      {/* 单价 */}
      <Form.Item
        label="单价"
        name="E_Price"
        rules={[{ required: true, message: '请输入单价' }]}
      >
        <InputNumber
          style={{ width: '100%' }}
          placeholder="请输入单价"
          min={0}
          precision={2}
          formatter={(value) => `¥ ${value}`.replace(/\B(?=(\d{3})+(?!\d))/g, ',')}
          parser={(value) => value?.replace(/¥\s?|(,*)/g, '') as any}
        />
      </Form.Item>

      {/* 提交按钮 */}
      <Form.Item {...tailLayout}>
        <Space>
          <Button type="primary" htmlType="submit">
            提交
          </Button>
          <Button htmlType="button" onClick={handleReset}>
            清空
          </Button>
        </Space>
      </Form.Item>
    </Form>
  );
};

export default AddPrice;
