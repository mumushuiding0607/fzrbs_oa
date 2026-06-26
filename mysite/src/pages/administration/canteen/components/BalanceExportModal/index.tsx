import { Modal } from 'antd';
import React, { useEffect, useImperativeHandle, useRef } from 'react';
import { useState } from 'react';
import { ProForm, ProFormInstance, ProFormSelect } from '@ant-design/pro-components';
import { type } from '../list/service';
import tools from '@/utils/tools';

export type RechargeModalProps = {
  onOk?: (values: object) => void;
};

const BalanceExportModal = React.forwardRef((props: RechargeModalProps, ref) => {
  const [visible, setVisible] = useState(false);
  const [userType, setUserType] = useState<any>({});
  const formRef = useRef<ProFormInstance>();

  const handleCancel = () => setVisible(false);
  const handleOk = async () => {
    const values = formRef?.current?.getFieldsFormatValue();
    let fileName = '食堂账户余额情况';
    if (values.userType && values.userType.length == 1) {
      fileName = fileName + '(' + userType[values.userType[0]].text + ')';
    }
    tools.downloadFile('/api/canteen/accountBalanceDownload', values, fileName + '.xls');
    setVisible(false);
  };

  useImperativeHandle(ref, () => ({
    setVisible: (value: boolean) => {
      setVisible(value);
    },
  }));

  useEffect(() => {
    type().then((res) => {
      setUserType(res.data);
    });
  }, []);

  return (
    <Modal visible={visible} title="余额导出" onCancel={handleCancel} onOk={handleOk}>
      <ProForm layout="vertical" formRef={formRef} submitter={false}>
        <ProFormSelect name="userType" label="" valueEnum={userType} placeholder="请选择用户分类" />
      </ProForm>
    </Modal>
  );
});
export default BalanceExportModal;
