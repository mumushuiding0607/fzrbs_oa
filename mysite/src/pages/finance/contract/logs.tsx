import { AntDesignOutlined, UserOutlined } from '@ant-design/icons';
import { Avatar, Divider, Modal, Timeline, Tooltip } from 'antd';
import React, { CSSProperties, useEffect, useState } from 'react';
import { getlog, getlogs } from './service';
import dayjs from 'dayjs';
import weekday from "dayjs/plugin/weekday"
import localeData from "dayjs/plugin/localeData"
dayjs.extend(weekday)
dayjs.extend(localeData)
const row:CSSProperties = {
  display:'flex',
  flexDirection: 'row',
  alignItems:'center',
  width:'100%',
  gap: '2em',
}
const Logs: React.FC<{id:any,onChange?:Function}> = ({id,onChange}) => {
  const [modal,setModal]= useState(false)
  const [datas,setDatas] = useState<any>([])
  const [current,setCurrent] = useState(0)
   const dateFormat = 'YYYY-MM-DD HH:mm:ss'
  useEffect(()=>{
    if(id){
      getlogs({id}).then((res:any)=>{
        if(res.errorMessage){
          Modal.error({title:res.errorMessage})
        } else {
          setDatas(res.data)
        }
      })
    }
  },[])


  return (
    <div style={row}>
      <span>{(datas && datas.length>0)?'更新记录':'创建合同'}</span>
      {
        (datas && datas.length>0)&&<Avatar.Group
        maxCount={2}
        maxPopoverTrigger="click"
        size="large"
        maxStyle={{ color: '#f56a00', backgroundColor: '#fde3cf', cursor: 'pointer' }}
      >
        {
          datas.map((item:any,index:number)=>(
            <Tooltip key={'t'+index} title={item.name+' 于 '+item.inserttime+' 更新了此文档'} placement="top">
              <Avatar  src={item.avatar}/>
            </Tooltip>
          ))
        }
      </Avatar.Group>
      
      }
      {
        (datas && datas.length>0)&&<span><span>当前版本：由{datas[current].name+' 更新于 '+datas[current].inserttime+' '}</span><a href='#' onClick={()=>setModal(true)}> 查看更多</a></span>
      }
      <Modal
          title="日志"
          style={{ top: 20 }}
          visible={modal}
          onOk={() => setModal(false)}
          onCancel={() => setModal(false)}
          footer= {null}
        >
          
          <Timeline>
            
            {
              datas.map((item:any,index:number)=>(
                <Timeline.Item key={'ti'+index}><span>{item.name+' 于 '+item.inserttime+' 更新了此文档'}</span>，<a onClick={()=>{
                  setCurrent(index)
                  setModal(false)
                  // 查询纪录并返回
                  getlog({id:item.id}).then((res:any)=>{
                    if (res.errorMessage) {
                      Modal.error({
                        title: '报错',
                        content: res.errorMessage,
                      });
                    } else {
                      console.log(res.data )
                      if (res.data.supplementary && res.data.supplementary instanceof String) {
                        res.data.supplementary = JSON.parse(res.data.supplementary)
                      }
                      // res.data.starttime = res.data.starttime+' 00:00:00'
                      // res.data.endtime = res.data.endtime+' 00:00:00'
                      res.data.type = parseInt(res.data.type)
                      onChange && onChange(res.data)
                    }
                  })
                }}>点击查看</a></Timeline.Item>

              ))
            }
          </Timeline>
        </Modal>
    </div>
  )
}

export default Logs;