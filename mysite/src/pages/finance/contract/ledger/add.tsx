import { Button, Card, DatePicker,Form,Input,InputNumber,Modal,Row,Select,Space,Upload } from 'antd';
import React, {  CSSProperties, useEffect, useRef, useState } from 'react';
import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';


import weekday from "dayjs/plugin/weekday"
import localeData from "dayjs/plugin/localeData"
import Companyselect from '../../company/companyselect';
import MyUploadFile from '@/components/MyUploadFile';
import { getFromUrl, setToFile, setToUrl } from '../../utils';


import ContractSelect from '../../contract/contract-select';
import moment from 'moment';
import { BalanceTypes } from '../../budget/config';
import Dictselect from '../../budget/dict/dictselect';
import { saveledger } from '../service';




 
dayjs.extend(weekday)
dayjs.extend(localeData)
// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
const row:CSSProperties = {
  display:'flex',
  flexDirection: 'row',
  width:'100%',
  padding:0,
  gap: '2em',
}
const formitem:CSSProperties={
  width:'50%'
}
const formItemLayout = {
  labelCol: {
    xs: { span: 6 },
    sm: { span: 6 },
  },
  wrapperCol: {
    xs: { span: 24 },
    sm: { span: 24 },
  },
};
// dom
const { TextArea } = Input;

dayjs.extend(customParseFormat);
const { RangePicker } = DatePicker;


const AddLedger:React.FC<{data?:any,onChange?:Function}> = ({data,onChange}) =>{
  const [form] = Form.useForm();


  var [obj,setObj] = useState(data)

  const [defaultImage, setDefaultImage] = useState((data&&data.fileurls)?data.fileurls.split(',').map((url:any)=>{

    return getFromUrl(url)
  }):[])
  const uploadRef = useRef<AnimationPlayState>();
  var [refreshkey,setRefreshkey] = useState(1)
  const dateFormat = 'YYYY-MM-DD HH:mm:ss'
  var [uprefresh,setUprefresh]=useState(0)

 
  if (obj.id){
    obj.date = moment(obj.date,dateFormat)
  }else{
    obj.date= moment()
  }

  const onReset = () => {
    form.resetFields();
  };


  

 
  
  const onFinish = (values: any) => {
    
    const uploads = uploadRef?.current?.getFileList();
    
    if (uploads) values.fileurls = uploads.map((u:any)=>{
      return setToUrl(u)
    }).join(',')
    

    if (values.contractid && values.contractid.id){
      values.contractid = values.contractid.id
    }
    if (values.agentid && values.agentid.id){
      values.agent = values.agentid.company
      values.agentid = values.agentid.id
    }


    console.log(values)
    save(values)
    
   

    
    
    
  };
  
  const save = (values:any)=>{


    Modal.confirm({ 
      title: '确定提交吗？',
      okText: '是',
      cancelText: '否',
      onOk: () => {
        saveledger(values).then(res=>{
          if (res.errorMessage){
            Modal.error({title:res.errorMessage})
          }else{
            onChange&&onChange(res)
          }
        })
      }
    })
    
  }
  



  return (
    <>
      <Form key={refreshkey} {...formItemLayout}  form={form} onFinish={onFinish} style={{ maxWidth: 800 }} initialValues={obj}>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        
        <div style={row}>
          <Form.Item style={{...formitem}} label="相关合同" name="contractid"   rules={[{ required: true, message: 'Please input!' }]}>
            <ContractSelect  multiple={false} showupload={false} type={BalanceTypes.EXPEND} />
          </Form.Item>
          <Form.Item style={formitem} label="招标代理机构" name="agentid" rules={[{ required: false, message: 'Please input!' }]}>
            <Companyselect  placeholder="招标代理机构" multiple={false} sign={1} />
          </Form.Item>
        </div>
        <div style={row}>
          <Form.Item style={formitem} label="采购编号" name="ledgerserial" rules={[{ required: true, message: 'Please input!' }]}>
            <Input/>
          </Form.Item>
          <Form.Item style={formitem} label="项目名称" name="title" rules={[{ required: true, message: 'Please input!' }]}>
              <Input placeholder='可自己添加名字/非报系统选择项目/直接显示合同名称'/>
          </Form.Item>

        </div>
        <div style={row}>
            <Form.Item  style={formitem} label="采购类别" name="type" rules={[{ required: true, message: 'Please input!' }]}>
              <Dictselect type={"采购类别"}  needAddItem={false}/>
            </Form.Item>
            <Form.Item  style={formitem} label="采购方式" name="method" rules={[{ required: true, message: 'Please input!' }]}>
              <Dictselect type={"采购方式"}  needAddItem={false}/>
            </Form.Item>
        </div>
        

        <div style={row}>
            <Form.Item  style={formitem} label="验收结果" name="resultid" rules={[{ required: true, message: 'Please input!' }]}>
              <Dictselect type={"验收结果"}  needAddItem={false}/>
            </Form.Item>
            <Form.Item  style={formitem} label="文件是否齐全" name="file" rules={[{ required: true, message: 'Please input!' }]}>
              <Select options={[{value:0,label:'否'},{value:1,label:'是'}]} />
            </Form.Item>
        </div>

       
        <Form.Item labelCol={{span: 3, offset: 30}} label="备注" name="notes">
            <TextArea rows={3} placeholder='备注内容'/>
          </Form.Item>
        <div style={row}>
          <Form.Item style={{paddingLeft:'30px'}} >
            <MyUploadFile
              key={uprefresh}  
              name="fileurls"
              label="相关附件"
              max={20}
              multiple={false}
              accept=".*"
              maxSize={100}
              listType="picture-card"
              defaultImage={defaultImage}
              uploadPath="contract"
              uploadType={3}
              ref={uploadRef}
            />
          </Form.Item>
        </div>
       <Form.Item {...tailLayout}>
        
        <Space>
          <Button type="primary" htmlType="submit">
            {!obj.id?'提交':'更新'}
          </Button>
          <Button htmlType="button" onClick={onReset}>
            清空
          </Button>
        </Space>
      </Form.Item>
      </Form>
    </>
  )
}
export default AddLedger