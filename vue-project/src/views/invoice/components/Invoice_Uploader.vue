<template>
  <Dialog v-model:show="visible" title="上传XML发票" @confirm="onUploadConfirm" :show-cancel-button="true" :show-confirm-button="true">

  
      <Field
        v-model:model-value="obj.businesstype"
        readonly
        name="businesstype"
        label="业务类型"
      />
      <Field
        v-model:model-value="obj.publication"
        is-link
        readonly
        name="publication"
        label="媒体"
        placeholder="点击选择媒体"
        @click="showDict = true"
      />
      <Field
        v-if="obj.businesstype=='新媒体业务'"
        v-model:model-value="obj.subpublication"
    
        name="businesstype"
        label="子媒体"
        placeholder="如果是新媒体业务必须选择"
        @click="showBusinesstype = true"
      />
      <Field name="uploader" label="">
          <template #input>
              <Uploader key="i_uploader"  :max-count="1" accept=".xml" 
                  :after-read="afterRead"  />
          </template>
      </Field>
    
    

    
</Dialog>
<Businesstype_Popup type="新媒体业务" :show.sync="showBusinesstype"  @update:show="(val:any)=>showBusinesstype=val" @update:value="(val:any)=>obj.subpublication=val" @update:label="(val:any)=>obj.subpublication=val"/>
<Dict_Select type="发票媒体" :notSelectFirst="true" :show.sync="showDict"  @update:show="(val:any)=>showDict=val"  @update:label="(val:any)=>obj.publication=val"/>
</template>
<script  lang="ts">
  import { Cell, Dialog,Field,Uploader,showDialog,Form} from 'vant';
  import Dict_Select from './Dict_Select.vue';
import { uploadedFiles } from '@/utils/common';
import { setToUrl } from '@/views/budget/utils';
import { saveinvoice } from '../invoice';
import Businesstype_Popup from './Businesstype_Popup.vue';
  export default {
    components: {Form,Cell,Dialog,Field,Dict_Select,Uploader,showDialog,Businesstype_Popup
    },
    props: ['show','invoicingid','par'],
    data () {
      return {
        showDict:false,
        publication:'',
        visible:false,
        obj:<any>{},
        showBusinesstype:false
      }
    },
    watch:{
      show(val){
        this.visible = val
      },
      visible(val){
        this.$emit('update:show',val)
      },
      par(val){ 
        this.obj = val||{}
      }

    },
   
    mounted() {
      
    },
    created() {

    },
    methods:{
      onUploadConfirm(){

      },

      afterRead(file:any){
        
        uploadedFiles(file, { 'uploadType': "2", 'uploadPath': 'contract' },(e:any)=>{
            var err:any = []
            if (e){
              e.forEach((f:any) => {
                if(f.state!='SUCCESS'){
                  err.push(f.state)
                }else{
                  var url = setToUrl(f)
                  this.readAndParseXML(file.file,url)
                }
                
              });
              if  (err.length>0){
                showDialog({'message':err.join('\n')})
              }
            }
            
            

          })
        
      },
      readAndParseXML(file:any,fileurls:string){
  
        if (!file) {
            alert('Please select an XML file.');
            return;
        }

        const reader = new FileReader();
        reader.onload = (e:any)=>{
            const xmlString = e.target.result;
            this.parseXML(xmlString,fileurls);
        };
        reader.readAsText(file);
      },
      parseXML(xmlString:any,fileurls:string){
   
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(xmlString, "text/xml");

        var result:any ={}

        // 发票号码
        result.EIid = xmlDoc.getElementsByTagName("EIid")[0]?.textContent || '';
        // 发票类别
        result.GeneralOrSpecialVAT= xmlDoc.getElementsByTagName("GeneralOrSpecialVAT")[0]?.getElementsByTagName("LabelName")[0]?.textContent || ''
        // 开票日期
        result.RequestTime = xmlDoc.getElementsByTagName("RequestTime")[0]?.textContent || '';
        // 销售方识别号
        result.SellerIdNum = xmlDoc.getElementsByTagName("SellerIdNum")[0]?.textContent || '';
        // 销售方名称
        result.SellerName = xmlDoc.getElementsByTagName("SellerName")[0]?.textContent || '';
        result.SellerAddr = xmlDoc.getElementsByTagName("SellerAddr")[0]?.textContent || '';
        result.SellerTelNum = xmlDoc.getElementsByTagName("SellerTelNum")[0]?.textContent || '';
        result.SellerBankName = xmlDoc.getElementsByTagName("SellerBankName")[0]?.textContent || '';
        result.SellerBankAccNum = xmlDoc.getElementsByTagName("SellerBankAccNum")[0]?.textContent || '';
        // 购买方名称
        result.BuyerName = xmlDoc.getElementsByTagName("BuyerName")[0]?.textContent || '';
        // 购买方识别号
        result.BuyerIdNum = xmlDoc.getElementsByTagName("BuyerIdNum")[0]?.textContent || '';
        result.BuyerTelNum = xmlDoc.getElementsByTagName("BuyerTelNum")[0]?.textContent || '';
        result.BuyerAddr = xmlDoc.getElementsByTagName("BuyerAddr")[0]?.textContent || '';
        result.BuyerBankName = xmlDoc.getElementsByTagName("BuyerBankName")[0]?.textContent || '';
        result.BuyerBankAccNum = xmlDoc.getElementsByTagName("BuyerBankAccNum")[0]?.textContent || '';

        // 不含税开票金额
        result.TotalAmwithoutTax=xmlDoc.getElementsByTagName('TotalAmWithoutTax')[0]?.textContent || '';
        // 含税开票金额
        result.TotalTaxIncludedAmount=xmlDoc.getElementsByTagName('TotalTax-includedAmount')[0]?.textContent || '';
        // 税额
        result.TotalTaxAm=xmlDoc.getElementsByTagName('TotalTaxAm')[0]?.textContent || '';
        // 备注
        result.Remark=xmlDoc.getElementsByTagName('Remark')[0]?.textContent || '';
        // 开票项目
        result.IssuItemInformation = []
        var temp = xmlDoc.getElementsByTagName('IssuItemInformation');
        for (var i=0;i<temp.length;i++){
          var temp2:any = {}
            temp2.ItemName = temp[i].getElementsByTagName('ItemName')[0]?.textContent || '';
            temp2.SpecMod = temp[i].getElementsByTagName('SpecMod')[0]?.textContent || '';
            temp2.MeaUnits = temp[i].getElementsByTagName('MeaUnits')[0]?.textContent || '';
            temp2.Quantity = temp[i].getElementsByTagName('Quantity')[0]?.textContent || '';
            temp2.UnPrice = temp[i].getElementsByTagName('UnPrice')[0]?.textContent || '';
            temp2.Amount = temp[i].getElementsByTagName('Amount')[0]?.textContent || '';
            temp2.TaxRate = temp[i].getElementsByTagName('TaxRate')[0]?.textContent || '';
            temp2.ComTaxAm = temp[i].getElementsByTagName('ComTaxAm')[0]?.textContent || '';
            temp2.TotaltaxIncludedAmount = temp[i].getElementsByTagName('TotaltaxIncludedAmount')[0]?.textContent || '';
            temp2.TaxClassificationCode = temp[i].getElementsByTagName('TaxClassificationCode')[0]?.textContent || '';
            result.IssuItemInformation.push(temp2)
        }

        result.invoicingid=this.invoicingid
        result.fileurls = fileurls
        // 将对象obj的值全部赋给result
        Object.assign(result, this.obj)
        saveinvoice({obj:result}).then((res:any)=>{
          if (res.errorMessage){
            showDialog({message:res.errorMessage})
          }else{
            if(res.msg){
              showDialog({message:res.msg})
            }else{
              this.visible = false
              this.$emit('suc')
            }
            
          }
        })
      

      
        
      }

    }
  }
</script>

<style>


</style>
<style>
  @media screen and (min-width: 500px) {
       

 
  }
</style>