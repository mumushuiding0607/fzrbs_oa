
<template>

  <div  class="box1" >
    <div v-if="data.info?.reject==1" class="mask">
      <div class="value" style="z-index: 10000;">
          <p  :style="{color:'red',fontWeight:'bold',fontSize:'25px'}">{{ '已驳回' }}</p>
          <div v-if="userid==data.info?.creator" :style="{color:'black',fontWeight:'bold',fontSize:'25px'}" @click="continueA()" >继续审批</div>
      </div>
    </div>
     <div v-if="data.basic.userName" class="row" :style="{padding:'8px'}">
        <Image width="50" radius="10" height="50" fit="cover" :round="false" :src="data.basic?.avatar"/>
        <span class="title" :style="{marginLeft:'5px',marginRight:'5px',fontWeight:'bold',color:'black'}">{{data.basic?.userName}}的审批申请</span>
        <Tag size="large" plain :type="tagcolor" class="head">{{data.statusCn[data.basic.status]}}</Tag> 
     </div>
     <CellGroup>
      <div v-if="data.basic?.department" class="cell">
         <div class="label">申请部门：</div>
         <div class="value">{{data.basic?.department }}</div>
       </div>
      <div v-if="data.info?.approvaltypename" class="cell">
         <div class="label">申请类型：</div>
         <div class="value">{{data.info?.approvaltypename }}</div>
       </div>
       <div class="cell">
         <div class="label">合同业务：</div>
         <div class="value">{{data.info?.contracttypename }}</div>
       </div>
       <div class="cell">
         <div class="label">业务类型：</div>
         <div class="value">{{data.info?.businesstype }}</div>
       </div>
       <div class="cell">
         <div class="label">媒体：</div>
         <div class="value">{{data.info?.publication }}</div>
       </div>
       <div class="cell">
         <div class="label">发票类型：</div>
         <div class="value">{{data.info?.type?'专票':'普票' }}</div>
       </div>
       <div class="cell" @click="viewCompany(data.info?.partb)">
         <div class="label">开票单位：</div>
         <div class="value" style="color: #1989fa;" >{{ data.info?.partbname }}</div>
       </div>
       <div class="cell" @click="viewCustomer(data.info?.customer)">
         <div class="label">客户名称：</div>
         <div class="value" style="color: #1989fa;">{{ data.info?.partaname }}</div>
       </div>
       <div class="cell" @click="viewCompany(data.info?.receiverid)">
         <div class="label">发票抬头：</div>
         <div class="value" style="color: #1989fa;">{{ data.info?.receiver }}</div>
       </div>
       <div class="cell">
        <div class="label">开票项目：</div>
       </div>
       <div style="padding: 10px;">
        <Invoicingitems :invoicingid="data.info?.id"></Invoicingitems>
       </div>
       
       
       <div class="cell">
         <div class="label">发票备注：</div>
       </div>
       <div class="cell" v-if="data.info?.content">
         <div class="value">{{data.info?.content }}</div>
       </div>
       <div v-if="data.info?.othercontent" class="cell">
         <div class="label">其他说明：</div>
       </div>
       <div v-if="data.info?.othercontent" class="cell">
         <div class="value">{{data.info?.othercontent }}</div>
       </div>
       <div class="cell" @click="viewContract(data.info?.contractid)">
         <div class="label">合同信息：</div>
         <div class="value" style="color: #1989fa;flex-grow: 1;text-align: left;" >{{data.info?.contractnames||'未签' }}</div>
         <div style="color: #1989fa; text-align: right;" @click.stop="unionContract()">关联</div>
       </div>
       <div class="cell" v-if="data.info?.projects">
         <div class="label">项目信息：</div>
      
            <div class="value" style="margin-right: 10px;color: #1989fa;" @click="viewProject(p.id)" v-for="(p,index) in data.info?.projects" :key="'p'+index">
              {{ p.title }}
            </div>

      
       </div>
     
       <Invoice_list :key="invoicekey" :invoicingid="data.info?.id" @suc="onUpInvoice"/>
       <div v-if="data.info?.pdffileurls" class="cell">
         <div class="label">pdf发票：</div>
       </div>
       <Filescard v-if="pdffileurls" :key="pdffileurls" :urls="pdffileurls"/>

       <div v-if="data.info?.fileurls" class="cell">
         <div class="label">附件：</div>
       </div>
       <Filescard v-if="data.info?.fileurls" :urls="data.info?.fileurls"/>

 

    </CellGroup>
    <div v-if="data.viewdata" class="cell title"  style="margin-top: 20px;">审批流程:</div>

    <Flow_Steps :data="data"/>
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
     
      <!-- 会计 -->
       <ActionBarButton v-if="(data.info?.state==InvoicingStatesEnum.INVOICED&&!thirdNo)||data.info?.state==InvoicingStatesEnum.WAITFORDELETE" type="primary" :loading="doing" :text="'XML发票'"  @click="uploadinvoice(data.info?.id)"/>
       <ActionBarButton v-if="data.info?.state==InvoicingStatesEnum.INVOICED&&!thirdNo" type="default" :loading="doing" :text="'PDF发票'"  @click="showPdf=true"/>
       <ActionBarButton v-if="[InvoicingStatesEnum.INVOICED,InvoicingStatesEnum.DELETEED].includes(data.info?.state)" type="warning" :loading="doing" :text="'打印'"  @click="showPrint=true"/>
      <!-- 经办 -->
      <ActionBarButton v-if="!data.viewdata&&data.info?.state==0&&!thirdNo" type="primary" :loading="doing" :text="'提交审批'"  @click="startflow(1)"/>
  
      <!-- 审批人操作 -->
      <ActionBarButton type="primary" :text="'同意'" v-if="showbar" @click="act({act:'agree'})"/>
      <ActionBarButton type="default" text="驳回" v-if="showbar" @click="act({act:'reject'})"/>
      <ActionBarButton type="warning" text="转审" v-if="showbar" @click="transfer()"/>

      <!-- 经办操作 -->
      
      <ActionBarButton icon="" text="催办"  v-if="showbar2&&FlowStateEunm.PASS!=data.basic?.status" type="default" @click="urge({act:'urge'})"/>
      <ActionBarButton text="撤销" color="#ff5000" v-if="showbar2&&FlowStateEunm.PASS!=data.basic?.status" type="default" @click="act({act:'cancel'})"/>
      <ActionBarButton text="编辑" v-if="data.info.creator==userid&&!data.info.invoiceids&&data.info?.state!=InvoicingStatesEnum.DELETEED"   type="default"  @click="edit"/>
      <ActionBarButton type="default" v-if="data.info.creator==userid&&data.info.state==1"   text="作废"  @click="del()"/>
      <ActionBarButton type="warning" v-if="data.info.creator==userid&&data.info.state==6"   text="取消作废"  @click="cancelDel()"/>
    </ActionBar>
    
    <CompanyDialog :id="companyid" :show.sync="showCompany" @update:show="(val:any)=>showCompany=val"/>
    <CompanyDialog :customer="customer" :show.sync="showCustomer" @update:show="(val:any)=>showCustomer=val"/>
    <ContractsDialog :ids="contractids" :show.sync="showContract" @update:show="(val:any)=>showContract=val"/>
    <ActionSheet v-model:show="showProject" title="预决算信息" style="height:100vh;">
      <Budgetdetail :id="projectid" :key="projectid" :showTab="true" show="final" />
    </ActionSheet>
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
    <Invoice_Uploader :par="{businesstype:data.info.businesstype}" :show.sync="showIU" @update:show="(val:any)=>showIU=val" :invoicingid="invoicingid" @suc="onUpInvoice"/>
    <Upload_Dialog  accept=".pdf" :show.sync="showPdf" @update:show="(val:any)=>showPdf=val" :fileurls="data.info?.pdffileurls" @update:value="(val:any)=>pdffileurls=val" @update="onUpPdf"/>
    <Print_Dialog :show.sync="showPrint" @update:show="(val:any)=>showPrint=val" :ids="data.info?.id"></Print_Dialog>
    <Contract_Select :show.sync="addContract"  @update:show="(val:any)=>addContract=val" @update:value="(val:any)=>contractCur=val"/>
  </div>
</template>
<script  lang="ts">

import { Divider,ActionSheet,Image,Tag,Cell,CellGroup,Steps,Step,Badge,Field,Button,showDialog,showConfirmDialog,Dialog,Uploader,ActionBar,ActionBarIcon,ActionBarButton} from 'vant';
import { addcontract, canceldelinvoicingnotice, delinvoicingnotice, flowact, getbyid, getflowdata, savepdfinvoice, startflow, viewflow } from '../invoice';
import Filescard from '@/views/budget/components/filescard.vue'
import { FlowStateEunm} from '@/views/invoice/invoicing_config';
import { InvoicingStatesEnum } from '@/views/invoice/invoicing_config'
import { useUserStore } from '@/stores';
import { storeToRefs } from 'pinia';
import { softKeyboard } from '../../../utils/common'
import CompanyDialog from './Company_Dialog.vue'
import ContractsDialog from './Contracts_Dialog.vue'
import Budgetdetail from '@/views/budget/components/budgetdetail.vue'

import Invoicingitems from './invoicingitems.vue';
import UserSelect from '@/views/invoice/components/UserSelect.vue'
import Invoice_list from './Invoice_list.vue';
import Invoice_Uploader from './Invoice_Uploader.vue';
import Upload_Dialog from './Upload_Dialog.vue';
import Print_Dialog from './Print_Dialog.vue';
import Contract_Select from './Contract_Select.vue';
import Flow_Steps from '@/views/finance/components/Flow_Steps.vue';
import { h } from 'vue';
const { loginStatus, userInfo } = storeToRefs(useUserStore());
export default {
  components: {
    Contract_Select,Flow_Steps,
    Print_Dialog,Invoice_Uploader,Invoice_list,CompanyDialog,ContractsDialog,ActionSheet,Budgetdetail,Invoicingitems,UserSelect,
    Image,Tag,Divider,Cell,CellGroup,Steps,Step,Badge,Filescard,Field,Button,Dialog,Uploader,ActionBar,ActionBarIcon,ActionBarButton,Upload_Dialog
  },
  props: ['thirdNo', 'invoicingid','state','info'],
  data () {
    return {
      FlowStateEunm:FlowStateEunm,
      InvoicingStatesEnum:InvoicingStatesEnum,
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
      data:<any>{info:this.info,basic:{},statusCn:["", "审批中", "已同意", "已驳回", "已取消"]},
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
      contractCur:<any>{}
    }
  },
  
  mounted() {
  
   this.userid = userInfo.value.userId
  
   this.curThirdNo = this.thirdNo
   this.getdata()
  },
  watch:{
    info(val){
      if(val&&(!this.data.info||!this.data.info.id)){
        this.data.info=val
        this.pdffileurls = val.pdffileurls
      }
      
    },
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
              this.act({act:'alter',userid:this.transferUser.userid,step:this.step,thirdNo:this.data.basic?.thirdNo})
              this.showUser = false
            })
            .catch(() => {
            });

      }
      
    },
    contractCur(val:any){

      showConfirmDialog({
            title: '关联合同【'+val.title+'】吗？',
            className:'confirmDialog'
          })
            .then(() => {
              addcontract({id:this.data.info.id,contractid:val.id}).then((res:any) => {
                if (res.errorMessage){
                  showDialog({message:res.errorMessage})
                }else{
                  showDialog({message:'关联成功'})
                  this.data.info.contractnames = val.title
                  this.data.info.contractid = val.id
                }
              })
            })
            .catch(() => {
            });
      
    }

  },
  created() {
    
  },
  methods:{
    uploadinvoice(id:any){
      this.showIU = true
    },
    onUpPdf(val:any){
      savepdfinvoice({id:this.data.info.id,pdffileurls:val}).then((res:any)=>{ 
        if (res.errorMessage){}else{
          showDialog({'message':'pdf发票上传成功'})
          this.showPdf=false
          this.pdffileurls=val
        }
      })
    },
    onUpInvoice(){
   
      getbyid({id:this.invoicingid}).then((res:any)=>{
        if (res) {
          this.data.info = res.data||{}
          this.invoicekey = this.invoicekey+1
        }
      })
    },
    viewCompany(companyid:any){
      this.showCompany=true
      this.companyid = companyid
    },
    viewCustomer(customer:string){
      this.showCustomer=true
      this.customer = JSON.parse(customer)
    },
    unionContract(){
      this.addContract=true
    },
    viewContract(ids:any){
      if (ids){
        this.contractids = ids
        this.showContract=true
      }else{
        this.addContract = true
      }
      
    },
    viewProject(id:any){
      this.projectid = id
      this.showProject = true
    },
    del(){
      showConfirmDialog({
        title: '确定要作废吗？',
      })
      .then(() => {
        delinvoicingnotice({id:this.data.info.id}).then((res:any)=>{
          if (res.errorMessage){
            showDialog({'message':res.errorMessage})
          }else{
            this.data.info.state=InvoicingStatesEnum.WAITFORDELETE
          }
        })
      })
      .catch(() => {
      })
    },
    cancelDel(){
      showConfirmDialog({
        title: '确定取消作废吗？',
      })
      .then(() => {
        canceldelinvoicingnotice({id:this.data.info.id}).then((res:any)=>{
          if (res.errorMessage){
            showDialog({'message':res.errorMessage})
          }else{
            this.data.info.state=InvoicingStatesEnum.INVOICED
          }
        })
      })
      .catch(() => {
      })
    },
    startflow(act:any){
      this.doing = true
      setTimeout(() => {
        this.doing = false
      }, 5000);
      

       viewflow({act,infoid:this.invoicingid}).then((res:any)=>{
          
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

                startflow({act,infoid:this.invoicingid}).then((res:any)=>{
     
                  if(res.errorMessage){}else{
                    this.curThirdNo = res.thirdNo
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
    edit(){
      this.$router.push({name:'kaipiaoxitong_add',query:{invoicingid:this.invoicingid}})
    },
    
    getdata(){
      
      if (!this.invoicingid){
        showDialog({message:'invoicingid为空'})
        return
      }
      
      var par:any = {infoid:this.invoicingid}
      if (this.state) par.state = this.state
      par.thirdNo = this.curThirdNo || this.$route.query?.thirdNo
      if(!par.thirdNo) return
      
      getflowdata(par).then((res:any)=>{
      
     
        this.data = res
        this.data.basic = res.basic||{}
        this.pdffileurls = this.data.info?.pdffileurls
        this.showbar = (this.data.basic?.approvalUserid||'').indexOf(this.userid)>-1 && this.data.basic?.status==FlowStateEunm.ING
        this.showbar2=(this.data.basic?.userId||'').indexOf(this.userid)>-1 && this.data.basic?.status!=FlowStateEunm.CANCEL
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
      if (!flow.thirdNo) flow.thirdNo = this.data.basic?.thirdNo||this.data.basic?.thirdno
      flowact(flow).then((res:any)=>{
        if (res.errorMessage){
        }else{
          showDialog({'message':'催办成功'})
        }
      })
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
      // this.showTransfer = true
      this.showUser = true
    },
    confirmTransfer(){
      
      if (!this.transferUser){
        showDialog({message:'请选择转审人'})
        return
      }
     
      this.act({act:'alter',userid:this.transferUser.userid,step:this.step,thirdNo:this.data.basic?.thirdNo})
      this.showTransfer = false
    },
  
  }
}
</script>
<style   lang="css" src="@/views/financeCss.css"></style>