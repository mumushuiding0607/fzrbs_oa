


import { Button, Form, Input, InputNumber, Modal, Row, Space, Timeline, message } from "antd";
import { useEffect, useState } from "react";
import './common.css'

const BankAccount:React.FC<{value?:any,onChange?:Function}> = ({value,onChange}) =>{

  const [v1,setV1] = useState('')
  const [v2,setV2] = useState('')

 
  useEffect(()=>{
    if (value && value.split) {
      var temp = value.split(' ')
      setV1(temp[0])
      setV2(temp[1])
      onChange && onChange(value)
    }
    
    
  },[value])


  const v1Change = (e:any)=>{
    setV1(e.target.value)
    
  }
  const v2Change = (e:any)=>{
    setV2(e.target.value)
  }
  const onBlur = ()=>{
    console.log('onBlur:', v1+' '+v2)
    onChange && onChange(v1+' '+v2)
  }
  return (

  <div id='mi'>

    <Row>
      <Input onBlur={onBlur} value={v1} onChange={v1Change} style={{ width: '100%' }} placeholder='开户行'/>
      <Input onBlur={onBlur} value={v2} onChange={v2Change} style={{ width: '100%' }} placeholder='银行账号'></Input>

    </Row>


  </div>
  )
}

export default BankAccount


