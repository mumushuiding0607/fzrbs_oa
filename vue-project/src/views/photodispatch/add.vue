
<template>
  <div class="box">
    
      
      <Form @submit="onSubmit" required="auto">
        <DateTimeField :value="data.begin_time"  label="开始日期" @update:value="(val:any)=>{
          data.begin_time=val
          reporterKey++
        }" />
        <DateTimeField  :value="data.end_time" label="结束日期" @update:value="(val:any)=>{
          data.end_time=val
          reporterKey++
        }" />
        <Field
          v-model="data.dispatch_name"
       
          readonly
          required
          label="记者"
          placeholder="点击选择记者"

          @click="showReporter=true"
          :rules="[{ required: true, message: '不能为空' }]"
        />
        <Field

            v-model="data.reason"
            name="reason"
            type="textarea"
            rows="3"
            :autosize="true"
            label="派工事由"
            placeholder="请输入派工事由"
            required
            
            :rules="[{ required: true, message: '不能为空' }]"
          />
        
      <div style="margin: 16px;">
        
        <Button v-if="!data.id" :loading="loading" round block type="primary" native-type="submit" style="font-size: calc(var(--van-cell-font-size)*var(--Big));">
          提交
        </Button>
    
        <Button v-if="data.id" round block type="primary" @click="update" style="font-size: calc(var(--van-cell-font-size)*var(--Big));">
          修改
        </Button>
      </div>

    </Form>
    <Reporters_Select :key="reporterKey" :show.sync="showReporter" :parameter="{begin:data.begin_time,end:data.end_time}" @update:show="(val:any)=>showReporter=val" @update:value="(val:any)=>{
      
      data.dispatch_userid=val.userid
      data.dispatch_name=val.name
    }"/>
  </div>
</template>
<script  lang="ts">
import {h} from 'vue'
import {  Form,CellGroup,Field,Button,Card,Uploader,Radio,RadioGroup,showDialog,showConfirmDialog, showToast } from 'vant';


import UserSelect from '../invoice/components/UserSelect.vue';


import Flow_Steps from '@/views/finance/components/Flow_Steps.vue';
import { getdata, getflow, save } from './api';
import { extractParameterFromUrl } from '../budget/utils';
import { useUserStore } from '@/stores';
import DatePicker_Dialog from '../press/components/DatePicker_Dialog.vue';
import TimePicker_Dialog from '../press/components/TimePicker_Dialog.vue';
import DateTimeField from './components/DateTimeField.vue';
import Reporters_Select from './components/Reporters_Select.vue';
import UserAvatarBox from '../finance/components/UserAvatarBox.vue';

const cacheStore = useUserStore()

  export default {
    components: {
      UserAvatarBox,Reporters_Select,DateTimeField,DatePicker_Dialog,TimePicker_Dialog,Flow_Steps,UserSelect,Form,CellGroup,Field,Button,Card,Uploader,Radio,RadioGroup,
    },
    
    data () {
      return {
        extractParameterFromUrl:extractParameterFromUrl,
        data:<any>{},
        currentUser:{},
        loading:false,
        showReporter:false,
        reporterKey:1


          
      }
    },
    watch:{
      
    },
    mounted() {
      this.currentUser = cacheStore.userInfo
      var thirdNo = this.$route.query?.thirdNo;
      if (thirdNo){
        getdata({thirdNo:thirdNo}).then((res:any)=>{
          if (res.errorMessage){
            showDialog({message:res.errorMessage})
          }else{
            this.data = {begin_time:res.info.begin_time,end_time:res.info.end_time,dispatch_userid:res.info.dispatch_userid,dispatch_name:res.info.dispatch_name,reason:res.info.reason}

          }
        })
      }

    },
    created() {
    },
    methods:{

  
      update(){},
 
      onSubmit(values:any){
        getflow({obj:this.data}).then((res:any)=>{
          
          if (res.errorMessage) {
            showDialog({'message':res.errorMessage})
          } else {
            
            showConfirmDialog({
              title: '请确认流程是否正确',
              message:() =>{
                return h(Flow_Steps,{data:res||{viewdata:{}}})
        
              },
                
            })
              .then(() => {
                if(this.loading) {
                  showToast({message:'提交中...'})
                  return
                }
                this.loading = true
                setTimeout(() => {
                  this.loading = false
                }, 3000);
                save({obj:this.data}).then((res:any)=>{
                  if (res.errorMessage){
                    showDialog({'message':res.errorMessage})
                  }else{
                    // 跳转到view页面
                    if (res.thirdNo){
                      this.$router.push({name:'photodispatch_view',query:{thirdNo:res.thirdNo}})
                    }
                     
      
                  }
                })
              })
              .catch(() => {
                // on cancel
              });

          }
        })
        
      },

       

    }
  }
</script>
<style  lang="css" src="@/views/financeCss.css">



  
</style>

<style>
  @media screen and (min-width: 500px) {


  }
</style>
