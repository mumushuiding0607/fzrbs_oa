
import { Button, Form, Input, Modal, Space } from 'antd';
import React, {useEffect, useRef, useState } from 'react';

import MyUploadFile from '@/components/MyUploadFile';
import { getFromUrl, setToUrl } from '../utils';
import { saveinvoicing, savepdfinvoice } from './service';
import Filescard from '../contract/filescard';





const AddPdfInvoice: React.FC<{invoicingid:any,pdffileurls:any,isinvoicer:boolean}> = ({invoicingid,pdffileurls,isinvoicer=false}) => {
  const data = {id:invoicingid,pdffileurls}
  const [urls,setUrls]=useState(pdffileurls)
  const [defaultImage, setDefaultImage] = useState(pdffileurls?pdffileurls.split(',').map((url:any)=>{
  
      return getFromUrl(url)
    }):[])
  const uploadRef = useRef<AnimationPlayState>();
   const [form] = Form.useForm();
  const [visible,setVisible] = useState(false)
  var [refreshkey,setRefreshkey] = useState(0)
  const isMounted = React.useRef(true)
  useEffect(() =>{
    console.log('useEffect init')
    if (!isMounted.current){
      return
    }
    isMounted.current = false;
    
    
  },[])

const onFinish = (values: any) => {
    
    const uploads = uploadRef?.current?.getFileList();
    
    if (uploads) values.pdffileurls = uploads.map((u:any)=>{
      return setToUrl(u)
    }).join(',')
    savepdfinvoice(values).then((res:any)=>{
      if (res.errorMessage){
        Modal.error({
          title: res.errorMessage,
        });
      }else{
        setVisible(false)
        setUrls(values.pdffileurls)
      }
    })
  
  }
  return (
    <>
    {
      isinvoicer &&
      <div className="ant-table-title" style={{width:'100%',display:'flex',alignItems:'center',borderLeft:'1px solid #edecec',borderRight:'1px solid #edecec'}}>
        <div ><Button type="link" onClick={()=>{setVisible(true)}}>上传PDF发票</Button></div>
    </div>
    }
    {
      !isinvoicer&&
      <div className="ant-table-title" style={{width:'100%',display:'flex',alignItems:'center',borderLeft:'1px solid #edecec',borderRight:'1px solid #edecec'}}>
        <div ><Button type="link" >PDF发票</Button></div>
    </div>
    }
    
    <div key={'div'+urls}>
      {
        urls!=null&&urls!=''&&
        <Filescard key={'filescard'+urls} urls={urls} mode='list' />
      }
    </div>
    
    <Modal
        title="上传pdf发票"
        style={{ top: 20, }}
        visible={visible}
        onOk={() => {
          setVisible(false)
        }}
        onCancel={() => setVisible(false)}
        footer={null}
      >
        <Form form={form} onFinish={onFinish}  initialValues={data}>
          <Form.Item label="id" name="id" style={{display:'none'}}>
              <Input disabled/>
          </Form.Item>
          <Form.Item  >
            <MyUploadFile
              name="pdffileurls"
              label=""
              max={10}
              multiple={false}
              accept=".pdf,.PDF"
              maxSize={100}
              listType="picture-card"
              defaultImage={defaultImage}
              uploadPath="contract"
              uploadType={3}
              ref={uploadRef}
            />
          </Form.Item>
          <Form.Item >
              <Space>
                <Button type="primary" htmlType="submit">
                  提交
                </Button>
    
              </Space>
          </Form.Item>
        </Form>
        
        
        </Modal>
      
    </>
  );
}

export default AddPdfInvoice;