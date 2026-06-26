import type { ActionType, ProFormInstance } from '@ant-design/pro-components';
import {

  ProTable,
} from '@ant-design/pro-components';
import { useRef, useState } from 'react';
import { cancelsyncinvoice, delpushinvoice, getinvoicelist, pushInvoiceToAdvertisingSystem, syncinvoice } from './service';
import { Button, DatePicker, Descriptions, Input, Modal, Popover, Select, Tag } from 'antd';
import Filescard from '../contract/filescard';
import ViewModal from './viewModal';
import CommonDownloadModal from '../common/CommonDownloadModal';
import Dictselect from '../budget/dict/dictselect';
import { render } from 'react-dom';
import BusinesstypeTree from './Businesstype_Tree';
import Companyselect from '../company/companyselect';
import PayCollection from './paycollection';
import TableScrollSync from '../common/TableScrollSync';








const Invoicelist:React.FC<{}> = ({}) => {
  const proTableFormRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  const [hmodal,setHmodal] = useState(false)
  var [params,setParams] = useState<any>({})
  const [selectedRows, setSelectedRows]=useState<any>([])
  const [ids,setIds]=useState<any>('')
  const [modal,setModal]=useState<any>(false)
  const [view,setView]=useState(false)
  const [selected,setSelected]=useState<any>({})
  const { RangePicker } = DatePicker;
  const [pmodal,setPmodal]=useState(false)
  const [rkey,setRkey]=useState(0)
  const onMenuClick = (action:String,record:any) => {
    
    switch (action) {
      
        case '开票信息':
          setView(true)
          setSelected(record)
          break

        case '详情':
          setSelected(record)
          setModal(true)
          break
        

      default:
        break;
    }
  };


  const columns: any = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      search:false,
      width: 50,
      render:(_:any,record:any,index:number)=>`${index+1}`
    },
    {
      title: 'ID',
      dataIndex: 'id',
    },
    {
      title: '发票号',
      dataIndex: 'EIid',
      width: 50,
      render:(_:any,record:any)=>{
        return <a onClick={()=>{onMenuClick('开票信息',record)}} style={{color:'#1890FF'}}>{record.EIid}</a>
      }
    },
    {
      title: '媒体',
      dataIndex: 'publication',
    },
    {
      title: '业务类型',
      dataIndex: 'businesstype',
    },
    {
      title: '发票类别',
      dataIndex: 'GeneralOrSpecialVAT',
      width: 150
    },
    {
      title: '开票日期',
      dataIndex: 'RequestTime',
    },

    {
      title: '销售方名称',
      dataIndex: 'SellerName',
      width: 150
    },

    {
      title: '购买方名称',
      dataIndex: 'BuyerName',
      width: 150
    },
    {
      title: '发票抬头',
      dataIndex: 'receiver',
      width: 150
    },
    {
      title: '不含税开票金额',
      dataIndex: 'TotalAmwithoutTax',
    },
    {
      title: '含税开票金额',
      dataIndex: 'TotalTaxIncludedAmount',
    },{
      title: '税额',
      dataIndex: 'TotalTaxAm',
    },{
      title: '经办',
      dataIndex: 'name',
    },
    ,{
      title: '广告系统',
      dataIndex: 'pushed',
      render: (text:any, record:any) => {
        return record.pushed?<Tag color="green">已推</Tag>:'未推'
      }
    },
    ,{
      title: '金蝶系统',
      dataIndex: 'isNew',
      render: (text:any, record:any) => {
        return !record.isNew?<Tag >已同步</Tag>:'未同步'
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
                <Button type="text" onClick={()=>{onMenuClick('详情',record)}}>详情</Button>
                <Button type="text" onClick={()=>{onMenuClick('开票信息',record)}}>开票信息</Button>
                <Button type="text" onClick={()=>{
                  setSelected(record)
                  setPmodal(true)
                  setRkey(rkey+1)
                }}>回款纪录</Button>
                {
                  record.pushed==1&&
                  <Button type="text" onClick={()=>{
            

                  Modal.confirm({
                    title: '确定要撤销吗？',
                    content: '撤销推送后，发票将删除广告管理系统对应的收款和发票',
                    onOk: () => {
                      delpushinvoice({EIid:record.EIid}).then((res:any)=>{
                        if (res.errorMessage) {
                          Modal.error({title: res.errorMessage})
                        } else {
                          actionRef.current?.reload() 
                          Modal.success({title: '撤销成功'})
                        }
                      })
                    }
                  })
                }}>撤销推送</Button>
                }
                <Button type="text" onClick={()=>{
                  Modal.confirm({
                    title: '确定要【'+(record.isNew?'设置为已同步':'设置为未同步')+'】吗？',
                    content: '已同步是指发票信息已经上传至金蝶系统',
                    onOk: async () => {
                      var res = null
                      if (record.isNew){
                        res = await syncinvoice({ids:record.id})
                      }else{
                        res = await cancelsyncinvoice({ids:record.id})
                      }
                      if (res.errorMessage) {
                          Modal.error({title: res.errorMessage})
                        } else {
                          actionRef.current?.reload() 
                          Modal.success({title: '操作成功'})
                        }
                    }
                  })
                }}>
                  {record.isNew?'设置为已同步':'设置为未同步'}
                </Button>
                </>)
              }
          
            
            >
                <Button>操作</Button>
            </Popover>
        </>
      ),

    },
  ];
  
 
  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    params.keyword=e.target.value;
    setParams(params)
  };

  const onSearch = (e:any)=>{

    actionRef.current?.reload() 
  }
  return (
    <>
    <ProTable
      id="invoiceTable"
      style={{minHeight:'90vh'}}
      tableLayout={'fixed'}
      scroll={{x:'max-content'}}
      headerTitle="发票列表"
      actionRef={actionRef}
      params={params}
      formRef={proTableFormRef}
      rowKey={(record:any)=>record.id}
      search={false}
      columns={columns}
      rowSelection={{
            onChange: (_, selectedRows) => {
              setSelectedRows(selectedRows);
              if (selectedRows.length>0){
                setIds(selectedRows.map((e:any)=>e.id).join(','))
              }else{
                setIds('')
              }
            },
          }}
      request={(params, sorter, filter) => {
        
        document.body.scrollTop = document.documentElement.scrollTop = 0;

        return getinvoicelist(params);
      }}
      toolbar={{

        filter: (
          <>
            
            <RangePicker allowEmpty={[true, true]} style={{width:'250px'}} onChange={(date:any, dateString:any) => {
                
                params.RequestTimeStart=dateString[0]
                if (dateString[1]) {
                  params.RequestTimeEnd=dateString[1]+' 23:59:59'
                }else{
                  params.RequestTimeEnd=''
                }
              }}  placeholder={['开票日期起','开票日期止']}/>
            <div style={{width:'730px',display:'flex',flexDirection:'row'}} >
              <Input style={{ width: '164px' }} onChange={handleInputChange}  placeholder="编号、开票单位" />
              <Input style={{ width: '164px' }} onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  params.EIid=e.target.value;
                  setParams(params)
                }}  placeholder="发票号" />
              
              <Companyselect style={{width:'200px'}} placeholder="开票单位" multiple={false} sign={1} onChange={(val:any)=>{
                if (val){
                  params.seller=val.company
                }else{
                  delete params.seller
                }
              }}/>
              <BusinesstypeTree style={{width:'120px'}} placeholder="业务类型" onChange={(val:any)=>{
                if (val){
                  params.businesstype = val
                }else{
                  delete params.businesstype
                }
              }}/>
              <Select
                allowClear
                style={{ width: 98 }}
                onChange={(val:any)=>{
                  if(val!=undefined){
                    params.pushed = val
                  }else{
                    delete params.pushed
                  }
                }}
                placeholder="是否推送"
                options={[
                  {
                    value: 1,
                    label: '已推送',
                  },
                  {
                    value: 0,
                    label: '未推送',
                  }
                ]}
              />
              <Dictselect type='发票媒体'  onChange={(value:any)=>{

                  if (value&&value.length>0){
                    
                    params.publication = value.map((x:any)=>x.label).join(',')
                  }else{
                    delete params.publication
                  }
                  
                }} multiple={true} needAddItem={false} style={{minWidth:'192px'}}></Dictselect>
              
            </div>
            <Button style={{ width: '65px' }} type="primary" onClick={onSearch}>搜索</Button>
            <Button onClick={()=>{
              
              setHmodal(true)
                
              }}>导出查询结果</Button>
              <Button onClick={()=>{
              
              Modal.confirm({
                title: '确定要推送发票至【广告管理系统】吗？',
                okText: '确定',
                cancelText: '取消',
                onOk: () => {
                  if(ids){
                      pushInvoiceToAdvertisingSystem({ids}).then((res:any)=>{ 
                        if (res.errorMessage){
                          Modal.error({
                            title: '错误信息',
                            content: (
                              <div dangerouslySetInnerHTML={{ __html: res.errorMessage }} />
                            ),
                          });
                          actionRef.current?.reload()
                    
                        }else{
                          Modal.success({title:'推送成功'})
                  
                        }
                      })
                    }else{
                      Modal.error({title:'勾选需要推送的发票'})
                      
                    }
                }
              });
                
              }}>推送发票</Button>

          </>
        ),
        actions: [

        ],
      }}
   
      
    />
    <TableScrollSync tableId="invoiceTable" onScroll={(scroll:any)=>{
                      const tableContent = document.querySelector('#invoiceTable .ant-table-content');
                      if (tableContent){
                        tableContent.scrollLeft = scroll;
                      }
          
                }} />
    <Modal
      title="回款纪录"

      style={{ top: 20, }}
      visible={pmodal}
      onOk={() => {
        setPmodal(false)
      }}
      onCancel={() => setPmodal(false)}
      footer={null}
    >
      <PayCollection key={rkey} EIid={selected.EIid}/>
    </Modal>
    <Modal
      title="发票详情"
      key={selected.id}
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
        <Descriptions.Item label="发票号码">{selected?.EIid}</Descriptions.Item>
        <Descriptions.Item label="发票类别">{selected.GeneralOrSpecialVAT}</Descriptions.Item>
        <Descriptions.Item label="开票日期">{selected.RequestTime}</Descriptions.Item>
        <Descriptions.Item label="销售方识别号">{selected.SellerIdNum}</Descriptions.Item>
        <Descriptions.Item label="销售方名称">{selected?.SellerName}</Descriptions.Item>
        <Descriptions.Item label="购买方识别号">{selected.BuyerIdNum}</Descriptions.Item>
        <Descriptions.Item label="购买方名称">{selected.BuyerName}</Descriptions.Item>
        <Descriptions.Item label="不含税开票金额">{selected?.TotalAmwithoutTax}</Descriptions.Item>
        <Descriptions.Item label="含税开票金额">{selected.TotalTaxIncludedAmount}</Descriptions.Item>
        <Descriptions.Item label="税额">{selected.TotalTaxAm}</Descriptions.Item>
        <Descriptions.Item label="备注">{selected.Remark}</Descriptions.Item>
        {
          selected.fileurls&&selected.fileurls!="" && 
          <Descriptions.Item label="附件">
            <Filescard  urls={selected.fileurls} mode='list'/>
         </Descriptions.Item>
         }
      </Descriptions>
    </Modal>
    <ViewModal key={'viewmodal'+selected.id} id={selected.invoicingid}  visible={view} onVisibleChange={setView}></ViewModal>
    <CommonDownloadModal url="/api/invoicing/getinvoicelist" headersUrl={"/api/invoicing/getheaders?type=invoice"} params={params}  visible={hmodal} onVisibleChange={setHmodal}/>
  </>);
};
export default Invoicelist