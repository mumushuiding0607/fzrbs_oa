<template>
  <Tabs v-model:active="active">
      <Tab title="表1.收支总表">
        <BudgetItem :show="show" v-for="(item,index) in datas[0]" :index="index+1" :data="item"/>
      </Tab>
      <Tab title="表2.收入明细表">
        <BudgetItem :show="show" v-for="(item,index) in datas[1]" :index="index+1" :data="item"/>
      </Tab>
      <Tab title="表3.支出明细表">
        <BudgetItem :show="show" v-for="(item,index) in datas[2]" :index="index+1" :data="item"/>
      </Tab>
    </Tabs>
</template>
<script  lang="ts">
  import { showDialog,Tabs,Tab } from 'vant';
  import { ProjectStatesEnum,FlowStateEunm } from '../config';
  import { getbudgetinfo } from '../budget';
  import BudgetItem from './budgetItem.vue';
 
  export default {
    components: {
      showDialog,Tabs,Tab,BudgetItem
    },
    props: ['id','showTab','show'],
    data () {
      return {
        titles:<any>['表1.收支总表','表2.收入明细表','表3.支出明细表'],
        active:0,

        datas:<any>[[],[],[]],
        ProjectStatesEnum:ProjectStatesEnum,
        FlowStateEunm:FlowStateEunm
      }
    },
    mounted() {
      getbudgetinfo({id:this.id}).then((res:any)=>{
      if (res.errorMessage) {
        showDialog({message:res.errorMessage})
      } else {
        this.datas = res.budget||[[],[],[]]
      }
    })
    },
    created() {

    },
    methods:{

    }
  }
</script>
<style lang="css" >
  .van-tab__text--ellipsis {
      display: -webkit-box;
      overflow: inherit;
      -webkit-box-orient: vertical;
      font-size: 16px;
  }
</style>
<style>
  @media screen and (min-width: 500px) {
    .van-tab__text--ellipsis {
        display: -webkit-box;
        overflow: inherit;
        -webkit-box-orient: vertical;
        font-size: 20px;
    }
  }
</style>
