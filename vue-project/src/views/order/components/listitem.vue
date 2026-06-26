<template>
  <div v-if="data" class="listrow">
     <div class="value"  style="flex-grow: 1;box-sizing: border-box;">
      

      <div style="color:#969799;font-size:12px;margin-top:2px">
        <span v-if="statename" :style="{marginRight:'5px',color:statecolor,border:'1px solid #F5F2EF',padding:'3px'}" >{{ statename }}</span>
        <span>{{ data?.userName }}</span>
        <span style="margin:0 8px">|</span>
        <span>{{ data?.department }}</span>
      </div>
     </div>
      <div class="date" >{{ data?.inserttime?.substring(0,10) }}</div>
  </div>
</template>
<script  lang="ts">
import { Tag } from 'vant';

  export default {
    components: {
      Tag
    },
    props: ['data'],
    data () {
      return {
        statecolor:'',
        statename:''
      }
    },
    mounted() {
      var text = '正常'
      var color = 'default'
      var record = this.data
      if (record){
        if (record.status == 1){
         text = '审批中';color='#FF6D25';
        } else if (record.status == 2){
          text = '已完成';color='green';
        } else if (record.reject == 1){
          text = '已驳回';color='red';
        }else if (record.reject == 4){
          text = '已撤销';color='default';
        }
      }
      
      this.statename = text
      this.statecolor = color
    },
    created() {

    },
    methods:{

    }
  }
</script>
<style  src="@/views/financeCss.css"></style>
