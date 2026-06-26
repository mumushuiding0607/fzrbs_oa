
import { Button, DatePicker, Form, Input, Modal, Space, message } from "antd";
import { CSSProperties, useEffect, useRef, useState } from "react";
import { UrgeStateEnum } from "./debtconfig";
import { updateurge } from "./service";



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

const FinishUrge: React.FC<AddUrgeLogProps> = ({ visible, data, onClose, onChange }) => {
  const [form] = Form.useForm();

  useEffect(() => {
 
  }, [visible]);




  const handleOk = () => {
    Modal.confirm({
      title: '确认结束催收吗？',
      onOk:()=>{
        form.validateFields().then((values) => {
          values.state=UrgeStateEnum.DEALED
          updateurge({obj:values}).then((res:any)=>{
            if (res.errorMessage){
              Modal.error({
                title: res.errorMessage,
              });
            }else{
              onChange&&onChange(values)
              handleCancel();
            }
          })
          
        }).catch(() => {
          message.warning('请填写必填项！');
        });
      }
    })
  };

  const handleCancel = () => {

    onClose?.();
  };

  return (
    <Modal
      title="结束催收"
      visible={visible}
      width={800}
      destroyOnClose
      footer={
      <>
        
        <Space>
          <Button onClick={handleCancel}>取消</Button>
          <Button type="primary" onClick={handleOk}>提交</Button>

        </Space>
        
      </>
    }
    >
      <Form form={form} layout="vertical" initialValues={data}>
        <Form.Item label="id" name="id" style={{display:'none'}}>
                    <Input disabled/>
                </Form.Item>
        <Form.Item label="debturgeid" name="debturgeid" style={{display:'none'}}>
          <Input disabled/>
      </Form.Item>
        {/* 清欠方式 */}



      </Form>
    </Modal>
  );
};

export default FinishUrge;