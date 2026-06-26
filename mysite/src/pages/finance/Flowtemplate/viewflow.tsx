
import { Button, Input, Modal, Radio } from "antd";

import { useEffect, useRef, useState } from "react";
import { addsigner, flowalter, flowalteritem, flowback, getflowdata } from "./service";
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
  const [alterMode, setAlterMode] = useState<'transfer' | 'addsigner'>('transfer');

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
          // 只允许当前和未来步骤加签（与后端校验一致）
          if (index < (data.viewdata?.step ?? 0)) {
            Modal.warning({ title: '已审批节点无法加签' });
            return;
          }
          Modal.confirm({
            title: '选择操作',
            content: (
              <Radio.Group
                defaultValue="transfer"
                onChange={(e:any)=>setAlterMode(e.target.value)}
              >
                <Radio.Button value="transfer">转交（替换原审批人）</Radio.Button>
                <Radio.Button value="addsigner">加签（插入新节点）</Radio.Button>
              </Radio.Group>
            ),
            onOk:()=>{
              setStepSelect(index)
              setItemSelect(idx !== undefined ? idx : 0)
              setRowSelect(item)
              setShowu(true)
            }
          })
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
          if (!e) return;
          const isAdd = alterMode === 'addsigner';
          const api = isAdd ? addsigner : flowalteritem;
          const params = isAdd
            ? { thirdNo: data.thirdNo, agentid, step: stepSelect, userid: e.value }
            : { thirdNo: data.thirdNo, agentid, step: stepSelect,
                userid: e.value, itemIndex: itemSelect };

          api(params).then((res:any)=>{
            if (res.errorMessage) {
              Modal.error({
                title: res.errorMessage,
              });
            } else {
              setAlterMode('transfer');   // 重置回默认「转交」
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
      }/>
      </Modal>
      

  </>
  )
}

export default ViewFlow