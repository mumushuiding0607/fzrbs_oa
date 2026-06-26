import {
  ModalForm,
  ProForm,
  ProFormDatePicker,
  ProFormSwitch,
} from '@ant-design/pro-components';
import { message } from 'antd';
import React, { useImperativeHandle, useState } from 'react';
import moment from 'moment';
import { signRule } from './service';

export type EditFormProps = {
    id: number;
    // channelId: number;
    // reload?: () => void;
  };
const SignForm = React.forwardRef((props: EditFormProps, ref) => {
  const [showForm, setShowForm] = useState<boolean>(false);//显示编辑框
  const [modalTitle, setModalTitle] = useState<string>('签发');//年月名称
  const [stState, setStState] = useState<number>(1);//签发状态1 签发 0 取消签发
  const [depIdState, setDepIdState] = useState<number>(0);//部门id
  const [lableName, setLableName] = useState<string>('发放时间');//年月名称

  //上传
  const handleSign = async (values: any) => {
    const hide = message.loading('正在保存');
    try {
      let result;
      result = await signRule(values);
      hide();
      return result;
    } catch (error) {
      message.error('保存失败！');
      return false;
    }
  };
    //钩子 外部调用
  useImperativeHandle(ref, () => ({
      setVisible: (visible: boolean) => {
        setShowForm(visible);
        // setFlag(false);
      },
      setContent:(st:number,depId:number,lableName:string) => {
        setStState(st);
        setModalTitle(st ? "签发":"取消签发");
        setDepIdState(depId);
        setLableName(lableName);
      }
  }));

  // const [form] = Form.useForm<{ name: string; company: string }>();
  return (
    <ModalForm<{
      name: string;
      company: string;
    }>
      title={modalTitle}
      // form={form}
      visible={showForm}
      onVisibleChange={setShowForm}
      autoFocusFirstInput
      modalProps={{
        destroyOnClose: true,
        onCancel: () => console.log('run'),
      }}
      submitTimeout={2000}
      initialValues={{
        pay_time: moment().format("YYYY-MM")
      }}
      onFinish={async (values) => {
        values.depId= depIdState;
        values.st= stState;
        const result = await handleSign(values);
        if (result) {
          if (result.errorCode) {
            message.warn(result.errorMessage);
            return false;
          }
          message.success('操作成功！');
        }
        // if (props.reload) {
        //   // setFlag(false);
        //   props.reload();
        // }
        return true;
      }}
    >
        <ProForm.Group>
        <ProFormDatePicker.Month name="pay_time" label={lableName} />
            <ProFormSwitch label="发送通知" name="notify"/>
        </ProForm.Group>
    </ModalForm>
  );
});

export default SignForm;
