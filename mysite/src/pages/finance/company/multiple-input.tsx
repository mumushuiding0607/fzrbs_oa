


import { Button, Form, Input, InputNumber, Modal, Row, Space, Timeline, message } from "antd";
import { useEffect, useState } from "react";
import './common.css'
import { DeleteOutlined } from "@ant-design/icons";

const MultipleInput:React.FC<{value?:any,onChange?:Function,placeholder:any}> = ({value,onChange,placeholder=['','']}) =>{

  const [v1,setV1] = useState('')
  const [v2,setV2] = useState('')
  const [values,setValues] = useState<any>([])

 
  useEffect(()=>{
    if (value && value.split) {
      setValues(value.split(','))
      onChange && onChange(value.split(','))
    }
    
    
  },[value])
  const add = ()=>{
    if(!v1||!v2) {
      Modal.error({title:'值不能为空'})
      return
    }
    if (values.includes(v1+' '+v2)) {
      Modal.error({title:'值不能重复'})
      return
    }
    var temp = [...values,v1+' '+v2]
    setValues(temp)
    onChange && onChange(temp)
  }
  const del = (index:any)=>{
    var temp = values.filter((e:any,i:any)=>i!=index)
    setValues(temp)
    onChange && onChange(temp)
  }
  const v1Change = (e:any)=>{
    setV1(e.target.value?e.target.value.replace(/\s/g, ''):'')
  }
  const v2Change = (e:any)=>{
    setV2(e.target.value?e.target.value.replace(/\s/g, ''):'')
  }
  return (

  <div id='mi'>

    <Row>
      <Input onChange={v1Change} style={{ width: '39%' }} placeholder={placeholder[0]}/>
      <Input onChange={v2Change} style={{ width: '39%' }} placeholder={placeholder[1]}></Input>
      <Button type="default" style={{width:'22%'}} onClick={()=>{add()}}>新增</Button>
    </Row>
    <Timeline style={{marginTop:'15px'}}>
      {
        values.map((e:any,index:any)=>{
          return <Timeline.Item key={placeholder[0]+index}>
            <div ><DeleteOutlined style={{fontSize:'17px',paddingRight:'5px'}} size={100} color="red" onClick={()=>del(index)}/>{e?e.replace('-',''):''}</div>
           
          </Timeline.Item>
        })
      }
    </Timeline>

  </div>
  )
}

export default MultipleInput


