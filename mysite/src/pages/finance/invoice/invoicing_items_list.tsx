
import { Button, Descriptions, InputNumber, Modal, Popover, Table, Timeline } from 'antd';
import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import Add from '../company/add';
import { delinvoiceitem, getinvoiceitems } from './service';
import AddInvoicingItem from './add_invoicing_item';
import { set } from 'lodash';
import { render } from 'react-dom';
import { copyTextToClipboard } from '../utils';


const InvoicingItemsList: React.FC<{invoicingid:any,onChange?:Function,update?:boolean}> = ({invoicingid={},onChange,update=false}) => {

  var [tablekey,setTablekey] = useState(0)
  var [refreshkey,setRefreshkey] = useState(0)
  const [dataSource, setDataSource] = useState<any[]>([]);
  const [obj,setObj]=useState<any>({})
  const [addModal,setAddModal]=useState(false)
  useEffect(() =>{
    
    if (invoicingid){
      getinvoiceitems({
        invoicingid
      }).then((res:any)=>{
        if (res&&res.data){
          setDataSource(res.data)
        }
      })
    }
    
    
  },[invoicingid])

  const onMenuClick = (action:String,record:any) => {
    switch (action) {
      case '更新开票项目':
        setObj(record)
        setRefreshkey(++refreshkey)
        setAddModal(true)
        break
      case '添加开票项目':

        setObj({invoicingid:invoicingid})
        setRefreshkey(++refreshkey)
        setAddModal(true)

        break

      case '删除开票项目':
        Modal.confirm({
          title: '确定要删除吗？',
          okText: '确认',
          cancelText: '取消',
          onOk: () => {
            delinvoiceitem({id:record.id}).then((res:any)=>{
              if (res.errorMessage){
                Modal.error({title:res.errorMessage})
              } else {
                setDataSource(dataSource.filter((d:any)=>d.id!=record.id))
                setTablekey(++tablekey)
                onChange && onChange()
              }
            })
          } 
        })
        break
      default:
        break;
    }
  };
  const defaultColumns: any[] = [
    {
      title: '序号',
      dataIndex: 'index',
      key:'index',
      width: 65,
      render:(_:any,record:any,index:number)=>`${index+1}`
    },
    {
      title:'开票项目',
      dataIndex:'title',
      key:'title',
      width: 150,
      render: (text:any,record:any)=>(
        <span onClick={()=>{
          copyTextToClipboard(text)
        }}>{text}</span>
      )
    },
    {
      title:'单位',
      dataIndex:'unit',
      key:'unit',
      width: 80,
      render: (text:any,record:any)=>(
        <span onClick={()=>{
          copyTextToClipboard(text)
        }}>{text}</span>
      )
    },
    {
      title:'数量',
      dataIndex:'number',
      key:'number',
      width: 100,
      render: (text:any,record:any)=>(
        <span onClick={()=>{
          copyTextToClipboard(text)
        }}>{text}</span>
      )
    },
    {
      title: '开票金额',
      dataIndex: 'amount',
      key: 'amount',
      width: 120,
      render: (text:any,record:any)=>(
        <span onClick={()=>{
          copyTextToClipboard(text)
        }}>{!Number.isNaN(text)?parseFloat(text).toLocaleString('en-US', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2,
        }):0}</span>
      )
    },

    {
      title: '操作',
      fixed:'right',
      dataIndex: 'operation',
      hideInTable:true,
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
  
                <Button type="text" onClick={()=>{onMenuClick('更新开票项目',record)}}>更新</Button>
                <Button danger type="text" onClick={()=>{onMenuClick('删除开票项目',record)}}>删除</Button>

                
                

                </>)
              }
          
            
            >
                <Button>操作</Button>
            </Popover>
        </>
      ),

    }
  ];
  const onAddChange = (newval:any)=>{
    var index  = dataSource.findIndex((d:any)=>d.id==newval.id)
    if (index>-1){
      dataSource[index] = newval
      setDataSource(dataSource)
    }else{
      dataSource.push(newval)
      setDataSource(dataSource)
    }
    setTablekey(++tablekey)
    onChange && onChange(newval)

    
  }

  
  
 
  return (
    <>
      <Table
        key={'itlkey'+tablekey}
        title={()=>{
          return <div style={{width:'100%',display:'flex',alignItems:'center'}}>
        

              <div ><Button type="link" onClick={()=>{onMenuClick('添加开票项目',{invoicingid})}}>添加开票项目</Button></div>


          </div>
        }}
        rowKey={(record:any) => {
          return 'contract'+record.serial
        }}

        bordered
        dataSource={dataSource}
        columns={defaultColumns}
        pagination={false}
        locale={{emptyText:'未关联合同'}}
      />
      <AddInvoicingItem key={'additem'+refreshkey} data={obj} visible={addModal} onClose={()=>setAddModal} onChange={onAddChange}/>
    </>
  );
}

export default InvoicingItemsList;