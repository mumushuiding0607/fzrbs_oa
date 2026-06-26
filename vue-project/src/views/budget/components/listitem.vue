<template>
  <div class="listrow">
    <div style="flex-grow: 1;">
      <div class="col">
        <div style="flex-grow: 1;color: black;font-weight: normal!important;" class="value">
          <Badge v-if="data?.age" style="margin-left: 10px;" :content="data.age" position="top-left">
            <span  :style="{margin:'0',color:'red',border:'1px solid #F5F2EF',padding:'2px'}" >逾</span>
          </Badge>
          {{ data?.title }}
        </div>
        <div class="label" v-if="data.name">{{ data.name+' '+((data.approvaltypename||data.statename)?((data.approvaltypename||data.statename)+'审批'):'') }}</div>
      </div>
    </div>
    <div class="date">{{ data?.inserttime.substring(0,10) }}</div>
  </div>
</template>
<script  lang="ts">
  import {Badge} from 'vant'
  export default {
    components: {Badge},
    props: ['data'],
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
<style src="@/views/financeCss.css">
 
</style>
<style>
  @media screen and (min-width: 500px) {
       
   
      
  }
</style>