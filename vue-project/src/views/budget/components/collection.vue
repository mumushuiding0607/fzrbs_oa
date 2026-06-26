<template>
  <div  class="row" v-if="data.state!=0&&data.valid==1">
    <!-- <Cell :title="data.name+' '+data.date.substring(0,10)+' 确认回款 '" :value="'￥'+data.amount+'元'" :lable="data.note?data.note:'财务已确认'"></Cell> -->
    <div style="flex-grow: 1;">
      <span>{{data.name+' '+data.date.substring(0,10)+' 确认回款 '}}</span>
      <span style="color:green;font-weight:bold">￥{{data.amount}}元</span>
    </div>
    <div v-if="data.state==1&&data.valid==1" style="color:#1890ff;" @click="visible=true">财务确认</div>
    
  </div>
  <div v-if="data.state==3" style="color:gray;padding-left:15px;" >{{data.note?(data.note):'财务已确认'}}</div>
  <Dialog v-model:show="visible" title="财务确认" show-cancel-button :show-confirm-button="false">
    <Form @submit="onSubmit">
      <Field
        v-model="data.id"
        style="display:none"
        name="id"
        label="id"/>
        <Field
        v-model="data.note"
        type="textarea"
        rows="3"
        :autosize="true"
        name="note"
        label=""
        placeholder="财务备注"
        :rules="[{ required: true }]"
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
  import { Dialog,Field,Form,Button,showDialog,Cell } from 'vant';
import { paycollectioncheck } from '../budget';
  export default {
    components: {Dialog,Field,Form,Button,Cell},
    props: ['data'],
    data () {
      return {
        visible:false
      }
    },
    mounted() {

    },
    created() {

    },
    methods:{
      onSubmit(){
        this.visible=false
        paycollectioncheck(this.data).then((res:any)=>{
          if (res.errorMessage){}else{
            showDialog({'message':'操作成功'})
            this.$emit('change',this.data)
          }
        })
      }

    }
  }
</script>
<style >
  .row{
    width: 100%;
    display: flex;
    flex-direction: row;
    align-items: center;
    padding: 5px;
  }

</style>
<style>
  @media screen and (min-width: 500px) {
       
    
  }
</style>