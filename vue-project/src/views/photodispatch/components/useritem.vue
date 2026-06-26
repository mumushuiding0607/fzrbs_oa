<template>
  <div v-if="data&&show" class="row"  >
     
     <div class="value"  style="flex-grow: 1;box-sizing: border-box;">
      
    
      
      <span style="color: black;">{{ data.username+(data.remark?'（'+data.remark+'）':'') }}</span>

     </div>
        
      
      <div class="date" style="width: 100px;text-align: right;flex-shrink: 0;">
        <Icon name="edit" size="25" style="margin-right: 5px;" @click="showAddReporter=true"/>
        
        <Icon name="delete-o" size="25"  @click="del(data)"/>
      </div>
  </div>
  <AddReporter_Dialog :data.sync="data" :show.sync="showAddReporter"  @update:show="(val:any)=>showAddReporter=val" @confirm="addReporter" />

  
  
</template>
<script  lang="ts">

import { Tag,Icon,showConfirmDialog,showDialog } from 'vant';
import AddReporter_Dialog from './AddReporter_Dialog.vue';
import { delreporter, savereporter } from '../api';


  export default {
    components: {
      Tag,Icon,showConfirmDialog,AddReporter_Dialog,showDialog
    },
    props: ['data','showVerify'],
    data () {
      return {
        statecolor:'',
        statename:'',
        amountsTypeName:'',
        showAddReporter:false,
        show:true,
      }
    },
    mounted() {

    },
    created() {

    },
    methods:{
      addReporter(obj:any){
        savereporter({obj}).then((res:any)=>{ 
          if (res.errorMessage){
            showDialog({'message':res.errorMessage})
          }else{
            this.showAddReporter = false
            showDialog({'message':'操作成功'})
            
          }
        })
      },
      del(item:any){
        showConfirmDialog({
          title: '确认删除【'+item.username+'】吗',

        }).then(() => {
          delreporter({id:item.id}).then((res:any)=>{ 
            if (res.errorMessage){
              showDialog({'message':res.errorMessage})
            }else{
              showDialog({'message':'删除成功'})
              this.show = false

            }
          })
        })
        .catch(() => {
        });
      }

    }
  }
</script>
<style  src="@/views/financeCss.css"></style>
