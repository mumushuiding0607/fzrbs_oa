<template>
  <ActionSheet v-model:show="visible" title="用户列表" show-cancel-button style="height: 50vh;">
    <Field   placeholder="请输入用户名" @update:model-value="onSearch" clearable/>
    <Cell v-for="item in options" :key="item.userid" :title="item.name" @click="onSelected(item)"/>
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,Field,Cell} from 'vant';
import { getusers } from '../invoice';

  export default {
    components: {
      ActionSheet,Field,Cell
    },
    props: ['id','show'],
    data () {
      return {
        visible:false,
        selectedValue:'',
        options:<any>[],
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
          this.$emit('update:value',{name:val.name,userid:val.userid,avatar:val.avatar,mobile:val.mobile})
        this.visible=false
        }
      },
      // 搜索方法
      async onSearch (query:any) {
    
        query=query.trim()

        try {
          getusers({keyword:query}).then((res:any)=>{
            
            this.options = res||[]
          })
    
          
        } catch (error) {
          console.error('远程搜索失败:', error);
        }
      },

    }
  }
</script>
