
<template>
  <div class="box" style="background-color: white;">
    
      
      <Form @submit="onSubmit" required="auto">
        <Field
           
            label="附件上传"
            readonly
            required
    
          />
          <Uploader_Component @update:uploading="(val:any)=>uploading=val"   style="margin-left:var(--van-padding-lg)" :value.sync="data.annex"   @update:value="(val:any)=>data.annex=val"    />
          
        <Field
            v-model="data.payer"
            is-link
            readonly
            required
            :disabled="data.id"
            name="payer"
            :label="amountsType  == FinanaceType.SALARY? '申请部门':'付款单位'"
            placeholder="点击选择"
            @click="showPayer= data.id?false:true"
            :rules="[{ required: true, message: '不能为空' }]"
          />


          <Field
            v-model="data.receiver"
            name="receiver"
            required
            v-if="amountsType == FinanaceType.SALARY"
            label="工资月份"
            is-link
            readonly
            placeholder="点击选择工资月份"
            @click="showDate=true"
            :rules="[{ required: true, message: '不能为空' }]"
          />
          <Field
            v-model="data.receiver"
            name="receiver"
            v-if="amountsType != FinanaceType.SALARY"
            label="收款单位"
            required
            placeholder="收款单位"
      
            :rules="[{ required: true, message: '不能为空' }]"
            @click="showReceiver= true"
          />
          <Field
            v-if="amountsType  == FinanaceType.SALARY"
            v-model="data.reason"
            name="reason"
            label="工资项目"
            type="textarea"
            rows="1"
            
            :autosize="true"

            placeholder="工资项目"
            
            :rules="[{ required: false, message: '不能为空' }]"
            
          />
          <Field v-if="data.amountsType!=FinanaceType.CAR" v-model="data.amountsType" name="amountsType" label="是否跨部门">
            <template #input>
              <Switch v-model="crossChecked" :disabled="data.id"/>
            </template>
          </Field>
          <Field v-if="isInsideCompany"  label="转内部调拨">
            <template #input>
              <Switch v-model="isInsideChecked" />
            </template>
          </Field>
          
          <Field
            v-model="payStatusName"
            v-if="amountsType != FinanaceType.SALARY"
            label="付款方式"
            is-link
            required
            readonly
            placeholder="点击选择付款方式"
            @click="showPayStatus= true"
            :rules="[{ required: true, message: '不能为空' }]"
          />

          <Field
            v-if="amountsType != FinanaceType.SALARY"
            v-model="data.account"
            name="account"
            label="收款账号"
            required
            placeholder="请输入收款账号"
            
            :rules="[{ required: true, message: '不能为空' }]"
          />
          <Field
            v-if="amountsType != FinanaceType.SALARY"
            v-model="data.bank"
            name="bank"
            label="开户银行"
            placeholder="开户银行"
            required
            
            :rules="[{ required: true, message: '不能为空' }]"
          />
          <Field
            v-model="data.amount"
            name="amount"
            :label="amountsType  == FinanaceType.SALARY? '应发金额':'付款金额'"
            placeholder="付款金额"
            required
            :disabled="data.id"
            :rules="[{ required: true, message: '不能为空' },{pattern:/^-?\d+(\.\d{1,2})?$/,message:'格式有误'}]"
          />
       
          <Field
            v-if="amountsType  == FinanaceType.SALARY"
            v-model="data.amountreal"
            name="amountreal"
            label="实发金额"
            placeholder="实发金额"
            required
            
            :rules="[{ required: true, message: '不能为空' },{pattern:/^-?\d+(\.\d{1,2})?$/,message:'格式有误'}]"
          />
    
          <Field
            v-if="amountsType != FinanaceType.SALARY"
            v-model="data.reason"
            name="reason"
            type="textarea"
            rows="3"
            :autosize="true"
            label="付款事项"
            placeholder="请输入付款事由"
            required
            
            :rules="[{ required: true, message: '不能为空' }]"
          />
          <Field
            v-if="amountsType == FinanaceType.CAR"
            v-model="data.caruserid"
            name="caruserid"
            label="用车人"
            placeholder="用车人"
   
            readonly
            required
            :rules="[{ required: true, message: '不能为空' }]"
          >
          <template #input>
            <UserAvatarBox :userids="data.caruserid" @update:value="(val:any)=>data.caruserid=val" :update="true" />
            </template>
        
           </Field>
          
          <Field
            v-if="amountsType != FinanaceType.SALARY"
            
            name="certifier"
            label="证明人"
            readonly
            placeholder="证明人"
            
            :rules="[{ required: false, message: '不能为空' }]"
          >
          <template #input>
            <UserAvatarBox :userids="data.certifier" @update:value="(val:any)=>data.certifier=val" :update="true" />
            </template>
          </Field>
          <Field
            v-if="[FinanaceType.NORMAL,FinanaceType.NORMAL_CROSS].some(item=>item==amountsType)"
           
            name="fileurls"
            label="合同文件"
            placeholder="点击选择合同"
            is-link
            readonly
            @click="showContract=true"
            :rules="[{ required: false, message: '不能为空' }]"
          >
 
        </Field>
        <Filescard  v-if="data.fileurls" :urls="data.fileurls" :showDelete="true" @update:urls="(val:any)=>data.fileurls=val"/>
          

        <Field
          v-if="[FinanaceType.NORMAL,FinanaceType.NORMAL_CROSS].some(item=>item==amountsType)"
          label="发票上传"
          readonly
  
        />
        <Uploader_Component    style="margin-left:var(--van-padding-lg)"   :value.sync="data.invoice"   @update:value="(val:any)=>data.invoice=val" />
 

      <div style="padding: 16px;">
        
        <Button v-if="!data.id" :loading="loading" round block type="primary" native-type="submit" style="font-size: calc(var(--van-cell-font-size)*var(--Big));">
          提交
        </Button>
        <Button v-if="!data.id" round block type="default" @click="showPreview=true" style="margin-top: 10px;font-size: calc(var(--van-cell-font-size)*var(--Big));" >
          预览流程
        </Button>
        <Button v-if="data.id" round block type="primary" @click="update" style="font-size: calc(var(--van-cell-font-size)*var(--Big));">
          修改
        </Button>
      </div>
    </Form>

    <Payer_Select :show.sync="showPayer"  @update:show="(val:any)=>showPayer=val" @update:value="(val:any)=>data.payerid=val" @onCrossChange="onCrossChange" @update:label="(val:any)=>data.payer=val" @update:cross="(val:any)=>{


    }" 
    />
    <Receiver_Select :show.sync="showReceiver"  @update:show="(val:any)=>showReceiver=val"  @update:label="(val:any)=>data.receiver=val" @change="onReiverChange" @update:inside="(val:any)=>{
      isInsideChecked=val
      isInsideCompany=val
    }" />
    <Dict_Select type="付款方式" :initialValue="data.payStatus||1" :show.sync="showPayStatus"  @update:show="(val:any)=>showPayStatus=val"  @update:value="(val:any)=>data.payStatus=val"  @update:label="(val:any)=>payStatusName=val"/>
    <DatePicker_Dialog  :show.sync="showDate"  @update:show="(val:any)=>showDate=val"  @update:label="(val:any)=>data.receiver=val"/>
    
    <Contract_Select :show.sync="showContract"  @update:show="(val:any)=>showContract=val" @update:fileurls="(val:any)=>data.fileurls=val" />
   
    
    

    <Preview_Flow :show.sync="showPreview"  @update:show="(val:any)=>showPreview=val" :obj="data" />
    
  </div>
</template>
<script  lang="ts">
import {h} from 'vue'
import {  Form,CellGroup,Field,Button,Card,Uploader,Radio,RadioGroup,showDialog,showConfirmDialog,Switch, showToast } from 'vant';
import Payer_Select from './components/Payer_Select.vue';
import { FinanaceType } from './finance_config';
import Receiver_Select from './components/Receiver_Select.vue';
import Dict_Select from '../invoice/components/Dict_Select.vue';
import DatePicker_Dialog from './components/DatePicker_Dialog.vue';
import UserSelect from '../invoice/components/UserSelect.vue';
import UserAvatarBox from './components/UserAvatarBox.vue';
import Filescard from '../budget/components/filescard.vue';
import Contract_Select from '@/views/finance/components/Contract_Select.vue';
import Upload_Dialog from '../invoice/components/Upload_Dialog.vue';
import Invoice_Select from './components/Invoice_Select.vue';
import Flow_Steps from './components/Flow_Steps.vue';
import { getdata, getdriverleader, getflow, save } from './finance';
import { extractParameterFromUrl } from '../budget/utils';
import { useUserStore } from '@/stores';
import Preview_Flow from './components/Preview_Flow.vue';
import Uploader_Component from '../invoice/components/Uploader_Component.vue';


const cacheStore = useUserStore()

  export default {
    components: {
      Uploader_Component,Preview_Flow,Flow_Steps,Invoice_Select,Upload_Dialog,Filescard,Contract_Select,UserAvatarBox,UserSelect,DatePicker_Dialog,Payer_Select,Dict_Select,Receiver_Select,Form,CellGroup,Field,Button,Card,Uploader,Radio,RadioGroup,Switch,
    },
    
    data () {
      return {
        extractParameterFromUrl:extractParameterFromUrl,
        data:<any>{},
        FinanaceType:FinanaceType,
        showPayer:false,
        showReceiver:false,
        invoiceNames:'',
        showPayStatus:false,
        showDate:false,
        uploadImage:<any>[],
        files:<any>[],
        isCrossDept:false,
        crossChecked:false,
        isInsideCompany:false,
        isInsideChecked:false,
        payStatusName:'',
        showContract:false,
        amountsType:<Number>0,
        showAnnex:false,
        showInvoice:false,
        annexNames:'',
        action:<any>'',
        currentUser:<any>{},
        showPreview:false,
        loading:false,
        uploading:false,
        
        
          
      }
    },
    watch:{
      crossChecked(val:any){
        
        switch (this.amountsType) {
            case 0:
            case 3:
              this.data.amountsType=val?FinanaceType.NORMAL_CROSS:FinanaceType.NORMAL
              break;
            case 1:
            case 4:
              this.data.amountsType=val?FinanaceType.SALARY_CROSS:FinanaceType.SALARY
              break;
            default:
              break;
          }
          console.log('wathc crossChecked:'+val+',this.amountsType:',this.data.amountsType)

      }
    },
    mounted() {
      this.currentUser = cacheStore.userInfo
      var thirdNo = this.$route.query?.thirdNo;
      var temp:any = this.$route.query.amountsType
      this.data.amountsType = temp||0
      this.action = this.$route.query.action||''
      if (this.$route.query?.amountsType){
        this.amountsType = parseInt(temp);
        if (this.amountsType==FinanaceType.CAR){
          getdriverleader({}).then((res:any)=>{
            if (res && res.userid){
              this.data.certifier = res.userid
            }

          })
        }
      }
      if (thirdNo){
        getdata({thirdNo:thirdNo}).then((res:any)=>{
          if (res){
            this.data = res
            this.amountsType = this.data.amountsType;
            if (this.action=='reapply'){
              delete this.data.id
              delete this.data.thirdNo
              delete this.data.inserttime
            }
            if ([FinanaceType.SALARY_CROSS,FinanaceType.NORMAL_CROSS].some(item=>item==this.amountsType)){
              this.isCrossDept=true
              this.crossChecked=true
            }
            if(FinanaceType.TRANSFER==this.amountsType){
              this.isInsideCompany = true
            }
          }
        })
      }

    },
    created() {
    },
    methods:{
      isIOS() {
        const ua = navigator.userAgent.toLowerCase();
        const isIphone = /iphone/.test(ua);
        const isIpad = /ipad/.test(ua);
        const isIpod = /ipod/.test(ua);
        const isMacLike = /macintosh/.test(ua) && navigator.maxTouchPoints > 1;


        return isIphone || isIpad || isIpod || isMacLike;
      },
      onCrossChange(val:any){
          this.isCrossDept=val
          this.crossChecked=false
      },
      update(){
        console.log(this.data)
        this.check()
        save({obj:this.data}).then((res:any)=>{
          if (res.errorMessage) {
            showDialog({'message':res.errorMessage})
          } else {
            this.$router.push({name:'finance_view',query:{thirdNo:this.data.thirdNo}})
          }
        })
      },
      check(){

        if (this.isInsideChecked){
          this.data.amountsType = FinanaceType.TRANSFER
        }
      
      },
      preview(){
        this.check()

        
        
        // const {payerid,payer,amount,amountsType,userId} = this.data
        getflow({obj:this.data}).then((res:any)=>{
          
          showConfirmDialog({
              title: '请确认流程是否正确',
             
              message:() =>{
                return h(Flow_Steps,{edit:true,par:this.data,data:res||{viewdata:{}}})
        
              },
                
            })
              .then(() => {

               
              })
              .catch(() => {
                // on cancel
              });
        })
      },
      onSubmit(values:any){
      
        console.log(this.uploading)

        this.data.userId= this.currentUser.userId
        if (this.uploading){
          showDialog({'message':'附件正在上传中，请稍候！'})
          return
        }
        this.check()

        
        
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
                      this.$router.push({name:'finance_view',query:{thirdNo:res.thirdNo}})
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
<style  src="@/views/financeCss.css"></style>


