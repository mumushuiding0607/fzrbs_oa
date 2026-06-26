<template>
  <div v-if="data" class="listrow" >
     
     <div class="value"  style="flex-grow: 1;box-sizing: border-box;">
      
      <span v-if="statename" :style="{marginRight:'5px',color:statecolor,border:'1px solid #F5F2EF',padding:'3px'}" >{{ statename }}</span>
      <span style="color: black;">{{ data?.userName+' '+data?.paper+' '+data?.layout }}</span>
     </div>
        
      
      <div class="date" >{{ data?.inserttime.substring(0,10) }}</div>
  </div>

  
  
</template>
<script  lang="ts">

import { Tag } from 'vant';
import { StatesEnum } from '../press_config';

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
          text = '催款中';color='green';
        } else if (record.status == StatesEnum.ING){
          text = '审批中';color='#FF6D25';
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