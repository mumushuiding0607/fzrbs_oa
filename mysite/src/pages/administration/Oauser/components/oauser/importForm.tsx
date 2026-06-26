import { message, Modal } from 'antd';
import React, { useEffect, useImperativeHandle, useRef } from 'react';
import { useState } from 'react';
import {
  ProColumns,
  ProForm,
  ProFormInstance,
  ProFormSelect,
  ProFormUploadButton,
  ProTable,
} from '@ant-design/pro-components';
import { type } from '../list/service';
import { request } from 'umi';

export type RechargeModalProps = {
  onOk?: () => void;
};

const ImportForm = React.forwardRef((props: RechargeModalProps, ref) => {
  const [visible, setVisible] = useState(false);
  const formRef = useRef<ProFormInstance>();

  const handleCancel = () => setVisible(false);
  const handleOk = async () => {
    const values = formRef?.current?.getFieldsFormatValue();
   
    if (!values.uploadFile) {
      message.warn('请选择Excel文件');
      return;
    }
    const formData = new FormData();
    formData.append('upfile', values.uploadFile[0].originFileObj);
    const hide = message.loading('正在上传处理文件数据');
    const result = await request('/api/oauser/import', {
      method: 'POST',
      body: formData,
    });
    hide();
    if (!result.errorMessage) {
      message.success('上传成功');
    } else {
      message.warn(result.errorMessage);
    }
    if (props.onOk) {
      props.onOk();
    }
    setVisible(false);
  };

  useImperativeHandle(ref, () => ({
    setVisible: (value: boolean) => {
      setVisible(value);
    },
  }));



  return (
    <Modal visible={visible} title="职员信息导入" onCancel={handleCancel} onOk={handleOk}>
      <ProForm layout="vertical" formRef={formRef} submitter={false}>
        <ProFormUploadButton
          name="uploadFile"
          label="Excel文件上传"
          max={1}
          fieldProps={{
            name: 'upfile',
            accept: '.xls,.xlsx',
            maxCount: 1,
            beforeUpload: () => {
              return new Promise(async (resolve, reject) => {
                return reject(false);
              });
            },
          }}
        />
      </ProForm>
    </Modal>
  );
});
export default ImportForm;
