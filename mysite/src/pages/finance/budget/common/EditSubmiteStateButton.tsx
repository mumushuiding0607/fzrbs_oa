import React, { useState } from 'react';
import { Modal, Form, Button, Input, DatePicker } from 'antd';

import dayjs from 'dayjs';
import { altersubmitdate } from '../project/service';

// 假设这是你已有的组件
// import DepartmentTreeSelect from './DepartmentTreeSelect';

const EditSubmiteStateButton: React.FC<{text:any,obj:any,onSave?:Function}> = ({obj,text,onSave}) =>{
  const [open, setOpen] = useState(false);
  const [form] = Form.useForm();

   if (obj.submitdate) {
    obj.submitdate = dayjs(obj.submitdate,"YYYY-MM-DD")
  }
  // 打开弹窗时初始化数据
  const handleOpen = () => {
    form.setFieldsValue({
      departmentId: obj.signdeptid, // 设置默认值
    });
    setOpen(true);
  };

  // 取消
  const handleCancel = () => {
    form.resetFields();
    setOpen(false);
  };

  // 确定保存
  const handleOk = async () => {
    try {
      const values = await form.validateFields();
      // 如果values.creator是对象
      if (values.creator && typeof values.creator === 'object') {
        values.creator = values.creator.value;
      }
      if (values.submitdate) {
      values.submitdate = values.submitdate.format('YYYY-MM-DD');
    }
      altersubmitdate(values).then((res:any)=>{
        if (res.errorMessage){
          Modal.error({
            title: res.errorMessage,
          });
        }else{
          setOpen(false);
          Modal.info({
            title: '保存成功',
          });
          onSave && onSave(res)
        }
      })
 
    } catch (error) {
      console.log('校验失败:', error);
    }
  };

  return (
    <>
      {/* 触发按钮 */}
      <Button type="text" onClick={handleOpen}>
        {text}
      </Button>

      {/* 弹窗 */}
      <Modal
        title="修改"
        visible={open}
        onOk={handleOk}
        onCancel={handleCancel}
        okText="确定"
        cancelText="取消"
      >
        <Form form={form} layout="vertical" initialValues={obj}>
          <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
          <Form.Item  label="提交日期" name="submitdate" rules={[{ required: true, message: 'Please input!' }]}>
            <DatePicker format="YYYY-MM-DD" style={{width:'100%'}}/>
          </Form.Item>
          <Form.Item  label="项目编号" name="serial"  rules={[{ required: true, message: 'Please input!' }]}>
            <Input placeholder="项目编号" />
          </Form.Item>
        </Form>
      </Modal>
    </>
  );
};

export default EditSubmiteStateButton;