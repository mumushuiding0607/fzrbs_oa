import { ProForm, ProFormInstance, ProFormSelect } from '@ant-design/pro-components';
import { message, Modal } from 'antd';
import React, { useImperativeHandle, useRef } from 'react';
import { useState } from 'react';
import { setType } from '../list/service';

export type UserTypeModalProps = {
  onOk?: (typeId: number) => void;
  onCancel?: () => void;
  selectedRows: number[];
  types: any;
};

const UserTypeModal = React.forwardRef((props: UserTypeModalProps, ref) => {
  const [visible, setVisible] = useState(false);
  const formRef = useRef<ProFormInstance>();

  const handleCancel = () => {
    setVisible(false);
    if (props.onCancel) {
      props.onCancel();
    }
  };
  const handleOk = async () => {
    const typeId = formRef.current?.getFieldValue('userType');
    if (typeId) {
      let updateIds = [];
      updateIds = props.selectedRows.map((row) => row.id);
      const result = await setType({
        typeid: typeId,
        userIds: updateIds,
      });
      if (result.errorCode) {
        message.warn(result.errorMessage);
      } else {
        message.success('结算分类成功');
      }
      if (props.onOk) {
        props.onOk(typeId);
      }
    }
    setVisible(false);
  };

  useImperativeHandle(ref, () => ({
    setVisible: (value: boolean) => {
      setVisible(value);
    },
  }));

  return (
    <Modal visible={visible} title="设置食堂账号结算分类" onCancel={handleCancel} onOk={handleOk}>
      <ProForm submitter={false} formRef={formRef}>
        <ProFormSelect
          name="userType"
          label=""
          valueEnum={props.types}
          placeholder="请选择分类"
          rules={[{ required: true, message: '请选择一个分类!' }]}
        />
      </ProForm>
    </Modal>
  );
});
export default UserTypeModal;
