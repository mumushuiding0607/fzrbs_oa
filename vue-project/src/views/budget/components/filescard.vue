<template>

  <Cell v-if="!showIcon" style="color: #1989fa;"  v-for="item in options" :title="item.name" :label="item.time" @click.stop="view(item)">
    <template #right-icon>
      <Loading v-if="item.processing"  type="spinner" color="gray" >解析中..</Loading>
      <span v-if="showDelete" style="padding-left: 10px;color: red;" @click.stop="del(item.url)">删除</span>
      <span v-if="!item.downloading" style="font-size: calc(var(--van-cell-font-size)*1)!important;padding-left: 10px;" @click.stop="download(item)">下载</span>
      <Loading v-if="item.downloading"  type="spinner" color="gray" >下载中..</Loading>
  </template>
  </Cell>
  <div v-if="showIcon" style="display: flex;flex-wrap: wrap;margin-left: 5px;" >
    <div v-for="item in files" style="position: relative;">
      <Image
        
          :width="iconSize||80"
          :height="iconSize||80"
          :src="item.thumbUrl"
          style="margin-left: 8px;margin-top: 10px;"
          @click.stop="viewFile(item)"
        />
       <Loading v-if="item.downloading"  style="position: absolute;right: 0;top: 10px;z-index: 1000;"  type="spinner" color="gray" ></Loading>
       <Icon  v-if="!item.downloading" name="down" style="position: absolute;right: -3px;top: 10px;z-index: 1000;background: gray;border-radius: 50%;padding: 3px;color: white;font-size: calc(var(--van-cell-font-size)*1.2);"  @click.stop="download(item)"/>
       <span  style="position: absolute;right: -3px;bottom: 5px;background: #ad646b;border-radius: 10%;padding:0 2px;color: white;font-size: calc(var(--van-cell-font-size)*0.8);">{{ item.type }}</span>
       <div v-if="item.processing"  style="position: absolute;right: 0;top: 10px;z-index: 1000;background-color: white;width: 80px;height: 80px;opacity: 0.6;display: flex;align-items: center;justify-content: center;">
          <Loading  type="spinner" color="green" ></Loading>
       </div>
    </div>
    <Viewer :key="imageUrls.length"  :options="viewerOptions" style="display: flex;flex-wrap: wrap;">
          <img 
            v-for="(img, index) in imageUrls" 
            :key="index" 
            :src="img" 
            :data-original="img"
            :width="80"
            :height="80"
            style="margin-left: 8px;margin-top: 10px;"

          />
        </Viewer>
        
      
 
  </div>
  <div class="pdfBox" :key="key" v-if="showpdffile" >
        <PdfPreview  page-scale="page-fit" theme="light" :src="filedata" @loaded="onLoaded"/>
        <Icon name="cross" class="pdfBoxClose closemargin" @click="showpdffile = false" />
  </div>
  <div class="pdfBox" v-if="invoiceContent">
    <pre v-html="invoiceContent"></pre>
  </div>



      


</template>

<script  lang="ts">
  
  import 'vue3-pdf-app/dist/icons/main.css'

  import { downloadfromUrl, extractParameterFromUrl, getFromUrl, isFileType } from '../utils';
  import { Cell,showImagePreview,Icon, showToast,Image,showDialog,ImagePreview,Tag,Loading  } from 'vant';
  import { previewFile } from '@/api/config';
  import { appEnv, base64ToBlob } from '@/utils/common';
  import PdfPreview from '../../../components/PdfPreview.vue';
  import pdfimg from './pdf-doc.svg'
  import { transfileurl } from '../budget';
  import Viewer from './Viewer.vue'

  export default {
    components: {Cell,PdfPreview,Icon,Image,ImagePreview,Tag,Loading,
      Viewer
    },
    props: ['urls','showDelete','showIcon','preview','iconSize'],
    data () {
      return {
        options:<any>[],
        option2:<any>[{name:'abd'}],
        showpdffile:false,
        filedata:'',
        invoiceContent:'',
        downloading:false,
        processing:false,
        showPreview:false,
        imageUrls:<any>[],
        files:<any>[],
        progress:0,
        startIndex:0,
        viewerOptions: {
          inline: false,
          navbar:true,
          toolbar: {
            zoomIn: 1,
            zoomOut: 1,
            rotateLeft: 1,
            rotateRight: 1,
            prev: 1,
            next: 1,
          },
          keyboard: true, // 启用键盘支持:cite[6]
        } as Viewer.Options,
        key:1,
      }
    },
    async mounted() {
      var urls = this.urls 
      if (urls) {

        if (urls.split){
          urls = urls.split(',')||[]
        }
        var temp:any
        if (urls.map){
    
          temp = (urls||[]).filter((e:any)=>!e||!e.name).map((u:any,index:number)=>{
            var result:any = getFromUrl(u)
            result.key = index
            result.thumbUrl = result.url
  
            if (isFileType(result.type)){
              result.thumbUrl = pdfimg
            }
            
            return result
          })
        }
        
        this.options = temp
        this.imageUrls = this.options.filter((item:any)=>{

          if (isFileType(item.type)){
            return false
          }
          return true
        }).map((e:any)=>e.url)
        this.files = this.options.filter((item:any)=>{
  
          if (isFileType(item.type)){
            
            return true
          }
          return false
        })

        if(this.preview){
          for (let item of this.files) {
            await this.previewFile(item); // 等待当前任务完成再继续循环
          }
        }
        
      }
    },
    created() {

    },
    methods:{
      onLoaded (){
          
      },
      asyncTask(item:any) {
        return new Promise<void>((resolve) => {
          setTimeout(() => {
            console.log(`处理完成: ${item}`);
            resolve();
          }, 3000); // 模拟异步操作
        });
      },

  


      onChange(index:number){
        this.startIndex = index
      },
      prev(){
        this.startIndex= this.startIndex-1
        this.startIndex = this.startIndex<0?this.imageUrls.length-1:this.startIndex
      },
      next(){
        this.startIndex= this.startIndex+1
        this.startIndex = this.startIndex>this.imageUrls.length-1?0:this.startIndex
      },

      share(url:any){
        var name = extractParameterFromUrl(url,'name')||''

        if (navigator.share) {
          navigator.share({
            title: name,
            url: url // 可选，分享链接
          })
          .then(() => console.log('分享成功'))
          .catch((error) => console.log('分享失败', error));
        } else {
          alert('当前浏览器不支持分享功能');
        }
      },
      del(url:any){
        console.log('del')
        this.options = this.options.filter((e:any)=>e.url!=url)
        this.$emit('update:urls',this.options.map((e:any)=>e.url).join(','))
      },
      viewFile(item:any){

        this.view(item)
      },
      async download(item:any){
    
        var url = item.url
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
              showDialog({message:'跳转页面失败,无法下载文件:'+res})
              item.downloading = false
             }
          }).catch((err:any)=>{
           
            showDialog({message:'跳转页面失败:'+err.message})
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

    

        previewFile({ fileurl }).then((res: any) => {
    
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
    
       previewFile(item:any){
        return new Promise<void>(async (resolve, reject) => { 
          var fileurl = item.url
          if (item.url.indexOf('qiyehao/downAnnex/file')>0){

          }else{
            fileurl = item.url.split('?')[0]
          }
          
          if(item.processing){
            showDialog({message:'文件解析中..'})
            return
          }
          
          if (!item.previewdata){
            item.processing = true
            setTimeout(() => {
              item.processing = false
            }, 10000);
            item.previewdata = await previewFile({ fileurl })
            item.processing = false
          }
          resolve()
        });
        
      },
      async view(item:any){
        
     
        
        var msg:any = getFromUrl(item.url)
        if (msg.name) {
          var sufix = msg.name.substring(msg.name.lastIndexOf('.'))
          if (['.pdf','.doc','.docx','.xml'].includes(sufix)){
              var fileurl = item.url
              if (item.url.indexOf('qiyehao/downAnnex/file')>0){

              }else{
                fileurl = item.url.split('?')[0]
              }
              
              if(item.processing){
                showDialog({message:'文件解析中..'})
                return
              }
              
              if (!item.previewdata){
                item.processing = true
                setTimeout(() => {
                  item.processing = false
                }, 10000);
                item.previewdata = await previewFile({ fileurl })
                item.processing = false
              }
 
              const res = item.previewdata 
              
              if (res?.data) {
                 if(sufix=='.pdf'){
                  const pdfUrl = base64ToBlob(res.data.content, 'application/pdf')
                  console.log('pdfUrl:',pdfUrl)
                  this.filedata = pdfUrl
                  this.showpdffile = true
                  this.key = this.key+1
                 }else  {
                  var mimetype = 'application/msword'
                  var decodedXml
                  switch (sufix) {
                    case '.xml':
                      mimetype="application/xml"
                      decodedXml = decodeURIComponent(escape(atob(res.data.content)));
                      break;
                    default:
                      decodedXml=this.base64ToArrayBuffer(res.data.content);
                      break;
                  }
                  // .xml文件的base64编码解析
                  try {
                     // 解码 Base64
                    const blob = new Blob([decodedXml], { type: mimetype }); // 创建 Blob 对象
                    const url = URL.createObjectURL(blob); // 生成下载链接
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = msg.name; // 设置文件名
                    a.click(); // 触发下载
                    URL.revokeObjectURL(url); // 释放链接
                  } catch (error) {
                    console.error("下载文件失败:", error);
                  }
                  
                 }
                  
              }else{
                showDialog({message:'预览文件失败'})
              }
            } else{

              showImagePreview({
                images: [msg.url],
                closeable: true,
              })

            }
        }
        
      },
     
      base64ToArrayBuffer(base64:any) {
        const binaryString = atob(base64);
        const len = binaryString.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) {
          bytes[i] = binaryString.charCodeAt(i);
        }
        return bytes.buffer;
      }
    }
  }
</script>
<style >

.van-cell{
    font-size: calc(var(--van-cell-font-size)*1.1)!important;
  }
.pdfBox {
    position: fixed;
    width: 100vw;
    height: 100vh;
    top: 0;
    left: 0;
    z-index: 1000;
}

.pdfBoxClose {
    position: absolute;
    right: 35px;
    top: 1px;
    z-index: 100000000;
    width: 35px;
    height: 30px;
    text-align: center;
    display: flex;
    align-items: center;
    font-size: 25px;
    color: red;
}
@media (min-width:771px) {
  .closemargin{
    margin-right: 60px;
  }
}
.vue-pdf-app-icon::before, .vue-pdf-app-icon::after {
        font-family: "pdf";
        font-size: 1.5rem!important;
        display: inline;
        text-decoration: inherit;
        text-align: center;
        font-variant: normal;
        text-transform: none;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
    /deep/ .van-action-bar-button--first {
    margin-left: 5px;
    border-top-left-radius: 0; 
    border-bottom-left-radius:0; 
  }
  /deep/ .van-action-bar-button--last {
    margin-right: 5px;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}
/deep/ .van-tab__text--ellipsis {
    display: -webkit-box;
    overflow:inherit;
    -webkit-box-orient: vertical;
}
/deep/ .pdf-app #toolbarContainer, .pdf-app .findbar, .pdf-app .secondaryToolbar {
    position: relative;
    height: 35px;
}
/deep/ .vue-pdf-app-icon::before {

    font-size: 25px;
}
/deep/.pdf-app .toolbar {
    position: relative;
    left: 0;
    right: 0;
    z-index: 9999;
    cursor: default;
    display: flex;
    flex-direction: row;
    align-items: center;
}
/deep/ .pdf-app #toolbarViewer {
   
    width: 100%;
    /* padding: 0 5px; */
}
/deep/ .pdf-app[class] #toolbarContainer, .pdf-app[class] .findbar, .pdf-app[class] .secondaryToolbar {
    background-color: var(--pdf-toolbar-color);
    width: 100%;
    display: flex;
    align-items: center;
}
/deep/ .pdf-app #toolbarContainer button {
    cursor: pointer;
    margin-right: 10px;
}

/deep/ .pdf-app .splitToolbarButtonSeparator {
    float: left;
    display: none;
}
/deep/ .vue-pdf-app-icon.presentation-mode::before {
    content: "";
}
/deep/ .pdf-app .pdfViewer .page {

    margin: 15px auto -8px auto;

}
</style>

<style>

  @media screen and (min-width:500px) {
    .pdfBoxClose {
      position: absolute;
      right: 35px;
      top: 1px;
      z-index: 100000000;
      width: 35px;
      height: 30px;
      text-align: center;
      display: flex;
      align-items: center;
      font-size: 30px;
      color: red;
  }
  }
  
</style>