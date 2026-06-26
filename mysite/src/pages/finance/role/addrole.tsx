
import { Button, Form, Input, Modal, Select, Space, Switch, message } from "antd";



import { getapps, saveflowrole } from "./service";
import Roleselect from "./roleselect";
import UserAutocomplete from "../budget/common/userAutocomplete";
import DepartmentTreeSelect from "../budget/common/department_treeselect";
import { ProFormSelect } from "@ant-design/pro-components";
import { useEffect, useState } from "react";
import PayerSelect from "./payerSelect";
import Dictselect from "../budget/dict/dictselect";
import Orgcascade from "../order/orgcascade";
import { unset } from "lodash";


// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
const formItemLayout = {
  labelCol: {
    xs: { span: 3 },
    sm: { span: 3 },
  },
  wrapperCol: {
    xs: { span: 24 },
    sm: { span: 24 },
  },
};
const Addrole:React.FC<{data?:any,onChange?:Function,agentid:any}> = ({data={type:0},onChange,agentid}) =>{
  const [form] = Form.useForm();
  const [appDict,setAppDict]=useState<any>([])
  const onReset = () => {
    form.resetFields();
  };
  
  useEffect( ()=>{
      getapps().then((res:any)=>{ 
        setAppDict(res.data||[])
      })
    },[])
  const onFinish = (values: any) => {
   
    if (values.userid&&values.userid.value){
      values.username = values.userid?.label
      values.userid = values.userid?.value
      
    }
  
    if (values.agent && Array.isArray(values.agent)){
      values.agent = values.agent.join(',')
    }
    if (values.company && Array.isArray(values.company)){
      values.company = values.company.map((e:any)=>e.value).join(',')
    }

    if (values.orgid&& Array.isArray(values.orgid)){
      
      values.orgid = `${values.orgid.join(',')}`
    }else{
      delete values.orgid
    }
    console.log("values33:",values)
    
    if (values.dept) {
      values.dept = values.dept.map((e: any) => typeof e === 'string' ? e : e?.value || e).join(',');
    }
    saveflowrole(values).then(res=>{
          if (res.errorMessage) {
            Modal.error({title: res.errorMessage})
          } else {
            onChange && onChange(res.data)
          }
        })
    
  };

  return (

  <>
    <Form {...formItemLayout} form={form} onFinish={onFinish}  initialValues={data}>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="角色：" name="role" rules={[{ required: true, message: 'Please input!' }]}>
          <Roleselect agentid={agentid} />
        </Form.Item>
        <Form.Item label="类别：" name="type" rules={[{ required: false, message: 'Please input!' }]}>
          <Select
            defaultValue={0}
            defaultActiveFirstOption={true}
      
            options={[
              {
                value: 0,
                label: '审批',
              },
              {
                value: 1,
                label: '抄送',
              }
            ]}
          />
        </Form.Item>
  
        <Form.Item label="用户：" name="username" style={{display:'none'}}>
          <Input />
        </Form.Item>
        <Form.Item label="用户：" name="userid" rules={[{ required: true, message: 'Please input!' }]}>
          <UserAutocomplete multiple={false}  />
        </Form.Item>

        <Form.Item label="部门：" name="dept" rules={[{ required: true, message: '请选择部门!' }]}>
          <DepartmentTreeSelect  defaultValue={data.dept} maxTagCount={2} showTreeCheckStrictly={true} />
        </Form.Item>

        <Form.Item label="主体：" name="company" rules={[{ required: false, message: 'Please input!' }]}>
          <PayerSelect  multiple={true}/>
        </Form.Item>
        <Form.Item
  
          label="刊物"
          name="publicationid"
          rules={[{ required: false, message: '请选择发布平台' }]}
        >
          <Dictselect
            type="刊物"
            multiple={true}
            needAddItem={true}
            placeholder="选择发布平台"
            onChange={(value: any, item: any) => {
   
              // 切换发布平台时清空版位选择
              var publicationid = ''
              if (value){
                publicationid = value.map((e:any)=>e.value).join(',')
              }
              form.setFieldsValue({
                publicationid
              });
              
            }}
          />
        </Form.Item>
        <Form.Item
  
          label="行业部门"
          name="orgid"
          rules={[{ required: false, message: '请选择行业部门' }]}
        >
          <Orgcascade
            key="AI_Org_ID"
            multiple={true}

          />
        </Form.Item>
  
        
          <ProFormSelect.SearchSelect
                  name="agent"
                  label="应用"          
                  fieldProps={{
                  labelInValue: false,
                  style: {
                      minWidth: 140,
                  },
                  }}
                  rules={[{ required: true, message: '请选择应用!' }]}
                  valueEnum={appDict}
              />
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

export default Addrole