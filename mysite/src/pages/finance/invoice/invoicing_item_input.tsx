


import { Button, Form, Input, InputNumber, Modal, Row, Space, Timeline, message } from "antd";
import { useEffect, useState } from "react";
import './common.css'
import { DeleteOutlined, PlusSquareOutlined } from "@ant-design/icons";

const formItemLayout = {
  labelCol: {
    xs: { span: 6 },
    sm: { span: 6 },
  },
  wrapperCol: {
    xs: { span: 18 },
    sm: { span: 18 },
  },
};
const InvoicingitemInput:React.FC<{onChange?:Function}> = ({onChange}) =>{

  const [temp,setTemp]=useState<any>([])
  const [form] = Form.useForm();
  const [obj,setObj]=useState<any>({})
  const [modal1, setModal1] = useState(false);
  useEffect(()=>{

    
    
  },[])

  const del = (title:any)=>{
    var d = temp.filter((e:any,index:any)=>{
      return title!=e.title
    })
    setTemp(d)
    onChange && onChange(d)
  }

  return (

  <div id='mi'>

    
    
      
    <PlusSquareOutlined  onClick={()=>setModal1(true)} style={{fontSize:'24px',marginTop:'5px'}
    }/>
    <Timeline style={{marginTop:'15px'}}>
      {
        temp.map((e:any,index:any)=>{
          return <Timeline.Item key={'timeline'+index}>
            <div style={{display:'flex',flexDirection:'row',alignItems:'center'}}>
              <DeleteOutlined style={{fontSize:'17px',paddingRight:'5px'}} size={100} color="red" onClick={()=>del(e.title)}/>
 
              名称：<span style={{fontWeight:'bold',marginRight:'5px'}}>{e.title}</span>
              金额：<span style={{fontWeight:'bold',marginRight:'5px'}}>{e.amount}</span>
              {
                e.unit!=null &&
                <>单位：<span style={{marginRight:'5px'}}>{e.unit}</span></>
              }
              {
                e.number!=null &&
                <>数量：<span style={{marginRight:'5px'}}>{e.number}</span></>
              }
              
              
            </div>
           
          </Timeline.Item>
        })
      }
    </Timeline>
    <Modal
    
            title='开票项目'
            style={{ top: 20 }}
            width={400}
    
            visible={modal1}
            onOk={() => setModal1(false)}
            onCancel={() => setModal1(false)}
            afterClose={()=>{
              
            }}
            footer={null}
          >
            <Form   {...formItemLayout} form={form}  style={{ width: '100%' }} initialValues={obj}>
    
          <Form.Item label="id" name="id" style={{display:'none'}}>
              <Input disabled/>
          </Form.Item>
          <Form.Item label="invoicingid" name="invoicingid" style={{display:'none'}}>
              <Input disabled/>
          </Form.Item>
          
          <Form.Item    label="开票项目" name="title" rules={[{ required: true, message: 'Please input!' }]}>
            <Input  placeholder="广告费、活动执行等"/>
          </Form.Item>
          <Form.Item  label="开票金额"  name="amount" style={{marginLeft:'5px'}} rules={[{ required: true, message: '输入开票金额!' }]}>
            <InputNumber prefix=""  placeholder='开票金额'  style={{width:'100%'}}/>
          </Form.Item>
          <Form.Item    label="单位" name="unit" style={{marginLeft:'5px'}} rules={[{ required: false, message: 'Please input!' }]}>
            <Input />
          </Form.Item>
          <Form.Item    label="数量" name="number" style={{marginLeft:'5px'}} rules={[{ required: false, message: 'Please input!' }]}>
            <Input />
          </Form.Item>
          
          <Form.Item style={{textAlign:'center'}}>
              <Space>
                <Button type="primary" onClick={() => {
                  form.validateFields() // 手动触发校验
                  .then((values) => {
                      var d = [...temp,values]
                      setTemp(d)
                      form.resetFields()
                      onChange && onChange(d)
                      setModal1(false)
                  })
                  .catch((errorInfo) => {
                      console.log('Validation Failed:', errorInfo);
                  });
                }}>
                  新增
                </Button>
                <Button onClick={()=>setModal1(false)}>取消</Button>
      
              </Space>
            </Form.Item>
        
       
          </Form>
          </Modal>
  </div>
  )
}

export default InvoicingitemInput


