
import { Button, Form, Input, InputNumber, Modal, Space, Table, Tag } from 'antd';

import React, { CSSProperties, useEffect, useState } from 'react';
import { saveinvoiceitem } from './service';

const formItemLayout = {
  labelCol: {
    xs: { span: 3 },
    sm: { span: 3 },
  },
  wrapperCol: {
    xs: { span: 21 },
    sm: { span: 21 },
  },
};
// balancetype 15收入，16支出
const AddInvoicingItem: React.FC<{data:any,visible?:boolean,onClose?:Function,onChange?:Function}> = ({data,onChange,visible=false,onClose}) =>{
  const [modal1, setModal1] = useState(visible)
  const [form] = Form.useForm();
  
  useEffect(()=>{
    
    setModal1(visible)
  },[visible])
  const onFinish = (values: any) => {
    
    saveinvoiceitem(values).then((res:any)=>{
      if (res.errorMessage){
        Modal.error({title:res.errorMessage})
      } else {
        onChange && onChange(res.data)
        setModal1(false)
      }
    })
    
  };
  return (
    <>
    <Modal

        title='开票项目'
        style={{ top: 20 }}
        width={600}

        visible={modal1}
        onOk={() => setModal1(false)}
        onCancel={() => setModal1(false)}
        afterClose={()=>{
          onClose && onClose(false)
        }}
        footer={null}
      >
        <div >
        <Form  {...formItemLayout}  form={form} onFinish={onFinish} style={{ maxWidth: 800 }} initialValues={data}>
          <Form.Item label="id" name="id" style={{display:'none'}}>
              <Input disabled/>
          </Form.Item>
          <Form.Item label="invoicingid" name="invoicingid" style={{display:'none'}}>
              <Input disabled/>
          </Form.Item>
          
          <Form.Item    label="开票项目" name="title" rules={[{ required: true, message: 'Please input!' }]}>
            <Input placeholder='广告费、活动执行等'/>
          </Form.Item>
          <Form.Item    label="单位" name="unit" rules={[{ required: false, message: 'Please input!' }]}>
            <Input/>
          </Form.Item>
          <Form.Item    label="数量" name="number" rules={[{ required: false, message: 'Please input!' }]}>
            <Input/>
          </Form.Item>
          <Form.Item  label="开票金额"  name="amount"  rules={[{ required: true, message: '输入开票金额!' }]}>
            <InputNumber prefix="￥" style={{ width: '100%' }} placeholder='输入开票金额' />
          </Form.Item>
          <Form.Item >
              <Space>
                <Button type="primary" htmlType="submit">
                  {!data.id?'新增':'更新'}
                </Button>
      
              </Space>
            </Form.Item>
        </Form>
          
          </div>
      </Modal>

        
        </>
  )
}
export default AddInvoicingItem;