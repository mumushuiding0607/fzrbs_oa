<template>
  <ActionSheet id="reporter" v-model:show="visible" title="用户列表" show-cancel-button style="height: 50vh;">
    <Field   placeholder="请输入用户名" @update:model-value="onSearch" />
    <Cell v-for="item in options" title-style="flex:auto!important;" :key="item.userid" :title="item.username+'【'+item.remark+'】'" @click="onSelected(item)" :value="item.statename||''" :value-class="valueClass[item.state]"/>
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,Field,Cell} from 'vant';
import { getreporters } from '../api';


  export default {
    components: {
      ActionSheet,Field,Cell
    },
    props: ['id','show','parameter'],
    data () {
      return {
        visible:false,
        selectedValue:'',
        options:<any>[],
        valueClass:['ready','tasking','leave']
        
      }
    },
    watch:{
      show(val){
        this.visible = val
      },
      visible(val){
        this.$emit('update:show',val)
      },
      
    },
    mounted() {
      this.onSearch('')
    },
    created() {

    },
    methods:{
      onSelected(val:any){
        
        if (val){
          console.log(val)
          this.$emit('update:value',{name:val.username,userid:val.userid,avatar:val.avatar})
          this.visible=false
        }
      },
      // 搜索方法
      async onSearch (query:any) {

        query = query.trim()

        try {
          var temp:any = {keyword:query,parameter:this.parameter}
          if (!query) delete temp.query
          if (!this.parameter || !this.parameter.begin||!this.parameter.end) delete temp.parameter
          getreporters(temp).then((res:any)=>{
            
            this.options = res||[]
          })
    
          
        } catch (error) {
          console.error('远程搜索失败:', error);
        }
      },

    }
  }
</script>
<style>
  /* #reporter .van-cell__title, .van-cell__value {
    flex:auto!important;
} */
  .ready{
    color: green!important;
  }
  .tasking{
    color: red;
  }
  .leave{
    color: black;
  
  }
  
</style>
