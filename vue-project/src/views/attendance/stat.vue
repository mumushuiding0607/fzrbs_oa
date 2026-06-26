<template>
  <div  >
  

    
     <div  >
          <Field
            v-model="date"
            
            readonly
            required

            label="年月"
            placeholder="点击选择年月"
            
            @click="showDate=true"

          />
 
            
       
        
          <div class="row" style="align-items: center;justify-content: center;">
            <Button @click.stop="exportData"  type="primary" size="small" style="margin-right: 10px;" >异常统计表</Button>
            <Button @click.stop="exportApply" type="success" size="small"  >异常申请纪录</Button>
          </div>
      </div>

    <DatePickerDialog :show.sync="showDate" :columnsType="['year','month']"  @update:show="(val:any)=>showDate=val"  @update:label="(val:any)=>date=val"/>
    

        
  
  </div>

  
  
</template>
<script  lang="ts">

import { Button,showConfirmDialog,showDialog,Field, showLoadingToast, closeToast} from 'vant';
import { exportapply, exportExcel, ignore } from './api';
import DatePickerDialog from './components/DatePickerDialog.vue';
import { downloadAsXlSX } from './utils';
import { appEnv, selectEnterpriseContact } from '@/utils/common';
import { addressBookPopup } from '@/components/AddressBook';

  export default {
    components: {
      Button,DatePickerDialog,Field
    },
    props: ['data'],
    data () {
      return {
        showDate:false,
        date:'',
        dept:'',
        showDept:false,
        parentids:''
      }
    },

    mounted() {
      var now=new Date();
      this.date = now.getFullYear()+'-'+(now.getMonth()+1);
    },
    created() {

    },
    methods:{
      async selDept(){
        // if (appEnv()) {
            addressBookPopup({ updateSelectedData:(val:any)=>{
              console.log(val)
              if (val){
                this.dept = val.map((item:any)=>item.title).join('/')
                this.parentids = val.map((item:any)=>item.value).join(',')
              }
            },parentid: 0, max: 500,user:false,selecttype:'department', mode:'multi'})
    
        // }
        
      },
      exportData(){
        showLoadingToast({message:'正在导出...'})
        var tmp = this.date.split('-');
        var m = parseInt(tmp[1])
        var y = tmp[0]
        var ystart =  y;

      
        var start = ystart+'-'+(m>9?m:('0'+m))+'-01';
        m++
        var end = y+'-'+(m>9?m:('0'+m))+'-01';
        console.log(`start:${start},end:${end}`)
        exportExcel({start,end,parentids:this.parentids}).then((res:any)=>{
          closeToast()
          if (res.errorMessage) {
            showDialog({'message':res.errorMessage})
          } else {
         
            downloadAsXlSX(res.data, '异常统计表'+new Date().toLocaleString())
          }
        })
      },
      exportApply(){
        showLoadingToast({message:'正在导出...'})
        var tmp = this.date.split('-');
        var m = parseInt(tmp[1])
        var y = tmp[0]
        var ystart =  y;

      
        var start = ystart+'-'+(m>9?m:('0'+m))+'-01';
        m++
        var end = y+'-'+(m>9?m:('0'+m))+'-01';
        console.log(`start:${start},end:${end}`)
        exportapply({start,end,parentids:this.parentids}).then((res:any)=>{ 
          closeToast()
          if (res.errorMessage) {
            showDialog({'message':res.errorMessage})
          } else {
            downloadAsXlSX(res.data, '异常申请纪录'+new Date().toLocaleString())
          }
        })

      }
    }
  }
</script>

<style  src="@/views/financeCss.css"></style>
