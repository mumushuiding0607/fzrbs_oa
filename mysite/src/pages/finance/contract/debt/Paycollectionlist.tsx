
import { Button, DatePicker, Input, Modal, Popover, Radio, Table, Tag } from 'antd';


import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import UserAutocomplete from '../../budget/common/userAutocomplete';
import { ActionType, ProFormInstance, ProTable } from '@ant-design/pro-components';
import Dictselect from '../../budget/dict/dictselect';
import { debtlist, debturge, paycollectionlist } from './service';

import Companyselect from '../../company/companyselect';


import TableScrollSync from '../../common/TableScrollSync';
import PayCollectionModal from './PaycollectionModal';



const tag:CSSProperties = {
  margin: '0 5px 0 0',
  padding: '0px 4px',
  borderRadius: '15%',
}
// balancetype 15收入，16支出
const Paycollectionlist: React.FC<{scrollTop?:boolean,id?:any,table?:any}> = ({scrollTop=false,id,table}) =>{
  const [params,setParams]=useState<any>({})
  const proTableFormRef = useRef<ProFormInstance>();
  const actionRef = useRef<ActionType>();
  const [showp,setShowp]=useState(false)
  const [pparams,setPparams]=useState<any>({})
  const size = 4
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
      title:'债务方信息',
      dataIndex:'partaname',
      key:'partaname',
      width: 150
    },
    {
      title:'合同总额',
      dataIndex:'contractamount',
      key:'contractamount',
      sorter: true,
      width: 120,
      render: (t:any,record:any)=>{

        return (
        <>
          {!Number.isNaN(t)?parseFloat(t).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
        </>
      )
      }
    },
    {
      title:'期初欠款',
      dataIndex:'startdebt',
      key:'startdebt',
      sorter: true,
      width: 120,
      render: (text:any,record:any)=>{
        var t = text
        if (!record.startdebt){
          t = record.contractamount-(record.startpaycollection||0)
        }
        return (
        <span style={{color:'#1890FF'}} onClick={()=>{
          var p = {...params}
          p.parta = record.parta
          p.dateend = params.datestart
          delete p.datestart
          setPparams(p)
          setShowp(true)
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
      title:'期间回款',
      dataIndex:'paycollection',
      key:'paycollection',
      sorter: true,
      width: 120,
      render: (t:any,record:any)=>{

        return (
        <span style={{color:'#1890FF'}} onClick={()=>{
          var p = {...params}
          p.parta = record.parta
          setPparams(p)
          setShowp(true)
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
      title:'期末金额',
      dataIndex:'debt',
      key:'debt',
      sorter: true,
      width: 120,
      render: (text:any,record:any)=>{
        var t = text
        if (!record.debt){
          t = record.contractamount-(record.paycollection||0)-(record.startpaycollection||0)
        }
        return (
        <span style={{color:'#1890FF'}} onClick={()=>{
          var p = {...params}
          p.parta = record.parta
          p.datestart = params.dateend
          delete p.datestart
          setPparams(p)
          setShowp(true)
        }}>
          {!Number.isNaN(t)?parseFloat(t).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }):0}
        </span>
      )
      }
    },



  ];
  const [expanded, setExpanded] = useState(false);

  // 所有表单项
  const items = [


    <DatePicker.RangePicker
        style={{width:'300px'}}
        placeholder={['开始日期', '结束日期']}
        allowEmpty={[false, true]}
        onChange={(date:any, dateString:any) => {
        
          console.log('dateString:',dateString)
          if(dateString){
            params.datestart = dateString[0];
            params.dateend = dateString[1];
            setParams(params);
          }
        }}
      />,

    <Companyselect
      key="parta"
      style={{ width: '200px' }}
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
    <UserAutocomplete placeholder='经办姓名' width='120px'  multiple={false} onChange={(value: any)=>{
      params.creator = value ? value.value : null;
      setParams(params);
    }}/>


  ];

  const displayItems = expanded ? items : items.slice(0, size);


 
  return (
    <>
  
    <ProTable
     
      scroll={{x:'100%'}}
      id={'debtlist'}
      actionRef={actionRef}
      params={params}
      formRef={proTableFormRef}
      rowKey={(record:any)=>record.id}
      search={false}
      columns={columns}

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

        setParams(params);
        
        return paycollectionlist(params);
      }}
      headerTitle={
              <>

              </>
            }
            title={()=>[
              
              
            ]}
      toolbar={{

        filter: (
          <><div style={{ display: 'flex', flexWrap: 'wrap', gap: '8px', alignItems: 'flex-start' }}>
                {displayItems}

                {/* 展开/收起按钮 */}
                {items.length > size && (
                  <Button
                    type="link"
                    size="small"
                    onClick={() => setExpanded(!expanded)}
                    style={{ whiteSpace: 'nowrap', alignSelf: 'center' }}
                  >
                    {expanded ? '收起' : `展开更多 (${items.length - size})`}
                  </Button>
                )}
              </div></>
        ),
        actions: [
          <Button
          key="search"
          type="primary"
          onClick={() => {
            actionRef.current?.reload(); // 触发 reload 即重新执行 request
          }}
        >
          搜索
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
    
      <PayCollectionModal key={'pm'+pparams?.parta+pparams.datestart+pparams.dateend} parta={pparams.parta} dateend={pparams.dateend} datestart={pparams.datestart}  visible={showp}  onVisibleChange={(visible:any)=>setShowp(visible)}/>
  </>
  )
}
export default Paycollectionlist;