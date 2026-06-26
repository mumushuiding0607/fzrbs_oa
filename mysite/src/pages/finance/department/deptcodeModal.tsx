
import { Button, Input, Modal} from "antd";

import { useEffect, useRef, useState } from "react";
import { getdeptcode, refreshprojectdeptcode, savedeptcode } from "./service";



const DeptcodeModal:React.FC<{department?:any,onChange?:Function,visible:boolean,onVisibleChange:Function}> = ({department,onChange,visible=false,onVisibleChange}) =>{
  const [dept,setDept]=useState<any>({})
  const [code,setCode]=useState('')
  const [showModal,setShowModal] = useState(visible)
  useEffect(()=>{
    setShowModal(visible)
    if (visible){
      if (department && department.id){
        setDept(department)
        setCode(department.code)
      } else {
        getdeptcode().then((res:any)=>{
          if (res.errorMessage){
            Modal.error({title:res.errorMessage})
          }else{
            if (res){
              setDept(res)
              setCode(res.code)
            }
            
          }
        })
      }
      
    }
  },[visible])
  return (

  <>
  <Modal
        title="部门简码设置"
        style={{ top: 20, }}
        visible={showModal}
        footer={null}
        onOk={() => {
          
        }}
        onCancel={() => onVisibleChange(false)}
      >
        <Input.Group compact>
          <Input style={{ width: 'calc(100% - 100px)' }} onChange={(e)=>{
            dept.code = e.target.value
            setDept(dept)
          }}  placeholder={dept?.code} />
          <Button type="primary" onClick={()=>{
            
            savedeptcode(dept).then((res:any)=>{
              
              if (res.errorMessage){
                Modal.error({title:res.errorMessage})
              }else{
                Modal.success({title:'更新成功'})
                onVisibleChange(false)
              }
            })
          }}>更新</Button>
   
        </Input.Group>
        <br></br>
        <Button type="default" onClick={()=>{
            
            Modal.confirm({
              title: '该操作不可逆',
              content: '点确定后，将更新【'+dept.name+'】对应的、所有的已提交计量项目的项目编码，该操作不可逆，请谨慎操作！',
              onOk() {
                refreshprojectdeptcode({departmentid:dept.id}).then((res:any)=>{
                  if (res.errorMessage){
                    Modal.error({title:res.errorMessage})
                  }else{
                    Modal.success({title:res.data||'更新成功'})
                    onVisibleChange(false)
                  }
           
                })
              },
            })
          }}>刷新已提交计量项目的项目编码</Button>
      </Modal>
    

  </>
  )
}

export default DeptcodeModal