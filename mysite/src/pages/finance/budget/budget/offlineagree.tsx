
import MyUploadFile from "@/components/MyUploadFile";
import { Button, Form, Input, Modal, Space, message } from "antd";
import { CSSProperties, useRef, useState } from "react";
import { getFromUrl, setToUrl } from "../../utils";
import TextArea from "antd/lib/input/TextArea";

// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
const row:CSSProperties = {
  display:'flex',
  flexDirection: 'row',
  // justifyContent:'space-between',

  width:'100%',
  padding:0,
  gap: '2em',
}
// 线下上会通过，
const Offlineagree:React.FC<{data?:any,onChange?:Function}> = ({data,onChange}) =>{
  const [form] = Form.useForm();
  const dateFormat = 'YYYY-MM-DD'
  const [defaultImage, setDefaultImage] = useState([])
  const uploadRef = useRef<AnimationPlayState>();
  const onReset = () => {
    form.resetFields();
  };
  const onFinish = (values: any) => {
    const uploads = uploadRef?.current?.getFileList();
    
    if (uploads) values.fileurls = uploads.map((u:any)=>{
      return setToUrl(u)
    }).join(',')

    onChange && onChange(values)
    
  };

  return (

  <>
    <Form form={form} onFinish={onFinish}  initialValues={data}>
        <Form.Item label="projectid" name="projectid" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="thirdNo" name="thirdNo" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="会议：" name="speech" rules={[{ required: true, message: 'Please input!' }]}>
            <TextArea rows={4} placeholder="输入会议名称和日期" />
        </Form.Item>
        <div style={row}>
          <Form.Item style={{paddingLeft:'10px'}} >
            <MyUploadFile
            
              name="fileurls"
              label="会议相关文件："
              max={20}
              multiple={false}
              accept="*/*"
              maxSize={100}
              listType="picture-card"
              defaultImage={defaultImage}
              uploadPath="contract"
              uploadType={2}
              ref={uploadRef}
            />
          </Form.Item>
        </div>

        <Form.Item {...tailLayout}>
          <Space>
            <Button type="primary" htmlType="submit">
              提 交
            </Button>

          </Space>
        </Form.Item>
    </Form>
    
  </>
  )
}

export default Offlineagree