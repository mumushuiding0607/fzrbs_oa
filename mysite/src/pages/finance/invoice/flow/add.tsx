
import { Button, Form, Input, Modal, Select, Space, message } from "antd";

import { useEffect, useRef, useState } from "react";
import UserAutocomplete from "../../budget/common/userAutocomplete";
import DepartmentTreeSelect from "../../budget/common/department_treeselect";
import { save } from "./service";
import TemplatesSelect from "../../budget/flow/templates-select";
import { INVOICE_AGENTID } from "../config";
import Dictselect from "../../budget/dict/dictselect";
import RangeNumber from "../../contract/RangeNumber";



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
const Add:React.FC<{data:any,onChange?:Function,visible:boolean,onVisibleChange:Function}> = ({data,onChange,visible=false,onVisibleChange}) =>{
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
        var temp = values.templateid
        values.templatename = temp.label
        values.templateid = temp.value
        
      }else if (values.templateid instanceof Array){
        values.templateid = values.templateid.join(',')
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
        values.username = values.uids.map((e:any)=>e.label).join(',')
        values.uids = values.uids.map((e:any)=>e.value).join(',')
        
      } else if (values.uids instanceof Object) {
        values.username = values.uids.label
        values.uids = values.uids.value
        
        
        
      }
    }
    
    if (values.contract&& values.contract instanceof Array){
      values.contract = values.contract.map((e:any)=>e.value).join(',')
    }
    if (values.types&& values.types instanceof Array){
      values.types = values.types.map((e:any)=>e.value).join(',')
    }
    if (values.amount && values.amount.length>0){
      if (values.amount[0]!=null) values.lamount = values.amount[0]
      if (values.amount[1]!=null) values.hamount = values.amount[1]
    }
    delete values.amount
    save(values).then((res:any)=>{
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
            <Form.Item  label="流程：" name="templateid"   rules={[{ required: true, message: 'Please input!' }]}>
              <TemplatesSelect multiple={false}  agentId={INVOICE_AGENTID}/>
            </Form.Item>
            
            <Form.Item label="部门："  name="dids" >
              <DepartmentTreeSelect multiple={true} defaultValue={data.dids} maxTagCount={2}/>
            </Form.Item>
            <Form.Item  label="用户："  name="uids" >
                <UserAutocomplete multiple={true}/>
            </Form.Item>
            <Form.Item   label="审批类型：" name="types" rules={[{ required: false, message: 'Please input!' }]}>
              <Dictselect type={"开票审批"} multiple={true}  needAddItem={false}/>
            </Form.Item>
            <Form.Item   label="合同业务：" name="contract" rules={[{ required: false, message: 'Please input!' }]}>
              <Dictselect type={"合同业务类型"} multiple={true}  needAddItem={false}/>
            </Form.Item>
            <Form.Item   label="有无合同：" name="hascontract" rules={[{ required: false, message: 'Please input!' }]}>
              <Select options={[
                  {
                    value: 0,
                    label: '无',
                  },
                  {
                    value: 1,
                    label: '有',
                  }
                ]}
              />
            </Form.Item>
            <Form.Item label="开票金额："  name="amount" >
              <RangeNumber></RangeNumber>
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

export default Add