
<template>
  <div class="box">
    <Viewflow :thirdNo="par.thirdNo" />
  </div>
</template>
<script  lang="ts">

import { Tabs,Tab,NavBar } from 'vant';
import Viewflow from '@/views/press/components/press_viewflow.vue';

  export default {
    components: {
      Viewflow,
      NavBar,
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
<style  lang="css">

  .van-notice-bar span{
    font-size:21px!important;
  }
 
  .box{
    width: 100%;
    min-height: 100vh;
    border-right: 2px solid #eff2f5;
    border-left: 2px solid #eff2f5;
    margin-left:0 ;
    margin-top: 0;
  }

  /* tab */
  .van-tabs__wrap {
      height: 40px;
  } 
  .van-tabs__nav .van-tabs__nav--card{
    height: 40px;
  }
  .van-tabs__nav--card {
      box-sizing: border-box;
      margin: 0 ;
      border: 1px solid rgb(238, 238, 238);
      border-radius: var(--van-border-radius-sm);
      height: 40px;
      padding: 0;
      
  }
  .van-tab--card {
      color: #666;
      background-color: white;
      border: none;
      font-size: 18px;
      border-right: 1px solid rgb(238, 238, 238);
  }
  .van-tab__text--ellipsis {
      display: -webkit-box;
      overflow:inherit;
      -webkit-box-orient: vertical;
  }
  .van-tab--card.van-tab--active {
    background-color: #F1F1F1;
    color: #666;
  }

  
</style>

<style>
  @media screen and (min-width: 500px) {
    
    .navbarClass{
      margin: 0 auto;
      width: 500px;
    }
    .van-notice-bar span{
      font-size:21px!important;
    }    
    /* tab */
  .van-tabs__wrap {
      height: 40px;
  } 
  .van-tabs__nav .van-tabs__nav--card{
    height: 40px;
  }
  .van-tabs__nav--card {
      box-sizing: border-box;
      margin: 0 ;
      border: 1px solid rgb(238, 238, 238);
      border-radius: var(--van-border-radius-sm);
      height: 40px;
      padding: 0;
      
  }
  .van-tab--card {
      color: #666;
      background-color: white;
      border: none;
      font-size: 18px;
      border-right: 1px solid rgb(238, 238, 238);
  }
  .van-tab__text--ellipsis {
      display: -webkit-box;
      overflow:inherit;
      -webkit-box-orient: vertical;
  }
  .van-tab--card.van-tab--active {
    background-color: #F1F1F1;
  }
      
  }
</style>
