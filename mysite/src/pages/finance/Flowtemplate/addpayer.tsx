
import { Button, Form, Input, Modal, Select, Space, message } from "antd";

import { useEffect, useRef, useState } from "react";

import { save, savefinanceflow, savepayer } from "./service";

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
const AddPayer:React.FC<{data:any,onChange?:Function,visible:boolean,onVisibleChange:Function}> = ({data,onChange,visible=false,onVisibleChange}) =>{
  const [form] = Form.useForm();
  const [showModal,setShowModal] = useState(visible)
  useEffect(()=>{
    setShowModal(visible)
    
  },[visible])
  const onReset = () => {
    form.resetFields();
  };
  
  const onFinish = (values: any) => {
 

    if (values.crossdept){
      if (values.crossdept instanceof Array){
        values.crossdept = values.crossdept.join(',')
      } else if (values.crossdept instanceof Object) {
        values.crossdept = values.crossdept.value
      }
    }
    if (values.userid){
      if (values.userid instanceof Object) {
        values.username = values.userid.label
        values.uid = values.userid.id
        values.userid = values.userid.value
        
      }
    }
    
    console.log(values)
    savepayer(values).then((res:any)=>{
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
            <Form.Item label="付款单位" name="company" rules={[{ required: true, message: 'Please input!' }]}>
                <Input/>
            </Form.Item>
            <Form.Item  label="负责人："  name="userid" >
                <UserAutocomplete multiple={false}/>
            </Form.Item>
            <Form.Item label="所跨部门："  name="crossdept"  rules={[{ required: false, message: 'Please input!' }]}>
              <DepartmentTreeSelect  multiple={false} defaultValue={data.crossdept} maxTagCount={1}/>
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

export default AddPayer