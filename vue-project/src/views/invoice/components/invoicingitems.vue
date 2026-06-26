<template>

  <div v-for="(item,index) in datas" :key="index" class="iibox">
    

    <Cell :title="item?.title" :value="'￥'+parseFloat(item?.amount).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            })"  />
    
  </div>
  
</template>
<script  lang="ts">
import { Card,Cell} from 'vant';
import { getinvoiceitems } from '../invoice';
  export default {
    components: {Card,Cell
    },
    props: ['invoicingid','id'],
    data () {
      return {
        datas:<any>[]
      }
    },
    watch:{
      invoicingid(val){
  
        if (val){
          getinvoiceitems({invoicingid:val}).then((res:any)=>{
            console.log('res:',res)
            if (res){
              this.datas = res.data||[]
            
            }
          })
        }
      }
    },
   
    mounted() {
      if (this.invoicingid){
          getinvoiceitems({invoicingid:this.invoicingid}).then((res:any)=>{
            console.log('res:',res)
            if (res){
              this.datas = res.data||[]
            
            }
          })
        }
    },
    created() {

    },
    methods:{
      

    }
  }
</script>

<style>
 .iibox{
    width: 100%;
 }
 .iibox::after {
    content: '';
    display: block;
    width: 100%;
    margin: 0 auto;
    margin-top: 8px;
    border-bottom: 1px solid rgb(249, 247, 247);
  }

</style>
<style>

</style>