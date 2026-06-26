import { Select } from "antd"
import { useEffect, useRef, useState } from "react";
import { getproblemstates } from "./service";

const Problemstates:React.FC<{value?:any,onChange?:Function}> = ({value,onChange}) => {
  const [options,setOptions]=useState<any>([])
  const [isMounted, setIsMounted] = useState(false);
  useEffect(()=>{
    if (!isMounted) {
     
      setIsMounted(true);
      getproblemstates().then((res:any)=>{
        if (res && res.length && res.length>0){
          setOptions(res.map((item:any,index:any)=>{
            return {value:index,label:item}
          }))

        }
      })
    }
    
  },[isMounted])
  const handleChange = (value: any) => {
    onChange && onChange(value);
  };
  return (<>
  <Select
      defaultValue={0}
      onChange={handleChange}
      options={options}
    />
  </>)
}
export default Problemstates