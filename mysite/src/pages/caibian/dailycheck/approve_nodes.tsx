import { Button, Modal, Row, Space, Timeline } from "antd";
import { useState } from "react";
import { flow } from "./service";
import { useModel } from "umi";

const ApproveNodes: React.FC<{data:any,onChange?:Function}> = ({onChange,data={ApprovalNodes:{ApprovalNode:[]}}}) => {
  const colors = ['','blue','green','red','gray']
  const stepnames = ['','审批中','同意','已驳回','已取消']
  const [speech,setSpeech] = useState('')
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;

  const action = (act:string)=>{
    flow({thirdNo:data.ThirdNo,speech,act}).then((res:any)=>{
      if (res.errorMessage) {
        Modal.error({
          title: '报错',
          content: res.errorMessage,
        });
      }else{
        Modal.success({
          title: act+'成功',
        });
        onChange && onChange(res)
      }
    })
  }
  return (
    <div>
      <h3 style={{marginBottom:'20px',marginTop:'20px'}}>审批流程</h3>
      <Timeline>
        {
          data?.ApprovalNodes?.ApprovalNode?.map((item:any,index:number)=>{
            var state = (data.approverstep-index)>=0?(' ● '+stepnames[item.NodeStatus]):''
            return (
              <Timeline.Item key={index} color={colors[item.NodeStatus]}  >
                
                {
                 item.Items.Item.map((ele:any,i:number)=><span key={i} style={{color: colors[ele.ItemStatus],marginRight:'10px'}}>{ele.ItemName}</span>)
                }
                <span>{state}</span>
              </Timeline.Item>
            )
          })
        }

      </Timeline>
      
      <Row>
        {/* 申请人 */}
        {
          currentUser.wxuserid==data.ApplyUserId && data.OpenSpstatus==1 &&
          <Space >
            <Button type="primary" onClick={()=>action('催办')}>催办</Button>
            <Button onClick={()=>action('撤销')}>撤销</Button>
          </Space>
        }
        {/* 审批人 */}
        {
          currentUser.wxuserid == data.approvalUserid && 
          <Space >
            <Button type="primary" onClick={()=>action('同意')}>同意</Button>
            <Button onClick={()=>action('驳回')}>驳回</Button>
          </Space>
        }
        
      </Row>
    </div>
  );
}

export default ApproveNodes;