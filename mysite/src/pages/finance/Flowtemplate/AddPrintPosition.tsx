
import { Button, Form, Input, Modal, Select, Space, message } from "antd";

import { useEffect, useRef, useState } from "react";


import AppSelect from "./AppSelect";
import Roleselect from "../role/roleselect";
import FinancePrintPosition from "./financePrintPosition";
import { saveprintposition } from "./service";
import UserAutocomplete from "../budget/common/userAutocomplete";
import TagSelect from "./components/TagSelect";







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
const AddPrintPosition:React.FC<{data:any,onChange?:Function,visible:boolean,onVisibleChange:Function}> = ({data,onChange,visible=false,onVisibleChange}) =>{
  const [form] = Form.useForm();
  const [showModal,setShowModal] = useState(visible)
  const [type,setType]=useState(data?.type||0)
 
  useEffect(()=>{
    setShowModal(visible)

  },[visible])
  const onReset = () => {
    form.resetFields();
  };
  
  const onFinish = (values: any) => {
 


    if (values.userid){
      values.userid=values.userid.value
    }
    console.log(values)
    saveprintposition(values).then((res:any)=>{
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
            <Form.Item label="应用" name="agentid" rules={[{ required: true, message: 'Please input!' }]}>
                <AppSelect multiple={false} />
            </Form.Item>
            <Form.Item  label="类型："  name="type"  rules={[{ required: true, message: 'Please input!' }]}>
                <Select defaultValue={0} options={[{value:0,label:'角色'},{value:3,label:'上级'},{value:20,label:'个人'},{value:8,label:'主体负责人'},{value:2,label:'标签'}]} onChange={(value)=>{
                  setType(value)
                }
                }/>
            </Form.Item>
            {
              type==0 && 
              <Form.Item label="角色" name="value"  rules={[{ required: true, message: 'Please input!' }]}>
                <Roleselect needAddItem={false}/>
              </Form.Item>
            }
            {
              type==20 && 
              <Form.Item  label="个人："  name="userid" >
                  <UserAutocomplete multiple={false}/>
              </Form.Item>
            }
            {
              type==2 && 
              <Form.Item label="标签" name="value" rules={[{ required: true, message: 'Please input!' }]}>
                <TagSelect/>
              </Form.Item>
            }
            
            <Form.Item label="位置" name="position" rules={[{ required: true, message: 'Please input!' }]}>
                <FinancePrintPosition data={data.position} />
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

export default AddPrintPosition