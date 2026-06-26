<template>
  <div style="display: flex;flex-direction: row;align-items: center;">

          <Field
            v-model="date"
            style="width: 65%;"
            readonly
            required

            :label="label"
            placeholder="点击选择日期"
            
            @click="showDate=true"

          />
          
          <Field
            v-model="time"
            style="width: 35%;"
            readonly
            required

            label=""
            placeholder="选择时间"
            @click="showTime=true"

          />
          </div>
          <DatePicker_Dialog :show.sync="showDate"  @update:show="(val:any)=>showDate=val"  @update:label="(val:any)=>date=val"/>
          <DPickerDialog :show.sync="showTime"  @update:show="(val:any)=>showTime=val"  @update:label="(val:any)=>time=val"/>
</template>
<script  lang="ts">
import DatePicker_Dialog from '@/views/press/components/DatePicker_Dialog.vue';
import { ActionSheet,DatePicker,Field} from 'vant';
import DPickerDialog from './DPickerDialog.vue';

  export default {
    components: {
      ActionSheet,DatePicker,Field,DatePicker_Dialog,DPickerDialog
    },
    props: ['value','label'],
    data () {
      return {
        showDate:false,
        showTime:false,
        date:'',
        time:'全天'

      }
    },
    watch:{
      date(val){
        this.$emit('update:value',val+' '+this.time)
      },
      time(val){
        this.$emit('update:value',this.date+' '+val)
      },
      value(val){
        if (val){
          this.date = val.split(' ')[0]
          this.time = val.split(' ')[1]
        }
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
