
<template>
  <div class='page'>
    <Tabs class="mytab"  style="margin-left: 0;padding-left: 0;"  v-model:active="active" type="card"   >
    
    <Tab   title="我的上传">

      <div class="searchBox">
        <Field

          required
          placeholder="日期"
          v-model="start[0]"
          right-icon="calendar-o"
          @click-right-icon="showStart0=true"
         
        />
        
        <span class="btn" @click="onSearch(0)">刷新</span>

      </div>
      <List
        :loading="loading[0]"
        :finished="finished[0]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(0)"
      >
        <div v-for="(item,index) in datas[0]" :key="item.id" :title="item" class="listitem" >

          <listitem @click="view(item)"  :data="item" :edit="false" :checkable="true" :check.sync="item.check"  @update:value="(val:any)=>item.scores=val"  @check="(val:any)=>{
    
            checkChange(val,index)
          }"></listitem>
        </div>
          
      </List>

    </Tab>
    <Tab   title="我的打分">
      <div class="searchBox">
        <Field
          v-model="keyword[1]"
          size="normal"
          center
          clearable
          style="padding:3px 5px 1px 15px;"
          placeholder="请输入关键字搜索"
        >
        </Field>
        <span class="btn" @click="onSearch(1)">刷新</span>
      </div>

      <List
        :loading="loading[1]"
        :finished="finished[1]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(1)"
      >
        <div v-for="item in datas[1]" :key="item.date" :title="item" class="listitem" @click="view(item)">

          <listitem  :data="item" ></listitem>
        </div>
          
      </List>
    </Tab>

    <Tab   title="已打分">
      <div class="searchBox">
        <Field

          required
          placeholder="开始日期"
          v-model="start[2]"
          right-icon="calendar-o"
          @click-right-icon="showStart=true"
         
        />
        <Field
          required
          placeholder="结束日期"
          v-model="end[2]"
          right-icon="calendar-o"
          @click-right-icon="showEnd=true"
         
        /><Field

          required
          placeholder="选择用户"
     
          v-model="user.name"
          right-icon="user-o"
          @click-right-icon="showUser=true"
          

         
        />
      </div>
      <div class="searchBox">
        
        <Field
          v-model="keyword[2]"
          size="normal"
          center
          clearable
          style="flex-grow: 1;width: 150px;;padding:3px 5px 1px 15px;"
          placeholder="关键字搜索"
        >
        </Field>
        <div style="display: flex;flex-direction: row;"><span class="btn" @click="onSearch(2)">刷新</span>
        <span class="btn" @click="onDownload(2)" style="width: 80px;background-color: gray;">导出分数</span></div>
      </div>

      <List
        :loading="loading[2]"
        :finished="finished[2]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(2)"
      >
        <div v-for="item in datas[2]" :key="item.id" :title="item" class="listitem" @click="view(item)">

          <listitem  :data="item" ></listitem>
        </div>
          
      </List>
    </Tab>

    
    
  </Tabs>
  <div v-if="active==0" class="cell"  :style="`position: fixed;max-width: 480px!important;margin-top: 20px;bottom:${app?'var(--van-action-bar-height)':0} ;`"  >
    <div class="searchBox"  style="width:100%;display: flex;justify-content: flex-end;align-items: flex-end;">
      
      <span  class="btn" @click="agreeAll()" style="background: green;color: white;border: 1px solid green;">提交</span>
      <span  class="btn" @click="delAll()" style="background: white;color: red;border: 1px solid lightcoral;">删除</span>
    </div>
         
 

       
         
       </div>

  <UserSelect :show.sync="showUser0"  @update:show="(val:any)=>showUser0=val" @update:value="(val:any)=>{
            datas[0][rowIndex].scores.push({userid:val.userid,name:val.name,score:0})
            datas[0][rowIndex].checked=false
          }"/>
  <UserSelect :show.sync="showUser"  @update:show="(val:any)=>showUser=val" @update:value="(val:any)=>user=val"/>
  <DatePickerDialog :show.sync="showStart0" @change="(val:any)=>{
    start[0]=val

  }"  @update:show="(val:any)=>showStart0=val" />
  <DatePickerDialog :show.sync="showStart"  @update:show="(val:any)=>showStart=val" @update:label="(val:any)=>start[2]=val" />
  <DatePickerDialog :show.sync="showEnd"  @update:show="(val:any)=>showEnd=val" @update:label="(val:any)=>end[2]=val" />
  <tabbar :options="tabbarOptions" :active="activeTab"/>
  </div>
</template>
<script  lang="ts">

import listitem from '@/views/manuscriptscoring/components/listitem_catagory.vue';

import { List,Cell,Image,Tabs,Tab,Search,Field, showConfirmDialog, showLoadingToast, showDialog, closeToast, showToast } from 'vant';
import Button from 'vant/lib/button';
import tabbar from '@/components/bottomtabbar.vue'
import { useUserStore } from '@/stores';

import { commit, commitbycatogory, commitscore, delbycatogory, delinfo, exportExcel, finishlist, getlist, getnotifydata, gettabs, history, inglist, listcatogory } from './api';
import DatePickerDialog from '../attendance/components/DatePickerDialog.vue';
import UserSelect from '../invoice/components/UserSelect.vue';
import { downloadAsXlSX } from '../attendance/utils';
import { isApp } from './config';

const cacheStore = useUserStore()
  export default {
    components: {
      Button,Search,DatePickerDialog,UserSelect,
      Tabs,Tab,List,Cell,Image,listitem,Field,tabbar
    },
    metaInfo:{
      title: '记者打分'
    },
    data () {
      return {
        query:<any>this.$route.query,
        active:0,
        app:isApp,
        refreshing:false,
        datas:<any>[[],[],[],[],[],[]],
        page:[0,0,0,0,0,0],
        loading:[false,false,false,false,false,false],
        finished:[false,false,false,false,false,false],
        keyword:['','','','','',''],
        start:['','','','','',''],
        end:['','','','','',''],
        user:<any>{},
        tabbarOptions:[],
        activeTab:0,
        select:false,
        showStart:false,
        showStart0:false,
        showEnd:false,
        showUser:false,
        rowIndex:-1,
        showUser0:false
      }
    },
    watch:{
      active(val){
 
        var user = cacheStore.userInfo
        user.manuscriptscoringMylistTab=val
        cacheStore.setUserInfo(user)
        this.query.tab=val
        console.log('active:',val)
      }
    },
    
    mounted() {
      var tab = cacheStore.userInfo?.manuscriptscoringMylistTab
      if(tab) this.active = tab
      if(this.query.tab!=null) this.active = parseInt(this.query.tab)
      console.log('isapp:',this.app)
      
      gettabs({}).then((res:any)=>{
        this.tabbarOptions = res.data||[]
        this.activeTab = res.activeTab||0
      })
      
    },
    created() {
      this.active = 0
    },
    methods:{
      checkChange(val:any,index:any){
       
        this.rowIndex = val? index:-1
        
        this.datas[0][index].check = val
      },
      selectAll(){
        this.datas[0]=this.datas[0].map((e:any)=>{
          e.check = !this.select
          return e
        })
        this.select = !this.select
      },
      add(){
        if (this.rowIndex==-1){
          showToast('请选择需要添加记者的行！')
          return
        }
        this.showUser0=true
      },
      agreeAll(){
        var datas = this.datas[0].filter((e:any)=>e.check)
        
        if (!datas){
          showDialog({'message':'请先打分再提交'})
          return
        }
        showConfirmDialog({title:'确定提交吗？'}).then(()=>{
          showLoadingToast({
            message:'提交中...',
            duration:0
          })
          
          commitbycatogory({data:datas}).then((res:any)=>{
            closeToast()
            if (res.errorMessage){
              showDialog({'message':res.errorMessage,'allowHtml':true})
            }else{
              this.select = false
              this.datas[0]=[]
              this.page[0]=0
              this.finished[0]=false
              this.loaddata(0)
            }
            
            
          })
        })
      },
      delAll(){
        
        var datas = this.datas[0].filter((e:any)=>e.check)
        if (datas.length==0){
          showDialog({'message':'请选择要删除的记录'})
          return
        }
        console.log('delAll:',datas)
        showConfirmDialog({title:'确定删除吗？'}).then(()=>{
          showLoadingToast({
            message:'删除中...',
            duration:0
          })
          delbycatogory({datas}).then((res:any)=>{
            closeToast()
            if (res.errorMessage){
              showDialog({'message':res.errorMessage,'allowHtml':true})
            }else{
              this.datas[0]=[]
              this.page[0]=0
              this.finished[0]=false
              this.loaddata(0)
              this.select = false
            }
            
          })

        })
      },
      onDownload(tab:any){
        const index = tab
        
        var res:any = null
        var par:any = {current:1}
        if (this.keyword[index]){
          par.keyword = this.keyword[index]
        }
        if (this.start[index]){
          par.startdate = this.start[index]
        }
        if (this.end[index]){
          par.enddate = this.end[index]
        }
        if (this.user&&this.user.name){
          par.userid = this.user.userid
        }
        par.state = index
   
        exportExcel(par).then((res:any)=>{
          closeToast()
          if (res.errorMessage) {
            showDialog({'message':res.errorMessage})
          } else {
         
            downloadAsXlSX(res.data, '稿件打分'+new Date().toLocaleString())
          }
        })
    
      },
      onSearch(tab:any){
        
        this.datas[tab]=[]
        this.page[tab]=0
        this.finished[tab]=false
     
        this.loaddata(tab)
      },
      viewHistory(e:any){
        var q = {thirdNo:e.thirdNo}
        this.$router.push({name:'manuscriptscoring_view',query:q})
      },
      view(e:any){
        this.$router.push({name:'manuscriptscoring_viewlist',query:{state:e.state,date:e.date}})
      },

      async loaddata(index:number){
        
    
        this.loading[index] = true;
        var current = this.page[index]+1
        console.log('loaddata curent:',current)
        var res:any = null
        var par:any = {pageSize:10,current}
        if (this.keyword[index]){
          par.keyword = this.keyword[index]
        }
        if (this.start[index]){
          par.startdate = this.start[index]
        }
        if (this.end[index]){
          par.enddate = this.end[index]
        }
        if (this.user&&this.user.name){
          par.userid = this.user.userid
        }
        
        par.state = index
    
        
        res = await listcatogory(par)
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
