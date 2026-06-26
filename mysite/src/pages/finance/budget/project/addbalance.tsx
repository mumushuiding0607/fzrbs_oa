
import { Button, DatePicker,Form,Input,InputNumber,Modal,Select,Space,Upload } from 'antd';
import React, { useEffect, useRef, useState } from 'react';
import dayjs from 'dayjs';
import customParseFormat from 'dayjs/plugin/customParseFormat';
import Dictselect from '../dict/dictselect';
import { useModel } from 'umi';
import moment from 'moment';
import weekday from "dayjs/plugin/weekday"
import localeData from "dayjs/plugin/localeData"
import { AGENTID, BalanceTypes, ProjectStatesEnum } from '../config';
import { getonlyproject, savebalance } from './service';
import MyUploadFile from '@/components/MyUploadFile';
import { getFromUrl, setToUrl } from '../../utils';
import ContractSelect from '../../contract/contract-select';
 
dayjs.extend(weekday)
dayjs.extend(localeData)
// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
const formItemLayout = {
  labelCol: {
    xs: { span: 4 },
    sm: { span: 4 },
  }
};
// dom
const { TextArea } = Input;

dayjs.extend(customParseFormat);
const { RangePicker } = DatePicker;

const dateFormat = 'YYYY-MM-DD';
const Addbalance:React.FC<{data:any,onChange:Function,isFinal?:boolean}> = ({data,onChange,isFinal=false}) =>{
  const [form] = Form.useForm();
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const [defaultImage, setDefaultImage] = useState((data.budgetfileurls)?(data.budgetfileurls).split(',').map((url:any)=>{
    return getFromUrl(url)
  }):[])
  const [defaultImage2, setDefaultImage2] = useState((data.finalfileurls)?(data.finalfileurls).split(',').map((url:any)=>{
    return getFromUrl(url)
  }):[])
  const uploadRef = useRef<AnimationPlayState>();
  const finalUploadRef = useRef<AnimationPlayState>();
  const [projectState,setProjectState]=useState(0)

  data.specialinvoice = data.specialinvoice?data.specialinvoice:0
  if (!data.date) data.date =  moment()
  if (data.projectid) data.projectid = parseInt(data.projectid)
  if (data.type) data.type = parseInt(data.type)
  const onReset = () => {
    form.resetFields();
  };

  useEffect(()=>{
    if (data.projectid) {
      getonlyproject({id:data.projectid,field:'state'}).then((res:any)=>{
        if (res.errorMessage){
          Modal.error({
            title: res.errorMessage,
          });
        }else{
          setProjectState(res.data.state)
        }
      })
    
    }
  },[])

  const onFinish = (values: any) => {
    const uploads = uploadRef?.current?.getFileList();

    if (uploads) {
      values.budgetfileurls = uploads.map((u:any)=>{
        return setToUrl(u)
      }).join(',')||null
    }else{
      values.budgetfileurls = ''
    }
    
    const finaluploads = finalUploadRef?.current?.getFileList();
    if (finaluploads) {
      values.finalfileurls = finaluploads.map((u:any)=>{
        return setToUrl(u)
      }).join(',')||null
    }else{
      values.finalfileurls = ''
    }
  


    

    if(values.contractids&&Array.isArray(values.contractids)){
        values.contractids = values.contractids.filter((e:any)=>e).map((e:any)=>e.value).join(',')
    }

    savebalance(values).then((res:any)=>{
      if (res.errorMessage) {
        Modal.error({
          title: res.errorMessage,
        });
      } else {
        onChange(res.data)
      }
      
    })
    
    
  };
  const onContracChange = (value:any)=>{
    var amount = value.reduce((acc:Number,cur:any)=>acc+cur.amount,0.00)
    form.setFieldsValue({final:amount})
  }
  return (
    <>
      <Form {...formItemLayout} disabled={data.creator && currentUser.wxuserid!=data.creator} form={form} onFinish={onFinish} style={{ maxWidth: 650 }} initialValues={data}>
        <Form.Item label="关联项目id" name="projectid" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="id" name="id" style={{display:'none'}}>
            <Input disabled/>
        </Form.Item>
        <Form.Item label="收支类型：" name="type"  rules={[{ required: true, message: 'Please input!' }]}>
          <Dictselect  type={"合同类型"} disabled/>
        </Form.Item>
        <Form.Item label={(data.type==BalanceTypes.INCOME?'收入':'支出')+"类型："} name="moneytype" rules={[{ required: true, message: 'Please input!' }]}>
            <Dictselect type={data.type==BalanceTypes.INCOME?'收入类型':'支出类型'}  />
        </Form.Item>
        {
          data.type==BalanceTypes.EXPEND && 
          <Form.Item  label="是否专票：" name={isFinal?'finalspecialinvoice':'specialinvoice'} style={{display:data.type==BalanceTypes.INCOME?'none':'flex'}} rules={[{ required: data.type==BalanceTypes.INCOME?false:true, message: 'Please input!' }]}>
              <Select options={[{value:0,label:'非专票'},{value:1,label:'专票'}]} />
          </Form.Item>
        }
        {
          data.type==BalanceTypes.EXPEND  && 
          <Form.Item label="关联合同：" name="contractids"  rules={[{ required: false, message: 'Please input!' }]}>
            <ContractSelect multiple={true} showupload={false} type={data.type} onChange={onContracChange} />
          </Form.Item>
          
        }
        <Form.Item  label="项目名称：" name="title" rules={[{ required: true, message: 'Please input!' }]}>
          <Input/>
        </Form.Item>

        {
          !isFinal && 
          <div>
            <Form.Item label={(data.type==BalanceTypes.INCOME?'收入':'支出')+"税率："} name="tax" rules={[{ required: data.type==BalanceTypes.INCOME, message: 'Please input!' }]}>
                
                <InputNumber prefix="%" style={{ width: '100%' }} />
            </Form.Item>
            
            
            <Form.Item label={"预算金额"} name="budget" rules={[{ required: true, message: 'Please input!' }]}>
              <InputNumber defaultValue={0} prefix="￥" style={{ width: '100%' }} />
            </Form.Item>
            <Form.Item label="预算备注：" name="budgetnote">
              <TextArea rows={4} />
            </Form.Item>
          </div>
        }
        {
          isFinal && 
          <div>
            <Form.Item label={"预算金额"} name="budget" rules={[{ required: true, message: 'Please input!' }]}>
              <InputNumber prefix="￥" style={{ width: '100%' }} disabled={isFinal}/>
            </Form.Item>
            <Form.Item label="决算金额" name="final" rules={[{ required: false, message: 'Please input!' }]}>
              <InputNumber prefix="￥" style={{ width: '100%' }} />
            </Form.Item>
            <Form.Item label="决算税率" name="finaltax" rules={[{ required: false, message: 'Please input!' }]}>
                {/* <Dictselect type={"税率"}  agentid={AGENTID}/> */}
                <InputNumber prefix="%" style={{ width: '100%' }} defaultValue={0}/>
            </Form.Item>
            <Form.Item label="备注：" name="finalnote">
              <TextArea rows={4} />
            </Form.Item>
            
          </div>
        }
        
          
          
          <Form.Item style={{paddingLeft:'22px'}} >
            <MyUploadFile
              name='budgetfileurls'
              label="预算附件："
              max={20}
              multiple={false}
              accept="image/*,.pdf"
              maxSize={100}
              listType="picture-card"
              defaultImage={defaultImage}
              uploadPath="contract"
              uploadType={3}
              ref={uploadRef}
            />
          </Form.Item>
        
        <Form.Item style={{paddingLeft:'22px'}} >
            <MyUploadFile
              name='finalfileurls'
              label="决算附件："
              max={20}
              multiple={false}
              accept="image/*,.pdf"
              maxSize={100}
              listType="picture-card"
              defaultImage={defaultImage2}
              uploadPath="contract"
              uploadType={3}
              ref={finalUploadRef}
            />
          </Form.Item>
        

        
        
        <Form.Item {...tailLayout}>
          <Space>
            <Button type="primary" htmlType="submit">
              {data.id?'提交':'创建'}
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
export default Addbalance