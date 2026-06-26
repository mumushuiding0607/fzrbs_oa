import {
  ModalForm,
  ProForm,
  ProFormDatePicker,
  ProFormSelect,
  ProFormSwitch,
} from '@ant-design/pro-components';
import { message } from 'antd';
import React, { useImperativeHandle, useState } from 'react';
import { signRule,types } from './service';
import moment from 'moment';

export type EditFormProps = {
    id: number;
    // channelId: number;
    // reload?: () => void;
  };
const SignForm = React.forwardRef((props: EditFormProps, ref) => {
  const [showForm, setShowForm] = useState<boolean>(false);//显示编辑框
  const [modalTitle, setModalTitle] = useState<string>('签发奖金');//年月名称
  const [stState, setStState] = useState<number>(1);//签发状态1 签发 0 取消签发
  const [depIdState, setDepIdState] = useState<number>(0);//部门id
  const [typeDict, setTypeDict] = useState<any>([]);

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
  const setTypes = async ()=>{
    await types().then((res) => {
      console.log('types:',res);
      let _types = {};
      for(let i in res.data){
        _types[i] = {text:res.data[i],status:''};
      }
      setTypeDict(_types);
    }); 
  }
    //钩子 外部调用
  useImperativeHandle(ref, () => ({
      setVisible: (visible: boolean) => {
        setTypes();
        setShowForm(visible);
        // setFlag(false);
      },
      setContent:(st:number,depId:number) => {
        setStState(st);
        setModalTitle(st ? "签发":"取消签发");
        setDepIdState(depId);
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
        bonus_year: moment().format("YYYY")
      }}
      onFinish={async (values) => {
        values.depId= depIdState;
        values.bonus_type= values.bonus_type.value;
        values.st= stState;

        console.log(values);


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
         
        <ProFormDatePicker.Year width="md" name="bonus_year" label="所属年度" />
        <ProForm.Group>
        <ProFormSelect
            width="md"
            fieldProps={{
              labelInValue: true,
            }}
            valueEnum={typeDict}
            name="bonus_type"
            label="奖金类型"
          />
            <ProFormSwitch label="发送通知" name="notify"/>
        </ProForm.Group>
    </ModalForm>
  );
});

export default SignForm;
