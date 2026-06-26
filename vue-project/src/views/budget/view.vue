
<template>
  <div class="box">
    <Tabs  swipe-threshold="3"  v-model:active="active"  :sticky="true" style="margin-left: 0;padding-left: 0;" type="card">
      <Tab  v-if="par.thirdNo" title="审批">
        <Viewflow :projectid="par.projectid" :thirdNo="par.thirdNo" />
      </Tab>
      <Tab  v-if="project.state>ProjectStatesEnum.BUDGET&&project.finalreport" title="决算报告">
        <NoticeBar
          style="display: block;height: 100vh;background-color: white;color: black;margin-top: 10px;"
          v-html="project?.finalreport||'暂无'"
        />
      </Tab>
      <Tab  v-if="project.approvaltype>ProjectStatesEnum.BUDGET" title="决算收支情况表">
        <Budgetdetail :id="par.projectid" :showTab="true" show="final" />
      </Tab>
      <Tab  v-if="project.state>ProjectStatesEnum.FINAL" title="决算审批">
        <Viewflow :projectid="par.projectid"  :state="ProjectStatesEnum.FINAL"/>
      </Tab>
      <Tab v-if="project.budgetreport" title="预算报告">
        <NoticeBar
          style="display: block;height: 100vh;background-color: white;color: black;;margin-top: 10px;"
          v-html="project?.budgetreport||'暂无'"
        />
      </Tab>
      <Tab  title="预算收支情况表">
        <Budgetdetail :id="par.projectid" :showTab="true" show="budget" />
      </Tab>
      <Tab  title="预算审批" v-if="project.state>ProjectStatesEnum.BUDGET">
        <Viewflow :projectid="par.projectid"  :state="ProjectStatesEnum.BUDGET"/>
      </Tab>

    </Tabs>
  </div>
</template>
<script  lang="ts">

import { Tabs,Tab,NoticeBar } from 'vant';
import Viewflow from './components/viewflow.vue';
import { getproject } from './budget';
import { ProjectStatesEnum,FlowStateEunm } from './config';
import Budgetdetail from './components/budgetdetail.vue';
  export default {
    components: {
      Viewflow,
      Tabs,Tab,Image,NoticeBar,Budgetdetail
    },
    data () {
      return {
        par:this.$route.query,
        active:0,
        project:<any>{},
        ProjectStatesEnum:ProjectStatesEnum
      }
    },
    mounted() {
      getproject({id:this.par.projectid}).then(res=>{
        if (res) this.project = res
      })
    },
    created() {
    },
    methods:{

    }
  }
</script>
<style  lang="css">

  .van-notice-bar span{
    font-size:21px!important;
  }
 
  .box{
    width: 100%;
    min-height: 100vh;
    border-right: 2px solid #eff2f5;
    border-left: 2px solid #eff2f5;
    margin-left:0 ;
    margin-top: 0;
  }

  /* tab */
  .van-tabs__wrap {
      height: 40px;
  } 
  .van-tabs__nav .van-tabs__nav--card{
    height: 40px;
  }
  .van-tabs__nav--card {
      box-sizing: border-box;
      margin: 0 ;
      border: 1px solid rgb(238, 238, 238);
      border-radius: var(--van-border-radius-sm);
      height: 40px;
      padding: 0;
      
  }
  .van-tab--card {
      color: #666;
      background-color: white;
      border: none;
      font-size: 18px;
      border-right: 1px solid rgb(238, 238, 238);
  }
  .van-tab__text--ellipsis {
      display: -webkit-box;
      overflow:inherit;
      -webkit-box-orient: vertical;
  }
  .van-tab--card.van-tab--active {
    background-color: #F1F1F1;
    color: #666;
  }

  
</style>

<style>
  @media screen and (min-width: 500px) {
    .van-notice-bar span{
      font-size:21px!important;
    }    
    /* tab */
  .van-tabs__wrap {
      height: 40px;
  } 
  .van-tabs__nav .van-tabs__nav--card{
    height: 40px;
  }
  .van-tabs__nav--card {
      box-sizing: border-box;
      margin: 0 ;
      border: 1px solid rgb(238, 238, 238);
      border-radius: var(--van-border-radius-sm);
      height: 40px;
      padding: 0;
      
  }
  .van-tab--card {
      color: #666;
      background-color: white;
      border: none;
      font-size: 18px;
      border-right: 1px solid rgb(238, 238, 238);
  }
  .van-tab__text--ellipsis {
      display: -webkit-box;
      overflow:inherit;
      -webkit-box-orient: vertical;
  }
  .van-tab--card.van-tab--active {
    background-color: #F1F1F1;
  }
      
  }
</style>
