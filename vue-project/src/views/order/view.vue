<template>
  <div class="box">

      <Viewflow :thirdNo="par.thirdNo" :infoid="par.infoid" :orderid="par.infoid" />
    
  </div>
</template>
<script lang="ts">
import { NavBar } from 'vant';
import Viewflow from './components/viewflow.vue';

export default {
  components: {
    NavBar,
    Viewflow
  },
  data () {
    return {
      par:this.$route.query,
      marginLeft:0,
      width:'100%',
      navbarkey:0,
    }
  },
  mounted() {
    this.resize()
    window.addEventListener('resize', this.resize);
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.resize);
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

