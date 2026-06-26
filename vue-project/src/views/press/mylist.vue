
<template>
  <div class="page">
    <Tabs class="mytab"  style="margin-left: 0;padding-left: 0;"  v-model:active="active" type="card"   >
      
    <Tab   title="审批中">
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


    <Tab   title="全部">
 
      <div style="display: flex;flex-direction: row;">
        <Field
          v-model="keyword[3]"
          size="normal"
          center
          clearable
          style="padding:5px 5px 5px 15px;"
          placeholder="请输入关键字搜索"
        >
        </Field>
        
        <span style="background-color: #1989fa;width: calc(var(--van-cell-font-size)*4);color: white;text-align: center;padding: 5px;margin: 5px;
          font-size: var(--van-cell-font-size);border-radius: 10%;" @click="onSearch(3)">搜索</span>
      </div>
      <List
        :loading="loading[3]"
        :finished="finished[3]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(3)"
      >
        <div v-for="item in datas[3]" :key="item.id" :title="item" class="listitem" @click="view(item)">

          <listitem  :data="item" ></listitem>
        </div>
          
      </List>
    </Tab>

    
    
  </Tabs>

  
  <tabbar :options="tabbarOptions" :active="activeTab"/>
  </div>
</template>
<script  lang="ts">

import listitem from '@/views/press/components/listitem.vue';

import { List,Cell,Image,Tabs,Tab,Search,Field } from 'vant';
import Button from 'vant/lib/button';
import tabbar from '@/components/bottomtabbar.vue'
import { useUserStore } from '@/stores';

import { finishlist, getlist, getnotifydata, gettabs, history, inglist } from './press';
const cacheStore = useUserStore()
  export default {
    components: {
      Button,Search,
      Tabs,Tab,List,Cell,Image,listitem,Field,tabbar
    },
    data () {
      return {
        query:<any>this.$route.query,
        active:0,
        refreshing:false,
        datas:<any>[[],[],[],[],[]],
        page:[0,0,0,0,0],
        loading:[false,false,false,false,false],
        finished:[false,false,false,false,false],
        keyword:['','','','',''],
        tabbarOptions:[],
        activeTab:0
      }
    },
    watch:{
      active(val){
 
        var user = cacheStore.userInfo
        user.pressMylistTab=val
        cacheStore.setUserInfo(user)
      }
    },
    
    mounted() {
    
      var tab = cacheStore.userInfo?.pressMylistTab
      if(tab) this.active = tab
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
        this.$router.push({name:'press_view',query:q})
      },
      view(e:any){
 
        this.$router.push({name:'press_view',query:{thirdNo:e.thirdNo}})
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
          // 待审批
          case 0:
            par.status=1
            res = await getlist(par)
            break;
            // 全部
          case 3:
            
            res = await getlist(par)
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
            this.datas[index].push(...res.data)
            this.page[index] = current
          }
          
          console.log(this.finished)
        }
  
       

        
        
      }
    }
  }
</script>
<style   lang="css" src="@/views/financeCommon.css"></style>