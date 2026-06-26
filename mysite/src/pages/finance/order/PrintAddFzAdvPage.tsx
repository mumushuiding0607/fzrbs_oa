import { Button, Modal } from 'antd';
import './print.css';
import { useEffect, useRef, useState } from 'react';
import moment from 'moment';
import Usersigns from '../budget/budget/usersigns';

const PrintAddFzAdvPage: React.FC<{ records?: any[] }> = ({records }) => {
  const printRef = useRef<HTMLDivElement>(null);
  const [data, setData] = useState<any[]>(records||[]);
  const [rk,setRk]=useState(0)

  // 如果传入records数组，直接使用；如果传入单个record，转换为数组
  useEffect(() => {
    console.log('records', records)
    setData(records||[]);
    setRk(rk+1)
  }, [ records]);

  // Format date function
  const formatDate = (date: string | undefined) => {
    return date ? moment(date).format('YYYY-MM-DD') : '';
  };

  // Format money value
  const formatMoney = (value: number | string | undefined) => {
    if (value === undefined || value === null) return '';
    return typeof value === 'number' ? value.toFixed(2) : value;
  };

 

  async function printIframe(selector:any) {

    const printContent = document.getElementById('print');


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
  --printRowMinHeight: 50px;
  --fontSize: 15px;
}
@media print {
  body {
    margin: 0;
    padding: 0;
  }

  /* 隐藏不需要打印的内容 */
  .no-print {
    display: none !important;
  }

  /* 设置打印区域的宽度 */
  @page {
    size: A4;   /* auto is the current printer page size */
    margin: 10mm; /* control the margins of the printed page */
  }

  /* 设置打印内容的边距 */
  .print-content {
    margin: 0;
    padding: 0;
  }

  /* 控制分页 */
  .page-break {
    page-break-before: always;
  }
  .header, .footer {
    display: none;
  }
}
.header, .footer {
  background-color: #f0f0f0;
  padding: 10px;
  text-align: center;
}
.container{
  width: 99%;
  height: '142.52mm'
  padding:2px;
  background-color: white;
  border:1px solid white;
}
.box{
  width: 100%;
  border:1px solid black;
  font-size: var(--fontSize);
  border-right:none ;
}
.row{
  display: flex;
  flex-direction: row;
  min-height: var(--printRowMinHeight);
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
  width:120px;
  /* flex: 1; */
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
#print .content .item{
  flex: 1;
  height: var(--printRowMinHeight);
  border-right: 1px solid black;
  border-left: 1px solid black;
  display: flex;
  align-items: center;
  justify-content: center;
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
  height: calc(var(--printRowMinHeight)*3);
  border-right: 1px solid black;
  padding-left: 10px;
}

.item4{
  flex:2;
  height: calc(var(--printRowMinHeight)*3);
  padding-left: 10px;
}

@media print {
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  .no-print {
    display: none !important;
  }
  .print-content {
    margin: 0 !important;
    padding: 0 !important;
  }
  .page-break {
    page-break-before: always !important;
  }
  .header, .footer {
    display: none !important;
  }
}






          </style>
        </head>
        <body id="print" style="border: 1px solid white;padding:10px 20px;">
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

  const element = (record:any)=>{
    return <div className="container" style={{height: '142.52mm'}}>
        <div className="print-content">
          <div className="row" style={{ minHeight:'35px',width: '100%', border: 'none', display: 'flex', justifyContent: 'center', fontSize: '21px', fontWeight: 'bold' }}>
            <div className="title">福州日报社广告以及纯服务收入登记表</div>
          </div>
          
          {/* 第一行：下单日期和单据编号 on the right side with 10px spacing, following PNG layout */}
          <div className="row" style={{ border: 'none', padding: 0, minHeight: '30px', fontSize: '16px' }}>
            <div style={{ flexGrow: 1 }}>
              <span>下单日期：</span>
              <span>{formatDate(record.SYS_CREATED)}</span>
            </div>
            <div style={{ flexGrow: 1, textAlign: 'right', paddingRight: '20px' }}>
              <span>合同编号：</span>
              <span>{record.contractserial}</span>
            </div>
            

            <div style={{ textAlign: 'right' }}>
              <span>单据编号：</span>
              <span>{record.SYS_DOCUMENTID}</span>
            </div>
            {
              record.issueno!=null &&
              <div style={{ textAlign: 'right',paddingLeft: '20px' }}>
                <span>广告期数：</span>
                <span>{record.issueno}</span>
              </div>
            }
            
          </div>
          
          {/* 边框内的内容 */}
          <div className="box">
            {/* 第二行：关联合同、客户、主体 */}
            <div className="row"> 
                <div className="label" >客户</div>
                <div className="content">
                  <div className='item' style={{padding:'5px','textAlign':'center'}}>{record.AI_Customer || ''}</div>
                </div>
                <div className="label">主体</div>
                <div className="content">
                  <div className='item' style={{padding:'5px','textAlign':'center'}}>{record.partbname || ''}</div>
                </div>
            </div>
            <div className="row"> 
                <div className="label">发布平台</div>
                <div className="content">
                  <div className='item'>{record.AI_Publication || ''}</div>
                </div>
                <div className="label">广告分类</div>
                <div className="content">
                  <div className='item'>{record.AI_Trade || ''}</div>
                </div>

     
            </div>

            <div className="row">
              <div className="label">媒体投放</div>
                <div className="content">
                  <div  className='item' style={{ height: 'auto', minHeight: '35px', alignItems: 'flex-start',justifyContent:'flex-start', padding: '5px' }}>{record.media}</div>
                </div>
            </div>
            <div className="row">
              <div className="label">合作内容</div>
                <div className="content">
                  <div className='item' dangerouslySetInnerHTML={{ __html: record.AI_Content || '' }}  style={{ height: 'auto', minHeight: '35px', alignItems: 'flex-start',justifyContent:'flex-start', padding: '5px' }}></div>
                </div>
            </div>
            
            <div className="row"> 

                <div className="label">刊例价</div>
                <div className="content">
                  <div className='item'>{formatMoney(record.AI_Price)}</div>
                </div>
                <div className="label">折扣比例</div>
                <div className="content">
                  <div className='item'>{record.discount || ''}%</div>
                </div>
                <div className="label">实收金额</div>
                <div className="content">
                  <div className='item'>{formatMoney(record.AI_AmountReceivable)}</div>
                </div>
            </div>
            <div className="row"> 
                
                <div className="label">次数</div>
                <div className="content">
                  <div className='item'>{record.AI_PublishDayCount || ''}</div>
                </div>
                <div className="label">颜色</div>
                <div className="content">
                  <div className='item'>{record.AI_Color || ''}</div>
                </div>
                <div className="label">收款方式</div>
                <div className="content">
                  <div className='item'>{record.AI_PayMode || ''}</div>
                </div>
            </div>
        
            {/* 第八行：协助人员 */}
            <div className="row">
              <div className="label">收款时间</div>
                <div className="content">
                  <div className='item'>{record.paytime || ''}</div>
                </div>
              <div className="label">协助部门</div>
              <div className="content">
                <div className='item col' >
          
                  <div>{record.assistantdepartmentname || ''}</div>
                  
                </div>
                
              </div>
              <div className="label">业务员</div>
              <div className="content">
                <div className='item col' >
                  <div>{record.AI_Salesman || ''}</div>
                  <div>{record.salesmandepartmentname||record.departmentname || ''}</div>
                  
                </div>
                
              </div>

            </div>
            <div className="row">
              <div className="label">备注</div>
                <div className="content">
                  <div className='item' style={{ height: 'auto', minHeight: '35px', alignItems: 'flex-start',justifyContent:'flex-start', padding: '10px' }}>{record.AI_Memo || ''}</div>
                </div>
            </div>
          </div>

          {/* 签名区域 */}
          <div className="row" style={{ minHeight:'35px',width: '100%', border: 'none', display: 'flex' }}>
            <Usersigns datas={record.approvers} ></Usersigns>
          </div>
        </div>
      </div>
  }

  return (
    <div ref={printRef} >
      <Button type="primary" size="large" className="no-print" onClick={printIframe}>
        打印页面
      </Button>

      <div id="print" key={rk}>
        {
          data.map((e:any, index:number)=>{
            // 每两个广告一组，组内不分页，组间分页
            return <div key={index} style={{ pageBreakAfter: (index + 1) % 2 === 0 ? 'auto' : 'always' }}>{element(e)}</div>
          })
        }
      </div>

      
    </div>
  );
};

export default PrintAddFzAdvPage;