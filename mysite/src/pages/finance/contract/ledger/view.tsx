import { Descriptions, Modal } from "antd"
import { useEffect, useState } from "react"
import { getledger } from "../service"
import moment from "moment"
import View from "../view"
import Filescard from "../filescard"

const ViewLedger:React.FC<{id:any}> = ({id}) => {
  var [refreshkey,setRefreshkey] = useState(1)
  const [data,setData] = useState<any>({})
  const [last,setLast] = useState(0)
  const [contract, setContract] = useState<any>({})
  const [viewmodal,setViewmodal] = useState(false)
  var [refreshKey, setRefreshKey]= useState(0)
  
  useEffect(() =>{
    setLast(id)
    if (id&&id!=last){
      setLast(id)
      getdata()
    }

    
    
  },[id])
  const getdata = async ()=>{
    console.log('get datas:',id)
    // 获取合同信息
    const res:any = await getledger({id})
    if (res.errorMessage){
      Modal.error({title:res.errorMessage})
    } else {
    
      
      setData(res.data)
      
      setRefreshkey(++refreshkey)
    }
    
  }
  return <>
  <div key={refreshkey}>
    <Descriptions
        bordered
        title={(
          <div style={{height:'30px'}}>
            台账详情
          </div>
        )}
        size={'default'}
        column={2}
        labelStyle={{width:120}}
      >
        <Descriptions.Item label="采购编号">{data?.ledgerserial}</Descriptions.Item>
        <Descriptions.Item label="采购类别">{data?.typename}</Descriptions.Item>
        <Descriptions.Item label="项目名称">{data?.title}</Descriptions.Item>
        <Descriptions.Item label="采购内容">{data?.content}</Descriptions.Item>
        <Descriptions.Item label="招标代理机构">{data?.agent}</Descriptions.Item>
        <Descriptions.Item label="采购方式">{data?.methodname}</Descriptions.Item>
        <Descriptions.Item label="成交供应商">{data?.partbname}</Descriptions.Item>
        <Descriptions.Item label="合同金额">{data.amount}</Descriptions.Item>
        <Descriptions.Item label="是否依约付款">{data.paydefault?'否':"是"}</Descriptions.Item>
        <Descriptions.Item label="验收结果">{data.result}</Descriptions.Item>
        <Descriptions.Item label="文件是否齐全">{data.file?'是':"否"}</Descriptions.Item>
        <Descriptions.Item label="合同编号">
        <div style={{textAlign:'left'}} onClick={()=>{
          setContract({id:data.contractid})
          setViewmodal(true)
          setRefreshKey(+refreshKey)
        }}>
          <p  style={{fontWeight:'bolder',margin:0}}>{data.serial}</p>
        </div>
        </Descriptions.Item>
        
        <Descriptions.Item label="采购人">{data.creatorname}</Descriptions.Item>
        
        <Descriptions.Item label="创建日期">{data.inserttime?moment(data.inserttime).format('YYYY-MM-DD'):''}</Descriptions.Item>
        <Descriptions.Item label="备注">{data.notes}</Descriptions.Item>
  
      </Descriptions>
      <Descriptions  bordered column={1} contentStyle={{padding:'25px'}} labelStyle={{width:120}}>
        {
          data.fileurls&&data.fileurls!="" && 
          <Descriptions.Item label="台账附件">
            <Filescard  urls={data.fileurls} mode='list'/>
         </Descriptions.Item>
         }
      </Descriptions>
  </div>
  <Modal
          width={850}
          style={{ top: 0}}
          visible={viewmodal}
          onOk={() => setViewmodal(false)}
          onCancel={() => setViewmodal(false)}
          footer= {null}
        >
          
          <View id={contract.id} key={refreshKey} paystate={contract.paystate} attachNumber = {contract.attachNumber}/>
        </Modal>

  </>
}
export default ViewLedger;