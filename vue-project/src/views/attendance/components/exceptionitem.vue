<template>
  <div v-if="data" class="listrow" >
  

    
     <div class="value"  style="flex-grow: 1;box-sizing: border-box;">

      <div >
        <span style="color: gray;">异常情况：</span>
        <span style="color: black;">{{ data?.exception_type }}</span>
      </div>
      <div >
        <span style="color: gray;">本次打卡：</span>
        <span style="color: #ee0a24;">{{ data?.checkin_time }}</span>
      </div>
      <div >
        <span style="color: gray;">标准打卡：</span>
        <span style="color: black;">{{ data?.sch_checkin_time }}</span>
      </div>
  
     </div>
        
      <div class="date" style="width: 80px!important;text-align: right;flex-shrink: 0;">

        <Button @click.stop="add(data?.checkin_time)" v-if="data.state!=8" type="primary" size="small" style="width: calc(var(--van-cell-font-size)*5)" >填写说明</Button>
        <Button @click.stop="ignore(data.id)" v-if="data.state!=8" type="primary" plain size="small" style="width: calc(var(--van-cell-font-size)*5);margin-top: 10px;"> 忽 略 </Button>
        <Button @click.stop v-if="data.state==8" type="default" size="small" style="width: calc(var(--van-cell-font-size)*5)">已 忽 略</Button>
      </div>
  </div>

  
  
</template>
<script  lang="ts">

import { Button,showConfirmDialog,showDialog} from 'vant';
import { ignore } from '../api';

  export default {
    components: {
      Button
    },
    props: ['data'],
    data () {
      return {

      }
    },

    mounted() {
 
    },
    created() {

    },
    methods:{
      add(date:any){
        if (date) {
          date = date.split(' ')[0]+' 全天'
        }
        this.$router.push({name:'attendance_add',query:{date}})
      },
      ignore(id:any){
        showConfirmDialog({title:'确定忽略此异常吗？'}).then(()=>{
          ignore({id}).then((res:any)=>{
            if (res.errorMessage) {
              showDialog({'message':res.errorMessage})
            }else{
              this.data.state=8
            }
          })
        })
      }
    }
  }
</script>

<style  src="@/views/financeCss.css"></style>
