
import { Progress } from "antd"
const box:React.CSSProperties = {
  padding: '10px',
  color: 'black',
  display: 'flex',
  flexDirection: 'column',
}
const textbox:React.CSSProperties = {

  display:'flex',
  flexDirection: 'column',
  alignItems:'center',
  color:'gray'
}
const title:React.CSSProperties = {
  display: 'flex',
  flexDirection: 'row',
  fontSize:'1vw',
  fontWeight: 'bold',
  marginBottom:'1vw'
}
const row:React.CSSProperties = {
  display: 'flex',
  flexDirection: 'row',
  width: '100%',
  justifyContent: 'space-between',
  height:'35px'
}
const label:React.CSSProperties = {
  width: '5vw',
  fontSize: '0.5vw',
  color: 'gray'
}
const StaticsCard: React.FC<{data:{},width?:string}> = ({data,width}) => {
  return (
    <div style={{...box,width:width?width:'15vw'}}>
        <div style={title}>
          <div >{data.title}</div>
        </div>
        <div style={{...textbox,width:'10vw'}}>
          {
            data.datas.map((e,index)=>{
              return (<>
                  <div style={row}>
                    <div style={label}>{data.fields[index]}</div>
                    <Progress key={index} type="line" percent={e} size="small" />
                  </div>
                  
              
              </>)
            })
          }
          
    
        </div>
        <div>

        </div>
    </div>
  )
}

export default StaticsCard