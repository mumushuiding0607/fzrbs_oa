
import { Button, Modal, Table, Tag } from 'antd';
import { ColumnsType } from 'antd/lib/table';
import moment from 'moment';
import React, { CSSProperties, useEffect, useState } from 'react';
import { getcontractswithprojects } from './service';
import Filescard from '../../contract/filescard';
import View from '../../contract/view';

const labelStyle:CSSProperties={
  color:'gray'
}
const valueStyle:CSSProperties={
  color:'black',
  marginRight: '20px'
}
// balancetype 15收入，16支出
const ContractsWithProjects: React.FC<{contractids:any,visible?:boolean,onClose?:Function,}> = ({contractids,visible=false,onClose}) =>{
  const [modal1, setModal1] = useState(visible)
  const [datas,setDatas] = useState<any[]>([])
  const [modal2, setModal2] = useState(false)
  const [urls, setUrls] = useState('')
  const [viewmodal,setViewmodal] = useState(false)
  const [contract, setContract] = useState<any>({})
  var [refreshKey, setRefreshKey]= useState(0)
  let columns = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      render:(text:any,record:any,index:number)=>`${index+1}`,
      width: 60,
      search:false,
      fixed:'left'
    },
    {
      title: '项目名称',
      dataIndex: 'title',

      key: 'title',
      width: 260,
      render:(text:any,record:any)=>(<div style={{textAlign:'left'}}>
        
        <p style={{fontWeight:'bolder',margin:0}}>
          <span  >{text}</span>
        </p>
        {
          record.balancetypename&& <p style={{color:'gray',fontSize:'12px',margin:'12px 0 0 0'}}>{record.balancetypename}</p>
        }
   
        
        
      </div>)
    },
    {
      title: '项目编号',
      dataIndex: 'serial',
      width: 120
    },
    {
      title: '部门/经办',
      dataIndex: 'name',
      width:150,
      search:false,
      render:(_:any,record:any)=>(
        <div style={{display:'flex',flexDirection:'column'}}>
          <span style={{color:'gray',fontSize:'12px'}}>{record.department}</span>
          <span>{record.name}</span>

        </div>
      )
    },
    {
      title: '立项时间',
      dataIndex: 'starttime',
      valueType: 'dateRange',
      width: 130,
      fieldProps: {
        // 你可以在这里自定义时间选择器的行为
        format: 'YYYY-MM-DD',
        
      },
      render: (_:any, record:any) => {
        return moment(record.starttime).format('YYYY-MM-DD')
      },
    },
    {
      title: '预算收入',
      dataIndex: 'budgetincome',
      key: 'budgetincome',
      search:false,
      width: 120,
      className:'right',
      render: (text:any,record:any)=>(
        <>
          <span>
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
          </span>
        </>
      )
    },
    {
      title: '决算收入',
      dataIndex: 'finalincome',
      key: 'finalincome',
      width: 120,
      className:'right',
      search:false,
      render: (text:any,record:any)=>(
        <>
          <span>
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
          </span>
        </>
      )
    },
    {
      title: '已收款',
      dataIndex: 'receivedmoney',
      key: 'receivedmoney',
      width: 120,
      className:'right',
      search:false,
      render: (text:any,record:any)=>(
        <>
          <span>
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
          </span>
        </>
      )
    },

   
  ]
  useEffect(()=>{
    getcontractswithprojects({contractids:''+contractids}).then((res:any)=>{
      setDatas(res)
    })
    setModal1(visible)
  },[visible])
  return (
    <>
    <Modal

        title='合同相关项目'
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
          {
            datas.map((item:any)=>{
              return <Table
                title={()=>{
                  return <div style={{width:'100%',display:'flex',alignItems:'center'}}>         
                      <div  onClick={()=>{
                        setViewmodal(true)
                        setContract(item?.contract)
                        setRefreshKey(+refreshKey)
                      }} ><span style={{...labelStyle}}>合同名称：</span><span style={{...valueStyle,color:"#1890FF"}}>{item?.contract?.title}</span></div>
                      <div ><span style={labelStyle}>合同编号：</span><span style={valueStyle}>{item?.contract?.serial}</span></div><div ><span style={labelStyle}>合同总额：</span><span style={valueStyle}>{!Number.isNaN(item?.contract?.amount)?parseFloat(item?.contract?.amount).toLocaleString('en-US', {
        minimumFractionDigits: 2,maximumFractionDigits: 2,
      }):0}</span></div>
                      <div ><span style={labelStyle}>预算收入：</span><span style={valueStyle}>{(item?.projects||[]).reduce((acc:any, curr:any) => curr.budgetincome + acc, 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,maximumFractionDigits: 2,
      })}</span></div>
                      <div ><span style={labelStyle}>决算收入：</span><span style={valueStyle}>{(item?.projects||[]).reduce((acc:any, curr:any) => curr.finalincome + acc, 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,maximumFractionDigits: 2,
      })}</span></div>
                  </div>
                }}
                rowKey={(record:any) => record.id}
    
                bordered
                dataSource={item?.projects||[]}
                columns={columns}
                pagination={false}
                locale={{emptyText:'暂无'}}
            />
            })
          }
          
          </div>
      </Modal>
      <Modal
          title={null}
          style={{ top: 20 }}
          width={650}
          visible={modal2}
          onOk={() => {

          }}
          onCancel={() => setModal2(false)}
          footer={null}
        >
          
          <Filescard key={urls} urls={urls}/>
        </Modal>
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
  )
}
export default ContractsWithProjects;