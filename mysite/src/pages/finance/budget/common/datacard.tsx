
import { Progress } from "antd"
import './datacard.css'
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

const DataCard: React.FC<{data:any,target?:any,width?:string}> = ({data,target,width}) => {
  const [percent,setPercent] = useState(0)
  useEffect(()=>{
    if (target && target.income && data.stat) {
      var finalincome:any = parseFloat(data.stat.finalincome || 0)
      var income:any = parseFloat(target.income)
      var temp:any = finalincome/income
      var percent = temp.toFixed(2)*100
      setPercent(percent)
    }
  },[])
  
  return (
    <div id="datacard" style={{...box,width:width?width:'20vw'}}>
        <div>
          <Progress
            type="dashboard"
            steps={8}
            percent={percent}
            trailColor="rgba(0, 0, 0, 0.06)"
         
            strokeWidth={10}
          />
        </div>
        <div style={textbox}>
          <div style={row}>{data.label}</div>
          <div style={row}>至今：<span style={{fontSize:'1.5vw',color:'black'}}>￥{data.stat.finalincome?data.stat.finalincome:0}</span></div>
          <div >占比：{percent}%</div>
        </div>
        <div>

        </div>
    </div>
  )
}

export default DataCard