
<template>
  <Image
    id="printArea"
    style="min-width: 100%;margin-top: 10px;"
    :src="src"
    @click="preview"
  />
  <div style="display:flex;justify-content:center;" v-if="!isApp">
    <Button type="primary" style="margin-right: 10px;" size="small" @click="triggerPrint">打印</Button>
    <Button type="default" size="small" @click="close">关闭</Button>
  </div>
  <p style="display:flex;justify-content:center;color:gray;font-weight: bold;font-size: 25px;" v-if="isApp" >长按审批单保存</p>
  <div id="printf" ref="printf" :style="{width: '210mm', height: '156.8mm',margin:'0 0 0 0',border:'2px solid white' }"  v-for="(e,index) in data" >
    <div class="container" :style="{width: '200mm', height: '156.8mm',margin:'0 20px',padding:'0 10px 0 10px' }">
    <div class="print-content">
      <div class="row" :style="{width:'100%',border:'none',display:'flex',justifyContent:'center',fontSize:'25px',fontWeight:'bold'}">
        <div class="title">签 付 印 延 迟 审 批 单</div>
      </div>
      <div class="row" :style="{border:'none',padding:0,minHeight:'30px',fontSize:'16px'}">
        <div >部门：</div>
        <div >{{e.data.department}}</div>
        <div :style="{flexGrow:1,display:'flex',justifyContent:'flex-end',marginRight:'20px'}">
          <div :style="{textAlign:'right',paddingRight:'5px'}">{{e.data?.inserttime?.substr(0,10)}}</div>
          <div>单号：{{e.data?.thirdNo}}</div>
        </div>
        
      </div>
      
    
      <div class="pbox" >
    
          <div class="row" >
            <div class="label">日期</div>
            <div class="content">
              <div class="item2" style="display:flex;align-items:center;">{{e.data?.date.substr(0,10)}}</div>
            </div>

            <div class="label" style="border-left: 1px solid black;border-right: 1px solid black;">传版延误</div>
   
             <div  class="content">
              <div class="item2" style="display:flex;align-items:center;">{{e.data?.time||e.data?.date.substr(10)}}</div>
            </div>
   
          </div>

          <div class="row" >
            <div class="label">版面</div>
            <div class="content">
              <div class="item2" style="display:flex;align-items:center;">{{e.data?.layout}}</div>
            </div>
          </div>
        

          <div  class="row" >
            <div class="label">延误说明</div>
            <div class="content">
              <div class="item2" style="display:flex;align-items:center;">{{e.data?.reason}}</div>
            </div>
          </div>


          <div style="display: flex;flex-direction: row;border-bottom: 1px solid black;" >

            <div class="roleRow" style="width: 33.33%;">
              <div class="role">值班人员</div>
              <div class="sign">
                  <div  class="row">
                    <span style="margin-right: 5px;">{{e.data.userName}}</span>
                    <span>{{ e.data.inserttime.substr(5,5) }}</span>
                  </div>
                  
              </div>
            </div>
            <div class="roleRow" style="width: 33.33%;">
              <div class="role">值班主任</div>
              <div class="sign">
                  <div v-if="e.flowdata[3]" class="row">
                    <span style="margin-right: 5px;">{{e.flowdata[3].title}}</span>
                    <span>{{ e.flowdata[3].date }}</span>
                  </div>
                  
              </div>
            </div>

            <div class="roleRow" style="width: 33.33%;;">
              <div class="role">值班领导</div>
              <div class="sign">
                  <div v-if="e.flowdata[4]"  class="row">
                      <span style="margin-right: 5px;">{{e.flowdata[4].title}}</span>
                      <span>{{ e.flowdata[4].date }}</span>
                    </div>
                  
                  
              </div>
            </div>

     
            

            
          </div>

          <div style="display: flex;flex-direction: row;border-bottom: 1px solid black;" >

            <div class="roleRow" style="width: 33.33%;">
              <div class="role">常务副总编</div>
              <div class="sign">
                  <div v-if="e.flowdata[0]"  class="row">
                      <span style="margin-right: 5px;">{{e.flowdata[0].title}}</span>
                      <span>{{ e.flowdata[0].date }}</span>
                    </div>
              </div>
            </div>
            <div  class="roleRow" style="width: 33.33%;">
              <div class="role">总编辑</div>
              <div class="sign">
                  <div v-if="e.flowdata[1]" class="row">
                    <span style="margin-right: 5px;">{{e.flowdata[1].title}}</span>
                    <span>{{ e.flowdata[1].date }}</span>
                  </div>
                  
              </div>
            </div>
            <div  class="roleRow" style="width: 33.33%;">
              <div class="role">社长</div>
              <div class="sign">
                  <div v-if="e.flowdata[2]" class="row">
                    <span style="margin-right: 5px;">{{e.flowdata[2].title}}</span>
                    <span>{{ e.flowdata[2].date }}</span>
                  </div>
                  
              </div>
            </div>
    

     
            

            
          </div>

        

          

      </div>

       
      <div style="margin-top: 10px;">{{ e.data.notice }}</div>
      
    
      
    </div>

  </div>
  </div>
</template>
<script  lang="ts">
import {Image,Button,showImagePreview} from 'vant';
import './printpress.css'
import html2canvas from 'html2canvas';
import { viewpic } from './press';
import { appEnv } from '@/utils/common';
  export default {
    components: {Image,Button,
    },
    props:['thirdNo'],
    data () {
      return {
        data:<any>[],
        src:'',
        htmlContent:'<p>这是一个 <strong>HTML 内容</strong></p>',
        isApp:appEnv(),
      }
    },
    watch:{

    },
    mounted() {
  
 
      if (this.thirdNo){
        viewpic({thirdNo:this.thirdNo}).then((res:any)=>{
          if (res.errorMessage){

          }else{
            

            this.data = [res]
            setTimeout(()=>{
              this.convertToImage()
            },100)
          }

          
        })
      }else{
        this.data = [{data:{}}]
      }
    },
    created() {
    },
    methods:{
      close(){
        this.$emit('close')
      },
      preview(){
        if(this.src){
          showImagePreview([this.src]);
        }
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
        const element:any = document.getElementById('printf')
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
