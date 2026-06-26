
<template>
  <div class="box">
    
      <Form @submit="onSubmit">
        <Field :disabled="data.state!=0"  v-model="contract.title" name="contractid" label="相关合同" placeholder="输入合同名称或合同编号" @click="addContract">
        </Field>
        <Field
          v-model:model-value="contractname"
          is-link
          readonly
          
          name="contract"
          label="合同业务"
          placeholder="点击选择合同业务"
          @click="showContractType = true"
        />
        <Field
          v-model:model-value="data.businesstype"
          is-link
          readonly
          required
          name="businesstype"
          label="业务类型"
          placeholder="点击选择业务类型"
          @click="showBusinesstype = true"
        />

        <Field name="type" label="发票类型">
          <template #input>
            <RadioGroup v-model="data.type" direction="horizontal">
              <Radio :name="0">普票</Radio>
              <Radio :name="1">专票</Radio>
            </RadioGroup>
          </template>
        </Field>
        <Field
          v-model="data.partbname"
          is-link
          readonly
          :disabled="data.state!=0"
          name="partbname"
          label="开票单位"
          placeholder="点击选择开票单位"
          @click="showPartb = true"
        />
        <Field
          v-model="data.partaname"
          is-link
          readonly
          name="partbname"
          label="客户名称"
          :disabled="data.state!=0"
          placeholder="点击选择客户名称"
          @click="showParta = true"
        />
        <Field
          v-model="data.receiver"
          is-link
          readonly
          name="receiver"
          label="发票抬头"
          :disabled="data.state!=0"
          placeholder="点击选择发票抬头"
          @click="showPartr = true"
        />
        <InvoicingitemsEdit :initialValue="data.items"  @update:value="(val:any)=>data.items=val"/>
        <Companyinfo_Input :key="ckey" :data.sync="customer" :item.sync="customer" :id="customerid" @update:value="(val:any)=>data.customer=val"/>
          <Field
            v-model="data.content"
            name="content"

            label="发票备注"
            placeholder="发票备注内容"
            
            :rules="[{ required: false, message: '不能为空' }]"
          />
        <Field  v-model="project.title" name="project" label="相关非报项目" placeholder="输入项目名称或编号" @click="showProject=true">
        </Field>
        <Field
            v-model="data.othercontent"
            name="othercontent"
            type="textarea"
            rows="3"
            :autosize="true"
            label="其他说明"
            placeholder="开票其他事项说明；非报系统未立项的项目请输入项目名称；报社签合同需转公司做活动的注明：（公司）。"
            
            :rules="[{ required: false, message: '不能为空' }]"
          />
        <Field name="uploader" label="附件" :disabled="data.state!=0">
          <template #input>
            
            <Uploader_Component :disabled="data.state!=0" accept="image/*,.pdf" :value.sync="data.fileurls" @update:value="(val:any)=>data.fileurls=val"  />
          </template>
        </Field>
      <NoticeBar
        :scrollable="false"
        wrapable 
        text="合同业务（标准合同、框架合同）申请开票未关联合同的，请在开票后一个月内上传并关联合同，若因未签合同导致款项无法收回的，根据报社规定部门负责人和相应业务人员需承担赔偿责任。"
      />
      <div style="margin: 16px;">
        <Button round block type="primary" native-type="submit">
          提交
        </Button>
      </div>
    </Form>
    <CompanySelect :show.sync="showPartb" :preloadInvoicingPartb="true" @update:show="(val:any)=>showPartb=val" @update:value="(val:any)=>data.partb=val" @update:label="(val:any)=>data.partbname=val"/>
    <CompanySelect :show.sync="showParta" :preloadInvoicingParta="true" @update:show="(val:any)=>showParta=val" @update:value="(val:any)=>{
      data.parta=val
      
      if (!data.receiver){
        customerid = val
        customer=null
        data.receiverid=val
      }
      
    }" @update:label="(val:any)=>{
      data.partaname=val
      data.receiver=val
    }"/>
    <CompanySelect :show.sync="showPartr"  @update:show="(val:any)=>showPartr=val" @update:value="(val:any)=>{
      data.receiverid=val
      customerid = val
        customer=null
    }" @update:label="(val:any)=>data.receiver=val"/>
    <ContractSelect :show.sync="showContract"  @update:show="(val:any)=>showContract=val" @update:value="(val:any)=>contract=val"/>
    <Project_Select :show.sync="showProject" :initialValue.sync="data.projectids"  @update:show="(val:any)=>showProject=val" @update:value="(val:any)=>{
      project=val
      data.projectids=val?val.id:''
    }"/>
    <Businesstype_Popup :show.sync="showBusinesstype"  @update:show="(val:any)=>showBusinesstype=val" @update:value="(val:any)=>data.businesstype=val" @update:label="(val:any)=>data.businesstype=val"/>
    <DictSelect type="合同业务类型" :initialValue.sync="data.contract" :show.sync="showContractType"  @update:show="(val:any)=>showContractType=val" @update:value="(val:any)=>data.contract=val" @update:label="(val:any)=>contractname=val"/>
  </div>
</template>
<script  lang="ts">

import { Form,CellGroup,Field,Button,Card,Uploader,Radio,RadioGroup,showDialog,NoticeBar } from 'vant';
import ContractSelect from './components/Contract_Select.vue';
import DictSelect from './components/Dict_Select.vue';
import CompanySelect from './components/Company_Select.vue';
import InvoicingitemsEdit from './components/Invoicingitems_edit.vue';
import Companyinfo_Input from './components/Companyinfo_Input.vue';

import Project_Select from './components/Project_Select.vue';
import { getbyid, saveinvoicing } from './invoice';
import Uploader_Component from './components/Uploader_Component.vue';
import Businesstype_Popup from './components/Businesstype_Popup.vue';
  export default {
    components: {
      Businesstype_Popup,Uploader_Component,Project_Select,Companyinfo_Input,InvoicingitemsEdit,Form,CellGroup,Field,Button,Card,Uploader,ContractSelect,DictSelect,Radio,RadioGroup,CompanySelect,NoticeBar
    },
    
    data () {
      return {
        data:<any>{type:0,state:0},
        showContract:false,
        showContractType:false,
        showBusinesstype:false,
        showPartb:false,
        showParta:false,
        showProject:false,
        contract:<any>{},
        project:<any>{},
        customerid:0,
        customer:<any>{},
        contractname:'',
        uploadImage:<any>[],
        files:<any>[],
        ckey:0,
        showPartr:false,
      }
    },
    watch:{
      contract(val){
    

        if (val&&val.id){
          this.data.contractid=val.id
          this.data.partb=val.partb
          this.data.parta=val.parta
          this.data.partbname=val.partbname
          this.data.partaname=val.partaname
          if (!this.data.receiverid){
            this.data.receiverid=val.parta
            this.data.receiver=val.partaname
          }
          if (!this.data.id){
            this.customer = null
            this.customerid = val.parta
          }
        }

      }
    },
    mounted() {
      var id = this.$route.query?.invoicingid;
 
      if (id){
        getbyid({id}).then((res:any)=>{


          this.data = res.data||{}
         
          if (res.data.customer){
            if (typeof res.data.customer === 'string'){
  
              this.customer = JSON.parse(res.data.customer)
              this.customerid = 0
              this.ckey++
              console.log('getbyid this.customer:',this.customer)
 
            }
          }
          this.contract = res.data.contracts||{}
    
          this.project = res.data.project||{}
          
          if (res.data.fileurls&&res.data.fileurls.split){
            this.uploadImage = res.data.fileurls.split(',').map((e:any)=>{
              return {
                url:e
              }
            })
          }
        })
      }
    },
    created() {
    },
    methods:{
      onSubmit(values:any){
      
        var err = ''
        console.log(this.data)
        var customer = this.data.customer

        if(!customer){
          err = '客户信息不能为空'
        }
        if (!customer.code){
          err = '客户信用代码不能为空'
        }
        if (this.data.type==1){
          if (!customer.address){
            err = '专票，客户公司地址不能为空'
          }else if (!customer.contacts){
            err = '专票，客户电话不能为空'
          }else if (!customer.bankaccount){
            err = '专票，客户开户信息不能为空，包含开户行和银行账号'
          }
        }
        if (!this.data.businesstype){
          err='业务类型不能为空'
        }
        if (err){
          showDialog({'message':err})
          return
        }
        
        saveinvoicing({obj:this.data}).then((res:any)=>{
          if (res.errorMessage){

          }else{
            if (res.data){
      
              this.$router.push({name:'kaipiaoxitong_view',query:{invoicingid:res.data.id}})
            }
            
          }
        })
        
      },
      addContract(){
        this.showContract=true
      
      },
      
    }
  }
</script>
<style  lang="css">


  .box{
    width: 100%;
    min-height: 100vh;
    border-right: 2px solid #eff2f5;
    border-left: 2px solid #eff2f5;
    margin-left:0 ;
    margin-top: 0;
  }
  .header{
    height: 10.6vw;
    font-size: 4.26vw;
    background: #F1F1F1;
    color:#666;
    display: flex;
    align-items: center;
    justify-content: center;
  }


  
</style>

<style>
  @media screen and (min-width: 500px) {

    .header{
      height: 40px;
      font-size: 20px;
      background: #F1F1F1;
      color:#666;
      display: flex;
      align-items: center;
      justify-content: center;
    }
  }
</style>
