<template>
  <div  v-if="data">
    <div v-if="data" class="listrow" >
  
      <Checkbox @click.stop v-if="checkable" shape="square" v-model="checked" style="width: 25px!important;text-align: right;flex-shrink: 0;"></Checkbox>
      
      <div class="value"  style="box-sizing: border-box;" >
        <span v-if="statename" :style="{margin:'0 5px',color:statecolor,border:'1px solid #F5F2EF',padding:'2px'}" >{{ statename }}</span>
        <span style="color: black;">{{ data.title }}</span>
        
      </div>
      
          
        
        <div class="date" style="width: 80px!important;text-align: right;flex-shrink: 0;flex-grow: 1;">
          <span>刊期：{{ data?.date.substring(0,10) }}</span>
        </div>
    </div>

    <div>
        

      </div>
  </div>

  
  
</template>
<script  lang="ts">

import { Tag,CheckboxGroup,Checkbox,Grid,GridItem,Icon } from 'vant';

  export default {
    components: {
      Tag,CheckboxGroup,Checkbox,Grid,GridItem,Icon
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
        editor:{}
      }
    },
    watch:{
      check(val){
        this.checked = val
      },
      checked(val){
        this.$emit('check',val)
      },

    },
    mounted() {
      var text = ''
      var color = 'default'
      var record = this.data
      if (record){
        if (record.state==0){
          text = '待确认';color='gray';
        }else if (record.state == 2){
          text = '已打分';color='green';
        } else if (record.state == 1){
          text = '打分中';color='#FF6D25';
        }
      }
      this.statename = text
      this.statecolor = color
    },
    created() {

    },
    methods:{
 
    }
  }
</script>
<style  src="@/views/financeCss.css">

</style>
