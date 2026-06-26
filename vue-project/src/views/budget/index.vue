
<template>
  <div class="page">
    <Tabs  class="mytab" style="margin-left: 0;padding-left: 0;"  v-model:active="active" type="card"   >
    <Tab   title="待审批">
      <List
        :loading="loading[0]"
        :finished="finished[0]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(0)"
      >
      <div v-for="item in datas[0]" :key="item.id" :title="item" class="listitem" @click="view(item)">

        <listitem  :data="item"></listitem>
      </div>
        
    </List>

    </Tab>
    

    
    <Tab title="回款">
      <List
        :loading="loading[3]"
        :finished="finished[3]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(3)"
      >
      <div v-for="item in datas[3]" :key="item.id" :title="item" class="listitem" @click="viewPayCollection(item)">

        <listitem  :data="item"></listitem>
      </div>
        
    </List>

    </Tab>
    <Tab title="历史审批">
      <List
        :loading="loading[2]"
        :finished="finished[2]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(2)"
      >
      <div v-for="item in datas[2]" :key="item.id" :title="item" class="listitem" @click="viewHistory(item)">

        <listitem  :data="item"></listitem>
      </div>
        
    </List>

    </Tab>
    <Tab title="全部">
      <List
        :loading="loading[1]"
        :finished="finished[1]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(1)"
      >
      <div v-for="item in datas[1]" :key="item.id" :title="item" class="listitem" @click="view(item)">
        <listitem  :data="item"></listitem>
      </div>
    </List>
    </Tab>
    
  </Tabs>
  </div>
</template>
<script  lang="ts">
import { approvallist, debtlist, getprolist,history,paycollectionchecklist } from '@/views/budget/budget';
import listitem from './components/listitem.vue';
import { Tabs,Tab } from 'vant';
import { List,Cell,Image } from 'vant';
import Button from 'vant/lib/button';
import { useUserStore } from '@/stores';
const cacheStore = useUserStore()
  
  export default {
    components: {
      Button,
      Tabs,Tab,List,Cell,Image,listitem
    },
    data () {
      return {
        active:0,
        refreshing:false,
        datas:<any>[[],[],[],[],[]],
        page:[0,0,0,0,0],
        loading:[false,false,false,false,false],
        finished:[false,false,false,false,false],
        
      }
    },
    watch:{
      active(val){
 
        var user = cacheStore.userInfo
        user.budgetIndexTab=val
        cacheStore.setUserInfo(user)
      }
    },
    mounted() {
      var tab = cacheStore.userInfo?.budgetIndexTab
      if(tab) this.active = tab
    },
    created() {
      this.active = 0
    },
    methods:{
      viewPayCollection(e:any){
        var q = {contractid:e.id}
        this.$router.push({name:'feibaoxitong_viewcollection',query:q})
      },
      viewDebt(e:any){
        var q = {contractid:e.id}
        this.$router.push({name:'feibaoxitong_debturge',query:q})
      },
      viewHistory(e:any){
        var q = {projectid:e.id,thirdNo:e.thirdno||e.thirdNo}
        if (e.data) {
          var temp = JSON.parse(e.data)
          q.projectid = temp.projectid
        }
        this.$router.push({name:'feibaoxitong_view',query:q})
      },
      view(e:any){
        // console.log(e.)
        this.$router.push({name:'feibaoxitong_view',query:{projectid:e.id,thirdNo:e.thirdno||e.thirdNo}})
      },

      async loaddata(index:number){
        
        this.loading[index] = true;
        var current = this.page[index]+1
        var res:any = null
        
        switch (index) {
          case 0:
            res = await approvallist({pageSize:10,current})
            break;
          case 1:
            res = await getprolist({pageSize:10,current})
            break
          case 2:
            res = await history({pageSize:10,current})
            break
          case 3:
            res = await paycollectionchecklist({pageSize:10,current})
            break
          case 4:
            res = await debtlist({pageSize:10,current})
            break
          default:
            break;
        }
        this.loading[index]=false
        if (res){
          
          // 数据全部加载完成
          if (res.data.length == 0) {
            this.finished[index] = true;
          }else {
            this.page[index] = current
          }
          this.datas[index].push(...res.data)
          console.log(this.loading)
        }
  
       

        
        
      }
    }
  }
</script>

<style   lang="css" src="@/views/financeCommon.css"></style>
