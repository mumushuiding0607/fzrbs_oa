
<template>
  <div class="page">
    <Tabs class="mytab" style="margin-left: 0;padding-left: 0;"  v-model:active="active" type="card"   >
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
    <Tab   title="待开票">
      <List
        :loading="loading[3]"
        :finished="finished[3]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(3)"
      >
        <div v-for="item in datas[3]" :key="item.id" :title="item" class="listitem" @click="view(item)">

          <listitem  :data="item"></listitem>
        </div>
          
      </List>
    </Tab>
    <Tab   title="待作废">
      <List
        :loading="loading[4]"
        :finished="finished[4]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(4)"
      >
        <div v-for="item in datas[4]" :key="item.id" :title="item" class="listitem" @click="view(item)">

          <listitem  :data="item"></listitem>
        </div>
          
      </List>
    </Tab>
        
    <Tab title="全部">
      <div class="searchBox" >
        <Field
          v-model="keyword[1]"
          size="normal"
          center
          clearable
          style="padding:5px 5px 5px 15px;"
          placeholder="输入关键字: 金额、开票单位、合同未签"
        >
        </Field>
        
        <span class="btn" @click="onSearch(1)">搜索</span>
        <span class="btn" style="background-color: white;border:1px solid lightgray;color: black;" @click="showStates=true">状态</span>
      </div>

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
    <Tab title="已审批">
      <List
        :loading="loading[2]"
        :finished="finished[2]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(2)"
      >
      <div v-for="(item,inx2) in datas[2]" :key="'2'+inx2" :title="item" class="listitem" @click="viewHistory(item)">

        <listitem  :data="item"></listitem>
      </div>
        
    </List>

    </Tab>
    
    
  </Tabs>
  <tabbar :options="tabbarOptions" :active="activeTab"/>
  <State_Select :key="currentState" :show.sync="showStates" :value="currentState"  @update:show="(val:any)=>showStates=val" @update:value="(val:any)=>currentState=val"/>
  </div>
</template>
<script  lang="ts">
import { approvallist, getlist,history } from '@/views/invoice/invoice';
import listitem from '@/views/invoice/components/listitem.vue';
import { Tabs,Tab,Field } from 'vant';
import { List,Cell,Image } from 'vant';
import Button from 'vant/lib/button';

import { InvoicingStatesEnum } from './invoicing_config';
import { useUserStore } from '@/stores';
import State_Select from './components/State_Select.vue';
import tabbar from '@/components/bottomtabbar.vue'
import { gettabs } from '../invoice/invoice';
const cacheStore = useUserStore()
  export default {
    components: {
      Button,
      Tabs,Tab,List,Cell,Image,listitem,Field,State_Select,tabbar
    },
    data () {
      return {
        query:this.$route.query,
        active:0,
        refreshing:false,
        datas:<any>[[],[],[],[],[]],
        page:[0,0,0,0,0],
        loading:[false,false,false,false,false],
        finished:[false,false,false,false,false],
        keyword:['','','','',''],
        showStates:false,
        currentState:-1,
        tabbarOptions:[],
        activeTab:0,
      }
    },
    watch:{
      active(val){
 
        var user = cacheStore.userInfo
        user.invoiceTab=val
        cacheStore.setUserInfo(user)
      },
      currentState(val){
        var user = cacheStore.userInfo
        user.currentState=val
        cacheStore.setUserInfo(user)


        var tab = 1
        this.datas[tab]=[]
        this.page[tab]=0
        this.finished[tab]=false
      
        this.loaddata(tab)
      }
    },
    mounted() {
      var query:any = this.query
      var tab = cacheStore.userInfo?.invoiceTab
      if(tab) this.active = tab

      var currentState = cacheStore.userInfo?.currentState
      if(currentState!=undefined) this.currentState = currentState
      gettabs({}).then((res:any)=>{
        this.tabbarOptions = res.data||[]
        this.activeTab = res.activeTab||0
      })
      
    },
    created() {
      this.active = 0
    },
    methods:{
      onSearch(tab:any){
        
        this.datas[tab]=[]
          this.page[tab]=0
          this.finished[tab]=false
        
          this.loaddata(tab)
      },
      viewHistory(e:any){
        var q = {invoicingid:e.id}
        this.$router.push({name:'kaipiaoxitong_view',query:q})
      },
      view(e:any){
 
        this.$router.push({name:'kaipiaoxitong_view',query:{invoicingid:e.id,thirdNo:e.thirdNo}})
      },

      async loaddata(index:number){
        
        this.loading[index] = true;
        var current = this.page[index]+1
        var res:any = null
        var par:any = {pageSize:10,current}
        if (this.keyword[index]){
          par.keyword = this.keyword[index]
        }
        switch (index) {
          case 0:
            res = await approvallist(par)
            break;
          case 1:
 
            par.currentState = this.currentState
            res = await getlist(par)
            break
          case 2:
            res = await history(par)
            if (res.data){
              res.data = res.data.map((e:any)=>{
                if(e) e.title = e.userName+'的审批申请'
                return e
              })
            }
            break
          case 3://待开票
            par.currentState = 2
            res = await getlist(par)
            break
          case 4://待作废
            par.currentState = InvoicingStatesEnum.WAITFORDELETE
            res = await getlist(par)
            break
          default:
            break;
        }
        console.log('查询参数：',par)
        this.loading[index]=false
        if (res){
          
          // 数据全部加载完成
          if (res.data.length == 0) {
            this.finished[index] = true;
          }else {
            this.datas[index].push(...res.data)
            this.page[index] = current
          }
          
          console.log(this.finished)
        }
  
       

        
        
      }
    }
  }
</script>

<style   lang="css" src="@/views/financeCommon.css">

  
</style>
