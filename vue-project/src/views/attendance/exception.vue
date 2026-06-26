
<template>
  <div class="page">
      


      <List
        :loading="loading[0]"
        :finished="finished[0]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(0)"
      >
        <div v-for="(item,index) in datas[0]" :key="item.id" :title="item" class="listitem" >

          <Exceptionitem  :data="item" ></Exceptionitem>
        </div>
          
      </List>
     
      <div style="width: 100%;">
        <span  @click="add" style="border-radius: 2%;width: 90%;margin: 5%;padding:10px ;background-color: #07c160;color: white;display: flex;justify-content: center;">直接填写说明</span>
      </div>

  
  <tabbar :options="tabbarOptions" :active="activeTab"/>
  </div>
</template>
<script  lang="ts">



import { List,Cell,Image,Tabs,Tab,Search,Field,Button } from 'vant';
import tabbar from '@/components/bottomtabbar.vue'
import { useUserStore } from '@/stores';

import { agreeall, exception, finishlist, getlist, getnotifydata, gettabs, history, inglist } from './api';
import Exceptionitem from './components/exceptionitem.vue';
const cacheStore = useUserStore()
  export default {
    components: {
      Button,Search,
      Tabs,Tab,List,Cell,Image,Exceptionitem,Field,tabbar
    },
    data () {
      return {
        query:<any>this.$route.query,
        active:0,
        refreshing:false,
        datas:<any>[[],[]],
        page:[0,0],
        loading:[false,false],
        finished:[false,false],
        keyword:['',''],
        tabbarOptions:[],
        activeTab:0,
        canAgreeAll:false,
        select:false
      }
    },

    
    mounted() {
      

      gettabs({}).then((res:any)=>{
        this.tabbarOptions = res.data||[]

      })
      
    },
    created() {

    },
    methods:{


      view(e:any){
       
        this.$router.push({name:'attendance_view',query:{invoicingid:e.id,thirdNo:e.thirdNo}})
      },
      add(){
        this.$router.push({name:'attendance_add'})
      
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
            res = await exception(par)
           
            break;
        
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

        }
  
       

        
        
      }
    }
  }
</script>

<style   lang="css" src="@/views/financeCommon.css"></style>


