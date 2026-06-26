<template>
  <Dialog v-model:show="visible" title="记者" show-cancel-button :show-confirm-button="false">
    <Form @submit="onSubmit">
      <Field
        v-model="obj.username"
        required
        name="username"
        readonly
        label="记者"
        placeholder="输入记者名字"
        @click="showUser=true"
        :rules="[{ required: true, message: '记者名字不能为空' }]"
      />
      <Field
        v-model="obj.remark"
        required
        name="remark"
        label="备注"
        placeholder="备注"
        :rules="[{ required: true, message: '备注' }]"
      />

      <Field
        v-model="obj.order_num"
        name="number"
        label="排序"
        required
        placeholder="排序：0~100"
        :rules="[{ required: false }]"
      />
      <div style="margin: 16px;">
        <Button round block type="primary" native-type="submit">
          提交
        </Button>
      </div>
    </Form>
    <UserSelect  :show.sync="showUser" @update:show="(val:any)=>showUser=val" @update:value="(val:any)=>{
      obj.userid=val.userid
      obj.username=val.name
      obj.mobile=val.mobile
    }" ></UserSelect>


  </Dialog>
</template>
<script  lang="ts">
import { Dialog,CellGroup,Cell,Form,Field,Button} from 'vant';
import CopyableCell from '@/views/invoice/components/Copyable_Cell.vue'
import UserSelect from '@/views/invoice/components/UserSelect.vue';

  export default {
    components: {
      Dialog,CellGroup,Cell,CopyableCell,Form,Field,Button,UserSelect
    },
    props: ['id','show','data'],
    data () {
      return {
        visible:false,
        obj:<any>{order_num:1},
        showUser:false,

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
        this.obj = val||{}
        console.log('data watch:',val)
      }

    },
    mounted() {
      this.obj = this.data||{}
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
