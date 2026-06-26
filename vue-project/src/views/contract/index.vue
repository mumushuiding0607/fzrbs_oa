
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
      <div v-for="item in datas[0]" :key="item.id" :title="item" class="listitem" @click="viewDebt(item)">

        <listitem  :data="item"></listitem>
      </div>
        
    </List>

    </Tab>
    <Tab   title="清欠措施">
      <div class="searchBox" >
        <Field
    
          size="normal"
          center
          clearable
          is-link
          style="padding:5px 5px 5px 15px;"
          placeholder="合同名称或编号"
          @click="addContract=true"
        >
        </Field>
        <Contract_Select :show.sync="addContract"  @update:show="(val:any)=>addContract=val" @update:value="(val:any)=>keyword[2]=val?.id"/>
        
        <span class="btn" @click="onSearch(2)">搜索</span>
        
      </div>
      <List
        :loading="loading[2]"
        :finished="finished[2]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(2)"
      >
      <div v-for="item in datas[2]" :key="item.id" :title="item" class="listitem" @click="onAddLog(item)">

        <listitem  :data="item"></listitem>
      </div>
        
    </List>

    </Tab>
    <Tab title="催款中">
      <div class="searchBox" >
        <Field
          v-model="keyword[1]"
          size="normal"
          center
          clearable
          style="padding:5px 5px 5px 15px;"
          placeholder="输入关键字: 合同名称、经办等"
        >
        </Field>
        
        <span class="btn" @click="onSearch(1)">搜索</span>
        
      </div>
      <List
        :loading="loading[1]"
        :finished="finished[1]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(1)"
      >
      <div v-for="item in datas[1]" :key="item.id" :title="item" class="listitem" @click="viewDebt(item)">
        <listitem  :data="item"></listitem>
      </div>
    </List>
    </Tab>

    <Tab   title="逾期">
      <div class="searchBox" >
        <Field
          v-model="keyword[4]"
          size="normal"
          center
          clearable
          style="padding:5px 5px 5px 15px;"
          placeholder="输入关键字: 合同名称、经办等"
        >
        </Field>
        
        <span class="btn" @click="onSearch(4)">搜索</span>
        
      </div>
      <List
        :loading="loading[4]"
        :finished="finished[4]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(4)"
      >
        <div v-for="item in datas[4]" :key="item.id" :title="item" class="listitem" @click="viewDebt(item)">

          <listitem  :data="item"></listitem>
        </div>
          
      </List>
    </Tab>


    
    
  </Tabs>
  <AddLog :show.sync="showAddlog"  @update:show="(val:any)=>showAddlog=val" :data="urgelog" />
  </div>
</template>
<script  lang="ts">

import listitem from './components/listitem.vue';
import { Tabs,Tab,Field } from 'vant';
import { List,Cell,Image } from 'vant';
import Button from 'vant/lib/button';
import { useUserStore } from '@/stores';
import { debtlist, finishlist, inglist, urgelogslist } from './api';
import AddLog from './addLog.vue';
import Contract_Select from '../invoice/components/Contract_Select.vue';
const cacheStore = useUserStore()
  
  export default {
    components: {
      Button,Contract_Select,
      Tabs,Tab,List,Cell,Image,listitem,Field,AddLog
    },
    data () {
      return {
        query:<any>this.$route.query,
        active:0,
        refreshing:false,
        datas:<any>[[],[],[],[],[]],
        keyword:['','','','',''],
        page:[0,0,0,0,0],
        loading:[false,false,false,false,false],
        finished:[false,false,false,false,false],
        showAddlog:false,
        urgelog:false,
        addContract:false,
        
      }
    },
    watch:{
      active(val){
 
        var user = cacheStore.userInfo
        user.contractIndexTab=val
        cacheStore.setUserInfo(user)
      }
    },
    mounted() {
      var tab = cacheStore.userInfo?.contractIndexTab
      if(tab) this.active = tab
      if(this.query.tab!=null) this.active = parseInt(this.query.tab)
    },
    created() {
      this.active = 0
    },
    methods:{
      onAddLog(e:any){
        this.showAddlog = true
        this.urgelog = e
      },
      viewDebt(e:any){
        
        var q = {thirdNo:e.thirdNo,contractid:e.contractid||e.id}
        console.log(q)
        this.$router.push({name:'contract_debturge',query:q})
      },
      onSearch(tab:any){
        
        this.datas[tab]=[]
        this.page[tab]=0
        this.finished[tab]=false
      
        this.loaddata(tab)
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
            res = await inglist(par)
            break;
          case 1:
            par.urgestate=2
            res = await debtlist(par)
            break
          case 2:
            par.type=1//清欠措施
            par.contractids=par.keyword
            res = await urgelogslist(par)
            break;
          case 3:
           
            break
          case 4:
            res = await debtlist(par)
            break
          default:
            break;
        }
        if (res){
          this.loading[index]=false
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
