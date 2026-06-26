
<template>
  
  <div  class="box1">
    <div v-if="data.info?.reject==1" class="mask">
      <div class="value" style="z-index: 10000;">
          <p  :style="{color:'red',fontWeight:'bold',fontSize:'25px'}">{{ '已驳回' }}</p>
          <div v-if="userid==data.info?.userId" :style="{color:'black',fontWeight:'bold',fontSize:'25px'}" @click="continueA()" >继续审批</div>
      </div>
    </div>
     <div v-if="data.info.userName" class="row" :style="{padding:'8px'}">
        <Image width="50" radius="10" height="50" fit="cover" :round="false" :src="data.info?.avatar"/>
        <span class="title" :style="{marginLeft:'5px',marginRight:'5px',fontWeight:'bold',color:'black'}">{{data.info?.userName}}的付款申请</span>
        <Tag size="large" plain :type="tagcolor" class="head">{{data.statusCn[data.info.status]}}</Tag> 
        <Tag v-if="data.info.verified" size="large" plain type="default" class="head">出纳确认</Tag> 
     </div>

     <CellGroup>

       <div class="cell" @click="copy(data.info?.thirdNo)">
         <div class="label" >唯一单号</div>
         <div class="value">{{data.info?.thirdNo }}</div>
       </div>
       <div class="cell">
         <div class="label">审批类型</div>
         <div class="value">{{data.info?.amountsTypeName }}</div>
       </div>
       <div v-if="!isSalary" class="cell">
          <div class="label">申请部门</div>
          <div class="value">{{data.info?.department }}</div>
        </div>
       <div v-if="data.info?.payer" class="cell"  @click="copy(data.info?.payer)">
          <div class="label">{{ isSalary?'申请部门':'付款单位' }}</div>
          <div class="value">{{data.info?.payer }}</div>
        </div>
        <div v-if="data.info?.receiver" class="cell">
          <div class="label">{{ isSalary?'工资月份':'收款单位' }}</div>
          <div class="value">{{data.info?.receiver }}</div>
        </div>
       <div class="cell" @click="copy(data.info?.amount)">
         <div class="label">{{ isSalary?'应发金额':'付款金额' }}</div>
         <div class="value">￥{{data.info?.amount }}</div>
       </div>
       <div class="cell" @click="copy(data.info?.amountCap)">
         <div class="label">大写金额</div>
         <div class="value">￥{{data.info?.amountCap }}</div>
       </div>
       <div v-if="isSalary" class="cell" @click="copy(data.info?.amountreal)">
         <div class="label">实发金额</div>
         <div class="value">￥{{data.info?.amountreal }}</div>
       </div>
       <div v-if="isSalary" class="cell" @click="copy(data.info?.amountrealCap)">
         <div class="label">实发大写</div>
         <div class="value">￥{{data.info?.amountrealCap }}</div>
       </div>
      <div v-if="!isSalary&&data.info?.payStatusName" class="cell">
         <div class="label">付款方式</div>
         <div class="value">{{data.info?.payStatusName }}</div>
       </div>
       <div v-if="data.info?.reason" class="cell">
         <div class="label">{{ isSalary?'工资项目':'付款事项' }}</div>
         <div class="value">{{data.info?.reason }}</div>
       </div>
       <div v-if="!isSalary" class="cell" @click="copy(data.info?.account)">
         <div class="label">银行账号</div>
         <div class="value">{{data.info?.account }}</div>
       </div>
       <div v-if="!isSalary" class="cell" @click="copy(data.info?.bank)">
         <div class="label">开户银行</div>
         <div class="value">{{data.info?.bank }}</div>
       </div>
       <div v-if="!isSalary&&data.info.caruseridNames"  class="cell">
         <div class="label">用车人</div>
         <div class="value">{{data.info?.caruseridNames }}</div>
       </div>
       <div v-if="!isSalary&&data.info.certifierNames" class="cell">
         <div class="label">证明人</div>
         <div class="value">{{data.info?.certifierNames }}</div>
       </div>

     
       <div v-if="data.info?.fileurls" class="cell">
         <div class="label">合同附件：</div>
       </div>
       <Filescard v-if="data.info?.fileurls" :urls="data.info?.fileurls" :preview="true"/>

       <div v-if="data.info?.annex" class="cell">
         <div class="label">附件：</div>
       </div>
       <Filescard v-if="data.info?.annex" :urls="data.info?.annex" :showIcon="true" :preview="true"/>
       <div v-if="data.info?.invoice" class="cell">
         <div class="label">发票：</div>
       </div>
       <Filescard v-if="data.info?.invoice" :urls="data.info?.invoice"/>

    </CellGroup>
    <div v-if="data.viewdata" class="cell title"  style="margin-top: 20px;">审批流程:</div>
    <Flow_Steps   :data="data" :approvalUserids="data.info?.approvalUserid" @updateApproval="updateApproval"/>

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
    <div style="height: 50px;"></div>
    
    <ActionBar >
     

      <ActionBarButton v-if="StatesEnum.PASS==data.info.status" type="warning" :loading="doing" :text="'生成付款审批单'"  @click="showPrint=true"/>
      <ActionBarButton v-if="data.info.userId=userid&&(StatesEnum.PASS==data.info.status||StatesEnum.CANCEL==data.info.status||StatesEnum.REJECT==data.info.status)" type="primary" :loading="doing" :text="'再申请一次'"  @click="reapply()"/>
      <ActionBarButton v-if="StatesEnum.PASS==data.info.status&&data.needverify&&!data.info?.verified" type="default" :loading="doing" :text="'出纳确认'"  @click="verify()"/>


      
      <!-- 经办 -->
   
      
      <!-- 审批人操作 -->
      <ActionBarButton v-if="showApproverBtn" type="primary" :text="'同意'"  @click="agree({act:'agree'})"/>
      <ActionBarButton v-if="showApproverBtn" type="default" text="驳回"  @click="reject({act:'reject'})"/>
      <ActionBarButton v-if="showApproverBtn" type="warning" text="转审" @click="transfer()"/>

      <!-- 经办操作 -->
      <ActionBarButton v-if="showApplierBtn" icon="" text="催办" type="primary" @click="urge({act:'urge'})"/>
      <ActionBarButton v-if="showApplierBtn&&data.canUpate" type="warning" :text="'修改'"  @click="edit"/>
      <ActionBarButton v-if="showApplierBtn" text="撤销"  type="default" @click="cancel({act:'cancel'})"/>
      
    </ActionBar>
    

    <ActionSheet v-model:show="showTransfer" title="转审确认" style="height:50vh;">
      <div @click="()=>showUser=true" style="z-index: 1000;;background-color: white;height: 60px;display: flex;align-items: center;padding: 15px;">
        <span class="title" style="color: gray;">转给：</span>
        <span class="title" v-if="transferUser.name">{{ transferUser.name }}</span>
        <span style="color: gray;" v-if="!transferUser.name">点击输入用户名</span>
      </div>
      <div style="padding: 10px;position: absolute;bottom: 0;width: 100%;">
        <Button type="primary" block @click="confirmTransfer()">确定转审</Button>
      </div>
      
      
    </ActionSheet>
    <UserSelect  :show.sync="showUser" @update:show="(val:any)=>showUser=val" @update:value="(val:any)=>transferUser=val" ></UserSelect>
    <Print_Dialog :key="pkey" :show.sync="showPrint" @update:show="(val:any)=>showPrint=val" :thirdNo="data.info?.thirdNo" @change="onPrintChange"></Print_Dialog>
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
import { flowact, getflowdata, startflow, updateapproval, verify } from '@/views/finance/finance';
import Print_Dialog from './Print_Dialog.vue';
import { FinanaceType, StatesEnum } from '../finance_config';
import Flow_Steps from '@/views/finance/components/Flow_Steps.vue';

const { loginStatus, userInfo } = storeToRefs(useUserStore());
export default {
  components: {
   Print_Dialog,ActionSheet,Budgetdetail,UserSelect,Flow_Steps,
    Image,Tag,Divider,Cell,CellGroup,Steps,Step,Badge,Filescard,Field,Button,Dialog,Uploader,ActionBar,ActionBarIcon,ActionBarButton
  },
  props: ['thirdNo'],
  data () {
    return {
      FinanaceType:FinanaceType,
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
      data:<any>{info:{},basic:{},statusCn:["", "审批中", "已同意", "已驳回", "已取消"]},
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
      pkey:1
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
    updateApproval(e:any){
      updateapproval({...e,thirdNo:this.thirdNo}).then((res:any)=>{ 
        if (res.errorMessage){
        }else{
          showDialog({'message':'更新成功'})
          this.getdata()
        }
      })
    },
    onPrintChange(){
   
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
      this.$router.push({name:'finance_add',query:{thirdNo:this.thirdNo}})
    },
    reapply(){
      this.$router.push({name:'finance_add',query:{thirdNo:this.thirdNo,action:'reapply'}})
    },
    
    getdata(){
      
      if (!this.thirdNo){
        showDialog({message:'thirdNo 为空'})
        return
      }

      getflowdata({thirdNo:this.thirdNo}).then((res:any)=>{
      
   
        this.data = res
        this.isSalary = [FinanaceType.SALARY,FinanaceType.SALARY_CROSS].some(item=>item==res.info?.amountsType)
        this.showApplierBtn = StatesEnum.ING==res.info.status&&res.info.userId==this.userid
        
        
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
    verify(){
      showConfirmDialog({
            title: '确认无误？',
          })
            .then(() => {
              verify({thirdNo:this.data.info.thirdNo}).then((res:any)=>{
                if (res.errorMessage){
                  showDialog({'message':res.errorMessage})
                }else{
                  showDialog({'message':'操作成功'})
                  this.getdata()
                }
              })
            })
            .catch(() => {
            });
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
    continueA(){
      this.act({act:'continue'})

    },
    transfer(){

      this.showUser = true
    },
    confirmTransfer(){
      
      if (!this.transferUser){
        showDialog({message:'请选择转审人'})
        return
      }
     
      this.act({act:'alter',userid:this.transferUser.userid,step:this.step,thirdNo:this.data.info?.thirdNo})
      this.showTransfer = false
    },
  
  }
}
</script>
<style   lang="css" src="@/views/financeCss.css">


  
 
</style>
