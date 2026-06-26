
import { Button, Modal, Table, Tag } from 'antd';

import React, { CSSProperties, useEffect, useState } from 'react';
import View from './view';
import { getlist } from './service';


const labelStyle:CSSProperties={
  color:'gray'
}
const valueStyle:CSSProperties={
  color:'black',
  marginRight: '20px'
}
// balancetype 15收入，16支出
const ContractsTable: React.FC<{contractids:any,visible?:boolean,onClose?:Function,}> = ({contractids,visible=false,onClose}) =>{
  const [modal1, setModal1] = useState(visible)
  const [datas,setDatas] = useState<any[]>([])
  const [contract, setContract] = useState<any>({})
  const [viewmodal,setViewmodal] = useState(false)
  var [refreshKey,setRefreshKey] = useState(0)
  let columns = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      width: 65,
      render:(_:any,record:any,index:number)=>`${index+1}`
    },
    {
      title:'合同编号',
      dataIndex:'serial',
      key:'serial',
      width: 100,
      render: (text:any,record:any)=>{
        return <span style={{color:'#1890FF'}} onClick={()=>{
          setViewmodal(true)
          setContract(record)
          setRefreshKey(+refreshKey)
        }}>{text}</span>
      }
    },
    {
      title:'付款方名称',
      dataIndex:'partaname',
      key:'partaname',
      width: 200
    },
    {
      title:'合同金额',
      dataIndex:'amount',
      key:'amount',
      width: 120,
      render: (text:any,record:any)=>(
        <>
          {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
        </>
      )
    }
  ];
  useEffect(()=>{
    console.log('useEffect:',contractids)
    if (contractids){
      getlist({contractids}).then((res:any)=>{
        setDatas(res.data)
      })
    }
    
    setModal1(visible)
  },[visible])
  return (
    <>
    <Modal

        title='合同'
        style={{ top: 20 }}
        width={980}

        visible={modal1}
        onOk={() => setModal1(false)}
        onCancel={() => setModal1(false)}
        afterClose={()=>{
          onClose && onClose(false)
        }}
        footer={null}
      >
        <div >
        <Table
                    title={()=>{
                      return <div style={{width:'100%',display:'flex',alignItems:'center'}}>
                    


                      </div>
                    }}
                    rowKey={(record:any) => {
                      return 'contract'+record.serial
                    }}
  
                    bordered
                    dataSource={datas}
                    columns={columns}
                    pagination={false}
                    locale={{emptyText:'无关联合同'}}
                  />
          
          </div>
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
        
        </>
  )
}
export default ContractsTable;