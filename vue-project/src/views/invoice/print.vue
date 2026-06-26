
<template>
  <Image
    id="printArea"
    style="min-width: 100%"
    :src="src"
  />
  <div style="display:flex;justify-content:center;"><Button type="primary" @click="triggerPrint">打印</Button></div>
  <p style="display:flex;justify-content:center;color:gray;">手机先右键保存图片，然后再打印</p>
  <div id="print" ref="print" :style="{width: '210mm', height: '156.8mm',margin:'0 0 0 0',border:'2px solid white' }"  v-for="(e,index) in data">
    <div class="container" :style="{width: '200mm', height: '156.8mm',margin:'0 20px',padding:'0 10px 0 10px' }">
    <div class="print-content">
      <div class="row" :style="{width:'100%',border:'none',display:'flex',justifyContent:'center',fontSize:'25px',fontWeight:'bold'}">
        <div class="title">开票申请单{{ e.state==InvoicingStatesEnum.DELETEED?'（作废）':'' }}</div>
      </div>
      <div class="row" :style="{border:'none',padding:0,minHeight:'30px',fontSize:'16px'}">
        <div >开票单位（销售方）：</div>
        <div >{{e.partbname}}</div>
        <div :style="{flexGrow:1,display:'flex',justifyContent:'flex-end',marginRight:'20px'}">
          <div :style="{textAlign:'right',paddingRight:'5px'}">
            <span style="padding-right: 5px;">{{e.businesstype||''}}</span>
            <span>{{e.type==1?'专票':'普票'}}</span>
          </div>


          
        </div>
          <div>单号：{{e.thirdNo}}</div>
        </div>
        
      </div>
      
    
      <div class="pbox" >
          <div class="row"  >
       
            <div class="contentBox" :style="{flex:1.5,border:'none'}">
               <div class="label2">
               发票抬头
               </div>
               <div class="content2">
               {{e.partaname}}
               </div>
            </div>

            <div class="contentBox">
               <div class="label2">
               纳税人识别号
               </div>
               <div class="content2">
               {{e.bueryid}}
               </div>
            </div>

            <div class="contentBox">
               <div class="label2">
               开票金额
               </div>
               <div class="content2">
               {{e.amount.toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,})}}
               </div>
            </div>

            <div class="contentBox">
               <div class="label2">
               申请日期
               </div>
               <div class="content2">
                {{e.date}}
               </div>
            </div>

            <div class="contentBox">
               <div class="label2">
               经办人
               </div>
               <div class="content2" :style="{flex:2}">
                 <div class="col" :style="{fontSize:'15px'}">
                   <div>{{e.department}}</div>
                   <div>{{e.name}}</div>
                 </div>
               </div>
            </div>

          </div>

          <div class="row">
            <div class="label">开票内容</div>
            <div class="content" style="padding-left: 5px;border-left: 1px solid black;">
              <div v-html="e.title"></div>
            </div>
          </div>

          <div class="row" >
            <div class="label">开票备注</div>
            <div class="content">
              <div class="item2" style="display:flex;align-items:center;">{{e.content}}</div>
            </div>
          </div>

          <div class="row">
            
            <div class="content">
              <div class="col">
                <div class="col" :style="{padding:'5px'}">
                    
                      
                      <div class="item5"><span :style="{fontWeight:'bold'}">合同：</span>{{e.contractnames?e.contractnames:'无合同'}}</div>
                    
                    
                    <div class="item5" v-if="e.projectnames"><span :style="{fontWeight:'bold'}">项目：</span>{{e.projectnames?e.projectnames:'未关联'}}</div>
                    
                    <div class="item5" v-if="e.othercontent"><span :style="{fontWeight:'bold'}">其他说明：</span>{{e.othercontent}}</div>
                    <div class="item5" v-if="e.orders"><span :style="{fontWeight:'bold'}">广告单：</span>{{e.orders}}</div>
        
                    <div class="item5" v-if="e.type==1"><span :style="{fontWeight:'bold'}">客户信息：</span>{{e.buyerinfo?e.buyerinfo:'暂无'}}</div>
                    
                    
                </div>


              </div>
            </div>
            
          </div>

      </div>

       
       <div v-if="e.state!=InvoicingStatesEnum.DELETEED" :style="{border:'none',marginTop:0,paddingTop:0}">
        <Usersigns :datas="e.approvers"></Usersigns>
       </div>
       <div v-if="e.state==InvoicingStatesEnum.DELETEED" :style="{border:'none',marginTop:0,paddingTop:0}">
        {{ e.deldate?('作废日期：'+e.deldate.substring(0,10)):'' }}
       </div>
      
    </div>

  </div>
 
</template>
<script  lang="ts">
import {Image,Button} from 'vant';
import './printInvoice.css'
import { getprintinfo } from './invoice';
import html2canvas from 'html2canvas';
import Usersigns  from './components/Usersigns.vue';
import { InvoicingStatesEnum } from './invoicing_config';
  export default {
    components: {Image,Button,Usersigns
    },
    props:['ids'],
    data () {
      return {
        InvoicingStatesEnum:InvoicingStatesEnum,
        data:<any>[],
        src:'',
        htmlContent:'<p>这是一个 <strong>HTML 内容</strong></p>'
      }
    },
    watch:{

    },
    mounted() {
  
 
      if (this.ids){
        getprintinfo({ids:this.ids}).then((res:any)=>{
          if (res.errorMessage){

          }else{
            this.data = res.data||[]
            setTimeout(()=>{
              this.convertToImage()
            },200)
          }

          
        })
      }
    },
    created() {
    },
    methods:{
      transform(title:any,index:any){
        var result = ''
        if(title&&title.split){
          var temp = title.split(',')
          result = temp.map((item:any)=>{
            var tt = item.split(':')
            return `<span >${tt[0]}：</span><span style="font-weight:bold,margin-right:10px">${parseFloat(tt[1]).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            })}</span>`
          }).join('')
        }
        
        return result
      },
      triggerPrint () {
        const content:any = document.getElementById('printArea')?.innerHTML
        const printWindow = window.open('', '', 'height=600,width=800')

        if (!printWindow) return

        printWindow.document.write(`
          <html>
            <head>
              <title>打印图片</title>
              <style>
                body {
                  margin: 20px;
                  font-family: Arial, sans-serif;
                }

                img {
                  max-width: 100%;
                  height: auto;
                }

                @media print {
                  body {
                    margin: 0;
                    padding: 10px;
                  }

                  .no-print {
                    display: none !important;
                  }

                  @page {
                    size: A4;
                    margin: 10mm;
                  }
                }
              </style>
            </head>
            <body>
              ${content}
            </body>
          </html>
        `)

        printWindow.document.close()
        printWindow.focus()

        setTimeout(() => {
          printWindow.print()
          printWindow.close()
        }, 500)
      },
      convertToImage(){
        const element:any = document.getElementById('print')
        const { width, height } = element.getBoundingClientRect();
        console.log(width,height)
        html2canvas(element, {
          scrollY: -window.scrollY, // 处理页面整体滚动偏移
          scrollX: 0,
          windowHeight: height,
          windowWidth: width,
          scale: 2 // 提高清晰度（默认是屏幕像素比）
        }).then(canvas => {
            // 将 canvas 转换为 base64 图片地址
            const image = canvas.toDataURL('image/png');
            this.src = image;
            element.style.display = 'none';
          });
      }
      
    }
  }
</script>
<style  lang="css">


  /* .box{
    width: 100%;
    min-height: 100vh;
    border-right: 2px solid #eff2f5;
    border-left: 2px solid #eff2f5;
    margin-left:0 ;
    margin-top: 0;
  } */

  .scroll-container {
    width: 400px;
    max-height: 200px;
    overflow: auto;
    border: 1px solid #ccc;
    padding: 10px;
    background-color: #f9f9f9;
  }

  
</style>

<style>
  @media screen and (min-width: 500px) {

  }
</style>
