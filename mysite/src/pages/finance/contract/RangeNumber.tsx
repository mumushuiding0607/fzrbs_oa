import { InputNumber } from "antd"
import { useEffect, useState } from "react"




const RangeNumber:React.FC<{value?:any,onChange?:Function}> = ({value,onChange}) =>{
  const [v1,setV1] = useState()
  const [v2,setV2] = useState()
  useEffect(()=>{
    if (value) {
      setV1(value[0])
      setV2(value[1])
    }
  },[])
  const vc1 = (e:any)=>{
   
    setV1(e)
    onChange && onChange([e,v2])
  }
  const vc2 = (e:any)=>{
  
    setV2(e)
    onChange && onChange([v1,e])
  }
  return(<>
    
    <div style={{display:'flex'}}>
      <InputNumber value={v1} onChange={vc1} style={{width:'100%'}} placeholder="最小值，可不填"></InputNumber>
      -
      <InputNumber value={v2} onChange={vc2} style={{width:'100%'}} placeholder="最大值，可不填"></InputNumber>
    </div>

  </>)
}

export default RangeNumber