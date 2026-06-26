<template>
  <div v-if="data" class="listrow" >
  
    <Checkbox @click.stop v-if="checkable" shape="square" v-model="checked"></Checkbox>
    
     <div class="value"  style="flex-grow: 1;box-sizing: border-box;">
      
      <span v-if="statename" :style="{margin:'0 5px',color:statecolor,border:'1px solid #F5F2EF',padding:'2px'}" >{{ statename }}</span>
      
      <span style="color: black;">{{ data?.userName+' '+data?.date }}</span>
  
     </div>
        
      
      <div class="date" style="width: 80px!important;text-align: right;flex-shrink: 0;">{{ data?.inserttime.substring(0,10) }}</div>
  </div>

  
  
</template>
<script  lang="ts">

import { Tag,CheckboxGroup,Checkbox } from 'vant';
import { StatesEnum } from '../config';
  export default {
    components: {
      Tag,CheckboxGroup,Checkbox
    },
    props: ['data','showVerify','check','checkable'],
    data () {
      return {
        statecolor:'',
        statename:'',
        amountsTypeName:'',
        checked:<any>false
      }
    },
    watch:{
      check(val){
        this.checked = val
      },
      checked(val){
        console.log('checked:',val)
        this.$emit('check',val)
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
          text = '已通过';color='green';
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
