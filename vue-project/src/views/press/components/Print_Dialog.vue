<template>
  <Popup v-model:show="visible" title="打印"  :style="{ width: '100%',maxWidth:'100%!important', height: '100%' }" closeable @close="visible = false">
    <Print :thirdNo="thirdNo" @close="visible=false"></Print>
  </Popup>
  
</template>
<script  lang="ts">
import Print from '@/views/press/print.vue';
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
      }
    },
   
    mounted() {
      
    },
    created() {

    },
    methods:{


    }
  }
</script>

<style>


</style>
<style>
  @media screen and (min-width: 500px) {
       

  }
</style>