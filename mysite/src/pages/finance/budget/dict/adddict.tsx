
import { Button, Form, Input, Modal, Select, Space, message } from "antd";

import DepartmentTreeSelect from "../common/department_treeselect";
import { savedict } from "./service";
import MyUploadFile from "@/components/MyUploadFile";
import { CSSProperties, useEffect, useRef, useState } from "react";
import { getFromUrl, setToUrl } from "../../utils";
// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
const row: CSSProperties = {
  display: 'flex',
  flexDirection: 'row',
  width: '100%',
  padding: 0,
  gap: '2em',
};
const Adddict:React.FC<{data?:any,onChange?:Function,agentid:any}> = ({data,onChange,agentid}) =>{
  const [form] = Form.useForm();
  const uploadRef = useRef<any>(null);
  
    const [defaultImage, setDefaultImage] = useState<any>(data.fileurls?data.fileurls.split(',').map((url:any)=>{
  
      return getFromUrl(url)
    }):[])

useEffect(() => {
      if (data) {
        form.setFieldsValue({
          type: data.type,
          subtype: data.subtype,
        });
        setDefaultImage(data.fileurls?data.fileurls.split(',').map((url:any)=>{
          return getFromUrl(url)
        }):[])

      }
    }, [data]);
  const onReset = () => {
    form.resetFields();
  };
  const onFinish = (values: any) => {
     const uploads = uploadRef.current?.getFileList?.();
           if (uploads && uploads.length > 0) {
             values.fileurls = uploads.map((u: any) => setToUrl(u)).join(',');
           } else {
             values.fileurls = ''; // 明确赋空字符串
           }
    if (values.username && Array.isArray(values.username)){
      values.userid = values['username'].map((e:any)=>e.value).join(',')
      values.username = values['username'].map((e:any)=>e.label).join(',')
    }
    if (values.dept) values.dept = values.dept.join(',')
    values.agentid = agentid
    savedict(values).then(res=>{
      if (res.errorMessage) {
        Modal.error({title: res.errorMessage})
      } else {
       onChange && onChange(res.data)
      }
    })
  };
  const deptOnChange = (e:any)=>{
    console.log(e)
  }
  return (

  <>
    <Form form={form} onFinish={onFinish}  initialValues={data}>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="类型：" name="type" rules={[{ required: true, message: 'Please input!' }]}>
            <Input />
        </Form.Item>
        <Form.Item label="名称" name="label" rules={[{ required: true, message: 'Please input!' }]}>
          <Input placeholder="输入名称"/>
        </Form.Item>
        <Form.Item label="子类型" name="subtype" rules={[{ required: false, message: 'Please input!' }]}>
          <Input />
        </Form.Item>
        <Form.Item label="key值" name="value" rules={[{ required: false, message: 'Please input!' }]}>
          <Input placeholder="可不填，会自动递增" disabled={data.value?true:false}/>
        </Form.Item>
        <Form.Item label="父节点标签" name="parent" rules={[{ required: false, message: 'Please input!' }]}>
          <Input />
        </Form.Item>
        <Form.Item label="部门" name="dept" rules={[{ required: false, message: '请选择部门!' }]}>
          <DepartmentTreeSelect defaultValue={data.dept} onChange={deptOnChange}/>
        </Form.Item>
        <div style={row}>

          <Form.Item label="附件：" style={{ flex: 1 }}>
            <MyUploadFile
              ref={uploadRef}
              name="fileurls"
              max={1}
              multiple={false}
              accept="*/*"
              maxSize={100}
              listType="picture-card"
              defaultImage={defaultImage}
              uploadPath="contract"
              uploadType={3}
            />
          </Form.Item>
        </div>
        

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

export default Adddict