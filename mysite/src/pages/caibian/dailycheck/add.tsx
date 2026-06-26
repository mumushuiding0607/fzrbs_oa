import { CSSProperties, useEffect, useState } from "react"

import { Button, DatePicker, Form, Input, Modal, Row, Switch } from "antd"
import UserAutocomplete from "@/pages/finance/budget/common/userAutocomplete"
import Checksgroup from "./checksgroup"
import moment from "moment"
import { getproblems, getthirdno, savedailycheck } from "./service"
import Dictselect from "./dictselect"
import Roleselect from "@/pages/finance/role/roleselect"
import Userselect from "./user-select"
import { values } from "lodash"
const col:CSSProperties = {
  display:'flex',
  flexDirection:'column',
  background:'white'
}
const wrap:CSSProperties = {width:'100%',background:'white'}

const row:CSSProperties = {
  display:'flex',
  flexDirection: 'row',
  // justifyContent:'space-between',
  alignItems:'center',
  width:'100%',
  padding:0,
  gap: '2em',
}
const formItemLayout = {
  labelCol: {
    xs: { span: 10 },
    sm: { span: 10 },
  },
  wrapperCol: {
    xs: { span: 24 },
    sm: { span: 24 },
  },
};
const Add:React.FC<{onChange?:Function}> = ({onChange}) =>{
  const [datas,setDatas] = useState<any>([])
  const [form] = Form.useForm();
  const [obj,setObj] = useState<any>({formtype:2,formdate:moment(new Date())})
  const [formtype,setFormtype] = useState(2)
  const [screenWidth,setScreenWidth] = useState(window.innerWidth)
  const [loading, setLoading] = useState(false)
  const [thirdNo ,setThirdNo] = useState()
  window.addEventListener('resize',()=>{
    setScreenWidth(window.innerWidth)
  })
  useEffect(()=>{
    getproblems({pageSize:1000}).then((res:any)=>{
      if (res.data){
        setDatas(res.data.map((item:any,index:any)=>({...item,key:index+1,disabled: item.state!=0&&item.state!=formtype})))
      }
    })
    getthirdno().then((res:any)=>{
      if (res){
        setThirdNo(res)
      }
    })
    
  },[])

  const onFinish = async (values:any) => {
  
    if (loading){
      Modal.error({title:'不要重复提交'})
      return
    }
    setLoading(true)
    setTimeout(() => {
      setLoading(false)
    }, 5000);
    values.thirdNo = thirdNo
    if (values.formdate) {
      values.formdate = values.formdate.format('YYYY-MM-DD');
    }
    values.formtype = values.formtype?2:1
    if (values.center_director_username && Array.isArray(values.center_director_username)) {
      values.center_director_userid = values.center_director_username.map((e:any)=>e.value).join(',')
      values.center_director_username = values.center_director_username.map((e:any)=>e.label).join(',')
    }
    if (values.director_username && Array.isArray(values.director_username)) {
      values.director_userid = values.director_username.map((e:any)=>e.value).join(',')
      values.director_username = values.director_username.map((e:any)=>e.label).join(',')
    }
    if (values.general_director_username && Array.isArray(values.general_director_username)) {
      values.general_director_userid = values.general_director_username.map((e:any)=>e.value).join(',')
      values.general_director_username = values.general_director_username.map((e:any)=>e.label).join(',')
    }
    console.log(values)
    var temp = values.datas.filter((d:any)=>d.problem==1)||[]
    Modal.confirm({
      title: '当日有问题数量:'+temp.length,
      okText: '确认',
      cancelText: '取消',
      onOk: async () => {
        save(values)
      },
    })

  };
  const save = (values:any)=>{
    savedailycheck(values).then((res:any)=>{
      setLoading(false)
      if (res.errorMessage) {
        Modal.error({
          title: '报错',
          content: res.errorMessage,
        });
      } else {
        onChange && onChange(res)
      }
        
    })
  }
  const formtypeChange = (formtype:boolean)=>{
    setFormtype(formtype?2:1)
  }
  return (<>
    <div style={col}>
      <Form  form={form} onFinish={onFinish} style={{ maxWidth: 950 }} initialValues={obj}>
        
            <Form.Item label="id" name="id" style={{display:'none'}}>
                  <Input disabled/>
              </Form.Item>
              <Form.Item  name="center_director_userid" style={{display:'none'}}>
                <Input />
              </Form.Item>
            <Form.Item  name="director_userid" style={{display:'none'}}>
              <Input />
            </Form.Item>
            <Form.Item  name="general_director_userid" style={{display:'none'}}>
                <Input />
              </Form.Item>
            <div style={{...row,margin:'10px 0 0 10px'}}>
              <Form.Item labelCol={{style:{maxWidth:'50px'}}} style={{display:'flex',flexDirection:'row',flexWrap:"nowrap",marginBottom:0}} label="媒体："  name="typeid" rules={[{ required: true, message: 'Please input!' }]}>
                  <Dictselect typeid={2} multiple={false} style={{width:'120px'}}/>
              </Form.Item>
              <Form.Item labelCol={{style:{maxWidth:'50px'}}} style={{display:'flex',flexDirection:'row',flexWrap:"nowrap",marginBottom:0}} label="班次："  name="formtype" rules={[{ required: true, message: 'Please input!' }]}>
                  <Switch  onChange={formtypeChange} checkedChildren="夜班" size="default" unCheckedChildren="白班" defaultChecked />
              </Form.Item>
              <Form.Item labelCol={{style:{maxWidth:'50px'}}}  style={{display:'flex',flexDirection:'row',flexWrap:"nowrap",marginBottom:0}} label="日期："  name="formdate" rules={[{ required: true, message: 'Please input!' }]}>
                <DatePicker defaultValue={moment()} format="YYYY-MM-DD"  style={{width:'120px'}}/>
              </Form.Item>
            </div>
            
            <Form.Item  name="datas" >
              <Checksgroup datas={datas} />
            </Form.Item>
          <div style={{display:'flex',width:'100%',flexDirection:'row',marginTop:'20px',marginLeft:'10px'}}>
                
                <Form.Item label=""  name="director_username" rules={[{ required: true, message: 'Please input!' }]}>
                    <Userselect rolename={'值班主任'} placeholder="值班主任（采编新）" multiple={true} style={{width:'200px'}}/>
                </Form.Item>
                
              <Form.Item  label="" name="center_director_username" >
                  <Userselect rolename={'指挥中心值班主任'} placeholder="指挥中心值班主任" multiple={true} style={{width:'200px'}}/>
              </Form.Item>
              
              <Form.Item  label=""  name="general_director_username" rules={[{ required: true, message: 'Please input!' }]}>
                  <Userselect rolename={'值班总指挥'} placeholder="值班总指挥" multiple={true} style={{width:'200px'}}/>
              </Form.Item>
            </div>
          <div style={{...row,justifyContent:'center',marginTop:'20px'}}>
          <Form.Item>
              <Button type="primary" htmlType="submit" loading={loading} style={{width:'100px'}} className="login-form-button">
                提交
              </Button>
            </Form.Item>
          </div>
        </Form>
    </div>
  </>)
}
export default Add