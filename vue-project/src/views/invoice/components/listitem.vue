<template>
  <div v-if="data" class="listrow">
     
     <div class="value"  style="flex-grow: 1;box-sizing: border-box;">
      <span v-if="statename" :style="{marginRight:'5px',color:statecolor,border:'1px solid #F5F2EF',padding:'3px'}" >{{ statename }}</span>
      <span style="color:black">{{ data?.name+' ￥'+data?.amount+' '+data?.partbname }}</span>
     </div>
        
      
      <div class="date" >{{ data?.inserttime.substring(0,10) }}</div>
  </div>

  
  
</template>
<script  lang="ts">
import { InvoicingStatesEnum } from '../invoicing_config'
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
      var text = '暂存'
      var color = 'default'
      var record = this.data
      if (record){
        if (record.state==InvoicingStatesEnum.DELETEED){
          text = '已作废';color='gray';
        }else if (record.state==InvoicingStatesEnum.WAITFORDELETE){
          text = '待作废';color='red';
        }else if (record.invoiceids!=null&&!record.realinvoiceamount){
          text = '已红冲';color='red';
        }else if (record.invoiceids!=null&&record.realinvoiceamount>0){
          text = '已开票';color='green';
        } else if (record.state==InvoicingStatesEnum.INVOICED&&record.invoiceids==null){
          text = '待开票';color='#7cb305';
        } else if (record.thirdNo!=null&&record.thirdNo!=''){
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
