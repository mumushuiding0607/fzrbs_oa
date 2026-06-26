import { Modal, Row, Select, Space } from "antd"
import { useEffect, useRef, useState } from "react";
import { getdailycheck, getproblemstates } from "./service";
import Checksgroup from "./checksgroup";
import ApproveNodes from "./approve_nodes";

const View:React.FC<{value?:any,onChange?:Function,thirdNo:any}> = ({thirdNo,value,onChange}) => {
  const [isMounted, setIsMounted] = useState(false);
  const [data,setData] = useState<any>({datas:[]})
  const [applydata,setApplydata] = useState<any>({})
  var [refresh,setRefresh] = useState(0)
  useEffect(()=>{
    if (!isMounted) {
      setIsMounted(true);
      getData()
    }
    
  },[isMounted])
  const getData = ()=>{
    getdailycheck({thirdNo}).then((res:any)=>{
      if (res.errorMessage){
        Modal.error({title:res.errorMessage})
        return
      }
      if (res.data){
        setData(res.data)
      }
      if (res.applydata){
        setApplydata(res.applydata)
      }
      setRefresh(++refresh)
      console.log(res)
    })
  }
  const onAppChange = (e:any)=>{
    getData()
  }
  return (<>
    <div>
      <Row>
        <Space style={{marginLeft:'10px',marginBottom:'10px'}}>
          <span>{data.formdate}</span>
          <span>{data.typeidname}</span>
          <span>{data.formtypename}</span>
        </Space>
      </Row>
      <Checksgroup key={refresh} datas={data.datas} view={true} />
      <Row>
        <ApproveNodes key={refresh} data={applydata} onChange={onAppChange}/>
      </Row>
    </div>
  </>)
}
export default View