import { Button } from 'antd';
import { useModel } from 'umi';
import './print.css';
import { useEffect, useRef, useState } from 'react';
import moment from 'moment';

const PrintSmallBusiness: React.FC<{ records?: any;  }> = ({ records }) => {
  const printRef = useRef<HTMLDivElement>(null);
  const { initialState } = useModel('@@initialState');
  const currentUser = initialState?.currentUser;
  const [data, setData] = useState<any[]>([]);
  const [rk,setRk]=useState(0)

  // 如果传入records数组，直接使用；如果传入单个record，转换为数组
  useEffect(() => {
    setData(records||[]);
    setRk(rk+1)
  }, [records]);

  // Format date function
  const formatDate = (date: string | undefined) => {
    return date ? moment(date).format('YYYY-MM-DD') : '';
  };

  // Format money value
  const formatMoney = (value: number | string | undefined) => {
    if (value === undefined || value === null) return '';
    return typeof value === 'number' ? value.toFixed(2) : String(value);
  };

  // Parse customer info from string format (name-phone-idnumber)
  const parseCustomerInfo = (value: string | undefined) => {
    if (!value) return { name: '', phone: '', idnumber: '' };
    const parts = value.split('-');
    return {
      name: parts[0] || '',
      phone: parts[1] || '',
      idnumber: parts[2] || ''
    };
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
  --printRowMinHeight: 30px;
  --fontSize: 16px;
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
  aspect-ratio: 210/297;
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
    const customerInfo = parseCustomerInfo(record.customerinfo);
    return ( 
      <div className="container">
        <div className="print-content">
          {/* Title */}
          <div className="row" style={{ width: '100%', border: 'none', display: 'flex', justifyContent: 'center', fontSize: '25px', fontWeight: 'bold' }}>
            <div className="title">福州日报社小额业务确认单</div>
          </div>
          
          {/* Sign date on top right */}
          <div className="row" style={{ border: 'none', padding: 0, minHeight: '30px', fontSize: '16px', justifyContent: 'flex-end' }}>
            <div style={{ textAlign: 'right', paddingRight: '20px' }}>
              <span>签订日期：</span>
              <span>{formatDate(record.SYS_CREATED)}</span>
            </div>
            {
              record.issueno!=null &&
              <div style={{ textAlign: 'right' }}>
                <span>广告期数：</span>
                <span>{record.issueno}</span>
              </div>
            }
          </div>
          
          {/* Main content box */}
          <div className="box">
            {/* Row 1: Customer and Main Entity */}
            <div className="row">
              <div className="label">客户</div>
              <div className="content">
                <div className='item' style={{padding:'5px','textAlign':'center'}}>{record.AI_Customer || ''}</div>
              </div>
              <div className="label">主体</div>
              <div className="content">
                <div className='item' style={{padding:'5px','textAlign':'center'}}>{record.partbname || ''}</div>
              </div>
            </div>



            {/* Row 2: Business Name */}
            <div className="row">
              <div className="label">业务名称</div>
              <div className="content" >
                <div className='item'>{record.AI_Trade || ''}</div>
              </div>
              <div className="label">发布平台</div>
              <div className="content">
                <div className='item'>{record.AI_Publication || ''}</div>
              </div>
            </div>

       

            {/* Row 4: Cooperation Time */}
            <div className="row">
              <div className="label">合作时间</div>
              <div className="content">
                  <div  className='item' style={{ height: 'auto', minHeight: '35px', alignItems: 'flex-start',justifyContent:'flex-start', padding: '5px' }}>{record.times||(formatDate(record.AI_PublishTime)+'至'+formatDate(record.AI_PublishEndTime))}</div>
                </div>
              <div className="label">收款方式</div>
              <div className="content">
                <div className='item'>{record.AI_PayMode || ''}</div>
              </div>
            </div>
            <div className="row">
              <div className="label">媒体投放</div>
                <div className="content">
                  <div  className='item' style={{ height: 'auto', minHeight: '35px', alignItems: 'flex-start',justifyContent:'flex-start', padding: '5px' }}>{record.media}</div>
                </div>
            </div>

            {/* Row 5: Cooperation Content (multi-line) */}
            <div className="row">
              <div className="label" style={{ height: '100px', alignItems: 'flex-start', paddingTop: '5px' }}>合作内容</div>
              <div className="content" style={{ flex: '2' }}>
                <div className='item' style={{ display:'flex',flexDirection:'column',height: '100%', minHeight: '100px', alignItems: 'flex-start',justifyContent: 'flex-start', padding: '5px' }}>
                  <div style={{width:'100%'}}>{record.AI_Content || ''}</div>
                  <div style={{ whiteSpace: 'pre-line' }} dangerouslySetInnerHTML={{ __html: (record.content || '').replace(/\n/g, '<br>') }} />
                </div>
              </div>
            </div>

            {/* Row 6: Amount */}
            <div className="row">
              <div className="label">金额</div>
              <div className="content">
                <div className='item'>¥ {formatMoney(record.AI_AmountReceivable)}</div>
              </div>
              <div className="label">（大写）</div>
              <div className="content">
                <div className='item'>{record.AI_AmountReceivable_Cap}</div>
              </div>
            </div>

 
          </div>

          {/* Notes section */}
          <div className="box" style={{ borderTop: 'none', marginTop: '0',fontSize:'14px' }}>
            <div className="row">
              <div className="content" style={{ flex: '1' }}>
                <div className='item col' style={{ height: 'auto', minHeight: 'auto', alignItems: 'flex-start', padding: '5px' }}>
                  <div>备注：确认单仅适用于金额 1 万元以内的刊前付款小额业务。</div>
                  <div>申明：</div>
                  <div>1.本确认单具备与合同同等的法律效力。</div>
                  <div>2.广告刊户所投放的广告必须符合国家广告法及新闻管理部门的有关规定，维护知
        识产权。若违反国家相关法规，一切经济责任与法律责任由广告刊户自行承担。</div>
                  <div>3.遇重大事件（如国家政策等）及不可抗力因素，广告刊出时间由报社统一安排。
        广告如在约定的两日内刊出，广告经营单位不另行通知刊户。 </div>
                  <div>4.双方共同遵守刊登媒体广告刊例的所有内容，特殊情况应在确认单中说明。 </div>
                  <div>5.经刊户（签字人）仔细核对确认，以上刊登内容准确无误、真实可信，如有不
        实，刊户本人愿承担由此产生的一切法律责任。</div>
                  <div>6.见报后如有更正，由刊户本人自行承担费用。</div>
                  <div>7.报纸可邮寄，免费赠送刊户报纸一份，邮费自行承担（快递顺丰到付）。</div>
                  <div style={{ marginTop: '10px' }}>其他：<span style={{borderBottom:'1px solid #000',marginBottom:'5px'}}>{record.AI_Memo||''}</span></div>
                </div>
              </div>
            </div>
          </div>

          {/* Signature section */}
          <div className="row" style={{ width: '100%', border: 'none', display: 'flex', justifyContent: 'space-between', marginTop: '0px' }}>
            <div className="row" style={{ width: '30%',border: 'none' }}>
              <div>业务经办人：</div>
              <div>{record.AI_Salesman }</div>
            </div>
            <div className="row" style={{ width: '30%',border: 'none' }}>
              <div>经办电话：</div>
              <div>{record.salemantel || ''}</div>
            </div>
            <div className="row" style={{ width: '30%',border: 'none' }}>
              <div>财务收款人：</div>
              <div></div>
            </div>
          </div>
          <div className="row" style={{ width: '100%', border: 'none', display: 'flex', justifyContent: 'space-between', marginTop: '0px' }}>
            <div className="row" style={{ width: '30%',border: 'none' }}>
              <div>客 户 签 字：</div>
              <div></div>
            </div>
            <div className="row" style={{ width: '30%',border: 'none' }}>
              <div>客户电话：</div>
              <div>{customerInfo.phone || record.phone || ''}</div>
            </div>
            <div className="row" style={{ width: '30%',border: 'none' }}>
              <div>客户身份证：</div>
              <div>{customerInfo.idnumber || ''}</div>
            </div>
          </div>
       
        </div>
      </div>
    )
  }

  return (
    <div ref={printRef} >
      <Button type="primary" size="large" className="no-print" onClick={printIframe}>
        打印页面
      </Button>
      <div id="print" key={rk}>
        {
          data.map((e:any, index:number)=>{
            return <div key={index} style={{ pageBreakAfter: 'always' }}>{element(e)}</div>
          })
        }
      </div>
      
    </div>
  );
};

export default PrintSmallBusiness;
