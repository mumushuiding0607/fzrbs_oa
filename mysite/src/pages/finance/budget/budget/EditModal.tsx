import React, { useState } from 'react';
import { Modal, Input } from 'antd';

interface EditModalProps {
  visible: boolean;
  initialValue: string;
  onOk: (value: string) => void;
  onCancel?: () => void;
}

const EditModal: React.FC<EditModalProps> = ({
  visible,
  initialValue,
  onOk,
  onCancel,
}) => {
  const [value, setValue] = useState<string>(initialValue);

  const handleOk = () => {
    onOk(value); // 返回修改后的值
  };



  React.useEffect(() => {
    if (visible) {
      setValue(initialValue); // 每次打开 Modal 时同步最新初始值
    }
  }, [visible, initialValue]);

  return (
    <Modal
      title="编辑文本"
      visible={visible}
      onOk={handleOk}
      onCancel={()=>onCancel && onCancel()}
      okText="确认"
      cancelText="取消"
    >
      <Input
        value={value}
        onChange={(e) => setValue(e.target.value)}
        placeholder="请输入内容"
        autoFocus
      />
    </Modal>
  );
};

export default EditModal;