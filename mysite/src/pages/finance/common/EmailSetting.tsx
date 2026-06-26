
import { Button, Form, Input, InputNumber, Modal, Space } from "antd"
import { useEffect, useState } from "react"

import { request } from "umi"
const formItemLayout = {
  labelCol: {
    xs: { span: 4 },
    sm: { span: 4 },
  },
  wrapperCol: {
    xs: { span: 24 },
    sm: { span: 24 },
  },
};
const EmailSetting:React.FC<{visible:boolean,onVisibleChange:Function,agentid?:any}> = ({visible,onVisibleChange,agentid}) =>{

  const [form] = Form.useForm();
  const [showmodal,setShowModal] = useState(visible)
  const [data,setData]= useState<any>({})
  const [dict,setDict]=useState<any>({})
  var [rk,setRk]=useState(0)
  useEffect(()=>{
    setShowModal(visible)
    request<{
      data:any
    }>('/api/budget/getbykeyword',{
      method:'GET',
      params:{keyword:'发件邮箱'}
    }).then((res:any)=>{
      if (res && res[0]){
        if (res[0].label){
          setDict(res[0])
          setData(JSON.parse(res[0].label))
        }
      }
    })
    
  },[visible])

  
  const onFinish = (values: any) => {
 
    dict.agentid = agentid
    dict.type = '发件邮箱'
    dict.label = JSON.stringify(values)
    request('/api/budget/savedict', {
      data:dict,
      method: 'POST',
    }).then((res:any)=>{
      if (res.errorMessage){
        Modal.error({
          title: res.errorMessage,
        });
      }else{
        Modal.success({
          title: '设置成功',
        });
      }
    })
  }
  return (

  <>
  <Modal
    title="添加"
    style={{ top: 20, }}
    visible={showmodal}
    onOk={() => {
      onVisibleChange(false)
    }}
    onCancel={() => onVisibleChange(false)}
    footer={null}
    >
    <Form key={rk} {...formItemLayout} form={form} onFinish={onFinish}  initialValues={data}>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="agentid" name="agentid" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
          
          <Form.Item
            name="smtp"
            label="smtp"
            rules={[{ required: true, message: 'smtp.qq.com' }]}
          >
            <Input style={{ width: '100%' }} disabled={data.id?true:false} />
          </Form.Item>
          <Form.Item
            name="port"
            label="端口"
            
          >
            <InputNumber style={{ width: '100%' }} placeholder="端口"/>
        </Form.Item>
        
        <Form.Item
            name="email"
            label="邮箱"
            rules={[
              {
                type: 'email',
                message: '请输入有效的邮箱地址!',
              },
              {
                required: false,
                message: '请输入邮箱地址!',
              },
            ]}
          >
            <Input style={{ width: '100%' }} placeholder="请输入邮箱地址" />
        </Form.Item>
        <Form.Item
            name="code"
            label="授权码"
            
          >
            <Input style={{ width: '100%' }} placeholder="邮箱对应的授权码"/>
        </Form.Item>
          <Form.Item>
          <Space>
            <Button type="primary" htmlType="submit">
              提交
            </Button>
          </Space>
        </Form.Item>
        
        
    </Form>
    </Modal>
    

  </>
  )
}
export default EmailSetting