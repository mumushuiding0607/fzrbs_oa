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
import { BalanceTypes, ProjectStatesEnum } from '../config';
 
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
const Addbalance:React.FC<{data:any,onChange:Function}> = ({data,onChange}) =>{
  const [form] = Form.useForm();
  const { initialState } = useModel('@@initialState');
  const { currentUser } = initialState;

  data.specialinvoice = data.specialinvoice?data.specialinvoice:0
  if (data.projectid) data.projectid = parseInt(data.projectid)
  if (data.type) data.type = parseInt(data.type)
  const onReset = () => {
    form.resetFields();
  };


  const onFinish = (values: any) => {
    if (values['date']&&values['date'][0]){
      values.starttime = moment(values['date'][0]).format('YYYY-MM-DD');
      values.endtime = moment(values['date'][1]).format('YYYY-MM-DD');
      delete values.date
    }
    if(values.coenterprisename&&Array.isArray(values.coenterprisename)){
        values.coenterprise = values.coenterprisename.map((e:any)=>e?e.value:'').join(',')
        values.coenterprisename = values.coenterprisename.map((e:any)=>e?e.label:'').join(',')
    }
    
    if (values.id) {
      if (currentUser.wxuserid!=data.creator){
        Modal.error({
          title: '报错',
          content: '只有创建人才能修改',
        });
        return
      }
    } else {
      values.creator = currentUser.wxuserid
      values.department = currentUser.department
    }
    console.log(values)
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



  const normFile = (e: any) => {
    if (Array.isArray(e)) {
      return e;
    }
    return e?.fileList;
  };
  return (
    <>
      <Form {...formItemLayout} form={form} onFinish={onFinish} style={{ maxWidth: 650 }} initialValues={data}>
        <Form.Item label="关联项目id" name="projectid" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="关联外部合同id" name="relatedcontractid" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="关联合同id" name="contractid" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="关联支出id" name="bid" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="收支类型：" name="type"  rules={[{ required: true, message: 'Please input!' }]}>
          <Dictselect disabled type={"合同类型"} />
        </Form.Item>
        <Form.Item  label="项目名称：" name="title" rules={[{ required: true, message: 'Please input!' }]}>
          <Input/>
        </Form.Item>
        <Form.Item label={(data.type==BalanceTypes.INCOME?'收入':'支出')+"税率："} name="tax" rules={[{ required: true, message: 'Please input!' }]}>
            <Dictselect type={"税率"}  />
        </Form.Item>
        <Form.Item label={(data.type==BalanceTypes.INCOME?'收入':'支出')+"类型："} name="moneytype" rules={[{ required: true, message: 'Please input!' }]}>
            <Dictselect type={data.type==BalanceTypes.INCOME?'收入类型':'支出类型'}  />
        </Form.Item>
        <Form.Item  label="是否专票：" name="specialinvoice" style={{display:data.type==BalanceTypes.INCOME?'none':'flex'}} rules={[{ required: data.type==BalanceTypes.INCOME?false:true, message: 'Please input!' }]}>
            <Select options={[{value:0,label:'非专票'},{value:1,label:'专票'}]} />
        </Form.Item>
        <Form.Item label={"预算金额"} name="budget" rules={[{ required: true, message: 'Please input!' }]}>
          <InputNumber prefix="￥" style={{ width: '100%' }} />
        </Form.Item>
        <Form.Item label="决算金额" name="final">
          <InputNumber prefix="￥" style={{ width: '100%' }} />
        </Form.Item>
        <Form.Item label="备注：" name="note">
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
export default Addbalance