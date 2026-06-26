
<template>
  
  <div   style="background-color: white;">
    <div v-if="data.flowdata?.userName" class="row" :style="{padding:'8px'}">
        <Image width="50" radius="10" height="50" fit="cover" :round="false" :src="data.flowdata.avatarUrl"/>
        <span class="title" :style="{marginLeft:'5px',marginRight:'5px',fontWeight:'bold',color:'black'}">{{data.flowdata.userName}}的催收审批</span>
        <Tag size="large" plain :type="tagcolor" class="head">{{data.statusCn[data.flowdata?.status]}}</Tag> 
     </div>
     <Form @submit="onSubmit">
      <CellGroup>


          <div  class="cell" v-if="data.flowdata?.thirdNo">
            <div class="label">审批单号</div>
            <div class="value">{{data.flowdata?.thirdNo }}</div>
          </div>
          <div  class="cell" v-if="data.urge?.serial">
            <div class="label">编号</div>
            <div class="value" >{{data.urge?.serial }}</div>
          </div>
          <div  class="cell">
            <div class="label">合同名称</div>
            <div class="value" >{{data.contract?.title }}</div>
          </div>
          <div  class="cell">
            <div class="label">责任人</div>
            <div class="value" >{{data.contract?.creatorname }}</div>
          </div>
          <div  class="cell">
            <div class="label">合同编号</div>
            <div class="value" >{{data.contract?.serial }}</div>
          </div>
          <div  class="cell" @click="viewCustomer(data.urge?.customer)">
            <div class="label" >债务方</div>
            <div class="value" >{{data.contract?.partaname }}</div>
          </div>
          <div  class="cell">
            <div class="label">账龄</div>
            <div class="value" >{{data.contract?.age }}</div>
          </div>
          <div  class="cell">
            <div class="label">合同总额</div>
            <div class="value" >￥{{data.contract?.amount }}</div>
          </div>
          <div  class="cell">
            <div class="label">催款金额</div>
            <div class="value" >￥{{data.urge?.debtamount||data.contract?.debt }}</div>
          </div>
          


          <div v-if="data.urge?.notifiernames" class="cell" >
            <div class="label">抄送</div>
            <div class="value">{{data.urge?.notifiernames }}</div>
          </div>
          <Field
            label-class="cellLabel"
            v-model="data.urge.note"
            name="note"
            
            label="备注"
            placeholder="备注内容"
            
            :rules="[{ required: false, message: '不能为空' }]"
          />
          <Field
            label-class="cellLabel"
            v-model:model-value="data.urge.urgetypename"
            is-link
            required
            name="urgetypename"
            label="清欠方式"
            placeholder="点击选择清欠方式"
            @click="showContract = true"
          />
          <Field
            label-class="cellLabel"
            v-model="data.urge.reason"
            name="reason"
            label="拖欠原因"
            placeholder="拖欠原因"
            required
            :rules="[{ required: true, message: '不能为空' }]"
          />
 
          
          <Field
            label-class="cellLabel"
            v-model="data.urge.contactor"
            name="contactor"
            label="联系人"
            placeholder="债务方联系人"
            required
            :rules="[{ required: true, message: '不能为空' }]"
          />
          <Field
            label-class="cellLabel"
            v-model="data.urge.mobile"
            name="mobile"
            label="电话"
            placeholder="债务方联系电话"
            required
            :rules="[{ required: true, message: '不能为空' },{pattern: /^(1[3-9]\d{9})|(\d{3,4}-?\d{7,8})$/,message: '请输入正确的手机号或座机号格式'
  }]"
          />
          <Field
            label-class="cellLabel"
            v-model="data.urge.address"
            name="address"
            label="地址"
            placeholder="债务方地址"
            required
            :rules="[{ required: true, message: '不能为空' }]"
          />
          

            <div v-if="data.viewdata" class="cell title"  style="margin-top: 20px;">审批流程:</div>
            <Flow_Steps   :data="data"/>
            <div style="margin: 16px;">
              <Button v-if="!data.urge.state" round block type="primary" native-type="submit">
                {{ '提交' }}
              </Button>
              <Button v-if="data.urge?.state==StatesEnum.END&&data.urge?.creator==userid" round block type="primary" native-type="submit">
                重新申请
              </Button>

            </div>
          
       
       


           

      </CellGroup>
      
      
    </Form>
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
     

    
      <ActionBarButton v-if="showApproverBtn" type="primary" :text="'同意'"  @click="agree({act:'agree'})"/>
      <ActionBarButton v-if="showApproverBtn" type="default" text="驳回"  @click="reject({act:'reject'})"/>
     
      <ActionBarButton v-if="data.flowdata?.status==StatesEnum.CANCEL" type="primary" :text="'再申请一次'"  @click="onSubmit"/>
      <ActionBarButton v-if="showApplierBtn" icon="" text="催办" type="primary" @click="urge({act:'urge'})"/>
      <ActionBarButton v-if="showApplierBtn" type="warning" :text="'修改'"  @click="edit"/>
      <ActionBarButton v-if="showApplierBtn" text="撤销"  type="default" @click="cancel({act:'cancel'})"/>
      <!-- <ActionBarButton v-if="data.urge.state==StatesEnum.PASS" type="primary" :text="'上传附件'"  @click="showAddlog=true"/> -->
    </ActionBar>

  

    <Dict_Select type="清欠方式" :initialValue.sync="data.urge?.urgetypename" :show.sync="showContract"  @update:show="(val:any)=>showContract=val" @change="onUrgetypeChange"/>
      <Dict_Select type="清欠结果" :initialValue.sync="data.urge?.urgeresultname" :show.sync="showResult"  @update:show="(val:any)=>showResult=val" @change="onUrgeresultChange"/>
  
    <Company_Dialog :customer="customer" :show.sync="showCustomer" @update:show="(val:any)=>showCustomer=val"/>
    <AddLog :show.sync="showAddlog"  @update:show="(val:any)=>showAddlog=val" :data="{contractid:data.contract?.id,debturgeid:data.urge.id}"  @change="handleChange"/>
  </div>
</template>
<script  lang="ts">

import { Divider,Form,ActionSheet, Image,Tag,Cell,CellGroup,Steps,Step,Badge,Field,Button,showDialog,showConfirmDialog,Dialog,Uploader,ActionBar,ActionBarIcon,ActionBarButton, showToast} from 'vant';


import Filescard from '@/views/budget/components/filescard.vue'
import { useUserStore } from '@/stores';
import { storeToRefs } from 'pinia';

import {  StatesEnum } from './config';
import Flow_Steps from '@/views/finance/components/Flow_Steps.vue';
import { endurge, flowact, previewdebtflow, startdebturge, updateurge, viewdebt } from './api';
import Companyinfo_Input from '../invoice/components/Companyinfo_Input.vue';
import Dict_Select from '../invoice/components/Dict_Select.vue';
import Uploader_Component from '../invoice/components/Uploader_Component.vue';
import { h } from 'vue';
import Company_Dialog from '../invoice/components/Company_Dialog.vue';
import AddLog from './addLog.vue';
import Log_Steps from './components/Log_Steps.vue';

const { loginStatus, userInfo } = storeToRefs(useUserStore());
export default {
  components: {
  ActionSheet,Flow_Steps,Companyinfo_Input,Form,Dict_Select,Uploader_Component,Company_Dialog,AddLog,Log_Steps,
    Image,Tag,Divider,Cell,CellGroup,Steps,Step,Badge,Filescard,Field,Button,Dialog,Uploader,ActionBar,ActionBarIcon,ActionBarButton
  },
  data () {
    return {
      query:<any>this.$route.query,
      showAddlog:false,
   
      StatesEnum:StatesEnum,
     
      showCompany:false,
      customer:<any>{},
      showCustomer:false,
      showContract:false,
      active:0,
      data:<any>{contract:{},urge:{},flowdata:{},statusCn:["", "审批中", "催款中", "已驳回", "已取消"]},
      step:0,
      speech:'',
      userid:'',
      showbar:false,
      showbar2:false,
      showResult:false,
      statecolor:['default','primary','success','default','default'],
      tagcolor:<any>'default',
      doing:false,
      loading:false,
  
      showApplierBtn:false,
      showApproverBtn:false,
      refreshkey:0,
      logpar:<any>{contractid:'',debturgeid:''},
    }
  },
  
  mounted() {
  this.userid = userInfo.value.userId
   this.getdata()
   this.logpar= {contractid:this.query.contractid,debturgeid:this.query.debturgeid}
  },

  created() {
    
  },
  methods:{
    handleChange(){
      this.refreshkey = this.refreshkey+1
      console.log('添加进度成功')
    },
    onUrgetypeChange(val:any){
      this.data.urge.urgetypename = val.text
      this.data.urge.urgetype = val.value
    },
    onUrgeresultChange(val:any){
      this.data.urge.urgeresultname = val.text
      this.data.urge.urgeresult = val.value
    },
    
    end(){
      if (!this.data.urge.urgeresult){
        showDialog({message:'清欠结果未填写'})
        return
      }
      showConfirmDialog({
        title: '确定【结束】吗？',
      }).then(() => { 
        this.loading = true
        updateurge({obj:{...this.data.urge,state:StatesEnum.END}}).then((res:any)=>{
          this.loading = false
          if (res.errorMessage){
            showDialog({message:res.errorMessage})
          }else{
            this.getdata()
          }
        })
      })
    },
    onSubmit(){
   
      var obj = this.data.urge
      obj.contractid = this.data.contract.id
      obj.partaname = this.data.contract.partaname
      obj.parta = this.data.contract.parta
      obj.age = this.data.contract.age
      delete obj.id

      previewdebtflow({...obj}).then((res:any)=>{
          
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
     
                startdebturge({obj:{...obj}}).then((res:any)=>{
                  if (res.errorMessage){
                    showDialog({'message':res.errorMessage})
                  }else{
                    // 跳转到view页面
                    this.getdata()
                    
                     
      
                  }
                })
              })
              .catch(() => {
                // on cancel
              });

          }
        })
    },
   
    
    getdata(){


      viewdebt({id:this.query.contractid}).then((res:any)=>{
      
   
        this.data = res
        this.logpar.debturgeid= res.urge?.id
        
        this.showApproverBtn = false
        this.showApplierBtn = false
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
    viewCustomer(customer:any){
      console.log(customer)
      if (customer){
        this.showCustomer=true
      this.customer = customer
      }
    },
    edit(){
      showConfirmDialog({ 
        title: '确定【修改】吗？',
      }).then(() => { 
        updateurge({obj:{...this.data.urge}}).then((res:any)=>{
          if (res.errorMessage){
            showDialog({'message':res.errorMessage})
          }else{
            showDialog({'message':'修改成功'})
          }
        })
      });
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
                  // 跳转
                 this.$router.push({name:'contract_index',query:{}})
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
