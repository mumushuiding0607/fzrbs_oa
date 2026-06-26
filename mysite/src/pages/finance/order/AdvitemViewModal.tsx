import { Avatar, Button, Descriptions, Divider, Modal, Tabs, Tag, Typography, Input, Card } from "antd";
import TextArea from "antd/lib/input/TextArea";

import { useEffect, useState, useRef } from "react";

import { getAdvitem, getflowdata, flowact, getthirdno } from "./service";

import Flow from '../budget/budget/flow';

import Filescard from "../contract/filescard";

import { useModel } from 'umi';



import './common.css';
import { FlowStateEunm } from "../budget/config";
import ViewFlow from "../Flowtemplate/viewflow";

const { Title } = Typography;

const row: React.CSSProperties = {
  display: 'flex',
  flexDirection: 'row',
  width: '100%'
}

const AdvitemApprovalView: React.FC<{ thirdNo?: any, onchange?: Function, infoid?: any, info?: any, basic?: any, viewdata?: any, statusCn?: any[], step?: number, onFlowRefresh?: Function }> = ({
  thirdNo, onchange, infoid, info, basic, viewdata, statusCn, step, onFlowRefresh
}) => {
  const [speech, setSpeech] = useState('')
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const [approve, setApprove] = useState(false);
  const [newThirdNo, setNewThirdNo] = useState('')
  const [viewflowmodal, setViewflowmodal] = useState(false)

  const requestedRef = useRef(false);

  useEffect(() => {
    if (!thirdNo) {
      getthirdno().then((e: any) => setNewThirdNo(e))
    }
  }, [thirdNo])

  const act = (flow: { act: string; thirdNo?: string; speech?: string }) => {
    if (!flow.thirdNo) flow.thirdNo = thirdNo || info?.thirdNo
    if (!flow.speech) flow.speech = speech

    flowact(flow).then((flowres: any) => {
      if (flowres.errorMessage) {
        Modal.error({ title: flowres.errorMessage });
      } else {
        Modal.info({ title: '操作成功！' });
        onFlowRefresh && onFlowRefresh();
        onchange && onchange();
      }
    })
  }

  return (
    <div style={{ position: 'relative' }}>
      {
        !viewdata && <Card>暂无审批流程</Card>
      }

      {
        viewdata != 0 && viewdata &&
        <div>
          {
            basic?.userName &&
            <div>
              <div style={{ ...row, alignItems: 'center' }}>
                <Avatar src={basic?.avatar} size="large" />
                <Title style={{ height: '100%', display: 'flex', alignItems: 'center' }} level={4}>
                  {basic?.userName + '的审批申请'}
                  {
                    basic?.status != FlowStateEunm.NONE && <Tag color="red">{statusCn?.[basic?.status]}</Tag>
                  }
                </Title>
              </div>
              <Divider />
          
                <Descriptions
                  bordered
                  size={'small'}
                  column={2}
                  labelStyle={{ width: 120 }}
                >
                  {
                        info?.thirdNo&&
                        <Descriptions.Item label="审批单号"><span style={{color:"#1890FF"}} onClick={()=>{
                                setViewflowmodal(true)
                                
                            }}>{info?.thirdNo}</span></Descriptions.Item>
                      }
                  <Descriptions.Item label="申请类型">广告审批</Descriptions.Item>
                  <Descriptions.Item label="广告金额">{(info?.AI_AmountReceivable || info?.AI_AmountReceivable || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</Descriptions.Item>
                  <Descriptions.Item label="客户">{info?.AI_Customer || info?.AI_Customer}</Descriptions.Item>
<Descriptions.Item label="业务员">{info?.AI_Salesman || info?.AI_Salesman}</Descriptions.Item>
                  <Descriptions.Item label="发布日期">{info?.AI_PublishTime?.substring(0, 10)} ~ {info?.AI_PublishEndTime?.substring(0, 10)}</Descriptions.Item>
                  <Descriptions.Item label="发布平台">{info?.AI_Publication}</Descriptions.Item>
                  <Descriptions.Item label="大行业">{info?.AI_Trade}</Descriptions.Item>
                  <Descriptions.Item label="行业部门">{info?.AI_Org}</Descriptions.Item>
                  <Descriptions.Item label="规格">{info?.AI_Size}</Descriptions.Item>
                  <Descriptions.Item label="版位">{info?.AI_Field}</Descriptions.Item>
                  <Descriptions.Item label="颜色">{info?.AI_Color}</Descriptions.Item>
                  <Descriptions.Item label="投放天数">{info?.AI_PublishDayCount}</Descriptions.Item>
                  <Descriptions.Item label="广告内容" span={2}>{info?.AI_Content}</Descriptions.Item>
                  <Descriptions.Item label="备注" span={2}>{info?.AI_Memo}</Descriptions.Item>
                </Descriptions>
                <Descriptions
                  bordered
                  size={'small'}
                  column={1}
                  key={info?.SYS_DOCUMENTID}
                  labelStyle={{ width: 120 }}
                >
                  {
                    (info?.fileurls || info?.fileurls) && (info?.fileurls || info?.fileurls) != "" &&
                    <Descriptions.Item label="附件">
                      <Filescard key={info?.SYS_DOCUMENTID} urls={info?.fileurls || info?.fileurls} mode='list' />
                    </Descriptions.Item>
                  }
                </Descriptions>
           
            </div>
          }

          <Flow data={viewdata} condition={basic} statusCn={statusCn} step={step || 0} />

          {
            info?.thirdNo != null && info?.thirdNo != '' &&
            <div>
              {
                (basic?.approvalUserid || '').includes(currentUser?.wxuserid) && basic?.status != FlowStateEunm.PASS &&
                <>
                  <TextArea placeholder="审批意见" autoSize={{ minRows: 2, maxRows: 4 }} value={speech} onChange={(e) => setSpeech(e.target.value)} />
                  <Divider />
                </>
              }

              {
                !approve && basic?.status == FlowStateEunm.ING && (basic?.approvalUserid || '').includes(currentUser?.wxuserid) &&
                <>
                  <Button type="primary" onClick={() => act({ act: 'agree' })}>同意</Button>
                  <Button type="default" onClick={() => act({ act: 'reject' })}>驳回</Button>
                </>
              }

              {
                (basic?.userId || '').includes(currentUser?.wxuserid) && basic?.status == FlowStateEunm.ING &&
                <>
                  <Button type="primary" onClick={() => act({ act: 'urge' })}>催办</Button>
                  <Button type="default" onClick={() => act({ act: 'cancel' })}>撤销</Button>
                </>
              }
            </div>
          }
        </div>
      }
      <ViewFlow onVisibleChange={setViewflowmodal} visible={viewflowmodal} thirdNo={thirdNo}/>
    </div>
  );
};

const AdvitemViewModal: React.FC<{
  id: any, thirdNo?: any, onVisibleChange?: Function, visible: boolean, onApplyChange?: Function, defaultActiveKey?: any
}> = ({ id, thirdNo, visible = false, onVisibleChange, onApplyChange, defaultActiveKey }) => {
  const [tabkey, setTabkey] = useState(defaultActiveKey)
  const [obj, setObj] = useState<any>({})
  const [basic, setBasic] = useState<any>({})
  const [viewdata, setViewdata] = useState<any>({})
  const [statusCn, setStatusCn] = useState<any[]>([])
  const [step, setStep] = useState(0)
  const [info, setInfo] = useState<any>({})
  const [refreshKey, setRefreshKey] = useState(0)
  const [refreshInfo,setRefreshInfo] = useState(0)

  useEffect(() => {
    if (visible) {
      getdata()
    }
  }, [visible])

  const getdata = () => {
    getAdvitem({ advitemId: id }).then((res: any) => {
      if (res && res.data && res.data[0]) {
        const adData = res.data[0];
        setObj(adData)
        setRefreshKey(refreshKey+1)
        setTabkey('1')

        if (adData.thirdNo) {
          getflowdata({ thirdNo: adData.thirdNo, infoid: id }).then((flowRes: any) => {
            if (flowRes && !flowRes.errorMessage) {
              setBasic(flowRes.basic || {})
              setViewdata(flowRes.viewdata || {})
              setStatusCn(flowRes.statusCn || [])
              setInfo(flowRes.info || adData)
              setRefreshInfo(refreshInfo+1)
              if (flowRes.viewdata) {
                setStep(flowRes.viewdata.step + 1)
              }
            }
          });
        }
      }
    })
  }

  const onFlowRefresh = () => {
    if (obj.thirdNo) {
      getflowdata({ thirdNo: obj.thirdNo, infoid: id }).then((flowRes: any) => {
        if (flowRes && !flowRes.errorMessage) {
          setBasic(flowRes.basic || {})
          setViewdata(flowRes.viewdata || {})
          setStatusCn(flowRes.statusCn || [])
          setInfo(flowRes.info || obj)
          if (flowRes.viewdata) {
            setStep(flowRes.viewdata.step + 1)
          }
        }
      });
    }
  }

  const onChange = () => {
    onApplyChange && onApplyChange()
  }

  return (
    <div>
      <Modal
        title={
          <Tabs activeKey={tabkey} onChange={setTabkey}>
            <Tabs.TabPane tab="审批" key="1">
              <AdvitemApprovalView
                key={thirdNo}
                infoid={id}
                thirdNo={thirdNo}
                info={info}
                basic={basic}
                viewdata={viewdata}
                statusCn={statusCn}
                step={step}
                onchange={onChange}
                onFlowRefresh={onFlowRefresh}
              />
            </Tabs.TabPane>
            
          </Tabs>
        }
        style={{ top: 20 }}
        visible={visible}
        width={800}
        onOk={() => onVisibleChange && onVisibleChange(false)}
        onCancel={() => onVisibleChange && onVisibleChange(false)}
        footer={null}
      >
      </Modal>
    </div>
  )
}

export default AdvitemViewModal