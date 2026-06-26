import { Modal } from 'antd';
import React, { useEffect, useImperativeHandle, useRef } from 'react';
import { useState } from 'react';
import {
  ProForm,
  ProFormDateRangePicker,
  ProFormInstance,
  ProFormSelect,
} from '@ant-design/pro-components';
import { type, menuType } from '../list/service';
import tools from '@/utils/tools';

export type RechargeModalProps = {
  onOk?: (values: object) => void;
};

const OrderDownloadModal = React.forwardRef((props: RechargeModalProps, ref) => {
  const [visible, setVisible] = useState(false);
  const [userType, setUserType] = useState<any>({});
  const [downloadMenuType, setDownloadMenuType] = useState<any>({});
  const formRef = useRef<ProFormInstance>();

  const handleCancel = () => setVisible(false);
  const handleOk = async () => {
    const values = formRef?.current?.getFieldsFormatValue();
    let fileName = '食堂订单';
    if (values.userType && values.userType.length == 1) {
      fileName = fileName + '(' + userType[values.userType[0]].text + ')';
    }
    tools.downloadFile('/api/canteen/orderDownload', values, fileName + '.xls');
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
    menuType().then((res) => {
      setDownloadMenuType(res.data);
    });
  }, []);

  return (
    <Modal visible={visible} title="订单导出" onCancel={handleCancel} onOk={handleOk}>
      <ProForm layout="vertical" formRef={formRef} submitter={false}>
        <ProFormDateRangePicker name="orderTime" label="订单时间" />
        <ProFormSelect
          name="userType"
          label=""
          valueEnum={userType}
          fieldProps={{
            mode: 'multiple',
          }}
          placeholder="请选择用户分类"
        />
        <ProFormSelect
          name="menuType"
          label=""
          valueEnum={downloadMenuType}
          fieldProps={{
            mode: 'multiple',
          }}
          placeholder="请选择菜单分类"
        />
        <ProFormSelect
          name="status"
          label=""
          valueEnum={{
            0: '未使用',
            1: '已使用',
            2: '已取消',
          }}
          fieldProps={{
            mode: 'multiple',
          }}
          placeholder="请选择订单状态"
        />
      </ProForm>
    </Modal>
  );
});
export default OrderDownloadModal;
