import { Button, Descriptions, Form,  Modal, Popover, Row, Table, Tabs } from "antd";

import { useEffect, useState } from "react";

import { getorderbyid } from "./service";

import Viewflow from "./flow/Viewflow";

import View from "../contract/view";
import Add from "../company/add";

import ProjectSelect from "../budget/project/projectSelect";
import Filescard from "../contract/filescard";
import ContractSelect from "../contract/contract-select";

import AdvitemList from "./AdvitemList";


// style
const tailLayout = {
  wrapperCol: { offset: 8, span: 16 },
};
const formItemLayout = {
  labelCol: {
    xs: { span: 3 },
    sm: { span: 3 },
  },
  wrapperCol: {
    xs: { span: 24 },
    sm: { span: 24 },
  },
};
const ViewModal:React.FC<{id:any,thirdNo?:any,onVisibleChange?:Function,visible:boolean,onApplyChange?:Function,defaultActiveKey?:any}> = ({id,thirdNo,visible=false,onVisibleChange,onApplyChange,defaultActiveKey}) =>{
  const [showModal,setShowModal] = useState(visible)
  const [contract, setContract] = useState<any>({})
  const [viewmodal,setViewmodal] = useState(false)
  const [project, setProject] = useState<any>({id:0})
  const [modal2, setModal2] = useState(false)
  var [tabkey,setTabkey]=useState(defaultActiveKey)
  var [applyKey,setApplyKey]=useState(0)
  const [obj,setObj] = useState<any>({})

  const [dataSource, setDataSource] = useState<any[]>([]);
  var [refreshKey,setRefreshKey] = useState(0)
  const [updateCompanyModal,setUpdateCompanyModal]=useState(false)
  const [company,setCompany]=useState<any>({})
  const [selectmodal,setSelectmodal]=useState(false)

  useEffect(()=>{
    setShowModal(visible)
    if (visible){
      getdata()
              
    }
  },[visible])

  const getdata=  ()=>{
    getorderbyid({orderid:id}).then((res:any)=>{
      if (res && res.data){
        setObj(res.data)
        setRefreshKey(++refreshKey)
        setTabkey('1')
      }
      
    })
    
  }
  const onChange = (e:any)=>{
    setApplyKey(++applyKey)
    onApplyChange && onApplyChange()
  }



  return (

  <div >
  <Modal
        title={
          <>
          
            <Tabs activeKey={tabkey} onChange={setTabkey}>
             <Tabs.TabPane tab="审批" key="1">
                 <Viewflow key={thirdNo} infoid={id} thirdNo={thirdNo} onchange={onChange}/>
             </Tabs.TabPane>
            <Tabs.TabPane tab="订单信息" key="2">
              <div key={'Descriptions'+refreshKey}>
                <Descriptions
                    bordered
                    size={'small'}
                    column={2}
                    labelStyle={{width:120}}
                  >

                    <Descriptions.Item label="订单编号">{obj?.SYS_DOCUMENTID}</Descriptions.Item>
                    <Descriptions.Item label="客户">{obj?.AO_Customer}</Descriptions.Item>
                    <Descriptions.Item label="主体">{obj?.partbname}</Descriptions.Item>
                    <Descriptions.Item label="业务员">{obj?.AO_Salesman}</Descriptions.Item>
                    <Descriptions.Item label="总金额">{(obj?.AO_AllMoney||0).toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,})}</Descriptions.Item>
                    <Descriptions.Item label="已收款">{(obj?.AO_ReceivedMoney||0).toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,})}</Descriptions.Item>
                    <Descriptions.Item label="欠款">{(obj?.AO_DebtMoney||0).toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,})}</Descriptions.Item>
                    <Descriptions.Item label="创建时间">{obj?.SYS_CREATED}</Descriptions.Item>
                    
                </Descriptions>

                <Descriptions
                    bordered
                    size={'small'}
                    column={1}
                    labelStyle={{width:120}}
                  >
                    {
                        obj.fileurls&&obj.fileurls!="" && 
                        <Descriptions.Item label="附件">
                          <Filescard  urls={obj.fileurls} mode='list'/>
                      </Descriptions.Item>
                      }
                  </Descriptions>
                  
                {/* 广告列表 */}
                <AdvitemList order={{SYS_DOCUMENTID: id}}  />

              </div>
            </Tabs.TabPane>
          </Tabs>
          
          </>
        }
        style={{ top: 20, }}
        visible={visible}
        width={800}
        onOk={() => {
          onVisibleChange && onVisibleChange(false)
        }}
        onCancel={() => onVisibleChange && onVisibleChange(false)}
        footer={null}
      >
        
        
      </Modal>
      <Modal
          width={400}
          centered
          title="选择项目"
          style={{ top: 0}}
          visible={selectmodal}
          onOk={() => setSelectmodal(false)}
          onCancel={() => setSelectmodal(false)}
          footer= {null}
        >
          
          <ProjectSelect></ProjectSelect>
        </Modal>
      <Modal
          width={850}
          style={{ top: 0}}
          visible={viewmodal}
          onOk={() => setViewmodal(false)}
          onCancel={() => setViewmodal(false)}
          footer= {null}
        >
          
          <View id={contract.id} key={'view'+refreshKey} paystate={contract.paystate} attachNumber = {contract.attachNumber}/>
        </Modal>
      <Modal
          key={'project'+refreshKey}
          title="项目信息"
          style={{ top: 20 }}
          width="60vw"
          visible={modal2}
          onOk={() => setModal2(false)}
          onCancel={() => setModal2(false)}
          footer={null}
        >
        </Modal>
      <Add key={'add'+refreshKey}  visible={updateCompanyModal} id={company.id} company={company.company}  onVisibleChange={setUpdateCompanyModal}></Add>
  </div>
  )
}

export default ViewModal
