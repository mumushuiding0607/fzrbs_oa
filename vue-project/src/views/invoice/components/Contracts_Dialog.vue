<template>
  
  <ActionSheet v-model:show="visible" title="合同" style="height:100vh;">
    <div v-for="(item,index) in data" :key="index">
      <CellGroup  >
        <CopyableCell  title="合同编号" :value="item.serial" />
        <CopyableCell  title="合同金额" :value="item.amount" />
        <CopyableCell  title="开票金额" :value="item.invoiceamount" />
        <CopyableCell  title="开票次数" :value="item.count" />
        
      </CellGroup>
      <Filescard v-if="item?.fileurls" :urls="item?.fileurls"/>
    </div>
    
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,CellGroup,Cell} from 'vant';
import Filescard from '@/views/budget/components/filescard.vue'
import CopyableCell from '@/views/invoice/components/Copyable_Cell.vue'
import { getcontracts } from '../invoice';
  export default {
    components: {
      ActionSheet,CellGroup,Cell,CopyableCell,Filescard
    },
    props: ['ids','show'],
    data () {
      return {
        visible:false,
        data:<any>[]
      }
    },
    watch:{
      show(val){
        this.visible = val
      },
      visible(val){
        this.$emit('update:show',val)
      },
      ids(val){
        if (val){
          getcontracts({ids:val}).then((res:any)=>{
            if (res){
              this.data = res
            }
          })
        }
        
      }
    },
    mounted() {
      
    },
    created() {

    },
    methods:{


    }
  }
</script>
