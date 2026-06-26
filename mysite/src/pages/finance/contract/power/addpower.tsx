
import { Button, Form, Input, Modal, Space } from "antd";



import { saveflowrole } from "./service";
import Dictlist from "../../budget/dict/dictlist";
import Dictselect from "../../budget/dict/dictselect";
import Roleselect from "../../role/roleselect";
import { CONTRACT_AGENTID } from "../config";

// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
const Addrole:React.FC<{data?:any,onChange?:Function}> = ({data,onChange}) =>{
  const [form] = Form.useForm();

  const onReset = () => {
    form.resetFields();
  };
  const onFinish = (values: any) => {
 
    console.log(values)
    if (values.powers){
      if (Array.isArray(values.powers)) values.powers = values.powers.join(',')
    }
    // saveflowrole(values).then(res=>{
    //   if (res.errorMessage) {
    //     Modal.error({title: res.errorMessage})
    //   } else {
    //    onChange && onChange(res.data)
    //   }
    // })
  };

  return (

  <>
    <Form form={form} onFinish={onFinish}  initialValues={data}>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="角色：" name="role" rules={[{ required: true, message: 'Please input!' }]}>
          <Roleselect agentid={CONTRACT_AGENTID}/>
        </Form.Item>
        <Form.Item label="权限：" name="powers" rules={[{ required: true, message: 'Please input!' }]}>
          <Dictselect type='角色权限' multiple={true} />
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

  </>
  )
}

export default Addrole