<template>
  <Cell id="customcell" :title="title" :value="value" @click="copy()" />
</template>
<script  lang="ts">
import { Cell, showToast} from 'vant';
  export default {
    components: {Cell
    },
    props: ['title','value'],
    data () {
      return {
      }
    },
   
    mounted() {
      
    },
    created() {

    },
    methods:{
      async copy() {
        try {
          // 将指定的值写入剪贴板
          await navigator.clipboard.writeText(this.value);

          showToast('复制成功');
        } catch (err) {
          console.log(err)
        }
      },

    }
  }
</script>

<style>

#customcell .van-cell__value {
    flex: 2.5;
    position: relative;
    overflow: hidden;
    color: var(--van-cell-value-color);
    text-align: right;
    vertical-align: middle;
    word-wrap: break-word;
}
</style>
<style>
  @media screen and (min-width: 500px) {
       

    #customcell .van-cell__value {
        flex: 2.5;
        position: relative;
        overflow: hidden;
        color: var(--van-cell-value-color);
        text-align: right;
        vertical-align: middle;
        word-wrap: break-word;
    } 
  }
</style>