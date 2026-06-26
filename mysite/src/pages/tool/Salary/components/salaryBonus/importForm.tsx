import {
  ModalForm,
  ProFormDatePicker,
  ProFormSelect,
} from '@ant-design/pro-components';
import { message } from 'antd';
import React, { useImperativeHandle, useState } from 'react';
import MyUploadFile from '@/components/MyUploadFile';
import moment from 'moment';
import { importData,types } from './service';

export type EditFormProps = {
    id: number;
    // channelId: number;
    // reload?: () => void;
  };
const ImportForm = React.forwardRef((props: EditFormProps, ref) => {
  const [showForm, setShowForm] = useState<boolean>(false);//显示编辑框
  const [typeDict, setTypeDict] = useState<any>([]);
  // const [flag, setFlag] = useState<boolean>(false);
  //上传
  const handleImport = async (values: any) => {
    console.log(values);
    const hide = message.loading('正在保存');
    try {
      let result;
      result = await importData(values);
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
      }
  }));

  return (
    <ModalForm<{
      name: string;
      company: string;
    }>
      title="导入奖金"
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
        // console.log(values);
        if(!values.upload){
          message.error('请上传文件');
          return false;
        }

        values.url = values.upload[0].response.data.url;
        
        delete values.upload;

        const result = await handleImport(values);
        if (result) {
          if (result.errorCode) {
            message.warn(result.errorMessage);
            return false;
          }
          message.success('保存成功！');
        }
        
        return true;
      }}
    >
      <ProFormDatePicker.Year width="md" name="bonus_year" label="所属年度" />
      <ProFormSelect
            width="md"
            fieldProps={{
              labelInValue: true,
            }}
            valueEnum={typeDict}
            name="bonus_type"
            label="奖金类型"
          />
        <MyUploadFile
            name="upload"
            label=""
            title="文件上传"
            colProps={{ md: 12, xl: 6 }}
            className="infouploaditem"
            max={1}
            multiple={false}
            accept=".xls, .xlsx"
            maxSize={1}
            listType="picture-card"
            // defaultImage={defaultImage}
            uploadPath="salary"
            uploadType={2}
          />
    </ModalForm>
  );
});

export default ImportForm;
