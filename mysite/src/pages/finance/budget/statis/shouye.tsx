import { Badge, Card, Progress } from "antd"
import './common.css'
const wrapStyle: React.CSSProperties = {
  
  display: 'flex',
  flexWrap: 'wrap'
}
const wrapItem: React.CSSProperties = {
  margin: '0px 10px 10px 0'
}
const Shouye:React.FC = ()=>{
  return (
    <div style={{...wrapStyle}}>
      <div style={{...wrapStyle,width: '540px'}}>
                <div  style={{...wrapItem}}>
                      <Badge.Ribbon text="全年指标完成情况" color="cyan" placement='start'>
                        <Card style={{paddingTop: '20px'}}>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={100} format={(percent) => ` 目标:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={50} format={(percent) => ` 收入:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={30} format={(percent) => ` 利润:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>

                          
                        </Card>
                    </Badge.Ribbon>
                  </div>
       </div>
      <div style={wrapStyle}>
                <div  style={{...wrapItem}}>
                      <Badge.Ribbon text="年度目标完成情况" color="cyan" placement='start'>
                        <Card style={{paddingTop: '20px'}}>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={100} format={(percent) => ` 一季度:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={50} format={(percent) => ` 二季度:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={30} format={(percent) => ` 三季度:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={30} format={(percent) => ` 四季度:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>
                          
                        </Card>
                    </Badge.Ribbon>
                  </div>
       </div>
       <div style={wrapStyle}>
                <div  style={{...wrapItem}}>
                      <Badge.Ribbon text="收入情况" color="cyan" placement='start'>
                        <Card style={{paddingTop: '20px'}}>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={100} format={(percent) => ` 一季度:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={50} format={(percent) => ` 二季度:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={30} format={(percent) => ` 三季度:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={30} format={(percent) => ` 四季度:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>
                          
                        </Card>
                    </Badge.Ribbon>
                  </div>
       </div>
       <div style={wrapStyle}>
                <div  style={{...wrapItem}}>
                      <Badge.Ribbon text="利润情况" color="cyan" placement='start'>
                        <Card style={{paddingTop: '20px'}}>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={100} format={(percent) => ` 一季度:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={50} format={(percent) => ` 二季度:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={30} format={(percent) => ` 三季度:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>
                          <Progress type="dashboard" steps={8} strokeWidth={10} percent={30} format={(percent) => ` 四季度:${percent}%`} trailColor="rgba(0, 0, 0, 0.10)"/>
                         
                        </Card>
                    </Badge.Ribbon>
                  </div>
       </div>
    </div>
  )
}
export default Shouye