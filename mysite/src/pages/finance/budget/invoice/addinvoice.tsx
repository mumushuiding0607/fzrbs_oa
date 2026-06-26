import { EyeOutlined, PlusOutlined, PlusSquareOutlined } from '@ant-design/icons';
import { Button, DatePicker,Divider,Form,Input,InputNumber,Modal,Select,Space,Upload } from 'antd';
import React, { useState } from 'react';
import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import Dictselect from '../dict/dictselect';
import { useModel } from 'umi';
import { save } from './service';
import moment from 'moment';
import weekday from "dayjs/plugin/weekday"
import localeData from "dayjs/plugin/localeData"
import { BalanceTypes } from '../config';

 
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

const AddInvoice:React.FC<{data:any,onChange?:Function,isaccountant?:boolean}> = ({data,onChange,isaccountant=false}) =>{
  const [form] = Form.useForm();
  const dateFormat = 'YYYY-MM-DD HH:mm:ss'
  if (data.date) {
    data.date = dayjs(data.date,dateFormat)
  }
  if (data.projectid) data.projectid = parseInt(data.projectid)
  const onReset = () => {
    form.resetFields();
  };


  const onFinish = (values: any) => {
    if (values.date) {
      values.date = values.date.format(dateFormat);
    }

    
    

    
    
  };

  return (
    <>
      <Form form={form} {...formItemLayout} onFinish={onFinish} style={{ maxWidth: 450 }} initialValues={data}>
        <Form.Item label="项目id" name="projectid" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="合同id" name="contractid" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="项目id" name="projectid" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="发票类型" name="type" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item  label="发票号码：" name="invoiceno" rules={[{ required: true, message: 'Please input!' }]}>
          <Input/>
        </Form.Item>
        <Form.Item label="开票金额" name="amount" rules={[{ required: true, message: 'Please input!' }]}>
          <InputNumber prefix="￥" style={{ width: '100%' }} />
        </Form.Item>
        <Form.Item label="开票税率" name="taxrate" rules={[{ required: true, message: 'Please input!' }]}>
          <InputNumber prefix="%" style={{ width: '100%' }} />
        </Form.Item>
        <Form.Item label="开票日期：" name="date" rules={[{ required: true, message: 'Please input!' }]}>
          <DatePicker format="YYYY-MM-DD" style={{ width: '100%' }}/>
        </Form.Item>
        <Form.Item label={data.type==BalanceTypes.INCOME?'到账金额':'付款金额'} name="redeem" >
          <InputNumber prefix="￥" style={{ width: '100%' }} disabled={!isaccountant} />
        </Form.Item>
        <Form.Item label="开票项目：" name="content">
          <TextArea rows={4} />
        </Form.Item>

        <Form.Item label="发票备注：" name="note">
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
export default AddInvoice