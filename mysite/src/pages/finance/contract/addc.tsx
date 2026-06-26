import { Button, Checkbox, DatePicker,Form,Input,InputNumber,Modal,Radio,Row,Select,Space,Upload } from 'antd';
import React, {  CSSProperties, useEffect, useRef, useState } from 'react';
import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';

import { useModel } from 'umi';
import { getcontract, savecontract } from './service';
import weekday from "dayjs/plugin/weekday"
import localeData from "dayjs/plugin/localeData"
import Dictselect from '../budget/dict/dictselect';
import Companyselect from '../company/companyselect';
import MyUploadFile from '@/components/MyUploadFile';
import UserAutocomplete from '../budget/common/userAutocomplete';
import DepartmentTreeSelect from '../budget/common/department_treeselect';
import { BalanceTypes } from '../budget/config';
import PayCondition from './paycondition';
import Supplementary from './supplementary';
import { CONTRACT_AGENTID } from './config';
import { getFromUrl, setToFile, setToUrl } from '../utils';
import CompanysView from './CompanysView';


 
dayjs.extend(weekday)
dayjs.extend(localeData)
// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
const row:CSSProperties = {
  display:'flex',
  flexDirection: 'row',
  // justifyContent:'space-between',

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


const AddC:React.FC<{data?:any,onChange:Function}> = ({data,onChange}) =>{
  const [form] = Form.useForm();
  
  const [dateString,setDateString] = useState([String,String])
  var [obj,setObj] = useState(data)
  const [defaultImage, setDefaultImage] = useState<any>(data.fileurls?data.fileurls.split(',').map((url:any)=>{

    return getFromUrl(url)
  }):[])
  const uploadRef = useRef<AnimationPlayState>();
  var [refreshkey,setRefreshkey] = useState(1)
  
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const [defaultDate,setDefaultDate] = useState('')
  const [type, setType] = useState(obj.type);
  const dateFormat = 'YYYY-MM-DD HH:mm:ss'
  var [uprefresh,setUprefresh]=useState(0)
  const [companys,setCompanys]=useState<any>([[],[]])
  const [mainamount,setMainamount] = useState(0)
  var [companysChange,setCompanysChange]=useState(0)
  const [check,setCheck]=useState(false)
  var [rc,setRc]=useState(0)
  if (obj.id){
    obj.signdate = dayjs(obj.signdate,dateFormat)
    if (obj.starttime && !obj.starttime.includes('00:00:00')) obj.starttime+=' 00:00:00'
    if (obj.endtime && !obj.endtime.includes('00:00:00')) obj.endtime+=' 00:00:00'
    obj.date = [obj.starttime&&dayjs(obj.starttime, dateFormat), obj.endtime&&dayjs(obj.endtime, dateFormat)]
    obj.type = parseInt(obj.type)
  }


 
  useEffect(() =>{
    
    if (obj.id) {
      
      const getdata = async ()=>{
        const res:any = await getcontract({id:obj.id})
        if (res.errorMessage){
          Modal.error({title:res.errorMessage})
        } else {
          
          obj.payconditions = res.data.payconditions
          form.setFieldsValue({['payconditions']:res.data.payconditions})
          form.setFieldsValue({signuserid:res.data.signuserid})
          setDateString([obj.starttime,obj.endtime])
          
          if (res.data.companyinfo){
            var temp = JSON.parse(res.data.companyinfo)
            if (Array.isArray(temp)){
              setCompanys(JSON.parse(res.data.companyinfo))
            }else {
              setMainamount(obj.mainamount)
              obj.parta=undefined
              obj.partaname=undefined
              obj.partb=undefined
              setCompanys([[],[]])
            }
            
          }
          setObj(obj)
          setRefreshkey(++refreshkey)
          console.log('obj:',obj)
        }
      }
      getdata()
    }
    
  },[])
  const onReset = () => {
    form.resetFields();
  };

  const onTypeSelect = (value: any,label: any) => {
    setType(value)
    form.setFieldsValue({['partbname']:[]})
    form.setFieldsValue({['partaname']:[]})
  }
  
  const signdateChange = (e:any)=>{
 
    obj.date = [e, obj.endtime&&dayjs(obj.endtime, dateFormat)]
    setDateString([e.format('YYYY-MM-DD'),dateString[1]])
    form.setFieldsValue({['date']:obj.date})
   
  }
 
  
  const onFinish = (values: any) => {
    
    const uploads = uploadRef?.current?.getFileList();
    
    if (uploads) values.fileurls = uploads.map((u:any)=>{
      return setToUrl(u)
    }).join(',')
    
    if (!values.companyinfo){
      alert('公司信息为空，把收付款方删掉，然后再选一遍')
      return
    }
    
    if (values.balancetype && Array.isArray(values.balancetype)){
      values.balancetypename = values['balancetype'].map((e:any)=>e.label).join(',')
      values.balancetype = values['balancetype'].map((e:any)=>e.value).join(',')
    }
   

    if (values.signuserid instanceof Object) {
      values.signusername = values.signuserid.label
      values.signuserid = values.signuserid.value
    }
    
    if (values.signdate) {
      values.signdate = values.signdate.format(dateFormat);
    }
    if (values.partaname && Array.isArray(values.partaname)) {
      values.parta = values.partaname.map((e:any)=>e.value).join(',')
      values.partaname = values.partaname.map((e:any)=>e.label).join(',')
    }
    if (values.partbname && Array.isArray(values.partbname)) {
      values.partb = values.partbname.map((e:any)=>e.value).join(',')
      values.partbname = values.partbname.map((e:any)=>e.label).join(',')
    }
    if (values.parta && Array.isArray(values.parta)) {
      values.partaname = values.parta.map((e:any)=>e.label).join(',')
      values.parta = values.parta.map((e:any)=>e.value).join(',')
    }
    if (values.partb && Array.isArray(values.partb)) {
      values.partbname = values.partb.map((e:any)=>e.label).join(',')
      values.partb = values.partb.map((e:any)=>e.value).join(',')
      
    }
    if (values.id) {
     
    } else {
      values.creator = currentUser.wxuserid
      values.department = currentUser.department
    }

    delete values.date
    values.starttime = dateString[0]
    values.endtime = dateString[1]
    if (!values.starttime){
      Modal.error({title:'合同开始日期不能为空'})
      return
    }
    if (values.supplementary  && values.supplementary.length>0){
      var temp = values.supplementary
      if (typeof values.supplementary == 'string'){
        temp = JSON.parse(values.supplementary)
      }
      var us = temp.map((x:any)=>x.urls).filter((t:any)=>t && t.length>0).join(',')
      if (us.length>0){
        if (values.fileurls && values.fileurls.length>0){
          values.fileurls+=','+us
        }else{
          values.fileurls = us
        }
      }
    }


    
    if(values.type==BalanceTypes.INCOME&&(!values.payconditions||values.payconditions.length==0)){
      
      Modal.confirm({
        title: '履约条件未设置，是否保存？',
        okText: '确认',
        cancelText: '取消',
        onOk: async () => {
          save(values)
        },
      })
    } else {
      save(values)
    }

    
    
    
  };
  
  const save = (values:any)=>{

    
    if (!values.parta||!values.partb){
      Modal.error({
        title: '付款方或收款方为空',
      });
      return
    }
    if (!values.agentid) values.agentid = CONTRACT_AGENTID
  
    savecontract(values).then((res)=>{
      if (res.errorMessage) {
        Modal.error({
          title: res.errorMessage,
        });
      } else {
        res.data.createMirror = values.createMirror
        onChange(res.data)
      }
      
    })
    
  }
  


  const onPartaChange = (e:any)=>{
    companys[0]=e
    setCompanys(companys)
    setRc(++rc)
  }
  const onPartbChange = (e:any)=>{
    
  
    companys[1]=e
    setCompanys(companys)
    setRc(++rc)
  }
  const onAmountChange = (e:any)=>{
    setMainamount(e.target.value||'0')
    setRc(++rc)
  }

  
  const onCompanysChange=(e:any)=>{
    e = e || [[],[]]
    if (e[0].length>0){
      obj.partaname = e[0].map((d:any)=>d.company).join(',')
      form.setFieldsValue({partaname:obj.partaname})
    }
    if (e[1].length>0){
      obj.partbname = e[1].map((d:any)=>d.company).join(',')
      form.setFieldsValue({partbname:obj.partbname})
    }
    // setCompanysChange(++companysChange)
    // setObj(obj)
  }
  const onaddFile = (e:any)=>{
    
    const uploads = uploadRef?.current?.getFileList();

    
    var temp = []
    if (uploads) temp = uploads.map((u:any)=>{
      if(u.name&&u.url){
        return u
      }
      return setToFile(u)
    })

    setDefaultImage([...temp,getFromUrl(e.url)])

    setUprefresh(++uprefresh)
  }
  const onDeleteFile=(e:any)=>{
    
    setDefaultImage(defaultImage.filter((d:any)=>d.url!=e))
    setUprefresh(++uprefresh)
  }
  return (
    <>
      <Form {...formItemLayout}  form={form} onFinish={onFinish} style={{ maxWidth: 800 }} initialValues={obj}>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="付款方：" name="partaname" style={{display:'none'}}>
             <Input/>
        </Form.Item>

        <Form.Item label="收款方：" name="partbname" style={{display:'none'}} >
            <Input/>
        </Form.Item>
        <Form.Item label="签订人：" name="signuserid" style={{display:'none'}}>
          <Input />
        </Form.Item>
        <div style={row}>
            <Form.Item  style={formitem} label="合同名称：" name="title" rules={[{ required: true, message: 'Please input!' }]}>
              <Input/>
            </Form.Item>
            <Form.Item  label="合同总价：" style={formitem} name="mainamount"  rules={[{ required: true, message: '与报社相关的合同金额!' }]}>
              <InputNumber prefix="￥" style={{ width: '100%' }} placeholder='仅与报社相关的合同金额' onBlur={onAmountChange} />
            </Form.Item>
            
        </div>
        <div style={row}>
            <Form.Item   style={formitem} label="合同编号：" name="serial" rules={[{ required: true, message: 'Please input!' }]}>
              <Input/>
            </Form.Item>
            <Form.Item   style={formitem} label="部门编号：" name="deptserial" >
              <Input/>
            </Form.Item>
        </div>
        <div style={row}>
          <Form.Item label="合同类型：" name="type" style={formitem} rules={[{ required: true, message: 'Please input!' }]}>
            <Dictselect  type={"合同类型"}  needAddItem={false} onChange={onTypeSelect} />
          </Form.Item>
          <Form.Item label="合同分类:" name="balancetype" style={formitem} rules={[{ required: true, message: 'Please input!' }]}>
            <Dictselect  type="合同收支类型"  needAddItem={true} multiple={true}  agentid={CONTRACT_AGENTID}/>
          </Form.Item>
        </div>
          
        <div style={row}>
          {/* <Form.Item label="付款方：" style={{width:'35%'}}  name="parta" rules={[{ required: true, message: 'Please input!' }]}>
            <Companyselect key={companysChange} multiple={true} sign={1} onChange={onPartaChange}/>
          </Form.Item>
          <Form.Item label="" style={{width:'15%'}} noStyle name="companytype" >
              <Dictselect  type={"单位性质"}  placeholder="付款方单位性质" />
          </Form.Item> */}
          
          <Form.Item label="付款方" style={{width:'50%'}}>
            <Input.Group compact>
              <Form.Item
                name='parta'
                noStyle
                rules={[{ required: true, message: '付款方不能为空' }]}
              >
                <Companyselect placeholder={'付款方名称'} style={{width:'66%'}} key={companysChange} multiple={true} sign={1} onChange={onPartaChange}/>
              </Form.Item>
              <Form.Item
                name='partatype'
                noStyle
                style={{width:'34%'}}
                rules={[{ required: false, message: '单位性质不能为空' }]}
              >
                <Dictselect style={{width:'100%',minWidth:'99px'}} type={"单位性质"}  placeholder="单位性质" />
              </Form.Item>
            </Input.Group>
          </Form.Item>
          <Form.Item label="收款方："  style={formitem} name="partb" rules={[{ required: true, message: 'Please input!' }]}>
              <Companyselect placeholder={'收款方名称'} key={companysChange} multiple={true} sign={1} onChange={onPartbChange} />
          </Form.Item>
 
          
        </div>
        {
          Array.isArray(companys)&&(companys[0].length>0||companys[1].length>0) &&
          <div style={{...row,padding:'0 0 0 96px'}}>
                                      
            <Form.Item label="" name="companyinfo" style={{width:'100%'}}>
                <CompanysView key={rc} datas={companys} amount={mainamount} update={true} onChange={onCompanysChange}></CompanysView>
            </Form.Item>
            
          </div>

        }
        
        <div style={row}>
          <Form.Item  label="签订人：" style={formitem} name="signuserid" rules={[{ required: true, message: 'Please input!' }]}>
              <UserAutocomplete key={'sign'+obj.signuserid} multiple={false} placeholder='仅与报社相关'/>
          </Form.Item>
         

          <Form.Item label="签订部门：" style={formitem} name="signdeptid" rules={[{ required: true, message: 'Please input!' }]}>
            <DepartmentTreeSelect multiple={false} defaultValue={obj.signdeptid} />
          </Form.Item>
        </div>
        <div style={row}>
          <Form.Item label="签订日期：" style={formitem} name="signdate" rules={[{ required: true, message: 'Please input!' }]}>
            <DatePicker format="YYYY-MM-DD" style={{ width: '100%' }} onChange={signdateChange}/>
          </Form.Item>
          <Form.Item label="合同期限："  name="date" style={{ width: '50%' }} rules={[{ required: true, message: 'Please input!' }]}>
              <DatePicker.RangePicker
                style={{width:'100%'}}
                placeholder={['合同开始日期', '至合同结束']}
                allowEmpty={[false, true]}
                onChange={(date:any, dateString:any) => {
                
                  setDateString(dateString)
                  if (dateString[1]) {
                    setDefaultDate(date[1])
                    setRefreshkey(++refreshkey)
                  }
                }}
              />
          </Form.Item>
        </div>
        <div style={row} key={refreshkey}>
          {type==BalanceTypes.INCOME &&<Form.Item labelCol={{span: 3, offset: 27}} label="履约条件：" style={{width:'100%'}} name="payconditions" >
            <PayCondition key={'paycondition'+refreshkey} defaultValues={obj.payconditions||[]} defaultDate={defaultDate}/>
          </Form.Item>}
        </div>
        <div style={row}>
          <Form.Item labelCol={{span: 3, offset: 27}} label="补充协议：" style={{width:'100%'}} name="supplementary" >
            <Supplementary defaultValues={obj.supplementary||[]} onDeleteFile={onDeleteFile} onaddFile={onaddFile} />
          </Form.Item>
        </div>

        <Form.Item labelCol={{span: 3, offset: 30}} label="合作内容：" name="content">
          <TextArea rows={4} />
        </Form.Item>
        
     
        <div style={row}>
          <Form.Item style={{paddingLeft:'30px'}} >
            <div style={{color: 'red', marginBottom: '8px', fontSize: '12px'}}>
              请上传合同原件扫描的附件
            </div>
            <MyUploadFile
              key={uprefresh}
              name="fileurls"
              label="合同附件："
              max={20}
              multiple={false}
              accept=".pdf,.jpg,.jpeg,.png,.gif,.bmp,.webp"
              maxSize={100}
              listType="picture-card"
              defaultImage={defaultImage}
              uploadPath="contract"
              uploadType={3}
              ref={uploadRef}
            />
          </Form.Item>
        </div>
   
        <Form.Item labelCol={{span: 3, offset: 30}} label="创建镜像合同：" name="createMirror">
          <Radio.Group>
            <Radio value={0} defaultChecked> 否 </Radio>
            <Radio value={1}> 是 </Radio>
          </Radio.Group>
        </Form.Item>
        
        <Form.Item {...tailLayout}>
            <Space>
              <Button type="primary" htmlType="submit">
                {obj.id?'更新':'创建'}
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
export default AddC