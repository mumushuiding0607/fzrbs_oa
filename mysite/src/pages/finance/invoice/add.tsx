import { Button, Card, DatePicker,Form,Input,InputNumber,Modal,Row,Select,Space,Upload } from 'antd';
import React, {  CSSProperties, useEffect, useRef, useState } from 'react';
import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';

import {  getinvoicing, saveinvoicing } from './service';
import weekday from "dayjs/plugin/weekday"
import localeData from "dayjs/plugin/localeData"
import Companyselect from '../company/companyselect';
import MyUploadFile from '@/components/MyUploadFile';
import { getFromUrl, setToFile, setToUrl } from '../utils';
import Dictselect from '../budget/dict/dictselect';
import { BalanceTypes } from '../budget/config';
import ContractSelect from '../contract/contract-select';
import moment from 'moment';
import CompanyinfoSelect from '../company/companyinfoSelect';
import InvoicingItemsList from './invoicing_items_list';
import InvoicingitemInput from './invoicing_item_input';
import ProjectSelect from '../budget/project/projectSelect';
import Businesstype_Tree from './Businesstype_Tree';



 
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


const Add:React.FC<{data?:any,onChange:Function}> = ({data,onChange}) =>{
  const [form] = Form.useForm();
  var [ck,setCk]=useState(0)
  var [ciskey,setCiskey]=useState(0)
  
  const [customerid,setCustomerid]=useState(0)
  const [customer,setCustomer]=useState<any>({})
  var [obj,setObj] = useState(data)
  const id=data?data.id:0
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

  useEffect(() =>{

    if (data.id) {
      if (data.customer){
  
        // 判断是否为string
        if (typeof data.customer === 'string'){
     
          var temp = JSON.parse(data.customer)
          setCustomer(temp)
          setCustomerid(temp.id)
        }

      }

    }
    console.log('addinvoice data:',data)
    
  },[data.id])
  const onReset = () => {
    form.resetFields();
  };


  

 
  
  const onFinish = (values: any) => {
    
    const uploads = uploadRef?.current?.getFileList();
    
    if (uploads) values.fileurls = uploads.map((u:any)=>{
      return setToUrl(u)
    }).join(',')
    
    if (values.date) {
      values.date = values.date.format(dateFormat);
    }
    if (values.contractid && values.contractid.id){
      values.contractid = values.contractid.id
    }
    if (values.projectids && values.projectids.value){
      values.projectids = values.projectids.value
    }
    // values.projectids = values.projectids.map((e:any)=>e.value).join(',')
    
    if (Array.isArray(values.parta)){
      values.partaname = values.parta.map((e:any)=>e.label).join(',')
      values.parta = values.parta.map((e:any)=>e.value).join(',')
    }
    if (Array.isArray(values.partb)){
      values.partbname = values.partb.map((e:any)=>e.label).join(',')
      values.partb = values.partb.map((e:any)=>e.value).join(',')
    }
    if (Array.isArray(values.receiverid)){
      values.receiver = values.receiverid.map((e:any)=>e.label).join(',')
      values.receiverid = values.receiverid.map((e:any)=>e.value).join(',')
    }
    var err = ''
    if(!values.customer){
      err = '客户信息不能为空'
    }
    if (!values.customer.code){
      err = '客户信用代码不能为空'
    }
    if (values.type==1){
      if (!values.customer.address){
        err = '专票，客户公司地址不能为空'
      }else if (!values.customer.contacts){
        err = '专票，客户电话不能为空'
      }else if (!values.customer.bankaccount){
        err = '专票，客户开户信息不能为空，包含开户行和银行账号'
      }
    }
    
    if (err){
      Modal.error({
        title: err,
      });
      return
    }
    console.log(values)
    save(values)
    
   

    
    
    
  };
  
  const save = (values:any)=>{


    Modal.confirm({ 
      title: '确定保存？',
      okText: '是',
      cancelText: '否',
      onOk: () => {
        saveinvoicing(values).then((res:any)=>{
      if (res.errorMessage) {
        Modal.error({
          title: res.errorMessage,
        });
      } else {
        
        onChange(res.data)
      }
      
    })
      }
    })
    
  }
  


  const onContractChange = (e:any)=>{
   
    if (e) e = [e]
    if (e && e.length>0){
      var parta = e.map((d:any)=>d.parta).join(',')
      var partb = e.map((d:any)=>d.partb).join(',')
      var partaname = e.map((d:any)=>d.partaname).join(',')
      var partbname = e.map((d:any)=>d.partbname).join(',')
      form.setFieldsValue({parta})
      form.setFieldsValue({partaname})
      form.setFieldsValue({partb})
      form.setFieldsValue({partbname})
      if (!form.getFieldValue('receiverid')){
        form.setFieldsValue({receiverid:parta})
        form.setFieldsValue({receiver:partaname})
      }
      setCk(++ck)
      setCustomerid(parta)
      setCustomer({})
    }
  }

  const onPartaChange = (e:any)=>{

    if (e && e.length>0){
      var parta = e.map((d:any)=>d.id).join(',')
      var partaname = e.map((d:any)=>d.company).join(',')
      
      if (!form.getFieldValue('receiverid')){
        setCk(++ck)
        form.setFieldsValue({receiverid:parta})
        form.setFieldsValue({receiver:partaname})
        setCustomerid(parta)
        setCustomer(null)
        setCiskey(++ciskey)
      }
    }
  }
  const onReceiveridChange = (e:any)=>{ 
    if (e && e.length>0){
      var parta = e.map((d:any)=>d.id).join(',')
      setCustomerid(parta)
      setCustomer(null)
      setCiskey(++ciskey)

    }
  }
  

  return (
    <>
      <Form key={refreshkey} {...formItemLayout}  form={form} onFinish={onFinish} style={{ maxWidth: 800 }} initialValues={obj}>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        
        <Form.Item labelCol={{xl:3,xs:3,xxl:3}} style={{...formitem,width:'100%'}} label="相关合同：" name="contractid"  >
          <ContractSelect  multiple={false} showupload={false} type={BalanceTypes.INCOME} onChange={onContractChange} />
        </Form.Item>
        
            
        <Form.Item label="合作单位：" name="partaname" style={{display:'none'}}>
            <Input/>
        </Form.Item>
        <Form.Item label="立项主体：" name="partbname" style={{display:'none'}} >
            <Input/>
        </Form.Item>
        <Form.Item label="立项主体：" name="receiver" style={{display:'none'}} >
            <Input/>
        </Form.Item>

        <div style={row}>
            <Form.Item  label="业务类型：" style={formitem} name="businesstype"  rules={[{ required: true, message: 'Please input!' }]}>
              <Businesstype_Tree />
            </Form.Item>
            <Form.Item  style={formitem} label="合同业务：" name="contract" rules={[{ required: true, message: 'Please input!' }]}>
              <Dictselect type={"合同业务类型"}  needAddItem={false}/>
            </Form.Item>
            
        </div>
        <div style={row}>
          <Form.Item style={formitem} label="销售方名称：" name="partb" rules={[{ required: true, message: 'Please input!' }]}>
            <Companyselect key={'b'+ck} placeholder="报社相关公司" multiple={true} sign={1} preloadInvoicingPartb={1}/>
          </Form.Item>
          <Form.Item  label="发票类型：" style={formitem} name="type"  rules={[{ required: true, message: 'Please input!' }]}>
                <Select defaultValue={0} options={[{value:0,label:'普票'},{value:1,label:'专票'}]} />
            </Form.Item>
        </div>
        <div style={row}>
          
          <Form.Item style={formitem} label="客户名称：" name="parta" rules={[{ required: true, message: 'Please input!' }]}>
            <Companyselect key={'a'+ck}  multiple={true} sign={1} onChange={onPartaChange}/>
          </Form.Item>
          <Form.Item style={formitem} label="发票抬头" name="receiverid" rules={[{ required: true, message: 'Please input!' }]}>
            <Companyselect key={'r'+ck} placeholder="发票抬头" multiple={true} onChange={onReceiveridChange}/>
          </Form.Item>
        </div>
        {
          !obj.id&&
          <Form.Item labelCol={{span: 3, offset: 30}} label="开票项目：" name="items">
          <InvoicingitemInput />
        </Form.Item>
        }
        {
          obj.id&&
          <InvoicingItemsList invoicingid={obj.id} />
        }
        
        
        
        <Card title="客户信息" bordered>
          <Form.Item label="" style={{width:'100%'}} name="customer" rules={[{ required: true, message: 'Please input!' }]}>
            <CompanyinfoSelect key={'customer'+ciskey} id={customerid} obj={customer} />
          </Form.Item>
        </Card>
        
   
  
    
        <Form.Item labelCol={{span: 3, offset: 30}} label="发票备注：" name="content">
            <Input placeholder='发票备注内容'/>
          </Form.Item>
          
          
        <Form.Item labelCol={{xl:3,xs:3,xxl:3}} style={{...formitem,width:'100%'}} label="相关非报项目：" name="projectids"  >
          <ProjectSelect multiple={false}></ProjectSelect>
        </Form.Item>
        <Form.Item labelCol={{span: 3, offset: 30}} label="其他说明：" name="othercontent">
            <TextArea rows={3} placeholder='其他开票要求请填写备注，非报系统未立项的项目请将项目名称输入备注框：项目名称（报社或公司）'/>
          </Form.Item>
        <div style={row}>
          <Form.Item style={{paddingLeft:'30px'}} >
            <MyUploadFile
              key={uprefresh}  
              name="fileurls"
              label="相关附件："
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
        <p style={{color:'red'}}>合同业务（标准合同、框架合同）申请开票未关联合同的，请在开票后一个月内上传并关联合同，若因未签合同导致款项无法收回的，根据报社规定部门负责人和相应业务人员需承担赔偿责任。</p>
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
export default Add