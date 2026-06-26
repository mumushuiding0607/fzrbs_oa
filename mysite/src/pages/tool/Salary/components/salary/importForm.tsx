import {
  ModalForm,
  ProFormDatePicker,
} from '@ant-design/pro-components';
import { message } from 'antd';
import React, { useImperativeHandle, useState } from 'react';
import MyUploadFile from '@/components/MyUploadFile';
import moment from 'moment';
import { importData } from './service';

export type EditFormProps = {
    id: number;
  };
const ImportForm = React.forwardRef((props: EditFormProps, ref) => {
  const [showForm, setShowForm] = useState<boolean>(false);//显示编辑框
  const [labelName, setLabelName] = useState<string>('发放时间');//年月名称
  const [defaultImage, setDefaultImage] = useState<any[]>([]);

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
    //钩子 外部调用
  useImperativeHandle(ref, () => ({
      setVisible: (visible: boolean) => {
        setShowForm(visible);
        // setFlag(false);
      },
      setLabelName:(name:string)=>{
        setLabelName(name);
      }
  }));

  return (
    <ModalForm<{
      name: string;
      company: string;
    }>
      title="导入工资"
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
        month: moment().format("YYYY-MM")
      }}
      onFinish={async (values) => {
        console.log(values);
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
      <ProFormDatePicker.Month name="month" label={labelName} />
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
            defaultImage={defaultImage}
            uploadPath="salary"
            uploadType={2}
          />
    </ModalForm>
  );
});

export default ImportForm;
