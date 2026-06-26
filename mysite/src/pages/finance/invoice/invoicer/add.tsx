
import { Button, Form, Input, Modal, Space, Switch, message } from "antd";


import UserAutocomplete from "../../budget/common/userAutocomplete";
import DepartmentTreeSelect from "../../budget/common/department_treeselect";
import Companyselect from "../../company/companyselect";
import { saveinvoicer } from "./service";




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
const Addrole:React.FC<{data?:any,onChange?:Function}> = ({data,onChange}) =>{
  const [form] = Form.useForm();
  const onReset = () => {
    form.resetFields();
  };
  const onFinish = (values: any) => {
 
    if (values.username && !Array.isArray(values.username)){
      values.userid = values['username'].value
      values.username = values['username'].label
    }
    

    
    if (values.dept) values.dept = values.dept.map((e:any)=>{
      if (typeof e == 'string'){
        return e
      } else if (e && e.value){
        return e.value
      }
      return e
    }).join(',')
    if(values.companyids&&values.companyids.map) values.companyids=values.companyids.map((e:any)=>e.value).join(',')
    console.log(values)
    saveinvoicer(values).then(res=>{
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
        <Form.Item label="用户" name="userid" style={{display:'none'}}>
          <Input />
        </Form.Item>
        <Form.Item label="用户：" name="username" rules={[{ required: true, message: 'Please input!' }]}>
          <UserAutocomplete multiple={false}  />
        </Form.Item>
        <Form.Item label="公司" name="companyids" rules={[{ required: false, message: '请选择部门!' }]}>
          <Companyselect  multiple={true}></Companyselect>
        </Form.Item>
        <Form.Item label="部门" name="dept" rules={[{ required: true, message: '请选择部门!' }]}>
          <DepartmentTreeSelect  defaultValue={data.dept} maxTagCount={2} showTreeCheckStrictly={true} initTreeCheckStrictly={data.id?false:true}/>
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