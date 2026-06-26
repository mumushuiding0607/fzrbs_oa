import React, { useState } from 'react';
import { Card, Col, Row, Statistic } from 'antd';
import { CheckOutlined } from '@ant-design/icons';


const StatCard: React.FC<{data:any,onDeadlineCheck?:Function,onOverdueCheck?:Function,onNopayconditionsCheck?:Function}> = ({data,onDeadlineCheck,onOverdueCheck,onNopayconditionsCheck}) => {
  const [deadline,setDeadline] = useState(false)
  const [overdue,setOverdue] = useState(false)
  const [nopayconditions,setNopayconditions] = useState(false)
  const dcheck = ()=>{
    setDeadline(!deadline)
    onDeadlineCheck && onDeadlineCheck(!deadline)
  }
  
  
  const ocheck = ()=>{
    setOverdue(!overdue)
    onOverdueCheck && onOverdueCheck(!overdue)
  }
  const ncheck = ()=>{
    setNopayconditions(!nopayconditions)
    onNopayconditionsCheck && onNopayconditionsCheck(!nopayconditions)
  }
    return (
      <Card key={'statcard'}>

        <Row gutter={16}>

            <Col span={4}>
              <Statistic title="总额" value={data.amount||0} precision={2} formatter='number' />
            </Col>
            <Col span={4}>
              <Statistic title="已收款" value={data.paycollection||0} precision={2} formatter='number' />
            </Col>
            <Col span={4}>
              <Statistic title="欠款"  value={data.left||0} precision={2} formatter='number' />
            </Col>
            <Col span={3} onClick={dcheck} style={{position:'relative'}}>
              {
                deadline && <CheckOutlined size={200} color='#34b23d' style={{position:'absolute',bottom:'-10px',left:'-6px',fontSize:'50px',color:'gray' }}/>
              }
              <Statistic valueStyle={{ fontWeight:'bolder' }} title="临期总数" value={data.deadlinenum||0} precision={0} formatter='number' />
              
            </Col>
            <Col span={3} onClick={ocheck}>
              {
                overdue && <CheckOutlined size={200} color='#34b23d' style={{position:'absolute',bottom:'-10px',left:'-6px',fontSize:'50px',color:'gray' }}/>
              }
              <Statistic title="逾期总数" valueStyle={{fontWeight:'bolder'}} value={data.overduenum||0} precision={0} formatter='number' />
            </Col>
            <Col span={3} onClick={ncheck}>
              {
                nopayconditions && <CheckOutlined size={200} color='#34b23d' style={{position:'absolute',bottom:'-10px',left:'-6px',fontSize:'50px',color:'gray' }}/>
              }
              <Statistic title="无履约条件" valueStyle={{fontWeight:'bolder'}} value={data.nopayconditions||0} precision={0} formatter='number' />
            </Col>
            <Col span={3} >
              
              <Statistic title="合同总数" valueStyle={{fontWeight:'bolder'}} value={data.total||0} precision={0} formatter='number' />
            </Col>
            
          </Row>
        </Card>
    )

}

export default StatCard;