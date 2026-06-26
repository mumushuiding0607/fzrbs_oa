import { ActionType, PageContainer, ProTable } from '@ant-design/pro-components';
import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import { Modal ,Space,Avatar,Segmented,Radio, Tag} from 'antd';

import { useHistory } from 'react-router-dom';
import { approvallist, getlist } from './service';


import { useLocation } from 'umi';

import moment from 'moment';
import { getdictlist } from '../budget/dict/service';
import { INVOICE_AGENTID, InvoicingStatesEnum } from './config';
import DepartmentTreeSelect from '../budget/common/department_treeselect';
import Companyselect from '../company/companyselect';
import ViewModal from './viewModal';

// style

// data

const tag:CSSProperties = {
  margin: '2px',
  padding: '0px 2px',
  width: '46px',
  display: 'flex',
  justifyContent:'space-evenly'
}
// dom
const Applylist:React.FC = () =>{
  const history = useHistory();
  const [project, setProject] = useState({})
  var [refreshKey, setRefreshKey]= useState(0)
  const [modal, setModal] = useState(false)
  const ref = useRef<ActionType>();
  const location = useLocation();
  const searchParams = new URLSearchParams(location.search);
  const [tabtype, setTabtype]=useState<any>(parseInt(searchParams.get('state')||'-1'))
  const [tabs, setTabs]=useState<any>([])
  const [obj,setObj]=useState<any>({})
  const [view,setView]=useState(false)
  useEffect(()=>{
    getdictlist({type:'开票审批',orderby:'value asc'}).then((res:any)=>{
      if (res.data){
        res.data.unshift({label:'我已审',value:-1})
        setTabs(res.data)
      }
    })
  },[])
  const onMenuClick = (action:String,record:any) => {
  
      switch (action) {
        

  
        default:
          break;
      }
    };
  // 列标
  let columns:any = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      hideInSearch:true,
      width: 80,
      render:(text:any,record:any,index:number)=>`${index+1}`
    },
    {
      title: '开票状态',
      dataIndex: 'state',
      width: 75,
      hideInSearch:true,
      render:(_:any,record:any)=>{
              var text = '暂存'
              var color = 'default'
              if (record.invoiceids!=null&&!record.realinvoiceamount){
                text = '已红冲';color='red';
              }else if (record.invoiceids!=null&&record.realinvoiceamount>0){
                text = '已开票';color='green';
              } else if (record.state==InvoicingStatesEnum.INVOICED&&record.invoiceids==null){
                text = '待开票';color='lime';
              } else if (record.thirdNo!=null&&record.thirdNo!=''){
                text = '审批中';color='red';
              }
              return (<div style={{textAlign:'left',display:'flex'}} onClick={()=>{
                setView(true)
                setObj(record)
              }}>
                <Tag color={color} style={tag}>{text}</Tag>

                
              </div>)
            }
    },
    {
      title: '合同',
      dataIndex: 'contractid',
      key: 'contractid',
      hideInSearch:true,
      width: 50,
      search:false,
      render: (_:any,record:any)=>(
        <>
          {
            !record.contractid &&
            <span>未签</span>
          }
          {
            record.contractid!=null && record.contractid!= "" &&
            <span style={{color:'#1890FF'}} onClick={()=>{onMenuClick('查看关联合同',record)}}>已签</span>
          }
        </>
      )
    },
    {
      title: '发票类别',
      dataIndex: 'type',
      key: 'type', 
      width: 75,
      valueEnum: {
        0: {
          text: '普票',
        },
        1: {
          text: '专票',
        },
      },
    },
    {
      title: '开票单位',
      dataIndex: 'partbname',
      key: 'partbname',
      sorter: true,
      hideInSearch:true,
      width: 200,
      render:(text:any,record:any)=>{
    
             
        return (<div style={{textAlign:'left',color:'#1890FF'}} onClick={()=>{
          setView(true)
          setObj(record)
        }}>
      
          {text}

          
        </div>)
      },
      renderFormItem: () => {

        return (
          <Companyselect  multiple={false}></Companyselect>
        )
      }
    },
    {
      title: '客户名称',
      dataIndex: 'partaname',
      key: 'partaname',
      sorter: true,
      hideInSearch:true,
      width: 200,
      renderFormItem: () => {

        return (
          <Companyselect  multiple={false}></Companyselect>
        )
      }
    },

    {
      title: '开票金额',
      dataIndex: 'amount',
      key: 'amount',
      width: 120,
      sorter: true,
      className:'right',
      hideInSearch:true,
      search:false,
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
      title: '合同业务',
      dataIndex: 'contractname',
      key: 'contractname', 
      width: 80,
      hideInSearch:true,
    },
    
    {
      title:'申请部门',
      dataIndex:'departmentid',
      key:'departmentid',
      hideInTable: true,
      renderFormItem: () => {

        return (
          <DepartmentTreeSelect  maxTagCount={1} />
        )
      }
    },
    {
      title: '部门/经办',
      dataIndex: 'name',
      width:150,
      search:false,
      sorter: true,
      hideInSearch:true,
      render:(_:any,record:any)=>(
        <div style={{display:'flex',flexDirection:'column'}}>
          <span style={{color:'gray',fontSize:'12px'}}>{record.department}</span>
          <span>{record.name}</span>

        </div>
      )
    },
    
    
    
    
    
    {
      title: '开票日期',
      dataIndex: 'date',
      key: 'date',
      sorter: true,
      hideInSearch:true,
      valueType: 'dateRange',
      width: 120,
      fieldProps: {
        // 你可以在这里自定义时间选择器的行为
        format: 'YYYY-MM-DD',
        
      },
      render: (_:any, record:any) => {
        return record.date?moment(record.date).format('YYYY-MM-DD'):''
      },
    },
    {
      title: '申请日期',
      dataIndex: 'inserttime',
      key: 'inserttime',
      sorter: true,
      valueType: 'dateRange',
      width: 120,
      hideInSearch:true,
      fieldProps: {
        // 你可以在这里自定义时间选择器的行为
        format: 'YYYY-MM-DD',
        
      },
      render: (_:any, record:any) => {
        return record.inserttime?moment(record.inserttime).format('YYYY-MM-DD'):''
      },
    },
    {
      title: '操作',
      key: 'action',
      hideInSearch:true,
      render: (_:any, record:any) => (
    
        <>
          <div>
            {
               <Space size="middle">
  
                  <a href='#' onClick={()=>{
                    setRefreshKey(refreshKey++)
                    setView(true)
                    setObj(record)
                  }}>查看</a>
              </Space>
            }
          </div>
        </>
      ),
    }
  ]
 
  const onApplyChange = (e:any)=>{

    ref.current?.reload()
  }
  const onTabtypechange = (e:any)=>{
    setTabtype(e.target.value)
    ref.current?.reload()
  }

  return (
    <>
      <PageContainer title="审批列表" header={{breadcrumb: {
          routes: [
            {
              path: '',
              breadcrumbName: '上一页',
            },
            {
              path: '/oa/finance/invoice/list/',
              breadcrumbName: '首页',
            }
            
          ],itemRender(route, params, routes, paths) {
            if (route.breadcrumbName=='上一页'){
              return <a href='#' onClick={()=>history.goBack()}>{route.breadcrumbName}</a>
            } else {
              return <a href={`/${paths.join("/")}`}>{route.breadcrumbName}</a>
            }
            
          
          },

        },}}>

        <ProTable
          rowKey={record=>record.id}
          request={(params, sorter, filter) => {
            document.body.scrollTop = document.documentElement.scrollTop = 0;
            params.state = tabtype
            return approvallist(params)
          }}
          actionRef={ref}
          columns={columns}
          form={{
           
          }}
          toolBarRender={() => [

          ]}
          headerTitle={
            <Radio.Group key={refreshKey} value={tabtype} onChange={onTabtypechange} optionType="button" size='large' buttonStyle="solid" style={{ margin: 0 }}>
              {
                 tabs.filter((e:any)=>e.label.indexOf('已提交')==-1).map((e:any,index:any)=>{
                  return <Radio.Button value={e.value}>{(e.value>0?'待':'')+e.label+(e.value>0?'审批':'')}</Radio.Button>
                 })
              }
             
            </Radio.Group>
          }
        />
      <ViewModal key={'viewmodal'+refreshKey} id={obj.id} thirdNo={obj.thirdNo} visible={view} onVisibleChange={setView} onApplyChange={onApplyChange}></ViewModal>
      </PageContainer>
    </>
  )
  
}
export default Applylist