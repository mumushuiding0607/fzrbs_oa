
import { RedoOutlined } from "@ant-design/icons";
import { Button, Card, Col, Row, Statistic } from "antd";




const ProStat: React.FC<{data:any[],onChange?:Function}>= ({data=[],onChange})=>{
 
  return (
    <div>
            <Card key={'statcard'}>

              <Row>
                {
                  data.map((e:any,index:number)=>{
                    return <div key={index}   style={{position:'relative',padding:'5px 20px 5px 0'}}>
 
 <Statistic valueStyle={{ fontSize:'15px' }}  prefix={<span style={{color:'rgba(0, 0, 0, 0.45)',fontSize:'12px'}}></span>} title={e.label} value={e.value||0} precision={2} formatter='number' />
                    
                  </div>
                  })
                }
                {
                  onChange && <div style={{color:'#dc676d',fontWeight:'bolder'}}>
                  <div onClick={()=>{onChange&&onChange()}}><RedoOutlined style={{ fontSize: '30px' }}/></div>刷新
                  
                  
                </div>
                }
                
                  

                  
                </Row>
              </Card>
          </div>
  )

}

export default ProStat;