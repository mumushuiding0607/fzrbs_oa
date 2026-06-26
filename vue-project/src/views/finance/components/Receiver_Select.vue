<template>
  <ActionSheet v-model:show="visible" title="收款单位" show-cancel-button style="height: 50vh;">
    <Field   placeholder="输入收款单位" @update:model-value="onSearch"/>
    <Cell v-for="item in options" :key="item.id" :title="item.temp" @click="onSelected(item)"/>
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,Field,Cell} from 'vant';
import { getbankaccount, isinsidecompany } from '@/views/finance/finance';
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
      this.onSearch('')
    },
    created() {

    },
    methods:{
      onSelected(val:any){
 
        if (val){
          this.$emit('update:value',val.id)
          this.$emit('update:label',val.receiver)
          this.visible=false
          this.$emit('change',val)
          // 判断当前收款单位是否是内部部门
          isinsidecompany({keyword:val.receiver}).then((res:any)=>{
            if (res){
              this.$emit('update:inside',res.data)
            }
          })
        }
      },
      // 搜索方法
      async onSearch (query:any) {



        try {
          getbankaccount({keyword:query}).then((res:any)=>{
            
            this.options = res||[]
            if (this.options.length==0){
              this.$emit('update:label',query)
            }
          })
    
          
        } catch (error) {
          console.error('远程搜索失败:', error);
        }
      },

    }
  }
</script>
