
import { Button,Form,Input,Modal,Space } from 'antd';
import React, { useState } from 'react';


import Problemstates from './problem_states';
import { saveproblem } from './service';


const formItemLayout = {
  labelCol: {
    xs: { span: 4 },
    sm: { span: 4 },
  }
};
// dom

const Addproblem:React.FC<{data?:any,onChange?:Function}> = ({data,onChange}) =>{
  const [form] = Form.useForm();

  const onFinish = (values: any) => {

    values.typeid = 1
    
    saveproblem(values).then((res:any)=>{
      if (res.errorMessage) {
        Modal.error({
          title: '报错',
          content: res.errorMessage,
        });
      } else {
        onChange&&onChange(res.data)
      }
    })
    
    
  };


  return (
    <>
      <Form {...formItemLayout} form={form} onFinish={onFinish} style={{ maxWidth: 650 }} initialValues={data}>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>

        <Form.Item  label="必问内容：" name="name"   rules={[{ required: true, message: 'Please input!' }]}>
          <Input/>
        </Form.Item>
        <Form.Item  label="必问类型：" name="state"   rules={[{ required: true, message: 'Please input!' }]}>
          <Problemstates />
        </Form.Item>

       <div style={{display:'flex',flexDirection:'column',alignItems:'center'}}>
          <Form.Item >
            
              <Button type="primary" htmlType="submit">
                {data.id?'更新':'新增'}
              </Button>
          
          </Form.Item>
       </div>
        
      </Form>
    </>
  )
}
export default Addproblem