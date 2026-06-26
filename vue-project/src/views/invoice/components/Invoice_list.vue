<template>
  <div  class="cell">
    <span class="label" >XML发票：</span>
    <span class="value" style="color:black" v-if="!options.length">未上传</span>
  </div>
 
  <Cell style="color: #1989fa;" v-for="item in options" :title="item.EIid" :value="'￥'+item.TotalTaxIncludedAmount" :label="item.RequestTime" @click="view(item)">
    <template #right-icon>
      <span @click.stop="download(item)">下载</span>
    </template>
  </Cell>

  <Popup v-model:show="visible" round position="bottom">
    <CellGroup >
      <CopyableCell v-if="invoice?.publication" label-class="label" title="媒体" :value="invoice?.publication+(invoice?.subpublication?'-'+invoice?.subpublication:'')" />
      <CopyableCell label-class="label" title="发票号" :value="invoice?.EIid" />
      <CopyableCell title="发票类别" :value="invoice?.GeneralOrSpecialVAT"  />
      <CopyableCell label-class="label" title="开票日期" :value="invoice?.RequestTime" />
      <CopyableCell label-class="label" title="销售方名称" :value="invoice?.SellerName" />
      <CopyableCell label-class="label" title="购买方名称" :value="invoice?.BuyerName" />
      <CopyableCell label-class="label" title="含税开票金额" :value="invoice?.TotalTaxIncludedAmount" />
      <CopyableCell label-class="label" title="开票备注" :value="invoice?.Remark" />
    </CellGroup>
    
    <Filescard :urls="invoice?.fileurls"/>
    <Button  type="danger" plain  block @click="del(invoice.id)" style="width:93%;margin:0 10px">删除</Button>
    
  </Popup>
  
</template>
<script  lang="ts">

  import { Cell,Icon,Popup,Button,showConfirmDialog,CellGroup,showToast,showDialog } from 'vant';
  import { delinvoice, getinvoicelist } from '../invoice';
  import CopyableCell from './Copyable_Cell.vue'
import Filescard from '@/views/budget/components/filescard.vue';
import { appEnv } from '@/utils/common';
import { transfileurl } from '@/views/budget/budget';
import { extractParameterFromUrl } from '@/views/budget/utils';
import { previewFile } from '@/api/config';
  
  export default {
    components: {Cell,Icon,Popup,CopyableCell,Filescard,Button,showConfirmDialog,CellGroup},
    props: ['invoicingid'],
    data () {
      return {
        options:<any>[],
        hasload:false,
        invoice:<any>{},
        visible:false,
        fileurls:'',
      }
    },
    watch:{
      invoicingid(val){
        if (val&&!this.hasload){
          getinvoicelist({invoicingid:val,pageSize:50}).then((res:any)=>{
            this.hasload = true
            if(res.data){
              this.options = res.data
              
            }

          })

        }
      }
    
    },
    mounted() {
     
      if (this.invoicingid&&!this.hasload){
        getinvoicelist({invoicingid:this.invoicingid,pageSize:50}).then((res:any)=>{
          this.hasload=true
          if(res.data){
            this.options = res.data
          }

        })

      }
      
    },
    created() {

    },
    methods:{
     view(item:any){
      this.invoice = item
      this.visible=true
     },
     del(id:any){
      showConfirmDialog({
        title: '确定要删除吗？',
      })
      .then(() => {
        delinvoice({id}).then((res:any)=>{
          if (res.errorMessage){}else{
            this.visible=false
            this.$emit('suc')
          }
        })
      })
      .catch(() => {
      });
      
     },
     async download(item:any){
    
        var url = item.fileurls
        if (item.downloading){
          showToast({message:'下载中..'})
          return
        }
        item.downloading = true

       

        if (appEnv()){
          console.log('is appEnv')
          transfileurl({url}).then((res:any)=>{
             console.log('is appEnv:',res)
             const popup = window.open(res)
             if (!popup){
              showDialog({message:'跳转下载页面失败,无法下载文件:'+res})
              item.downloading = false
             }
          }).catch((err:any)=>{
            showDialog({message:'跳转下载页面失败,错误:'+err.message})
            item.downloading = false
          })
          
          return
        }
        console.log('is not appEnv')
        var mimeType = 'application/pdf'
        if (url.indexOf('.xml')>0){
          mimeType = 'application/xml'
        }else if (url.indexOf('.doc')>0 || url.indexOf('.docx')>0) {
          mimeType = 'application/msword'
        }
        var fileurl = url
        var name:any = extractParameterFromUrl(url, 'name')
        if (url.indexOf('qiyehao/attachment')>-1){

        }else{
          fileurl = url.split('?')[0]
        }
        console.log('download:',fileurl)
        if(item.processing){
          showDialog({message:'文件解析中..'})
          return
        }
        item.processing = true

        previewFile({ fileurl }).then((res: any) => {
              item.processing = false
              item.downloading = false
              if(res.data){
                try {
                    var decodedXml:any = null
                    if (mimeType=='application/xml'){
                      decodedXml=decodeURIComponent(escape(atob(res.data.content))); // 解码 Base64
                    }else{
                      
                      decodedXml=this.base64ToArrayBuffer(res.data.content); // 解码 Base64
                    }
                    const blob = new Blob([decodedXml], { type: mimeType }); // 创建 Blob 对象
        
                      
                      const url = URL.createObjectURL(blob); // 生成下载链接
                      const a = document.createElement("a");
                      a.href = url;
                      a.download = name; // 设置文件名
                      a.click(); // 触发下载
                      URL.revokeObjectURL(url); // 释放链接
 
                    
                  } catch (error) {
                    console.error("下载 XML 文件失败:", error);
                  }
              }else{

                showDialog({message:'下载文件失败'})
              }
          })
  
      },
      base64ToArrayBuffer(base64:any) {
        const binaryString = atob(base64);
        const len = binaryString.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) {
          bytes[i] = binaryString.charCodeAt(i);
        }
        return bytes.buffer;
      },
    }
  }
</script>
<style >

  .headerType{
    font-size: 3.73vw;
    color: gray;
    padding-left: 2.5vw;
    padding-bottom: 5px;
  }
  .van-cell__value {

    padding-right: 10px!important;
}
</style>

<style>

  @media screen and (min-width:500px) {
    .headerType{
      font-size: 16px;
      color: rgb(160, 157, 157);
      padding-left: 20px;
      padding-bottom: 5px;
    }
  
  }
</style>