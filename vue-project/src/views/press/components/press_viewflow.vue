
<template>
  
  <div  class="box1">

     <div v-if="data.flowdata.userName" class="row" :style="{padding:'8px'}">
        <Image width="50" radius="10" height="50" fit="cover" :round="false" :src="data.flowdata.avatarUrl"/>
        <span class="title" :style="{marginLeft:'5px',marginRight:'5px',fontWeight:'bold',color:'black'}">{{data.flowdata.userName}}的签付印延迟申请</span>
        <Tag size="large" plain :type="tagcolor" class="head">{{data.statusCn[data.flowdata.status]}}</Tag> 
     </div>
     <div v-if="!data.flowdata.userName" class="header">签付印延迟申请</div>
     <CellGroup>

       <div class="cell" @click="copy(data.flowdata.thirdNo)">
         <div class="label" >单号</div>
         <div class="value">{{data.flowdata.thirdNo }}</div>
       </div>
       <div  class="cell">
          <div class="label">部门</div>
          <div class="value">{{data.flowdata.department }}</div>
        </div>

       <div  class="cell" @click="copy(data.info?.paper)">
         <div class="label">报纸</div>
         <div class="value">{{data.info?.paper }}</div>
       </div>
       <div v-if="data.info?.date" class="cell" @click="copy(data.info?.date)">
         <div class="label">日期</div>
         <div class="value">{{data.info?.date.substr(0,10) }}</div>
       </div>
       <div v-if="data.info?.date" class="cell">
         <div class="label">延误</div>
         <div class="value">{{data.info?.time||data.info?.date.substr(10) }}</div>
       </div>
       <div  class="cell" @click="copy(data.info?.layout)">
         <div class="label">版面</div>
         <div class="value">{{data.info?.layout }}</div>
       </div>
       <div v-if="data.info?.reason" class="cell">
         <div class="label">原因</div>
         <div class="value">{{data.info?.reason }}</div>
       </div>

     

       <div v-if="data.flowdata.annex" class="cell">
         <div class="label">附件：</div>
       </div>
       <Filescard v-if="data.annex" :urls="data.annex"/>


    </CellGroup>
    <div v-if="data.viewdata" class="cell title"  style="margin-top: 20px;">审批流程:</div>
    <Flow_Steps   :data="data"/>

    <Divider/>
    <Field
      v-if="StatesEnum.ING==data.flowdata.status"
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
     

      <ActionBarButton v-if="StatesEnum.PASS==data.flowdata.status" type="primary" :loading="doing" :text="'生成审批单'"  @click="showPrint=true"/>
     



      
      <!-- 经办 -->
   
      
      <!-- 审批人操作 -->
      <ActionBarButton v-if="showApproverBtn" type="primary" :text="'同意'"  @click="agree({act:'agree'})"/>
      <ActionBarButton v-if="showApproverBtn" type="default" text="驳回"  @click="reject({act:'reject'})"/>
     

      <!-- 经办操作 -->
      <ActionBarButton v-if="showApplierBtn" icon="" text="催办" type="primary" @click="urge({act:'urge'})"/>
      <ActionBarButton v-if="showApplierBtn" type="warning" :text="'修改'"  @click="edit"/>
      <ActionBarButton v-if="showApplierBtn" text="撤销"  type="default" @click="cancel({act:'cancel'})"/>
      
    </ActionBar>
    
    <UserSelect  :show.sync="showUser" @update:show="(val:any)=>showUser=val" @update:value="(val:any)=>transferUser=val" ></UserSelect>
    
    <Print_Dialog :show.sync="showPrint" @update:show="(val:any)=>showPrint=val" :thirdNo="data.flowdata?.thirdNo"></Print_Dialog>
  </div>
</template>
<script  lang="ts">

import { Divider,ActionSheet, Image,Tag,Cell,CellGroup,Steps,Step,Badge,Field,Button,showDialog,showConfirmDialog,Dialog,Uploader,ActionBar,ActionBarIcon,ActionBarButton} from 'vant';

import Filescard from '@/views/budget/components/filescard.vue'
import { FlowStateEunm} from '@/views/invoice/invoicing_config';
import { InvoicingStatesEnum } from '@/views/invoice/invoicing_config'
import { useUserStore } from '@/stores';
import { storeToRefs } from 'pinia';
import { softKeyboard } from '../../../utils/common'

import Budgetdetail from '@/views/budget/components/budgetdetail.vue'

import UserSelect from '@/views/invoice/components/UserSelect.vue'
import { flowact, getflowdata, startflow } from '@/views/press/press';

import {  StatesEnum } from '../press_config';
import Flow_Steps from '@/views/finance/components/Flow_Steps.vue';
import Print_Dialog from '@/views/press/components/Print_Dialog.vue';

const { loginStatus, userInfo } = storeToRefs(useUserStore());
export default {
  components: {
  ActionSheet,Budgetdetail,UserSelect,Flow_Steps,Print_Dialog,
    Image,Tag,Divider,Cell,CellGroup,Steps,Step,Badge,Filescard,Field,Button,Dialog,Uploader,ActionBar,ActionBarIcon,ActionBarButton
  },
  props: ['thirdNo'],
  data () {
    return {

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
      data:<any>{info:{},flowdata:{},statusCn:["", "审批中", "已同意", "已驳回", "已取消"]},
      step:0,
      speech:'',
      userid:'',
      showbar:false,
      showbar2:false,
      show:false,
      files:<any>[],
      uploadImage:<any>[],
      softKeyboard:softKeyboard,
      
      contracturls:'',
      statecolor:['default','primary','success','default','default'],
      tagcolor:<any>'default',
      doing:false,
      showIU:false,
      invoicekey: 0,
      showPdf:false,
      pdffileurls:'',
      showPrint:false,
      addContract:false,
      contractCur:<any>{},
      isSalary:false,
      showApplierBtn:false,
      showApproverBtn:false,
    }
  },
  
  mounted() {
  
   this.userid = userInfo.value.userId
   
   this.curThirdNo = this.thirdNo
   this.getdata()
  },
  watch:{
      
    thirdNo(val){
      
      this.curThirdNo = val
      if (val){
        this.getdata()
      }
    },
    transferUser(val:any){

      if (val&&val.name){
        showConfirmDialog({
            title: '确定要转给['+val.name+']吗？',
          })
            .then(() => {
              this.act({act:'alter',userid:this.transferUser.userid,step:this.step,thirdNo:this.data.info?.thirdNo})
              this.showUser = false
            })
            .catch(() => {
            });

      }
      
    },

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
    startflow(act:any){
      this.doing = true
      setTimeout(() => {
        this.doing = false
      }, 5000);
      startflow({act,thirdNo:this.thirdNo}).then((res:any)=>{
     
        if(res.errorMessage){}else{
          this.curThirdNo = res.thirdNo
          this.getdata()
          
        }
      })
    },
    edit(){
      this.$router.push({name:'press_add',query:{thirdNo:this.thirdNo}})
    },
    reapply(){
      this.$router.push({name:'press_add',query:{thirdNo:this.thirdNo,action:'reapply'}})
    },
    
    getdata(){
      
      if (!this.thirdNo){
        showDialog({message:'thirdNo 为空'})
        return
      }

      getflowdata({thirdNo:this.thirdNo}).then((res:any)=>{
      
   
        this.data = res
    
        this.showApplierBtn = StatesEnum.ING==res.flowdata.status&&res.flowdata.userId==this.userid
        
        
        if (res.flowdata && res.flowdata.approvalUserid){
          this.showApproverBtn = res.isCurApprover||(StatesEnum.ING==res.flowdata.status&& res.flowdata.approvalUserid.split('|').some((item:any)=>item==this.userid)) 
          // 是否包含当前用户
  
        }
        if(res && res.flowdata){
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

    transfer(){

      this.showUser = true
    },
    confirmTransfer(){
      
      if (!this.transferUser){
        showDialog({message:'请选择转审人'})
        return
      }
     
      this.act({act:'alter',userid:this.transferUser.userid,step:this.step,thirdNo:this.data.flowdata.thirdNo})
      this.showTransfer = false
    },
  
  }
}
</script>
<style   lang="css" src="@/views/financeCss.css">

  
</style>
<style scoped>
.cell .label {

    width: calc(var(--van-field-label-width)* 0.5)!important;

  }
</style>