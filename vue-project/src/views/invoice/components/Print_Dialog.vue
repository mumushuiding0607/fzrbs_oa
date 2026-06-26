<template>
  <Popup v-model:show="visible" title="打印"  :style="{ width: '499px', height: '100%' }" closeable @close="visible = false">
    <Print :ids="ids"></Print>
  </Popup>
  
</template>
<script  lang="ts">
import Print from '../print.vue';
import { Popup} from 'vant';
  export default {
    components: {Print,Popup
    },
    props: ['ids','show'],
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