import React, { useEffect, useRef, useState } from 'react';
import { Modal, Form, Input, Button, Space } from 'antd';
import Dictselect from '../../budget/dict/dictselect';
import Flow from '../../budget/budget/flow';
import { previewdebtflow } from '../service';
import { startdeal, startdebturge } from './service';
import MyUploadFile from '@/components/MyUploadFile';
import { getFromUrl, setToUrl } from '../../utils';
import { CONTRACT_AGENTID } from '../config';

interface ApprovalModalProps {
  visible: boolean;
  data: any;
  onVisibleChange:any,
  onSuccess?:any,
}

const AddDealResult: React.FC<ApprovalModalProps> = ({
  visible,
  data,
  onVisibleChange,
  onSuccess

}) => {
  const [form] = Form.useForm();
  const [loading, setLoading] = useState(false);
  const uploadRef = useRef<any>(null);

  const [defaultImage, setDefaultImage] = useState<any>(data.dealresultfileurls?data.dealresultfileurls.split(',').map((url:any)=>{

    return getFromUrl(url)
  }):[])


  useEffect(() => {
    if (visible && data) {

      setDefaultImage(data.dealresultfileurls?data.dealresultfileurls.split(',').map((url:any)=>{

        return getFromUrl(url)
      }):[])
    }
  }, [visible, data]);


  const start = (par:any={})=>{
    const uploads = uploadRef.current?.getFileList?.();
          if (uploads && uploads.length > 0) {
            par.dealresultfileurls = uploads.map((u: any) => setToUrl(u)).join(',');
          } else {
            par.dealresultfileurls = ''; // 明确赋空字符串
          }
      startdeal({obj:{...par}}).then((res:any)=>{
        if (res.errorMessage) {
     
          Modal.error({
            title: res.errorMessage
          });
        } else {
          onVisibleChange(false)
          onSuccess && onSuccess({...par})

        }
      })
    }
  const onFinish = (values: any) => {
    values.contractid = values.contractid||data.id
    
    previewdebtflow({...values}).then((res:any)=>{
      if (res.errorMessage||res.message) {
        Modal.error({
          title: res.errorMessage||res.message,
        });
      } else {
        Modal.confirm({
          title:"请确认流程是否正确",
          bodyStyle:{marginLeft:0},
          width: '600px',
          centered:false,
          content:(
            <div style={{marginLeft:'0!important'}}>
              <Flow   data={res.viewdata} statusCn={res.statusCn} step={res.step}></Flow>
      
            </div>
          ),
          onOk:()=>{
            
            start(values)
            
          },
        })
      }
    })
  }
  return (
    <>
    <Modal
      title="处置审批"
      visible={visible}
      confirmLoading={loading}
      width={500}
      onCancel={() => onVisibleChange(false)}
      footer={null}
    >
      <Form
        form={form}
        layout="vertical"
        initialValues={data}
        onFinish={onFinish} 
      >
        <Form.Item  label="合同名称" name="debturgeid" style={{display:'none'}} >
          <Input />
        </Form.Item>
        <Form.Item  label="合同名称" name="contractid" style={{display:'none'}} >
          <Input />
        </Form.Item>
        <Form.Item label="type" name="type" style={{display:'none'}}>
          <Input disabled value={1}/>
        </Form.Item>

        {/* 清欠方式 */}
        <Form.Item
          label="处置措施"
          name="dealresult"
          rules={[{ required: true, message: '处置措施' }]}
        >
          <Dictselect type={"处置措施"} needAddItem={true} agentid={CONTRACT_AGENTID} placeholder={"处置措施"}  />
        </Form.Item>

    
   

        {/* 备注 */}
        <Form.Item label="处置说明" name="dealresultnote">
          <Input.TextArea placeholder="处置说明" autoSize={{ minRows: 2 }} />
        </Form.Item>
      <Form.Item label="相关文件：" style={{marginLeft:'10px'}}>
        <MyUploadFile
          ref={uploadRef}
          name="dealresultfileurls"
          max={20}
          multiple={false}
          accept="*/*"
          maxSize={100}
          listType="picture-card"
          defaultImage={defaultImage}
          uploadPath="contract"
          uploadType={3}
        />
      </Form.Item>
        <Form.Item>
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
};

export default AddDealResult;