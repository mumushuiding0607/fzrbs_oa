
<template>

    
    <ActionSheet title="上传附件" v-model:show="visible"  show-cancel-button style="height: 90vh;">
      <Form @submit="onSubmit" required="auto">
        <Field
            v-model:model-value="data1.urgetypename"
            name="urgetypename"
            label="清欠方式"
            placeholder="点击选择清欠方式"
            :readonly="readonlyUrgeType"
          />
        <Field
              v-model="data1.date"
              style="width: 100%;"
              readonly
              name="date"
              label="清欠日期"
              placeholder="点击选择日期"
              @click="showDate=true"
            />
        <Field

            v-model="data1.note"
            name="note"
            type="textarea"
            rows="3"
            :autosize="true"
            label="清欠备注"
            placeholder="请输入清欠备注"
            :rules="[{ required: false, message: '不能为空' }]"
          />
        <Field name="uploader" label="清欠文件" >
          <template #input>
            
            <Uploader_Component  accept="*/*" :value.sync="data1.fileurls" @update:value="(val:any)=>data1.fileurls=val"  />
          </template>
        </Field>
        <Field
      
            
            v-model:model-value="data1.urgeresultname"
            is-link
            name="urgeresultname"
            label="清欠结果"
            placeholder="点击选择清欠结果"
            @click="showResult = true"
          />
        <Field
              v-model="data1.dealdate"
              style="width: 100%;"
              readonly
              name="dealdate"
              label="清欠结果日期"
              placeholder="点击选择日期"
              @click="showDealDate=true"
            />
        <Field

            v-model="data1.dealnote"
            name="dealnote"
            type="textarea"
            rows="3"
            :autosize="true"
            label="清欠结果备注"
            placeholder="请输入清欠结果备注"
            :rules="[{ required: false, message: '不能为空' }]"
          />
        <Field name="dealuploader" label="清欠结果文件" >
          <template #input>
            
            <Uploader_Component  accept="*/*" :value.sync="data1.dealfileurls" @update:value="(val:any)=>data1.dealfileurls=val"  />
          </template>
        </Field>
      <ActionBar >
        <ActionBarButton  :loading="loading" round  type="primary" native-type="submit" style="font-size: calc(var(--van-cell-font-size)*var(--Big));">
          提交
        </ActionBarButton>
        <ActionBarButton v-if="data1.id" :loading="loading" round  type="danger" @click="onDelete" style="font-size: calc(var(--van-cell-font-size)*var(--Big));">
          删除
        </ActionBarButton>
      </ActionBar>

    </Form>
    </ActionSheet>
    <DatePicker_Dialog :show.sync="showDate"  @update:show="(val:any)=>showDate=val"  @update:label="(val:any)=>data1.date=val"/>
    <DatePicker_Dialog :show.sync="showDealDate"  @update:show="(val:any)=>showDealDate=val"  @update:label="(val:any)=>data1.dealdate=val"/>
    <Dict_Select type="清欠方式" :initialValue.sync="data1.urgetypename" :show.sync="showUrgeType"  @update:show="(val:any)=>showUrgeType=val" @change="onUrgetypeChange"/>
    <Dict_Select type="清欠结果" :initialValue.sync="data1.urgeresultname" :show.sync="showResult"  @update:show="(val:any)=>showResult=val" @change="onUrgeresultChange"/>
</template>
<script  lang="ts">
import {  ActionBarButton,ActionBar,Form,CellGroup,Field,Button,Card,Uploader,Radio,RadioGroup,showDialog,showConfirmDialog, showToast,ActionSheet } from 'vant';


import UserSelect from '../invoice/components/UserSelect.vue';


import Flow_Steps from '@/views/finance/components/Flow_Steps.vue';
import { useUserStore } from '@/stores';
import TimePicker_Dialog from '../press/components/TimePicker_Dialog.vue';

import UserAvatarBox from '../finance/components/UserAvatarBox.vue';
import {  saveurgelog, delurgelog } from './api';
import Uploader_Component from '../invoice/components/Uploader_Component.vue';
import DatePicker_Dialog from '../press/components/DatePicker_Dialog.vue';
import Dict_Select from '../invoice/components/Dict_Select.vue';


const cacheStore = useUserStore()

  export default {
    components: {
      ActionBarButton,ActionBar,
      Dict_Select,UserAvatarBox,DatePicker_Dialog,TimePicker_Dialog,Flow_Steps,UserSelect,Form,CellGroup,Field,Button,Card,Uploader,Radio,RadioGroup,Uploader_Component,ActionSheet
    },
    props: ['show','data','readonlyUrgeType'],
    data () {
      return {
        showResult:false,
        showUrgeType:false,
        showDealDate:false,
        currentUser:{},
        loading:false,
        showDate:false,
        visible:false,
        data1:<any>{},
        showContract:false,


          
      }
    },
    watch:{
    
      show(val){
        this.visible = val
      },
      visible(val){
        this.$emit('update:show',val)
      },
      data(val){
        this.data1 = { ...val }
      }
      
    },
    mounted() {
      this.currentUser = cacheStore.userInfo
      this.data1 = { ...this.data }
    },
    created() {
    },
    methods:{
     onUrgeresultChange(val:any){
      this.data1.urgeresultname = val.text
      this.data1.urgeresult = val.value
    },
    onUrgetypeChange(val:any){
        this.data1.urgetypename = val.text
        this.data1.urgetype = val.value
      },
      onSubmit(values:any){
        if (!this.data1.debturgeid){
          return showDialog({message:'debturgeid 不能为空'})
        }
        console.log(this.data1)
        if (this.loading) return
        showConfirmDialog({
          title: '确定提交吗？',
          confirmButtonText: '确定',
          cancelButtonText: '取消',
        }).then(() => { 
          this.loading = true
          saveurgelog({obj:this.data1}).then((res:any)=>{
            this.loading = false
            if (res.errorMessage){
              showDialog({message:res.errorMessage})
            }else{
              showDialog({message:'提交成功'})
              this.visible = false
              this.$emit('change', this.data1);
    
            } 
          })
        });
        
      },
      onDelete(){
        showConfirmDialog({
          title: '确定删除吗？',
          confirmButtonText: '确定',
          cancelButtonText: '取消',
        }).then(() => { 
          this.loading = true
          delurgelog({id:this.data1.id}).then((res:any)=>{
            this.loading = false
            if (res.errorMessage){
              showDialog({message:res.errorMessage})
            }else{
              this.visible = false
              this.$emit('change', this.data1);
            } 
          })
        });
      }

       

    }
  }
</script>
<style  lang="css" src="@/views/financeCss.css">



  
</style>

<style>
  @media screen and (min-width: 500px) {


  }
</style>
