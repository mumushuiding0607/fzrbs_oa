
<template>
  <div class="box">
    
      
      <Form @submit="onSubmit" required="auto">
        <Field

          required
          label="异常类型"
          placeholder="点击选择异常类型"
          is-link
          readonly
          v-model="data.type"
          @click="showType=true"
          :rules="[{ required: true, message: '不能为空' }]"
        />

        <Dict_Select :show.sync="showType"  @update:show="(val:any)=>showType=val" type="考勤异常申请" order="value asc" :initialValue="4" @update:value="(val:any)=>data.typeid=val"  @update:label="(val:any)=>data.type=val"/>
        <DateTimeField :value="data.date"  label="异常日期" @update:value="checkExpire" />


        <Field

            v-model="data.reason"
            name="reason"
            type="textarea"
            rows="3"
            :autosize="true"
            label="异常说明"
            placeholder="请输入异常说明"
            required
            
            :rules="[{ required: true, message: '不能为空' }]"
          />
          <Field
            
            label="附件上传"
            readonly
            
          />
          <Uploader_Component @update:uploading="(val:any)=>uploading=val"   style="margin-left:var(--van-padding-lg)" :value.sync="data.annex"   @update:value="(val:any)=>data.annex=val"    />
          
        
      <div style="margin: 16px;">
        
        <Button v-if="!data.id" :loading="loading" round block type="primary" native-type="submit" style="font-size: calc(var(--van-cell-font-size)*var(--Big));">
          提交
        </Button>
 
      </div>

    </Form>

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
import DateTimeField from './components/DatePickerField.vue';
import Uploader_Component from '../invoice/components/Uploader_Component.vue';
import Dict_Select from '../invoice/components/Dict_Select.vue';

const cacheStore = useUserStore()

  export default {
    components: {
      Uploader_Component,Dict_Select,DateTimeField,DatePicker_Dialog,TimePicker_Dialog,Flow_Steps,UserSelect,Form,CellGroup,Field,Button,Card,Uploader,Radio,RadioGroup,
    },
    
    data () {
      return {
        extractParameterFromUrl:extractParameterFromUrl,
        data:<any>{},
        currentUser:{},
        loading:false,
        hasexpired:false,
        uploading:false,
        EXPIRETIME: 7 * 24 * 3600 * 1000,
        showType:false

          
      }
    },
    watch:{
      
    },
    mounted() {
      this.currentUser = cacheStore.userInfo
      var date = this.$route.query?.date;
      if (date){
        this.data = {
          date,
          type:'其他'
        }
      }else{
        this.data = {
          date:this.getDateNow()+' 全天',
          type:'其他'
        }
      }
   

    },
    created() {
    },
    methods:{
      getDateNow(){
        return new Date().toLocaleDateString().replace(/\//g, '-')
      },
      checkExpire(val:string){
        console.log(val)
        this.data.date = val

        var d = new Date(val.split(' ')[0])
        var n = new Date()
        console.log((n.getTime()-d.getTime()))
        if ((n.getTime()-d.getTime())>this.EXPIRETIME) {
          this.hasexpired = true
        }else{
          this.hasexpired = false
        }
     
      },
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
                      this.$router.push({name:'attendance_view',query:{thirdNo:res.thirdNo}})
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
