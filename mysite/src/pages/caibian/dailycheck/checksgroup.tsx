import { useEffect } from "react"
import Check from "./check"


const Checksgroup:React.FC<{value?:any,onChange?:Function,datas?:any,view?:boolean}> = ({value={},onChange,datas=[],view=false}) =>{

  useEffect(()=>{
    
    if (onChange){
      onChange(datas||[])
    }
  },[datas])

  return (<>
  {
            datas.map((item:any,index:any)=>(
              <Check key={index} value={item} view={view} onChange={(e:any)=>{
         
                var i = datas.findIndex((t:any)=>t.id==e.id)
                datas[i] = e
                console.log(datas)
                onChange && onChange(datas)
              }}/>
            ))
          }
  </>
  )
}
export default Checksgroup