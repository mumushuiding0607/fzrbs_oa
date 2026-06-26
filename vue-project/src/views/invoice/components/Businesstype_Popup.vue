<template>
  <Popup v-model:show="visible" round position="bottom">
    <Cascader
      title="请选择业务类型"
      :options="options"
      @close="visible = false"
      :multiple="false"
      @finish="onSelected"
    />
</Popup>
</template>
<script  lang="ts">
import { Popup,Cascader} from 'vant';
import { invoicetypes } from '../invoice';


  export default {
    components: {
      Popup,Cascader
    },
    props: ['show','initialValue','type'],
    data () {
      return {
        visible:false,
        cascaderValue:'',
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

          
          
        }
      }
      
    },
    mounted() {
      console.log('mounted')
      this.onSearch('')
    },
    created() {
      console.log('created')
    },
    methods:{
      onSelected(val:any){
        console.log('onselect:',val)
        if (val){
          var len = val.selectedOptions.length

          this.$emit('update:value',val.selectedOptions[len-1].text)
          this.visible=false
        }
      },
      // 搜索方法
      async onSearch (query:any) {


        try {
          invoicetypes({keyword:query,type:this.type}).then((res:any)=>{
            
            this.options = res||[]
          })
    
          
        } catch (error) {
          console.error('远程搜索失败:', error);
        }
      },

    }
  }
</script>
