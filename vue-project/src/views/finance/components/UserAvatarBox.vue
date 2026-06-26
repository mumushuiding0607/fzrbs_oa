<template>
  <div style="display: flex;flex-direction: row;flex-wrap: wrap;align-items: center;">
 
    
    <div v-for="u in users" @click="remove(u)"   style="display: flex;flex-direction: column;border: 1px solid #eee;padding: 0 .2rem;">
      <div  class="imageBox" :style="{background: 'url('+u.avatar+')',backgroundSize: 'cover',backgroundPosition: 'center',backgroundRepeat: 'no-repeat'}"></div>
      <div style="font-size: .6rem;text-align: center;">{{ u.name}}</div>
    </div>


    <Icon v-if="update" name="add-o" class="imageBox" size="2rem" @click="show=true"/>

  </div>
  <UserSelect  :show.sync="show" @update:show="(val:any)=>show=val" @update:value="(val:any)=>{
    addUser(val)
  }" />
</template>
<script  lang="ts">
import UserSelect from '@/views/invoice/components/UserSelect.vue';
import { Image,Icon} from 'vant';
import { getusers } from '@/views/finance/finance';

  export default {
    components: {
      Image,UserSelect,Icon
    },
    props: ['userids','update'],
    data () {
      return {
        show:false,
        users:<any>[]

      }
    },
    watch:{
      userids(val){
        if (val){
          getusers({userids:val}).then((res:any)=>{
            this.users = res
            
          })
      }
      }
    },
    mounted() {
      
    },
    created() {

    },
    methods:{
      addUser(val:any){
        // 判断是否已经存在
        if (this.users.find((item:any)=>item.userid==val.userid)){
          return
        }
        this.users.push(val)
        
        this.$emit('update:value',this.users.map((e:any)=>e.userid).join(','))
      },
      remove(val:any){
        if(!this.update) return
        this.users = this.users.filter((item:any)=>item.userid!=val.userid)
        this.$emit('update:value',this.users.map((e:any)=>e.userid).join(','))
      }
    }
  }
</script>
<style lang="css">
.imageBox{
    width: 2rem;
    height: 2rem;
  }
</style>
