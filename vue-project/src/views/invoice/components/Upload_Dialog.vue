<template>
  <Dialog v-model:show="visible" title="上传文件" show-cancel-button @confirm="onUploadConfirm">
    <div style="padding-left: 10px;"><Uploader_Component :key="urls" :returnid="returnid" :value.sync="urls" @update:value="(val:any)=>update(val)" @update:ids="(val:any)=>updateids(val)" :accept="accept"  /></div>
  </Dialog>
</template>
<script  lang="ts">
import { Dialog} from 'vant';
import Uploader_Component from './Uploader_Component.vue';
  export default {
    components: {
      Dialog,Uploader_Component
    },
    // returnid 为 bool值，true时返回id，false返回文件url
    props: ['show','fileurls','accept','returnid'],
    data () {
      return {
        visible:false,
        urls:''
      }
    },
    watch:{
      show(val){
        this.visible = val
      },
      visible(val){
        this.$emit('update:show',val)
      },
      fileurls(val){
        this.urls = val
        
      }

    },
    mounted() {
      
    },
    created() {

    },
    methods:{
      onUploadConfirm(){
        
        this.$emit('update',this.urls)
        this.$emit('update:value',this.urls)

      },
      update(val:any){
        
        this.urls = val
        this.$emit('update:value',val)
      },
      updateids(val:any){
        
        this.urls = val
        this.$emit('update:ids',val)
      }

      

    }
  }
</script>
