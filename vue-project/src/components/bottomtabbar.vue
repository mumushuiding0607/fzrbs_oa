<template  >
    <div :style="{ height: app ? '60px' : '0px' }"></div>
    <Tabbar v-if="app"  v-model="activeTab"   safe-area-inset-bottom style="z-index: 1000;" active-color="#1989fa" inactive-color="#7d7e80">

        <TabbarItem v-for="(e,index) in options" @click="routeTo(e)">
          <span>{{ e.name }}</span>
          <template v-if="e.iconActive||e.icon" #icon="props">
            <img :src="activeTab==index ? e.iconActive : e.icon" />
          </template>
        </TabbarItem>
    </Tabbar>
    <ActionSheet v-model:show="showBottom" :actions="columns" @select="onChange" />

</template>
<script  lang="ts">
import { appEnv } from '@/utils/common';
import { Tabbar, TabbarItem,ActionSheet } from 'vant';

export default {
    components: {
      Tabbar,TabbarItem,ActionSheet
    },
    props: ['options','active'],
    data () {
      return {
        app:false,
        showBottom:false,
        columns:[],
        activeTab:0
      }
    },

    watch:{
      active(val){
        this.activeTab = val
      }
    },

    mounted() {
      this.app = !appEnv()
      this.activeTab = this.active
      
    },
    created() {
 
    },
    methods:{
      
       onChange(e:any){
        if (e.route){
            // 判断route是否已经包含?
            if (e.route.indexOf('?')>-1){
              this.$router.push(e.route+"&withUserAgent=true")
            }else{
              this.$router.push(e.route+'?withUserAgent=true')
            }
          }
       },
        routeTo(e:any){
          if (e.route){
            // 判断route是否已经包含?
            if (e.route.indexOf('?')>-1){
              this.$router.push(e.route+"&withUserAgent=true")
            }else{
              this.$router.push(e.route+'?withUserAgent=true')
            }
          }else if (e.children && e.children.length>0){
            this.columns = e.children
            this.showBottom = true

          }
        }
        
      
    }
  }
</script>

