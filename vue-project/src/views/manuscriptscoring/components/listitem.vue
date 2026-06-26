<template>
  <div id="scorelist" v-if="data">
    <div v-if="data" class="listrow" >
  
      <Checkbox @click.stop v-if="checkable" shape="square" v-model="checked" style="width: 25px!important;text-align: right;flex-shrink: 0;"></Checkbox>
      
      <div class="value"  style="box-sizing: border-box;" @click="view">
        
        <span v-if="statename" :style="{margin:'0 5px',color:statecolor,border:'1px solid #F5F2EF',padding:'2px'}" >{{ statename }}</span>
        
        <span style="color: black;" @click="viewcontent">{{ data?.title }}</span>
        <span style="color: gray;margin-left: 5px;">{{ data?.edition }}版</span>
        
      </div>
      
          
        
        <div class="date" style="width: 80px!important;text-align: right;flex-shrink: 0;flex-grow: 1;">
          <span>{{ data?.date.substring(5,10) }}</span>
          
        </div>
    </div>

    <div>
        

      </div>
    <UserScoring :column="3" :datas="data?.scores" @update:value="(val:any)=>scores=val" :edit="edit" :state="data?.state"/>
  </div>
  <ViewHtml class="html-content" :data="content" :key="rkey" :show.sync="showHtml"  @update:show="(val:any)=>showHtml=val"/>

  
  
</template>
<script  lang="ts">

import { Tag,CheckboxGroup,Checkbox,Grid,GridItem,Icon } from 'vant';
import { StatesEnum } from '../config';
import UserScoring from './UserScoring.vue';
import UserSelect from '@/views/invoice/components/UserSelect.vue';
import ViewHtml from './ViewHtml.vue';

  export default {
    components: {
      Tag,CheckboxGroup,Checkbox,UserScoring,Grid,GridItem,UserSelect,Icon,ViewHtml
    },
    props: ['data','showVerify','check','checkable','edit'],
    data () {
      return {
        statecolor:'',
        statename:'',
        amountsTypeName:'',
        checked:<any>false,
        scores:[],
        showUser:false,
        editor:{},
        showHtml:false,
        rkey:0,
        content:''
      }
    },
    watch:{
      check(val){
        this.checked = val
      },
      checked(val){
       
        this.$emit('check',val)
      },
      scores(val){
        this.$emit('update:scores',val)
      
      },

    },
    mounted() {
   
      this.scores = this.data?.scores
      var text = ''
      var color = 'default'
      var record = this.data
      if (record){
        if (record.status==StatesEnum.REJECT){
          text = '已驳回';color='gray';
        }else if (record.status==StatesEnum.CANCEL){
          text = '已取消';color='gray';
        }else if (record.status == StatesEnum.PASS){
          text = '已打分';color='green';
        } else if (record.status == StatesEnum.ING){
          text = '审批中';color='#FF6D25';
        }
      }
      this.statename = text
      this.statecolor = color
    },
    created() {

    },
    methods:{
      view(){ 
        this.$emit('view')
      },
      viewcontent(){
        this.content = this.data.content
        this.showHtml = true
      }
    }
  }
</script>
<style  src="@/views/financeCss.css">

</style>
