<template>
 
  <Uploader :key="key" v-model="uploadImage" multiple :max-count="50" accept="*" 
                        :after-read="afterRead" :before-delete="beforeDelete" :preview-full-image="true"  @click-preview="handlePreview" />

  
</template>
<script  lang="ts">
import { uploadDelete } from '@/api/config';
import { uploadedFiles } from '@/utils/common';
import { getFromUrl, isFileType, setToUrl } from '@/views/budget/utils';
import {  Uploader,showDialog} from 'vant';
import { showImagePreview } from 'vant/es';

  export default {
    components: {
      Uploader
    },
    // returnid 为 bool值，true时返回id，false返回文件url
    props: ['value','accept','returnid'],
    data () {
      return {
        uploadImage:<any>[],
        key:1,
        uploading:false
      }
    },
    watch:{
      value(val){
          
          this.uploadImage = val?val.split(',').map((url:any)=>{
          var result:any = {_url:url,url:url}
          if (isFileType(url)) result.file = getFromUrl(url)
          return result
          }):[]
      },
    },
    mounted() {

      if (this.value){
          this.uploadImage = this.value?this.value.split(',').map((url:any)=>{
            
          return {file:getFromUrl(url),_url:url,url}
          }):[]
      
        }
    },
    created() {
      
    },
    methods:{

      afterRead(file: any) {
          
        try{
          this.uploading = true
          this.$emit('update:uploading',true)
          uploadedFiles(file, { 'uploadType': "3", 'uploadPath': 'contract' }
          ,(e:any)=>{
      
            if (e){
  
              e.forEach((f:any) => {
           
                var url = setToUrl(f)
        
                var index = this.uploadImage.findIndex((item:any)=>item._url==f.url)
                if (index>-1) {
                  this.uploadImage[index]._url = url
                  this.uploadImage[index].url = url
                }
                

              });
              console.log(this.uploadImage)
    
              this.$emit('update:value',this.uploadImage.filter((u:any)=>u._url).map((u:any)=>u._url).join(','))
         

            }
            this.uploading = false
            this.$emit('update:uploading',false)
            
            
            

          },true
        )
        }catch(e:any){
          showDialog({
            'message':e.message
          })

        }
          
          
      },
      beforeDelete  (file: any) {
        
          uploadDelete(file._url).then((res:any)=>{
            if(res.success){
              this.uploadImage = this.uploadImage.filter((u:any)=>u._url!=file._url)
              this.$emit('update:value',this.uploadImage.map((u:any)=>u._url).join(','))

            }
          })
          return true
      },
      handlePreview(item:any){

        showImagePreview({
          images: [item],
          startPosition: 0,
          closeable: true,
          closeOnPopstate: true,
          onClose: () => { },

        });
        
      }

    }
  }
</script>
<style>
  .van-image-preview__close {
  display: flex !important;
  opacity: 1 !important;
  z-index: 2002!important;
}
</style>
