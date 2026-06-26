<template>
  <div v-if="curdata" >
     <Steps :active="step" direction="vertical" active-icon="success" active-color="#07c160">
      <Step v-for="(item,index) in curdata.viewdata?.approval||[]" class="no-border-steps">
        <div v-if="item.title" class="cell" style="padding: 0;text-align: left;" @click="showLog(item,index)">
          <Image v-if="item.title" width="30" height="30" fit="cover" style="margin-right: 10px;" :round="true" :src="item?.avatar"/>
          <div :style="{flexGrow:1}" >
            <span v-if="item.title">{{item.title+(item.status&&index<=step?('->'+curdata.statusCn[item.status]):'')}}</span>
            
            <div><span :style="{color:'red'}" >{{item.speech}}</span></div>        
           </div>
          <div :style="{textAlign:'right',float:'right',marginRight:'10px',display:'flex',alignItems:'center'}">
            {{item.date}}
            <span v-if="approvalUserids&&approvalUserids!=approval&&index==step"><Button type="warning" @click.stop="refresh" size="mini">审批人有误</Button></span>
          </div>
        </div>
        <div class="cell" v-if="item.items && item.items.length>0" style="text-align: left;padding-left: 0;">
          <Badge  v-for="ele in item.items" :dot="ele.status==2">
            <Image width="30" height="30" fit="cover" style="margin-right: 2px;" :round="true" :src="ele?.avatar"/>
          </Badge>
          <div class="van-step">{{item.items.map((e:any)=>e.title).join('|')}}</div>
          <span v-if="approvalUserids&&approvalUserids!=approval&&index==step"><Button type="warning" @click="refresh" size="mini">审批人有误</Button></span>
        </div>
         <div v-if="item.items && item.items.length>0">
            <div v-for="e in item.items" :style="{color:'red'}">
              <span v-if="e.speech">{{e.title+'：'+e.speech}}</span>
            </div>
         </div>
 
      </Step>
      <Step v-if="curdata.viewdata?.notify&&curdata.viewdata?.notify.length" class="mystep">
        <div class="cell" style="padding: 0;text-align: left;">
       
          <div :style="{flexGrow:1}">
            <span >抄送：{{ curdata.viewdata?.notify?curdata.viewdata?.notify.join(','):'' }}</span>
           </div>
        </div>

      </Step>
    </Steps>

    <ActionSheet v-model:show="visible" title="审批意见" show-cancel-button style="min-height: 20vh;">
      <div v-if="curItem.NodeAttr==1||(curItem.Items&&curItem.Items.Item?.length==1)"  style="display: flex;flex-direction: row;"  >
        <Field
          v-model="speech"
          size="normal"
          center
          clearable
          style="padding:5px 5px 5px 15px;"
          placeholder="请输入审批意见"
          label=""
        >
        </Field>
        
        <span style="background-color: #1989fa;width: calc(var(--van-cell-font-size)*4);color: white;text-align: center;padding: 5px;margin: 5px;
          font-size: var(--van-cell-font-size);border-radius: 10%;" @click="updateSpeech(curItem)">更新</span>
      </div>
    </ActionSheet>
    
  </div>

  
  
</template>
<script  lang="ts">

import UserSelect from '@/views/invoice/components/UserSelect.vue';
import { Steps,Step,Badge,Image,Field, Button, showDialog,Icon,ActionSheet } from 'vant';

import { useUserStore } from '@/stores';
import { alterspeech } from '../finance';
const cacheStore = useUserStore()
  export default {
    components: {
      ActionSheet,Steps,Step,Badge,Image,UserSelect,Field,Button,Icon
    },
    props: ['data','edit','approvalUserids'],
    data () {
      return {
  
        step:0,
        showUser:false,
        user:<any>{},
        curdata:this.data,
        visible:false,
        curItem:<any>{},
        curStep:0,
        currentUser:<any>{},
        speech:'',
        approval:'',
        approvalnames:''
      }
    },
    watch:{
      data(val){
        this.curdata = val
        this.step = val.viewdata?.step+1
        if (val&&val.viewdata&&val.viewdata?.approval){
          var node = val.viewdata.approval[this.step]
          if (node.Items&&node.Items.Item){
            this.approval = node.Items.Item.map((e:any)=>e.ItemUserId).join('|')
            this.approvalnames = node.Items.Item.map((e:any)=>e.ItemName).join('|')
          }
        }
      }
    },
    mounted() {
     this.currentUser = cacheStore.userInfo
     
    },
    created() {

    },
    methods:{
        refresh(){
          this.$emit('updateApproval',{approvalUserid:this.approval,approvalUsername:this.approvalnames})
        },
        showLog(item:any,index:number){
          this.visible=true
          this.curItem = item||{}
          this.speech = this.curItem.speech
          this.curStep = index
        },
        updateSpeech(item:any){
         
          // 找出同意的项目
          var agreeItem = this.curItem.Items?.Item?.find((e:any)=>e.ItemStatus==2&&e.ItemUserId==this.currentUser.userId)
          if(!agreeItem){
            showDialog({'message':'只有审批人可以修改！'})
            return
          }
          console.log(agreeItem)
          alterspeech({thirdNo:this.curdata.viewdata.thirdNo,speech:this.speech,step:this.curStep}).then((res:any)=>{ 
            if (res.errorMessage){
              showDialog({'message':res.errorMessage})
            }else{
              this.curItem.speech = this.speech
              showDialog({'message':'修改成功'})
              this.visible =false
            }
          })
        },

    }
  }
</script>
<style  src="@/views/financeCss.css">
  
</style>
