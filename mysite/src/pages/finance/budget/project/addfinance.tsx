
import { Button, DatePicker,Divider,Form,Input,InputNumber,Modal,Select,Space,Upload } from 'antd';
import React, { useState } from 'react';
import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import weekday from "dayjs/plugin/weekday"
import localeData from "dayjs/plugin/localeData"
import { BalanceTypes } from '../config';
import { useModel } from 'umi';
import {  saveinvoicecheck } from '../invoice/service';

 
dayjs.extend(weekday)
dayjs.extend(localeData)
// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
const formItemLayout = {
  labelCol: {
    xs: { span: 6 },
    sm: { span: 6 },
  },
  wrapperCol: {
    xs: { span: 24 },
    sm: { span: 24 },
  },
};
// dom
const { TextArea } = Input;

dayjs.extend(customParseFormat);
const { RangePicker } = DatePicker;

const Addfinance:React.FC<{data:any,onChange?:Function,}> = ({data,onChange}) =>{
  const [form] = Form.useForm();

  const dateFormat = 'YYYY-MM-DD HH:mm:ss'
  if (data.financedate) {
    data.financedate = dayjs(data.financedate,dateFormat)
  }
  if (data.projectid) data.projectid = parseInt(data.projectid)

  const onReset = () => {
    form.resetFields();
  };


  const onFinish = (values: any) => {
    if (values.financedate) {
      values.financedate = values.financedate.format(dateFormat);
    }
  
    saveinvoicecheck(values).then((res:any)=>{
      if (res.errorMessage) {
        Modal.error({
          title: '报错',
          content: res.errorMessage,
        });
      } else {

        onChange &&onChange(res.data)
      }
      
    })
    
    
  };

  return (
    <>
      <Form form={form} {...formItemLayout} onFinish={onFinish} style={{ maxWidth: 500 }} initialValues={data}>
        <Form.Item label="项目id" name="projectid" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
    
        <Form.Item  label="发票号码：" name="invoiceno" rules={[{ required: true, message: 'Please input!' }]}>
          <Input disabled/>
        </Form.Item>
        <Form.Item  label="开票金额：" name="amount" rules={[{ required: true, message: 'Please input!' }]}>
          <Input disabled/>
        </Form.Item>
        <Form.Item label="开票日期" name="date" rules={[{ required: true, message: 'Please input!' }]}>
          <Input disabled/>
        </Form.Item>
        <Form.Item label="凭证号:" name="voucher" rules={[{ required: true, message: 'Please input!' }]}>
          <Input  style={{ width: '100%' }} />
        </Form.Item>
        <Form.Item label="税费复核:" name="taxcheck" rules={[{ required: true, message: 'Please input!' }]}>
          <Input  style={{ width: '100%' }} />
        </Form.Item>
        <Form.Item label="走款时间：" name="financedate" rules={[{ required: true, message: 'Please input!' }]}>
          <DatePicker format="YYYY-MM-DD" style={{ width: '100%' }}/>
        </Form.Item>
        <Form.Item label="财务备注:" name="financenote">
          <TextArea rows={4} />
        </Form.Item>
        <Form.Item {...tailLayout}>
          <Space>
            <Button type="primary" htmlType="submit">
              {data.id?'更新':'创建'}
            </Button>
            <Button htmlType="button" onClick={onReset}>
              清空
            </Button>
          </Space>
        </Form.Item>
      </Form>
    </>
  )
}
export default Addfinance