
<template>
  <div class="box">
    
      <Form @submit="onSubmit" required="auto">

          <Field
            v-model="data.paper"
            is-link
            :disabled="data.thirdNo?true:false"
            readonly
            required
            name="paper"
            label="报纸"
            placeholder="点击选择报纸"
            @click="showPaper=data.thirdNo?false:true"
            :rules="[{ required: true, message: '不能为空' }]"
          />
          <div style="display: flex;flex-direction: row;align-items: center;">

            <Field
              v-model="data.date"
              style="width: 70%;"
              readonly
              required
              name="date"
              label="日期"
              placeholder="点击选择日期"
              
              @click="showDate=true"
              :rules="[{ required: true, message: '不能为空' }]"
            />
          
          <Field
            v-model="data.time"
            style="width: 30%;"
            readonly
            required
            name="time"
            label=""
            placeholder="延误时间"
            @click="showTime=true"
        
            :rules="[{ required: true, message: '不能为空' }]"
          />
          </div>
          <Field
            v-model="data.layout"
            name="layout"
            label="版面"
            required
            placeholder="请输入版面"
            
            :rules="[{ required: true, message: '不能为空' }]"
          />
          <Field
            
            v-model="data.reason"
            name="reason"
            label="延迟说明"
            type="textarea"
            rows="1"
            
            :autosize="true"

            placeholder="延迟说明"
            
            :rules="[{ required: false, message: '不能为空' }]"
            
          />
          <Field
            v-model="data.director"
            is-link
            readonly
            required
            name="director"
            label="值班主任"
            placeholder="点击选择值班主任"
            :disabled="data.thirdNo?true:false"
            @click="showDirector=data.thirdNo?false:true"
            :rules="[{ required: true, message: '不能为空' }]"
          />
          <Field
            v-model="data.leader"
            is-link
            readonly
            required
            name="leader"
            label="值班领导"
            placeholder="点击选择值班领导"
            :disabled="data.thirdNo?true:false"
            @click="showLeader=data.thirdNo?false:true"
            :rules="[{ required: true, message: '不能为空' }]"
          />
          
        
   

      <div style="margin: 16px;">
        
        <Button v-if="!data.thirdNo" :loading="loading" round block type="primary" native-type="submit" style="font-size: calc(var(--van-cell-font-size)*var(--Big));">
          提交
        </Button>
    
        <Button v-if="data.thirdNo" round block type="primary" @click="update" style="font-size: calc(var(--van-cell-font-size)*var(--Big));">
          修改
        </Button>
      </div>
    </Form>


    <DatePicker_Dialog :show.sync="showDate"  @update:show="(val:any)=>showDate=val"  @update:label="(val:any)=>data.date=val"/>
    <TimePicker_Dialog :show.sync="showTime"  @update:show="(val:any)=>showTime=val"  @update:label="(val:any)=>data.time=val"/>
    <Preview_Flow :show.sync="showPreview"  @update:show="(val:any)=>showPreview=val" :obj="data" />
    <Common_Select key="paperid" :options="paperColumns" :show.sync="showPaper"  @update:show="(val:any)=>showPaper=val"   @update:label="(val:any)=>data.paper=val" @update:value="(val:any)=>data.paperid=val" @change="onPapaerChange"
    />
    <Common_Select key="directorid" :options="directorColumns" :show.sync="showDirector"  @update:show="(val:any)=>showDirector=val" @update:value="(val:any)=>data.directorid=val"  @update:label="(val:any)=>data.director=val"
    />
    <Common_Select key="leaderid" :options="leaderColumns" :show.sync="showLeader"  @update:show="(val:any)=>showLeader=val" @update:value="(val:any)=>data.leaderid=val"  @update:label="(val:any)=>data.leader=val"
    />
    
  </div>
</template>
<script  lang="ts">
import {h} from 'vue'
import {  Form,CellGroup,Field,Button,Card,Uploader,Radio,RadioGroup,showDialog,showConfirmDialog,Switch } from 'vant';

import Dict_Select from '../invoice/components/Dict_Select.vue';

import UserSelect from '../invoice/components/UserSelect.vue';

import Filescard from '../budget/components/filescard.vue';
import Contract_Select from '@/views/finance/components/Contract_Select.vue';
import Upload_Dialog from '@/views/invoice/components/Upload_Dialog.vue';

import Flow_Steps from '@/views/finance/components/Flow_Steps.vue';

import { extractParameterFromUrl } from '../budget/utils';
import { useUserStore } from '@/stores';
import Preview_Flow from '@/views/finance/components/Preview_Flow.vue';
import Common_Select from './components/Common_Select.vue';
import DatePicker_Dialog from './components/DatePicker_Dialog.vue';
import { findpersononduty, getdata, getflow, getpapers, save } from './press';
import TimePicker_Dialog from './components/TimePicker_Dialog.vue';

const cacheStore = useUserStore()

  export default {
    components: {
      TimePicker_Dialog,DatePicker_Dialog,Common_Select,Preview_Flow,Flow_Steps,Upload_Dialog,Filescard,Contract_Select,UserSelect,Dict_Select,Form,CellGroup,Field,Button,Card,Uploader,Radio,RadioGroup,Switch,
    },
    
    data () {
      return {
        extractParameterFromUrl:extractParameterFromUrl,
        data:<any>{},
        showPreview:false,
        showPaper:false,
        showDirector:false,
        paperColumns:[],
        directorColumns:[],
        leaderColumns:[],
        showLeader:false,
        showDate:false,
        loading:false,
        showTime:false
          
      }
    },

    mounted() {

      getpapers({}).then((res:any)=>{
        this.paperColumns = res||[]
      })
      var thirdNo = this.$route.query?.thirdNo;

     

      if (thirdNo){
        getdata({thirdNo:thirdNo}).then((res:any)=>{
          if (res.errorMessage){
            showDialog({message:res.errorMessage})
          }else{
            this.data = res.info
            this.getOptions(this.data.paperid)
          }
        })
      }
      

    },
    created() {
    },
    methods:{
      onPapaerChange(val:any){
    
        if (val){
          this.getOptions(val.value)
        }
      },
      getOptions(company:any){
        findpersononduty({company}).then((res:any)=>{
          if (res){
            
            this.directorColumns = res.directors||[];
            this.leaderColumns = res.leaders||[];
          }
        })
      },
      update(){

        save({obj:this.data}).then((res:any)=>{
          if (res.errorMessage){
            showDialog({'message':res.errorMessage})
          }else{
            // 跳转到view页面
            if (res.thirdNo){
              this.$router.push({name:'press_view',query:{thirdNo:res.thirdNo}})
            }
              

          }
        })
      },


      onSubmit(values:any){

        if(this.loading) return
        this.loading = true
        setTimeout(() => {
          this.loading = false
        }, 3000);
        
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

                save({obj:this.data}).then((res:any)=>{
                  if (res.errorMessage){
                    showDialog({'message':res.errorMessage})
                  }else{
                    // 跳转到view页面
                    if (res.thirdNo){
                      this.$router.push({name:'press_view',query:{thirdNo:res.thirdNo}})
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
      onPaperChange(e:any){
        console.log(e)
      }

       

    }
  }
</script>
<style  lang="css">
:root{
  --Big: 1.3;
}

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

  .van-cell {
    position: relative;
    display: flex;
    box-sizing: border-box;
    width: 100%;
    padding: calc(var(--van-cell-vertical-padding)*var(--Big)) calc(var(--van-cell-horizontal-padding));
    overflow: hidden;
    color: var(--van-cell-text-color);
    font-size: calc(var(--van-cell-font-size)*var(--Big));
    line-height: var(--van-cell-line-height);
    background: var(--van-cell-background);
}
.van-field__label {
    flex: none;
    box-sizing: border-box;
    width: calc(var(--van-field-label-width)*0.9);
    margin-right: 0;
    color: var(--van-field-label-color);
    text-align: left;
    word-wrap: break-word;
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
