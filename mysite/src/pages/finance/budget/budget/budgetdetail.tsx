import React, { CSSProperties, useEffect, useState } from 'react';
import { Button, Modal, Table,Tabs,Typography } from 'antd';
import { getbudgetinfo } from './service';

import Pdetail from '../balance/pdetail';

import Listb from '../balance/listb';
import { ColumnsType } from 'antd/lib/table';
import { ProjectStatesEnum } from '../config';
import { getbalancefileurls } from '../project/service';
import Filescard from '../../contract/filescard';
import '../common.css'
import Addbalance from '../project/addbalance';
const { Title } = Typography;

const tdStyle:CSSProperties = {fontSize:'18px'}


const Budgetdetail: React.FC<{id:any,showTab?:boolean,show?:string,print?:boolean}> = ({print=false,id,showTab=false,show='all'}) => {
  const [datas, setDatas] = useState<any>([])
  const [bid, setBid] = useState(0)
  const [contractid, setContractid] = useState(0)
  const [modal, setModal] = useState(false)
  const [modal4, setModal4] = useState(false)
  const [modal3, setModal3] = useState(false)
  var [refreshkey,setRefreshkey] = useState(0)
  const [record,setRecord] = useState<any>({})
  const [modal2, setModal2] = useState(false)
  const [urls, setUrls] = useState('')
  const [balance,setBalance]=useState<any>({})
  const [bfinal,setBfinal] = useState(false)
  const [balanceModal,setBalanceModal]=useState(false);
  const [memoWidth,setMemoWidth]=useState(350)
  const namewidth = 150
  const moneyWidth = 150

  const { TabPane } = Tabs;
  
  const getfileurls = (record:any)=>{
    getbalancefileurls({id:record.id}).then((res:any)=>{
      if (res.data&&res.data!=','){
        setUrls(res.data)
        setModal2(true)
      }else{
        Modal.info({title:'暂无关联附件'})
      }
    })
  }
  const col1:ColumnsType<any>=[
    {

      title: '序号',
      dataIndex: 'index',
      key:'index',
      width: 50,
      align:'center',
      render:(_:any,record:any,index:number)=>`${index+1}`
  
    },
    {
      title: '名称',
      dataIndex: 'title',
      width: namewidth,
      render: (text:any,record:any)=>(
        <>
          <span  style={tdStyle}>
            {text}
          </span>
        </>
      )
    },
    {
      title: '预算金额',
      dataIndex: 'budget',
      width:moneyWidth,
      className:'right',
      render:(text:any,record:any)=>{
                        
        return (
          <span style={tdStyle}>

            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0}

          </span>
        )
      }
    },
    {
      title: '决算金额',
      dataIndex: 'final',
      width:moneyWidth,
      className:'right',
      render:(text:any,record:any)=>{
                        
        return (
          <span style={tdStyle}>

            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0}

          </span>
        )
      }
    },
   
    {
      title: '预算备注',
      dataIndex: 'budgetnote',
      width:memoWidth,
      render: (text) => <span style={tdStyle}>{text}</span>,
    },
    {
      title: '备注',
      dataIndex: 'finalnote',
      width:memoWidth,
      render: (text) => <span style={tdStyle}>{text}</span>,
    },
  ]
  const col2:ColumnsType<any>=[
    {

      title: '序号',
      dataIndex: 'index',
      key:'index',
      width: 50,
      align:'center',
      render:(_:any,record:any,index:number)=>`${index+1}`
  
    },
    {
      title: '名称',
      dataIndex: 'title',
      width: namewidth,
      render: (text:any,record:any)=>(
        <>
          <span style={{...tdStyle,color:!['合计','税费','执行绩效奖励'].includes(record.title)&&!print?'#1890ff':''}} onClick={()=>{
                getfileurls(record)
            }}>
            {text}
          </span>
        </>
      )
    },
    {
      title: '预算金额',
      dataIndex: 'budget',
      width:moneyWidth,
      className:'right',
      render:(text:any,record:any)=>{
                        
        return (
          <>
            <span style={{...tdStyle,color:record.id>0&&!print?'#1890ff':''}} onClick={()=>{
                
                if (record.id>0){
                  setBfinal(false)
                  setBalance(record)
                  setBalanceModal(true)
                }
            }}>
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0}
          </span>
          </>
        )
      }
    },
    {
      title: '决算金额',
      dataIndex: 'final',
      width:moneyWidth,
      className:'right',
      render:(text:any,record:any)=>{
                        
        return (
          <>
            <span style={{...tdStyle,color:record.id>0&&!print?'#1890ff':''}} onClick={()=>{
                
                if (record.id>0){
                  setBfinal(true)
                  setBalance(record)
                  setBalanceModal(true)
                }
            }}>
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0}
          </span>
          </>
        )
      }
    },
    {
      title: '预算备注',
      dataIndex: 'budgetnote',
      width:memoWidth,
      render: (text) => <span style={tdStyle}>{text}</span>,
    },
    {
      title: '备注',
      dataIndex: 'finalnote',
      width:memoWidth,
      render: (text) => <span style={tdStyle}>{text}</span>,
    },
  ]
  const [columns,setColumns] = useState([
    col1,
    col2,
    col2,
  ])

  const titles = ['表1.收支总表','表2.收入明细表','表3.支出明细表']

  
  useEffect(()=>{
    getdata()
  },[id])
const getdata = ()=>{
  getbudgetinfo({id}).then((res:any)=>{
    if (res.errorMessage) {
      Modal.error({
        title: '报错',
        content: res.errorMessage,
      });
    } else {
      setDatas(res.budget);
      const incomeRow = res.budget[0]?.find((item: any) => item.title === '总收入');
      const expenseRow = res.budget[0]?.find((item: any) => item.title === '总支出');
      const incomeFinal = incomeRow?.final ?? 0;
      const expenseFinal = expenseRow?.final ?? 0;
      console.log('show，expenseFinal：',expenseFinal)
      switch (show) {
        case 'budget':
          setColumns(columns.map(arr=>arr.filter((e:any)=>e.dataIndex!='final'&&e.dataIndex!='finalnote')))
          break;
        case 'final':
          setColumns(columns.map(arr=>arr.filter((e:any)=>e.dataIndex!='budgetnote')))
          break
        case 'all':
          // if (res.project.state>ProjectStatesEnum.BUDGET){
          //   setColumns(columns.map(arr=>arr.filter((e:any)=>e.dataIndex!='budget'&&e.dataIndex!='budgetnote')))
          // }else {
            
          // }
          // 当有决算数据时（收入或支出有一个不为0），隐藏预算备注列
            if (incomeFinal !== 0 || expenseFinal !== 0) {

              setColumns(columns.map(arr=>arr.filter((e:any)=>e.dataIndex!='budgetnote')))
            } else {
              setColumns(columns.map(arr=>arr.filter((e:any)=>e.dataIndex!='final'&&e.dataIndex!='finalnote')))
            }
          break;
        default:
          break;
      }
    }
  })
}
const onAddbalance = (res:any)=>{
  if (res.errorMessage){
    Modal.error({title:res.errorMessage})
  } else{
    setBalanceModal(false)
    getdata()
  }
}

const onBalanceChange = ()=>{
  getbudgetinfo({id}).then((res:any)=>{
    if (res.errorMessage) {
      Modal.error({
        title: '报错',
        content: res.errorMessage,
      });
    } else {
      setDatas(res.budget)
      if (res.project.state<ProjectStatesEnum.FINAL){
        setColumns(columns.map(arr=>arr.filter((e:any)=>e.dataIndex!='final')))
      }else{
        setColumns(columns.map(arr=>arr.filter((e:any)=>e.dataIndex!='budget')))
      }
    }
  })
}
  return (
  
    <div>
      
      { !showTab && Array.from({length: columns.length}).map((_,i)=>(
        <div key={'d'+i}>
          <Title key={titles[i]+i} level={5} style={{'textAlign':'left','marginTop':'10px'}}>{titles[i]}：</Title>
          <Table rowKey='key' bordered columns={columns[i]} dataSource={(datas[i]||[]).map((item:any,index:any)=>({...item,key:'dt'+i+index}))} size="small" pagination={false} />
        </div>
      ))}
      {
        showTab &&
        <Tabs  key={'tab1'} defaultActiveKey="0" >
          {
            Array.from({length: columns.length}).map((_,i)=>(
              <TabPane tab={titles[i]} key={i}>
                {/* <Title key={titles[i]+i} level={5} style={{'textAlign':'left','marginTop':'10px'}}>{titles[i]}：</Title> */}
                <Table rowKey='key' bordered columns={columns[i]} dataSource={(datas[i]||[]).map((item:any,index:any)=>({...item,key:'dt'+i+index}))} size="small" pagination={false} />
              </TabPane>
       
            ))
          }
        </Tabs>
      }


      <Modal key={'m1'+refreshkey} visible={modal} width="80vw" onCancel={()=>setModal(false)} onOk={()=>setModal(false)} centered footer={null} >
        <div style={{display:'flex',alignItems:'center',justifyContent:'center',width:'100%'}}>
            <Pdetail id={bid} contractid={contractid} needRoute={false}/>
        </div>
        
      </Modal>

      <Modal key={'m2'+refreshkey} visible={modal3} width="80vw" onCancel={()=>setModal3(false)} onOk={()=>setModal3(false)} centered footer={null} >
        <Listb projectid={record.projectid} moneytype={record.moneytype} type={record.type} onChange={onBalanceChange}/>
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
        
        <Filescard key={urls} mode='list' urls={urls}/>
      </Modal>
      <Modal
        title={'更新'}
        style={{ top: 20 }}
        width={600}
        visible={balanceModal}
        onOk={() => setBalanceModal(false)}
        onCancel={() => setBalanceModal(false)}
        
        footer={null}
        >
        <Addbalance key={balance.id} data={balance} isFinal={bfinal} onChange={onAddbalance}/>
      </Modal>
    </div>
  )
};

export default Budgetdetail;