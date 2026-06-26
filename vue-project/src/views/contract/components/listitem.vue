<template>
  <div class="listrow">
    <div style="flex-grow: 1;">
      <div class="col">
        <div style="flex-grow: 1;color: black;font-weight: normal!important;" class="value">
          <Badge v-if="data?.age" style="margin-left: 10px;" :content="data.age" position="top-left">
            <span  :style="{margin:'0',color:'red',border:'1px solid #F5F2EF',padding:'2px'}" >逾</span>
          </Badge>
          <span v-if="statename" :style="{marginRight:'5px',color:statecolor,border:'1px solid #F5F2EF',padding:'3px'}" >{{ statename }}</span>
          <span v-if="data?.serial&&!data?.fileurls" :style="{marginRight:'5px',color:'green',border:'1px solid #F5F2EF',padding:'3px'}">待上传</span>
          {{ data?.title||(data?.serial+' '+data?.contracttitle) }}
          <span class="label" v-if="data.name">{{ data.name+' '+((data.approvaltypename||data.statename)?((data.approvaltypename||data.statename)+'审批'):'') }}</span>
        </div>
        
      </div>
    </div>
    <div class="date">{{ data?.inserttime.substring(0,10) }}</div>
  </div>
</template>
<script  lang="ts">
  import {Badge} from 'vant'
import { StatesEnum } from '../config';
  export default {
    components: {Badge},
    props: ['data'],
    data () {
      return {
        statename:'',statecolor:''
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
<style src="@/views/financeCss.css">
 
</style>
<style>
  @media screen and (min-width: 500px) {
       
   
      
  }
</style>