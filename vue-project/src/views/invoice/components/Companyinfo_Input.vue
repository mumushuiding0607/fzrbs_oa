<template>
    <Form >
      <Field
        v-model="obj.company"
        name="company"
        label="公司名称"
        placeholder="输入公司名称"
       
        :rules="[{ required: true, message: '请输入项目名称' }]"
        
      />
      <Field
        v-model="obj.code"
        name="code"
        label="信用代码"
        placeholder="客户信用代码"
        :rules="[{ required: true, message: '不能为空' }]"
      />
      <Field
        v-model="obj.address"
        name="address"
        label="公司地址"
        placeholder="公司地址"
        :rules="[{ required: false, message: '不能为空' }]"
      />
      <Field
        v-model="obj.contacts"
        name="contacts"
        label="联系电话"
        placeholder="联系电话"
        :rules="[{ required: false, message: '不能为空' },{pattern: /^(1[3-9]\d{9})|(\d{3,4}-?\d{7,8})$/,message: '请输入正确的手机号或座机号格式'
  }]"
      />
      <Field
        v-model="obj.email"
        name="email"
        label="邮箱"
        placeholder="邮箱"
        :rules="[{ required: false, message: '不能为空' },{ pattern: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/, message: '邮箱格式不正确' }]"
      />
      <Field
        v-if="!isEmpty"
        v-model="result"
        is-link
        readonly
        name="picker"
        label="开户信息"
        placeholder="点击选择开户行和开户账号"
        @click="showPicker = true"
      />
      <Field
        v-if="isEmpty"
        v-model="obj.bank"
        name="bank"
        label="开户行"
        placeholder="开户行"
        @change="onChange"
        :rules="[{ required: false, message: '不能为空' }]"
      />
      <Field
        v-if="isEmpty"
        v-model="obj.account"
        name="account"
        label="开户账号"
        placeholder="开户账号"
        @change="onChange"
        :rules="[{ required: false, message: '不能为空' }]"
      />
      
    </Form>
    <Popup v-model:show="showPicker" destroy-on-close position="bottom">
      <Picker
        :columns="columns"
        @confirm="onConfirm"
        @cancel="showPicker = false"
      />
    </Popup>
</template>
<script  lang="ts">
import { CellGroup,Cell,Form,Field,Button,Popup,Picker} from 'vant';
import CopyableCell from '@/views/invoice/components/Copyable_Cell.vue'
import { getcompany } from '../invoice';

  export default {
    components: {
      CellGroup,Cell,CopyableCell,Form,Field,Button,Popup,Picker
    },
    props: ['id','show','data','item'],
    data () {
      return {
        isEmpty:false,
        obj:<any>{},
        showPicker:false,
        columns:<any>[],
        pickerValue:<any>[],
        result:''

      }
    },
    watch:{
      id(val){
      
        if (val&&(!this.data||!this.data.id)){
          getcompany({id:val}).then((res:any)=>{
            console.log('this.data:',this.data)
            if(res&&res[0]){
              var temp = res[0]
              if (temp.bankaccount&&temp.bankaccount!='Array' && temp.bankaccount.split){
                var tbanks = temp.bankaccount.split(',').map((e:any)=>{
                  return {value:e,text:e}
                })
                this.columns = tbanks
                this.columns.push({text:'点击自定义开户行和银行账号',value:'-1'})
                this.isEmpty = false
                this.pickerValue = tbanks[0]
                temp.bankaccount = tbanks[0].value
                this.result = tbanks[0].text
              }else{
                this.columns = []
                this.isEmpty = true
                this.pickerValue = []
                this.result = ''
              }
              this.obj = temp
              this.$emit('update:value',temp)
            }
          })
        }
      },
      data(temp){
        this.setval(temp)
        
      },


    },
    mounted() {
      this.setval(this.data)
    },
    created() {

    },
    methods:{
      setval(temp:any){
        console.log('temp:',temp)
        this.isEmpty=true
        this.obj = temp||{}
        if (temp){
          if (temp.bankaccount&&temp.bankaccount!='Array' && temp.bankaccount.split){
            var arr = temp.bankaccount.split(' ')
            this.obj.bank = arr[0]
            this.obj.account = arr[1]
          }
        }
        this.$emit('update:value',this.obj)
      },
      onConfirm({ selectedValues, selectedOptions }: any) {
        if(selectedOptions[0]?.value==-1){
          this.isEmpty=true

        }else{
        this.result = selectedOptions[0]?.text;
        this.pickerValue = selectedValues;
        
        this.obj.bankaccount = selectedValues[0]
        this.$emit('update:value',this.obj)
        }
        this.showPicker = false;
        
      },
      onChange(){
        this.obj.bankaccount = (this.obj.bank?this.obj.bank:'-')+' '+(this.obj.account?this.obj.account:'-')
        
      }

    }
  }
</script>
