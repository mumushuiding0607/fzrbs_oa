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
 
dayjs.extend(weekday)
dayjs.extend(localeData)
// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
// dom
const { TextArea } = Input;

dayjs.extend(customParseFormat);

const Updatefinance:React.FC<{data:any,onChange:Function}> = ({data,onChange}) =>{
  const [form] = Form.useForm();
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;
  const dateFormat = 'YYYY-MM-DD HH:mm:ss'
  if (data.paydate) {
    data.paydate = dayjs(data.paydate,dateFormat)
  }
  if (data.projectid) data.projectid = parseInt(data.projectid)
  if (data.type) data.type = parseInt(data.type)
  const onReset = () => {
    form.resetFields();
  };


  const onFinish = (values: any) => {
    if (values.paydate) {
      values.paydate = values.paydate.format(dateFormat);
    }

    save(values).then((res)=>{
      if (res.errorMessage) {
        Modal.error({
          title: '报错',
          content: res.errorMessage,
        });
      } else {
        onChange(res.data)
      }
      
    })
    
    
  };


  return (
    <>
      <Form form={form} onFinish={onFinish} style={{ maxWidth: 650 }} initialValues={data}>
        <Form.Item label="关联项目id" name="projectid" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>

        <Form.Item  label="凭证号码：" name="voucher" >
          <Input/>
        </Form.Item>


        <Form.Item label="税费复核：" name="taxcheck">
          <TextArea rows={2} />
        </Form.Item>
        <Form.Item label="财务备注：" name="financenote">
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
export default Updatefinance