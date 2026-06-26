<template>
  <div v-if="data" class="listrow" >
     
     <div class="value"  style="flex-grow: 1;box-sizing: border-box;">
      
      <span v-if="statename" :style="{marginRight:'5px',color:statecolor,border:'1px solid #F5F2EF',padding:'3px'}" >{{ statename }}</span>
      
      <span style="color: black;">{{ data?.opt_name+'-'+data?.dispatch_name+'(记者)' }}</span>

     </div>
        
      
      <div class="date" style="width: 100px;text-align: right;flex-shrink: 0;">{{ data?.created.substring(0,10) }}</div>
  </div>

  
  
</template>
<script  lang="ts">

import { Tag } from 'vant';
import { StatesEnum } from '../config';

  export default {
    components: {
      Tag
    },
    props: ['data','showVerify'],
    data () {
      return {
        statecolor:'',
        statename:'',
        amountsTypeName:''
      }
    },
    mounted() {
      var text = ''
      var color = 'default'
      var record = this.data
      if (record){
        
        if (record.status==StatesEnum.REJECT){
          text = '已驳回';color='gray';
        }else if (record.status==StatesEnum.CANCEL){
          text = '已取消';color='gray';
        }else if (record.status == StatesEnum.PASS){
          text = '任务中';color='red';
        } else if (record.status == StatesEnum.ING){
          text = '审批中';color='#FF6D25';
        } else {
          text = '已结束';color='green';
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
