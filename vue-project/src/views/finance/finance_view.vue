
<template>
  <div class="box">
    <Viewflow :thirdNo="par.thirdNo" />
  </div>
</template>
<script  lang="ts">

import { Tabs,Tab,NavBar } from 'vant';
import Viewflow from './components/finance_viewflow.vue';

  export default {
    components: {
      Viewflow,NavBar,
      Tabs,Tab,Image
    },
    data () {
      return {
        par:this.$route.query,
        active:0,
        data:{},
        thirdNo:'',
        marginLeft:0,
        width:'100%',
        navbarkey:0,
      }
    },
    mounted() {

      this.resize()
      window.addEventListener('resize', this.resize);
      
      
    },
    created() {
    },
    methods:{
      resize(){
        var elementWidth = window.innerWidth;
        if (elementWidth>500){
          this.marginLeft = (elementWidth-500)/2-7
          this.width = '496px'
        }else{
          this.marginLeft = 0
          this.width = '100%'
        }
        this.navbarkey = this.navbarkey+1
      },
      onClickLeft(){
        this.$router.go(-1)
      }
    }
  }
</script>


