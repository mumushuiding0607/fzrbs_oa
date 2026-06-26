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

const ExcelImportRechargeModal = React.forwardRef((props: RechargeModalProps, ref) => {
  const [visible, setVisible] = useState(false);
  const [userType, setUserType] = useState<any>({});
  const formRef = useRef<ProFormInstance>();
  const [fielList, setFielList] = useState<any[]>([]);

  const handleCancel = () => {
    setFielList([]);
    setVisible(false);
  }
  const handleOk = async () => {
    const values = formRef?.current?.getFieldsFormatValue();
    if (!values.userType) {
      message.warn('请选择用户分类');
      return;
    }
    if (!values.uploadFile || values.uploadFile.length == 0) {
      message.warn('请选择Excel文件');
      return;
    }
    const formData = new FormData();
    formData.append('upfile', values.uploadFile[0].originFileObj);
    formData.append('userType', values.userType);
    const hide = message.loading('正在上传处理文件数据');
    const result = await request('/api/canteen/accountExcelRecharge', {
      method: 'POST',
      body: formData,
    });
    hide();
    if (!result.errorMessage) {
      message.success('成功充值 ' + result.successNum.toString() + ' 个用户');
    } else {
      message.warn(result.errorMessage);
    }
    if (props.onOk) {
      props.onOk();
    }
    setFielList([]);
    formRef?.current?.setFieldsValue({ userType: '', uploadFile: undefined });
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

  const columns: ProColumns<any>[] = [
    {
      title: '序号(A)',
      dataIndex: 'xuhao',
    },
    {
      title: '姓名(B)',
      dataIndex: 'xingming',
    },
    {
      title: '基础餐补(C)',
      dataIndex: 'jichucanbu',
    },
    {
      title: '请假缺勤天数(D)',
      dataIndex: 'queqintianshu',
    },
    {
      title: '当月核发(E)',
      dataIndex: 'dangyuehefa',
    },
    {
      title: '手机号(F)',
      dataIndex: 'mobile',
      tip: '其他部门存在同名员工时，该员工导入信息需要填写企业号绑定的手机号',
    },
  ];

  return (
    <Modal visible={visible} title="Excel文件导入充值" onCancel={handleCancel} onOk={handleOk}>
      <ProForm layout="vertical" formRef={formRef} submitter={false}>
        <ProFormSelect name="userType" label="" valueEnum={userType} placeholder="请选择用户分类" />
        <ProFormUploadButton
          name="uploadFile"
          label="Excel文件上传"
          max={1}
          value={fielList}
          fieldProps={{
            fileList: fielList,
            name: 'upfile',
            accept: '.xls,.xlsx',
            maxCount: 1,
            beforeUpload: () => {
              return new Promise(async (resolve, reject) => {
                return reject(false);
              });
            },
          }}
          onChange={(e) => {
            setFielList([...e.fileList]);
          }}
        />
      </ProForm>
      <ProTable<any, any>
        headerTitle="导入文件字段模板"
        title={() => <div style={{ textAlign: 'center' }}>xxxx年x月xxxx部门虚拟饭卡发放表</div>}
        columns={columns}
        search={false}
        options={false}
        pagination={false}
        rowKey="id"
        request={async () => ({
          data: [
            {
              id: 1,
              xuhao: 1,
              xingming: '张三',
              jichucanbu: 300,
              queqintianshu: '',
              dangyuehefa: 300,
              mobile: '',
            },
          ],
        })}
      />
    </Modal>
  );
});
export default ExcelImportRechargeModal;
