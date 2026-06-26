<template>

     <div class="cell">
        <div class="value" @click="getfileurls(data.id)" :style="data.id?'color: rgb(25, 137, 250);padding-left: 0;':'padding-left: 0;'">{{ index+'.'+ data.title }}</div>
     </div>
     <div class="cell">
        <div class="col" style="flex:1">
          <div  class="cell">
            <div class="label">预算金额</div>
            <div  class="value" >{{ data.budget!=null?parseFloat(data.budget).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0 }}</div>
          </div>
        </div>
        <div v-if="show=='final'" class="col" style="flex:1">
          <div  class="cell">
            <div class="label">决算金额</div>
            <div class="value" >{{ data.final!=null?parseFloat(data.final).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            }):0 }}</div>
          </div>
        </div>
     </div>
     <Filescard v-if="urls"  :urls="urls"/>
     <!-- 汇总备注 -->
     <div v-if="data.memo" class="cell">
        <div class="label" style="margin-left: 10px;">备注</div>
     </div>
     <div v-if="data.memo" class="cell">
        <div class="value" style="margin-left: 10px;">
          
          {{ data.memo }}
        </div>
     </div>
     <!-- 预算备注 -->
     <div v-if="data.budgetnote" class="cell">
        <div class="label" style="margin-left: 10px;">预算备注：</div>
     </div>
     <div v-if="data.budgetnote" class="cell">
        <div class="value" style="margin-left: 10px;">
          
          {{ data.budgetnote }}
        </div>
     </div>
     <!-- 决算备注 -->
     <div v-if="data.finalnote &&  show=='final'" class="cell">
        <div class="label" style="margin-left: 10px;">决算备注：</div>
     </div>
     <div v-if="data.finalnote &&  show=='final'" class="cell">
        <div class="value" style="margin-left: 10px;">
          
          {{ data.finalnote }}
        </div>
     </div>
    
  
  
  
</template>
<script  lang="ts">
  import { Collapse, CollapseItem, showDialog,Dialog} from 'vant';
import { getbalancefileurls } from '../budget';
import Filescard from './filescard.vue';
  
  export default {
    components: {
      Collapse, CollapseItem,Dialog,Filescard,
    
    },
    props: ['data','index','show'],
    data () {
      return {
        urls:'',
        showfiles:false
      }
    },
    mounted() {

    },
    created() {

    },
    methods:{
      getfileurls(id:any){
        console.log('金额和附件')
        if (!id){
          showDialog({message:'无关联附件'})
          return
        }
        getbalancefileurls({id}).then((res:any)=>{
          console.log(res)
          if (res.data&&res.data!=','){
            this.urls = res.data
          }else{
            showDialog({message:'暂无关联附件'})
          }
        })
      }
    }
  }
</script>
<style   lang="css" src="@/views/financeCss.css"></style>