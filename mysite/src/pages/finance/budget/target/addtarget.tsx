
import { Button, Form, Input, InputNumber, Modal, Space, message } from "antd";
import { useEffect, useRef, useState } from "react";
import DepartmentTreeSelect from "../common/department_treeselect";
import { getsettargetdeparts, savetarget } from "./service";

// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
const Addtarget:React.FC<{data?:any,onChange?:Function}> = ({data,onChange}) =>{
  const [form] = Form.useForm();
  var [refdt,setRefdt] = useState(0)
  var [formKey,setFormKey] = useState(0)
  const [depts, setDepts] = useState('')
  const year = new Date().getFullYear()
  const [d,setD] =useState({})
  data.year = data.year || year
  
  useEffect(()=>{
    if (data.dept && typeof data.dept == 'string' ) data.dept = data.dept.split(',')
    setD(data)
    setFormKey(++formKey)
    console.log('data:',d)
    getsettargetdeparts({year:new Date().getFullYear()}).then(res=>{
      
      if (res.dept ) {
       
        if (data.id && data.dept) {
          var arr = data.dept
          res.dept = res.dept.split(',').filter(temp=>{

            return !arr.includes(temp)
          }).join(',')
          

        }

        setDepts(res.dept)
        
        setRefdt(++refdt)
      }
      
    })
  },[])
  const onReset = () => {
    form.resetFields();
  };
  const onFinish = (values: any) => {
 
    if (values.dept) values.dept = values.dept.join(',')
    savetarget(values).then(res=>{
      if (res.errorMessage) {
        Modal.error({title: res.errorMessage})
      } else {
       onChange && onChange(res.data)
      }
    })
  };

  return (

  <>
    <Form form={form} onFinish={onFinish} key={formKey} initialValues={d}>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="年度" name="year" rules={[{ required: true, message: '请输入年份' }]}>
          <InputNumber  style={{ width: '100%' }} addonAfter={<Button type="primary" onClick={()=>{
            getsettargetdeparts({year: form.getFieldValue('year')}).then(res=>{
              setDepts(res.dept)
              setRefdt(++refdt)
              
            })
          }} >刷新部门</Button>}/>
        </Form.Item>
        <Form.Item label="标题" name="title"  rules={[{ required: true, message: '请输入标题' }]}>
          <Input  style={{ width: '100%' }} placeholder="如：日报、晚报、新媒体等等" />
        </Form.Item>
        <Form.Item label="收入" name="income" rules={[{ required: true, message: '请输入金额!' }]}>
          <InputNumber prefix="￥" style={{ width: '100%' }} />
        </Form.Item>
        <Form.Item label="利润" name="profit"  rules={[{ required: true, message: '请输入金额!' }]}>
          <InputNumber prefix="￥" style={{ width: '100%' }} />
        </Form.Item>
        <Form.Item label="部门" name="dept" rules={[{ required: true, message: '请选择部门!' }]}>
          <DepartmentTreeSelect key={refdt} defaultValue={data.dept}  disableValues={depts} />
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

export default Addtarget