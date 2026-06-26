
import { Badge, Descriptions, Divider, Modal, Tag } from 'antd';
import moment from 'moment';
import React, { CSSProperties, useEffect, useRef, useState } from 'react';
import Supplementary from './supplementary';
import { BalanceTypes } from '../budget/config';
import { getcontract } from './service';
import './common.css'
import Filescard from './filescard';
import PayCollection from './paycollection';
import { ContractStatesEnum } from './config';
import CompanysView from './CompanysView';
import InvoiceView from './invoice/invoiceView';
import AddLedger from './ledger/add';
import ViewLedger from './ledger/view';
import Urgelogs from './debt/urgelogs';
import CollectionGantt from './collectionGantt';
const tag:CSSProperties = {
  margin: '0 5px 0 0',
  padding: '0px 4px',
  borderRadius: '15%',
}
const View: React.FC<{id:any,paystate:any,attachNumber?:any}> = ({id={},paystate,attachNumber}) => {
  var [refreshkey,setRefreshkey] = useState(1)
  const [data,setData] = useState<any>({})
  const [last,setLast] = useState(0)
  const [viewLedger,setViewLedger]=useState(false)
  const [ledger,setLedger]=useState<any>({})
  const [addLedger,setAddLedger]=useState(false)
  var [ledgerkey,setLedgerkey]=useState(0)
  useEffect(() =>{
    setLast(id)
    if (id&&id!=last){
      setLast(id)
      getdata()
    }

    
    
  },[id])

  const onAddcSuc=(e:any)=>{
    
    data.ledgerid = e.data?.id
    setData(data)
    setAddLedger(false)
  }
  const getdata = async ()=>{
    console.log('get datas:',id)
    // 获取合同信息
    const res:any = await getcontract({id})
    if (res.errorMessage){
      Modal.error({title:res.errorMessage})
    } else {
      if (res.data.companyinfo!=undefined && typeof res.data.companyinfo=='string' && res.data.companyinfo!="" ) {
        res.data.companyinfo = JSON.parse(res.data.companyinfo)
      }
      
      setData(res.data)
      
      setRefreshkey(++refreshkey)
    }
    
  }
  return (
    <div key={refreshkey}>
      <Descriptions
        bordered
        title={(
          <div style={{height:'30px'}} key={'ledger'+data.ledgerid}>
            合同详情
            {
              data.state==ContractStatesEnum.NULLIFY && <Tag color="gray" style={tag}>作废</Tag>
            }
            {
              data.state==ContractStatesEnum.LOCK && <Tag color="green" style={tag}>存档</Tag>
            }
            {
               paystate?.includes('逾期') && <Tag color="red" style={tag}>逾期</Tag>
            }
            {
               paystate?.includes('临期') && <Tag color="orange" style={tag}>临期</Tag>
            }
            {
               attachNumber>0 && <Badge count={attachNumber} size='small' offset={[5,20]} style={{marginRight:'10px'}}><Tag color='blue' style={tag} >补</Tag></Badge>
            }
            {
              data.type == BalanceTypes.EXPEND && data.ledgerid>0 &&
              <span style={{color:'#1890FF',marginLeft:'10px'}} onClick={()=>{
                setViewLedger(true)
                setLedger({id:data.ledgerid})
              }}>查看台账</span>
              
            }
            {
              data.type == BalanceTypes.EXPEND && !data.ledgerid &&
              <span style={{color:'#1890FF',marginLeft:'10px'}} onClick={()=>{
                setAddLedger(true)
                setLedger({contractid:data.id})
              }}>创建台账</span>
              
            }

          </div>
        )}
        size={'default'}
        column={2}
        labelStyle={{width:120}}
      >
        <Descriptions.Item label="合同名称">{data?.title}</Descriptions.Item>
        <Descriptions.Item label="合同编号">
        <div style={{textAlign:'left'}}>
          <p  style={{fontWeight:'bolder',margin:0}}>{data.serial}</p>
          {data.deptserial&&<span style={{color:'gray',fontSize:'12px'}}>{data.deptserial}</span>}
        </div>
        </Descriptions.Item>
        <Descriptions.Item label="付款方">{data.partaname}</Descriptions.Item>
        <Descriptions.Item label="收款方">{data.partbname}</Descriptions.Item>
        <Descriptions.Item label="合同总价">{data.mainamount}</Descriptions.Item>
        <Descriptions.Item label="签订日期">{data.signdate?moment(data.signdate).format('YYYY-MM-DD'):''}</Descriptions.Item>
        <Descriptions.Item label="开票金额">{data.invoiceamount}</Descriptions.Item>
        <Descriptions.Item label="签订人">{data.signusername}</Descriptions.Item>
        <Descriptions.Item label="签订部门">{data.signdept}</Descriptions.Item>
        <Descriptions.Item label="合同类型">{data.type==BalanceTypes.INCOME?'收入':'支出'}</Descriptions.Item>
        <Descriptions.Item label="合同期限">{(data.starttime?data.starttime.substring(0,10):'')+'至'+(data.endtime?data.endtime.substring(0,10):'执行结束')}</Descriptions.Item>
        <Descriptions.Item label="经办人">{data.creatorname}</Descriptions.Item>
        <Descriptions.Item label="合同分类">{data.balancetypename}</Descriptions.Item>
        
        

        
         <Descriptions.Item label="合作内容">{data.content}</Descriptions.Item>

      </Descriptions>

      <Divider style={{ margin: '24px 0' }} />
      <CollectionGantt contractId={id} compact />
      
      <Descriptions  bordered column={1} contentStyle={{padding:'25px'}} labelStyle={{width:120}}>
        {
          data.companyinfo && Array.isArray(data.companyinfo) &&
          <Descriptions.Item label="公司信息" >
            <CompanysView datas={data.companyinfo} update={false}/>
         </Descriptions.Item>
          
          
        }

        {
          <Descriptions.Item label="履约条件" >
            <PayCollection key={refreshkey} contractid={data.id} financechek={true} editable={false}/>
         </Descriptions.Item>
        }
        {data.debturgeid && (
           <Descriptions.Item label="清欠措施">
             <Urgelogs contractid={data.id} debturgeid={data.debturgeid} type={1}/>
           </Descriptions.Item>
        )}
         
        {
          data.supplementary&&
          <Descriptions.Item label="补充协议">
           <Supplementary  key={refreshkey} editable={false} defaultValues={data.supplementary||[]}/>
         </Descriptions.Item>
        }
        
         {
          data.fileurls&&data.fileurls!="" && 
          <Descriptions.Item label="合同附件">
            <Filescard  urls={data.fileurls} mode='list'/>
         </Descriptions.Item>
         }
         {
          data.nullifyurls&&data.nullifyurls!="" && 
          <Descriptions.Item label="作废证明">
            <Filescard  urls={data.nullifyurls} mode='list' />
         </Descriptions.Item>
         }
         
         <Descriptions.Item label="发票信息" >
            <InvoiceView contractid={id}/>
         </Descriptions.Item>
         
         
      </Descriptions>
      {
          data.companyinfo && data.companyinfo.id &&
          
            <>
            <Descriptions  bordered column={2} contentStyle={{padding:'25px'}} labelStyle={{width:120}}>
              <Descriptions.Item label="信用编码">{data.companyinfo.code}</Descriptions.Item>
              <Descriptions.Item label="公司地址">{data.companyinfo.address}</Descriptions.Item>
              <Descriptions.Item label="联系人">{data.companyinfo.contacts&&data.companyinfo.contacts.split?data.companyinfo.contacts.split(' ')[0]:''}</Descriptions.Item>
              <Descriptions.Item label="联系电话">{data.companyinfo.contacts&&data.companyinfo.contacts.split?data.companyinfo.contacts.split(' ')[1]:''}</Descriptions.Item>
              <Descriptions.Item label="开户行">{data.companyinfo.bankaccount&&data.companyinfo.bankaccount.split?data.companyinfo.bankaccount.split(' ')[0]:''}</Descriptions.Item>
              <Descriptions.Item label="银行卡号">{data.companyinfo.bankaccount&&data.companyinfo.bankaccount.split?data.companyinfo.bankaccount.split(' ')[1]:''}</Descriptions.Item>
              </Descriptions>
            </>
         }
         <Modal
          title={'台账'}
          maskClosable={false}
          width={850}
          style={{ top: 20}}
          visible={addLedger}
          onOk={() => setAddLedger(false)}
          onCancel={() => setAddLedger(false)}
          footer= {null}
        >
          
          <AddLedger key={ledgerkey} data={ledger} onChange={onAddcSuc}/>
        </Modal>
      <Modal
          width={850}
          style={{ top: 0}}
          visible={viewLedger}
          onOk={() => setViewLedger(false)}
          onCancel={() => setViewLedger(false)}
          footer= {null}
        >
          
          <ViewLedger id={ledger.id} key={ledgerkey} />
        </Modal>
    </div>
    
  );
};

export default View;