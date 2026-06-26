import { ActionType, PageContainer, ProColumns, ProFormInstance } from '@ant-design/pro-components';
import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import {useLocation  } from 'react-router-dom';
import { Button,Modal,DatePicker,Tag ,Popover,Radio, Select, Alert} from 'antd'; 

import { PlusOutlined } from '@ant-design/icons';
import Addpro from './addpro';
import Apply from '../budget/apply';
import { useHistory } from 'react-router-dom';
import ProTable from '@ant-design/pro-table';
import type {  TableListPagination } from './data';
import { delproject, getlist, lockpro, submitmeasure } from './service';

import moment from 'moment';
import ProjectDetail from './projectdetail';
import { AGENTID, BalanceTypes,  ProjectStatesEnum } from '../config';
import Dictselect from '../dict/dictselect';
import { request, useModel } from 'umi';
import DepartmentTreeSelect from '../common/department_treeselect';
import Print from '../budget/print';
import { getBykeyword,getdictlist } from '../dict/service';

import Addincome from './addincome';
import '../common.css'
import UserAutocomplete from '../common/userAutocomplete';
import ContractsWithProjects from './contractsWithProjects';
import ProStat from '../home/prostat';
import {  getstattotal } from '../service';
import { getthirdno, startflow, viewflow } from '../budget/service';
import Flow from '../budget/flow';
import TableScrollSync from '../../common/TableScrollSync';
import EditCreatorButton from './EditCreatorButton';


// *************************页面元素*********************************
// 项目列表
const List:React.FC<{showHeader?:boolean}> = ({showHeader=true}) => {
  const history = useHistory() as any;
  const [modal1, setModal1] = useState(false)
  const [approvalstate,setApprovalstate]=useState(-1)
  const [issubmitted,setIssubmitted]=useState(-1)
  const [showModal, setShowModal] = useState(false)
  var [refreshKey, setRefreshKey]= useState(0)
  var [refreshKey2, setRefreshKey2]= useState(100)
  const location = useLocation() as any;
  const [project, setProject] = useState<any>({id:0})
  const [modal2, setModal2] = useState(false)
  const actionRef = useRef<ActionType>();
  const proTableFormRef = useRef<ProFormInstance>();
  const [params, setParams] = useState<any>({})
  const { RangePicker } = DatePicker;
  const { initialState } = useModel<any>('@@initialState');

  const [printModal,setPrintModal]=useState(false)
  const [open, setOpen] = useState(false);
  const [type,setType]=useState(location.query.protype||-1)
  const [types,setTypes]=useState<any>([])
  var [radiokey,setRadiokey]=useState(0)
  const [addincomeModal,setAddincomeModal]=useState(false)
  const [balancetype,setBalancetype]=useState(BalanceTypes.INCOME)
  var [incomelistkey,setIncomelistkey]=useState(0)
  const [tabs, setTabs]=useState<any>([])

  const [contractids,setContractids]=useState(false)
  const [showProjects,setShowProjects] = useState(false)
  const [stat,setStat]=useState<any>([])
  const [loading,setLoading]=useState(false)
  const [searchStat,setSearchStat]=useState<any>([])

  const [refreshStat,setRefreshStat]=useState(1)
  useEffect(()=>{
    getdictlist({type:'审批类型',agentid:AGENTID,orderby:'value asc'}).then((res:any)=>{
      setTabs(res.data||[])
    })
  },[])

  let columns: ProColumns<any>[] = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      render:(text,record,index:number)=>`${index+1}`,
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
      title:'部门',
      dataIndex:'departmentid',
      key:'departmentid',
      hideInTable: true,
      renderFormItem: (_, { type, defaultRender, ...rest }, form) => {

        return (
          <DepartmentTreeSelect  maxTagCount={1} onChange={deptOnChange}/>

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
      title: '项目名称',
      dataIndex: 'title',
      sorter: true,
      key: 'title',
      width: 260,
      render:(text:any,record:any)=>(<div style={{textAlign:'left'}}>
        
        <p style={{fontWeight:'bolder',margin:0}}>
            <Tag color={record.thirdno!=null&&record.thirdno!=""?'red':'default'}>
              {(record.state==ProjectStatesEnum.SUBMITTED||record.directsubmit==1)?'已提交':(record.thirdno!=null&&record.thirdno!=""?'提交中':'未提交')}
            </Tag>
          
          <span style={{color:'#1890FF'}} onClick={()=>{onMenuClick('预览',record)}}>{text}</span>
          {
            record.lock==1&&<Tag color="red" style={{marginLeft:'5px'}} >锁</Tag>
          }
        </p>
        {
          record.balancetypename&& <p style={{color:'gray',fontSize:'12px',margin:'12px 0 0 0'}}>{record.balancetypename}</p>
        }
        
   
        
        
      </div>)
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
      title: '状态',
      width: 50,
      dataIndex: 'state',
      hideInTable:true,
      renderFormItem: (_, { type, defaultRender, ...rest }, form) => {

        return (
          <Dictselect  type="审批类型" onChange={onStatechange}/>

        )
      }
    },
    {
      dataIndex:'creators',
      key:'creators',
      title:'经办',
      hideInTable: true,
      renderFormItem: (_, { type, defaultRender, ...rest }, form) => {

        return (
          <UserAutocomplete multiple={false} onChange={onUserchange} />

        )
      }
    },
    {
      dataIndex:'chargers',
      key:'chargers',
      title:'项目负责人',
      hideInTable: true,
      renderFormItem: (_, { type, defaultRender, ...rest }, form) => {

        return (
          <UserAutocomplete multiple={false} onChange={onChargerChange} />

        )
      }
    },
    {
      title: '项目编号',
      dataIndex: 'serial',
      width: 120,
      sorter: true,
    },
    {
      title: '合同',
      dataIndex: 'contractids',
      key: 'contractids',
      hideInSearch:true,
      width: 50,
      search:false,
      render: (_:any,record:any)=>(
        <>
          {
            !record.contractids &&
            <span>未签</span>
          }
          {
            record.contractids!=null && record.contractids!= "" &&
            <span style={{color:'#1890FF'}} onClick={()=>{onMenuClick('查看合同关联项目',record)}}>已签</span>
          }
        </>
      )
    },
    {
      title: '部门/经办',
      dataIndex: 'name',
      width:150,
      search:false,
      sorter: true,
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
      width: 110,
      sorter: true,
      fieldProps: {
        // 你可以在这里自定义时间选择器的行为
        format: 'YYYY-MM-DD',
        
      },
      render: (_, record) => {
        return moment(record.starttime).format('YYYY-MM-DD')
      },
    },

    {
      title: '预算收入',
      dataIndex: 'budgetincome',
      key: 'budgetincome',
      search:false,
      width: 120,
      sorter: true,
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
      width: 120,
      sorter: true,
      className:'right',
      search:false,
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
      title: '预算支出',
      dataIndex: 'realbudgetexpend',
      key: 'realbudgetexpend',
      width: 120,
      sorter: true,
      className:'right',
      search:false,
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
      className:'right',
      sorter: true,
      width: 120,
      search:false,
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
      title: '毛利润',
      dataIndex: 'profit',
      key: 'profit',
      className:'right',
      sorter: true,
      width: 120,
      search:false,
      render: (_,record)=> {
    
        var result = record.budgetincome - record.realbudgetexpend
        if (record.finalincome) result = record.finalincome - record.realfinalexpend
        return result.toLocaleString('en-US', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })
      }
    },
    {
      title: '预算税费',
      dataIndex: 'budgettaxtotal',
      key: 'budgettaxtotal',
      width: 120,
      sorter: true,
      className:'right',
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
      title: '决算税费',
      dataIndex: 'finaltaxtotal',
      key: 'finaltaxtotal',
      width: 120,
      sorter: true,
      className:'right',
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
      title: '预算绩效',
      dataIndex: 'budgetbonus',
      key: 'budgetbonus',
      width: 120,
      sorter: true,
      className:'right',
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
      title: '决算绩效',
      dataIndex: 'finalbonus',
      key: 'finalbonus',
      width: 120,
      sorter: true,
      className:'right',
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
      title: '流程',
      key: 'flow',
      width: 120,
      search:false,
      render: (_:any, record:any) => {
        var curtab = tabs[record.state-1]||{}
        var finalindex = tabs.findIndex((e:any)=>e.label=='已提交')+1
        var temp = record.approvaltype||record.state 
        return (
          <>

            {
              (record.thirdno==null || record.thirdno.length==0) &&
              <Popover
                placement="topLeft"
                trigger={'click'}
                content={(<>
                  {
                    record.state<finalindex  &&
                    <Button type="text" onClick={()=>{
                      record.act = record.state
                      if (record.act==ProjectStatesEnum.START) record.act==ProjectStatesEnum.BUDGET
                      onMenuClick('流程',record)
                    }}>发起{curtab.label=='立项'?'预算':curtab.label}审批</Button>
                  }

                  

                  
                  </>)
                }
            
              
              >
                  <Button type='link'>{curtab.label=='已提交'?'已提交':'未'+curtab.label}</Button>
              </Popover>
            }
            {
              record.thirdno && record.thirdno.length>0 &&
              <Button type="link" onClick={()=>{onMenuClick('流程',record)}}>{tabs[temp-1].label}审批中</Button>
            }
            
          </>
        )
      },
    },
    {
      title: '操作',
      fixed: 'right',
      key: 'action',
      width: 100,
      onHeaderCell:()=>({
        style:{
          right:'-5px!important'
        }
      }),
      search:false,
      render: (_:any, record:any,index:Number) => {
        

        return (
          <>
           
            
         
              <Popover
                placement="topLeft"
                trigger={'click'}
                content={(<>
                  <Button type="text" onClick={()=>{onMenuClick('更新',record)}}>编辑</Button>
                  <Button danger type="text" onClick={()=>{onMenuClick('删除',record)}}>删除</Button>
              
                  <Button type="text" onClick={()=>{onMenuClick('预览',record)}}>预览</Button>
                  {
                    record.directsubmit==0 && record.state>ProjectStatesEnum.START && record.state<ProjectStatesEnum.SUBMITTED &&
                    <Button type="text" onClick={()=>{
                      Modal.confirm({
                        title:'确认提交？',
                        content:'提交后不可撤回，请谨慎操作',
                        onOk:()=>{
                          record.act = ProjectStatesEnum.READYTOSUBMIT
                          // onMenuClick('流程',record)
                          viewflow({projectid:record.id,act:record.act}).then((res:any)=>{
      
                              if (res.errorMessage) {
                                Modal.error({
                                  title: '报错',
                                  content: res.errorMessage,
                                });
                              } else {
                                Modal.confirm({
                                  title:"请确认流程是否正确",
                                  bodyStyle:{marginLeft:0},
                                  width: '600px',
                                  centered:false,
                                  content:(
                                    <div style={{marginLeft:'0!important'}}>
                                      <Flow key={record.act}  data={res.viewdata} statusCn={res.statusCn} step={res.step}></Flow>
                                    </div>
                                  ),
                                  onOk:async ()=>{
                                    var thirdno = await getthirdno()
                                    const par = {flowtype:record.state,thirdno,projectid:record.id,act:record.act}
                                    var res:any = await startflow(par)
                                    if (res.errorMessage) {
                                  
                                        Modal.error({
                                          title: res.errorMessage
                                        });
                                      } else {
                                        onMenuClick('流程',record)
                                      }
                                    
                                  },
                                })
                              }
                            })
                        }
                      })
                      
                    }}>提交计量</Button>
                  }
            
                  <Button  type="link" onClick={()=>{
          
                    Modal.confirm({
                        title:record.lock?'确认解档?':'确认锁档？',
                        content:record.lock?'':'锁档后项目及关联项目的收入和支出无法做任何修改！',
                        onOk:()=>{
                          lockpro({id:record.id}).then((res:any)=>{
                            if (res.errorMessage) {
                              Modal.error({
                                title: res.errorMessage
                              });
                            } else {
                              Modal.success({
                                title: '操作成功'
                              });
                              actionRef.current?.reload()
                            }
                          })
                        }
                      })
                  }}>{record.lock?'解档':'锁档'}</Button>
                  <EditCreatorButton onSave={()=>{ 
                      actionRef.current?.reload()
                    }}
                     obj={{ id:record.id,departmentid:record.departmentid,creator:record.creator }}/>
                  </>

                  )
                  
                }
            
              
              >
                  <Button>操作</Button>
              </Popover>
  
            
            
          </>
        )
      }
    }
  ]

  const onMenuClick = (action:String,record:any) => {
    setOpen(false)
    switch (action) {
      case '更新':
        setRefreshKey(++refreshKey)
        setProject(record)
        setModal1(true)
        break;
      case '删除':
        Modal.confirm({
          title: '确定要删除吗？',
          okText: '确认',
          cancelText: '取消',
          onOk: async () => {
            delproject({id:record.id}).then((res)=>{
              if (res.errorMessage){
                Modal.error({title:res.errorMessage})
              } else {
                actionRef.current?.reload()
              }
            })
          },
        });
        break
      case '预览':
        record.act = record.state
        setRefreshKey(++refreshKey)
        setProject(record)
        setModal2(true)


        break
      case '打印':

        setProject(record)
        setPrintModal(true)
        break;
      case '流程':
        setRefreshKey(++refreshKey)
        setProject(record)
        setModal2(true)
        break
      case '提交计量':
        Modal.confirm({
          title: '确定要提交计量吗？',
          okText: '确认',
          cancelText: '取消',
          onOk: async () => {
            submitmeasure({id:record.id}).then((res:any)=>{
              if (res.errorMessage){
                Modal.error({title:res.errorMessage})
              } else {
                actionRef.current?.reload()
              }
            })
          }
        })
        break
        case '查看合同关联项目':
          setRefreshKey(++refreshKey)
          setContractids(record.contractids)
          setShowProjects(true)
          break
      default:
        break;
    }
  };


const onStatechange = (e:any)=>{
  
  proTableFormRef.current?.setFieldsValue({'state':e})
}
const onUserchange = (e:any)=>{
  proTableFormRef.current?.setFieldsValue({'creators':e?e.value:''})
}
const onChargerChange = (e:any)=>{
  proTableFormRef.current?.setFieldsValue({'chargers':e?e.value:''})
}
const deptOnChange = (e:any)=>{
  proTableFormRef.current?.setFieldsValue({'departmentid':e})
}
const pdeptOnChange = (e:any)=>{
    
    proTableFormRef.current?.setFieldsValue({'departmentid':e})
}
const handleChange= (e:any)=>{
  setApprovalstate(e)
  actionRef.current?.reload()
}

const handleSubChange= (e:any)=>{
  setIssubmitted(e)
  actionRef.current?.reload()
}

const  addprosuc = (e:any)=>{

  actionRef.current?.reload()
 
} 
const onApplyChange = (e:any)=>{
  setProject(e)
  setRefreshKey(++refreshKey) //刷新modal内容
  actionRef.current?.reload()
}
const onTypeChange =(e:any)=>{
   
  setType(e.target.value)

  actionRef.current?.reload()
}

useEffect(()=>{
  getBykeyword({showall:true,keyword:'项目类别',order:'value asc'}).then((res:any)=>{
    res = res||[]
    res.unshift({value:-1,label:'全部'})
    setTypes(res)
    setRadiokey(++radiokey)
  })
},[])
  return (
    <PageContainer title={!showHeader?'':(location.query.title?('项目列表-'+location.query.title):'项目列表')} header={{title:!showHeader?'':'',breadcrumb: !showHeader?{}:{
      routes: [
        {
          path: '',
          breadcrumbName: '上一页',
        },
        {
          path: '/oa/finance/budget/index/',
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
      
      <ProTable<any, TableListPagination>
          id="projectTable"
          headerTitle={
            <Radio.Group key={'radio'+radiokey} value={type} defaultValue={type} onChange={onTypeChange} optionType="button" size='large' buttonStyle="solid" style={{ margin: 0 }}>
              {
                types.map((e:any,index:any)=><Radio.Button key={'radio'+index}  value={e.value}>{e.label}</Radio.Button>)
              }
            </Radio.Group>
          }
          onSubmit={(params)=>{
            if (Object.keys(params).length>0&&type!=-1){
              setType(-1)
            }
          }}

          title={()=>[
            

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
          ]}
          scroll={{x:'100%'}}
   
          actionRef={actionRef}
          formRef={proTableFormRef}
          rowKey={record=>record.id}
          request={(params:any, sorter, filter) => {
            document.body.scrollTop = document.documentElement.scrollTop = 0;
            params.approvalstate=approvalstate
            params.issubmitted = issubmitted
            if (sorter){
              Object.keys(sorter).forEach((key)=>{
                var order = sorter[key]=='ascend'?'asc':'desc'
                params.orderby=key+" " + order
              })
            }
            if (type) {
              params.type = type
          
            }
            if (location.query.state){
              params.state = params.state==undefined ? location.query.state: params.state
            }
            if (params.starttime){
              params.starttimestart = params.starttime[0]+' 00:00:00'
              params.starttimeend = params.starttime[1]+' 23:59:59'
          
            }
            if (params.creator) {
              params.creators = params.creator.map((e:any)=>e.value).join(',')
              
            }
            if (params.departmentid){
              params.departmentid = params.departmentid.join(',')
            }
            if (params.pdepartmentid){
              params.pdepartmentid = params.pdepartmentid.join(',')
            }
            if (params.submitdate) {
              params.submitdatestart = params.submitdate[0].substring(0,8)+'01 00:00:00';
              var d = new Date(params.submitdate[1].substring(0,4), parseInt(params.submitdate[1].substring(5,7)), 0).getDate();
              params.submitdateend = params.submitdate[1].substring(0,8)+d+' 23:59:59';
              
            }
            setParams(params)
            var res =  getlist(params)
        

            if (params.current==1){
                if(refreshStat){
                  getstattotal(params).then((res2:any)=>{ 
                  if (res2){
                      setStat(res2.stat||[])
                    }
                  })
                  setRefreshStat(0)
                }
                
              }
          
            return res;
          
          }}
          columns={columns}
          rowSelection={{
            onChange: (temp, selectedRows) => {
              var t = []
              var number = selectedRows.length
              var contractamount = 0
              var budgetincome = 0
              var finalincome = 0
              var realbudgetexpend = 0
              var realfinalexpend = 0
              var profit = 0
              var incomeinvoiceamount = 0
              var expendinvoiceamount = 0
              var budgetbonus = 0
              var finalbonus = 0
              if (selectedRows.length>0){
                selectedRows.forEach((row:any)=>{
                  contractamount+=(row.contractamount||0)
                  budgetincome+=(row.budgetincome||0)
                  finalincome+=(row.finalincome||0)
                  realbudgetexpend+=(row.realbudgetexpend||0)
                  realfinalexpend+=(row.realfinalexpend||0)
                  profit+=(row.profit||0)
                  incomeinvoiceamount+=(row.incomeinvoiceamount||0)
                  expendinvoiceamount+=(row.expendinvoiceamount||0)
                  budgetbonus+=(row.budgetbonus||0)
                  finalbonus+=(row.finalbonus||0)
                })
      
                t = [
                  {
                    label: '项目数量',
                    value: number
                  },
                  {
                    label: '合同总价',
                    value: contractamount
                  },
                  {
                    label: '预算金额',
                    value: budgetincome
                  },
                  {
                    label: '决算金额',
                    value: finalincome
                  },
                  {
                    label: '预算支出',
                    value: realbudgetexpend
                  },
                  {
                    label: '决算支出',
                    value: realfinalexpend
                  },
                  {
                    label: '利润',
                    value: profit
                  },
                  {
                    label: '入账收入',
                    value: incomeinvoiceamount
                  },
                  {
                    label: '入账成本',
                    value: expendinvoiceamount
                  },
                  {
                    label: '预算绩效',
                    value: budgetbonus
                  },
                  {
                    label: '决算绩效',
                    value: finalbonus
                  },
                ]
                setStat(t)
              }else{
                setStat(searchStat)
              }
              
              
            },
          }}
          tableAlertRender={false}
          toolBarRender={() => [
            <Select
                style={{ width: 130 }}
                onChange={handleSubChange}
                placeholder="是否提交"
                options={[
                  {
                    value: 1,
                    label: '已提交',
                  },
                  {
                    value: 0,
                    label: '未提交',
                  },
                  {
                    value: -1,
                    label: '全部',
                  },
                ]}
              />,
            <Select
                style={{ width: 130 }}
                onChange={handleChange}
                placeholder="是否在审批中"
                options={[
                  {
                    value: 1,
                    label: '审批中',
                  },
                  {
                    value: 0,
                    label: '非审批中',
                  },
                  {
                    value: -1,
                    label: '全部',
                  },
                ]}
              />,
            <Button
              type="primary"
              key="primary"
              onClick={() => {
                setRefreshKey(++refreshKey)
                setModal1(true)
                setProject({hascontract:1})
              }}
            >
              <PlusOutlined /> 新建
            </Button>,

            
          ]}
      />
      <TableScrollSync tableId="projectTable" onScroll={(scroll:any)=>{
                      const tableContent = document.querySelector('#projectTable .ant-table-content');
                      if (tableContent){
                        tableContent.scrollLeft = scroll;
                      }
          
                }} />
      <Addincome key={'addincome'+incomelistkey} visible={addincomeModal} balancetype={balancetype}  pid={project.id} onClose={()=>{
        setAddincomeModal(false)
      }}/>
      <ContractsWithProjects key={'合同'+contractids}  contractids={contractids} visible={showProjects} onClose={()=>setShowProjects(false)}/>

      <Addpro key={'addpro'+refreshKey} onChange={addprosuc} visible={modal1} data={project} onVisibleChange={setModal1}/>
     
      <Modal
        key={refreshKey}
        title="详情预览"
        style={{ top: 20 }}
        width="60vw"
        visible={modal2}
        onOk={() => setModal2(false)}
        onCancel={() => setModal2(false)}
        footer={null}
      >
        <Apply key={refreshKey} data={project}  onchange={onApplyChange}/>
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
        <ProjectDetail key={refreshKey2} id={project.id}></ProjectDetail>
      </Modal>
      <Modal
        title=""
        style={{ top: 0,left:0, aspectRatio: '210/297'}}
        width={'80vw'}
        visible={printModal}
        onOk={() => setPrintModal(false)}
        onCancel={() => setPrintModal(false)}
        footer={null}
      >
        <Print record={project} key={project.id}/>
      </Modal>

    </PageContainer>
    
  )
}
// 新增或修改项目


export default List;