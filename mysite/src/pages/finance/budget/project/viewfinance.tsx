import { Descriptions } from "antd";
import moment from "moment";
import { useEffect, useState } from "react";
import { getprojectbyid } from "./service";

const Viewfinance:React.FC<{id:any,onchange?: Function}> = ({id,onchange}) =>{
  useEffect(()=>{
    getprojectbyid({id}).then((res:any)=>{
      setData(res)
    })
  },[id])
  // data
  const [data,setData] = useState<any>({contract:{},newmedia:{},data:{}})
  // dom
  return (
    <>

      <Descriptions
        bordered
        title={(
          <div style={{height:'30px'}}>
            项目财务详情

          </div>
        )}
        size={'default'}
        column={2}
        labelStyle={{width:120}}
      >
        <Descriptions.Item label="收入甲方">{data.contract.partincome}</Descriptions.Item>
        <Descriptions.Item label="支出乙方">{data.contract.partexpend}</Descriptions.Item>
        <Descriptions.Item label="收入合同总价">{data.contract.contractincome}</Descriptions.Item>
        <Descriptions.Item label="支出合同总价">{data.contract.contractexpend}</Descriptions.Item>
        <Descriptions.Item label="预算收入">{data.data.budgetincome}</Descriptions.Item>
        <Descriptions.Item label="决算收入">{data.data.finalincome}</Descriptions.Item>
        <Descriptions.Item label="预算支出">{data.data.budgetexpend}</Descriptions.Item>
        <Descriptions.Item label="决算支出">{data.data.finalexpend}</Descriptions.Item>
        <Descriptions.Item label="预算利润">{data.data.budgetincome-data.data.budgetexpend}</Descriptions.Item>
        <Descriptions.Item label="决算利润">{data.data.finalincome-data.data.finalexpend}</Descriptions.Item>
        <Descriptions.Item label="新媒体收入">{data.newmedia.finalincome||data.newmedia.budgetincome}</Descriptions.Item>
        <Descriptions.Item label="新媒体支出">{data.newmedia.finalexpend||data.newmedia.budgetexpend}</Descriptions.Item>
        <Descriptions.Item label="入账收入">{data.contract.incomeinvoiceamount}</Descriptions.Item>
        <Descriptions.Item label="入账成本">{data.contract.expendinvoiceamount}</Descriptions.Item>
        {/* <Descriptions.Item label="合同期限">{(data.starttime?data.starttime.substring(0,10):'')+'至'+(data.endtime?data.endtime.substring(0,10):'执行结束')}</Descriptions.Item> */}
  
      </Descriptions>

    </>
  )
}
export default Viewfinance