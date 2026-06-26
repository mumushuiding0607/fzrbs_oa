
import { CheckOutlined } from "@ant-design/icons";
import { Col, Row } from "antd";
import e from "express";
import { CSSProperties, useState } from "react";

const colstyle:CSSProperties={
  textAlign:'center',
  background:'#94c3e7ff',

  width:'150px',
  borderBottom:'1px solid gray',
  borderRight:'1px solid gray',
  alignItems:'center',
  justifyContent:'center',
  
}
const item:CSSProperties={
  padding:'5px',
  textAlign:'center',
  borderBottom:'1px solid gray',
  background:'#599af0ff',
  width:'100%',
  display:'flex',
  alignItems:'center',
  justifyContent:'center',
  color:'white'
}
const item2:CSSProperties={
  padding:'5px',
  textAlign:'center',
  borderBottom:'1px solid gray',
  background:'#599af0ff',
  width:'100%',
  display:'flex',
  alignItems:'center',
  justifyContent:'center',
  color:'#5aef5a',
  fontWeight:'bolder'
}
const FinancePrintPosition:React.FC<{data:any,onChange?:Function}> = ({data,onChange}) =>{
  
  const [position,setPosition] = useState(data?data:0)
  const set=(e:any)=>{
    setPosition(e)
    onChange && onChange(e)
  }
  return (

  <>
    <Row>
      <Col span={8} style={colstyle}>
        <Row>
          <span style={position==6?item2:item} onClick={()=>set(6)}>社长审批</span>
     
          
        </Row>
      </Col>
      <Col span={8} style={colstyle}>
        <Row><span style={position==5?item2:item} onClick={()=>set(5)}>分管领导、常务副总编</span></Row>

      </Col>
      <Col span={8} style={colstyle}>
        <Row><span style={position==1?item2:item} onClick={()=>set(1)}>公司负责人</span></Row>
        <Row><span style={position==2?item2:item} onClick={()=>set(2)}>部门负责人</span></Row>
      </Col>
    </Row>
    <Row>
      <Col span={8} style={colstyle}>
        <Row><span style={position==4?item2:item} onClick={()=>set(4)}>财务主管</span></Row>
        <Row><span style={position==3?item2:item} onClick={()=>set(3)}>会计</span></Row>
        <Row><span style={position==10?item2:item} onClick={()=>set(10)}>公司会计</span></Row>
      </Col>
      <Col span={8} style={colstyle}>
        <Row><span style={position==0?item2:item} onClick={()=>set(0)}>直接上级</span></Row>
        <Row><span style={position==7?item2:item} onClick={()=>set(7)}>人事主管（工资）</span></Row>
        <Row><span style={position==8?item2:item} onClick={()=>set(8)}>人事经办（工资）</span></Row>
      </Col>
      <Col span={8} style={colstyle}>
   
      </Col>
    </Row>
  </>
  )
}

export default FinancePrintPosition