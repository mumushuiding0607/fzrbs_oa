
import { Button, Input, Modal } from "antd";

import { useEffect, useRef, useState } from "react";
import { flowalter, flowalteritem, flowback, getflowdata } from "./service";
import Flow from "../budget/budget/flow";
import AppSelect from "./AppSelect";
import UserAutocomplete from "../budget/common/userAutocomplete";


const { Search } = Input;




const ViewFlow:React.FC<{thirdNo?:any,visible:boolean,onVisibleChange:Function}> = ({thirdNo='',visible=false,onVisibleChange}) =>{

  const [showModal,setShowModal] = useState(visible)
  const [showu,setShowu]=useState(false)
  const [data,setData]=useState<any>({})
  const [vkey,setVkey]=useState(0)
  const [stepSelect,setStepSelect]=useState<any>(0)
  const [itemSelect,setItemSelect]=useState<any>(0)
  const [roowSelect,setRowSelect]=useState<any>({})
  const [agentid,setAgentid]=useState<any>()
  const [searchValue,setSearchValue]=useState(thirdNo||'')

  useEffect(()=>{
    setShowModal(visible)
    setSearchValue(thirdNo||'')
    if (thirdNo&&thirdNo.length>0){
      getflowdata({thirdNo}).then(res=>{
      if (res.errorMessage){
          Modal.error({
            title: '报错',
            content: res.errorMessage,
          });
        }else{
          setData(res)
          if(res.agentid){
            setAgentid(res.agentid)
          }
        }
      })
    }
  },[visible,thirdNo])

  
  const onSearch = (e:any)=>{
   
    getflowdata({thirdNo:e,agentid:agentid}).then(res=>{
      if (res.errorMessage){
        Modal.error({
          title: '报错',
          content: res.errorMessage,
        });
      }else{
        setData(res)
      }
    })
  }
  return (

  <>
  <Modal
        title="流程"
        style={{ top: 20, }}
        visible={visible}
        onOk={() => {
          onVisibleChange(false)
        }}
        onCancel={() => onVisibleChange(false)}
        footer={null}
      >
        <AppSelect  multiple={false} value={agentid} onChange={(e:any)=>{

              setAgentid(e)
            }} />
        <Search
          placeholder="输入单号"
          allowClear
          enterButton="搜索"
          size="large"
          value={searchValue}
          onChange={(e:any)=>setSearchValue(e.target.value)}
          onSearch={onSearch}
        />
        <Flow key={vkey} data={data.viewdata} thirdNo={data.thirdNo}  statusCn={data.statusCn} step={data?.viewdata?.step+1} onRowClick={(item:any,index:any)=>{
          setStepSelect(index)
          setItemSelect(0)
          setRowSelect(item)
        }} onAlterApprover={(item:any,index:any,idx:any)=>{
          setStepSelect(index)
          setItemSelect(idx !== undefined ? idx : 0)
          setRowSelect(item)
          setShowu(true)
        }}></Flow>
        {
          data.viewdata!=0 &&
          <div>
            <Button onClick={()=>{
              Modal.confirm({
                title:'确认返回上一步吗？',
                content:'返回后不可撤回，请谨慎操作',
                onOk:()=>{
                  
                  flowback({thirdNo:data.thirdNo,agentid}).then((res:any)=>{ 
                    if (res.errorMessage) {
                      Modal.error({
                        title: res.errorMessage,
                      });
                    } else {
                      getflowdata({thirdNo:data.thirdNo}).then(res=>{
                        if (res.errorMessage){
                          Modal.error({
                            title: '报错',
                            content: res.errorMessage,
                          });
                        }else{
                          setData(res)
                        }
                      })
                    }
                  })
                }
              })
            }}>返回上一步</Button>
          </div>
        }
      </Modal>
      <Modal
        title="修改审批人"
        style={{ top: 20, }}
        visible={showu}
        onOk={() => {
          console.log(roowSelect)
          console.log('step:',stepSelect)
          setShowu(false)
        }}
        onCancel={() => setShowu(false)}
        footer={null}
      >
        <UserAutocomplete multiple={false} onChange={(e:any)=>{
          console.log(e)
          
          if (e){
            flowalteritem({thirdNo:data.thirdNo,agentid,step:stepSelect,userid:e.value,itemIndex:itemSelect}).then((res:any)=>{
            if (res.errorMessage) {
              Modal.error({
                title: res.errorMessage,
              });
            } else {
              getflowdata({thirdNo:data.thirdNo}).then(res=>{
                if (res.errorMessage){
                  Modal.error({
                    title: '报错',
                    content: res.errorMessage,
                  });
                }else{
                  setData(res)
                }
              })
            }

          })
          }
        }
      }/>
      </Modal>
      

  </>
  )
}

export default ViewFlow