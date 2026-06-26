<template>


  <Common_Select key="leaderid" :options="leaderColumns" :show.sync="showLeader"  @update:show="(val:any)=>showLeader=val" @change="change"
    />
  
</template>
<script  lang="ts">

import Common_Select from '@/views/press/components/Common_Select.vue';
import { getleaders } from '../api';



  export default {
    components: {
      Common_Select
    },
    props: [],
    data () {
      return {
        leaderColumns:[],
        showLeader:false,
      }
    },

    mounted() {
      getleaders({}).then((res:any)=>{
        if (res){
          this.leaderColumns = res.list||[]
        }
      })
    },
    created() {
      
    },
    methods:{
      change(e:any){
        if (e){
          this.$emit('update:value',e.value)
          this.$emit('update:label',e.name)
          this.showLeader=false

        }
      }
    }
  }
</script>

