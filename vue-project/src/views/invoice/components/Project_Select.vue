<template>
  <ActionSheet v-model:show="visible" title="非报项目列表" show-cancel-button style="height: 50vh;">
    <Field   placeholder="非报项目名称或编号" @update:model-value="onSearch"/>
    <Cell v-for="item in options" :key="item.id" :title="item.title" @click="onSelected(item)"/>
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,Field,Cell} from 'vant';
import { getprojectbykeyword } from '../invoice';


  export default {
    components: {
      ActionSheet,Field,Cell
    },
    props: ['show','initialValue'],
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
      async initialValue(val){
        if(val){
          getprojectbykeyword({keyword:val}).then((res:any)=>{
            
            this.options = res||[]
            var id = this.options.map((item:any)=>item.id).join(',')
            var title = this.options.map((item:any)=>item.title).join(',')
            this.$emit('update:value',{id,title})
          })
          
          
        }
      }
      
    },
    mounted() {
    },
    created() {

    },
    methods:{
      onSelected(val:any){
 
        if (val){
          this.$emit('update:value',val)
          this.visible=false
        }
      },
      // 搜索方法
      async onSearch (query:any) {
        if (!query.trim()) {
          // 如果输入为空，清空选项
          this.options = [];
          return;
        }

        try {
          getprojectbykeyword({keyword:query}).then((res:any)=>{
            
            this.options = res||[]
          })
    
          
        } catch (error) {
          console.error('远程搜索失败:', error);
        }
      },

    }
  }
</script>
