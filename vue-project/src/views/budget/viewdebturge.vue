
<template>
  
  <div  class="box1">

     <div v-if="data.flowdata?.userName" class="row" :style="{padding:'8px'}">
        <Image width="50" radius="10" height="50" fit="cover" :round="false" :src="data.flowdata.avatarUrl"/>
        <span class="title" :style="{marginLeft:'5px',marginRight:'5px',fontWeight:'bold',color:'black'}">{{data.flowdata.userName}}的催收审批</span>
        <Tag size="large" plain :type="tagcolor" class="head">{{data.statusCn[data.flowdata?.status]}}</Tag> 
     </div>
     
     <CellGroup>



       <div v-if="data.flowdata?.thirdNo" class="cell" @click="copy(data.flowdata.thirdNo)">
         <div class="label" >单号</div>
         <div class="value">{{data.flowdata.thirdNo }}</div>
       </div>

       <div  class="cell">
         <div class="label">合同</div>
         <div class="value">{{data.contract?.title }}</div>
       </div>
       <div  class="cell">
         <div class="label">债务方</div>
         <div class="value">{{data.contract?.partaname }}</div>
       </div>
  
       
   
       <div v-if="data.urge?.fileurls" class="cell">
         <div class="label">附件：</div>
       </div>
       <Filescard v-if="data.urge?.fileurls" :urls="data.urge?.fileurls"/>


    </CellGroup>
    <div v-if="data.viewdata" class="cell title"  style="margin-top: 20px;">审批流程:</div>
    <Flow_Steps   :data="data"/>

    <Divider/>
    <Field
      v-if="StatesEnum.ING==data.flowdata?.status"
      v-model="speech"
      rows="3"
      autosize
      label=""
      type="textarea"
      maxlength="50"
      placeholder="请输入审批意见"
      show-word-limit
    />
    <div style="height: 50px;"></div>
    
    <ActionBar >
     

      <!-- 审批人操作 -->
      <ActionBarButton v-if="showApproverBtn" type="primary" :text="'同意'"  @click="agree({act:'agree'})"/>
      <ActionBarButton v-if="showApproverBtn" type="default" text="驳回"  @click="reject({act:'reject'})"/>
     

      <!-- 经办操作 -->
      <ActionBarButton v-if="showApplierBtn" icon="" text="催办" type="primary" @click="urge({act:'urge'})"/>
      <ActionBarButton v-if="showApplierBtn" type="warning" :text="'修改'"  @click="edit"/>
      <ActionBarButton v-if="showApplierBtn" text="撤销"  type="default" @click="cancel({act:'cancel'})"/>
      
    </ActionBar>
    <Dict_Select type="催收方式" :initialValue.sync="data.urge?.urgetypename" :show.sync="showContract"  @update:show="(val:any)=>showContract=val" @update:value="(val:any)=>data.urge.urgetype=val" @update:label="(val:any)=>data.urge.urgetypename=val"/>
  
    
   
  </div>
</template>
<script  lang="ts">

import { Divider,Form,ActionSheet, Image,Tag,Cell,CellGroup,Steps,Step,Badge,Field,Button,showDialog,showConfirmDialog,Dialog,Uploader,ActionBar,ActionBarIcon,ActionBarButton, showToast} from 'vant';

import Filescard from '@/views/budget/components/filescard.vue'
import { FlowStateEunm} from '@/views/invoice/invoicing_config';
import { InvoicingStatesEnum } from '@/views/invoice/invoicing_config'
import { useUserStore } from '@/stores';
import { storeToRefs } from 'pinia';

import { flowact, getflowdata, startflow } from '@/views/press/press';
import {  StatesEnum } from './config';
import Flow_Steps from '@/views/finance/components/Flow_Steps.vue';
import { previewdebtflow, startdebturge, viewdebt } from './budget';
import Companyinfo_Input from '../invoice/components/Companyinfo_Input.vue';
import Dict_Select from '../invoice/components/Dict_Select.vue';
import Uploader_Component from '../invoice/components/Uploader_Component.vue';
import { h } from 'vue';

const { loginStatus, userInfo } = storeToRefs(useUserStore());
export default {
  components: {
  ActionSheet,Flow_Steps,Companyinfo_Input,Form,Dict_Select,Uploader_Component,
    Image,Tag,Divider,Cell,CellGroup,Steps,Step,Badge,Filescard,Field,Button,Dialog,Uploader,ActionBar,ActionBarIcon,ActionBarButton
  },
  data () {
    return {
      query:<any>this.$route.query,
      FlowStateEunm:FlowStateEunm,
      InvoicingStatesEnum:InvoicingStatesEnum,
      StatesEnum:StatesEnum,
      companyid:0,
      showCompany:false,
      customer:<any>{},
      showCustomer:false,
      showContract:false,
      contractids:0,
      projectid:0,
      showProject:false,
      showUser:false,
      showTransfer:false,
      transferUser:<any>{userid:'',name:''},
      curThirdNo:'',
      active:0,
      data:<any>{contract:{},urge:{},flowdata:{},statusCn:["", "审批中", "已同意", "已驳回", "已取消"]},
      step:0,
      speech:'',
      userid:'',
      showbar:false,
      showbar2:false,
      show:false,
      files:<any>[],
      uploadImage:<any>[],
      
      contracturls:'',
      statecolor:['default','primary','success','default','default'],
      tagcolor:<any>'default',
      doing:false,
      loading:false,
      invoicekey: 0,



      showApplierBtn:false,
      showApproverBtn:false,
    }
  },
  
  mounted() {

   this.getdata()
  },

  created() {
    
  },
  methods:{

    async copy(val:any) {
      try {
        // 将指定的值写入剪贴板
        await navigator.clipboard.writeText(val);

        showDialog({message:'复制成功：'+val})
      } catch (err) {
        console.log(err)
      }
    },
 
   

    
    getdata(){


      viewdebt({id:this.query.contractid,thirdNo:this.query.thirdNo}).then((res:any)=>{
      
   
        this.data = res
    
        
        
        
        if (res.flowdata){
          if(res.flowdata.approvalUserid){
            this.showApproverBtn = res.isCurApprover||(StatesEnum.ING==res.flowdata.status&& res.flowdata.approvalUserid.split('|').some((item:any)=>item==this.userid)) 
          }
          this.showApplierBtn = StatesEnum.ING==res.flowdata.status&&res.flowdata.userId==this.userid
  
           this.tagcolor = this.statecolor[res.flowdata?.status] || 'default'
  
        }

    
      
      if (res.viewdata) {
          this.step=res.viewdata.step+1
          const items = res.viewdata.approval[this.step].Items.Item
          for (var i=0;i<items.length;i++){
            if (items[i].ItemStatus==2 && items[i].ItemUserId==this.userid){
              this.showbar = false
              break
            }
          }

        
        }
   
      })
    },

    edit(){
      this.$router.push({name:'press_add',query:{}})
    },
    agree(flow:any){
      if (!flow.thirdNo) flow.thirdNo = this.data.flowdata.thirdNo
      if (this.speech){
        flow.speech = this.speech
      }
      showConfirmDialog({
            title: '确定【同意】吗？',
          })
            .then(() => {
              flowact(flow).then((res:any)=>{
                if (res.errorMessage){
                }else{
                  
                  this.getdata()
                }
              })
            })
            .catch(() => {
            });
      
    },
    reject(flow:any){
      if (!flow.thirdNo) flow.thirdNo = this.data.flowdata.thirdNo
      flow.speech = this.speech
      if(!flow.speech){
        showDialog({message:'请填写驳回理由'})
        return
      }
      showConfirmDialog({
            title: '确定【驳回】吗？',
          })
            .then(() => {
              flowact(flow).then((res:any)=>{
                if (res.errorMessage){
                }else{
                  
                  this.getdata()
                }
              })
            })
            .catch(() => {
            });
      
    },
    cancel(flow:any){
      if (!flow.thirdNo) flow.thirdNo = this.data.flowdata.thirdNo
      showConfirmDialog({
            title: '确定要【撤销】吗？',
          })
            .then(() => {
              flowact(flow).then((res:any)=>{
                if (res.errorMessage){
                }else{
                  showDialog({'message':'撤销成功'})
                  this.getdata()
                }
              })
            })
            .catch(() => {
            });
      
    },
    urge(flow:any){
      if (!flow.thirdNo) flow.thirdNo = this.data.flowdata.thirdNo
      flowact(flow).then((res:any)=>{
        if (res.errorMessage){
        }else{
          showDialog({'message':'催办成功'})
        }
      })
    },
    
    act(flow:any){
      if (!flow.thirdNo) flow.thirdNo = this.data.flowdata.thirdNo
      if (!flow.speech) flow.speech= this.speech
   
      flowact(flow).then((flowres:any)=>{
        if (flowres.errorMessage){
          this.getdata()
        }else{
          if (flowres.data && flowres.data.touser&&flowres.data.touser.indexOf(this.userid)>-1){
            var node = this.data.viewdata.approval[this.step]
            if (node.NodeAttr==2&&node.Items.Item.length>1){
              console.log('当前节点为会签节点且有多人审批，禁止自动连审！')
              this.getdata()
              return
            }
            this.act(flow)
          }else{
            
            this.getdata()
          }
        }
      })
        
    },


 
  
  }
}
</script>
<style   lang="css" src="@/views/financeCss.css">

  
</style>
