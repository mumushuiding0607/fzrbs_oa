<template>
  <div v-if="datas&&datas.length>0" >
    <div  class="cell title">催款进度:</div>
     <Steps direction="vertical" :active="0">
      <Step v-for="(item,index) in datas||[]" >
      
        <div style="padding: 0;text-align: left;">
          <div class="cell" style="align-items: center;" @click="edit(item)">
            <Image  width="30" height="30" fit="cover" style="margin-right: 10px;" :round="true" :src="item?.avatar"/>
            {{item.creatorname+'  '+(item.date||item.inserttime).substring(0,10)+" "+(item.urgeresultname||'')+" "+(item.amount?('回款：'+item.amount):'')}}
            <Icon v-if="user.userId==item.creator" name="delete" size="25" style="margin-left: 10px;" @click.stop="del(item)" />
          </div>
          <div style="padding:0 calc(var(--van-cell-vertical-padding)* var(--Big)* 3.3)"><span :style="{color:'gray',}" >{{item.note}}</span></div> 
          <div style="padding:0 calc(var(--van-cell-vertical-padding)* var(--Big)* 2)">
            <Filescard :key="fkey" v-if="item?.fileurls" :urls="item?.fileurls" :preview="false" :showIcon="false" />
          </div>
          
        </div>
    
      </Step>

    </Steps>

    <AddLog :show.sync="showAddlog"  @update:show="(val:any)=>showAddlog=val" :data="curItem" @change="handleChange" />
    
  </div>

  
  
</template>
<script  lang="ts">

import UserSelect from '@/views/invoice/components/UserSelect.vue';
import { Steps,Step,Badge,Image,Field, Button, showDialog,Icon,ActionSheet, showToast, showConfirmDialog } from 'vant';

import { useUserStore } from '@/stores';
import { getdata } from '@/views/finance/finance';
import { delurgelog, geturgelogs } from '../api';
import Filescard from '@/views/budget/components/filescard.vue';
import AddLog from '../addLog.vue';
import { storeToRefs } from 'pinia';
const cacheStore = useUserStore()
const { loginStatus, userInfo } = storeToRefs(useUserStore());
  export default {
    components: {
      ActionSheet,Steps,Step,Badge,Image,UserSelect,Field,Button,Icon,Filescard,AddLog
    },
    props: ['param','edit','refreshkey'],
    data () {
      return {
  
        step:0,
        visible:false,
        datas:<any>[],
        showAddlog:false,
        curItem:<any>{},
        fkey:0,
        user:userInfo.value,
        
      }
    },
    watch:{
      refreshkey(val){
        console.log('Log_Steps refreshkey:',val)
        this.getdata()
      },
      // 监听param对象,deep为true
      param:{
        handler(val, oldVal) { 
          this.getdata()
        },
        deep:true
      }
    },
    mounted() {
      
    },
    created() {

    },
    methods:{
      handleChange(e:any){
        const index = this.datas.findIndex((item:any)=>item.id==e.id)
        this.datas[index] = e
        this.fkey++


      },
      del(e:any){
        showConfirmDialog({ 
          title: '确定【删除】吗？',
        }).then(() => { 
          delurgelog({id:e.id}).then((res:any)=>{
            if (res.errorMessage){
              showDialog({message:res.errorMessage})
            }else{
              showDialog({message:'删除成功'})
              const index = this.datas.findIndex((item:any)=>item.id==e.id)
              // 删除
              this.datas.splice(index,1)
              this.fkey++
            } 
          })
        });
      },
      edit(item:any){
        this.curItem = item
        this.showAddlog = true
      },
      getdata(){
        if (!this.param){
          showToast({message:'param为空'})
          return
        }
        if (!this.param.debturgeid) return
        if (!this.param.contractid){
          return
        }
        geturgelogs({contractid:this.param.contractid,debturgeid:this.param.debturgeid,onlyThisUrge:1}).then((res:any)=>{
          this.datas = res.data
        })
      }


    }
  }
</script>
<style  src="@/views/financeCss.css">
  
</style>
