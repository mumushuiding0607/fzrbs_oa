
<template>
  <div class="box">
    
      
      <Form @submit="onSubmit" required="auto">
        <Field
           
            label="选择附件"
            readonly
            required
    
          />
        <Uploader v-model="uploadImage" style="margin-left:var(--van-padding-lg)" key="i_uploader"  :max-count="1" accept=".xlsx,.xls" 
                  :after-read="afterRead"  />
        <Field
            v-model="data.approvalUsername"
            is-link
            readonly
            required
            name="approvalUsername"
            label="值班领导"
            placeholder="点击选择值班领导"
            @click="showLeader=true"
            :rules="[{ required: true, message: '不能为空' }]"
          />
        
      <div style="margin: 16px;">
        
        <Button  :loading="loading" round block type="primary" native-type="submit" style="font-size: calc(var(--van-cell-font-size)*var(--Big));">
          提交
        </Button>
      </div>

    </Form>
    <LeaderOnDutySelect key="leaderid"  :show.sync="showLeader"  @update:show="(val:any)=>showLeader=val" @update:value="(val:any)=>data.approvalUserid=val"  @update:label="(val:any)=>data.approvalUsername=val"
    />

  </div>
</template>
<script  lang="ts">

import * as XLSX from 'xlsx';
import {  Form,Field,Button,Uploader,showDialog,showConfirmDialog, showToast } from 'vant';


import {uploaddatas } from './api';
import { useUserStore } from '@/stores';
import LeaderOnDutySelect from './components/LeaderOnDutySelect.vue';


const cacheStore = useUserStore()

  export default {
    components: {
      LeaderOnDutySelect,Form,Field,Button,Uploader,
    },
    
    data () {
      return {
        uploadImage:<any>[],
        data:<any>{},
        currentUser:{},
        loading:false,
        items:<any>[],
        header:[],
        showLeader:false,
      }
    },
    watch:{
      
    },
    mounted() {
      this.currentUser = cacheStore.userInfo
    },
    created() {
    },
    methods:{
      afterRead(e:any){
        this.loading = true
        const file = e.file;
         if (!file) return;
         const reader = new FileReader();
         reader.onload = (event:any) => {
           const binaryStr = event.target.result;
           const workbook = XLSX.read(binaryStr, { type: 'binary' });
           // 获取第一个工作表
           const sheetName = workbook.SheetNames[0];
           const worksheet = workbook.Sheets[sheetName];
           // 转换为 JSON 数组（二维数组）
           var items:any = XLSX.utils.sheet_to_json(worksheet, { header: 1 });
           if (items[0][0]=='导出新闻数据集'||!items[0][1]){
              // 删除第一行
              items.shift()
            }
           this.header = items[0]
           items.shift()
           this.items = items


           this.loading = false
          
         };
         reader.readAsBinaryString(file);
      },
 
      onSubmit(values:any){
        const par={items:this.items,header:this.header,leader:this.data}
        console.log(par)
        showConfirmDialog({
          title: '确认提交吗？'

        })
          .then(() => {

            uploaddatas(par).then((res:any)=>{
              this.$router.push({name:'manuscriptscoring_mylist',query:{tab:0}})
              if (res.errorMessage){
                showDialog({'message':res.errorMessage,'allowHtml':true})
              }else{
                showDialog({'message':'上传成功'})
                
              }
            })
          })
        
      },

       

    }
  }
</script>
<style  lang="css" src="@/views/financeCss.css">



  
</style>

