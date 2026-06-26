import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import {getrole, saverole} from './service'
import { Button, Divider, Form, Input, InputRef, Modal, Select, Space } from 'antd';
import { PlusOutlined } from '@ant-design/icons';
import Dictselect from '../budget/dict/dictselect';
import { savedict } from '../budget/dict/service';

const row:CSSProperties = {
  display:'flex',
  flexDirection: 'row',
  width:'100%',
  padding:'0 10px',

}
const formitem:CSSProperties={
  flex:2
}
const Roleselect:React.FC<{type?:string,value?:any,style?:any,onChange?:any, onSelect?:Function,disabled?:boolean,needAddItem?:boolean,agentid?:any}> =  ({agentid,style,needAddItem=true,type,value,onChange,onSelect,disabled})=>{

  const [options ,setOptions] = useState<any['options']>([])
  const [data, setData] = useState<any>({});
  const [form] = Form.useForm();
  var [fkey,setFkey]=useState(0)
  const getdata = ()=>{
    getrole({type,agentid}).then((res:any)=>{
      if (res) {
        res.map((e:any)=>{
          e.label = e.rolename
          if (!e.value && e.value!=0) e.value = e.id
          return e
        })
   

      }

      setOptions(res)
    })
  }
  useEffect( ()=>{
    
    getdata()
  },[])



  const handleSelect = (e:any)=>{
    

    const indx = options.findIndex((x:any)=>x.value==e)
    setData(options[indx])
    setFkey(++fkey)
    form.setFieldsValue(options[indx])
    onChange&&onChange(e,options[indx].label)
  }
  const onValueClear = ()=>{
    onChange&&onChange('',null)
  }

  const onFinish = (values: any) => {
    values.type=type
    if (values.power && Array.isArray(values.power)){
      values.powername = values.power.map((e:any)=>e.label).join(',')
      values.power = values.power.map((e:any)=>e.value).join(',')
    }
    values.agentid = agentid
    saverole(values).then(res=>{
      if (res.errorMessage) {
        Modal.error({title: res.errorMessage})
      } else {
       Modal.success({title:'成功'})
       onChange && onChange(res.data)
       getdata()
      }
    })
  };
  const dropdownRender = (menu:any)=>{
    return (<>
    
      {menu}
      
      
      {
        needAddItem &&
        <>
        <Space/>
        <Form form={form} key={fkey} onFinish={onFinish} initialValues={data}>
          <div style={row}>
            <Form.Item label="id" name="id" style={{display:'none'}}>
                <Input disabled/>
            </Form.Item>

            <Form.Item  style={formitem} label="" name="rolename" rules={[{ required: true, message: 'Please input!' }]}>
              <Input placeholder='输入名称'/>
            </Form.Item>
            <Form.Item   style={{flex:2}} label="" name="power" rules={[{ required: true, message: 'Please input!' }]}>
                <Dictselect  multiple={true} type='角色权限' agentid={agentid} needAddItem={false}></Dictselect>
              </Form.Item>
            <Form.Item >
            <Button   type="primary" htmlType="submit">
              {data.id?'更新':'新增'}
            </Button>

        </Form.Item>
        </div>
        </Form>
        
        </>
        
        
      }
      
    </>)
  }
  return (
    <div>

        <Select
            disabled={disabled}
            key={value}
            showSearch
            allowClear
            placeholder={type||'选择角色'}
            optionFilterProp="children"
            options={options}
            value={value}  onSelect={handleSelect}
            dropdownRender={dropdownRender}
            onClear={onValueClear}
            style={style||{width:'100%'}}
          />

    </div>
  )
}
export default Roleselect
