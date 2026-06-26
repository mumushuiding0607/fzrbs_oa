import { ActionType, PageContainer, ProCard, ProColumns, ProFormInstance, ProTable } from '@ant-design/pro-components';
import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import { Avatar, Badge, Button, Card,ConfigProvider,DatePicker,Input,Menu,Modal ,Popover,Radio,Segmented,Space, Tabs, Tag} from 'antd';

import AddC from './addc';

import { delcontract, getlist, lock, savecontract,nullify, getpowers, downloadpurchase } from './service';
import { PlusOutlined, SearchOutlined } from '@ant-design/icons';
import Companyselect from '../company/companyselect';
import RangeNumber from './RangeNumber';
import moment from 'moment';
import DepartmentTreeSelect from '../budget/common/department_treeselect';
import Filescard from './filescard';
import { useModel } from 'umi';
import UserAutocomplete from '../budget/common/userAutocomplete';
import { BalanceTypes } from '../budget/config';
import Dictselect from '../budget/dict/dictselect';

import PayCollection from './paycollection';
import StatCard from './statcard';

import Logs from './logs';

import './common.css'
import Powerlist from './power/powerlist';
import View from './view';
import { CONTRACT_AGENTID, ContractStates, ContractStatesEnum } from './config';
import { downloadAsXlSX, downloadfromBlob } from '../utils';
import HeaderTransfer from './header_transfer';
import Rolelist from '../role/rolelist';
import Nullify from './nullify';
import Addinvoice from './invoice/addinvoice';
import Debtsearch from './debt/debtsearch';
import LedgerList from './ledger/list';
import TableScrollSync from '../common/TableScrollSync';
import Paycollectionlist from './debt/Paycollectionlist';
import DownloadDocDropdown from '../Flowtemplate/DownloadDocDropdown';

// style
const tag:CSSProperties = {
  margin: '0 5px 0 0',
  padding: '0px 4px',
  borderRadius: '15%',
}

// data



// dom
const Listc:React.FC = () =>{

  const [contract, setContract] = useState<any>({})
  const [viewmodal,setViewmodal] = useState(false)
  const [stat, setStat] = useState({})
  var [refreshKey, setRefreshKey]= useState(0)
  const [modal1, setModal1] = useState(false)
  const [modal2, setModal2] = useState(false)
  const [modal3, setModal3] = useState(false)
  const [powermodal, setPowermodal] = useState(false)
  
  const [rolemodal,setRolemodal] = useState(false)
  
  const [nullmodal,setNullmodal] = useState(false)
  const [balancetype, setBalancetype] = useState(BalanceTypes.INCOME)
  const [state,setState]=useState(-1)
  const [selectedRows, setSelectedRows]=useState<any>([])
  const [urls, setUrls] = useState('')
  const [contractid,setContractid] = useState<any>(0)
  const [params, setParams] = useState<any>({})
  const [hmodal,setHmodal] = useState(false)
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const [deadline,setDeadline] = useState(false)
  const [overdue,setOverdue] = useState(false)
  const [nopayconditions,setNopayconditions] = useState(false)
  var [headers,setHeaders] = useState([])
  const [powers,setPowers] = useState<any>([])
  const [addinvoiceModal,setAddinvoiceModal] = useState(false)
  const ref = useRef<ActionType>();
  const formRef = useRef<ProFormInstance>();
  const [debtModal,setDebtModal]=useState(false)
  const [viewfileMethod,setViewfileMethod]=useState('')
  const [activeKey, setActiveKey] = useState('tab1');

  const handleTabChange = (key:any) => {
    setActiveKey(key);
  };
  const onMenuClick = (action:String,record:any) => {

    switch (action) {
      case '更新':
        setRefreshKey(++refreshKey)
        setUrls(record.fileurls)
        setContract(record)
        setModal1(true)
        break;
      case '附件':
        setRefreshKey(++refreshKey)
        setUrls(record.fileurls)        
        setModal2(true)
        break;
      case '回款':
        setContractid(record.id)
        setModal3(true)
        setRefreshKey(++refreshKey)
        break;
      case '存档':
        Modal.confirm({
          title: '确定要存档吗？',
          okText: '确认',
          cancelText: '取消',
          onOk: async () => {
            lock({id:record.id,state:1,agentid:CONTRACT_AGENTID}).then((res:any)=>{
              if (res.errorMessage){
                Modal.error({title:res.errorMessage})
              } else {
                ref.current?.reload()
              }
            })
          },
        });
        break;
      case '解档':
          if (powers.includes('解档')){
            Modal.confirm({
              title: '确定要解档吗？',
              okText: '确认',
              cancelText: '取消',
              onOk: async () => {
                
                lock({id:record.id,state:0,agentid:CONTRACT_AGENTID}).then((res:any)=>{
                  if (res.errorMessage){
                    Modal.error({title:res.errorMessage})
                  } else {
                    ref.current?.reload()
                  }
                })
              },
            });
          } else {
            Modal.error({title:'需要【解档】权限'})
          }
          
          break;
      case '作废':
        Modal.confirm({
          title: '确定要作废吗？',
          okText: '确认',
          cancelText: '取消',
          onOk: async () => {
            setContractid(record.id)
            setNullmodal(true)
          },
      });
      break
      case '删除':
        Modal.confirm({
          title: '确定要删除吗？',
          okText: '确认',
          cancelText: '取消',
          onOk: async () => {
            delcontract({id:record.id,agentid:CONTRACT_AGENTID}).then((res)=>{
              if (res.errorMessage){
                Modal.error({title:res.errorMessage})
              } else {
                ref.current?.reload()
              }
            })
          },
        });
        break;
      case '上传发票':
        setContractid(record.id)
        setAddinvoiceModal(true)
        break
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
      search:false,
      width: 50,
      render:(_:any,record:any,index:number)=>`${index+1}`
    },
    {
      title: '申请部门',
      dataIndex: 'departmentid',
      key: 'departmentid',
      hideInTable:true,
      renderFormItem: () => {

        return (
          <DepartmentTreeSelect maxTagCount={1}/>

        )
      }
    },
    {
      title: '签订部门',
      dataIndex: 'signdept',
      key: 'signdept',
      hideInTable:true,
      renderFormItem: () => {

        return (
          <DepartmentTreeSelect maxTagCount={1}/>

        )
      }
    },
    
    {
      title: '合同名称',
      dataIndex: 'title',
      key: 'title',
      width: 260,
      sorter: true,
      render:(text:any,record:any)=>{
        var num = 0
        if (record.supplementary!=null && record.supplementary!=''){
          num = (record.supplementary.match(new RegExp('"name','g'))||[]).length
        }
       
        return (<div style={{textAlign:'left'}}>
        
          <p  style={{fontWeight:'bolder',margin:0}}>
            {
              record.state==ContractStatesEnum.NULLIFY && <Tag color="gray" style={tag}>作废</Tag>
            }
            {
              record.state==ContractStatesEnum.LOCK && <Tag color="green" style={tag}>存档</Tag>
            }
            {
               record.paystate?.includes('逾期') && <Tag color="red" style={tag}>逾期</Tag>
            }
            {
               record.paystate?.includes('临期') && <Tag color="orange" style={tag}>临期</Tag>
            }
            {
              record.state!=ContractStatesEnum.NULLIFY && num>0 && <Badge count={num} size='small' offset={[5,-2]} style={{marginRight:'10px'}}><Tag color='blue' style={tag} >补</Tag></Badge>
            }
            <span onClick={()=>{
              setViewmodal(true)
              record.attachNumber = num
              setContract(record)
              setRefreshKey(+refreshKey)
            }}>{text}</span>
          </p>
          {
            record.balancetypename&& <span style={{color:'gray',fontSize:'12px'}}>{record.balancetypename}</span>
          }
     
          
          
        </div>)
      }
    },
    {
      title: '合同编号',
      dataIndex: 'serial',
      key: 'serial',
      sorter: true,
      width: 150,
      render:(text:any,record:any)=>{
        return (<div style={{textAlign:'left'}}>
          <p  style={{fontWeight:'bolder',margin:0}}>{text}</p>
          {record.deptserial&&<span style={{color:'gray',fontSize:'12px'}}>{record.deptserial}</span>}
        </div>)
      }
    },
    {
      title: '合同类型',
      dataIndex: 'typename',
      key: 'typename', 
      hideInSearch: true,
      width: 80,
      valueEnum: {
        15: {
          text: '收入',
        },
        16: {
          text: '支出',
        },
      },
    },
    {
      title: '合同分类',
      dataIndex: 'balancetypename',
      key: 'balancetypename', 
      hideInTable:true,
      width: 80,
      sorter: true,
      renderFormItem: () => {

        return (
          <Dictselect type="合同收支类型" multiple={true} needAddItem={false} ></Dictselect>
        )
      }
    },
    
    {
      title: '付款方',
      dataIndex: 'partaname',
      key: 'partaname',
      sorter: true,
      width: 180,
      renderFormItem: () => {

        return (
          <Companyselect  multiple={false}></Companyselect>
        )
      }
    },
    {
      title: '付款方(模糊)',
      dataIndex: 'partanameLike',
      key: 'partanameLike',
      hideInTable:true,
      renderFormItem: () => {

        return (
          <Input  placeholder='请输入付款方名称'></Input>
        )
      }
    },
    {
      title: '单位性质',
      dataIndex: 'partatype',
      key: 'partatype', 
      hideInTable:true,
      width: 80,
      sorter: false,
      renderFormItem: () => {

        return (
          <Dictselect type="单位性质" multiple={true}  ></Dictselect>
        )
      }
    },
    {
      title: '收款方',
      dataIndex: 'partbname',
      key: 'partbname',
      sorter: true,
      width: 180,
      renderFormItem: () => {

        return (
          <Companyselect  multiple={false}></Companyselect>

        )
      }
    },
    {
      title: '合同总价',
      dataIndex: 'amount',
      key: 'amount',
      sorter: true,
      width: 120,
      className:'right',
      render: (text:any)=>!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0,
 
      renderFormItem: () => {

        return (
          <RangeNumber />

        )
      }
    },
    {
      title: '开票金额',
      dataIndex: 'invoiceamount',
      key: 'invoiceamount',
      hideInSearch: true,
      width: 120,
      className:'right',
      render: (text:any)=>{
        if (Number.isFinite(text)){
     
          return text.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          })
        }else{

          return 0.00
        }

      }
    },
    {
      title: balancetype==BalanceTypes.INCOME?'已回款':(balancetype==BalanceTypes.EXPEND?'已付款':'已回/已付'),
      dataIndex: 'paycollection',
      key: 'paycollection',
      className:'right',
      sorter: true,
      headerCell: () => ({ style: { textAlign: 'left' } }),
      width: 120,
      render: (text:any)=>{
        if (Number.isFinite(text)){
     
          return text.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          })
        }else{

          return 0.00
        }

      }
    },
    {
      title: '签订日期',
      dataIndex: 'signdate',
      key: 'signdate',
      sorter: true,
      valueType: 'dateRange',
      width: 120,
      fieldProps: {
        // 你可以在这里自定义时间选择器的行为
        format: 'YYYY-MM-DD',
        
      },
      render: (_:any, record:any) => {
        return record.signdate?moment(record.signdate).format('YYYY-MM-DD'):''
      },
    },
    ,
    {
      title: '创建日期',
      dataIndex: 'inserttime',
      key: 'inserttime',
      sorter: true,
      valueType: 'dateRange',
      width: 120,
      fieldProps: {
        // 你可以在这里自定义时间选择器的行为
        format: 'YYYY-MM-DD',
        
      },
      render: (_:any, record:any) => {
        return record.inserttime?moment(record.inserttime).format('YYYY-MM-DD'):''
      },
    },
   
    {
      title: '合同起止',
      key: 'date',
      valueType: 'dateRange',
      width: 120,
      fieldProps: {
        // 你可以在这里自定义时间选择器的行为
        format: 'YYYY-MM-DD',
        
      },
      render:(text:any,record:any)=>`${(record.starttime?record.starttime.substring(0,10):'')+'至'+(record.endtime?record.endtime.substring(0,10):'执行结束')}`
    },

    {
      title: '签订部门',
      dataIndex: 'signdept',
      key: 'signdept',
      width: 120,
      hideInSearch:true
    },
  
    {
      title:'经办人',
      dataIndex:'creator',
      key:'creator',
      width:100,
      sorter: true,
      search:true,
      render:(_:any,record:any)=>record.name,
      renderFormItem: () => {

        return (
          <UserAutocomplete multiple={true}/>

        )
      }
    },
   
    {
      title: '状态',
      dataIndex: 'state',
      key: 'state',
      sorter: true,
      hideInSearch:true,
      hideInTable:true
    },
    {
      title: '操作',
      key: 'action',
      fixed: 'right',

      onHeaderCell:()=>({
        style:{
          right:'-5px!important'
        }
      }),
      search:false,
      render: (_:any, record:any,index:Number) => (
    
        <>

           {
            record.state!=ContractStatesEnum.NULLIFY && 
            <Popover
              placement="topLeft"
              trigger={'click'}
              
              key={'popover'+index}
              overlay={<div key={'overlay'+index} id={'overlay'+index}></div>}
              content={(<>
                
                <Button type="text" onClick={()=>{onMenuClick('回款',record)}}>{record.type==BalanceTypes.INCOME?'回款':'付款'}</Button>
                <Button type="text" onClick={()=>{onMenuClick('附件',record)}}>附件</Button>
                
                {
                  (powers.includes('编辑')||currentUser.wxuserid==record.creator)&&
                  <div>
                    <Button type="text" onClick={()=>{onMenuClick(record.state==1?'解档':'存档',record)}}>{record.state==1?'解档':'存档'}</Button>
                    <Button type="text" onClick={()=>{onMenuClick('更新',record)}}>更新</Button>
                    
                    <br />
                    <Button danger type="link" onClick={()=>{onMenuClick('作废',record)}}>作废</Button>
                    <Button danger type="text" onClick={()=>{onMenuClick('删除',record)}}>删除</Button>
                    
                  </div>
                }
             
                <Button type="link" onClick={()=>{onMenuClick('上传发票',record)}}>上传发票</Button>

                </>)
              }
          
            
            >
              <Button key={'button'+index}>操作</Button>
            </Popover>
           }
        </>
      ),
    },

  ]


  // method
  const onPaycollectionChange = ()=>{
    ref.current?.reload()
  }
  const onAddcSuc = (e:any)=>{
    if(e&&e.createMirror) setBalancetype(BalanceTypes.ALL)
    ref.current?.reload()
    setModal1(false)

  }
  const onDeadlineCheck = (e:any)=>{
    setDeadline(e)
    ref.current?.reload()
  }
  const onOverdueCheck = (e:any)=>{
    setOverdue(e)
    ref.current?.reload()
  }
  const onNopayconditionsCheck = (e:any)=>{
    setNopayconditions(e)
    ref.current?.reload()
  }
  const onBalancetypechange =(e:any)=>{
    setBalancetype(e.target.value)
    ref.current?.reload(true)
  }

  const onLogsChange = (e:any)=>{
    setContract(e)
    setRefreshKey(++refreshKey)
  }

  const onStatechange = (e:any)=>{
    
    setState(e)
    
    ref.current?.reload(true)
  }
  const onHeaderChange = (h:any)=>{
    if (h.length==0){
      Modal.error({title:'表头不能为空'})
      return
    }
    setHeaders(h)
  }

  useEffect(()=>{
    getpowers({agentid:CONTRACT_AGENTID}).then((res:any)=>{
      if (res.errorMessage){
        Modal.error({title:res.errorMessage})
        return
      }
      res.data = res.data||''
      res.data.split && setPowers(res.data.split(','))

    })
  },[])
  return (
    <ConfigProvider >
      <PageContainer title="合同列表" 
 
        tabList={[
            {
              tab: '合同台账',
              key: 'tab1',
            },
            {
              tab: '采购项目台账',
              key: 'tab2',
            },
            {
              tab: '欠款查询',
              key: 'tab3',
            },

          ]}
          onTabChange={handleTabChange}
       header={{breadcrumb: {},}} extra={powers && powers.includes('管理')?[
              
              <Button key="e1"  onClick={()=>{
                setRolemodal(true)
              }}>角色设置</Button>
            ]:[]
        
        
      }>
        
        {
          activeKey=='tab1'&&
          <>
          <ProTable
            id="contractTable"
            tableLayout={'fixed'}

            scroll={{x:'max-content'}}
          
            rowSelection={{
              onChange: (_, selectedRows) => {
                setSelectedRows(selectedRows);
              },
            }}
            
            pagination={{pageSize:20}}
            rowKey={record=>'c'+record.id}
          
            request={(params, sorter, filter) => {
              
              document.body.scrollTop = document.documentElement.scrollTop = 0;
              if (sorter){
                Object.keys(sorter).forEach((key)=>{
                  var order = sorter[key]=='ascend'?'asc':'desc'
                  params.orderby=key+" " + order
                })
              }
              if (params.creator && params.creator[0]) params.creator = params.creator.map((e:any)=>e.value).join(',')

              if (params.partaname && params.partaname instanceof Object) {
                params.parta = params.partaname.value
                params.partaname = undefined
              }else{
                delete params.parta
              }
              if (params.partbname && params.partbname instanceof Object) {
                params.partb = params.partbname.value
                params.partbname = undefined
              }else{
                delete params.partb
              }
              if (params.amount) {
                if (params.amount[0]!=undefined) params.amountfloor = params.amount[0]
                if (params.amount[1]!=undefined) params.amountceil = params.amount[1]
              }
              if (params.partatype){
                params.partatype = params.partatype.map((e:any)=>e.value).join(',')
              }else{
                delete params.partatype
              
              }
              if (params.signdate){
                params.signdatestart = params.signdate[0]+' 00:00:00'
                params.signdateend = params.signdate[1]+' 23:59:59'
              }
              if (params.signdate){
                params.signdatestart = params.signdate[0]+' 00:00:00'
                params.signdateend = params.signdate[1]+' 23:59:59'
              }
              if (params.inserttime){
                params.inserttimestart = params.inserttime[0]+' 00:00:00'
                params.inserttimeend = params.inserttime[1]+' 23:59:59'
              }
              
              if (params.date){
                params.starttime = params.date[0]+' 00:00:00'
                params.endtime = params.date[1]+' 23:59:59'
              }
              if (params.signdept) {
                params.signdeptid = params.signdept.join(',')
              }
              // if (params.balancetypename) params.balancetype = params.balancetypename
              if (params.balancetypename && params.balancetypename[0]) params.balancetypename = params.balancetypename.map((e:any)=>e.label).join(',')
              // if (params.typename) params.type = params.typename
              if (balancetype) params.type = balancetype
              if (deadline) params.showdeadline = true
              if (overdue) params.showoverdue = true
              if (nopayconditions) params.shownopayconditions=true
              params.state = state

              var res =  getlist(params)
              res.then((temp:any)=>{
                setViewfileMethod(temp.viewfileMethod)
                setStat(temp.stat)
              })
              setParams(params)
              return res;
            }}
            actionRef={ref}
            formRef={formRef}
            columns={columns}
            
          
            form={{
            
            }}
            onSubmit={(params)=>{
              if (Object.keys(params).length>0 && balancetype!=BalanceTypes.ALL){

                setBalancetype(BalanceTypes.ALL)
              }
            }}
            headerTitle={
              <Radio.Group key={balancetype} value={balancetype} onChange={onBalancetypechange} optionType="button" size='large' buttonStyle="solid" style={{ margin: 0 }}>
                <Radio.Button value={BalanceTypes.INCOME}>收款合同</Radio.Button>
                <Radio.Button value={BalanceTypes.EXPEND}>付款合同</Radio.Button>
                <Radio.Button value={0}>全部合同</Radio.Button>
              </Radio.Group>
            }
            
            title={()=>[
              <StatCard key='statcard' data={stat} onDeadlineCheck={onDeadlineCheck} onOverdueCheck={onOverdueCheck} onNopayconditionsCheck={onNopayconditionsCheck}/>
            ]}
            toolBarRender={() => [
              <Dictselect onChange={onStatechange}   needAddItem={false} type="合同状态" />,
              <Button
                type="default"
                key="primary"
                
                onClick={() => {
                  setDebtModal(true)
                }}
              >
                <SearchOutlined /> 逾期欠款查询
              </Button>,
              <Button
                type="primary"
                key="primary"
                
                onClick={() => {
                  setContract({type:BalanceTypes.INCOME})

                  setRefreshKey(++refreshKey)
                  setModal1(true)
                }}
              >
                <PlusOutlined /> 新建
              </Button>,
              <Button onClick={()=>{
                var temp:any = []
                selectedRows.map((s:any)=>s.fileurls).filter((e:any)=>e!=null&&e!='').map((u:any)=>u.split(',')).forEach((element:any) => {
                  // ?替换成&
                  element = element.map((e:any)=>{
                    e = e.replace('?','&')
                    e = viewfileMethod+e
                    return e
                  })
                  
                  temp.push(...element)
                });
                setUrls(temp)
                console.log('urls:',temp)
                setRefreshKey(++refreshKey)
                setModal2(true)
              }}>批量下载附件</Button>,
              <Button onClick={()=>{
                setHmodal(true)

              }}>合同信息导出</Button>
            ]}
            
          />
          <TableScrollSync tableId="contractTable" onScroll={(scroll:any)=>{
            const tableContent = document.querySelector('#contractTable .ant-table-content');
            if (tableContent){
              tableContent.scrollLeft = scroll;
            }

      }} />
          
          </>
          
          
        }
        {
          activeKey=='tab2'&&
          <>
          <LedgerList />
          
          </>
          

        }
        {
          activeKey=='tab3'&&
          <>
          <Paycollectionlist />
          
          </>
          

        }
        
        <Modal
          title={
            (<Logs key={refreshKey} id={contract.id} onChange={onLogsChange}/>)
          }
          maskClosable={false}
          width={850}
          style={{ top: 20}}
          visible={modal1}
          onOk={() => setModal1(false)}
          onCancel={() => setModal1(false)}
          footer= {null}
        >
          
          <AddC key={refreshKey} data={contract} onChange={onAddcSuc}/>
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
          
          <Filescard key={refreshKey} urls={urls}/>
        </Modal>
        <Modal
          title={null}
          style={{ top: 20 }}
          width={650}
          visible={addinvoiceModal}
          onOk={() => {

          }}
          onCancel={() => setAddinvoiceModal(false)}
          footer={null}
        >
          
          <Addinvoice key={contractid} id={contractid}/>
        </Modal>
        <Modal
          title="记录"
          style={{ top: 20 }}
          visible={modal3}
          onOk={() => setModal3(false)}
          onCancel={() => setModal3(false)}
          footer= {null}
        >
          
          <PayCollection key={'paycollection'+refreshKey} financechek={true} contractid={contractid} onChange={onPaycollectionChange}/>
        </Modal>
        <Modal
     
          maskClosable={false}
          width={850}
          style={{ top: 0}}
          visible={powermodal}
          onOk={() => setPowermodal(false)}
          onCancel={() => setPowermodal(false)}
          footer= {null}
        >
          
          <Powerlist/>
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
        <Modal
          width={'100vw'}
          style={{ top: 0}}
          visible={debtModal}
          onOk={() => setDebtModal(false)}
          onCancel={() => setDebtModal(false)}
          footer= {null}

        >
          <Debtsearch/>
          
        </Modal>

        <Modal
          width={600}
          style={{ top: 0}}
          visible={hmodal}
          onOk={() => {
            params.pageSize = 100000
            params.current = 1

            if (headers.length==0){
              headers = columns.filter((e:any)=>!e.hideInTable&&!['index','action'].includes(e.key))
            }
       
            getlist(params).then((res:any)=>{

              if (res.data.length<0){
                Modal.error({title:'数据为空'})
              }else{
                var temp = res.data.map((row:any)=>{
                  var arr:any = []
                
                  headers.forEach((h:any)=>{
                    
                    var temp:string = (row[h.key]||'').toString()
                    if (temp) {
                      temp = temp.replaceAll(',','，').trim()
                    }
                    
                    switch (h.key) {
                      case 'date':
                        arr.push((row.starttime?row.starttime.substring(0,10):'')+'至'+(row.endtime?row.endtime.substring(0,10):'执行结束'))
                        break;
                      case 'creator':
                        arr.push(row.name)
                        break
                      
                      case 'signdate':
                        arr.push(row.signdate?moment(row.signdate).format('YYYY-MM-DD'):'')
                        break
                      case 'state':
                        arr.push(ContractStates[row.state])
                        break
                      default:
                        arr.push(temp)
                        break;
                    }
                    
                    
                  })

                  return arr
                })
                var x = headers.map((t:any)=>t.title)
                x.push('状态')
                temp.unshift(x)
                downloadAsXlSX(temp,'合同信息导出')
              }
            })
          }}
          onCancel={() => setHmodal(false)}

        >
          
          <HeaderTransfer  onChange={onHeaderChange} />
        </Modal>
        <Modal
         
         
          visible={rolemodal}
          width='100vw'
          style={{top:0,right:0}}
          onOk={() => setRolemodal(false)}
          onCancel={() => setRolemodal(false)}
          footer= {null}
        >
          <Rolelist type='合同管理' agentid={CONTRACT_AGENTID}></Rolelist>
          
        </Modal>

        <Nullify visible={nullmodal} id={contractid} onVisibleChange={(v:any)=>{
          setNullmodal(v)
          ref.current?.reload(true)
        }}></Nullify>
      </PageContainer>
    </ConfigProvider>
  )
  
}
export default Listc