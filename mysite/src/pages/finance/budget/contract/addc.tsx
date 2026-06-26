import { EyeOutlined, PlusOutlined, PlusSquareOutlined } from '@ant-design/icons';
import { Button, DatePicker,Form,Input,InputNumber,Modal,Select,Space,Upload } from 'antd';
import React, { useState } from 'react';
import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import Dictselect from '../dict/dictselect';
import Companyselect from '../../company/companyselect';
import { useModel } from 'umi';
import { savecontract } from './service';
import moment from 'moment';
import weekday from "dayjs/plugin/weekday"
import localeData from "dayjs/plugin/localeData"
import ContractSelect from '../../contract/contract-select';
import { BalanceTypes } from '../config';
 
dayjs.extend(weekday)
dayjs.extend(localeData)
// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
const formItemLayout = {
  labelCol: {
    xs: { span: 4 },
    sm: { span: 4 },
  }
};
// dom
const { TextArea } = Input;

dayjs.extend(customParseFormat);
const { RangePicker } = DatePicker;

const dateFormat = 'YYYY-MM-DD';
const AddC:React.FC<{data?:any,onChange:Function}> = ({data,onChange}) =>{
  const [form] = Form.useForm();
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const dateFormat = 'YYYY-MM-DD HH:mm:ss'

  const onReset = () => {
    form.resetFields();
  };

  const onContractChange = (e:any) => {
    console.log(e)
  }
  const onFinish = (values: any) => {

    
    if (values.contractids){
      if (values.contractids instanceof Array){
        values.contractids = values.contractids.map((e:any)=>e.value).join(',')
      } else if (values.contractids instanceof Object) {
        values.contractids = values.contractids.value
      }
    }

    savecontract(values).then((res)=>{
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
      <Form {...formItemLayout} form={form} onFinish={onFinish} style={{ maxWidth: 650 }} initialValues={data}>
      <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="bid" name="bid" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="projectid" name="projectid" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item  label="搜索合同：" name="contractids" style={{display:data.id?'none':''}}  rules={[{ required: true, message: 'Please input!' }]}>
          <ContractSelect multiple={false}  type={data.type} onChange={onContractChange}/>
        </Form.Item>
        <Form.Item  name="type" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="合同类型：">
          <Input disabled value={data.type==BalanceTypes.INCOME?'收入':'支出'}/>
        </Form.Item>

        <Form.Item {...tailLayout}>
        <Space>
          <Button type="primary" htmlType="submit">
            {data.id?'更新':'关联'}
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
export default AddC