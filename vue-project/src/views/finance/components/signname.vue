<template>
  <div style="display: flex;flex-direction: row;justify-content: space-around;padding: 2px 12px 2px 12px;width: 100%;font-size: var(--van-font-size-md);">
    <div style="flex-grow: 1;">
      <p v-for="e in value" style="text-align: left;color: black!important;">{{ e }}</p>
    </div>
    <div style="text-align: left;margin-left: 5px;color: black!important;">
      {{ date }}
    </div>
  </div>
</template>
<script  lang="ts">
import { Cell, showToast} from 'vant';
  export default {
    components: {Cell
    },
    props: ['data'],
    
    data () {
      return {
        value:[],
        date:''
      }
    },
    watch:{
    
    },
   
    mounted() {

      if (this.data){
        if (this.data.title) this.value = this.data.title.split(' ')
        this.date = this.data.date
      }
      
    },
    created() {

    },
    methods:{
      

    }
  }
</script>