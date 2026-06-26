import UEditor from '@/components/UEditor';
import {
  DrawerForm,
  ProForm,
  ProFormDateTimePicker,
  ProFormInstance,
  ProFormText,
  ProFormTextArea,
} from '@ant-design/pro-components';
import { Button, Form, message } from 'antd';
import React, { useImperativeHandle, useRef, useState } from 'react';
import { addRule, one, updateRule } from '../list/service';
import MyUploadFile from '@/components/MyUploadFile';

export type EditFormProps = {
  id: number;
  channelId: number;
  customField?: any;
  reload?: () => void;
};

const EditForm = React.forwardRef((props: EditFormProps, ref) => {
  const [showForm, setShowForm] = useState<boolean>(false);
  const formRef = useRef<ProFormInstance>();
  const editorRef = useRef<any>();
  const [ueditorData, setUeditorData] = useState<any>('');
  const [ueditorConfig] = useState({
    initialFrameWidth: '100%',
    initialFrameHeight: 500,
  });
  const [defaultImage, setDefaultImage] = useState<any[]>([]);
  const [flag, setFlag] = useState<boolean>(false);
  const uploadRef = useRef();
  const [state, setState] = useState<number>(0);
  // const [fieldTitle, setFieldTitle] = useState<any>({
  //   'title': '标题',
  //   'subtitle': '副标题',
  //   'shorttitle': '短标题',
  //   'source': '来源',
  //   'writer': '作者',
  //   'keywords': '标题',
  //   'redirect': '跳转网址',
  //   'remark': '摘要',
  // });

  const fieldTitle = {
    'title': '标题',
    'subtitle': '副标题',
    'shorttitle': '短标题',
    'source': '来源',
    'writer': '作者',
    'keywords': '关键字',
    'redirect': '跳转网址',
    'remark': '摘要',
  };

  if (props.customField && props.customField != undefined) {
    const channelKeys = Object.keys(props.customField);
    const channelIndex = channelKeys.findIndex((value, index) => { return value == 'channel_' + props.channelId })
    if (channelIndex > -1) {
      const channelFieldTitle = props.customField['channel_' + props.channelId];
      for (let i in channelFieldTitle) {
        if (i != 'id') {
          fieldTitle[i] = channelFieldTitle[i];
        }
      }
    }
  }

  // 富文本失焦就触发setContent函数设置表单的content内容
  const setContent = (e) => {
    formRef?.current.setFieldsValue({
      content: editorRef?.current.getUEContent(),
    });
  };

  const handleAddAndUpdate = async (id: number, values: any) => {
    const hide = message.loading('正在保存');
    try {
      values.channelid = props.channelId;
      let result;
      if (id == 0) {
        result = await addRule({
          values,
        });
      } else {
        result = await updateRule({
          id: id,
          values,
        });
      }
      hide();
      return result;
    } catch (error) {
      message.error('保存失败！');
      return false;
    }
  };

  useImperativeHandle(ref, () => ({
    setVisible: (visible: boolean) => {
      setShowForm(visible);
      setFlag(true);
    },
  }));

  return (
    <>
      <DrawerForm
        title="编辑信息内容"
        width="100vw"
        visible={showForm}
        onVisibleChange={setShowForm}
        formRef={formRef}
        autoFocusFirstInput
        drawerProps={{
          destroyOnClose: true,
          onClose: () => {
            setFlag(false);
          },
        }}
        // submitter={{ searchConfig: { submitText: '提交' } }}
        submitter={{
          render: (fromProps, doms) => {
            const buttons = [<Button key="close" onClick={() => setShowForm(false)}>
              取消
            </Button>, <Button type="primary" key="submit" onClick={() => { setState(0); fromProps.form?.submit?.(); }}>
              保存
            </Button>, <Button key="public" onClick={() => { setState(1); fromProps.form?.submit?.(); }}>
              签发
            </Button>];

            return buttons;
          },
        }}
        submitTimeout={2000}
        onFinish={async (values) => {
          if (values.upload && values.upload.length > 0) {
            const images: string[] = [];
            values.upload.forEach(element => {
              if (element.response) {
                images.push(element.response.data.url);
              } else {
                images.push(element.url);
              }
            });
            values.image = images.join(',');
          } else {
            const uploads = uploadRef?.current.getFileList();
            if (uploads.length > 0) {
              const images: string[] = [];
              uploads.forEach(element => {
                images.push(element.url);
              });
              values.image = images.join(',');
            } else {
              values.image = '';
            }
          }
          delete values.upload;
          values.state = state;
          const result = await handleAddAndUpdate(props.id, values);
          if (result) {
            if (result.errorCode) {
              message.warn(result.errorMessage);
              return false;
            }
            message.success((state == 0 ? '保存' : '签发') + '成功！');
          }
          if (props.reload) {
            setFlag(false);
            props.reload();
          }
          return true;
        }}
        request={async () => {
          if (props.id > 0) {
            const info = await one({ id: props.id });
            setUeditorData(info.data.content);
            if (info.data.image != '') {
              const imagesInfo: any[] = [];
              const images = info.data.image.split(',');
              images.forEach((element, index) => {
                const url =
                  element.substr(0, 6) == 'assets' ? '/' + element : element;
                imagesInfo.push({
                  uid: info.data.id.toString() + index.toString(),
                  name: info.data.title,
                  status: 'done',
                  url: url,
                  thumbUrl: url,
                });
              });
              setDefaultImage(imagesInfo);
            } else {
              setDefaultImage([]);
            }
            return info.data;
          } else {
            setUeditorData('');
            setDefaultImage([]);
          }
          return {};
        }}
        layout="horizontal"
        grid={true}
      >
        <ProForm.Group>
          <ProFormText
            name="title"
            label={fieldTitle.title}
            placeholder={'请输入' + fieldTitle.title}
            rules={[
              {
                required: true,
                message: '请输入' + fieldTitle.title,
              },
            ]}
            colProps={{ md: 12, xl: 10 }}
          />
          <ProFormText
            name="subtitle"
            label={fieldTitle.subtitle}
            placeholder={'请输入' + fieldTitle.subtitle}
            colProps={{ md: 12, xl: 8 }}
          />
          <ProFormText
            name="shorttitle"
            label={fieldTitle.shorttitle}
            placeholder={'请输入' + fieldTitle.shorttitle}
            colProps={{ md: 12, xl: 6 }}
          />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormText
            name="source"
            label={fieldTitle.source}
            placeholder={'请输入' + fieldTitle.source}
            colProps={{ md: 12, xl: 3 }}
          />
          <ProFormText
            name="writer"
            label={fieldTitle.writer}
            placeholder={'请输入' + fieldTitle.writer}
            colProps={{ md: 12, xl: 3 }}
          />
          <ProFormText
            name="keywords"
            label={fieldTitle.keywords}
            placeholder={'请输入' + fieldTitle.keywords}
            colProps={{ md: 12, xl: 4 }}
          />
          <ProFormText
            name="redirect"
            label={fieldTitle.redirect}
            placeholder={'请输入' + fieldTitle.redirect}
            // rules={[
            //   {
            //     type: 'url',
            //     message: '请输入正确的网址格式！',
            //   },
            // ]}
            colProps={{ md: 12, xl: 6 }}
          />
          <ProFormDateTimePicker colProps={{ md: 12, xl: 6 }} label="发布时间" name="publictime" />
        </ProForm.Group>
        <ProForm.Group>
          <ProFormTextArea colProps={{ md: 12, xl: 6 }} label={fieldTitle.remark} name="remark" />
          <MyUploadFile
            name="upload"
            label=""
            title="标题图上传"
            colProps={{ md: 12, xl: 6 }}
            className="infouploaditem"
            max={3}
            multiple={false}
            accept="image/*"
            maxSize={1}
            listType="picture-card"
            defaultImage={defaultImage}
            uploadPath="information"
            uploadType={1}
            ref={uploadRef}
          />
        </ProForm.Group>
        {flag === true && (
          <Form.Item name="content">
            <UEditor
              ref={editorRef}
              config={ueditorConfig}
              initData={ueditorData}
              setContent={(e) => setContent(e)}
            />
          </Form.Item>
        )}
      </DrawerForm>
    </>
  );
});

export default EditForm;
