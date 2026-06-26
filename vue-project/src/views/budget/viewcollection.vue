
<template>
  <div class="box1">
    <div class="header">合同回款确认</div>
    <CellGroup>
       <div class="cell">
         <div class="label">合同名称：</div>
         <div class="value">{{data.title }}</div>
       </div>
       <div class="cell">
         <div class="label">合同编号：</div>
         <div class="value">{{data.serial }}</div>
       </div>
       <div class="cell">
         <div class="label">履约条件：</div>
       </div>
       <div class="cell" v-if="data.payconditions">
        <div  class="row" v-for="c in data.payconditions">
          <span>{{c.date.substring(0,10)+'前累计回款 '}}</span>
          <span style="color:red;font-weight:bold;padding-left:5px;">{{c.rate}}%</span>
          
        </div>
       </div>
       <div class="cell">
         <div class="label">回款纪录：</div>
       </div>
       <div class="cell">
          <collection v-for="e in data.paycollections" :data="e" @change="onChange" />
       </div>
      </CellGroup>
  </div>
</template>
<script  lang="ts">
import {CellGroup} from 'vant'
import { getcontract } from './budget';
import Collection from './components/collection.vue'


  export default {
    components: {
      CellGroup,Collection
    },
    data () {
      return {
        par:this.$route.query,
        data:<any>{}
      }
    },
    mounted() {
      if (this.par.contractid){
        getcontract({id:this.par.contractid}).then((res:any)=>{
          this.data = res.data||{}
        })
      }
    },
    created() {
    },
    methods:{
      onChange(){
        if (this.par.contractid){
          getcontract({id:this.par.contractid}).then((res:any)=>{
            this.data = res.data||{}
          })
        }
      }

    }
  }
</script>
<style  lang="css">
.box1{
    width: 100%;
    min-height: 100vh;
    background-color: white;
    box-sizing: border-box;
    margin: 0;
    position: relative;
    font-weight: normal;
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
  .cell{
    position: relative;
    display: flex;
    box-sizing: border-box;
    width: 100%;
    padding: 8px var(--van-cell-horizontal-padding) 0 var(--van-cell-horizontal-padding);
    overflow: hidden;
    color: var(--van-cell-text-color);
    font-size: var(--van-cell-font-size);
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
    width: var(--van-field-label-width);
    color: #969799;
    text-align: left;
    word-wrap: break-word;
  }
  .cell .value{
    position: relative;
    overflow: hidden;
    color: #323233;
    text-align: right;
    vertical-align: middle;
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
    .box1{
      width: 100%;
      min-height: 100vh;
      box-sizing: border-box;
      margin: 0;
      position: relative;
      font-weight: normal;
    }
    .cell{
        position: relative;
        display: flex;
        box-sizing: border-box;
        width: 100%;
        padding: 13.6px var(--van-cell-horizontal-padding) 0 var(--van-cell-horizontal-padding);
        overflow: hidden;
        color: var(--van-cell-text-color);
        font-size: 18px;
        line-height: 30px;
        background: var(--van-cell-background);
        flex-wrap: wrap;
      }
      .cell::after {
          content: '';
          display: block;
          width: 100%;
          margin: 0 auto;
          margin-top: 13.6px;
          border-bottom: 1px solid rgb(249, 247, 247);
        }
  }
</style>
