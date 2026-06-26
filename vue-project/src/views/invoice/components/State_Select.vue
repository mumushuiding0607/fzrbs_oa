<template>
  <ActionSheet v-model:show="visible" title="状态" show-cancel-button style="height: 50vh;">
    <Cell v-for="item in options" :key="item.value" :title="item.label" @click="onSelected(item)" :icon="item.value==selectedValue?'success':''"/>
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,Field,Cell} from 'vant';
import { getstates } from '../invoice';

  export default {
    components: {
      ActionSheet,Field,Cell
    },
    props: ['show','value'],
    data () {
      return {
        visible:false,
        selectedValue:'',

        options:<any>[

        ],
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
      this.selectedValue = this.value
      getstates({}).then((res:any)=>{
        this.options = res
      })
    },
    created() {

    },
    methods:{
      onSelected(val:any){
 
        if (val){
          this.selectedValue = val.value
          this.$emit('update:value',val.value)
          this.$emit('update:label',val.label)
          this.visible=false
          this.$emit('update:show',false)
        }
      },


    }
  }
</script>
