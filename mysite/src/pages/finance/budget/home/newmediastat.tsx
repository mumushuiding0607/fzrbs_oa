
import { Progress, Row } from "antd"
import '../common/datacard.css'
import { useEffect, useState } from "react"
const box:React.CSSProperties = {
  padding: '10px',
  color: 'black',
  display: 'flex',
  flexDirection: 'row',
  alignItems: 'center',
}
const textbox:React.CSSProperties = {
  marginLeft:'0.5vw',
  display:'flex',
  flexDirection: 'column',
  color:'gray',
}
const row:React.CSSProperties = {
  display:'flex',
  flexDirection: 'row',
  alignItems:'center'
}

const Newmediastat: React.FC<{data?:{},width?:string}> = ({data={},width}) => {

  
  return (
    <Row>
      <div id="datacard" style={{...box,marginLeft:'30px'}}>
        <div>
          <Progress
            type="dashboard"
            steps={8}
            percent={data.incomepercent}
            trailColor="rgba(0, 0, 0, 0.06)"
            size="small"
            strokeWidth={10}
          />
        </div>
        <div style={textbox}>
          <div style={row}>新媒体收入：</div>
          <div style={row}><span style={{fontSize:'20px',color:'black'}}>￥{data.finalincome}</span></div>
          <div >占比：{data.incomepercent}%</div>
        </div>
        <div>

        </div>
    </div>
    <div id="datacard"  style={{...box,marginLeft:'30px'}}>
        <div>
          <Progress
            type="dashboard"
            steps={8}
            size="small"
            percent={data.profitpercent}
            trailColor="rgba(0, 0, 0, 0.06)"
         
            strokeWidth={10}
          />
        </div>
        <div style={textbox}>
          <div style={row}>新媒体利润：</div>
          <div style={row}><span style={{fontSize:'20px',color:'black'}}>￥{data.finalprofit}</span></div>
          <div >占比：{data.profitpercent}%</div>
        </div>
        <div>

        </div>
    </div>

    </Row>
    
  )
}

export default Newmediastat