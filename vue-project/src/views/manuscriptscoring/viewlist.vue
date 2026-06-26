
<template>
  <div class='page'>

      <List
        :loading="loading[0]"
        :finished="finished[0]"
        finished-text="没有更多了"
        offset="10"
        @load="loaddata(0)"
        :key="refreshkey"
      >
        <div v-for="(item,index) in datas[0]"  :title="item" class="listitem" >

          <listitem   :data="item" :edit="query?.state==1||query?.state==2" :checkable="query?.state!=2" :check.sync="item.check"  @update:value="(val:any)=>item.scores=val"  @check="(val:any)=>{
    
            checkChange(val,item.id)
          }"></listitem>
        </div>
          
      </List>
      <div style="height: 150px;"></div>


  <div  class="cell"  :style="`background:transparent;z-index: 1000;position: fixed;max-width: 480px!important;margin-top: 20px;bottom: ${app?'var(--van-action-bar-height)':0};`"  >
    <div class="searchBox"  style="width:100%;display: flex;justify-content: flex-end;align-items: flex-end;background:transparent;">
      
      <span v-if="query?.state!=2" class="btn" @click="agreeAll()" style="background: green;color: white;border: 1px solid green;">提交</span>
      <button  class="btn" v-if="query?.state!=0" @click="save()" style="background: #1989FA;color: white;border: 1px solid #1989FA;">保存</button>
      <button v-if="query?.state!=2"  class="btn" @click="delAll()" style="background: white;color: red;border: 1px solid lightcoral;">删除</button>
      <button v-if="query?.state!=2" class="btn" @click="add()" style="width:calc(var(--van-cell-font-size)* 5);background: white;color: gray;border: 1px solid gray;">添加记者</button>
    </div>
         
 

       
         
       </div>


  <UserSelect :show.sync="showUser0"  @update:show="(val:any)=>showUser0=val" @update:value="(val:any)=>{
            datas[0][rowIndex].scores.push({userid:val.userid,name:val.name,score:0})
            datas[0][rowIndex].checked=false
          }"/>
  <DatePickerDialog :show.sync="showStart0" @change="(val:any)=>{
    start[0]=val

  }"  @update:show="(val:any)=>showStart0=val" />
  <DatePickerDialog :show.sync="showStart"  @update:show="(val:any)=>showStart=val" @update:label="(val:any)=>start[2]=val" />
  <DatePickerDialog :show.sync="showEnd"  @update:show="(val:any)=>showEnd=val" @update:label="(val:any)=>end[2]=val" />
  <tabbar :options="tabbarOptions" :active="activeTab"/>
  </div>
</template>
<script  lang="ts">

import listitem from '@/views/manuscriptscoring/components/listitem.vue';

import { List,Cell,Image,Tabs,Tab,Search,Field, showConfirmDialog, showLoadingToast, showDialog, closeToast, showToast } from 'vant';
import Button from 'vant/lib/button';
import tabbar from '@/components/bottomtabbar.vue'
import { useUserStore } from '@/stores';

import { commit, commitapply, commitscore, delinfo, exportExcel, finishlist, getlist, getnotifydata, gettabs, history, inglist, listcatogory } from './api';
import DatePickerDialog from '../attendance/components/DatePickerDialog.vue';
import UserSelect from '../invoice/components/UserSelect.vue';
import { appEnv } from '@/utils/common';
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
        refreshkey:100,
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
      }
    },
    
    mounted() {
      var tab = cacheStore.userInfo?.manuscriptscoringMylistTab
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
      checkChange(val:any,id:any){
        if (!val) {
          this.rowIndex = -1;
          return
        }
        const list = this.datas[0];
        this.rowIndex = list.findIndex((item: any) => item.id === id);

        list.forEach((item: any) => {
          item.check = item.id === id;
        });
      },
      selectAll(){
        this.datas[0]=this.datas[0].map((e:any)=>{
          e.check = !this.select
          return e
        })
        this.select = !this.select
      },
      add(){
        console.log('add:',this.rowIndex)
        if (this.rowIndex==-1){
          
          showDialog({'message':'请选择需要添加记者的行！'})
          return
        }
        this.showUser0=true
      },
      async save (){
        showLoadingToast({
            message:'保存中...',
            duration:0
          })
        var res:any = commitapply({data:this.datas[0]})
        closeToast()
          if (res.errorMessage){
              showDialog({'message':res.errorMessage,'allowHtml':true})
          }else{
            showDialog({'message':'保存成功！'})
          }
      },
      async agreeAll(){
 
        showConfirmDialog({title:'确定提交吗？'}).then(()=>{
          showLoadingToast({
            message:'提交中...',
            duration:0
          })
          var res:any = {}
          var datas:any = []
          switch (''+this.query.state) {
            case '0':
              res = commitapply({data:this.datas[0]})
              break;
            case '1':
              datas = this.datas[0].filter((item: any) =>
  item.scores && Array.isArray(item.scores) &&
  item.scores.every((s: any) => s.score != null && s.score !== ''))
              res = commit({data:datas})
              break;
          
            default:
              break;
          }
          closeToast()
          if (res.errorMessage){
              showDialog({'message':res.errorMessage,'allowHtml':true})
          }else{

            if (datas.length>0){
              this.datas[0] = this.datas[0].filter((item: any) => datas.findIndex((e: any) => e.id === item.id) === -1)
            }
          }
          
          
        })
      },
      delAll(){
        console.log('delAll')
        var idsarr = this.datas[0].filter((e:any)=>e.check).map((e:any)=>e.id)
        if (!idsarr){
          showDialog({'message':'请选择要提交的记录'})
          return
        }
        showConfirmDialog({title:'确定删除吗？'}).then(()=>{
          showLoadingToast({
            message:'删除中...',
            duration:0
          })
          delinfo({ids:idsarr.join(',')}).then((res:any)=>{
            closeToast()
            if (res.errorMessage){
              showDialog({'message':res.errorMessage,'allowHtml':true})
            }else{
              if (idsarr.length>0){
                this.datas[0] = this.datas[0].filter((item: any) => idsarr.findIndex((e: any) => e === item.id) === -1)
              }
            }
            
          })

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
 
        this.$router.push({name:'manuscriptscoring_view',query:{thirdNo:e.thirdNo,id:e.id}})
      },

      async loaddata(index:number){
        
    
        this.loading[index] = true;
        var current = this.page[index]+1
        console.log('loaddata curent:',current)
        var res:any = null
        var par:any = {pageSize:10,current,...this.query}
        res = await getlist(par)
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
