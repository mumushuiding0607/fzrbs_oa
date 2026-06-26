<template>
  <ActionSheet v-model:show="visible"  show-cancel-button style="height: 50vh;">
    <TimePicker
      title="选择时分"
      :columns-type="['hour', 'minute']"
      @confirm="confirm"
      @cancel="visible = false"
      
      
    />
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,TimePicker} from 'vant';

  export default {
    components: {
      ActionSheet,TimePicker
    },
    props: ['show'],
    data () {
      return {
        visible:false,
      }
    },
    watch:{
      show(val){
        this.visible = val
      },
      visible(val){
        this.$emit('update:show',val)
      },
      
    },
    mounted() {
 

    },
    created() {

    },
    methods:{
      confirm(val:any){
   
        if(val.selectedValues){
          this.$emit('update:label',val.selectedValues.join(':'))
          this.visible = false
        }
      }

    }
  }
</script>
