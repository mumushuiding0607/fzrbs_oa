
import { Button, Form, Input, Modal, Space, message } from "antd";

import { useEffect, useRef, useState } from "react";
import MyUploadFile from "@/components/MyUploadFile";
import { CONTRACT_AGENTID } from "./config";
import { nullify } from "./service";
import { setToUrl } from "../utils";


const Nullify:React.FC<{id:any,onChange?:Function,visible:boolean,onVisibleChange:Function}> = ({id,onChange,visible=false,onVisibleChange}) =>{
  const uploadRef = useRef<any>();
  const [showModal,setShowModal] = useState(visible)
  useEffect(()=>{
    setShowModal(visible)
  },[visible])

  const act =()=>{
    var values:any = {id,agentid:CONTRACT_AGENTID}
    const uploads = uploadRef?.current?.getFileList();
    if (uploads && uploads.map) {
      values.nullifyurls = uploads.map((u:any)=>{
        return setToUrl(u)
      }).join(',')
    } else {
      Modal.error({title:'请上传作废证明'})
      return
    }
   

    nullify(values).then((res:any)=>{
      if (res.errorMessage){
        Modal.error({title:res.errorMessage})
      } else {
        onVisibleChange(false)
      }
    })
  }

  return (

  <>
  <Modal
        title="作废合同"
        style={{ top: 20, }}
        visible={showModal}
        onOk={() => {
          act()
        }}
        onCancel={() => onVisibleChange(false)}
        
      >
        <MyUploadFile
              
              name="fileurls"
              label="作废证明："
              max={5}
              multiple={false}
              accept="*/*"
              maxSize={50}
              listType="picture-card"
              defaultImage={[]}
              uploadPath="contract"
              uploadType={1}
              ref={uploadRef}
            />
      </Modal>
    

  </>
  )
}

export default Nullify