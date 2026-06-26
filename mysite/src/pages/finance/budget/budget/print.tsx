import { Button, Modal } from "antd";
import './print.css'
import Budgetdetail from "./budgetdetail";
import { useEffect, useRef, useState } from "react";
import { getsingleprojectbyid, getFlowinfodata, getuserbyrole } from "./service";
import moment from "moment";
import Filescard from "../../contract/filescard";
import Usersigns from "./usersigns";
import { FlowStateEunm, ProjectStatesEnum } from "../config";
import ReportView from "../project/reportview";
import { getprojectbyid } from "../project/service";
const Print:React.FC<{record:any,onchange?: Function,typename?:any}> = ({typename,record,onchange}) =>{
  const printRef = useRef<HTMLDivElement>(null);
  const [isPrinting, setIsPrinting] = useState(false);
  const [hasContract,setHasContract]=useState<boolean>(false)
  const [flowinfo,setFlowInfo]=useState<any>({})
  var [refresh,setRefresh]=useState(0)
  const [leader,setLeader]=useState('')
  const [show,setShow]=useState('')
  const [project,setProject]=useState<any>({})
  useEffect(()=>{
 
    if (record.id){

      getsingleprojectbyid({id:record.id}).then((res:any)=>{
        if (res){
          setProject(res.data||{})
        }
        
      })
    }
    
    if (record.thirdno||record.thirdNo){
      getFlowinfodata({thirdNo:record.thirdno||record.thirdNo}).then((res:any)=>{
        
        if (res){
          setRefresh(++refresh)
          setShow(res.state==ProjectStatesEnum.FINAL?'all':'budget')
          setFlowInfo(res)
        }
      })
    }
    
    

  },[record.id])
  async function printIframe(selector:any) {

    const printContent = document.getElementById('printP');
  


    // 创建 iframe
    const iframe = document.createElement('iframe');
    if (!iframe) return;
    iframe.style.position = 'absolute';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = 'none';
    document.body.appendChild(iframe);
    const iframeDoc = iframe.contentWindow?.document;
    iframeDoc?.open();



    iframeDoc?.write(`
      <html>
        <head>
          <title>打印1</title>
          <style>
   :root{
                --printRowMinHeight: 60px;
                --fontSize: 18px;
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
                aspect-ratio: 210/297;
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
        <body class="print-content" style="border: 1px solid white;padding:20px;">
          ${printContent?.innerHTML}
        </body>
      </html>
    `);
    iframeDoc?.close();

    // 等待 iframe 内容加载完成
    iframe.onload = function() {
      iframe.contentWindow?.print();
      // 打印后移除 iframe（可选）
      setTimeout(() => document.body.removeChild(iframe), 1000);
    };
  }
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
                --fontSize: 18px;
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
                aspect-ratio: 210/297;
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
      if (!printWindow) return;
      // 打印新窗口
 
      printWindow.onload = () => {
        printWindow.print();
        // 可选：监听打印完成，关闭窗口
        printWindow.onafterprint = () => printWindow.close();
    };


      document.body.removeChild(printContent);

      setIsPrinting(false);
    }
  };
  return (<div ref={printRef} >
    <Button type="primary" size="large" className="no-print" onClick={printIframe}>打印页面</Button>

    <div id="printP" className="container">
      <div className="print-content">
        <div className="row" style={{width:'100%',border:'none',display:'flex',justifyContent:'center',fontSize:'25px',fontWeight:'bold'}}>
          <div className="title">福州日报社经营项目（活动）审批表</div>
        </div>
        {/* 边框内的内容 */}
        <div className="box">
            {/* 项目内容  */}
            <div className="row">
              <div className="label">项目内容：</div>
              <div className="content">
                  <div className="col" style={{flexGrow:1}}>
                    <div className="row" >
                      <div className="item">立项时间</div>
                      <div className="item">{project.starttime?moment(project.starttime).format('YYYY-MM-DD'):''}</div>
                      <div className="item">是否签订合同</div>
                      <div className="item">{project.contractids&&project.contractids.length>0?'有':'无'}</div>
                    </div>
                    <div className="row" >
                      <div className="item2" style={{border:'none',fontWeight:'bold',fontSize:'20px'}}>关于{project.title}的{typename||'预算'}报告</div>
                    </div>
                    
                  </div>
              </div>
              
            </div>
            {/* 申报部门意见 */}
            <div className="row">
              <div className="label">申报部门意见</div>
              <div className="content">
                <div className="item2">
                  <div><span>经初审，本项目具有可行性，收支项目合规，特提请审议。项目负责人：</span><span style={{fontWeight:'bolder',marginRight:'10px'}}>{flowinfo?.projectcharger||project.chargername}</span>  部门负责人： <span style={{fontWeight:'bolder'}}>{flowinfo?.deptcharger}</span></div>

                </div>
              </div>
              
            </div>
            <div className="row">
              <div className="label">分管领导意见</div>
              <div className="content">
                <div className="item2">
                  同意（‌{flowinfo?.leaders && flowinfo?.leaders.length>0 && '✔‌'}）；不同意（ ）。<span style={{fontWeight:'bold'}}>{flowinfo?.leaders}</span></div>
              </div>
            </div>
            <div className="row">
              <div className="label">法律顾问意见</div>
              <div className="content">
                <div className="item2">同意（‌ ）；不同意（ ）。</div>
              </div>
            </div>
            {/* 审核意见 */}
            <div className="row">
              <div className="label">审核意见</div>
              <div className="content">
                <div className="col">
                  <div className="row">
                    <div className="item3">编委会（公司办公会）会签或附会议纪要（注明会议名称、日期）</div>
                    <div className="item4">
                      经审核，本项目符合可行性、合规性要求。
                      {
                        flowinfo.editorialSpeech &&
                        <div  style={{fontSize:'18px'}} >
                          <span >{flowinfo.editorialSpeech}</span>
                          <span style={{marginLeft:'10px',fontWeight:'bolder'}}>{flowinfo.editorialUsername}</span>
                          {
                            flowinfo.editorialDate &&
                            <span style={{marginLeft:'10px',fontWeight:'bolder'}}>{flowinfo.editorialDate}</span>
                          }
                        </div>
                      }
                      {
                        flowinfo.editorialboard &&
                        <Usersigns datas={flowinfo.editorialboard}></Usersigns>
                      }
                      
                    </div>
                  </div>
                  <div className="row">
                    <div className="item3">
                      收支是否合规（30万元以下项目由财务、法务、内审审核会签）</div>
                    <div className="item4">
                      经审核，本项目收支符合财务管理要求、法务及其他内审规定。
                      
                      {
                        flowinfo.approvers &&
                        <Usersigns datas={flowinfo.approvers}></Usersigns>
                      }
                    </div>
                  </div>
                  <div className="row">
                    <div className="item3">社经营活动审核监督管理小组审核或会签（30万元以上项目）</div>
                    <div className="item4">
                      经审核，本项目收支符合财务管理要求、法务及其他内审规定。
                
                      {
                        flowinfo.economicalSpeech &&
                        <div  style={{fontSize:'16px',marginTop:'10px'}} >
                          <span >{flowinfo.economicalSpeech}</span>
                          <span style={{marginLeft:'10px',fontWeight:'bolder'}}>{flowinfo.economicalUsername}</span>
                          {
                            flowinfo.economicalDate &&
                            <span style={{marginLeft:'10px',fontWeight:'bolder'}}>{flowinfo.economicalDate}</span>
                          }
                        </div>
                      }
                      
                      {
                        flowinfo.economicalboard &&
                        <Usersigns datas={flowinfo.economicalboard}></Usersigns>
                      }
                    </div>
                  </div>
                </div>
              </div>
              
            </div>

        </div>

       
          <div  className="row" style={{width:'100%',border:'none',display:'flex',justifyContent:'flex-end',fontSize:'20px',paddingRight:'20px'}}>
            <span>审批编号：</span>
            <span style={{borderBottom:'1px solid black',lineHeight:'25px',fontSize:'18px',width:'130px',height:'25px'}}></span>
            
          </div>
        
      </div>
      <div style={{height:'100px',width:'100vw'}}></div>
      {/* 报告 */}
      <div>
      {
        typename=='预算'&&
        <ReportView id={record.id} field={'budgetreport'} edit={false}  />  
      }
      
      </div>
      {
        typename=='决算'&&
        <div>
          <ReportView id={record.id} field={'finalreport'} edit={false}  />
        </div>
      }
      {/* 收入支出总表 */}
      <div className="page-break" >
          <div style={{height:'80px',width:'99%',display:'flex',alignItems:'center',marginTop:"10PX"}}></div>
         
          <Budgetdetail print={true}   id={record.id} show={['决算','提交计量','已提交'].includes(typename)?'final':'budget'}></Budgetdetail>
          {
        flowinfo.accounts &&
        <div style={{display:'flex',alignItems:'center'}}>
        <span>金额计算已核对：</span><Usersigns datas={flowinfo.accounts}></Usersigns>
        </div>
      }
      </div>
      
    </div>


  </div>);
}
export default Print;


