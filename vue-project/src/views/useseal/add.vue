
<template>
  <div class="box">
    
      
      <Form @submit="onSubmit" required="auto">
        <Field
            readonly
            required
            label="申请类别"
          />
          <Dict_Radio type="用印申请类别" order="value asc" :initialValue="data.usesealType||1"  @update:value="(val:any)=>data.usesealType=val"/>
          <div v-if="[1,2].includes(data.usesealType)">

            <Field
              readonly
              required
              label="协议金额"

            />
          <Dict_Radio :key="data.id" type="用印协议金额" order="value asc" :initialValue="data.amountsType"  @update:value="(val:any)=>data.amountsType=val"/>
          </div>
          
          <Field name="type" label="工会申请" required>
            <template #input>
              <RadioGroup v-model="data.flowtype" direction="horizontal">
                <Radio :name="0">否</Radio>
                <Radio :name="1">是</Radio>
              </RadioGroup>
            </template>
          </Field>
    
          <Field
            v-model="data.usesealReason"
            name="usesealReason"
            type="textarea"
            rows="3"
            :autosize="true"
            label="申请事由"
            placeholder="请输入申请事由"
            required
            
            :rules="[{ required: true, message: '不能为空' }]"
          />
 
          

          <Field
           
            label="附件上传"
            readonly
            required
    
          />
          <Uploader_Component style="margin:10px 10px 10px 20px" :value.sync="data.annex"   @update:value="(val:any)=>data.annex=val"    />
 
   

      <div style="margin: 16px;">
        
        <Button v-if="!data.id" :loading="loading" round block type="primary" native-type="submit" style="font-size: calc(var(--van-cell-font-size)*var(--Big));">
          提交
        </Button>
    
        <Button v-if="data.id" round block type="primary" @click="update" style="font-size: calc(var(--van-cell-font-size)*var(--Big));">
          修改
        </Button>
      </div>
    </Form>


   

    


    
  </div>
</template>
<script  lang="ts">
import {h} from 'vue'
import {  Form,CellGroup,Field,Button,Card,Uploader,Radio,RadioGroup,showDialog,showConfirmDialog,Switch, showToast } from 'vant';



import Dict_Select from '../invoice/components/Dict_Select.vue';

import UserSelect from '../invoice/components/UserSelect.vue';

import Filescard from '../budget/components/filescard.vue';

import Upload_Dialog from '../invoice/components/Upload_Dialog.vue';

import Flow_Steps from '@/views/finance/components/Flow_Steps.vue';
import { getdata, getdriverleader, getflow, save } from './api';
import { extractParameterFromUrl } from '../budget/utils';
import { useUserStore } from '@/stores';
import Dict_Radio from '../invoice/components/Dict_Radio.vue';
import Uploader_Component from '../invoice/components/Uploader_Component.vue';

const cacheStore = useUserStore()

  export default {
    components: {
      Uploader_Component,Dict_Radio,Flow_Steps,Upload_Dialog,Filescard,UserSelect,Dict_Select,Form,CellGroup,Field,Button,Card,Uploader,Radio,RadioGroup,Switch,
    },
    
    data () {
      return {
        extractParameterFromUrl:extractParameterFromUrl,
        data:<any>{flowtype:0},
       
        uploadImage:<any>[],
        files:<any>[],
        usesealTypename:'',
        amountsTypename:'',
        showAnnex:false,
        action:<any>'',
        currentUser:{},
        loading:false,
        showUsesealType:false,
        showAmountsType:false,
        annexNames:''
          
      }
    },
    watch:{
      
    },
    mounted() {
      this.currentUser = cacheStore.userInfo
      var thirdNo = this.$route.query?.thirdNo;
      this.action = this.$route.query.action||''

      if (thirdNo){
        getdata({thirdNo:thirdNo}).then((res:any)=>{
          if (res){
            this.data = res.info
            
            console.log('data.amountsType:',this.data.amountsType)
            
            if (this.action=='reapply'){
              delete this.data.id
              delete this.data.thirdNo
              delete this.data.inserttime
              delete this.data.annex
   
            }
 
          }
        })
      }

    },
    created() {
    },
    methods:{

      update(){

        console.log(this.data)
        save({obj:this.data}).then((res:any)=>{
          if (res.errorMessage) {
            showDialog({'message':res.errorMessage})
          } else {
            this.$router.push({name:'useseal_view',query:{thirdNo:this.data.thirdNo}})
          }
        })
      },

 
      onSubmit(values:any){
      
        
        var err = ''
        if (err){
          showDialog({'message':err})
          return
        }
   

        
        
        // const {payerid,payer,amount,amountsType,userId} = this.data
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
                      this.$router.push({name:'useseal_view',query:{thirdNo:res.thirdNo}})
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
      onReiverChange(val:any){
        this.data.bank = val.bank
        this.data.account = val.account
      },
       

    }
  }
</script>
<style  lang="css">
:root{
  --Big: 1.3;
}

  .box{
    width: 100%;
    min-height: 100vh;
    border-right: 2px solid #eff2f5;
    border-left: 2px solid #eff2f5;
    margin-left:0 ;
    margin-top: 0;
  }
  .header{
    height: 10.6vw;
    font-size: 4.26vw;
    background: #F1F1F1;
    color:#666;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .van-cell {
    position: relative;
    display: flex;
    box-sizing: border-box;
    width: 100%;
    padding: calc(var(--van-cell-vertical-padding)*var(--Big)) calc(var(--van-cell-horizontal-padding));
    overflow: hidden;
    color: var(--van-cell-text-color);
    font-size: calc(var(--van-cell-font-size)*var(--Big));
    line-height: var(--van-cell-line-height);
    background: var(--van-cell-background);
}
.van-field__label {
    flex: none;
    box-sizing: border-box;
    width: calc(var(--van-field-label-width)*0.9);
    margin-right: 0;
    color: var(--van-field-label-color);
    text-align: left;
    word-wrap: break-word;
}


  
</style>

<style>
  @media screen and (min-width: 500px) {

    .header{
      height: 40px;
      font-size: 20px;
      background: #F1F1F1;
      color:#666;
      display: flex;
      align-items: center;
      justify-content: center;
    }
  }
</style>
