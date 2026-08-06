import { Avatar, Badge, Button, Modal, Steps, Tooltip } from "antd"
import { message } from "antd"

import MyUploadFile from "@/components/MyUploadFile";
import { setToUrl, getFromUrl } from "../../utils";
import { useState, useRef } from "react";
import EditModal from "./EditModal";
import { alterspeech, delflownode, alterflownodefileurls } from "../../Flowtemplate/service";


const row:React.CSSProperties = {
  display: 'flex',
  flexDirection: 'row',
  width: '100%'
}
const col:React.CSSProperties = {
  display: 'flex',
  flexDirection: 'column',
  justifyContent: 'center'
}
const Flow: React.FC<{data:any,thirdNo?:any,step?:any,statusCn?:any,condition?:any,offlineAgree?:Function,onRowClick?:Function,onUpdate?:Function,onAlterApprover?:Function}> = ({thirdNo,onUpdate,onRowClick,offlineAgree,onAlterApprover,condition={},data={approval:[]},statusCn=[],step=0}) => {
  const Step = Steps.Step;
  const [modalVisible, setModalVisible] = useState(false);
  const [curitem,setCuitem]=useState<any>({})
  const [fileurlsModalVisible, setFileurlsModalVisible] = useState(false);
  const [currentNodeFileurls, setCurrentNodeFileurls] = useState<any[]>([]);
  const [currentNodeStep, setCurrentNodeStep] = useState<number>(0);
  const uploadRef = useRef<any>();
  const handleSave=(e:any)=>{
   
    alterspeech({
      thirdNo:thirdNo,
      step:curitem.step,
      speech:encodeURIComponent(e),
    }).then(res=>{
      if (res.errorMessage){
        Modal.error({
          title: res.errorMessage,
        });
      }else{
        setModalVisible(false)
        data.approval[curitem.step].speech = e
      }
    })
  }
  const handleDelete=(item:any,index:number)=>{
    Modal.confirm({
      title:'确认删除',
      content:'确定要删除此审批节点吗？',
      onOk:()=>{
        delflownode({
          thirdNo:thirdNo,
          step:index,
        }).then(res=>{
          if (res.errorMessage){
            Modal.error({
              title: res.errorMessage,
            });
          }else{
            onUpdate && onUpdate()
          }
        })
      }
    })
  }
  const handleUpdateFileurls = () => {
    const uploads = uploadRef?.current?.getFileList() || [];
    const fileurls = uploads.map((u: any) => setToUrl(u)).join(',');
    alterflownodefileurls({
      thirdNo: thirdNo,
      step: currentNodeStep,
      fileurls: fileurls,
    }).then((res: any) => {
      if (res.errorMessage) {
        Modal.error({ title: res.errorMessage });
      } else {
        message.success('更新成功');
        setFileurlsModalVisible(false);
        onUpdate && onUpdate();
      }
    });
  };
  return (
    <div >
        <Steps
          direction="vertical"
          size="small"
          current={step} style={{padding:'20px'}}>
            {(data?(data.approval||[]):[]).map((item:any,index:any)=> (
                <Step style={{width:'100%'}} key={index} title={
                  
                    <div>
                        <div style={row}>
                          <span style={{cursor:index >= step && item.status != 2 ? 'pointer' : 'not-allowed'}} onClick={()=>{
                            if (index >= step && item.status != 2) {
                              onAlterApprover && onAlterApprover(item, index)
                            }
                          }}>
                            <Avatar src={item.avatar} style={{ marginRight: 8 }} />
                          </span>
                          <div style={{...col,flexGrow:1}}>
                            {
                              item.next!='offline'&&item.offline!=1&&
                              <>
                                <span>{item.title+(item.status&&index<=step?('>'+statusCn[item.status]):'')}</span>
                              </>
                            }
                            
                            {
                              item.next=='offline'&&
                              <>
                                <span>线下上会处理</span>
                              </>
                            }
                            {
                              (condition.offline==1||item.offline==1)&&step==index&& <a href="#" onClick={()=>{
                                offlineAgree && offlineAgree()
                              }}>线下上会材料上传</a>
                            }
                            <span style={{color:'red'}} onClick={()=>{
                              setModalVisible(true)
                              setCuitem({...item,step:index})
                            }}>{item.speech}</span>
                          </div>
<div style={{textAlign:'right',float:'right',display:'flex',alignItems:'center'}}>
                            {item.date}
                            <Button type="text" danger size="small" icon={<span>×</span>} onClick={(e)=>{
                              e.stopPropagation()
                              handleDelete(item,index)
                            }} style={{marginLeft:8}} />
                          </div>
                        </div>
                        {
                         item.next!='offline'&&item.offline!=1&& (condition.offline!=1||condition.offline==1&&step!=index) && item.items && item.items.length>0 && 
                          <Avatar.Group
                            maxPopoverTrigger="click"
                            size="default"
                            maxStyle={{ color: '#f56a00', backgroundColor: '#fde3cf', cursor: 'pointer' }}
                          >
                            {
                              item.items.map((aitem:any,idx:number)=>(
                                <Tooltip key={'t'+idx} title={aitem.title} placement="top">
                                  <Badge dot={aitem.status==2}>
                                    <span style={{cursor:index >= step && aitem.status != 2 ? 'pointer' : 'not-allowed'}} onClick={()=>{
                                      if (index >= step && aitem.status != 2) {
                                        onAlterApprover && onAlterApprover(aitem, index, idx)
                                      }
                                    }}>
                                      <Avatar src={aitem.avatar}/>
                                    </span>
                                  </Badge>
                                </Tooltip>
                              ))
                            }
                          </Avatar.Group>
                          
                        }
                        {
                          item.fileurls && item.fileurls.length>0 && (
                            <div style={{display:'flex', alignItems:'center', gap:8, flexWrap:'wrap'}}>
                              {item.fileurls.split(',').filter(Boolean).map((url: string, idx: number) => {
                                const fileInfo = getFromUrl(url);
                                return (
                                  <span key={idx} style={{display:'inline-flex', alignItems:'center', gap:4}}>
                                    <a href={fileInfo.url} target="_blank">{fileInfo.name}</a>
                                    <span style={{color:'gray',fontSize:12}}>{fileInfo.time}</span>
                                  </span>
                                );
                              })}
                              <Button size="small" type="link" onClick={() => {
                                const files = item.fileurls ? item.fileurls.split(',').filter(Boolean).map((url: string) => getFromUrl(url)) : [];
                                setCurrentNodeFileurls(files);
                                setCurrentNodeStep(index);
                                setFileurlsModalVisible(true);
                              }}>更新附件</Button>
                            </div>
                          )
                        }
                        
                    </div>
                  
                } onClick={()=>{
                  onRowClick && onRowClick(item,index)
                }}></Step>
              ))}
              
              
        </Steps>
        
    
        
        
        <EditModal
          visible={modalVisible}
          initialValue={curitem.speech}
          onOk={handleSave}
          onCancel={() => setModalVisible(false)}
        />
        <Modal
          title="更新附件"
          visible={fileurlsModalVisible}
          onOk={handleUpdateFileurls}
          onCancel={() => setFileurlsModalVisible(false)}
          okText="保存"
          cancelText="取消"
        >
          <MyUploadFile
            name="fileurls"
            label="附件："
            max={20}
            multiple={true}
            accept="*/*"
            maxSize={100}
            listType="picture-card"
            defaultImage={currentNodeFileurls}
            uploadPath="contract"
            uploadType={2}
            ref={uploadRef}
          />
        </Modal>

    </div>
  )
}

export default Flow