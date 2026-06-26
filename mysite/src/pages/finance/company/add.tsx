


import { Button, Form, Input, InputNumber, Modal, Row, Space, message } from "antd";

import { useEffect, useRef, useState } from "react";
import MultipleInput from "./multiple-input";
import { getcompany, savecompany } from "./service";


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
const Add:React.FC<{id:any,onChange?:Function,agentid?:any,visible:boolean,onVisibleChange:Function,sign?:any,update?:boolean,company?:any}> = ({update=true,company,sign,id,onChange,agentid,visible=false,onVisibleChange}) =>{
  const [form] = Form.useForm();
  const [showmodal,setShowModal] = useState(visible)
  const [data,setData]= useState<any>({})
  var [rk,setRk]=useState(0)
  useEffect(()=>{
    console.log("data:",data)
    setShowModal(visible)
    
    if (id){
      getcompany({id}).then((res:any)=>{
        if (res){
          setData(res[0]||{})
          setRk(++rk)
        }
      })
    }else{
      setData({company})
    }
    
  },[visible])


  const onFinish = (values: any) => {
 
    

    if (values.bankaccount && values.bankaccount.length>0&&values.bankaccount.join){
      values.bankaccount = values.bankaccount.join(',')
    }else{
      values.bankaccount = ''
    }
    
    values.sign=sign
    values.agentid=agentid
    if (values.username){
      values.userid = values.username.value
      values.username = values.username.label
    }
    // company字段去掉空格
    values.company = values.company.trim()
    savecompany(values).then((res)=>{
      
      if (res.errorMessage) {
        Modal.error({
          title: '报错',
          content: res.errorMessage,
        });
      } else {
        
        onChange && onChange(res.data)
        onVisibleChange &&onVisibleChange(false)
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
        <Form.Item label="sign" name="sign" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>

          <div style={{ color:'gray',fontSize:'12px',paddingLeft:'80px'}}>公司名称不可修改，若名称有误请新增</div>
          <Form.Item
            name="company"
            label="公司名称"
            rules={[{ required: true, message: '请输入公司名称!' }]}
          >
            <Input style={{ width: '100%' }} disabled={data?.id?true:false} />
          </Form.Item>
          <Form.Item
            name="code"
            label="信用代码"
            
          >
            <Input style={{ width: '100%' }} />
        </Form.Item>
        <Form.Item
            name="address"
            label="公司地址"
            
          >
            <Input style={{ width: '100%' }} />
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
            name="contacts"
            label="联系电话"
           
          >
            <Input/>
        </Form.Item>
        <Form.Item
            name="contactor"
            label="联系人"
           
          >
            <Input/>
        </Form.Item>
        <Form.Item
            name="bankaccount"
            label="开户信息"
            
          >
            <MultipleInput placeholder={['开户行','银行卡号']}/>
        </Form.Item>
          
        

        {
          update && 
          <Form.Item>
          <Space style={{marginLeft:'10px'}}>
            <Button type="primary" htmlType="submit">
              {data?.id?'更新':'创建'}
            </Button>
            {
              data.id&&data.id>0&&<Button type="primary" danger onClick={()=>{
                var company = data.company

                if(company.includes('（已作废）')){
                  company = company.replaceAll('（已作废）','')
                }else{
                  company = '（已作废）'+company
                }
        
                // 确定作废吗？
                Modal.confirm({
                  title: '确定执行吗？',
                  okText: '确定',
                  cancelText: '取消',
                  onOk: () => {
                    

                    savecompany({id:data.id,company}).then((res)=>{
      
                          if (res.errorMessage) {
                            Modal.error({
                              title: '报错',
                              content: res.errorMessage,
                            });
                          } else {
                            
                            onChange && onChange(res.data)
                            onVisibleChange &&onVisibleChange(false)
                          }
                          
                        })
                  }
                })

              }}>
              {data.company?.includes('（已作废）')?'取消作废':'作废'}
            </Button>
            }
          </Space>
        </Form.Item>
        }
        
    </Form>
    </Modal>
    

  </>
  )
}

export default Add


