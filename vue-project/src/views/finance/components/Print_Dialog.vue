<template>
  <Popup v-model:show="visible" title="打印"  :style="{ width: '100%',maxWidth:'100%!important', height: '100%' }" closeable @close="visible = false">
    <Print :key="pkey" :thirdNo="thirdNo" @close="visible=false" @change="onPrintChange"></Print>
  </Popup>
  
</template>
<script  lang="ts">
import Print from '../print.vue';
import { Popup} from 'vant';
  export default {
    components: {Print,Popup
    },
    props: ['thirdNo','show'],
    watch:{
      show(val){
        this.visible = val
      },
      visible(val){
        this.$emit('update:show',val)
      },
    },
    data () {
      return {
        visible:false,
        pkey:0
      }
    },
   
    mounted() {
      
    },
    created() {

    },
    methods:{
      onPrintChange(){
        this.pkey++
      }
    }
  }
</script>

