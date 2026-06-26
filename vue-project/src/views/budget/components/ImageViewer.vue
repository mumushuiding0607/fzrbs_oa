<template>
  <div>
    <VDPhoto 
    ref="VDPhoto"
    :imgArr="src"
/>
  </div>
</template>
<script  >
  // 在入口（main.js）中引入并挂载
  // import VDPhoto from 'vue-dark-photo'
  // // 引入vue-dark-photo 样式
  // import 'vue-dark-photo/lib/vue-dark-photo.css'
  import VDPhoto from 'v-viewer'

  export default {
    components: {VDPhoto},
    props: ['src'],
    data () {
      return {
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
<style >
 
</style>
<style>
  @media screen and (min-width: 500px) {
       
    .row{
      width: 100%;
      display: flex;
      flex-direction: row;
      align-items: center;
      padding: 5px;
    }
    .col{
      width: 100%;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .row .label{
      color: gray;
      width: 180px;
      font-size: 18px;
      padding-left: 5px;
    }
    .row .value{
      font-size: 22px;
      font-weight: normal;
    }
    .date{
    color: gray;
    font-size: 18px;
  }
      
  }
</style>