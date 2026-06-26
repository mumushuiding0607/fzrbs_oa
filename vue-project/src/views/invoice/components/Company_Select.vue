<template>
  <ActionSheet v-model:show="visible" title="公司列表" show-cancel-button style="height: 50vh;">
    <Field   placeholder="输入公司名称" @update:model-value="onSearch"/>
    <Cell v-for="item in options" :key="item.id" :title="item.company" @click="onSelected(item)"/>
    <div v-if="options.length==0&&query" @click="addNewCompany()" style="color: #1989fa;padding: 10px;">
      未找到相关的公司。点击新增
    </div>
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,Field,Cell,showConfirmDialog,showDialog} from 'vant';
import { getcompany, getcontracts, savecompany } from '../invoice';

  export default {
    components: {
      ActionSheet,Field,Cell
    },
    // preloadInvoicingPartb 专门用于开票申请，当填写开票信息时，点击弹出时默认显示经常使用的开票单位
    props: ['show','preloadInvoicingPartb','preloadInvoicingParta'],
    data () {
      return {
        visible:false,
        selectedValue:'',
        options:<any>[],
        query:''
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
      if (this.preloadInvoicingPartb){
        getcompany({preloadInvoicingPartb:1,company:''}).then((res:any)=>{
            
            this.options = res||[]
            
          })
      }else if (this.preloadInvoicingParta){
        getcompany({preloadInvoicingParta:1,company:''}).then((res:any)=>{
            
            this.options = res||[]
            
          })
      }
    },
    created() {

    },
    methods:{
      addNewCompany(){
        showConfirmDialog({
            title: '确定要新增【'+this.query+'】吗？',
          })
            .then(() => {
              console.log(this.query)
              savecompany({obj:{company:this.query}}).then((res:any)=>{
                if (res.errorMessage){
                  showDialog({message:res.errorMessage})
                }else{
                  showDialog({message:'公司新增成功'})
                  this.query = ''
                  this.$emit('update:value',res.data.id)
                  this.$emit('update:label',res.data.company)
                  this.visible=false
                }
              })
            })
            .catch(() => {
            });
      },
      onSelected(val:any){
 
        if (val){
          this.$emit('update:value',val.id)
          this.$emit('update:label',val.company)
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
        this.query = query.trim()
        try {
          getcompany({company:query}).then((res:any)=>{
            
            this.options = res||[]
            
          })
    
          
        } catch (error) {
          console.error('远程搜索失败:', error);
        }
      },

    }
  }
</script>
