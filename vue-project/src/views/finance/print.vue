
<template>
  <div style="display:flex;justify-content:center;">
    <Button type="primary" style="margin-right: 10px;height: 30px;" size="normal" @click="triggerPrint" v-if="!isApp">打印</Button>
    <Button type="success" style="margin-right: 10px;height: 30px;" size="normal" @click="setPosition">设置</Button>
    <Button type="default" style="margin-right: 10px;height: 30px;" size="normal" @click="close">关闭</Button>
    
  </div>
  <p style="display:flex;justify-content:center;color:gray;" v-if="isApp">长按审批单保存</p>
  <Image
    id="printArea"
    style="min-width: 100%;margin-top: 10px;"
    :src="src"
    @click="preview"
  />
  
  
  <div :key="pkey" v-if="data.flowdata" id="printf" ref="printf" :style="{width: '210mm',height: '297mm',margin:'0 0 0 0',border:'2px solid white' }"  >
    <div class="container" :style="{width: '200mm',margin:'0 20px',padding:'0 10px 0 10px' }">
    <div class="print-content">
      <div class="row" :style="{width:'100%',border:'none',display:'flex',justifyContent:'center',fontSize:'25px',fontWeight:'bold'}">
        <div class="title">付款审批单</div>
      </div>
      <div class="row" :style="{border:'none',padding:0,minHeight:'30px',fontSize:'16px',color: 'black!important'}">
        <div >部门：</div>
        <div >{{data?.data?.department}}</div>
        <div :style="{flexGrow:1,display:'flex',justifyContent:'flex-end',marginRight:'20px'}">
          <div :style="{textAlign:'right',paddingRight:'5px'}">{{data.data?.inserttime?.substr(0,10)}}</div>
          <div>单号：{{data.data?.thirdNo}}</div>
        </div>
        
      </div>
      
    
      <div class="pbox" >
    
    

          <div class="row" >
            <div class="label">{{ isSalary?'申请部门':'付款单位' }}</div>
            <div class="content">
              <div class="item2" style="display:flex;align-items:center;">{{data.data?.payer}}</div>
            </div>
          </div>
          <div class="row" >
            <div class="label">{{ isSalary?'工资月份':'收款单位或个人' }}</div>
            <div class="content">
              <div class="item2" style="display:flex;align-items:center;">{{data.data?.receiver}}</div>
            </div>

            <div v-if="!isSalary" class="label" style="border-left: 1px solid black;border-right: 1px solid black;">付款方式</div>
            <div v-if="!isSalary" class="label">{{data.data?.payStatusName}}</div>

            <div v-if="isSalary" class="label" style="border-left: 1px solid black;border-right: 1px solid black;">工资项目</div>
   
             <div v-if="isSalary" class="content">
              <div class="item2" style="display:flex;align-items:center;">{{data.data?.reason}}</div>
            </div>
   
          </div>

    
          <div v-if="!isSalary" class="row" >
            <div class="label">收款账号</div>
            <div class="content">
              <div class="item2" style="display:flex;align-items:center;">{{data.data?.account}}</div>
            </div>
            <div class="label" style="height: 100%;border-left: 1px solid black;border-right: 1px solid black;">开户银行</div>
            <div class="label">{{data.data?.bank}}</div>
   
          </div>


          <div class="row" >
            <div class="label">{{isSalary?'应发金额':'付款金额'}}</div>
            <div class="content">
              <div class="item2" style="display:flex;align-items:center;">
                人民币：<span style="flex-grow: 1;">{{data.data?.amountCap}}</span><span>￥{{ data.data?.amount }}</span>
              </div>
            </div>
          </div>
          <div v-if="isSalary" class="row" >
            <div class="label">实发金额</div>
            <div class="content">
              <div class="item2" style="display:flex;align-items:center;">
                人民币：<span style="flex-grow: 1;">{{data.data?.amountrealCap}}</span><span>￥{{ data.data?.amountreal }}</span>
              </div>
            </div>
          </div>
          <div v-if="!isSalary" class="row" >
            <div class="label">付款事项</div>
            <div class="content">
              <div  style="display:flex;align-items:center;border-left:1px solid black;padding-left:5px;color: black!important;">{{data.data?.reason}}</div>
            </div>
          </div>


          <div style="display: flex;flex-direction: row;border-bottom: 1px solid black;" >

            <div class="roleRow" style="width: 33.33%;">
              <div class="role">社长审批</div>
              <div class="sign">
                  
                  <div v-if="data.flowdata[6]" class="roleRow">
                    <Signname  :data="data.flowdata[6]"/>
                  </div>
                  
              </div>
            </div>
            <div class="roleRow" style="width: 33.33%;">
              <div class="role">分管领导</div>
              <div class="sign">
                  <div v-if="data.flowdata[5]" class="roleRow">
                    <Signname  :data="data.flowdata[5]"/>
                  </div>
                  
              </div>
            </div>

            <div class="roleRow" style="width: 33.33%;;">
              <div class="role">公司/部门负责人审批</div>
              <div class="sign">
                  <div class="col">
                    <div v-if="data.flowdata[1]"  class="roleRow">
                      <Signname  :data="data.flowdata[1]"/>
                    </div>
                    <div v-if="data.flowdata[2]"  class="roleRow">
                      <Signname  :data="data.flowdata[2]"/>
                    </div>
                    <div v-if="data.flowdata[9]"  class="roleRow">
                      <Signname  :data="data.flowdata[9]"/>
                    </div>

                  </div>
                  
                  
              </div>
            </div>

     
            

            
          </div>

          <div style="display: flex;flex-direction: row;border-bottom: 1px solid black;" >

            <div class="roleRow" style="width: 33.33%;">
              <div class="role">财务审核</div>
              <div class="sign">
                  <div class="col">
                    <Signname  class="roleRow" :data="data.flowdata[4]"/>
                    <Signname  class="roleRow" :data="data.flowdata[3]"/>
                    <Signname  class="roleRow" :data="data.flowdata[10]"/>
                  </div>
              </div>
            </div>
            <div v-if="!isSalary" class="roleRow" style="width: 33.33%;">
              <div class="role">报批部门主管审核</div>
              <div class="sign">
                  <div v-if="data.flowdata[0]" class="row">
                    <Signname  :data="data.flowdata[0]"/>
                  </div>
                  
              </div>
            </div>
            <div v-if="isSalary" class="roleRow" style="width: 33.33%;;">
              <div class="role" style="display: flex;flex-direction: column;">
                <p>人事主管</p>
               
                <p>人事经办</p>
              </div>
              <div class="sign">
                  <div class="col">
                    <div v-if="data.flowdata[7]"  class="roleRow">
                      <Signname  :data="data.flowdata[7]"/>
                    </div>
                    <div v-if="data.flowdata[8]"  class="roleRow">
                      <Signname  :data="data.flowdata[8]"/>
                    </div>
      

                  </div>
                  
                  
              </div>
            </div>
            <div  v-if="!isSalary" class="roleRow" style="width: 33.33%;;">
              <div class="role" style="display: flex;flex-direction: column;color: black!important;">
                <p>经办人</p>
               
                <p>证明人</p>
              </div>
              <div class="sign">
                  <div class="col">
                    <div   class="roleRow">
                      <Signname  :data="{title:data.data.userName}"/>
                    </div>
                    <div v-if="data.data.certifiers"  class="roleRow">
                      <Signname  :data="{title:data.data.certifiers}"/>

                    </div>

                  </div>
                  
                  
              </div>
            </div>

            <div v-if="isSalary" class="roleRow" style="width: 33.33%;;">
              <div class="role" style="display: flex;flex-direction: column;">
                <p>主管审核</p>
               
                <p>经办人</p>
              </div>
              <div class="sign">
                  <div class="col">
                    <div v-if="data.flowdata[0]"  class="roleRow">
                      <Signname  :data="data.flowdata[0]"/>
                    </div>
                    <div  class="roleRow" style="text-align: center;">
                      <span style="margin-left:12px">{{data.data.userName}}</span>
                    </div>
      

                  </div>
                  
                  
              </div>
            </div>

     
            

            
          </div>



          

      
      
        </div>
          <div style="display: flex;flex-direction: column;" v-if="data?.speeches" v-for="r in data?.speeches">
            <div class="memo">{{ r }}</div>
          </div>
       
    
      
    </div>

  </div>
  
  </div>
  <ActionSheet v-model:show="visible" title="修改打印位置" show-cancel-button style="min-height: 50vh;">
      <Form @submit="onChangePosition" required="auto">
        <Field name="type" label="审批人" required>
            <template #input>
              <RadioGroup v-model="obj.step" direction="horizontal">
                <Radio v-for="(ele,index) in approverNames" :name="index" >{{ ele }}</Radio>
        
              </RadioGroup>
            </template>
          </Field>
        <Field
            readonly
            required
            label="打印位置"
          />
          <Dict_Radio type="付款审批打印位置"  order="value asc"   @update:value="(val:any)=>obj.position=val"/>
          <div style="margin: 16px;">
        
        <Button  round block type="primary" native-type="submit" style="font-size: calc(var(--van-cell-font-size)*var(--Big));">
          确定
        </Button>

      </div>
      </Form>
      
  </ActionSheet>
</template>
<script  lang="ts">
import {Image,Button,showImagePreview,ActionSheet,Form,Field,RadioGroup,Radio,showDialog} from 'vant';
import './printfinance.css'
import html2canvas from 'html2canvas';
import { setposition, viewpic } from './finance';
import { FinanaceType } from './finance_config';
import { appEnv } from '@/utils/common';
import Signname from './components/signname.vue';
import Dict_Radio from '../invoice/components/Dict_Radio.vue';
  export default {
    components: {Image,Button,Signname,ActionSheet,Dict_Radio,Form,Field,RadioGroup,Radio
    },
    props:['thirdNo'],
    data () {
      return {
        data:<any>{},
        FinanaceType:FinanaceType,
        src:'',
        htmlContent:'<p>这是一个 <strong>HTML 内容</strong></p>',
        isSalary:false,
        isApp:appEnv(),
        visible:false,
        obj:<any>{},
        approverNames:[],
        pkey:1
      }
    },
    watch:{

    },
    mounted() {
  
      this.getData()
      
    },
    created() {
    },
    
    methods:{
      getData(){
        
        if (this.thirdNo){
          
          viewpic({thirdNo:this.thirdNo}).then((res:any)=>{
            if (res.errorMessage){

            }else{
              
              console.log('res:',res)
              this.isSalary = [FinanaceType.SALARY,FinanaceType.SALARY_CROSS].some(item=>item==res.data?.amountsType)
              this.data = res||{data:{}}
              this.obj.thirdNo = res.data?.thirdNo
              this.approverNames = res.approverNames||[]
              setTimeout(()=>{
                this.convertToImage()
              },100)
            }

            
          })
        }else{
          this.data = {data:{}}
        }
      },
      close(){
        this.$emit('close')
      },
      setPosition(){
        this.visible=true
      },
      onChangePosition(){
        if(this.obj.step==undefined){
          showDialog({'message':'请选择需要修改打印位置的审批人'})
          return
        }
        
        setposition(this.obj).then((res:any)=>{
          if (res.errorMessage){
            showDialog({'message':res.errorMessage})
          }else{
            this.$emit('change')
            this.visible=false
          }
        })
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
            // element.style.display = 'none';
          });
      }
      
    }
  }
</script>
<style  lang="css">


  .memo{
      font-size: var(--van-cell-font-size);
      font-family: "微软雅黑";
      color: black;
      padding-top: calc(var(--van-cell-vertical-padding)*0.2);
  }

  .scroll-container {
    width: 400px;
    max-height: 200px;
    overflow: auto;
    border: 1px solid #ccc;
    padding: 10px;
    background-color: #f9f9f9;
  }

  
</style>

