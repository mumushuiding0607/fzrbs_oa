import React, { useEffect, useState } from 'react';
import { Card, Col, Row, Statistic } from 'antd';
import { CheckOutlined, RedoOutlined } from '@ant-design/icons';


const DataColumn: React.FC<{data:any,onCheck?:Function,onRefresh?:Function}> = ({data,onCheck,onRefresh}) => {

  // const [current,setCurrent] = useState(-1)
  const [checks,setChecks]=useState<any>([])
  var [rkey,setRkey]=useState(1)

  const handleCheck = (index:any,id:any)=>{
    // 如果已经包含id，就删除
    if(checks.includes(id)){
      checks.splice(checks.indexOf(id),1)
    }else{
      checks.push(id)
    }
    
    setRkey(rkey+1)
    setChecks(checks)
    console.log(checks)

    onCheck && onCheck(checks.join(','))

  }
  
  return (
    <Card key={'statcard'}>
      <div style={{display:'flex',flexDirection:'row',width:'100%'}}>
        <Row  key={rkey} style={{flexGrow:1}}>
        {
          data.map((e:any,index:number)=>{
            return <Col key={index} span={4} onClick={()=>{
              handleCheck(index,e.value)
            }} style={{position:'relative'}}>
            {
              checks.includes(e.value) && <CheckOutlined size={200} color='#34b23d' style={{position:'absolute',bottom:'-10px',left:'-6px',fontSize:'50px',color:'gray' }}/>
            }
            <Statistic valueStyle={{ fontSize:'18px' }}  prefix={<span style={{color:'rgba(0, 0, 0, 0.45)',fontSize:'12px'}}>收入:</span>} title={e.label} value={e.stat.finalincome||e.stat.budgetincome||0} precision={2} formatter='number' />
            <Statistic valueStyle={{ fontSize:'18px' }} title={''} prefix={<span style={{color:'rgba(0, 0, 0, 0.45)',fontSize:'12px'}}>毛利:</span>} value={(e.stat.finalincome-e.stat.finalexpend)||(e.stat.budgetincome-e.stat.budgetexpend)||0} precision={2} formatter='number' />
            
          </Col>
          })
        }
        

          
        </Row>
        {
                  onRefresh && 
                  <Col span={1} style={{color:'#dc676d',fontWeight:'bolder',display:'flex',alignItems: 'center',justifyItems: 'center',flexDirection: 'column',marginTop: '12px'}}>
                    <div onClick={()=>{onRefresh&&onRefresh()}}><RedoOutlined style={{ fontSize: '30px' }}/></div>刷新
                  </Col>
                }  
      </div>
      
      </Card>
  )

}

export default DataColumn;