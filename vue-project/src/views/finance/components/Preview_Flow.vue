<template>
  <ActionSheet v-model:show="visible" title="预览流程" show-cancel-button style="height: 80vh;">
     <div class="row" style="align-items: flex-start;justify-content: flex-start;">
        <div v-if="curdata?.viewdata && curdata?.viewdata?.approval" class="col" style="width: 35%;">
 
          <div class="row" style="border-bottom: 1px solid #eee;padding: 5px;" v-for="(item,index) in curdata?.viewdata?.approval||[]">
             <Image width="30" height="30" fit="cover" style="margin-right: 10px;" :round="true" :src="item?.avatar"/>
             <span>{{ item.title }}</span>
          </div>
          <div style="text-align: left;margin-left: 10px;">抄送:{{ curdata.viewdata?.notify?curdata.viewdata?.notify.join(','):'' }}</div>
        </div>
        <div class="col" style="width: 65%;">
          <Form  required="auto">
            <Field
              v-model="data.payer"
              readonly

              :disabled="data.id"
              name="payer"
              :label="data.amountsType  == FinanaceType.SALARY? '申请部门':'付款单位'"
              placeholder="点击选择"
              @click="showPayer= true"
              :rules="[{ required: true, message: '不能为空' }]"
            />
    

            <Field
              v-model="data.amount"
              name="amount"
              :label="data.amountsType  == FinanaceType.SALARY? '应发金额':'付款金额'"
              placeholder="金额"

              :disabled="data.id"
              :rules="[{ required: true, message: '不能为空' },{pattern:/^-?\d+(\.\d{1,2})?$/,message:'格式有误'}]"
            />
            <Field
              v-model="data.userId"
              name="userId"
              label="用户"
              placeholder="点击修改用户"

              @click="showUser= true"
              :rules="[{ required: true, message: '不能为空' }]"
            />
            <Field
              v-model="amountsTypeName"

              label="申请类型"
              readonly
              placeholder="点击选择申请类型"
              @click="showAmountsType= true"
              :rules="[{ required: true, message: '不能为空' }]"
            />


            
            
          </Form>
          <Copyable_Cell v-if="curdata?.templatename" :value="curdata?.templatename" title="流程:"/>
          <Copyable_Cell v-if="curdata?.viewdata?.templateid" :value="curdata?.viewdata?.templateid" title="流程id:"/>
          <ActionBar >
            <ActionBarButton type="primary" @click="search">查询</ActionBarButton>
            <ActionBarButton  @click="search">打印</ActionBarButton>
          </ActionBar>
          <Dict_Select type="付款审批类型" :initialValue="amountsType||0" :show.sync="showAmountsType"  @update:show="(val:any)=>showAmountsType=val"  @update:value="(val:any)=>amountsType=val"  @update:label="(val:any)=>amountsTypeName=val"/>
          <UserSelect  :show.sync="showUser" @update:show="(val:any)=>showUser=val" @update:value="(val:any)=>data.userId=val.userid" ></UserSelect>
          <Payer_Select :show.sync="showPayer"  @update:show="(val:any)=>showPayer=val" @update:value="(val:any)=>data.payerid=val" @update:label="(val:any)=>data.payer=val" @update:cross="(val:any)=>{
              isCrossDept=val
              crossChecked=val
            }" 
            />
        </div>
     </div>
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,Field,Cell,Form, showDialog,Image,Switch,Button,ActionBarButton,ActionBar,RadioGroup,Radio} from 'vant';
import { FinanaceType } from '../finance_config';
import Payer_Select from './Payer_Select.vue';
import UserSelect from '@/views/invoice/components/UserSelect.vue';
import { getflow } from '../finance';
import Flow_Steps from './Flow_Steps.vue';
import Copyable_Cell from '@/views/invoice/components/Copyable_Cell.vue';
import Dict_Select from '@/views/invoice/components/Dict_Select.vue';

  export default {
    components: {
      Dict_Select,RadioGroup,Radio,ActionSheet,Field,Cell,Form,Payer_Select,UserSelect,Flow_Steps,Image,Switch,Button,ActionBarButton,ActionBar,Copyable_Cell
    },
    props: ['show','obj'],
    data () {
      return {
        FinanaceType:FinanaceType,
        visible:false,
        selectedValue:'',
        showAmountsType:false,
        amountsTypeName:'付款审批',
        options:<any>[],
        showPayer:false,
        isCrossDept:false,
        crossChecked:false,
        amountsType:this.obj.amountsType||0,
        data: this.obj||{},
        showUser:false,
        curdata:<any>{viewdata:{}}

      }
    },
    watch:{
      show(val){
        this.visible = val
      },
      visible(val){
        this.$emit('update:show',val)
      },
      
    },
    mounted() {
      // this.search()

    },
    created() {

    },
    methods:{
      search(){
            
            var obj = this.data
            obj.amountsType = this.amountsType
            obj.print = true

            getflow({obj}).then((res:any)=>{
              if (res.errorMessage){
                showDialog({message:res.errorMessage})
              }else{
                this.curdata = res||{viewdata:{}}
                console.log(this.curdata)
              }
            })
        },
  

    }
  }
</script>
<style>
 .row{
    display:flex;
    flex-direction: row;

 }
 .col{
  display: flex;
  flex-direction: column;
 }
</style>
