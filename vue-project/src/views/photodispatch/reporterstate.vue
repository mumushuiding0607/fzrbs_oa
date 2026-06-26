
<template>
  <div class='box'>
    aaaa
  </div>
</template>
<script  lang="ts">

import listitem from '@/views/photodispatch/components/listitem.vue';

import { List,Cell,Image,Tabs,Tab,Search,Field } from 'vant';
import Button from 'vant/lib/button';
import tabbar from '@/components/bottomtabbar.vue'
import { useUserStore } from '@/stores';

import { finishlist, getlist, getnotifydata, gettabs, history, inglist } from './api';
const cacheStore = useUserStore()
  export default {
    components: {
      Button,Search,
      Tabs,Tab,List,Cell,Image,listitem,Field,tabbar
    },
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

<style   lang="css">

 
</style>

<style>
  @media screen and (min-width: 500px) {
    
  }
</style>
