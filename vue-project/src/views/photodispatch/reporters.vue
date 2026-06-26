
<template>
  <div class='page'>
    <Tabs class="mytab" style="margin-left: 0;padding-left: 0;"  v-model:active="active" type="card"   >
      
    <Tab   title="记者列表">
      <div class="searchBox">
        <Field
          v-model="keyword[0]"
          size="normal"
          center
          clearable
          style="padding:3px 5px 1px 15px;"
          placeholder="请输入关键字搜索"
        >
        </Field>
        <span class="btn"  @click="onSearch(0)">搜索</span>
        <span class="btn" style="background-color: var(--van-danger-color);" @click="showAddReporter=true">新增</span>
      </div>
      <List
        :loading="loading[0]"
        :finished="finished[0]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(0)"
      >
        <div v-for="item in datas[0]" :key="item.id" :title="item" class="listitem" >

          <Useritem  :data="item" ></Useritem>
        </div>
          
      </List>
    </Tab>
    <Tab   title="记者去向表">
      <div class="searchBox">
        <Field
              v-model="keyword2"
              readonly
              required
              name="date"
              label="日期"
              placeholder="点击选择日期"
              
              @click="showDate=true"
              :rules="[{ required: true, message: '不能为空' }]"
            />
        <span class="btn" @click="getReporterst()">搜索</span>
      </div>

      <List
        :loading="loading2"
        :finished="finished2"
        finished-text="没有更多了"
        :key="rkey"
        offset="10"
        @load="getReporterst()"
      >
        <div v-for="(item,index) in reportersts.await" class="listitem" >

          <Cell  :title="reportersts.peopleName[item]+'（'+reportersts.peopleRemark[item]+'）'" label="空闲" />
        </div>
        <div v-for="(item,index) in reportersts.drawOut" class="listitem" >

          <Cell  :title="reportersts.peopleName[item]+'（'+reportersts.peopleRemark[item]+'）'" label="任务中" :value="reportersts.drawOutTimes[item]?reportersts.drawOutTimes[item].join('，'):''" />
        </div>
        <div v-for="(item,index) in reportersts.leave" class="listitem" >

          <Cell  :title="reportersts.peopleName[item]+'（'+reportersts.peopleRemark[item]+'）'" label="请假" />
        </div>
          
      </List>
    </Tab>



    
    
  </Tabs>

  
  <tabbar :options="tabbarOptions" :active="activeTab"/>
  <DatePicker_Dialog :show.sync="showDate"  @update:show="(val:any)=>showDate=val"  @update:label="(val:any)=>keyword2=val"/>
  <AddReporter_Dialog :show.sync="showAddReporter"  @update:show="(val:any)=>showAddReporter=val" @confirm="addReporter" />
  </div>
</template>
<script  lang="ts">



import { List,Cell,Image,Tabs,Tab,Search,Field,showDialog } from 'vant';
import Button from 'vant/lib/button';
import tabbar from '@/components/bottomtabbar.vue'
import { useUserStore } from '@/stores';

import { gettabs, reporterlist, reportst, savereporter} from './api';
import { StatesEnum } from './config';
import Useritem from './components/useritem.vue';
import DatePicker_Dialog from '../press/components/DatePicker_Dialog.vue';
import AddReporter_Dialog from './components/AddReporter_Dialog.vue';
const cacheStore = useUserStore()
  export default {
    components: {
      Button,Search,DatePicker_Dialog,AddReporter_Dialog,showDialog,
      Tabs,Tab,List,Cell,Image,Useritem,Field,tabbar
    },
    metaInfo:{
      title: '我的派工'
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
        loading2:false,
        finished2:false,
        keyword2:'',
        reportersts:<any>{},
        showDate: false,
        showAddReporter:false,
        rkey:1

      }
    },
    watch:{
      active(val){
 
        var user = cacheStore.userInfo
        user.invoiceTab=val
        cacheStore.setUserInfo(user)
      }
    },
    
    mounted() {
    

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
      getReporterst(){
        this.rkey++
        this.loading2 = true
        reportst({keyword:this.keyword2}).then((res:any)=>{ 
          this.loading2 = false
          this.reportersts = res||{}
          this.finished2 = true
        })  
      },
      addReporter(obj:any){
        
        savereporter({obj}).then((res:any)=>{ 
          if (res.errorMessage){
            showDialog({'message':res.errorMessage})
          }else{
            this.reportersts = {}
            this.finished2 = false
            this.getReporterst()
            showDialog({'message':'操作成功'})
          }
        })

      },

      async loaddata(index:number){
        
        console.log('index:',index)
        this.loading[index] = true;
        var current = this.page[index]+1
        var res:any = null
        var par:any = {pageSize:20,current}
        if (this.keyword[index]){
          par.keyword = this.keyword[index]
        }
        
        switch (index) {
          case 0:
            res = await reporterlist(par)
            break;
          case 1:
            
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

<style   lang="css" src="@/views/financeCommon.css">

  
</style>