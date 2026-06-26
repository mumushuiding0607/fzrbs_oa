<template>
  <RadioGroup v-model="checked" @change="onChange" direction="horizontal" style="padding:var(--van-cell-vertical-padding) 0 var(--van-cell-vertical-padding) calc(var(--van-cell-horizontal-padding)*0.8);">
    <Radio :name="e.value" v-for="e in columns" style="width: 40%;font-size: calc(var(--van-cell-font-size));margin-top: 10px;" >{{ e.text }}</Radio>
    
  </RadioGroup>

</template>
<script  lang="ts">
  import { RadioGroup,Radio} from 'vant';
  import {  getdictbykeyword } from '../invoice';

  export default {
    components: {
      RadioGroup,Radio
    },
    props: ['type','subtype','placeholder','initialValue','notSelectFirst','order'],
    data () {
      return {
        visible:false,
        selectedValue:<any>[],
        columns:<any>[],
        checked:0
      }
    },

    watch:{

      initialValue(val){
        var index = 0
        if (val){

          index = this.columns.findIndex((e:any)=>e.value==val)

        }
        this.selectedValue = this.columns[index]
        this.checked = this.columns[index]?.value

        console.log(this.type+'watch:',this.initialValue)        
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



      onChange(val:any){
        this.$emit('update:value',val)
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
              console.log(this.type+'getDatas:',this.initialValue)
              if (this.notSelectFirst) return
              this.selectedValue = this.columns[index]
              this.checked = this.columns[index]?.value
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
