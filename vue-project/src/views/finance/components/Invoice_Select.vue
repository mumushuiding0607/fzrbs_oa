<template>
  <ActionSheet v-model:show="visible" title="发票列表" show-cancel-button style="height: 50vh;">
    <Field   placeholder="开票申请系统：搜索开票单位、开票金额" @update:model-value="onSearch"/>
    <Cell v-for="item in options" :key="item.id" :title="item.invoiceno" :value="'￥'+item.amount" :label="item.partbname" @click="onSelected(item)"/>
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,Field,Cell,showDialog} from 'vant';
import { getinvoice } from '@/views/finance/finance';

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
      getinvoice({keyword:''}).then((res:any)=>{
              this.options = res||[]
              
            })
    },
    created() {

    },
    methods:{
      onSelected(val:any){
 
        if (val){
          this.$emit('update:value',val)
          this.visible=false
          if (val.pdffileurls){
            val.fileurls = val.fileurls?(val.fileurls+','+val.pdffileurls):val.pdffileurls
          }
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
          getinvoice({keyword:query}).then((res:any)=>{
              this.options = res||[]
              
            })
          
    
          
        } catch (error) {
          console.error('远程搜索失败:', error);
        }
      },

    }
  }
</script>
