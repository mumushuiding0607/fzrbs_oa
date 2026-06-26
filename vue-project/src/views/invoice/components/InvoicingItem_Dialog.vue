<template>
  <Dialog v-model:show="visible" title="开票项目" show-cancel-button :show-confirm-button="false">
    <Form @submit="onSubmit">
      <Field
        v-model="obj.title"
        name="title"
        label="项目名称"
        placeholder="广告费、活动执行等"
        :rules="[{ required: true, message: '广告费、活动执行等' }]"
      />
      <Field
        v-model="obj.amount"
        name="amount"
        label="项目金额"
        placeholder="项目金额"
        :rules="[{ required: true,pattern: /^\d+(\.\d{1,2})?$/, message: '金额格式不正确（最多两位小数）' }]"
      />
      <Field
        v-model="obj.unit"
        name="unit"
        label="项目单位"
        placeholder="项目单位"
        :rules="[{ required: false, message: '输入单位' }]"
      />
      <Field
        v-model="obj.number"
        name="number"
        label="项目数量"
        placeholder="项目数量"
        :rules="[{ required: false }]"
      />
      <div style="margin: 16px;">
        <Button round block type="primary" native-type="submit">
          提交
        </Button>
      </div>
    </Form>

  </Dialog>
</template>
<script  lang="ts">
import { Dialog,CellGroup,Cell,Form,Field,Button} from 'vant';
import CopyableCell from '@/views/invoice/components/Copyable_Cell.vue'

  export default {
    components: {
      Dialog,CellGroup,Cell,CopyableCell,Form,Field,Button
    },
    props: ['id','show','data'],
    data () {
      return {
        visible:false,
        obj:<any>{},

      }
    },
    watch:{
      show(val){
        this.visible = val
      },
      visible(val){
        this.$emit('update:show',val)
      },
      data(val){
        this.obj = val
      }

    },
    mounted() {
      
    },
    created() {

    },
    methods:{
      onSubmit(){
        this.visible=false
        this.$emit('confirm',this.obj)
      }

    }
  }
</script>
