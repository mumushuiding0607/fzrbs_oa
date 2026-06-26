<template>
  <ActionSheet v-model:show="visible" title="合同列表" show-cancel-button style="height: 50vh;">
    <Field   placeholder="合同名称" @update:model-value="onSearch"/>
    <Cell v-for="item in options" :key="item.id" :title="item.title" @click="onSelected(item)"/>
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,Field,Cell} from 'vant';
import { getcontracts } from '../invoice';

  export default {
    components: {
      ActionSheet,Field,Cell
    },
    props: ['show'],
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
    },
    created() {

    },
    methods:{
      onSelected(val:any){
 
        if (val){
       
          this.$emit('update:value',val)
          this.visible=false
          this.$emit('update:fileurls',val.fileurls)
        }
      },
      // 搜索方法
      async onSearch (query:any) {
;
        if (!query.trim()) {
          // 如果输入为空，清空选项
          this.options = [];
          return;
        }

        try {
          getcontracts({keyword:query}).then((res:any)=>{
              this.options = res||[]
            })
          
    
          
        } catch (error) {
          console.error('远程搜索失败:', error);
        }
      },

    }
  }
</script>
