import React, { useState } from 'react';
import { Modal, Form, Button, Input } from 'antd';
import DepartmentTreeSelect from '../../budget/common/department_treeselect';
import UserAutocomplete from '../../budget/common/userAutocomplete';
import { altercharger, savecontract } from '../service';
import dayjs from 'dayjs';
// 假设这是你已有的组件
// import DepartmentTreeSelect from './DepartmentTreeSelect';

const EditResponsibleDeptButton: React.FC<{obj?:any,onSave?:Function}> = ({obj,onSave}) =>{
  const [open, setOpen] = useState(false);
  const [form] = Form.useForm();

 
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
      altercharger(values).then((res:any)=>{
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
        修改责任部门和责任人
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
          <Form.Item
            name="departmentid"
            label="责任部门"
            rules={[{ required: true, message: '请选择责任部门' }]}
          >
            <DepartmentTreeSelect multiple={false} defaultValue={obj.departmentid}/>
          </Form.Item>
          <Form.Item  label="责任人"  name="creator" rules={[{ required: true, message: 'Please input!' }]}>
              <UserAutocomplete multiple={false} placeholder='默认是合同经办'/>
          </Form.Item>
        </Form>
      </Modal>
    </>
  );
};

export default EditResponsibleDeptButton;