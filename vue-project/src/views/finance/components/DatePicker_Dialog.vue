<template>
  <ActionSheet v-model:show="visible"  show-cancel-button style="height: 50vh;">
    <DatePicker
      v-model="currentDate"
      title="选择年月"
      :min-date="minDate"
      :columns-type="['year', 'month']"
      @confirm="confirm"
      @cancel="visible = false"
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
        columnsType: ['year', 'month'],
        formatter: (type:any, value:any) => {
          if (type === 'year') {
            return `${value}年`;
          }
          if (type === 'month') {
            return `${value}月`;
          }
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
      this.minDate = new Date(now.getFullYear(), 0, 1)
      var month:any = now.getMonth()
      month = month<10?'0'+month:month
      this.currentDate = [''+now.getFullYear(),''+month]
    },
    created() {

    },
    methods:{
      confirm(val:any){
        if(val.selectedValues){
          this.$emit('update:label',val.selectedValues[0]+'年'+val.selectedValues[1]+'月')
          this.visible = false
        }
      }

    }
  }
</script>
