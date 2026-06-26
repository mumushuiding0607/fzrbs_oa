<template>
  <Popup v-model:show="visible" destroy-on-close position="bottom">
    <Picker
      :columns="columns"
      :placeholder="placeholder"
      @confirm="onConfirm"
      @cancel="onCancel"
    />
  </Popup>

</template>
<script  lang="ts">
  import { Picker,Popup} from 'vant';
  import {  getdictbykeyword } from '../invoice';

  export default {
    components: {
      Picker,Popup
    },
    props: ['type','subtype','placeholder','initialValue','notSelectFirst','order'],
    data () {
      return {
        visible:false,
        selectedValue:<any>[],
        columns:<any>[],
      }
    },

    watch:{
      visible(val){
        console.log('visible:',val)
        
      },
      initialValue(val){
        var index = 0
        if (val){

          index = this.columns.findIndex((e:any)=>e.value==val)

        }
        this.selectedValue = this.columns[index]
        
        this.$emit('update:label',this.columns[index]?.text)
        this.$emit('update:value',this.columns[index]?.value)
        
      }
      
    },
    mounted() {
      this.getDatas()
    },
    created() {

    },
    methods:{
      onCancel(){
        this.visible = false
        this.$emit('update:show',false)

      },
      onConfirm({ selectedValues, selectedOptions }:{selectedValues:any,selectedOptions:any}){
        this.$emit('update:label',selectedOptions[0]?.text)
        this.$emit('update:value',selectedOptions[0]?.value)
        this.selectedValue = selectedValues
        this.visible = false
        this.$emit('update:show',false)
        this.$emit('change',selectedOptions[0])
      },

      onChange(val:any){
        console.log(val)
      },
      // 搜索方法
      async getDatas () {


        try {
          var par:any = {keyword:this.type}
          if (this.order){
            par.order = this.order
          }
          getdictbykeyword(par).then((res:any)=>{
            if(res&&res.map){
              this.columns = res.map((e:any)=>{
                e.text = e.label
                return e
              })
             
              var index = 0
              if (this.initialValue){
                index= this.columns.findIndex((e:any)=>e.value==this.initialValue)
                
              }
              if (this.notSelectFirst) return
              this.selectedValue = this.columns[index]
              
              this.$emit('update:label',this.columns[index]?.text)
              this.$emit('update:value',this.columns[index]?.value)
            }
      
          })
    
          
        } catch (error) {
          console.error('远程搜索失败:', error);
        }
      },

    }
  }
</script>
