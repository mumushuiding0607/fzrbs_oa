<template>
  <ActionSheet v-model:show="visible" :actions="actions" @select="onSelected" />
</template>
<script  lang="ts">
import { ActionSheet,Field,Cell} from 'vant';

  export default {
    components: {
      ActionSheet,Field,Cell
    },
    props: ['show','options'],
    data () {
      return {
        visible:false,
        selectedValue:'',
        actions:<any>[],
      }
    },
    watch:{
      show(val){
        this.visible = val

      },
      options(val){
        
        this.actions = val
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
        console.log(val)
        if (val){
          this.$emit('update:value',val.value)
          this.$emit('update:label',val.name)
          this.visible=false
          this.$emit('change',val)
        }
      },


    }
  }
</script>
