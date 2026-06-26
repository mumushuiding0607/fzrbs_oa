import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import {
  Button,
  Dropdown,
  Menu,
  Upload,
  Modal,
  message,
  Form,
  Input,
  Space,
} from 'antd';
import { UploadOutlined, DownloadOutlined, EyeFilled } from '@ant-design/icons';
import { getBykeyword, savedict } from '../budget/dict/service';
import MyUploadFile from '@/components/MyUploadFile';
import { getFromUrl, setToUrl } from '../utils';

const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
const row: CSSProperties = {
  display: 'flex',
  flexDirection: 'row',
  width: '100%',
  padding: 0,
  gap: '2em',
};


const DownloadDocDropdown = ({ }) => {
  const [documents,setDocuments]=useState<any[]>([])
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [data,setData]=useState<any>({type:'操作文档'})
  const [form] = Form.useForm();
  const uploadRef = useRef<any>(null);
    
  const [defaultImage, setDefaultImage] = useState<any>(data.fileurls?data.fileurls.split(',').map((url:any)=>{

    return getFromUrl(url)
  }):[])

  useEffect(() => {
    getData()
  }, []);
  const  getData = ()=>{
    getBykeyword({keyword:'操作文档',showall:true}).then((res:any)=>{

      if (res) {
        res.map((e:any)=>{
          if (!e.value && (e.value!=0||e.value!='0')) e.value = e.id
          return e
        })
      }
      setDocuments(res)
      

    })
  } 

const onFinish = (values: any) => {
    const uploads = uploadRef.current?.getFileList?.();
    if (uploads && uploads.length > 0) {
      values.fileurls = uploads.map((u: any) => setToUrl(u)).join(',');
    } else {
      values.fileurls = ''; // 明确赋空字符串
    }

   
    savedict(values).then(res=>{
      if (res.errorMessage) {
        Modal.error({title: res.errorMessage})
      } else {
        Modal.info({title: '上传成功'})
        setIsModalOpen(false)
       getData()
      }
    })
  };
  // 构建下拉菜单
  const menu = (
    <Menu>
      {documents.length > 0 ? (
        documents.map((doc, index) => (
          <Menu.Item key={index} icon={<EyeFilled />} >
            <a href={doc.fileurls} target="blank">{doc.label}</a>
          </Menu.Item>
        ))
      ) : (
        <Menu.Item disabled>暂无文档</Menu.Item>
      )}
      <Menu.Divider />
      <Menu.Item onClick={() => setIsModalOpen(true)}>➕ 上传新文档</Menu.Item>
    </Menu>
  );

  return (
    <>
      {/* 下拉按钮 */}
      <Dropdown overlay={menu} trigger={['click']} placement="bottomLeft">
        <Button type="primary">操作文档</Button>
      </Dropdown>
      <Modal
        title="更新"
        style={{ top: 20, }}
        visible={isModalOpen}
        onOk={() => setIsModalOpen(false)}
        onCancel={() => setIsModalOpen(false)}
        footer={null}
      >
      <Form form={form} onFinish={onFinish}  initialValues={data}>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="文档类型：" name="type" rules={[{ required: true, message: 'Please input!' }]} >
            <Input value={'操作文档'} disabled={true}/>
        </Form.Item>
        <Form.Item label="文档名称" name="label" rules={[{ required: true, message: 'Please input!' }]}>
          <Input />
        </Form.Item>
       
        <div style={row}>

          <Form.Item label="相关附件：" style={{ flex: 1,marginLeft:'10px' }}>
            <MyUploadFile
              ref={uploadRef}
              name="fileurls"
              max={1}
              multiple={false}
              accept="*/*"
              maxSize={100}
              listType="picture-card"
              defaultImage={defaultImage}
              uploadPath="contract"
              uploadType={3}
            />
          </Form.Item>
        </div>
        

        <Form.Item {...tailLayout}>
          <Space>
            <Button type="primary" htmlType="submit">
              {data.id?'更新':'创建'}
            </Button>
     
          </Space>
        </Form.Item>
    </Form>

      </Modal>

    </>
  );
};

export default DownloadDocDropdown;