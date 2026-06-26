
import { Button, Descriptions, InputNumber, Modal, Popover, Table, Timeline } from 'antd';
import React, { CSSProperties, useEffect, useRef, useState } from 'react';

import { delinvoice, getinvoicelist } from '../service';
import Filescard from '../filescard';
import { request } from 'umi';
import { pushInvoiceToAdvertisingSystem } from '../../invoice/service';



const InvoiceView: React.FC<{contractid?:any,invoicingid?:any,projectid?:any}> = ({contractid=0,invoicingid=0,projectid=0}) => {
  const [data,setData] = useState<any>([])
  const [modal,setModal]=useState<any>(false)

  const [sending,setSending]=useState(false)
  const [selected,setSelected]=useState<any>({})
  const onMenuClick = (action:String,record:any) => {
    
    switch (action) {
      
        case '删除':
          Modal.confirm({
            title: '确定要删除吗？',
            okText: '确认',
            cancelText: '取消',
            onOk: () => {
              delinvoice({id:record.id}).then((res:any)=>{
                if (res.errorMessage){
                  Modal.error({title:res.errorMessage})
                } else {
                  
                  setData(data.filter((item:any)=>item.id!==record.id))
                  setRefreshkey(++refreshkey)
                }
                
              })
            },
            
          });
        break
        case '详情':
          setSelected(record)
          setModal(true)
          break
        case '发送发票':
          setSending(true)
          if(record.sended){
            Modal.confirm({
              title: '该发票已通过邮箱发送给客户了，需要重新发送吗？',
              okText: '发送',
              cancelText: '取消',
              onOk: () => {
                
                request('/api/invoicing/sendinvoice',{
                  method:'GET',
                  params:{id:record.id}
                }).then((res:any)=>{
                  setSending(false)
                  if (res.errorMessage){
                    Modal.error({title:res.errorMessage})
                  } else {
                    Modal.success({title:'发送成功'})
                  }
                })
              },
              onCancel: () => {
                setSending(false)
              }
            })
          }else{
            Modal.confirm({
              title: '确定要发送发票给客户吗？',
              okText: '确定',
              cancelText: '取消',
              onOk: () => {
                request('/api/invoicing/sendinvoice',{
                  method:'GET',
                  params:{id:record.id}
                }).then((res:any)=>{
                  setSending(false)
                  if (res.errorMessage){
                    Modal.error({title:res.errorMessage})
                  } else {
                    Modal.success({title:'发送成功'})
                  }
                })
              },
              onCancel: () => {
               
              }
            })
            
          }
          
          break

      default:
        break;
    }
  };
  
  const columns:any = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      width: 65,
      render:(_:any,record:any,index:number)=>`${index+1}`
    },
     {
      title:'开票日期',
      dataIndex:'RequestTime',
      key:'RequestTime',
      width:108,
      render:(text:any)=>{
        return text&&text.substring?text.substring(0,10):''
      }
    },
    {
      title:'发票号',
      dataIndex:'EIid',
      key:'EIid',
      // 点击发票号跳转到发票详情
      render: (text:any,record:any)=>{
        return <span style={{color:'#1890FF',width:'150px'}} onClick={()=>{onMenuClick('详情',record)}}>{text}</span>
      },
      width: 150,
    },
    
    {
      title:'开票金额',
      dataIndex:'TotalTaxIncludedAmount',
      key:'TotalTaxIncludedAmount'
    },
   
    {
      title:'媒体',
      dataIndex:'publication',
      key:'publication'
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
  
                <Button type="text" onClick={()=>{onMenuClick('删除',record)}}>删除</Button>
                <br></br>
                <Button type="text" onClick={()=>{onMenuClick('详情',record)}}>详情</Button>
                <br></br>
                <Button type="text" loading={sending} onClick={()=>{onMenuClick('发送发票',record)}}>发送发票</Button>
                <br></br>
                <Button onClick={()=>{
                              
                              Modal.confirm({
                                title: '确定要推送发票至【广告管理系统】吗？',
                                okText: '确定',
                                cancelText: '取消',
                                onOk: () => {
                                  
                                pushInvoiceToAdvertisingSystem(record.id).then((res:any)=>{ 
                                  if (res.errorMessage){
                                    Modal.error({title:res.errorMessage})
                              
                                  }else{
                                    Modal.success({title:'推送成功'})
                            
                                  }
                                })
                                   
                                }
                              });
                                
                              }}>推送发票</Button>

                </>)
              }
          
            
            >
                <Button>操作</Button>
            </Popover>
        </>
      ),

    },
  ];
  var [refreshkey,setRefreshkey] = useState(0)
  const isMounted = React.useRef(true)
  useEffect(() =>{
    if (!isMounted.current){
      return
    }
    isMounted.current = false;
    // 获取发票信息
    if(!contractid&&!invoicingid&&!projectid){
      Modal.error({title:'合同contractid、开票申请invoicingid或非报项目projectid不能都为空'})
      return
    }
    getinvoicelist({contractid,invoicingid,projectid,pageSize:50}).then((res:any)=>{
      if (res.errorMessage){
        Modal.error({title:res.errorMessage})
      }else{
        setData(res.data||[])
      }
    })
  },[contractid,invoicingid,projectid])


  
 
  return (
    <div style={{'width':'100%'}}>

    
      <Table
        
        rowKey={(record:any) => record.id}
        bordered
        dataSource={data}
        columns={columns}
        locale={{emptyText:'暂无发票'}}
        pagination={false}
      />
    
     
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
     
 
    </div>
  );
}

export default InvoiceView;