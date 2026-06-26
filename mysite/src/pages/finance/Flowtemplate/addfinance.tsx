
import { Button, Form, Input, Modal, Select, Space, message } from "antd";

import { useEffect, useRef, useState } from "react";

import { save, savefinanceflow } from "./service";
import TemplatesSelect from "../budget/flow/templates-select";
import { FINANCE_AGENTID } from "../config";
import Dictselect from "../budget/dict/dictselect";
import DepartmentTreeSelect from "../budget/common/department_treeselect";
import UserAutocomplete from "../budget/common/userAutocomplete";
import RangeNumber from "../contract/RangeNumber";
import PayerSelect from "../role/payerSelect";





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
const AddFinanceTemplate:React.FC<{data:any,onChange?:Function,visible:boolean,onVisibleChange:Function}> = ({data,onChange,visible=false,onVisibleChange}) =>{
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
    if (values.company && Array.isArray(values.company)){
      values.company = values.company.map((e:any)=>e.value).join(',')
    }
    if (values.amount){
      values.lamount = values.amount[0]?values.amount[0] : 0
      values.hamount = values.amount[1]?values.amount[1] : 0
    }
    delete values.amount
    console.log(values)
    savefinanceflow(values).then((res:any)=>{
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
            <Form.Item   label="审批类型：" name="type" rules={[{ required: true, message: 'Please input!' }]}>
              <Dictselect  type={"付款审批类型"} multiple={false}  needAddItem={false}/>
            </Form.Item>
            <Form.Item label="付款金额："  name="amount" >
              <RangeNumber></RangeNumber>
            </Form.Item>
            <Form.Item label="付款单位：" name="company" rules={[{ required: false, message: 'Please input!' }]}>
              <PayerSelect  multiple={true}/>
            </Form.Item>
            <Form.Item label="部门："  name="dids" >
              <DepartmentTreeSelect showTreeCheckStrictly={true} multiple={true} defaultValue={data.dids} maxTagCount={2}/>
            </Form.Item>
            <Form.Item  label="用户："  name="uids" >
                <UserAutocomplete valueKey="id" multiple={true}/>
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

export default AddFinanceTemplate