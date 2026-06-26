
<template>
  <div v-if="!data.viewdata" style="margin: 10px;">暂无</div>
  <div v-if="data.viewdata" class="box1">
    <div v-if="data.basic.reject==1" class="mask">
      <div class="value" :style="{color:'red',fontWeight:'bold'}">
            <span>{{ '已驳回' }}</span>
      </div>
    </div>
     <div class="row" :style="{margin:'10px 0px'}">
        <Image width="50" radius="10" height="50" fit="cover" :round="false" :src="data.basic?.avatar"/>
        <span class="title" :style="{marginLeft:'5px',marginRight:'5px',fontWeight:'bold',color:'black!important'}">{{data.basic?.userName}}的审批申请</span>
        <Tag size="large" plain :type="tagcolor" class="head">{{data.basic?.statusname}}</Tag> 
     </div>
     <CellGroup>
      <div class="cell" v-if="data.basic?.thirdNo">
         <div class="label">审批单号：</div>
         <div class="value">{{data.basic?.thirdNo }}</div>
       </div>
      <div class="cell">
         <div class="label">申请类型：</div>
         <div class="value" >{{data.basic?.typename|| data.basic?.statename}}{{ data.basic.statename!=data.basic?.typename?(data.basic.statename?"（未"+data.basic.statename+"）":""):"" }}</div>
       </div>
       <div class="cell">
         <div class="label">项目名称：</div>
         <div class="value">{{data.basic?.title }}</div>
       </div>
       <div class="cell">
         <div class="label">项目类型：</div>
         <div class="value">{{data.basic?.protypename }}</div>
       </div>
       
       <div class="cell">
         <div class="label">申请部门：</div>
         <div class="value">{{data.basic?.department }}</div>
       </div>
       <div class="cell">
         <div class="label">项目负责人</div>
         <div class="value">{{data.basic?.chargername }}</div>
       </div>
       <div class="cell" v-if="data.basic?.submitdate&&data.basic?.typename=='提交计量'">
         <div class="label">提交月份</div>
         <div class="value">{{data.basic?.submitdate.substr(0,7) }}</div>
       </div>
       <div class="cell">
         <div class="label">总收入</div>
         <div class="value">{{income.toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,}) }}</div>
       </div>
       <div class="cell">
         <div class="label">总支出</div>
         <div class="value">{{expend.toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,})}}</div>
       </div>
       <div class="cell">
         <div class="label">毛利润</div>
         <div class="value">{{profit.toLocaleString('en-US', {minimumFractionDigits: 2,maximumFractionDigits: 2,}) }}</div>
       </div>
       <div class="cell">
         <div class="label">合同状态：</div>
         <div class="value" :style="{color:data.basic?.contractids?'#1989fa':'black'}" @click="getContract(data.basic?.contractids)">{{data.basic?.contractids?'已签':'未签' }}</div>
       </div>
       <Filescard v-if="contracturls" :urls="contracturls"/>
       <div v-if="data.basic?.state<=ProjectStatesEnum.BUDGET&&data.basic?.content">
          <div class="cell" >
            <div class="label">预算备注：</div>
          </div>
          <div class="cell" >
            <div class="value">{{data.basic?.content }}</div>
          </div>
       </div>
       <div v-if="data.basic?.state>ProjectStatesEnum.BUDGET&&data.basic?.finalcontent">
          <div class="cell" >
            <div class="label">决算备注：</div>
          </div>
          <div class="cell" >
            <div class="value">{{data.basic?.finalcontent }}</div>
          </div>
       </div>

    </CellGroup>
    <div class="row title"  style="margin-top: 20px;">审批流程:</div>
    <Steps :active="step" direction="vertical" active-icon="success" active-color="#07c160">
      <Step v-for="(item,index) in data.viewdata?.approval||[]">
        <div class="row" style="padding: 0;">
          <Image width="30" height="30" fit="cover" style="margin-right: 10px;" :round="true" :src="item?.avatar"/>
          <div :style="{flexGrow:1}">
            <span v-if="item.next!='offline'&&item.offline!=1">{{item.title+(item.status&&index<=step?('>'+data.statusCn[item.status]):'')}}</span>
            <span v-if="item.next=='offline'">线下上会处理</span>
            <div><span v-if="(data.basic?.offline==1||item.offline==1)&&step==index"  @click="upload"  style="color: #1989fa">线下上会材料上传</span></div>
            <div><span :style="{color:'red'}">{{item.speech}}</span></div>
            
           </div>
          <div :style="{textAlign:'right',float:'right',marginRight:'10px',display:'flex',alignItems:'center'}">
            {{item.date}}
          </div>
        </div>
        <div class="row" v-if="item.next!='offline'&&item.offline!=1&& (data.basic?.offline!=1||data.basic?.offline==1&&step!=index) && item.items && item.items.length>0">
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
      <ActionBarButton  type="warning" :text="'上会'+typelabel" v-if="data&&data.viewdata&&data.viewdata.showSpecialBtn&&data.viewdata.offlineBtnShowBefore" @click="offline()"/>
      <ActionBarButton  type="primary" :text="(data&&data.viewdata&&data.viewdata.showSpecialBtn)?'会签'+typelabel:'同意'+typelabel " v-if="showbar&&!data.viewdata.notShowCountersign" @click="actAgree({act:'agree'})"/>
      <ActionBarButton  type="warning" :text="'上会'+typelabel" v-if="data&&data.viewdata&&data.viewdata.showSpecialBtn&&!data.viewdata.offlineBtnShowBefore" @click="offline()"/>
      <ActionBarButton type="default" text="驳回" v-if="showbar" @click="act({act:'reject'})"/>
        <!-- 经办操作 -->
      <ActionBarButton type="warning" text="上会通过" v-if="showbar2&&['预算','决算'].indexOf(data.basic?.typename)>-1&&data.basic?.offline==1" @click="upload"/>
      <ActionBarButton icon="chat-o" text="催办"  v-if="showbar2&&FlowStateEunm.PASS!=data.basic?.status" type="primary" @click="urge({act:'urge'})"/>
      <ActionBarButton icon="cross" text="撤销" color="#ff5000" v-if="showbar2&&FlowStateEunm.PASS!=data.basic?.status" type="default" @click="act({act:'cancel'})"/>
    </ActionBar>
    <Dialog v-model:show="show" title="上会材料上传" @confirm="onUploadConfirm" :show-cancel-button="true" :show-confirm-button="true">
            <Cell title="会议：">
            </Cell>
            <Field v-model="speech" rows="2" autosize label="" type="textarea" maxlength="250" placeholder="输入会议名称和日期"
                show-word-limit @focus="softKeyboard" />
            <Cell title="附件上传">
            </Cell>
            <Field name="uploader" label="">
                <template #input>
                    <Uploader  v-model="uploadImage"  multiple :max-count="10" accept="image/*,.pdf" :before-read="beforeRead"
                        :after-read="afterRead" :before-delete="beforeDelete" />
                </template>
            </Field>
           
    </Dialog>

  </div>
</template>
<script  lang="ts">

import { Divider, Image,Tag,Cell,CellGroup,Steps,Step,Badge,Field,Button,showDialog,showConfirmDialog,Dialog,Uploader,ActionBar,ActionBarIcon,ActionBarButton} from 'vant';
import { flowact, getflowdata,getfileurlsbycontractids } from '../budget';
import Filescard from './filescard.vue'
import { ProjectStatesEnum,FlowStateEunm } from '../config';
import { useUserStore } from '@/stores';
import { storeToRefs } from 'pinia';
import { checkUploadType, uploadedFiles, previewImage,softKeyboard } from '../../../utils/common'
import { uploadDelete } from '../../../api/config'
import { setToUrl } from '../utils';
import { ref } from 'vue';
const { loginStatus, userInfo } = storeToRefs(useUserStore());

export default {
  components: {
    Image,Tag,Divider,Cell,CellGroup,Steps,Step,Badge,Filescard,Field,Button,Dialog,Uploader,ActionBar,ActionBarIcon,ActionBarButton 
  },
  props: ['thirdNo', 'projectid','state'],
  data () {
    return {
      active:0,
      data:<any>{},
      step:0,
      speech:'',
      userid:'',
      ProjectStatesEnum:ProjectStatesEnum,
      FlowStateEunm:FlowStateEunm,
      showbar:false,
      showbar2:false,
      show:false,
      files:<any>[],
      uploadImage:<any>[],
      softKeyboard:softKeyboard,
      showContract:false,
      contracturls:'',
      statecolor:['default','primary','success','default','default'],
      tagcolor:<any>'default',
      income:0,
      expend:0,
      profit:0,
      typelabel:'',
      reset:false,
    }
  },
  mounted() {
   
   this.userid = userInfo.value.userId

  },
  created() {
    this.getdata()
  },
  methods:{
    upload(){
      this.show = true
    },
    onUploadChange(e:any){
      console.log(e)
    },
    onUploadConfirm(){
      var fileurls = this.files.map((u:any)=>{
        return setToUrl(u)
      }).join(',')
      if (!this.speech) {
        showDialog({message:'请输入会议名称和日期'})
        return
      }
      var flow:any = {fileurls}
      flow.act='offlineAgree'
      flow.offline=0
      this.act(flow)
    },
    beforeRead(file: any){
      return true
    },
    afterRead(file: any) {
        console.log('afterread')
        uploadedFiles(file, { 'uploadType': "3", 'uploadPath': 'contract' },(e:any)=>{
          this.files.push(...e)
        })
        
    },
    beforeDelete  (file: any) {
        uploadDelete(file._url).then((res:any)=>{
          if(res.success){
            this.files.splice(this.files.findIndex((item:any)=>item.url==file._url),1)
          }
        })
        return true
    },
    getdata(){
      var par:any = {projectid:this.projectid}
      if (this.state) par.state = this.state
      if (this.thirdNo) par.thirdNo = this.thirdNo
      getflowdata(par).then((res:any)=>{
      
      this.data = res
      if(['预算','决算'].indexOf(res.basic?.typename)>-1){

        this.typelabel = '（'+this.data.basic?.typename+'）'
      }
      this.showbar = (this.data.basic?.approvalUserid||'').indexOf(this.userid)>-1 && this.data.basic?.status==FlowStateEunm.ING
      this.showbar2=(this.data.basic?.userId||'').indexOf(this.userid)>-1 && this.data.basic?.status!=FlowStateEunm.CANCEL
      if(res && res.basic){
        this.tagcolor = this.statecolor[res.basic?.status] || 'default'
      }
      
      // 计算总收入、总支出和总毛利
      var income = 0
      var expend = 0
      var profit = 0
      if (res.basic.finalincome>0){
        income = res.basic.finalincome
        expend = res.basic.realfinalexpend
      }else{
        income = res.basic.budgetincome
        expend = res.basic.realbudgetexpend
      }
      profit = income-expend
      profit = profit>0?profit:0
      this.income = income
      this.expend = expend
      this.profit = profit
      
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
        if(res.basic?.offline) this.showbar=false
      })
    },
    urge(flow:any){
      if (!flow.thirdNo) flow.thirdNo = this.data.basic?.thirdNo||this.data.basic?.thirdno
      flowact(flow).then((res:any)=>{
        if (res.errorMessage){
        }else{
          showDialog({'message':'催办成功'})
        }
      })
    },
    actAgree(flow:any){
      showConfirmDialog({
        title: '确定同意【'+this.data.basic?.typename+'】吗？'
      })
        .then(() => {
          this.act(flow)
        })
        .catch(() => {
          // on cancel
        });
    },
    act(flow:any){
      if (!flow.thirdNo) flow.thirdNo = this.data.basic?.thirdNo||this.data.basic?.thirdno
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
            
            flow.continuous = 1
            this.act(flow)
          }else{
            
            this.getdata()
          }
        }
      })
        
    },
    continue(){
      Dialog.confirm({
        title: '确定要提交审批吗？',
        onOk() {
          this.act({act:'continue'})
        },
      });
    },
    offline(){
      var viewdata = this.data.viewdata
      if (viewdata && viewdata.approval){
            if (viewdata.approval.length<=(this.step+1)){ // 无下个节点
                showDialog({'message':'禁止操作'})
                return
            }else{
              
              var node = viewdata.approval[this.step+1]
              if (node.NodeAttr!=2||node.Items.Item.length<2){
                showDialog({message:'下个节点为会签且多人审批才能上会'})
                return
              }
            }
            
            
          }
          showConfirmDialog({
            title: '确定要【线下上会】吗？'
          })
            .then(() => {
              this.act({act:'agree',offline:1})
            })
            .catch(() => {
              // on cancel
            });

    },
    getContract(ids:any){
      if (ids){
        getfileurlsbycontractids({contractids:ids}).then(res=>{
          if (res){
            this.contracturls = res.data
            this.showContract = true
          }
        })
      }
    }
  }
}
</script>
<style   lang="css">
  .mask{
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to bottom, rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.5));
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 9999;
    color: white;
    font-size: 20px;
    font-weight: bold;
    
  }
  .title{
    font-weight: bold;
    font-size: calc(var(--van-cell-font-size)*var(--Big));
  }
  span{
    font-size: 14px;
  }
  .box1{
    width: 100%;
    min-height: 100vh;
  }
  .row{
    /* width: 100%; */
    display: flex;
    flex-direction: row;
    align-items: center;
    padding: 2px 10px;
  }
  .row .label{
    flex: none;
    box-sizing: border-box;
    width: calc(var(--van-field-label-width)*0.9);
    color: #969799;
    text-align: left;
    word-wrap: break-word;
  }
  .row .value{
    position: relative;
    overflow: hidden;
    color: #323233;
    text-align: left;
    vertical-align: middle;
    word-wrap: break-word;
  }
  .cell{
    position: relative;
    display: flex;
    box-sizing: border-box;
    width: 100%;
    padding: 8px var(--van-cell-horizontal-padding) 0 var(--van-cell-horizontal-padding);
    overflow: hidden;
    color: var(--van-cell-text-color);
    font-size: calc(var(--van-cell-font-size)*var(--Big));
    line-height: var(--van-cell-line-height);
    background: var(--van-cell-background);
    flex-wrap: wrap;
    
   
  }
  .cell::after {
    content: '';
    display: block;
    width: 100%;
    margin: 0 auto;
    margin-top: 8px;
    border-bottom: 1px solid rgb(249, 247, 247);
  }
  .cell .label{
    flex: none;
    box-sizing: border-box;
    width: calc(var(--van-field-label-width)*0.9);
    color: #969799;
    text-align: left;
    word-wrap: break-word;
  }
  .cell .value{
    position: relative;
    overflow: hidden;
    color: #323233;
    text-align: left;
    vertical-align: middle;
    word-wrap: break-word;
  }
  .van-cell .van-cell__title span{
    font-size: calc(var(--van-cell-font-size)*0.8*var(--Big))!important;
  }

 
</style>
<style>
  @media screen and (min-width: 500px) {
    .title{
      font-weight: bold;
      font-size: 18px;
    }
    span{
      font-size: 18px;
    }
    .box1{
      width: 100%;
      min-height: 100vh;
    }
    .row{
      /* width: 100%; */
      display: flex;
      flex-direction: row;
      align-items: center;
      padding: 2px 10px;
    }
    .row .label{
      color: rgb(160, 157, 157);
      width: 100px;
      font-size: 16px;
    }
    .row .value{
      font-size: 20px;
    }
    .van-action-bar {
        position: relative;
        /* right: 0;
        bottom: 0;
        left: 0;  */
        display: flex;
        flex-direction: row;
        align-items: center;
        box-sizing: content-box;
        height: var(--van-action-bar-height);
        background: var(--van-action-bar-background);
        max-width: 500px;
        justify-content: center;
    }
      
  }
</style>