import ContractSelect from '@/pages/finance/contract/contract-select';
import { getbyids, getbykeyword, getcontract, savecontract } from '@/pages/finance/contract/service';
import type { InputRef } from 'antd';
import { Button, Descriptions, Form, Input, InputNumber, Modal, Popconfirm, Popover, Row, Table } from 'antd';
import type { FormInstance } from 'antd/es/form';
import moment from 'moment';
import React, { useContext, useEffect, useRef, useState } from 'react';
import { AGENTID, BalanceTypes } from '../../config';
import { delbalance, getbalancefileurls, getbalancelist, getinvoicecheck, getinvoicelist, getprojectbyid, savebalance, saveproject, startpayment } from '../service';
import { getpowers } from '@/pages/finance/role/service';
import { useModel } from 'umi';
import AddInvoice from '../../invoice/addinvoice';
import Addfinance from '../addfinance';
import Addbalance from '../addbalance';
import Filescard from '@/pages/finance/contract/filescard';
import { ColumnsType } from 'antd/lib/table';

import Enteraccount from '../enteraccount';
import PayCollection from '@/pages/finance/contract/paycollection';

const EditableContext = React.createContext<FormInstance<any> | null>(null);

interface Item {
  key: string;
  name: string;
  age: string;
  address: string;
}

interface EditableRowProps {
  index: number;
}

const EditableRow: React.FC<EditableRowProps> = ({ index, ...props }) => {
  const [form] = Form.useForm();
  return (
    <Form form={form} component={false}>
      <EditableContext.Provider value={form}>
        <tr {...props} />
      </EditableContext.Provider>
    </Form>
  );
};

interface EditableCellProps {
  title: React.ReactNode;
  editable: boolean;
  children: React.ReactNode;
  dataIndex: keyof Item;
  record: Item;
  handleSave: (record: Item) => void;
}

const EditableCell: React.FC<EditableCellProps> = ({
  title,
  editable,
  children,
  dataIndex,
  record,
  handleSave,
  ...restProps
}) => {
  const [editing, setEditing] = useState(false);
  const inputRef = useRef<InputRef>(null);
  const form = useContext(EditableContext)!;

  useEffect(() => {
    if (editing) {
      inputRef.current!.focus();
    }
  }, [editing]);

  const toggleEdit = () => {
    setEditing(!editing);
    form.setFieldsValue({ [dataIndex]: record[dataIndex] });
  };

  const save = async () => {
    try {
      const values = await form.validateFields();

      toggleEdit();
      handleSave({ ...record, ...values });
    } catch (errInfo) {
      console.log('Save failed:', errInfo);
    }
  };

  let childNode = children;

  if (editable) {
    childNode = editing ? (
      <Form.Item
        style={{ margin: 0 }}
        name={dataIndex}
        rules={[
          {
            required: true,
            message: `${title} is required.`,
          },
        ]}
      >
        <Input ref={inputRef} onPressEnter={save} onBlur={save} />
      </Form.Item>
    ) : (
      <div className="editable-cell-value-wrap" style={{ paddingRight: 24 }} onClick={toggleEdit}>
        {children}
      </div>
    );
  }

  return <td {...restProps}>{childNode}</td>;
};

type EditableTableProps = Parameters<typeof Table>[0];

interface DataType {
  key: React.Key;
  name: string;
  age: string;
  address: string;
}

type ColumnTypes = Exclude<EditableTableProps['columns'], undefined>;

const Incomelist: React.FC<{pid:any,balancetype:any,onChange?:Function,onPayCheck?:Function,onlyBalance?:boolean}> = ({onlyBalance=false,pid,onChange,balancetype,onPayCheck}) => {
  const [datas,setDatas]=useState<any>([]);
  const [dataSource, setDataSource] = useState<any[]>([]);
  const [amount,setAmount]= useState(0);
  const [loading,setLoading]=useState(false);
  const [contractids,setContractids]=useState('')
  const [powers,setPowers]=useState<any>([])
  const { initialState } = useModel<any>('@@initialState');
  const { currentUser } = initialState;
  const [invoiceModal,setInvoiceModal]=useState(false);
  const [invoice,setInvoice]=useState<any>({})
  const [project, setProject]=useState<any>({})
  const [invoicelist,setInvoicelist]=useState<any>([])
  const [selectedContracts,setSelectedContracts]=useState<any>([])
  const [financeModal,setFinanceModal]=useState(false);
  const [balance,setBalance]=useState<any>({})
  const [bfinal,setBfinal] = useState(false)
  const [balanceModal,setBalanceModal]=useState(false);
  const [balancelist,setBalancelist]=useState<any>([])
  const [finalb,setFinalb]=useState<any>([])
  const tempfinalRef = useRef<HTMLInputElement>(null)
  const finalnoteRef = useRef<HTMLInputElement>(null)
  
  const [addCModal,setAddCModal]=useState(false)
  const [modal2, setModal2] = useState(false)
  const [urls, setUrls] = useState('')
  const [modal,setModal]=useState(false)
  var [refresh,setRefresh]=useState(0)
  const [enterModal,setEnterModal]=useState(false)
  const [paycontractid,setPaycontractid]=useState('')
  const _typename = balancetype==BalanceTypes.INCOME?'收入':'支出'
  useEffect(()=>{
    if (pid){
      getprojectbyid({id:pid}).then((res1:any)=>{
        if (res1.errorMessage){
          Modal.error({title:res1.errorMessage})
        }else{
          setProject(res1.data)
          var temp = balancetype==BalanceTypes.INCOME?res1.data.contractids:''
          getContracts(temp)
        }
      })
      getpowers({agentid:AGENTID}).then((res:any)=>{
        if (res.errorMessage){
          Modal.error({title:res.errorMessage})
          return
        }
        res.data = res.data||''
        res.data.split && setPowers(res.data.split(','))
        console.log('powers:',res.data.split(','))
  
      })
      // getInvoices(pid,0)
      getBalances(pid)
    }

  },[pid+balancetype])
  const getamount=(items:any)=>{
    var temp = 0;
    (items||[]).forEach((e:any)=>{
      temp += e.amount
    })
    setAmount(temp)
  }
  const getContracts = (contractids:any)=>{
    setContractids(contractids)
  
    if (contractids){
      getbyids({ids:contractids}).then((res:any)=>{
        if (res&&res.length>0){
          setDatas(res)
          setDataSource(res)
          var temp = 0
          res.forEach((e:any)=>{
            temp += e.amount
          })
          setAmount(temp)
          getamount(res)
        }
        
      })
    }else{
      setDatas([])
      setDataSource([])
      setAmount(0)
      getamount(0)
    }
  }
  const handleDelete = (id:any) => {
    var temp = dataSource.filter((e:any)=>e.id!=id)
    var ids = temp.map(e=>e.id).join(',')
    save(ids,temp)
  };

  const getInvoices = (projectid:any,contractid:any)=>{
    if (!contractid) {
      setInvoicelist([])
    }else{
      getinvoicelist({projectid,contractid,pageSize:50,type:balancetype}).then((res:any)=>{
        setInvoicelist(res.data||[])
      })
    }
    
  }
  const getBalances = (projectid:any)=>{
    if (!projectid) return
    getbalancelist({pageSize:50,projectid,type:balancetype,orderby:'id asc'}).then((res:any)=>{

      var a = res.data||[]
      if (balancetype==BalanceTypes.EXPEND){
        a.push(res.tax||{title: "税费", budget: 0, final: 0})
        a.push(res.performance||{title: "执行绩效奖励", budget: 0, final: 0})
      }
      
      
      setBalancelist(a)
      setFinalb(a.filter((e:any)=>e.final!=0||["税费","执行绩效奖励"].includes(e.title)))
    })
  }
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
  const onMenuClick = (action:String,record:any) => {
    
    switch (action) {
      
        case '删除':
        Modal.confirm({
          title: '确定要删除吗？',
          okText: '确认',
          cancelText: '取消',
          onOk: () => {
            console.log('删除合同')
            handleDelete(record.id)
          },
          
        });
        break
        case '开票':
          setInvoiceModal(true)
          setInvoice({projectid:project.id,contractid:record.id,type:balancetype})
          break;
        case '已开发票':
          getInvoices(project.id,record.id)
          break
        case '财务信息':
          // 查询财务信息
          
          getinvoicecheck({invoiceno:record.EIid}).then((res:any)=>{
            if (res.data){
              setInvoice(res.data)
            }else{
              setInvoice({projectid:project.id,amount:record.TotalTaxIncludedAmount,invoiceno:record.EIid,date:record.RequestTime})
            }
            setFinanceModal(true)
          })
          
          break;
        case '发票详情':
          setInvoice(record)
          setModal(true)
          break
        case '更新发票':
          setInvoiceModal(true)
          setInvoice(record)
          break;
        case '更新收入':
          setBfinal(false)
          setBalance(record)
          setBalanceModal(true)
          
          break
        case '收入决算':
          setBfinal(true)
          setBalance(record)
          setBalanceModal(true)
          break
        case '删除收入':
          Modal.confirm({
            title: '确定要删除吗？',
            okText: '确认',
            cancelText: '取消',
            onOk: () => {
              delbalance({id:record.id,projectid:project.id}).then(res=>{
                if (res.errorMessage) {
                  Modal.error({
                    title:  res.errorMessage,
                  });
                } else {
                  getBalances(project.id)
                }
              })
            },
            
          });
          break
        case '删除决算':
          savebalance({projectid:record.projectid,id:record.id,final:0}).then((res:any)=>{
            if (res.errorMessage) {
              Modal.error({
                title:  res.errorMessage,
              });
            } else {
              Modal.success({
                title: '操作成功',
              })
              getBalances(project.id)
            }
          })
          break
        case '收入决算':
          
          savebalance({projectid:record.projectid,id:record.id,final:tempfinalRef.current?.value,finalnote:finalnoteRef.current?.value}).then((res:any)=>{
            if (res.errorMessage) {
              Modal.error({
                title:  res.errorMessage,
              });
            } else {
              Modal.success({
                title: '操作成功',
              })
              getBalances(project.id)
            }
          })
          break
        case '预算附件':
          setUrls(record.budgetfileurls)        
          setModal2(true)  
          break
        case '决算附件':
          setUrls(record.finalfileurls)        
          setModal2(true)  
          break
        case '合同附件':
          setUrls(record.fileurls)        
          setModal2(true)
          break
        case '支出关联合同':
          setAddCModal(true)
          setBalance(record)
          break
        case '入账':
          setEnterModal(true)
          setRefresh(++refresh)
          record.projectid=record.projectid||pid
          record.type = record.type||balancetype
          setBalance(record)
          break
        case '发起付款审批':
          var par:any = {}
          if (record.id>0){
            par.id  =record.id
            par.projectid = pid||record.projectid
          }else{
            par = {
              title:record.title,
              finalnote:record.title,
              final:record.final||record.budget,
              projectid: pid||record.projectid,
              type: BalanceTypes.EXPEND
            }
          }
          if (!par.projectid){
            Modal.error({title:'projectid 不能为空'})
            return
          }

          startpayment(par).then((res:any)=>{
            if (res.errorMessage){
              Modal.error({title:res.errorMessage})
            }else{
              Modal.success({title:res.data})
            }
          })
          break
      default:
        break;
    }
  };
  
  const defaultColumns: (ColumnTypes[number] & { editable?: boolean; dataIndex: string })[] = [
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
      width: 100
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
      key:'amount'
    },
    {
      title: '签订日期',
      dataIndex: 'signdate',
      key: 'signdate',
      width: 120,
      render: (_:any, record:any) => {
        return record.signdate?moment(record.signdate).format('YYYY-MM-DD'):''
      },
    },
    {
      title: '操作',
      dataIndex: 'operation',
      width:100,
      fixed:'right',
      onHeaderCell:()=>({
        style:{
          right:'-5px!important'
        }
      }),
      render: (_:any, record:any,index:Number) => (
        <>
            <Popover
              placement="topLeft"
              trigger={'click'}
              content={(<>
                <Button type="text" onClick={()=>{onMenuClick('已开发票',record)}}>发票</Button>
      
                {/* {
                  powers.includes('财务') && <Button type="text" onClick={()=>{onMenuClick('财务',record)}}>财务</Button>
                } */}
                {
                  (powers.includes('财务') || project.creator==currentUser.wxuserid) && <Button type="text" onClick={()=>{onMenuClick('删除',record)}}>删除</Button>
                }
                
                <Button type="text" onClick={()=>{onMenuClick('合同附件',record)}}>附件</Button>
                

                </>)
              }
          
            
            >
                <Button>操作</Button>
            </Popover>

          
          
        </>
      ),

    },
  ];
  const invoiceColumns: (ColumnTypes[number] & { editable?: boolean; dataIndex: string })[] = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      width: 65,
      render:(_:any,record:any,index:number)=>`${index+1}`
    },
    {
      title:'发票号',
      dataIndex:'EIid',
      key:'EIid',
    },
    {
      title:'开票金额',
      dataIndex:'TotalTaxIncludedAmount',
      key:'TotalTaxIncludedAmount'
    },
    {
      title:'开票日期',
      dataIndex:'RequestTime',
      key:'RequestTime',
      render:(text:any)=>{
        return text&&text.substring?text.substring(0,10):''
      }
    },
    {
      title: '操作',
      fixed:'right',
      dataIndex: 'operation',
      width:100,
      onHeaderCell:()=>({
        style:{
          right:'-5px!important'
        }
      }),
      render: (_:any, record:any,index:Number) => (
        <>
            <Popover
              placement="topLeft"
              trigger={'click'}
              content={(<>
                <Button type="text" onClick={()=>{onMenuClick('发票详情',record)}}>详情</Button>
                <br></br>
                <Button type="text" onClick={()=>{onMenuClick('财务信息',record)}}>财务</Button>
                
                

                </>)
              }
          
            
            >
                <Button>操作</Button>
            </Popover>

          
          
        </>
      ),

    },
  ];
 
  const balanceColumns:ColumnsType<any> = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      width: 65,
      render:(_:any,record:any,index:number)=>`${index+1}`
    },
    {
      title:''+_typename+'项目',
      dataIndex:'title',
      key:'title',
      width:150,
      render:(text:any,record:any)=>{
                        
        return (
          <>
          <span style={{color:'#1890ff'}} onClick={()=>{
                getfileurls(record)
            }}>
            {text}
          </span>
            
          </>
        )
      }
    },
    {
      title:''+_typename+'类别',
      dataIndex:'moneytypename',
      key:'moneytypename',
      width:150
    },
    {
      title:''+_typename+'金额',
      dataIndex:'budget',
      key:'budget',
      render: (text:any,record:any)=>{
        if (text=='-') text=0
        return onlyBalance?<span>{text}</span>:<span style={{color:'#1890ff'}} onClick={()=>{
          if (record.id){
            setBalance(record)
            setBfinal(false)
            setBalanceModal(true)
          }
      }}>{parseFloat(text).toLocaleString('en-US', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })}</span>
      }
  
    },
    {
      title:'税率',
      dataIndex:'tax',
      key:'tax',
      width:65
    },
    {
      title: '操作',
      fixed:'right',
      dataIndex: 'operation',
      width:100,
      onHeaderCell:()=>({
        style:{
          right:'-5px!important'
        }
      }),
      render: (_:any, record:any,index:Number) => (
        <>
            {
              onlyBalance && 
              <Button type="text" onClick={()=>{onMenuClick('入账',record)}}>入账</Button>
            }
            {
              !onlyBalance && record.id>0 &&
              <Popover
                  placement="topLeft"
                  trigger={'click'}
                  content={(<>
                    <div>
                        <Button type="text" onClick={()=>{onMenuClick('更新收入',record)}}>更新</Button>
                        <Button type="text" onClick={()=>{onMenuClick('删除收入',record)}}>删除</Button>
                        <br></br>
                        <Button type="text" onClick={()=>{onMenuClick('收入决算',record)}}>决算</Button>
                        <Button type="text" onClick={()=>{onMenuClick('预算附件',record)}}>附件</Button>
                        <br></br>
                        {
                          _typename=='支出' &&
                          <Button type="text" onClick={()=>{onMenuClick('发起付款审批',record)}}>发起付款审批</Button>
                        }
                        
                        
                      </div>
                    
                    </>)
                  }
              
                
                >
                    <Button>操作</Button>
                </Popover>
            }
            {
              !onlyBalance && record.title=='执行绩效奖励' &&
              <Button type="text" onClick={()=>{onMenuClick('发起付款审批',record)}}>付款审批</Button>
            }
            

          
          
        </>
      ),

    },
  ];
  const fbalanceColumns:(ColumnTypes[number] & { editable?: boolean; dataIndex: string })[] = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      width: 65,
      render:(_:any,record:any,index:number)=>`${index+1}`
    },
    {
      title:''+_typename+'项目',
      dataIndex:'title',
      key:'title',
      width:150,
      render:(text:any,record:any)=>{
                        
        return (
          <span style={{color:'#1890ff'}} onClick={()=>{
                getfileurls(record)
            }}>
            {text}
          </span>
        )
      }
    },
    {
      title:''+_typename+'类别',
      dataIndex:'moneytypename',
      key:'moneytypename',
      width:150
    },
    {
      title:''+_typename+'金额',
      dataIndex:'final',
      key:'final',
      render: (text:any,record:any)=>{
        if (text=='-') text=0
        return onlyBalance?<span>{text}</span>:<span style={{color:'#1890ff'}} onClick={()=>{
          if (record.id){
            setBalance(record)
            setBfinal(true)
            setBalanceModal(true)
          }
      }}>{parseFloat(text).toLocaleString('en-US', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        })}</span>
      }
    },
    {
      title:'税率',
      dataIndex:'finaltax',
      key:'finaltax',
      width:65
    },

    {
      title: '操作',
      fixed:'right',
      dataIndex: 'operation',
      width:100,
      onHeaderCell:()=>({
        style:{
          right:'-5px!important'
        }
      }),
      render: (_:any, record:any,index:Number) => (
        <>
        {
            onlyBalance &&
            <Button type="text" onClick={()=>{onMenuClick('入账',record)}}>入账</Button>
          }
         {
          !onlyBalance && record.id>0 &&
            <Popover
              placement="topLeft"
              trigger={'click'}
              content={(<>
              
                <Button type="text" onClick={()=>{onMenuClick('收入决算',record)}}>更新</Button>
                <Button type="text" onClick={()=>{onMenuClick('删除决算',record)}}>删除</Button>
                <Button type="text" onClick={()=>{onMenuClick('决算附件',record)}}>附件</Button>
                </>)
              }
          
            
            >
                <Button>操作</Button>
            </Popover>
         }
        

          
          
        </>
      ),

    },
  ];
  

  
  const handleSave = (row: DataType) => {
    const newData = [...dataSource];
    const index = newData.findIndex(item => row.key === item.key);
    const item = newData[index];
    newData.splice(index, 1, {
      ...item,
      ...row,
    });
    setDataSource(newData);
  };

  const components = {
    body: {
      row: EditableRow,
      cell: EditableCell,
    },
  };

  const columns = defaultColumns.map(col => {
    if (!col.editable) {
      return col;
    }
    return {
      ...col,
      onCell: (record: DataType) => ({
        record,
        editable: col.editable,
        dataIndex: col.dataIndex,
        title: col.title,
        handleSave,
      }),
    };
  });
  const onContractChange = (e:any)=>{
    setAddCModal(false)
    if (e && e.length>0){
      e=e.filter((item:any)=>{ // 只留下不存在的
        return datas.findIndex((d:any)=>d.id==item.id)<0
      })
      var temp = [...e,...datas]
      var ids= temp.map((e:any)=>e.id).join(',')
      save(ids,temp)
    }

  }
  const save = (ids:any,nowDataSource:any)=>{
    if (loading) return
    setLoading(true)
   
    if(pid){
      var par:any = {id:pid}
        if (balancetype==BalanceTypes.EXPEND){
          par.expendcontractids = ids
        }else{
          par.contractids = ids
        }
        saveproject(par).then((res:any)=>{
          setLoading(false)
          if (res.errorMessage){
            Modal.error({title:res.errorMessage})
          }else{
            Modal.info({title:'成功'})
            setDatas(nowDataSource)
            getamount(nowDataSource)
            setDataSource(nowDataSource)
          }
        })
    } else {
      alert('pid 为空')
    }
  }
  const onAddInvoice = (e:any)=>{
    setInvoiceModal(false)
    getInvoices(project.id,selectedContracts.map((e:any)=>e.id).join(','))
  }
  const onAddFinance=()=>{
    setFinanceModal(false)
    getInvoices(project.id,selectedContracts.map((e:any)=>e.id).join(','))
  }
  const onAddbalance = ()=>{
    setBalanceModal(false)
    getBalances(project.id)
  
  }
  const rowSelection = {
    onChange: (selectedRowKeys: React.Key[], selectedRows: any[]) => {
      setSelectedContracts(selectedRows)
      if (selectedRows.length==1){
        setPaycontractid(selectedRows.map(e=>e.id).join(','))
      }else{
        setPaycontractid('')
      }
      getInvoices(project.id,selectedRows.map(e=>e.id).join(','))
    },
    getCheckboxProps: (record: any) => ({
      // disabled: record.index === 'Disabled User', // Column configuration not to be checked
      id: record.index,
    }),
  };
  const balanceRowSelection = {
    onChange: (selectedRowKeys: React.Key[], selectedRows: any[]) => {

      getContracts(selectedRows.map((e:any)=>e.contractids).join(','))
    },
    getCheckboxProps: (record: any) => ({
      // disabled: record.index === 'Disabled User', // Column configuration not to be checked
      id: record.index,
    }),
  };

  return (
    <div>

      {
        _typename=='收入'&&!onlyBalance&&
        <Table
        title={()=>{
          return <div style={{width:'100%',display:'flex',alignItems:'center'}}>
        

        <div >合同总金额：{amount}</div>
        <div style={{paddingLeft:'10px'}}>合同数量： {dataSource.length}</div>
        {
          invoicelist&&invoicelist.length>0&&
          <div style={{paddingLeft:'10px',fontWeight:'bold',color:'#1890ff'}}>开票金额： {invoicelist.reduce((acc:any, invoice:any) => acc + parseFloat((invoice.amount || 0)), 0)}</div>
        }
        {
           (powers.includes('财务管理') || project.creator==currentUser.wxuserid) &&
           <div style={{marginLeft:'auto'}}>
            <Button style={{marginLeft:'auto'}} onClick={()=>{
              setRefresh(++refresh)
              setAddCModal(true)

              }} type="primary">关联合同</Button>
           </div>
        }
        
          </div>
        }}
        rowKey={(record:any) => record.id}
        rowSelection={{
          type: 'checkbox',
          ...rowSelection,
        }}
        components={components}
        rowClassName={() => 'editable-row'}
        bordered
        dataSource={dataSource}
        columns={columns}
        pagination={false}
        locale={{emptyText:'未签订合同'}}
      />
      }
      {
        _typename=='收入'&&!onlyBalance &&invoicelist && invoicelist.length>0 && 
        <Table
        title={()=>{
          return <div style={{width:'100%',display:'flex',alignItems:'center'}}>{'发票 '+' '+selectedContracts.map((e:any)=>"《"+e.title+"》").join('、')}</div>
        }}
        rowKey={(record:any) => record.id}
        components={components}
        rowClassName={() => 'editable-row'}
        bordered
        dataSource={invoicelist}
        columns={invoiceColumns}
        locale={{emptyText:'暂无发票'}}
        pagination={false}
      />
      }
      {
        _typename=='收入'&& !onlyBalance&&paycontractid && 
        <Descriptions  bordered column={1} contentStyle={{padding:'25px'}} labelStyle={{width:110}}>
          <Descriptions.Item label="履约条件" >
            <PayCollection key={paycontractid} contractid={paycontractid} editable={false} financechek={true} onPayCheck={onPayCheck}/>
         </Descriptions.Item>
        </Descriptions>
        
      }
      
      <Table
        title={()=>{
          return <div style={{width:'100%',display:'flex',alignItems:'center'}}>
            <div>{_typename}列表</div>
            {
              !onlyBalance &&
              <Button style={{marginLeft:'auto'}} onClick={()=>{

                setBalanceModal(true)
                setBfinal(false)
                setBalance({projectid:project.id,type:balancetype})
                setRefresh(++refresh)
              }} type="primary">新增{_typename}</Button>
            }
            
          </div>
        }}
        rowKey={(record:any) => record.id}

        expandable={{
          expandedRowRender: (record:any) => <p style={{ margin: 0 }}>{record.specialinvoice?record.budgetnote+"(专票："+record.tax+"%)":record.budgetnote}</p>,
          rowExpandable: (record:any) => record.budgetnote && record.budgetnote.length>0,
          defaultExpandAllRows:true,
          showExpandColumn:true,
        }}
        
        components={components}
        rowClassName={() => 'editable-row'}
        bordered
        dataSource={balancelist}
        columns={balanceColumns}
        pagination={false}
      />
      {
        finalb&&finalb.length>0&&
        <Table
        title={()=>{
          return <div style={{width:'100%',display:'flex',alignItems:'center'}}>
            <div>决算列表</div>
            {
              !onlyBalance &&
              <Button style={{marginLeft:'auto'}} onClick={()=>{

                setBalanceModal(true)
                setBfinal(true)
                setBalance({projectid:project.id,type:balancetype,budget:0})
                setRefresh(++refresh)
              }} type="primary">新增{_typename}</Button>
            }
          </div>
        }}
        rowKey={(record:any) => record.id}
    
        components={components}
        expandable={{
          expandedRowRender: (record:any) => <p style={{ margin: 0 }}>{record.finalspecialinvoice?record.finalnote+"(专票："+record.finaltax+"%)":record.finalnote}</p>,
          rowExpandable: (record:any) => record.finalnote && record.finalnote.length>0,
          defaultExpandAllRows:true,
          showExpandColumn:true,
        }}
        rowClassName={() => 'editable-row'}
        bordered
        dataSource={finalb}
        columns={fbalanceColumns}
        pagination={false}
      />
      }
      {
        _typename=='支出'&&!onlyBalance && dataSource.length>0 &&
        <Table
          title={()=>{
            return <div style={{width:'100%',display:'flex',alignItems:'center'}}>
          

          <div >合同总金额：{amount}</div>
          <div style={{paddingLeft:'10px'}}>合同数量： {dataSource.length}</div>
          {
            invoicelist&&invoicelist.length>0&&
            <div style={{paddingLeft:'10px',fontWeight:'bold',color:'#1890ff'}}>开票金额： {invoicelist.reduce((acc:any, invoice:any) => acc + parseFloat((invoice.amount || 0)), 0)}</div>
          }

            </div>
          }}
          rowKey={(record:any) => record.id}
          rowSelection={{
            type: 'checkbox',
            ...rowSelection,
          }}
          components={components}
          rowClassName={() => 'editable-row'}
          bordered
          dataSource={dataSource}
          columns={columns}
          pagination={false}
          locale={{emptyText:'未签订合同'}}
        />
      }
      {
        _typename=='支出'&&!onlyBalance &&invoicelist && invoicelist.length>0 && 
        <Table
        title={()=>{
          return <div style={{width:'100%',display:'flex',alignItems:'center'}}>{'发票 '+' '+selectedContracts.map((e:any)=>"《"+e.title+"》").join('、')}</div>
        }}
        rowKey={(record:any) => record.id}
        components={components}
        rowClassName={() => 'editable-row'}
        bordered
        dataSource={invoicelist}
        columns={invoiceColumns}
        locale={{emptyText:'暂无发票'}}
        pagination={false}
      />
      }
      <Modal
        title={balancetype==BalanceTypes.INCOME?'收入发票':'支出发票'}
        style={{ top: 20 }}
        width={600}

        visible={invoiceModal}
        onOk={() => setInvoiceModal(false)}
        onCancel={() => setInvoiceModal(false)}

        footer={null}
        >
        <AddInvoice key={invoice.contractid}  data={invoice} onChange={onAddInvoice}/>
      </Modal>
      <Modal
        title={'关联合同'}
        style={{ top: 20 }}
        width={600}
        visible={addCModal}
        onOk={() => setAddCModal(false)}
        onCancel={() => setAddCModal(false)}
        
        footer={null}
        >
        <ContractSelect key={refresh} multiple={true} showupload={false} type={balancetype}  onChange={onContractChange} />
      </Modal>
      <Modal
        title={_typename}
        style={{ top: 20 }}
        width={600}
        key={refresh}
        visible={balanceModal}
        onOk={() => setBalanceModal(false)}
        onCancel={() => setBalanceModal(false)}
        
        footer={null}
        >
        <Addbalance key={refresh} data={balance} isFinal={bfinal} onChange={onAddbalance}/>
      </Modal>
      <Modal
        title={'入账'}
        style={{ top: 20 }}
        width={600}

        visible={enterModal}
        onOk={() => setEnterModal(false)}
        onCancel={() => setEnterModal(false)}
        
        footer={null}
        >
        <Enteraccount key={refresh} bid={balance.id} type={balance.type} projectid={balance.projectid}/>
      </Modal>

      <Modal
        title="财务信息"
        style={{ top: 20 }}
        width={600}

        visible={financeModal}
        onOk={() => setFinanceModal(false)}
        onCancel={() => setFinanceModal(false)}

        footer={null}
        >
        <Addfinance key={invoice.invoiceno} data={invoice} onChange={onAddFinance} />
      </Modal>
      <Modal
        title={_typename}
        style={{ top: 20 }}
        width={600}

        visible={balanceModal}
        onOk={() => setBalanceModal(false)}
        onCancel={() => setBalanceModal(false)}
        
        footer={null}
        >
        <Addbalance key={''+balance.id+''+bfinal} data={balance} isFinal={bfinal} onChange={onAddbalance}/>
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
    title="发票详情"
    key={invoice.id}
    style={{ top: 20, }}
    visible={modal}
    onOk={() => {
      setModal(false)
    }}
    onCancel={() => setModal(false)}
    footer={null}
    >
    <Descriptions
        bordered
 
        size={'default'}
        column={1}
        labelStyle={{width:150}}
      >
        <Descriptions.Item label="发票号码">{invoice?.EIid}</Descriptions.Item>
        <Descriptions.Item label="发票类别">{invoice.GeneralOrSpecialVAT}</Descriptions.Item>
        <Descriptions.Item label="开票日期">{invoice.RequestTime}</Descriptions.Item>
        <Descriptions.Item label="销售方识别号">{invoice.SellerIdNum}</Descriptions.Item>
        <Descriptions.Item label="销售方名称">{invoice?.SellerName}</Descriptions.Item>
        <Descriptions.Item label="购买方识别号">{invoice.BuyerIdNum}</Descriptions.Item>
        <Descriptions.Item label="购买方名称">{invoice.BuyerName}</Descriptions.Item>
        <Descriptions.Item label="不含税开票金额">{invoice?.TotalAmwithoutTax}</Descriptions.Item>
        <Descriptions.Item label="含税开票金额">{invoice.TotalTaxIncludedAmount}</Descriptions.Item>
        <Descriptions.Item label="税额">{invoice.TotalTaxAm}</Descriptions.Item>
        <Descriptions.Item label="备注">{invoice.Remark}</Descriptions.Item>
 
      </Descriptions>
    </Modal>
    </div>
  );
};

export default Incomelist;