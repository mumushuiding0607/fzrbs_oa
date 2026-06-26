import { Button, DatePicker,Form,Input,Select,Col, Row, Space, Modal, InputNumber, Tabs, Radio } from 'antd';
import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import Dictselect from '../dict/dictselect';

import {saveproject} from './service'
import { useModel } from 'umi';
import dayjs from 'dayjs';
import weekday from "dayjs/plugin/weekday"
import localeData from "dayjs/plugin/localeData"
import ContractSelect from '../../contract/contract-select';
import {  BalanceTypes, ProjectStatesEnum, ProjectTypesEnum } from '../config';
import Companyselect from '../../company/companyselect';
import UserAutocomplete from '../common/userAutocomplete';
import DepartmentTreeSelect from '../common/department_treeselect';
import MyUploadFile from '@/components/MyUploadFile';
import { getFromUrl, setToUrl } from '../../utils';
import DeptcodeModal from '../../department/deptcodeModal';
import ReportView from './reportview';
;
import Incomelist from './income/incomelist';

import Apply from '../budget/apply';
 
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
const Addpro:React.FC<{onChange:any,data:any,visible:boolean,onVisibleChange?:Function}> = ({visible,onVisibleChange,onChange,data={}}) =>{
  const [form] = Form.useForm();
  const [obj,setObj]=useState(data)
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  var [ck,setCk]=useState(0)
  var [tabkey,setTabkey]=useState(0)
  var [applyKey,setApplyKey]=useState(0)
  const dateFormat = 'YYYY-MM-DD'
  const [addincomeModal,setAddincomeModal]=useState(false)
  const [deptcodeModal,setDeptcodeModal]=useState(false)

  const uploadRef = useRef<AnimationPlayState>();
  const [defaultImage, setDefaultImage] = useState(obj.fileurls?obj.fileurls.split(',').map((url:any)=>{
    return getFromUrl(url)
  }):[])

  const uploadRef2 = useRef<AnimationPlayState>();
  const [defaultImage2, setDefaultImage2] = useState(obj.finalfileurls?obj.finalfileurls.split(',').map((url:any)=>{
    return getFromUrl(url)
  }):[])
  if (obj.starttime) {
    obj.starttime = dayjs(obj.starttime,dateFormat)
  }
  useEffect(() => {
    // 组件挂载时执行的代码
    console.log('组件已挂载');

    // 返回一个清理函数，在组件卸载或更新前执行
    // return () => {
    //   console.log('组件即将卸载或更新');
    // };
  }, []); 
  const onReset = () => {
    form.resetFields();
  };

  const onFinish = (values: any) => {
    
    

    if (values.charger instanceof Object) {
      values.chargername = values.charger.label
      values.charger = values.charger.value
      
    }

    const uploads = uploadRef?.current?.getFileList();
    if (uploads) values.fileurls = uploads.map((u:any)=>{
      return setToUrl(u)
    }).join(',')
    const uploads2 = uploadRef2?.current?.getFileList();
    if (uploads2) values.finalfileurls = uploads2.map((u:any)=>{
      return setToUrl(u)
    }).join(',')


    if (Array.isArray(values.contractids)){
      values.contractids = values.contractids.map((e:any)=>e.value).join(',')
    }
    if (Array.isArray(values.parta)){
      values.partaname = values.parta.map((e:any)=>e.label).join(',')
      values.parta = values.parta.map((e:any)=>e.value).join(',')
    }
    if (Array.isArray(values.partb)){
      values.partbname = values.partb.map((e:any)=>e.label).join(',')
      values.partb = values.partb.map((e:any)=>e.value).join(',')
    }
    if (values.starttime) {
      values.starttime = values.starttime.format('YYYY-MM-DD HH:mm:ss');
    }
  
    if (obj.id && obj.type!=values.type){
      return saveAlterType(values)
    }
 

    // 创建
    if (!obj.id &&[ProjectTypesEnum.FEIBAO,ProjectTypesEnum.HUODONG].indexOf(values.type)>-1){
      Modal.confirm({
        title: '您当前所选择的项目类别，需要经过预决算，是否继续提交？',
        okText: '确认',
        cancelText: '取消',
        onOk: async () => {
          save(values)
        },
      })
    }else{
      save(values)
    }

    
    
    
    
    
  };
  const saveAlterType= (values:any)=>{
    Modal.confirm({
        title: '您更改了项目类别，正在审批中的流程将自动撤回，是否继续？',
        okText: '确认',
        cancelText: '取消',
        onOk: async () => {
          save(values)
        },
        onCancel: () => {
          return
        },
      })
  }
  const save= (values:any)=>{
    saveproject(values).then((res)=>{
        if (res.errorMessage) {
          Modal.error({
            title: '报错',
            content: res.errorMessage,
          });
        } else {
          Modal.success({
            title: '操作成功',
          });
          setTabkey(++tabkey)
         
          res.data.starttime = dayjs(res.data.starttime,dateFormat)
          form.setFieldsValue(res.data)
         
          setDefaultImage(res.data.fileurls?res.data.fileurls.split(',').map((url:any)=>{
            return getFromUrl(url)
          }):[])
          setDefaultImage2(res.data.finalfileurls?res.data.finalfileurls.split(',').map((url:any)=>{
            return getFromUrl(url)
          }):[])
          onChange(res.data)
        }
  
      })
  }
  const onContractChange = (e:any)=>{
    console.log('onContractChange:',e)
    if (e && e.length>0){
      e = e.filter((d:any)=>d)
      var parta = e.map((d:any)=>d.parta).join(',')
      var partb = e.map((d:any)=>d.partb).join(',')
      var partaname = e.map((d:any)=>d.partaname).join(',')
      var partbname = e.map((d:any)=>d.partbname).join(',')
      form.setFieldsValue({parta})
      form.setFieldsValue({partaname})
      form.setFieldsValue({partb})
      form.setFieldsValue({partbname})
      setCk(++ck)
    }
  }
  const onApplyChange = (e:any)=>{
    setApplyKey(++applyKey)
  }
  return (
    <Modal
            title={<>
            <Tabs key={tabkey} defaultActiveKey="1">
              <Tabs.TabPane tab="项目信息" key="1">
                <Form {...formItemLayout}  disabled={obj.creator && currentUser.wxuserid!=obj.creator} form={form} onFinish={onFinish} style={{ width: 800 }} initialValues={obj}>
                  <Form.Item label="id" name="id" style={{display:'none'}}>
                      <Input disabled/>
                  </Form.Item>
                  <Form.Item label="合作单位：" name="partaname" style={{display:'none'}}>
                        <Input/>
                  </Form.Item>
                  <Form.Item label="立项主体：：" name="partbname" style={{display:'none'}} >
                      <Input/>
                  </Form.Item>
                  <Form.Item label="state" name="state" style={{display:'none'}}>
                      <Input disabled/>
                  </Form.Item>
                  <div style={row}>
                    <Form.Item style={formitem} label="项目名称"  name="title" rules={[{ required: true, message: 'Please input!' }]}>
                      <Input />
                    </Form.Item>
                    <Form.Item style={formitem} label="项目类别：" name="type"  rules={[{ required: true, message: 'Please input!' }]}>
                      <Dictselect type={"项目类别"}  needAddItem={false}/>
                    </Form.Item>
                  </div>
                  <div style={row}>
                    <Form.Item style={formitem} label="相关合同：" name="contractids">
                      <ContractSelect multiple={true} showupload={false} type={BalanceTypes.INCOME} onChange={onContractChange} />
                    </Form.Item>
                    <Form.Item label="立项部门:" name="pdepartmentid" style={formitem}  rules={[{ required: true, message: 'Please input!' }]}>
                      <DepartmentTreeSelect multiple={false} defaultValue={obj.pdepartmentid} />
                    </Form.Item>
                  </div>
                  <div style={row}>
                    <Form.Item style={formitem} label="立项主体：" name="partb" rules={[{ required: true, message: 'Please input!' }]}>
                      <Companyselect key={ck} placeholder="立项主体，报社相关公司" multiple={true} sign={1} />
                    </Form.Item>
                    <Form.Item style={formitem} label="合作单位：" name="parta" rules={[{ required: true, message: 'Please input!' }]}>
                      <Companyselect key={ck}  multiple={true} sign={1} />
                    </Form.Item>
                  </div>
                  <div style={row}>
                    <Form.Item style={formitem} label="立项时间：" name="starttime" rules={[{ required: true, message: 'Please input!' }]}>
                      <DatePicker format="YYYY-MM-DD" style={{width:'100%'}}/>
                    </Form.Item>
                    <Form.Item style={formitem} label="项目负责人：" name="charger" rules={[{ required: true, message: 'Please input!' }]}>
                      <UserAutocomplete multiple={false}/>
                    </Form.Item>
                  </div>
                  <div style={row}>
                    {
                      <Form.Item style={formitem} label="预算绩效比例：" name="performanceratio"  rules={[{ required: true, message: 'Please input!' }]}>
                        <InputNumber placeholder="非报:0-15%,其他:0-5%" style={{width:'285px'}}/>
                      </Form.Item>
                    }
                    {
                      obj.state!=null && obj.state<=ProjectStatesEnum.SUBMITTED&&
                      <Form.Item style={formitem} label="决算绩效比例：" name="finalperformanceratio"  rules={[{ required: true, message: 'Please input!' }]}>
                        <InputNumber placeholder="非报:0-15%,其他:0-5%" style={{width:'285px'}}/>
                      </Form.Item>
                    }
                  </div>
      
                  
                  <div style={row}>
                      <Form.Item style={formitem} label="是否有合同"  name="hascontract" rules={[{ required: true, message: 'Please input!' }]}>
                      <Select
                        style={{width:'285px'}}
                        options={[{value: 1,label: '有',},{value: 0,label:'无',}
                        ]}
                      />
                      </Form.Item>
                    </div>
                    <Form.Item labelCol={{span: 3, offset: 30}} label="预算备注：" name="content">
                        <TextArea rows={4} />
                      </Form.Item>
                    {
                      obj.state!=null && obj.state>=ProjectStatesEnum.FINAL&&
                      <Form.Item labelCol={{span: 3, offset: 30}} label="决算备注：" name="finalcontent">
                        <TextArea rows={4} />
                      </Form.Item>
                    }
                  
                  
                  {/* 预算附件到了决算阶段不能修改 */}
                  {
                    (obj.state<ProjectStatesEnum.FINAL||[ProjectTypesEnum.CHUNXIN,ProjectTypesEnum.HUODONG,ProjectTypesEnum.OFFLINE].includes(obj.type)) &&
                    <div style={row}>
                      <Form.Item style={{paddingLeft:'30px'}}>
                        <MyUploadFile
                          name="fileurls"
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
                    </div>
                  }
                  {/* 决算附件，决算之后不可修改 */}
                  {
                    (obj.state>ProjectStatesEnum.BUDGET||[ProjectTypesEnum.CHUNXIN,ProjectTypesEnum.HUODONG,ProjectTypesEnum.OFFLINE].includes(obj.type)) &&
                    <div style={row}>
                      <Form.Item style={{paddingLeft:'30px'}}>
                        <MyUploadFile
                          name="finalfileurls"
                          label="决算附件："
                          max={20}
                          multiple={false}
                          accept="image/*,.pdf"
                          maxSize={100}
                          listType="picture-card"
                          defaultImage={defaultImage2}
                          uploadPath="contract"
                          uploadType={3}
                          ref={uploadRef2}
                          
                        />
                      </Form.Item>
                    </div>
                  }
                  <Form.Item labelCol={{span: 3, offset: 30}} label="预询价:" name="inquire">
                            <Radio.Group onChange={(e)=>{
                              if (e.target.value==1){
                                Modal.info({
                                  title: '你选择了预询价，不论金额大小，审批流程按照大金额进行(如30万以上)'
                                });
                              }
                            }}>
                              <Radio value={0} defaultChecked> 否 </Radio>
                              <Radio value={1}> 是 </Radio>
                            </Radio.Group>
                          </Form.Item>
                  <Form.Item {...tailLayout}>
                  <Space>
                    <Button type="primary" htmlType="submit">
                      提交
                    </Button>
                    <Button htmlType="button" onClick={onReset}>
                      清空
                    </Button>
                    <Button type="link" onClick={()=>setDeptcodeModal(true)}>部门简码</Button>
                  </Space>
                </Form.Item>
                </Form>
              </Tabs.TabPane>
              {
                obj.id &&
                <>
                
                <Tabs.TabPane tab="收入" key="4">
                    <Incomelist  pid={obj.id} balancetype={BalanceTypes.INCOME} onlyBalance={false}/>
                </Tabs.TabPane>
                <Tabs.TabPane tab="支出" key="5">
                  <Incomelist  pid={obj.id} balancetype={BalanceTypes.EXPEND} onlyBalance={false}/>
                </Tabs.TabPane>
                <Tabs.TabPane tab="预算报告" key="2">
                  
                  <ReportView key={'budgetreport'+obj.id} id={obj.id} field={'budgetreport'} edit={obj.creator==currentUser.wxuserid} onChange={(text:any)=>{
                    obj.budgetreport = text
                    
                    setObj(obj)
                  }}/>
                </Tabs.TabPane>
                <Tabs.TabPane tab="决算报告" key="3">
      
                  <ReportView key={'finalreport'+obj.id} id={obj.id} field={'finalreport'} edit={obj.creator==currentUser.wxuserid }  onChange={(text:any)=>{
        
                          obj.finalreport = text
                          setObj(obj)
                          
                    }}/>
                </Tabs.TabPane>
                <Tabs.TabPane tab="提交审批" key="6">
                    <Apply key={applyKey} data={obj} showReport={false} onchange={onApplyChange}/>
                </Tabs.TabPane>
                </>

              }
              
            </Tabs>
            </>}
            style={{ top: 20 }}
            width={880}
            maskClosable={false}
            visible={visible}
            onOk={() => onVisibleChange &&onVisibleChange(false)}
            onCancel={() => onVisibleChange &&onVisibleChange(false)}
            footer={null}
          >

      <DeptcodeModal visible={deptcodeModal} onVisibleChange={()=>setDeptcodeModal(false)}/>
    </Modal>
  )
}
export default Addpro