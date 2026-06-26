


import { Button, Divider, Form, Input, InputNumber, Modal, Row, Select, Space, Timeline, message } from "antd";
import { CSSProperties, useEffect, useState } from "react";
import './common.css'
import { getcompany } from "./service";
import BankAccount from "./BankAccount";
import { set } from "lodash";

const row:CSSProperties = {
  display:'flex',
  flexDirection: 'row',
  width:'100%',
  padding:0,
  gap: '2em',
}
const formitem:CSSProperties={
  width:'50%'
}
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
const CompanyinfoSelect:React.FC<{id:any,obj?:any,onChange?:Function}> = ({id,obj=undefined,onChange}) =>{

  const [data,setData]=useState<any>({id:0})
  var [refreshkey,setRefreshkey]=useState(0)
  const [banks,setBanks]=useState<any[]>([])
  const [isEmpty,setIsEmpty]=useState(true)
  const [form] = Form.useForm();
    useEffect(()=>{
     
      console.log('CompanyinfoSelect id:',id)
      if (obj&&obj.id){
        console.log('CompanyinfoSelect obj:',obj)
        setData(obj)
        setRefreshkey(refreshkey+1)
      }else if (id&&/^\d+$/.test(id)) {
      

        getcompany({id}).then((res:any)=>{
          if (res&&res[0]){
            var temp = res[0]
            
            if (temp.bankaccount&&temp.bankaccount!='Array' && temp.bankaccount.split){
              var tbanks = temp.bankaccount.split(',').map((e:any)=>{
                return {value:e,label:e}
              })
              setBanks(tbanks)
              setIsEmpty(false)
              temp.bankaccount = tbanks[0].value
            }

            setData(temp||{})
            setRefreshkey(++refreshkey)
            onChange&&onChange(temp)
          }
        })
      }
      
    },[id,obj])
  const onFinish = (values: any) => {

  }
  const onBankChange = (e:any)=>{
    data.bankaccount = e
    setData(data)
    onChange&&onChange(data)
  }
  const onCodeChange = (e:any)=>{
   data.code = e.target.value
   onChange&&onChange(data)
  }
  const onAddressChange = (e:any)=>{
    data.address = e.target.value
    onChange&&onChange(data)
  }
  const onEmailChange = (e:any)=>{
    data.email = e.target.value
    onChange&&onChange(data)
  }
  const onContactsChange = (e:any)=>{
    data.contacts = e.target.value
    onChange&&onChange(data)
  }
  const onBankAccountChange = (e:any)=>{
    data.bankaccount = e
    
    onChange&&onChange(data)
  }

  return (

  <div key={refreshkey}>
      {
        id>0 &&
        <Form   form={form} {...formItemLayout} onFinish={onFinish}  initialValues={data}>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <div style={row}>
          <Form.Item style={formitem}
              name="company"
              label="公司名称"
              rules={[{ required: true, message: '请输入公司名称!' }]}
            >
              <Input  disabled={data.id?true:false} />
            </Form.Item>
            <Form.Item style={formitem}
              name="code"
              label="信用代码"
              rules={[{ required: true, message: 'Please input!' }]}
            >
              <Input  onChange={onCodeChange}/>
          </Form.Item>
        </div>
        <div style={row}>
          <Form.Item style={formitem}
              name="address"
              label="公司地址"
              rules={[{ required: false, message: 'Please input!' }]}
            >
              <Input  onChange={onAddressChange}/>
          </Form.Item>
          <Form.Item style={formitem}
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
              <Input onChange={onEmailChange}  placeholder="请输入邮箱地址" />
          </Form.Item>
        </div>
        <div style={row}>
          <Form.Item
              name="contacts" style={formitem}
              label="联系电话"
              rules={[{ required: false, message: 'Please input!' }]}
            >
              <Input onChange={onContactsChange} />
          </Form.Item>
          {
            isEmpty && 
            <Form.Item style={formitem}
            name="bankaccount"
            label="开户信息"
            rules={[{ required: true, message: 'Please input!' }]}
          >
            <BankAccount onChange={onBankAccountChange}/>
        </Form.Item>
          }
          
          {
            !isEmpty &&<Form.Item style={formitem}
                name="bankaccount"
                label="开户信息"
                
              >
                <Select onChange={onBankChange}  options={banks} dropdownRender={menu=>(
                  <>
                  {menu}
                  <Space style={{ padding: '0 8px 4px' }}>
                    <Button type="text" danger  onClick={()=>setIsEmpty(true)}>
                      其它
                    </Button>
                  </Space>
                  </>
                  
                  
                )}></Select>
            </Form.Item>
          }
        </div>
        
          
        


        
    </Form>
      }


  </div>
  )
}

export default CompanyinfoSelect


