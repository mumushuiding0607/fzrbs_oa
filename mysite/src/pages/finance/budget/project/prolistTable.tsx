
import { Modal, Tag } from 'antd';
import React, { useEffect, useState } from 'react';
import Incomelist from './income/incomelist';
import { BalanceTypes, ProjectStatesEnum } from '../config';
import { ProColumns, ProTable } from '@ant-design/pro-components';
import moment from 'moment';
import { getprohistory } from './service';
import Viewflow from '../budget/viewflow';
import ProjectTimeline from './timeline';


const ProlistTable: React.FC<{projectid:any}> = ({projectid}) =>{
  const [modalh, setModalh] = useState(false)
  var [refresh,setRefresh]=useState(0)
  const [select,setSelect]=useState<any>({})
  let columns: ProColumns<any>[] = [
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
      dataIndex:'depts',
      key:'depts',
      hideInTable: true,
      search:false
    },
    {
      title: '审批类型',
      dataIndex: 'statename',
      key: 'statename',
      width: 200,
      
    },
    {
      title: '项目名称',
      dataIndex: 'title',
      key: 'title',
      width: 260,
      render:(text:any,record:any)=>(<div style={{textAlign:'left'}}>
        
        <p style={{fontWeight:'bolder',margin:0}}>
           
          <span style={{color:'#1890FF'}} onClick={()=>{

            setModalh(true)
            setSelect(record)
            setRefresh(++refresh)
          }}>{text}</span>
        </p>
   
   
        
        
      </div>)

    },
    {
      dataIndex:'creators',
      key:'creators',
      hideInTable: true,
      search:false
    },

    {
      title: '项目编号',
      dataIndex: 'serial',
      width: 120
    },
    {
      title: '合同',
      dataIndex: 'contractids',
      key: 'contractids',
      hideInSearch:true,
      width: 80,
      search:false,
      render: (_:any,record:any)=>(
        <>
          {
            record.contractids?'已签':'未签'
          }
        </>
      )
    },
    {
      title: '部门/经办',
      dataIndex: 'department',
      width:150,
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
      width: 150,
      fieldProps: {
        // 你可以在这里自定义时间选择器的行为
        format: 'YYYY-MM-DD',
        
      },
      render: (_:any, record:any) => {
        return moment(record.starttime).format('YYYY-MM-DD')
      },
    },
    {
      title: '合同付款方',
      dataIndex: 'partaname',
      width:180
    },
    {
      title: '起止期限',
      dataIndex: 'contractperiod',
      width:160
    },
    {
      title: '合同总价',
      dataIndex: 'contractamount',
      key: 'contractamount',
      width: 120,
      className:'right',
      render: (text:any)=>!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0,

    },
    {
      title: '预算收入',
      dataIndex: 'budgetincome',
      key: 'budgetincome',
      width: 120,
      className:'right',
      render: (text:any,record:any)=>(
        <>
          
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0}

        </>
      )

    },
    {
      title: '决算收入',
      dataIndex: 'finalincome',
      key: 'finalincome',
      width: 120,
      className:'right',
      render: (text:any,record:any)=>(
        <>
          
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0}
        </>
      )
    },
    {
      title: '已收款',
      dataIndex: 'receivedmoney',
      key: 'receivedmoney',
      width: 120,
      className:'right',
      render: (text:any)=>!Number.isNaN(text||0)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0,
    },
    {
      title: '预算支出',
      dataIndex: 'realbudgetexpend',
      key: 'realbudgetexpend',
      width: 120,
      className:'right',
      render: (text:any,record:any)=>(
        <>
          
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0}
        </>
      )
    },

    {
      title: '决算支出',
      dataIndex: 'realfinalexpend',
      key: 'realfinalexpend',
      width: 120,
      className:'right',

      render: (text:any,record:any)=>(
        <>
          
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0}
        </>
      )
    },
    {
      title: '毛利润',
      dataIndex: 'profit',
      key: 'profit',
      width: 120,
      className:'right',
      render: (_:any,record:any)=> {
    
        var result = 0
        if (record.budgetincome) result = record.budgetincome - record.realbudgetexpend
        if (record.finalincome) result = record.finalincome - record.realfinalexpend
        return result.toLocaleString('en-US', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })
      }
    },
    {
      title: '入账收入',
      dataIndex: 'incomeinvoiceamount',
      key: 'incomeinvoiceamount',
      width: 120,
      className:'right',
      render: (text:any)=>{
        if (!text||text=='-') text=0
        return parseFloat(text).toLocaleString('en-US', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })
      },
    },
    {
      title: '入账成本',
      dataIndex: 'expendinvoiceamount',
      key: 'expendinvoiceamount',
      width: 120,
      className:'right',
      render: (text:any)=>{
        if (!text||text=='-') text=0
        return parseFloat(text).toLocaleString('en-US', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })
      },
    },
    {
      title: '提交时间',
      dataIndex: 'submitdate',
      key: 'submitdate',
      width: 120,
      render: (_:any, record:any) => {
        return record.submitdate?moment(record.submitdate).format('YYYY-MM'):''
      },
    },

  ]
 
  return (
    <>
    <ProTable
              scroll={{x:'100%'}}
              headerTitle={<><h2>历史审批数据</h2><ProjectTimeline projectId={projectid} /></>}
              search={false}
              rowKey={record=>record.id}
              tableAlertRender={false}
              request={(params, sorter, filter) => {
                document.body.scrollTop = document.documentElement.scrollTop = 0;
                return getprohistory({projectid});
              }}

              columns={columns}
              form={{

              }}

            />
            <Modal
        key="历史审批m2"
        title="历史审批"
        style={{ top: 20 }}
        width="60vw"
        visible={modalh}
        onOk={() => setModalh(false)}
        onCancel={() => setModalh(false)}
        footer={null}
      >
        <Viewflow key={refresh}  projectid={select.id} state={select.approvaltype||select.state} ></Viewflow>
      </Modal></>
  )
}
export default ProlistTable;