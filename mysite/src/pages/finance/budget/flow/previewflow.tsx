
import { Button, DatePicker,Form,Input,InputNumber,Modal,Radio,Select,Space,Upload } from 'antd';
import React, { useState } from 'react';
import dayjs from 'dayjs';


import weekday from "dayjs/plugin/weekday"
import localeData from "dayjs/plugin/localeData"

import UserAutocomplete from '../common/userAutocomplete';
import Dictselect from '../dict/dictselect';
import AppSelect from '../../Flowtemplate/AppSelect';
import DepartmentTreeSelect from '../common/department_treeselect';
import Companyselect from '../../company/companyselect';
import { getflow, getinvoicingflow } from './service';
import Flow from '../budget/flow';
import { previewdebtflow } from '../../contract/service';
import { getcommonflow } from '../../Flowtemplate/service';

 
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


const PreViewFlow:React.FC<{data?:any,visible:boolean,onVisibleChange:Function}> = ({data={},onVisibleChange,visible=false}) =>{
  const [form] = Form.useForm();
  const [formkey,setFormkey]=useState(0)

 
  const onReset = () => {
    form.resetFields();
  };

   const  onFinish = async (values: any) =>  {

    

    if (values.userid){
      values.userid = values.userid.value
    }
    if (values.charger){
      values.charger = values.charger.value
    }
    if (values.partb){
      values.partbname = values.partb.label
      values.partb = values.partb.value
    }
    if (values.types && Array.isArray(values.types)){
      values.types = values.types.map((e:any)=>e.value).join(',')
    }
    if (values.projecttype && Array.isArray(values.projecttype)){
      values.projecttype = values.projecttype.map((e:any)=>e.value).join(',')
    }
 
    console.log(values)
    var res:any = {}
    switch (values.agentid) {
      case 1000080:
        res = await getflow(values)
        break;
      case 1000085:
        res = await getinvoicingflow(values)
        break;
      case 1000078:
        res = await previewdebtflow(values)
        break
      default:
        res = await getcommonflow(values)
        break;
    }
    if (res.errorMessage) {
        Modal.error({
          title: '报错',
          content: res.errorMessage,
        });
      } else {
        Modal.confirm({
          title:"请确认流程是否正确",
          bodyStyle:{marginLeft:0},
          width: '600px',
          centered:false,
          content:(
            <div style={{marginLeft:'0!important'}}>
              <Flow data={res.viewdata}  statusCn={res.statusCn} step={res.tep} thirdNo={res.viewdata?.thirdNo} onUpdate={()=>{
                  onVisibleChange(false)
                }}></Flow>
              {
                res.invoicers!=null&&
                <div>开票人: <span style={{color:'gray'}}>{res.invoicers}</span></div>
              }
              {
                res.viewdata&&res.viewdata.notify!=null&&
                <div>抄送人: <span style={{color:'gray'}}>{res.viewdata?.notify}</span></div>
              }
              <div>流程: <span style={{color:'gray'}}>{res.viewdata?.templatename}</span></div>
              <div>流程id: <span style={{color:'gray'}}>{res.viewdata?.templateid}</span></div>
            </div>
          )
        })
      }

    
    
  };


  return (
    <>
    <Modal
        title="预览流程"
        style={{ top: 20, }}
        visible={visible}
        onOk={() => {
          onVisibleChange(false)
        }}
        onCancel={() => onVisibleChange(false)}
        footer={null}
        
      >
      <Form {...formItemLayout} key={formkey} form={form} onFinish={onFinish} style={{ maxWidth: 650 }} initialValues={data}>
  
        <Form.Item label="应用" name="agentid" rules={[{ required: true, message: 'Please input!' }]}>
            <AppSelect multiple={false} onChange={(e:any)=>{
     
              setFormkey(e)
            }} />
        </Form.Item>

        {
          formkey==1000080&&
          <>
          
          <Form.Item label="审批类型："  name="types" rules={[{ required: true, message: 'Please input!' }]}>
            <Dictselect type='审批类型' multiple={false}/>
          </Form.Item>
          <Form.Item label="立项部门:" name="pdepartmentid"   rules={[{ required: true, message: 'Please input!' }]}>
            <DepartmentTreeSelect multiple={false}  />
          </Form.Item>
          <Form.Item  label="立项主体：" name="partb" rules={[{ required: true, message: 'Please input!' }]}>
            <Companyselect placeholder="立项主体，报社相关公司" multiple={false} sign={1} />
          </Form.Item>
          <Form.Item  label="项目负责人：" name="charger" rules={[{ required: true, message: 'Please input!' }]}>
            <UserAutocomplete multiple={false}/>
          </Form.Item>
          <Form.Item label="项目类别："  name="projecttype" rules={[{ required: true, message: 'Please input!' }]}>
            <Dictselect type='项目类别' multiple={false}/>
          </Form.Item>
          <Form.Item  label="预询价:" name="inquire">
            <Radio.Group>
              <Radio value={0} defaultChecked> 否 </Radio>
              <Radio value={1}> 是 </Radio>
            </Radio.Group>
          </Form.Item>
          
          </>
        }
        {
          formkey==1000085&&
          <>
            <Form.Item   label="审批类型：" name="type" rules={[{ required: true, message: 'Please input!' }]}>
              <Dictselect type={"开票审批"} multiple={false}  needAddItem={false}/>
            </Form.Item>
            <Form.Item  label="开票单位：" name="partb" rules={[{ required: true, message: 'Please input!' }]}>
              <Companyselect placeholder="开票单位，报社相关公司" multiple={false} sign={1} />
            </Form.Item>
            <Form.Item   label="合同业务：" name="contract" rules={[{ required: true, message: 'Please input!' }]}>
              <Dictselect type={"合同业务类型"} multiple={false}  needAddItem={false}/>
            </Form.Item>
            <Form.Item   label="有无合同：" name="hascontract" rules={[{ required: true, message: 'Please input!' }]}>
              <Select options={[
                  {
                    value: 0,
                    label: '无',
                  },
                  {
                    value: 1,
                    label: '有',
                  }
                ]}
              />
            </Form.Item>
          
          </>
        }
        {
          formkey==1000078&&
          <>
            <Form.Item   label="清欠方式：" name="urgetype" rules={[{ required: true, message: 'Please input!' }]}>
              <Dictselect type={"清欠方式"} multiple={false}  needAddItem={false}/>
            </Form.Item>

          </>
        }
        {
          formkey==1000083&&
          <>
            <Form.Item   label="刊物" name="publicationid" rules={[{ required: false, message: 'Please input!' }]}>
              <Dictselect
                type="刊物"
                multiple={false}
                needAddItem={false}
                placeholder="选择发布平台"
                

              />
            </Form.Item>

          </>
        }
        
        <Form.Item label="金额："  name="amount" rules={[{ required: false, message: 'Please input!' }]}>
          <InputNumber  style={{width:'100%'}} placeholder="金额"></InputNumber>
        </Form.Item>
        

        <Form.Item  label="用户："  name="userid" rules={[{ required: true, message: 'Please input!' }]}>
            <UserAutocomplete multiple={false}/>
        </Form.Item>


        <Form.Item {...tailLayout}>
        <Space>
          <Button type="primary" htmlType="submit">
            预览
          </Button>
          <Button htmlType="button" onClick={onReset}>
            清空
          </Button>
        </Space>
      </Form.Item>
      </Form>
      </Modal>
    </>
  )
}
export default PreViewFlow