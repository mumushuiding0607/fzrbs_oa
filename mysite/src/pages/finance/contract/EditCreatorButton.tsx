import React, { useState } from 'react';
import { Modal, Form, Button, Input } from 'antd';
import DepartmentTreeSelect from '../budget/common/department_treeselect';
import UserAutocomplete from '../budget/common/userAutocomplete';
import { altercreator } from './service';


const EditCreatorButton: React.FC<{obj?:any, ids?:string, visible?:boolean, onCancel?:Function, onSave?:Function}> = ({obj, ids, visible, onCancel, onSave}) => {
  const [open, setOpen] = useState(false);
  const [form] = Form.useForm();

  // 受控模式：由外部控制显隐
  const isControlled = visible !== undefined;
  const isOpen = isControlled ? visible : open;

  // 打开弹窗时初始化数据
  const handleOpen = () => {
    if (ids) {
      // 批量模式：不需要初始化数据
      form.resetFields();
    } else {
      // 单条模式：初始化数据
      form.setFieldsValue({
        id: obj.id,
        signdeptid: obj.signdeptid,
      });
    }
    if (!isControlled) {
      setOpen(true);
    }
  };

  // 取消
  const handleCancel = () => {
    form.resetFields();
    if (isControlled) {
      onCancel && onCancel();
    } else {
      setOpen(false);
    }
  };

  // 确定保存
  const handleOk = async () => {
    try {
      const values = await form.validateFields();
      // 如果values.creator是对象
      if (values.creator && typeof values.creator === 'object') {
        values.creator = values.creator.value;
      }

      // 组装请求数据
      const data = ids ? { ...values, ids } : { ...values, id: obj.id };

      altercreator(data).then((res: any) => {
        if (res.errorMessage) {
          Modal.error({
            title: res.errorMessage,
          });
        } else {
          handleCancel();
          Modal.info({
            title: '保存成功',
          });
          onSave && onSave(res);
        }
      });

    } catch (error) {
      console.log('校验失败:', error);
    }
  };

  // 如果是受控模式，不渲染触发按钮
  if (isControlled) {
    return (
      <Modal
        title={ids ? '批量转人' : '合同转人'}
        visible={isOpen}
        onOk={handleOk}
        onCancel={handleCancel}
        okText="确定"
        cancelText="取消"
      >
        <Form form={form} layout="vertical">
          {!ids && (
            <Form.Item label="id" name="id" style={{display: 'none'}}>
              <Input disabled />
            </Form.Item>
          )}
          <Form.Item
            name="signdeptid"
            label="签订部门"
            rules={[{ required: true, message: '请选择签订部门' }]}
          >
            <DepartmentTreeSelect multiple={false} />
          </Form.Item>
          <Form.Item label="经办人" name="creator" rules={[{ required: true, message: '请选择新经办人' }]}>
            <UserAutocomplete multiple={false} placeholder="选择用户" />
          </Form.Item>
        </Form>
      </Modal>
    );
  }

  return (
    <>
      {/* 触发按钮 */}
      <Button type="text" onClick={handleOpen}>
        转人
      </Button>

      {/* 弹窗 */}
      <Modal
        title={ids ? '批量转人' : '合同转人'}
        visible={open}
        onOk={handleOk}
        onCancel={handleCancel}
        okText="确定"
        cancelText="取消"
      >
        <Form form={form} layout="vertical" initialValues={obj}>
          {!ids && (
            <Form.Item label="id" name="id" style={{display: 'none'}}>
              <Input disabled />
            </Form.Item>
          )}
          <Form.Item
            name="signdeptid"
            label="签订部门"
            rules={[{ required: true, message: '请选择签订部门' }]}
          >
            <DepartmentTreeSelect multiple={false} />
          </Form.Item>
          <Form.Item label="经办人" name="creator" rules={[{ required: true, message: '请选择新经办人' }]}>
            <UserAutocomplete multiple={false} placeholder="选择用户" />
          </Form.Item>
        </Form>
      </Modal>
    </>
  );
};

export default EditCreatorButton;