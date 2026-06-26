<template>
  <ActionSheet v-model:show="visible" title="付款单位" show-cancel-button style="height: 50vh;">
    <Field   placeholder="输入付款单位" @update:model-value="onSearch"/>
    <Cell v-for="item in options" :key="item.id" :title="item.company" @click="onSelected(item)"/>
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,Field,Cell} from 'vant';
import { getpayers, iscrossdept } from '@/views/finance/finance';

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
          this.$emit('update:label',val.company)
          this.visible=false
          if (parseInt(val.ctype)) { // 是否允许跨部门
            // 判断当前付款单位是否涉及跨部门审批
              iscrossdept({payer:val.company}).then((res:any)=>{
                if (res){
                  this.$emit('update:cross',res.data)
                  this.$emit('onCrossChange',res.data)
                }
              })
          }else {
            this.$emit('update:cross',false)
            this.$emit('onCrossChange',false)
          }
        
        }
      },
      // 搜索方法
      async onSearch (query:any) {



        try {
          getpayers({keyword:query}).then((res:any)=>{
            
            this.options = res||[]
          })
    
          
        } catch (error) {
          console.error('远程搜索失败:', error);
        }
      },

    }
  }
</script>
