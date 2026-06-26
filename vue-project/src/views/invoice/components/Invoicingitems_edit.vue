
<template>
  <div style="display: flex;flex-direction: row;justify-content: center;background-color: white;width: 100%;">
    <div @click="add" style="color:var(--van-text-color)!important;padding: 6px;display: flex;flex-direction: row;width: 95%;border-bottom: 1px solid #f2f2f2;align-items: center;">
      <span style="font-size: 14px;">开票项目</span><span style="margin-left: 15px;"><Icon name="add-square" size="30" /></span>
    </div>
  </div>
  <!-- 开票项目列表，包括 发票项目名称、单位、数量和金额 -->
  <CellGroup v-for="(item,index) in data" :key="index">
    <div style="display: flex;flex-direction: row;">
      
    <Cell  :title="item.title" :value="'￥'+parseFloat(item?.amount).toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            })" :label="(item.number?item.number:'')+(item.unit?'*'+item.unit:'')" @click="updateItem(item)">
            <template #right-icon>
              <Icon name="delete" size="25" style="margin-left: 5px;" @click.stop="delItem(index)" />
            </template>
      </Cell>
    </div>
    
  </CellGroup>
  <InvoicingItem_Dialog :show.sync="addDia" :data.sync="itemData" @update:show="val=>addDia=val" @confirm="onItemchange"></InvoicingItem_Dialog>
</template>
<script  lang="ts">

import { CellGroup,Cell,Icon } from 'vant';
import InvoicingItem_Dialog from '@/views/invoice/components/InvoicingItem_Dialog.vue'
import { add } from '@/api/lingdaoxinxiang';
import { delinvoiceitem } from '../invoice';

  export default {
    components: {
      CellGroup,Cell,Icon,InvoicingItem_Dialog
    },
    props:['initialValue'],
    data () {
      return {
      
        addDia:false,
        data:<any>[],
        itemData:<any>{}
      }
    },
    watch:{
      initialValue(val){
        this.data = val
      }
    },
    mounted() {

    },
    created() {
    },
    methods:{
      add(){
        this.addDia = true
        this.itemData = {}
      },
      updateItem(value:any){
        this.addDia = true
        this.itemData = value
      },
      async delItem(index:any){
        var temp:any = this.data.splice(index,1)[0]
        if (temp.id){
          delinvoiceitem({id:temp.id}).then((res:any)=>{
            if (res.errorMessage){
            }else{
              this.$emit('update:value',this.data)
            }
          })
        }else{
          this.$emit('update:value',this.data)
        }

      },
      onItemchange(value:any){
        var temp = this.data
        // 判断data是否包含value,通过id判断，若id为空，则通过title判断，如果已经存在就替换，如果不存在就添加
        var index = -1;
        if (value.id){
          index = temp.findIndex((item:any)=>item.id==value.id)
          
        }else{

          index = temp.findIndex((item:any)=>{
            return item.title==value.title
          })

        }
        
        if (index>-1){
          temp[index] = value
        }else{
          temp.push(value)
        }
        this.data = temp
        this.$emit('update:value',temp)
      }
    }
  }
</script>

