import MyUploadFile from "@/components/MyUploadFile";
import { Button, DatePicker, Form, Input, Modal, Space, message,Radio } from "antd";
import { CSSProperties, useEffect, useRef, useState } from "react";
import { getFromUrl, setToUrl } from "../../utils";
import TextArea from "antd/lib/input/TextArea";
import { delurgelog, saveurgelog } from "./service";
import dayjs from 'dayjs';
import Dictselect from "../../budget/dict/dictselect";


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

const AddFile: React.FC<AddUrgeLogProps> = ({ visible, data, onClose, onChange }) => {
  const [form] = Form.useForm();
  const uploadRef = useRef<any>(null);

  // 安全地处理fileurls，防止dayjs对象导致split错误
  const getFileUrls = (urls: any): any[] => {
    if (!urls) return [];
    if (typeof urls === 'string') {
      return urls.split(',').map((url: string) => getFromUrl(url));
    }
    if (Array.isArray(urls)) {
      return urls.map((url: any) => getFromUrl(url));
    }
    return [];
  };

  const [defaultImage, setDefaultImage] = useState<any[]>(() => getFileUrls(data?.fileurls));
  const uploadRef2 = useRef<any>(null);
  const [defaultImage2, setDefaultImage2] = useState<any[]>(() => getFileUrls(data?.dealfileurls));

  useEffect(() => {
    if (visible && data) {
      console.log('data:',data)
      // 处理日期字段，确保转换为dayjs对象或null
      let dateValue = null;
      let dealdateValue = null;
      
      if (data.date) {
        if (typeof data.date === 'object' && data.date.format) {
          dateValue = data.date;
        } else if (typeof data.date === 'string') {
          dateValue = dayjs(data.date);
        } else if (data.date._isAMomentObject) {
          dateValue = dayjs(data.date);
        } else {
          dateValue = null;
        }
      }
      if (data.dealdate) {
        if (typeof data.dealdate === 'object' && data.dealdate.format) {
          dealdateValue = data.dealdate;
        } else if (typeof data.dealdate === 'string') {
          dealdateValue = dayjs(data.dealdate);
        } else if (data.dealdate._isAMomentObject) {
          dealdateValue = dayjs(data.dealdate);
        } else {
          dealdateValue = null;
        }
      }
      
      // 设置表单字段值
      form.setFieldsValue({
        ...data,
        date: dateValue,
        dealdate: dealdateValue,
      });
      setDefaultImage(getFileUrls(data?.fileurls));

      // 处置
      setDefaultImage2(getFileUrls(data?.dealfileurls));
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


      const uploads2 = uploadRef2.current?.getFileList?.();
      if (uploads2 && uploads2.length > 0) {
        values.dealfileurls = uploads2.map((u: any) => setToUrl(u)).join(',');
      } else {
        values.dealfileurls = ''; // 明确赋空字符串
      }
      if (values.dealdate) {
        values.dealdate = values.dealdate.format('YYYY-MM-DD');
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
      title="添加进度"
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
      <Form form={form} layout="vertical">
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

<Form.Item
          label="清欠方式"
          name="urgetype"
          rules={[{ required: true, message: '请选择清欠方式' }]}
          
        >
          <Dictselect type={"清欠方式"}  needAddItem={false} />
        </Form.Item>
     
        <Form.Item label="清欠日期："  name="date" rules={[{ required: false, message: 'Please input!' }]}>
            <DatePicker format="YYYY-MM-DD" style={{ width: '100%' }}  />
          </Form.Item>
        {/* <Form.Item labelCol={{span: 3, offset: 30}} label="账销案存:" name="recoverable">
            <Radio.Group >
              <Radio value={0} defaultChecked> 否 </Radio>
              <Radio value={1}> 是 </Radio>
            </Radio.Group>
          </Form.Item> */}
          <Form.Item
            label="清欠备注"
            name="note"
            rules={[{ required: false, message: '请输入清欠备注' }]}
          >
            <TextArea rows={4} placeholder="请输入清欠备注" />
          </Form.Item>
        <div style={row}>

          <Form.Item label="清欠文件：" style={{ flex: 1 }}>
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
        <Form.Item
          label="清欠结果"
          name="urgeresult"
          rules={[{ required: false, message: '清欠结果' }]}
        >
          <Dictselect type={"清欠结果"}  needAddItem={false}/>
        </Form.Item>
         <Form.Item label="清欠结果日期："  name="dealdate" rules={[{ required: false, message: 'Please input!' }]}>
            <DatePicker format="YYYY-MM-DD" style={{ width: '100%' }}  />
          </Form.Item>

          <Form.Item
            label="清欠结果备注"
            name="dealnote"
            rules={[{ required: false, message: '请输入清欠结果备注' }]}
          >
            <TextArea rows={4} placeholder="请输入清欠结果备注" />
          </Form.Item>
        <div style={row}>

          <Form.Item label="清欠结果文件：" style={{ flex: 1 }}>
            <MyUploadFile
              ref={uploadRef2}
              name="dealfileurls"
              max={20}
              multiple={false}
              accept="*/*"
              maxSize={100}
              listType="picture-card"
              defaultImage={defaultImage2}
              uploadPath="contract"
              uploadType={3}
            />
          </Form.Item>
        </div>
      </Form>
    </Modal>
  );
};

export default AddFile;