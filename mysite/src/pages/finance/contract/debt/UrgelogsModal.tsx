import {  Avatar, Modal, Timeline } from "antd"
import { useEffect, useRef, useState } from "react"
import { geturgelogs } from "./service"
import Filescard from "../filescard"
import Urgelogs from "./urgelogs"




const row:React.CSSProperties = {
  display: 'flex',
  flexDirection: 'row',
  width: '100%'
}
const UrgelogsModal:React.FC<{contractid:any,visible:boolean,onVisibleChange:Function,debturgeid?:any,type?:any}> = ({type,contractid,debturgeid,visible=false,onVisibleChange})=>{


  return (
  
<Modal
        title="欠款催收进度"
        style={{ top: 20, }}
        width={800}
        visible={visible}
        onOk={() => {
          onVisibleChange(false)
        }}
        onCancel={() => onVisibleChange(false)}
        footer={null}
      >
        <Urgelogs  contractid={contractid} debturgeid={debturgeid} type={type}/>
      </Modal>

  
  )
}

export default UrgelogsModal