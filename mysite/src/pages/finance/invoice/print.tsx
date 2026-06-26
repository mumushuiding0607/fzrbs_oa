import { Button, Modal } from "antd";
import './printInvoice.css'

import { useEffect, useRef, useState } from "react";

import moment from "moment";
import Usersigns from "../budget/budget/usersigns";
import { request } from "umi";
import { InvoicingStatesEnum } from "./config";

const Print:React.FC<{ids:any,onchange?: Function,typename?:any}> = ({typename,ids,onchange}) =>{
  const printRef = useRef<HTMLDivElement>(null);
  const [isPrinting, setIsPrinting] = useState(false);

  const [data,setData]=useState<any>([])
  useEffect(()=>{
 
    if (ids){

      request<{
        data: {};
      }>('/api/invoicing/getprintinfo', {
        method: 'GET',
        params: {
          ids
        },
      }).then((res:any)=>{
        if (res.errorMessage){
          Modal.error({
            title: res.errorMessage,
          });
        }else{
          setData(res.data||[])
        }
    
        
      })
    }
    

    
    

  },[ids])
  const handlePrint = () => {
    if (printRef.current) {
      const printContent = document.createElement('div');
      printContent.innerHTML = printRef.current.innerHTML;

      // 创建一个隐藏的打印容器
      document.body.appendChild(printContent);

      // 打开一个新的打印窗口
      const printWindow = window.open('', '', 'height=600,width=800');

      // 复制打印容器的内容到新窗口
      printWindow?.document.write(`
        <html>
          <head>
            <title></title>
            <style>
              :root{
                --printRowMinHeight: 60px;
                --fontSize: 16px;
              }
              /* 打印样式 */
              @media print {
                body {
                  margin: 0;
                  padding: 0 20px 0 20px ;
                  border: 1px solid white;
                }

                /* 隐藏不需要打印的内容 */
                .no-print {
                  display: none !important;
                }

                /* 设置打印区域的宽度 */
                @page {
                  size: auto;   /* auto is the current printer page size */
                  margin: 10mm; /* control the margins of the printed page */
                }

                /* 设置打印内容的边距 */
                .print-content {
                  margin: 0;
                  padding: 0;
                  box-sizing: border-box;
                  width: 100%;
                  height: 100%;
                }

                /* 控制分页 */
                .page-break {
                  page-break-after: always;
                }
                
              }
              .header, .footer {
                background-color: #f0f0f0;
                padding: 10px;
                text-align: center;
              }
              .container{
                width: 99%;
                height: '156.8mm'
                padding:2px;
                background-color: white;
                border:1px solid white;
              }
              .box{
                width: 100%;
                border:1px solid black;
                font-size: var(--fontSize);
              }
              .row{
                display: flex;
                flex-direction: row;
                min-height: 60px;
                border-bottom: 1px solid black;
                align-items: center;
              }
              .box .row:last-child{
                border: none;
              }
              .col{
                display: flex;
                flex-direction: column;
              }
              .col .row{
                border-left: 1px solid black;
              }
              .col .row:last-child{
                border-left: 1px solid black;
              }
              .label{
                /* width:80px; */
                flex: 1;
                height: 100%;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: center;
                padding: 5px;
              }
              .content{
                flex:5
              }
              .content .item{
                flex: 1;
                height: var(--printRowMinHeight);
                border-right: 1px solid black;
                display: flex;
                align-items: center;
                justify-content: center;
              }
              .content .item:last-child{
                border:none;
              }
              .content .item2{
                flex: 1;
                height: var(--printRowMinHeight);
                border-left: 1px solid black;
                display: flex;
                align-items: center;
                padding-left: 10px;
              }
              .item3{
                flex: 1;
                height: calc(var(--printRowMinHeight)*3.5);
                border-right: 1px solid black;
                padding-left: 10px;
              }

              .item4{
                flex:2;
                height: calc(var(--printRowMinHeight)*3.5);
                padding-left: 10px;
              }
              .item5{
                width: 100%;
              }
                /* 新增样式 */
              .contentBox{
                flex: 1;
                border-left: 1px solid black;
                height: calc(var(--printRowMinHeight)*2);
                
              }
              .label2{
                height:calc(var(--printRowMinHeight)*0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 0 5px;
                border-bottom: 1px solid black;
              
              }
              .content2{
                height:calc(var(--printRowMinHeight)*1.2);
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 0 5px;
              }
              .ant-table {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
                color: rgba(0, 0, 0, 0.85);
                font-variant: tabular-nums;
                line-height: 1.5715;
                list-style: none;
                font-feature-settings: 'tnum', "tnum";
                position: relative;
                font-size: 16px;
                background: #fff;
                border-radius: 2px;
              }

              .ant-table-tbody > tr > td {
                  border-bottom: 1px solid black;
                  border-right: 1px solid black;
                  padding: 5px;
              }
              .ant-table-tbody > tr > td:first-child {
                border-left: 1px solid black;
              }
              .ant-table-thead > tr > th {
                border-top: 1px solid black;
                border-bottom: 1px solid black;
                border-right: 1px solid black;
                text-align: left;
                padding: 5px;
              }
              .ant-table-thead > tr > th:first-child {
                border-left: 1px solid black;
              }
              .ant-table-thead > tr > th:second-child {
                text-align: right;
              }
              .ant-table-tbody > tr > td:nth-child(3),
              .ant-table-tbody > tr > td:nth-last-child(2)
               {
                text-align: right;
               }
    
            </style>
          </head>
          <body>
            <div class="print-content">
              ${printContent.innerHTML}
            </div>
          </body>
        </html>
      `);

      // 打印新窗口
      printWindow?.print();

      // 清理
      printWindow?.close();
      document.body.removeChild(printContent);

      setIsPrinting(false);
    }
  };
  const element = (e:any)=>{

    return <div className="container" style={{width: '210mm', height: '156.8mm' }}>
    <div className="print-content">
      <div className="row" style={{width:'100%',border:'none',display:'flex',justifyContent:'center',fontSize:'25px',fontWeight:'bold'}}>
        <div className="title">开票申请单{e.deldate?'（作废）':''}</div>
      </div>
      <div className="row" style={{border:'none',padding:0,minHeight:'30px',fontSize:'16px'}}>
        <div >开票单位（销售方）：</div>
        <div >{e.partbname}</div>

        <div style={{flexGrow:1,textAlign:'right',paddingRight:'5px'}}>
          <span style={{paddingRight:'5px'}}>{e.businesstype||''}</span>
          <span>{e.type==1?'专票':'普票'}</span>
          
        </div>
        <div>单号：{e.thirdNo}</div>
      </div>
      
      {/* 边框内的内容 */}
      <div className="box">
          <div className="row"  >
       
            <div className="contentBox" style={{flex:1.5,border:'none'}}>
               <div className="label2">
               发票抬头
               </div>
               <div className="content2">
               {e.partaname}
               </div>
            </div>

            <div className="contentBox">
               <div className="label2">
               纳税人识别号
               </div>
               <div className="content2">
               {e.bueryid}
               </div>
            </div>

            <div className="contentBox">
               <div className="label2">
               开票金额
               </div>
               <div className="content2">
               {e.amount.toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,})}
               </div>
            </div>

            <div className="contentBox">
               <div className="label2">
               申请日期
               </div>
               <div className="content2">
                {e.date?moment(e.date).format('YYYY-MM-DD'):''}
               </div>
            </div>

            <div className="contentBox">
               <div className="label2">
               经办人
               </div>
               <div className="content2" style={{flex:2}}>
                 <div className="col" style={{fontSize:'15px',textAlign:'center'}}>
                   <div style={{textAlign:'center'}}>{e.department}</div>
                   <div style={{textAlign:'center'}}>{e.name}</div>
                 </div>
               </div>
            </div>

          </div>

          <div className="row">
            <div className="label">开票内容</div>
            <div className="content" style={{borderLeft:'1px solid black'}}>
                
                  <div style={{padding:'5px'}}>{e.title}</div>
                
            </div>
          </div>

          <div className="row">
            <div className="label">开票备注</div>
            <div className="content">
              <div className="item2">{e.content}</div>
            </div>
          </div>

          {/* 审核意见 */}
          <div className="row">
            
            <div className="content">
              <div className="col">
                <div className="col" style={{padding:'5px'}}>
                    
                      
                      <div className="item5"><span style={{fontWeight:'bold'}}>合同：</span>{e.contractnames?e.contractnames:'无合同'}</div>
                    
                    {
                      e.projectnames!=null&&<div className="item5"><span style={{fontWeight:'bold'}}>项目：</span>{e.projectnames?e.projectnames:'未关联'}</div>
                    }
                    {
                      e.othercontent!=null&&<div className="item5"><span style={{fontWeight:'bold'}}>其他说明：</span>{e.othercontent}</div>
                    }
                    {
                      e.orders!=null&&<div className="item5"><span style={{fontWeight:'bold'}}>广告单：</span>{e.orders}</div>
                    }
                    {
                      e.type==1 &&
                      <div className="item5"><span style={{fontWeight:'bold'}}>客户信息：</span>{e.buyerinfo?e.buyerinfo:'暂无'}</div>
                    }
                    
                </div>


              </div>
            </div>
            
          </div>

      </div>

       
       {
        e.state!=InvoicingStatesEnum.DELETEED&&<div className="row" style={{border:'none',minHeight:'30px'}}>
          <div className="label2" style={{border:'none'}}></div><Usersigns datas={e.approvers}></Usersigns>
        </div>
       }
       {
        e.deldate &&
        <div className="row" style={{border:'none',minHeight:'30px'}}>
          作废日期：{e.deldate.substring(0,10)}
        </div>
       }
      
    </div>

  </div>
  }
  return (<div ref={printRef} id="print">
    <Button type="primary" size="large" className="no-print" onClick={handlePrint}>打印页面</Button>

     {
      data.map((e:any)=>{
        return element(e)
      })
     }


  </div>);
}
export default Print;


