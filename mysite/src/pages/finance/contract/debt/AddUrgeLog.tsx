import MyUploadFile from "@/components/MyUploadFile";
import { Button, DatePicker, Form, Input, Modal, Space, message,Radio } from "antd";
import { CSSProperties, useEffect, useRef, useState } from "react";
import { getFromUrl, setToUrl } from "../../utils";
import TextArea from "antd/lib/input/TextArea";
import { delurgelog, saveurgelog } from "./service";
import dayjs from 'dayjs';


const row: CSSProperties = {
  display: 'flex',
  flexDirection: 'row',
  width: '100%',
  padding: 0,
  gap: '2em',
};

// 弹窗表单组件
interface AddUrgeLogProps {
  visible: boolean;
  data?: any;
  onClose: () => void;
  onChange: Function;
}

const AddUrgeLog: React.FC<AddUrgeLogProps> = ({ visible, data, onClose, onChange }) => {
  const [form] = Form.useForm();
  const uploadRef = useRef<any>(null);
  const [defaultImage, setDefaultImage] = useState<any>(data.fileurls?data.fileurls.split(',').map((url:any)=>{

    return getFromUrl(url)
  }):[])
  // 处置
  
  useEffect(() => {
    if (visible && data) {
      
      // 设置表单字段值
      form.setFieldsValue({
        ...data,
        date: data.date ? dayjs(data.date) : null,
      });
      setDefaultImage(data.fileurls?data.fileurls.split(',').map((url:any)=>{

        return getFromUrl(url)
      }):[])
      
    }
  }, [visible, data]);




  const handleOk = () => {
    form.validateFields().then((values) => {
      const uploads = uploadRef.current?.getFileList?.();
      if (uploads && uploads.length > 0) {
        values.fileurls = uploads.map((u: any) => setToUrl(u)).join(',');
      } else {
        values.fileurls = ''; // 明确赋空字符串
      }
       if (values.date) {
        values.date = values.date.format('YYYY-MM-DD');
      }

      // disabled字段不会包含在values中，需要手动合并
      values.debturgeid = data.debturgeid;


     
      saveurgelog({obj:values}).then((res:any)=>{
        if (res.errorMessage){
          Modal.error({
            title: res.errorMessage,
          });
        }else{

        }
      })


      onChange&&onChange(values)
      handleCancel();
    }).catch(() => {
      message.warning('请填写必填项！');
    });
  };

  const handleCancel = () => {

    onClose?.();
  };

  return (
    <Modal
      title={data.type==1?"清欠措施":"每月反馈"}
      visible={visible}
      width={800}
      destroyOnClose
      centered
      onCancel={handleCancel}
      footer={
      <>
        
        <Space>
          <Button onClick={handleCancel}>取消</Button>
          <Button type="primary" onClick={handleOk}>提交</Button>
          {data?.id && (
            <Button danger onClick={()=>{
              Modal.confirm({
                title: '确定删除吗？',
                okText: '确定',
                cancelText: '取消',
                onOk: () => {
                   delurgelog({id:data.id}).then((res:any)=>{
                    if (res.errorMessage){
                      Modal.error({
                        title: res.errorMessage,
                      });
                    }else{
                      onChange&&onChange(data)
                      handleCancel();
                    }
                  })
                }
              })
            }}>
              删除
            </Button>
          )}
        </Space>
        
      </>
    }
    >
      <Form form={form} layout="vertical" initialValues={data}>
        <Form.Item label="id" name="id" style={{display:'none'}}>
                    <Input disabled/>
                </Form.Item>
                <Form.Item label="contractid" name="contractid" style={{display:'none'}}>
                    <Input disabled/>
                </Form.Item>
        <Form.Item label="debturgeid" name="debturgeid" style={{display:'none'}}>
          <Input disabled/>
        </Form.Item>
      
<Form.Item label="type" name="type" style={{display:'none'}}>
          <Input disabled/>
        </Form.Item>
 
     
        <Form.Item label="日期："  name="date" rules={[{ required: false, message: 'Please input!' }]}>
            <DatePicker format="YYYY-MM-DD" style={{ width: '100%' }}  />
          </Form.Item>

          <Form.Item
            label="备注"
            name="note"
            rules={[{ required: false, message: '请输入备注' }]}
          >
            <TextArea rows={4} placeholder="请输入备注" />
          </Form.Item>
        <div style={row}>

          <Form.Item label="相关文件：" style={{ flex: 1 }}>
            <MyUploadFile
              ref={uploadRef}
              name="fileurls"
              max={20}
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

       
        
      </Form>
    </Modal>
  );
};

export default AddUrgeLog;