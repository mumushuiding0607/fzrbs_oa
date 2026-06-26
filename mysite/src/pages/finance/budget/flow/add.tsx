
import { Button, DatePicker,Form,Input,InputNumber,Modal,Radio,Select,Space,Upload } from 'antd';
import React, { useState } from 'react';
import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';

import { save } from './service';

import weekday from "dayjs/plugin/weekday"
import localeData from "dayjs/plugin/localeData"

import TemplatesSelect from './templates-select';
import DepartmentTreeSelect from '../common/department_treeselect';
import UserAutocomplete from '../common/userAutocomplete';
import Dictselect from '../dict/dictselect';
import RangeNumber from '../../contract/RangeNumber';
 
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
const Add:React.FC<{data?:any,onChange:Function}> = ({data,onChange}) =>{
  const [form] = Form.useForm();
 

 
  const onReset = () => {
    form.resetFields();
  };

  const onContractChange = (e:any) => {
    console.log(e)
  }
  const onFinish = (values: any) => {

    console.log(values)
    if (values.templateid){
      if (values.templateid instanceof Array){
        values.templateid = values.templateid.join(',')
      } else if (values.templateid instanceof Object) {
        values.templateid = values.templateid.value
        values.templatename = values.templateid.lable
      }
    }
    if (values.dids){
      if (values.dids instanceof Array){
        values.dids = values.dids.join(',')
      } else if (values.dids instanceof Object) {
        values.dids = values.dids.value
      }
    }
    if (values.uids){
      if (values.uids instanceof Array){
        values.uids = values.uids.map((e:any)=>e.value).join(',')
      } else if (values.dids instanceof Object) {
        values.uids = values.uids.value
      }
    }
    if (values.types && Array.isArray(values.types)){
      values.types = values.types.map((e:any)=>e.value).join(',')
    }
    if (values.projecttypes && Array.isArray(values.projecttypes)){
      values.projecttypes = values.projecttypes.map((e:any)=>e.value).join(',')
    }
    if (values.amount && values.amount.length>0){
      if (values.amount[0]!=null) values.lamount = values.amount[0]
      if (values.amount[1]!=null) values.hamount = values.amount[1]
    }
    delete values.amount
    
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
      <Form {...formItemLayout} form={form} onFinish={onFinish} style={{ maxWidth: 650 }} initialValues={data}>
      <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
       
        <Form.Item  label="流程：" name="templateid"   rules={[{ required: true, message: 'Please input!' }]}>
          <TemplatesSelect multiple={false}   onChange={onContractChange}/>
        </Form.Item>
        <Form.Item label="审批类型："  name="types" >
          <Dictselect type='审批类型' multiple={true}/>
        </Form.Item>
        <Form.Item label="金额："  name="amount" >
          <RangeNumber></RangeNumber>
        </Form.Item>
        <Form.Item label="项目类别："  name="projecttypes" >
          <Dictselect type='项目类别' multiple={true}/>
        </Form.Item>
        <Form.Item  label="预询价:" name="inquire">
          <Radio.Group>
            <Radio value={0} defaultChecked> 否 </Radio>
            <Radio value={1}> 是 </Radio>
          </Radio.Group>
        </Form.Item>

        <Form.Item label="部门："  name="dids" >
          <DepartmentTreeSelect multiple={true} defaultValue={data.dids} maxTagCount={2}/>
        </Form.Item>
        <Form.Item  label="用户："  name="uids" >
            <UserAutocomplete multiple={true}/>
        </Form.Item>


        <Form.Item {...tailLayout}>
        <Space>
          <Button type="primary" htmlType="submit">
            {data.id?'更新':'提交'}
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
export default Add