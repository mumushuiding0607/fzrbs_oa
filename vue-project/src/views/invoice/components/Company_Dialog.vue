<template>
  <Dialog v-model:show="visible" title="公司" show-cancel-button>
    <CellGroup >
      <CopyableCell label-class="label" title="公司名称" :value="company?.company" />
      <CopyableCell title="信用代码" :value="company?.code"  />
      <CopyableCell title="开户银行" :value="bankname"  />
      <CopyableCell label-class="label" title="银行账号" :value="account"  />
      <CopyableCell title="公司地址" :value="company?.address"  />
      <CopyableCell title="联系电话" :value="company?.contacts"  />
    </CellGroup>
  </Dialog>
</template>
<script  lang="ts">
import { Dialog,CellGroup,Cell} from 'vant';
import { getbyid, getcompany } from '../invoice';
import CopyableCell from '@/views/invoice/components/Copyable_Cell.vue'
  export default {
    components: {
      Dialog,CellGroup,Cell,CopyableCell
    },
    props: ['id','show','customer'],
    data () {
      return {
        visible:false,
        company:<any>{company:'',code:'',address:''},
        bankname:'',
        account:'',
        mobile:''
      }
    },
    watch:{
      show(val){
        this.visible = val
      },
      visible(val){
        this.$emit('update:show',val)
      },
      id(val){
        if (val){
          getcompany({id:val}).then((res:any)=>{
            if (res){
              this.company = res[0]
              if (res[0].bankaccount){
                var temp = res[0].bankaccount.split(',')
                temp = temp[0].split(' ')
                this.bankname = temp[0]
                this.account = temp[1]
              }else{
                this.bankname = ''
                this.account = ''
              }
              
            }
          })
        }
        
      },
      customer(val){
        if (val){
          this.company = val
          if (val.bankaccount){
            var temp = val.bankaccount.split(',')
            temp = temp[0].split(' ')
            this.bankname = temp[0]
            this.account = temp[1]
          }
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
