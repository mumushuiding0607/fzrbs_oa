import { Input, Switch } from "antd"
import { useState } from "react"

const col:React.CSSProperties = {display:'flex',background:'white',flexDirection:'column',padding:'10px 10px 0 10px',}
const box:React.CSSProperties = {
  width:'100%',
  background:'white',
  display:'flex',
  flexDirection:'row',
  justifyItems: 'center',
  alignItems: 'center',
  borderBottom:'1px solid #ccc'
}
const Check:React.FC<{value?:any,onChange?:Function,view?:boolean}> = ({value={},onChange,view=false}) =>{
  const digitMap:any= {
    0: '零',
    1: '一',
    2: '二',
    3: '三',
    4: '四',
    5: '五',
    6: '六',
    7: '七',
    8: '八',
    9: '九',
    10: '十',
    100: '百',
    1000: '千',
    10000: '万',
  }
  var [data,setData] = useState(value||{})
  var [rkey,setRkey] = useState(1)
  
  
  const schange = (problem:boolean)=>{

    data.problem = problem?1:0
    if (!problem) {
   
      data.remark =''
    }
    setData(data)
    setRkey(rkey+1)
    console.log('switch change:',data)
    onChange && onChange(data)
  }
  const dchange = (e:any)=>{
    data.remark = e.target.value

    setData(data)
    console.log('remark change:',data)
    onChange && onChange(data)
  }

  return (<>
     <div style={col}>
        <div style={{...box,color:value.disabled?'lightgray':'black'}}>
            <span style={{width:'30px'}}>{value.key||0}</span>
            <span style={{flexGrow:1,padding: '5px 0'}}><span>{value.name}</span><span style={{color:'red'}}>{value.state!=0?'':'*'}</span></span>
            <span>有无问题</span>
            <Switch disabled={value.disabled||view} onChange={(e)=>schange(e)} style={{margin:'0 10px 0 10px'}}  checkedChildren="有" unCheckedChildren="无" defaultChecked={value.problem} />
            
        </div>
        <span><Input value={data.remark} key={rkey} onChange={dchange} style={{minWidth:'100px',border:data.remark?'none':'1px solid red',display:data.problem?'block':'none'}} placeholder="具体描述" /></span>
     </div>
      
      
    
  </>)
}
export default Check