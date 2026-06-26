<template>
  <div v-if="!column||column==1" v-for="(item,index) in datas" class="listrow cell" >
  
      <Icon v-if="edit" name="delete" @click="deleteItem(index)"/>
      <span style="flex-grow: 1;">{{ item.name }}</span>
      <span style="color:gray;margin-right: 20px;font-size: calc(var(--van-cell-font-size)*0.9);">{{ item.departmentname }}</span>
      <div>
        <Stepper v-if="edit" v-model="item.score" :min="0" step="1" :decimal-length="0" />
      </div>
      <div v-if="!edit">
        <span style="color:gray;margin-right: 20px;font-size: calc(var(--van-cell-font-size)*0.9);">{{ item.score }}分</span>
      </div>
  </div>
  <div id="us" v-if="column>1">
    <Grid :column-num="edit?2:3">
      <GridItem v-for="(item,index) in datas" :key="index" >
            <span v-if="!edit" style="font-size: calc(var(--van-cell-font-size)*0.9)!important;" @click="deleteItem(index)">{{ item.name+'：'+(item.score||0)+'分' }}</span>
            <Field v-if="edit" :border="true"  size="normal" class="field-underline" v-model="item.score" type="number"  :label="item.name" placeholder=""   :readonly="!edit" input-align="right" inputs
             @click-left-icon="deleteItem(index)" >
             <template #label >
                
                <span @click="view(item)">{{ item.name }}</span>
                
              </template>
              <template #left-icon >
                
                <Icon name="edit"/>
                
              </template>
              <template #right-icon>
                <Tag v-if="item.typename" color="#ffe1e1" text-color="#ad0000">{{item.typename.substring(0,1) }}</Tag>
                <Tag plain type="primary" style="border: none;">分</Tag>
                
              </template>
            </Field>

      
      
      </GridItem>
      

    </Grid>
    
  </div>

  
  
</template>
<script  lang="ts">
  import {Stepper,Icon,GridItem,Grid, showConfirmDialog,Field,Tag} from 'vant'


  export default {
    components: {
     Stepper,Icon,GridItem,Grid,Field,Tag
    },
    props: ['datas','edit','column','state'],
    watch:{
      // datas deep 监听
      datas:{
        handler(val:any,oldVal:any){
          
          this.$emit('update:value',val)
        },
        deep:true
      }
    },

    data () {
      return {

      }
    },

    mounted() {
      console.log('userscor:',this.datas)
    },
    created() {

    },
    methods:{
      view(item:any){

      },
      deleteItem(index:any){
        if (this.state!=1){
          return
        }
        showConfirmDialog({
            title: '确认删除'+this.datas[index].name+'吗，所属部门'+this.datas[index].departmentname+'',

          })
            .then(() => {
              this.datas.splice(index,1)
            })
            .catch(() => {
            });
      }

    }
  }
</script>
<style  src="@/views/financeCss.css">

</style>
<style scoped>


  :deep(.van-dialog__header .van-dialog__header--isolated) {
    font-size: calc(var(--van-cell-font-size)*0.9)!important;
  }
  #us .van-cell{
    font-size: calc(var(--van-cell-font-size)*0.9)!important;
    padding: 0 0 0 5px!important;
  }
  #us .no-padding-grid-item .van-grid-item__content {
    padding: 0!important;
  }
  :deep(.field-underline .van-field__control) {
    border: none;
    outline: none;
    padding: 0;
    border-bottom: 1px solid #c8c9cc;
    border-radius: 0;
    background: transparent;
  }

:deep(.field-underline.van-field--focus .van-field__control) {
  border-color: #efefef;
}


</style>
