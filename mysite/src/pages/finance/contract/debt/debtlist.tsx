
import { Button, Card, DatePicker, Dropdown, Input, Modal, Popover, Radio, Select, Tag, Tooltip } from 'antd';
import moment from 'moment';

import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import UserAutocomplete from '../../budget/common/userAutocomplete';
import { ActionType, ProFormInstance, ProTable } from '@ant-design/pro-components';
import Dictselect from '../../budget/dict/dictselect';
import { debtlist, debturge, setrecoverable, delurge } from './service';
import View from '../view';
import DepartmentTreeSelect from '../../budget/common/department_treeselect';
import Companyselect from '../../company/companyselect';

import { downloadAsXlSX } from '../../utils';
import Urgelogs from './urgelogs';
import UrgeView from './urgeview';
import TableScrollSync from '../../common/TableScrollSync';
import AddDebtUrge from './AddDebtUrge';
import UrgelogsModal from './UrgelogsModal';
import Add from '../../company/add';
import AddDealResult from './AddDealResult';
import PayCollection from '../../invoice/paycollection';
import EditResponsibleDeptButton from './EditResponsibleDeptButton';
import ContractSelect from '../contract-select';
import { BalanceTypes } from '../../budget/config';



const tag:CSSProperties = {
  margin: '0 5px 0 0',
  padding: '0px 4px',
  borderRadius: '15%',
}
// balancetype 15收入，16支出
const Debtlist: React.FC<{scrollTop?:boolean,table?:any,searbarSize?:number,params?:any}> = ({params={},searbarSize=2,scrollTop=false,table}) =>{
  const [contract, setContract] = useState<any>({})
  const [viewmodal,setViewmodal] = useState(false)
  var [refreshKey, setRefreshKey]= useState(0)
  const proTableFormRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  var [params,setParams] = useState<any>(params)
  const [showUrgelogs,setShowUrgelogs] = useState(false)
  const [showUrgeView,setShowUrgeView] = useState(false)
  const [searchtype,setSearchtype]=useState('全部')
  const [addView,setAddView]=useState(false)
  const [visible,setVisible]=useState(false)
  const [company,setCompany]=useState<any>({})
  const [dealView,setDealView]=useState(false)
  const [pmodal,setPmodal]=useState(false)
  const [payParams,setPayParams]=useState<any>({})
  const [contractkey,setContractkey]=useState(0)
  const [tkey,setTkey]=useState(0)
  
  const onSearchtypeChange = (e:any) => {
    setSearchtype(e.target.value)
    actionRef.current?.reload(true)
  }
  let columns:any = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      width: 65,
      fixed:'left',
      render:(_:any,record:any,index:number)=>`${index+1}`
    },
    {
      title:'审批单号',
      dataIndex:'thirdNo',
      key:'thirdNo',
      sorter: true,
      width: 170,
      hideInTable: searchtype !== '待审批',
      render: (text:any,record:any)=>{
        var temp =  record.thirdNo&&record.thirdNo!='-'&&record.thirdNo.split?record.thirdNo.split(','):[]
        return <div>
          {
            temp.map((item:any,index:number)=>{
              return <span onClick={()=>{
          

          if (searchtype === '无合同'){
            setContract({debturgeid:record.id})
          }else{

            setContract({...record,thirdNo:item,urgeserial:undefined})
          }
          setContractkey(contractkey+1)
          setShowUrgeView(true)

          
        }} style={{color:text?'#1890FF':''}}>{item}</span>
            })
          }
        </div>
      }
    },
    {
      title:'催收编号',
      dataIndex:'urgeserial',
      key:'urgeserial',
      sorter: true,
      width: 170,
      render: (text:any,record:any)=>{
        var temp =  record.urgeserial&&record.urgeserial!='-'&&record.urgeserial.split?record.urgeserial.split(','):[]
        return temp.length>0?<div style={{display:'flex',flexDirection:'column'}}>
          {
            temp.map((item:any,index:number)=>{
              return <span onClick={()=>{
         
          if (!text){
            return
          }
          if (searchtype === '无合同'){
            setContract({debturgeid:record.id})
          }if (table=='催收记录'){
            setContract({...record,id:record.contractid,urgeserial:item,thirdNo:undefined})
          } else{

            setContract({...record,urgeserial:item,thirdNo:undefined})
          }
          setContractkey(contractkey+1)
          setShowUrgeView(true)

          
        }} style={{color:text?'#1890FF':''}}>{item}</span>
            })
          }
        </div>:<span onClick={()=>{
         
   
          if (searchtype === '无合同'){
            setContract({debturgeid:record.id})
          }else{

            setContract({...record})
          }
          setContractkey(contractkey+1)
          setShowUrgeView(true)

          
        }} style={{color:text?'#1890FF':''}}>{record.thirdNo?'审批中':'暂无编号'}</span>
      }

    },
    {
      title: '合同名称',
      dataIndex: 'title',
      key: 'title',
      width: 200,
      sorter: true,
      render:(text:any,record:any)=>{
        var num = 0
        if (record.supplementary!=null && record.supplementary!=''){
          num = (record.supplementary.match(new RegExp('"name','g'))||[]).length
        }
       
        return (searchtype !== '无合同'?<div style={{textAlign:'left'}}>
        
          <p  style={{fontWeight:'bolder',margin:0}}>

            {
               record.paystate?.includes('逾期') && <Tag color="red" style={tag}>逾期</Tag>
            }
            {
               record.paystate?.includes('临期') && <Tag color="orange" style={tag}>临期</Tag>
            }

            <span onClick={()=>{
              setViewmodal(true)
              record.attachNumber = num
              if (searchtype === '无合同'){
                setContract({debturgeid:record.id})
              }if (table=='催收记录'){
                setContract({id:record.contractid})
              } else{
                setContract({...record})
              }
              setRefreshKey(+refreshKey)
            }}>{text=='-'?'':text}</span>
            
          </p>
          <div style={{color:'gray',fontSize:'12px'}}>{record?.contractserial||record.serial}</div>
          {
            record.balancetypename&& <span style={{color:'gray',fontSize:'12px'}}>{record.balancetypename}</span>
          }

     
          
          
        </div>:<span onClick={()=>{
          setContract({debturgeid:record.id})
          setContractkey(contractkey+1)
          setAddView(true)
        }}>{record?.contractserial||record?.serial}</span>)
      }
    },
    {
      title:'债务方信息',
      dataIndex:'partaname',
      key:'partaname',
      width: 250,
      render: (text:any,record:any)=>{
        
        return table=='逾期明细表'?<div style={{display:'flex',flexDirection:'column'}}>
         <span>{record.partaname}</span>
         <span>{record.contactor+' '+record.mobile}</span>  
         <span>{record.address}</span>  

        
        </div>:<span onClick={()=>{
          setCompany({id:record.parta,company:record.partaname})
          setVisible(true)
        }} style={{color:text?'#1890FF':''}}>{record?.contractpartaname||text}</span>
      }
    },
    {
      title: '业务时间',
      dataIndex: 'signdate',
      key: 'signdate',
      sorter: true,
      valueType: 'dateRange',
      width: 150,
      fieldProps: {
        // 你可以在这里自定义时间选择器的行为
        format: 'YYYY-MM-DD',
        
      },
      render: (_:any, record:any) => {
        var overdueDate = moment().subtract(record.age, 'days');
        return <>
          {searchtype !== '无合同'&&table !== '催收记录' && (
            <>
              <div>签订:{record.signdate?moment(record.signdate).format('YYYY-MM-DD'):''}</div>
              <div>开票:{record.invoicedate?moment(record.invoicedate).format('YYYY-MM-DD'):''}</div>
            </>
          )}
          {
            table === '催收记录'&&(
              <div>签订:{record.signdate?moment(record.signdate).format('YYYY-MM-DD'):''}</div>
            )
          }
          <div>逾期:{overdueDate.format('YYYY-MM-DD')}</div>
        </>
      },
    },
    {
      title:'业务款项',
      dataIndex:'urgeamount',
      key:'urgeamount',
      width: 150,
      render: (_:any, record:any) => {
        record.paycollection = record.paycollection?record.paycollection:0
        return <>
          {searchtype !== '无合同' &&table !== '催收记录'? (
            <>
              <div>合同:￥{!Number.isNaN(record.amount)?parseFloat(record.amount).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }):0}</div>
              <div>开票:￥{!Number.isNaN(record.invoiceamount||0)?parseFloat(record.invoiceamount||0).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }):0}</div>
            </>
          ) : null}
          {
            table === '催收记录'&&(
              <div>合同:￥{!Number.isNaN(record.contractamount)?parseFloat(record.contractamount).toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
              }):0}</div>
            )
          }
          <div>回款:￥{!Number.isNaN(record.paycollection)?parseFloat(record.paycollection).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
          }):0}</div>
        </>
      },
    },
    {
      title:'催款金额',
      dataIndex:'debtamount',
      key:'debtamount',
      sorter: true,
      hideInTable: table !== '催收记录',
      width: 120,
      render: (text:any,record:any)=>{
        var t = text||0
        if (t==='-') t=0
        return (
        <span >
          {!Number.isNaN(t)?parseFloat(t).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
        </span>
      )
      }
    },

    {
      title:'欠款金额',
      dataIndex:'debt',
      key:'debt',
      sorter: true,
      width: 120,
      render: (text:any,record:any)=>{
        var t = text
        if (!record.debtamount){
          t = record.amount-record.paycollection
        }else{
          t = record.debtamount-record.paycollection
        }
        if (t==='-') t=0
        return (
        <span onClick={()=>{
          setPmodal(true)
          setContract(record)
        }}>
          {!Number.isNaN(t)?parseFloat(t).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
        </span>
      )
      }
    },
    {
      title:'账龄(年)',
      dataIndex:'age',
      key:'age',
      width: 100,
      sorter: true,
     
      render: (text:any,record:any)=>{
        if (text=='-') text=0
        return <>
        <span style={{color:'red',fontWeight:'bold'}}>
          {
            text?((parseFloat(text)/365).toFixed(1) + '年'):''
          }
        </span></>
      }
        
     
    },
    {
      title:'责任人',
      dataIndex:'creator',
      key:'creator',
      width: 100,
      search:true,
      render:(_:any,record:any)=>{
        return <>
          <p>{record.name||record.creatorname}</p>
          <p>{record.department||record.applydeptname}</p>
        </>
      },
      renderFormItem: () => {

        return (
          <UserAutocomplete multiple={false}/>

        )
      }
    },
    {
      title:'签订部门',
      dataIndex:'signdept',
      key:'signdept',
      width: 250,
      render:(_:any,record:any)=>record.signdeptname
    },
    {
      title:'拖欠原因',
      dataIndex:'reason',
      key:'reason',
      width: 200,
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
      title:'催款措施',
      dataIndex:'urgetypename',
      key:'urgetypename',
      width: 150,
      render: (text:any,record:any)=>{
      var temp = text
      var datas = record.urgetype_info?record.urgetype_info.split('||'):[]
        
        return table=='逾期明细表'?<div style={{display:'flex',flexDirection:'column'}}>
          {
            datas.map((item:any)=>{
              
              return <span>{item}</span>
            })
          }
      
        </div>:<span onClick={()=>{
          if(!temp)return
          
          setContract(record)
          setShowUrgelogs(true)
        }} >{temp||''}</span>
      }
     
    },
    {
      title:'清欠结果',
      dataIndex:'urgeresultname',
      key:'urgeresultname',
      width: 300,
      render: (text:any,record:any)=>{
      var temp = text
        var datas = record.urgeresult_info?record.urgeresult_info.split('||'):[]
        return table=='逾期明细表'?<div style={{display:'flex',flexDirection:'column'}}>
 
          {
            datas.map((item:any)=>{
              
              return <span>{item}</span>
            })
          }
        </div>:<span onClick={()=>{
          if(!temp)return
          
          setContract(record)
          setShowUrgelogs(true)
        }} >{temp||''}</span>
      }
    },
    {
      title:'处置结果',
      dataIndex:'dealresultname',
      key:'dealresultname',
      width: 150
    },
    {
      title:'备注',
      dataIndex:'note',
      key:'note',
      width: 200,
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
      title: '操作',
      key: 'action',
      dataIndex:'action',
      fixed: 'right',
      width: 80,
      onHeaderCell:()=>({
        style:{
          right:'-5px!important'
        }
      }),

      search:false,
      render: (_:any, record:any,index:Number) => (
    
        <>

      
            <Popover
              placement="topLeft"
              trigger={'click'}
              
              key={'popover'+index}
              overlay={<div key={'overlay'+index} id={'overlay'+index}></div>}
              content={(<>
                
                <Button type="text" onClick={()=>{
          
                  // 计算欠款金额：合同金额 - 已催收金额
                  const urgedAmount = record.urgedamount || 0;
                  const debtAmount = record.amount - urgedAmount;
                  
                  console.log('table:',table)
                  if (table == '催收记录'){
                    console.log('催收记录:',record)
                    setContract({contractid:record.contractid,debturgeid:record.id,parta:record.parta})
                  }else{
                    setContract({contractid:record.id,debturgeid:record.debturgeid,parta:record.parta,amount:record.amount,debtamount:debtAmount})
                  }

                  setAddView(true)
                  setContractkey(contractkey+1)
                }}>欠款催收</Button>
                <Button type="text" onClick={()=>{
          
                  setContract({contractid:record.id,urgetype:record.urgetype})
                  setDealView(true)
                }}>处置</Button>
                <Button type="text" onClick={()=>{
                  setShowUrgelogs(true)
                  setContract(record)
                }}>催收纪录</Button>
                <Button type="text" onClick={()=>{
                  setrecoverable({contractid:record.id}).then((res:any)=>{
                    if(res.errorMessage){
                      Modal.error({
                        title: res.errorMessage,
                      });
                    }else{
                      Modal.info({title:'操作成功！'})
                      actionRef.current?.reload()
                      
                    }
                  })
                }}>{record.recoverable?'取消':'设置'}账销案存</Button>
                   <EditResponsibleDeptButton
                     onSave={()=>{ 
                      actionRef.current?.reload()
                    }}
                     obj={{ id:record.id,departmentid:record.departmentid,creator:record.creator }}/>
                     

                </>)
              }
          
            
            >
              <Button key={'button'+index}>操作</Button>
            </Popover>
           
        </>
      ),
    },
  ];
  const [expanded, setExpanded] = useState(false);

  // 所有表单项
  const items = [
    <DatePicker.RangePicker
            style={{width:'300px'}}
            placeholder={['开始日期', '结束日期']}
            allowEmpty={[false, true]}
            picker="month"
            onChange={(date:any, dateString:any) => {
              if(dateString){
                params.datestart = dateString[0];
                params.dateend = dateString[1];
                setPayParams({datestart:dateString[0],dateend:dateString[1]})
                setParams(params);
              }
            }}
          />,
          <div style={{ width: 100 }}>
    <UserAutocomplete
      key="creator"
      style={{ width: '100px' }}
      placeholder="责任人"
      multiple={false}
      onChange={(value: any) => {
        params.creator = value ? value.value : null;
        setParams(params);
      }}
    /></div>,
    <div style={{ width: 250 }}>
      <DepartmentTreeSelect
        key="applydept"
        showTreeCheckStrictly={true}
        placeholder={'责任部门'}
        style={{ width: '250px' }}
        defaultValue={params?.departmentid||undefined}
        maxTagCount={1}
        onChange={(value: any) => {
          params.departmentid = value.join(',');
          setParams(params);
        }}
      />
    </div>,
    <div style={{ width: 250 }}>
      <ContractSelect multiple={false} showupload={false} type={BalanceTypes.INCOME} onChange={(value: any) => {
     
          params.contractids = value.id
          setParams(params);
        }} />
    </div>,
    
    
    <Input
      key="urgeserial"
      style={{ width: '150px' }}
      onChange={(e: any) => {
        params.urgeserial = e.target.value;
        setParams(params);
      }}
      placeholder="催收编号"
    />,
   <div style={{ width: 250 }}>
    <DepartmentTreeSelect
      key="signdept"
      showTreeCheckStrictly={true}
      placeholder={'签订部门'}
      style={{ width: '250px' }}
      maxTagCount={1}
      onChange={(value: any) => {
        console.log('签订部门:',value)
        params.signdeptid = value.join(',');
        setParams(params);
      }}
    /></div>,
    <Companyselect
      key="parta"
      style={{ width: '150px' }}
      multiple={false}
      placeholder="债务方名称"
      onChange={(value: any) => {
        params.parta = value ? value.id : null;
        setParams(params);
      }}
    />,
    <Dictselect
      key="partatype"
      type="单位性质"
      multiple={true}
      style={{ width: '150px' }}
      onChange={(value: any) => {
        params.partatype = value ? value.map((e: any) => e.value).join(',') : null;
        setParams(params);
      }}
    />,
    <Dictselect
      key="urgetype"
      type="清欠方式"
      multiple={false}
      needAddItem={false}
      style={{ width: '150px' }}
      onChange={(value: any) => {
        params.urgetype = value;
        setParams(params);
      }}
    />,
    <Dictselect
      key="urgeresult"
      type="清欠结果"
      multiple={true}
      needAddItem={false}
      style={{ width: '150px' }}
      onChange={(value: any) => {
        
        params.urgeresult = value?value.map((e: any) => e.value).join(',') : null;
        setParams(params);
      }}
    />,
    <Dictselect style={{ width: '150px' }}  type="合同收支类型"  needAddItem={true} multiple={true}  onChange={(value: any) => {
        params.balancetype = value?value.map((e: any) => e.value).join(',') : null;
        setParams(params);
      }}/>
    ,
    <Select
      style={{ width: 120 }}
      placeholder="账龄"
      allowClear
      onChange={(value: string) => {
        if (value) {
          params.debtage_start = value;
        } else {
          params.debtage_start = undefined;
        }
        setParams({ ...params });
      }}
    >
      <Select.Option value="1">1年以内</Select.Option>
      <Select.Option value="2">2年以内</Select.Option>
      <Select.Option value="3">3年以内</Select.Option>
      <Select.Option value="4">4年以内</Select.Option>
      <Select.Option value="5">5年以上</Select.Option>
    </Select>
  ];

  const displayItems = expanded ? items : items.slice(0, searbarSize);


 
  // 统计摘要数据（实际数据需要从API获取）
  const [summaryData, setSummaryData] = useState({
    totalDebt: 0,
    totalReceived: 0,
    totalAmount: 0
  });

  return (
    <>
    
    {/* 统计栏 */}
    {
      table=='逾期明细表'&&
      <Card  style={{ marginBottom: 16 }}>
      <div key={tkey} style={{ display: 'flex', justifyContent: 'space-around', textAlign: 'center' }}>
        <div>
          <div style={{ fontSize: 12, color: '#888' }}>合同总金额</div>
          <div style={{ fontSize: 20, fontWeight: 'bold', color: '#faad14' }}>
            ¥{summaryData.totalAmount.toLocaleString()}
          </div>
        </div>
        <div>
          <div style={{ fontSize: 12, color: '#888' }}>欠款总额</div>
          <div style={{ fontSize: 20, fontWeight: 'bold', color: '#ff4d4f' }}>
            ¥{summaryData.totalDebt.toLocaleString()}
          </div>
        </div>
        <div>
          <div style={{ fontSize: 12, color: '#888' }}>已收金额</div>
          <div style={{ fontSize: 20, fontWeight: 'bold', color: '#52c41a' }}>
            ¥{summaryData.totalReceived.toLocaleString()}
          </div>
        </div>
      </div>
    </Card>
    }
    
  
    <ProTable
     
      scroll={{x:'100%'}}
      id={'debtlist'}
      actionRef={actionRef}
      params={params}
      formRef={proTableFormRef}
      rowKey={(record:any)=>record.id}
      search={false}
      columns={columns}
      
      pagination={{
        defaultPageSize: 20,
        showQuickJumper: true,
        showSizeChanger: true,
      }}
      request={(params, sorter, filter) => {
        if (scrollTop){
          document.body.scrollTop = document.documentElement.scrollTop = 0;
        }
        
        if (sorter){
          Object.keys(sorter).forEach((key)=>{
            var order = sorter[key]=='ascend'?'asc':'desc'
            params.orderby=key+" " + order
          })
        }
        if (searchtype){
          params.searchtype = searchtype;
        }
        if (table){
          params.table = table;
        }
       
        
        return debtlist(params).then((res: any) => {
          // 设置统计摘要数据
          if (res?.summary) {

            console.log("res.summary:",res.summary)
            setSummaryData({
              totalDebt: res.summary.totalDebt || 0,
              totalReceived: res.summary.totalReceived || 0,
              totalAmount: res.summary.totalAmount || 0
            });
            setTkey(tkey+1)
          }
          return res;
        });
      }}
      headerTitle={
              <>
              {
                !table&&<Radio.Group key={searchtype} value={searchtype} onChange={onSearchtypeChange} optionType="button" size='large' buttonStyle="solid" style={{ margin: 0 }}>
                <Radio.Button value={'待审批'}>待审批</Radio.Button>
                <Radio.Button value={'催收中'}>催收中</Radio.Button>
                <Radio.Button value={'待处置'}>待处置</Radio.Button>
                <Radio.Button value={'应催收'}>应催收</Radio.Button>
                <Radio.Button value={'已处置'}>已处置</Radio.Button>
                <Radio.Button value={'全部'}>全部</Radio.Button>
                <Radio.Button value={'无合同'}>无合同</Radio.Button>
              </Radio.Group>
              }
              </>
            }
            title={()=>[
              
              
            ]}
      toolbar={{

        filter: (
          <><div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', alignItems: 'flex-start' }}>
                {displayItems}

                {/* 展开/收起按钮 */}
                {items.length > searbarSize && (
                  <Button
                    type="link"
                    size="small"
                    onClick={() => setExpanded(!expanded)}
                    style={{ whiteSpace: 'nowrap', alignSelf: 'center' }}
                  >
                    {expanded ? '收起' : `展开更多 (${items.length - searbarSize})`}
                  </Button>
                )}
              </div></>
        ),
        actions: [
          <Button
          key="search"
          type="primary"
          onClick={() => {
          
            setParams(params);
            actionRef.current?.reload(); // 触发 reload 即重新执行 request
          }}
        >
          搜索
        </Button>,
        
        <Button
          key="search"
          type="default"
          style={{display:table?'none':''}}
          onClick={() => {
            setAddView(true)
            setContract({})
            setContractkey(contractkey+1)
          }}
        >
          新建
        </Button>,
        
        
          <Button
            key="primary"
            type="default"
            onClick={() => {
        
              var par = {...params}
              par.current=1
              par.pageSize=10000
              par.download=1
              debtlist(par).then((res:any)=>{
    
                if (res.errorMessage) {
                  Modal.error({title: res.errorMessage})
                } else {
                  var result = res.data.map((row:any,rowIndex:any)=>{
                  var arr:any = []
                  columns.forEach((h:any,index:number)=>{
                    
                    var temp:string = (row[h.dataIndex]||'').toString()
                    if (temp) {
                      temp = temp.replaceAll(',','，').trim()
                    }
                    switch (h.dataIndex) {
                      case 'index':
                        arr.push(rowIndex +1)
                        break
                      case 'action':
                       
                        break
                      case 'signdate':
                        var overdueDate = moment().subtract(row.age, 'days');
                        arr.push(
                          "签订："+(row.signdate?moment(row.signdate).format('YYYY-MM-DD'):'')+"\n开票："+(row.invoicedate?moment(row.invoicedate).format('YYYY-MM-DD'):'')+"\n逾期："+(overdueDate?overdueDate.format('YYYY-MM-DD'):'')
                        )
                        break
                      case 'urgeamount':
               
                        arr.push(
                          "合同："+row.amount+"\n开票："+(row.invoiceamount||0)+"\n回款："+row.paycollection
                        )
                        break
                      case 'partaname':
               
                        arr.push(
                          "名称："+row.partaname+"\n地址："+(row.address||'')+"\n联系人："+(row.contactor||'')+"\n电话："+(row.mobile||'')
                        )
                        break
                      case 'title':
                        arr.push(
                          row.title+"\n"+row.serial
                        )
                        break

                      default:
                        arr.push(temp)
                        break;
                    }
                    
                    
                  })

                  return arr
                })
                  var x = columns.map((t:any)=>t.title).filter((t:any)=>t!='操作')
                  result.unshift(x)
                  downloadAsXlSX(result,'欠款导出')
                }
              })
            }}
          >
            导出
          </Button>,

        ],
      }}
    />

      <TableScrollSync tableId="debtlist" onScroll={(scroll:any)=>{
        const tableContent = document.querySelector('#debtlist .ant-table-content');
        if (tableContent){
          tableContent.scrollLeft = scroll;
        }
        
       
      
      }} />
    
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
    <UrgeView key={contractkey} visible={showUrgeView} contractid={contract.id} debturgeid={contract.debturgeid} thirdNo={contract.thirdNo} urgeserial={contract.urgeserial} onVisibleChange={(visible:any)=>setShowUrgeView(visible)}/>
    <UrgelogsModal key={'uc'+contract.id} visible={showUrgelogs} type={1}  contractid={contract.id} onVisibleChange={(visible:any)=>setShowUrgelogs(visible)}
                  />
    <AddDebtUrge key={'addurge'+contractkey} data={contract} visible={addView}  onVisibleChange={(visible:any)=>setAddView(visible)} onSuccess={()=>{
       actionRef.current?.reload(true)
    }}/>
    <AddDealResult key={'adddeal'+contract.id} data={contract} visible={dealView}  onVisibleChange={(visible:any)=>setDealView(visible)} onSuccess={()=>{
       actionRef.current?.reload(true)
       console.log('adddeal:',contract)
       if (searchtype === '无合同'){
            setContract({debturgeid:contract.debturgeid})
          }else{
            setContract({id:contract.id||contract.contractid})
          }
          setContractkey(contractkey+1)
          setShowUrgeView(true)
    }}/>
    <Add key={'c'+company.id}  visible={visible} id={company.id||company.value} company={company.company}   onVisibleChange={setVisible}></Add>
    <Modal
              title={
                <>
                <span>回款纪录</span>
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
              <PayCollection key={'viewpay'+contract.id} contractids={contract.id} params={payParams}/>
            </Modal>

  </>
  )
}
export default Debtlist;