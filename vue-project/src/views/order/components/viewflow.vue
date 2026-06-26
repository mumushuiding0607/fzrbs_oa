<template>
   
  <div v-if="data.viewdata" class="box1">
    <div v-if="data.basic.reject==1" class="mask">
      <div class="value" :style="{color:'red',fontWeight:'bold'}">
            <span>{{ '已驳回' }}</span>
      </div>
    </div>
     <div class="row" :style="{margin:'10px 0px'}">
        <Image width="50" radius="10" height="50" fit="cover" :round="false" :src="data.basic?.avatar"/>
        <span class="title" :style="{marginLeft:'5px',marginRight:'5px',fontWeight:'bold',color:'black!important'}">{{data.basic?.userName}}的审批申请</span>
        <Tag size="large" plain :type="tagcolor" class="head">{{data.statusCn[data.basic?.status]}}</Tag> 
     </div>
     <CellGroup>
       <div class="cell" v-if="data.basic?.thirdNo">
          <div class="label">审批单号：</div>
          <div class="value">{{data.basic?.thirdNo }}</div>
        </div>
        <div class="cell">
          <div class="label">申请类型：</div>
          <div class="value" >广告审批</div>
        </div>
        <div class="cell">
          <div class="label">广告金额：</div>
          <div class="value">{{(data.info?.AI_AmountReceivable||0).toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,}) }}</div>
        </div>
        <div class="cell">
          <div class="label">客户：</div>
          <div class="value">{{data.info?.AI_Customer }}</div>
        </div>
        <div class="cell">
          <div class="label">业务员：</div>
          <div class="value">{{data.info?.AI_Salesman }}</div>
        </div>
        <div class="cell">
          <div class="label">发布日期：</div>
          <div class="value">{{data.info?.AI_PublishTime?.substring(0,10) }} ~ {{data.info?.AI_PublishEndTime?.substring(0,10) }}</div>
        </div>
        <div class="cell">
          <div class="label">发布平台：</div>
          <div class="value">{{data.info?.AI_Publication }}</div>
        </div>
        <div class="cell">
          <div class="label">大行业：</div>
          <div class="value">{{data.info?.AI_Trade }}</div>
        </div>
        <div class="cell">
          <div class="label">行业部门：</div>
          <div class="value">{{data.info?.AI_Org }}</div>
        </div>
        <div class="cell">
          <div class="label">规格：</div>
          <div class="value">{{data.info?.AI_Size }}</div>
        </div>
        <div class="cell">
          <div class="label">版位：</div>
          <div class="value">{{data.info?.AI_Field }}</div>
        </div>
        <div class="cell">
          <div class="label">颜色：</div>
          <div class="value">{{data.info?.AI_Color }}</div>
        </div>
        <div class="cell">
          <div class="label">投放天数：</div>
          <div class="value">{{data.info?.AI_PublishDayCount }}</div>
        </div>
        <div class="cell">
          <div class="label">广告内容：</div>
          <div class="value">{{data.info?.AI_Content }}</div>
        </div>
        <div class="cell">
          <div class="label">备注：</div>
          <div class="value">{{data.info?.AI_Memo }}</div>
        </div>

      <Filescard v-if="data.info?.fileurls" :urls="data.info?.fileurls"/>
    </CellGroup>
    
    <div class="row title"  style="margin-top: 20px;">审批流程:</div>
    <Steps :active="step" direction="vertical" active-icon="success" active-color="#07c160">
      <Step v-for="(item,index) in data.viewdata?.approval||[]">
        <div class="row" style="padding: 0;">
          <Image width="30" height="30" fit="cover" style="margin-right: 10px;" :round="true" :src="item?.avatar"/>
          <div :style="{flexGrow:1}">
            <span>{{item.title+(item.status&&parseInt(index)<=step?('>'+data.statusCn[item.status]):'')}}</span>
            <div><span :style="{color:'red'}">{{item.speech}}</span></div>
           </div>
          <div :style="{textAlign:'right',float:'right',marginRight:'10px',display:'flex',alignItems:'center'}">
            {{item.date}}
          </div>
        </div>
        <div class="row" v-if="item.items && item.items.length>0">
          <Badge  v-for="ele in item.items" :dot="ele.status==2">
            <Image width="25" height="25" fit="cover" style="margin-right: 2px;" :round="true" :src="ele?.avatar"/>
          </Badge>
        </div>
        <Filescard v-if="item.fileurls" :urls="item.fileurls"/>
      </Step>
    </Steps>
    <Divider/>
    <Field
      v-if="showbar"
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
      <ActionBarButton  type="primary" text="同意" v-if="showbar" @click="actAgree({act:'agree'})"/>
      <ActionBarButton type="default" text="驳回" v-if="showbar" @click="act({act:'reject'})"/>
      <!-- 经办操作 -->
      <ActionBarButton icon="chat-o" text="催办"  v-if="showbar2" type="primary" @click="urge({act:'urge'})"/>
      <ActionBarButton icon="cross" text="撤销" color="#ff5000" v-if="showbar2" type="default" @click="act({act:'cancel'})"/>
    </ActionBar>
  </div>
</template>
<script  lang="ts">

import { Divider, Image,Tag,Cell,CellGroup,Steps,Step,Badge,Field,showDialog,showConfirmDialog,ActionBar,ActionBarButton } from 'vant';
import { flowact, getflowdata } from '../order';
import Filescard from '@/views/budget/components/filescard.vue'

import { FlowStateEunm } from '../config';
import { useUserStore } from '@/stores';
import { storeToRefs } from 'pinia';

const { userInfo } = storeToRefs(useUserStore());

export default {
  components: {
    Image,Tag,Divider,Cell,CellGroup,Steps,Step,Badge,Filescard,Field,ActionBar,ActionBarButton
  },
  props: ['thirdNo', 'infoid', 'orderid'],
  data () {
    return {
      data:<any>{},
      step:0,
      speech:'',
      userid:'',
      FlowStateEunm:FlowStateEunm,
      showbar:false,
      showbar2:false,
      statecolor:['default','primary','success','default','default'],
      tagcolor:<any>'default',
    }
  },
  mounted() {
   this.userid = userInfo.value.userId
  },
  created() {
    this.getdata()
  },
  methods:{
    getdata(){
      var par:any = {}
      if (this.infoid) par.infoid = this.infoid
      if (this.orderid && !this.infoid) par.infoid = this.orderid
      if (this.thirdNo) par.thirdNo = this.thirdNo
      
      getflowdata(par).then((res:any)=>{
        this.data = res
        this.showbar = (this.data.basic?.approvalUserid||'').indexOf(this.userid)>-1 && this.data.basic?.status==FlowStateEunm.ING
        this.showbar2=(this.data.basic?.userId||'').indexOf(this.userid)>-1 && this.data.basic?.status==FlowStateEunm.ING
        
        if(res && res.basic){
          this.tagcolor = this.statecolor[res.basic?.status] || 'default'
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
    urge(flow:any){
      if (!flow.thirdNo) flow.thirdNo = this.data.basic?.thirdNo
      flowact(flow).then((res:any)=>{
        if (res.errorMessage){
          showDialog({message:res.errorMessage})
        }else{
          showDialog({'message':'催办成功'})
        }
      })
    },
    actAgree(flow:any){
      showConfirmDialog({
        title: '确定同意审批吗？'
      })
        .then(() => {
          this.act(flow)
        })
        .catch(() => {});
    },
    act(flow:any){
      if (!flow.thirdNo) flow.thirdNo = this.data.basic?.thirdNo
      if (!flow.speech) flow.speech= this.speech

      flowact(flow).then((flowres:any)=>{
        if (flowres.errorMessage){
          showDialog({message:flowres.errorMessage})
          this.getdata()
        }else{
          if (flowres.data && flowres.data.touser&&flowres.data.touser.indexOf(this.userid)>-1){
            flow.continuous = 1
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
<style   lang="css" src="@/views/financeCss.css"></style>