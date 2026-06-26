import { useEffect, useState } from "react"
import Filescard from "../../contract/filescard"
import { getallfileurs } from "./service"
import { Descriptions, Modal } from "antd"


const Allfiles:React.FC<{projectid?:any,mode?:string}> = ({projectid,mode='list'}) =>{
  const [list,setList]=useState<any>([])
  useEffect(()=>{
    
    if (projectid) {
      getallfileurs({projectid}).then((res:any)=>{
        if (res.errorMessage) {
          Modal.error({
            title: res.errorMessage,
          });
        } else {
          setList(res.list)
        }
      })
    }
  },[projectid])


  
  return (
    <>
      <Descriptions
              bordered
              size={'small'}
              column={1}
              labelStyle={{width:120}}
            >
     
              {
                list.map((item:any,index:any)=>{
                  return (
                    <Descriptions.Item label={item.label}>
                      <Filescard key={index} mode="list" urls={item.value}/>
                    </Descriptions.Item>
                  )
                })
              }
            </Descriptions>
    </>
  )
}
export default Allfiles