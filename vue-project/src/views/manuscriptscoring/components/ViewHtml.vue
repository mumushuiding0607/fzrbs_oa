<template>
  <ActionSheet v-model:show="visible" title="稿件内容" show-cancel-button style="height: 100vh;margin-top: 0;padding-top: 0;">
    <div v-html="html" style="padding: 0 10px;box-sizing: border-box;"></div>
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,Field,Cell} from 'vant';


  export default {
    components: {
      ActionSheet,Field,Cell
    },
    props: ['show','data'],
    data () {
      return {
        visible:false,
        selectedValue:'',
        options:<any>[],
        html:''
      }
    },
    watch:{
      show(val){
        this.visible = val
      },
      visible(val){
        this.$emit('update:show',val)
      },
      data(val){
        this.html =val? val.replace(
    /<img([^>]*)>/g,
    '<img $1 style="display:block; margin:10px auto; max-width:100%; height:auto;">'
  ):'';
      },
      
    },
    mounted() {
    },
    created() {

    },
    methods:{


    }
  }
</script>
