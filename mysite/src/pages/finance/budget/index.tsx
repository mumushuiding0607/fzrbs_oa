import { ActionType, PageContainer, ProColumns, ProFormInstance, ProTable } from '@ant-design/pro-components';
import React, { useEffect, useRef, useState } from 'react';
import {  DatePicker, Badge, Button, Card, Divider, List, Modal, Popover, Progress, Row, Skeleton, Tag, Affix, Tabs, Tooltip  } from 'antd';

import { useHistory } from 'react-router-dom';

import { getcatogorystat, getheaders, getstat,getstattotal, home, refreshprojectreceived } from './service';
import { AGENTID, BalanceTypes, ProjectStatesEnum, ProjectTypesEnum } from './config';
import Apply from './budget/apply';
import ProjectDetail from './project/projectdetail';
import Projectsummary from './home/projectsummary';
import CustomDivider from './common/CustomDivider';
import Todolist from './home/todolist';
import { getlist } from './project/service';
import moment from 'moment';
import DepartmentTreeSelect from './common/department_treeselect';
import Viewfinance from './project/viewfinance';
import './common.css'
import Addincome from './project/addincome';
import { downloadAsXlSX, downloadfromBlob } from '../utils';
import HeaderTransfer from '../contract/header_transfer';
import { getpowers } from '../role/service';
import ProStat from './home/prostat';
import Departmentlist from '../department/departmentlist';
import Rolelist from '../role/rolelist';
import ContractsWithProjects from './project/contractsWithProjects';
import ContractSelect from '../contract/contract-select';
import Companyselect from '../company/companyselect';
import Enteraccount from './project/enteraccount';
import { TableListPagination } from './project/data';
import ProlistModal from './prolistModal';
import InvoiceView from '../contract/invoice/invoiceView';
import PayCollection from '../invoice/paycollection';
import TableScrollSync from '../common/TableScrollSync';
import EditSubmiteStateButton from './common/EditSubmiteStateButton';
import YearCloseSelector from './common/YearClose';
import DownloadDocDropdown from '../Flowtemplate/DownloadDocDropdown';
import ProjectList from './project/list';


const { RangePicker } = DatePicker;

const Index: React.FC = () => {
  const [activeKey, setActiveKey] = useState('tab0');
  const [initLoading, setInitLoading] = useState(true);
  const [showModal, setShowModal] = useState(false)
  const [homedata,setHomedata] = useState<any>({projecttypes:[],projects:[],tasks:[],businessstat:[]})
  const [modal,setModal] = useState(false)
  const [project, setProject] = useState<any>({})
  var [refreshKey, setRefreshKey] = useState(0)
  const [rolemodal,setRolemodal] = useState(false)
  const [projecttypes,setProjecttypes]=useState<any>([])
  const ref = useRef<ActionType>();
  const statref = useRef<ActionType>();
  const [departmentid,setDepartmentid]=useState<any>('')
  const [type,setType]=useState(-1)
  const [canSeeDepartments,setCanSeeDepartments]=useState<any>([])
  var [deptrefresh,setDeptrefresh]=useState(0)
  const [viewmodal,setViewmodal] = useState(false)
  const [addincomeModal,setAddincomeModal]=useState(false)
  const [balancetype,setBalancetype]=useState(BalanceTypes.INCOME)

  var [incomelistkey,setIncomelistkey]=useState(0)
  const [statcol,setStatcol]=useState<any>([])
  const [params, setParams] = useState<any>({})
  const [hmodal,setHmodal] = useState(false)
  const [smodal,setSmodal] = useState(false)
  var [headers,setHeaders]=useState<any>([])
  const [powers,setPowers] = useState<any>([])
  const [stat,setStat]=useState<any>([])
  const [contractids,setContractids]=useState(false)
  const [showProjects,setShowProjects] = useState(false)
  const [deptModal,setDeptModal]=useState(false)
  const [yearCloseModal,setYearCloseModal]=useState(false)
  const proTableFormRef = useRef<ProFormInstance>();
  const statFormRef = useRef<ProFormInstance>();
  const [enterModal,setEnterModal]=useState(false)
  const [balance,setBalance]=useState<any>({})
  const [loading,setLoading]=useState(false)
  const [submitdate,setSubmitdate]=useState<any>([])
  const [refreshStat,setRefreshStat]=useState(1)
  const [scrollLeft, setScrollLeft] = React.useState(0);
  const [statpar,setStatpar]=useState<any>({})
  const [showtStatpro,setShowtStatpro]=useState(false)
  const [tableContainer,setTableContainer]=useState<any>(null)
  const [pmodal,setPmodal]=useState(false)

  let columns: ProColumns<any>[] = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      render:(text:any,record:any,index:number)=>`${index+1}`,
      width: 60,
      search:false,
      fixed:'left',
    },
    {
      dataIndex:'depts',
      key:'depts',
      hideInTable: true,
      search:false
    },
    {
      title:'申请部门',
      dataIndex:'departmentid',
      key:'departmentid',
      hideInTable: true,
      renderFormItem: (_, { type, defaultRender, ...rest }, form) => {

        return (
          <DepartmentTreeSelect  maxTagCount={2} onChange={deptOnChange} showTreeCheckStrictly={true}/>

        )
      }
    },
    {
      title:'立项部门',
      dataIndex:'pdepartmentid',
      key:'pdepartmentid',
      hideInTable: true,
      renderFormItem: (_, { type, defaultRender, ...rest }, form) => {

        return (
          <DepartmentTreeSelect  maxTagCount={2} onChange={pdeptOnChange} showTreeCheckStrictly={true}/>

        )
      }
    },
    {
      title:'提交月份',
      dataIndex:'submitdate',
      key:'submitdate',
      hideInTable: true,
      renderFormItem: (_, { type, defaultRender, ...rest }, form) => {

        return (
          <RangePicker  picker="month" placeholder={['开始月份','结束月份']}/>
        )
      }
    },
    
    {
      title:'项目',
      dataIndex:'keyword',
      key:'keyword',
      hideInTable: true,
    },
    {
      title:'合同',
      dataIndex:'contractids',
      key:'contractids',
      hideInTable: true,
      renderFormItem: (_, { type, defaultRender, ...rest }, form) => {

        return (
          <ContractSelect  multiple={false} showupload={false}/>

        )
      }
    },
    {
      title: '项目名称',
      dataIndex: 'title',
      key: 'title',
      width: 200,
      sorter: true,
      fixed:'left',
      search:false,
      render: (text:any,record:any)=>(
        <>
        <p style={{fontWeight:'bolder',margin:0}}>

        <span  onClick={()=>{onMenuClick('预览',record)}}>{text}</span>
          {
            record.directsubmit==1 && record.type!=ProjectTypesEnum.QITA&& (record.state<ProjectStatesEnum.FINAL&&record.history.indexOf(ProjectStatesEnum.BUDGET)==-1)&&
            <Tag color={'#f50'} style={{fontSize:'12px'}}>
              仅立项
            </Tag>
          }
            
          
            
        </p>
        </>
      )
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
      sorter: true,
      width: 120,
      search:false
    },
    {
      title: '合同',
      dataIndex: 'contractids',
      key: 'contractids',
      hideInSearch:true,
      width: 100,
      search:false,
      sorter: true,
      render: (_:any,record:any)=>(
        <>
          {
            !record.contractids &&
            <span>未签</span>
          }
          {
            record.contractids!=null && record.contractids!= "" &&
            <span style={{color:'#1890FF'}} onClick={()=>{onMenuClick('查看合同关联项目',record)}}>{record.contractserial||'已签'}</span>
          }
        </>
      )
    },
  
    {
      title: '部门/项目负责人',
      dataIndex: 'department',
      sorter: true,
      search:false,
      width:150,
      render:(_:any,record:any)=>(
        <div style={{display:'flex',flexDirection:'column'}}>
          <span style={{color:'gray',fontSize:'12px'}}>{record.department}</span>
          <span>{record.chargername}</span>

        </div>
      )
    },
    {
      title: '立项时间',
      dataIndex: 'starttime',
      valueType: 'dateRange',
      width: 150,
      search:false,
      sorter: true,
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
      key: 'partaname',
      sorter: true,
      width: 180,
      renderFormItem: () => {

        return (
          <Companyselect  multiple={false} ></Companyselect>
        )
      },
      render: (text: any) => (
        <Tooltip title={text} placement="topLeft">
          <div
            style={{
              maxWidth: '150px',
              overflow: 'hidden',
              textOverflow: 'ellipsis',
              whiteSpace: 'nowrap',
              cursor: 'pointer'
            }}
          >
            {text}
          </div>
        </Tooltip>
      ),
    },
    {
      title: '起止期限',
      dataIndex: 'contractperiod',
      sorter: true,
      width:160,
      search:false,
    },
    {
      title: '合同总价',
      dataIndex: 'contractamount',
      key: 'contractamount',
      sorter: true,
      width: 120,
      search:false,
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
      sorter: true,
      width: 120,
      search:false,
      className:'right',
      render: (text:any,record:any)=>(
        <>
          <Button type="link" onClick={()=>{
            setAddincomeModal(true)
            setProject(record)
            setBalancetype(BalanceTypes.INCOME)
            setIncomelistkey(++incomelistkey)
            }}>
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0}
          </Button>
        </>
      )

    },
    {
      title: '决算收入',
      dataIndex: 'finalincome',
      key: 'finalincome',
      sorter: true,
      search:false,
      width: 120,
      className:'right',
      render: (text:any,record:any)=>{
        // 未通过决算，显示为0
        if(record.state<=ProjectStatesEnum.FINAL){
          text=0
        }
       
        return (
        <>
          <Button type="link" onClick={()=>{
            setAddincomeModal(true)
            setProject(record)
            setBalancetype(BalanceTypes.INCOME)
            setIncomelistkey(++incomelistkey)
            }}>
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0}
          </Button>
        </>
      )
      }
    },
    {
      title: '已收款',
      dataIndex: 'receivedmoney',
      key: 'receivedmoney',
      sorter: true,
      search:false,
      width: 120,
      className:'right',
      render: (text:any,record:any)=>{
        if (text==null||text=='-') text=0

        return <>
          <Button type="link" onClick={()=>{

            setProject(record)
            setPmodal(true)

          }}>
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0}
          </Button>
        </>
      }

    },
    {
      title: '预算支出',
      dataIndex: 'realbudgetexpend',
      key: 'realbudgetexpend',
      width: 120,
      sorter: true,
      search:false,
      className:'right',
      render: (text:any,record:any)=>(
        <>
          <Button type="link" onClick={()=>{
            setAddincomeModal(true)
            setProject(record)
            setBalancetype(BalanceTypes.EXPEND)
            setIncomelistkey(++incomelistkey)
          }}>
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0}
          </Button>
        </>
      )
    },

    {
      title: '决算支出',
      dataIndex: 'realfinalexpend',
      key: 'realfinalexpend',
      sorter: true,
      width: 120,
      search:false,
      className:'right',

      render: (text:any,record:any)=>{
        // 未通过决算，显示为0
        if(record.state<=ProjectStatesEnum.FINAL){
          text=0
        }
     

        return <>
          <Button type="link" onClick={()=>{
            setAddincomeModal(true)
            setProject(record)
            setBalancetype(BalanceTypes.EXPEND)
            setIncomelistkey(++incomelistkey)
          }}>
            {!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0}
          </Button>
        </>
      }
    },
    {
      title: '毛利润',
      dataIndex: 'profit',
      key: 'profit',
      sorter: true,
      search:false,
      width: 120,
      className:'right',
      render: (text:any,record:any)=> {
        text = getProfit(record)
        return text.toLocaleString('en-US', {
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
      sorter: true,
      search:false,
      className:'right',
      render: (text:any,record:any)=>{
        if (!text||text=='-') text=0
        return <span onClick={()=>{onMenuClick('入账收入',record)}}>
          {
            parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            })
          }
        </span>
      },
    },
    {
      title: '入账成本',
      dataIndex: 'expendinvoiceamount',
      key: 'expendinvoiceamount',
      width: 120,
      search:false,
      className:'right',
      sorter: true,
      render: (text:any,record:any)=>{
        if (!text||text=='-') text=0
        return <span onClick={()=>{onMenuClick('入账成本',record)}}>
          {
            parseFloat(text).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            })
          }
        </span>
      },
    },
    {
      title: '提交时间',
      dataIndex: 'submitdate',
      key: 'submitdate',
      search:false,
      sorter: true,
      width: 120,
      render: (_:any, record:any) => {
        return <EditSubmiteStateButton text={moment(record.submitdate).format('YYYY-MM-DD')} obj={record} onSave={()=>{
          ref.current?.reload()
        }}/>
      },
    },

  ]
  const onMenuClick = (action:String,record:any) => {
   
    switch (action) {
      case '预览':
        setModal(true)
        setProject(record)
        setRefreshKey(++refreshKey)
        break;
      case '财务信息':
        setViewmodal(true)
        setProject(record)
        setRefreshKey(++refreshKey)
        break
      case '查看合同关联项目':
        setContractids(record.contractids)
        setShowProjects(true)
        break
      case '入账收入':
        setEnterModal(true)
        setBalance({projectid:record.id,type:BalanceTypes.INCOME})
        break
      case '入账成本':
        setEnterModal(true)
        setBalance({projectid:record.id,type:BalanceTypes.EXPEND})
        break
      default:
        break;
    }
  };
  const handleTabChange = (key:any) => {
    setActiveKey(key);
  };
  useEffect(() => {
      home({}).then((res:any)=>{
        setHomedata(res)
        if (res.canSeeDepartments){
          setCanSeeDepartments(res.canSeeDepartments||[])
          setDeptrefresh(++deptrefresh)
        }
        setInitLoading(false)
      })
      getpowers({agentid:AGENTID}).then((res:any)=>{
        if (res.errorMessage){
          Modal.error({title:res.errorMessage})
          return
        }
        res.data = res.data||''
        res.data.split && setPowers(res.data.split(','))
  
      })
      setTimeout(() => {
        setTableContainer(document.querySelector('.ant-table-content'))
      }, 3000);

  }, []);



  
  const getProfit=(row:any)=>{
    var result = 0;
    if (![ProjectTypesEnum.FEIBAO,ProjectTypesEnum.HUODONG,ProjectTypesEnum.OFFLINE].includes(row.type)){
     
      if (row.finalincome>0){
        result=row.finalincome-row.realfinalexpend
      }else{
        result = row.budgetincome-row.realbudgetexpend
      }
    }else{
  

      if (row && row.history && row.history.includes(ProjectStatesEnum.FINAL)){
        result =  row.finalincome-row.realfinalexpend
      }else{
        result = row.budgetincome-row.realbudgetexpend
      }
    }
    return result
    
  }
  const deptOnChange = (e:any)=>{
   
    // 判断e是否是数组
    if (Array.isArray(e)){
      const value = e.map(item=>{
        // 判断是否是字符串
        if (typeof item === 'string'){
          return item
        }else{
          return item.value
        }
      })
      
      proTableFormRef.current?.setFieldsValue({'departmentid':value})
      statFormRef.current?.setFieldsValue({'departmentid':value})
    }else{
      proTableFormRef.current?.setFieldsValue({'departmentid':e})
      statFormRef.current?.setFieldsValue({'departmentid':e})
    }
    
  }
  const pdeptOnChange = (e:any)=>{
    
    // 判断e是否是数组
    if (Array.isArray(e)){
      const value = e.map(item=>{
        // 判断是否是字符串
        if (typeof item === 'string'){
          return item
        }else{
          return item.value
        }
      })
      
      proTableFormRef.current?.setFieldsValue({'pdepartmentid':value})
 
    }else{
      proTableFormRef.current?.setFieldsValue({'pdepartmentid':e})

    }
  }
  const statDeptOnChange = (e:any)=>{
    
    // 判断e是否是数组
    if (Array.isArray(e)){
      const value = e.map(item=>{
        // 判断是否是字符串
        if (typeof item === 'string'){
          return item
        }else{
          return item.value
        }
      })
      
      setDepartmentid(value.join(','))
    }else{
      setDepartmentid('')
    
    }
    
    
  }
  const onSubmitdateChange = (e:any,datestr:any)=>{
    console.log(datestr)
    setSubmitdate(datestr)
    statref.current?.reload()
  }
    const history = useHistory<any>() as any;
    const onApplyChange = (e:any)=>{
      console.log(e)
    }

    const onPayCheck = ()=>{
      ref.current?.reload()
    }
    const onHeaderChange = (h:any)=>{
      if (h.length==0){
        Modal.error({title:'表头不能为空'})
        return
      }
      setHeaders(h)
    }
    const onTypeChange = (e:any)=>{
      setType(e)
      ref.current?.reload()
    }

    return (
        <PageContainer
            header={{breadcrumb: {},}}
            fixedHeader
            extra={powers.includes('管理')?[
              <DownloadDocDropdown />,
              <Button key="e1"  onClick={()=>{
           
                setRolemodal(true)
              }}>角色设置</Button>,
              <Button key="3" onClick={()=>{
                history.push({pathname:'/finance/budget/flow/flowlist'})
              }}>流程设置</Button>,
              <Button key="4" onClick={()=>{
                history.push({pathname:'/finance/budget/dict/dictlist'})
              }}>字典管理</Button>,
              <Button key="5" onClick={()=>{
                setDeptModal(true)
              }}>部门简码</Button>,
              <Button key="6" onClick={()=>{
                setYearCloseModal(true)
              }}>结账年份</Button>
              
            ]:[]}
            tabList={[
              {
                tab: '项目管理',
                key: 'tab0',
              },
              {
                tab: '非报业务表',
                key: 'tab1',
              },
              {
                tab: '新媒体业务表',
                key: 'tab2',
              }
            ]}
          tabProps={{style:{marginBottom:'10px'}}}
          
          onTabChange={handleTabChange}
        >
          {
            activeKey==='tab0'&& <ProjectList key={'tab0'} showHeader={false}/>
          }
        
          {
            activeKey==='tab1'&&<>
<Todolist url={'/api/budget/todolist'}/>
          <CustomDivider/>
                  <ProTable
                    id="projectTable"
                    scroll={{x:'100%'}}
        
                    pagination={{pageSize:20,showSizeChanger: true,}}
                    title={()=>[
                      <Projectsummary mode={'column'} data={projecttypes} target={homedata.target} onChange={onTypeChange} onRefresh={(data:any)=>{
                                 if (loading){
                                  Modal.info({
                                    title: '正在刷新数据，请稍等',
                                  });
                                  setTimeout(() => {
                                    setLoading(false)
                                  }, 3000);
                                 }else{
                                  setLoading(true)
                                  getstat(params).then((res2:any)=>{ 
                                    setLoading(false)
                                    if (res2){
               
                                        setProjecttypes(res2.projecttypes||[])
                                      }
                                    })
                                 }
                                 
                              }}/>
                    ]}
                    headerTitle={
                      <Button type='primary'  onClick={()=>{
                        history.push({pathname:'/finance/budget/project/list'})
                      }}>新增项目</Button>
                      
                    }
          
                    toolBarRender={() => [
              
                      <Button onClick={()=>{
                        
                        // setRefreshKey(++refreshKey)
                        getheaders({typename:'已提交报表导出'}).then((res:any)=>{
                          if (res.data){
                            setHeaders(res.data)
                            setHmodal(true)
                          }
                        })
                        
                      }}>导出查询结果</Button>,
        
                    ]}
                    rowKey={record=>record.id}
                    
                    request={(params, sorter, filter) => {
                      params.orderby = "serial asc"
                      if (sorter){
                        Object.keys(sorter).forEach((key)=>{
                          var order = sorter[key]=='ascend'?'asc':'desc'
                          params.orderby=key+" " + order

                        })
                      }
                      if (params.departmentid){
                        params.departmentid = params.departmentid.join(',')
                      }
                      if (params.pdepartmentid){
                        params.pdepartmentid = params.pdepartmentid.join(',')
                      }
                      
                      params.type = type 
                      if (!params.keyword && !params.contractids) {
                        params.submitdatestart = params.submitdatestart||new Date().getFullYear()+'-01-01'
                      }
                      if (params.submitdate) {
                        params.submitdatestart = params.submitdate[0].substring(0,8)+'01 00:00:00';
                        var d = new Date(params.submitdate[1].substring(0,4), parseInt(params.submitdate[1].substring(5,7)), 0).getDate();
                        params.submitdateend = params.submitdate[1].substring(0,8)+d+' 23:59:59';
                      }
                      params.directsubmit = 1
                      if (params.contractids && params.contractids.id){
                        params.contractids = params.contractids.id
                      }
                      if (params.partaname && params.partaname.value) {
                        params.parta = params.partaname.value
                        delete params.partaname
                      }else{
                        delete params.parta
                      }
                      setParams(params)
                      var result = getlist(params)

                      if (params.current==1){
                          
                          if(refreshStat){
                            getstat(params).then((res2:any)=>{ 
                              if (res2){
                                  
                                  setProjecttypes(res2.projecttypes||[])
                                }
                            })
                            getstattotal(params).then((res3:any)=>{
                              setStat(res3.stat||[])
                            })
                            setRefreshStat(0)
                          }
                          
                        }
                      return result;
                    }}
                    
                    actionRef={ref}
                    formRef={proTableFormRef}
                    columns={columns}
                    form={{
                    
                    }}
        
                  />
                  <ProStat  data={stat} onChange={(data:any)=>{
                                 if (loading){
                                  Modal.info({
                                    title: '正在刷新数据，请稍等',
                                  });
                                  setTimeout(() => {
                                    setLoading(false)
                                  }, 3000);
                                 }else{
                                  setLoading(true)
                                  getstattotal(params).then((res3:any)=>{
                                    setLoading(false)
                                    setStat(res3.stat||[])
                                  })
                                 }
                                 
                              }}/>
                  <TableScrollSync tableId="projectTable" onScroll={(scroll:any)=>{
                      const tableContent = document.querySelector('#projectTable .ant-table-content');
                      if (tableContent){
                        tableContent.scrollLeft = scroll;
                      }
          
                }} />

            </>
          }
          {
            activeKey=='tab2'&&<>
            
            <ProTable<any, TableListPagination>
                    id="statTable"
                    scroll={{x:'100%'}}
                    title={()=>[
                    
                    ]}
                    headerTitle={
                      <Row>
                        
                        <h2>新媒体业务统计表</h2>
                      </Row>
                      
                    }

                    search={false}
                    pagination={{
                      pageSize: 20, // 每页显示的条数
                      showSizeChanger:true
                    }}
                    
                    rowKey={record=>record.id}
                    tableAlertRender={false}
                    request={(params:any, sorter, filter) => {
                      if (params.departmentid){
                        params.departmentid = params.departmentid.join(',')
                      }
                      if (departmentid){
                 
                        params.departmentid = departmentid
                      }

                      params.submitdatestart = params.submitdatestart||new Date().getFullYear()+'-01-01'
                      if (submitdate && submitdate[0]) {
                        params.submitdatestart = submitdate[0].substring(0,8)+'-01 00:00:00';
                        var d = new Date(submitdate[1].substring(0,4), parseInt(submitdate[1].substring(5,7)), 0).getDate();
                        params.submitdateend = submitdate[1].substring(0,8)+'-'+d+' 23:59:59';
                      }
                      params.state=ProjectStatesEnum.SUBMITTED
                    
                      setParams(params)
                      var result = getcatogorystat(params)
                      result.then((res:any)=>{

                        setStatcol((res.col||[]).map((e:any,index:number)=>{
               
                          return {
                            ...e,onCell:(record:any)=>({
                                onClick: () => {

                                  setStatpar({departmentid:record.departmentid,moneytypename:e.title,submitdatestart:params.submitdatestart,submitdateend:params.submitdateend})
                                  setShowtStatpro(true)
                                },
                              })
                          }
                        }))
                        
                      })
                      return result;
                    }}
                    actionRef={statref}
                    formRef={statFormRef}
                    columns={statcol}
                    form={{
                    
                    }}
                    toolBarRender={() => [
                      <RangePicker  picker="month" placeholder={['开始月份','结束月份']} onChange={onSubmitdateChange}/>,
                      <DepartmentTreeSelect style={{minWidth:'200px'}} maxTagCount={2} onChange={statDeptOnChange} showTreeCheckStrictly={true}/>,
                      <Button  onClick={()=>{
                        
                        setSmodal(true)
                        setHeaders(statcol)
                        
                        
                      }}>导出查询结果</Button>
                    ]}
        
                  />
                  <TableScrollSync tableId="statTable" onScroll={(scroll:any)=>{
                      const tableContent = document.querySelector('#statTable .ant-table-content');
                      if (tableContent){
                        tableContent.scrollLeft = scroll;
                      }
          
                }} />
                  <ProlistModal  visible={showtStatpro} data={statpar} onVisibleChange={setShowtStatpro}/>
            
            </>
          }
         

          

          
            
          
            
         
        
            
            <Modal
              title={
                <>
                <span>回款纪录</span>
                <Button style={{marginLeft:'15px'}} type="link" onClick={()=>{
                  refreshprojectreceived({contractids:project.contractids}).then((res:any)=>{ 
                    if (res.errorMessage){
                      Modal.error({
                        title: '错误信息',
                        content: (
                          <div dangerouslySetInnerHTML={{ __html: res.errorMessage }} />
                        ),
                      });
                    }else{
                      Modal.success({title:'刷新成功'})
                      ref.current?.reload()
                    }
                  })
                }}>刷新已收款</Button>
                </>
              }

              style={{ top: 20, }}
              visible={pmodal}
              onOk={() => {
                setPmodal(false)
              }}
              onCancel={() => setPmodal(false)}
              footer={null}
            >
              <PayCollection key={project.id} contractids={project.contractids}/>
            </Modal>
        
           
            <Modal
              title="详情预览"
              style={{ top: 20 }}
              width="60vw"
              visible={modal}
              onOk={() => setModal(false)}
              onCancel={() => setModal(false)}
              footer={null}
            >
              <Apply key={refreshKey} data={project} onchange={onApplyChange} onPayCheck={onPayCheck}/>
            </Modal>
          <Modal
            title="项目内容"
            key="m2"
            style={{ top: 20, }}
            width={'80%'}
            visible={showModal}
            onOk={() => setShowModal(false)}
            onCancel={() => setShowModal(false)}
            footer={null}
          >
            <ProjectDetail id={project?.id}></ProjectDetail>
          </Modal>
          <Modal
         
            
              visible={rolemodal}
              width='100vw'
              style={{top:0,right:0}}
              onOk={() => setRolemodal(false)}
              onCancel={() => setRolemodal(false)}
              footer= {null}
            >
            <Rolelist type='非报业务系统' agentid={AGENTID}></Rolelist>
          </Modal>
        <Modal
         
         
          visible={deptModal}
          width='100vw'
          style={{top:0,right:0}}
          onOk={() => setDeptModal(false)}
          onCancel={() => setDeptModal(false)}
          footer= {null}
        >
          <Departmentlist ></Departmentlist>
          
        </Modal>
        <YearCloseSelector
          open={yearCloseModal}
          onClose={() => setYearCloseModal(false)}
        />
        <Modal
          width={850}
          style={{ top: 0}}
          visible={viewmodal}
          onOk={() => setViewmodal(false)}
          onCancel={() => setViewmodal(false)}
          footer= {null}
        >
          
          <Viewfinance id={project.id} key={refreshKey}/>
        </Modal>
        <Modal
          width={600}
          style={{ top: 0}}
          visible={hmodal}
          onOk={() => {
            if(loading) {
              Modal.warn({title:'正在导出，请稍候'})
              return
            }
            setLoading(true)
            setTimeout(() => {
              setLoading(false)
            }, 3000);
            params.pageSize = 100000
            params.current = 1

            if (headers.length==0){
              headers = columns.filter((e:any)=>!e.hideInTable&&!['index','action'].includes(e.key)&&e.title)
            }
     
            params.columns = headers.map((e:any)=>e.key).filter((e:any)=>!['index','typename','expendinvoiceamount','incomeinvoiceamount','name','budgetincome','finalincome','budgetexpend','finalexpend','profit'].includes(e)).join(',')

            getlist(params).then((res:any)=>{
              
              if (res.data.length<0){
                Modal.error({title:'数据为空'})
              }else{
                // 判断headers是否有typename这个key，如果没有就添加
                if (!headers.find((e:any)=>e.key=='typename')){
                  headers.unshift({title:'项目类型',key:'typename'})
                }
                if (!headers.find((e:any)=>e.key=='index')){
                  headers.unshift({title:'序号',key:'index'})
                }
                if (!headers.find((e:any)=>e.key=='state')){
                  headers.push({title:'是否决算',key:'state'})
                }
                if (!headers.find((e:any)=>e.key=='name')){
                  headers.push({title:'业务经办人',key:'name'})
                }
                
                var temp = res.data.map((row:any,index:any)=>{
                  var arr:any = []
                  row.index = index+1
                  
                  headers.forEach((h:any)=>{

                    
                    switch (h.key) {
 
                      case 'submitdate':
                        arr.push(row.submitdate?moment(row.submitdate).format('YYYY-MM-DD'):'')
                        break
                      case 'starttime':
                        arr.push(row.starttime?moment(row.starttime).format('YYYY-MM-DD'):'')
                        break
                      case 'contractids':
                        arr.push(row.contractids?'已签':'未签')
                        break
                      case 'state':
                        arr.push(row.state>ProjectStatesEnum.FINAL?'是':'否')
                        break
                      case 'finalincome':
                        if (row.state<=ProjectStatesEnum.FINAL){
                          arr.push(0)
                        }else{
                          arr.push(row.finalincome)
                        }
                        break
                      case 'realfinalexpend':
                        if (row.state<=ProjectStatesEnum.FINAL){
                          arr.push(0)
                        }else{
                          arr.push(row.realfinalexpend)
                        }
                        break
                      case 'profit':
                        arr.push(getProfit(row))
                        break
                      default:
                        arr.push(row[h.key]||'')
                        break;
                    }
                    

                    
                  })

                  if(row.state<=ProjectStatesEnum.FINAL){
                    row.realfinalexpend=0
                    row.finalincome=0
                  }

                  return arr
                })
                var x = headers.map((t:any)=>t.title||'该列列标未设置')
                temp.unshift(x)
                downloadAsXlSX(temp,'项目信息导出')
              }
            })
          }}
          onCancel={() => {
            console.log('onCancel')
            setHmodal(false)
          }}

        >
          
          <HeaderTransfer key={refreshKey}  onChange={onHeaderChange} headers={headers} />
        </Modal>
        <Modal
          width={600}
          style={{ top: 0}}
          visible={smodal}
          onOk={() => {
            params.pageSize = 100000
            params.current = 1
   
            if (headers.length==0){
              headers = columns.filter((e:any)=>!e.hideInTable&&!['index','action'].includes(e.key)&&e.title)
            }
       
            getcatogorystat(params).then((res:any)=>{
              
              if (!res.data||res.data.length<0){
                Modal.error({title:'数据为空'})
              }else{
                var temp = res.data.map((row:any)=>{
                  var arr:any = []
                
                  headers.forEach((h:any)=>{

                    
                    switch (h.key) {
 
                      case 'submitdate':
                        arr.push(row.submitdate?moment(row.submitdate).format('YYYY-MM-DD'):'')
                        break

  
                      default:
                        arr.push("\t" + (row[h.key]||'').toString())
                        break;
                    }
                    
                    
                  })

                  return arr
                })
                var x = headers.map((t:any)=>t.title||'该列列标未设置')
                temp.unshift(x)

                const blob = new Blob([temp.join('\n')], { type: "text/csv;charset=utf-8;" })
                downloadfromBlob({blob,filename:'统计表.csv'})
              }
            })
          }}
          onCancel={() => setSmodal(false)}

        >
          
          <HeaderTransfer   onChange={onHeaderChange} headers={headers} />
        </Modal>
        <Modal
          title={'入账'}
          style={{ top: 20 }}
          width={700}

          visible={enterModal}
          onOk={() => setEnterModal(false)}
          onCancel={() => setEnterModal(false)}
          
          footer={null}
          >
          
          <Enteraccount key={balance.projectid+'-'+balance.type} bid={balance.id} type={balance.type} projectid={balance.projectid} showAll={true}/>
          <InvoiceView key={balance.projectid} projectid={balance.projectid}/>
        </Modal>
        <Addincome key={incomelistkey} onlyBalance={true} visible={addincomeModal} balancetype={balancetype}  pid={project.id} onClose={()=>{
          setAddincomeModal(false)
        }}/>
        <ContractsWithProjects key={''+contractids}  contractids={contractids} visible={showProjects} onClose={()=>setShowProjects(false)}/>

        </PageContainer>
    );
};


export default Index;
