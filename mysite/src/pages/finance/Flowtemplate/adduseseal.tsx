
import { Button, Form, Input, Modal, Select, Space, message } from "antd";

import { useEffect, useRef, useState } from "react";

import { save, savefinanceflow, saveusesealflow, saveyxkhflow } from "./service";
import TemplatesSelect from "../budget/flow/templates-select";
import { FINANCE_AGENTID } from "../config";
import Dictselect from "../budget/dict/dictselect";
import DepartmentTreeSelect from "../budget/common/department_treeselect";
import UserAutocomplete from "../budget/common/userAutocomplete";





// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
const formItemLayout = {
  labelCol: {
    xs: { span: 4 },
    sm: { span: 4 },
  },
  wrapperCol: {
    xs: { span: 20 },
    sm: { span: 20 },
  },
};
const AddUsesealTemplate:React.FC<{data:any,onChange?:Function,visible:boolean,onVisibleChange:Function}> = ({data,onChange,visible=false,onVisibleChange}) =>{
  const [form] = Form.useForm();
  const [showModal,setShowModal] = useState(visible)
  useEffect(()=>{
    setShowModal(visible)
    
  },[visible])
  const onReset = () => {
    form.resetFields();
  };
  
  const onFinish = (values: any) => {
 
    if (values.templateid){
      if (values.templateid instanceof Object) {
        values.templateid = values.templateid.value
        
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
        
        
      } 
    }
    if (values.assesstype){
      if (values.assesstype instanceof Array){
        values.assesstype = values.assesstype.map((e:any)=>e.value).join(',')
      }
    }
    if (values.type){
      if (values.type instanceof Array){
        values.type = values.type.map((e:any)=>e.value).join(',')
      }
    }

    console.log(values)
    saveusesealflow(values).then((res:any)=>{
      if (res.errorMessage) {
        Modal.error({title: res.errorMessage})
      } else {
        onChange && onChange(res.data)
        onVisibleChange(false)
      }
    })
  };

  return (

  <>
  <Modal
        title="流程"
        style={{ top: 20, }}
        visible={visible}
        onOk={() => {
          onVisibleChange(false)
        }}
        onCancel={() => onVisibleChange(false)}
        footer={null}
      >
        <Form {...formItemLayout}  form={form} onFinish={onFinish}  initialValues={data}>
            <Form.Item label="id" name="id" style={{display:'none'}}>
                <Input disabled/>
            </Form.Item>
            <Form.Item  label="模板id：" name="templateid"   rules={[{ required: true, message: 'Please input!' }]}>
              <TemplatesSelect multiple={false}  agentId={FINANCE_AGENTID}/>
            </Form.Item>
            <Form.Item label="名称" name="templatename" rules={[{ required: true, message: 'Please input!' }]}>
                <Input/>
            </Form.Item>
            <Form.Item   label="申请类别：" name="type" rules={[{ required: true, message: 'Please input!' }]}>
              <Dictselect  type={"用印申请类别"} multiple={false}  needAddItem={false}/>
            </Form.Item>
            <Form.Item   label="流程类别：" name="flowtype" rules={[{ required: true, message: 'Please input!' }]}>
              <Dictselect  type={"用印流程类别"} multiple={false}  needAddItem={false}/>
            </Form.Item>
            <Form.Item   label="金额：" name="amounts" rules={[{ required: true, message: 'Please input!' }]}>
              <Dictselect  type={"用印协议金额"} multiple={false}  needAddItem={false}/>
            </Form.Item>
            
        
            <Form.Item label="部门："  name="dids" >
              <DepartmentTreeSelect showTreeCheckStrictly={true} multiple={true} defaultValue={data.dids} maxTagCount={2}/>
            </Form.Item>
            <Form.Item  label="用户："  name="uids" >
                <UserAutocomplete valueKey={'id'} multiple={true}/>
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
      </Modal>
    

  </>
  )
}

export default AddUsesealTemplate