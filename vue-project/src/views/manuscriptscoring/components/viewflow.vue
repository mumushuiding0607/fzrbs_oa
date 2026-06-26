
<template>
  
  <div  class="box1">

     <div v-if="data.info.userName&&data.info.thirdNo" class="row" :style="{padding:'8px'}">
        <Image width="50" radius="10" height="50" fit="cover" :round="false" :src="data.info?.avatar"/>
        <span class="title" :style="{marginLeft:'5px',marginRight:'5px',fontWeight:'bold',color:'black!important'}">{{data.info?.userName}}的申请</span>
        <Tag size="large" plain :type="tagcolor">{{data.statusCn[data.info.status]}}</Tag> 
    
     </div>
     
     <CellGroup>

       <div class="cell" v-if="data.info?.thirdNo" @click="copy(data.info?.thirdNo)">
         <div class="label" >审批单号</div>
         <div class="value">{{data.info?.thirdNo }}</div>
       </div>
       <div class="cell" @click="view">
         <div class="label" >标题</div>
         <div class="value" style="color: var(--van-blue);">{{data.info?.title }}</div>
       </div>
       <div class="cell" @click="copy(data.info?.date)">
         <div class="label" >发布时间</div>
         <div class="value">{{data.info?.date }}</div>
       </div>
       
       <div class="cell" >
         <div class="label" >版次</div>
         <div class="value">{{data.info?.edition }}</div>
       </div>
       <div class="cell" >
         <div class="label" >值班领导</div>
         <div class="value">{{data.info?.approvalUsername }}</div>
       </div>
       <div class="cell" >
         <div class="label" >记者</div>
         <Icon name="add" size="24" @click="showUser=true"/>
       </div>
       <UserScoring  :datas="data.info?.scores" @update:value="(val:any)=>data.info.scores=val" :edit="StatesEnum.PASS!=data.info.status" />
      <UserSelect :show.sync="showUser"  @update:show="(val:any)=>showUser=val" @update:value="(val:any)=>{
      
            data.info?.scores.push({userid:val.userid,name:val.name,score:0})
          }"/>
      
    </CellGroup>
    <div v-if="data.viewdata" class="cell title"  style="margin-top: 20px;">审批流程:</div>
    <Flow_Steps   :data="{viewdata:data.viewdata,statusCn:statusCn}"/>

    <Divider/>
    <Field
      v-if="StatesEnum.ING==data.info.status"
      v-model="speech"
      rows="3"
      autosize
      label=""
      type="textarea"
      maxlength="50"
      placeholder="请输入审批意见"
      show-word-limit
    />
  <ViewHtml class="html-content" :data="data.info.content"  :show.sync="showHtml"  @update:show="(val:any)=>showHtml=val"/>
    
    
    <ActionBar >
     

 
      <ActionBarButton v-if="data.info.userId==userid&&!data.info.thirdNo" type="primary" :loading="doing" :text="'提交'"  @click="commit()"/>
    
      <!-- 经办 -->
   
      
      <!-- 审批人操作 -->
      <ActionBarButton v-if="showApproverBtn" type="primary" :text="'同意'"  @click="agree({act:'agree'})"/>
      <ActionBarButton v-if="showApproverBtn" type="default" text="驳回"  @click="reject({act:'reject'})"/>
   

      <!-- 经办操作 -->
      <ActionBarButton v-if="showApplierBtn" icon="" text="催办" type="primary" @click="urge({act:'urge'})"/>
      <ActionBarButton v-if="showApplierBtn" text="撤销"  type="default" @click="cancel({act:'cancel'})"/>
      <ActionBarButton v-if="showApplierBtn" text="修改"  type="warning" @click="edit"/>
      
    </ActionBar>
    
  
    
  </div>
</template>
<script  lang="ts">

import { Icon,Divider,ActionSheet, Image,Tag,Cell,CellGroup,Steps,Step,Badge,Field,Button,showDialog,showConfirmDialog,Dialog,Uploader,ActionBar,ActionBarIcon,ActionBarButton,Rate, showToast} from 'vant';

import Filescard from '@/views/budget/components/filescard.vue'
import { FlowStateEunm} from '@/views/invoice/invoicing_config';
import { InvoicingStatesEnum } from '@/views/invoice/invoicing_config'
import { useUserStore } from '@/stores';
import { storeToRefs } from 'pinia';
import { softKeyboard } from '../../../utils/common'

import Budgetdetail from '@/views/budget/components/budgetdetail.vue'

import UserSelect from '@/views/invoice/components/UserSelect.vue'
import { commit, commitscore, flowact, getflowdata, save, startflow } from '../api';

import {  StatesEnum } from '../config';
import Flow_Steps from '@/views/finance/components/Flow_Steps.vue';
import UserScoring from './UserScoring.vue';
import ViewHtml from './ViewHtml.vue';


const { loginStatus, userInfo } = storeToRefs(useUserStore());
export default {
  components: {Icon,ViewHtml,Rate,ActionSheet,Budgetdetail,UserSelect,Flow_Steps,
    UserScoring,Image,Tag,Divider,Cell,CellGroup,Steps,Step,Badge,Filescard,Field,Button,Dialog,Uploader,ActionBar,ActionBarIcon,ActionBarButton
  },
  props: ['thirdNo','id'],
  data () {
    return {
      FlowStateEunm:FlowStateEunm,
      InvoicingStatesEnum:InvoicingStatesEnum,
      StatesEnum:StatesEnum,
      companyid:0,
      showHtml:false,
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
      statusCn:["", "审批中", "已同意", "已驳回", "已取消",'已结束'],
      data:<any>{info:{},basic:{},statusCn:["", "审批中", "已同意", "已驳回", "已取消",'已结束']},
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
      statecolor:['default','primary','warning','gray','gray','success'],
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


  },
  created() {
    
  },
  methods:{
    view(){
      console.log('vew')
      this.showHtml = true
    },
    async copy(val:any) {
        try {
          // 将指定的值写入剪贴板
          await navigator.clipboard.writeText(val);

          showDialog({message:'复制成功：'+val})
        } catch (err) {
          console.log(err)
        }
    },
    commit(){
      console.log(this.data.info)
      if(!this.data.info?.scores||this.data.info?.scores.length==0){
        showToast('没有评分项！')
        return
      }
      // if(!this.data.info?.editor){
      //   showToast('请设置值班编辑！')
      //   return
      // }
      
      showConfirmDialog({title:'确定提交吗？'}).then(()=>{
        commitscore({data:{id:this.data.info.id,scores:this.data.info?.scores}}).then((res:any)=>{ 
          if (res.errorMessage){
            showDialog({'message':res.errorMessage})
          }else{
            this.curThirdNo = res.thirdNo
            this.getdata()
          }
        })
      })
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
   
    getdata(){
      
  

      getflowdata({thirdNo:this.thirdNo,id:this.id}).then((res:any)=>{
      
   
        this.data = res
        
        this.showApplierBtn =( StatesEnum.ING==res.info.status)&&(res.info.userId==this.userid)
        
        
        if (res.info && res.info.approvalUserid){
          this.showApproverBtn = res.isCurApprover||(StatesEnum.ING==res.info.status&& res.info.approvalUserid.split('|').some((item:any)=>item==this.userid)) 
          // 是否包含当前用户
  
        }
        if(res && res.info){
            this.tagcolor = this.statecolor[res.info?.status] || 'default'
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
      if (!flow.thirdNo) flow.thirdNo = this.data.info?.thirdNo
      flow.speech = this.speech
      showConfirmDialog({
            title: '确定【同意】吗？',
          })
            .then(() => {
              flowact(flow).then((res:any)=>{
                if (res.errorMessage){
                  showDialog({'message':res.errorMessage})
                }else{
                  
                  this.getdata()
                }
              })
            })
            .catch(() => {
            });
      
    },
    reject(flow:any){
      if (!flow.thirdNo) flow.thirdNo = this.data.info?.thirdNo
      flow.speech = this.speech
      if(!this.speech){
        showDialog({'message':'请填写驳回意见'})
        return 
      }
      showConfirmDialog({
            title: '确定【驳回】吗？',
          })
            .then(() => {
              flowact(flow).then((res:any)=>{
                if (res.errorMessage){
                  showDialog({'message':res.errorMessage})
                }else{
                  
                  this.getdata()
                }
              })
            })
            .catch(() => {
            });
      
    },
    cancel(flow:any){
      if (!flow.thirdNo) flow.thirdNo = this.data.info?.thirdNo
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
      if (!flow.thirdNo) flow.thirdNo = this.data.info?.thirdNo
      flowact(flow).then((res:any)=>{
        if (res.errorMessage){
        }else{
          showDialog({'message':'催办成功'})
        }
      })
    },
    
    act(flow:any){
      if (!flow.thirdNo) flow.thirdNo = this.data.info?.thirdNo||this.data.info?.thirdno
      flow.speech = this.speech
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
    edit(){
      save({obj:{id:this.data.info.id,scores:this.data.info.scores,
        editor:this.data.info.editor,
        editorid:this.data.info.editorid
      }}).then((res:any)=>{ 
        if (res.errorMessage){
          showDialog({'message':res.errorMessage})
        }else{
          showToast('修改成功')
        }
      })
    },

  
  }
}
</script>
<style   lang="css" src="@/views/financeCss.css"></style>
<style >
.html-content :deep(img),
.html-content img {
  display: block;
  margin: 10px auto;
  max-width: 100%;
  height: auto;
}
</style>
