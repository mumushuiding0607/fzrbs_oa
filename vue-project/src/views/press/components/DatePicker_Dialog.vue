<template>
  <ActionSheet v-model:show="visible"  show-cancel-button style="height: 50vh;">
    <DatePicker
      title="选择年月日"
      :min-date="minDate"
      :columns-type="['year', 'month','day']"
      @confirm="confirm"
      @cancel="visible = false"
      :model-value="currentDate"
      
      
    />
  </ActionSheet>
</template>
<script  lang="ts">
import { ActionSheet,DatePicker} from 'vant';

  export default {
    components: {
      ActionSheet,DatePicker
    },
    props: ['show'],
    data () {
      return {
        visible:false,
        currentDate: <any>[],
        minDate: <any>undefined,
        formatter: (type:any, value:any) => {

        }
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
      const now = new Date()
      this.minDate = new Date(now.getFullYear()-1, 0, 1)
      var month:any = now.getMonth()+1
      month = month<10?'0'+month:month
      var day:any = now.getDate()
      day = day<10?'0'+day:day
      this.currentDate = [''+now.getFullYear(),''+month,''+day]
    

    },
    created() {

    },
    methods:{
      confirm(val:any){
   
        if(val.selectedValues){
          this.$emit('update:label',val.selectedValues.join('-'))
          this.visible = false
        }
      }

    }
  }
</script>
