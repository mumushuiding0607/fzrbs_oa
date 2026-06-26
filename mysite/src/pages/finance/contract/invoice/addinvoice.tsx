
import { Button, Card, Modal, Row, Table, Tag } from 'antd';
import Paragraph from 'antd/lib/typography/Paragraph';
import Title from 'antd/lib/typography/Title';
import React, { CSSProperties, useRef, useState } from 'react';

import MyUploadFile from '@/components/MyUploadFile';
import { setToUrl } from '../../utils';

import { request } from 'umi';

import moment from 'moment';
import { InvoicingStatesEnum } from '../../invoice/config';

import ViewModal from '../../invoice/viewModal';
import Dictselect from '../../budget/dict/dictselect';
import Businesstype_Tree from '../../invoice/Businesstype_Tree';


const tag:CSSProperties = {
  margin: '2px',
  padding: '0px 2px',
  width: '46px',
  display: 'flex',
  justifyContent:'space-evenly'
}
/**
 * 
 * @param param0 id 为 对应的fzrbs_contract合同id,invoicingid为fzrbs_invoicing表对应的id
 * @returns 
 */
const Addinvoice: React.FC<{id?:any,invoicingid?:any,url?:any,onChange?:Function}> = ({id=0,invoicingid=0,url,onChange}) => {
  const [data,setData]=useState<any>({})
  const [loading,setLoading]=useState(false)
  const uploadRef = useRef<AnimationPlayState>();
  const [defaultImage, setDefaultImage] = useState([])
  const [obj,setObj]=useState<any>({})
  const [view,setView]=useState(false)
  const [invoice,setInvoice] = useState<any>({})
  const [invoicings,setInvoicings]=useState<any[]>([])
  const [publication,setPublication]=useState('')
  const [subpublication,setSubpublication]=useState('')
  var [refreshkey,setRefreshkey]=useState(0)

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
      title: '开票状态',
      dataIndex: 'state',
      hideInSearch:true,
      width: 75,
      render:(_:any,record:any)=>{
              var text = '暂存'
              var color = 'default'
              if (record.state==InvoicingStatesEnum.DELETEED){
                text = '已作废';color='default';
              }else if (record.invoiceids!=null&&!record.realinvoiceamount){
                text = '已红冲';color='red';
              }else if (record.invoiceids!=null&&record.realinvoiceamount>0){
                text = '已开票';color='green';
              } else if (record.state==InvoicingStatesEnum.INVOICED&&record.invoiceids==null){
                text = '待开票';color='lime';
              } else if (record.thirdNo!=null&&record.thirdNo!=''){
                text = '审批中';color='red';
              }
              return (<div style={{textAlign:'left',display:'flex'}} onClick={()=>{
                setView(true)
                setObj(record)
              }}>
                <Tag color={color} style={tag}>{text}</Tag>

                
              </div>)
            }
    },
    {
      title: '合同',
      dataIndex: 'contractid',
      key: 'contractid',
      hideInSearch:true,
      width: 50,
      search:false,
      render: (_:any,record:any)=>(
        <>
          {
            !record.contractid &&
            <span>未签</span>
          }
          {
            record.contractid!=null && record.contractid!= "" &&
            <span style={{color:'#1890FF'}} >已签</span>
          }
        </>
      )
    },
    {
      title: '发票类别',
      dataIndex: 'type',
      key: 'type', 
      width: 75,
      render: (text:any,record:any)=> text?'专票':'普票'

    },
    {
      title: '开票单位(销售方)',
      dataIndex: 'partbname',
      key: 'partbname',
      sorter: true,
      width: 200,
      render:(text:any,record:any)=>{
    
             
        return (<div style={{textAlign:'left',color:'#1890FF'}} onClick={()=>{
          setView(true)
          setObj(record)
        }}>
      
          {text}

          
        </div>)
      }

    },
    {
      title: '客户名称',
      dataIndex: 'partaname',
      key: 'partaname',
      sorter: true,
      width: 200
    },
    {
      title: '开票项目',
      dataIndex: 'title',
      key: 'title',
      sorter: true,
      width: 150,
    },
    {
      title: '开票金额',
      dataIndex: 'amount',
      key: 'amount',
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
      title: '开票日期',
      dataIndex: 'date',
      key: 'date',
      sorter: true,
      valueType: 'dateRange',
      width: 120,
      fieldProps: {
        // 你可以在这里自定义时间选择器的行为
        format: 'YYYY-MM-DD',
        
      },
      render: (_:any, record:any) => {
        return record.date?moment(record.date).format('YYYY-MM-DD'):''
      },
    },
    {
      title: '申请日期',
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

            <Button key={'button'+index} onClick={()=>{
              invoice.invoicingid = record.id
              invoice.publication = publication
              invoice.subpublication = subpublication
              request(url||'/api/contract/saveinvoice', {
                data:invoice,
                method: 'POST',
              }).then((res:any)=>{
                if (uploadRef.current) {
                  uploadRef.current.fileList = [];
                  setDefaultImage([])
                }
                if (res.errorMessage){
                  Modal.error({title:res.errorMessage})
                }else{
                  
                  if (res.invoicings){
                    
                    setInvoicings(res.invoicings)
                    
                  }else{
                    setData(res.data)
                    onChange&&onChange(res.data)
                    record.invoiceids = res.id
                    record.publication = publication
                    record.subpublication = subpublication
                    setInvoicings([record])
                    setRefreshkey(++refreshkey)
                  }
                  Modal.success({title:res.msg?res.msg:'上传成功'})
                }
              })
            }}>上传发票</Button>
           
        </>
      ),
    },

  ]
  const save = ()=>{
    const uploads = uploadRef?.current?.getFileList()||[];
  
    if (uploads && uploads.length>0) {
      
    }else{
      Modal.error({title:'请点击选择发票'})
      return
    }
    if (loading) return
    setLoading(true)
    setTimeout(() => {
      setLoading(false)
    }, 3000);


    
    
    
    readAndParseXML(uploads[0].originFileObj,uploads.map((u:any)=>{
      return setToUrl(u)
    }).join(','))
    
    
    
  }
  const readAndParseXML=(file:any,fileurls:string)=> {
  
    if (!file) {
        alert('Please select an XML file.');
        return;
    }
  
    const reader = new FileReader();
    reader.onload = (e:any)=>{
        const xmlString = e.target.result;
        parseXML(xmlString,fileurls);
    };
    reader.readAsText(file);
  }
  const  parseXML = (xmlString:any,fileurls:string)=>{
   
    const parser = new DOMParser();
    const xmlDoc = parser.parseFromString(xmlString, "text/xml");

    var result:any ={}

    // 发票号码
    result.EIid = xmlDoc.getElementsByTagName("EIid")[0]?.textContent || '';
    // 发票类别
    result.GeneralOrSpecialVAT= xmlDoc.getElementsByTagName("GeneralOrSpecialVAT")[0]?.getElementsByTagName("LabelName")[0]?.textContent || ''
    // 开票日期
    result.RequestTime = xmlDoc.getElementsByTagName("RequestTime")[0]?.textContent || '';
    // 销售方识别号
    result.SellerIdNum = xmlDoc.getElementsByTagName("SellerIdNum")[0]?.textContent || '';
    // 销售方名称
    result.SellerName = xmlDoc.getElementsByTagName("SellerName")[0]?.textContent || '';
    result.SellerAddr = xmlDoc.getElementsByTagName("SellerAddr")[0]?.textContent || '';
    result.SellerTelNum = xmlDoc.getElementsByTagName("SellerTelNum")[0]?.textContent || '';
    result.SellerBankName = xmlDoc.getElementsByTagName("SellerBankName")[0]?.textContent || '';
    result.SellerBankAccNum = xmlDoc.getElementsByTagName("SellerBankAccNum")[0]?.textContent || '';
    // 购买方名称
    result.BuyerName = xmlDoc.getElementsByTagName("BuyerName")[0]?.textContent || '';
    // 购买方识别号
    result.BuyerIdNum = xmlDoc.getElementsByTagName("BuyerIdNum")[0]?.textContent || '';
    result.BuyerTelNum = xmlDoc.getElementsByTagName("BuyerTelNum")[0]?.textContent || '';
    result.BuyerAddr = xmlDoc.getElementsByTagName("BuyerAddr")[0]?.textContent || '';
    result.BuyerBankName = xmlDoc.getElementsByTagName("BuyerBankName")[0]?.textContent || '';
    result.BuyerBankAccNum = xmlDoc.getElementsByTagName("BuyerBankAccNum")[0]?.textContent || '';

    // 不含税开票金额
    result.TotalAmwithoutTax=xmlDoc.getElementsByTagName('TotalAmWithoutTax')[0]?.textContent || '';
    // 含税开票金额
    result.TotalTaxIncludedAmount=xmlDoc.getElementsByTagName('TotalTax-includedAmount')[0]?.textContent || '';
    // 税额
    result.TotalTaxAm=xmlDoc.getElementsByTagName('TotalTaxAm')[0]?.textContent || '';
    // 备注
    result.Remark=xmlDoc.getElementsByTagName('Remark')[0]?.textContent || '';
    // 开票项目
    result.IssuItemInformation = []
    var temp = xmlDoc.getElementsByTagName('IssuItemInformation');
    for (var i=0;i<temp.length;i++){
      var temp2:any = {}
        temp2.ItemName = temp[i].getElementsByTagName('ItemName')[0]?.textContent || '';
        temp2.SpecMod = temp[i].getElementsByTagName('SpecMod')[0]?.textContent || '';
        temp2.MeaUnits = temp[i].getElementsByTagName('MeaUnits')[0]?.textContent || '';
        temp2.Quantity = temp[i].getElementsByTagName('Quantity')[0]?.textContent || '';
        temp2.UnPrice = temp[i].getElementsByTagName('UnPrice')[0]?.textContent || '';
        temp2.Amount = temp[i].getElementsByTagName('Amount')[0]?.textContent || '';
        temp2.TaxRate = temp[i].getElementsByTagName('TaxRate')[0]?.textContent || '';
        temp2.ComTaxAm = temp[i].getElementsByTagName('ComTaxAm')[0]?.textContent || '';
        temp2.TotaltaxIncludedAmount = temp[i].getElementsByTagName('TotaltaxIncludedAmount')[0]?.textContent || '';
        temp2.TaxClassificationCode = temp[i].getElementsByTagName('TaxClassificationCode')[0]?.textContent || '';
        result.IssuItemInformation.push(temp2)
    }

    if (id)result.contractid=id
    if (invoicingid) result.invoicingid=invoicingid
    result.fileurls = fileurls
    // 媒体
    result.publication = publication
    result.subpublication = subpublication
    setInvoice(result)
    request(url||'/api/contract/saveinvoice', {
      data:result,
      method: 'POST',
    }).then((res:any)=>{
      if (uploadRef.current) {
        uploadRef.current.fileList = [];
        setDefaultImage([])
      }
      if (res.errorMessage){
        Modal.error({title:res.errorMessage})
      }else{
        
        if (res.invoicings){
          if (res.invoicings.length==0){
            Modal.error({title:'没有找到对应的开票申请，无法上传发票！'})
          }
          setInvoicings(res.invoicings)
          if (res.msg){
            Modal.warn({title:res.msg})
          }
          
          
        }else{
          setData(result)
          setRefreshkey(++refreshkey)
          onChange&&onChange(result)
          Modal.success({title:res.msg?res.msg:'上传成功'})
        }
        
      }
    })
 
  
    
  }
  const onPublicationChange=(value:any)=>{

    if (value&&value.length>0){
      setPublication(value.map((x:any)=>x.label).join(','))
    }else{
      setPublication('')
    }
    
  }
  const onBusinesstypeChange=(value:any)=>{
    setSubpublication(value)
  }
  return (
    <div>
      
  
    
            <MyUploadFile
              name="fileurls"
              label=""
              max={1}
              multiple={false}
              accept=".xml,.XML"
              maxSize={100}
              listType="picture-card"
              defaultImage={defaultImage}
              uploadPath="contract"
              uploadType={3}
              ref={uploadRef}
            />
          {
            invoicings.length>0 &&
            <Table
              rowKey={(record:any) => {
                return 'invoicing'+record.id
              }}
              key={'table'+refreshkey}
              bordered
              dataSource={invoicings}
              columns={columns}
              pagination={false}
              locale={{emptyText:'未找到对应的开票申请'}}
            />
          }
          
          <Row>
      
            <Dictselect type='发票媒体' onChange={onPublicationChange} multiple={true} needAddItem={false} style={{minWidth:'150px'}}></Dictselect>
            <Businesstype_Tree type="新媒体业务" placeholder="新媒体类型" onChange={onBusinesstypeChange}/>
  
      
            <Button type='primary' loading={loading} onClick={()=>{
              save()
            }}>提交发票</Button>
          </Row>
        
      {
        data && data.EIid &&
        <Card title="发票详情">
            <Title level={4}>发票信息</Title>
            <Paragraph><strong>发 票 号 码:</strong> {data.EIid}</Paragraph>
            <Paragraph><strong>发 票 类 别:</strong> {data.GeneralOrSpecialVAT}</Paragraph>
            <Paragraph><strong>开 票 日 期:</strong> {data.RequestTime}</Paragraph>
            <Paragraph><strong>销售方识别号:</strong> {data.SellerIdNum}</Paragraph>
            <Paragraph><strong> 销售方名称:</strong> {data.SellerName}</Paragraph>
            <Paragraph><strong>购买方识别号:</strong> {data.BuyerIdNum}</Paragraph>
            <Paragraph><strong> 购买方名称:</strong> {data.BuyerName}</Paragraph>
            <Paragraph><strong>不含税开票金额:</strong>{data.TotalAmwithoutTax}</Paragraph>
            <Paragraph><strong>含税开票金额:</strong> {data.TotalTaxIncludedAmount}</Paragraph>
            <Paragraph><strong>备注:</strong> {data.Remark}</Paragraph>
      </Card>
      }
    <ViewModal key={'viewmodal'+obj.id} id={obj.id} thirdNo={obj.thirdNo} visible={view} onVisibleChange={setView} defaultActiveKey={'2'}></ViewModal>
    </div>
  );
}

export default Addinvoice;