import { FilePptOutlined, UserOutlined } from "@ant-design/icons"
import { Avatar, Segmented } from "antd"
import { useEffect, useState } from "react"

const Filescard:React.FC<{urls?:string}> = ({urls}) =>{
  const [options, setOptions] = useState([])
  useEffect(()=>{
    if (urls) {
      var temp:any = urls.split(',').map(u=>{
        var arr = u.split('?name=')
        return {
          label:(
          <a href={u} target="blank">
            <div style={{ padding: 4 }}>
              <FilePptOutlined style={{fontSize:'100px'}}/>
              <div>{arr[arr.length-1]}</div>
            </div>
          </a>
          
          ),
          value: arr[arr.length-1]
        }

      })

      setOptions(temp)
    }
  },[])
  return (<>
  
    <div style={{flexWrap:'wrap',width:"100%"}}>
    <Segmented
      size="large"
      options={options}
    />
    </div>
  </>)
}
export default Filescard