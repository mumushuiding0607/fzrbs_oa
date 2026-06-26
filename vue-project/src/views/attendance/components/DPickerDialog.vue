<template>
  <ActionSheet v-model:show="visible"  show-cancel-button style="height: 50vh;">
    <Picker
      title="请点击选择"
      :columns="columns"
      @confirm="confirm"
      @cancel="visible = false"
      
      
    />
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,Picker} from 'vant';

  export default {
    components: {
      ActionSheet,Picker
    },
    props: ['show'],
    data () {
      return {
        visible:false,
        columns:[
          {text:'全天',value:'全天'},
          {text:'上午',value:'上午'},
          {text:'下午',value:'下午'},
          {text:'晚上',value:'晚上'}
        ]
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
