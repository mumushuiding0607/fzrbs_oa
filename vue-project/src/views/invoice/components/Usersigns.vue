<template>
  <div style="display: flex;flex-wrap: wrap;">
    <div v-for="item in items" :style="{margin:'5px',fontWeight:'bold'}">{{item}}</div>

    </div>
</template>
<script  lang="ts">
import { Cell, showToast} from 'vant';
  export default {
    components: {Cell
    },
    props: ['datas'],
    data () {
      return {
        items:[]
      }
    },
   
    mounted() {
      console.log('========================',this.datas)
      if (this.datas&&this.datas.split){
        this.items = this.datas.split(';')
      }
      console.log('========================',this.items)
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